<?php
/**
 * transport_manager_dashboard.php
 *
 * Main dashboard for the TRANSPORT_MANAGER role.
 *
 * Location  : transport_manager/transport_manager_dashboard.php
 * Includes  : ../includes/db.php
 * Stylesheet: ../assets/css/style.css (linked in transport_manager_dashboard_view.php)
 *
 * Access: TRANSPORT_MANAGER only
 *
 * Features:
 * - Summary stat cards: students, drivers, attendants, vehicles, routes, active trips
 * - Today's Trips table: all trips for today's date regardless of status
 *
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TRANSPORT_MANAGER') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db.php';

$school_id = (int)$_SESSION['school_id'];
$username  = $_SESSION['username'];

// ============================================================
// SECTION 2: FETCH SUMMARY STATISTICS
// ============================================================

$result         = mysqli_query($conn, "SELECT COUNT(*) as count FROM students WHERE school_id = $school_id");
$row            = mysqli_fetch_assoc($result);
$total_students = $row['count'];

$result        = mysqli_query($conn, "SELECT COUNT(*) as count FROM drivers WHERE school_id = $school_id");
$row           = mysqli_fetch_assoc($result);
$total_drivers = $row['count'];

$result           = mysqli_query($conn, "SELECT COUNT(*) as count FROM attendants WHERE school_id = $school_id");
$row              = mysqli_fetch_assoc($result);
$total_attendants = $row['count'];

$result         = mysqli_query($conn, "SELECT COUNT(*) as count FROM vehicles WHERE school_id = $school_id");
$row            = mysqli_fetch_assoc($result);
$total_vehicles = $row['count'];

$result       = mysqli_query($conn, "SELECT COUNT(*) as count FROM routes WHERE school_id = $school_id");
$row          = mysqli_fetch_assoc($result);
$total_routes = $row['count'];

$result       = mysqli_query($conn, "SELECT COUNT(*) as count FROM trips WHERE school_id = $school_id AND status = 'IN_PROGRESS'");
$row          = mysqli_fetch_assoc($result);
$active_trips = $row['count'];

// ============================================================
// SECTION 3: FETCH TODAY'S TRIPS
// ============================================================

/*
 * Fetches all trips for today's date regardless of status.
 * CURDATE() always matches today automatically.
 * Ordered: IN_PROGRESS first, then PENDING, then COMPLETED.
 */
$todays_trips_sql = "SELECT t.trip_id, t.trip_date, t.start_time, t.end_time, t.status,
                            r.route_name, v.plate_no,
                            d.fname AS driver_fname,    d.lname AS driver_lname,
                            a.fname AS attendant_fname, a.lname AS attendant_lname
                     FROM trips t
                     JOIN routes     r ON t.route_id     = r.route_id
                     JOIN vehicles   v ON t.vehicle_id   = v.vehicle_id
                     JOIN drivers    d ON t.driver_id    = d.driver_id
                     JOIN attendants a ON t.attendant_id = a.attendant_id
                     WHERE t.school_id = $school_id AND t.trip_date = CURDATE()
                     ORDER BY FIELD(t.status, 'IN_PROGRESS', 'PENDING', 'COMPLETED'), t.start_time ASC";

$todays_trips_result = mysqli_query($conn, $todays_trips_sql);

$conn->close();

// ============================================================
// SECTION 4: LOAD THE VIEW (HTML display only)
// ============================================================

include 'transport_manager_dashboard_view.php';
