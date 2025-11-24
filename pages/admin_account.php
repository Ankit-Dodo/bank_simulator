<?php

// Total accounts
$totalAccountsSql = "SELECT COUNT(*) AS total_accounts FROM account";
$totalAccountsRes = mysqli_query($conn, $totalAccountsSql);
$totalAccounts = (int)mysqli_fetch_assoc($totalAccountsRes)['total_accounts'];

// Active accounts
$totalActiveSql = "SELECT COUNT(*) AS total_active FROM account WHERE status='Active'";
$totalActiveRes = mysqli_query($conn, $totalActiveSql);
$totalActive = (int)mysqli_fetch_assoc($totalActiveRes)['total_active'];

// Pending accounts
$totalPendingSql = "SELECT COUNT(*) AS total_pending FROM account WHERE status='Pending'";
$totalPendingRes = mysqli_query($conn, $totalPendingSql);
$totalPending = (int)mysqli_fetch_assoc($totalPendingRes)['total_pending'];

// Total money in all accounts
$totalMoneySql = "SELECT SUM(balance) AS total_money FROM account";
$totalMoneyRes = mysqli_query($conn, $totalMoneySql);
$totalMoneyRow = mysqli_fetch_assoc($totalMoneyRes);
$totalMoney = $totalMoneyRow['total_money'] ?? 0;

// Total users
$totalUsersSql = "SELECT COUNT(*) AS total_users FROM users";
$totalUsersRes = mysqli_query($conn, $totalUsersSql);
$totalUsers = (int)mysqli_fetch_assoc($totalUsersRes)['total_users'];

// Total transactions
$totalTxSql = "SELECT COUNT(*) AS total_tx FROM `transaction`";
$totalTxRes = mysqli_query($conn, $totalTxSql);
$totalTransactions = (int)mysqli_fetch_assoc($totalTxRes)['total_tx'];


//  PENDING ACCOUNTS 
$pendingSql = "
    SELECT a.account_id, a.account_number, a.account_type, a.balance, a.status,
           a.min_balance, a.ifsc_code, a.account_date,
           p.full_name, p.phone,
           u.username, u.email
    FROM account a
    JOIN profile p ON a.profile_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE a.status = 'Pending'
";
$pendingRes = mysqli_query($conn, $pendingSql);


//  ALL ACCOUNTS 
$accountsSql = "
    SELECT a.account_id, a.account_number, a.account_type, a.balance, a.status,
           a.ifsc_code, a.account_date,
           p.full_name, p.phone,
           u.username, u.email
    FROM account a
    JOIN profile p ON a.profile_id = p.id
    JOIN users u ON p.user_id = u.id
    ORDER BY a.account_id DESC
";
$accountsRes = mysqli_query($conn, $accountsSql);

?>

<link rel="stylesheet" href="../css/admin.css">

<!-- STATS SECTION -->
<div class="stats-wrapper">

    <div class="stat-box">
        <h4>Total Accounts</h4>
        <p><?= $totalAccounts ?></p>
    </div>

    <div class="stat-box">
        <h4>Active Accounts</h4>
        <p><?= $totalActive ?></p>
    </div>

    <div class="stat-box">
        <h4>Pending Accounts</h4>
        <p><?= $totalPending ?></p>
    </div>

    <div class="stat-box">
        <h4>Total Money in Bank</h4>
        <p>Rs.<?= number_format($totalMoney, 2) ?></p>
    </div>

    <div class="stat-box">
        <h4>Total Users</h4>
        <p><?= $totalUsers ?></p>
    </div>

    <div class="stat-box">
        <h4>Total Transactions</h4>
        <p><?= $totalTransactions ?></p>
    </div>

</div>


<h4 class="section-title">Account Management</h4>

<!-- TAB BUTTONS -->
<div class="tab-card">
    <div class="tab-buttons">
        <button type="button" onclick="showAdminTab('pending')" id="btn-tab-pending" class="active-tab">
            Pending Requests
        </button>

        <button type="button" onclick="showAdminTab('all')" id="btn-tab-all">
            All Accounts
        </button>
    </div>
</div>


<!-- PENDING ACCOUNTS TAB -->
<div id="admin-tab-pending" class="section-card">

<?php if ($pendingRes && mysqli_num_rows($pendingRes) > 0): ?>

<table class="ui-table">
    <thead>
        <tr>
            <th>Customer</th>
            <th>Phone</th>
            <th>Username</th>
            <th>Email</th>
            <th>Account No</th>
            <th>Type</th>
            <th>IFSC</th>
            <th>Date</th>
            <th>Balance</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>
        <?php while($row = mysqli_fetch_assoc($pendingRes)): ?>
        <tr>
            <td><?= $row['full_name'] ?></td>
            <td><?= $row['phone'] ?></td>
            <td><?= $row['username'] ?></td>
            <td><?= $row['email'] ?></td>
            <td><?= $row['account_number'] ?></td>
            <td><?= $row['account_type'] ?></td>
            <td><?= $row['ifsc_code'] ?></td>
            <td><?= $row['account_date'] ?></td>
            <td><?= number_format($row['balance'], 2) ?></td>
            <td><span class="status-pending">Pending</span></td>
            <td>
                <a class="btn-approve" href="../actions/approve_account.php?id=<?= $row['account_id'] ?>">Approve</a>
                <a class="btn-decline" href="../actions/decline_account.php?id=<?= $row['account_id'] ?>">Decline</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php else: ?>
    <p>No pending accounts.</p>
<?php endif; ?>

</div>


<!-- ALL ACCOUNTS TAB -->
<div id="admin-tab-all" class="section-card" style="display:none;">

<?php if ($accountsRes && mysqli_num_rows($accountsRes) > 0): ?>

<table class="ui-table">
    <thead>
        <tr>
            <th>Customer</th>
            <th>Phone</th>
            <th>Username</th>
            <th>Email</th>
            <th>Account No</th>
            <th>Type</th>
            <th>IFSC</th>
            <th>Date</th>
            <th>Balance</th>
            <th>Status</th>
        </tr>
    </thead>

    <tbody>
        <?php while($row = mysqli_fetch_assoc($accountsRes)): ?>
        <tr>
            <td><?= $row['full_name'] ?></td>
            <td><?= $row['phone'] ?></td>
            <td><?= $row['username'] ?></td>
            <td><?= $row['email'] ?></td>
            <td><?= $row['account_number'] ?></td>
            <td><?= $row['account_type'] ?></td>
            <td><?= $row['ifsc_code'] ?></td>
            <td><?= $row['account_date'] ?></td>
            <td><?= number_format($row['balance'], 2) ?></td>
            <td><span class="status <?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php else: ?>
    <p>No accounts found.</p>
<?php endif; ?>

</div>

<script>
function showAdminTab(tab) {
    document.getElementById('admin-tab-pending').style.display = (tab === 'pending') ? 'block' : 'none';
    document.getElementById('admin-tab-all').style.display = (tab === 'all') ? 'block' : 'none';

    document.getElementById('btn-tab-pending').classList.toggle('active-tab', tab === 'pending');
    document.getElementById('btn-tab-all').classList.toggle('active-tab', tab === 'all');
}
</script>
