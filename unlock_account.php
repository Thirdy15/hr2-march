<?php
session_start();
include 'db/db_conn.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set the default timezone to Philippine Time (PHT)
date_default_timezone_set('Asia/Manila');

// Get email and token from the URL
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($email) || empty($token)) {
    die("Invalid request.");
}

// Check if the token is valid, not expired, and not used
$sql = "SELECT email FROM login_attempts WHERE email = ? AND unlock_token = ? AND token_expiry > NOW() AND is_used = 0";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("ss", $email, $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Mark the token as used
    $updateSql = "UPDATE login_attempts SET is_used = 1 WHERE email = ? AND unlock_token = ?";
    $updateStmt = $conn->prepare($updateSql);
    if ($updateStmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $updateStmt->bind_param("ss", $email, $token);
    $updateStmt->execute();
    $updateStmt->close();

    // Unlock the account
    $unlockSql = "UPDATE login_attempts SET is_locked = 0, failed_attempts = 0 WHERE email = ?";
    $unlockStmt = $conn->prepare($unlockSql);
    if ($unlockStmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $unlockStmt->bind_param("s", $email);
    $unlockStmt->execute();
    $unlockStmt->close();

    echo "Your account has been unlocked. You can now <a href='/HR2/login.php'>log in</a>.";
} else {
    echo "Invalid, expired, or already used token.";
}

$stmt->close();
$conn->close();
?>