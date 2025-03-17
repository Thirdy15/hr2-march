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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation Dashboard | HR2</title>
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/star.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="sb-nav-fixed bg-black">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main class="bg-black">
                <div class="container-fluid position-relative px-4">
                    <h1 class="mb-4 text-light">Evaluation</h1>
                </div>
                <div class="container" id="calendarContainer" 
                    style="position: fixed; top: 9%; right: 0; z-index: 1050; 
                    width: 700px; display: none;">
                    <div class="row">
                        <div class="col-md-12">
                            <div id="calendar" class="p-2"></div>
                        </div>
                    </div>
                </div>     
                <div class="container-fluid px-4">
                    <div class="row justify-content-center">
                        <?php include '../db/db_conn.php'; 

                            $position = 'employee';
                            $department = 'Finance Department';

                            // Check if it is the first week of the month
                            $currentDay = date('j'); // Current day of the month (1-31)
                            $isFirstWeek = ($currentDay <= 15); // First week is days 1-7

                            // Set the evaluation period to the previous month if it is the first week
                            if ($isFirstWeek) {
                                $evaluationMonth = date('m', strtotime('last month')); // Previous month
                                $evaluationYear = date('Y', strtotime('last month'));  // Year of the previous month
                                $evaluationPeriod = date('F Y', strtotime('last month')); // Format: February 2024

                                // Calculate the end date of the evaluation period (7th day of the current month)
                                $evaluationEndDate = date('F j, Y', strtotime(date('Y-m-15'))); // Format: March 7, 2024
                            } else {
                                // If it is not the first week, evaluations are closed
                                $evaluationMonth = null;
                                $evaluationYear = null;
                                $evaluationPeriod = null;
                                $evaluationEndDate = null;
                            }

                            // Fetch employee records where role is 'employee' and department is 'Administration Department'
                            $sql = "SELECT employee_id, first_name, last_name, role, position, department FROM employee_register WHERE position = ? AND department = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param('ss', $position, $department);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            // Fetch evaluations for this admin
                            $adminId = $_SESSION['a_id'];
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

                            // Fetch evaluation questions from the database for each category
                            $categories = ['Quality of Work', 'Communication Skills', 'Teamwork', 'Punctuality', 'Initiative'];
                            $questions = [];

                            foreach ($categories as $category) {
                                // Fetch questions for the specific category and position
                                $categorySql = "SELECT question FROM evaluation_questions WHERE category = ? AND role = ?";
                                $categoryStmt = $conn->prepare($categorySql);
                                $categoryStmt->bind_param('ss', $category, $role); // $position is the position being evaluated
                                $categoryStmt->execute();
                                $categoryResult = $categoryStmt->get_result();
                                $questions[$category] = [];

                                if ($categoryResult->num_rows > 0) {
                                    while ($row = $categoryResult->fetch_assoc()) {
                                        $questions[$category][] = $row['question'];
                                    }
                                }
                            }

                            // Check if any records are found
                            $employees = [];
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $employees[] = $row;
                                }
                            }
                        ?>
                        <div class="col-xl-4 col-md-6 mt-5">
                            <div class="card mb-4">
                                <div class="card-body bg-secondary text-center">
                                    <button class="btn card-button text-light font-weight-bold bg-dark border border-dark w-100" data-bs-toggle="modal" data-bs-target="#financeModal">
                                        Finance Department
                                    </button>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-dark border-bottom border-light department-toggle">
                                    <div class="small text-warning">Details</div>
                                </div>
                                <div id="financeInfo" class=" bg-dark text-dark">
                                    <div class="card-body">
                                        <h5 class="text-center mb-4 text-light">Finance Evaluation Status</h5>
                                        <div class="text-center mb-3">
                                            <?php if ($financeData['pending'] > 0): ?>
                                                <span class="badge badge-danger mx-1 fs-5 px-2" style="top: -17px;">
                                                    Pending: <?php echo $financeData['pending']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="progress mb-2">
                                        <?php if ($financeData['total'] > 0): ?>
                                    <div class="progress-bar bg-success font-weight-bold fs-5" role="progressbar" 
                                        style="width: <?php echo ($financeData['evaluated'] / $financeData['total']) * 100; ?>%;" 
                                        aria-valuenow="<?php echo $financeData['evaluated']; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="<?php echo $financeData['total']; ?>">
                                        Evaluated <?php echo $financeData['evaluated']; ?>
                                    </div>
                                    <div class="progress-bar bg-danger text-light font-weight-bold fs-5" role="progressbar" 
                                        style="width: <?php echo ($financeData['pending'] / $financeData['total']) * 100; ?>%;" 
                                        aria-valuenow="<?php echo $financeData['pending']; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="<?php echo $financeData['total']; ?>">
                                        Pending <?php echo $financeData['pending']; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="progress-bar bg-secondary font-weight-bold w-100" role="progressbar" 
                                        aria-valuenow="0" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                        No employees available
                                    </div>
                                <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Modal -->
                        <div class="modal fade" id="financeModal" tabindex="-1" aria-labelledby="financeModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content bg-dark text-light">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="financeModalLabel">Finance Department</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body ">
                                        <!-- Display Evaluation Period -->
                                        <?php if ($isFirstWeek): ?>
                                            <p class="text-center text-warning">
                                                Evaluation is open for <?php echo $evaluationPeriod; ?> until <?php echo $evaluationEndDate; ?>.
                                            </p>
                                        <?php else: ?>
                                            <p class="text-center text-danger">
                                                Evaluations are closed. They will open in the first week of the next month.
                                            </p>
                                        <?php endif; ?>

                                        <!-- Employee Evaluation Table -->
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead class="thead-dark">
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Position</th>
                                                        <th>Role</th>
                                                        <th>Evaluation</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($employees)): ?>
                                                        <?php foreach ($employees as $employee): ?>
                                                            <tr>
                                                                <td class="text-light"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                                                                <td class="text-light"><?php echo htmlspecialchars($employee['role']); ?></td>
                                                                <td class="text-light"><?php echo htmlspecialchars($employee['position']); ?></td>
                                                                <td>
                                                                    <button class="btn btn-success" 
                                                                        onclick="evaluateEmployee(
                                                                            <?php echo $employee['employee_id']; ?>, 
                                                                            '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>', 
                                                                            '<?php echo htmlspecialchars($employee['role']); ?>', 
                                                                            '<?php echo htmlspecialchars($employee['department']); ?>' // Add department here
                                                                        )"
                                                                        <?php echo !$isFirstWeek || in_array($employee['employee_id'], $evaluatedEmployees) ? 'disabled' : ''; ?>>
                                                                        <?php echo in_array($employee['employee_id'], $evaluatedEmployees) ? 'Done' : 'Evaluate'; ?>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr><td class="text-light text-center" colspan="4">No employees found for evaluation in Finance Department.</td></tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        <?php
                            include '../db/db_conn.php'; 

                            // Define the values for role and department
                            $position = 'employee';
                            $department = 'Human Resource Department';
                            
                            // Check if it is the first week of the month
                            $currentDay = date('j'); // Current day of the month (1-31)
                            $isFirstWeek = ($currentDay <= 15); // First week is days 1-7
                            
                            // Set the evaluation period to the previous month if it is the first week
                            if ($isFirstWeek) {
                                // Handle January edge case
                                if (date('m') == '01') {
                                    $evaluationMonth = '15'; // December
                                    $evaluationYear = date('Y') - 1; // Previous year
                                } else {
                                    $evaluationMonth = date('m', strtotime('last month')); // Previous month
                                    $evaluationYear = date('Y', strtotime('last month'));  // Year of the previous month
                                }
                            
                                // Format evaluation period and end date
                                $evaluationPeriod = date('F Y', strtotime('last month')); // Format: February 2024
                                $evaluationEndDate = date('F j, Y', strtotime(date('Y-m-') . '15')); // Format: March 7, 2024
                            } else {
                                // If it is not the first week, evaluations are closed
                                $evaluationMonth = null;
                                $evaluationYear = null;
                                $evaluationPeriod = null;
                                $evaluationEndDate = null;
                            }
                            
                            // Fetch employee records where role is 'employee' and department is 'Administration Department'
                            $sql = "SELECT employee_id, first_name, last_name, role, position, department FROM employee_register WHERE position = ? AND department = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param('ss', $position, $department);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            // Fetch evaluations for this admin
                            $adminId = $_SESSION['a_id'];
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
                            
                            // Fetch evaluation questions from the database for each category
                            $categories = ['Quality of Work', 'Communication Skills', 'Teamwork', 'Punctuality', 'Initiative'];
                            $questions = [];

                            foreach ($categories as $category) {
                                // Fetch questions for the specific category and position
                                $categorySql = "SELECT question FROM evaluation_questions WHERE category = ? AND role = ?";
                                $categoryStmt = $conn->prepare($categorySql);
                                $categoryStmt->bind_param('ss', $category, $role); // $position is the position being evaluated
                                $categoryStmt->execute();
                                $categoryResult = $categoryStmt->get_result();
                                $questions[$category] = [];

                                if ($categoryResult->num_rows > 0) {
                                    while ($row = $categoryResult->fetch_assoc()) {
                                        $questions[$category][] = $row['question'];
                                    }
                                }
                            }
                            
                            // Check if any records are found
                            $employees = [];
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $employees[] = $row;
                                }
                            }
                        ?>
                        <div class="col-xl-4 col-md-6 mt-5">
                            <div class="card mb-4">
                                <div class="card-body bg-secondary text-center">
                                    <button class="btn card-button text-light font-weight-bold bg-dark border border-dark w-100" data-bs-toggle="modal" data-bs-target="#hrModal">
                                        Human Resource Department
                                    </button>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-dark border-bottom border-light department-toggle">
                                    <div class="small text-warning">Details</div>
                                </div>
                                <div id="hrInfo" class="bg-dark text-dark">
                                    <div class="card-body">
                                        <h5 class="text-center mb-4 text-light">HR Evaluation Status</h5>
                                        <div class="text-center mb-3">
                                            <?php if ($hrData['pending'] > 0): ?>
                                                <span class="badge badge-danger mx-1 fs-5 px-2" style="top: -17px;">
                                                    Pending: <?php echo $hrData['pending']; ?>
                                                </span>
                                            <?php endif; ?>                                        
                                        </div>
                                        <div class="progress mb-2">
                                            <?php if ($hrData['total'] > 0): ?>
                                                <div class="progress-bar bg-success font-weight-bold fs-5" role="progressbar" 
                                                    style="width: <?php echo ($hrData['evaluated'] / $hrData['total']) * 100; ?>%;" 
                                                    aria-valuenow="<?php echo $hrData['evaluated']; ?>" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="<?php echo $hrData['total']; ?>">
                                                    Evaluated <?php echo $hrData['evaluated']; ?>
                                                </div>
                                                <div class="progress-bar bg-danger text-light font-weight-bold fs-5" role="progressbar" 
                                                    style="width: <?php echo ($hrData['pending'] / $hrData['total']) * 100; ?>%;" 
                                                    aria-valuenow="<?php echo $hrData['pending']; ?>" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="<?php echo $hrData['total']; ?>">
                                                    Pending <?php echo $hrData['pending']; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="progress-bar bg-secondary font-weight-bold w-100" role="progressbar" 
                                                    aria-valuenow="0" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="100">
                                                    No employees available
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="hrModal" tabindex="-1" aria-labelledby="hrModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content bg-dark text-light">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="hrModalLabel">HR Department</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body ">
                                        <!-- Display Evaluation Period -->
                                        <?php if ($isFirstWeek): ?>
                                            <p class="text-center text-warning">
                                                Evaluation is open for <?php echo $evaluationPeriod; ?> until <?php echo $evaluationEndDate; ?>.
                                            </p>
                                        <?php else: ?>
                                            <p class="text-center text-danger">
                                                Evaluations are closed. They will open in the first week of the next month.
                                            </p>
                                        <?php endif; ?>

                                        <!-- Employee Evaluation Table -->
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="thead-dark">
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Position</th>
                                                        <th>Role</th>
                                                        <th>Evaluation</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($employees)): ?>
                                                        <?php foreach ($employees as $employee): ?>
                                                            <tr>
                                                                <td class="text-light"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                                                                <td class="text-light"><?php echo htmlspecialchars($employee['role']); ?></td>
                                                                <td class="text-light"><?php echo htmlspecialchars($employee['position']); ?></td>
                                                                <td>
                                                                    <button class="btn btn-success" 
                                                                        onclick="evaluateEmployee(
                                                                            <?php echo $employee['employee_id']; ?>, 
                                                                            '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>', 
                                                                            '<?php echo htmlspecialchars($employee['role']); ?>', 
                                                                            '<?php echo htmlspecialchars($employee['department']); ?>' // Add department here
                                                                        )"
                                                                        <?php echo !$isFirstWeek || in_array($employee['employee_id'], $evaluatedEmployees) ? 'disabled' : ''; ?>>
                                                                        <?php echo in_array($employee['employee_id'], $evaluatedEmployees) ? 'Done' : 'Evaluate'; ?>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr><td class="text-light text-center" colspan="4">No employees found for evaluation in HR Department.</td></tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    

                        <?php
                            include '../db/db_conn.php'; 

                            // Define the values for role and department
                            $position = 'employee';
                            $department = 'Administration Department';
                            
                            // Check if it is the first week of the month
                            $currentDay = date('j'); // Current day of the month (1-31)
                            $isFirstWeek = ($currentDay <= 15); // First week is days 1-7
                            
                            // Set the evaluation period to the previous month if it is the first week
                            if ($isFirstWeek) {
                                // Handle January edge case
                                if (date('m') == '01') {
                                    $evaluationMonth = '12'; // December
                                    $evaluationYear = date('Y') - 1; // Previous year
                                } else {
                                    $evaluationMonth = date('m', strtotime('last month')); // Previous month
                                    $evaluationYear = date('Y', strtotime('last month'));  // Year of the previous month
                                }
                            
                                // Format evaluation period and end date
                                $evaluationPeriod = date('F Y', strtotime('last month')); // Format: February 2024
                                $evaluationEndDate = date('F j, Y', strtotime(date('Y-m-') . '15')); // Format: March 7, 2024
                            } else {
                                // If it is not the first week, evaluations are closed
                                $evaluationMonth = null;
                                $evaluationYear = null;
                                $evaluationPeriod = null;
                                $evaluationEndDate = null;
                            }
                            
                            // Fetch employee records where role is 'employee' and department is 'Administration Department'
                            $sql = "SELECT employee_id, first_name, last_name, role, position, department FROM employee_register WHERE position = ? AND department = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param('ss', $position, $department);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            // Fetch evaluations for this admin
                            $adminId = $_SESSION['a_id'];
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
                            
                            // Fetch evaluation questions from the database for each category
                            $categories = ['Quality of Work', 'Communication Skills', 'Teamwork', 'Punctuality', 'Initiative'];
                            $questions = [];

                            foreach ($categories as $category) {
                                // Fetch questions for the specific category and position
                                $categorySql = "SELECT question FROM evaluation_questions WHERE category = ? AND role = ?";
                                $categoryStmt = $conn->prepare($categorySql);
                                $categoryStmt->bind_param('ss', $category, $role); // $position is the position being evaluated
                                $categoryStmt->execute();
                                $categoryResult = $categoryStmt->get_result();
                                $questions[$category] = [];

                                if ($categoryResult->num_rows > 0) {
                                    while ($row = $categoryResult->fetch_assoc()) {
                                        $questions[$category][] = $row['question'];
                                    }
                                }
                            }
                            
                            // Check if any records are found
                            $employees = [];
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $employees[] = $row;
                                }
                            }
                        ?>
                        <div class="col-xl-4 col-md-6 mt-5">
                            <div class="card mb-4">
                                <div class="card-body bg-secondary text-center">
                                    <button class="btn card-button text-light font-weight-bold bg-dark border border-dark w-100" data-bs-toggle="modal" data-bs-target="#adminModal">
                                        Admin Department
                                    </button>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-dark border-bottom border-light department-toggle">
                                    <div class="small text-warning">Details</div>
                                </div>
                                <div id="administrationInfo" class="bg-dark text-dark">
                                    <div class="card-body">
                                        <h5 class="text-center mb-4 text-light">Administration Evaluation Status</h5>
                                        <div class="text-center mb-3">
                                            <?php if ($administrationData['pending'] > 0): ?>
                                                <span class="badge badge-danger mx-1 fs-5 px-2" style="top: -17px;">
                                                    Pending: <?php echo $administrationData['pending']; ?>
                                                </span>
                                            <?php endif; ?>                                        
                                        </div>
                                        <div class="progress mb-2">
                                            <?php if ($administrationData['total'] > 0): ?>
                                                <div class="progress-bar bg-success font-weight-bold fs-5" role="progressbar" 
                                                    style="width: <?php echo ($administrationData['evaluated'] / $administrationData['total']) * 100; ?>%;" 
                                                    aria-valuenow="<?php echo $administrationData['evaluated']; ?>" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="<?php echo $administrationData['total']; ?>">
                                                    Evaluated <?php echo $administrationData['evaluated']; ?>
                                                </div>
                                                <div class="progress-bar bg-danger text-light font-weight-bold fs-5" role="progressbar" 
                                                    style="width: <?php echo ($administrationData['pending'] / $administrationData['total']) * 100; ?>%;" 
                                                    aria-valuenow="<?php echo $salesData['pending']; ?>" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="<?php echo $administrationData['total']; ?>">
                                                    Pending <?php echo $administrationData['pending']; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="progress-bar bg-secondary font-weight-bold w-100" role="progressbar" 
                                                    aria-valuenow="0" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="100">
                                                    No employees available
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="adminModal" tabindex="-1" aria-labelledby="adminModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content bg-dark text-light">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="adminModalLabel">Admin Department</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body ">
                                        <!-- Display Evaluation Period -->
                                        <?php if ($isFirstWeek): ?>
                                            <p class="text-center text-warning">
                                                Evaluation is open for <?php echo $evaluationPeriod; ?> until <?php echo $evaluationEndDate; ?>.
                                            </p>
                                        <?php else: ?>
                                            <p class="text-center text-danger">
                                                Evaluations are closed. They will open in the first week of the next month.
                                            </p>
                                        <?php endif; ?>

                                        <!-- Employee Evaluation Table -->
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead class="thead-dark">
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Position</th>
                                                        <th>Role</th>
                                                        <th>Evaluation</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($employees)): ?>
                                                        <?php foreach ($employees as $employee): ?>
                                                            <tr>
                                                                <td class="text-light"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                                                                <td class="text-light"><?php echo htmlspecialchars($employee['role']); ?></td>
                                                                <td class="text-light"><?php echo htmlspecialchars($employee['position']); ?></td>
                                                                <td>
                                                                    <button class="btn btn-success" 
                                                                        onclick="evaluateEmployee(
                                                                            <?php echo $employee['employee_id']; ?>, 
                                                                            '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>', 
                                                                            '<?php echo htmlspecialchars($employee['role']); ?>', 
                                                                            '<?php echo htmlspecialchars($employee['department']); ?>' // Add department here
                                                                        )"
                                                                        <?php echo !$isFirstWeek || in_array($employee['employee_id'], $evaluatedEmployees) ? 'disabled' : ''; ?>>
                                                                        <?php echo in_array($employee['employee_id'], $evaluatedEmployees) ? 'Done' : 'Evaluate'; ?>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr><td class="text-light text-center" colspan="4">No employees found for evaluation in Admin Department.</td></tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        

                        <?php
                            include '../db/db_conn.php'; 

                            // Define the values for role and department
                            $position = 'employee';
                            $department = 'Sales Department';

                            // Check if it is the first week of the month
                            $currentDay = date('j'); // Current day of the month (1-31)
                            $isFirstWeek = ($currentDay <= 15); // First week is days 1-7

                            // Set the evaluation period to the previous month if it is the first week
                            if ($isFirstWeek) {
                                $evaluationMonth = date('m', strtotime('last month')); // Previous month
                                $evaluationYear = date('Y', strtotime('last month'));  // Year of the previous month
                                $evaluationPeriod = date('F Y', strtotime('last month')); // Format: February 2024

                                // Calculate the end date of the evaluation period (7th day of the current month)
                                $evaluationEndDate = date('F j, Y', strtotime(date('Y-m-15'))); // Format: March 7, 2024
                            } else {
                                // If it is not the first week, evaluations are closed
                                $evaluationMonth = null;
                                $evaluationYear = null;
                                $evaluationPeriod = null;
                                $evaluationEndDate = null;
                            }

                            // Fetch employee records where role is 'employee' and department is 'Administration Department'
                            $sql = "SELECT employee_id, first_name, last_name, role, position, department FROM employee_register WHERE position = ? AND department = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param('ss', $position, $department);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            // Fetch evaluations for this admin
                            $adminId = $_SESSION['a_id'];
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

                            // Fetch evaluation questions from the database for each category
                            $categories = ['Quality of Work', 'Communication Skills', 'Teamwork', 'Punctuality', 'Initiative'];
                            $questions = [];

                            foreach ($categories as $category) {
                                // Fetch questions for the specific category and position
                                $categorySql = "SELECT question FROM evaluation_questions WHERE category = ? AND role = ?";
                                $categoryStmt = $conn->prepare($categorySql);
                                $categoryStmt->bind_param('ss', $category, $role); // $position is the position being evaluated
                                $categoryStmt->execute();
                                $categoryResult = $categoryStmt->get_result();
                                $questions[$category] = [];

                                if ($categoryResult->num_rows > 0) {
                                    while ($row = $categoryResult->fetch_assoc()) {
                                        $questions[$category][] = $row['question'];
                                    }
                                }
                            }

                            // Check if any records are found
                            $employees = [];
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $employees[] = $row;
                                }
                            }

                        ?>
                        <div class="col-xl-4 col-md-6 mt-5">
                            <div class="card mb-4">
                                <div class="card-body bg-secondary text-center">
                                    <button class="btn card-button text-light font-weight-bold bg-dark border border-dark w-100" data-bs-toggle="modal" data-bs-target="#salesModal">
                                        Sales Department
                                    </button>                                
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-dark border-bottom border-light department-toggle">
                                    <div class="small text-warning">Details</div>
                                </div>
                                <div id="salesInfo" class="bg-dark text-dark">
                                    <div class="card-body">
                                        <h5 class="text-center mb-4 text-light">Sales Evaluation Status</h5>
                                        <div class="text-center mb-3">
                                            <?php if ($salesData['pending'] > 0): ?>
                                                <span class="badge badge-danger mx-1 fs-5 px-2" style="top: -17px;">
                                                    Pending: <?php echo $salesData['pending']; ?>
                                                </span>
                                            <?php endif; ?>                                        
                                        </div>
                                        <div class="progress mb-2">
                                        <?php if ($salesData['total'] > 0): ?>
                                    <div class="progress-bar bg-success font-weight-bold fs-5" role="progressbar" 
                                        style="width: <?php echo ($salesData['evaluated'] / $salesData['total']) * 100; ?>%;" 
                                        aria-valuenow="<?php echo $salesData['evaluated']; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="<?php echo $salesData['total']; ?>">
                                        Evaluated <?php echo $salesData['evaluated']; ?>
                                    </div>
                                    <div class="progress-bar bg-danger text-light font-weight-bold fs-5" role="progressbar" 
                                        style="width: <?php echo ($salesData['pending'] / $salesData['total']) * 100; ?>%;" 
                                        aria-valuenow="<?php echo $salesData['pending']; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="<?php echo $salesData['total']; ?>">
                                        Pending <?php echo $salesData['pending']; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="progress-bar bg-secondary font-weight-bold w-100" role="progressbar" 
                                        aria-valuenow="0" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                        No employees available
                                    </div>
                                <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="salesModal" tabindex="-1" aria-labelledby="salesModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content bg-dark text-light">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="salesModalLabel">Sales Department</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body ">
                                        <!-- Display Evaluation Period -->
                                        <?php if ($isFirstWeek): ?>
                                            <p class="text-center text-warning">
                                                Evaluation is open for <?php echo $evaluationPeriod; ?> until <?php echo $evaluationEndDate; ?>.
                                            </p>
                                        <?php else: ?>
                                            <p class="text-center text-danger">
                                                Evaluations are closed. They will open in the first week of the next month.
                                            </p>
                                        <?php endif; ?>

                                        <!-- Employee Evaluation Table -->
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead class="thead-dark">
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Position</th>
                                                        <th>Role</th>
                                                        <th>Evaluation</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($employees)): ?>
                                                        <?php foreach ($employees as $employee): ?>
                                                            <tr>
                                                                <td class="text-light"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                                                                <td class="text-light"><?php echo htmlspecialchars($employee['role']); ?></td>
                                                                <td class="text-light"><?php echo htmlspecialchars($employee['position']); ?></td>
                                                                <td>
                                                                    <button class="btn btn-success" 
                                                                        onclick="evaluateEmployee(
                                                                            <?php echo $employee['employee_id']; ?>, 
                                                                            '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>', 
                                                                            '<?php echo htmlspecialchars($employee['role']); ?>', 
                                                                            '<?php echo htmlspecialchars($employee['department']); ?>' // Add department here
                                                                        )"
                                                                        <?php echo !$isFirstWeek || in_array($employee['employee_id'], $evaluatedEmployees) ? 'disabled' : ''; ?>>
                                                                        <?php echo in_array($employee['employee_id'], $evaluatedEmployees) ? 'Done' : 'Evaluate'; ?>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr><td class="text-light text-center" colspan="4">No employees found for evaluation in Sales Department.</td></tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <?php
                            include '../db/db_conn.php'; 

                            // Define the values for role and department
                            $position = 'employee';
                            $department = 'Credit Department';

                            // Check if it is the first week of the month
                            $currentDay = date('j'); // Current day of the month (1-31)
                            $isFirstWeek = ($currentDay <= 15); // First week is days 1-7

                            // Set the evaluation period to the previous month if it is the first week
                            if ($isFirstWeek) {
                                $evaluationMonth = date('m', strtotime('last month')); // Previous month
                                $evaluationYear = date('Y', strtotime('last month'));  // Year of the previous month
                                $evaluationPeriod = date('F Y', strtotime('last month')); // Format: February 2024

                                // Calculate the end date of the evaluation period (7th day of the current month)
                                $evaluationEndDate = date('F j, Y', strtotime(date('Y-m-15'))); // Format: March 7, 2024
                            } else {
                                // If it is not the first week, evaluations are closed
                                $evaluationMonth = null;
                                $evaluationYear = null;
                                $evaluationPeriod = null;
                                $evaluationEndDate = null;
                            }

                            // Fetch employee records where role is 'employee' and department is 'Administration Department'
                            $sql = "SELECT employee_id, first_name, last_name, role, position, department FROM employee_register WHERE position = ? AND department = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param('ss', $position, $department);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            // Fetch evaluations for this admin
                            $adminId = $_SESSION['a_id'];
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

                            // Fetch evaluation questions from the database for each category
                            $categories = ['Quality of Work', 'Communication Skills', 'Teamwork', 'Punctuality', 'Initiative'];
                            $questions = [];

                            foreach ($categories as $category) {
                                // Fetch questions for the specific category and position
                                $categorySql = "SELECT question FROM evaluation_questions WHERE category = ? AND role = ?";
                                $categoryStmt = $conn->prepare($categorySql);
                                $categoryStmt->bind_param('ss', $category, $role); // $position is the position being evaluated
                                $categoryStmt->execute();
                                $categoryResult = $categoryStmt->get_result();
                                $questions[$category] = [];

                                if ($categoryResult->num_rows > 0) {
                                    while ($row = $categoryResult->fetch_assoc()) {
                                        $questions[$category][] = $row['question'];
                                    }
                                }
                            }

                            // Check if any records are found
                            $employees = [];
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $employees[] = $row;
                                }
                            }

                            // Close the database connection
                            $conn->close();
                        ?>
                        <div class="col-xl-4 col-md-6 mt-5">
                            <div class="card mb-4">
                                <div class="card-body bg-secondary text-center">
                                    <button class="btn card-button text-light font-weight-bold bg-dark border border-dark w-100" data-bs-toggle="modal" data-bs-target="#creditModal">
                                        Credit Department
                                    </button>                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-dark border-bottom border-light department-toggle">
                                    <div class="small text-warning">Details</div>
                                </div>
                                <div id="creditInfo" class="bg-dark text-dark">
                                    <div class="card-body">
                                        <h5 class="text-center mb-4 text-light">Credit Evaluation Status</h5>
                                        <div class="text-center mb-3">
                                            <?php if ($creditData['pending'] > 0): ?>
                                                <span class="badge badge-danger mx-1 fs-5 px-2" style="top: -17px;">
                                                    Pending: <?php echo $creditData['pending']; ?>
                                                </span>
                                            <?php endif; ?>                                            
                                        </div>
                                        <div class="progress mb-2">
                                        <?php if ($creditData['total'] > 0): ?>
                                    <div class="progress-bar bg-success font-weight-bold fs-5" role="progressbar" 
                                        style="width: <?php echo ($creditData['evaluated'] / $creditData['total']) * 100; ?>%;" 
                                        aria-valuenow="<?php echo $creditData['evaluated']; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="<?php echo $creditData['total']; ?>">
                                        Evaluated <?php echo $creditData['evaluated']; ?>
                                    </div>
                                    <div class="progress-bar bg-danger text-light font-weight-bold fs-5" role="progressbar" 
                                        style="width: <?php echo ($creditData['pending'] / $creditData['total']) * 100; ?>%;" 
                                        aria-valuenow="<?php echo $creditData['pending']; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="<?php echo $creditData['total']; ?>">
                                        Pending <?php echo $creditData['pending']; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="progress-bar bg-secondary font-weight-bold w-100" role="progressbar" 
                                        aria-valuenow="0" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                        No employees available
                                    </div>
                                <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="creditModal" tabindex="-1" aria-labelledby="creditModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content bg-dark text-light">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="creditModalLabel">Credit Department</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body ">
                                        <!-- Display Evaluation Period -->
                                        <?php if ($isFirstWeek): ?>
                                            <p class="text-center text-warning">
                                                Evaluation is open for <?php echo $evaluationPeriod; ?> until <?php echo $evaluationEndDate; ?>.
                                            </p>
                                        <?php else: ?>
                                            <p class="text-center text-danger">
                                                Evaluations are closed. They will open in the first week of the next month.
                                            </p>
                                        <?php endif; ?>

                                        <!-- Employee Evaluation Table -->
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead class="thead-dark">
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Position</th>
                                                        <th>Role</th>
                                                        <th>Evaluation</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($employees)): ?>
                                                        <?php foreach ($employees as $employee): ?>
                                                            <tr>
                                                                <td class="text-light"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                                                                <td class="text-light"><?php echo htmlspecialchars($employee['role']); ?></td>
                                                                <td class="text-light"><?php echo htmlspecialchars($employee['position']); ?></td>
                                                                <td>
                                                                    <button class="btn btn-success" 
                                                                        onclick="evaluateEmployee(
                                                                            <?php echo $employee['employee_id']; ?>, 
                                                                            '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>', 
                                                                            '<?php echo htmlspecialchars($employee['role']); ?>', 
                                                                            '<?php echo htmlspecialchars($employee['department']); ?>' // Add department here
                                                                        )"
                                                                        <?php echo !$isFirstWeek || in_array($employee['employee_id'], $evaluatedEmployees) ? 'disabled' : ''; ?>>
                                                                        <?php echo in_array($employee['employee_id'], $evaluatedEmployees) ? 'Done' : 'Evaluate'; ?>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr><td class="text-light text-center" colspan="4">No employees found for evaluation in Credit Department.</td></tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <?php
                            include '../db/db_conn.php'; 

                            // Define the values for role and department
                            $position = 'employee';
                            $department = 'IT Department';

                            // Check if it is the first week of the month
                            $currentDay = date('j'); // Current day of the month (1-31)
                            $isFirstWeek = ($currentDay <= 15); // First week is days 1-7

                            // Set the evaluation period to the previous month if it is the first week
                            if ($isFirstWeek) {
                                $evaluationMonth = date('m', strtotime('last month')); // Previous month
                                $evaluationYear = date('Y', strtotime('last month'));  // Year of the previous month
                                $evaluationPeriod = date('F Y', strtotime('last month')); // Format: February 2024

                                // Calculate the end date of the evaluation period (7th day of the current month)
                                $evaluationEndDate = date('F j, Y', strtotime(date('Y-m-15'))); // Format: March 7, 2024
                            } else {
                                // If it is not the first week, evaluations are closed
                                $evaluationMonth = null;
                                $evaluationYear = null;
                                $evaluationPeriod = null;
                                $evaluationEndDate = null;
                            }

                            // Fetch employee records where role is 'employee' and department is 'Administration Department'
                            $sql = "SELECT employee_id, first_name, last_name, role, position, department FROM employee_register WHERE position = ? AND department = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param('ss', $position, $department);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            // Fetch evaluations for this admin
                            $adminId = $_SESSION['a_id'];
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

                            // Fetch evaluation questions from the database for each category
                            $categories = ['Quality of Work', 'Communication Skills', 'Teamwork', 'Punctuality', 'Initiative'];
                            $questions = [];

                            foreach ($categories as $category) {
                                // Fetch questions for the specific category and position
                                $categorySql = "SELECT question FROM evaluation_questions WHERE category = ? AND role = ?";
                                $categoryStmt = $conn->prepare($categorySql);
                                $categoryStmt->bind_param('ss', $category, $role); // $position is the position being evaluated
                                $categoryStmt->execute();
                                $categoryResult = $categoryStmt->get_result();
                                $questions[$category] = [];

                                if ($categoryResult->num_rows > 0) {
                                    while ($row = $categoryResult->fetch_assoc()) {
                                        $questions[$category][] = $row['question'];
                                    }
                                }
                            }

                            // Check if any records are found
                            $employees = [];
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $employees[] = $row;
                                }
                            }

                            // Close the database connection
                            $conn->close();
                        ?>
                        <div class="col-xl-4 col-md-6 mt-5">
                            <div class="card mb-4">
                                <div class="card-body bg-secondary text-center">
                                    <button class="btn card-button text-light font-weight-bold bg-dark border border-dark w-100" data-bs-toggle="modal" data-bs-target="#itModal">
                                        IT Department
                                    </button>                                  </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-dark border-bottom border-light department-toggle">
                                    <div class="small text-warning">Details</div>
                                </div>
                                <div id="itInfo" class="bg-dark text-dark">
                                    <div class="card-body">
                                        <h5 class="text-center mb-4 text-light">IT Evaluation Status</h5>
                                        <div class="text-center mb-3">
                                            <?php if ($itData['pending'] > 0): ?>
                                                <span class="badge badge-danger mx-1 fs-5 px-2" style="top: -17px;">
                                                    Pending: <?php echo $itData['pending']; ?>
                                                </span>
                                            <?php endif; ?>                                            
                                        </div>
                                        <div class="progress mb-2">
                                            <?php if ($itData['total'] > 0): ?>
                                                <div class="progress-bar bg-success font-weight-bold fs-5" role="progressbar" 
                                                    style="width: <?php echo ($itData['evaluated'] / $itData['total']) * 100; ?>%;" 
                                                    aria-valuenow="<?php echo $itData['evaluated']; ?>" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="<?php echo $itData['total']; ?>">
                                                    Evaluated <?php echo $itData['evaluated']; ?>
                                                </div>
                                                <div class="progress-bar bg-danger text-light font-weight-bold fs-5" role="progressbar" 
                                                    style="width: <?php echo ($itData['pending'] / $itData['total']) * 100; ?>%;" 
                                                    aria-valuenow="<?php echo $itData['pending']; ?>" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="<?php echo $itData['total']; ?>">
                                                    Pending <?php echo $itData['pending']; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="progress-bar bg-secondary font-weight-bold w-100" role="progressbar" 
                                                    aria-valuenow="0" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="100">
                                                    No employees available
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="itModal" tabindex="-1" aria-labelledby="itModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content bg-dark text-light">
                            <div class="modal-header">
                                <h5 class="modal-title" id="itModalLabel">IT Department</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body ">
                                <!-- Display Evaluation Period -->
                                <?php if ($isFirstWeek): ?>
                                    <p class="text-center text-warning">
                                        Evaluation is open for <?php echo $evaluationPeriod; ?> until <?php echo $evaluationEndDate; ?>.
                                    </p>
                                <?php else: ?>
                                    <p class="text-center text-danger">
                                        Evaluations are closed. They will open in the first week of the next month.
                                    </p>
                                <?php endif; ?>

                                <!-- Employee Evaluation Table -->
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Name</th>
                                                <th>Position</th>
                                                <th>Role</th>
                                                <th>Evaluation</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($employees)): ?>
                                                <?php foreach ($employees as $employee): ?>
                                                    <tr>
                                                        <td class="text-light"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                                                        <td class="text-light"><?php echo htmlspecialchars($employee['role']); ?></td>
                                                        <td class="text-light"><?php echo htmlspecialchars($employee['position']); ?></td>
                                                        <td>
                                                            <button class="btn btn-success" 
                                                                onclick="evaluateEmployee(
                                                                    <?php echo $employee['employee_id']; ?>, 
                                                                    '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>', 
                                                                    '<?php echo htmlspecialchars($employee['role']); ?>', 
                                                                    '<?php echo htmlspecialchars($employee['department']); ?>' // Add department here
                                                                )"
                                                                <?php echo !$isFirstWeek || in_array($employee['employee_id'], $evaluatedEmployees) ? 'disabled' : ''; ?>>
                                                                <?php echo in_array($employee['employee_id'], $evaluatedEmployees) ? 'Done' : 'Evaluate'; ?>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td class="text-light text-center" colspan="4">No employees found for evaluation in IT Department.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
                <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-dark text-light">
                            <div class="modal-header border-bottom border-secondary">
                                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to log out?
                            </div>
                            <div class="modal-footer border-top border-secondary">
                                <button type="button" class="btn border-secondary text-light" data-bs-dismiss="modal">Cancel</button>
                                <form action="../admin/logout.php" method="POST">
                                    <button type="submit" class="btn btn-danger">Logout</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>                
                <div class="modal fade" id="evaluationModal" tabindex="-1" role="dialog" aria-labelledby="evaluationModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content bg-dark text-light">
                            <div class="modal-header">
                                <h5 class="modal-title" id="employeeDetails"></h5>
                                <!-- Corrected close button structure -->
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" id="a_id" value="<?php echo $_SESSION['a_id']; ?>">
                                <div id="questions"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" onclick="submitEvaluation()">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>                
            <?php include 'footer.php'; ?>                                 
        </div>
    </div>
    
