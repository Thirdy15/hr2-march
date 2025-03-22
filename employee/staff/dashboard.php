<?php
session_start();
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Staff') {
    header("Location: ../../login.php");
    exit();
}

include '../../db/db_conn.php';

// Fetch user info
$employeeId = $_SESSION['employee_id'];
$sql = "SELECT first_name, middle_name, last_name, email, role, position, pfp FROM employee_register WHERE employee_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$employeeInfo = $result->fetch_assoc();
$stmt->close();
$conn->close();

$profilePicture = !empty($employeeInfo['pfp']) ? $employeeInfo['pfp'] : '../../img/defaultpfp.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Employee Dashboard | HR2</title>
    <link href="../../css/styles.css" rel="stylesheet" />
    <link href="../../css/calendar.css" rel="stylesheet"/>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
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
            --default-day-color: #6c757d;
        }

        body {
            background: rgba(16, 17, 18) !important;
            color: var(--text-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sb-nav-fixed {
            background: rgba(16, 17, 18) !important;
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
            background: rgba(33, 37, 41) !important;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
            font-weight: 600;
        }

        .card-header i {
            color: var(--accent-color);
            margin-right: 10px;
        }

        .card-header a {
            text-decoration: none;
            color: var(--text-light);
            transition: color 0.2s;
        }

        .card-header a:hover {
            color: var(--accent-color);
        }

        .btn-primary {
            background: #3498db !important;
            border: none;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: #2980b9 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-danger {
            background: #e74c3c !important;
            border: none;
            border-radius: 8px;
        }

        .progress {
            height: 8px;
            border-radius: 4px;
            background: rgba(0, 0, 0, 0.2) !important;
            overflow: hidden;
        }

        .progress-bar {
            border-radius: 4px;
        }

        .list-group-item {
            background: rgba(33, 37, 41, 0.7) !important;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem;
            transition: background 0.2s;
        }

        .list-group-item:hover {
            background: rgba(33, 37, 41, 0.9) !important;
        }

        .rounded-circle {
            border: 3px solid var(--secondary-color);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .modal-content {
            background: rgba(33, 37, 41) !important;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .modal-header, .modal-footer {
            border-color: var(--border-color);
        }

        .btn-close-white {
            filter: brightness(0) invert(1);
        }

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
            background-color: rgba(33, 37, 41, 0.5);
        }

        #ATTENDANCEcalendar .btn:hover {
            transform: scale(1.1);
            box-shadow: 0 0 10px rgba(26, 188, 156, 0.5);
            background-color: rgba(33, 37, 41, 0.8);
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
            color: var(--default-day-color) !important;
        }

        .badgeT {
            padding: 3px;
            font-weight: 600;
            border-radius: 30px;
        }

        .form-control {
            background: rgba(33, 37, 41, 0.5) !important;
            border: 1px solid var(--border-color);
            color: var(--text-light);
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }

        .form-control:focus {
            background: rgba(33, 37, 41, 0.7) !important;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.25rem rgba(26, 188, 156, 0.25);
            color: var(--text-light);
        }

        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
            padding: 1rem;
        }

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
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark border-bottom border-1 border-warning">
        <a class="navbar-brand ps-3 text-light" href="../../employee/staff/dashboard.php">Employee Portal</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars text-light"></i></button>
        <div class="d-flex ms-auto me-0 me-md-3 my-2 my-md-0 align-items-center">
            <div class="text-light me-3 p-2 rounded shadow-sm bg-gradient" id="currentTimeContainer"
                style="background: linear-gradient(45deg, #333333, #444444); border-radius: 5px;">
                <span class="d-flex align-items-center">
                    <span class="pe-2">
                        <i class="fas fa-clock"></i>
                        <span id="currentTime">00:00:00</span>
                    </span>
                    <button class="btn btn-outline-warning btn-sm ms-2" type="button" onclick="toggleCalendar()">
                        <i class="fas fa-calendar-alt"></i>
                        <span id="currentDate">00/00/0000</span>
                    </button>
                </span>
            </div>
            <form class="d-none d-md-inline-block form-inline">
                <div class="input-group">
                    <input class="form-control" type="text" placeholder="Search for..." aria-label="Search for..." aria-describedby="btnNavbarSearch" />
                    <button class="btn btn-warning" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
    </nav>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading text-center text-white">Your Profile</div>
                        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle text-light d-flex justify-content-center ms-4" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <img src="<?php echo (!empty($employeeInfo['pfp']) && $employeeInfo['pfp'] !== 'defaultpfp.png')
                                        ? htmlspecialchars($employeeInfo['pfp'])
                                        : '../../img/defaultpfp.jpg'; ?>"
                                        class="rounded-circle border border-light" width="120" height="120" alt="" />
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item" href="../../employee/staff/profile.php">Profile</a></li>
                                    <li><a class="dropdown-item" href="#!">Settings</a></li>
                                    <li><a class="dropdown-item" href="#!">Activity Log</a></li>
                                    <li><hr class="dropdown-divider" /></li>
                                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</a></li>
                                </ul>
                            </li>
                            <li class="nav-item text-light d-flex ms-3 flex-column align-items-center text-center">
                                <span class="big text-light mb-1">
                                    <?php echo htmlspecialchars($employeeInfo['first_name'] . ' ' . $employeeInfo['middle_name'] . ' ' . $employeeInfo['last_name']); ?>
                                </span>
                                <span class="big text-light">
                                    <?php echo htmlspecialchars($employeeInfo['position']); ?>
                                </span>
                            </li>
                        </ul>
                        <div class="sb-sidenav-menu-heading text-center text-muted border-top border-1 border-warning mt-3">Employee Dashboard</div>
                        <a class="nav-link text-light" href="../../employee/staff/dashboard.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Dashboard
                        </a>
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapseTAD" aria-expanded="false" aria-controls="collapseTAD">
                            <div class="sb-nav-link-icon"><i class="fa fa-address-card"></i></div>
                            Time and Attendance
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseTAD" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light" href="../../employee/staff/attendance.php">Attendance Scanner</a>
                                <a class="nav-link text-light" href="">View Attendance Record</a>
                                <a class="nav-link text-light" href="../../admin/timeout.php">Time Out</a>
                            </nav>
                        </div>
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLM" aria-expanded="false" aria-controls="collapseLM">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-times"></i></div>
                            Leave Management
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseLM" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light" href="../../employee/staff/leave_file.php">File Leave</a>
                                <a class="nav-link text-light" href="../../employee/staff/leave_balance.php">View Remaining Leave</a>
                            </nav>
                        </div>
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePM" aria-expanded="false" aria-controls="collapsePM">
                            <div class="sb-nav-link-icon"><i class="fas fa-line-chart"></i></div>
                            Performance Management
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapsePM" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light" href="../../employee/staff/evaluation.php">Evaluation</a>
                            </nav>
                        </div>
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSR" aria-expanded="false" aria-controls="collapseSR">
                            <div class="sb-nav-link-icon"><i class="fa fa-address-card"></i></div>
                            Social Recognition
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseSR" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light" href="../../employee/staff/awardee.php">Awardee</a>
                            </nav>
                        </div>
                        <div class="sb-sidenav-menu-heading text-center text-muted border-top border-1 border-warning mt-3">Feedback</div>
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapseFB" aria-expanded="false" aria-controls="collapseFB">
                            <div class="sb-nav-link-icon"><i class="fas fa-exclamation-circle"></i></div>
                            Report Issue
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseFB" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light" href="">Report Issue</a>
                            </nav>
                        </div>
                    </div>
                </div>
                <div class="sb-sidenav-footer bg-black border-top border-1 border-warning">
                    <div class="small text-light">Logged in as: <?php echo htmlspecialchars($employeeInfo['role']); ?></div>
                </div>
            </nav>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid position-relative px-4">
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
                                        <a class="text-light" href="../../employee/staff/attendance.php">Attendance</a>
                                    </div>
                                    <div class="bg-dark rounded-pill px-3 py-1">
                                        <span class="text-info">Time in: </span>
                                        <span class="text-light">08:11 AM</span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="bg-dark rounded-pill px-3 py-2 d-flex align-items-center me-3">
                                        <i class="fas fa-calendar-day me-2 text-info"></i>
                                        <span id="todaysDateContent" class="text-light">March 21, 2025</span>
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

            <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-dark text-light">
                        <div class="modal-header border-bottom border-warning">
                            <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to log out?
                        </div>
                        <div class="modal-footer border-top border-warning">
                            <button type="button" class="btn border-secondary text-light" data-bs-dismiss="modal">Cancel</button>
                            <form action="../../employee/logout.php" method="POST">
                                <button type="submit" class="btn btn-danger">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="timeInfoModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold" id="timeInfoModalLabel">
                                <i class="fas fa-calendar-check me-2 text-info"></i>Attendance Information
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
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

            <footer class="py-4 bg-light mt-auto bg-dark border-top border-1 border-warning">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Your Website 2023</div>
                        <div>
                            <a href="#">Privacy Policy</a>
                            &middot;
                            <a href="#">Terms & Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script>
        // Global variable for calendar
        let calendar;

        function toggleCalendar() {
            const calendarContainer = document.getElementById('calendarContainer');
            if (calendarContainer.style.display === 'none' || calendarContainer.style.display === '') {
                calendarContainer.style.display = 'block';
                if (!calendar) {
                    initializeCalendar();
                }
            } else {
                calendarContainer.style.display = 'none';
            }
        }

        function initializeCalendar() {
            const calendarEl = document.getElementById('calendar');
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                height: 440,
                events: {
                    url: '../../db/holiday.php',
                    method: 'GET',
                    failure: function() {
                        alert('There was an error fetching events!');
                    }
                }
            });
            calendar.render();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const currentDateElement = document.getElementById('currentDate');
            const currentDate = new Date().toLocaleDateString();
            currentDateElement.textContent = currentDate;
        });

        document.addEventListener('click', function(event) {
            const calendarContainer = document.getElementById('calendarContainer');
            const calendarButton = document.querySelector('button[onclick="toggleCalendar()"]');
            if (!calendarContainer.contains(event.target) && !calendarButton.contains(event.target)) {
                calendarContainer.style.display = 'none';
            }
        });

        let currentMonth = new Date().getMonth();
        let currentYear = new Date().getFullYear();
        let employeeId = <?php echo $employeeId; ?>;
        let filteredDay = null;

        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

        const operationStartTime = new Date();
        operationStartTime.setHours(8, 10, 0, 0);

        const operationEndTime = new Date();
        operationEndTime.setHours(16, 0, 0, 0);

        function formatTimeWithAmPm(time24) {
            if (!time24 || time24 === 'N/A') {
                return 'No data';
            }
            let [hour, minute] = time24.split(':');
            hour = parseInt(hour);
            const amPm = hour >= 12 ? 'PM' : 'AM';
            hour = hour % 12 || 12;
            return `${hour}:${minute} ${amPm}`;
        }

        function calculateAttendanceStatus(timeIn, timeOut) {
            let status = [];
            if (onLeave) {
                status.push('On Leave');
                return status;
            }
            if (date.getDay() === 0) {
                return ['Day Off'];
            }
            if (!timeIn || !timeOut) {
                return ['Absent'];
            }
            const timeInDate = new Date('1970-01-01T' + timeIn);
            const timeOutDate = new Date('1970-01-01T' + timeOut);
            const timeThreshold = new Date('1970-01-01T08:10:00');
            const overtimeThreshold = new Date('1970-01-01T18:00:00');
            const earlyOutStart = new Date('1970-01-01T14:00:00');
            const operationEndTime = new Date('1970-01-01T17:00:00');

            if (timeInDate > timeThreshold) {
                status.push('Late');
            }
            if (timeOutDate > overtimeThreshold) {
                status.push('Overtime');
            }
            if (timeOutDate >= earlyOutStart && timeOutDate < operationEndTime) {
                status.push('Early Out');
            }
            if (status.length === 0) {
                status.push('Present');
            }
            return status;
        }

        function renderCalendar(month, year, attendanceRecords = {}) {
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const firstDay = new Date(year, month, 1).getDay();
            const currentDate = new Date();
            const currentDay = currentDate.getDate();
            const currentMonth = currentDate.getMonth();
            const currentYear = currentDate.getFullYear();

            let calendarHTML = '<div class="row text-center pt-3">';
            for (let i = 0; i < firstDay; i++) {
                calendarHTML += '<div class="col"></div>';
            }
            let dayCounter = 1;
            for (let i = firstDay; i < 7; i++) {
                const dayStatus = attendanceRecords[dayCounter];
                const status = (i === 0) ? 'Day Off' :
                               (dayStatus && dayStatus.status === 'Holiday') ? 'Holiday' :
                               dayStatus || '';
                const statusCount = Array.isArray(attendanceRecords[dayCounter]) ? attendanceRecords[dayCounter].length : 1;
                const isFilteredDay = filteredDay && filteredDay.getDate() === dayCounter && filteredDay.getMonth() === month && filteredDay.getFullYear() === year;
                const borderClass = isFilteredDay ? 'border border-2 border-light' : '';
                const isToday = dayCounter === currentDay && month === currentMonth && year === currentYear;
                const todayClass = isToday ? 'border border-2 border-light rounded-circle text-white d-flex align-items-center justify-content-center mx-auto' : '';
                let statusClass = '';
                if (statusCount > 1) {
                    statusClass = 'text-dark';
                } else {
                    statusClass = status === 'Present' ? 'text-success' :
                                  status === 'Absent' ? 'text-danger' :
                                  status === 'Late' ? 'text-warning' :
                                  status === 'Half-Day' ? 'text-success' :
                                  status === 'Early Out' ? 'text-warning' :
                                  status === 'Day Off' || status === 'Holiday' || status === 'On Leave' ? 'text-danger' :
                                  'text-default';
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
            while (dayCounter <= daysInMonth) {
                calendarHTML += '<div class="row text-center pt-3">';
                let dayOfWeek = 0;
                for (let i = 0; i < 7 && dayCounter <= daysInMonth; i++) {
                    const dayStatus = attendanceRecords[dayCounter];
                    const status = (dayOfWeek === 0) ? 'Day Off' :
                                   (dayStatus && dayStatus.status === 'Holiday') ? 'Holiday' :
                                   dayStatus || '';
                    const statusCount = Array.isArray(attendanceRecords[dayCounter]) ? attendanceRecords[dayCounter].length : 1;
                    const isFilteredDay = filteredDay && filteredDay.getDate() === dayCounter && filteredDay.getMonth() === month && filteredDay.getFullYear() === year;
                    const borderClass = isFilteredDay ? 'border border-2 border-light' : '';
                    const isToday = dayCounter === currentDay && month === currentMonth && year === currentYear;
                    const todayClass = isToday ? 'border border-2 border-light rounded-circle text-white d-flex align-items-center justify-content-center mx-auto' : '';
                    let statusClass = '';
                    if (statusCount > 1) {
                        statusClass = 'text-dark';
                    } else {
                        statusClass = status === 'Present' ? 'text-success' :
                                      status === 'Absent' ? 'text-danger' :
                                      status === 'Late' ? 'text-warning' :
                                      status === 'Half-Day' ? 'text-success' :
                                      status === 'Early Out' ? 'text-warning' :
                                      status === 'Day Off' || status === 'Holiday' || status === 'On Leave' ? 'text-danger' :
                                      'text-default';
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

        async function fetchAttendance(month, year) {
            try {
                const response = await fetch(`/HR2/employee_db/supervisor/fetch_attendance.php?employee_id=${employeeId}&month=${month + 1}&year=${year}`);
                const data = await response.json();
                if (data.error) {
                    console.error('Error fetching attendance data:', data.error);
                    return;
                }
                renderCalendar(month, year, data);
            } catch (error) {
                console.error('Error fetching attendance data:', error);
            }
        }

        async function showAttendanceDetails(day) {
            const selectedDate = `${monthNames[currentMonth]} ${day}, ${currentYear}`;
            document.getElementById('attendanceDate').textContent = selectedDate;
            const currentDate = new Date();
            const selectedDateObj = new Date(currentYear, currentMonth, day);
            const isCurrentOrPastDay = selectedDateObj <= currentDate;
            const isSunday = selectedDateObj.getDay() === 0;
            const leaveResponse = await fetch(`/HR2/employee_db/supervisor/fetch_leave.php?employee_id=${employeeId}&day=${day}&month=${currentMonth + 1}&year=${currentYear}`);
            const leaveData = await leaveResponse.json();
            if (leaveData.onLeave) {
                document.getElementById('timeIn').textContent = `On Leave`;
                document.getElementById('timeOut').textContent = `On Leave`;
                document.getElementById('workStatus').textContent = leaveData.leaveType || 'On Leave';
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
                const isHoliday = data.status === 'Holiday';
                const isDayOff = data.status === 'Day Off' || isSunday;
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
                    statusElement.classList.add('text-danger');
                } else {
                    if (!isCurrentOrPastDay) {
                        document.getElementById('timeIn').textContent = 'No Data Found';
                        document.getElementById('timeOut').textContent = 'No Data Found';
                        document.getElementById('workStatus').textContent = 'No Data Found';
                        const statusElement = document.getElementById('workStatus');
                        statusElement.classList.remove('text-success', 'text-warning', 'text-info', 'text-light', 'text-muted', 'text-warning');
                        statusElement.classList.add('text-muted');
                    } else if (isCurrentOrPastDay && (!data.time_in && !data.time_out)) {
                        document.getElementById('timeIn').textContent = 'Absent';
                        document.getElementById('timeOut').textContent = 'Absent';
                        document.getElementById('workStatus').textContent = 'Absent';
                        const statusElement = document.getElementById('workStatus');
                        statusElement.classList.remove('text-success', 'text-warning', 'text-info', 'text-light', 'text-muted', 'text-warning');
                        statusElement.classList.add('text-danger');
                    } else {
                        const timeInFormatted = data.time_in ? formatTimeWithAmPm(data.time_in) : 'Absent';
                        const timeOutFormatted = data.time_out ? formatTimeWithAmPm(data.time_out) : 'Absent';
                        const attendanceStatus = calculateAttendanceStatus(data.time_in, data.time_out, day, leaveData.onLeave);
                        document.getElementById('timeIn').textContent = timeInFormatted;
                        document.getElementById('timeOut').textContent = timeOutFormatted;
                        const statusElement = document.getElementById('workStatus');
                        statusElement.innerHTML = '';
                        attendanceStatus.forEach((status, index) => {
                            const span = document.createElement('span');
                            span.textContent = status;
                            switch (status) {
                                case 'Late':
                                    span.classList.add('text-warning');
                                    break;
                                case 'Overtime':
                                    span.classList.add('text-primary');
                                    break;
                                case 'Present':
                                    span.classList.add('text-success');
                                    break;
                                case 'Absent':
                                    span.classList.add('text-danger');
                                    break;
                                case 'Day Off':
                                    span.classList.add('text-danger');
                                    break;
                                case 'Half-Day':
                                    span.classList.add('text-light');
                                    break;
                                case 'On Leave':
                                    span.classList.add('text-danger');
                                    break;
                                case 'Early Out':
                                    span.classList.add('text-warning');
                                    break;
                                default:
                                    span.classList.add('text-default');
                            }
                            statusElement.appendChild(span);
                            if (index < attendanceStatus.length - 1) {
                                const separatorSpan = document.createElement('span');
                                separatorSpan.textContent = ' & ';
                                separatorSpan.classList.add('text-white');
                                statusElement.appendChild(separatorSpan);
                            }
                        });
                    }
                }
            }
        }

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

        document.getElementById('dateFilter').addEventListener('change', function () {
            const selectedDate = new Date(this.value);
            currentMonth = selectedDate.getMonth();
            currentYear = selectedDate.getFullYear();
            filteredDay = selectedDate;
            fetchAttendance(currentMonth, currentYear);
        });

        fetchAttendance(currentMonth, currentYear);

        const evaluationData = {
            avg_quality: <?php echo json_encode($evaluation['avg_quality'] ?? null); ?>,
            avg_communication_skills: <?php echo json_encode($evaluation['avg_communication_skills'] ?? null); ?>,
            avg_teamwork: <?php echo json_encode($evaluation['avg_teamwork'] ?? null); ?>,
            avg_punctuality: <?php echo json_encode($evaluation['avg_punctuality'] ?? null); ?>,
            avg_initiative: <?php echo json_encode($evaluation['avg_initiative'] ?? null); ?>,
            totalAverage: <?php echo json_encode($totalAverage ?? null); ?>
        };

        const ctx = document.getElementById('performanceRadarChart').getContext('2d');

        function getDynamicFontSize() {
            const screenWidth = window.innerWidth;
            if (screenWidth < 576) {
                return 10;
            } else if (screenWidth < 768) {
                return 12;
            } else {
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
                            parseFloat(evaluationData.avg_quality || 0).toFixed(2),
                            parseFloat(evaluationData.avg_communication_skills || 0).toFixed(2),
                            parseFloat(evaluationData.avg_teamwork || 0).toFixed(2),
                            parseFloat(evaluationData.avg_punctuality || 0).toFixed(2),
                            parseFloat(evaluationData.avg_initiative || 0).toFixed(2)
                        ],
                        backgroundColor: 'rgba(26, 188, 156, 0.2)',
                        borderColor: 'rgba(26, 188, 156, 1)',
                        borderWidth: 2
                    },
                    {
                        label: 'Overall Rating',
                        data: [
                            parseFloat(evaluationData.totalAverage || 0).toFixed(2),
                            parseFloat(evaluationData.totalAverage || 0).toFixed(2),
                            parseFloat(evaluationData.totalAverage || 0).toFixed(2),
                            parseFloat(evaluationData.totalAverage || 0).toFixed(2),
                            parseFloat(evaluationData.totalAverage || 0).toFixed(2)
                        ],
                        backgroundColor: 'rgba(52, 152, 219, 0.2)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 2
                    }
                ]
            },
            options: {
                scales: {
                    r: {
                        angleLines: {
                            display: true,
                            color: 'rgba(255, 255, 255, 0.2)'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        suggestedMin: 0,
                        suggestedMax: 6,
                        ticks: {
                            stepSize: 1,
                            display: false
                        },
                        pointLabels: {
                            color: 'white',
                            font: {
                                size: getDynamicFontSize(),
                                weight: 'bold',
                                family: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif'
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
                        enabled: true,
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
                                return `${context.dataset.label}: ${parseFloat(context.raw || 0).toFixed(2)}`;
                            }
                        }
                    },
                    datalabels: {
                        color: function (context) {
                            return context.datasetIndex === 0 ? '#1abc9c' : '#3498db';
                        },
                        anchor: 'center',
                        align: function (context) {
                            return context.datasetIndex === 0 ? 'top' : 'bottom';
                        },
                        formatter: function (value) {
                            return parseFloat(value || 0).toFixed(2);
                        },
                        font: {
                            weight: 'bold',
                            size: getDynamicFontSize(),
                            family: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif'
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            },
            plugins: [ChartDataLabels]
        });

        function updateChartOnResize() {
            performanceRadarChart.options.plugins.datalabels.font.size = getDynamicFontSize();
            performanceRadarChart.update();
        }

        window.addEventListener('resize', updateChartOnResize);

        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('todaysDateContent').textContent = today.toLocaleDateString('en-US', options);
        });

        function setCurrentTime() {
            const currentTimeElement = document.getElementById('currentTime');
            const currentDateElement = document.getElementById('currentDate');
            const currentDate = new Date();
            currentDate.setHours(currentDate.getHours() + 0);
            const hours = currentDate.getHours();
            const minutes = currentDate.getMinutes();
            const seconds = currentDate.getSeconds();
            const formattedHours = hours < 10 ? '0' + hours : hours;
            const formattedMinutes = minutes < 10 ? '0' + minutes : minutes;
            const formattedSeconds = seconds < 10 ? '0' + seconds : seconds;
            currentTimeElement.textContent = `${formattedHours}:${formattedMinutes}:${formattedSeconds}`;
            currentDateElement.textContent = currentDate.toLocaleDateString();
        }

        setCurrentTime();
        setInterval(setCurrentTime, 1000);
    </script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/employee.js"></script>
</body>
</html>
