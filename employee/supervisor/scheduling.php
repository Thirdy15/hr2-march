<?php
session_start(); // Start the session

// Include database connection
include '../../db/db_conn.php';

// Check if the user is logged in
if (!isset($_SESSION['employee_id'])) {
    header("Location: ../../login.php"); // Redirect to login if not logged in
    exit();
}

// Number of records to show per page
$recordsPerPage = 10;

// Get the current page or set a default
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $recordsPerPage;

// Fetch logged-in employee info
$employeeId = $_SESSION['employee_id'];
$sql = "SELECT employee_id, first_name, middle_name, last_name, birthdate, email, role, position, department, phone_number, address, pfp FROM employee_register WHERE employee_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$employeeInfo = $result->fetch_assoc();
$stmt->close();

// Set the profile picture, default if not provided
$profilePicture = !empty($employeeInfo['pfp']) ? $employeeInfo['pfp'] : '../../img/defaultpfp.png';

// Fetch total number of employees for pagination
$totalQuery = "SELECT COUNT(*) as total FROM employee_register";
$totalResult = $conn->query($totalQuery);
$totalEmployees = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalEmployees / $recordsPerPage);

// Fetch all employees with pagination
$query = "SELECT * FROM employee_register LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $recordsPerPage, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Fetch notifications for the employee
$notificationQuery = "SELECT * FROM notifications WHERE employee_id = ? ORDER BY created_at DESC";
$notificationStmt = $conn->prepare($notificationQuery);
$notificationStmt->bind_param("s", $employeeId);
$notificationStmt->execute();
$notifications = $notificationStmt->get_result();

// Get department distribution for chart
$deptQuery = "SELECT department, COUNT(*) as count FROM employee_register GROUP BY department";
$deptResult = $conn->query($deptQuery);
$departments = [];
$deptCounts = [];
while ($row = $deptResult->fetch_assoc()) {
    $departments[] = $row['department'];
    $deptCounts[] = $row['count'];
}