<script>

        let currentEmployeeId;
        let currentEmployeeName;  
        let currentEmployeeRole; 
        let currentDepartment; // Add this line at the top of your script

        // Function to fetch questions based on the evaluated employee's position
        async function fetchQuestions(role) {
            const response = await fetch(`../db/fetchQuestions.php?role=${role}`);
            return await response.json();
        }

        async function evaluateEmployee(employee_id, employeeName, employeeRole, department) {
            currentEmployeeId = employee_id; 
            currentEmployeeName = employeeName; 
            currentEmployeeRole = employeeRole; 
            currentDepartment = department; // Store the department

            // Fetch questions based on the evaluated employee's position
            const questions = await fetchQuestions(employeeRole);

            const employeeDetails = `<strong>Name: ${employeeName} <br> Position: ${employeeRole} <br> Department: ${department}</strong>`;
            document.getElementById('employeeDetails').innerHTML = employeeDetails;

            const questionsDiv = document.getElementById('questions');
            questionsDiv.innerHTML = ''; 

            // Start the table structure
            let tableHtml = `
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Question</th>
                        <th>Rating</th>
                    </tr>
                </thead>
                <tbody>`;

            // Loop through categories and questions to add them into the table
            for (const [category, categoryQuestions] of Object.entries(questions)) {
                categoryQuestions.forEach((question, index) => {
                    const questionName = `${category.replace(/\s/g, '')}q${index}`; // Unique name per question
                    tableHtml += `
                    <tr>
                        <td>${index === 0 ? category : ''}</td>
                        <td>${question}</td>
                        <td>
                            <div class="star-rating">
                                ${[6, 5, 4, 3, 2, 1].map(value => `
                                    <input type="radio" name="${questionName}" value="${value}" id="${questionName}star${value}">
                                    <label for="${questionName}star${value}">&#9733;</label>
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

            $('#evaluationModal').modal('show'); 
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
                alert('Please complete the evaluation before submitting.');
                return;
            }

            const categoryAverages = {
                QualityOfWork: calculateAverage('Quality of Work', evaluations),
                CommunicationSkills: calculateAverage('Communication Skills', evaluations),
                Teamwork: calculateAverage('Teamwork', evaluations),
                Punctuality: calculateAverage('Punctuality', evaluations),
                Initiative: calculateAverage('Initiative', evaluations)
            };

            console.log('Category Averages:', categoryAverages);

            const adminId = document.getElementById('a_id').value;

            $.ajax({
                type: 'POST',
                url: '../db/submit_evaluation.php',
                data: {
                    employee_id: currentEmployeeId,
                    employeeName: currentEmployeeName,
                    employeeRole: currentEmployeeRole,
                    categoryAverages: categoryAverages,
                    adminId: adminId,
                    department: currentDepartment // Use the dynamic department
                },
                success: function (response) {
                    console.log(response); 
                    if (response === 'You have already evaluated this employee.') {
                        alert(response); 
                    } else {
                        $('#evaluationModal').modal('hide');
                        alert('Evaluation submitted successfully!');
                    }
                },
                error: function (err) {
                    console.error(err);
                    alert('An error occurred while submitting the evaluation.');
                }
            });
        }

        function calculateAverage(category, evaluations) {
            const categoryEvaluations = evaluations.filter(evaluation => evaluation.question.startsWith(category.replace(/\s/g, '')));

            if (categoryEvaluations.length === 0) {
                return 0; 
            }

            const total = categoryEvaluations.reduce((sum, evaluation) => sum + parseInt(evaluation.rating), 0);
            return total / categoryEvaluations.length;
        }

</script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../js/admin.js"></script>
</body>
</html>
