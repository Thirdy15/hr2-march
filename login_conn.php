<?php
session_start();
include 'db/db_conn.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'phpmailer/vendor/autoload.php'; // Ensure PHPMailer is autoloaded

// Check if form data is set
$inputEmail = isset($_POST['email']) ? trim($_POST['email']) : '';
$inputPassword = isset($_POST['password']) ? trim($_POST['password']) : '';

// Basic validation
if (empty($inputEmail) || empty($inputPassword)) {
    $error = urlencode("Email and password fields are required.");
    header("Location: ../HR2/login.php?error=$error");
    exit();
}

// Password validation: at least one uppercase letter, one lowercase letter, one number, and one special character
$passwordPattern = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).+$/";
if (!preg_match($passwordPattern, $inputPassword)) {
    $error = urlencode("Password must be at least 8 characters long and contain at least one number, special character, uppercase and lowercase letter.");
    header("Location: ../HR2/login.php?error=$error");
    exit();
}

// Track failed login attempts
$failedAttemptsSql = "SELECT failed_attempts, last_failed_attempt, is_locked FROM login_attempts WHERE email = ?";
$stmt = $conn->prepare($failedAttemptsSql);
$stmt->bind_param("s", $inputEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $loginData = $result->fetch_assoc();
    $failedAttempts = $loginData['failed_attempts'];
    $lastFailedAttempt = strtotime($loginData['last_failed_attempt']);
    $isLocked = $loginData['is_locked'];
    $currentTime = time();

    // Reset failed attempts if it's been more than 30 minutes
    if ($currentTime - $lastFailedAttempt > 1800) {
        $failedAttempts = 0;
        $updateFailedAttemptsSql = "UPDATE login_attempts SET failed_attempts = 0, is_locked = 0 WHERE email = ?";
        $updateStmt = $conn->prepare($updateFailedAttemptsSql);
        $updateStmt->bind_param("s", $inputEmail);
        $updateStmt->execute();
        $updateStmt->close();
    }

    // Check if the account is locked
    if ($isLocked) {
        $error = urlencode("Your account is locked. Please check your email to unlock it.");
        header("Location: ../HR2/login.php?error=$error");
        exit();
    }

    // Lock the account after 3 failed attempts
    if ($failedAttempts >= 3) {
        $lockAccountSql = "UPDATE login_attempts SET is_locked = 1 WHERE email = ?";
        $lockStmt = $conn->prepare($lockAccountSql);
        $lockStmt->bind_param("s", $inputEmail);
        $lockStmt->execute();
        $lockStmt->close();

        // Send unlock email
        sendUnlockEmail($inputEmail);

        $error = urlencode("Your account has been locked. Please check your email to unlock it.");
        header("Location: ../HR2/login.php?error=$error");
        exit();
    }
} else {
    // Initialize failed login attempts for new user
    $insertSql = "INSERT INTO login_attempts (email, failed_attempts, last_failed_attempt, is_locked) VALUES (?, 0, NOW(), 0)";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param("s", $inputEmail);
    $insertStmt->execute();
    $insertStmt->close();
}

// Employee login check
$sql = "SELECT employee_id, password, role, department, face_image, face_descriptor FROM employee_register WHERE email = ?"; // Fetch face_image and face_descriptor
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $inputEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $employeeData = $result->fetch_assoc();

    // Verify the password
    if (password_verify($inputPassword, $employeeData['password'])) {
        // Reset failed attempts on successful login
        $resetAttemptsSql = "UPDATE login_attempts SET failed_attempts = 0, is_locked = 0 WHERE email = ?";
        $resetStmt = $conn->prepare($resetAttemptsSql);
        $resetStmt->bind_param("s", $inputEmail);
        $resetStmt->execute();
        $resetStmt->close();

        // Debug: Check face_image and face_descriptor values
        error_log("Face Image: " . $employeeData['face_image']);
        error_log("Face Descriptor: " . $employeeData['face_descriptor']);

        // Check if face_image and face_descriptor are missing or empty
        if (empty($employeeData['face_image']) || empty($employeeData['face_descriptor'])) {
            // Set a session flag to indicate face registration is required
            $_SESSION['face_registration_required'] = true;
            $_SESSION['employee_id'] = $employeeData['employee_id'];
            $_SESSION['role'] = $employeeData['role'];
            $_SESSION['department'] = $employeeData['department'];

            // Redirect back to the login page to show the modal
            header("Location: ../HR2/login.php");
            exit();
        } else {
            // If face_image and face_descriptor are valid, store user data in the session
            $_SESSION['employee_id'] = $employeeData['employee_id']; 
            $_SESSION['role'] = $employeeData['role']; // Store the user's role in the session
            $_SESSION['department'] = $employeeData['department']; // Store the user's department in the session

            // Set the timezone and record login activity
            date_default_timezone_set('Asia/Manila');
            $login_time = date("Y-m-d H:i:s");

            // Record login activity in the user_activity table
            $activitySql = "INSERT INTO user_activity (user_id, login_time) VALUES (?, ?)";
            $activityStmt = $conn->prepare($activitySql);
            $activityStmt->bind_param("is", $_SESSION['employee_id'], $login_time);
            $activityStmt->execute();
            $activityStmt->close();

            // Redirect based on role
            if ($employeeData['role'] === 'Staff') {
                $stmt->close();
                $conn->close();
                header("Location: ../HR2/employee/staff/dashboard.php");
                exit();
            } elseif ($employeeData['role'] === 'Supervisor') {
                $stmt->close();
                $conn->close();
                header("Location: ../HR2/employee/supervisor/dashboard.php");
                exit();
            } elseif ($employeeData['role'] === 'Field Worker') {
                $stmt->close();
                $conn->close();
                header("Location: ../HR2/employee/fieldworker/dashboard.php");
                exit();
            } elseif ($employeeData['role'] === 'Contractual') {
                $stmt->close();
                $conn->close();
                header("Location: ../HR2/employee/contractual/dashboard.php");
                exit();
            } else {
                // Handle other roles or unknown roles
                $error = urlencode("Invalid role detected.");
                $stmt->close();
                $conn->close();
                header("Location: ../HR2/login.php?error=$error");
                exit();
            }
        }
    } else {
        // Increment failed login attempts
        incrementFailedAttempts($inputEmail);
        $error = urlencode("Invalid email or password.");
        $stmt->close();
        $conn->close();
        header("Location: ../HR2/login.php?error=$error");
        exit();
    }
}

