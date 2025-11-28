<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../config/db.php";

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/withdraw.php");
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    die("Invalid session.");
}

// checking the role of user
$isAdmin = false;
$roleSql = "SELECT role FROM users WHERE id = $user_id LIMIT 1";
$roleRes = mysqli_query($conn, $roleSql);
if ($roleRes && mysqli_num_rows($roleRes) === 1) {
    $role = mysqli_fetch_assoc($roleRes);
    $isAdmin = (strtolower($role['role']) === 'admin');
}


$from_account_id = (int)($_POST['from_id'] ?? 0);
$amount          = trim($_POST['amount'] ?? '');

if ($from_account_id <= 0) {
    die("Please select an account.");
}

if ($amount === '' || !is_numeric($amount) || $amount <= 0) {
    die("Invalid withdrawal amount.");
}

$amount_int = (int)round($amount);

if ($isAdmin) {
    $sql = "
        SELECT a.id, a.balance, a.min_balance, a.status
        FROM account a
        WHERE a.id = $from_account_id
        LIMIT 1
    ";
} else {
    $sql = "
        SELECT a.id, a.balance, a.min_balance, a.status
        FROM account a
        INNER JOIN profile p ON a.profile_id = p.id
        WHERE a.id = $from_account_id
          AND p.user_id = $user_id
        LIMIT 1
    ";
}

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    if ($isAdmin) {
        die("Account not found.");
    } else {
        die("Account not found or you do not own this account.");
    }
}

$account = mysqli_fetch_assoc($result);

if (strtolower($account['status']) !== 'active') {
    die("Account is not active.");
}

$current_balance = (float)$account['balance'];
$min_balance     = (float)$account['min_balance'];

$new_balance = $current_balance - $amount_int;

if ($new_balance < $min_balance) {
    die("Withdrawal denied. Cannot go below minimum balance of ₹" . number_format($min_balance));
}

$account_id = (int)$account['id'];

$updateSql = "
    UPDATE account
    SET balance = $new_balance
    WHERE id = $account_id
";

if (!mysqli_query($conn, $updateSql)) {
    die("Error updating balance: " . mysqli_error($conn));
}

$logSql = "
    INSERT INTO `transaction`
        (account_id, transaction_type, amount, transaction_date, performed_by, status)
    VALUES
        ($account_id, 'withdraw', $amount_int, NOW(), $user_id, 'completed')
";

if (!mysqli_query($conn, $logSql)) {
    die("Balance updated but failed to log transaction: " . mysqli_error($conn));
}

header("Location: ../pages/withdraw.php?success=withdraw&amount=$amount_int");
exit;
?>
