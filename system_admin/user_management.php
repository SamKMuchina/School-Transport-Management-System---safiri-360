<?php
/**
 * user_management.php
 *
 * Allows System Administrators to manage users across all schools.
 *
 * Location  : system_admin/user_management.php
 * Includes  : ../includes/db.php
 * Stylesheet: ../assets/css/style.css (linked in user_management_view.php)
 *
 * Access: SYSTEM_ADMIN only
 *
 * Features:
 * - Filter users by school (blank by default until school is selected)
 * - View users for selected school with pagination (8 per page)
 * - Add new Transport Manager users with phone and email
 * - Edit user school assignment and status
 * - Activate/Deactivate user accounts
 * - Reset user passwords
 *
 * Database tables used:
 * - users, schools, transport_managers
 *
 * NOTE ON QUERIES: Uses mysqli_query() with (int) casting for numeric
 * values and mysqli_real_escape_string() for text values.
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'SYSTEM_ADMIN') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db.php';

$current_user_id = (int)$_SESSION['user_id'];
$username        = $_SESSION['username'];
$success_message = '';
$error_message   = '';

// ============================================================
// SECTION 2: HANDLE ADD USER
// ============================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_user') {

    $first_name  = trim(isset($_POST['first_name'])  ? $_POST['first_name']  : '');
    $second_name = trim(isset($_POST['second_name']) ? $_POST['second_name'] : '');
    $phone       = trim(isset($_POST['phone'])        ? $_POST['phone']       : '');
    $email       = trim(isset($_POST['email'])        ? $_POST['email']       : '');
    $school_id   = (int)(isset($_POST['school_id'])   ? $_POST['school_id']   : 0);
    $is_active   = (int)(isset($_POST['is_active'])   ? $_POST['is_active']   : 1);

    $generated_username = strtolower($first_name . '.' . $second_name);

    if (empty($first_name) || empty($second_name) || empty($school_id)) {
        $error_message = 'First name, second name and school are required.';
    } else {

        $generated_username_safe = mysqli_real_escape_string($conn, $generated_username);

        $check_sql    = "SELECT user_id FROM users WHERE username = '$generated_username_safe'";
        $check_result = mysqli_query($conn, $check_sql);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $error_message = 'Username already exists. Please adjust the name.';
        } else {

            $insert_user_sql = "INSERT INTO users (username, password_hash, role, school_id, is_active, created_at)
                                 VALUES ('$generated_username_safe', NULL, 'TRANSPORT_MANAGER', $school_id, $is_active, NOW())";

            if (mysqli_query($conn, $insert_user_sql)) {
                $new_user_id = $conn->insert_id;

                $first_name_safe  = mysqli_real_escape_string($conn, $first_name);
                $second_name_safe = mysqli_real_escape_string($conn, $second_name);
                $phone_safe       = mysqli_real_escape_string($conn, $phone);
                $email_safe       = mysqli_real_escape_string($conn, $email);

                $insert_manager_sql = "INSERT INTO transport_managers (user_id, first_name, second_name, phone, email, school_id)
                                        VALUES ($new_user_id, '$first_name_safe', '$second_name_safe', '$phone_safe', '$email_safe', $school_id)";

                if (mysqli_query($conn, $insert_manager_sql)) {
                    $success_message = 'Transport Manager created successfully. Username: ' . htmlspecialchars($generated_username);
                } else {
                    $error_message = 'Failed to create transport manager profile. Please try again.';
                }
            } else {
                $error_message = 'Failed to add user. Please try again.';
            }
        }
    }
}

// ============================================================
// SECTION 3: HANDLE EDIT USER
// ============================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_user') {

    $user_id   = (int)(isset($_POST['user_id'])   ? $_POST['user_id']   : 0);
    $school_id = (int)(isset($_POST['school_id']) ? $_POST['school_id'] : 0);
    $is_active = (int)(isset($_POST['is_active']) ? $_POST['is_active'] : 1);

    if (empty($user_id) || empty($school_id)) {
        $error_message = 'Invalid data provided.';
    } else {
        $update_sql = "UPDATE users SET school_id = $school_id, is_active = $is_active WHERE user_id = $user_id";
        if (mysqli_query($conn, $update_sql)) {
            $success_message = 'User updated successfully!';
        } else {
            $error_message = 'Failed to update user. Please try again.';
        }
    }
}

// ============================================================
// SECTION 4: HANDLE TOGGLE STATUS
// ============================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'toggle_status') {

    $user_id    = (int)(isset($_POST['user_id'])    ? $_POST['user_id']    : 0);
    $new_status = (int)(isset($_POST['new_status']) ? $_POST['new_status'] : 0);

    if ($user_id == $current_user_id && $new_status == 0) {
        $error_message = 'You cannot deactivate your own account.';
    } else {
        $toggle_sql = "UPDATE users SET is_active = $new_status WHERE user_id = $user_id";
        if (mysqli_query($conn, $toggle_sql)) {
            $status_text     = $new_status == 1 ? 'activated' : 'deactivated';
            $success_message = 'User ' . $status_text . ' successfully!';
        } else {
            $error_message = 'Failed to update status. Please try again.';
        }
    }
}

// ============================================================
// SECTION 5: HANDLE RESET PASSWORD
// ============================================================

/*
 * Resetting a password no longer means the admin picks a new one for
 * the user. Instead it sets password_hash to NULL - login.php already
 * checks for a NULL password and sends the user to set_password.html,
 * so this just lets them set their own new password, like a first
 * login, next time they try to sign in.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'reset_password') {

    $user_id = (int)(isset($_POST['user_id']) ? $_POST['user_id'] : 0);

    if (empty($user_id)) {
        $error_message = 'Invalid user.';
    } else {
        $reset_sql = "UPDATE users SET password_hash = NULL WHERE user_id = $user_id";
        if (mysqli_query($conn, $reset_sql)) {
            $success_message = 'Password reset. The user will be asked to set a new password next time they log in.';
        } else {
            $error_message = 'Failed to reset password. Please try again.';
        }
    }
}

// ============================================================
// SECTION 6: SCHOOL FILTER AND PAGINATION
// ============================================================

/*
 * Users are only shown when a school is selected from the filter.
 * Default state is blank - no users shown until a school is picked.
 * Pagination shows 8 users per page.
 */
