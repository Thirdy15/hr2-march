<?php
// Assuming you're using MySQLi connection as in previous examples
require_once 'db_connection.php';  // Make sure to include your DB connection

if (isset($_GET['department'])) {
    $department = $_GET['department'];

    // Fetch employees based on department
    $employees_sql = "SELECT employee_id, first_name, last_name FROM employee_register WHERE department = ?";
    $stmt = $conn->prepare($employees_sql);
    $stmt->bind_param('s', $department);  // Bind the department value
    $stmt->execute();
    $result = $stmt->get_result();

    // Prepare an array to send back as JSON
    $employees = [];
    while ($employee = $result->fetch_assoc()) {
        $employees[] = $employee;
    }

    // Return the result as JSON
    echo json_encode($employees);
}
?>
