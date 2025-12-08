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

/*  SEARCH + SORT + FILTER + PAGINATION SETUP  */

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$searchEsc = $search !== "" ? mysqli_real_escape_string($conn, $search) : "";

// sorting (name A–Z / Z–A)
$sort = $_GET['sort'] ?? '';
$allowedSort = ['name_asc', 'name_desc'];
if (!in_array($sort, $allowedSort, true)) {
    $sort = '';
}

// filtering (by date or by name)
$filterType = $_GET['filter_type'] ?? '';
$allowedFilterType = ['date', 'name'];
if (!in_array($filterType, $allowedFilterType, true)) {
    $filterType = '';
}

$filterName = isset($_GET['filter_name']) ? trim($_GET['filter_name']) : '';
$filterFrom = $_GET['filter_from'] ?? '';
$filterTo   = $_GET['filter_to'] ?? '';

$filterNameEsc = $filterName !== '' ? mysqli_real_escape_string($conn, $filterName) : '';
$filterFromEsc = $filterFrom !== '' ? mysqli_real_escape_string($conn, $filterFrom) : '';
$filterToEsc   = $filterTo   !== '' ? mysqli_real_escape_string($conn, $filterTo)   : '';

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
    JOIN users u ON p.user_id = u.id        -- account owner
    JOIN users pu ON t.performed_by = pu.id -- performer
";

// base WHERE condition for checking among user and admin
if ($isAdmin) {
    $where = "WHERE 1=1";
} else {
    $where = "WHERE u.id = $userId";
}

// global search
if ($searchEsc !== "") {
    $where .= " AND (
        p.full_name LIKE '%$searchEsc%' OR
        p.phone LIKE '%$searchEsc%' OR
        u.username LIKE '%$searchEsc%' OR
        u.email LIKE '%$searchEsc%' OR
        a.account_number LIKE '%$searchEsc%' OR
        t.transaction_type LIKE '%$searchEsc%' OR
        t.status LIKE '%$searchEsc%' OR
        pu.username LIKE '%$searchEsc%' OR
        t.amount LIKE '%$searchEsc%'
    )";
}

// filter by name (account holder name)
if ($filterType === 'name' && $filterNameEsc !== '') {
    $where .= " AND p.full_name LIKE '%$filterNameEsc%'";
}

// filter by date range (transaction_date)
if ($filterType === 'date') {
    if ($filterFromEsc !== '') {
        $where .= " AND t.transaction_date >= '{$filterFromEsc} 00:00:00'";
    }
    if ($filterToEsc !== '') {
        $where .= " AND t.transaction_date <= '{$filterToEsc} 23:59:59'";
    }
}

