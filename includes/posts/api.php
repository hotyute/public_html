<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../sanitize.php';
require_once __DIR__ . '/../content_helpers.php';
require_once __DIR__ . '/../article_audio.php';

header('Content-Type: application/json');

function posts_api_forbidden(): void
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

function posts_api_can_edit(): bool
{
    return isset($_SESSION['user_id']) && in_array($_SESSION['user_role'] ?? '', ['admin', 'editor'], true);
}

function posts_api_thumbnail(?string $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    $scheme = parse_url($value, PHP_URL_SCHEME);
    if ($scheme !== null && !in_array(strtolower($scheme), ['http', 'https'], true)) {
        throw new InvalidArgumentException('Thumbnail must be an http(s) URL or a local path.');
    }

    if ($scheme === null && strpos($value, ':') !== false) {
        throw new InvalidArgumentException('Thumbnail path is invalid.');
    }

    return $value;
}

function posts_api_thumbnail_style(?string $value): string
{
    return app_safe_image_style($value);
}

function posts_api_payload(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    return $data;
}

function posts_api_bool($value, bool $default = true): bool
{
    if ($value === null) {
        return $default;
    }
    if (is_bool($value)) {
        return $value;
    }
    if (is_numeric($value)) {
        return (int)$value === 1;
    }
    if (is_string($value)) {
        $normalized = strtolower(trim($value));
        if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }
    }

    return $default;
}

if (!posts_api_can_edit()) {
    posts_api_forbidden();
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'get';
        if ($action !== 'get') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            exit;
        }

        $postId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$postId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid post id']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, title, content, thumbnail, thumbnail_style FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$post) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Post not found']);
            exit;
        }

        echo json_encode(['success' => true, 'post' => $post]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $data = posts_api_payload();
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    $action = is_scalar($data['action'] ?? null) ? (string)$data['action'] : '';
    $titleInput = $data['title'] ?? '';
    $contentInput = $data['content'] ?? '';
    $thumbnailInput = $data['thumbnail'] ?? '';
    $thumbnailStyleInput = $data['thumbnail_style'] ?? '';
    $generateAudio = posts_api_bool($data['generate_audio'] ?? null, true);

    $title = trim(strip_tags(is_scalar($titleInput) ? (string)$titleInput : ''));
    $rawContent = is_string($contentInput) ? $contentInput : '';
    $rawContent = preg_replace('/<hr\b[^>]*class="[^"]*\bpagebreak\b[^"]*"[^>]*>/i', '<!-- pagebreak -->', $rawContent);
    $content = sanitize_html2($rawContent);
    $thumbnail = posts_api_thumbnail(is_scalar($thumbnailInput) ? (string)$thumbnailInput : '');
    $thumbnailStyle = posts_api_thumbnail_style(is_scalar($thumbnailStyleInput) ? (string)$thumbnailStyleInput : '');

    if ($title === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Title is required']);
        exit;
    }

    if ($action === 'create') {
        $stmt = $pdo->prepare("INSERT INTO posts (title, content, thumbnail, thumbnail_style, user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $content, $thumbnail !== '' ? $thumbnail : null, $thumbnailStyle !== '' ? $thumbnailStyle : null, (int)$_SESSION['user_id']]);
        $postId = (int)$pdo->lastInsertId();
        $audio = article_audio_generate_for_post($pdo, $postId, $content, $generateAudio);
        echo json_encode(['success' => true, 'id' => $postId, 'audio' => $audio]);
        exit;
    }

    if ($action === 'update') {
        $postId = isset($data['id']) ? (int)$data['id'] : 0;
        if ($postId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid post id']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, thumbnail = ?, thumbnail_style = ? WHERE id = ?");
        $stmt->execute([$title, $content, $thumbnail !== '' ? $thumbnail : null, $thumbnailStyle !== '' ? $thumbnailStyle : null, $postId]);
        if ($generateAudio) {
            article_audio_delete_for_post($postId);
            $audio = article_audio_generate_for_post($pdo, $postId, $content, true);
        } elseif (article_audio_manifest_exists($postId)) {
            $audio = article_audio_preserved_result($postId);
        } else {
            $audio = article_audio_generate_for_post($pdo, $postId, $content, false);
        }
        echo json_encode(['success' => true, 'id' => $postId, 'audio' => $audio]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['success' => false, 'message' => 'Server error'];
    if (getenv('APP_DEBUG')) {
        $payload['error'] = $e->getMessage();
    }
    echo json_encode($payload);
}
