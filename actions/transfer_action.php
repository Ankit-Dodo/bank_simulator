<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../config/db.php");
include("../includes/auth_check.php");

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/transfer.php");
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    die("Invalid user session.");
}

// ---------- Read & validate input ----------
$amount      = trim($_POST['amount'] ?? '');
$acc         = trim($_POST['account_number'] ?? '');
$acc_confirm = trim($_POST['confirm_account_number'] ?? '');

if ($amount === '' || $acc === '' || $acc_confirm === '') {
    die("All fields are required.");
}

if ($acc !== $acc_confirm) {
    die("Account numbers do not match.");
}

if (!is_numeric($amount) || $amount <= 0) {
    die("Invalid transfer amount.");
}

$amount_int = (int)round($amount);
if ($amount_int <= 0) {
    die("Invalid transfer amount.");
}

// Account number: digits only
$acc_digits = preg_replace('/\D/', '', $acc);
if ($acc_digits === '' || strlen($acc_digits) < 6 || strlen($acc_digits) > 20) {
    die("Invalid receiver account number.");
}

// ---------- Find sender account (current user) ----------
$senderSql = "
    SELECT a.account_id, a.balance, a.min_balance, a.status
    FROM account a
    JOIN profile p ON a.profile_id = p.id
    WHERE p.user_id = ?
      AND LOWER(a.status) = 'active'
    ORDER BY a.account_id ASC
    LIMIT 1
";

if (!$senderStmt = mysqli_prepare($conn, $senderSql)) {
    die("Error preparing sender query: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($senderStmt, "i", $user_id);
mysqli_stmt_execute($senderStmt);
$senderRes = mysqli_stmt_get_result($senderStmt);
mysqli_stmt_close($senderStmt);

if (!$senderRes || mysqli_num_rows($senderRes) === 0) {
    die("No active account found for the current user.");
}

$sender = mysqli_fetch_assoc($senderRes);
$sender_account_id = (int)$sender['account_id'];
$sender_balance    = (int)$sender['balance'];
$sender_min        = (int)$sender['min_balance'];

// Check minimum balance rule
$new_sender_balance = $sender_balance - $amount_int;
if ($new_sender_balance < $sender_min) {
    die("Insufficient balance: cannot go below minimum balance.");
}

// ---------- Find receiver account (by account number) ----------
$receiverSql = "
    SELECT account_id, balance, status
    FROM account
    WHERE account_number = ?
    LIMIT 1
";
if (!$receiverStmt = mysqli_prepare($conn, $receiverSql)) {
    die("Error preparing receiver query: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($receiverStmt, "s", $acc_digits);
mysqli_stmt_execute($receiverStmt);
$receiverRes = mysqli_stmt_get_result($receiverStmt);
mysqli_stmt_close($receiverStmt);

if (!$receiverRes || mysqli_num_rows($receiverRes) === 0) {
    die("Receiver account not found.");
}

$receiver = mysqli_fetch_assoc($receiverRes);
$receiver_account_id = (int)$receiver['account_id'];

if (strtolower($receiver['status']) !== 'active') {
    die("Receiver account is not active.");
}

// Prevent sending to same account
if ($receiver_account_id === $sender_account_id) {
    die("Cannot transfer to the same account.");
}

// ---------- Perform transfer in a transaction ----------
mysqli_begin_transaction($conn);

try {
    // Deduct from sender
    $updateSenderSql = "
        UPDATE account
        SET balance = balance - ?
        WHERE account_id = ?
    ";
    if (!$updateSenderStmt = mysqli_prepare($conn, $updateSenderSql)) {
        throw new Exception("Failed to prepare sender update: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($updateSenderStmt, "ii", $amount_int, $sender_account_id);
    mysqli_stmt_execute($updateSenderStmt);

    if (mysqli_stmt_affected_rows($updateSenderStmt) !== 1) {
        $err = mysqli_stmt_error($updateSenderStmt);
        mysqli_stmt_close($updateSenderStmt);
        throw new Exception("Failed to deduct from sender account. " . $err);
    }
    mysqli_stmt_close($updateSenderStmt);

    // Credit receiver
    $updateReceiverSql = "
        UPDATE account
        SET balance = balance + ?
        WHERE account_id = ?
    ";
    if (!$updateReceiverStmt = mysqli_prepare($conn, $updateReceiverSql)) {
        throw new Exception("Failed to prepare receiver update: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($updateReceiverStmt, "ii", $amount_int, $receiver_account_id);
    mysqli_stmt_execute($updateReceiverStmt);

    if (mysqli_stmt_affected_rows($updateReceiverStmt) !== 1) {
        $err = mysqli_stmt_error($updateReceiverStmt);
        mysqli_stmt_close($updateReceiverStmt);
        throw new Exception("Failed to credit receiver account. " . $err);
    }
    mysqli_stmt_close($updateReceiverStmt);

    mysqli_commit($conn);
    header("Location: ../pages/home.php?success=Transfer+completed");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    die("Transfer failed: " . $e->getMessage());
}