// Admin login check if employee login fails
$sql = "SELECT a_id, password, role FROM admin_register WHERE email = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $inputEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $adminData = $result->fetch_assoc();

    // Verify the password
    if (password_verify($inputPassword, $adminData['password'])) {
        // Reset failed attempts on successful login
        $resetAttemptsSql = "UPDATE login_attempts SET failed_attempts = 0, is_locked = 0 WHERE email = ?";
        $resetStmt = $conn->prepare($resetAttemptsSql);
        $resetStmt->bind_param("s", $inputEmail);
        $resetStmt->execute();
        $resetStmt->close();

        $_SESSION['a_id'] = $adminData['a_id']; 
        $_SESSION['role'] = $adminData['role']; // Store the admin's role in the session

        date_default_timezone_set('Asia/Manila');

        $login_time = date("Y-m-d H:i:s");

        // Record login activity in the user_activity table
        $activitySql = "INSERT INTO user_activity (user_id, login_time) VALUES (?, ?)";
        $activityStmt = $conn->prepare($activitySql);
        $activityStmt->bind_param("is", $_SESSION['a_id'], $login_time);
        $activityStmt->execute();
        $activityStmt->close();

        // Redirect to admin dashboard
        $stmt->close();
        $conn->close();
        header("Location: ../HR2/admin/dashboard.php");
        exit();
    } else {
        // Increment failed login attempts
        incrementFailedAttempts($inputEmail);
        $error = urlencode("Invalid email or password.");
        $stmt->close();
        $conn->close();
        header("Location: ../HR2/login.php?error=$error");
        exit();
    }
} else {
    // If no match is found in both employee_register and admin_register
    incrementFailedAttempts($inputEmail);
    $error = urlencode("Invalid email or password.");
    $stmt->close();
    $conn->close();
    header("Location: ../HR2/login.php?error=$error");
    exit();
}

// Function to increment failed login attempts
function incrementFailedAttempts($email) {
    global $conn;

    $updateSql = "UPDATE login_attempts SET failed_attempts = failed_attempts + 1, last_failed_attempt = NOW() WHERE email = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("s", $email);
    $updateStmt->execute();
    $updateStmt->close();
}

// Function to send an unlock email
function sendUnlockEmail($email) {
    global $conn;

    // Set the default timezone to Philippine Time (PHT)
    date_default_timezone_set('Asia/Manila');

    // Generate a unique token
    $token = bin2hex(random_bytes(32));
    $expiry = date("Y-m-d H:i:s", strtotime("+30 minutes")); // Token expires in 30 minutes (PHT)

    // Store the token in the database
    $tokenSql = "UPDATE login_attempts SET unlock_token = ?, token_expiry = ?, is_used = 0 WHERE email = ?";
    $tokenStmt = $conn->prepare($tokenSql);
    $tokenStmt->bind_param("sss", $token, $expiry, $email);
    $tokenStmt->execute();
    $tokenStmt->close();

    // Send the email
    $mail = new PHPMailer(true);
    try {
        // Email setup
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // Set the SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'microfinancehr2@gmail.com';  // SMTP username
        $mail->Password = 'yjla pidq jfdr qbnz';  // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Sender and recipient
        $mail->setFrom('microfinancehr2@gmail.com', 'HR System');
        $mail->addAddress($email);

        // Email content
        $mail->Subject = 'Unlock Your Account';
        $mail->Body    = "Your account has been locked due to multiple failed login attempts. Click the link below to unlock your account:\n\n";
        $mail->Body   .= "http://localhost/HR2/unlock_account.php?email=" . urlencode($email) . "&token=" . urlencode($token);

        $mail->send();
    } catch (Exception $e) {
        error_log("Failed to send email. Error: " . $mail->ErrorInfo);
    }
}
?>