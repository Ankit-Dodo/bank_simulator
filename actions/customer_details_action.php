<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../config/db.php");
include("../includes/auth_check.php");

$errors = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/customer_details.php");
    exit;
}

$uid       = (int)($_SESSION['user_id'] ?? 0);
$full_name = trim($_POST['full_name'] ?? '');
$dob       = trim($_POST['dob'] ?? '');
$address   = trim($_POST['address'] ?? '');
$phone     = trim($_POST['phone'] ?? '');

//  USER ID CHECK 
if ($uid <= 0) {
    $errors[] = "Invalid session. Please log in again.";
}

//  FULL NAME 
if (empty($full_name)) {
    $errors[] = "Full Name is required.";
} elseif (strlen($full_name) > 255) {
    $errors[] = "Full Name is too long.";
}

//  DATE OF BIRTH 
if (empty($dob)) {
    $errors[] = "Date of Birth is required.";
} else {
    $parts = explode('-', $dob);
    if (count($parts) !== 3 || !checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
        $errors[] = "Invalid Date of Birth format.";
    }
}

//  ADDRESS 
if (empty($address)) {
    $errors[] = "Address is required.";
} elseif (strlen($address) > 500) {
    $errors[] = "Address is too long.";
}

//  PHONE 
if (empty($phone)) {
    $errors[] = "Phone number is required.";
} elseif (!preg_match('/^\d{10}$/', $phone)) {
    $errors[] = "Phone number must be exactly 10 digits.";
}

//  ON ERROR 
if (!empty($errors)) {
    echo "<div style='color:red; font-size:14px;'>";
    foreach ($errors as $e) echo $e . "<br>";
    echo "</div>";
    exit;
}

//  ESCAPE VALUES 
$full_name_esc = mysqli_real_escape_string($conn, $full_name);
$dob_esc       = mysqli_real_escape_string($conn, $dob);
$address_esc   = mysqli_real_escape_string($conn, $address);
$phone_esc     = mysqli_real_escape_string($conn, $phone);

//  CHECK IF PROFILE EXISTS 
$checkSql = "SELECT id FROM profile WHERE user_id = $uid LIMIT 1";
$checkRes = mysqli_query($conn, $checkSql);

if ($checkRes && mysqli_num_rows($checkRes) === 1) {

    // UPDATE EXISTING PROFILE
    $row = mysqli_fetch_assoc($checkRes);
    $profile_id = (int)$row['id'];

    $updateSql = "
        UPDATE profile
        SET full_name = '$full_name_esc',
            dob       = '$dob_esc',
            address   = '$address_esc',
            phone     = '$phone_esc'
        WHERE id = $profile_id
    ";

    if (!mysqli_query($conn, $updateSql)) {
        error_log("Profile update failed: " . mysqli_error($conn));
        die("Database update failed.");
    }

} else {

    // INSERT NEW PROFILE
    $insertSql = "
        INSERT INTO profile (user_id, full_name, dob, address, phone)
        VALUES ($uid, '$full_name_esc', '$dob_esc', '$address_esc', '$phone_esc')
    ";

    if (!mysqli_query($conn, $insertSql)) {
        error_log("Profile insert failed: " . mysqli_error($conn));
        die("Database insert failed.");
    }
}
header("Location: ../pages/create_account.php");
exit;
?>
