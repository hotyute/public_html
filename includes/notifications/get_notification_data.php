<?php
require __DIR__ . '/../../database.php';

function add_notification($user_id, $message) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $message);
    $stmt->execute();
    $stmt->close();
}

function get_notifications($user_id, $all = false) {
    global $conn;
    if ($all) {
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ?");
    } else {
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = FALSE");
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    if (!$all) {
        mark_notifications_as_read($user_id);
    }
    return $notifications;
}

function mark_notifications_as_read($user_id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}
?>
