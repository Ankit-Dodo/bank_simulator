<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

//  checking the role (admin/ customer)
$isAdmin = false;
$roleSql = "SELECT role FROM users WHERE id = $user_id LIMIT 1";
$roleRes = mysqli_query($conn, $roleSql);
if ($roleRes && mysqli_num_rows($roleRes) === 1) {
    $roleRow = mysqli_fetch_assoc($roleRes);
    $isAdmin = (strtolower($roleRow['role']) === 'admin');
}

$accounts = [];

if ($isAdmin) {
    // Admin: all active accounts
    $sql = "
        SELECT 
            a.id,
            a.account_number,
            a.account_type,
            a.balance,
            p.full_name,
            u.username
        FROM account a
        INNER JOIN profile p ON a.profile_id = p.id
        INNER JOIN users u ON p.user_id = u.id
        WHERE LOWER(a.status) = 'active'
        ORDER BY p.full_name ASC, a.account_number ASC
    ";
} else {
    // Customer: only their own active accounts
    $sql = "
        SELECT 
            a.id,
            a.account_number,
            a.account_type,
            a.balance,
            p.full_name,
            u.username
        FROM account a
        INNER JOIN profile p ON a.profile_id = p.id
        INNER JOIN users u ON p.user_id = u.id
        WHERE u.id = $user_id
          AND LOWER(a.status) = 'active'
        ORDER BY a.account_number ASC
    ";
}

$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $accounts[] = $row;
    }
}

include "../includes/header.php";
?>
<link rel="stylesheet" href="../css/withdraw.css">

<h3 class="page-title-center">Withdraw Money</h3>

<?php if (isset($_GET['success']) && $_GET['success'] === 'withdraw' && isset($_GET['amount'])): ?>

<div class="flash-message" id="flashMsg">
    ₹<?= htmlspecialchars($_GET['amount']) ?> was successfully withdrawn!
</div>

<script>
    // fade-out animation
    setTimeout(() => {
        const flash = document.getElementById('flashMsg');
        if (flash) flash.classList.add('flash-hide');
    }, 2500);

    // redirect after fade
    setTimeout(() => {
        window.location.href = "/pages/withdraw.php";
    }, 3200);
</script>

<?php endif; ?>


<div class="form-container-center">
    <?php if (empty($accounts)): ?>
        <p class="empty-text" style="text-align:center;">
            No active accounts available for withdrawal.
        </p>
    <?php else: ?>
        <form id="withdrawForm" method="post" action="../actions/withdraw_action.php">
            <div class="form-group">
                <label for="from_id">From Account</label>
                <select id="from_id" name="from_id" required>
                    <option value="">-- Select Account --</option>
                    <?php foreach ($accounts as $acc): ?>
                        <option value="<?= (int)$acc['id'] ?>">
                            <?= htmlspecialchars($acc['account_number']) ?>
                            - <?= htmlspecialchars($acc['account_type']) ?>
                            (₹<?= number_format((float)$acc['balance'], 2) ?>)
                            - <?= htmlspecialchars($acc['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="error-message" id="fromAccountError"></span>
            </div>
            <div class="form-group">
                <label for="amount">Amount (Rs.)</label>
                <input type="text" id="amount" name="amount" required>
                <span class="error-message" id="amountError"></span>
            </div>

            <button type="submit" class="btn-primary">Withdraw Money</button>
        </form>
    <?php endif; ?>
</div>

<script>
document.getElementById('withdrawForm')?.addEventListener('submit', function(event) {
    let isValid = true;

    const fromAccount = document.getElementById('from_id');
    const amountVal   = document.getElementById('amount').value.trim();

    const fromError   = document.getElementById('fromAccountError');
    const amountError = document.getElementById('amountError');

    // reset errors
    fromError.textContent = '';
    amountError.textContent = '';

    // validate account selection
    if (!fromAccount.value) {
        fromError.textContent = 'Please select an account.';
        isValid = false;
    }

    // validate amount
    const amountNum = parseFloat(amountVal);
    if (!amountVal || isNaN(amountNum) || amountNum <= 0) {
        amountError.textContent = 'Please enter a valid positive amount.';
        isValid = false;
    }

    // stop if invalid
    if (!isValid) {
        event.preventDefault();
        return;
    }

    // confirmation popup
    const ok = confirm('Are you sure you want to WITHDRAW this amount?');
    if (!ok) {
        event.preventDefault();
    }
});
</script>

<?php include "../includes/footer.php"; ?>