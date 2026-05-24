<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../content_helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'editor'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$videoFile = __DIR__ . '/../featured_video.txt';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $url = file_exists($videoFile) ? trim(file_get_contents($videoFile)) : '';
    echo json_encode([
        'success' => true,
        'url' => $url,
        'embed' => app_video_embed_url($url),
        'preview' => app_video_preview_image($url),
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? '');
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$url = trim((string)($data['video_link'] ?? $data['url'] ?? ''));
$embed = app_video_embed_url($url);
if ($url === '' || $embed === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid YouTube, Vimeo, or embeddable video link.']);
    exit;
}

file_put_contents($videoFile, $embed);
echo json_encode([
    'success' => true,
    'url' => $embed,
    'embed' => $embed,
    'preview' => app_video_preview_image($embed),
]);
