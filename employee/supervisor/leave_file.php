<?php
session_start();
include '../../db/db_conn.php';

if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Supervisor') {
    header("Location: ../../login.php");
    exit();
}

// Fetch user info from the employee_register table
$employeeId = $_SESSION['employee_id'];
$sql = "SELECT employee_id, first_name, middle_name, last_name, birthdate, gender, email, role, position, department, phone_number, address, pfp FROM employee_register WHERE employee_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$employeeInfo = $result->fetch_assoc();

if (!$employeeInfo) {
    die("Error: Employee information not found.");
}

$gender = $employeeInfo['gender']; // Fetch gender

// Fetch the available leaves from the employee_leaves table (including both male and female leave types)
$leavesQuery = "SELECT 
                    bereavement_leave, emergency_leave, maternity_leave, mcw_special_leave, 
                    parental_leave, service_incentive_leave, sick_leave, vacation_leave, vawc_leave,
                    bereavement_leave_male,emergency_leave_male, parental_leave_male, 
                    paternity_leave_male, service_incentive_leave_male, sick_leave_male, vacation_leave_male 
                FROM employee_leaves 
                WHERE employee_id = ?";
$leavesStmt = $conn->prepare($leavesQuery);
$leavesStmt->bind_param("s", $employeeId);
$leavesStmt->execute();
$leavesResult = $leavesStmt->get_result();
$leavesInfo = $leavesResult->fetch_assoc();

// If no leave information is found, set default values for leave types
if (!$leavesInfo) {
    $leaveTypes = [
        'bereavement_leave', 'emergency_leave', 'maternity_leave', 'mcw_special_leave', 
        'parental_leave', 'service_incentive_leave', 'sick_leave', 'vacation_leave', 'vawc_leave',
        'bereavement_leave_male', 'emergency_leave_male', 'parental_leave_male', 'paternity_leave_male', 
        'service_incentive_leave_male', 'sick_leave_male', 'vacation_leave_male'
    ];
    $leavesInfo = array_fill_keys($leaveTypes, 0);
}

// Fetch the used leave by summing up approved leave days
$usedLeaveQuery = "SELECT start_date, end_date, SUM(DATEDIFF(end_date, start_date) + 1) AS used_leaves 
                   FROM leave_requests 
                   WHERE employee_id = ? AND status = 'approved'
                   GROUP BY employee_id";
$usedLeaveStmt = $conn->prepare($usedLeaveQuery);
$usedLeaveStmt->bind_param("i", $employeeId);
$usedLeaveStmt->execute();
$usedLeaveResult = $usedLeaveStmt->get_result();
$usedLeaveRow = $usedLeaveResult->fetch_assoc();
$usedLeave = $usedLeaveRow['used_leaves'] ?? 0; // Default to 0 if no leave has been used

// Calculate total available leaves based on gender
$totalAvailableLeaves = 0;
if ($employeeInfo['gender'] === 'Male') {
    // For male employees
    $totalAvailableLeaves = 
        $leavesInfo['bereavement_leave_male'] +
        $leavesInfo['emergency_leave_male'] +
        $leavesInfo['parental_leave_male'] +
        $leavesInfo['paternity_leave_male'] +
        $leavesInfo['service_incentive_leave_male'] +
        $leavesInfo['sick_leave_male'] +
        $leavesInfo['vacation_leave_male'];
} else {
    // For female employees
    $totalAvailableLeaves = 
        $leavesInfo['bereavement_leave'] +
        $leavesInfo['emergency_leave'] +
        $leavesInfo['maternity_leave'] +
        $leavesInfo['mcw_special_leave'] +
        $leavesInfo['parental_leave'] +
        $leavesInfo['service_incentive_leave'] +
        $leavesInfo['sick_leave'] +
        $leavesInfo['vacation_leave'] +
        $leavesInfo['vawc_leave'];
}

// Calculate remaining total leaves by subtracting used leaves
$remainingLeaves = $totalAvailableLeaves;

