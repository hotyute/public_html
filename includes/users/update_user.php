// admin/update_user.php
<?php
include('../database.php');

$userId = $_POST['id'];
$displayName = $_POST['displayname'];
$role = $_POST['role'];

$stmt = $pdo->prepare("UPDATE users SET displayname = ?, rights = ? WHERE id = ?");
$stmt->execute([$displayName, $role, $userId]);

echo json_encode(['success' => true]);
?>
