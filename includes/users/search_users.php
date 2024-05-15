// admin/search_users.php
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../../base_config.php';
include('includes/config.php');
include('includes/database.php');

$searchQuery = '%' . $_GET['query'] . '%';

$stmt = $pdo->prepare("SELECT id, username, displayname, role FROM users WHERE username LIKE ? OR displayname LIKE ?");
$stmt->execute([$searchQuery, $searchQuery]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
?>
