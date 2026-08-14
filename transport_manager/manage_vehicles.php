<?php
/**
 * manage_vehicles.php
 *
 * Transport Manager can register, search and edit vehicles.
 *
 * Location  : transport_manager/manage_vehicles.php
 * Includes  : ../includes/db.php
 * Stylesheet: ../assets/css/style.css (linked in manage_vehicles_view.php)
 *
 * Access: TRANSPORT_MANAGER only
 *
 * Features:
 * - Register new vehicles with plate number and capacity
 * - Search vehicles by plate number (blank by default until search is done)
 * - Edit vehicle plate number and capacity
 * - Prevent duplicate plate numbers within the same school
 *
 * Business Rules:
 * - Table is blank by default - only shows results after a search
 * - Searching by plate number uses LIKE with wildcard on both sides
 *   so partial plate numbers also return results
 * - Duplicate plate numbers are not allowed within the same school
 *
 * Database tables used:
 * - vehicles
 *
 * NOTE ON QUERIES: Uses mysqli_query() with (int) casting for numeric
 * values (school_id, vehicle_id, capacity) and mysqli_real_escape_string()
 * for text values (plate_no, search term).
 *
 * FILE STRUCTURE: PHP logic at the top, then includes
 * manage_vehicles_view.php for the HTML display. No queries or
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
// every query below is working with a plain number
$school_id       = (int)$_SESSION['school_id'];
$username        = $_SESSION['username'];
$success_message = '';
$error_message   = '';

// ============================================================
// SECTION 2: HANDLE ADD VEHICLE
// ============================================================

/*
 * Inserts a new vehicle into the vehicles table.
 * Checks for duplicate plate numbers within the same school first.
 * capacity is cast to (int) after the is_numeric check so it is
 * always stored as a clean integer.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_vehicle') {

    $plate_no = trim(isset($_POST['plate_no']) ? $_POST['plate_no'] : '');
    $capacity = trim(isset($_POST['capacity']) ? $_POST['capacity'] : '');

    if (empty($plate_no) || empty($capacity)) {
        $error_message = 'All fields are required.';
    } elseif (!is_numeric($capacity) || $capacity <= 0) {
        $error_message = 'Capacity must be a number greater than 0.';
    } else {

        $plate_no_safe = mysqli_real_escape_string($conn, $plate_no);
        $capacity_int  = (int)$capacity;

        // Check if this plate number already exists for this school
        $check_sql    = "SELECT vehicle_id FROM vehicles WHERE school_id = $school_id AND plate_no = '$plate_no_safe'";
        $check_result = mysqli_query($conn, $check_sql);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $error_message = 'A vehicle with this plate number already exists.';
        } else {
            $insert_sql = "INSERT INTO vehicles (school_id, plate_no, capacity) VALUES ($school_id, '$plate_no_safe', $capacity_int)";
            if (mysqli_query($conn, $insert_sql)) {
                $success_message = 'Vehicle registered successfully!';
            } else {
                $error_message = 'Failed to register vehicle. Please try again.';
            }
        }
    }
}

// ============================================================
// SECTION 3: HANDLE EDIT VEHICLE
// ============================================================

/*
 * Updates plate number and capacity for an existing vehicle.
 * Duplicate check excludes the current vehicle from the comparison
 * so the vehicle can be saved with its own existing plate number.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_vehicle') {

    $vehicle_id = (int)(isset($_POST['vehicle_id']) ? $_POST['vehicle_id'] : 0);
    $plate_no   = trim(isset($_POST['plate_no'])     ? $_POST['plate_no']   : '');
    $capacity   = trim(isset($_POST['capacity'])     ? $_POST['capacity']   : '');

    if (empty($plate_no) || empty($capacity)) {
        $error_message = 'All fields are required.';
    } elseif (!is_numeric($capacity) || $capacity <= 0) {
        $error_message = 'Capacity must be a number greater than 0.';
    } else {

        $plate_no_safe = mysqli_real_escape_string($conn, $plate_no);
        $capacity_int  = (int)$capacity;

        // Check duplicate plate - exclude the current vehicle_id from check
        $check_sql    = "SELECT vehicle_id FROM vehicles WHERE school_id = $school_id AND plate_no = '$plate_no_safe' AND vehicle_id != $vehicle_id";
        $check_result = mysqli_query($conn, $check_sql);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $error_message = 'A vehicle with this plate number already exists.';
        } else {
            $update_sql = "UPDATE vehicles SET plate_no = '$plate_no_safe', capacity = $capacity_int
                            WHERE vehicle_id = $vehicle_id AND school_id = $school_id";
            if (mysqli_query($conn, $update_sql)) {
                $success_message = 'Vehicle updated successfully!';
            } else {
                $error_message = 'Failed to update vehicle. Please try again.';
            }
        }
    }
}

// ============================================================
// SECTION 4: FETCH TOTAL COUNT AND SEARCH
// ============================================================

/*
 * Total count shown in the section title regardless of search state.
 * $vehicles_result is null by default (no search done yet) - the view
 * shows a blank state prompting the user to search first.
 * When a search term is present, results are filtered using LIKE
 * with % wildcard on both sides so partial plate numbers also match.
 */
$count_result   = mysqli_query($conn, "SELECT COUNT(*) as count FROM vehicles WHERE school_id = $school_id");
$row            = mysqli_fetch_assoc($count_result);
$total_vehicles = $row['count'];

$search_query   = isset($_GET['search']) ? trim($_GET['search']) : '';
$vehicles_result = null;

if (!empty($search_query)) {
    $search_safe     = mysqli_real_escape_string($conn, $search_query);
    $vehicles_sql    = "SELECT vehicle_id, plate_no, capacity FROM vehicles
                         WHERE school_id = $school_id AND plate_no LIKE '%$search_safe%'
                         ORDER BY plate_no ASC";
    $vehicles_result = mysqli_query($conn, $vehicles_sql);
}

$conn->close();

// ============================================================
// SECTION 5: LOAD THE VIEW (HTML display only)
// ============================================================

include 'manage_vehicles_view.php';
