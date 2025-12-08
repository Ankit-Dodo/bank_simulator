<?php include("../config/db.php"); ?>
<?php include("../includes/header.php"); ?>

<link rel="stylesheet" href="../css/style.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="auth-container">
    <h3 class="auth-title">Register</h3>

    <?php if (isset($_GET['success'])): ?>
    <script>
        Swal.fire({
            title: 'Registration Successful!',
            text: 'Your account has been created successfully.',
            icon: 'success',
            confirmButtonText: 'Go to Login'
        }).then(() => {
            window.location.href = "login.php"; 
        });
    </script>
    <?php endif; ?>

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

    // --- NEW FUNCTION: Check if email already exists ---
    async function checkEmailExists(email) {
        const response = await fetch("../actions/check_email.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email })
        });

        const data = await response.json();
        return data.exists; // true / false
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault(); // Prevent form before checks
        clearErrors();
        let isValid = true;

        // Username validation
        const username = usernameInput.value.trim();
        if (username.length < 3) {
            usernameError.textContent = 'Username must be at least 3 characters.';
            isValid = false;
        }

        // Email validation (format)
        const email = emailInput.value.trim();
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            emailError.textContent = 'Please enter a valid email address.';
            isValid = false;
        }

        // Password validation
        const password = passwordInput.value;
        const strongPasswordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;

        if (!strongPasswordRegex.test(password)) {
            passwordError.textContent =
                'Password must be at least 8 characters long and include a capital letter, a small letter, and a number.';
            isValid = false;
        }

        // If basic validation fails, stop here
        if (!isValid) {
            return;
        }

        // --- NEW CHECK: Email already registered ---
        const exists = await checkEmailExists(email);
        if (exists) {
            emailError.textContent = "This email is already registered.";
            return;
        }

        // Submit form only if everything is valid
        form.submit();
    });
</script>

<?php include("../includes/footer.php"); ?>
