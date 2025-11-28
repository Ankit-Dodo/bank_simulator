<?php
session_start();
require_once "../config/db.php";

// ensure ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../pages/admin_account.php?error=Invalid+Account+ID");
    exit;
}

$accountId = (int)$_GET['id'];

// delete account
$sql = "DELETE FROM account WHERE id = $accountId LIMIT 1";
mysqli_query($conn, $sql);

// redirect
header("Location: ../pages/admin_account.php?success=Account+Deleted");
exit;
