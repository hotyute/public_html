<?php
require_once 'base_config.php';
session_start();
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
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>style.css">
    <script src="<?php echo BASE_URL; ?>js/script.js"></script>
    <title>MyGospel Christian Website</title>
    <script>
        // Function to handle redirection
        function redirectTo(url) {
            window.location.href = url;
        }
    </script>
</head>
<body>
<header style="background-image: url('<?php echo BASE_URL; ?>images/banner.jpg'); background-repeat: no-repeat; background-size: cover;">
    <div class="hamburger">☰</div> <!-- Hamburger Icon -->
    <h1>Welcome to Our Christian Community</h1>
    <nav>
        <ul class="nav-links">
            <li><button onclick="redirectTo('<?php echo BASE_URL; ?>index.php')">Home</button></li>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <li><button onclick="redirectTo('<?php echo BASE_URL; ?>admin/posts.php')">Admin</button></li>
            <?php endif; ?>
            <li><button onclick="redirectTo('<?php echo BASE_URL; ?>contact.php')">Contact Us</button></li>
        </ul>
        <ul class="auth">
            <?php if (isset($_SESSION['username'])): ?>
                <li><span>Hello, <?php echo $_SESSION['username']; ?></span></li>
                <li><button onclick="redirectTo('?logout=true')">Logout</button></li> <!-- Logout Button -->
            <?php else: ?>
                <li><button onclick="redirectTo('<?php echo BASE_URL; ?>login.php')">Login</button></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

