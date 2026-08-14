<?php
/**
 * manage_trips.php
 *
 * Transport Manager can create, search, edit and delete trips.
 *
 * Location  : transport_manager/manage_trips.php
 * Includes  : ../includes/db.php
 * Stylesheet: ../assets/css/style.css (linked in manage_trips_view.php)
 *
 * Access: TRANSPORT_MANAGER only
 *
 * Features:
 * - Create new trips for a date range with vehicle, route, driver, attendant
 * - Auto-assigns students from the selected route to each trip created
 * - Search trips by date to find PENDING trips only
 * - Edit a PENDING trip (change vehicle, route, driver, attendant, date)
 * - Delete a PENDING trip (also removes its trip_students records)
 *
 * Business Rules:
 * - Only PENDING trips can be edited or deleted
 * - IN_PROGRESS and COMPLETED trips do not appear in search results
 * - Deleting a trip first removes its trip_students records to avoid
 *   orphaned data, then removes the trip itself
 * - Create Trip cannot use a start date before today (checked against
 *   date_from only - since date_to must be on or after date_from,
 *   checking date_from covers the whole range)
 *
 * DATE FORMAT: Users enter dates as dd/mm/yyyy (matching the supervisor's
 * validation blueprint). PHP converts to YYYY-MM-DD before saving to MySQL
 * using convert_to_mysql_date(). Dates from MySQL are converted back to
 * dd/mm/yyyy for display using convert_to_display_date().
 *
 * Database tables used:
 * - trips, trip_students, vehicles, routes, drivers, attendants,
 *   student_assignments
 *
 * NOTE ON QUERIES: Uses mysqli_query() with (int) casting for numeric
 * values and mysqli_real_escape_string() for text values.
 *
 * FILE STRUCTURE: PHP logic at the top, then includes manage_trips_view.php
 * for the HTML display. No queries or business logic in the view file.
 */

// ============================================================
// SECTION 1: DATE HELPER FUNCTIONS
// ============================================================

/*
 * Checks that a date string looks like dd/mm/yyyy.
 * Uses the split("/") approach from the supervisor's validation document:
 * - Checks the "/" separator is present
 * - Splits into 3 components and checks each one
 * - Checks all components are numbers using is_numeric()
 * - Checks day is not greater than 31
 * - Checks month is not greater than 12
 * Returns true if the format looks correct, false otherwise.
 */
function is_valid_date_format($date_text) {
    if (strpos($date_text, '/') === false) return false;
    $comps = explode('/', $date_text);
    if (count($comps) != 3) return false;
    if (strlen($comps[0]) < 1 || strlen($comps[1]) < 1 || strlen($comps[2]) != 4) return false;
    if (!is_numeric($comps[0]) || !is_numeric($comps[1]) || !is_numeric($comps[2])) return false;
    if ($comps[0] > 31) return false;
    if ($comps[1] > 12) return false;
    return true;
}

/*
 * Converts dd/mm/yyyy (what the user typed) to YYYY-MM-DD (what MySQL
 * needs). Called before any date is placed in a database query.
 */
function convert_to_mysql_date($date_text) {
    $comps = explode('/', $date_text);
    return $comps[2] . '-' . $comps[1] . '-' . $comps[0];
}

/*
 * Converts YYYY-MM-DD (what comes back from MySQL) to dd/mm/yyyy
 * (what the user sees). Called when displaying a saved date in a form.
 */
function convert_to_display_date($date_text) {
    $comps = explode('-', $date_text);
    return $comps[2] . '/' . $comps[1] . '/' . $comps[0];
}

// ============================================================
// SECTION 2: SESSION & ACCESS CONTROL
// ============================================================

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TRANSPORT_MANAGER') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db.php';

// (int) cast ensures school_id is always a plain number in every query
$school_id       = (int)$_SESSION['school_id'];
$username        = $_SESSION['username'];
$success_message = '';
$error_message   = '';

// ============================================================
// SECTION 3: HANDLE CREATE TRIP
// ============================================================

