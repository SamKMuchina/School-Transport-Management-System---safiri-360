<?php
/**
 * student_assignments.php
 *
 * Transport Manager can assign students to routes and stops.
 *
 * Location  : transport_manager/student_assignments.php
 * Includes  : ../includes/db.php
 * Stylesheet: ../assets/css/style.css (linked in student_assignments_view.php)
 *
 * Access: TRANSPORT_MANAGER only
 *
 * Features:
 * - View unassigned students
 * - Assign students to route and stop
 * - Edit existing assignments
 * - Unassign students (deactivates assignment, preserves history)
 * - Stop dropdown updates when a route is picked, with no server
 *   round-trip: every route's stops are already rendered on the page
 *   (in hidden per-route <select> elements), and a small JS function
 *   just copies the right one's markup into the visible dropdown.
 *
 * Business Rules:
 * - Only ONE active assignment per student allowed
 * - Unassigning sets is_active = 0
 * - Stop must belong to selected route
 * - All entities must belong to this school
 *
 * Database tables used:
 * - students, student_assignments, routes, route_stops
 *
 * NOTE ON QUERIES: This file uses mysqli_query() instead of prepared
 * statements (per project requirement). Every value used in a query
 * here is a numeric ID (student_id, route_id, stop_id, assignment_id,
 * school_id), so every one is cast with (int) before use - there is
 * no free text in this file that needs escaping.
 *
 *
 * FILE STRUCTURE: This file contains ALL PHP logic - access control,
 * form handling, and database queries. It stores the results in plain
 * variables, then includes student_assignments_view.php, which contains
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
// SECTION 2: HANDLE ASSIGN STUDENT
// ============================================================

/*
 * Creates a new active assignment for a student.
 * Verifies student, route, and stop all belong to this school.
 * Prevents duplicate active assignments for same student.
 * Each check is a normal nested if/else - no goto.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'assign_student') {

    $student_id = (int)(isset($_POST['student_id']) ? $_POST['student_id'] : 0);
    $route_id   = (int)(isset($_POST['route_id'])   ? $_POST['route_id']   : 0);
    $stop_id    = (int)(isset($_POST['stop_id'])    ? $_POST['stop_id']    : 0);

    if (empty($student_id) || empty($route_id) || empty($stop_id)) {
        $error_message = 'All fields are required.';
    } else {

        // Verify student belongs to this school
        $student_check_sql = "SELECT student_id FROM students WHERE student_id = $student_id AND school_id = $school_id";
        $student_check_result = mysqli_query($conn, $student_check_sql);

        if (!$student_check_result || mysqli_num_rows($student_check_result) == 0) {
            $error_message = 'Invalid student access.';
        } else {

            // Verify route belongs to this school
            $route_check_sql = "SELECT route_id FROM routes WHERE route_id = $route_id AND school_id = $school_id";
            $route_check_result = mysqli_query($conn, $route_check_sql);

            if (!$route_check_result || mysqli_num_rows($route_check_result) == 0) {
                $error_message = 'Invalid route access.';
            } else {

                // Verify stop belongs to selected route
                $stop_check_sql = "SELECT stop_id FROM route_stops WHERE stop_id = $stop_id AND route_id = $route_id";
                $stop_check_result = mysqli_query($conn, $stop_check_sql);

                if (!$stop_check_result || mysqli_num_rows($stop_check_result) == 0) {
                    $error_message = 'Invalid stop for selected route.';
                } else {

                    // Check student has no active assignment
                    $active_check_sql = "SELECT assignment_id FROM student_assignments WHERE student_id = $student_id AND is_active = 1";
                    $active_check_result = mysqli_query($conn, $active_check_sql);

                    if ($active_check_result && mysqli_num_rows($active_check_result) > 0) {
                        $error_message = 'Student already has an active assignment. Please unassign first.';
                    } else {

                        // All checks passed - insert assignment
                        $insert_sql = "INSERT INTO student_assignments (student_id, route_id, stop_id, is_active, assigned_date) 
                                        VALUES ($student_id, $route_id, $stop_id, 1, CURDATE())";

                        if (mysqli_query($conn, $insert_sql)) {
                            $success_message = 'Student assigned successfully!';
                        } else {
                            $error_message = 'Failed to assign student. Please try again.';
                        }
                    }
                }
            }
        }
    }
}

// ============================================================
// SECTION 3: HANDLE EDIT ASSIGNMENT
// ============================================================

/*
 * Updates the route and stop for an existing active assignment.
 * Same nested if/else style as the assign handler above.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_assignment') {

    $assignment_id = (int)(isset($_POST['assignment_id']) ? $_POST['assignment_id'] : 0);
    $route_id      = (int)(isset($_POST['route_id'])      ? $_POST['route_id']      : 0);
    $stop_id       = (int)(isset($_POST['stop_id'])       ? $_POST['stop_id']       : 0);

    if (empty($assignment_id) || empty($route_id) || empty($stop_id)) {
        $error_message = 'All fields are required.';
    } else {

        // Verify assignment belongs to a student of this school
        $assignment_check_sql = "SELECT sa.assignment_id FROM student_assignments sa
                                  JOIN students s ON sa.student_id = s.student_id
                                  WHERE sa.assignment_id = $assignment_id AND s.school_id = $school_id AND sa.is_active = 1";
        $assignment_check_result = mysqli_query($conn, $assignment_check_sql);

        if (!$assignment_check_result || mysqli_num_rows($assignment_check_result) == 0) {
            $error_message = 'Invalid assignment access.';
        } else {

            // Verify route belongs to this school
            $route_check_sql = "SELECT route_id FROM routes WHERE route_id = $route_id AND school_id = $school_id";
            $route_check_result = mysqli_query($conn, $route_check_sql);

            if (!$route_check_result || mysqli_num_rows($route_check_result) == 0) {
                $error_message = 'Invalid route access.';
            } else {

                // Verify stop belongs to route
                $stop_check_sql = "SELECT stop_id FROM route_stops WHERE stop_id = $stop_id AND route_id = $route_id";
                $stop_check_result = mysqli_query($conn, $stop_check_sql);

                if (!$stop_check_result || mysqli_num_rows($stop_check_result) == 0) {
                    $error_message = 'Invalid stop for selected route.';
                } else {

                    // All checks passed - update assignment
                    $update_sql = "UPDATE student_assignments SET route_id = $route_id, stop_id = $stop_id 
                                    WHERE assignment_id = $assignment_id AND is_active = 1";

                    if (mysqli_query($conn, $update_sql)) {
                        $success_message = 'Assignment updated successfully!';
                    } else {
                        $error_message = 'Failed to update assignment. Please try again.';
                    }
                }
            }
        }
    }
}

// ============================================================
// SECTION 4: HANDLE UNASSIGN STUDENT
// ============================================================

/*
 * Deactivates an assignment (sets is_active = 0).
 * Does NOT delete - history is preserved.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'unassign_student') {

    $assignment_id = (int)(isset($_POST['assignment_id']) ? $_POST['assignment_id'] : 0);

    // Verify assignment belongs to this school
    $check_sql = "SELECT sa.assignment_id FROM student_assignments sa
                   JOIN students s ON sa.student_id = s.student_id
                   WHERE sa.assignment_id = $assignment_id AND s.school_id = $school_id";
    $check_result = mysqli_query($conn, $check_sql);

    if (!$check_result || mysqli_num_rows($check_result) == 0) {
        $error_message = 'Invalid assignment access.';
    } else {
        $update_sql = "UPDATE student_assignments SET is_active = 0 WHERE assignment_id = $assignment_id";

        if (mysqli_query($conn, $update_sql)) {
            $success_message = 'Student unassigned successfully!';
        } else {
            $error_message = 'Failed to unassign student. Please try again.';
        }
    }
}

// ============================================================
// SECTION 5: FETCH SUMMARY STATISTICS
// ============================================================

$total_students_sql = "SELECT COUNT(*) as count FROM students WHERE school_id = $school_id";
$total_students_result = mysqli_query($conn, $total_students_sql);
$total_students = mysqli_fetch_assoc($total_students_result)['count'];

$assigned_sql = "SELECT COUNT(DISTINCT sa.student_id) as count
                  FROM student_assignments sa
                  JOIN students s ON sa.student_id = s.student_id
                  WHERE sa.is_active = 1 AND s.school_id = $school_id";
$assigned_result = mysqli_query($conn, $assigned_sql);
$assigned_students = mysqli_fetch_assoc($assigned_result)['count'];
$unassigned_students = $total_students - $assigned_students;

// ============================================================
// SECTION 6: FETCH UNASSIGNED STUDENTS
// ============================================================

$unassigned_sql = "SELECT s.student_id, s.fname, s.lname, s.grade
                    FROM students s
                    LEFT JOIN student_assignments sa ON s.student_id = sa.student_id AND sa.is_active = 1
                    WHERE s.school_id = $school_id AND sa.assignment_id IS NULL
                    ORDER BY s.fname ASC";
$unassigned_result = mysqli_query($conn, $unassigned_sql);

// ============================================================
// SECTION 7: FETCH ASSIGNED STUDENTS GROUPED BY ROUTE
// ============================================================

/*
 * Fetch all routes with their assigned students.
 * Results stored in $routes_with_students array.
 * Each route contains an array of assigned students.
 */
