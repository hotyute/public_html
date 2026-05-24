<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/notifications/notification_data.php';

function app_message_unread_count(int $userId): int
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE recipient_id = ? AND is_read = FALSE");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function app_message_total_count(int $userId): int
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE recipient_id = ?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function app_message_sent_count(int $userId): int
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE sender_id = ?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function app_message_recent_for_user(int $userId, int $limit = 5): array
{
    global $pdo;
    $limit = max(1, min(20, $limit));
    $stmt = $pdo->prepare("
        SELECT messages.id, messages.subject, messages.body, messages.is_read, messages.created_at,
               COALESCE(users.displayname, users.username, 'Unknown') AS sender_name
        FROM messages
        JOIN users ON users.id = messages.sender_id
        WHERE messages.recipient_id = ?
        ORDER BY messages.created_at DESC
        LIMIT " . $limit
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function app_message_users(int $currentUserId): array
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT id, COALESCE(displayname, username) AS displayname, username, role
        FROM users
        WHERE id <> ?
        ORDER BY displayname ASC, username ASC
    ");
    $stmt->execute([$currentUserId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function app_message_find(int $messageId, int $userId): ?array
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT messages.*,
               COALESCE(sender.displayname, sender.username, 'Unknown') AS sender_name,
               COALESCE(recipient.displayname, recipient.username, 'Unknown') AS recipient_name
        FROM messages
        JOIN users sender ON sender.id = messages.sender_id
        JOIN users recipient ON recipient.id = messages.recipient_id
        WHERE messages.id = ?
          AND (messages.sender_id = ? OR messages.recipient_id = ?)
        LIMIT 1
    ");
    $stmt->execute([$messageId, $userId, $userId]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    return $message ?: null;
}

function app_message_mark_read(int $messageId, int $userId): void
{
    global $pdo;
    $stmt = $pdo->prepare("
        UPDATE messages
        SET is_read = TRUE, read_at = COALESCE(read_at, NOW())
        WHERE id = ? AND recipient_id = ?
    ");
    $stmt->execute([$messageId, $userId]);
}

function app_message_send(int $senderId, int $recipientId, string $subject, string $body): int
{
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, recipient_id, subject, body)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$senderId, $recipientId, $subject, $body]);
    $messageId = (int)$pdo->lastInsertId();

    $senderStmt = $pdo->prepare("SELECT COALESCE(displayname, username, 'Someone') FROM users WHERE id = ?");
    $senderStmt->execute([$senderId]);
    $senderName = (string)($senderStmt->fetchColumn() ?: 'Someone');
    add_notification(
        $recipientId,
        'New message from ' . $senderName,
        htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'),
        'message',
        '/userportal/messages.php?message_id=' . $messageId
    );

    return $messageId;
}
?>
