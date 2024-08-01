<?php
session_start();
require_once('base_config.php');
include_once('includes/notifications/get_notification_data.php');

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$notifications = get_notifications($user_id, true); // Fetch all notifications

?>

<!DOCTYPE html>
<html>
<head>
    <title>Notifications</title>
    <link rel="stylesheet" type="text/css" href="styles.css"> <!-- Assume there's a CSS file -->
</head>
<body>
    <?php include('header.php'); ?>
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
</body>
</html>
