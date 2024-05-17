<?php
require '../database.php';

$searchQuery = '%' . $_GET['query'] . '%';
$response = [];

$stmt = $pdo->prepare("SELECT id, username, displayname, role FROM users WHERE username LIKE ? OR displayname LIKE ?");
$stmt->execute([$searchQuery, $searchQuery]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($results) {
    $response = $results;
} else {
    $response['error'] = 'No results found';
}

header('Content-Type: application/json');
echo json_encode($response);
?>
