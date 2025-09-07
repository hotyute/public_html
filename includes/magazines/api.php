<?php
// includes/magazines/api.php
require_once '../session.php';
require_once '../database.php';
header('Content-Type: application/json');

// Return JSON on unexpected exceptions (admin-only endpoint)
set_exception_handler(function(Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
    exit;
});

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'editor'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized (admin only)']);
    exit;
}

function current_issue_label(): string {
    $month = (int)date('n');
    $year  = date('Y');
    switch ($month) {
        case 1: case 2:   return "January-February $year";
        case 3: case 4:   return "March-April $year";
        case 5: case 6:   return "May-June $year";
        case 7: case 8:   return "July-August $year";
        case 9: case 10:  return "September-October $year";
        case 11: case 12: return "November-December $year";
        default:          return "Unknown Issue";
    }
}

function ok_url($url): bool {
    if (!filter_var($url, FILTER_VALIDATE_URL)) return false;
    $scheme = parse_url($url, PHP_URL_SCHEME);
    return in_array(strtolower($scheme), ['http', 'https'], true);
}

$method = $_SERVER['REQUEST_METHOD'];

// GET
if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'issues') {
        $stmt = $pdo->query("SELECT DISTINCT issue FROM magazine_articles ORDER BY id DESC");
        $issues = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'issue');
        echo json_encode(['success' => true, 'issues' => $issues]);
        exit;
    }

    // Default: list
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(50, max(1, (int)($_GET['perPage'] ?? 12)));
    $search  = trim($_GET['search'] ?? '');
    $issue   = trim($_GET['issue'] ?? '');

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(title LIKE :q OR author LIKE :q)";
        $params[':q'] = '%' . $search . '%';
    }
    if ($issue !== '') {
        $where[] = "issue = :issue";
        $params[':issue'] = $issue;
    }

    $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $countSql = "SELECT COUNT(*) FROM magazine_articles $sqlWhere";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $offset = ($page - 1) * $perPage;
    $listSql = "SELECT id, title, author, image_url, article_url, DATE(published_date) AS published_date, issue
                FROM magazine_articles
                $sqlWhere
                ORDER BY id DESC
                LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($listSql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'total' => $total, 'items' => $items]);
    exit;
}

// POST
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = $_POST;

    // CSRF
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    $action = $data['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $id            = isset($data['id']) ? (int)$data['id'] : 0;
        $title         = trim($data['title'] ?? '');
        $author        = trim($data['author'] ?? '');
        $image_url     = trim($data['image_url'] ?? '');
        $article_url   = trim($data['article_url'] ?? '');
        $published_date= trim($data['published_date'] ?? date('Y-m-d'));
        $issue         = trim($data['issue'] ?? current_issue_label());

        if ($title === '' || $author === '' || $image_url === '' || $article_url === '' || $issue === '') {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }
        if (!ok_url($image_url) || !ok_url($article_url)) {
            echo json_encode(['success' => false, 'message' => 'URLs must be http(s).']);
            exit;
        }
        $d = DateTime::createFromFormat('Y-m-d', $published_date);
        if (!$d || $d->format('Y-m-d') !== $published_date) {
            $published_date = date('Y-m-d');
        }

        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO magazine_articles (title, author, image_url, article_url, published_date, issue)
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $ok = $stmt->execute([$title, $author, $image_url, $article_url, $published_date, $issue]);
            echo json_encode(['success' => (bool)$ok, 'id' => $pdo->lastInsertId()]);
            exit;
        } else {
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE magazine_articles
                                   SET title = ?, author = ?, image_url = ?, article_url = ?, published_date = ?, issue = ?
                                   WHERE id = ?");
            $ok = $stmt->execute([$title, $author, $image_url, $article_url, $published_date, $issue, $id]);
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }
    }

    if ($action === 'delete') {
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM magazine_articles WHERE id = ?");
        $ok = $stmt->execute([$id]);
        echo json_encode(['success' => (bool)$ok]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);