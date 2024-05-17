// admin/search_users.php
<?php
require '../database.php';

$searchQuery = $_GET['query'];

$stmt = $pdo->prepare("SELECT id, username, displayname, role FROM users WHERE username = ?");
$stmt->execute([$searchQuery, $searchQuery]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
?>
