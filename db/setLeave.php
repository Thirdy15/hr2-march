<?php
session_start();
include '../db/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data and explicitly cast it to integers
    $employee_id = $_POST['employee_id'];
    $gender = $_POST['gender']; // Get the selected gender from the form
    $bereavement_leave = (int) ($_POST['bereavement_leave'] ?? 0);
    $emergency_leave = (int) ($_POST['emergency_leave'] ?? 0);
    $maternity_leave = (int) ($_POST['maternity_leave'] ?? 0);
    $mcw_special_leave = (int) ($_POST['mcw_special_leave'] ?? 0);
    $parental_leave = (int) ($_POST['parental_leave'] ?? 0);
    $service_incentive_leave = (int) ($_POST['service_incentive_leave'] ?? 0);
    $sick_leave = (int) ($_POST['sick_leave'] ?? 0);
    $vacation_leave = (int) ($_POST['vacation_leave'] ?? 0);
    $vawc_leave = (int) ($_POST['vawc_leave'] ?? 0);
    $bereavement_leave_male = (int) ($_POST['bereavement_leave_male'] ?? 0);
    $emergency_leave_male = (int) ($_POST['emergency_leave_male'] ?? 0);
    $parental_leave_male = (int) ($_POST['parental_leave_male'] ?? 0);
    $paternity_leave_male = (int) ($_POST['paternity_leave_male'] ?? 0);
    $service_incentive_leave_male = (int) ($_POST['service_incentive_leave_male'] ?? 0);
    $sick_leave_male = (int) ($_POST['sick_leave_male'] ?? 0);
    $vacation_leave_male = (int) ($_POST['vacation_leave_male'] ?? 0);

    // Debugging: Check employee_id and gender
    echo "Employee ID: " . $employee_id . "<br>"; // Debugging line
    echo "Selected Gender: " . $gender . "<br>"; // Debugging line

    // Handle "All Employees" case
    if ($employee_id === 'all') {
        // Fetch all employees of the selected gender
        $employees_query = "SELECT employee_id, first_name, last_name, gender FROM employee_register WHERE gender = ?";
        if ($employees_stmt = $conn->prepare($employees_query)) {
            $employees_stmt->bind_param("s", $gender);
            $employees_stmt->execute();
            $employees_result = $employees_stmt->get_result();

            if ($employees_result->num_rows > 0) {
                while ($employee = $employees_result->fetch_assoc()) {
                    $employee_id = $employee['employee_id'];
                    $employee_name = $employee['first_name'] . ' ' . $employee['last_name'];
                    $employee_gender = $employee['gender'];

                    // Allocate leave for each employee of the selected gender
                    allocateLeave($conn, $employee_id, $employee_name, $employee_gender, $bereavement_leave, $emergency_leave, $maternity_leave, $mcw_special_leave, $parental_leave, $service_incentive_leave, $sick_leave, $vacation_leave, $vawc_leave, $bereavement_leave_male, $emergency_leave_male, $parental_leave_male, $paternity_leave_male, $service_incentive_leave_male, $sick_leave_male, $vacation_leave_male);
                }
            } else {
                echo "No employees found for the selected gender: " . $gender . "<br>";
            }

            $employees_stmt->close();
        } else {
            echo "Error preparing employees query: " . $conn->error . "<br>";
            exit;
        }
    } else {
        // Fetch employee's personal info (firstname, lastname, gender)
        $employee_info_query = "SELECT first_name, last_name, gender FROM employee_register WHERE employee_id = ?";
        if ($info_stmt = $conn->prepare($employee_info_query)) {
            $info_stmt->bind_param("s", $employee_id);
            $info_stmt->execute();
            $info_result = $info_stmt->get_result();

            if ($info_result->num_rows > 0) {
                $employee_info = $info_result->fetch_assoc();
                $employee_name = $employee_info['first_name'] . ' ' . $employee_info['last_name'];
                $employee_gender = $employee_info['gender'];

                // Allocate leave for the selected employee
                allocateLeave($conn, $employee_id, $employee_name, $employee_gender, $bereavement_leave, $emergency_leave, $maternity_leave, $mcw_special_leave, $parental_leave, $service_incentive_leave, $sick_leave, $vacation_leave, $vawc_leave, $bereavement_leave_male, $emergency_leave_male, $parental_leave_male, $paternity_leave_male, $service_incentive_leave_male, $sick_leave_male, $vacation_leave_male);
            } else {
                echo "Error fetching employee info. No employee found with ID: " . $employee_id . "<br>"; // Debugging line
                exit;
            }

            $info_stmt->close();
        } else {
            echo "Error preparing employee info query: " . $conn->error . "<br>";
            exit;
        }
    }
} else {
    echo "Invalid request method.";
}

// Close the database connection
$conn->close();

/**
 * Function to allocate leave for an employee
 */
