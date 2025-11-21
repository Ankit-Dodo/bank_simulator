<?php include("../config/db.php"); ?>

<link rel="stylesheet" href="../css/deposit.css">

<?php include("../includes/header.php"); ?>

<h3>Deposit Money</h3>

<form id="depositForm" method="post" action="../actions/deposit_action.php">
    
    <label for="amount">Amount:</label>
    <input type="text" id="amount" name="amount" required>
    <span class="error" id="amountError"></span><br>

    <label for="account_number">Account Number:</label>
    <input type="text" id="account_number" name="account_number" required>
    <span class="error" id="accountNumberError"></span><br>

    <label for="confirm_account_number">Confirm Account Number:</label>
    <input type="text" id="confirm_account_number" name="confirm_account_number" required>
    <span class="error" id="confirmAccountError"></span><br>

    <button type="submit">Deposit Money</button>
</form>

<script>
// simple front-end validation
document.getElementById('depositForm').addEventListener('submit', function (e) {
    let valid = true;

    const amountInput   = document.getElementById('amount');
    const accInput      = document.getElementById('account_number');
    const confirmInput  = document.getElementById('confirm_account_number');

    const amountError   = document.getElementById('amountError');
    const accountError  = document.getElementById('accountNumberError');
    const confirmError  = document.getElementById('confirmAccountError');

    // reset errors
    amountError.textContent  = '';
    accountError.textContent = '';
    confirmError.textContent = '';

    const amountVal  = amountInput.value.trim();
    const accVal     = accInput.value.trim();
    const confirmVal = confirmInput.value.trim();

    // amount: required, numeric, > 0
    const amountNum = parseFloat(amountVal);
    if (!amountVal || isNaN(amountNum) || amountNum <= 0) {
        amountError.textContent = 'Please enter a valid amount greater than 0.';
        valid = false;
    }

    // account number: required, digits only, length 6–20
    const accDigitsOnly = /^[0-9]{6,20}$/;
    if (!accDigitsOnly.test(accVal)) {
        accountError.textContent = 'Account number must be 6–20 digits.';
        valid = false;
    }

    // confirm account number: must match
    if (confirmVal !== accVal) {
        confirmError.textContent = 'Account numbers do not match.';
        valid = false;
    }

    if (!valid) {
        e.preventDefault();
    }
});
</script>

<?php include("../includes/footer.php"); ?>
