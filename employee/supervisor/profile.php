<?php
session_start();
include '../../db/db_conn.php';
include '../../phpqrcode/qrlib.php'; // Include phpqrcode library

if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Supervisor') {
    header("Location: ../../login.php");
    exit();
}

if (isset($_SESSION['update_success'])) {
    echo '<script>alert("' . htmlspecialchars($_SESSION['update_success']) . '");</script>';
    unset($_SESSION['update_success']);
}

// Fetch user info
$employeeId = $_SESSION['employee_id'];
$sql = "SELECT
    e.employee_id, e.first_name, e.middle_name, e.last_name, e.birthdate, e.gender, e.email, e.created_at,
    e.role, e.position, e.department, e.phone_number, e.address, e.pfp,
    ua.login_time,
    -- Fetch the last valid logout time
    (SELECT ua2.logout_time
     FROM user_activity ua2
     WHERE ua2.user_id = e.employee_id
     AND ua2.logout_time IS NOT NULL
     ORDER BY ua2.logout_time ASC
     LIMIT 1) AS last_logout_time
FROM
    employee_register e
LEFT JOIN
    user_activity ua ON e.employee_id = ua.user_id
WHERE
    e.employee_id = ?
ORDER BY
    ua.login_time DESC
LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $employeeInfo = $result->fetch_assoc();
} else {
    $employeeInfo = null;
}

$stmt->close();
$conn->close();

// Generate QR Code content
$qrData = 'Employee ID: ' . $employeeInfo['employee_id'] . ' | Email: ' . $employeeInfo['email'];

$qrCodeDir = '../qrcodes/';
if (!is_dir($qrCodeDir)) {
    mkdir($qrCodeDir, 0755, true); // Create the directory if it doesn't exist
}

// Path to store the generated QR Code image
$qrImagePath = '../qrcodes/employee_' . $employeeId . '.png';

