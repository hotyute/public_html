<?php
require '../database.php';

$stmt = $pdo->prepare("SELECT id, username, displayname, role, devotion FROM users");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($users);
?>
