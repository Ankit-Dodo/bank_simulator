<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../config/db.php");
include("../includes/auth_check.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/create_account.php");
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    die("Invalid user");
}

$profileSql = "SELECT id FROM profile WHERE user_id = $user_id LIMIT 1";
$profileRes = mysqli_query($conn, $profileSql);

if (!$profileRes || mysqli_num_rows($profileRes) !== 1) {
    die("Please fill customer details first.");
}

$profileRow = mysqli_fetch_assoc($profileRes);
$profile_id = (int)$profileRow['id'];

$account_type = trim($_POST['account_type'] ?? '');
$min_balance  = trim($_POST['min_balance'] ?? '');

$valid_types = ['savings', 'current', 'salary'];
if (!in_array($account_type, $valid_types, true)) {
    die("Invalid account type");
}

if ($min_balance === '') {
    $min_balance_val = 'NULL';
    $balance         = 0;
} else {
    $min_balance_int = (int)$min_balance;
    if ($min_balance_int < 0) {
        die("Minimum balance cannot be negative");
    }

    $min_balance_val = $min_balance_int;
    $balance         = $min_balance_int; 
}

function generateAccountNumber($conn) {
    while (true) {
        $num = random_int(1000000000, 9999999999);
        $checkSql = "SELECT account_id FROM account WHERE account_number = $num LIMIT 1";
        $checkRes = mysqli_query($conn, $checkSql);

        if ($checkRes && mysqli_num_rows($checkRes) === 0) {
            return $num;
        }
    }
}

$account_number = generateAccountNumber($conn);

$ifsc_code = "INDB0000323";

$dateSql = "SELECT created_at FROM users WHERE id = $user_id LIMIT 1";
$dateRes = mysqli_query($conn, $dateSql);

if (!$dateRes || mysqli_num_rows($dateRes) !== 1) {
    die("Failed to fetch user creation date.");
}

$dateRow = mysqli_fetch_assoc($dateRes);
$account_date = mysqli_real_escape_string($conn, $dateRow['created_at']);

$account_type_esc = mysqli_real_escape_string($conn, $account_type);
$ifsc_esc         = mysqli_real_escape_string($conn, $ifsc_code);
$status           = "Pending";
$status_esc       = mysqli_real_escape_string($conn, $status);

$sql = "
    INSERT INTO account 
        (profile_id, account_type, account_number, balance, min_balance, status, ifsc_code, account_date)
    VALUES 
        ($profile_id, '$account_type_esc', $account_number, $balance, $min_balance_val, '$status_esc', '$ifsc_esc', '$account_date')
";

if (mysqli_query($conn, $sql)) {
    header("Location: ../pages/home.php");
    exit;
} else {
    echo "Error creating account: " . mysqli_error($conn);
}
?>
