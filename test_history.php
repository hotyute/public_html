<?php
// Start the session and check if the user is authenticated and is an admin.
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'editor', 'member'])) {
    header('Location: /login.php'); // Redirect to login if not authenticated as admin.
    exit();
}

// Include header file
include 'header.php';
?>

<!-- <div class="admin-container">
    <h1>Portal Dashboard</h1>
    <p>Welcome to the portal dashboard. Use the links below to access your data:</p>
    <ul class="admin-links">
        <li><a href="test_history.php">Test History</a></li> -->
        <!-- Additional links for other admin tasks can be added here -->
    <!-- </ul>
</div> -->

<?php
// Include footer file
include 'footer.php';
?>