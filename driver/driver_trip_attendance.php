<?php
/**
 * driver_trip_attendance.php
 *
 * Displays the list of students assigned to a specific trip.
 * Read-only view for the DRIVER to see which students
 * are on the trip and their pickup/drop-off stops.
 *
 * Location  : driver/driver_trip_attendance.php
 * Includes  : ../includes/db.php
 *
 * Access: DRIVER only
 *
 * NOTE ON QUERIES: Uses mysqli_query() with (int) casting for all
 * numeric values. No prepared statements per project requirement.
 *
 * Database tables used:
 * - trips, routes, trip_students, students, student_assignments, route_stops
 *
 * FILE STRUCTURE: This file contains ALL PHP logic - access control
 * and database queries. It stores the results in plain variables,
 * then includes driver_trip_attendance_view.php, which contains ONLY
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

$user_id  = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];

// ============================================================
// SECTION 2: FETCH DRIVER ID
// ============================================================

$result    = mysqli_query($conn, "SELECT driver_id FROM drivers WHERE user_id = $user_id");
$drv       = mysqli_fetch_assoc($result);
if (!$drv) die("Driver record not found.");
$driver_id = (int)$drv['driver_id'];

// ============================================================
// SECTION 3: GET AND VERIFY TRIP
// ============================================================

/*
 * trip_id comes from the URL as a GET parameter.
 * (int) cast ensures it can only ever be a number.
 * We verify the trip is assigned to this driver before showing anything.
 */
$trip_id = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;

if ($trip_id <= 0) {
    die("Invalid trip ID.");
}

$trip_sql    = "SELECT t.trip_id, t.trip_date, t.route_id, r.route_name
                FROM trips t
                JOIN routes r ON t.route_id = r.route_id
                WHERE t.trip_id = $trip_id AND t.driver_id = $driver_id";
$trip_result = mysqli_query($conn, $trip_sql);
$trip        = mysqli_fetch_assoc($trip_result);

if (!$trip) {
    die("Trip not found or not assigned to you.");
}

$route_id = (int)$trip['route_id'];

// ============================================================
// SECTION 4: FETCH STUDENTS FOR THIS TRIP
// ============================================================

/*
 * Fetches all students on this trip ordered by their stop order
 * so the list matches the physical sequence of the route.
 */
$students_sql    = "SELECT s.fname, s.lname, s.grade, rs.stop_name, rs.stop_order
                    FROM trip_students ts
                    JOIN students            s  ON ts.student_id = s.student_id
                    JOIN student_assignments sa ON s.student_id  = sa.student_id AND sa.is_active = 1
                    JOIN route_stops         rs ON sa.stop_id    = rs.stop_id
                    WHERE ts.trip_id = $trip_id
                    ORDER BY rs.stop_order ASC";
$students_result = mysqli_query($conn, $students_sql);

$conn->close();

// ============================================================
// SECTION 5: LOAD THE VIEW (HTML display only)
// ============================================================

include 'driver_trip_attendance_view.php';
