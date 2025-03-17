<?php
session_start();

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Include the database connection  
include '../../db/db_conn.php'; 

$role = $_GET['role']; // Get the evaluated employee's position from the query parameter

if (!$role) {
    echo json_encode(['error' => 'Position parameter is missing.']);
    exit();
}

// Fetch evaluation questions from the database for each category and position
$categories = ['Quality of Work', 'Communication Skills', 'Teamwork', 'Punctuality', 'Initiative'];
$questions = [];

foreach ($categories as $category) {
    // Fetch questions for the specific category and position
    $categorySql = "SELECT question FROM evaluation_questions WHERE category = ? AND role = ?";
    $categoryStmt = $conn->prepare($categorySql);
    $categoryStmt->bind_param('ss', $category, $role); // $position is the position being evaluated
    $categoryStmt->execute();
    $categoryResult = $categoryStmt->get_result();
    $questions[$category] = [];

    if ($categoryResult->num_rows > 0) {
        while ($row = $categoryResult->fetch_assoc()) {
            $questions[$category][] = $row['question'];
        }
    }
}

// Output the questions as JSON
echo json_encode($questions);

// Close the database connection
$conn->close();
?>