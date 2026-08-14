<?php
/**
 * driver_report_incident.php - Driver Report Incident
 * Simple form: date filter → trips dropdown → type → description.
 * Incident history removed entirely.
 * Access: DRIVER only
 */

function is_valid_date($d) {
    if (strpos($d, '/') === false) return false;
    $c = explode('/', $d);
    if (count($c) != 3) return false;
    if (!is_numeric($c[0]) || !is_numeric($c[1]) || !is_numeric($c[2])) return false;
    if ($c[0] > 31 || $c[1] > 12 || strlen($c[2]) != 4) return false;
    return true;
}
function to_mysql($d) { $c = explode('/', $d); return $c[2].'-'.$c[1].'-'.$c[0]; }

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'DRIVER') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db.php';

$user_id   = (int)$_SESSION['user_id'];
$username  = $_SESSION['username'];
$result    = mysqli_query($conn, "SELECT driver_id FROM drivers WHERE user_id = $user_id");
$drv       = mysqli_fetch_assoc($result);
if (!$drv) die("Driver record not found.");
$driver_id = (int)$drv['driver_id'];

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'report') {
    $trip_id       = (int)(isset($_POST['trip_id'])       ? $_POST['trip_id']       : 0);
    $incident_type = trim(isset($_POST['incident_type'])   ? $_POST['incident_type'] : '');
    $description   = trim(isset($_POST['description'])     ? $_POST['description']   : '');
    if (empty($trip_id) || empty($incident_type) || empty($description)) {
        $error = 'All fields are required.';
    } else {
        $r = mysqli_query($conn, "SELECT trip_id FROM trips WHERE trip_id = $trip_id AND driver_id = $driver_id");
        if (!$r || mysqli_num_rows($r) == 0) {
            $error = 'Invalid trip selected.';
        } else {
            $its = mysqli_real_escape_string($conn, $incident_type);
            $ds  = mysqli_real_escape_string($conn, $description);
            if (mysqli_query($conn, "INSERT INTO incidents (trip_id, reported_by, incident_type, description, reported_at) VALUES ($trip_id, $user_id, '$its', '$ds', NOW())")) {
                $success = 'Incident reported successfully!';
            } else {
                $error = 'Failed to report incident. Please try again.';
            }
        }
    }
}

$filter_date = isset($_GET['filter_date']) ? trim($_GET['filter_date']) : '';
$trips_list  = array();

if (!empty($filter_date) && is_valid_date($filter_date)) {
    $date_safe = mysqli_real_escape_string($conn, to_mysql($filter_date));
    $r = mysqli_query($conn, "SELECT t.trip_id, t.trip_date, r.route_name, v.plate_no
                               FROM trips t
                               JOIN routes   r ON t.route_id   = r.route_id
                               JOIN vehicles v ON t.vehicle_id = v.vehicle_id
                               WHERE t.driver_id = $driver_id AND t.trip_date = '$date_safe'
                               ORDER BY t.trip_id ASC");
    if ($r) { while ($row = mysqli_fetch_assoc($r)) $trips_list[] = $row; }
} elseif (!empty($filter_date)) {
    $error = 'Please enter a valid date in dd/mm/yyyy format.';
}

$conn->close();
include 'driver_report_incident_view.php';
