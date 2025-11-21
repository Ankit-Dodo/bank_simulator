<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/register.php");
    exit;
}

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$email    = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($username === '' || $email === '' || $password === '') {
    die("All fields are required");
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

// escape values
$username_esc      = mysqli_real_escape_string($conn, $username);
$email_esc         = mysqli_real_escape_string($conn, $email);
$password_hash_esc = mysqli_real_escape_string($conn, $password_hash);

$sql = "INSERT INTO users (username, email, password_hash)
        VALUES ('$username_esc', '$email_esc', '$password_hash_esc')";

if (mysqli_query($conn, $sql)) {
    header("Location: ../pages/login.php");
    exit;
} else {
    echo "Error: " . mysqli_error($conn);
}
