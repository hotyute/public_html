<?php 
include 'header.php';
include_once 'includes/notifications/notification_data.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_notification_id'])) {
    $notification_id = $_POST['remove_notification_id'];
    remove_notification($notification_id, $user_id);
}

$notifications = get_notifications($user_id, true); // Fetch all notifications
echo 'Notifications: ' . $user_id . ' ' . count($notifications);
?>

<div class="container">
    <h2>Your Notifications</h2>
    <?php
    echo 'Notifications: ' . $user_id . ' ' . count($notifications);
    if (count($notifications) > 0) {
        foreach ($notifications as $notification) {
            echo "<div class='notification-main'>";
            echo "<strong>" . htmlspecialchars($notification['title']) . "</strong><br>";
            echo htmlspecialchars($notification['message']) . " - " . htmlspecialchars($notification['created_at']);
            echo "<form method='POST' style='display:inline;'>
                    <input type='hidden' name='remove_notification_id' value='" . $notification['id'] . "'>
                    <button type='submit'>Remove</button>
                  </form>";
            echo "</div>";
        }
    } else {
        echo "<p>No notifications.</p>";
    }
    ?>
</div>
<?php include 'footer.php'; ?>
