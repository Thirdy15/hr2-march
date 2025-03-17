<?php
// Start the session
session_start();

// Include the database connection file
include '../db/db_conn.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['a_id'])) {
    header("Location: ../login.php");
    exit();
}

$adminId = $_SESSION['a_id'];

// Fetch admin info
$sql = "SELECT a_id, firstname, middlename, lastname, birthdate, email, role, department, phone_number, address, pfp FROM admin_register WHERE a_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$adminInfo = $result->fetch_assoc();

// Get the selected month and year from the request (default to current month and year)
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');
$searchName = isset($_GET['search_name']) ? $_GET['search_name'] : '';

// Fetch all employees from the employee_register table
$employeeQuery = "SELECT employee_id, CONCAT(first_name, ' ', last_name) AS name 
                  FROM employee_register";
if (!empty($searchName)) {
    $employeeQuery .= " WHERE CONCAT(first_name, ' ', last_name) LIKE '%" . $searchName . "%'";
}
$employeeResult = $conn->query($employeeQuery);
$employees = [];
while ($row = $employeeResult->fetch_assoc()) {
    $employees[$row['employee_id']] = $row['name'];
}

// Fetch attendance logs for the selected month and year
$attendanceQuery = "SELECT employee_id, attendance_date, time_in, time_out, status 
                    FROM attendance_log 
                    WHERE MONTH(attendance_date) = ? 
                    AND YEAR(attendance_date) = ?";
if ($attendanceStmt = $conn->prepare($attendanceQuery)) {
    $attendanceStmt->bind_param("ii", $selectedMonth, $selectedYear);
    $attendanceStmt->execute();
    $attendanceResult = $attendanceStmt->get_result();

    // Fetch all rows as an associative array
    $attendanceLogs = [];
    while ($row = $attendanceResult->fetch_assoc()) {
        $attendanceLogs[$row['attendance_date']][$row['employee_id']] = $row;
    }

    // Close the statement
    $attendanceStmt->close();
} else {
    die("Error preparing attendance statement: " . $conn->error);
}

// Fetch holidays from non_working_days table
$holidays = [];
$holidayQuery = "SELECT date, description FROM non_working_days 
                 WHERE MONTH(date) = ? AND YEAR(date) = ?";
if ($holidayStmt = $conn->prepare($holidayQuery)) {
    $holidayStmt->bind_param("ii", $selectedMonth, $selectedYear);
    $holidayStmt->execute();
    $holidayResult = $holidayStmt->get_result();
    while ($holidayRow = $holidayResult->fetch_assoc()) {
        $holidays[$holidayRow['date']] = $holidayRow['description'];
    }
    $holidayStmt->close();
} else {
    die("Error preparing holiday statement: " . $conn->error);
}

// Fetch leave requests from leave_requests table
$leaveRequests = [];
$leaveQuery = "SELECT start_date, end_date, leave_type, employee_id FROM leave_requests 
               WHERE status = 'Approved' 
               AND ((MONTH(start_date) = ? AND YEAR(start_date) = ?) 
               OR (MONTH(end_date) = ? AND YEAR(end_date) = ?))";

if ($leaveStmt = $conn->prepare($leaveQuery)) {
    $leaveStmt->bind_param("iiii", $selectedMonth, $selectedYear, $selectedMonth, $selectedYear);
    $leaveStmt->execute();
    $leaveResult = $leaveStmt->get_result();
    
    while ($leaveRow = $leaveResult->fetch_assoc()) {
        $startDate = new DateTime($leaveRow['start_date']);
        $endDate = new DateTime($leaveRow['end_date']);
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($startDate, $interval, $endDate->modify('+1 day'));

        foreach ($dateRange as $date) {
            $leaveRequests[$date->format('Y-m-d')][$leaveRow['employee_id']] = $leaveRow['leave_type'];
        }
    }
    $leaveStmt->close();
} else {
    die("Error preparing leave statement: " . $conn->error);
}

// Close the database connection
$conn->close();

// Generate all dates for the selected month and year
$allDatesInMonth = [];
$numberOfDays = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
$currentDate = new DateTime(); // Get current date/time

