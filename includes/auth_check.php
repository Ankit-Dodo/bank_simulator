<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    header("Location: ../pages/login.php?error=Please+login+first");
    exit();
}
?>

