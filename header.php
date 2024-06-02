<?php
require_once 'base_config.php';
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
    </script>
</head>

<body>
    <header style="background-image: url('<?php echo BASE_URL; ?>images/banner.jpg');">
        <div class="header-content">
            <div class="logo">
                <img src="<?php echo BASE_URL; ?>images/logo.png" alt="Logo">
            </div>
            <div class="userinfo">
                <div class="search-bar">
                    <form action="<?php echo BASE_URL; ?>search.php" method="GET">
                        <input type="text" name="query" placeholder="Search...">
                        <button type="submit">Search</button>
                    </form>
                </div>
                <?php if (isset($_SESSION['username'])) : ?>
                    <span>Hello, <?php echo $_SESSION['username']; ?></span>
                    <button class="auth-button" onclick="logout()">Logout</button>
                <?php else : ?>
                    <button class="auth-button" onclick="window.location.href='<?php echo BASE_URL; ?>login.php'">Login</button>
                <?php endif; ?>
            </div>
        </div>
        <div class="hamburger">☰</div> <!-- Hamburger Icon -->
        <nav>
            <ul class="nav-links">
                <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
                <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'editor')) : ?>
                    <li><a href="<?php echo BASE_URL; ?>admin/admin_panel.php">Admin</a></li>
                <?php endif; ?>
                <li><a href="<?php echo BASE_URL; ?>contact.php">Contact Us</a></li>
                <li><a href="<?php echo BASE_URL; ?>roster.php">Roster</a></li>
                <li><a href="<?php echo BASE_URL; ?>about.php">About</a></li>
            </ul>
        </nav>
    </header>