for ($day = 1; $day <= $numberOfDays; $day++) {
    $dateStr = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $day);
    $dateObj = new DateTime($dateStr);
    $dayOfWeek = $dateObj->format('N'); // 1=Monday, 7=Sunday

    // Reset time components for accurate comparison
    $dateObj->setTime(0, 0, 0);
    $currentDate->setTime(0, 0, 0);

    // Determine status
    if ($dayOfWeek == 7) {
        $status = 'Day Off';
    } elseif (isset($holidays[$dateStr])) {
        $status = 'Holiday (' . $holidays[$dateStr] . ')';
    } else {
        $status = ($dateObj <= $currentDate) ? 'Absent' : 'No Record';
    }

    // Add the date to the array
    $allDatesInMonth[$dateStr] = [
        'date' => $dateStr,
        'status' => $status,
    ];
}

// Merge attendance logs with all dates for each employee
$employeeAttendance = [];
foreach ($allDatesInMonth as $dateStr => $dateInfo) {
    foreach ($employees as $employee_id => $name) {
        if (isset($attendanceLogs[$dateStr][$employee_id])) {
            // Use the attendance log if it exists
            $employeeAttendance[] = array_merge($attendanceLogs[$dateStr][$employee_id], ['name' => $name]);
        } elseif (isset($leaveRequests[$dateStr][$employee_id])) {
            // Use leave request if it exists
            $employeeAttendance[] = [
                'employee_id' => $employee_id,
                'name' => $name,
                'attendance_date' => $dateStr,
                'time_in' => 'N/A',
                'time_out' => 'N/A',
                'total_hours' => 'N/A',
                'status' => 'Leave (' . $leaveRequests[$dateStr][$employee_id] . ')',
            ];
        } else {
            // Mark as absent if no attendance or leave record exists
            $employeeAttendance[] = [
                'employee_id' => $employee_id,
                'name' => $name,
                'attendance_date' => $dateStr,
                'time_in' => 'N/A',
                'time_out' => 'N/A',
                'total_hours' => 'N/A',
                'status' => $dateInfo['status'],
            ];
        }
    }
}

// Filter by employee name if search is provided
if (!empty($searchName)) {
    $employeeAttendance = array_filter($employeeAttendance, function($record) use ($searchName) {
        return stripos($record['name'], $searchName) !== false;
    });
    // Re-index array after filtering
    $employeeAttendance = array_values($employeeAttendance);
}

// Sort by date (newest first) and then by employee name
usort($employeeAttendance, function($a, $b) {
    $dateCompare = strcmp($b['attendance_date'], $a['attendance_date']);
    if ($dateCompare === 0) {
        return strcmp($a['name'], $b['name']);
    }
    return $dateCompare;
});

// Calculate metrics for each employee
$employeeMetrics = [];
foreach ($employees as $employee_id => $name) {
    $totalLeaveDays = 0;
    $totalAbsentDays = 0;
    $totalWorkHours = 0;

    foreach ($allDatesInMonth as $dateStr => $dateInfo) {
        if (isset($leaveRequests[$dateStr][$employee_id])) {
            // Employee is on leave
            $totalLeaveDays++;
        } elseif (isset($attendanceLogs[$dateStr][$employee_id])) {
            // Employee has attendance record
            $attendance = $attendanceLogs[$dateStr][$employee_id];
            if ($attendance['time_in'] !== 'N/A' && $attendance['time_out'] !== 'N/A') {
                // Calculate work hours
                $timeIn = new DateTime($attendance['time_in']);
                $timeOut = new DateTime($attendance['time_out']);
                $interval = $timeIn->diff($timeOut);
                $totalWorkHours += $interval->h + ($interval->i / 60); // Convert minutes to hours
            }
        } elseif ($dateInfo['status'] === 'Absent') {
            // Employee is absent
            $totalAbsentDays++;
        }
    }

    // Add metrics to the array
    $employeeMetrics[] = [
        'employee_id' => $employee_id,
        'name' => $name,
        'total_leave_days' => $totalLeaveDays,
        'total_absent_days' => $totalAbsentDays,
        'total_work_hours' => round($totalWorkHours, 2), // Round to 2 decimal places
    ];
}


$employeeTotalHours = [];

