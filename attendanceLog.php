<?php
date_default_timezone_set('Asia/Manila');
include 'db/db_conn.php';

// Function to insert default "Absent" records for all employees
function insertDefaultAbsentRecords($conn, $currentDate) {
    // Fetch all employees from employee_register
    $query = "SELECT employee_id FROM employee_register";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $employeeId = $row['employee_id'];

            // Check if the employee already has an attendance record for today
            $checkQuery = "SELECT * FROM attendance_log WHERE employee_id = ? AND attendance_date = ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param("ss", $employeeId, $currentDate);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            // If no record exists, insert a default "Absent" record without time_in or time_out
            if ($checkResult->num_rows == 0) {
                $insertQuery = "INSERT INTO attendance_log (employee_id, attendance_date, status) VALUES (?, ?, ?)";
                $insertStmt = $conn->prepare($insertQuery);
                $status = 'Absent'; // Default status
                $insertStmt->bind_param("sss", $employeeId, $currentDate, $status);

                if (!$insertStmt->execute()) {
                    error_log("Failed to insert default absent record for employee ID: $employeeId");
                }
            }
        }
    }
}

// Current date and time
$currentDate = date('Y-m-d');
$currentTime = date('Y-m-d H:i:s');

// Insert default "Absent" records for all employees
insertDefaultAbsentRecords($conn, $currentDate);

if (isset($_POST['employeeId'])) {
    $employeeId = $_POST['employeeId']; // employee_id is VARCHAR

    // Check existing attendance for today
    $query = "SELECT * FROM attendance_log WHERE employee_id = ? AND attendance_date = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $employeeId, $currentDate);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $attendance = $result->fetch_assoc();

        // If time_in is null, treat this as a time-in event
        if ($attendance['time_in'] === null) {
            $updateQuery = "UPDATE attendance_log SET time_in = ?, status = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);

            $timeIn = new DateTime($currentTime);
            $lateThreshold = new DateTime($currentDate . ' 08:00:00');
            $status = ($timeIn > $lateThreshold) ? 'Late' : 'Present';

            $updateStmt->bind_param("ssi", $currentTime, $status, $attendance['id']);
            $updateStmt->execute();
            echo json_encode(["success" => true, "message" => "Time-in recorded.", "status" => $status]);
        }
        // If time_out is null, treat this as a time-out event
        elseif ($attendance['time_out'] === null) {
            $updateQuery = "UPDATE attendance_log SET time_out = ?, status = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);

            // Define operation hours and break time
            $operationStart = new DateTime($currentDate . ' 08:00:00');
            $operationEnd = new DateTime($currentDate . ' 17:00:00');
            $breakStart = new DateTime($currentDate . ' 12:00:00');
            $breakEnd = new DateTime($currentDate . ' 13:00:00');

            // Adjust time_in and time_out based on operation hours
            $timeIn = new DateTime($attendance['time_in']);
            $timeOut = new DateTime($currentTime);

            // If time_in is before operation start, set it to operation start
            if ($timeIn < $operationStart) {
                $timeIn = $operationStart;
            }

            // If time_out is after operation end, set it to operation end
            if ($timeOut > $operationEnd) {
                $timeOut = $operationEnd;
            }

            // Calculate total worked time (including minutes)
            $workedTime = $timeOut->diff($timeIn);
            $workedHours = $workedTime->h; // Total worked hours
            $workedMinutes = $workedTime->i; // Total worked minutes

            // Check if the working period overlaps with break time
            $isBreakTimeOverlap = ($timeIn < $breakEnd && $timeOut > $breakStart);

            // Subtract 1 hour only if there is an overlap with break time
            if ($isBreakTimeOverlap) {
                $workedHours -= 1; // Subtract 1 hour for break time
            }

            // Convert total worked time to minutes for easier comparison
            $totalWorkedMinutes = ($workedHours * 60) + $workedMinutes;

            // Determine status
            if ($totalWorkedMinutes < 120) { // Less than 2 hours (120 minutes)
                $status = 'Absent';
            } elseif ($totalWorkedMinutes < 360) { // 2 to 6 hours (120 to 360 minutes)
                $status = 'Half-Day';
            } elseif ($timeOut > $operationEnd) {
                $status = 'Overtime'; // Time-out after 5:00 PM
            } elseif ($timeOut < $operationEnd) {
                $status = 'Undertime'; // Time-out before 5:00 PM but worked 6 hours or more
            } else {
                $status = 'Present'; // Worked 6 hours or more
            }

            $updateStmt->bind_param("ssi", $currentTime, $status, $attendance['id']);
            $updateStmt->execute();
            echo json_encode(["success" => true, "message" => "Time-out recorded.", "status" => $status]);
        } else {
            echo json_encode(["success" => false, "message" => "Attendance already logged."]);
        }
    } else {
        // If no record exists (unlikely due to default records), insert a new time-in record
        $insertQuery = "INSERT INTO attendance_log (employee_id, time_in, attendance_date, status) VALUES (?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);

        $timeIn = new DateTime($currentTime);
        $lateThreshold = new DateTime($currentDate . ' 08:00:00');
        $status = ($timeIn > $lateThreshold) ? 'Late' : 'Present';

        $insertStmt->bind_param("ssss", $employeeId, $currentTime, $currentDate, $status);

        if ($insertStmt->execute()) {
            echo json_encode(["success" => true, "message" => "Time-in recorded.", "status" => $status]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to log time-in."]);
        }
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "No employee ID."]);
}

$conn->close();
?>