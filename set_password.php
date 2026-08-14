<?php
/**
 * set_password.php
 *
 * Handles first-time password setup only.
 *
 * On success: password is saved, user is logged in, redirected to dashboard.
 * On any failure: pops up a JS alert with the message, then sends the
 * user back to set_password.html automatically (or to login.html if
 * the account already has a password).
 */

session_start();
require_once 'includes/db.php';

function fail($message, $redirect = 'set_password.html') {
    echo "<script>alert('$message'); window.location='$redirect';</script>";
    exit();
}

$username         = strtolower(trim(isset($_POST['username']) ? $_POST['username'] : ''));
$new_password     = isset($_POST['new_password']) ? $_POST['new_password'] : '';
$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

if ($username === '' || $new_password === '' || $confirm_password === '') {
    fail('All fields are required.');
}

if ($new_password !== $confirm_password) {
    fail('Passwords do not match.');
}

if (strlen($new_password) < 6) {
    fail('Password must be at least 6 characters.');
}

$username_safe = mysqli_real_escape_string($conn, $username);
$sql    = "SELECT user_id, school_id, username, role, is_active, password_hash FROM users WHERE username = '$username_safe'";
$result = mysqli_query($conn, $sql);
$user   = mysqli_fetch_assoc($result);

if (!$user) {
    fail('Username not found. Please contact your administrator.');
}

if ((int)$user['is_active'] !== 1) {
    fail('Your account is not active. Please contact your administrator.');
}

if ($user['password_hash'] !== null) {
    fail('This account already has a password. Please log in normally.', 'login.html');
}

// All checks passed - save the password and log the user in
$password_hash      = password_hash($new_password, PASSWORD_DEFAULT);
$password_hash_safe = mysqli_real_escape_string($conn, $password_hash);
$user_id             = (int)$user['user_id'];
$update_sql           = "UPDATE users SET password_hash = '$password_hash_safe' WHERE user_id = $user_id";

if (!mysqli_query($conn, $update_sql)) {
    fail('Failed to set password. Please try again.');
}

$_SESSION['user_id']   = $user['user_id'];
$_SESSION['username']  = $user['username'];
$_SESSION['role']      = $user['role'];
$_SESSION['school_id'] = $user['school_id'];

if ($user['role'] === 'SYSTEM_ADMIN')          header('Location: system_admin/system_admin_dashboard.php');
elseif ($user['role'] === 'TRANSPORT_MANAGER')  header('Location: transport_manager/transport_manager_dashboard.php');
elseif ($user['role'] === 'DRIVER')              header('Location: driver/driver_dashboard.php');
elseif ($user['role'] === 'ATTENDANT')           header('Location: attendant/attendant_dashboard.php');
exit();
?>
