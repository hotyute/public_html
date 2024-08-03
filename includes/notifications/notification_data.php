<?php
require __DIR__ . '/../database.php'; // Ensure the path is correct

function add_notification($user_id, $title, $message) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $title, $message]);
}

function get_notifications($user_id, $all = false) {
    global $pdo;
    if ($all) {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = FALSE");
    }
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$all) {
        mark_notifications_as_read($user_id);
    }
    return $notifications;
}

function mark_notifications_as_read($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
    $stmt->execute([$user_id]);
}
?>
