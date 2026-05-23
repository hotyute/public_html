<?php
require __DIR__ . '/../database.php'; // Ensure the path is correct

function add_notification($user_id, $title, $message) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $title, $message]);
}

function get_notifications($user_id, $all = false, $markRead = false) {
    global $pdo;
    if ($all) {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = FALSE ORDER BY created_at DESC");
    }
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$all && $markRead) {
        mark_notifications_as_read($user_id);
    }
    return $notifications;
}

function mark_notifications_as_read($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
    $stmt->execute([$user_id]);
}

function remove_notification($notification_id, $user_id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $user_id]);
}
?>