// Generate QR Code and save it as a PNG image
QRcode::png($qrData, $qrImagePath, QR_ECLEVEL_L, 4);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="User Profile Dashboard" />
    <meta name="author" content="Your Name" />
    <title>My Profile | HR2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../../css/styles.css" rel="stylesheet" />
    <link href="../../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --dark-bg: #121212;
            --card-bg: rgba(33, 37, 41) !important;
            --border-color: #333;
            --text-primary: #f8f9fa;
            --text-secondary: #adb5bd;
            --success: #4ade80;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        .navbar {
            background-color: var(--card-bg) !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .sidebar {
            background-color: var(--card-bg);
            border-right: 1px solid var(--border-color);
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            border-bottom: 1px solid var(--border-color);
            background-color: transparent;
            padding: 1.25rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .form-control {
            background-color: rgba(30, 30, 30, 0.8);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            transition: all 0.3s;
            padding: 0.75rem 1rem;
            font-size: 1.1rem;
        }

        .form-control:focus {
            background-color: rgba(40, 40, 40, 0.8);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }

        .form-floating label {
            color: var(--text-secondary);
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: var(--primary-color);
        }

        .profile-picture {
            width: 230px;
            height: 230px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--primary-color);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
        }

        .profile-picture:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
        }

        .initials-avatar {
            width: 230px;
            height: 230px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: var(--card-bg);
            color: white;
            font-size: 64px;
            font-weight: bold;
            border: 4px solid var(--primary-color);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
        }

        .initials-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
        }

        .modal-content {
            background-color: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .modal-header, .modal-footer {
            border-color: var(--border-color);
        }

        .dropdown-menu {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .dropdown-item {
            color: var(--text-primary);
        }

        .dropdown-item:hover {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .form-label-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .form-label-group label {
            position: absolute;
            top: -10px;
            left: 10px;
            background-color: var(--card-bg);
            padding: 0 8px;
            font-size: 13px;
            color: var(--text-secondary);
            z-index: 1;
        }

        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .activity-card {
            border-left: 4px solid var(--primary-color);
        }

        .loading-spinner {
            width: 3rem;
            height: 3rem;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            border-top: 4px solid var(--primary-color);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        * {
            transition: background-color 0.3s, border-color 0.3s, color 0.3s;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .profile-picture, .initials-avatar {
                width: 180px;
                height: 180px;
                margin-top: -50px;
            }

            .col-lg-3 {
                margin-top: -50px;
                padding: 0 1rem;
            }

            .col-lg-9 {
                padding: 0 1rem;
            }
        }

        @media (max-width: 768px) {
            .profile-picture, .initials-avatar {
                width: 150px;
                height: 150px;
                margin-top: -30px;
            }

            .col-lg-3, .col-lg-9 {
                text-align: center;
                margin-top: 20px;
            }

            .form-label-group {
                text-align: left;
            }
        }

        @media (max-width: 576px) {
            .profile-picture, .initials-avatar {
                width: 120px;
                height: 120px;
                margin-top: -20px;
            }

            .col-lg-3, .col-lg-9 {
                margin-top: 10px;
            }
        }
    </style>
</head>
<body class="sb-nav-fixed bg-black">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main class="">
                <div class="container-fluid position-relative px-4">
                    <div class="d-flex justify-content-between align-items-center mb-4 ">
                        <h1 class="fw-bold">My Profile</h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                                <li class="breadcrumb-item active border-secondary text-light" aria-current="page">Profile</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="container-fluid" id="calendarContainer"
                    style="position: fixed; top: 7%; right: 40; z-index: 1050;
                    max-width: 100%; display: none;">
                    <div class="row">
                        <div class="col-md-9 mx-auto">
                            <div id="calendar" class="p-2"></div>
                        </div>
                    </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-body p-0">
                                    <div class="row g-0">
                                        <!-- Profile Header with Background -->
                                        <div class="col-12 bg-gradient" style="background: linear-gradient(135deg, #4361ee, #3a0ca3); height: 150px; border-radius: 12px 12px 0 0;"></div>

                                        <!-- Profile Picture Section -->
                                        <div class="col-lg-3 text-center d-flex flex-column align-items-center" style="margin-top: -75px; padding: 0 2rem;">
                                            <?php
                                            // Check if a custom profile picture exists
                                            if (!empty($employeeInfo['pfp']) && $employeeInfo['pfp'] !== 'defaultpfp.png') {
                                                // Display the custom profile picture
                                                echo '<img src="' . htmlspecialchars($employeeInfo['pfp']) . '"
                                                    class="profile-picture"
                                                    alt="Profile Picture"
                                                    id="profilePic" data-bs-toggle="modal" data-bs-target="#profilePicModal" />';
                                            } else {
                                                // Generate initials from the first name and last name
                                                $firstName = $employeeInfo['first_name'] ?? '';
                                                $lastName = $employeeInfo['last_name'] ?? '';
                                                $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

                                                // Display the initials in a circular container
                                                echo '<div class="initials-avatar"
                                                    id="profilePic" data-bs-toggle="modal" data-bs-target="#profilePicModal">' . $initials . '</div>';
                                            }
                                            ?>

                                            <h4 class="mt-3 mb-1 fw-bold border-secondary text-light"><?php echo htmlspecialchars($employeeInfo['first_name'] . ' ' . $employeeInfo['last_name']); ?></h4>
                                            <p class="text-muted mb-3"><?php echo htmlspecialchars($employeeInfo['role']); ?></p>

                                            <div class="d-grid gap-2 mb-4">
                                                <div class="dropdown">
                                                    <button class="btn btn-primary w-100 btn-icon" type="button" id="editPictureDropdown"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-user-cog"></i>
                                                        Profile Settings
                                                    </button>
                                                    <ul class="dropdown-menu w-100" aria-labelledby="editPictureDropdown">
                                                        <li>
                                                            <a class="dropdown-item fw-medium" href="javascript:void(0);" id="changePictureOption">
                                                                <i class="fas fa-user-edit me-2"></i>Change Profile Picture
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button class="dropdown-item fw-medium text-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteProfilePictureModal">
                                                                <i class="fa fa-trash me-2"></i>Delete Picture
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <a href="../admin/change_pass.php" class="btn btn-outline-primary btn-icon">
                                                    <i class="fas fa-key"></i>
                                                    Change Password
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Profile Information Section -->
                                        <div class="col-lg-9 ps-lg-4">
                                            <div class="p-4">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <h5 class="fw-bold mb-4 border-bottom pb-2 border-secondary text-light">Personal Information</h5>
                                                    </div>
                                                </div>

                                                <div class="row g-4 ">
                                                    <div class="col-md-4">
                                                        <div class="form-label-group">
                                                            <label>Full Name</label>
                                                            <input class="form-control" value="<?php echo htmlspecialchars($employeeInfo['first_name'] . ' ' . $employeeInfo['middle_name'] . ' ' . $employeeInfo['last_name']); ?>" readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-label-group">
                                                            <label>ID Number</label>
                                                            <input class="form-control" value="<?php echo htmlspecialchars($employeeInfo['employee_id']); ?>" readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-label-group">
                                                            <label>Gender</label>
                                                            <input class="form-control" value="<?php echo htmlspecialchars($employeeInfo['gender']); ?>" readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-label-group">
                                                            <label>Role</label>
                                                            <input class="form-control" value="<?php echo htmlspecialchars($employeeInfo['role']); ?>" readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-label-group">
                                                            <label>Department</label>
                                                            <input class="form-control" value="<?php echo htmlspecialchars($employeeInfo['department']); ?>" readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="form-label-group">
                                                            <label>Email</label>
                                                            <input class="form-control" value="<?php echo htmlspecialchars($employeeInfo['email']); ?>" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Information Card -->
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 fw-bold border-secondary text-light">Edit Information</h5>
                                    <button type="button" id="editButton" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit me-2"></i>Edit
                                    </button>
                                </div>
                                <div class="card-body">
                                    <form id="infoForm" action="/HR2/employee_db/supervisor/update_profile.php" method="post">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <div class="form-label-group">
                                                    <label>First Name</label>
                                                    <input type="text" class="form-control" id="inputfName" name="first_name"
                                                        value="<?php echo htmlspecialchars($employeeInfo['first_name']); ?>" readonly required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-label-group">
                                                    <label>Middle Name</label>
                                                    <input type="text" class="form-control" id="inputmName" name="middlename"
                                                        value="<?php echo htmlspecialchars($employeeInfo['middle_name']); ?>" readonly required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-label-group">
                                                    <label>Last Name</label>
                                                    <input type="text" class="form-control" id="inputlName" name="last_name"
                                                        value="<?php echo htmlspecialchars($employeeInfo['last_name']); ?>" readonly required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-label-group">
                                                    <label>Birthdate</label>
                                                    <input type="date" class="form-control" id="inputbirth" name="birthdate"
                                                        value="<?php echo htmlspecialchars($employeeInfo['birthdate']); ?>" readonly required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-label-group">
                                                    <label>Email Address</label>
                                                    <input type="email" class="form-control" id="inputEmail" name="email"
                                                        value="<?php echo htmlspecialchars($employeeInfo['email']); ?>" readonly required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-label-group">
                                                    <label>Phone Number</label>
                                                    <input type="tel" class="form-control" id="inputPhone" name="phone_number"
                                                        value="<?php echo htmlspecialchars($employeeInfo['phone_number']); ?>" readonly required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-label-group">
                                                    <label>Address</label>
                                                    <input class="form-control" id="inputAddress" name="address"
                                                        value="<?php echo htmlspecialchars($employeeInfo['address']); ?>" readonly required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end mt-4">
                                            <button type="button" class="btn btn-primary d-none" id="saveButton" data-bs-toggle="modal" data-bs-target="#saveChangesModal">
                                                <i class="fas fa-save me-2"></i>Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Activity Card -->
                            <div class="card activity-card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0 fw-bold border-secondary text-light">
                                        <i class="fas fa-history me-2 border-secondary text-light"></i>
                                        User Activity
                                    </h5>
                                </div>
                                <div class="card-body border-secondary text-light">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3">
                                            <span class="badgeT bg-primary p-2 rounded-circle">
                                                <i class="fas fa-sign-in-alt"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 ">Last Login</h6>
                                            <p class="text-muted mb-0 ">
                                                <?php
                                                    if (!empty($employeeInfo['login_time'])) {
                                                        $login = strtotime($employeeInfo['login_time']);
                                                        echo date("l, F j, Y | g:i A", $login);
                                                    } else {
                                                        echo "No login time available";
                                                    }
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <span class="badgeT bg-danger p-2 rounded-circle">
                                                <i class="fas fa-sign-out-alt"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Last Logout</h6>
                                            <p class="text-muted mb-0">
                                                <?php
                                                    if (!empty($employeeInfo['last_logout_time'])) {
                                                        $logout = strtotime($employeeInfo['last_logout_time']);
                                                        echo date("l, F j, Y | g:i A", $logout);
                                                    } else {
                                                        echo "No logout time available";
                                                    }
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Profile Picture Modal -->
    <div class="modal fade" id="profilePicModal" tabindex="-1" aria-labelledby="profilePicModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="profilePicModalLabel">
                        <?php echo htmlspecialchars($employeeInfo['first_name'] . ' ' . $employeeInfo['last_name']); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex justify-content-center align-items-center">
                    <?php
                    // Check if a custom profile picture exists
                    if (!empty($employeeInfo['pfp']) && $employeeInfo['pfp'] !== 'defaultpfp.png') {
                        // Display the custom profile picture
                        echo '<img src="' . htmlspecialchars($employeeInfo['pfp']) . '"
                            class="img-fluid rounded"
                            style="max-height: 400px; object-fit: cover;"
                            alt="Profile Picture" />';
                    } else {
                        // Generate initials from the first name and last name
                        $firstName = $employeeInfo['first_name'] ?? '';
                        $lastName = $employeeInfo['last_name'] ?? '';
                        $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

                        // Display the initials in a circular container
                        echo '<div class="rounded-circle d-flex justify-content-center align-items-center"
                            style="width: 300px; height: 300px; background: linear-gradient(135deg, #4361ee, #3a0ca3); color: white; font-size: 120px; font-weight: bold;">' . $initials . '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Profile Picture Modal -->
    <div class="modal fade" id="deleteProfilePictureModal" tabindex="-1" aria-labelledby="deleteProfilePictureLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteProfilePictureLabel">Delete Profile Picture?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                        <p class="mt-3">Are you sure you want to delete your profile picture? This action cannot be undone.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <form action="/HR2/employee_db/supervisor/delete_employee_pfp.php" method="post">
                        <input type="hidden" name="employeeId" value="<?php echo $employeeInfo['employee_id']; ?>">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Changes Modal -->
    <div class="modal fade" id="saveChangesModal" tabindex="-1" aria-labelledby="saveChangesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="saveChangesModalLabel">Confirm Save?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-question-circle text-primary" style="font-size: 1rem;"></i>
                        <p class="mt-3">Are you sure you want to save the changes to your information?</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmSave">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body d-flex flex-column align-items-center justify-content-center">
                    <div class="loading-spinner mb-3"></div>
                    <div class="text-light fw-bold">Please wait...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Form for Profile Picture Upload -->
    <form action="/HR2/employee_db/supervisor/update_employee_pfp.php" method="post" enctype="multipart/form-data" id="profilePictureForm" style="display:none;">
        <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*" onchange="showConfirmationModal();">
    </form>

    <!-- Confirmation Modal for Profile Picture -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Profile Picture Update</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <p>Are you sure you want to update your profile picture with the selected image?</p>
                        <div class="d-flex justify-content-center align-items-center mt-3">
                            <img id="modalProfilePicturePreview" src="#" alt="Selected Profile Picture" class="img-fluid rounded" style="max-height: 200px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitProfilePictureForm()">
                        <i class="fas fa-check me-2"></i>Update
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-sign-out-alt text-warning" style="font-size: 3rem;"></i>
                        <p class="mt-3">Are you sure you want to log out?</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="../../employee/logout.php" method="POST">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Edit button functionality
            const editButton = document.getElementById('editButton');
            const saveButton = document.getElementById('saveButton');
            const formInputs = document.querySelectorAll('#infoForm input');

            editButton.addEventListener('click', function() {
                // Enable all form inputs
                formInputs.forEach(input => {
                    input.removeAttribute('readonly');
                    input.classList.add('border-primary');
                });

                // Show save button, hide edit button
                saveButton.classList.remove('d-none');
                editButton.classList.add('d-none');
            });

            // Confirm save button
            document.getElementById('confirmSave').addEventListener('click', function() {
                // Show loading modal
                const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
                loadingModal.show();

                // Submit the form
                document.getElementById('infoForm').submit();
            });

            // Profile picture change
            document.getElementById('changePictureOption').addEventListener('click', function() {
                document.getElementById('profilePictureInput').click();
            });

            // Check for success message
            <?php if (isset($_SESSION['update_success'])) : ?>
                // Create a toast notification instead of alert
                const toastContainer = document.createElement('div');
                toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
                toastContainer.style.zIndex = '11';

                toastContainer.innerHTML = `
                    <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header bg-success text-white">
                            <strong class="me-auto">Success</strong>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            <?php echo $_SESSION['update_success']; ?>
                        </div>
                    </div>
                `;

                document.body.appendChild(toastContainer);

                // Auto hide after 5 seconds
                setTimeout(() => {
                    const toast = document.querySelector('.toast');
                    const bsToast = new bootstrap.Toast(toast);
                    bsToast.hide();
                }, 5000);
            <?php endif; ?>
        });

        // Show confirmation modal with image preview
        function showConfirmationModal() {
            const input = document.getElementById('profilePictureInput');
            const preview = document.getElementById('modalProfilePicturePreview');

            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result;
                    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
                    modal.show();
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        // Submit profile picture form
        function submitProfilePictureForm() {
            // Show loading modal
            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
            loadingModal.show();

            // Hide confirmation modal
            const confirmationModal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
            confirmationModal.hide();

            // Submit the form
            document.getElementById('profilePictureForm').submit();
        }
    </script>
</body>
</html>
