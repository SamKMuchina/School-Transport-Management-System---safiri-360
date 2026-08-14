<?php
/**
 * driver_reports.php - Driver Reports
 * Blank defaults, no CSV, shared date filter for both tabs simultaneously.
 * Access: DRIVER only
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'DRIVER') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db.php';

$user_id   = (int)$_SESSION['user_id'];
$result    = mysqli_query($conn, "SELECT driver_id FROM drivers WHERE user_id = $user_id");
$drv       = mysqli_fetch_assoc($result);
if (!$drv) die("Driver record not found.");
$driver_id = (int)$drv['driver_id'];
$username  = $_SESSION['username'];

function valid_date($d) {
    if (strpos($d, '/') === false) return false;
    $c = explode('/', $d);
    if (count($c) != 3) return false;
    if (!is_numeric($c[0]) || !is_numeric($c[1]) || !is_numeric($c[2])) return false;
    if ($c[0] > 31 || $c[1] > 12 || strlen($c[2]) != 4) return false;
    return true;
}
function to_mysql($d) { $c = explode('/', $d); return $c[2].'-'.$c[1].'-'.$c[0]; }

$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to   = isset($_GET['date_to'])   ? trim($_GET['date_to'])   : '';

$date_from_mysql = (!empty($date_from) && valid_date($date_from)) ? to_mysql($date_from) : '';
$date_to_mysql   = (!empty($date_to)   && valid_date($date_to))   ? to_mysql($date_to)   : '';

$active_tab     = isset($_GET['tab']) && $_GET['tab'] === 'incidents' ? 'incidents' : 'trips';
$filter_applied = !empty($date_from_mysql) && !empty($date_to_mysql);

$trips_data = array();
if ($filter_applied) {
    $df = mysqli_real_escape_string($conn, $date_from_mysql);
    $dt = mysqli_real_escape_string($conn, $date_to_mysql);
    $r  = mysqli_query($conn, "SELECT t.trip_date, r.route_name, v.plate_no, t.start_time, t.end_time, t.status,
                                       (SELECT COUNT(*) FROM trip_students WHERE trip_id = t.trip_id) AS student_count
                                FROM trips t
                                JOIN routes   r ON t.route_id   = r.route_id
                                JOIN vehicles v ON t.vehicle_id = v.vehicle_id
                                WHERE t.driver_id = $driver_id AND t.trip_date BETWEEN '$df' AND '$dt'
                                ORDER BY t.trip_date DESC, t.start_time DESC");
    if ($r) $trips_data = mysqli_fetch_all($r, MYSQLI_ASSOC);
}

$incidents_data = array();
if ($filter_applied) {
    $df = mysqli_real_escape_string($conn, $date_from_mysql);
    $dt = mysqli_real_escape_string($conn, $date_to_mysql);
    $r  = mysqli_query($conn, "SELECT i.reported_at, t.trip_date, r.route_name, i.incident_type, i.description
                                FROM incidents i
                                JOIN trips  t ON i.trip_id  = t.trip_id
                                JOIN routes r ON t.route_id = r.route_id
                                WHERE i.reported_by = $user_id AND DATE(i.reported_at) BETWEEN '$df' AND '$dt'
                                ORDER BY i.reported_at DESC");
    if ($r) $incidents_data = mysqli_fetch_all($r, MYSQLI_ASSOC);
}

$conn->close();
include 'driver_reports_view.php';
