<?php include("../config/db.php"); ?>
<?php include("../includes/header.php"); ?>
<link rel="stylesheet" href="../css/withdraw.css">


<form id="withdrawForm" method="post" action="../actions/withdraw_action.php">
    
    <h3>Withdraw Money</h3>
    <label for="amount">Amount:</label>
    <input type="text" id="amount" name="amount" required>
    <span class="error" id="amountError"></span><br>

    <label for="account_number">Account Number:</label>
    <input type="text" id="account_number" name="account_number" required>
    <span class="error" id="accountNumberError"></span><br>

    <label for="confirm_account_number">Confirm Account Number:</label>
    <input type="text" id="confirm_account_number" name="confirm_account_number" required>
    <span class="error" id="confirmAccountError"></span><br>

    <button type="submit">Withdraw Money</button>
</form>

<script>
document.getElementById('withdrawForm').addEventListener('submit', function(event) {
    let isValid = true;

    const amount = document.getElementById('amount').value.trim();
    const accNum = document.getElementById('account_number').value.trim();
    const confirmAccNum = document.getElementById('confirm_account_number').value.trim();

    // error spans
    const amountError  = document.getElementById('amountError');
    const accError     = document.getElementById('accountNumberError');
    const confirmError = document.getElementById('confirmAccountError');

    // reset errors
    amountError.textContent  = '';
    accError.textContent     = '';
    confirmError.textContent = '';

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