function allocateLeave($conn, $employee_id, $employee_name, $gender, $bereavement_leave, $emergency_leave, $maternity_leave, $mcw_special_leave, $parental_leave, $service_incentive_leave, $sick_leave, $vacation_leave, $vawc_leave, $bereavement_leave_male, $emergency_leave_male, $parental_leave_male, $paternity_leave_male, $service_incentive_leave_male, $sick_leave_male, $vacation_leave_male) {
    // Check if the employee already exists in the employee_leaves table
    $check_query = "SELECT * FROM employee_leaves WHERE employee_id = ?";
    if ($stmt = $conn->prepare($check_query)) {
        $stmt->bind_param("s", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // If the employee exists, update the leave balances by adding the new values
        if ($result->num_rows > 0) {
            $update_query = "UPDATE employee_leaves 
                             SET bereavement_leave = bereavement_leave + ?, 
                                 emergency_leave = emergency_leave + ?,  
                                 maternity_leave = maternity_leave + ?, 
                                 mcw_special_leave = mcw_special_leave + ?, 
                                 parental_leave = parental_leave + ?, 
                                 service_incentive_leave = service_incentive_leave + ?, 
                                 sick_leave = sick_leave + ?, 
                                 vacation_leave = vacation_leave + ?, 
                                 vawc_leave = vawc_leave + ?, 
                                 bereavement_leave_male = bereavement_leave_male + ?, 
                                 emergency_leave_male = emergency_leave_male + ?, 
                                 parental_leave_male = parental_leave_male + ?, 
                                 paternity_leave_male = paternity_leave_male + ?, 
                                 service_incentive_leave_male = service_incentive_leave_male + ?, 
                                 sick_leave_male = sick_leave_male + ?, 
                                 vacation_leave_male = vacation_leave_male + ?, 
                                 employee_name = ?, 
                                 gender = ?
                             WHERE employee_id = ?";
            if ($update_stmt = $conn->prepare($update_query)) {
                // Bind parameters for UPDATE query
                $update_stmt->bind_param("iiiiiiiiiiiiiiiisss",
                    $bereavement_leave,        // i (integer)
                    $emergency_leave,          // i (integer)
                    $maternity_leave,          // i (integer)
                    $mcw_special_leave,        // i (integer)
                    $parental_leave,           // i (integer)
                    $service_incentive_leave,  // i (integer)
                    $sick_leave,               // i (integer)
                    $vacation_leave,           // i (integer)
                    $vawc_leave,               // i (integer)
                    $bereavement_leave_male,   // i (integer)
                    $emergency_leave_male,     // i (integer)
                    $parental_leave_male,      // i (integer)
                    $paternity_leave_male,     // i (integer)
                    $service_incentive_leave_male, // i (integer)
                    $sick_leave_male,          // i (integer)
                    $vacation_leave_male,      // i (integer)
                    $employee_name,            // s (string)
                    $gender,                   // s (string)
                    $employee_id               // s (string)
                );

                if ($update_stmt->execute()) {
                    echo "Leave allocation updated successfully for $employee_name!<br>";
                } else {
                    echo "Error updating leave allocation for $employee_name: " . $update_stmt->error . "<br>";
                }

                $update_stmt->close();
            } else {
                echo "Error preparing update query for $employee_name: " . $conn->error . "<br>";
            }
        } else {
            // If employee doesn't exist, insert a new record
            $insert_query = "INSERT INTO employee_leaves 
                             (employee_id, employee_name, gender, bereavement_leave, emergency_leave, maternity_leave, 
                              mcw_special_leave, parental_leave, service_incentive_leave, sick_leave, 
                              vacation_leave, vawc_leave, bereavement_leave_male, emergency_leave_male, paternity_leave_male, 
                              parental_leave_male, service_incentive_leave_male, sick_leave_male, vacation_leave_male) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            if ($insert_stmt = $conn->prepare($insert_query)) {
                // Bind parameters for INSERT query
                $insert_stmt->bind_param("sssiiiiiiiiiiiiiiii", 
                    $employee_id,              // s (string)
                    $employee_name,            // s (string)
                    $gender,                   // s (string)
                    $bereavement_leave,        // i (integer)
                    $emergency_leave,          // i (integer)
                    $maternity_leave,          // i (integer)
                    $mcw_special_leave,        // i (integer)
                    $parental_leave,           // i (integer)
                    $service_incentive_leave,  // i (integer)
                    $sick_leave,               // i (integer)
                    $vacation_leave,           // i (integer)
                    $vawc_leave,               // i (integer)
                    $bereavement_leave_male,   // i (integer)
                    $emergency_leave_male,     // i (integer)
                    $parental_leave_male,      // i (integer)
                    $paternity_leave_male,     // i (integer)
                    $service_incentive_leave_male, // i (integer)
                    $sick_leave_male,          // i (integer)
                    $vacation_leave_male       // i (integer)
                );

                if ($insert_stmt->execute()) {
                    echo "Leave allocation added successfully for $employee_name!<br>";
                } else {
                    echo "Error inserting leave allocation for $employee_name: " . $insert_stmt->error . "<br>";
                }

                $insert_stmt->close();
            } else {
                echo "Error preparing insert query for $employee_name: " . $conn->error . "<br>";
            }
        }

        $stmt->close();
    } else {
        echo "Error preparing check query for $employee_name: " . $conn->error . "<br>";
    }
}
?>