/*
 * Creates one trip per day from date_from to date_to inclusive.
 * Verifies all selected resources (vehicle, route, driver, attendant)
 * belong to this school before creating anything.
 * Auto-assigns all active students on the selected route to each trip.
 * Uses a transaction so all trips and student assignments are created
 * together - if anything fails, nothing is saved.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_trip') {

    $vehicle_id   = (int)(isset($_POST['vehicle_id'])   ? $_POST['vehicle_id']   : 0);
    $route_id     = (int)(isset($_POST['route_id'])     ? $_POST['route_id']     : 0);
    $driver_id    = (int)(isset($_POST['driver_id'])    ? $_POST['driver_id']    : 0);
    $attendant_id = (int)(isset($_POST['attendant_id']) ? $_POST['attendant_id'] : 0);
    $date_from    = trim(isset($_POST['date_from'])      ? $_POST['date_from']    : '');
    $date_to      = trim(isset($_POST['date_to'])        ? $_POST['date_to']      : '');

    if (empty($vehicle_id) || empty($route_id) || empty($driver_id) || empty($attendant_id) || empty($date_from) || empty($date_to)) {
        $error_message = 'All fields are required.';
    } elseif (!is_valid_date_format($date_from) || !is_valid_date_format($date_to)) {
        $error_message = 'Dates must be in dd/mm/yyyy format.';
    } else {

        // Convert to MySQL format before using in queries
        $date_from_mysql = convert_to_mysql_date($date_from);
        $date_to_mysql   = convert_to_mysql_date($date_to);

        // Today's date in the same YYYY-MM-DD format, so it can be
        // compared directly against the dates entered by the user.
        $today_mysql = date('Y-m-d');

        if ($date_from_mysql < $today_mysql) {
            $error_message = 'Trip date cannot be in the past. Please select today or a future date.';
        } elseif ($date_to_mysql < $date_from_mysql) {
            $error_message = 'End date cannot be before start date.';
        } else {

            $conn->begin_transaction();

            try {

                // Verify all selections belong to this school
                $r = mysqli_query($conn, "SELECT vehicle_id FROM vehicles WHERE vehicle_id = $vehicle_id AND school_id = $school_id");
                if (!$r || mysqli_num_rows($r) == 0) throw new Exception('Invalid vehicle selected.');

                $r = mysqli_query($conn, "SELECT route_id FROM routes WHERE route_id = $route_id AND school_id = $school_id");
                if (!$r || mysqli_num_rows($r) == 0) throw new Exception('Invalid route selected.');

                $r = mysqli_query($conn, "SELECT driver_id FROM drivers WHERE driver_id = $driver_id AND school_id = $school_id");
                if (!$r || mysqli_num_rows($r) == 0) throw new Exception('Invalid driver selected.');

                $r = mysqli_query($conn, "SELECT attendant_id FROM attendants WHERE attendant_id = $attendant_id AND school_id = $school_id");
                if (!$r || mysqli_num_rows($r) == 0) throw new Exception('Invalid attendant selected.');

                // Fetch students assigned to the selected route
                $students_result = mysqli_query($conn, "SELECT student_id FROM student_assignments WHERE route_id = $route_id AND is_active = 1");
                $student_ids     = array();
                while ($row = mysqli_fetch_assoc($students_result)) {
                    $student_ids[] = (int)$row['student_id'];
                }
                $student_count = count($student_ids);

                // Loop through each day in the range and create one trip per day
                $trips_created = 0;
                $current_date  = $date_from_mysql;

                while ($current_date <= $date_to_mysql) {

                    $current_date_safe = mysqli_real_escape_string($conn, $current_date);

                    // Insert the trip with PENDING status
                    $insert_trip_sql = "INSERT INTO trips (school_id, vehicle_id, route_id, driver_id, attendant_id, trip_date, start_time, end_time, status)
                                         VALUES ($school_id, $vehicle_id, $route_id, $driver_id, $attendant_id, '$current_date_safe', NULL, NULL, 'PENDING')";
                    if (!mysqli_query($conn, $insert_trip_sql)) throw new Exception('Failed to create trip for ' . $current_date);

                    // Get the ID of the trip just inserted
                    $trip_id = $conn->insert_id;

                    // Assign each student to this trip
                    foreach ($student_ids as $student_id) {
                        $insert_student_sql = "INSERT INTO trip_students (trip_id, student_id, boarded, dropped) VALUES ($trip_id, $student_id, 0, 0)";
                        if (!mysqli_query($conn, $insert_student_sql)) throw new Exception('Failed to assign students to trip for ' . $current_date);
                    }

                    $trips_created++;

                    // Move to the next day
                    $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
                }

                $conn->commit();
                $success_message = $trips_created . ' trip(s) created successfully! ' . $student_count . ' students assigned to each trip.';

            } catch (Exception $e) {
                $conn->rollback();
                $error_message = $e->getMessage();
            }
        }
    }
}

// ============================================================
// SECTION 4: HANDLE EDIT TRIP
// ============================================================

/*
 * Updates vehicle, route, driver, attendant and trip_date for a trip.
 * Only PENDING trips can be edited - the query includes status = 'PENDING'
 * as a safety check so even if someone bypasses the UI they cannot
 * edit a trip that is already running or completed.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_trip') {

    $trip_id      = (int)(isset($_POST['trip_id'])      ? $_POST['trip_id']      : 0);
    $vehicle_id   = (int)(isset($_POST['vehicle_id'])   ? $_POST['vehicle_id']   : 0);
    $route_id     = (int)(isset($_POST['route_id'])     ? $_POST['route_id']     : 0);
    $driver_id    = (int)(isset($_POST['driver_id'])    ? $_POST['driver_id']    : 0);
    $attendant_id = (int)(isset($_POST['attendant_id']) ? $_POST['attendant_id'] : 0);
    $trip_date    = trim(isset($_POST['trip_date'])      ? $_POST['trip_date']    : '');

    if (empty($trip_id) || empty($vehicle_id) || empty($route_id) || empty($driver_id) || empty($attendant_id) || empty($trip_date)) {
        $error_message = 'All fields are required.';
    } elseif (!is_valid_date_format($trip_date)) {
        $error_message = 'Trip date must be in dd/mm/yyyy format.';
    } else {

        // Convert the display date to MySQL format before saving
        $trip_date_mysql = convert_to_mysql_date($trip_date);
        $trip_date_safe  = mysqli_real_escape_string($conn, $trip_date_mysql);

        // Only update if the trip is PENDING and belongs to this school
        $update_sql = "UPDATE trips SET vehicle_id = $vehicle_id, route_id = $route_id,
                                driver_id = $driver_id, attendant_id = $attendant_id,
                                trip_date = '$trip_date_safe'
                        WHERE trip_id = $trip_id AND school_id = $school_id AND status = 'PENDING'";

        if (mysqli_query($conn, $update_sql) && $conn->affected_rows > 0) {
            $success_message = 'Trip updated successfully!';
        } else {
            $error_message = 'Failed to update trip. It may no longer be pending.';
        }
    }
}

// ============================================================
// SECTION 5: HANDLE DELETE TRIP
// ============================================================

/*
 * Deletes a PENDING trip and all its trip_students records.
 * trip_students are deleted first to avoid orphaned records.
 * Uses a transaction so both deletions happen together.
 * Only PENDING trips belonging to this school can be deleted.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_trip') {

    $trip_id = (int)(isset($_POST['trip_id']) ? $_POST['trip_id'] : 0);

    if (empty($trip_id)) {
        $error_message = 'Invalid trip.';
    } else {

        // Verify the trip is PENDING and belongs to this school
        $check_sql    = "SELECT trip_id FROM trips WHERE trip_id = $trip_id AND school_id = $school_id AND status = 'PENDING'";
        $check_result = mysqli_query($conn, $check_sql);

        if (!$check_result || mysqli_num_rows($check_result) == 0) {
            $error_message = 'Trip not found or cannot be deleted.';
        } else {

            $conn->begin_transaction();

            try {
                // Delete student assignments for this trip first
                $delete_students_sql = "DELETE FROM trip_students WHERE trip_id = $trip_id";
                if (!mysqli_query($conn, $delete_students_sql)) throw new Exception('Failed to remove student assignments.');

                // Now delete the trip itself
                $delete_trip_sql = "DELETE FROM trips WHERE trip_id = $trip_id AND school_id = $school_id AND status = 'PENDING'";
                if (!mysqli_query($conn, $delete_trip_sql)) throw new Exception('Failed to delete trip.');

                $conn->commit();
                $success_message = 'Trip deleted successfully.';

            } catch (Exception $e) {
                $conn->rollback();
                $error_message = $e->getMessage();
            }
        }
    }
}

// ============================================================
// SECTION 6: SEARCH PENDING TRIPS BY DATE
// ============================================================

/*
 * Searches for PENDING trips on the date entered by the user.
 * Only PENDING trips are shown since those are the only ones
 * that can be edited or deleted.
 * $search_performed is false by default (or if the date is in the
 * wrong format) - the view shows a blank state until a valid search
 * date is submitted.
 */
