<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];

/* ensure user is admin */
$roleSql = "SELECT role FROM users WHERE id = $current_user_id LIMIT 1";
$roleRes = mysqli_query($conn, $roleSql);

if (!$roleRes || mysqli_num_rows($roleRes) === 0) {
    die("Current user not found.");
}

$roleRow = mysqli_fetch_assoc($roleRes);
if (strtolower($roleRow['role'] ?? '') !== 'admin') {
    die("Only admin can access this page.");
}

/* dropdown for list of users */
$users = [];
$usersSql = "
    SELECT u.id, u.username, COALESCE(p.full_name, '') AS full_name
    FROM users u
    LEFT JOIN profile p ON p.user_id = u.id
    ORDER BY u.username ASC
";
$usersRes = mysqli_query($conn, $usersSql);
if ($usersRes && mysqli_num_rows($usersRes) > 0) {
    while ($row = mysqli_fetch_assoc($usersRes)) {
        $users[] = $row;
    }
}

/* form actions */
$selectedUserId = null;
$editUser  = null;
$successMsg = '';
$errorMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //  LOAD USER
    if (isset($_POST['load_user'])) {
        $selectedUserId = (int)($_POST['user_id'] ?? 0);
    }

    //  SAVE USER CHANGES
    if (isset($_POST['save_user'])) {
        $selectedUserId = (int)($_POST['edit_user_id'] ?? 0);

        $full_name   = trim($_POST['full_name'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $address     = trim($_POST['address'] ?? '');
        $newPass     = trim($_POST['new_password'] ?? '');
        $confirmPass = trim($_POST['confirm_password'] ?? '');
        $user_status = trim($_POST['user_status'] ?? 'Active');

        $user_status = ucfirst(strtolower($user_status));
        if (!in_array($user_status, ['Active', 'Inactive'], true)) {
            $user_status = 'Active';
        }

        // BACKEND VALIDATIONS
        if ($selectedUserId <= 0) {
            $errorMsg = "Invalid user selected.";
        } elseif ($full_name === '' || $email === '') {
            $errorMsg = "Full name and email are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = "Invalid email format.";
        } elseif ($phone !== '' && !preg_match('/^[0-9]{10}$/', $phone)) {
            $errorMsg = "Phone number must be exactly 10 digits.";
        } elseif ($newPass !== '' && $newPass !== $confirmPass) {
            $errorMsg = "New password and confirm password do not match.";
        } else {

            // UPDATE users
            $emailEsc  = mysqli_real_escape_string($conn, $email);
            $statusEsc = mysqli_real_escape_string($conn, $user_status);

            if ($newPass !== '') {
                $hash   = password_hash($newPass, PASSWORD_DEFAULT);
                $hashEsc = mysqli_real_escape_string($conn, $hash);
                $userUpdateSql = "
                    UPDATE users
                    SET email = '$emailEsc',
                        password_hash = '$hashEsc',
                        status = '$statusEsc'
                    WHERE id = $selectedUserId
                    LIMIT 1
                ";
            } else {
                $userUpdateSql = "
                    UPDATE users
                    SET email = '$emailEsc',
                        status = '$statusEsc'
                    WHERE id = $selectedUserId
                    LIMIT 1
                ";
            }

            if (!mysqli_query($conn, $userUpdateSql)) {
                $errorMsg = "Failed to update user: " . mysqli_error($conn);
            } else {

                // UPDATE / INSERT profile
                $fullEsc  = mysqli_real_escape_string($conn, $full_name);
                $phoneEsc = mysqli_real_escape_string($conn, $phone);
                $addrEsc  = mysqli_real_escape_string($conn, $address);

                $checkProfileSql = "SELECT id FROM profile WHERE user_id = $selectedUserId LIMIT 1";
                $profRes = mysqli_query($conn, $checkProfileSql);

                if ($profRes && mysqli_num_rows($profRes) > 0) {
                    $updateProfileSql = "
                        UPDATE profile
                        SET full_name = '$fullEsc',
                            phone     = '$phoneEsc',
                            address   = '$addrEsc'
                        WHERE user_id = $selectedUserId
                        LIMIT 1
                    ";
                    mysqli_query($conn, $updateProfileSql);
                } else {
                    $insertProfileSql = "
                        INSERT INTO profile (user_id, full_name, phone, address)
                        VALUES ($selectedUserId, '$fullEsc', '$phoneEsc', '$addrEsc')
                    ";
                    mysqli_query($conn, $insertProfileSql);
                }

                $successMsg = "User details updated successfully.";
            }
        }
    }
}

// load selected user
if ($selectedUserId) {
    $editSql = "
        SELECT 
            u.id,
            u.username,
            u.email,
            COALESCE(u.status, 'Active') AS status,
            COALESCE(p.full_name, '') AS full_name,
            COALESCE(p.phone, '')     AS phone,
            COALESCE(p.address, '')   AS address
        FROM users u
        LEFT JOIN profile p ON p.user_id = u.id
        WHERE u.id = $selectedUserId
        LIMIT 1
    ";

    $editRes = mysqli_query($conn, $editSql);
    if ($editRes && mysqli_num_rows($editRes) === 1) {
        $editUser = mysqli_fetch_assoc($editRes);
    } else {
        $errorMsg = "Selected user not found.";
    }
}

include "../includes/header.php";
?>
<link rel="stylesheet" href="../css/edit_user.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<h3 class="page-title-center">Manage Accounts / Users - Edit User Details</h3>

<div class="form-container-center admin-edit-container">

    <?php if ($errorMsg): ?>
        <script>
            Swal.fire({
                icon: "error",
                title: "Error!",
                text: "<?= htmlspecialchars($errorMsg) ?>",
                confirmButtonColor: "#d33"
            });
        </script>
    <?php endif; ?>


    <?php if ($successMsg): ?>
        <script>
            Swal.fire({
                icon: "success",
                title: "Success!",
                text: "<?= htmlspecialchars($successMsg) ?>",
                timer: 2500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = "../pages/home.php";
            });
        </script>
    <?php endif; ?>


    <!-- Select user dropdown -->
    <form method="post" class="admin-edit-select-form">
        <label for="user_id" class="admin-edit-label"><strong>Select User</strong></label>
        <select name="user_id" id="user_id" required class="admin-edit-select">
            <option value="">-- Choose User --</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= (int)$u['id'] ?>" <?= ($selectedUserId == (int)$u['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['username']) ?>
                    <?php if (!empty($u['full_name'])): ?>
                        (<?= htmlspecialchars($u['full_name']) ?>)
                    <?php endif; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="load_user" class="btn-primary admin-edit-load-btn">Load User</button>
    </form>

    <?php if ($editUser): ?>
        <!-- Edit user details form -->
        <form method="post" id="editUserForm" class="admin-edit-form">
            <input type="hidden" name="edit_user_id" value="<?= (int)$editUser['id'] ?>">

            <div class="form-group">
                <label><strong>Username</strong></label>
                <input type="text" value="<?= htmlspecialchars($editUser['username']) ?>" disabled>
            </div>

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="<?= htmlspecialchars($editUser['full_name']) ?>"
                    required
                >
                <span class="error-message" id="fullNameError"></span>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($editUser['email']) ?>"
                    required
                >
                <span class="error-message" id="emailError"></span>
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input
                    type="text"
                    id="phone"
                    name="phone"
                    value="<?= htmlspecialchars($editUser['phone']) ?>"
                >
                <span class="error-message" id="phoneError"></span>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea
                    id="address"
                    name="address"
                    rows="3"
                ><?= htmlspecialchars($editUser['address']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="user_status">User Status</label>
                <select id="user_status" name="user_status">
                    <?php
                        $currentStatus = ucfirst(strtolower($editUser['status'] ?? 'Active'));
                    ?>
                    <option value="Active"   <?= $currentStatus === 'Active'   ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $currentStatus === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
                <span class="error-message" id="statusError"></span>
            </div>

            <hr>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    autocomplete="new-password"
                >
                <span class="error-message" id="newPasswordError"></span>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    autocomplete="new-password"
                >
                <span class="error-message" id="confirmPasswordError"></span>
            </div>

            <button type="submit" name="save_user" class="btn-primary">Save Changes</button>
        </form>
    <?php endif; ?>

</div>

<script>
const form = document.getElementById("editUserForm");

if (form) {
    const fullNameInput = document.getElementById("full_name");
    const emailInput    = document.getElementById("email");
    const phoneInput    = document.getElementById("phone");
    const statusSelect  = document.getElementById("user_status");
    const newPassInput  = document.getElementById("new_password");
    const confPassInput = document.getElementById("confirm_password");

    const errFull  = document.getElementById("fullNameError");
    const errEmail = document.getElementById("emailError");
    const errPhone = document.getElementById("phoneError");
    const errStat  = document.getElementById("statusError");
    const errNew   = document.getElementById("newPasswordError");
    const errConf  = document.getElementById("confirmPasswordError");

    // Limit phone to 10 digits, digits only
    phoneInput.addEventListener("input", function () {
        let digits = phoneInput.value.replace(/\D/g, "");
        phoneInput.value = digits.slice(0, 10);
    });

    function validate() {
        let ok = true;

        errFull.textContent  = "";
        errEmail.textContent = "";
        errPhone.textContent = "";
        errStat.textContent  = "";
        errNew.textContent   = "";
        errConf.textContent  = "";

        const fullVal   = fullNameInput.value.trim();
        const emailVal  = emailInput.value.trim();
        const phoneVal  = phoneInput.value.trim();
        const statusVal = statusSelect.value.trim();
        const p1        = newPassInput.value.trim();
        const p2        = confPassInput.value.trim();

        // full name
        if (!fullVal || fullVal.length < 2) {
            errFull.textContent = "Full name must be at least 2 characters.";
            ok = false;
        }

        // email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailVal)) {
            errEmail.textContent = "Invalid email format.";
            ok = false;
        }

        // phone (optional, but if present must be exactly 10 digits)
        if (phoneVal && phoneVal.length !== 10) {
            errPhone.textContent = "Phone number must be exactly 10 digits.";
            ok = false;
        }

        // status
        if (statusVal !== "Active" && statusVal !== "Inactive") {
            errStat.textContent = "Invalid status.";
            ok = false;
        }

        // passwords (optional)
        if (p1) {
            if (p1.length < 5) {
                errNew.textContent = "Password must be at least 5 characters.";
                ok = false;
            }
            if (p1 !== p2) {
                errConf.textContent = "Passwords do not match.";
                ok = false;
            }
        }

        return ok;
    }

    form.addEventListener("submit", function (e) {
        if (!validate()) {
            e.preventDefault();
        }
    });
}
</script>

<?php include "../includes/footer.php"; ?>
