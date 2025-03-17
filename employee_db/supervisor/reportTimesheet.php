<?php
// Start the session
session_start();

// Include the database connection file
include '../../db/db_conn.php';

// Include PhpSpreadsheet
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Check if the user is logged in
if (!isset($_SESSION['employee_id'])) {
    // Redirect to the login page if the user is not logged in
    header("Location: ../../login.php");
    exit();
}

// Get the logged-in user's ID from the session
$loggedInUserId = $_SESSION['employee_id'];

// Get the selected month and year from the POST request
$selectedMonth = isset($_POST['month']) ? $_POST['month'] : date('m');
$selectedYear = isset($_POST['year']) ? $_POST['year'] : date('Y');

// Fetch employee details (name, ID, department, and position)
$employeeQuery = "SELECT employee_id, CONCAT(first_name, ' ', last_name) AS name, department, role 
                  FROM employee_register
                  WHERE employee_id = ?";
if ($employeeStmt = $conn->prepare($employeeQuery)) {
    $employeeStmt->bind_param("s", $loggedInUserId);
    $employeeStmt->execute();
    $employeeResult = $employeeStmt->get_result();
    if ($employeeRow = $employeeResult->fetch_assoc()) {
        $employeeInfo = $employeeRow; // Populate the $employeeInfo array
    }
    $employeeStmt->close();
} else {
    die("Error preparing employee statement: " . $conn->error);
}

// Fetch leave requests from leave_requests table
$leaveRequests = [];
$leaveQuery = "SELECT start_date, end_date, leave_type 
               FROM leave_requests 
               WHERE employee_id = ? 
               AND status = 'Approved' 
               AND ((MONTH(start_date) = ? AND YEAR(start_date) = ?) 
               OR (MONTH(end_date) = ? AND YEAR(end_date) = ?))";

if ($leaveStmt = $conn->prepare($leaveQuery)) {
    $leaveStmt->bind_param("siiii", $loggedInUserId, $selectedMonth, $selectedYear, $selectedMonth, $selectedYear);
    $leaveStmt->execute();
    $leaveResult = $leaveStmt->get_result();
    
    while ($leaveRow = $leaveResult->fetch_assoc()) {
        $startDate = new DateTime($leaveRow['start_date']);
        $endDate = new DateTime($leaveRow['end_date']);
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($startDate, $interval, $endDate->modify('+1 day'));

        foreach ($dateRange as $date) {
            $leaveRequests[$date->format('Y-m-d')] = $leaveRow['leave_type'];
        }
    }
    $leaveStmt->close();
} else {
    die("Error preparing leave statement: " . $conn->error);
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

// Fetch data from the attendance_log table for the logged-in user and selected month/year
$sql = "SELECT employee_id, name, attendance_date, time_in, time_out, status 
        FROM attendance_log 
        WHERE employee_id = ? 
        AND MONTH(attendance_date) = ? 
        AND YEAR(attendance_date) = ?";

// Prepare and execute the query
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("sii", $loggedInUserId, $selectedMonth, $selectedYear);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch all rows as an associative array
    $attendanceLogs = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate total_hours based on time_in and time_out
        if (!empty($row['time_in']) && !empty($row['time_out'])) {
            $timeIn = new DateTime($row['time_in']);
            $timeOut = new DateTime($row['time_out']);
            $interval = $timeIn->diff($timeOut);

            // Calculate total hours and minutes
            $totalHours = ($interval->days * 24) + $interval->h; // Include days if any
            $minutes = $interval->i;

            // Format the duration display
            $durationParts = [];
            if ($totalHours > 0) {
                $durationParts[] = $totalHours . ' hour' . ($totalHours != 1 ? 's' : '');
            }
            if ($minutes > 0) {
                $durationParts[] = $minutes . ' minute' . ($minutes != 1 ? 's' : '');
            }

            // Handle cases where both are zero
            if (empty($durationParts)) {
                $row['total_hours'] = '0 hours';
            } else {
                $row['total_hours'] = implode(' and ', $durationParts);
            }
        } else {
            // If time_in or time_out is null, set total_hours to 'N/A'
            $row['total_hours'] = 'N/A';
        }

        // Append the row to the attendanceLogs array
        $attendanceLogs[] = $row;
    }

    // Close the statement
    $stmt->close();
} else {
    die("Error preparing statement: " . $conn->error);
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
    } elseif (isset($leaveRequests[$dateStr])) {
        $status = 'Leave (' . $leaveRequests[$dateStr] . ')';
    } else {
        $status = ($dateObj <= $currentDate) ? 'Absent' : 'No Record';
    }

    // Initialize default values
    $timeIn = 'N/A';
    $timeOut = 'N/A';
    $totalHours = 'N/A';

    // Check if there is attendance data for this date
    foreach ($attendanceLogs as $log) {
        if ($log['attendance_date'] == $dateStr) {
            $timeIn = $log['time_in'] ? date('g:i a', strtotime($log['time_in'])) : 'N/A';
            $timeOut = $log['time_out'] ? date('g:i a', strtotime($log['time_out'])) : 'N/A';
            $totalHours = $log['total_hours'];
            $status = $log['status'];
            break;
        }
    }

    // Add the date to the array
    $allDatesInMonth[$dateStr] = [
        'date' => $dateStr,
        'time_in' => $timeIn,
        'time_out' => $timeOut,
        'total_hours' => $totalHours,
        'status' => $status,
    ];
}

// Create a new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers for Employee Information
$sheet->setCellValue('A1', 'Employee Information');
$sheet->setCellValue('A2', 'Name');
$sheet->setCellValue('B2', $employeeInfo['name']);
$sheet->setCellValue('A3', 'Employee ID');
$sheet->setCellValue('B3', $employeeInfo['employee_id']);
$sheet->setCellValue('A4', 'Department');
$sheet->setCellValue('B4', $employeeInfo['department']);
$sheet->setCellValue('A5', 'Position');
$sheet->setCellValue('B5', $employeeInfo['role']);

// Add a blank row
$sheet->setCellValue('A7', 'Timesheet Data');

// Set headers for Timesheet Table
$sheet->setCellValue('A8', 'Date');
$sheet->setCellValue('B8', 'Time-In');
$sheet->setCellValue('C8', 'Time-Out');
$sheet->setCellValue('D8', 'Total Hours');
$sheet->setCellValue('E8', 'Status');

// Populate Timesheet Data
$row = 9;
foreach ($allDatesInMonth as $date => $log) {
    $sheet->setCellValue('A' . $row, date('F j, Y', strtotime($date)));
    $sheet->setCellValue('B' . $row, $log['time_in']);
    $sheet->setCellValue('C' . $row, $log['time_out']);
    $sheet->setCellValue('D' . $row, $log['total_hours']);
    $sheet->setCellValue('E' . $row, $log['status']);
    $row++;
}

// Auto-size columns for better readability
foreach (range('A', 'E') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Set the file name
$filename = 'Timesheet_' . $employeeInfo['name'] . '_' . date('F_Y', strtotime("$selectedYear-$selectedMonth-01")) . '.xlsx';

// Redirect output to a client’s web browser (Excel2007)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Create Excel file and send it to the browser
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>