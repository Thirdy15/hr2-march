<?php
session_start();

include '../../db/db_conn.php';

// Fetch user ID from session
$employeeId = $_SESSION['employee_id'];

// Prepare and bind
$stmt = $conn->prepare("UPDATE employee_register SET first_name = ?, middle_name = ?, last_name = ?, birthdate = ?, email = ?, phone_number = ?, address = ? WHERE employee_id = ?");
$stmt->bind_param("sssssssi", $firstname, $middlename, $lastname, $birthdate, $email, $phone_number, $address, $employeeId);

// Get form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST['first_name'];
    $middlename = $_POST['middle_name'];
    $lastname = $_POST['last_name'];
    $birthdate = $_POST['birthdate'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];

// Execute the statement to update user information
if ($stmt->execute()) {
    // Set success message in session
    $_SESSION['update_success'] = "Your information has been updated successfully.";
} else {
    // Set error message in session
    $_SESSION['update_error'] = "Error updating profile: " . $stmt->error;
}

    // Redirect back to the profile page
    header("Location: ../../employee/supervisor/profile.php");  // Adjust path if needed
    exit();
}


$stmt->close();
$conn->close();
?>
