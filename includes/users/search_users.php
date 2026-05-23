<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$q = trim($_GET['query'] ?? '');
$qLength = function_exists('mb_strlen') ? mb_strlen($q) : strlen($q);
if ($q === '' || $qLength < 2) {
    echo json_encode(['users' => []]);
    exit;
}

$searchParam = '%' . $q . '%';
$stmt = $pdo->prepare("
    SELECT id, username, displayname, role
    FROM users
    WHERE username LIKE ? OR displayname LIKE ?
    ORDER BY username ASC
    LIMIT 20
");
$stmt->execute([$searchParam, $searchParam]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['users' => $results]);
