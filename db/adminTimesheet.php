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

// Include PhpSpreadsheet
require '../vendor/autoload.php'; // Adjust the path if necessary

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Get the selected month, year, and search name from the POST request
$selectedMonth = $_POST['month'];
$selectedYear = $_POST['year'];
$searchName = $_POST['search_name'];

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

// Generate all dates for the selected month and year
$allDatesInMonth = [];
$numberOfDays = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);

for ($day = 1; $day <= $numberOfDays; $day++) {
    $dateStr = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $day);
    $dateObj = new DateTime($dateStr);
    $dayOfWeek = $dateObj->format('N'); // 1=Monday, 7=Sunday

    // Determine status
    if ($dayOfWeek == 7) {
        $status = 'Day Off';
    } else {
        $status = 'No Record';
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
        } else {
            // Mark as absent if no attendance record exists
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

// Group records by date and handle "Day Off" and "Holiday" statuses
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

// Create a new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers
$sheet->setCellValue('A1', 'Date');
$sheet->setCellValue('B1', 'Employee');
$sheet->setCellValue('C1', 'Time-In');
$sheet->setCellValue('D1', 'Time-Out');
$sheet->setCellValue('E1', 'Total Hours');
$sheet->setCellValue('F1', 'Status');

// Add data to the spreadsheet
$row = 2;
foreach ($groupedRecords as $date => $data) {
    if (isset($data['status'])) {
        // Display a single row for "Day Off" or "Holiday"
        $sheet->setCellValue('A' . $row, $date);
        $sheet->setCellValue('B' . $row, 'N/A'); // Employee column
        $sheet->setCellValue('C' . $row, 'N/A'); // Time-In column
        $sheet->setCellValue('D' . $row, 'N/A'); // Time-Out column
        $sheet->setCellValue('E' . $row, 'N/A'); // Total Hours column
        $sheet->setCellValue('F' . $row, $data['status']); // Status column
        $row++;
    } else {
        // Display individual employee rows for other statuses
        foreach ($data['employees'] as $record) {
            $sheet->setCellValue('A' . $row, $record['attendance_date']);
            $sheet->setCellValue('B' . $row, $record['name']);
            $sheet->setCellValue('C' . $row, $record['time_in']);
            $sheet->setCellValue('D' . $row, $record['time_out']);
            $sheet->setCellValue('E' . $row, $record['total_hours']);
            $sheet->setCellValue('F' . $row, $record['status']);
            $row++;
        }
    }
}

// Set the file name
$filename = 'timesheet_' . $selectedMonth . '_' . $selectedYear . '.xlsx';

// Redirect output to a client's web browser (Excel2007)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Ensure no output is sent before the headers
ob_clean(); // Clean the output buffer
ob_end_flush(); // Flush the output buffer

// Create the Excel file and output it to the browser
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;