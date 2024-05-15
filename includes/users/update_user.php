// admin/update_user.php
<?php
require_once '../../base_config.php';
include('includes/config.php');
include('includes/database.php');

$userId = $_POST['id'];
$displayName = $_POST['displayname'];
$role = $_POST['role'];

$stmt = $pdo->prepare("UPDATE users SET displayname = ?, role = ? WHERE id = ?");
$stmt->execute([$displayName, $role, $userId]);

echo json_encode(['success' => true]);
?>
