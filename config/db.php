<?php

$host = "localhost";
$user = "phpmyadmin";
$pass = "Passw0rd!123";
$db   = "bank_simulator";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
