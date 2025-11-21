<?php
$uid = (int)($_SESSION['user_id'] ?? 0); 
$accountsRes = false;

if ($uid <= 0) {
    echo "<p class='error-text'>Invalid session. Please log in again.</p>";
} else {

    $accountsSql = "
        SELECT 
            a.account_number,
            a.account_type,
            a.balance,
            a.min_balance,
            a.status,
            a.ifsc_code,
            a.account_date
        FROM account a
        JOIN profile p ON a.profile_id = p.id
        WHERE p.user_id = ?
    ";

    if ($stmt = mysqli_prepare($conn, $accountsSql)) {
        mysqli_stmt_bind_param($stmt, "i", $uid);
        mysqli_stmt_execute($stmt);
        $accountsRes = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>

<h4>Your Accounts</h4>

<div class="section-card">
<?php if ($accountsRes && mysqli_num_rows($accountsRes) > 0): ?>
    <table class="ui-table">
        <thead>
            <tr>
                <th>Account No</th>
                <th>Type</th>
                <th>Balance</th>
                <th>Min Balance</th>
                <th>IFSC</th>
                <th>Created</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
        <?php while ($row = mysqli_fetch_assoc($accountsRes)): ?>

            <?php
                // STATUS BADGE
                $status = strtolower($row['status']);
                $badgeClass = ($status === 'active')
                    ? 'status-badge status-active'
                    : (($status === 'pending')
                        ? 'status-badge status-pending'
                        : 'status-badge status-other');
            ?>

            <tr>
                <td><?= htmlspecialchars($row['account_number']) ?></td>
                <td><?= htmlspecialchars($row['account_type']) ?></td>

                <!-- SAFE balance formatting -->
                <td><?= $row['balance'] !== null ? number_format((float)$row['balance'], 2) : '0.00' ?></td>

                <!-- SAFE min balance formatting -->
                <td><?= $row['min_balance'] !== null ? number_format((float)$row['min_balance'], 2) : '0.00' ?></td>

                <td><?= htmlspecialchars($row['ifsc_code']) ?></td>
                <td><?= htmlspecialchars($row['account_date']) ?></td>

                <td>
                    <span class="<?= $badgeClass ?>">
                        <?= htmlspecialchars($row['status']) ?>
                    </span>
                </td>
            </tr>

        <?php endwhile; ?>
        </tbody>
    </table>

<?php else: ?>
    <p class="empty-text">
        You have no accounts yet.
        <a href="customer_details.php" class="inline-link">Create your first account</a>
    </p>
<?php endif; ?>
</div>
