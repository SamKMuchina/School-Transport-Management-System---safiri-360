<?php
/**
 * driver_dashboard_view.php - DISPLAY ONLY
 * Variables: $username, $first_name, $active_trip, $upcoming_result
 * Same hero card design as attendant dashboard.
 * Completed Trips removed from sidebar.
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - School Transport Management</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">&#9776;</button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<div class="dashboard-container">
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">School<span>Track</span><span class="sidebar-subtitle">Driver Portal</span></div>
        <div class="menu-section"><div class="menu-section-title">Main</div><a href="driver_dashboard.php" class="menu-item active">Dashboard</a></div>
        <div class="menu-section"><div class="menu-section-title">Trip Management</div><a href="driver_upcoming_trips.php" class="menu-item">Upcoming Trips</a></div>
        <div class="menu-section"><div class="menu-section-title">Safety</div><a href="driver_report_incident.php" class="menu-item">Report Incident</a></div>
        <div class="menu-section"><div class="menu-section-title">Reports</div><a href="driver_reports.php" class="menu-item">My Reports</a></div>
        <div class="menu-section"><div class="menu-section-title">Account</div><a href="../logout.php" class="menu-item">Logout</a></div>
    </div>
    <div class="main-wrapper">
        <div class="top-header">
            <div class="header-left"><h1>Driver Dashboard</h1></div>
            <div class="header-right"><span class="user-name"><?php echo htmlspecialchars($first_name); ?></span><a href="../logout.php" class="logout-btn">Logout</a></div>
        </div>
        <div class="dashboard-content">

            <!-- HERO: CURRENT ACTIVE TRIP -->
            <div class="content-section">
                <div class="section-header"><span class="section-title">Current Active Trip</span></div>
                <?php if ($active_trip): ?>
                <div class="hero-card">
                    <div class="hero-label">Active Trip</div>
                    <div class="hero-route"><?php echo htmlspecialchars($active_trip['route_name']); ?></div>
                    <div class="hero-details">
                        <span><strong>Vehicle</strong><?php echo htmlspecialchars($active_trip['vehicle_plate']); ?></span>
                        <span><strong>Attendant</strong><?php echo htmlspecialchars($active_trip['attendant_name']); ?></span>
                        <span><strong>Date</strong><?php echo date('d/m/Y', strtotime($active_trip['trip_date'])); ?></span>
                        <span><strong>Status</strong><?php echo htmlspecialchars($active_trip['status']); ?></span>
                    </div>
                    <div class="hero-actions">
                        <a href="driver_trip_attendance.php?trip_id=<?php echo $active_trip['trip_id']; ?>" class="btn btn-primary">View Students</a>
                        <?php if ($active_trip['status'] === 'PENDING'): ?>
                            <a href="start_trip.php?trip_id=<?php echo $active_trip['trip_id']; ?>" class="btn btn-success">Start Trip</a>
                        <?php else: ?>
                            <a href="start_trip.php?trip_id=<?php echo $active_trip['trip_id']; ?>" class="btn btn-info">Resume Trip</a>
                        <?php endif; ?>
                        <a href="driver_report_incident.php" class="btn btn-danger">Report Incident</a>
                    </div>
                </div>
                <?php else: ?>
                <div class="hero-empty"><p>No active trip at this time.</p></div>
                <?php endif; ?>
            </div>

            <!-- TODAY'S UPCOMING TRIPS -->
            <div class="content-section">
                <div class="section-header">
                    <span class="section-title">Today's Upcoming Trips</span>
                    <a href="driver_upcoming_trips.php" class="view-all-link">View This Week</a>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead><tr><th>Route</th><th>Vehicle</th><th>Attendant</th><th>Students</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            <?php if ($upcoming_result && mysqli_num_rows($upcoming_result) > 0): ?>
                                <?php while ($trip = mysqli_fetch_assoc($upcoming_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($trip['route_name']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['plate_no']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['attendant_fname'] . ' ' . $trip['attendant_lname']); ?></td>
                                    <td><?php echo $trip['student_count']; ?></td>
                                    <td><span class="badge badge-warning"><?php echo htmlspecialchars(str_replace('_',' ',$trip['status'])); ?></span></td>
                                    <td><a href="driver_dashboard.php?active_trip_id=<?php echo $trip['trip_id']; ?>" class="btn btn-primary btn-sm">Select</a></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="table-empty">No upcoming trips for today.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
<script>
    function toggleSidebar(){var s=document.getElementById("sidebar"),o=document.getElementById("sidebarOverlay");if(s.className.indexOf("open")!==-1){s.className="sidebar";o.className="sidebar-overlay";}else{s.className="sidebar open";o.className="sidebar-overlay active";}}
    function closeSidebar(){document.getElementById("sidebar").className="sidebar";document.getElementById("sidebarOverlay").className="sidebar-overlay";}
</script>
</body>
</html>
