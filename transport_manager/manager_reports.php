<?php
/**
 * manager_reports.php
 *
 * Transport Manager reports - 5 tabs, each self-contained with its own
 * filters. Access: TRANSPORT_MANAGER only.
 *
 * Tabs: Trip Summary (date range + filters + pagination, blank until
 * Filter clicked), Driver/Attendant Performance (date range + staff
 * picker + pagination), Student Attendance (name search, blank by
 * default), Incidents (single date + type filter, shows reporter's role).
 *
 * Dates are dd/mm/yyyy on screen, converted to YYYY-MM-DD for queries
 * via to_mysql(). Uses mysqli_query() throughout - no prepared statements.
 *
 * Includes manager_reports_view.php for the HTML display.
 */

// ============================================================
// SECTION 1: DATE HELPERS
// ============================================================

// Checks dd/mm/yyyy shape: "/" separator, 3 parts, numeric, day<=31, month<=12
function is_valid_date($d) {
    if (strpos($d, '/') === false) return false;
    $c = explode('/', $d);
    if (count($c) != 3) return false;
    if (strlen($c[0]) < 1 || strlen($c[1]) < 1 || strlen($c[2]) != 4) return false;
    if (!is_numeric($c[0]) || !is_numeric($c[1]) || !is_numeric($c[2])) return false;
    if ($c[0] > 31 || $c[1] > 12) return false;
    return true;
}

function to_mysql($d)   { $c = explode('/', $d); return $c[2] . '-' . $c[1] . '-' . $c[0]; }
function to_display($d) { $c = explode('-', $d); return $c[2] . '/' . $c[1] . '/' . $c[0]; }

// ============================================================
// SECTION 2: SESSION & ACCESS CONTROL
// ============================================================

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TRANSPORT_MANAGER') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db.php';

$school_id  = (int)$_SESSION['school_id'];
$username   = $_SESSION['username'];
$active_tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'trips';

// ============================================================
// SECTION 3: TOP DATE RANGE
// Shared by Trip Summary, Driver Performance, Attendant Performance.
// Other tabs use their own date field instead.
// ============================================================

$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to   = isset($_GET['date_to'])   ? trim($_GET['date_to'])   : '';

$date_from_mysql = (!empty($date_from) && is_valid_date($date_from)) ? to_mysql($date_from) : '';
$date_to_mysql   = (!empty($date_to)   && is_valid_date($date_to))   ? to_mysql($date_to)   : '';

// ============================================================
// SECTION 4: TRIP SUMMARY TAB
// Blank until Filter is clicked (filter_applied flag). 10 per page.
// ============================================================

$filter_route   = (int)(isset($_GET['route'])   ? $_GET['route']   : 0);
$filter_vehicle = (int)(isset($_GET['vehicle']) ? $_GET['vehicle'] : 0);
$filter_status  = isset($_GET['status'])        ? trim($_GET['status']) : '';
$filter_applied = isset($_GET['filter_applied']);

$per_page     = 10;
$current_page = (int)(isset($_GET['p']) ? $_GET['p'] : 1);
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $per_page;

$trips_data         = array();
$total_trips_count  = 0;
$total_pages        = 0;

