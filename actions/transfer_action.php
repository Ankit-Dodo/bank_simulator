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

// Logged-in user
$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    die("Invalid user session.");
}

// Get role from DB
$isAdmin = false;
$roleSql = "SELECT role FROM users WHERE id = $user_id LIMIT 1";
$roleRes = mysqli_query($conn, $roleSql);
if ($roleRes && mysqli_num_rows($roleRes) === 1) {
    $row = mysqli_fetch_assoc($roleRes);
    $isAdmin = (strtolower($row['role']) === 'admin');
}

// ---------- Read & validate input ----------
$from_account_id_raw = $_POST['from_id'] ?? '';
$amount      = trim($_POST['amount'] ?? '');
$acc         = trim($_POST['account_number'] ?? '');
$acc_confirm = trim($_POST['confirm_account_number'] ?? '');

if ($from_account_id_raw === '' || $amount === '' || $acc === '' || $acc_confirm === '') {
    die("All fields are required.");
}

if (!ctype_digit($from_account_id_raw)) {
    die("Invalid sender account selection.");
}
$from_account_id = (int)$from_account_id_raw;

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

// Clean receiver account number
$acc_digits = preg_replace('/\D/', '', $acc);
if ($acc_digits === '' || strlen($acc_digits) < 6 || strlen($acc_digits) > 20) {
    die("Invalid receiver account number.");
}

// ---------- Find SENDER account ----------
if ($isAdmin) {
    // Admin: any active account by ID
    $senderSql = "
        SELECT a.id, a.balance, a.min_balance, a.status
        FROM account a
        WHERE a.id = $from_account_id
        LIMIT 1
    ";
} else {
    // Customer: sender account must belong to them
    $senderSql = "
        SELECT a.id, a.balance, a.min_balance, a.status
        FROM account a
        JOIN profile p ON a.profile_id = p.id
        WHERE p.user_id = $user_id
          AND a.id = $from_account_id
        LIMIT 1
    ";
}

$senderRes = mysqli_query($conn, $senderSql);

if (!$senderRes || mysqli_num_rows($senderRes) === 0) {
    if ($isAdmin) {
        die("Sender account not found.");
    } else {
        die("Sender account not found or does not belong to you.");
    }
}

$sender = mysqli_fetch_assoc($senderRes);

$sender_account_id = (int)$sender['id'];
$sender_balance    = (int)$sender['balance'];
$sender_min        = (int)$sender['min_balance'];

if (strtolower($sender['status']) !== 'active') {
    die("Sender account is not active.");
}

$new_sender_balance = $sender_balance - $amount_int;
if ($new_sender_balance < $sender_min) {
    die("Insufficient balance: cannot go below minimum balance.");
}

// ---------- Find RECEIVER account ----------
$receiverSql = "
    SELECT id, balance, status
    FROM account
    WHERE account_number = ?
    LIMIT 1
";
$receiverStmt = mysqli_prepare($conn, $receiverSql);
if (!$receiverStmt) {
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

$receiver_account_id = (int)$receiver['id'];

if (strtolower($receiver['status']) !== 'active') {
    die("Receiver account is not active.");
}

// Prevent self-transfer
if ($receiver_account_id === $sender_account_id) {
    die("Cannot transfer to the same account.");
}

//  Perform transfer in a transaction 
mysqli_begin_transaction($conn);

try {
    // Deduct from sender
    $updateSenderSql = "
        UPDATE account
        SET balance = balance - $amount_int
        WHERE id = $sender_account_id
    ";
    if (!mysqli_query($conn, $updateSenderSql) || mysqli_affected_rows($conn) !== 1) {
        throw new Exception("Failed to deduct from sender account: " . mysqli_error($conn));
    }

    // Log sender transaction
    $logSenderSql = "
        INSERT INTO `transaction`
            (account_id, transaction_type, amount, transaction_date, performed_by, status)
        VALUES
            ($sender_account_id, 'transfer', $amount_int, NOW(), $user_id, 'completed')
    ";
    if (!mysqli_query($conn, $logSenderSql)) {
        throw new Exception("Failed to log sender transaction: " . mysqli_error($conn));
    }

    // Credit receiver
    $updateReceiverSql = "
        UPDATE account
        SET balance = balance + $amount_int
        WHERE id = $receiver_account_id
    ";
    if (!mysqli_query($conn, $updateReceiverSql) || mysqli_affected_rows($conn) !== 1) {
        throw new Exception("Failed to credit receiver account: " . mysqli_error($conn));
    }

    // Log receiver transaction
    $logReceiverSql = "
        INSERT INTO `transaction`
            (account_id, transaction_type, amount, transaction_date, performed_by, status)
        VALUES
            ($receiver_account_id, 'transfer', $amount_int, NOW(), $user_id, 'completed')
    ";
    if (!mysqli_query($conn, $logReceiverSql)) {
        throw new Exception("Failed to log receiver transaction: " . mysqli_error($conn));
    }

    mysqli_commit($conn);

    header("Location: ../pages/transfer.php?success=transfer&amount=" . urlencode($amount_int));
    exit;


} catch (Exception $e) {
    mysqli_rollback($conn);
    die("Transfer failed: " . $e->getMessage());
}
?>
