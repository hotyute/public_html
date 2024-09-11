<?php
require '../database.php';

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['userId'];
$devotion = $data['devotion'];

// Update the user's devotion in the database
$stmt = $pdo->prepare("UPDATE roster_data SET devotion = ? WHERE user_id = ?");
$success = $stmt->execute([$devotion, $userId]);

header('Content-Type: application/json');

echo json_encode(['success' => $success]);
?>
