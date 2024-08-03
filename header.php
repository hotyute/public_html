<?php
require_once 'base_config.php';
include_once 'includes/notifications/notification_data.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Handle logout action
if (isset($_GET['logout'])) {
    session_destroy();  // Destroy all session data
    header("Location: " . BASE_URL . "login.php"); // Redirect to the login page after logout
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php include 'includes/stylesheets.php'; ?>

    <script src="<?php echo BASE_URL; ?>js/script.js"></script>
    <title>Divine Word</title>
    <script>
        // Function to handle logout redirection
        function logout() {
            window.location.href = '?logout=true';
        }

        // Function to toggle notifications dropdown
        function toggleNotifications() {
            const dropdown = document.querySelector('.notifications-dropdown');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.notifications-dropdown');
            const button = document.querySelector('.notifications-button');
            if (!dropdown.contains(event.target) && !button.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });
    </script>
</head>

<body>
    <header style="background-image: url('<?php echo BASE_URL; ?>images/banner.jpg');">
        <div class="header-content">
            <div class="logo">
                <img src="<?php echo BASE_URL; ?>images/logo.png" alt="Logo">
            </div>
            <div class="user-info">
                <div class="search-bar">
                    <form action="<?php echo BASE_URL; ?>search.php" method="GET">
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
                    <span>Hello, <?php echo $_SESSION['username']; ?> 
                        <a class="notifications-button" href="javascript:void(0);" onclick="toggleNotifications()">
                            <span class="notification-count">(<?php echo $notification_count; ?>)</span>
                        </a>
                    </span>
                    <div class="notifications-dropdown">
                        <?php
                        if ($notification_count > 0) {
                            foreach ($notifications as $notification) {
                                echo "<div class='notification'>";
                                echo "<a href='notifications.php'>";
                                echo "<strong>" . htmlspecialchars($notification['title']) . "</strong><br>";
                                echo htmlspecialchars($notification['message']);
                                echo "</a>";
                                echo "</div>";
                            }
                        } else {
                            echo "<div class='notification'>No new notifications</div>";
                        }
                        ?>
                    </div>
                    <button class="auth-button" onclick="logout()">Logout</button>
                <?php
                } else {
                ?>
                    <button class="auth-button" onclick="window.location.href='<?php echo BASE_URL; ?>login.php'">Login</button>
                <?php
                }
                ?>
            </div>
        </div>
        <div class="hamburger" onclick="document.querySelector('.nav-links').classList.toggle('active')">☰</div> <!-- Hamburger Icon -->
        <nav>
            <ul class="nav-links">
                <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
                <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'editor')) : ?>
                    <li><a href="<?php echo BASE_URL; ?>admin/admin_panel.php">Admin</a></li>
                <?php endif; ?>
                <li><a href='<?php echo BASE_URL; ?>roster.php'>Roster</a></li>
                <li><a href='<?php echo BASE_URL; ?>contact.php'>Contact Us</a></li>
                <li><a href='<?php echo BASE_URL; ?>about.php'>About</a></li>
            </ul>
        </nav>
    </header>
</body>
</html>
