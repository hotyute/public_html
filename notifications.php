<?php 
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

$notifications_list = get_notifications($user_id, true); // Fetch all notifications
?>

<?php include 'header.php'; ?>
<div class="container">
    <h2>Your Notifications</h2>
    <?php
    if (count($notifications_list) > 0) {
        foreach ($notifications_list as $notification) {
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
