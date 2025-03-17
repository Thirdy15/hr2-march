<?php
session_start();

// Include database connection
include '../../db/db_conn.php';

// Redirect if the employee is not logged in
if (!isset($_SESSION['employee_id'])) {
    echo 'You are not AUTHORIZED.';
    exit();
}

// Get the logged-in employee's ID from the session
$evaluatorId = $_SESSION['employee_id'];
echo 'Evaluator ID from session: ' . $evaluatorId . '<br>';  // Debugging output

// Fetch evaluator's details using the session's employee_id
$evaluatorSql = "SELECT first_name, last_name FROM employee_register WHERE employee_id = ?";
$evaluatorStmt = $conn->prepare($evaluatorSql);

// Check if the statement is prepared correctly
if (!$evaluatorStmt) {
    echo "Error in preparing the query: " . $conn->error;
    exit();
}

// Bind the evaluatorId and execute the query
$evaluatorStmt->bind_param('s', $evaluatorId);
$evaluatorStmt->execute();

// Debugging: check if the query executed and fetched data
if ($evaluatorStmt->bind_result($evaluatorFirstName, $evaluatorLastName) && $evaluatorStmt->fetch()) {
    $evaluatorName = $evaluatorFirstName . ' ' . $evaluatorLastName;
    echo "Evaluator name: " . $evaluatorName . '<br>';  // Debugging output
} else {
    echo 'Error: Evaluator not found or failed to fetch name for ID: ' . $evaluatorId;
    exit();
}

$evaluatorStmt->close();

// Get data from the POST request
$employeeId = $_POST['employee_id'];
$categoryAverages = $_POST['categoryAverages'];
$department = $_POST['department']; // Get the department from POST data

// Fetch the employee's first and last name
$employeeSql = "SELECT first_name, last_name FROM employee_register WHERE employee_id = ?";
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

// Check if the current evaluator has already evaluated this employee
$checkSql = "SELECT * FROM ptp_evaluations WHERE evaluator_id = ? AND employee_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param('ss', $evaluatorId, $employeeId);
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
    $sql = "INSERT INTO ptp_evaluations (
                evaluator_id, evaluator_name, employee_id, employee_name, department, quality, communication_skills, teamwork, punctuality, initiative, evaluated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // Bind the average ratings to the statement (i for integer, d for decimal)
    $stmt->bind_param(
        'sssssddddds', 
        $evaluatorId,
        $evaluatorName, 
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
        echo 'Evaluation saved successfully.';
    } else {
        echo 'Error: ' . $conn->error;
    }
    $stmt->close();
}

$checkStmt->close();
$conn->close();
?>