<?php
/**
 * system_reports.php
 *
 * System Admin reports - platform-wide aggregated data.
 *
 * Location  : system_admin/system_reports.php
 * Includes  : ../includes/db.php
 * Stylesheet: ../assets/css/style.css (linked in system_reports_view.php)
 *
 * Access: SYSTEM_ADMIN only
 *
 * Tabs:
 * - Platform Dashboard : high-level counts (schools, users, students, vehicles)
 * - School Performance : per-school completed trips
 * - User Activity      : role breakdown and user list per school
 * - Incident Summary   : incident counts by type per school or all schools
 *
 * Database tables used:
 * - schools, users, students, vehicles, trips, incidents, drivers, attendants
 *
 * NOTE ON QUERIES: This file uses mysqli_query() instead of prepared
 * statements (per project requirement). $school_filter is cast with
 * (int) right where it is read from $_GET, so every later use of it
 * in a query string is guaranteed to be a plain number.
 *
 * FILE STRUCTURE: This file contains ALL PHP logic - access control
 * and tab data fetching. It stores the results in plain variables,
 * then includes system_reports_view.php, which contains ONLY the
 * HTML display - no queries, no business logic.
 */

// ============================================================
// SECTION 1: SESSION & ACCESS CONTROL
// ============================================================

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'SYSTEM_ADMIN') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db.php';

$username      = $_SESSION['username'];
$active_tab    = isset($_GET['tab'])       ? $_GET['tab']        : 'dashboard';
$school_filter = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;

// ============================================================
// SECTION 2: INITIALIZE DATA ARRAYS
// ============================================================

$dashboard_data   = array();
$school_data      = array();
$user_roles       = array();
$user_totals      = array();
$incident_summary = array();
$schools_list     = array();
$users_in_school  = array();

// ============================================================
// SECTION 3: FETCH SCHOOLS FOR FILTER DROPDOWN
// ============================================================

$schools_res = mysqli_query($conn, "SELECT school_id, school_name FROM schools ORDER BY school_name");
if ($schools_res) {
    $schools_list = mysqli_fetch_all($schools_res, MYSQLI_ASSOC);
}

// ============================================================
// SECTION 4: PLATFORM DASHBOARD DATA
// ============================================================

/*
 * Fetches high-level platform statistics:
 * - Total and active schools
 * - Total and active users
 * - Total students
 * - Total vehicles
 */
if ($active_tab === 'dashboard') {

    $res = mysqli_query($conn, "SELECT COUNT(*) AS total, SUM(is_active = 1) AS active FROM schools");
    if ($res) $dashboard_data['schools'] = mysqli_fetch_assoc($res);

    $res = mysqli_query($conn, "SELECT COUNT(*) AS total, SUM(is_active = 1) AS active FROM users");
    if ($res) $dashboard_data['users'] = mysqli_fetch_assoc($res);

    $res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM students");
    if ($res) $dashboard_data['students'] = mysqli_fetch_assoc($res)['total'];

    $res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM vehicles");
    if ($res) $dashboard_data['vehicles'] = mysqli_fetch_assoc($res)['total'];
}

// ============================================================
// SECTION 5: SCHOOL PERFORMANCE DATA
// ============================================================

/*
 * Fetches per-school statistics using subqueries.
 * Shows completed trips, students, drivers, attendants per school.
 */
if ($active_tab === 'schools') {

    $schools_perf_sql = "SELECT s.school_name,
                   (SELECT COUNT(*) FROM trips      WHERE school_id = s.school_id AND status = 'COMPLETED') AS completed_trips,
                   (SELECT COUNT(*) FROM students   WHERE school_id = s.school_id) AS students,
                   (SELECT COUNT(*) FROM drivers    WHERE school_id = s.school_id) AS drivers,
                   (SELECT COUNT(*) FROM attendants WHERE school_id = s.school_id) AS attendants
            FROM schools s
            ORDER BY s.school_name";

    $res = mysqli_query($conn, $schools_perf_sql);
    if ($res) {
        $school_data = mysqli_fetch_all($res, MYSQLI_ASSOC);
    }
}

// ============================================================
// SECTION 6: USER ACTIVITY DATA
// ============================================================

/*
 * Fetches user role breakdown with active/inactive counts.
 * Optional school filter to show users in a specific school.
 * Also fetches full user list if a school is selected.
 */
if ($active_tab === 'users') {

    // Role breakdown
    $roles_sql = "SELECT u.role,
                   SUM(u.is_active = 1) AS active,
                   SUM(u.is_active = 0) AS inactive,
                   COUNT(*) AS total
            FROM users u
            WHERE u.role IN ('TRANSPORT_MANAGER', 'DRIVER', 'ATTENDANT')";

    if ($school_filter > 0) {
        $roles_sql .= " AND u.school_id = $school_filter";
    }
    $roles_sql .= " GROUP BY u.role ORDER BY u.role";

    $res = mysqli_query($conn, $roles_sql);
    if ($res) {
        $user_roles = mysqli_fetch_all($res, MYSQLI_ASSOC);
    }

    // Totals row for table footer
    $totals_sql = "SELECT SUM(u.is_active = 1) AS total_active,
                         SUM(u.is_active = 0) AS total_inactive,
                         COUNT(*) AS total_all
                  FROM users u
                  WHERE u.role IN ('TRANSPORT_MANAGER', 'DRIVER', 'ATTENDANT')";

    if ($school_filter > 0) {
        $totals_sql .= " AND u.school_id = $school_filter";
    }

    $res_total = mysqli_query($conn, $totals_sql);
    if ($res_total) {
        $user_totals = mysqli_fetch_assoc($res_total);
    }

    // Full user list for selected school
    if ($school_filter > 0) {
        $school_users_sql = "SELECT username, role,
                             CASE WHEN is_active = 1 THEN 'Active' ELSE 'Inactive' END AS status,
                             created_at
                      FROM users
                      WHERE school_id = $school_filter
                      AND role IN ('TRANSPORT_MANAGER', 'DRIVER', 'ATTENDANT')
                      ORDER BY role, username";

        $res_users = mysqli_query($conn, $school_users_sql);
        if ($res_users) {
            $users_in_school = mysqli_fetch_all($res_users, MYSQLI_ASSOC);
        }
    }
}

// ============================================================
// SECTION 7: INCIDENT SUMMARY DATA
// ============================================================

/*
 * Fetches incident type counts with percentage calculation.
 * INNER JOIN with trips to support school filter.
 * Optional school filter via WHERE clause.
 */
if ($active_tab === 'incidents') {

    $incidents_sql = "SELECT i.incident_type, COUNT(*) AS count
            FROM incidents i
            JOIN trips t ON i.trip_id = t.trip_id";

    if ($school_filter > 0) {
        $incidents_sql .= " WHERE t.school_id = $school_filter";
    }
    $incidents_sql .= " GROUP BY i.incident_type ORDER BY count DESC";

    $res = mysqli_query($conn, $incidents_sql);
    if ($res) {
        $incident_summary  = mysqli_fetch_all($res, MYSQLI_ASSOC);
        $total_incidents   = array_sum(array_column($incident_summary, 'count'));

        foreach ($incident_summary as &$row) {
            $row['percentage'] = $total_incidents > 0 ? round(($row['count'] / $total_incidents) * 100, 1) : 0;
        }
        unset($row);
    }
}

$conn->close();

// ============================================================
// SECTION 8: LOAD THE VIEW (HTML display only)
// ============================================================

include 'system_reports_view.php';
