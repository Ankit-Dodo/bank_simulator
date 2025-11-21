<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

if (empty($_GET['id'])) {
    header("Location: ../pages/home.php?error=Missing+Account+ID");
    exit();
}

$account_id = (int)$_GET['id'];

$sql = "DELETE FROM account WHERE account_id = ? AND status = 'Pending'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $account_id);

if (mysqli_stmt_execute($stmt)) {
    header("Location: ../pages/home.php?success=Account+Request+Declined");
    exit();
}

header("Location: ../pages/home.php?error=Decline+Failed");
exit();
?>
