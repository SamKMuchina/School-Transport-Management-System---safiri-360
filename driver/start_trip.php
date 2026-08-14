<?php
/**
 * start_trip.php (DRIVER copy)
 *
 * Trip execution page for the DRIVER role.
 *
 * Location  : driver/start_trip.php
 * Includes  : ../includes/db.php
 *
 * Access: DRIVER only
 *
 * Flow:
 * - PENDING trip (not yet started): shows start form to select trip type and tracking link
 * - IN_PROGRESS trip: shows attendance checklist (mark picked/dropped/absent per student)
 * - COMPLETED trip: shows completion message
 *
 * POST actions (plain form submits - the whole page reloads after each one,
 * same as every other page in this system. No AJAX, no JSON.):
 * - start_trip   : sets status = IN_PROGRESS, records start_time, trip_type, tracking_link
 * - mark_student : marks student as boarded or dropped
 * - mark_absent  : marks student absent
 * - end_trip     : sets status = COMPLETED, records end_time, redirects to dashboard
 *
 * Database tables used:
 * - trips, trip_students, students, student_assignments, route_stops
 * - routes, vehicles, drivers, attendants
 *
 * NOTE ON QUERIES: Uses mysqli_query() with (int) casting for numeric
 * values and mysqli_real_escape_string() for text values.
 *
 * NOTE ON ROLE: This exact page also exists as attendant/start_trip.php,
 * a separate copy hardcoded for the ATTENDANT role. The two copies are
 * intentionally not merged with role-switching logic - each copy only
 * ever runs for one role, so there is nothing to switch on.
 *
 * FILE STRUCTURE: This file contains ALL PHP logic - access control,
 * form handling, database queries. It stores the results in plain
 * variables, then includes start_trip_view.php, which contains ONLY
 * the HTML display - no queries, no business logic.
 */

// ============================================================
// SECTION 1: SESSION & ACCESS CONTROL
// ============================================================

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'DRIVER') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db.php';

$user_id        = (int)$_SESSION['user_id'];
$username       = $_SESSION['username'];
$back_dashboard = 'driver_dashboard.php';

$error_message = '';

// Fetch driver_id from user_id
$staff_result = mysqli_query($conn, "SELECT driver_id FROM drivers WHERE user_id = $user_id");
$staff        = mysqli_fetch_assoc($staff_result);
if (!$staff) {
    die("Staff record not found.");
}
$staff_id = (int)$staff['driver_id'];

// Get trip_id from URL
$trip_id = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;
if ($trip_id <= 0) {
    die("Invalid trip ID.");
}

// Fetch trip details - verify trip is assigned to this driver
$trip_sql = "SELECT t.trip_id, t.status, t.trip_type, t.tracking_link, t.start_time, t.end_time,
                    t.trip_date, r.route_name, v.plate_no,
                    CONCAT(d.fname, ' ', d.lname) AS driver_name
             FROM trips t
             JOIN routes   r ON t.route_id   = r.route_id
             JOIN vehicles v ON t.vehicle_id = v.vehicle_id
             JOIN drivers  d ON t.driver_id  = d.driver_id
             WHERE t.trip_id = $trip_id AND t.driver_id = $staff_id";
$trip_result = mysqli_query($conn, $trip_sql);
$trip        = mysqli_fetch_assoc($trip_result);

if (!$trip) {
    die("Trip not found or not assigned to you.");
}

$status        = $trip['status'];
$trip_type     = $trip['trip_type'];
$tracking_link = $trip['tracking_link'];

// ============================================================
// SECTION 2: HANDLE START TRIP
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'start_trip') {

    $new_trip_type     = isset($_POST['trip_type'])     ? trim($_POST['trip_type'])     : '';
    $new_tracking_link = isset($_POST['tracking_link']) ? trim($_POST['tracking_link']) : '';

    if (!in_array($new_trip_type, array('morning', 'evening'))) {
        $error_message = 'Please select a trip type.';
    } elseif (empty($new_tracking_link)) {
        $error_message = 'Tracking link is required.';
    } elseif (!filter_var($new_tracking_link, FILTER_VALIDATE_URL)) {
        $error_message = 'Invalid tracking link. Please enter a valid URL.';
    } else {

        $new_trip_type_safe     = mysqli_real_escape_string($conn, $new_trip_type);
        $new_tracking_link_safe = mysqli_real_escape_string($conn, $new_tracking_link);

        $start_sql = "UPDATE trips SET status = 'IN_PROGRESS', start_time = NOW(),
                       tracking_link = '$new_tracking_link_safe', trip_type = '$new_trip_type_safe'
                       WHERE trip_id = $trip_id AND driver_id = $staff_id";

        if (mysqli_query($conn, $start_sql)) {
            // Refresh trip details so the rest of this page sees the new status
            $trip_result   = mysqli_query($conn, $trip_sql);
            $trip          = mysqli_fetch_assoc($trip_result);
            $status        = $trip['status'];
            $trip_type     = $trip['trip_type'];
            $tracking_link = $trip['tracking_link'];
        } else {
            $error_message = 'Failed to start trip. Please try again.';
        }
    }
}

