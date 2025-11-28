<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Indian Bank</title>
    <link rel="icon" type="image/png" href="../images/logo.png">
    <link rel="stylesheet" href="../css/style.css">
</head>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const icon = document.querySelector(".profile-icon");
    const dropdown = document.querySelector(".dropdown");

    if (icon) {
        icon.addEventListener("click", () => {
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        });
    }

    document.addEventListener("click", e => {
        if (icon && !icon.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = "none";
        }
    });
});
</script>

<body>

<div class="wrapper">
<div class="content">

<div class="top-container">
    <a href="<?=
        !empty($_SESSION['user_id'])
            ? '../pages/home.php'
            : '../pages/login.php';
        ?>">
        <img class="logo" src="../images/logo.png" alt="logo">
    </a>
    <h2>Indian Bank</h2>

    <?php if (!empty($_SESSION['user_id'])): ?>
        
        <span class="username-text">
            <?= htmlspecialchars($username) ?>
        </span>

        <div class="profile-menu">
            <img src="../images/user.png" class="profile-icon" alt="profile">

            <div class="dropdown">
                <a href="../pages/customer_details.php">
                    <img src="../images/add-user.png" alt="Create Icon" class="dropdown-icon">
                    Add Details
                </a>

                <a href="../actions/logout.php" class="logout-option">
                    <img src="../images/quit.png" alt="Logout Icon" class="dropdown-icon">
                    Logout
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($_SESSION['user_id'])): ?>
    <div class="top-nav">
        <a href="../pages/home.php">Home</a>
        <a href="../pages/deposit.php">Deposit</a>
        <a href="../pages/withdraw.php">Withdraw</a>
        <a href="../pages/transfer.php">Transfer</a>
        <a href="../pages/transaction.php">Transactions</a>
    </div>
<?php endif; ?>