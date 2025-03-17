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
    <link href="../../css/styles.css" rel="stylesheet" />
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
    <body class="sb-nav-fixed bg-black">
        <?php include 'navbar.php'; ?>
        <div id="layoutSidenav">
            <?php include 'sidebar.php'; ?>
            <div id="layoutSidenav_content">
                <main class="bg-black">
                    <div class="container-fluid position-relative px-4">
                        <div class="container-fluid" id="calendarContainer" 
                            style="position: fixed; top: 9%; right: 0; z-index: 1050; 
                            max-width: 100%; display: none;">
                            <div class="row">
                                <div class="col-12 col-md-10 col-lg-8 mx-auto">
                                    <div id="calendar" class="p-2"></div>
                                </div>
                            </div>
                        </div>
                        <h1 class="big mb-2 text-light">Profile</h1>
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card mb-4">
                                    <div class="card-header bg-dark text-light">
                                        <h3 class="card-title text-start">User Information</h3>
                                        <hr>
                                    </div>
                                    <div class="card-body bg-dark">
                                        <div class="row">
                                            <div class="col-xl-2">
                                                <div class="d-flex justify-content-center">
                                                    <?php
                                                    // Check if a custom profile picture exists
                                                    if (!empty($employeeInfo['pfp']) && $employeeInfo['pfp'] !== 'defaultpfp.png') {
                                                        // Display the custom profile picture
                                                        echo '<img src="' . htmlspecialchars($employeeInfo['pfp']) . '" 
                                                            class="rounded-circle border border-light img-fluid" 
                                                            style="max-width: 230px; max-height: 230px; min-width: 230px; min-height: 230px; object-fit: cover; cursor: pointer;" 
                                                            alt="Profile Picture" 
                                                            id="profilePic" data-bs-toggle="modal" data-bs-target="#profilePicModal" />';
                                                    } else {
                                                        // Generate initials from the first name and last name
                                                        $firstName = $employeeInfo['first_name'] ?? '';
                                                        $lastName = $employeeInfo['last_name'] ?? '';
                                                        $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

                                                        // Display the initials in a circular container
                                                        echo '<div class="rounded-circle border border-light d-flex justify-content-center align-items-center img-fluid" 
                                                            style="max-width: 230px; max-height: 230px; min-width: 230px; min-height: 230px; background-color:rgba(16, 17, 18); color: white; font-size: 48px; font-weight: bold; cursor: pointer; object-fit: cover;" 
                                                            id="profilePic" data-bs-toggle="modal" data-bs-target="#profilePicModal">' . $initials . '</div>';
                                                    }
                                                    ?>
                                                </div>
                                                <div class="modal fade" id="profilePicModal" tabindex="-1" aria-labelledby="profilePicModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered"> <!-- Set the modal size using 'modal-lg' for large -->
                                                        <div class="modal-content bg-dark text-light" style="width: 600px; height: 500px;">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="profilePicModalLabel">
                                                                    <?php echo htmlspecialchars($employeeInfo['first_name'] . ' ' . $employeeInfo['middle_name'] . ' ' . $employeeInfo['last_name']); ?>
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body d-flex justify-content-center align-items-center">
                                                                <?php
                                                                // Check if a custom profile picture exists
                                                                if (!empty($employeeInfo['pfp']) && $employeeInfo['pfp'] !== 'defaultpfp.png') {
                                                                    // Display the custom profile picture
                                                                    echo '<img src="' . htmlspecialchars($employeeInfo['pfp']) . '" 
                                                                        class="img-fluid rounded" 
                                                                        style="width: 500px; height: 400px; object-fit: cover;" 
                                                                        alt="Profile Picture" />';
                                                                } else {
                                                                    // Generate initials from the first name and last name
                                                                    $firstName = $employeeInfo['first_name'] ?? '';
                                                                    $lastName = $employeeInfo['last_name'] ?? '';
                                                                    $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

                                                                    // Display the initials in a circular container
                                                                    echo '<div class="rounded-circle d-flex justify-content-center align-items-center" 
                                                                        style="width: 400px; height: 400px; background-color: rgba(16, 17, 18); color: white; font-size: 120px; font-weight: bold;">' . $initials . '</div>';
                                                                }
                                                                ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-center align-items-center mt-4 mb-3">
                                                    <button class="btn btn-primary text-center w-50" type="button" title="Profile Settings" id="editPictureDropdown" 
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="me-2 fs-5 fas fa-user-cog"></i>
                                                        Settings
                                                    </button>
                                                    <div class="dropdown">
                                                        <ul class="dropdown-menu" aria-labelledby="editPictureDropdown">
                                                            <li>
                                                                <a class="dropdown-item fw-bold text-start" title="Change Profile" href="javascript:void(0);" id="changePictureOption"> <i class="me-2 fs-5 fas fa-user-edit"></i>Change Profile</a>
                                                            </li>
                                                            <hr>
                                                            <li>
                                                                <button class="dropdown-item fw-bold text-start text-danger" title="Delete Profile" type="button" data-bs-toggle="modal" data-bs-target="#deleteProfilePictureModal">
                                                                    <i class="me-2 fs-5 fa fa-trash"></i>
                                                                    Delete
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-10 mb-4">
                                               <div class="">
                                                    <!-- Your buttons -->
                                                    <div class="d-flex justify-content-start">
                                                        <a href="../admin/change_pass.php" class="btn btn-primary text-light loading" role="status">Change password</a>
                                                    </div>                              
                                                </div>
                                                <div class="mt-3">
                                                    <div class="form-group row">
                                                        <div class="col-sm-4 mb-3 position-relative">
                                                            <label class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Name</label>
                                                            <input class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" name="fname" 
                                                                value="<?php echo htmlspecialchars($employeeInfo['first_name'] . ' ' . $employeeInfo['middle_name'] . ' ' . $employeeInfo['last_name']); ?>" readonly>
                                                        </div>

                                                        <div class="col-sm-4 position-relative">
                                                            <label class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">ID No.</label>
                                                            <input class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" name="id" 
                                                                value="<?php echo htmlspecialchars($employeeInfo['employee_id']); ?>" readonly>
                                                        </div>


                                                        <div class="col-sm-4 position-relative">
                                                            <label class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Gender</label>
                                                            <input class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" name="gender" value="<?php echo htmlspecialchars($employeeInfo['gender']); ?>" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-3">
                                                    <div class="form-group row">
                                                        <div class="col-sm-6 position-relative mb-3">
                                                            <label class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Role</label>
                                                            <input class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" name="position" value="<?php echo htmlspecialchars($employeeInfo['role']); ?>" readonly>
                                                        </div>

                                                        <div class="col-sm-6 position-relative">
                                                            <label class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Department</label>
                                                            <input class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" name="department" value="<?php echo htmlspecialchars($employeeInfo['department']); ?>" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-3">
                                                    <div class="form-group row">
                                                        <div class="col-sm-12 position-relative mb-3">
                                                            <label class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Email</label>
                                                            <input class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" name="email" value="<?php echo htmlspecialchars($employeeInfo['email']); ?>" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-header bg-dark text-light">
                                                <hr>
                                                <h3 class="card-title text-center">Edit Information</h3>
                                                <hr>
                                            </div>
                                            <div class="card-body bg-dark">
                                                <form id="infoForm" action="/HR2/employee_db/supervisor/update_profile.php" method="post">
                                                    <div class="mb-4 text-info">
                                                        <h4>Personal Details</h4>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-sm-4 bg-dark position-relative mb-3">
                                                            <label for="inputfName" class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">First Name</label>
                                                            <input type="text" class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" id="inputfName" name="first_name" value="<?php echo htmlspecialchars($employeeInfo['first_name']); ?>" readonly required>
                                                        </div>
                                                        <div class="col-sm-4 bg-dark position-relative mb-3">
                                                            <label for="inputmName" class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Middle Name</label>
                                                            <input type="text" class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" id="inputmName" name="middlename" value="<?php echo htmlspecialchars($employeeInfo['middle_name']); ?>" readonly required>
                                                        </div>
                                                        <div class="col-sm-4 bg-dark position-relative">
                                                            <label for="inputlName" class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Last Name</label>
                                                            <input type="text" class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" id="inputlName" name="last_name" value="<?php echo htmlspecialchars($employeeInfo['last_name']); ?>" readonly required>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-sm-6 bg-dark position-relative mb-3">
                                                            <label for="inputbirth" class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Birthdate</label>
                                                            <input type="date" class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" id="inputbirth" name="birthdate" value="<?php echo htmlspecialchars($employeeInfo['birthdate']); ?>" readonly required>
                                                        </div>
                                                        <div class="col-sm-6 bg-dark position-relative">
                                                            <label for="inputEmail" class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Email Address</label>
                                                            <input type="email" class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" id="inputEmail" name="email" value="<?php echo htmlspecialchars($employeeInfo['email']); ?>" readonly required>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-sm-6 bg-dark position-relative mb-3">
                                                            <label for="inputPhone" class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Phone Number</label>
                                                            <input type="number" class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" id="inputPhone" id="inputPhone" name="phone_number" value="<?php echo htmlspecialchars($employeeInfo['phone_number']); ?>" readonly required>
                                                        </div>
                                                        <div class="col-sm-6 bg-dark position-relative">
                                                            <label for="inputAddress" class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Address</label>
                                                            <input class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" id="inputAddress" name="address" value="<?php echo htmlspecialchars($employeeInfo['address']); ?>" readonly required>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-end">
                                                        <button type="button" id="editButton" class="btn btn-primary">Update Information</button>
                                                        <button type="button" class="btn btn-primary d-none ms-2" id="saveButton" data-bs-toggle="modal" data-bs-target="#saveChangesModal">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        <form action="/HR2/employee_db/supervisor/update_employee_pfp.php" method="post" enctype="multipart/form-data" id="profilePictureForm" style="display:none;">
                                            <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*" onchange="showConfirmationModal();">
                                        </form>
                                        <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content bg-dark text-light" style="width: 500px; height: 400px;">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="confirmationModalLabel">Confirm Profile Picture Update</h5>
                                                        <button type="button" class="close text-light bg-dark" data-bs-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to update your profile picture with the selected image?</p>
                                                        <!-- Center the image in the modal -->
                                                        <div class="d-flex justify-content-center align-items-center img-fluid rounded">
                                                            <img id="modalProfilePicturePreview" src="#" alt="Selected Profile Picture"  style="width: 150px; height: 150px;">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="button" class="btn btn-primary" onclick="submitProfilePictureForm()">Update</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 mb-4">
                            <div class="card bg-dark text-light">
                                <div class="card-header border-bottom border-secondary">
                                    <h3 class="mb-0">User Activity</h3>
                                </div>
                                <div class="card-body">
                                    <p><strong>Your last login was on:</strong> 
                                        <?php 
                                            if (!empty($employeeInfo['login_time'])) {
                                                $login = strtotime($employeeInfo['login_time']); 
                                                echo date("l, F j, Y | g:i A", $login); 
                                            } else {
                                                echo "No login time available";
                                            }
                                        ?>
                                    </p>
                                    <hr>
                                    <p><strong>Your last logout was on:</strong> 
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
                </main>
                    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content bg-dark text-light">
                                <div class="modal-header border-bottom border-secondary">
                                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    Are you sure you want to log out?
                                </div>
                                <div class="modal-footer border-top border-secondary">
                                    <button type="button" class="btn border-secondary text-light" data-bs-dismiss="modal">Cancel</button>
                                    <form action="../../employee/logout.php" method="POST">
                                        <button type="submit" class="btn btn-danger">Logout</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>  
                    <div class="modal fade" id="deleteProfilePictureModal" tabindex="-1" aria-labelledby="deleteProfilePictureLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content bg-dark text-light">
                                <div class="modal-header border-bottom border-secondary">
                                    <h5 class="modal-title" id="deleteProfilePictureLabel">Delete Profile Picture</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-start">
                                    <p>Are you sure you want to delete your profile picture?</p>
                                </div>
                                <div class="modal-footer border-top border-secondary">
                                    <form action="/HR2/employee_db/supervisor/delete_employee_pfp.php" method="post">
                                        <input type="hidden" name="employeeId" value="<?php echo $employeeInfo['employee_id']; ?>">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="saveChangesModal" tabindex="-1" aria-labelledby="saveChangesModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content bg-dark text-light">
                                <div class="modal-header boder-bottom border-secondary">
                                    <h5 class="modal-title" id="saveChangesModalLabel">Confirm Save</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    Are you sure you want to save the changes to your information?
                                </div>
                                <div class="modal-footer boder-bottom border-secondary">
                                    <button type="button" class="btn btn-outline-secondary text-light" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="confirmSave">Save Changes</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php include 'footer.php'; ?>
            </div>
        </div>
        <script>
                document.addEventListener('DOMContentLoaded', function() {
                // Check if there is a message to show
                <?php if (isset($message)) : ?>
                    var modalMessage = "<?php echo $message; ?>";
                    var messageType = "<?php echo $messageType; ?>";

                    // Set the modal content based on success or error
                    document.getElementById('modalMessage').textContent = modalMessage;
                    
                    // Change modal appearance based on message type (optional)
                    if (messageType === "success") {
                        document.getElementById('messageModalLabel').textContent = "Success";
                        document.querySelector('.modal-content').classList.add('bg-success', 'text-white');
                    } else {
                        document.getElementById('messageModalLabel').textContent = "Error";
                        document.querySelector('.modal-content').classList.add('bg-danger', 'text-white');
                    }

                    // Show the modal
                    var myModal = new bootstrap.Modal(document.getElementById('messageModal'));
                    myModal.show();
                <?php endif; ?>
            });


        //SAVE CHANGES (MODAL)
            document.getElementById('confirmSave').addEventListener('click', function() {
            // Add your form submission logic here
            document.getElementById('infoForm').submit(); // Submit the form
        });
        //END
        </script>
        <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="../../js/employee.js"></script>
        <script src="../../js/profile.js"></script>
    </body>
</html>