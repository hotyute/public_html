<?php
session_start();

header('Content-Type: application/json');

if (isset($_SESSION['user_role'])) {
    echo json_encode(['role' => $_SESSION['user_role']]);
} else {
    echo json_encode(['role' => 'guest']); // Default role if not set
}
?>