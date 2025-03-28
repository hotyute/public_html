<?php
require_once 'includes/session.php';
require_once 'base_config.php';
include_once 'includes/notifications/notification_data.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Handle logout action
if (isset($_GET['logout'])) {
    session_destroy();  // Destroy all session data
    header("Location: /login.php"); // Redirect to the login page after logout
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php include 'includes/stylesheets.php'; ?>

    <script src="/js/script.js"></script>
    <title>Divine Word</title>
    <script>
        // Function to toggle notifications dropdown
        function toggleNotifications(event) {
            event.stopPropagation();  // Stop the click event from propagating to the document
            const dropdown = document.querySelector('.notifications-dropdown');
            const isVisible = dropdown.style.display === 'block';

            // Close any open dropdown first
            closeAllDropdowns();

            // Toggle the current dropdown
            dropdown.style.display = isVisible ? 'none' : 'block';
        }

        // Function to close all dropdowns
        function closeAllDropdowns() {
            const dropdowns = document.querySelectorAll('.notifications-dropdown');
            dropdowns.forEach(dropdown => {
                dropdown.style.display = 'none';
            });
        }

        // Event listener to close the dropdown if clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.notifications-dropdown');
            const button = document.querySelector('.notifications-button');

            if (dropdown && !dropdown.contains(event.target) && !button.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });

        // Attach the toggle function to the notifications button
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.notifications-button').addEventListener('click', toggleNotifications);
        });

        // Function to handle logout redirection
        function logout() {
            window.location.href = '?logout=true';
        }
    </script>
</head>

<body>
    <header style="background-image: url('/images/banner.jpg');">
        <div class="header-content">
            <div class="logo">
                <img src="/images/logo.png" alt="Logo">
            </div>
            <div class="user-info">
                <div class="search-bar">
                    <form action="/search.php" method="GET">
                        <input type="text" name="query" placeholder="Search...">
                        <button type="submit">Search</button>
                    </form>
                </div>
                <?php
                if (isset($_SESSION['username'])) {
                    $user_id = $_SESSION['user_id']; // Assuming user_id is stored in session
                    $notifications = get_notifications($user_id);
                    $notification_count = count($notifications);
                ?>
                    <span>Hello, <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?>
                        <a class="notifications-button" href="javascript:void(0);" onclick="toggleNotifications()">
                            <span class="notification-count">(<?php echo htmlspecialchars($notification_count, ENT_QUOTES, 'UTF-8'); ?>)</span>
                        </a>
                    </span>
                    <div class="notifications-dropdown">
                        <?php
                        if ($notification_count > 0) {
                            foreach ($notifications as $notification) {
                                echo "<div class='notification'>";
                                echo "<a href='/notifications.php'>";
                                echo "<strong>" . htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8') . "</strong><br>";
                                echo htmlspecialchars_decode($notification['message']);
                                echo "</a>";
                                echo "</div>";
                            }
                        } else {
                            echo "<div class='notification'>";
                            echo "<a href='/notifications.php'>";
                            echo "No new notifications";
                            echo "</a>";
                            echo "</div>";
                        }
                        ?>
                    </div>
                    <button class="auth-button" onclick="logout()">Logout</button>
                <?php
                } else {
                ?>
                    <button class="auth-button" onclick="window.location.href='/login.php'">Login</button>
                <?php
                }
                ?>
            </div>
        </div>
        <div class="hamburger" onclick="document.querySelector('.nav-links').classList.toggle('active')">☰</div> <!-- Hamburger Icon -->
        <nav>
            <ul class="nav-links">
                <li><a href="/index.php">Home</a></li>
                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'editor'])) : ?>
                    <li><a href='/admin/admin_panel.php'>Admin</a></li>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id']) && in_array($_SESSION['user_role'], ['admin', 'editor', 'member'])) : ?>
                    <li><a href='/userportal/user_portal.php'>User Portal</a></li>
                <?php endif; ?>
                <li><a href='/members.php'>Members</a></li>
                <li><a href='/contact.php'>Contact Us</a></li>
                <li><a href='/about.php'>About</a></li>
            </ul>
        </nav>
    </header>
