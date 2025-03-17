<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    header("Location: ../admin/login.php");
    exit();
}

include '../db/db_conn.php';

// Fetch the admin's ID
$adminId = $_SESSION['a_id'];

// Fetch the admin's info
$sql = "SELECT a_id, firstname, middlename, lastname, birthdate, email, role, department, phone_number, address, pfp FROM admin_register WHERE a_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$adminInfo = $result->fetch_assoc();

// Function to get evaluation progress by department for a specific admin
function getAdminEvaluationProgress($conn, $department, $adminId) {
    // Get total employees in the department
    $employeeQuery = "SELECT COUNT(*) as total FROM employee_register WHERE department = '$department'";
    $employeeResult = $conn->query($employeeQuery);
    $totalEmployees = $employeeResult->fetch_assoc()['total'];

    // Get total employees evaluated by the admin in the department
    $evaluatedQuery = "SELECT COUNT(*) as evaluated FROM evaluations WHERE department = '$department' AND a_id = '$adminId'";
    $evaluatedResult = $conn->query($evaluatedQuery);
    $evaluated = $evaluatedResult->fetch_assoc()['evaluated'];

    $pendingEmployees = $totalEmployees - $evaluated;

    return array('total' => $totalEmployees, 'evaluated' => $evaluated, 'pending' => $pendingEmployees);
}

// Fetch data for different departments for the logged-in admin
$financeData = getAdminEvaluationProgress($conn, 'Finance Department', $adminId);
$hrData = getAdminEvaluationProgress($conn, 'Human Resource Department', $adminId);
$administrationData = getAdminEvaluationProgress($conn, 'Administration Department', $adminId);
$salesData = getAdminEvaluationProgress($conn, 'Sales Department', $adminId);
$creditData = getAdminEvaluationProgress($conn, 'Credit Department', $adminId);
$itData = getAdminEvaluationProgress($conn, 'IT Department', $adminId);

// Check if it is the first week of the month
$currentDay = date('j'); // Current day of the month (1-31)
$isFirstWeek = ($currentDay <= 21); // First 15 days of the month

// Set the evaluation period to the previous month if it is the first week
if ($isFirstWeek) {
    $evaluationMonth = date('m', strtotime('last month')); // Previous month
    $evaluationYear = date('Y', strtotime('last month'));  // Year of the previous month
    $evaluationPeriod = date('F Y', strtotime('last month')); // Format: February 2024

    // Calculate the end date of the evaluation period (15th day of the current month)
    $evaluationEndDate = date('F j, Y', strtotime(date('Y-m-21'))); // Format: March 15, 2024
} else {
    // If it is not the first week, evaluations are closed
    $evaluationMonth = null;
    $evaluationYear = null;
    $evaluationPeriod = null;
    $evaluationEndDate = null;
}

// Define departments array with icons and IDs
$departments = [
    ['id' => 'finance', 'name' => 'Finance Department', 'icon' => 'fa-chart-line'],
    ['id' => 'hr', 'name' => 'Human Resource Department', 'icon' => 'fa-users'],
    ['id' => 'admin', 'name' => 'Administration Department', 'icon' => 'fa-building'],
    ['id' => 'sales', 'name' => 'Sales Department', 'icon' => 'fa-shopping-cart'],
    ['id' => 'credit', 'name' => 'Credit Department', 'icon' => 'fa-credit-card'],
    ['id' => 'it', 'name' => 'IT Department', 'icon' => 'fa-laptop-code']
];

// Function to get employees by department
function getEmployeesByDepartment($conn, $department) {
    $position = 'employee';
    $sql = "SELECT employee_id, first_name, last_name, role, position, department FROM employee_register WHERE position = ? AND department = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $position, $department);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $employees = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
    }
    return $employees;
}

// Get evaluated employees
$evaluatedEmployees = [];
$evalSql = "SELECT employee_id FROM evaluations WHERE a_id = ?";
$evalStmt = $conn->prepare($evalSql);
$evalStmt->bind_param('i', $adminId);
$evalStmt->execute();
$evalResult = $evalStmt->get_result();
if ($evalResult->num_rows > 0) {
    while ($row = $evalResult->fetch_assoc()) {
        $evaluatedEmployees[] = $row['employee_id'];
    }
}