// Get shift type distribution for chart
$shiftQuery = "SELECT shift_type, COUNT(*) as count FROM employee_schedule GROUP BY shift_type";
$shiftResult = $conn->query($shiftQuery);
$shiftTypes = [];
$shiftCounts = [];
while ($row = $shiftResult->fetch_assoc()) {
    $shiftTypes[] = $row['shift_type'] ? $row['shift_type'] : 'Unassigned';
    $shiftCounts[] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Schedule Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='../../css/styles.css' rel='stylesheet' />
    <link href='../../css/calendar.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #0466c8;
            --secondary-color: #0353a4;
            --accent-color: #48cae4;
            --success-color: #38b000;
            --warning-color: #ffb703;
            --danger-color: #d00000;
            --dark-bg: #0a0908;
            --card-bg: rgba(33, 37, 41) !important;
            --border-color: #333333;
            --grid-color: rgba(255, 255, 255, 0.05);
            --text-primary: #ffffff;
            --text-secondary: #adb5bd;
            --text-muted: #6c757d;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            font-family: 'Inter', 'Segoe UI', sans-serif;
            line-height: 1.6;
            min-height: 100vh;
        }

        .container-fluid {
            padding: 1.5rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .page-title i {
            margin-right: 0.75rem;
            color: var(--primary-color);
        }

        .dashboard-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .dashboard-card .card-body {
        padding: 20px;
    }

        .card-header {
            background-color: rgba(0, 0, 0, 0.2);
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            color: var(--primary-color);
        }

        .card-body {
            padding: 1.25rem;
        }

        .chart-container {
            background-color: #141414;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            position: relative;
            height: 300px;
        }

        .chart-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 20px 20px;
            pointer-events: none;
            border-radius: 6px;
        }

        .chart-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1rem;
            color: var(--text-primary);
            text-align: center;
        }

        .chart-subtitle {
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-align: center;
            margin-bottom: 1.25rem;
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1rem;
        }

        .data-table th {
            background-color: rgba(0, 0, 0, 0.3);
            color: var(--text-primary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
        }

        .data-table td {
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            font-size: 0.9rem;
        }

        .data-table tr:nth-child(odd) td {
            background-color: rgba(255, 255, 255, 0.02);
        }

        .data-table tr:hover td {
            background-color: rgba(4, 102, 200, 0.05);
        }

        .badge-shift {
            display: inline-block;
            padding: 0.35rem 0.65rem;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.85rem;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
        }

        .badge-day {
            background-color: rgba(72, 202, 228, 0.15);
            color: #48cae4;
        }

        .badge-night {
            background-color: rgba(3, 83, 164, 0.15);
            color: #0353a4;
        }

        .btn-action {
            padding: 0.375rem 0.75rem;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.875rem;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
        }

        .btn-edit {
            background-color: rgba(4, 102, 200, 0.15);
            color: var(--primary-color);
            border: 1px solid rgba(4, 102, 200, 0.3);
        }

        .btn-edit:hover {
            background-color: rgba(4, 102, 200, 0.25);
            color: var(--primary-color);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-outline-primary {
            background-color: transparent;
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .search-container {
            position: relative;
            max-width: 300px;
        }

        .search-input {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-primary);
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            width: 100%;
            transition: all 0.2s ease;
        }

        .search-input:focus {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(4, 102, 200, 0.25);
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            pointer-events: none;
        }

        .avatar-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
            margin-right: 0.75rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }

        .pagination .page-item .page-link {
            background-color: rgba(255, 255, 255, 0.05);
            border-color: var(--border-color);
            color: var(--text-primary);
            padding: 0.5rem 0.75rem;
            margin: 0 0.25rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .pagination .page-item .page-link:hover:not(.active) {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .pagination .page-item.disabled .page-link {
            background-color: rgba(255, 255, 255, 0.02);
            color: var(--text-muted);
            pointer-events: none;
        }

        .modal-content {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem;
        }

        .modal-title {
            color: var(--primary-color);
            font-weight: 600;
        }

        .modal-body {
            padding: 1.25rem;
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 1.25rem;
        }

        .form-label {
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-primary);
            padding: 0.5rem 0.75rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(4, 102, 200, 0.25);
        }

        .form-select option {
            background-color: var(--card-bg);
            color: var(--text-primary);
        }

        .btn-close {
            color: var(--text-primary);
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }

        .btn-close:hover {
            opacity: 1;
        }

        .employee-select-container {
            background-color: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color) !important;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
        }

        .form-check-input {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-label {
            color: var(--text-primary);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background-color: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .stat-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            height: 40px;
            width: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: rgba(4, 102, 200, 0.15);
            color: var(--primary-color);
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            font-family: 'Courier New', monospace;
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .chart-legend {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
            margin-right: 0.5rem;
        }

        .chart-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .chart-footer .timestamp {
            font-family: 'Courier New', monospace;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--card-bg);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container-fluid {
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .chart-container {
                height: 250px;
            }
        }

        /* Calendar container styling */
        #calendarContainer {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            padding: 1.25rem;
        }
    </style>
</head>
<body class="sb-nav-fixed bg-black">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid" id="calendarContainer" 
                    style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1050; 
                    width: 80%; height: 80%; display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="m-0 text-primary">Schedule Calendar</h3>
                        <button type="button" class="btn-close btn-close-white" id="closeCalendar"></button>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div id="calendar" class="p-2"></div>
                        </div>
                    </div>
                </div> 
                
                <div class="container-fluid position-relative">
                    <div class="page-header">
                        <h1 class="page-title">
                            <i class="fas fa-calendar-alt"></i>
                            Employee Schedule Management
                        </h1>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary" id="calendarToggle">
                                <i class="fas fa-calendar-week me-2"></i>View Calendar
                            </button>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bulkEditModal">
                                <i class="fas fa-users-cog me-2"></i>Bulk Edit Schedules
                            </button>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="dashboard-card">
                                <div class="card-header">
                                    <h2><i class="fas fa-chart-bar me-2"></i>Schedule Analytics</h2>
                                </div>
                                <div class="card-body">
                                    <div class="chart-title">Employee Schedule Distribution</div>
                                    <div class="chart-subtitle">Analysis of current scheduling patterns across departments and shift types</div>
                                    
                                    <div class="chart-container">
                                        <canvas id="scheduleChart"></canvas>
                                    </div>

                                    <canvas height="160"></canvas>
                                    
                                    <div class="chart-footer">
                                        <div>
                                            <i class="fas fa-info-circle me-1 active"></i>
                                            Data based on current employee schedules
                                        </div>
                                        <div class="timestamp">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('Y-m-d H:i:s'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="dashboard-card">
                                <div class="card-header">
                                    <h2><i class="fas fa-chart-pie me-2"></i>Shift Distribution</h2>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="shiftChart"></canvas>
                                    </div>
                                    
                                    <div class="stats-grid mt-3">
                                        <div class="stat-card">
                                            <div class="stat-icon">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <div class="stat-value"><?php echo $totalEmployees; ?></div>
                                            <div class="stat-label">Total Employees</div>
                                        </div>
                                        
                                        <div class="stat-card">
                                            <div class="stat-icon" style="background-color: rgba(72, 202, 228, 0.15); color: #48cae4;">
                                                <i class="fas fa-sun"></i>
                                            </div>
                                            <div class="stat-value"><?php echo isset($shiftCounts[0]) && isset($shiftTypes[0]) && $shiftTypes[0] == 'day' ? $shiftCounts[0] : 0; ?></div>
                                            <div class="stat-label">Day Shifts</div>
                                        </div>
                                        
                                        <div class="stat-card">
                                            <div class="stat-icon" style="background-color: rgba(3, 83, 164, 0.15); color: #0353a4;">
                                                <i class="fas fa-moon"></i>
                                            </div>
                                            <div class="stat-value"><?php echo isset($shiftCounts[1]) && isset($shiftTypes[1]) && $shiftTypes[1] == 'night' ? $shiftCounts[1] : 0; ?></div>
                                            <div class="stat-label">Night Shifts</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h2><i class="fas fa-table me-2"></i>Employee Schedule Overview</h2>
                            <div class="d-flex gap-2">
                                <div class="search-container">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" class="search-input" id="searchInput" placeholder="Search employee...">
                                </div>
                                <button class="btn btn-outline-primary" id="refreshButton">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="scheduleTable">
                                <thead>
                                    <tr>
                                        <th width="5%">ID</th>
                                        <th width="25%">Employee</th>
                                        <th width="15%">Shift Type</th>
                                        <th width="15%">Date</th>
                                        <th width="15%">Start Time</th>
                                        <th width="15%">End Time</th>
                                        <th width="10%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($employee = $result->fetch_assoc()): ?>
                                            <?php
                                            // Fetch the employee's schedule
                                            $scheduleQuery = "SELECT * FROM employee_schedule WHERE employee_id = ? ORDER BY schedule_date DESC LIMIT 1";
                                            $stmt = $conn->prepare($scheduleQuery);
                                            $stmt->bind_param('s', $employee['employee_id']);
                                            $stmt->execute();
                                            $scheduleResult = $stmt->get_result();
                                            $schedule = $scheduleResult->fetch_assoc();
                                            $stmt->close();

                                            // Determine shift badge class
                                            $shiftType = $schedule['shift_type'] ?? 'day';
                                            $badgeClass = ($shiftType == 'night') ? 'badge-night' : 'badge-day';
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-circle">
                                                            <?php echo strtoupper(substr($employee['first_name'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <div><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></div>
                                                            <div class="text-muted small"><?php echo htmlspecialchars($employee['department']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge-shift <?php echo $badgeClass; ?>">
                                                        <?php if ($shiftType == 'night'): ?>
                                                            <i class="fas fa-moon me-1"></i>
                                                        <?php else: ?>
                                                            <i class="fas fa-sun me-1"></i>
                                                        <?php endif; ?>
                                                        <?php echo ucfirst(htmlspecialchars($schedule['shift_type'] ?? 'Day')); ?> Shift
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (isset($schedule['schedule_date'])): ?>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-calendar-day me-2 text-primary"></i>
                                                            <?php echo htmlspecialchars($schedule['schedule_date']); ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not Scheduled</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (isset($schedule['start_time'])): ?>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-hourglass-start me-2 text-success"></i>
                                                            <?php echo htmlspecialchars($schedule['start_time']); ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (isset($schedule['end_time'])): ?>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-hourglass-end me-2 text-danger"></i>
                                                            <?php echo htmlspecialchars($schedule['end_time']); ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn-action btn-edit"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editModal"
                                                            data-employee-id="<?php echo $employee['employee_id']; ?>"
                                                            data-employee-name="<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>">
                                                        <i class="fas fa-edit me-1"></i> Edit
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7">
                                                <div class="text-center py-4">
                                                    <i class="fas fa-users-slash fa-3x mb-3 text-muted"></i>
                                                    <p class="mb-0">No employees found in the system.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <li class="page-item <?php echo $currentPage == 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $currentPage == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $currentPage == $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </main>

            <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-titleT" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-sign-out-alt text-warning" style="font-size: 3rem;"></i>
                        <p class="mt-3">Are you sure you want to log out?</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="../../employee/logout.php" method="POST">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit Employee Schedule
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editForm" method="POST" action="../../employee/supervisor/updateSchedule.php">
                    <div class="modal-body">
                        <input type="hidden" id="editEmployeeId" name="employee_id">

                        <div class="mb-3">
                            <label class="form-label">Employee:</label>
                            <div class="employee-name fw-bold" id="employeeName"></div>
                        </div>

                        <div class="mb-3">
                            <label for="editShiftType" class="form-label">Shift Type:</label>
                            <select class="form-select" id="editShiftType" name="shift_type" required>
                                <option value="day">Day Shift</option>
                                <option value="night">Night Shift</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="editScheduleDate" class="form-label">Schedule Date:</label>
                            <input type="date" class="form-control" id="editScheduleDate" name="schedule_date" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editStartTime" class="form-label">Start Time:</label>
                                <input type="time" class="form-control" id="editStartTime" name="start_time" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="editEndTime" class="form-label">End Time:</label>
                                <input type="time" class="form-control" id="editEndTime" name="end_time" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Edit Modal -->
    <div class="modal fade" id="bulkEditModal" tabindex="-1" aria-labelledby="bulkEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkEditModalLabel">
                        <i class="fas fa-users-cog me-2"></i>Bulk Edit Employee Schedules
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="bulkEditForm" method="POST" action="../../employee/supervisor/bulkUpdateSchedule.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Employees:</label>
                            <div class="employee-select-container p-3">
                                <?php
                                // Reset the result pointer
                                $result->data_seek(0);
                                while ($employee = $result->fetch_assoc()):
                                ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="employee_ids[]" value="<?php echo $employee['employee_id']; ?>" id="employee<?php echo $employee['employee_id']; ?>">
                                    <label class="form-check-label" for="employee<?php echo $employee['employee_id']; ?>">
                                        <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                    </label>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="bulkShiftType" class="form-label">Shift Type:</label>
                            <select class="form-select" id="bulkShiftType" name="shift_type" required>
                                <option value="day">Day Shift</option>
                                <option value="night">Night Shift</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="bulkScheduleDate" class="form-label">Schedule Date:</label>
                                <input type="date" class="form-control" id="bulkScheduleDate" name="schedule_date" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="bulkStartTime" class="form-label">Start Time:</label>
                                <input type="time" class="form-control" id="bulkStartTime" name="start_time" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="bulkEndTime" class="form-label">End Time:</label>
                                <input type="time" class="form-control" id="bulkEndTime" name="end_time" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check me-1"></i>Apply to Selected
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src="../../js/employee.js"></script>

    <script>
        // Initialize charts and UI components
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize department distribution chart
            const deptCtx = document.getElementById('scheduleChart').getContext('2d');
            const deptChart = new Chart(deptCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($departments); ?>,
                    datasets: [{
                        label: 'Employees per Department',
                        data: <?php echo json_encode($deptCounts); ?>,
                        backgroundColor: [
                            'rgba(4, 102, 200, 0.7)',
                            'rgba(3, 83, 164, 0.7)',
                            'rgba(72, 202, 228, 0.7)',
                            'rgba(144, 224, 239, 0.7)',
                            'rgba(202, 240, 248, 0.7)'
                        ],
                        borderColor: [
                            'rgba(4, 102, 200, 1)',
                            'rgba(3, 83, 164, 1)',
                            'rgba(72, 202, 228, 1)',
                            'rgba(144, 224, 239, 1)',
                            'rgba(202, 240, 248, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)',
                                lineWidth: 1
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)',
                                font: {
                                    family: "'Courier New', monospace",
                                    size: 11
                                },
                                precision: 0
                            },
                            title: {
                                display: true,
                                text: 'Number of Employees',
                                color: 'rgba(255, 255, 255, 0.7)',
                                font: {
                                    family: "'Inter', sans-serif",
                                    size: 12,
                                    weight: 'normal'
                                }
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)',
                                lineWidth: 1
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)',
                                font: {
                                    family: "'Inter', sans-serif",
                                    size: 11
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            titleFont: {
                                family: "'Inter', sans-serif",
                                size: 13
                            },
                            bodyFont: {
                                family: "'Courier New', monospace",
                                size: 12
                            },
                            padding: 12,
                            cornerRadius: 4,
                            displayColors: false
                        }
                    }
                }
            });
            

            // Initialize shift distribution chart
            const shiftCtx = document.getElementById('shiftChart').getContext('2d');
            const shiftChart = new Chart(shiftCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($shiftTypes); ?>,
                    datasets: [{
                        data: <?php echo json_encode($shiftCounts); ?>,
                        backgroundColor: [
                            'rgba(72, 202, 228, 0.7)',
                            'rgba(3, 83, 164, 0.7)',
                            'rgba(202, 240, 248, 0.7)'
                        ],
                        borderColor: [
                            'rgba(72, 202, 228, 1)',
                            'rgba(3, 83, 164, 1)',
                            'rgba(202, 240, 248, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: 'rgba(255, 255, 255, 0.7)',
                                font: {
                                    family: "'Inter', sans-serif",
                                    size: 11
                                },
                                padding: 15
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            titleFont: {
                                family: "'Inter', sans-serif",
                                size: 13
                            },
                            bodyFont: {
                                family: "'Courier New', monospace",
                                size: 12
                            },
                            padding: 12,
                            cornerRadius: 4,
                            displayColors: false
                        }
                    }
                }
            });

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Handle edit modal
            const editModal = document.getElementById('editModal');
            if (editModal) {
                editModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const employeeId = button.getAttribute('data-employee-id');
                    const employeeName = button.getAttribute('data-employee-name');

                    // Set employee name in the modal
                    document.getElementById('employeeName').textContent = employeeName;
                    document.getElementById('editEmployeeId').value = employeeId;

                    // Fetch the employee's schedule data
                    fetch(`/HR2/employee_db/supervisor/getSchedule.php?employee_id=${employeeId}`)
                        .then(response => response.json())
                        .then(data => {
                            document.getElementById('editShiftType').value = data.shift_type || 'day';
                            document.getElementById('editScheduleDate').value = data.schedule_date || '';
                            document.getElementById('editStartTime').value = data.start_time || '';
                            document.getElementById('editEndTime').value = data.end_time || '';
                        })
                        .catch(error => {
                            console.error('Error fetching schedule:', error);
                            // Show error toast or notification
                            alert('Failed to load employee schedule data. Please try again.');
                        });
                });
            }

            // Search functionality
            const searchInput = document.getElementById('searchInput');
            const scheduleTable = document.getElementById('scheduleTable');

            function performSearch() {
                const searchTerm = searchInput.value.toLowerCase();
                const rows = scheduleTable.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const employeeName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    if (employeeName.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            if (searchInput) {
                searchInput.addEventListener('keyup', performSearch);
            }

            // Refresh button
            const refreshButton = document.getElementById('refreshButton');
            if (refreshButton) {
                refreshButton.addEventListener('click', function() {
                    location.reload();
                });
            }

            // Calendar toggle
            const calendarToggle = document.getElementById('calendarToggle');
            const calendarContainer = document.getElementById('calendarContainer');
            const closeCalendar = document.getElementById('closeCalendar');

            if (calendarToggle && calendarContainer && closeCalendar) {
                calendarToggle.addEventListener('click', function() {
                    calendarContainer.style.display = 'block';
                    
                    // Initialize calendar if not already done
                    if (!window.calendar) {
                        const calendarEl = document.getElementById('calendar');
                        window.calendar = new FullCalendar.Calendar(calendarEl, {
                            initialView: 'dayGridMonth',
                            headerToolbar: {
                                left: 'prev,next today',
                                center: 'title',
                                right: 'dayGridMonth,timeGridWeek,timeGridDay'
                            },
                            themeSystem: 'bootstrap',
                            events: [] // You can load events here if needed
                        });
                        window.calendar.render();
                    }
                });

                closeCalendar.addEventListener('click', function() {
                    calendarContainer.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html>