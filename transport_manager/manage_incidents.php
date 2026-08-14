<?php
/**
 * manage_incidents.php
 *
 * Transport Manager can report incidents for specific trips.
 *
 * Location  : transport_manager/manage_incidents.php
 * Includes  : ../includes/db.php
 * Stylesheet: ../assets/css/style.css (linked in manage_incidents_view.php)
 *
 * Access: TRANSPORT_MANAGER only
 *
 * HOW IT WORKS:
 * 1. Manager enters a date and clicks Filter
 * 2. Page reloads showing only trips for that date in a dropdown
 * 3. Manager selects a trip, fills in type and description, submits
 *
 * Database tables used:
 * - incidents, trips, routes, vehicles
 *
 * NOTE ON QUERIES: Uses mysqli_query() with (int) casting for numeric
 * values and mysqli_real_escape_string() for text values.
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

$school_id       = (int)$_SESSION['school_id'];
$user_id         = (int)$_SESSION['user_id'];
$username        = $_SESSION['username'];
$success_message = '';
$error_message   = '';

// ============================================================
// SECTION 2: HANDLE CREATE INCIDENT
// ============================================================

/*
 * Inserts a new incident record into the incidents table.
 * Verifies the selected trip belongs to this school before inserting.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_incident') {

    $trip_id       = (int)(isset($_POST['trip_id'])       ? $_POST['trip_id']       : 0);
    $incident_type = trim(isset($_POST['incident_type'])   ? $_POST['incident_type'] : '');
    $description   = trim(isset($_POST['description'])     ? $_POST['description']   : '');

    if (empty($trip_id) || empty($incident_type) || empty($description)) {
        $error_message = 'All fields are required.';
    } else {

        // Verify the selected trip belongs to this school
        $trip_check_sql    = "SELECT trip_id FROM trips WHERE trip_id = $trip_id AND school_id = $school_id";
        $trip_check_result = mysqli_query($conn, $trip_check_sql);

        if (!$trip_check_result || mysqli_num_rows($trip_check_result) == 0) {
            $error_message = 'Invalid trip selected.';
        } else {

            $incident_type_safe = mysqli_real_escape_string($conn, $incident_type);
            $description_safe   = mysqli_real_escape_string($conn, $description);

            $insert_sql = "INSERT INTO incidents (trip_id, reported_by, incident_type, description, reported_at)
                            VALUES ($trip_id, $user_id, '$incident_type_safe', '$description_safe', NOW())";

            if (mysqli_query($conn, $insert_sql)) {
                $success_message = 'Incident reported successfully!';
            } else {
                $error_message = 'Failed to report incident. Please try again.';
            }
        }
    }
}

// ============================================================
// SECTION 3: DATE FILTER AND TRIPS DROPDOWN
// ============================================================

/*
 * When a date is submitted via GET, fetch trips for that date.
 * $trips_list is empty by default - the dropdown shows no trips
 * until the manager enters a date and clicks Filter.
 */
$filter_date = isset($_GET['filter_date']) ? trim($_GET['filter_date']) : '';
$trips_list  = array();

if (!empty($filter_date)) {

    // Validate dd/mm/yyyy format
    $valid = false;
    if (strpos($filter_date, '/') !== false) {
        $comps = explode('/', $filter_date);
        if (count($comps) == 3 && is_numeric($comps[0]) && is_numeric($comps[1]) && is_numeric($comps[2])) {
            $valid = true;
        }
    }

    if ($valid) {
        // Convert dd/mm/yyyy to YYYY-MM-DD for the query
        $date_mysql = $comps[2] . '-' . $comps[1] . '-' . $comps[0];
        $date_safe  = mysqli_real_escape_string($conn, $date_mysql);

        $trips_sql = "SELECT t.trip_id, t.trip_date, r.route_name, v.plate_no
                      FROM trips t
                      JOIN routes   r ON t.route_id   = r.route_id
                      JOIN vehicles v ON t.vehicle_id = v.vehicle_id
                      WHERE t.school_id = $school_id AND t.trip_date = '$date_safe'
                      ORDER BY t.trip_id ASC";
        $trips_result = mysqli_query($conn, $trips_sql);

        if ($trips_result) {
            while ($trip = mysqli_fetch_assoc($trips_result)) {
                $trips_list[] = $trip;
            }
        }
    } else {
        $error_message = 'Please enter a valid date in dd/mm/yyyy format.';
    }
}

$conn->close();

// ============================================================
// SECTION 4: LOAD THE VIEW (HTML display only)
// ============================================================

include 'manage_incidents_view.php';
