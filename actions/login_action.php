<?php
session_start();
require_once "../config/db.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/login.php?error=Invalid+Request");
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    header("Location: ../pages/login.php?error=All+fields+are+required");
    exit;
}

$emailEsc = mysqli_real_escape_string($conn, $email);

// Fetch user
$sql = "SELECT * FROM users WHERE email = '$emailEsc' LIMIT 1";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) !== 1) {
    header("Location: ../pages/login.php?error=Invalid+email+or+password");
    exit;
}

$user = mysqli_fetch_assoc($result);

// Verify hashed password
if (!password_verify($password, $user['password_hash'])) {
    header("Location: ../pages/login.php?error=Invalid+email+or+password");
    exit;
}

// Set session
$_SESSION['user_id']   = $user['id'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['email']     = $user['email'];
$_SESSION['username']  = $user['username']; 
// Redirect
header("Location: ../pages/home.php");
exit;
?>
