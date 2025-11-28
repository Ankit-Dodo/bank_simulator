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

    //  "Load User"
    if (isset($_POST['load_user'])) {
        $selectedUserId = (int)($_POST['user_id'] ?? 0);
    }

    //  "Save Changes"
    if (isset($_POST['save_user'])) {
        $selectedUserId = (int)($_POST['edit_user_id'] ?? 0);

        $full_name   = trim($_POST['full_name'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $address     = trim($_POST['address'] ?? '');
        $newPass     = trim($_POST['new_password'] ?? '');
        $confirmPass = trim($_POST['confirm_password'] ?? '');
        $user_status = trim($_POST['user_status'] ?? 'Active');

        // normalize status
        $user_status = ucfirst(strtolower($user_status));
        if (!in_array($user_status, ['Active', 'Inactive'], true)) {
            $user_status = 'Active';
        }

        if ($selectedUserId <= 0) {
            $errorMsg = "Invalid user selected.";
        } elseif ($full_name === '' || $email === '') {
            $errorMsg = "Full name and email are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = "Invalid email format.";
        } elseif ($newPass !== '' && $newPass !== $confirmPass) {
            $errorMsg = "New password and confirm password do not match.";
        } else {
            // Update users table 
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
                // Update profile table (full_name, phone, address)
                $fullEsc  = mysqli_real_escape_string($conn, $full_name);
                $phoneEsc = mysqli_real_escape_string($conn, $phone);
                $addrEsc  = mysqli_real_escape_string($conn, $address);

                // If profile row exists -> update, else insert
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

// selected user current data
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
        $errorMsg = $errorMsg ?: "Selected user not found.";
    }
}

include "../includes/header.php";
?>

<h3 class="page-title-center">Manage Accounts / Users - Edit User Details</h3>

<div class="form-container-center" style="max-width: 600px; margin: 0 auto;">

    <?php if ($errorMsg): ?>
        <p class="error-message" style="color:red;"><?= htmlspecialchars($errorMsg) ?></p>
    <?php endif; ?>

    <?php if ($successMsg): ?>
        <script>
            alert("<?= htmlspecialchars($successMsg) ?>");
            window.location.href = "../pages/home.php";
        </script>
    <?php endif; ?>


    <!-- Select user dropdown -->
    <form method="post" style="margin-bottom: 20px;">
        <label for="user_id"><strong>Select User</strong></label>
        <select name="user_id" id="user_id" required>
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
        <button type="submit" name="load_user" class="btn-primary" style="margin-top:10px;">Load User</button>
    </form>

    <?php if ($editUser): ?>
        <!-- Edit user details form -->
        <form method="post">
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
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input
                    type="text"
                    id="phone"
                    name="phone"
                    value="<?= htmlspecialchars($editUser['phone']) ?>"
                >
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
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" name="save_user" class="btn-primary">Save Changes</button>
        </form>
    <?php endif; ?>

</div>

<?php include "../includes/footer.php"; ?>
