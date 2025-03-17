<?php
date_default_timezone_set('Asia/Manila');
include '../db/db_conn.php';

// Function to insert default "Absent" records for all employees
function insertDefaultAbsentRecords($conn, $currentDate) {
    // Fetch all employees from employee_register
    $query = "SELECT employee_id FROM employee_register";
    $result = $conn->query($query);

    if ($result === false) {
        error_log("Error fetching employees: " . $conn->error);
        return;
    }

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $employeeId = $row['employee_id'];

            // Check if the employee already has an attendance record for today
            $checkQuery = "SELECT * FROM attendance_log WHERE employee_id = ? AND attendance_date = ?";
            $checkStmt = $conn->prepare($checkQuery);

            if ($checkStmt === false) {
                error_log("Error preparing check query: " . $conn->error);
                continue;
            }

            $checkStmt->bind_param("ss", $employeeId, $currentDate);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            // If no record exists, insert a default "Absent" record
            if ($checkResult->num_rows == 0) {
                $insertQuery = "INSERT INTO attendance_log (employee_id, attendance_date, status) VALUES (?, ?, ?)";
                $insertStmt = $conn->prepare($insertQuery);

                if ($insertStmt === false) {
                    error_log("Error preparing insert query: " . $conn->error);
                    continue;
                }

                $status = 'Absent'; // Default status
                $insertStmt->bind_param("sss", $employeeId, $currentDate, $status);

                if (!$insertStmt->execute()) {
                    error_log("Failed to insert default absent record for employee ID: $employeeId");
                }

                $insertStmt->close();
            }

            $checkStmt->close();
        }
    } else {
        error_log("No employees found in employee_register.");
    }
}

// Check database connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection failed: " . $conn->connect_error);
}

// Current date
$currentDate = date('Y-m-d');

// Insert default "Absent" records for all employees
insertDefaultAbsentRecords($conn, $currentDate);

$conn->close();
?>