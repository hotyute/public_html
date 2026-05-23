<?php
require_once __DIR__ . '/includes/session.php';
include_once __DIR__ . '/includes/notifications/notification_data.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_notification_id'])) {
    if (hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $notification_id = filter_input(INPUT_POST, 'remove_notification_id', FILTER_VALIDATE_INT);
        if ($notification_id) {
            remove_notification($notification_id, $user_id);
        }
    }
}

$notifications_list = get_notifications($user_id, true); // Fetch all notifications
mark_notifications_as_read($user_id);
?>

<?php include 'header.php'; ?>
<div class="container">
    <h2>Your Notifications</h2>
    <?php
    if (count($notifications_list) > 0) {
        foreach ($notifications_list as $notification) {
            echo "<div class='notification-main'>";
            echo "<strong>" . htmlspecialchars($notification['title']) . "</strong><br>";
            echo strip_tags(htmlspecialchars_decode($notification['message']), '<a><strong><em><span><br>') . " - " . htmlspecialchars($notification['created_at']);
            echo "<form method='POST' style='display:inline;'>
                    <input type='hidden' name='remove_notification_id' value='" . (int)$notification['id'] . "'>
                    <input type='hidden' name='csrf_token' value='" . htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') . "'>
                    <button type='submit'>Delete</button>
                  </form>";
            echo "</div>";
        }
    } else {
        echo "<p>No notifications.</p>";
    }
    ?>
</div>
<?php include 'footer.php'; ?>
