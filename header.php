
<?php
require_once 'base_config.php';
session_start(); // Start the session to use session variables
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>style.css">
    <script src="<?php echo BASE_URL; ?>js/script.js"></script>
    <title>MyGospel Christian Website</title>
</head>
<body>
<header style="background-image: url('<?php echo BASE_URL; ?>images/banner.jpg'); background-repeat: no-repeat; background-size: cover;">
    <div class="hamburger">☰</div> <!-- Hamburger Icon -->
    <h1>Welcome to Our Christian Community</h1>
    <nav>
        <ul>
            <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <li><a href="<?php echo BASE_URL; ?>admin/posts.php">Admin</a></li>
            <?php endif; ?>
            <li><a href="<?php echo BASE_URL; ?>contact.php">Contact Us</a></li>
            <li><a href="<?php echo BASE_URL; ?>login.php">Login</a></li>
        </ul>
    </nav>
</header>
