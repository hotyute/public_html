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
$notifications = get_notifications($user_id, true); // Fetch all notifications

?>

<?php include 'header.php'; ?>
<div class="container">
    <h2>Your Notifications</h2>
    <?php
    if (count($notifications) > 0) {
        foreach ($notifications as $notification) {
            echo "<div class='notification'>" . $notification['message'] . " - " . $notification['created_at'] . "</div>";
        }
    } else {
        echo "<p>No notifications.</p>";
    }
    ?>
</div>
<?php include 'footer.php'; ?>