$filter_school_id = (int)(isset($_GET['school_id']) ? $_GET['school_id'] : 0);
$current_page     = (int)(isset($_GET['p'])         ? $_GET['p']         : 1);
if ($current_page < 1) $current_page = 1;

$per_page     = 8;
$offset       = ($current_page - 1) * $per_page;
$total_users  = 0;
$total_pages  = 0;
$users_result = null;

if ($filter_school_id > 0) {

    $count_sql    = "SELECT COUNT(*) as total FROM users WHERE school_id = $filter_school_id";
    $count_result = mysqli_query($conn, $count_sql);
    $total_users  = mysqli_fetch_assoc($count_result)['total'];
    $total_pages  = ceil($total_users / $per_page);

    $users_sql = "SELECT u.user_id, u.username, u.role, u.school_id,
                         s.school_name, u.is_active
                  FROM users u
                  LEFT JOIN schools s ON u.school_id = s.school_id
                  WHERE u.school_id = $filter_school_id
                  ORDER BY u.username ASC
                  LIMIT $per_page OFFSET $offset";
    $users_result = mysqli_query($conn, $users_sql);
}

// ============================================================
// SECTION 7: FETCH SCHOOLS FOR DROPDOWNS
// ============================================================

$schools_sql    = "SELECT school_id, school_name FROM schools ORDER BY school_name ASC";
$schools_result = mysqli_query($conn, $schools_sql);
$schools_list   = array();
while ($school = mysqli_fetch_assoc($schools_result)) {
    $schools_list[] = $school;
}

$conn->close();

// ============================================================
// SECTION 8: LOAD THE VIEW
// ============================================================

include 'user_management_view.php';
