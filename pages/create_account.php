<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../config/db.php");
include("../includes/header.php");
?>

<link rel="stylesheet" href="../css/form_style.css">

<div class="form-card">
    <h3 class="form-create-title">Create Account</h3>

    <form method="post" action="../actions/create_account_action.php">

        <label>Account Type:</label>
        <select name="account_type" id="account_type" required>
            <option value="">-- Select Type --</option>
            <option value="savings">Savings</option>
            <option value="current">Current</option>
            <option value="salary">Salary</option>
        </select>

        <label>Minimum Balance:</label>
        <input type="number" name="min_balance" id="min_balance" min="0" placeholder="e.g. 2000">

        <button type="submit">Submit Account Request</button>
    </form>

    <p class="form-note">
        Note: Your account request will go to admin for approval.
    </p>
</div>

<script>
    const accountType = document.getElementById('account_type');
    const minBalance = document.getElementById('min_balance');

    accountType.addEventListener('change', function() {
        switch (accountType.value) {
            case 'current':
                minBalance.value = 2000;
                minBalance.readOnly = true; // prevent editing
                break;
            case 'salary':
                minBalance.value = 0;
                minBalance.readOnly = false; // allow editing
                break;
            case 'savings':
                minBalance.value = 1000;
                minBalance.readOnly = true; // prevent editing
                break;
            default:
                minBalance.value = '';
                minBalance.readOnly = false;
        }
    });
</script>

<?php include("../includes/footer.php"); ?> 
