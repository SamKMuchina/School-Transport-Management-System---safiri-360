<?php
/**
 * login.php
 *
 * Handles normal login only (username + password for a user who
 * already has a password set).
 *
 * On success: redirects to the correct dashboard.
 * On any failure: pops up a JS alert with the message, then sends
 * the user back to login.html automatically.
 */

session_start();
require_once 'includes/db.php';

function fail($message) {
    echo "<script>alert('$message'); window.location='login.html';</script>";
    exit();
}

$username = strtolower(trim(isset($_POST['username']) ? $_POST['username'] : ''));
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($username === '' || $password === '') {
    fail('Please enter both username and password.');
}

$username_safe = mysqli_real_escape_string($conn, $username);
$sql    = "SELECT user_id, school_id, username, password_hash, role, is_active FROM users WHERE username = '$username_safe'";
$result = mysqli_query($conn, $sql);
$user   = mysqli_fetch_assoc($result);

if (!$user) {
    fail('Invalid username or password.');
}

if ((int)$user['is_active'] !== 1) {
    fail('Your account is not active. Please contact your administrator.');
}

if ($user['password_hash'] === null) {
    echo "<script>alert('No password set for this account yet. You will be taken to the Set Password page.'); window.location='set_password.html';</script>";
    exit();
}

if (!password_verify($password, $user['password_hash'])) {
    fail('Invalid username or password.');
}

// Success - log the user in
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
