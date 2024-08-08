<?php
// Start the session and check if the user is authenticated and is an admin.
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'editor', 'member'])) {
    header('Location: /login.php'); // Redirect to login if not authenticated as admin.
    exit();
}

// Include header file
include '../header.php';
?>