<?php
session_start();

if (isset($_SESSION['test_started'])) {
    unset($_SESSION['test_started']);
}

if (isset($_SESSION['test_completed'])) {
    unset($_SESSION['test_completed']);
}

echo "Session variables unset.";
?>
