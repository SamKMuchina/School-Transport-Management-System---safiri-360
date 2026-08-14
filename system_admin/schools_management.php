<?php
/**
 * schools_management.php
 *
 * Allows System Administrators to manage all schools on the platform.
 *
 * Location  : system_admin/schools_management.php
 * Includes  : ../includes/db.php
 * Stylesheet: ../assets/css/style.css (linked in schools_management_view.php)
 *
 * Access: SYSTEM_ADMIN only
 *
 * Features:
 * - View all schools with aggregated statistics
 * - Search schools by name
 * - Add new schools
 * - Edit school information
 * - Toggle school active/inactive status
 *
 * Database tables used:
 * - schools, users, students, drivers, attendants, vehicles, routes
 *
 * NOTE ON QUERIES: This file uses mysqli_query() instead of prepared
 * statements (per project requirement). Numeric values (school_id,
 * new_status) are cast with (int) before use in a query. Text values
 * (school_name, address, search term) are passed through
 * mysqli_real_escape_string() before use in a query.
 *
 * FILE STRUCTURE: This file contains ALL PHP logic - access control,
 * form handling, and database queries. It stores the results in plain
 * variables, then includes schools_management_view.php, which contains
 * ONLY the HTML display - no queries, no business logic.
 */

// ============================================================
// SECTION 2: SESSION & ACCESS CONTROL
// ============================================================

session_start();

// Check if user is logged in and has SYSTEM_ADMIN role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'SYSTEM_ADMIN') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db.php';

$username        = $_SESSION['username'];
$success_message = '';
$error_message   = '';

// ============================================================
// SECTION 3: HANDLE ADD SCHOOL
// ============================================================

/*
 * Inserts a new school into the schools table.
 * Only school_name is required. Address is optional.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_school') {

    $school_name = trim(isset($_POST['school_name']) ? $_POST['school_name'] : '');
    $address     = trim(isset($_POST['address'])     ? $_POST['address']     : '');

    if (empty($school_name)) {
        $error_message = 'School name is required.';
    } else {
        $school_name_safe = mysqli_real_escape_string($conn, $school_name);
        $address_safe     = mysqli_real_escape_string($conn, $address);

        $insert_sql = "INSERT INTO schools (school_name, address) VALUES ('$school_name_safe', '$address_safe')";

        if (mysqli_query($conn, $insert_sql)) {
            $success_message = 'School added successfully!';
        } else {
            $error_message = 'Failed to add school. Please try again.';
        }
    }
}

// ============================================================
// SECTION 4: HANDLE EDIT SCHOOL
// ============================================================

/*
 * Updates school_name and address for an existing school.
 * school_id comes from hidden form field.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_school') {

    $school_id   = (int)(isset($_POST['school_id'])   ? $_POST['school_id']   : 0);
    $school_name = trim(isset($_POST['school_name'])   ? $_POST['school_name'] : '');
    $address     = trim(isset($_POST['address'])       ? $_POST['address']     : '');

    if (empty($school_name)) {
        $error_message = 'School name is required.';
    } else {
        $school_name_safe = mysqli_real_escape_string($conn, $school_name);
        $address_safe     = mysqli_real_escape_string($conn, $address);

        $update_sql = "UPDATE schools SET school_name = '$school_name_safe', address = '$address_safe' 
                        WHERE school_id = $school_id";

        if (mysqli_query($conn, $update_sql)) {
            $success_message = 'School updated successfully!';
        } else {
            $error_message = 'Failed to update school. Please try again.';
        }
    }
}

// ============================================================
// SECTION 5: HANDLE TOGGLE SCHOOL STATUS
// ============================================================

/*
 * Toggles a school between active (1) and inactive (0).
 * new_status comes from hidden form field set by PHP in the table row.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'toggle_status') {

    $school_id  = (int)(isset($_POST['school_id'])  ? $_POST['school_id']  : 0);
    $new_status = (int)(isset($_POST['new_status']) ? $_POST['new_status'] : 0);

    $update_sql = "UPDATE schools SET is_active = $new_status WHERE school_id = $school_id";

    if (mysqli_query($conn, $update_sql)) {
        $status_text     = $new_status == 1 ? 'activated' : 'deactivated';
        $success_message = "School " . $status_text . " successfully!";
    } else {
        $error_message = 'Failed to update status. Please try again.';
    }
}

// ============================================================
// SECTION 6: FETCH SUMMARY METRICS
// ============================================================

$total_schools_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM schools");
$total_schools = mysqli_fetch_assoc($total_schools_result)['count'];

// ============================================================
// SECTION 7: HANDLE SEARCH & FETCH ALL SCHOOLS
// ============================================================

/*
 * If search term is provided, filter schools by name using LIKE.
 * Otherwise fetch all schools.
 * LEFT JOIN users and students for count aggregation.
 * GROUP BY school_id to avoid duplicate rows from the JOINs.
 */
$search_term = '';
if (isset($_GET['search'])) {
    $search_term = trim($_GET['search']);
}

if (!empty($search_term)) {

    $search_safe = mysqli_real_escape_string($conn, $search_term);

    $schools_sql = "SELECT
                s.school_id,
                s.school_name,
                s.address,
                s.is_active,
                s.created_at,
                COUNT(DISTINCT u.user_id)  AS total_users,
                COUNT(DISTINCT st.student_id) AS total_students
             FROM schools s
             LEFT JOIN users u    ON s.school_id = u.school_id
             LEFT JOIN students st ON s.school_id = st.school_id
             WHERE s.school_name LIKE '%$search_safe%'
             GROUP BY s.school_id
             ORDER BY s.created_at DESC";

    $schools_result = mysqli_query($conn, $schools_sql);

} else {

    $schools_sql = "SELECT
                s.school_id,
                s.school_name,
                s.address,
                s.is_active,
                s.created_at,
                COUNT(DISTINCT u.user_id)     AS total_users,
                COUNT(DISTINCT st.student_id) AS total_students
            FROM schools s
            LEFT JOIN users u     ON s.school_id = u.school_id
            LEFT JOIN students st ON s.school_id = st.school_id
            GROUP BY s.school_id
            ORDER BY s.created_at DESC";

    $schools_result = mysqli_query($conn, $schools_sql);
}

// ============================================================
// SECTION 8: LOAD THE VIEW (HTML display only)
// ============================================================

include 'schools_management_view.php';
