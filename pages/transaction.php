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

/* ---------- SEARCH + PAGINATION SETUP ---------- */

// search by account holder name
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$searchEsc = $search !== "" ? mysqli_real_escape_string($conn, $search) : "";

// pagination settings
$perPage = 25;

$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0
    ? (int)$_GET['page']
    : 1;

// common FROM/JOIN part
$baseFrom = "
    FROM `transaction` t
    JOIN account a ON t.account_id = a.id
    JOIN profile p ON a.profile_id = p.id
    JOIN users u ON p.user_id = u.id       -- account owner
    JOIN users pu ON t.performed_by = pu.id -- performer
";

// base WHERE
if ($isAdmin) {
    $where = "WHERE 1=1";
} else {
    $where = "WHERE u.id = $userId";
}

// add search condition if any
if ($searchEsc !== "") {
    $where .= " AND p.full_name LIKE '%$searchEsc%'";
}

// total rows count (for pagination)
$countSql = "SELECT COUNT(*) AS total " . $baseFrom . " " . $where;
$countRes = mysqli_query($conn, $countSql);

$totalRows = 0;
$queryError = null;

if ($countRes) {
    $countRow = mysqli_fetch_assoc($countRes);
    $totalRows = (int)($countRow['total'] ?? 0);
} else {
    $queryError = mysqli_error($conn);
}

$totalPages = max(1, (int)ceil($totalRows / $perPage));

// clamp page if out of range
if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

// main data query (only if no error and there may be rows)
$result = null;
if ($queryError === null && $totalRows > 0) {
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
        " . $baseFrom . "
        " . $where . "
        ORDER BY t.transaction_date DESC
        LIMIT $perPage OFFSET $offset
    ";

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        $queryError = mysqli_error($conn);
    }
}

?>

<link rel="stylesheet" href="../css/transaction.css">

<h3 class="page-title">
    <?= $isAdmin ? "All Transactions" : "Your Transactions"; ?>
</h3>

<div class="transactions-container">

    <!-- SEARCH BAR -->
    <form method="GET" class="search-bar" style="margin-bottom: 15px;">
        <input
            type="text"
            name="search"
            placeholder="Search by account holder name..."
            value="<?= htmlspecialchars($search) ?>"
        >
        <button type="submit">Search</button>
        <input type="hidden" name="page" value="1">
    </form>

    <?php if ($queryError !== null): ?>

        <div class="no-data">
            SQL Error: <?= htmlspecialchars($queryError); ?>
        </div>

    <?php elseif ($totalRows === 0): ?>

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
                        <td><?= htmlspecialchars($row['performed_by_username']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- PAGINATION -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <!-- Prev -->
                <?php if ($page > 1): ?>
                    <a
                        href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>"
                        class="page-link"
                    >Prev</a>
                <?php endif; ?>

                <!-- Pages -->
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a
                        href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
                        class="page-link <?= ($i === $page) ? 'active-page' : '' ?>"
                    ><?= $i ?></a>
                <?php endfor; ?>

                <!-- Next -->
                <?php if ($page < $totalPages): ?>
                    <a
                        href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>"
                        class="page-link"
                    >Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
