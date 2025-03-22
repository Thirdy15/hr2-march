<?php
session_start();

include '../db/db_conn.php';

// Fetch user ID from session
$employeeId = $_SESSION['employee_id'];

// Prepare and bind
$stmt = $conn->prepare("UPDATE employee_register SET firstname = ?, middlename = ?, lastname = ?, birthdate = ?, email = ?, phone_number = ?, address = ? WHERE employee_id = ?");
$stmt->bind_param("ssssssss", $firstname, $middlename, $lastname, $birthdate, $email, $phone_number, $address, $employeeId);

// Get form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST['firstname'];
    $middlename =$_POST['middlename'];
    $lastname = $_POST['lastname'];
    $birthdate = $_POST['birthdate'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $profilePicture = $_FILES['profile_picture'];
        $targetDir = "../uploads/"; // Ensure this directory exists and is writable
        $targetFile = $targetDir . basename($profilePicture['name']);
        
        // Move the uploaded file
        if (move_uploaded_file($profilePicture['tmp_name'], $targetFile)) {
            // Prepare to update the profile picture path in the database
            $stmtPic = $conn->prepare("UPDATE employee_register SET pfp = ? WHERE employee_id = ?");
            $stmtPic->bind_param("ss", $targetFile, $userId);
            $stmtPic->execute();
            $stmtPic->close();
        } else {
            echo "Error uploading profile picture.";
        }
    }

    // Execute the statement to update user information
    if ($stmt->execute()) {
        // Set success message
        $_SESSION['update_success'] = "Your information has been updated successfully.";
        header("Location: ../employee/staff/profile.php");
        exit();
    } else {
        echo "Error updating profile: " . $stmt->error;
    }
}

$stmt->close();
$conn->close();
?>