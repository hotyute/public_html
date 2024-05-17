<?php
include('../database.php');

$userId = $_POST['id'];
$displayName = $_POST['displayname'];
$role = $_POST['role'];

$stmt = $pdo->prepare("UPDATE users SET displayname = ?, role = ? WHERE id = ?");
$stmt->execute([$displayName, $role, $userId]);

header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>
