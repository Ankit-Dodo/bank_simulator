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

// ---------- SERVER-SIDE VALIDATION ----------

// 1. Username length
if (strlen($username) < 3) {
    die("Username must be at least 3 characters long.");
}

// 2. Email format validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email format.");
}

// 3. Password strength check (at least 8 chars, one uppercase, one lowercase, one number)
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
    die("Password must be at least 8 characters long and include a capital letter, a small letter, and a number.");
}

// 4. Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    die("This email is already registered.");
}
$stmt->close();

// ---------- CONTINUE WITH REGISTRATION ----------

$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Escape values for safe insertion
$username_esc      = mysqli_real_escape_string($conn, $username);
$email_esc         = mysqli_real_escape_string($conn, $email);
$password_hash_esc = mysqli_real_escape_string($conn, $password_hash);

$sql = "INSERT INTO users (username, email, password_hash)
        VALUES ('$username_esc', '$email_esc', '$password_hash_esc')";

if (mysqli_query($conn, $sql)) {
    // Redirect back to register page to show SweetAlert
    header("Location: ../pages/register.php?success=1");
    exit;
} else {
    echo "Error: " . mysqli_error($conn);
}
?>
