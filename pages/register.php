<?php include("../config/db.php"); ?>
<?php include("../includes/header.php"); ?>

<link rel="stylesheet" href="../css/style.css">

<div class="auth-container">
    <h3 class="auth-title">Register</h3>

    <form method="post" action="../actions/register_action.php" id="registerForm" novalidate>
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required minlength="3">
            <small class="error-message" id="usernameError"></small>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            <small class="error-message" id="emailError"></small>
        </div>

        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required minlength="6">
            <small class="error-message" id="passwordError"></small>
        </div>

        <button type="submit" class="btn-primary">Register</button>
    </form>

    <p class="auth-switch">
        Already have an account? <a href="login.php">Login</a>
    </p>
</div>

<script>
    const form = document.getElementById('registerForm');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    const usernameError = document.getElementById('usernameError');
    const emailError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');

    function clearErrors() {
        usernameError.textContent = '';
        emailError.textContent = '';
        passwordError.textContent = '';
    }

    form.addEventListener('submit', function (e) {
        clearErrors();
        let isValid = true;

        // Username validation
        const username = usernameInput.value.trim();
        if (username.length < 3) {
            usernameError.textContent = 'Username must be at least 3 characters.';
            isValid = false;
        }

        // Email validation (basic)
        const email = emailInput.value.trim();
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            emailError.textContent = 'Please enter a valid email address.';
            isValid = false;
        }

        // Password validation
        const password = passwordInput.value.trim();
        if (password.length < 5) {
            passwordError.textContent = 'Password must be at least 5 characters.';
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        }
    });
</script>

<?php include("../includes/footer.php"); ?>
