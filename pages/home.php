<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../config/db.php");

$uid   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$role  = $_SESSION['user_role'] ?? 'customer';

$user = null;

// Fetch user info
$userSql = "SELECT username, last_login, role FROM users WHERE id = $uid LIMIT 1";
$userRes = mysqli_query($conn, $userSql);

if ($userRes && mysqli_num_rows($userRes) === 1) {
    $user = mysqli_fetch_assoc($userRes);
}

include("../includes/header.php");
?>

<div class="page-container">

    <div class="dashboard-header">
        <div>
            <h3>Dashboard</h3>

            <p class="welcome-text">
                Welcome,
                <strong><?php echo htmlspecialchars($user['username'] ?? ''); ?></strong>

                <?php if (($user['role'] ?? $role) === 'admin'): ?>
                    <span class="role-pill">ADMIN</span>
                <?php endif; ?>
            </p>

            <?php if ($user): ?>
                <p class="last-login">Last login: <?php echo $user['last_login']; ?></p>
            <?php endif; ?>

        </div>
    </div>

    <div class="dashboard-content">
        <?php if ($role === 'admin'): ?>
            <?php include("admin_account.php"); ?>
        <?php else: ?>
            <?php include("customer_account.php"); ?>
        <?php endif; ?>
    </div>

</div>

<?php include("../includes/footer.php"); ?>
