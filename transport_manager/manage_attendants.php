<?php
/**
 * manage_attendants.php
 *
 * Transport Manager can register, view, edit and activate/deactivate attendants.
 *
 * Location  : transport_manager/manage_attendants.php
 * Includes  : ../includes/db.php
 * Stylesheet: ../assets/css/style.css (linked in manage_attendants_view.php)
 *
 * Access: TRANSPORT_MANAGER only
 *
 * Features:
 * - Register new attendants (auto-creates linked user account)
 * - Search attendants by name or phone (blank by default until search is done)
 * - Edit attendant phone number (names are read-only)
 * - Activate/Deactivate attendant accounts
 *
 * Business Rules:
 * - When adding a attendant, a user account is created (password_hash = NULL)
 * - Username auto-generated: fname.lname (lowercase, unique)
 * - Only phone number can be edited after creation
 * - Duplicate phone numbers not allowed within same school
 * - List is blank by default - only shows results after a search
 *
 * Database tables used:
 * - attendants, users
 *
 * NOTE ON QUERIES: This file uses mysqli_query() instead of prepared
 * statements (per project requirement). To keep this safe:
 *   - Every numeric value (school_id, attendant_id, user_id, new_status,
 *     new_user_id) is cast with (int) before being placed in a query.
 *     A cast number can never contain SQL syntax.
 *   - Every text value (fname, lname, phone, username, search term) is
 *     passed through mysqli_real_escape_string() before being placed in
 *     a query, so quote characters cannot break out of the SQL string.
 *
 * FILE STRUCTURE: This file (manage_attendants.php) contains ALL PHP logic -
 * access control, form handling, and database queries. It stores the
 * results in plain variables, then includes manage_attendants_view.php,
 * which contains ONLY the HTML display - no queries, no business logic.
 */

// ============================================================
// SECTION 1: SESSION & ACCESS CONTROL
// ============================================================

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TRANSPORT_MANAGER') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db.php';

// (int) cast: school_id comes from the session, but casting it here
// guarantees every query below is working with a plain number.
$school_id       = (int)$_SESSION['school_id'];
$username        = $_SESSION['username'];
$success_message = '';
$error_message   = '';

// ============================================================
// SECTION 2: HANDLE ADD ATTENDANT
// ============================================================