if ($active_tab === 'trips' && $filter_applied) {

    // Build WHERE clause from whichever filters were submitted
    $where = "WHERE t.school_id = $school_id";
    if (!empty($date_from_mysql)) { $df = mysqli_real_escape_string($conn, $date_from_mysql); $where .= " AND t.trip_date >= '$df'"; }
    if (!empty($date_to_mysql))   { $dt = mysqli_real_escape_string($conn, $date_to_mysql);   $where .= " AND t.trip_date <= '$dt'"; }
    if ($filter_route > 0)   $where .= " AND t.route_id = $filter_route";
    if ($filter_vehicle > 0) $where .= " AND t.vehicle_id = $filter_vehicle";
    if (!empty($filter_status)) { $fs = mysqli_real_escape_string($conn, $filter_status); $where .= " AND t.status = '$fs'"; }

    // Count first (for pagination), then fetch this page only
    $count_result      = mysqli_query($conn, "SELECT COUNT(*) as total FROM trips t $where");
    $count_row         = mysqli_fetch_assoc($count_result);
    $total_trips_count = $count_row['total'];
    $total_pages       = ceil($total_trips_count / $per_page);

    $trips_sql = "SELECT t.trip_id, t.trip_date, r.route_name, v.plate_no,
                         CONCAT(d.fname, ' ', d.lname) AS driver,
                         CONCAT(a.fname, ' ', a.lname) AS attendant,
                         t.start_time, t.end_time, t.status,
                         COUNT(ts.student_id) AS total_students,
                         SUM(ts.boarded) AS boarded, SUM(ts.dropped) AS dropped, SUM(ts.absent) AS absent
                  FROM trips t
                  JOIN routes     r  ON t.route_id     = r.route_id
                  JOIN vehicles   v  ON t.vehicle_id   = v.vehicle_id
                  JOIN drivers    d  ON t.driver_id    = d.driver_id
                  JOIN attendants a  ON t.attendant_id = a.attendant_id
                  LEFT JOIN trip_students ts ON t.trip_id = ts.trip_id
                  $where
                  GROUP BY t.trip_id
                  ORDER BY t.trip_date DESC, t.start_time DESC
                  LIMIT $per_page OFFSET $offset";
    $result = mysqli_query($conn, $trips_sql);
    if ($result) $trips_data = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// ============================================================
// SECTION 5: DRIVER PERFORMANCE TAB
// Date range + pick a driver (plain link) + their trips, paginated.
// Uses 'dp' as the page parameter to avoid clashing with other tabs.
// ============================================================

$driver_list        = array();
$driver_trips        = array();
$driver_total        = 0;
$driver_total_pages  = 0;
$staff_id            = (int)(isset($_GET['staff_id']) ? $_GET['staff_id'] : 0);
$staff_role          = isset($_GET['staff_role'])      ? trim($_GET['staff_role']) : '';

$staff_date_from       = isset($_GET['staff_date_from']) ? trim($_GET['staff_date_from']) : '';
$staff_date_to         = isset($_GET['staff_date_to'])   ? trim($_GET['staff_date_to'])   : '';
$staff_date_from_mysql = (!empty($staff_date_from) && is_valid_date($staff_date_from)) ? to_mysql($staff_date_from) : '';
$staff_date_to_mysql   = (!empty($staff_date_to)   && is_valid_date($staff_date_to))   ? to_mysql($staff_date_to)   : '';

$driver_page   = (int)(isset($_GET['dp']) ? $_GET['dp'] : 1);
if ($driver_page < 1) $driver_page = 1;
$driver_offset = ($driver_page - 1) * $per_page;

if ($active_tab === 'driver_performance') {

    $result = mysqli_query($conn, "SELECT driver_id, fname, lname FROM drivers WHERE school_id = $school_id ORDER BY fname, lname");
    if ($result) $driver_list = mysqli_fetch_all($result, MYSQLI_ASSOC);

    if ($staff_id > 0 && $staff_role === 'driver') {
        $where = "WHERE t.driver_id = $staff_id AND t.school_id = $school_id";
        if (!empty($staff_date_from_mysql)) { $sf = mysqli_real_escape_string($conn, $staff_date_from_mysql); $where .= " AND t.trip_date >= '$sf'"; }
        if (!empty($staff_date_to_mysql))   { $st = mysqli_real_escape_string($conn, $staff_date_to_mysql);   $where .= " AND t.trip_date <= '$st'"; }

        $count_result       = mysqli_query($conn, "SELECT COUNT(*) as total FROM trips t $where");
        $count_row          = mysqli_fetch_assoc($count_result);
        $driver_total       = $count_row['total'];
        $driver_total_pages = ceil($driver_total / $per_page);

        $result = mysqli_query($conn, "SELECT t.trip_date, r.route_name, v.plate_no, CONCAT(a.fname,' ',a.lname) AS attendant, t.start_time, t.end_time, t.status
                                        FROM trips t
                                        JOIN routes     r ON t.route_id     = r.route_id
                                        JOIN vehicles   v ON t.vehicle_id   = v.vehicle_id
                                        JOIN attendants a ON t.attendant_id = a.attendant_id
                                        $where ORDER BY t.trip_date DESC, t.start_time DESC
                                        LIMIT $per_page OFFSET $driver_offset");
        if ($result) $driver_trips = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}

// ============================================================
// SECTION 6: ATTENDANT PERFORMANCE TAB
// Same pattern as Driver Performance. Uses 'ap' as the page parameter.
// ============================================================

$attendant_list        = array();
$attendant_trips        = array();
$attendant_total        = 0;
$attendant_total_pages  = 0;

$attendant_page   = (int)(isset($_GET['ap']) ? $_GET['ap'] : 1);
if ($attendant_page < 1) $attendant_page = 1;
$attendant_offset = ($attendant_page - 1) * $per_page;

if ($active_tab === 'attendant_performance') {

    $result = mysqli_query($conn, "SELECT attendant_id, fname, lname FROM attendants WHERE school_id = $school_id ORDER BY fname, lname");
    if ($result) $attendant_list = mysqli_fetch_all($result, MYSQLI_ASSOC);

    if ($staff_id > 0 && $staff_role === 'attendant') {
        $where = "WHERE t.attendant_id = $staff_id AND t.school_id = $school_id";
        if (!empty($staff_date_from_mysql)) { $sf = mysqli_real_escape_string($conn, $staff_date_from_mysql); $where .= " AND t.trip_date >= '$sf'"; }
        if (!empty($staff_date_to_mysql))   { $st = mysqli_real_escape_string($conn, $staff_date_to_mysql);   $where .= " AND t.trip_date <= '$st'"; }

        $count_result          = mysqli_query($conn, "SELECT COUNT(*) as total FROM trips t $where");
        $count_row              = mysqli_fetch_assoc($count_result);
        $attendant_total        = $count_row['total'];
        $attendant_total_pages  = ceil($attendant_total / $per_page);

        $result = mysqli_query($conn, "SELECT t.trip_date, r.route_name, v.plate_no, CONCAT(d.fname,' ',d.lname) AS driver, t.start_time, t.end_time, t.status
                                        FROM trips t
                                        JOIN routes   r ON t.route_id   = r.route_id
                                        JOIN vehicles v ON t.vehicle_id = v.vehicle_id
                                        JOIN drivers  d ON t.driver_id  = d.driver_id
                                        $where ORDER BY t.trip_date DESC, t.start_time DESC
                                        LIMIT $per_page OFFSET $attendant_offset");
        if ($result) $attendant_trips = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}

// ============================================================
// SECTION 7: STUDENT ATTENDANCE TAB
// Name search, no dropdown. Optional single date filter. Blank until a search is submitted.
// ============================================================

$student_search     = isset($_GET['student_search']) ? trim($_GET['student_search']) : '';
$student_date        = isset($_GET['student_date'])   ? trim($_GET['student_date'])   : '';
$student_attendance = array();
$students_found     = array();

if ($active_tab === 'student_attendance' && !empty($student_search)) {
    $ss = mysqli_real_escape_string($conn, $student_search);

    $result = mysqli_query($conn, "SELECT student_id, fname, lname FROM students
                                    WHERE school_id = $school_id
                                    AND (fname LIKE '%$ss%' OR lname LIKE '%$ss%' OR CONCAT(fname,' ',lname) LIKE '%$ss%')
                                    ORDER BY fname, lname");
    if ($result) $students_found = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Only one match - load their attendance straight away
    if (count($students_found) == 1) {
        $sid   = (int)$students_found[0]['student_id'];
        $where = "WHERE ts.student_id = $sid AND t.school_id = $school_id AND t.status = 'COMPLETED'";

        // Optional single date filter - narrows results to one day
        if (!empty($student_date) && is_valid_date($student_date)) {
            $sd = mysqli_real_escape_string($conn, to_mysql($student_date));
            $where .= " AND t.trip_date = '$sd'";
        }

        $result = mysqli_query($conn, "SELECT t.trip_date, r.route_name, v.plate_no, t.trip_type,
                                              ts.boarded, ts.boarded_time, ts.dropped, ts.dropped_time, ts.absent
                                       FROM trip_students ts
                                       JOIN trips    t ON ts.trip_id   = t.trip_id
                                       JOIN routes   r ON t.route_id   = r.route_id
                                       JOIN vehicles v ON t.vehicle_id = v.vehicle_id
                                       $where
                                       ORDER BY t.trip_date DESC, t.start_time DESC");
        if ($result) $student_attendance = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}

// ============================================================
// SECTION 8: INCIDENTS TAB
// Specific date + incident type filter. Shows reporter's role.
// ============================================================

$incident_date        = isset($_GET['incident_date'])  ? trim($_GET['incident_date'])  : '';
$incident_type_filter = isset($_GET['incident_type'])  ? trim($_GET['incident_type'])  : '';
$incident_list        = array();
$incident_types       = array();

if ($active_tab === 'incidents') {

    $result = mysqli_query($conn, "SELECT DISTINCT i.incident_type FROM incidents i JOIN trips t ON i.trip_id = t.trip_id WHERE t.school_id = $school_id ORDER BY i.incident_type");
    if ($result) $incident_types = mysqli_fetch_all($result, MYSQLI_ASSOC);

    if (!empty($incident_date) || !empty($incident_type_filter)) {

        $where = "WHERE t.school_id = $school_id";
        if (!empty($incident_date) && is_valid_date($incident_date)) {
            $ids = mysqli_real_escape_string($conn, to_mysql($incident_date));
            $where .= " AND DATE(i.reported_at) = '$ids'";
        }
        if (!empty($incident_type_filter)) {
            $its = mysqli_real_escape_string($conn, $incident_type_filter);
            $where .= " AND i.incident_type = '$its'";
        }

        $result = mysqli_query($conn, "SELECT i.reported_at, t.trip_date, r.route_name, i.incident_type, i.description, u.role AS reported_by_role
                                       FROM incidents i
                                       JOIN trips  t ON i.trip_id     = t.trip_id
                                       JOIN routes r ON t.route_id    = r.route_id
                                       JOIN users  u ON i.reported_by = u.user_id
                                       $where
                                       ORDER BY i.reported_at DESC");
        if ($result) $incident_list = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}

// ============================================================
// SECTION 9: FILTER DROPDOWN DATA
// Routes and vehicles for the Trip Summary filter bar.
// ============================================================

$result = mysqli_query($conn, "SELECT route_id, route_name FROM routes WHERE school_id = $school_id ORDER BY route_name");
$routes = array();
if ($result) $routes = mysqli_fetch_all($result, MYSQLI_ASSOC);

$result   = mysqli_query($conn, "SELECT vehicle_id, plate_no FROM vehicles WHERE school_id = $school_id ORDER BY plate_no");
$vehicles = array();
if ($result) $vehicles = mysqli_fetch_all($result, MYSQLI_ASSOC);

$conn->close();

include 'manager_reports_view.php';
