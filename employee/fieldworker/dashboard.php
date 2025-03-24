<?php
session_start();
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'fieldworker') {
    header("Location: ../../login.php");
    exit();
}

include '../../db/db_conn.php';

$employeeId = $_SESSION['employee_id'];
$employeeRole = $_SESSION['role'];

// Fetch the average of the employee's evaluations
$sql = "SELECT 
            AVG(quality) AS avg_quality, 
            AVG(communication_skills) AS avg_communication_skills, 
            AVG(teamwork) AS avg_teamwork, 
            AVG(punctuality) AS avg_punctuality, 
            AVG(initiative) AS avg_initiative,
            COUNT(*) AS total_evaluations 
        FROM ptp_evaluations 
        WHERE employee_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $employeeId);
$stmt->execute();
$result = $stmt->get_result();

// Check if evaluations exist
if ($result->num_rows > 0) {
    $evaluation = $result->fetch_assoc();

    // Calculate the total average
    $totalAverage = (
        $evaluation['avg_quality'] +
        $evaluation['avg_communication_skills'] +
        $evaluation['avg_teamwork'] +
        $evaluation['avg_punctuality'] +
        $evaluation['avg_initiative']
    ) / 5;
} else {
    echo "No evaluations found.";
    exit;
}

// Fetch user info
$sql = "SELECT first_name, middle_name, last_name, email, role, position, pfp FROM employee_register WHERE employee_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$employeeInfo = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Set the profile picture, default if not provided
$profilePicture = !empty($employeeInfo['pfp']) ? $employeeInfo['pfp'] : '../../img/defaultpfp.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Fieldworker Dashboard for HR Management System" />
    <meta name="author" content="" />
    <title>Employee Dashboard | HR2</title>
    <link href="../../css/styles.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <link href="../../css/calendar.css" rel="stylesheet"/>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet'/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #1abc9c;
            --accent-color: #3498db;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --success-color: #2ecc71;
            --bg-dark: rgba(33, 37, 41) !important; 
            --bg-black: rgba(16, 17, 18) !important; 
            --card-bg: rgba(33, 37, 41) !important; 
            --text-light: #ecf0f1;
            --border-color: rgba(255, 255, 255, 0.1);
            --default-day-color: #6c757d; /* Default color for days with no status */
        }
        
        body {
            background:  rgba(16, 17, 18) !important; 
            color: var(--text-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sb-nav-fixed {
            background:  rgba(16, 17, 18) !important; 
        }
        
        #layoutSidenav_content {
            background: rgba(16, 17, 18) !important; 
        }
        
        .card {
            background: rgba(33, 37, 41) !important; 
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.3);
        }
        
        .card-header {
            background:  rgba(33, 37, 41) !important; 
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
            font-weight: 600;
        }
        
        .card-header i {
            color: var(--accent-color); /* Fixed icon color */
            margin-right: 10px;
        }
        
        .card-header a {
            text-decoration: none;
            color: var(--text-light);
            transition: color 0.2s;
        }
        
        .card-header a:hover {
            color: var(--accent-color); /* Fixed hover color */
        }
        
        .btn-primary {
            background: #3498db !important; /* Fixed button color */
            border: none;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: #2980b9 !important; /* Fixed hover color */
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .btn-danger {
            background: #e74c3c !important; /* Fixed danger button color */
            border: none;
            border-radius: 8px;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            background: rgba(0, 0, 0, 0.2) !important; /* Fixed progress background */
            overflow: hidden;
        }
        
        .progress-bar {
            border-radius: 4px;
        }
        
        .list-group-item {
            background: rgba(33, 37, 41, 0.7) !important; /* Fixed list item background */
            border-bottom: 1px solid var(--border-color);
            padding: 1rem;
            transition: background 0.2s;
        }
        
        .list-group-item:hover {
            background: rgba(33, 37, 41, 0.9) !important; /* Fixed hover background */
        }
        
        .rounded-circle {
            border: 3px solid var(--secondary-color);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .modal-content {
            background: rgba(33, 37, 41) !important; /* Fixed modal background */
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        
        .modal-header, .modal-footer {
            border-color: var(--border-color);
        }
        
        .btn-close-white {
            filter: brightness(0) invert(1);
        }
        
        /* Calendar Styles */
        #ATTENDANCEcalendar .col {
            padding: 5px;
        }
        
        #ATTENDANCEcalendar .btn {
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            background-color: rgba(33, 37, 41, 0.5); /* Default background for all days */
        }
        
        #ATTENDANCEcalendar .btn:hover {
            transform: scale(1.1);
            box-shadow: 0 0 10px rgba(26, 188, 156, 0.5);
            background-color: rgba(33, 37, 41, 0.8); /* Darker on hover */
        }
        
        .text-success {
            color: var(--success-color) !important;
        }
        
        .text-warning {
            color: var(--warning-color) !important;
        }
        
        .text-danger {
            color: var(--danger-color) !important;
        }
        
        .text-info {
            color: var(--accent-color) !important;
        }
        
        .text-default {
            color: var(--default-day-color) !important; /* Default color for days with no status */
        }
        
        /* Badge styles */
        .badgeT {
            padding: 3px;
            font-weight: 600;
            border-radius: 30px;
        }
        
        /* Form controls */
        .form-control {
            background: rgba(33, 37, 41, 0.5) !important; /* Fixed form control background */
            border: 1px solid var(--border-color);
            color: var(--text-light);
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }
        
        .form-control:focus {
            background: rgba(33, 37, 41, 0.7) !important; /* Fixed focus background */
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.25rem rgba(26, 188, 156, 0.25);
            color: var(--text-light);
        }
        
        /* Chart container */
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
            padding: 1rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            #ATTENDANCEcalendar .btn span {
                font-size: 0.9rem;
            }
            
            #ATTENDANCEcalendar .col {
                padding: 2px;
            }
            
            #ATTENDANCEcalendar .btn {
                height: 35px !important;
                width: 35px !important;
            }
            
            .chart-container {
                height: 300px;
            }
        }
        
        @media (max-width: 575.98px) {
            #ATTENDANCEcalendar .btn span {
                font-size: 0.8rem;
            }
            
            #ATTENDANCEcalendar .col {
                padding: 1px;
            }
            
            #ATTENDANCEcalendar .btn {
                height: 30px !important;
                width: 30px !important;
            }
            
            .chart-container {
                height: 250px;
            }
            
            .row.text-center.fw-bold .col {
                font-size: 0.8rem;
                padding: 2px;
            }
        }
        
        /* Animation effects */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .card:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .card:nth-child(3) {
            animation-delay: 0.4s;
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4 py-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="mb-0 text-light fw-bold">
                            <i class="fas fa-tachometer-alt me-2 text-info"></i>Dashboard
                        </h1>
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

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-calendar-check me-1 text-info"></i> 
                                        <a class="text-light" href="../../employee/fieldworker/timesheet.php#timesheet">Attendance</a>
                                    </div>
                                            
                                    <div class="bg-dark rounded-pill px-3 py-1">
                                        <span class="text-info">Time in: </span>
                                        <span class="text-light">08:11 AM</span>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="bg-dark rounded-pill px-3 py-2 d-flex align-items-center me-3">
                                    <i class="fas fa-calendar-day me-2 text-info"></i>
                                <span id="todaysDateContent" class="text-light">Mar 21, 2025</span>
                            </div>
                        </div>
                                
                                <div class="card-body overflow-auto" style="max-height: 400px;">
                                    <div class="mb-3">
                                        <label for="dateFilter" class="form-label">Filter by Date:</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent text-light border-secondary">
                                                <i class="fas fa-calendar"></i>
                                            </span>
                                            <input type="date" class="form-control" id="dateFilter">
                                        </div>
                                    </div>
                                    <hr class="border-secondary">
                                    <div class="mb-0">
                                        <h3 class="mb-3 text-center fw-bold" id="monthYearDisplay"></h3>
                                        <div class="row text-center fw-bold bg-dark bg-opacity-50 rounded-3 py-2 mb-2">
                                            <div class="col p-1">Sun</div>
                                            <div class="col p-1">Mon</div>
                                            <div class="col p-1">Tue</div>
                                            <div class="col p-1">Wed</div>
                                            <div class="col p-1">Thu</div>
                                            <div class="col p-1">Fri</div>
                                            <div class="col p-1">Sat</div>
                                        </div>

                                        <!-- Calendar rows with attendance status -->
                                        <div id="ATTENDANCEcalendar" class="pt-3 text-light rounded"></div>
                                    </div>
                                </div>
                                <div class="card-footer text-center d-flex justify-content-around">
                                    <button class="btn btn-primary d-flex align-items-center px-4 py-2 rounded-pill" id="prevMonthBtn">
                                        <i class="fas fa-chevron-left me-2"></i> Previous
                                    </button>
                                    <button class="btn btn-primary d-flex align-items-center px-4 py-2 rounded-pill" id="nextMonthBtn">
                                        Next <i class="fas fa-chevron-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-chart-line me-1 text-info"></i>
                                        <a class="text-light" href="">Performance Ratings</a>
                                    </div>
                                   <!-- <div class="badgeT bg-info text-dark rounded-pill px-3 py-2">
                                        <?php echo number_format($totalAverage, 2); ?> / 5.0
                                    </div> -->
                                </div>
                                <div class="chart-container">
                                    <canvas id="performanceRadarChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-trophy me-1 text-warning"></i> 
                                        <a class="text-light" href="">Top Performers</a>
                                    </div>
                                    <div class="badgeT bg-dark text-light rounded-pill px-3 py-2">
                                        March 2025
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-4">
                                        <!-- Performer 1 -->
                                        <div class="col-md-4">
                                            <div class="card bg-dark bg-opacity-50 border-0 h-100">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <img src="../../uploads/profile_pictures/try.jpg" alt="Performer 1" class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                        <div>
                                                            <h5 class="mb-3">John Doe</h5>
                                                            <span class="badgeT bg-warning text-dark mt-1">Sales Manager</span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="text-light">Performance</span>
                                                        <span class="text-success fw-bold">90%</span>
                                                    </div>
                                                    <div class="progress mb-3" style="height: 8px;">
                                                        <div class="progress-bar bg-success" role="progressbar" style="width: 90%;" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <div class="d-flex justify-content-between text-muted">
                                                        <small><i class="fas fa-tasks me-1"></i> 24 Tasks</small>
                                                        <small><i class="fas fa-star me-1"></i> 4.8/5.0</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Performer 2 -->
                                        <div class="col-md-4">
                                            <div class="card bg-dark bg-opacity-50 border-0 h-100">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <img src="../../uploads/profile_pictures/pfp3.jpg" alt="Performer 2" class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                        <div>
                                                            <h5 class="mb-3">Jane Smith</h5>
                                                            <span class="badgeT bg-warning text-dark mt-1">Marketing Specialist</span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="text-light">Performance</span>
                                                        <span class="text-success fw-bold">85%</span>
                                                    </div>
                                                    <div class="progress mb-3" style="height: 8px;">
                                                        <div class="progress-bar bg-success" role="progressbar" style="width: 85%;" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <div class="d-flex justify-content-between text-muted">
                                                        <small><i class="fas fa-tasks me-1"></i> 19 Tasks</small>
                                                        <small><i class="fas fa-star me-1"></i> 4.5/5.0</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Performer 3 -->
                                        <div class="col-md-4">
                                            <div class="card bg-dark bg-opacity-50 border-0 h-100">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <img src="../../uploads/profile_pictures/logo.jpg" alt="Performer 3" class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                        <div>
                                                            <h5 class="mb-3">Michael Johnson</h5>
                                                            <span class="badgeT bg-warning text-dark mt-1">HR Manager</span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="text-light">Performance</span>
                                                        <span class="text-success fw-bold">80%</span>
                                                    </div>
                                                    <div class="progress mb-3" style="height: 8px;">
                                                        <div class="progress-bar bg-success" role="progressbar" style="width: 80%;" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <div class="d-flex justify-content-between text-muted">
                                                        <small><i class="fas fa-tasks me-1"></i> 17 Tasks</small>
                                                        <small><i class="fas fa-star me-1"></i> 4.2/5.0</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            
            <!-- Logout Modal -->
            <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-sign-out-alt text-warning fa-2x me-3"></i>
                                <p class="mb-0">Are you sure you want to log out?</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                            <form action="../../employee/logout.php" method="POST">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Attendance Modal -->
            <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="timeInfoModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <!-- Modal Header -->
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold" id="timeInfoModalLabel">
                                <i class="fas fa-calendar-check me-2 text-info"></i>Attendance Information
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <!-- Modal Body -->
                        <div class="modal-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="card bg-dark bg-opacity-50 border-0">
                                        <div class="card-body">
                                            <h6 class="fw-bold text-light mb-3">
                                                <i class="fas fa-calendar-day me-2 text-info"></i>Date
                                            </h6>
                                            <p class="fw-bold text-info mb-0 fs-5" id="attendanceDate"></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card bg-dark bg-opacity-50 border-0">
                                        <div class="card-body">
                                            <h6 class="fw-bold text-light mb-3">
                                                <i class="fas fa-check-circle me-2 text-info"></i>Status
                                            </h6>
                                            <p class="fw-bold mb-0 fs-5" id="workStatus"></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card bg-dark bg-opacity-50 border-0">
                                        <div class="card-body">
                                            <h6 class="fw-bold text-light mb-3">
                                                <i class="fas fa-sign-in-alt me-2 text-info"></i>Time In
                                            </h6>
                                            <p class="fw-bold text-info mb-0 fs-5" id="timeIn"></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card bg-dark bg-opacity-50 border-0">
                                        <div class="card-body">
                                            <h6 class="fw-bold text-light mb-3">
                                                <i class="fas fa-sign-out-alt me-2 text-info"></i>Time Out
                                            </h6>
                                            <p class="fw-bold text-info mb-0 fs-5" id="timeOut"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include 'footer.php'; ?>
        </div>
    </div>

<script>
// ATTENDANCE
let currentMonth = new Date().getMonth(); // January is 0, December is 11
let currentYear = new Date().getFullYear();
let employeeId = <?php echo $employeeId; ?>; // Employee ID from PHP session
let filteredDay = null; // Track the filtered day

const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

const operationStartTime = new Date();
operationStartTime.setHours(8, 10, 0, 0);

const operationEndTime = new Date();
operationEndTime.setHours(16, 0, 0, 0);

// Function to format time with AM/PM
function formatTimeWithAmPm(time24) {
    if (!time24 || time24 === 'N/A') {
        return 'No data';  // Handle cases where there's no data
    }
    
    // Split time into hours and minutes
    let [hour, minute] = time24.split(':');
    hour = parseInt(hour); // Convert hour to an integer
    const amPm = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12 || 12; // Convert 0 to 12 for midnight (12 AM)
    return `${hour}:${minute} ${amPm}`;
}

// Function to calculate attendance status
function calculateAttendanceStatus(timeIn, timeOut) {
    let status = '';

    if (timeIn && timeIn !== 'Absent') {
        const timeInDate = new Date(`1970-01-01T${timeIn}:00`);
        if (timeInDate > operationStartTime) {
            status += 'Late';
        }
    }

    if (timeOut && timeOut !== 'Absent') {
        const timeOutDate = new Date(`1970-01-01T${timeOut}:00`);
        if (timeOutDate > operationEndTime) {
            if (status) {
                status += ' & Overtime';
            } else {
                status = 'Overtime';
            }
        }
    }

    return status || 'Present'; // Default to "Present" if no issues
}

function renderCalendar(month, year, attendanceRecords = {}) {
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const firstDay = new Date(year, month, 1).getDay();
    const currentDate = new Date(); // Get the current date
    const currentDay = currentDate.getDate();
    const currentMonth = currentDate.getMonth();
    const currentYear = currentDate.getFullYear();

    let calendarHTML = '<div class="row text-center pt-3">';

    // Fill empty cells for days before the first day of the month
    for (let i = 0; i < firstDay; i++) {
        calendarHTML += '<div class="col"></div>';
    }

    // Fill in the days of the month
    let dayCounter = 1;
    for (let i = firstDay; i < 7; i++) {
        const dayStatus = attendanceRecords[dayCounter];
        const status = (i === 0) ? 'Day Off' :
                       (dayStatus && dayStatus.status === 'Holiday') ? 'Holiday' :
                       dayStatus || '';

        // Check for multiple statuses
        const statusCount = Array.isArray(attendanceRecords[dayCounter]) ? attendanceRecords[dayCounter].length : 1;
        const isFilteredDay = filteredDay && filteredDay.getDate() === dayCounter && filteredDay.getMonth() === month && filteredDay.getFullYear() === year;
        const borderClass = isFilteredDay ? 'border border-2 border-light' : '';

        // Check if the current day is today
        const isToday = dayCounter === currentDay && month === currentMonth && year === currentYear;
        const todayClass = isToday ? 'border border-2 border-light rounded-circle text-white d-flex align-items-center justify-content-center mx-auto' : ''; // Bootstrap classes for circle highlight

        // Simplified status logic, adding 'text-muted' for holidays, leaves, and day off
        let statusClass = '';
        if (statusCount > 1) {
            statusClass = 'text-dark'; // Black for multiple statuses
        } else {
            statusClass = status === 'Present' ? 'text-success' : // Green for Present/Present
                          status === 'Absent' ? 'text-danger' : // Red for Absent
                          status === 'Late' ? 'text-warning' : // Yellow for Late
                          status === 'Half-Day' ? 'text-success' : // Light for Half-Day
                          status === 'Early Out' ? 'text-warning' : // warning for Early Out
                          status === 'Day Off' || status === 'Holiday' || status === 'On Leave' ? 'text-danger' : 
                          'text-default'; // Default color for days with no specific status
        }

        calendarHTML += `
            <div class="col p-1">
                <button class="btn text-light p-0 ${borderClass} ${todayClass}" data-bs-toggle="modal" data-bs-target="#attendanceModal" onclick="showAttendanceDetails(${dayCounter})" style="width: 100%; height: 40px;">
                    <span class="fw-bold ${statusClass}">
                        ${dayCounter}
                    </span>
                </button>
            </div>
        `;
        dayCounter++;
    }
    calendarHTML += '</div>';

    // Continue filling rows for the remaining days
    while (dayCounter <= daysInMonth) {
        calendarHTML += '<div class="row text-center pt-3">';
        let dayOfWeek = 0; // Reset for each row

        for (let i = 0; i < 7 && dayCounter <= daysInMonth; i++) {
            const dayStatus = attendanceRecords[dayCounter]; // Get the status for the current day
            const status = (dayOfWeek === 0) ? 'Day Off' : // Set "Day Off" for Sundays (day 0)
                           (dayStatus && dayStatus.status === 'Holiday') ? 'Holiday' :
                           dayStatus || ''; // Fallback to the status or empty string

            // Check for multiple statuses
            const statusCount = Array.isArray(attendanceRecords[dayCounter]) ? attendanceRecords[dayCounter].length : 1;
            const isFilteredDay = filteredDay && filteredDay.getDate() === dayCounter && filteredDay.getMonth() === month && filteredDay.getFullYear() === year;
            const borderClass = isFilteredDay ? 'border border-2 border-light' : '';

            // Check if the current day is today
            const isToday = dayCounter === currentDay && month === currentMonth && year === currentYear;
            const todayClass = isToday ? 'border border-2 border-light rounded-circle text-white d-flex align-items-center justify-content-center mx-auto' : ''; // Bootstrap classes for circle highlight

            // Simplified status logic, adding 'text-muted' for holidays, leaves, and day off
            let statusClass = '';
            if (statusCount > 1) {
                statusClass = 'text-dark'; // Black for multiple statuses
            } else {
                statusClass = status === 'Present' ? 'text-success' : // Green for Present/Present
                              status === 'Absent' ? 'text-danger' : // Red for Absent
                              status === 'Late' ? 'text-warning' : // Yellow for Late
                              status === 'Half-Day' ? 'text-success' : // Light for Half-Day
                              status === 'Early Out' ? 'text-warning' : // warning for Early Out
                              status === 'Day Off' || status === 'Holiday' || status === 'On Leave' ? 'text-danger' : 
                              'text-default'; // Default color for days with no specific status
            }

            calendarHTML += `
                <div class="col p-1">
                    <button class="btn text-light p-0 ${borderClass} ${todayClass}" data-bs-toggle="modal" data-bs-target="#attendanceModal" onclick="showAttendanceDetails(${dayCounter})" style="width: 100%; height: 40px;">
                        <span class="fw-bold ${statusClass}">
                            ${dayCounter}
                        </span>
                    </button>
                </div>
            `;
            dayCounter++;
            dayOfWeek++;
        }

        if (dayOfWeek < 7) {
            for (let j = dayOfWeek; j < 7; j++) {
                calendarHTML += '<div class="col p-1"></div>';
            }
        }

        calendarHTML += '</div>';
    }

    document.getElementById('ATTENDANCEcalendar').innerHTML = calendarHTML;
    document.getElementById('monthYearDisplay').textContent = `${monthNames[month]} ${year}`;
    document.getElementById('todaysDateContent').textContent = `${monthNames[new Date().getMonth()]} ${new Date().getDate()}, ${new Date().getFullYear()}`;
}

// Fetch attendance for the given month and year
async function fetchAttendance(month, year) {
    try {
        const response = await fetch(`/HR2/employee_db/supervisor/fetch_attendance.php?employee_id=${employeeId}&month=${month + 1}&year=${year}`);
        const data = await response.json();

        if (data.error) {
            console.error('Error fetching attendance data:', data.error);
            return;
        }

        // Handle attendance records and render calendar
        renderCalendar(month, year, data); // Pass attendance data to render calendar
    } catch (error) {
        console.error('Error fetching attendance data:', error);
    }
}

// Show attendance details when a specific day is clicked
async function showAttendanceDetails(day) {
    const selectedDate = `${monthNames[currentMonth]} ${day}, ${currentYear}`;
    document.getElementById('attendanceDate').textContent = selectedDate;

    // Get the current date
    const currentDate = new Date();
    const selectedDateObj = new Date(currentYear, currentMonth, day);
    const isCurrentOrPastDay = selectedDateObj <= currentDate;

    // Check if the selected day is a Sunday
    const isSunday = selectedDateObj.getDay() === 0; // Sunday is 0 in JavaScript's getDay()

    const leaveResponse = await fetch(`/HR2/employee_db/supervisor/fetch_leave.php?employee_id=${employeeId}&day=${day}&month=${currentMonth + 1}&year=${currentYear}`);
    const leaveData = await leaveResponse.json();

    if (leaveData.onLeave) {
        document.getElementById('timeIn').textContent = `On Leave`;
        document.getElementById('timeOut').textContent = `On Leave`;
        document.getElementById('workStatus').textContent = leaveData.leaveType || 'On Leave'; // Fallback to 'On Leave' if leaveType is undefined

        const statusElement = document.getElementById('workStatus');
        statusElement.classList.remove('text-success', 'text-warning', 'text-info', 'text-light', 'text-muted', 'text-warning');
        statusElement.classList.add('text-danger');
    } else {
        const attendanceResponse = await fetch(`/HR2/employee_db/supervisor/fetch_attendance.php?employee_id=${employeeId}&day=${day}&month=${currentMonth + 1}&year=${currentYear}`);
        const data = await attendanceResponse.json();

        if (data.error) {
            console.error(data.error);
            return;
        }

        const isHoliday = data.status === 'Holiday'; // Assuming the status is returned as 'Holiday' for holidays
        const isDayOff = data.status === 'Day Off' || isSunday; // Mark Sunday as "Day Off"

        if (isHoliday) {
            document.getElementById('timeIn').textContent = 'Holiday';
            document.getElementById('timeOut').textContent = 'Holiday';
            document.getElementById('workStatus').textContent = data.holiday_name || 'Holiday';

            const statusElement = document.getElementById('workStatus');
            statusElement.classList.remove('text-success', 'text-warning', 'text-info', 'text-light', 'text-muted', 'text-warning');
            statusElement.classList.add('text-danger');
        } else if (isDayOff) {
            document.getElementById('timeIn').textContent = 'Day Off';
            document.getElementById('timeOut').textContent = 'Day Off';
            document.getElementById('workStatus').textContent = 'Day Off';

            const statusElement = document.getElementById('workStatus');
            statusElement.classList.remove('text-success', 'text-warning', 'text-info', 'text-light', 'text-muted', 'text-warning');
            statusElement.classList.add('text-danger'); // Use danger color for "Day Off"
        } else {
            // Check if it's a future day
            if (!isCurrentOrPastDay) {
                document.getElementById('timeIn').textContent = 'No Data Found';
                document.getElementById('timeOut').textContent = 'No Data Found';
                document.getElementById('workStatus').textContent = 'No Data Found';

                const statusElement = document.getElementById('workStatus');
                statusElement.classList.remove('text-success', 'text-warning', 'text-info', 'text-light', 'text-muted', 'text-warning');
                statusElement.classList.add('text-muted'); // Use a muted color for "No Data Found"
            }
            // Check if it's the current day or a past day and there's no attendance data
            else if (isCurrentOrPastDay && (!data.time_in && !data.time_out)) {
                document.getElementById('timeIn').textContent = 'Absent';
                document.getElementById('timeOut').textContent = 'Absent';
                document.getElementById('workStatus').textContent = 'Absent';

                const statusElement = document.getElementById('workStatus');
                statusElement.classList.remove('text-success', 'text-warning', 'text-info', 'text-light', 'text-muted', 'text-warning');
                statusElement.classList.add('text-danger'); // Changed to danger color for "Absent"
            } else {
                const timeInFormatted = data.time_in ? formatTimeWithAmPm(data.time_in) : 'Absent';
                const timeOutFormatted = data.time_out ? formatTimeWithAmPm(data.time_out) : 'Absent';

                // Pass onLeave status to calculateAttendanceStatus
                const attendanceStatus = calculateAttendanceStatus(data.time_in, data.time_out, day, leaveData.onLeave);

                // Display time-in and time-out
                document.getElementById('timeIn').textContent = timeInFormatted;
                document.getElementById('timeOut').textContent = timeOutFormatted;

                // Display status with individual colors
                const statusElement = document.getElementById('workStatus');
                statusElement.innerHTML = ''; // Clear previous content

                attendanceStatus.forEach((status, index) => {
                    const span = document.createElement('span');
                    span.textContent = status;

                    // Assign color based on the status
                    switch (status) {
                        case 'Late':
                            span.classList.add('text-warning'); // Yellow for Late
                            break;
                        case 'Overtime':
                            span.classList.add('text-primary'); // Blue for Overtime
                            break;
                        case 'Present':
                            span.classList.add('text-success'); // Green for Present
                            break;
                        case 'Absent':
                            span.classList.add('text-danger'); // Red for Absent
                            break;
                        case 'Day Off':
                            span.classList.add('text-danger'); // Light for Day Off
                            break;
                        case 'Half-Day':
                            span.classList.add('text-light'); // Light for Half-Day
                            break;
                        case 'On Leave':
                            span.classList.add('text-danger'); // Red for On Leave
                            break;
                        case 'Early Out':
                            span.classList.add('text-warning'); // warning for Early Out
                            break;
                        default:
                            span.classList.add('text-default'); // Default color
                    }

                    statusElement.appendChild(span);

                    // Add a separator (&) between statuses (except for the last one)
                    if (index < attendanceStatus.length - 1) {
                        const separatorSpan = document.createElement('span');
                        separatorSpan.textContent = ' & ';
                        separatorSpan.classList.add('text-white'); // White color for the separator
                        statusElement.appendChild(separatorSpan);
                    }
                });
            }
        }
    }
}

// Function to calculate attendance status
function calculateAttendanceStatus(timeIn, timeOut, day, onLeave = false) {
    let status = [];

    // Check if the employee is on leave
    if (onLeave) {
        status.push('On Leave');
        return status; // Return early if the employee is on leave
    }

    // Check if the day is a Sunday (0 for Sunday in JavaScript)
    const date = new Date(currentYear, currentMonth, day);
    if (date.getDay() === 0) {
        return ['Day Off'];
    }

    // If there's no time_in or time_out, return "Absent"
    if (!timeIn || !timeOut) {
        return ['Absent'];
    }

    // Convert timeIn and timeOut to Date objects for comparison
    const timeThreshold = new Date('1970-01-01T08:10:00'); // Threshold time for Late check
    const timeInDate = new Date('1970-01-01T' + timeIn);
    const timeOutDate = new Date('1970-01-01T' + timeOut);

    // Check if employee is late
    if (timeInDate > timeThreshold) {
        status.push('Late');
    }

    // Check if there's overtime (Example: work beyond 6:00 PM)
    const overtimeThreshold = new Date('1970-01-01T18:00:00');
    if (timeOutDate > overtimeThreshold) {
        status.push('Overtime');
    }

    // Check if employee left early (1 to 3 hours before operation end time)
    const operationEndTime = new Date('1970-01-01T17:00:00'); // Operation end time is 5:00 PM
    const earlyOutStart = new Date('1970-01-01T14:00:00'); // Early out starts at 2:00 PM
    if (timeOutDate >= earlyOutStart && timeOutDate < operationEndTime) {
        status.push('Early Out');
    }

    // If no specific status, return "Present"
    if (status.length === 0) {
        status.push('Present');
    }

    return status; // Return an array of statuses
}


// Function to format time in HH:MM AM/PM format
function formatTimeWithAmPm(time) {
    const [hours, minutes] = time.split(':');
    const ampm = parseInt(hours) >= 12 ? 'PM' : 'AM';
    const formattedHours = (parseInt(hours) % 12) || 12;
    return `${formattedHours}:${minutes} ${ampm}`;
}


// Event listeners for next and previous month buttons
document.getElementById('nextMonthBtn').addEventListener('click', function() {
    currentMonth++;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    fetchAttendance(currentMonth, currentYear);
});

document.getElementById('prevMonthBtn').addEventListener('click', function() {
    currentMonth--;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    fetchAttendance(currentMonth, currentYear);
});

// Date filter functionality
document.getElementById('dateFilter').addEventListener('change', function () {
    const selectedDate = new Date(this.value); // Get the selected date
    currentMonth = selectedDate.getMonth(); // Update the current month
    currentYear = selectedDate.getFullYear(); // Update the current year
    filteredDay = selectedDate; // Track the filtered day
    fetchAttendance(currentMonth, currentYear); // Fetch and render the calendar for the selected month and year
});

// Fetch the initial calendar for the current month and year
fetchAttendance(currentMonth, currentYear);


 // PHP variables passed to JavaScript
const evaluationData = {
    avg_quality: <?php echo json_encode($evaluation['avg_quality'] ?? null); ?>,
    avg_communication_skills: <?php echo json_encode($evaluation['avg_communication_skills'] ?? null); ?>,
    avg_teamwork: <?php echo json_encode($evaluation['avg_teamwork'] ?? null); ?>,
    avg_punctuality: <?php echo json_encode($evaluation['avg_punctuality'] ?? null); ?>,
    avg_initiative: <?php echo json_encode($evaluation['avg_initiative'] ?? null); ?>,
    totalAverage: <?php echo json_encode($totalAverage ?? null); ?>
};

const ctx = document.getElementById('performanceRadarChart').getContext('2d');

// Function to calculate dynamic font size based on screen width
function getDynamicFontSize() {
    const screenWidth = window.innerWidth;
    if (screenWidth < 576) { // Mobile
        return 10;
    } else if (screenWidth < 768) { // Tablet
        return 12;
    } else { // Desktop
        return 14;
    }
}

const performanceRadarChart = new Chart(ctx, {
    type: 'radar',
    data: {
        labels: ['Quality of Work', 'Communication Skills', 'Teamwork', 'Punctuality', 'Initiative'],
        datasets: [
            {
                label: 'Category Ratings',
                data: [
                    parseFloat(evaluationData.avg_quality || 0).toFixed(2), // Fallback to 0 if invalid
                    parseFloat(evaluationData.avg_communication_skills || 0).toFixed(2), // Fallback to 0 if invalid
                    parseFloat(evaluationData.avg_teamwork || 0).toFixed(2), // Fallback to 0 if invalid
                    parseFloat(evaluationData.avg_punctuality || 0).toFixed(2), // Fallback to 0 if invalid
                    parseFloat(evaluationData.avg_initiative || 0).toFixed(2) // Fallback to 0 if invalid
                ],
                backgroundColor: 'rgba(26, 188, 156, 0.2)', // Light teal fill
                borderColor: 'rgba(26, 188, 156, 1)', // Teal border
                borderWidth: 2
            },
            {
                label: 'Overall Rating',
                data: [
                    parseFloat(evaluationData.totalAverage || 0).toFixed(2), // Fallback to 0 if invalid
                    parseFloat(evaluationData.totalAverage || 0).toFixed(2), // Fallback to 0 if invalid
                    parseFloat(evaluationData.totalAverage || 0).toFixed(2), // Fallback to 0 if invalid
                    parseFloat(evaluationData.totalAverage || 0).toFixed(2), // Fallback to 0 if invalid
                    parseFloat(evaluationData.totalAverage || 0).toFixed(2) // Fallback to 0 if invalid
                ],
                backgroundColor: 'rgba(52, 152, 219, 0.2)', // Light blue fill
                borderColor: 'rgba(52, 152, 219, 1)', // Blue border
                borderWidth: 2
            }
        ]
    },
    options: {
        scales: {
            r: {
                angleLines: {
                    display: true,
                    color: 'rgba(255, 255, 255, 0.2)' // Customize angle line color
                },
                grid: {
                    color: 'rgba(255, 255, 255, 0.1)' // Customize grid line color
                },
                suggestedMin: 0,
                suggestedMax: 6,
                ticks: {
                    stepSize: 1,
                    display: false // Hide the tick labels (1 to 6)
                },
                pointLabels: {
                    color: 'white', // Change label color
                    font: {
                        size: getDynamicFontSize(), // Dynamic font size
                        weight: 'bold', // Make label text bold
                        family: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif' // Change label font family
                    },
                    padding: 15
                }
            }
        },
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    color: 'white',
                    font: {
                        family: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif'
                    }
                }
            },
            tooltip: {
                enabled: true, // Enable tooltips
                backgroundColor: 'rgba(22, 33, 62, 0.8)',
                titleFont: {
                    family: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif',
                    size: 14
                },
                bodyFont: {
                    family: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif',
                    size: 13
                },
                callbacks: {
                    label: function (context) {
                        // Format tooltip value to 2 decimal places
                        return `${context.dataset.label}: ${parseFloat(context.raw || 0).toFixed(2)}`;
                    }
                }
            },
            datalabels: {
                color: function (context) {
                    // Use different colors for the two datasets
                    return context.datasetIndex === 0 ? '#1abc9c' : '#3498db'; // Customize data label colors
                },
                anchor: 'center', // Position the label at the center of the point
                align: function (context) {
                    // Align first dataset labels to top, second dataset labels to bottom
                    return context.datasetIndex === 0 ? 'top' : 'bottom';
                },
                formatter: function (value) {
                    // Format data label value to 2 decimal places
                    return parseFloat(value || 0).toFixed(2);
                },
                font: {
                    weight: 'bold', // Make the text bold
                    size: getDynamicFontSize(), // Use dynamic font size
                    family: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif' // Set font family
                }
            }
        },
        responsive: true, // Enable responsiveness
        maintainAspectRatio: false // Allow chart to adjust height and width independently
    },
    plugins: [ChartDataLabels] // Enable the datalabels plugin
});

// Function to update the chart on window resize
function updateChartOnResize() {
    performanceRadarChart.options.plugins.datalabels.font.size = getDynamicFontSize(); // Update font size
    performanceRadarChart.update(); // Update the chart to reflect new font sizes
}

// Add event listener for window resize
window.addEventListener('resize', updateChartOnResize);

// Set today's date in the header
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('todaysDateContent').textContent = today.toLocaleDateString('en-US', options);
});
</script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/employee.js"></script>
</body>
</html>