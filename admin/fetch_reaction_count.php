<?php
session_start();
include '../db/db_conn.php'; // Include your database connection

if (!isset($_SESSION['a_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $employeeId = $data['employee_id'];

    // Validate input
    if (empty($employeeId)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
        exit();
    }

    // Fetch reaction counts for each type
    $sql = "SELECT reaction, COUNT(DISTINCT admin_id) AS count 
            FROM employee_reactions 
            WHERE employee_id = ? 
            GROUP BY reaction";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();

    $reactionCounts = [];
    while ($row = $result->fetch_assoc()) {
        $reactionCounts[$row['reaction']] = $row['count'];
    }

    if (!empty($reactionCounts)) {
        echo json_encode(['status' => 'success', 'counts' => $reactionCounts]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No reactions found']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
