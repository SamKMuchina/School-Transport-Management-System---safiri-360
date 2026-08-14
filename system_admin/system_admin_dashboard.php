<?php
/**
 * system_admin_dashboard.php
 *
 * System Administrator dashboard.
 * Shows summary statistics and preview tables for schools and users.
 * Users table shows only Transport Managers (most relevant for dashboard).
 *
 * Location : system_admin/system_admin_dashboard.php
 * Includes : ../includes/db.php
 * Stylesheet: ../assets/css/style.css (linked in system_admin_dashboard_view.php)
 *
 * Access: SYSTEM_ADMIN only
 *
 * Database tables used:
 * - schools
 * - users
 * - students
 * - trips
 * - transport_managers
 *
 * NOTE ON QUERIES: This page has no user input at all (no $_GET/$_POST
 * values are used in any query), so every query here is a fixed string
 * with nothing to cast or escape. mysqli_query() is used, same as the
 * rest of the system.
 *
 * FILE STRUCTURE: This file contains ALL PHP logic - access control and
 * database queries. It stores the results in plain variables, then
 * includes system_admin_dashboard_view.php, which contains ONLY the
 * HTML display - no queries, no business logic.
 */

// ============================================================
// SECTION 1: SESSION & ACCESS CONTROL
// ============================================================

session_start();

// Check if user is logged in and has SYSTEM_ADMIN role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'SYSTEM_ADMIN') {
    header('Location: ../login.php');
    exit();
}

// Include database connection
require_once '../includes/db.php';

// Get username from session
$username = $_SESSION['username'];

// ============================================================
// SECTION 2: FETCH SUMMARY STATISTICS
// ============================================================

// Total number of schools registered
$schools_count_result = mysqli_query($conn, "SELECT COUNT(*) FROM schools");
$total_schools = mysqli_fetch_row($schools_count_result)[0] ?? 0;

// Total number of active users across all roles
$active_users_count_result = mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE is_active = 1");
$total_active_users = mysqli_fetch_row($active_users_count_result)[0] ?? 0;

// Total number of students across all schools
$students_count_result = mysqli_query($conn, "SELECT COUNT(*) FROM students");
$total_students = mysqli_fetch_row($students_count_result)[0] ?? 0;

// Number of trips currently in progress
$active_trips_count_result = mysqli_query($conn, "SELECT COUNT(*) FROM trips WHERE status = 'IN_PROGRESS'");
$active_trips = mysqli_fetch_row($active_trips_count_result)[0] ?? 0;

// ============================================================
// SECTION 3: FETCH SCHOOLS OVERVIEW (last 5)
// ============================================================

/*
 * JOIN schools with transport_managers to show the manager name.
 * LEFT JOIN used so schools without a manager still appear.
 * Subquery counts students per school.
 */
$schools_sql = "
    SELECT
        s.school_id,
        s.school_name,
        CONCAT(tm.first_name, ' ', tm.second_name) AS transport_manager,
        (SELECT COUNT(*) FROM students WHERE school_id = s.school_id) AS total_students,
        s.is_active
    FROM schools s
    LEFT JOIN transport_managers tm ON s.school_id = tm.school_id
    ORDER BY s.school_id DESC
    LIMIT 5
";

$schools_result = mysqli_query($conn, $schools_sql);
if (!$schools_result) {
    $schools_result = false;
}

// ============================================================
// SECTION 4: FETCH TRANSPORT MANAGERS OVERVIEW (last 5)
// ============================================================

/*
 * Shows only Transport Managers for dashboard relevance.
 * JOIN with schools to display the school name.
 */
$users_sql = "
    SELECT
        u.username,
        u.role,
        s.school_name,
        u.is_active
    FROM users u
    LEFT JOIN schools s ON u.school_id = s.school_id
    WHERE u.role = 'TRANSPORT_MANAGER'
    ORDER BY u.user_id DESC
    LIMIT 5
";

$users_result = mysqli_query($conn, $users_sql);
if (!$users_result) {
    $users_result = false;
}

// Close database connection
$conn->close();

// ============================================================
// SECTION 5: LOAD THE VIEW (HTML display only)
// ============================================================

/*
 * $username, $total_schools, $total_active_users, $total_students,
 * $active_trips, $schools_result, and $users_result are now ready to
 * display. system_admin_dashboard_view.php contains no PHP logic of
 * its own - only HTML markup with these variables echoed/looped into
 * place.
 */
include 'system_admin_dashboard_view.php';