// ORDER BY logic
$orderBy = "ORDER BY t.transaction_date DESC";
if ($sort === 'name_asc') {
    $orderBy = "ORDER BY p.full_name ASC, t.transaction_date DESC";
} elseif ($sort === 'name_desc') {
    $orderBy = "ORDER BY p.full_name DESC, t.transaction_date DESC";
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

// checking page if out of range
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
        $orderBy
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

    <div class="search-row">

        <!-- LEFT → Go Back button (only when search is active) -->
        <?php if ($search !== ""): ?>
            <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="back-btn">← Go Back</a>
        <?php else: ?>
            <div></div>
        <?php endif; ?>

        <!-- RIGHT → Search + small filter image (popup) -->
        <form method="GET" class="search-bar">
            <input
                type="text"
                name="search"
                placeholder="Search here..."
                value="<?= htmlspecialchars($search) ?>"
            >
            <button type="submit">Search</button>

            <!-- keep page reset on each search -->
            <input type="hidden" name="page" value="1">

            <!-- keep sort / filter on submit -->
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
            <input type="hidden" name="filter_type" value="<?= htmlspecialchars($filterType) ?>">
            <input type="hidden" name="filter_name" value="<?= htmlspecialchars($filterName) ?>">
            <input type="hidden" name="filter_from" value="<?= htmlspecialchars($filterFrom) ?>">
            <input type="hidden" name="filter_to" value="<?= htmlspecialchars($filterTo) ?>">

            <!-- small filter image -->
            <button type="button" class="filter-trigger" id="filterToggle">
                <img src="../images/filter.png" alt="Filter">
            </button>

            <!-- small pop-up -->
            <div class="filter-popup" id="filterPopup">
                <h4>View Options</h4>

                <div class="filter-section">
                    <strong>Sort by Name</strong>
                    <label>
                        <input
                            type="radio"
                            name="sort"
                            value="name_asc"
                            <?= $sort === 'name_asc' ? 'checked' : '' ?>
                        >
                        A → Z
                    </label>
                    <label>
                        <input
                            type="radio"
                            name="sort"
                            value="name_desc"
                            <?= $sort === 'name_desc' ? 'checked' : '' ?>
                        >
                        Z → A
                    </label>
                    <label>
                        <input
                            type="radio"
                            name="sort"
                            value=""
                            <?= $sort === '' ? 'checked' : '' ?>
                        >
                        Default (Latest first)
                    </label>
                </div>

                <div class="filter-section">
                    <strong>Filter</strong>

                    <label>
                        <input
                            type="radio"
                            name="filter_type"
                            value="date"
                            <?= $filterType === 'date' ? 'checked' : '' ?>
                        >
                        By Date
                    </label>
                    <div class="filter-sub" id="filterDateFields">
                        <small>Date range</small>
                        <div class="filter-row">
                            <input
                                type="date"
                                name="filter_from"
                                value="<?= htmlspecialchars($filterFrom) ?>"
                            >
                            <span>to</span>
                            <input
                                type="date"
                                name="filter_to"
                                value="<?= htmlspecialchars($filterTo) ?>"
                            >
                        </div>
                    </div>

                    <label>
                        <input
                            type="radio"
                            name="filter_type"
                            value="name"
                            <?= $filterType === 'name' ? 'checked' : '' ?>
                        >
                        By Name
                    </label>
                    <div class="filter-sub" id="filterNameFields">
                        <input
                            type="text"
                            name="filter_name"
                            placeholder="Account holder name"
                            value="<?= htmlspecialchars($filterName) ?>"
                        >
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn-apply">Apply</button>
                    <button type="button" class="btn-clear" id="filterClear">Clear</button>
                </div>
            </div>
        </form>

    </div>

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
                        href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>&filter_type=<?= urlencode($filterType) ?>&filter_name=<?= urlencode($filterName) ?>&filter_from=<?= urlencode($filterFrom) ?>&filter_to=<?= urlencode($filterTo) ?>"
                        class="page-link"
                    >Prev</a>
                <?php endif; ?>

                <!-- Pages -->
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a
                        href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>&filter_type=<?= urlencode($filterType) ?>&filter_name=<?= urlencode($filterName) ?>&filter_from=<?= urlencode($filterFrom) ?>&filter_to=<?= urlencode($filterTo) ?>"
                        class="page-link <?= ($i === $page) ? 'active-page' : '' ?>"
                    ><?= $i ?></a>
                <?php endfor; ?>

                <!-- Next -->
                <?php if ($page < $totalPages): ?>
                    <a
                        href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>&filter_type=<?= urlencode($filterType) ?>&filter_name=<?= urlencode($filterName) ?>&filter_from=<?= urlencode($filterFrom) ?>&filter_to=<?= urlencode($filterTo) ?>"
                        class="page-link"
                    >Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const toggle   = document.getElementById("filterToggle");
    const popup    = document.getElementById("filterPopup");
    const clearBtn = document.getElementById("filterClear");

    const filterTypeRadios = document.querySelectorAll("input[name='filter_type']");
    const dateFields  = document.getElementById("filterDateFields");
    const nameFields  = document.getElementById("filterNameFields");
    const form        = document.querySelector(".search-bar");

    const searchInput     = form.querySelector("input[name='search']");
    const sortInputs      = form.querySelectorAll("input[name='sort']");
    const filterNameInput = form.querySelector("input[name='filter_name']");
    const filterFromInput = form.querySelector("input[name='filter_from']");
    const filterToInput   = form.querySelector("input[name='filter_to']");

    function updateFilterFields() {
        let activeType = "";
        filterTypeRadios.forEach(r => {
            if (r.checked) activeType = r.value;
        });

        if (activeType === "date") {
            dateFields.style.display = "block";
            nameFields.style.display = "none";
        } else if (activeType === "name") {
            dateFields.style.display = "none";
            nameFields.style.display = "block";
        } else {
            dateFields.style.display = "none";
            nameFields.style.display = "none";
        }
    }

    updateFilterFields();
    filterTypeRadios.forEach(r => r.addEventListener("change", updateFilterFields));

    if (toggle) {
        toggle.addEventListener("click", function (e) {
            e.stopPropagation();
            popup.style.display = (popup.style.display === "block") ? "none" : "block";
        });
    }

    document.addEventListener("click", function (e) {
        if (popup && !popup.contains(e.target) && e.target !== toggle && !toggle.contains(e.target)) {
            popup.style.display = "none";
        }
    });

    if (clearBtn) {
        clearBtn.addEventListener("click", function () {
            if (searchInput) searchInput.value = "";

            sortInputs.forEach(i => { i.checked = false; });
            const defaultSort = form.querySelector("input[name='sort'][value='']");
            if (defaultSort) defaultSort.checked = true;

            filterTypeRadios.forEach(i => { i.checked = false; });
            if (filterNameInput) filterNameInput.value = "";
            if (filterFromInput) filterFromInput.value = "";
            if (filterToInput)   filterToInput.value   = "";

            form.submit();
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
