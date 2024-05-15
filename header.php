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

// Get the current script name
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/style.css">
    
    <?php if ($current_page == 'index'): ?>
        <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/surplus.css">
        <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/featuredvid.css">
    <?php endif; ?>
    
    <?php if ($current_page == 'admin_panel'): ?>
        <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/admin.css">
    <?php endif; ?>
    
    <?php if ($current_page == 'post'): ?>
        <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/post.css">
        <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/comments.css">
    <?php endif; ?>
    
    <?php if ($current_page == 'contact'): ?>
        <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/surplus.css">
    <?php endif; ?>

    <script src="<?php echo BASE_URL; ?>js/script.js"></script>
    <title>MyGospel Christian Website</title>
    <script>
        // Function to handle logout redirection
        function logout() {
            window.location.href = '?logout=true';
        }
    </script>
</head>

<body>
    <header style="background-image: url('<?php echo BASE_URL; ?>images/banner.jpg'); background-repeat: no-repeat; background-size: cover;">
        <div class="hamburger">☰</div> <!-- Hamburger Icon -->
        <h1>Welcome to Our Christian Community</h1>
        <nav>
            <ul class="nav-links">
                <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') : ?>
                    <li><a href="<?php echo BASE_URL; ?>admin/admin_panel.php">Admin</a></li>
                <?php endif; ?>
                <li><a href="<?php echo BASE_URL; ?>contact.php">Contact Us</a></li>
            </ul>
            <ul class="auth">
                <?php if (isset($_SESSION['username'])) : ?>
                    <li><span>Hello, <?php echo $_SESSION['username']; ?></span></li>
                    <li><button class="auth-button" onclick="logout()">Logout</button></li> <!-- Styled Logout Button -->
                <?php else : ?>
                    <li><button class="auth-button" onclick="window.location.href='<?php echo BASE_URL; ?>login.php'">Login</button></li> <!-- Styled Login Button -->
                <?php endif; ?>
            </ul>
        </nav>
    </header>
