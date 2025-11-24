<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';
require_once '../includes/header.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
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

// Admin → all transactions
// User  → only their accounts' transactions
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
            u.username
        FROM `transaction` t
        JOIN account a ON t.account_id = a.account_id
        JOIN profile p ON a.profile_id = p.id
        JOIN users u ON p.user_id = u.id
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
            u.username
        FROM `transaction` t
        JOIN account a ON t.account_id = a.account_id
        JOIN profile p ON a.profile_id = p.id
        JOIN users u ON p.user_id = u.id
        WHERE u.id = $userId
        ORDER BY t.transaction_date DESC
    ";
}

$result = mysqli_query($conn, $sql);
?>

<h3 class="page-title">
    <?php echo $isAdmin ? "All Transactions" : "Your Transactions"; ?>
</h3>

<div class="transactions-container">
    <?php if (!$result): ?>
        <div class="no-data">
            SQL Error: <?php echo htmlspecialchars(mysqli_error($conn)); ?>
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
                    <th>Performed By (User ID)</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo (int)$row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['account_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td>₹<?php echo number_format((float)$row['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($row['transaction_type'])); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td><?php echo htmlspecialchars($row['transaction_date']); ?></td>
                        <td><?php echo (int)$row['performed_by']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

<style>
.page-title {
    text-align: center;
    font-size: 22px;
    font-weight: bold;
    margin-top: 25px;
    margin-bottom: 15px;
    color: #003b95;
}

.transactions-container {
    max-width: 1000px;
    margin: 20px auto 40px;
    background: #ffffff;
    padding: 20px 25px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
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