$routes_with_students = array();

// First get all routes for this school
$routes_sql = "SELECT route_id, route_name, description FROM routes WHERE school_id = $school_id ORDER BY route_name ASC";
$routes_result = mysqli_query($conn, $routes_sql);

while ($route = mysqli_fetch_assoc($routes_result)) {
    // For each route fetch assigned students
    $route_id_int = (int)$route['route_id'];
    $students_sql = "SELECT sa.assignment_id, s.student_id, s.fname, s.lname, s.grade,
                             r.route_id, r.route_name, rs.stop_id, rs.stop_name
                      FROM student_assignments sa
                      INNER JOIN students    s  ON sa.student_id = s.student_id
                      INNER JOIN routes      r  ON sa.route_id   = r.route_id
                      INNER JOIN route_stops rs ON sa.stop_id    = rs.stop_id
                      WHERE sa.is_active = 1 AND sa.route_id = $route_id_int AND s.school_id = $school_id
                      ORDER BY rs.stop_order ASC, s.fname ASC";
    $students_result = mysqli_query($conn, $students_sql);
    $students_in_route = mysqli_fetch_all($students_result, MYSQLI_ASSOC);

    $routes_with_students[] = array(
        'route_id'    => $route['route_id'],
        'route_name'  => $route['route_name'],
        'description' => $route['description'],
        'students'    => $students_in_route
    );
}

