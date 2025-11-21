<?php
session_start();
include("../config/db.php");
include("../includes/header.php");
?>

<link rel="stylesheet" href="../assets/css/auth.css">

<div class="auth-container">
    <h3 class="auth-title">Login</h3>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert-error">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="../actions/login_action.php">
        <div class="form-group">
            <label for="email">Email:</label>
            <input
                type="email"
                id="email"
                name="email"
                required
            >
        </div>

        <div class="form-group">
            <label for="password">Password:</label>
            <input
                type="password"
                id="password"
                name="password"
                required
            >
        </div>

        <button type="submit" class="btn-primary">Login</button>
    </form>

    <p class="auth-switch">
        No account? <a href="register.php">Register</a>
    </p>
</div>

<?php include("../includes/footer.php"); ?>