/*
 * Registers a new attendant and creates a user account.
 * Uses a transaction to ensure both records are created together.
 * If username already exists, appends a number to make it unique.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_attendant') {

    $fname = trim(isset($_POST['fname']) ? $_POST['fname'] : '');
    $lname = trim(isset($_POST['lname']) ? $_POST['lname'] : '');
    $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');

    if (empty($fname) || empty($lname) || empty($phone)) {
        $error_message = 'All fields are required.';
    } else {

        // Escape text values once up front - safe to reuse below
        $fname_safe = mysqli_real_escape_string($conn, $fname);
        $lname_safe = mysqli_real_escape_string($conn, $lname);
        $phone_safe = mysqli_real_escape_string($conn, $phone);

        $conn->begin_transaction();

        try {
            // Check duplicate phone in attendants table for this school
            $check_sql    = "SELECT attendant_id FROM attendants WHERE school_id = $school_id AND phone = '$phone_safe'";
            $check_result = mysqli_query($conn, $check_sql);
            if ($check_result && mysqli_num_rows($check_result) > 0) {
                throw new Exception('A attendant with this phone number already exists.');
            }

            // Generate unique username: fname.lname
            $base_username = strtolower($fname . '.' . $lname);
            $new_username  = $base_username;
            $counter       = 1;

            while (true) {
                $username_safe      = mysqli_real_escape_string($conn, $new_username);
                $user_check_sql     = "SELECT user_id FROM users WHERE username = '$username_safe'";
                $user_check_result  = mysqli_query($conn, $user_check_sql);
                if (mysqli_num_rows($user_check_result) == 0) break;
                $new_username = $base_username . $counter;
                $counter++;
            }
            $new_username_safe = mysqli_real_escape_string($conn, $new_username);

            // Insert into users table (password NULL - set on first login)
            $insert_user_sql = "INSERT INTO users (username, password_hash, role, school_id, is_active, created_at)
                                 VALUES ('$new_username_safe', NULL, 'ATTENDANT', $school_id, 1, NOW())";
            mysqli_query($conn, $insert_user_sql);
            $new_user_id = $conn->insert_id;

            // Insert into attendants table
            $insert_attendant_sql = "INSERT INTO attendants (school_id, fname, lname, phone, user_id)
                                   VALUES ($school_id, '$fname_safe', '$lname_safe', '$phone_safe', $new_user_id)";
            mysqli_query($conn, $insert_attendant_sql);

            $conn->commit();
            $success_message = 'Attendant registered successfully! A user account has been created.';

        } catch (Exception $e) {
            $conn->rollback();
            $error_message = $e->getMessage();
        }
    }
}

// ============================================================
// SECTION 3: HANDLE EDIT ATTENDANT (phone only)
// ============================================================

/*
 * Only the phone number can be updated.
 * Names are read-only because they are used for username generation.
 * Duplicate phone check excludes the current attendant.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_attendant') {

    // (int) cast: attendant_id is a number, so casting removes any chance
    // of SQL syntax being smuggled in through this field
    $attendant_id = (int)(isset($_POST['attendant_id']) ? $_POST['attendant_id'] : 0);
    $phone     = trim(isset($_POST['phone']) ? $_POST['phone'] : '');

    if (empty($phone)) {
        $error_message = 'Phone number is required.';
    } else {
        $phone_safe = mysqli_real_escape_string($conn, $phone);

        // Check duplicate phone excluding this attendant
        $check_sql    = "SELECT attendant_id FROM attendants
                          WHERE school_id = $school_id AND phone = '$phone_safe' AND attendant_id != $attendant_id";
        $check_result = mysqli_query($conn, $check_sql);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $error_message = 'A attendant with this phone number already exists.';
        } else {
            $update_sql = "UPDATE attendants SET phone = '$phone_safe'
                            WHERE attendant_id = $attendant_id AND school_id = $school_id";
            if (mysqli_query($conn, $update_sql)) {
                $success_message = 'Attendant phone updated successfully!';
            } else {
                $error_message = 'Failed to update attendant.';
            }
        }
    }
}

// ============================================================
// SECTION 4: HANDLE TOGGLE STATUS
// ============================================================

/*
 * Activates or deactivates a attendant's user account.
 * Updates users.is_active using the user_id linked to the attendant.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'toggle_status') {

    // Both values are numbers (a user id and a 0/1 flag) - (int) cast both
    $user_id    = (int)(isset($_POST['user_id'])    ? $_POST['user_id']    : 0);
    $new_status = (int)(isset($_POST['new_status']) ? $_POST['new_status'] : 0);

    $update_sql = "UPDATE users SET is_active = $new_status WHERE user_id = $user_id";
    if (mysqli_query($conn, $update_sql)) {
        $status_text     = $new_status == 1 ? 'activated' : 'deactivated';
        $success_message = "Attendant " . $status_text . " successfully!";
    } else {
        $error_message = 'Failed to update status.';
    }
}

// ============================================================
// SECTION 5: FETCH SUMMARY STATISTICS
// ============================================================

$count_sql     = "SELECT COUNT(*) as count FROM attendants WHERE school_id = $school_id";
$count_result  = mysqli_query($conn, $count_sql);
$row           = mysqli_fetch_assoc($count_result);
$total_attendants = $row['count'];

// ============================================================
// SECTION 6: SEARCH ATTENDANTS
// ============================================================

/*
 * Attendants are only shown when a search has been submitted.
 * By default ($search_query is empty) $attendants_result is null and the
 * view shows a blank state prompting the user to search first.
 * When a search term is present, results are filtered by first name,
 * last name, or phone number using LIKE with wildcard % on both sides.
 */
$search_query   = isset($_GET['search']) ? trim($_GET['search']) : '';
$attendants_result = null;

if (!empty($search_query)) {

    // Escape the search text, then wrap in % wildcards for LIKE comparison
    $search_safe = mysqli_real_escape_string($conn, $search_query);
    $attendants_sql = "SELECT d.attendant_id, d.fname, d.lname, d.phone, d.user_id, u.is_active
                     FROM attendants d
                     LEFT JOIN users u ON d.user_id = u.user_id
                     WHERE d.school_id = $school_id
                     AND (d.fname LIKE '%$search_safe%' OR d.lname LIKE '%$search_safe%' OR d.phone LIKE '%$search_safe%')
                     ORDER BY d.fname ASC";
    $attendants_result = mysqli_query($conn, $attendants_sql);
}

$conn->close();

// ============================================================
// SECTION 7: LOAD THE VIEW (HTML display only)
// ============================================================

/*
 * Everything above has finished its work. $username, $success_message,
 * $error_message, $search_query, $total_attendants, and $attendants_result are
 * now ready to be displayed. manage_attendants_view.php contains no PHP
 * logic of its own - only HTML markup with these variables echoed/looped
 * into place.
 */
include 'manage_attendants_view.php';
