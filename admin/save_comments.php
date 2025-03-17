
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
    $comment = $data['comment'] ?? '';
    $reaction = $data['reaction'] ?? '';

    // Validate input
    if (empty($employeeId) || (empty($comment) && empty($reaction))) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
        exit();
    }

    // Save comment to the database
    if (!empty($comment)) {
        $sql = "INSERT INTO employee_comments (employee_id, comment) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $employeeId, $comment);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Comment saved successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save comment']);
        }
    }

    // Save reaction to the database
    if (!empty($reaction)) {
        $sql = "INSERT INTO employee_reactions (employee_id, reaction) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $employeeId, $reaction);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Reaction saved successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save reaction']);
        }
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
