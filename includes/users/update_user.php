<?php
require '../session.php';
require '../database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$userId = filter_input(INPUT_POST, 'userId', FILTER_VALIDATE_INT);
$displayName = trim($_POST['displayName'] ?? '');
$role = $_POST['role'] ?? 'member';
$allowedRoles = ['admin', 'editor', 'member'];

if (!$userId || $displayName === '' || !in_array($role, $allowedRoles, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$stmt = $pdo->prepare('UPDATE users SET displayname = ?, role = ? WHERE id = ?');
$ok = $stmt->execute([$displayName, $role, $userId]);

echo json_encode(['success' => (bool)$ok]);