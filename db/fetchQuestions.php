<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    // Return an error response if the session is not set
    echo json_encode(['error' => 'Unauthorized access. Please log in.']);
    exit();
}

include '../db/db_conn.php'; 

$role = $_GET['role'] ?? null;

if (!$role) {
    echo json_encode(['error' => 'Position parameter is missing.']);
    exit();
}

$categories = ['Quality of Work', 'Communication Skills', 'Teamwork', 'Punctuality', 'Initiative'];
$questions = [];

foreach ($categories as $category) {
    $categorySql = "SELECT question FROM evaluation_questions WHERE category = ? AND role = ?";
    $categoryStmt = $conn->prepare($categorySql);
    $categoryStmt->bind_param('ss', $category, $role);
    $categoryStmt->execute();
    $categoryResult = $categoryStmt->get_result();
    $questions[$category] = [];

    if ($categoryResult->num_rows > 0) {
        while ($row = $categoryResult->fetch_assoc()) {
            $questions[$category][] = $row['question'];
        }
    }
}

echo json_encode($questions);

$conn->close();
?>