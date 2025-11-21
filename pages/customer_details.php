<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");

// Fetch existing details if already saved
$uid = (int)($_SESSION['user_id'] ?? 0);
$full_name = $dob = $address = $phone = "";

// Correct SELECT query for profile
$sql = "SELECT full_name, dob, address, phone FROM profile WHERE user_id = ? LIMIT 1";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if ($res && mysqli_num_rows($res) === 1) {
        $row = mysqli_fetch_assoc($res);
        $full_name = $row['full_name'];
        $dob       = $row['dob'];
        $address   = $row['address'];
        $phone     = $row['phone'];
    }
    mysqli_stmt_close($stmt);
}

include("../includes/header.php");
?>

<link rel="stylesheet" href="../css/form_style.css">

<div class="form-card">
    <h3 class="form-details-title">Customer Details</h3>

    <form method="post" action="../actions/customer_details_action.php">
        
        <label>Full Name:</label>
        <input type="text" name="full_name" required
               value="<?= htmlspecialchars($full_name) ?>"
               pattern="[A-Za-z\s.']{5,100}"
               title="Full Name must be 5 to 100 characters, containing only letters, spaces, dots, or apostrophes."
               maxlength="100">

        <label>Date of Birth:</label>
        <input type="date" name="dob" required
               value="<?= htmlspecialchars($dob) ?>"
               max="<?= date('Y-m-d', strtotime('-18 years')) ?>"
               title="You must be at least 18 years old.">

        <label>Address:</label>
        <textarea name="address" required minlength="10" maxlength="255"><?= htmlspecialchars($address) ?></textarea>

        <label>Phone Number:</label>
        <input type="text" name="phone" required
               value="<?= htmlspecialchars($phone) ?>"
               pattern="[0-9]{10}"
               title="Phone number must be exactly 10 digits."
               maxlength="10">

        <button type="submit">Next</button>
    </form>
</div>

<?php include("../includes/footer.php"); ?>
