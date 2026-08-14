<?php
/**
 * trip_monitoring_view.php
 *
 * DISPLAY ONLY - no database queries, no business logic.
 *
 * Included by trip_monitoring.php after that file has already:
 *   - looked up attendance for the trip in ?view_trip= (if present)
 *   - fetched today's IN_PROGRESS trips
 *   - prepared these variables for us to display:
 *
 *       $username         (string)        - logged-in user's name, for header
 *       $trips_result     (mysqli result) - today's IN_PROGRESS trips, or
 *                                           empty result if none are running
 *       $view_trip_id     (int)           - trip_id being viewed, 0 if none
 *       $view_trip        (array|null)    - trip_id, trip_type, route_name
 *                                           for the trip being viewed
 *       $attendance_list  (array)         - each student's pickup/drop-off
 *                                           status for the trip being viewed
 *
 * PAGE BEHAVIOUR:
 * - Table shows only IN_PROGRESS trips for today
 * - Blank state shown if no trips are currently running
 * - "View Attendance" is a plain link to ?view_trip=<trip_id> - the page
 *   reloads and the attendance modal below is rendered already open by
 *   PHP, no AJAX or JavaScript building HTML involved
 */
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Monitoring - Transport Manager</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">&#9776;</button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">School<span>Track</span><span class="sidebar-subtitle">Transport Manager</span></div>
        <div class="menu-section"><div class="menu-section-title">Main</div><a href="transport_manager_dashboard.php" class="menu-item">Dashboard</a></div>
        <div class="menu-section"><div class="menu-section-title">Student Management</div><a href="manage_students.php" class="menu-item">Students</a><a href="student_assignments.php" class="menu-item">Assignments</a></div>
        <div class="menu-section"><div class="menu-section-title">Staff Management</div><a href="manage_drivers.php" class="menu-item">Drivers</a><a href="manage_attendants.php" class="menu-item">Attendants</a></div>
        <div class="menu-section"><div class="menu-section-title">Fleet and Routes</div><a href="manage_vehicles.php" class="menu-item">Vehicles</a><a href="manage_routes.php" class="menu-item">Routes</a><a href="manage_route_stops.php" class="menu-item">Route Stops</a></div>
        <div class="menu-section"><div class="menu-section-title">Operations</div><a href="manage_trips.php" class="menu-item">Trips</a><a href="trip_monitoring.php" class="menu-item active">Monitoring</a><a href="manage_incidents.php" class="menu-item">Incidents</a></div>
        <div class="menu-section"><div class="menu-section-title">Reports</div><a href="manager_reports.php" class="menu-item">Reports</a></div>
        <div class="menu-section"><div class="menu-section-title">Account</div><a href="../logout.php" class="menu-item">Logout</a></div>
    </div>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">

        <!-- TOP HEADER -->
        <div class="top-header">
            <div class="header-left"><h1>Trip Monitoring</h1></div>
            <div class="header-right">
                <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="dashboard-content">

            <!-- ===== ONGOING TRIPS TABLE ===== -->
            <div class="content-section">
                <div class="section-header">
                    <span class="section-title">
                        Ongoing Trips &mdash; <?php echo date('F j, Y'); ?>
                    </span>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Route</th>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Attendant</th>
                                <th>Trip Type</th>
                                <th>Start Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($trips_result && mysqli_num_rows($trips_result) > 0): ?>

                                <?php while ($trip = mysqli_fetch_assoc($trips_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($trip['route_name']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['plate_no']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['driver_fname'] . ' ' . $trip['driver_lname']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['attendant_fname'] . ' ' . $trip['attendant_lname']); ?></td>
                                    <td>
                                        <!-- Show Morning Pickup or Evening Drop-off based on trip_type -->
                                        <?php
                                        if ($trip['trip_type'] === 'morning') {
                                            echo 'Morning Pickup';
                                        } elseif ($trip['trip_type'] === 'evening') {
                                            echo 'Evening Drop-off';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $trip['start_time'] ? date('h:i A', strtotime($trip['start_time'])) : '-'; ?></td>
                                    <td>
                                        <!-- View Attendance is a plain link - page reloads with ?view_trip= -->
                                        <a href="trip_monitoring.php?view_trip=<?php echo (int)$trip['trip_id']; ?>"
                                           class="btn btn-primary btn-sm">
                                            View Attendance
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>

                            <?php else: ?>
                                <!-- Blank state: no trips currently running -->
                                <tr>
                                    <td colspan="7" class="table-empty">
                                        No trips are currently in progress.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     ATTENDANCE MODAL
     Rendered "active" by PHP when ?view_trip=<trip_id> is present and
     valid - no JS or AJAX involved, the controller already ran the
     attendance query and passed the results down as $attendance_list.
============================================================ -->
<div id="attendanceModal" class="modal <?php echo $view_trip ? 'active' : ''; ?>">
    <div class="modal-content">
        <a href="trip_monitoring.php" class="modal-close">&times;</a>
        <div class="modal-header">
            <span class="modal-title">Live Attendance</span>
            <?php if ($view_trip): ?>
            <span class="modal-subtitle"><?php echo htmlspecialchars($view_trip['route_name']); ?></span>
            <?php endif; ?>
        </div>

        <?php if ($view_trip_id > 0 && !$view_trip): ?>
            <p class="text-danger">Trip not found or not in progress.</p>
        <?php elseif ($view_trip && empty($attendance_list)): ?>
            <p class="table-empty">No students assigned to this trip.</p>
        <?php elseif ($view_trip): ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>Student Name</th><th>Grade</th><th>Stop</th><th>Status</th><th>Time</th></tr></thead>
                    <tbody>
                        <?php foreach ($attendance_list as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></td>
                            <td><?php echo htmlspecialchars($row['grade']); ?></td>
                            <td><?php echo htmlspecialchars($row['stop_name'] ?? 'N/A'); ?></td>
                            <td>
                                <!-- Status label depends on trip_type: morning trips show
                                     Boarded status, evening trips show Dropped status -->
                                <?php if ($row['absent'] == 1): ?>
                                    <span class="badge badge-danger">Absent</span>
                                <?php elseif ($view_trip['trip_type'] === 'morning' && $row['boarded'] == 1): ?>
                                    <span class="badge badge-success">Picked Up</span>
                                <?php elseif ($view_trip['trip_type'] === 'evening' && $row['dropped'] == 1): ?>
                                    <span class="badge badge-success">Dropped Off</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Waiting</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['absent'] == 1): ?>
                                    -
                                <?php elseif ($view_trip['trip_type'] === 'morning' && $row['boarded'] == 1): ?>
                                    <?php echo $row['boarded_time'] ? date('h:i A', strtotime($row['boarded_time'])) : '-'; ?>
                                <?php elseif ($view_trip['trip_type'] === 'evening' && $row['dropped'] == 1): ?>
                                    <?php echo $row['dropped_time'] ? date('h:i A', strtotime($row['dropped_time'])) : '-'; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="modal-footer">
            <a href="trip_monitoring.php" class="btn btn-secondary">Close</a>
        </div>
    </div>
</div>

<script>

    // ============================================================
    // COLLAPSIBLE SIDEBAR
    // ============================================================

    function toggleSidebar() {
        var sidebar = document.getElementById("sidebar");
        var overlay = document.getElementById("sidebarOverlay");
        if (sidebar.className.indexOf("open") !== -1) {
            sidebar.className = "sidebar";
            overlay.className = "sidebar-overlay";
        } else {
            sidebar.className = "sidebar open";
            overlay.className = "sidebar-overlay active";
        }
    }

    function closeSidebar() {
        document.getElementById("sidebar").className = "sidebar";
        document.getElementById("sidebarOverlay").className = "sidebar-overlay";
    }

</script>
</body>
</html>
