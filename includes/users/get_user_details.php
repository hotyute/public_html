<?php
include('../database.php');

$userId = $_GET['id'];

$stmt = $pdo->prepare("SELECT id, displayname, role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($user);
?>
