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

/* ---------- Determine if current user is admin ---------- */
$isAdmin = false;
$roleSql = "SELECT role FROM users WHERE id = $user_id LIMIT 1";
$roleRes = mysqli_query($conn, $roleSql);
if ($roleRes && mysqli_num_rows($roleRes) === 1) {
    $roleRow = mysqli_fetch_assoc($roleRes);
    $isAdmin = (strtolower($roleRow['role']) === 'admin');
}

/* ---------- Load accounts for dropdown ---------- */
$accounts = [];

if ($isAdmin) {
    // Admin can see all active accounts
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
    // Customer sees only their own active accounts
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

<h3 class="page-title-center">Transfer Money</h3>

<div class="form-container-center">
    <?php if (empty($accounts)): ?>
        <p class="empty-text" style="text-align:center;">
            No active accounts available for transfer.
        </p>
    <?php else: ?>
        <form id="transferForm" method="post" action="../actions/transfer_action.php">

            <!-- From Account -->
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

            <!-- Amount -->
            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="text" id="amount" name="amount" required>
                <span class="error-message" id="amountError"></span>
            </div>

            <!-- Receiver account -->
            <div class="form-group">
                <label for="account_number">Receiver's Account Number</label>
                <input type="text" id="account_number" name="account_number" required>
                <span class="error-message" id="accountNumberError"></span>
            </div>

            <!-- Confirm receiver account -->
            <div class="form-group">
                <label for="confirm_account_number">Confirm Account Number</label>
                <input type="text" id="confirm_account_number" name="confirm_account_number" required>
                <span class="error-message" id="confirmAccountError"></span>
            </div>

            <button type="submit" class="btn-primary">Send Money</button>
        </form>
    <?php endif; ?>
</div>

<script>
document.getElementById("transferForm")?.addEventListener("submit", function(event) {
    let isValid = true;

    const fromAccount = document.getElementById("from_id");
    const amountVal   = document.getElementById("amount").value.trim();
    const accNum      = document.getElementById("account_number").value.trim();
    const confirmAcc  = document.getElementById("confirm_account_number").value.trim();

    const errFrom  = document.getElementById("fromAccountError");
    const errAmt   = document.getElementById("amountError");
    const errAcc   = document.getElementById("accountNumberError");
    const errConf  = document.getElementById("confirmAccountError");

    errFrom.textContent = "";
    errAmt.textContent  = "";
    errAcc.textContent  = "";
    errConf.textContent = "";

    // From account required
    if (!fromAccount.value) {
        errFrom.textContent = "Please select an account.";
        isValid = false;
    }

    // Amount validation
    const amountNum = parseFloat(amountVal);
    if (!amountVal || isNaN(amountNum) || amountNum <= 0) {
        errAmt.textContent = "Enter a valid positive amount.";
        isValid = false;
    }

    // Account number: 6–20 digits
    const digitsOnly = /^[0-9]{6,20}$/;
    if (!digitsOnly.test(accNum)) {
        errAcc.textContent = "Account number must be 6–20 digits.";
        isValid = false;
    }

    // Confirm match
    if (accNum !== confirmAcc) {
        errAcc.textContent  = "Account numbers do not match.";
        errConf.textContent = "Account numbers do not match.";
        isValid = false;
    }

    if (!isValid) {
        event.preventDefault();
    }
});
</script>

<?php include "../includes/footer.php"; ?>
