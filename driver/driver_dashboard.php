<?php
/**
 * driver_dashboard.php
 *
 * Main dashboard for the DRIVER role.
 * Same changes as attendant dashboard:
 * - Stat cards removed
 * - Active trip is hero of the page
 * - Upcoming trips shows today only
 * - Recently completed trips removed
 *
 * Location  : driver/driver_dashboard.php
 * Access    : DRIVER only
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'DRIVER') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db.php';

$user_id  = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];

$result    = mysqli_query($conn, "SELECT driver_id, fname FROM drivers WHERE user_id = $user_id");
$driver    = mysqli_fetch_assoc($result);
if (!$driver) die("Driver record not found.");
$driver_id  = (int)$driver['driver_id'];
$first_name = $driver['fname'];

// Fetch active trip
$active_trip_id = isset($_GET['active_trip_id']) ? (int)$_GET['active_trip_id'] : 0;

if (!$active_trip_id) {
    $next_result = mysqli_query($conn, "SELECT trip_id FROM trips WHERE driver_id = $driver_id AND status IN ('PENDING','IN_PROGRESS') ORDER BY trip_date ASC, start_time ASC LIMIT 1");
    $next_row    = mysqli_fetch_assoc($next_result);
    if ($next_row) $active_trip_id = (int)$next_row['trip_id'];
}

$active_trip = null;
if ($active_trip_id) {
    $trip_result = mysqli_query($conn, "SELECT t.trip_id, t.status, t.trip_date, v.plate_no AS vehicle_plate,
                                               r.route_name, CONCAT(a.fname, ' ', a.lname) AS attendant_name
                                        FROM trips t
                                        JOIN vehicles   v ON t.vehicle_id   = v.vehicle_id
                                        JOIN routes     r ON t.route_id     = r.route_id
                                        JOIN attendants a ON t.attendant_id = a.attendant_id
                                        WHERE t.trip_id = $active_trip_id AND t.driver_id = $driver_id");
    $active_trip = mysqli_fetch_assoc($trip_result);
}

// Today's upcoming trips only
$upcoming_result = mysqli_query($conn, "SELECT t.trip_id, t.trip_date, t.status, r.route_name, v.plate_no,
                                               a.fname AS attendant_fname, a.lname AS attendant_lname,
                                               (SELECT COUNT(*) FROM trip_students WHERE trip_id = t.trip_id) AS student_count
                                        FROM trips t
                                        JOIN routes     r ON t.route_id     = r.route_id
                                        JOIN vehicles   v ON t.vehicle_id   = v.vehicle_id
                                        JOIN attendants a ON t.attendant_id = a.attendant_id
                                        WHERE t.driver_id = $driver_id
                                        AND t.status IN ('PENDING','IN_PROGRESS')
                                        AND t.trip_date = CURDATE()
                                        ORDER BY t.start_time ASC");

$conn->close();

include 'driver_dashboard_view.php';
