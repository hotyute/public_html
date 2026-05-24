<?php
require_once __DIR__ . '/../database.php';

function add_notification($user_id, $title, $message, string $type = 'general', ?string $linkUrl = null): void
{
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, notification_type, link_url) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([(int)$user_id, (string)$title, (string)$message, $type, $linkUrl]);
}

function get_notifications($user_id, $all = false, $markRead = false, int $limit = 20): array
{
    global $pdo;
    $limit = max(1, min(100, $limit));
    if ($all) {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT " . $limit);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = FALSE ORDER BY created_at DESC LIMIT " . $limit);
    }
    $stmt->execute([(int)$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$all && $markRead) {
        mark_notifications_as_read($user_id);
    }
    return $notifications;
}

function notification_unread_count($user_id): int
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $stmt->execute([(int)$user_id]);
    return (int)$stmt->fetchColumn();
}

function notification_total_count($user_id): int
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
    $stmt->execute([(int)$user_id]);
    return (int)$stmt->fetchColumn();
}

function mark_notifications_as_read($user_id): void
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
    $stmt->execute([(int)$user_id]);
}

function mark_notification_as_read($notification_id, $user_id): void
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$notification_id, (int)$user_id]);
}

function remove_notification($notification_id, $user_id): void
{
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$notification_id, (int)$user_id]);
}

function remove_all_notifications($user_id): void
{
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
    $stmt->execute([(int)$user_id]);
}

function notification_link(array $notification): string
{
    $link = trim((string)($notification['link_url'] ?? ''));
    if ($link !== '' && $link[0] === '/') {
        return $link;
    }

    return '/notifications.php';
}
?>
