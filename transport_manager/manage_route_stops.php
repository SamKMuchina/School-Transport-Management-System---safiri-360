<?php
/**
 * manage_route_stops.php
 *
 * Transport Manager can manage stops for each route.
 *
 * Location  : transport_manager/manage_route_stops.php
 * Includes  : ../includes/db.php
 * Stylesheet: ../assets/css/style.css (linked in manage_route_stops_view.php)
 *
 * Access: TRANSPORT_MANAGER only
 *
 * Features:
 * - View routes with expandable stop lists
 * - Add stops to routes with auto-ordering
 * - Edit stop name and address
 * - Move stops up/down to reorder them
 * - Prevent duplicate stop names within same route
 *
 * Note: Drag and drop replaced with up/down buttons
 * to comply with no external library requirement.
 *
 * Database tables used:
 * - routes, route_stops
 *
 * NOTE ON QUERIES: This file uses mysqli_query() instead of prepared
 * statements (per project requirement). Numeric values (school_id,
 * route_id, stop_id, stop_order) are cast with (int) before use in a
 * query. Text values (stop_name, address) are passed through
 * mysqli_real_escape_string() before use in a query.
 *
 * FILE STRUCTURE: This file contains ALL PHP logic - access control,
 * form handling, and database queries. It stores the results in plain
 * variables, then includes manage_route_stops_view.php, which contains
 * ONLY the HTML display - no queries, no business logic.
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
$username        = $_SESSION['username'];
$success_message = '';
$error_message   = '';

// ============================================================
// SECTION 2: HANDLE MOVE STOP UP OR DOWN
// ============================================================

/*
 * Swaps the stop_order of two adjacent stops.
 * Direction: 'up' swaps with previous stop, 'down' swaps with next stop.
 * Plain form POST, page reload - no AJAX, no JSON reply.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move_stop'])) {

    $stop_id   = (int)(isset($_POST['stop_id'])   ? $_POST['stop_id']   : 0);
    $route_id  = (int)(isset($_POST['route_id'])  ? $_POST['route_id']  : 0);
    $direction = isset($_POST['direction'])          ? $_POST['direction'] : '';

    // Verify route belongs to this school
    $route_check_sql = "SELECT route_id FROM routes WHERE route_id = $route_id AND school_id = $school_id";
    $route_check_result = mysqli_query($conn, $route_check_sql);

    if (!$route_check_result || mysqli_num_rows($route_check_result) === 0) {
        $error_message = 'Invalid route.';
    } else {

        // Get current stop order
        $current_sql = "SELECT stop_order FROM route_stops WHERE stop_id = $stop_id AND route_id = $route_id";
        $current_result = mysqli_query($conn, $current_sql);
        $current = mysqli_fetch_assoc($current_result);

        if (!$current) {
            $error_message = 'Stop not found.';
        } else {

            $current_order = (int)$current['stop_order'];

            // Find the adjacent stop to swap with
            if ($direction === 'up') {
                $adjacent_sql = "SELECT stop_id, stop_order FROM route_stops 
                                  WHERE route_id = $route_id AND stop_order < $current_order 
                                  ORDER BY stop_order DESC LIMIT 1";
            } else {
                $adjacent_sql = "SELECT stop_id, stop_order FROM route_stops 
                                  WHERE route_id = $route_id AND stop_order > $current_order 
                                  ORDER BY stop_order ASC LIMIT 1";
            }

            $adjacent_result = mysqli_query($conn, $adjacent_sql);
            $adjacent = mysqli_fetch_assoc($adjacent_result);

            if (!$adjacent) {
                $error_message = 'Cannot move stop further in that direction.';
            } else {

                $adjacent_id    = (int)$adjacent['stop_id'];
                $adjacent_order = (int)$adjacent['stop_order'];

                // Swap the two stop orders
                $conn->begin_transaction();
                try {
                    $update_current_sql = "UPDATE route_stops SET stop_order = $adjacent_order WHERE stop_id = $stop_id AND route_id = $route_id";
                    mysqli_query($conn, $update_current_sql);

                    $update_adjacent_sql = "UPDATE route_stops SET stop_order = $current_order WHERE stop_id = $adjacent_id AND route_id = $route_id";
                    mysqli_query($conn, $update_adjacent_sql);

                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollback();
                    $error_message = 'Error updating order.';
                }
            }
        }
    }
}

// ============================================================
// SECTION 3: HANDLE ADD STOP
// ============================================================

/*
 * Adds a new stop to a route.
 * Auto-assigns the next sequential stop_order.
 * Verifies route belongs to this school.
 * Prevents duplicate stop names within same route.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stop'])) {

    $route_id  = (int)(isset($_POST['route_id'])  ? $_POST['route_id']  : 0);
    $stop_name = trim(isset($_POST['stop_name'])   ? $_POST['stop_name'] : '');
    $address   = trim(isset($_POST['address'])     ? $_POST['address']   : '');

    if (empty($stop_name) || empty($address)) {
        $error_message = 'Stop name and address are required.';
    } else {

        // Verify route belongs to this school
        $route_check_sql = "SELECT route_id FROM routes WHERE route_id = $route_id AND school_id = $school_id";
        $route_check_result = mysqli_query($conn, $route_check_sql);

        if (!$route_check_result || mysqli_num_rows($route_check_result) === 0) {
            $error_message = 'Invalid route selected.';
        } else {

            $stop_name_safe = mysqli_real_escape_string($conn, $stop_name);

            // Check for duplicate stop name in this route
            $dup_check_sql = "SELECT stop_id FROM route_stops WHERE route_id = $route_id AND stop_name = '$stop_name_safe'";
            $dup_check_result = mysqli_query($conn, $dup_check_sql);

            if ($dup_check_result && mysqli_num_rows($dup_check_result) > 0) {
                $error_message = 'This stop name already exists for this route.';
            } else {

                // Get next stop_order
                $max_order_sql = "SELECT MAX(stop_order) AS max_order FROM route_stops WHERE route_id = $route_id";
                $max_order_result = mysqli_query($conn, $max_order_sql);
                $max_order_row = mysqli_fetch_assoc($max_order_result);
                $next_order = ($max_order_row['max_order'] ?? 0) + 1;

                $address_safe = mysqli_real_escape_string($conn, $address);

                // Insert new stop
                $insert_sql = "INSERT INTO route_stops (route_id, stop_order, stop_name, address) 
                                VALUES ($route_id, $next_order, '$stop_name_safe', '$address_safe')";
                if (mysqli_query($conn, $insert_sql)) {
                    $success_message = 'Stop added successfully!';
                } else {
                    $error_message = 'Error adding stop. Please try again.';
                }
            }
        }
    }
}

// ============================================================
// SECTION 4: HANDLE EDIT STOP
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_stop'])) {

    $stop_id   = (int)(isset($_POST['stop_id'])   ? $_POST['stop_id']   : 0);
    $route_id  = (int)(isset($_POST['route_id'])  ? $_POST['route_id']  : 0);
    $stop_name = trim(isset($_POST['stop_name'])   ? $_POST['stop_name'] : '');
    $address   = trim(isset($_POST['address'])     ? $_POST['address']   : '');

    if (empty($stop_name) || empty($address)) {
        $error_message = 'Stop name and address are required.';
    } else {

        // Verify route belongs to this school
        $route_check_sql = "SELECT route_id FROM routes WHERE route_id = $route_id AND school_id = $school_id";
        $route_check_result = mysqli_query($conn, $route_check_sql);

        if (!$route_check_result || mysqli_num_rows($route_check_result) === 0) {
            $error_message = 'Invalid route selected.';
        } else {

            $stop_name_safe = mysqli_real_escape_string($conn, $stop_name);

            // Check for duplicate stop name excluding current
            $dup_check_sql = "SELECT stop_id FROM route_stops 
                               WHERE route_id = $route_id AND stop_name = '$stop_name_safe' AND stop_id != $stop_id";
            $dup_check_result = mysqli_query($conn, $dup_check_sql);

            if ($dup_check_result && mysqli_num_rows($dup_check_result) > 0) {
                $error_message = 'This stop name already exists for this route.';
            } else {
                $address_safe = mysqli_real_escape_string($conn, $address);

                $update_sql = "UPDATE route_stops SET stop_name = '$stop_name_safe', address = '$address_safe' 
                                WHERE stop_id = $stop_id AND route_id = $route_id";
                if (mysqli_query($conn, $update_sql)) {
                    $success_message = 'Stop updated successfully!';
                } else {
                    $error_message = 'Error updating stop. Please try again.';
                }
            }
        }
    }
}

// ============================================================
// SECTION 5: FETCH ALL ROUTES AND THEIR STOPS
// ============================================================

$routes      = array();
$route_stops = array();

$routes_sql = "SELECT route_id, route_name, description FROM routes WHERE school_id = $school_id ORDER BY route_name ASC";
$routes_result = mysqli_query($conn, $routes_sql);
while ($row = mysqli_fetch_assoc($routes_result)) {
    $routes[] = $row;
}

foreach ($routes as $route) {
    $route_id_int = (int)$route['route_id'];
    $stops_sql = "SELECT stop_id, stop_order, stop_name, address FROM route_stops WHERE route_id = $route_id_int ORDER BY stop_order ASC";
    $stops_result = mysqli_query($conn, $stops_sql);
    $route_stops[$route['route_id']] = mysqli_fetch_all($stops_result, MYSQLI_ASSOC);
}

// ============================================================
// SECTION 6: FETCH SUMMARY
// ============================================================

$count_sql = "SELECT COUNT(*) as count FROM routes WHERE school_id = $school_id";
$count_result = mysqli_query($conn, $count_sql);
$total_routes = mysqli_fetch_assoc($count_result)['count'];

$total_stops = 0;
foreach ($route_stops as $stops) {
    $total_stops += count($stops);
}

$conn->close();

// ============================================================
// SECTION 7: LOAD THE VIEW (HTML display only)
// ============================================================

include 'manage_route_stops_view.php';
