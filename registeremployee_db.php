<?php
include 'db/db_conn.php'; // Include database connection

// Get the email from the form
$email = $_POST['email'];

// Check if the email exists in the database
$checkQuery = "SELECT email FROM employee_register WHERE email = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Email does not exist. Please check the email and try again.'); window.history.back();</script>";
    exit();
}

// Check if the email already has face data
$checkFaceDataQuery = "SELECT face_descriptor, face_image FROM employee_register WHERE email = ?";
$stmt = $conn->prepare($checkFaceDataQuery);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (!is_null($row['face_descriptor']) || !is_null($row['face_image'])) {
        echo "<script>alert('This email already has face data. Please choose another email.'); window.history.back();</script>";
        exit();
    }
}

// Handle face descriptor
$faceDescriptor = $_POST['face_descriptor'] ?? null;
if (empty($faceDescriptor)) {
    echo "<script>alert('No face descriptor received. Please try again.'); window.history.back();</script>";
    exit();
}

// Decode the face descriptor string into an array
$faceDescriptorArray = json_decode($faceDescriptor);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "<script>alert('Invalid face descriptor format.'); window.history.back();</script>";
    exit();
}

// Handle multiple photo uploads (maximum 3 images)
$uploadedPhotos = [];
if (isset($_FILES['photo']) && count($_FILES['photo']['name']) > 0) {
    $maxFiles = 3; // Limit to 3 files
    $targetDirectory = $_SERVER['DOCUMENT_ROOT'] . '/HR2/face/';  // Absolute path
    
    // Ensure the target directory exists, if not create it
    if (!file_exists($targetDirectory)) {
        if (!mkdir($targetDirectory, 0755, true)) {
            echo "<script>alert('Failed to create upload directory.'); window.history.back();</script>";
            exit();
        }
    }

    // Loop through all uploaded files
    for ($i = 0; $i < min(count($_FILES['photo']['name']), $maxFiles); $i++) {
        if ($_FILES['photo']['error'][$i] === UPLOAD_ERR_OK) {
            $photoTmpName = $_FILES['photo']['tmp_name'][$i];
            $photoName = $_FILES['photo']['name'][$i];
            $targetFilePath = $targetDirectory . basename($photoName);

            // Validate the file type (image/jpeg, image/png, etc.)
            $allowedFileTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            $fileType = mime_content_type($photoTmpName);

            // Check extension as well
            $fileExtension = pathinfo($photoName, PATHINFO_EXTENSION);
            $allowedExtensions = ['jpg', 'jpeg', 'png'];

            if (in_array($fileType, $allowedFileTypes) && in_array(strtolower($fileExtension), $allowedExtensions)) {
                // Move the uploaded file to the uploads folder
                if (move_uploaded_file($photoTmpName, $targetFilePath)) {
                    $uploadedPhotos[] = $targetFilePath;
                } else {
                    echo "<script>alert('Error uploading the image. Please try again.'); window.history.back();</script>";
                    exit();
                }
            } else {
                echo "<script>alert('Invalid file type. Only JPEG or PNG images are allowed.'); window.history.back();</script>";
                exit();
            }
        } else {
            echo "<script>alert('Error in file upload. Error code: " . $_FILES['photo']['error'][$i] . "'); window.history.back();</script>";
            exit();
        }
    }
} else {
    echo "<script>alert('No photos uploaded. Please try again.'); window.history.back();</script>";
    exit();
}

// Update face data for the specified email
$updateQuery = "UPDATE employee_register SET face_descriptor = ?, face_image = ? WHERE email = ?";
$stmt = $conn->prepare($updateQuery);
$stmt->bind_param("sss", json_encode($faceDescriptorArray), json_encode($uploadedPhotos), $email);

if ($stmt->execute()) {
    echo "<script>alert('Face data registered successfully!'); window.location.href='login.php';</script>";
} else {
    echo "<script>alert('Error registering face data. Please try again.'); window.history.back();</script>";
}

$stmt->close();
$conn->close();
?>

