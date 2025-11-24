<?php
// pages/transaction.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");
include("../includes/header.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

$currentUserId = (int)$_SESSION['user_id'];
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// build query
if ($isAdmin) {
    // admin: see all transactions
    $sql = "
        SELECT 
            t.id,
            t.amount,
            t.transaction_type,
            t.created_at,
            s.full_name AS sender_name,
            s.account_number AS sender_account,
            r.full_name AS receiver_name,
            r.account_number AS receiver_account
        FROM transactions t
        LEFT JOIN users s ON t.sender_id = s.id
        LEFT JOIN users r ON t.receiver_id = r.id
        ORDER BY t.created_at DESC
    ";
} else {
    // normal user: only own
    $sql = "
        SELECT 
            t.id,
            t.amount,
            t.transaction_type,
            t.created_at,
            s.full_name AS sender_name,
            s.account_number AS sender_account,
            r.full_name AS receiver_name,
            r.account_number AS receiver_account
        FROM transactions t
        LEFT JOIN users s ON t.sender_id = s.id
        LEFT JOIN users r ON t.receiver_id = r.id
        WHERE t.sender_id = $currentUserId
           OR t.receiver_id = $currentUserId
        ORDER BY t.created_at DESC
    ";
}

$result = mysqli_query($conn, $sql);
?>

<style>
.page-title {
    text-align: center;
    font-size: 22px;
    font-weight: 600;
    margin: 25px 0 15px;
    color: #003b95;
}

.transactions-container {
    max-width: 1000px;
    margin: 0 auto 40px;
    background: #ffffff;
    padding: 20px 25px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}

.transactions-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.transactions-table th,
.transactions-table td {
    padding: 8px 10px;
    border-bottom: 1px solid #e0e0e0;
    text-align: left;
}

.transactions-table th {
    background: #f4f6fb;
    font-weight: 600;
}

.transactions-table tr:hover {
    background: #f9fbff;
}

.no-data {
    text-align: center;
    padding: 20px 0;
    font-size: 14px;
    opacity: 0.8;
}
</style>

<h3 class="page-title">
    <?php echo $isAdmin ? 'All Transactions' : 'Your Transactions'; ?>
</h3>

<div class="transactions-container">
    <?php if (!$result): ?>
        <div class="no-data">
            Error loading transactions: <?php echo htmlspecialchars(mysqli_error($conn)); ?>
        </div>
    <?php elseif (mysqli_num_rows($result) === 0): ?>
        <div class="no-data">No transactions found.</div>
    <?php else: ?>
        <table class="transactions-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Sender</th>
                    <th>Sender A/C</th>
                    <th>Receiver</th>
                    <th>Receiver A/C</th>
                    <th>Amount</th>
                    <th>Type</th>
                    <th>Date &amp; Time</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo (int)$row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['sender_name'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['sender_account'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['receiver_name'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['receiver_account'] ?? '—'); ?></td>
                        <td><?php echo number_format((float)$row['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['transaction_type'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php

// include("../includes/footer.php");
?>
