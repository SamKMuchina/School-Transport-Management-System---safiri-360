<?php
/**
 * trip_monitoring.php
 *
 * Live trip monitoring dashboard for Transport Manager.
 *
 * Location  : transport_manager/trip_monitoring.php
 * Includes  : ../includes/db.php
 * Stylesheet: ../assets/css/style.css (linked in trip_monitoring_view.php)
 *
 * Access: TRANSPORT_MANAGER only
 *
 * Features:
 * - Shows only today's IN_PROGRESS trips (currently running trips)
 * - Blank state if no trips are currently running
 * - Each trip has a "View Attendance" link that reloads the page with
 *   ?view_trip=<trip_id> and shows live student pickup status in a
 *   modal, already rendered by PHP - no AJAX involved
 *
 * Business Rules:
 * - Only IN_PROGRESS trips for today's date are shown
 * - PENDING and COMPLETED trips do not appear on this page
 * - Attendance modal shows which students have been picked up (boarded)
 *   and which are still waiting, fed directly from trip_students table
 *   as updated by the driver/attendant on start_trip.php
 *
 * Database tables used:
 * - trips, routes, vehicles, drivers, attendants, trip_students, students
 *
 * NOTE ON QUERIES: Uses mysqli_query() with (int) casting for numeric
 * values ($school_id, $trip_id). No text input on this page.
 *
 * FILE STRUCTURE: PHP logic at the top, then includes
 * trip_monitoring_view.php for the HTML display. No queries or
 * business logic belong in the view file.
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

// (int) cast: school_id comes from the session but casting guarantees
// every query below is working with a plain number, not a string
$school_id = (int)$_SESSION['school_id'];
$username  = $_SESSION['username'];

// ============================================================
// SECTION 2: VIEW ATTENDANCE FOR A TRIP (?view_trip=trip_id)
// ============================================================

/*
 * When the Transport Manager clicks "View Attendance" on a trip row,
 * the page reloads with ?view_trip=<trip_id> in the URL - a plain
 * link, no AJAX. This block looks up that trip's students and their
 * pickup/drop-off status up front, so the modal below can be rendered
 * already open and already filled in, straight from PHP.
 *
 * trip_type determines the column label:
 *   morning trip -> shows "Boarded" status (was the student picked up?)
 *   evening trip -> shows "Dropped" status (was the student dropped off?)
 */
$view_trip_id     = isset($_GET['view_trip']) ? (int)$_GET['view_trip'] : 0;
$view_trip        = null;
$attendance_list  = array();

if ($view_trip_id > 0) {

    // Verify this trip belongs to this school and is IN_PROGRESS.
    // Also grab the route name for the modal's subtitle.
    $trip_check_sql = "SELECT t.trip_id, t.trip_type, r.route_name
                        FROM trips t
                        JOIN routes r ON t.route_id = r.route_id
                        WHERE t.trip_id = $view_trip_id
                        AND t.school_id = $school_id
                        AND t.status = 'IN_PROGRESS'";
    $trip_check_result = mysqli_query($conn, $trip_check_sql);
    $view_trip          = mysqli_fetch_assoc($trip_check_result);

    if ($view_trip) {

        // Fetch all students on this trip with their pickup/drop-off status.
        // Ordered by stop_order so students appear in the order the bus visits stops.
        $attendance_sql = "SELECT s.fname, s.lname, s.grade, rs.stop_name,
                                  ts.boarded, ts.dropped, ts.absent,
                                  ts.boarded_time, ts.dropped_time
                           FROM trip_students ts
                           JOIN students   s  ON ts.student_id = s.student_id
                           LEFT JOIN student_assignments sa ON s.student_id = sa.student_id AND sa.is_active = 1
                           LEFT JOIN route_stops rs ON sa.stop_id = rs.stop_id
                           WHERE ts.trip_id = $view_trip_id
                           ORDER BY rs.stop_order ASC, s.fname ASC";
        $attendance_result = mysqli_query($conn, $attendance_sql);

        while ($row = mysqli_fetch_assoc($attendance_result)) {
            $attendance_list[] = $row;
        }
    }
}

// ============================================================
// SECTION 3: FETCH TODAY'S IN_PROGRESS TRIPS
// ============================================================

/*
 * Fetches only IN_PROGRESS trips for today's date.
 * PENDING and COMPLETED trips are excluded - this page is for
 * monitoring trips that are currently running only.
 * CURDATE() always returns today's date automatically.
 */
$trips_sql = "SELECT t.trip_id, t.trip_date, t.start_time, t.trip_type,
                     r.route_name, v.plate_no,
                     d.fname AS driver_fname,    d.lname AS driver_lname,
                     a.fname AS attendant_fname, a.lname AS attendant_lname
              FROM trips t
              JOIN routes     r ON t.route_id     = r.route_id
              JOIN vehicles   v ON t.vehicle_id   = v.vehicle_id
              JOIN drivers    d ON t.driver_id    = d.driver_id
              JOIN attendants a ON t.attendant_id = a.attendant_id
              WHERE t.school_id = $school_id
              AND t.trip_date = CURDATE()
              AND t.status = 'IN_PROGRESS'
              ORDER BY t.start_time ASC";

$trips_result = mysqli_query($conn, $trips_sql);

$conn->close();

// ============================================================
// SECTION 4: LOAD THE VIEW (HTML display only)
// ============================================================

include 'trip_monitoring_view.php';
