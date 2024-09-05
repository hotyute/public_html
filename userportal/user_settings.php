<?php
// Start the session and check if the user is authenticated.
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}

// Include necessary files and the header
include 'header.php';
require_once 'base_config.php';

// Handle form submission for updating email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_email'])) {
    $new_email = $_POST['email'];
    // TODO: Validate and update email in the database
}

// Handle form submission for updating password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    // TODO: Validate and update password in the database
}
?>

<div class="settings-container">
    <h1>User Settings</h1>

    <!-- Form to update email -->
    <form method="POST" action="user_settings.php">
        <div class="form-group">
            <label for="email">Update Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <button type="submit" name="update_email">Update Email</button>
    </form>

    <!-- Form to update password -->
    <form method="POST" action="user_settings.php">
        <div class="form-group">
            <label for="password">Update Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" name="update_password">Update Password</button>
    </form>
</div>

<?php
// Include the footer
include 'footer.php';
?>