foreach ($employeeAttendance as $record) {
    $employeeId = $record['employee_id'];
    $timeIn = $record['time_in'];
    $timeOut = $record['time_out'];

    // Calculate total hours if both time_in and time_out are valid
    if ($timeIn !== 'N/A' && $timeOut !== 'N/A') {
        $timeInObj = new DateTime($timeIn);
        $timeOutObj = new DateTime($timeOut);
        $interval = $timeInObj->diff($timeOutObj);

        // Calculate total hours and minutes
        $totalHours = $interval->h + ($interval->i / 60); // Convert minutes to hours
        $employeeTotalHours[$employeeId] = ($employeeTotalHours[$employeeId] ?? 0) + $totalHours;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Timesheet</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/styles.css" rel="stylesheet">
    <link href="../css/calendar.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #4cc9f0;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #3498db;
            --bg-dark: rgba(33, 37, 41) !important;
            --bg-black: rgba(16, 17, 18) !important;
            --card-bg: rgba(33, 37, 41) !important;
            --border-color: #333;
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: rgba(16, 17, 18) !important;
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            padding: 2rem 1rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .card-header {
            background-color: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-header i {
            margin-right: 0.75rem;
            color: var(--accent-color);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .employee-info-card {
            background: linear-gradient(145deg, #1e1e1e, #252525);
            border: none;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .employee-info-header {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-bottom: none;
            border-radius: 12px 12px 0 0;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .info-item i {
            width: 40px;
            height: 40px;
            background-color: rgba(67, 97, 238, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--primary-color);
        }
        
        .info-label {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-secondary);
        }
        
        .info-value {
            font-size: 1.1rem;
            color: var(--text-primary);
        }
        
        .stats-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            height: 100%;
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .stats-present .stats-icon {
            background-color: rgba(46, 204, 113, 0.15);
            color: var(--success-color);
        }
        
        .stats-late .stats-icon {
            background-color: rgba(243, 156, 18, 0.15);
            color: var(--warning-color);
        }
        
        .stats-absent .stats-icon {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger-color);
        }
        
        .stats-hours .stats-icon {
            background-color: rgba(52, 152, 219, 0.15);
            color: var(--info-color);
        }
        
        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .month-selector {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }
        
        .form-control, .form-select {
            background-color: var(--bg-black);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            background-color: var(--bg-black);
            border-color: var(--primary-color);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .form-select option {
            background-color: var(--bg-black);
            color: var(--text-primary);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #3a56d4;
            border-color: #3a56d4;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            background-color: #27ae60;
            border-color: #27ae60;
            transform: translateY(-2px);
        }
        
        .table {
            color: var(--text-primary);
            border-color: var(--border-color);
        }
        
        .table th {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            border-color: var(--border-color);
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-color: var(--border-color);
        }
        
        .table tbody tr {
            background-color: var(--card-bg);
            transition: background-color 0.2s;
        }
        
        .table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .badgeTimesheet {
            font-size: 0.8rem;
            font-weight: 500;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
        }
        
        .badge-present {
            background-color: rgba(46, 204, 113, 0.15);
            color: var(--success-color);
            border: 1px solid rgba(46, 204, 113, 0.3);
        }
        
        .badge-late {
            background-color: rgba(243, 156, 18, 0.15);
            color: var(--warning-color);
            border: 1px solid rgba(243, 156, 18, 0.3);
        }
        
        .badge-absent {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger-color);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        .badge-holiday {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger-color);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        .badge-leave {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger-color);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        .badge-dayoff {
            background-color: rgba(149, 165, 166, 0.15);
            color: #95a5a6;
            border: 1px solid rgba(149, 165, 166, 0.3);
        }
        
        .badge-norecord {
            background-color: rgba(189, 195, 199, 0.15);
            color: #bdc3c7;
            border: 1px solid rgba(189, 195, 199, 0.3);
        }
        
        .progress {
            height: 8px;
            background-color: var(--bg-black);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        .progress-bar {
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }
        
        .attendance-chart {
            height: 200px;
            margin-top: 1rem;
        }
        
        .datatable-wrapper .datatable-top,
        .datatable-wrapper .datatable-bottom {
            padding: 0.75rem 1.5rem;
            background-color: var(--card-bg);
            border-color: var(--border-color);
        }
        
        .datatable-wrapper .datatable-search input {
            border: 1px solid var(--border-color);
            color: rgba(33, 37, 41) !important;
            border-radius: 8px;
            padding: 0.5rem 1rem;
        }
        
        .datatable-wrapper .datatable-selector {
            border: 1px solid var(--border-color);
            color: rgba(33, 37, 41) !important;
                        border-radius: 8px;
            padding: 0.5rem;
        }
        
        .datatable-wrapper .datatable-info {
            color: var(--text-secondary);
        }
        
        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .page-title {
                font-size: 1.75rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .card-header .btn {
                margin-top: 1rem;
                align-self: flex-end;
            }
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container" id="calendarContainer" 
                    style="position: fixed; top: 3%; right: 0; z-index: 1050; 
                    width: 700px; display: none;">
                    <div class="row">
                        <div class="col-md-12">
                            <div id="calendar" class="p-2"></div>
                        </div>
                    </div>
                </div>
                
                <div class="container-fluid px-4">
                    <div class="page-header">
                        <h1> Admin Timesheet </h1>
                        <div class="d-flex align-items-center">
                            <span class="text-secondary me-3">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)); ?>
                            </span>
                        </div>
                    </div>

                    <div class="row fade-in" style="animation-delay: 0.2s;">
                        <div class="col-md-4 col-sm-6 mb-4">
                            <div class="stats-card stats-late">
                                <div class="stats-icon">
                                    <i class="fas fa-umbrella-beach"></i>
                                </div>
                                <div class="stats-value">
                                    <?php
                                    // Calculate total leave days for all employees
                                    $totalLeaveDays = array_sum(array_column($employeeMetrics, 'total_leave_days'));
                                    echo $totalLeaveDays;
                                    ?>
                                </div>
                                <div class="stats-label">Total Leave</div>
                                <div class="progress">
                                    <div class="progress-bar" style="width: <?php echo ($totalLeaveDays / $numberOfDays) * 100; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6 mb-4">
                            <div class="stats-card stats-absent">
                                <div class="stats-icon">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <div class="stats-value">
                                    <?php
                                    // Calculate total absent days for all employees
                                    $totalAbsentDays = array_sum(array_column($employeeMetrics, 'total_absent_days'));
                                    echo $totalAbsentDays;
                                    ?>
                                </div>
                                <div class="stats-label">Total Absent</div>
                                <div class="progress">
                                    <div class="progress-bar" style="width: <?php echo ($totalAbsentDays / $numberOfDays) * 100; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6 mb-4">
                            <div class="stats-card stats-hours">
                                <div class="stats-icon">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                                <div class="stats-value">
                                    <?php
                                    // Calculate total work hours for all employees
                                    $totalWorkHours = array_sum(array_column($employeeMetrics, 'total_work_hours'));
                                    echo round($totalWorkHours, 2); // Round to 2 decimal places
                                    ?>
                                </div>
                                <div class="stats-label">Total Work Hours</div>
                                <div class="progress">
                                    <div class="progress-bar" style="width: <?php echo min(100, ($totalWorkHours / 160) * 100); ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="month-selector fade-in" style="animation-delay: 0.1s;">
                        <form method="GET" class="row align-items-end">
                            <div class="col-md-3">
                                <label for="month" class="form-label">Month</label>
                                <select name="month" id="month" class="form-select bg-light text-dark">
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($i == $selectedMonth) ? 'selected' : ''; ?>>
                                            <?php echo date('F', mktime(0, 0, 0, $i, 10)); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="year" class="form-label">Year</label>
                                <input type="number" name="year" id="year" class="form-control bg-light text-dark" value="<?php echo $selectedYear; ?>" min="2000" max="<?php echo date('Y'); ?>">
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3 mb-md-0">
                                    <label for="search_name" class="filter-label">Search Employee</label>
                                    <input type="text" name="search_name" id="search_name" class="form-control bg-light text-dark" value="<?php echo htmlspecialchars($searchName); ?>" placeholder="Enter name...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i> Apply Filter
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center text-light">
                            <h5 class="mb-0"><i class="fas fa-table"></i> Detailed Timesheet</h5>
                            <!-- Add a form for the download button -->
                            <form method="POST" action="../db/adminTimesheet.php">
                                <input type="hidden" name="month" value="<?php echo $selectedMonth; ?>">
                                <input type="hidden" name="year" value="<?php echo $selectedYear; ?>">
                                <input type="hidden" name="search_name" value="<?php echo $searchName; ?>">
                                <button type="submit" name="download_excel" class="btn btn-success text-dark">
                                    <i class="fas fa-download me-2 text-dark"></i> Download
                                </button>
                            </form>
                        </div>
                        <div class="card-body">
                            <table id="timesheetTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Employee</th>
                                        <th>Time-In</th>
                                        <th>Time-Out</th>
                                        <th>Total Hours</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($employeeAttendance)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No data available for the selected criteria.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php
                                        // Group records by date
                                        $groupedRecords = [];
                                        foreach ($employeeAttendance as $record) {
                                            $date = $record['attendance_date'];
                                            $status = $record['status'];

                                            // Check if the status is "Day Off" or "Holiday"
                                            if (strpos($status, 'Day Off') !== false || strpos($status, 'Holiday') !== false) {
                                                // Add to grouped records with a special key
                                                $groupedRecords[$date]['status'] = $status;
                                            } else {
                                                // Add to grouped records as normal
                                                $groupedRecords[$date]['employees'][] = $record;
                                            }
                                        }

                                        // Loop through grouped records
                                        foreach ($groupedRecords as $date => $data): 
                                            if (isset($data['status'])): 
                                                // Display a single row for "Day Off" or "Holiday"
                                                ?>
                                                <tr class="highlight-red"> <!-- Add highlight class -->
                                                    <td><?php echo date('F j, Y', strtotime($date)); ?></td>
                                                    <td>N/A</td> <!-- Employee column -->
                                                    <td>N/A</td> <!-- Time-In column -->
                                                    <td>N/A</td> <!-- Time-Out column -->
                                                    <td>N/A</td> <!-- Total Hours column -->
                                                    <td>
                                                        <?php
                                                        $status = $data['status'];
                                                        $statusClass = '';
                                                        if (strpos($status, 'Day Off') !== false) {
                                                            $statusClass = 'badge-dayoff';
                                                        } elseif (strpos($status, 'Holiday') !== false) {
                                                            $statusClass = 'badge-holiday';
                                                        }
                                                        ?>
                                                        <span class="badgeTimesheet <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                                    </td>
                                                </tr>
                                            <?php else: 
                                                // Display individual employee rows
                                                foreach ($data['employees'] as $record): ?>
                                                    <tr>
                                                        <td><?php echo date('F j, Y', strtotime($record['attendance_date'])); ?></td>
                                                        <td><?php echo $record['name'] . " (" . $record['employee_id'] . ")"; ?></td>
                                                        <td><?php echo $record['time_in']; ?></td>
                                                        <td><?php echo $record['time_out']; ?></td>
                                                        <td>
                                                            <?php
                                                            // Calculate total hours for the employee
                                                            $timeIn = strtotime($record['time_in']);
                                                            $timeOut = strtotime($record['time_out']);
                                                            if ($timeIn && $timeOut) {
                                                                $workedSeconds = $timeOut - $timeIn; // Difference in seconds
                                                                $workedHours = floor($workedSeconds / 3600); // Full hours
                                                                $workedMinutes = floor(($workedSeconds % 3600) / 60); // Remaining minutes
                                                                echo $workedHours . ' hours and ' . $workedMinutes . ' minutes';
                                                            } else {
                                                                echo 'N/A';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                                // Set badge class based on status
                                                                $statusClass = '';
                                                                $status = $record['status'];

                                                                if (strpos($status, 'Present') !== false) {
                                                                    $statusClass = 'badge-present';
                                                                } elseif (strpos($status, 'Late') !== false) {
                                                                    $statusClass = 'badge-late';
                                                                } elseif (strpos($status, 'Overtime') !== false) {
                                                                    $statusClass = 'badge-overtime';
                                                                } elseif (strpos($status, 'Leave') !== false) {
                                                                    $statusClass = 'badge-leave';
                                                                } elseif (strpos($status, 'Absent') !== false) {
                                                                    $statusClass = 'badge-absent';
                                                                } elseif (strpos($status, 'No Record') !== false) {
                                                                    $statusClass = 'badge-norecord';
                                                                }
                                                            ?>
                                                            <span class="badgeTimesheet <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; 
                                            endif; 
                                        endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'footer.php'; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
    <script>

        // Initialize DataTable
        document.addEventListener('DOMContentLoaded', function () {
            const table = new simpleDatatables.DataTable("#timesheetTable", {
                searchable: true,
                fixedHeight: true,
                perPage: 10,
                perPageSelect: [5, 10, 15, 20, 25],
                labels: {
                    placeholder: "Search timesheet...",
                    perPage: "{select} entries per page",
                    noRows: "No entries found",
                    info: "Showing {start} to {end} of {rows} entries",
                }
            });
        });
    </script>
</body>
</html>

