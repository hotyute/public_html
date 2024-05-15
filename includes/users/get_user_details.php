// admin/get_user_details.php
<?php
require_once '../../base_config.php';
include('includes/config.php');
include('includes/database.php');

$userName = $_GET['id'];

$stmt = $pdo->prepare("SELECT username, displayname, role FROM users WHERE username = ?");
$stmt->execute([$userName]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($user);
?>