// ============================================================
// SECTION 3: HANDLE MARK STUDENT (boarded or dropped)
// ============================================================

/*
 * Marks a student as boarded (morning trips) or dropped (evening trips).
 * Cannot mark if the student is already marked or is absent.
 * Uses an explicit if/elseif for the two possible fields, instead of
 * building the column name dynamically, so each case is easy to read
 * on its own.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_student') {

    $student_id = (int)(isset($_POST['student_id']) ? $_POST['student_id'] : 0);
    $field      = isset($_POST['field']) ? $_POST['field'] : '';

    $check_sql    = "SELECT boarded, dropped, absent FROM trip_students WHERE trip_id = $trip_id AND student_id = $student_id";
    $check_result = mysqli_query($conn, $check_sql);
    $row          = mysqli_fetch_assoc($check_result);

    if (!$row) {
        $error_message = 'Student not found on this trip.';
    } elseif ($row['absent'] == 1) {
        $error_message = 'Student marked absent. Cannot mark.';
    } elseif ($field === 'boarded' && $row['boarded'] == 1) {
        $error_message = 'Student already marked.';
    } elseif ($field === 'dropped' && $row['dropped'] == 1) {
        $error_message = 'Student already marked.';
    } elseif ($field === 'boarded') {
        mysqli_query($conn, "UPDATE trip_students SET boarded = 1, boarded_time = NOW() WHERE trip_id = $trip_id AND student_id = $student_id");
    } elseif ($field === 'dropped') {
        mysqli_query($conn, "UPDATE trip_students SET dropped = 1, dropped_time = NOW() WHERE trip_id = $trip_id AND student_id = $student_id");
    } else {
        $error_message = 'Invalid field.';
    }
}

// ============================================================
// SECTION 4: HANDLE MARK STUDENT ABSENT
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_absent') {

    $student_id = (int)(isset($_POST['student_id']) ? $_POST['student_id'] : 0);

    $check_sql    = "SELECT boarded, dropped, absent FROM trip_students WHERE trip_id = $trip_id AND student_id = $student_id";
    $check_result = mysqli_query($conn, $check_sql);
    $row          = mysqli_fetch_assoc($check_result);

    if (!$row) {
        $error_message = 'Student not found on this trip.';
    } elseif ($row['boarded'] == 1 || $row['dropped'] == 1) {
        $error_message = 'Student already marked as present.';
    } elseif ($row['absent'] == 1) {
        $error_message = 'Student already marked absent.';
    } else {
        mysqli_query($conn, "UPDATE trip_students SET absent = 1 WHERE trip_id = $trip_id AND student_id = $student_id");
    }
}

// ============================================================
// SECTION 5: HANDLE END TRIP
// ============================================================

/*
 * On success, redirects straight to the dashboard - a normal PHP
 * redirect, the same pattern used after login and other actions
 * elsewhere in this system.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'end_trip') {

    $end_sql = "UPDATE trips SET status = 'COMPLETED', end_time = NOW()
                 WHERE trip_id = $trip_id AND driver_id = $staff_id AND status = 'IN_PROGRESS'";

    if (mysqli_query($conn, $end_sql) && $conn->affected_rows > 0) {
        header('Location: ' . $back_dashboard);
        exit();
    } else {
        $error_message = 'Could not end trip. It may already be completed.';
    }
}

// ============================================================
// SECTION 6: FETCH STUDENTS (if trip is IN_PROGRESS)
// ============================================================

$students_rows = array();
$all_handled   = true;

if ($status === 'IN_PROGRESS') {
    $students_sql = "SELECT s.student_id, s.fname, s.lname, s.grade, rs.stop_name,
                            ts.boarded, ts.dropped, ts.absent
                     FROM trip_students ts
                     JOIN students s ON ts.student_id = s.student_id
                     JOIN student_assignments sa ON s.student_id = sa.student_id AND sa.is_active = 1
                     JOIN route_stops rs ON sa.stop_id = rs.stop_id
                     WHERE ts.trip_id = $trip_id
                     ORDER BY rs.stop_order ASC";
    $students_result = mysqli_query($conn, $students_sql);

    if ($students_result && mysqli_num_rows($students_result) > 0) {
        while ($row = mysqli_fetch_assoc($students_result)) {
            $students_rows[] = $row;
            $is_done = ($trip_type === 'morning') ? ($row['boarded'] == 1) : ($row['dropped'] == 1);
            if (!$is_done && !$row['absent']) {
                $all_handled = false;
            }
        }
    }
}

$show_form = ($status === 'PENDING' && is_null($trip_type) && empty($tracking_link));

$conn->close();

// ============================================================
// SECTION 7: LOAD THE VIEW (HTML display only)
// ============================================================

include 'start_trip_view.php';
