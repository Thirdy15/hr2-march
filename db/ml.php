<?php
session_start();
include '../db/db_conn.php';

// Check for database connection errors
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Get the previous month
$currentMonth = date('n'); // Current month (1-12)
$currentYear = date('Y'); // Current year

// Calculate the previous month and year
if ($currentMonth == 1) {
    // If the current month is January, the previous month is December of the previous year
    $month = 12;
    $year = $currentYear - 1;
} else {
    // Otherwise, just subtract 1 from the current month
    $month = $currentMonth - 1;
    $year = $currentYear;
}

// Fetch attendance data for the previous month, excluding 'dayoff'
$attendance_sql = "SELECT employee_id, status, COUNT(*) as count 
                   FROM attendance_log 
                   WHERE status != 'dayoff' AND MONTH(attendance_date) = $month AND YEAR(attendance_date) = $year
                   GROUP BY employee_id, status";
$attendance_result = $conn->query($attendance_sql);

if (!$attendance_result) {
    die(json_encode(['error' => 'Error fetching attendance data: ' . $conn->error]));
}

$attendance_data = [];
while ($row = $attendance_result->fetch_assoc()) {
    $attendance_data[$row['employee_id']][$row['status']] = (int)$row['count']; // Convert to integer
}

// Fetch admin evaluations for the previous month
$admin_sql = "SELECT employee_id, AVG(quality) as avg_quality, AVG(teamwork) as avg_teamwork, 
                     AVG(communication_skills) as avg_communication, AVG(initiative) as avg_initiative, 
                     AVG(punctuality) as avg_punctuality 
              FROM evaluations 
              WHERE MONTH(evaluated_at) = $month AND YEAR(evaluated_at) = $year
              GROUP BY employee_id";
$admin_result = $conn->query($admin_sql);

if (!$admin_result) {
    die(json_encode(['error' => 'Error fetching admin evaluations: ' . $conn->error]));
}

$admin_data = [];
while ($row = $admin_result->fetch_assoc()) {
    $admin_data[$row['employee_id']] = [
        'avg_quality' => (float)$row['avg_quality'],
        'avg_teamwork' => (float)$row['avg_teamwork'],
        'avg_communication' => (float)$row['avg_communication'],
        'avg_initiative' => (float)$row['avg_initiative'],
        'avg_punctuality' => (float)$row['avg_punctuality']
    ];
}

// Fetch PTP evaluations for the previous month
$ptp_sql = "SELECT employee_id, AVG(quality) as avg_quality, AVG(teamwork) as avg_teamwork, 
                   AVG(communication_skills) as avg_communication, AVG(initiative) as avg_initiative, 
                   AVG(punctuality) as avg_punctuality 
            FROM ptp_evaluations 
            WHERE MONTH(evaluated_at) = $month AND YEAR(evaluated_at) = $year
            GROUP BY employee_id";
$ptp_result = $conn->query($ptp_sql);

if (!$ptp_result) {
    die(json_encode(['error' => 'Error fetching PTP evaluations: ' . $conn->error]));
}

$ptp_data = [];
while ($row = $ptp_result->fetch_assoc()) {
    $ptp_data[$row['employee_id']] = [
        'avg_quality' => (float)$row['avg_quality'],
        'avg_teamwork' => (float)$row['avg_teamwork'],
        'avg_communication' => (float)$row['avg_communication'],
        'avg_initiative' => (float)$row['avg_initiative'],
        'avg_punctuality' => (float)$row['avg_punctuality']
    ];
}

// Combine data
$data = [];
foreach ($attendance_data as $employee_id => $attendance) {
    $data[$employee_id] = [
        'attendance' => $attendance,
        'admin_evaluation' => $admin_data[$employee_id] ?? [
            'avg_quality' => 0,
            'avg_teamwork' => 0,
            'avg_communication' => 0,
            'avg_initiative' => 0,
            'avg_punctuality' => 0
        ],
        'ptp_evaluation' => $ptp_data[$employee_id] ?? [
            'avg_quality' => 0,
            'avg_teamwork' => 0,
            'avg_communication' => 0,
            'avg_initiative' => 0,
            'avg_punctuality' => 0
        ]
    ];
}

// Log the data for debugging
error_log(print_r($data, true));

// Return JSON response
header('Content-Type: application/json');
echo json_encode($data);

$conn->close();
?>