// Get department data
$departmentData = [];
foreach ($departments as $dept) {
    $deptStats = null;
    switch ($dept['name']) {
        case 'Finance Department':
            $deptStats = $financeData;
            break;
        case 'Human Resource Department':
            $deptStats = $hrData;
            break;
        case 'Administration Department':
            $deptStats = $administrationData;
            break;
        case 'Sales Department':
            $deptStats = $salesData;
            break;
        case 'Credit Department':
            $deptStats = $creditData;
            break;
        case 'IT Department':
            $deptStats = $itData;
            break;
    }
    
    $departmentData[$dept['name']] = [
        'employees' => getEmployeesByDepartment($conn, $dept['name']),
        'stats' => $deptStats
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation Dashboard | HR2</title>
    <link href="../css/styles.css" rel="stylesheet">
    <link href="../css/star.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../css/calendar.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-hover: #3a0ca3;
            --secondary: #4cc9f0;
            --success: #4ade80;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --bg-dark: rgba(33, 37, 41) !important;
            --card-bg: rgba(33, 37, 41) !important;
            --bg-black: rgba(16, 17, 18) !important;
            --text-light: #f8f9fa;
            --text-muted: #adb5bd;
            --border-color: #333;
            --gradient-start: #4361ee;
            --gradient-end: #3a0ca3;
        }
        
        body {
            background-color: var(--bg-black);
            color: var(--text-light);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .navbar-dark {
            background-color: var(--card-bg) !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .evaluation-status {
            padding: 0.75rem 1.25rem;
            border-radius: 50px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .evaluation-status.active {
            background-color: var(--warning);
            color: #000;
        }
        
        .evaluation-status.closed {
            background-color: var(--danger);
            color: white;
        }
        
        /* Department Navigation */
        .department-nav {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 70px;
            z-index: 100;
        }
        
        .nav-tabs {
            border-bottom: none;
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) var(--dark-light);
            gap: 0.5rem;
            padding-bottom: 0.5rem;
        }
        
        .nav-tabs::-webkit-scrollbar {
            height: 5px;
        }
        
        .nav-tabs::-webkit-scrollbar-track {
            background: var(--dark-light);
            border-radius: 10px;
        }
        
        .nav-tabs::-webkit-scrollbar-thumb {
            background-color: var(--primary);
            border-radius: 10px;
        }
        
        .nav-tabs .nav-link {
            color: var(--text-light);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.25rem;
            margin: 0 0.25rem;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }
        
        .nav-tabs .nav-link:hover {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }
        
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .department-icon {
            font-size: 1.2rem;
        }
        
        .badge-notification {
            background-color: var(--danger);
            color: white;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
        }
        
        /* Department Content */
        .tab-content {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .tab-pane {
            display: none;
        }
        
        .tab-pane.active {
            display: block;
        }
        
        /* Status Cards */
        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .status-card {
            background: rgba(33, 37, 41) !important;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
        
        .status-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .status-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            line-height: 1;
        }
        
        .status-label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .status-total .status-icon {
            color: var(--info);
        }
        
        .status-evaluated .status-icon {
            color: var(--success);
        }
        
        .status-pending .status-icon {
            color: var(--danger);
        }
        
        /* Progress Bar */
        .progress-container {
            background: rgba(33, 37, 41) !important;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .progress {
            height: 1.5rem;
            background-color: var(--dark-light);
            border-radius: 8px;
            margin: 1rem 0;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .progress-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.3);
            transition: width 0.6s ease;
        }
        
        /* Employee Table */
        .employee-table-container {
            background: rgba(33, 37, 41) !important;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .employee-table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .employee-search {
            position: relative;
            max-width: 300px;
        }
        
        .employee-search input {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 50px;
            color: var(--text-light);
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .employee-search input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.3);
        }
        
        .employee-search i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        
        .table {
            color: var(--text-light);
            border-color: var(--border-color);
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: rgba(255, 255, 255, 0.05);
            border-color: var(--border-color);
            color: var(--text-light);
            padding: 1rem;
            font-weight: 600;
        }
        
        .table tbody td {
            border-color: var(--border-color);
            padding: 1rem;
            vertical-align: middle;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .employee-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 0.75rem;
        }
        
        .badgeT {
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .badge-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .badge-success {
            background: linear-gradient(135deg, #4ade80, #22c55e);
            color: white;
        }
        
        .btn-evaluate {
            min-width: 100px;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--gradient-end), var(--gradient-start));
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.2);
        }
        
        .btn-outline-success {
            color: var(--success);
            border-color: var(--success);
            background-color: transparent;
        }
        
        .btn-outline-success:hover {
            background-color: var(--success);
            color: white;
        }
        
        /* Evaluation Period Notice */
        .evaluation-notice {
            background: rgba(33, 37, 41) !important;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .evaluation-notice.active {
            border-left: 4px solid var(--warning);
        }
        
        .evaluation-notice.closed {
            border-left: 4px solid var(--danger);
        }
        
        .notice-icon {
            font-size: 2rem;
        }
        
        .evaluation-notice.active .notice-icon {
            color: var(--warning);
        }
        
        .evaluation-notice.closed .notice-icon {
            color: var(--danger);
        }
        
        /* Modal Styles */
        .modal-content {
            background-color: var(--card-bg);
            border: none;
            border-radius: 12px;
           
        }
        
        .modal-header, .modal-footer {
            border-color: var(--border-color);
        }
        
        .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        
        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .status-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .evaluation-status {
                align-self: flex-start;
            }
            
            .status-cards {
                grid-template-columns: 1fr;
            }
            
            .employee-table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .employee-search {
                width: 100%;
                max-width: none;
            }
            
            .nav-tabs .nav-link {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid" id="calendarContainer" 
                    style="position: fixed; top: 7%; right: 40; z-index: 1050; 
                    max-width: 100%; display: none;">
                    <div class="row">
                        <div class="col-md-9 mx-auto">
                            <div id="calendar" class="p-2"></div>
                        </div>
                    </div>
                </div> 
                <div class="container-fluid px-4 py-4">
                    <!-- Page Header -->
                    <div class="page-header">
                        <h1 class="page-title">Employee Evaluation Dashboard</h1>
                        
                        <?php if ($isFirstWeek): ?>
                            <div class="evaluation-status active">
                                <i class="fas fa-clock text"></i>
                                <span>Evaluation Period: <?php echo $evaluationPeriod; ?> (Ends <?php echo $evaluationEndDate; ?>)</span>
                            </div>
                        <?php else: ?>
                            <div class="evaluation-status closed">
                                <i class="fas fa-lock"></i>
                                <span>Evaluations Closed</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Department Navigation -->
                    <div class="department-nav d-flex justify-content-center">
                        <ul class="nav nav-tabs" id="departmentTabs" role="tablist">
                            <?php foreach ($departments as $index => $dept): 
                                $deptData = $departmentData[$dept['name']]['stats'];
                            ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" 
                                            id="<?php echo $dept['id']; ?>-tab" 
                                            data-bs-toggle="tab" 
                                            data-bs-target="#<?php echo $dept['id']; ?>" 
                                            type="button" 
                                            role="tab" 
                                            aria-controls="<?php echo $dept['id']; ?>" 
                                            aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>">
                                        <i class="fas <?php echo $dept['icon']; ?> department-icon"></i>
                                        <span><?php echo explode(' ', $dept['name'])[0]; ?></span>
                                        <?php if ($deptData['pending'] > 0): ?>
                                            <span class="badge-notification"><?php echo $deptData['pending']; ?></span>
                                        <?php endif; ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- Department Content -->
                    <div class="tab-content" id="departmentTabsContent">
                        <?php foreach ($departments as $index => $dept): 
                            $deptData = $departmentData[$dept['name']];
                            $stats = $deptData['stats'];
                            $employees = $deptData['employees'];
                            $isActive = $index === 0 ? 'show active' : '';
                        ?>
                        <div class="tab-pane fade <?php echo $isActive; ?>" id="<?php echo $dept['id']; ?>" role="tabpanel" aria-labelledby="<?php echo $dept['id']; ?>-tab">
                            <!-- Evaluation Period Notice -->
                            <div class="evaluation-notice <?php echo $isFirstWeek ? 'active' : 'closed'; ?>">
                                <i class="fas <?php echo $isFirstWeek ? 'fa-info-circle' : 'fa-exclamation-triangle'; ?> notice-icon"></i>
                                <div>
                                    <h5 class="mb-1"><?php echo $isFirstWeek ? 'Evaluation Period Open' : 'Evaluations Closed'; ?></h5>
                                    <p class="mb-0">
                                        <?php echo $isFirstWeek 
                                            ? "Evaluation is open for $evaluationPeriod until $evaluationEndDate." 
                                            : "Evaluations are closed. They will open in the first 15 days of the next month."; 
                                        ?>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Status Cards -->
                            <div class="status-cards">
                                <div class="status-card status-total">
                                    <i class="fas fa-users status-icon"></i>
                                    <div class="status-value"><?php echo $stats['total']; ?></div>
                                    <div class="status-label">Total Employees</div>
                                </div>
                                <div class="status-card status-evaluated">
                                    <i class="fas fa-check-circle status-icon"></i>
                                    <div class="status-value"><?php echo $stats['evaluated']; ?></div>
                                    <div class="status-label">Evaluated</div>
                                </div>
                                <div class="status-card status-pending">
                                    <i class="fas fa-clock status-icon"></i>
                                    <div class="status-value"><?php echo $stats['pending']; ?></div>
                                    <div class="status-label">Pending</div>
                                </div>
                            </div>
                            
                            <!-- Progress Bar -->
                            <div class="progress-container">
                                <h5 class="mb-3">Evaluation Progress</h5>
                                <?php if ($stats['total'] > 0): ?>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                            style="width: <?php echo ($stats['evaluated'] / $stats['total']) * 100; ?>%;" 
                                            aria-valuenow="<?php echo $stats['evaluated']; ?>" 
                                            aria-valuemin="0" 
                                            aria-valuemax="<?php echo $stats['total']; ?>">
                                            <?php echo $stats['evaluated']; ?> Evaluated
                                        </div>
                                        <div class="progress-bar bg-danger" role="progressbar" 
                                            style="width: <?php echo ($stats['pending'] / $stats['total']) * 100; ?>%;" 
                                            aria-valuenow="<?php echo $stats['pending']; ?>" 
                                            aria-valuemin="0" 
                                            aria-valuemax="<?php echo $stats['total']; ?>">
                                            <?php echo $stats['pending']; ?> Pending
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2">
                                        <small class="text-success"><?php echo round(($stats['evaluated'] / $stats['total']) * 100); ?>% Complete</small>
                                        <small class="text-danger"><?php echo round(($stats['pending'] / $stats['total']) * 100); ?>% Pending</small>
                                    </div>
                                <?php else: ?>
                                    <div class="progress">
                                        <div class="progress-bar bg-secondary w-100" role="progressbar">
                                            No employees available
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Employee Table -->
                            <div class="employee-table-container">
                                <div class="employee-table-header">
                                    <h5 class="mb-0">
                                        <i class="fas <?php echo $dept['icon']; ?> me-2"></i>
                                        <?php echo $dept['name']; ?> Employees
                                    </h5>
                                    <div class="employee-search">
                                        <i class="fas fa-search"></i>
                                        <input type="text" placeholder="Search employees..." onkeyup="searchEmployees(this, '<?php echo $dept['id']; ?>')">
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover" id="<?php echo $dept['id']; ?>-table">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Position</th>
                                                <th>Role</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($employees)): ?>
                                                <?php foreach ($employees as $employee): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="employee-avatar">
                                                                    <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                                                                </div>
                                                                <span><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></span>
                                                            </div>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($employee['role']); ?></td>
                                                        <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                                        <td class="text-center">
                                                            <?php if (in_array($employee['employee_id'], $evaluatedEmployees)): ?>
                                                                <span class="badgeT badge-success">
                                                                    <i class="fas fa-check-circle me-1"></i> Evaluated
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badgeT badge-danger">
                                                                    <i class="fas fa-clock me-1"></i> Pending
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <button class="btn btn-sm <?php echo in_array($employee['employee_id'], $evaluatedEmployees) ? 'btn-outline-success' : 'btn-primary'; ?> btn-evaluate" 
                                                                onclick="evaluateEmployee(
                                                                    <?php echo $employee['employee_id']; ?>, 
                                                                    '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>', 
                                                                    '<?php echo htmlspecialchars($employee['role']); ?>', 
                                                                    '<?php echo htmlspecialchars($employee['department']); ?>'
                                                                )"
                                                                <?php echo !$isFirstWeek || in_array($employee['employee_id'], $evaluatedEmployees) ? 'disabled' : ''; ?>>
                                                                <?php echo in_array($employee['employee_id'], $evaluatedEmployees) ? '<i class="fas fa-check me-1"></i> Done' : '<i class="fas fa-star me-1"></i> Evaluate'; ?>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No employees found for evaluation in <?php echo $dept['name']; ?>.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Evaluation Modal -->
                <div class="modal fade" id="evaluationModal" tabindex="-1" role="dialog" aria-labelledby="evaluationModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="employeeDetails"></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" id="a_id" value="<?php echo $_SESSION['a_id']; ?>">
                                <div id="questions"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </button>
                                <button type="button" class="btn btn-primary" onclick="submitEvaluation()">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Evaluation
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Logout Modal -->
                <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center mb-3">
                                    <i class="fas fa-sign-out-alt text-warning fs-1 mb-3"></i>
                                    <p>Are you sure you want to log out?</p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <form action="../admin/logout.php" method="POST">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Toast Container for Notifications -->
                <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>
            </main>
            <?php include 'footer.php'; ?>
        </div>
    </div>
    
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../js/admin.js"></script>
    <script>
        let currentEmployeeId;
        let currentEmployeeName;  
        let currentEmployeeRole; 
        let currentDepartment;

        // Function to fetch questions based on the evaluated employee's position
        async function fetchQuestions(role) {
            try {
                const response = await fetch(`../db/fetchQuestions.php?role=${role}`);
                return await response.json();
            } catch (error) {
                console.error('Error fetching questions:', error);
                showToast('Error', 'Failed to fetch evaluation questions', 'danger');
                return {};
            }
        }

        async function evaluateEmployee(employee_id, employeeName, employeeRole, department) {
            currentEmployeeId = employee_id; 
            currentEmployeeName = employeeName; 
            currentEmployeeRole = employeeRole; 
            currentDepartment = department;

            // Show loading state
            document.getElementById('employeeDetails').innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm text-primary me-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span>Loading evaluation form...</span>
                </div>
            `;
            
            const evaluationModal = new bootstrap.Modal(document.getElementById('evaluationModal'));
            evaluationModal.show();

            // Fetch questions based on the evaluated employee's position
            const questions = await fetchQuestions(employeeRole);

            const employeeDetails = `
                <div class="d-flex align-items-center">
                    <div class="employee-avatar me-3" style="width: 50px; height: 50px; font-size: 1.2rem;">
                        ${employeeName.split(' ')[0][0]}${employeeName.split(' ')[1][0]}
                    </div>
                    <div>
                        <h5 class="mb-1">${employeeName}</h5>
                        <p class="mb-0 text-muted">${employeeRole} - ${department}</p>
                    </div>
                </div>
            `;
            
            document.getElementById('employeeDetails').innerHTML = employeeDetails;

            const questionsDiv = document.getElementById('questions');
            questionsDiv.innerHTML = ''; 

            // Start the table structure
            let tableHtml = `
            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle me-2"></i>
                Please rate the employee on a scale of 1-6 for each question (6 being the highest).
            </div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th width="20%">Category</th>
                        <th width="50%">Question</th>
                        <th width="30%">Rating</th>
                    </tr>
                </thead>
                <tbody>`;

            // Loop through categories and questions to add them into the table
            for (const [category, categoryQuestions] of Object.entries(questions)) {
                categoryQuestions.forEach((question, index) => {
                    const questionName = `${category.replace(/\s/g, '')}q${index}`; // Unique name per question
                    tableHtml += `
                    <tr>
                        <td class="align-middle">${index === 0 ? `<strong>${category}</strong>` : ''}</td>
                        <td class="align-middle">${question}</td>
                        <td>
                            <div class="star-rating">
                                ${[6, 5, 4, 3, 2, 1].map(value => `
                                    <input type="radio" name="${questionName}" value="${value}" id="${questionName}star${value}">
                                    <label for="${questionName}star${value}" title="${value} stars">&#9733;</label>
                                `).join('')}
                            </div>
                        </td>
                    </tr>`;
                });
            }

            // Close the table structure
            tableHtml += `
                </tbody>
            </table>`;

            questionsDiv.innerHTML = tableHtml;
        }

        function submitEvaluation() {
            const evaluations = [];
            const questionsDiv = document.getElementById('questions');

            questionsDiv.querySelectorAll('input[type="radio"]:checked').forEach(input => {
                evaluations.push({
                    question: input.name,  
                    rating: input.value    
                });
            });

            const totalQuestions = questionsDiv.querySelectorAll('.star-rating').length;

            if (evaluations.length !== totalQuestions) {
                showToast('Warning', 'Please complete the evaluation before submitting.', 'warning');
                return;
            }

            const categoryAverages = {
                QualityOfWork: calculateAverage('QualityofWork', evaluations),
                CommunicationSkills: calculateAverage('CommunicationSkills', evaluations),
                Teamwork: calculateAverage('Teamwork', evaluations),
                Punctuality: calculateAverage('Punctuality', evaluations),
                Initiative: calculateAverage('Initiative', evaluations)
            };

            const adminId = document.getElementById('a_id').value;

            // Show loading indicator
            const submitBtn = document.querySelector('#evaluationModal .btn-primary');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Submitting...';
            submitBtn.disabled = true;

            $.ajax({
                type: 'POST',
                url: '../db/submit_evaluation.php',
                data: {
                    employee_id: currentEmployeeId,
                    employeeName: currentEmployeeName,
                    employeeRole: currentEmployeeRole,
                    categoryAverages: categoryAverages,
                    adminId: adminId,
                    department: currentDepartment
                },
                success: function (response) {
                    // Hide modal
                    const evaluationModal = bootstrap.Modal.getInstance(document.getElementById('evaluationModal'));
                    evaluationModal.hide();
                    
                    // Show success message
                    if (response === 'You have already evaluated this employee.') {
                        showToast('Warning', response, 'warning');
                    } else {
                        showToast('Success', 'Evaluation submitted successfully!', 'success');
                        
                        // Refresh the page after a short delay
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    }
                },
                error: function (err) {
                    console.error(err);
                    showToast('Error', 'An error occurred while submitting the evaluation.', 'danger');
                    
                    // Reset button
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                }
            });
        }

        function calculateAverage(category, evaluations) {
            const categoryEvaluations = evaluations.filter(evaluation => evaluation.question.startsWith(category));

            if (categoryEvaluations.length === 0) {
                return 0; 
            }

            const total = categoryEvaluations.reduce((sum, evaluation) => sum + parseInt(evaluation.rating), 0);
            return total / categoryEvaluations.length;
        }
        
        function showToast(title, message, type) {
            // Create toast container if it doesn't exist
            const toastContainer = document.querySelector('.toast-container');
            
            // Create toast
            const toastId = 'toast-' + Date.now();
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.id = toastId;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}:</strong> ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            
            // Initialize and show toast
            const bsToast = new bootstrap.Toast(toast, {
                autohide: true,
                delay: 5000
            });
            bsToast.show();
            
            // Remove toast after it's hidden
            toast.addEventListener('hidden.bs.toast', function () {
                toast.remove();
            });
        }
        
        // Search functionality for employee tables
        function searchEmployees(input, deptId) {
            const filter = input.value.toUpperCase();
            const table = document.getElementById(`${deptId}-table`);
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) { // Start from 1 to skip header row
                const nameCell = rows[i].getElementsByTagName('td')[0];
                const positionCell = rows[i].getElementsByTagName('td')[1];
                const roleCell = rows[i].getElementsByTagName('td')[2];
                
                if (nameCell && positionCell && roleCell) {
                    const nameText = nameCell.textContent || nameCell.innerText;
                    const positionText = positionCell.textContent || positionCell.innerText;
                    const roleText = roleCell.textContent || roleCell.innerText;
                    
                    if (nameText.toUpperCase().indexOf(filter) > -1 || 
                        positionText.toUpperCase().indexOf(filter) > -1 || 
                        roleText.toUpperCase().indexOf(filter) > -1) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        }
    </script>
</body>
</html>

