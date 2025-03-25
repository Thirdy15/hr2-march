<?php 
session_start(); 
include '../../db/db_conn.php'; // Include your database connection 
if (!isset($_SESSION['employee_id'])) { 
echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']); 
exit(); 
} 
if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
$data = json_decode(file_get_contents('php://input'), true); 
$employeeId = $data['employee_id']; // Employee ID for whom the comment is being posted 
$comment = $data['comment'] ?? ''; 
$commenterId = $_SESSION['employee_id']; // Use the logged-in employee's ID 
// Validate input 
if (empty($comment)) { 
echo json_encode(['status' => 'error', 'message' => 'Invalid input']); 
exit(); 
} 
// Fetch the username of the commenter 
$sql = "SELECT first_name, last_name FROM employee_register WHERE employee_id = ?"; 
$stmt = $conn->prepare($sql); 
$stmt->bind_param("i", $commenterId); 
$stmt->execute(); 
$result = $stmt->get_result(); 
$employeeInfo = $result->fetch_assoc(); 
$username = $employeeInfo['first_name'] . ' ' . $employeeInfo['last_name']; 
// Get the current timestamp 
$timestamp = date('Y-m-d H:i:s'); 
// Save comment to the database 
$sql = "INSERT INTO employee_comments (employee_id, comment, username, created_at) VALUES (?, ?, ?, ?)"; 
$stmt = $conn->prepare($sql); 
$stmt->bind_param("isss", $employeeId, $comment, $username, $timestamp); 
if ($stmt->execute()) { 
echo json_encode(['status' => 'success', 'message' => 'Comment saved successfully', 'username' => $username, 'created_at' => $timestamp]); 
} else { 
echo json_encode(['status' => 'error', 'message' => 'Failed to save comment']); 
} 
$stmt->close(); 
$conn->close(); 
} else { 
echo json_encode(['status' => 'error', 'message' => 'Invalid request method']); 
} 
?> 