// Close the database connection
$stmt->close();
$leavesStmt->close();
$usedLeaveStmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Request Form</title>
    <link href="../../css/styles.css" rel="stylesheet" />
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <script src="../../js/admin.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --dark-bg: #1a1a1a;
            --darker-bg: #141414;
            --card-bg: #242424;
            --input-bg: #2d2d2d;
            --border-color: #3d3d3d;
            --text-color: #e0e0e0;
            --text-muted: #a0a0a0;
            --primary-color: #4f6df5;
            --primary-hover: #3a58e0;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
        }
        
        body {
            background-color: var(--dark-bg);
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sb-nav-fixed {
            background-color: var(--dark-bg) !important;
        }
        
        #layoutSidenav_content {
            background-color: var(--dark-bg);
        }
        
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid var(--border-color);
            padding: 15px;
        }
        
        .text-light {
            color: var(--text-color) !important;
        }
        
        .text-muted-foreground {
            color: var(--text-muted) !important;
        }
        
        .form-control, .form-select {
            background-color: var(--input-bg);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            background-color: var(--input-bg);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(79, 109, 245, 0.25);
            color: var(--text-color);
        }
        
        .form-control::placeholder {
            color: var(--text-muted);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .text-success {
            color: var(--success-color) !important;
        }
        
        .text-danger {
            color: var(--danger-color) !important;
        }
        
        .modal-content {
            background-color: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }
        
        .modal-header, .modal-footer {
            border-color: var(--border-color);
        }
        
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.2);
            border-color: rgba(231, 76, 60, 0.3);
            color: #f5b7b1;
        }
        
        /* Custom styling for leave info cards */
        .leave-balance-card {
            background: linear-gradient(145deg, #242424, #2a2a2a);
        }
        
        .leave-info-section {
            background: linear-gradient(145deg, #2a2a2a, #303030);
            border-radius: 10px;
            padding: 20px;
            transition: transform 0.3s ease;
        }
        
        .leave-info-section:hover {
            transform: translateY(-5px);
        }
        
        /* Improved form styling */
        .form-floating-label {
            position: absolute;
            top: -10px;
            left: 15px;
            background-color: var(--card-bg);
            padding: 0 5px;
            font-size: 0.85rem;
            z-index: 1;
        }
        
        /* Custom file input styling */
        input[type="file"] {
            padding: 10px;
        }
        
        input[type="file"]::file-selector-button {
            background-color: #3d3d3d;
            color: var(--text-color);
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            margin-right: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        input[type="file"]::file-selector-button:hover {
            background-color: #4d4d4d;
        }
        
        /* Button styling */
        .btn {
            border-radius: 6px;
            font-weight: 500;
            padding: 8px 16px;
            transition: all 0.3s ease;
        }
        
        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        /* Calendar container styling */
        #calendarContainer {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main class="container-fluid position-relative px-4">
                <div class="container" id="calendarContainer" 
                    style="position: fixed; top: 9%; right: 0; z-index: 1050; 
                    width: 700px; display: none;">
                    <div class="row">
                        <div class="col-md-12">
                            <div id="calendar" class="p-2"></div>
                        </div>
                    </div>
                </div> 
                <h1 class="mb-4 mt-4 text-light">File Leave</h1>                   
                <div class="py-4">
                    <?php if (isset($_SESSION['status_message'])): ?>
                        <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="statusModalLabel">
                                            <i class="fa fa-info-circle text-light me-2 fs-4"></i> Message
                                        </h5>
                                        <button type="button" class="btn-close text-light" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body align-items-center">
                                        <?php echo $_SESSION['status_message']; ?>
                                        <div class="d-flex justify-content-center mt-3">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ok</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var myModal = new bootstrap.Modal(document.getElementById('statusModal'));
                                myModal.show();
                            });
                        </script>
                        <?php unset($_SESSION['status_message']); // Clear the message after displaying ?>
                    <?php endif; ?>
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card leave-balance-card">
                                <div class="card-body text-center p-4">
                                    <h3 class="card-title mb-4 text-light">Leave Information</h3>
                                    <div class="row">
                                        <div class="col-md-6 mb-3 mb-md-0">
                                            <div class="leave-info-section">
                                                <h5 class="text-light mb-3">Available Paid Leave</h5>
                                                <p class="fs-2 fw-bold text-success mb-3"><?php echo htmlspecialchars($remainingLeaves); ?> days</p>
                                                <a class="btn btn-success w-100 btn-icon" href="../../employee/supervisor/leaveDetails.php">
                                                    <i class="fas fa-info-circle"></i> View leave details
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="leave-info-section">
                                                <h5 class="text-light mb-3">Used Leave</h5>
                                                <p class="fs-2 fw-bold text-danger mb-3"><?php echo htmlspecialchars($usedLeave); ?> days</p>
                                                <a class="btn btn-danger w-100 btn-icon" href="../../employee/supervisor/leaveHistory.php">
                                                    <i class="fas fa-history"></i> View leave history
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                        if (isset($_GET['error']) && $_GET['error'] === 'proof_required') {
                            $leave_type = isset($_GET['leave_type']) ? htmlspecialchars($_GET['leave_type']) : 'this leave type';
                            echo '<div class="alert alert-danger mb-4"><i class="fas fa-exclamation-triangle me-2"></i> Proof is required for ' . $leave_type . '.</div>';
                        }
                    ?>
                    <form id="leave-request-form" action="../../employee_db/supervisor/leave_conn.php" class="needs-validation" method="POST" enctype="multipart/form-data" novalidate>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card leave-form text">
                                    <div class="card-header text-center border-bottom">
                                        <h3 class="mb-0 text-light">Request Leave</h3>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="row mb-4">
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <div class="position-relative">
                                                    <label for="name" class="form-floating-label text-light" 
                                                        style="top: -10px; left: 15px; background-color: #242424; padding: 0 5px;">Name:</label>
                                                    <input type="text" class="form-control fw-bold" 
                                                        style="height: 60px; padding-top: 20px;" id="name" name="name" value="<?php echo htmlspecialchars($employeeInfo['first_name'] . ' ' . $employeeInfo['last_name']); ?>" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="position-relative">
                                                    <label for="department" class="form-floating-label text-light" 
                                                        style="top: -10px; left: 15px; background-color: #242424; padding: 0 5px;">Department:</label>
                                                    <input type="text" class="form-control fw-bold" 
                                                        style="height: 60px; padding-top: 20px;" id="department" name="department" value="<?php echo htmlspecialchars($employeeInfo['department']); ?>" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-4">
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <div class="position-relative">
                                                    <label class="form-floating-label text-light" 
                                                        style="top: -10px; left: 15px; background-color: #242424; padding: 0 5px;" for="leave_type">Leave Type</label>
                                                    <select id="leave_type" name="leave_type" class="form-select fw-bold" 
                                                        style="height: 60px; padding-top: 20px;" required>
                                                        <option value="" disabled selected>Select leave type</option>
                                                        <option value="Bereavement Leave">Bereavement Leave</option>
                                                        <option value="Emergency Leave">Emergency Leave</option>
                                                        <option value="Maternity Leave" class="female-leave">Maternity Leave</option>
                                                        <option value="MCW Special Leave" class="female-leave">MCW Special Leave Benefit</option>
                                                        <option value="Parental Leave">Parental Leave</option>
                                                        <option value="Paternity Leave" class="male-leave">Paternity Leave</option>
                                                        <option value="Service Incentive Leave">Service Incentive Leave</option>
                                                        <option value="Sick Leave">Sick Leave</option>
                                                        <option value="Vacation Leave">Vacation Leave</option>
                                                        <option value="VAWC Leave" class="female-leave">VAWC Leave</option>
                                                    </select>
                                                    <div class="invalid-feedback">Please select leave type.</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="position-relative">
                                                    <label for="leave_category" class="form-floating-label text-light" 
                                                        style="top: -10px; left: 15px; background-color: #242424; padding: 0 5px;">Leave Category
                                                    </label>
                                                    <select class="form-select fw-bold" 
                                                        style="height: 60px; padding-top: 20px;" id="leave_category" name="leave_category" required>
                                                        <option value="" disabled selected>Select leave category</option>
                                                        <option value="Paid Leave">Paid Leave</option>
                                                        <option value="Unpaid Leave">Unpaid Leave</option>
                                                    </select>
                                                    <div class="invalid-feedback">Please select a category.</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-4">
                                            <div class="col-md-4 mb-3 mb-md-0">
                                                <div class="position-relative">
                                                    <label for="start_date" class="form-floating-label text-light" 
                                                        style="top: -10px; left: 15px; background-color: #242424; padding: 0 5px;">Start Date
                                                    </label>
                                                    <input type="date" id="start_date" name="start_date" class="form-control fw-bold" 
                                                        style="height: 60px; padding-top: 20px;" required>
                                                    <div class="invalid-feedback">Please set a date.</div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3 mb-md-0">
                                                <div class="position-relative">
                                                    <label for="end_date" class="form-floating-label text-light" 
                                                        style="top: -10px; left: 15px; background-color: #242424; padding: 0 5px;">End Date</label>
                                                    <input type="date" id="end_date" name="end_date" class="form-control fw-bold" 
                                                        style="height: 60px; padding-top: 20px;" required>
                                                    <div class="invalid-feedback">Please set a date.</div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="position-relative">
                                                    <label for="leave_days" class="form-floating-label text-light" 
                                                        style="top: -10px; left: 15px; background-color: #242424; padding: 0 5px;">Number of Days</label>
                                                    <input type="number" name="leave_days" id="leave_days" class="form-control fw-bold" 
                                                        style="height: 60px; padding-top: 20px;" min="1" max="30" placeholder="" required readonly>
                                                    <div class="invalid-feedback">Please set a value.</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-4" id="proof-container">
                                            <div class="position-relative">
                                                <label for="proof" class="form-floating-label text-light" 
                                                    style="top: -10px; left: 15px; background-color: #242424; padding: 0 5px;">Attach Proof</label>
                                                <input type="file" id="proof" name="proof[]" class="form-control fw-bold" 
                                                    style="height: 60px; padding-top: 20px;" accept="*/*" multiple>
                                                <div class="invalid-feedback">Proof is required with the selected leave type.</div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end gap-3 mt-4">
                                            <button type="button" class="btn btn-danger btn-icon" onclick="resetForm()">
                                                <i class="fas fa-times"></i> Clear
                                            </button>
                                            <button type="submit" class="btn btn-primary btn-icon">
                                                <i class="fas fa-paper-plane"></i> Submit Leave Request
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
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
            <?php include 'footer.php'; ?>
        </div>
    </div>
    <script>
        //LEAVE DAYS
        document.getElementById('start_date').addEventListener('change', calculateLeaveDays);
        document.getElementById('end_date').addEventListener('change', calculateLeaveDays);

        function calculateLeaveDays() {
            const start_date = document.getElementById('start_date').value;
            const end_date = document.getElementById('end_date').value;
            
            if (start_date && end_date) {
                const start = new Date(start_date);
                const end = new Date(end_date);
                let totalDays = 0;

                // Loop through the dates between start and end dates
                for (let date = new Date(start); date <= end; date.setDate(date.getDate() + 1)) {
                    // Exclude Sundays (0 is Sunday)
                    if (date.getDay() !== 0) {
                        totalDays++;
                    }
                }

                // Update the number of days in the input field
                document.getElementById('leave_days').value = totalDays;
            }
        }
        //LEAVE DAYS END


        //LEAVE REQUEST
        document.addEventListener('DOMContentLoaded', function () {
            const leaveType = document.getElementById('leave_type');
            const leaveDays = document.getElementById('leave_days');
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            let holidays = [];

            // Fetch holidays from the server
            fetch('../../employee_db/getHolidays.php')
                .then(response => response.json())
                .then(data => {
                    console.log('Holidays fetched:', data);
                    if (Array.isArray(data)) {
                        holidays = data;
                    } else {
                        console.error('Expected an array of holidays, but received:', data);
                    }
                })
                .catch(error => {
                    console.error('Error fetching holidays:', error);
                });

            function calculateEndDate(startDate, days) {
                let count = 0;
                let currentDate = new Date(startDate);
                
                while (count < days) {
                    currentDate.setDate(currentDate.getDate() + 1);

                    const currentDateString = currentDate.toISOString().split('T')[0];
                    if (currentDate.getDay() !== 0 && !holidays.includes(currentDateString)) {
                        count++;
                    }
                }
                return currentDate.toISOString().split('T')[0];
            }

            function isInvalidStartDate(date) {
                const dateString = date.toISOString().split('T')[0];
                const todayString = new Date().toISOString().split('T')[0]; // Today's date in YYYY-MM-DD format

                // Check if the date is today or a holiday or a Sunday
                return date.getDay() === 0 || holidays.includes(dateString) || dateString === todayString;
            }

            // Event listener for start date change
            startDate.addEventListener('change', function () {
                const selectedStartDate = new Date(startDate.value);
                
                // Check if the selected start date is invalid
                if (isInvalidStartDate(selectedStartDate)) {
                    alert("You cannot file leave on Sundays, holidays, or the current day.");
                    startDate.value = ''; // Clear the selected start date
                    endDate.value = ''; // Clear the end date as well
                    return;
                }

                if (leaveType.value === 'Paternity Leave' && startDate.value) {
                    const endDateValue = calculateEndDate(startDate.value, 7);
                    endDate.value = endDateValue;
                } else if (leaveType.value === 'Maternity Leave' && startDate.value) {
                    const endDateValue = calculateEndDate(startDate.value, 105);
                    endDate.value = endDateValue;
                } else {
                    endDate.value = '';
                }
            });
        });

        //LEAVE REQUEST END

        //GENDER BASED
        // Get the gender from PHP
        const gender = "<?php echo addslashes($gender); ?>";

        const femaleLeaveOptions = document.querySelectorAll('.female-leave');
        const maleLeaveOptions = document.querySelectorAll('.male-leave');

        // Hide all gender-specific options by default
        femaleLeaveOptions.forEach(option => option.style.display = 'none');
        maleLeaveOptions.forEach(option => option.style.display = 'none');

        // Show gender-specific leave options based on the user's gender
        if (gender === 'Female') {
            femaleLeaveOptions.forEach(option => option.style.display = 'block');  // Show Female Leave Options
        } else if (gender === 'Male') {
            maleLeaveOptions.forEach(option => option.style.display = 'block');  // Show Male Leave Options
        }

        function resetForm() {
            document.getElementById('leave-request-form').reset();  // Reset the form
        }
        //GENDER BASED

        //VALIDATION
        (function () {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
        })();
        //VALIDATION


        document.addEventListener('DOMContentLoaded', function () {
            const leaveTypeDropdown = document.getElementById('leave_type');
            const proofInput = document.getElementById('proof');

            // Function to check if proof is required
            function checkProofRequirement() {
                const selectedLeaveType = leaveTypeDropdown.value;
                const proofRequiredLeaveTypes = ['Sick Leave', 'Maternity Leave', 'Paternity Leave'];

                if (proofRequiredLeaveTypes.includes(selectedLeaveType)) {
                    proofInput.setAttribute('required', true); // Make proof required
                } else {
                    proofInput.removeAttribute('required'); // Remove the required attribute
                }
            }

            // Add event listener to the leave type dropdown
            leaveTypeDropdown.addEventListener('change', checkProofRequirement);

            // Check proof requirement on page load
            checkProofRequirement();
        });
</script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/employee.js"></script>
</body>
</html>