<?php
/**
 * upcoming_trips.php
 *
 * Shows PENDING and IN_PROGRESS trips for the next 7 days (rolling window).
 *
 * Location  : attendant/upcoming_trips.php
 * Includes  : ../includes/db.php
 *
 * Access: ATTENDANT only
 *
 * CHANGE: Rolling 7-day window - always shows today + next 6 days.
 * As days pass, the window rolls forward automatically.
 * Completed trips drop off as they are completed.
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ATTENDANT') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db.php';

$user_id = (int)$_SESSION['user_id'];

$result       = mysqli_query($conn, "SELECT attendant_id FROM attendants WHERE user_id = $user_id");
$attendant    = mysqli_fetch_assoc($result);
if (!$attendant) die("Attendant record not found.");
$attendant_id = (int)$attendant['attendant_id'];

// ============================================================
// FETCH UPCOMING TRIPS - ROLLING 7 DAY WINDOW
// ============================================================

/*
 * BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 DAY)
 * = today + the next 6 days = always 7 days total.
 * As each day passes this window rolls forward automatically.
 * Only PENDING and IN_PROGRESS trips are shown - completed
 * trips drop off as soon as they are marked complete.
 */
$trips_sql = "SELECT t.trip_id, t.trip_date, t.status, r.route_name, v.plate_no,
                     d.fname AS driver_fname, d.lname AS driver_lname,
                     (SELECT COUNT(*) FROM trip_students WHERE trip_id = t.trip_id) AS student_count
              FROM trips t
              JOIN routes   r ON t.route_id   = r.route_id
              JOIN vehicles v ON t.vehicle_id = v.vehicle_id
              JOIN drivers  d ON t.driver_id  = d.driver_id
              WHERE t.attendant_id = $attendant_id
              AND t.status IN ('PENDING','IN_PROGRESS')
              AND t.trip_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 DAY)
              ORDER BY t.trip_date ASC, t.start_time ASC";

$trips_result  = mysqli_query($conn, $trips_sql);
$total_upcoming = $trips_result ? mysqli_num_rows($trips_result) : 0;

$conn->close();

include 'upcoming_trips_view.php';
