<?php
/**
 * transport_manager_dashboard_view.php
 *
 * DISPLAY ONLY - no database queries, no business logic.
 *
 * Included by transport_manager_dashboard.php after that file has
 * already run every query and prepared these variables:
 *
 *     $username            (string)        - logged-in user's name
 *     $total_students      (int)           - stat card
 *     $total_drivers       (int)           - stat card
 *     $total_attendants    (int)           - stat card
 *     $total_vehicles      (int)           - stat card
 *     $total_routes        (int)           - stat card
 *     $active_trips        (int)           - stat card (IN_PROGRESS trips)
 *     $todays_trips_result (mysqli result) - all trips for today
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Manager Dashboard - School Transport Management</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">&#9776;</button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">School<span>Track</span><span class="sidebar-subtitle">Transport Manager</span></div>
        <div class="menu-section"><div class="menu-section-title">Main</div><a href="transport_manager_dashboard.php" class="menu-item active">Dashboard</a></div>
        <div class="menu-section"><div class="menu-section-title">Student Management</div><a href="manage_students.php" class="menu-item">Students</a><a href="student_assignments.php" class="menu-item">Assignments</a></div>
        <div class="menu-section"><div class="menu-section-title">Staff Management</div><a href="manage_drivers.php" class="menu-item">Drivers</a><a href="manage_attendants.php" class="menu-item">Attendants</a></div>
        <div class="menu-section"><div class="menu-section-title">Fleet and Routes</div><a href="manage_vehicles.php" class="menu-item">Vehicles</a><a href="manage_routes.php" class="menu-item">Routes</a><a href="manage_route_stops.php" class="menu-item">Route Stops</a></div>
        <div class="menu-section"><div class="menu-section-title">Operations</div><a href="manage_trips.php" class="menu-item">Trips</a><a href="trip_monitoring.php" class="menu-item">Monitoring</a><a href="manage_incidents.php" class="menu-item">Incidents</a></div>
        <div class="menu-section"><div class="menu-section-title">Reports</div><a href="manager_reports.php" class="menu-item">Reports</a></div>
        <div class="menu-section"><div class="menu-section-title">Account</div><a href="../logout.php" class="menu-item">Logout</a></div>
    </div>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">

        <!-- TOP HEADER -->
        <div class="top-header">
            <div class="header-left"><h1>Transport Manager Dashboard</h1></div>
            <div class="header-right">
                <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="dashboard-content">

            <!-- ===== STAT CARDS ===== -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-label">Total Students</span>
                    <span class="stat-value"><?php echo $total_students; ?></span>
                </div>
                <div class="stat-card stat-info">
                    <span class="stat-label">Drivers</span>
                    <span class="stat-value"><?php echo $total_drivers; ?></span>
                </div>
                <div class="stat-card stat-info">
                    <span class="stat-label">Attendants</span>
                    <span class="stat-value"><?php echo $total_attendants; ?></span>
                </div>
                <div class="stat-card stat-warning">
                    <span class="stat-label">Vehicles</span>
                    <span class="stat-value"><?php echo $total_vehicles; ?></span>
                </div>
                <div class="stat-card stat-warning">
                    <span class="stat-label">Routes</span>
                    <span class="stat-value"><?php echo $total_routes; ?></span>
                </div>
                <div class="stat-card stat-success">
                    <span class="stat-label">Active Trips</span>
                    <span class="stat-value"><?php echo $active_trips; ?></span>
                </div>
            </div>

            <!-- ===== TODAY'S TRIPS TABLE ===== -->
            <div class="content-section">
                <div class="section-header">
                    <span class="section-title">Today's Trips &mdash; <?php echo date('F j, Y'); ?></span>
                    <a href="manage_trips.php" class="view-all-link">Manage Trips</a>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Route</th>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Attendant</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($todays_trips_result && mysqli_num_rows($todays_trips_result) > 0): ?>
                                <?php while ($trip = mysqli_fetch_assoc($todays_trips_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($trip['route_name']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['plate_no']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['driver_fname'] . ' ' . $trip['driver_lname']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['attendant_fname'] . ' ' . $trip['attendant_lname']); ?></td>
                                    <td><?php echo $trip['start_time'] ? date('h:i A', strtotime($trip['start_time'])) : '-'; ?></td>
                                    <td><?php echo $trip['end_time']   ? date('h:i A', strtotime($trip['end_time']))   : '-'; ?></td>
                                    <td>
                                        <?php
                                        $st    = $trip['status'];
                                        $badge = 'badge-secondary';
                                        if ($st == 'COMPLETED')   $badge = 'badge-success';
                                        if ($st == 'IN_PROGRESS') $badge = 'badge-info';
                                        if ($st == 'PENDING')     $badge = 'badge-warning';
                                        ?>
                                        <span class="badge <?php echo $badge; ?>">
                                            <?php echo htmlspecialchars(str_replace('_', ' ', $st)); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="table-empty">
                                        No trips scheduled for today.
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
