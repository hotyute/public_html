<?php
require '../database.php';

$userId = $_POST['userId'];
$displayName = $_POST['displayName'];
$role = $_POST['role'];

$stmt = $pdo->prepare("UPDATE users SET displayname = ?, role = ? WHERE id = ?");
$stmt->execute([$displayName, $role, $userId]);

header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>
