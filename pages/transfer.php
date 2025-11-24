<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");
include("../includes/header.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// ---------- Determine if current user is admin (from DB) ----------
$isAdmin = false;
$roleSql = "SELECT role FROM users WHERE id = $user_id LIMIT 1";
$roleRes = mysqli_query($conn, $roleSql);
if ($roleRes && mysqli_num_rows($roleRes) === 1) {
    $roleRow = mysqli_fetch_assoc($roleRes);
    $isAdmin = (strtolower($roleRow['role']) === 'admin');
}

// ---------- Load accounts for dropdown ----------
//  - Customer: only their own active accounts
//  - Admin   : all active accounts (any customer)
$accounts = [];

if ($isAdmin) {
    // Admin can see all active accounts
    $sql = "
        SELECT 
            a.account_id,
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
            a.account_id,
            a.account_number,
            a.account_type,
            a.balance,
            p.full_name,
            u.username
        FROM account a
        INNER JOIN profile p ON a.profile_id = p.id
        INNERJOIN users u ON p.user_id = u.id
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
?>

<link rel="stylesheet" href="../css/transfer.css">

<div class="transfer-container">
    <div class="transfer-card">
        <h3 class="transfer-title">Transfer Money</h3>

        <?php if (empty($accounts)): ?>
            <p style="text-align:center; margin-top:20px;">
                No active accounts available for transfer.
            </p>
        <?php else: ?>
            <form class="transfer-form" id="transferForm" method="post" action="../actions/transfer_action.php">
                <!-- From Account (dropdown) -->
                <label for="from_account_id">From Account:</label>
                <select id="from_account_id" name="from_account_id" required>
                    <option value="">-- Select Account --</option>
                    <?php foreach ($accounts as $acc): ?>
                        <option value="<?php echo (int)$acc['account_id']; ?>">
                            <?php
                                echo htmlspecialchars($acc['account_number'])
                                     . " - " . htmlspecialchars($acc['account_type'])
                                     . " (Rs." . number_format((float)$acc['balance'], 2) . ")"
                                     . " - " . htmlspecialchars($acc['full_name']);
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="error" id="fromAccountError"></span>

                <!-- Amount -->
                <label for="amount">Amount:</label>
                <input type="text" id="amount" name="amount" required>
                <span class="error" id="amountError"></span>

                <!-- Receiver account -->
                <label for="account_number">Receiver's Account Number:</label>
                <input type="text" id="account_number" name="account_number" required>
                <span class="error" id="accountNumberError"></span>

                <!-- Confirm receiver account -->
                <label for="confirm_account_number">Confirm Account Number:</label>
                <input type="text" id="confirm_account_number" name="confirm_account_number" required>
                <span class="error" id="confirmAccountError"></span>

                <button type="submit" class="transfer-btn">Send Money</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('transferForm')?.addEventListener('submit', function(event) {
    let isValid = true;

    const fromAccount = document.getElementById('from_account_id');
    const amount = document.getElementById('amount').value.trim();
    const accNum = document.getElementById('account_number').value.trim();
    const confirmAccNum = document.getElementById('confirm_account_number').value.trim();

    // error spans
    const fromAccountError = document.getElementById('fromAccountError');
    const amountError      = document.getElementById('amountError');
    const accError         = document.getElementById('accountNumberError');
    const confirmError     = document.getElementById('confirmAccountError');

    // reset errors
    fromAccountError.textContent = '';
    amountError.textContent      = '';
    accError.textContent         = '';
    confirmError.textContent     = '';

    // 0. From account must be selected
    if (!fromAccount.value) {
        fromAccountError.textContent = 'Please select an account to transfer from.';
        isValid = false;
    }

    // 1. Amount must be numeric & > 0
    const amountNum = parseFloat(amount);
    if (!amount || isNaN(amountNum) || amountNum <= 0) {
        amountError.textContent = 'Please enter a valid positive amount.';
        isValid = false;
    }

    // 2. Account number must be digits only (6–20)
    const digitsOnly = /^[0-9]{6,20}$/;
    if (!digitsOnly.test(accNum)) {
        accError.textContent = 'Account number must be 6–20 digits.';
        isValid = false;
    }

    // 3. Both account numbers must match
    if (accNum !== confirmAccNum) {
        accError.textContent = 'Account numbers do not match.';
        confirmError.textContent = 'Account numbers do not match.';
        isValid = false;
    }

    if (!isValid) {
        event.preventDefault();
    }
});
</script>

<?php include("../includes/footer.php"); ?>
