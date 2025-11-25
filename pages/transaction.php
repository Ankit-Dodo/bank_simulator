<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';
require_once '../includes/header.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];

// check role
$isAdmin = false;
$roleSql = "SELECT role FROM users WHERE id = $userId LIMIT 1";
$roleRes = mysqli_query($conn, $roleSql);
if ($roleRes && mysqli_num_rows($roleRes) === 1) {
    $row = mysqli_fetch_assoc($roleRes);
    $isAdmin = (strtolower($row['role']) === 'admin');
}

//    ADMIN: See all transactions
//    USER : See only their own account transactions


if ($isAdmin) {
    $sql = "
        SELECT 
            t.id,
            t.account_id,
            t.transaction_type,
            t.amount,
            t.transaction_date,
            t.status,
            t.performed_by,
            a.account_number,
            p.full_name,
            u.username,
            pu.username AS performed_by_username
        FROM `transaction` t
        JOIN account a ON t.account_id = a.id
        JOIN profile p ON a.profile_id = p.id
        JOIN users u ON p.user_id = u.id             -- account owner
        JOIN users pu ON t.performed_by = pu.id      -- performer
        ORDER BY t.transaction_date DESC
    ";
} else {
    $sql = "
        SELECT 
            t.id,
            t.account_id,
            t.transaction_type,
            t.amount,
            t.transaction_date,
            t.status,
            t.performed_by,
            a.account_number,
            p.full_name,
            u.username,
            pu.username AS performed_by_username
        FROM `transaction` t
        JOIN account a ON t.account_id = a.id
        JOIN profile p ON a.profile_id = p.id
        JOIN users u ON p.user_id = u.id             -- account owner
        JOIN users pu ON t.performed_by = pu.id      -- performer
        WHERE u.id = $userId
        ORDER BY t.transaction_date DESC
    ";
}

$result = mysqli_query($conn, $sql);
?>

<link rel="stylesheet" href="../css/transaction.css">

<h3 class="page-title">
    <?= $isAdmin ? "All Transactions" : "Your Transactions"; ?>
</h3>

<div class="transactions-container">
    <?php if (!$result): ?>
        <div class="no-data">
            SQL Error: <?= htmlspecialchars(mysqli_error($conn)); ?>
        </div>

    <?php elseif (mysqli_num_rows($result) === 0): ?>
        <div class="no-data">No transactions found.</div>

    <?php else: ?>
        <table class="transactions-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Account No.</th>
                    <th>Account Holder</th>
                    <th>Username</th>
                    <th>Amount</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Date &amp; Time</th>
                    <th>Performed By</th>
                </tr>
            </thead>

            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= (int)$row['id']; ?></td>
                        <td><?= htmlspecialchars($row['account_number']); ?></td>
                        <td><?= htmlspecialchars($row['full_name']); ?></td>
                        <td><?= htmlspecialchars($row['username']); ?></td>
                        <td>Rs.<?= number_format((float)$row['amount'], 2); ?></td>
                        <td><?= htmlspecialchars(ucfirst($row['transaction_type'])); ?></td>
                        <td><?= htmlspecialchars($row['status']); ?></td>
                        <td><?= htmlspecialchars($row['transaction_date']); ?></td>

                        <td>
                            <?= htmlspecialchars($row['performed_by_username']); ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
