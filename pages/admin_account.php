<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/db.php"; // uses $conn from db.php

//  LOGIN CHECK 
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// ADMIN CHECK 
$roleSql = "SELECT role FROM users WHERE id = $user_id LIMIT 1";
$roleRes = mysqli_query($conn, $roleSql);

if (!$roleRes || mysqli_num_rows($roleRes) === 0) {
    die("User not found.");
}

$roleRow = mysqli_fetch_assoc($roleRes);
if (strtolower($roleRow['role'] ?? '') !== 'admin') {
    die("Only admin can access this page.");
}

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


//  PENDING ACCOUNTS 
$pendingSql = "
    SELECT a.id, a.account_number, a.account_type, a.balance, a.status,
           a.min_balance, a.ifsc_code, a.account_date,
           p.full_name, p.phone,
           u.username, u.email
    FROM account a
    JOIN profile p ON a.profile_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE a.status = 'Pending'
";
$pendingRes = mysqli_query($conn, $pendingSql);

// search
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$searchCondition = ""; // empty by default

if ($search !== "") {
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $searchCondition = " AND (
        p.full_name LIKE '%$searchEsc%' OR
        u.username LIKE '%$searchEsc%' OR
        u.email LIKE '%$searchEsc%' OR
        p.phone LIKE '%$searchEsc%' OR
        p.address LIKE '%$searchEsc%' OR
        a.account_number LIKE '%$searchEsc%'
    )";
}


// per-page
$perPage = 10;

// total rows (for pagination) with search filter
$countSql = "
    SELECT COUNT(*) AS total
    FROM account a
    JOIN profile p ON a.profile_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE 1
    $searchCondition
";

$countRes = mysqli_query($conn, $countSql);
$totalAccountsList = (int)mysqli_fetch_assoc($countRes)['total'];

$totalPages = max(1, (int)ceil($totalAccountsList / $perPage));

// current page
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0
    ? (int)$_GET['page']
    : 1;

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

// ALL ACCOUNTS query
$accountsSql = "
    SELECT a.id, a.account_number, a.account_type, a.balance, a.status,
           a.ifsc_code, a.account_date,
           p.full_name, p.phone,
           u.username, u.email
    FROM account a
    JOIN profile p ON a.profile_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE 1
    $searchCondition
    ORDER BY a.id DESC
    LIMIT $perPage OFFSET $offset
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

</div>

<!-- manage users button -->
<div style="display:flex; justify-content:space-between; align-items:center; margin: 10px 0 5px 0;">
    <h4 class="section-title" style="margin:0;">Manage Accounts / Users</h4>
    <a href="../pages/edit_user.php" class="btn-secondary">Edit User Details</a>
</div>

<!-- TAB BUTTONS -->
<div class="tab-card">
    <div class="tab-buttons">
        <button type="button" onclick="showAdminTab('pending')" id="btn-tab-pending">
            Pending Requests
        </button>

        <button type="button" onclick="showAdminTab('all')" id="btn-tab-all" class="active-tab">
            All Accounts
        </button>
    </div>
</div>


<!-- PENDING ACCOUNTS TAB -->
<div id="admin-tab-pending" class="section-card" style="display:none;">

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
                <a class="btn-approve" href="../actions/approve_account.php?id=<?= $row['id'] ?>">Approve</a>
                <a class="btn-decline" href="../actions/decline_account.php?id=<?= $row['id'] ?>">Decline</a>
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
<div id="admin-tab-all" class="section-card">

    <!-- SEARCH BAR -->
    <form method="GET" class="search-bar">
        <input
            type="text"
            name="search"
            placeholder="Search by name..."
            value="<?= htmlspecialchars($search) ?>"
        >
        <button type="submit">Search</button>
        <input type="hidden" name="page" value="1">
    </form>

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
            <th>Action</th>
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
            <td>
                <a href="../actions/delete_account.php?id=<?= $row['id'] ?>"
                   class="btn-delete"
                   onclick="return confirm('Are you sure you want to delete this account? This action cannot be undone.');">
                    Delete
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- PAGINATION -->
<?php if ($totalPages > 1): ?>
    <div class="pagination">
        <!-- Previous button -->
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="page-link">Prev</a>
        <?php endif; ?>

        <!-- Page numbers -->
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
               class="page-link <?= ($i === $page) ? 'active-page' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <!-- Next button -->
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="page-link">Next</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- GO BACK BUTTON WHEN SEARCH IS ACTIVE -->
<?php if ($search !== ""): ?>
    <div class="back-box">
        <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="back-btn">Go Back</a>
    </div>
<?php endif; ?>

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