// ============================================================
// SECTION 8: FETCH ROUTES FOR DROPDOWN
// ============================================================

$routes_dropdown_sql = "SELECT route_id, route_name FROM routes WHERE school_id = $school_id ORDER BY route_name ASC";
$routes_dropdown_result = mysqli_query($conn, $routes_dropdown_sql);

// Store routes in array for reuse in both modals
$routes_list = array();
while ($route = mysqli_fetch_assoc($routes_dropdown_result)) {
    $routes_list[] = $route;
}

// ============================================================
// SECTION 9: FETCH ALL STOPS, GROUPED BY ROUTE
// ============================================================

/*
 * Fetches every stop for every route in this school up front, keyed
 * by route_id. The view uses this to pre-render one hidden <select>
 * of stops per route. When the transport manager picks a route in
 * the Assign/Edit modal, a small JS function just copies the matching
 * hidden <select>'s already-rendered HTML into the visible Stop
 * dropdown - no server round-trip.
 */
$stops_by_route = array();
foreach ($routes_list as $route) {
    $route_id_int = (int)$route['route_id'];
    $stops_sql    = "SELECT stop_id, stop_name FROM route_stops WHERE route_id = $route_id_int ORDER BY stop_order ASC";
    $stops_result = mysqli_query($conn, $stops_sql);
    $stops_by_route[$route_id_int] = mysqli_fetch_all($stops_result, MYSQLI_ASSOC);
}

$conn->close();

// ============================================================
// SECTION 10: LOAD THE VIEW (HTML display only)
// ============================================================

include 'student_assignments_view.php';
