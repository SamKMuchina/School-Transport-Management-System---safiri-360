<?php
/**
 * driver_upcoming_trips.php - Rolling 7-day window
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
$driver    = mysqli_fetch_assoc($result);
if (!$driver) die("Driver record not found.");
$driver_id = (int)$driver['driver_id'];
$username  = $_SESSION['username'];

// AJAX: get students for a trip
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_students') {
    $trip_id = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;
    $r = mysqli_query($conn, "SELECT trip_id FROM trips WHERE trip_id = $trip_id AND driver_id = $driver_id");
    if (!$r || mysqli_num_rows($r) == 0) { echo '<p class="text-danger text-center">Unauthorized.</p>'; exit(); }
    $r = mysqli_query($conn, "SELECT s.fname, s.lname, s.grade, rs.stop_name
                               FROM trip_students ts
                               JOIN students s ON ts.student_id = s.student_id
                               JOIN student_assignments sa ON s.student_id = sa.student_id AND sa.is_active = 1
                               JOIN route_stops rs ON sa.stop_id = rs.stop_id
                               WHERE ts.trip_id = $trip_id ORDER BY rs.stop_order");
    if (!$r || mysqli_num_rows($r) == 0) { echo '<p class="text-muted text-center">No students assigned.</p>'; exit(); }
    echo '<div class="table-wrapper"><table class="data-table"><thead><tr><th>Name</th><th>Grade</th><th>Stop</th></tr></thead><tbody>';
    while ($s = mysqli_fetch_assoc($r)) {
        echo '<tr><td>'.htmlspecialchars($s['fname'].' '.$s['lname']).'</td><td>'.htmlspecialchars($s['grade']).'</td><td>'.htmlspecialchars($s['stop_name']).'</td></tr>';
    }
    echo '</tbody></table></div>';
    exit();
}

// Rolling 7-day window
$trips_sql = "SELECT t.trip_id, t.trip_date, t.status, r.route_name, v.plate_no,
                     d.fname AS driver_fname, d.lname AS driver_lname,
                     (SELECT COUNT(*) FROM trip_students WHERE trip_id = t.trip_id) AS student_count
              FROM trips t
              JOIN routes   r ON t.route_id   = r.route_id
              JOIN vehicles v ON t.vehicle_id = v.vehicle_id
              JOIN drivers  d ON t.driver_id  = d.driver_id
              WHERE t.driver_id = $driver_id
              AND t.status IN ('PENDING','IN_PROGRESS')
              AND t.trip_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 DAY)
              ORDER BY t.trip_date ASC, t.start_time ASC";

$trips_result   = mysqli_query($conn, $trips_sql);
$total_upcoming = $trips_result ? mysqli_num_rows($trips_result) : 0;

$conn->close();

include 'driver_upcoming_trips_view.php';
