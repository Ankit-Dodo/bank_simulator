<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../config/db.php");
include("../includes/auth_check.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/deposit.php");
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);

// ---- Read & validate input ----
$amount      = trim($_POST['amount'] ?? '');
$acc         = trim($_POST['account_number'] ?? '');
$acc_confirm = trim($_POST['confirm_account_number'] ?? '');

if ($amount === '' || $acc === '' || $acc_confirm === '') {
    die("All fields are required.");
}

if (!is_numeric($amount) || $amount <= 0) {
    die("Invalid amount.");
}

// DB stores BIGINT — use integer
$amount_int = (int)round($amount);

if ($acc !== $acc_confirm) {
    die("Account numbers do not match.");
}

// keep only digits
$acc_digits = preg_replace('/\D/', '', $acc);
if ($acc_digits === '') {
    die("Invalid account number.");
}

$acc_esc = mysqli_real_escape_string($conn, $acc_digits);

$sql = "
    SELECT a.account_id, a.balance, a.status
    FROM account a
    JOIN profile p ON a.profile_id = p.id
    WHERE p.user_id = $user_id
      AND a.account_number = '$acc_esc'
    LIMIT 1
";

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) === 0) {
    die("Account not found or does not belong to you.");
}

$account = mysqli_fetch_assoc($result);

// Only allow deposit on active accounts
if (strtolower($account['status']) !== 'active') {
    die("Account is not active.");
}

$account_id = (int)$account['account_id'];

// ---- Update balance ----
$updateSql = "
    UPDATE account
    SET balance = balance + $amount_int
    WHERE account_id = $account_id
";

if (mysqli_query($conn, $updateSql)) {
    header("Location: ../pages/home.php");
    exit;
} else {
    echo "Error updating balance: " . mysqli_error($conn);
}
?>
