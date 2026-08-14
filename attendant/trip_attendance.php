<?php
/**
 * trip_attendance.php
 *
 * Displays the list of students assigned to a specific trip.
 * Read-only view for the ATTENDANT to see which students
 * are on the trip and their pickup/drop-off stops.
 *
 * Location  : attendant/trip_attendance.php
 * Includes  : ../includes/db.php
 *
 * Access: ATTENDANT only
 *
 * NOTE ON QUERIES: Uses mysqli_query() with (int) casting for all
 *
 * Database tables used:
 * - trips, routes, trip_students, students, student_assignments, route_stops
 */

// ============================================================
// SECTION 1: SESSION & ACCESS CONTROL
// ============================================================

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ATTENDANT') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db.php';

$user_id  = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];

// ============================================================
// SECTION 2: FETCH ATTENDANT ID
// ============================================================

$result   = mysqli_query($conn, "SELECT attendant_id FROM attendants WHERE user_id = $user_id");
$att      = mysqli_fetch_assoc($result);
if (!$att) die("Attendant record not found.");
$attendant_id = (int)$att['attendant_id'];

// ============================================================
// SECTION 3: GET AND VERIFY TRIP
// ============================================================

/*
 * trip_id comes from the URL as a GET parameter.
 * (int) cast ensures it can only ever be a number.
 * We verify the trip is assigned to this attendant before showing anything.
 */
$trip_id = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;

if ($trip_id <= 0) {
    die("Invalid trip ID.");
}

$trip_sql    = "SELECT t.trip_id, t.trip_date, t.route_id, r.route_name
                FROM trips t
                JOIN routes r ON t.route_id = r.route_id
                WHERE t.trip_id = $trip_id AND t.attendant_id = $attendant_id";
$trip_result = mysqli_query($conn, $trip_sql);
$trip        = mysqli_fetch_assoc($trip_result);

if (!$trip) {
    die("Trip not found or not assigned to you.");
}

$route_id = (int)$trip['route_id'];

// ============================================================
// SECTION 4: FETCH STUDENTS FOR THIS TRIP
// ============================================================

/*
 * Fetches all students on this trip ordered by their stop order
 * so the list matches the physical sequence of the route.
 */
$students_sql    = "SELECT s.fname, s.lname, s.grade, rs.stop_name, rs.stop_order
                    FROM trip_students ts
                    JOIN students            s  ON ts.student_id = s.student_id
                    JOIN student_assignments sa ON s.student_id  = sa.student_id AND sa.is_active = 1
                    JOIN route_stops         rs ON sa.stop_id    = rs.stop_id
                    WHERE ts.trip_id = $trip_id
                    ORDER BY rs.stop_order ASC";
$students_result = mysqli_query($conn, $students_sql);

$conn->close();
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Attendance - Attendant</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
    <style>
        body { background: linear-gradient(135deg, #2f3b4f 0%, #4a5f7f 100%); min-height: 100vh; display: flex; justify-content: center; align-items: flex-start; padding: 2rem 1rem; }
        .container { background-color: #ffffff; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); width: 100%; max-width: 800px; overflow: hidden; }
        .page-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; background-color: #f8f9fa; border-bottom: 1px solid #e0e0e0; flex-wrap: wrap; gap: 0.5rem; }
        .page-header h1 { color: #2f3b4f; font-size: 1.3rem; font-weight: 600; }
        .trip-info-bar { background-color: #E8F4FD; padding: 0.8rem 1.5rem; border-bottom: 4px solid #2196F3; display: flex; flex-wrap: wrap; gap: 1.5rem; font-size: 13px; color: #2f3b4f; }
        .trip-info-bar strong { font-weight: 600; }
        .content-area { padding: 1.5rem; }
    </style>
</head>
<body>
<div class="container">

    <div class="page-header">
        <h1>Trip Attendance</h1>
        <a href="attendant_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="trip-info-bar">
        <div><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($trip['trip_date'])); ?></div>
        <div><strong>Route:</strong> <?php echo htmlspecialchars($trip['route_name']); ?></div>
    </div>

    <div class="content-area">
        <?php if ($students_result && mysqli_num_rows($students_result) > 0): ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Student Name</th><th>Grade</th><th>Stop</th></tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($students_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></td>
                        <td><?php echo htmlspecialchars($row['grade']); ?></td>
                        <td><?php echo htmlspecialchars($row['stop_name']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="table-empty">No students assigned to this trip.</p>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
