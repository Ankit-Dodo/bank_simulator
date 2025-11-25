<?php
session_start();
require_once '../config/db.php'; 

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../pages/home.php?error=Missing+Account+ID");
    exit();
}

$account_id = (int)$_GET['id'];

// SQL to update the account status to 'Active'
$sql = "UPDATE account SET status = 'Active' WHERE id = ? AND status = 'Pending'";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $account_id);

if (mysqli_stmt_execute($stmt)) {

    header("Location: ../pages/home.php?success=Account+Approved+Successfully");
} else {
    // Handle error
    header("Location: ../pages/home.php?error=Approval+Failed");
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
exit();
?>