$search_date       = isset($_GET['search_date']) ? trim($_GET['search_date']) : '';
$search_performed  = !empty($search_date) && is_valid_date_format($search_date);
$trips_list        = array();

if ($search_performed) {

    // Convert the user's dd/mm/yyyy input to YYYY-MM-DD for the query
    $search_date_mysql = convert_to_mysql_date($search_date);
    $search_date_safe  = mysqli_real_escape_string($conn, $search_date_mysql);

    $search_sql = "SELECT t.trip_id, t.trip_date, t.status,
                          r.route_name, v.plate_no,
                          d.fname AS driver_fname, d.lname AS driver_lname,
                          a.fname AS attendant_fname, a.lname AS attendant_lname,
                          t.vehicle_id, t.route_id, t.driver_id, t.attendant_id
                   FROM trips t
                   JOIN routes     r ON t.route_id     = r.route_id
                   JOIN vehicles   v ON t.vehicle_id   = v.vehicle_id
                   JOIN drivers    d ON t.driver_id    = d.driver_id
                   JOIN attendants a ON t.attendant_id = a.attendant_id
                   WHERE t.school_id = $school_id
                   AND t.trip_date = '$search_date_safe'
                   AND t.status = 'PENDING'
                   ORDER BY t.trip_id ASC";

    $search_result = mysqli_query($conn, $search_sql);

    while ($trip = mysqli_fetch_assoc($search_result)) {
        $trips_list[] = $trip;
    }
}

