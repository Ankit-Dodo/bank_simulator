<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../config/db.php");
include("../includes/auth_check.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit;
}

$admin_user_id = (int)$_SESSION['user_id'];

// check admin from database
$roleSql = "SELECT role FROM users WHERE id = $admin_user_id LIMIT 1";
$roleRes = mysqli_query($conn, $roleSql);

if (!$roleRes || mysqli_num_rows($roleRes) === 0) {
    die("User not found.");
}

$roleRow = mysqli_fetch_assoc($roleRes);
$dbRole  = strtolower($roleRow['role'] ?? '');

if ($dbRole !== 'admin') {
    die("Only admin can perform deposits.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/deposit.php");
    exit;
}

$amount      = trim($_POST['amount'] ?? '');
$acc         = trim($_POST['account_number'] ?? '');
$acc_confirm = trim($_POST['confirm_account_number'] ?? '');

if ($amount === '' || $acc === '' || $acc_confirm === '') {
    die("All fields are required.");
}

if (!is_numeric($amount) || $amount <= 0) {
    die("Invalid amount.");
}

$amount_int = (int)round($amount);
if ($amount_int <= 0) {
    die("Invalid amount.");
}

if ($acc !== $acc_confirm) {
    die("Account numbers do not match.");
}

$acc_digits = preg_replace('/\D/', '', $acc);
if ($acc_digits === '') {
    die("Invalid account number.");
}

$acc_esc = mysqli_real_escape_string($conn, $acc_digits);

$sql = "
    SELECT a.id, a.balance, a.status
    FROM account a
    WHERE a.account_number = '$acc_esc'
    LIMIT 1
";

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) === 0) {
    die("Account not found.");
}

$account    = mysqli_fetch_assoc($result);
$account_id = (int)$account['id'];

if (strtolower($account['status']) !== 'active') {
    die("Account is not active.");
}
$updateSql = "
    UPDATE account
    SET balance = balance + $amount_int
    WHERE id = $account_id
";

if (!mysqli_query($conn, $updateSql)) {
    die("Error updating balance: " . mysqli_error($conn));
}
$logSql = "
    INSERT INTO `transaction`
        (account_id, transaction_type, amount, transaction_date, performed_by, status)
    VALUES
        ($account_id, 'deposit', $amount_int, NOW(), $admin_user_id, 'completed')
";

if (!mysqli_query($conn, $logSql)) {
    die("Balance updated, but failed to log transaction: " . mysqli_error($conn));
}

// redirect back to deposit page with amount so popup can show it
header("Location: ../pages/deposit.php?success=deposit&amount=" . urlencode($amount_int));
exit;
?>
