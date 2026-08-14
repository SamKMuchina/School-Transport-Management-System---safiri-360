<?php
/**
 * attendant_dashboard.php
 *
 * Main dashboard for the ATTENDANT role.
 *
 * Location  : attendant/attendant_dashboard.php
 * Includes  : ../includes/db.php
 * Stylesheet: ../assets/css/style.css (linked in attendant_dashboard_view.php)
 *
 * Access: ATTENDANT only
 *
 * Features:
 * - Current Active Trip shown as the hero of the page
 * - Upcoming trips for TODAY only (not future days)
 * - Stat cards removed (Students Today, Upcoming Trips, Completed Trips)
 * - Recently Completed Trips section removed
 *
 * Database tables used:
 * - attendants, trips, routes, vehicles, drivers
 *
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ATTENDANT') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db.php';

$user_id = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch attendant record
$result       = mysqli_query($conn, "SELECT attendant_id, fname FROM attendants WHERE user_id = $user_id");
$attendant    = mysqli_fetch_assoc($result);
if (!$attendant) die("Attendant record not found.");
$attendant_id = (int)$attendant['attendant_id'];
$first_name   = $attendant['fname'];

// ============================================================
// SECTION 2: FETCH ACTIVE TRIP
// ============================================================

/*
 * Looks for the most recent PENDING or IN_PROGRESS trip assigned
 * to this attendant. This becomes the hero card on the dashboard.
 */
$active_trip_id = isset($_GET['active_trip_id']) ? (int)$_GET['active_trip_id'] : 0;

if (!$active_trip_id) {
    $next_sql    = "SELECT trip_id FROM trips WHERE attendant_id = $attendant_id AND status IN ('PENDING','IN_PROGRESS') ORDER BY trip_date ASC, start_time ASC LIMIT 1";
    $next_result = mysqli_query($conn, $next_sql);
    $next_row    = mysqli_fetch_assoc($next_result);
    if ($next_row) $active_trip_id = (int)$next_row['trip_id'];
}

$active_trip = null;
if ($active_trip_id) {
    $trip_sql    = "SELECT t.trip_id, t.status, t.trip_date, v.plate_no AS vehicle_plate,
                           r.route_name, CONCAT(d.fname, ' ', d.lname) AS driver_name
                    FROM trips t
                    JOIN vehicles v ON t.vehicle_id = v.vehicle_id
                    JOIN routes   r ON t.route_id   = r.route_id
                    JOIN drivers  d ON t.driver_id  = d.driver_id
                    WHERE t.trip_id = $active_trip_id AND t.attendant_id = $attendant_id";
    $trip_result = mysqli_query($conn, $trip_sql);
    $active_trip = mysqli_fetch_assoc($trip_result);
}

// ============================================================
// SECTION 3: FETCH TODAY'S UPCOMING TRIPS
// ============================================================

/*
 * Shows only PENDING and IN_PROGRESS trips for TODAY.
 * Not future days - just today so the attendant knows
 * what is coming up for the rest of the current day.
 */
$upcoming_sql    = "SELECT t.trip_id, t.trip_date, t.status, r.route_name, v.plate_no,
                           d.fname AS driver_fname, d.lname AS driver_lname,
                           (SELECT COUNT(*) FROM trip_students WHERE trip_id = t.trip_id) AS student_count
                    FROM trips t
                    JOIN routes   r ON t.route_id   = r.route_id
                    JOIN vehicles v ON t.vehicle_id = v.vehicle_id
                    JOIN drivers  d ON t.driver_id  = d.driver_id
                    WHERE t.attendant_id = $attendant_id
                    AND t.status IN ('PENDING','IN_PROGRESS')
                    AND t.trip_date = CURDATE()
                    ORDER BY t.start_time ASC";
$upcoming_result = mysqli_query($conn, $upcoming_sql);

$conn->close();

include 'attendant_dashboard_view.php';