// ============================================================
// SECTION 7: FETCH DROPDOWN DATA FOR FORMS
// ============================================================

/*
 * Fetches vehicles, routes, drivers and attendants for this school
 * to populate the dropdowns in both the Create and Edit forms.
 */
$vehicles_result = mysqli_query($conn, "SELECT vehicle_id, plate_no FROM vehicles WHERE school_id = $school_id ORDER BY plate_no ASC");
$vehicles_list   = array();
while ($v = mysqli_fetch_assoc($vehicles_result)) { $vehicles_list[] = $v; }

$routes_result = mysqli_query($conn, "SELECT route_id, route_name FROM routes WHERE school_id = $school_id ORDER BY route_name ASC");
$routes_list   = array();
while ($r = mysqli_fetch_assoc($routes_result)) { $routes_list[] = $r; }

$drivers_result = mysqli_query($conn, "SELECT driver_id, fname, lname FROM drivers WHERE school_id = $school_id ORDER BY fname ASC");
$drivers_list   = array();
while ($d = mysqli_fetch_assoc($drivers_result)) { $drivers_list[] = $d; }

$attendants_result = mysqli_query($conn, "SELECT attendant_id, fname, lname FROM attendants WHERE school_id = $school_id ORDER BY fname ASC");
$attendants_list   = array();
while ($a = mysqli_fetch_assoc($attendants_result)) { $attendants_list[] = $a; }

$conn->close();

// ============================================================
// SECTION 8: LOAD THE VIEW (HTML display only)
// ============================================================

include 'manage_trips_view.php';
