<?php
session_start();

// Include database connection
include '../db/db_conn.php';

if (!isset($_SESSION['a_id'])) {
    echo 'You are not AUTHORIZED.';
    exit();
}

// Get the admin ID from the session
$adminId = $_SESSION['a_id'];
echo 'Admin ID from session: ' . $adminId . '<br>';  // Debugging output for admin ID

// Fetch admin details using the session's a_id
$adminSql = "SELECT firstname, lastname FROM admin_register WHERE a_id = ?";
$adminStmt = $conn->prepare($adminSql);

// Check if the statement is prepared correctly
if (!$adminStmt) {
    echo "Error in preparing the query: " . $conn->error;
    exit();
}

// Bind the adminId and execute the query
$adminStmt->bind_param('i', $adminId);
$adminStmt->execute();

// Debugging: check if the query executed and fetched data
if ($adminStmt->bind_result($adminFirstName, $adminLastName) && $adminStmt->fetch()) {
    $adminName = $adminFirstName . ' ' . $adminLastName;
    echo "Admin name: " . $adminName . '<br>';  // Debugging output for admin name
} else {
    echo 'Error: Admin not found or failed to fetch name for ID: ' . $adminId;
    exit();
}

$adminStmt->close();

// Get data from the POST request
$employeeId = $_POST['employee_id'];
$categoryAverages = $_POST['categoryAverages'];
$department = $_POST['department']; // Get the department from POST data

// Fetch the employee's first and last name
$employeeSql = "SELECT first_name, last_name FROM employee_register WHERE employee_id= ?";
$employeeStmt = $conn->prepare($employeeSql);
$employeeStmt->bind_param('s', $employeeId);
$employeeStmt->execute();
$employeeStmt->bind_result($employeeFirstName, $employeeLastName);

if ($employeeStmt->fetch()) {
    // Combine employee first and last name
    $employeeName = $employeeFirstName . ' ' . $employeeLastName;
} else {
    echo 'Error: Employee not found or unable to fetch the name.<br>';
    exit();
}
$employeeStmt->close();

// Check if the current admin has already evaluated this employee
$checkSql = "SELECT * FROM evaluations WHERE a_id = ? AND employee_id= ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param('is', $adminId, $employeeId);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    echo 'You have already evaluated this employee.';
} else {
    // Get the current date and time
    $currentDate = date('Y-m-d H:i:s'); // Current date and time

    // Calculate the delayed date and time (2 weeks ago)
    $delayedDate = date('Y-m-d H:i:s', strtotime('-2 weeks')); // Subtract 2 weeks from the current date and time

    // Prepare the SQL to insert the evaluation into the database
    $sql = "INSERT INTO evaluations (
                a_id, evaluator_name, employee_id, employee_name, department, quality, communication_skills, teamwork, punctuality, initiative, evaluated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // Bind the average ratings to the statement (i for integer, d for decimal)
    $stmt->bind_param(
        'isissddddds', 
        $adminId,
        $adminName, 
        $employeeId,
        $employeeName,
        $department,
        $categoryAverages['QualityOfWork'], 
        $categoryAverages['CommunicationSkills'], 
        $categoryAverages['Teamwork'], 
        $categoryAverages['Punctuality'], 
        $categoryAverages['Initiative'],
        $delayedDate // Add the delayed date and time to the query
    );

    // Execute the statement and check if successful
    if ($stmt->execute()) {
        // Log this activity
        $actionType = "Employee Evaluation";
        $affectedFeature = "Evaluation";
        $details = "Admin ($adminName) evaluated employee Name: $employeeName in $department on $delayedDate.";

        // Capture IP address
        $ipAddress = $_SERVER['REMOTE_ADDR'];

        // Log query
        $logQuery = "INSERT INTO activity_logs (admin_id, admin_name, action_type, affected_feature, details, ip_address) 
                     VALUES (?, ?, ?, ?, ?, ?)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("isssss", $adminId, $adminName, $actionType, $affectedFeature, $details, $ipAddress);

        if ($logStmt->execute()) {
            echo 'Evaluation saved and activity logged successfully.';
        } else {
            echo 'Error logging the activity: ' . $logStmt->error;
        }

        $logStmt->close();
    } else {
        echo 'Error: ' . $conn->error;
    }
    $stmt->close();
}

$checkStmt->close();
$conn->close();
?>