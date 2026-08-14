<?php
/**
 * manage_routes.php
 *
 * Transport Manager can register, search and edit routes.
 *
 * Location  : transport_manager/manage_routes.php
 * Includes  : ../includes/db.php
 * Stylesheet: ../assets/css/style.css (linked in manage_routes_view.php)
 *
 * Access: TRANSPORT_MANAGER only
 *
 * Features:
 * - Register new routes with name and description
 * - Search routes by name (blank by default until search is done)
 * - Edit route name and description
 * - Delete a route (blocked if it's in use)
 * - Prevent duplicate route names within the same school
 *
 * Business Rules:
 * - Table is blank by default - only shows results after a search
 * - Searching by route name uses LIKE with wildcard on both sides
 *   so partial names also return results
 * - A route cannot be deleted if any trip (past or present) references
 *   it, or if any student is currently assigned to it - this protects
 *   trip history and prevents orphaning active assignments
 *
 * Database tables used:
 * - routes
 *
 * NOTE ON QUERIES: Uses mysqli_query() with (int) casting for numeric
 * values (school_id, route_id) and mysqli_real_escape_string() for
 * text values (route_name, description, search term).
 *
 * FILE STRUCTURE: PHP logic at the top, then includes
 * manage_routes_view.php for the HTML display.
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
// SECTION 2: HANDLE ADD ROUTE
// ============================================================

/*
 * Inserts a new route into the routes table.
 * Checks for duplicate route names within the same school first.
 * Description is optional - stored as empty string if not provided.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_route') {

    $route_name  = trim(isset($_POST['route_name'])  ? $_POST['route_name']  : '');
    $description = trim(isset($_POST['description']) ? $_POST['description'] : '');

    if (empty($route_name)) {
        $error_message = 'Route name is required.';
    } else {

        $route_name_safe  = mysqli_real_escape_string($conn, $route_name);
        $description_safe = mysqli_real_escape_string($conn, $description);

        // Check if this route name already exists for this school
        $check_sql    = "SELECT route_id FROM routes WHERE school_id = $school_id AND route_name = '$route_name_safe'";
        $check_result = mysqli_query($conn, $check_sql);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $error_message = 'A route with this name already exists.';
        } else {
            $insert_sql = "INSERT INTO routes (school_id, route_name, description) VALUES ($school_id, '$route_name_safe', '$description_safe')";
            if (mysqli_query($conn, $insert_sql)) {
                $success_message = 'Route added successfully!';
            } else {
                $error_message = 'Failed to add route. Please try again.';
            }
        }
    }
}

// ============================================================
// SECTION 3: HANDLE EDIT ROUTE
// ============================================================

/*
 * Updates route name and description for an existing route.
 * Duplicate check excludes the current route from the comparison
 * so the route can be saved with its own existing name.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_route') {

    $route_id    = (int)(isset($_POST['route_id'])    ? $_POST['route_id']    : 0);
    $route_name  = trim(isset($_POST['route_name'])   ? $_POST['route_name']  : '');
    $description = trim(isset($_POST['description'])  ? $_POST['description'] : '');

    if (empty($route_name)) {
        $error_message = 'Route name is required.';
    } else {

        $route_name_safe  = mysqli_real_escape_string($conn, $route_name);
        $description_safe = mysqli_real_escape_string($conn, $description);

        // Check duplicate name - exclude the current route_id from the check
        $check_sql    = "SELECT route_id FROM routes WHERE school_id = $school_id AND route_name = '$route_name_safe' AND route_id != $route_id";
        $check_result = mysqli_query($conn, $check_sql);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $error_message = 'A route with this name already exists.';
        } else {
            $update_sql = "UPDATE routes SET route_name = '$route_name_safe', description = '$description_safe'
                            WHERE route_id = $route_id AND school_id = $school_id";
            if (mysqli_query($conn, $update_sql)) {
                $success_message = 'Route updated successfully!';
            } else {
                $error_message = 'Failed to update route. Please try again.';
            }
        }
    }
}

// ============================================================
// SECTION 4: HANDLE DELETE ROUTE
// ============================================================

/*
 * Deletes a route, but only if it is not currently "in use":
 *   - blocked if any trip (of any status, including history) references it
 *   - blocked if any student is actively assigned to it
 * If neither of those apply, the route's own stops are deleted first
 * (they have no meaning without the route), then the route itself.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_route') {

    $route_id = (int)(isset($_POST['route_id']) ? $_POST['route_id'] : 0);

    if (empty($route_id)) {
        $error_message = 'Invalid route.';
    } else {

        // Verify the route belongs to this school
        $check_sql    = "SELECT route_id FROM routes WHERE route_id = $route_id AND school_id = $school_id";
        $check_result = mysqli_query($conn, $check_sql);

        if (!$check_result || mysqli_num_rows($check_result) == 0) {
            $error_message = 'Route not found.';
        } else {

            // Block deletion if any trip (past or present) uses this route
            $trip_check_sql    = "SELECT trip_id FROM trips WHERE route_id = $route_id LIMIT 1";
            $trip_check_result = mysqli_query($conn, $trip_check_sql);

            // Block deletion if any student is actively assigned to this route
            $assignment_check_sql    = "SELECT assignment_id FROM student_assignments WHERE route_id = $route_id AND is_active = 1 LIMIT 1";
            $assignment_check_result = mysqli_query($conn, $assignment_check_sql);

            if ($trip_check_result && mysqli_num_rows($trip_check_result) > 0) {
                $error_message = 'This route cannot be deleted because it has trips (past or present) linked to it.';
            } elseif ($assignment_check_result && mysqli_num_rows($assignment_check_result) > 0) {
                $error_message = 'This route cannot be deleted because students are currently assigned to it.';
            } else {

                $conn->begin_transaction();

                try {
                    // Remove the route's own stops first - they have no
                    // meaning without the route they belong to
                    $delete_stops_sql = "DELETE FROM route_stops WHERE route_id = $route_id";
                    if (!mysqli_query($conn, $delete_stops_sql)) throw new Exception('Failed to remove route stops.');

                    $delete_route_sql = "DELETE FROM routes WHERE route_id = $route_id AND school_id = $school_id";
                    if (!mysqli_query($conn, $delete_route_sql)) throw new Exception('Failed to delete route.');

                    $conn->commit();
                    $success_message = 'Route deleted successfully.';

                } catch (Exception $e) {
                    $conn->rollback();
                    $error_message = $e->getMessage();
                }
            }
        }
    }
}

// ============================================================
// SECTION 5: FETCH TOTAL COUNT AND SEARCH
// ============================================================

/*
 * Total count shown in the section title regardless of search state.
 * $search_performed is false by default (no search done yet) - the
 * view shows a blank state prompting the user to search first.
 * When a search term is present, results are filtered using LIKE
 * with % wildcard on both sides so partial route names also match.
 */
$count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM routes WHERE school_id = $school_id");
$row          = mysqli_fetch_assoc($count_result);
$total_routes = $row['count'];

$search_query      = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_performed  = !empty($search_query);
$routes_list       = array();

if ($search_performed) {
    $search_safe   = mysqli_real_escape_string($conn, $search_query);
    $routes_sql    = "SELECT route_id, route_name, description FROM routes
                       WHERE school_id = $school_id AND route_name LIKE '%$search_safe%'
                       ORDER BY route_name ASC";
    $routes_result = mysqli_query($conn, $routes_sql);

    while ($route = mysqli_fetch_assoc($routes_result)) {
        $routes_list[] = $route;
    }
}

$conn->close();

// ============================================================
// SECTION 6: LOAD THE VIEW (HTML display only)
// ============================================================

include 'manage_routes_view.php';
