<?php
/**
 * upcoming_trips_view.php - DISPLAY ONLY
 *
 * Variables: $username, $total_upcoming, $trips_result
 *
 * Shows rolling 7-day window of upcoming trips.
 * Completed Trips removed from sidebar.
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upcoming Trips - Attendant</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">&#9776;</button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<div class="dashboard-container">
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">School<span>Track</span><span class="sidebar-subtitle">Attendant Portal</span></div>
        <div class="menu-section"><div class="menu-section-title">Main</div><a href="attendant_dashboard.php" class="menu-item">Dashboard</a></div>
        <div class="menu-section"><div class="menu-section-title">Trip Management</div><a href="upcoming_trips.php" class="menu-item active">Upcoming Trips</a></div>
        <div class="menu-section"><div class="menu-section-title">Safety</div><a href="report_incident.php" class="menu-item">Report Incident</a></div>
        <div class="menu-section"><div class="menu-section-title">Reports</div><a href="attendant_reports.php" class="menu-item">My Reports</a></div>
        <div class="menu-section"><div class="menu-section-title">Account</div><a href="../logout.php" class="menu-item">Logout</a></div>
    </div>
    <div class="main-wrapper">
        <div class="top-header">
            <div class="header-left"><h1>Upcoming Trips</h1></div>
            <div class="header-right"><span class="user-name"><?php echo htmlspecialchars($username); ?></span><a href="../logout.php" class="logout-btn">Logout</a></div>
        </div>
        <div class="dashboard-content">
            <div class="content-section">
                <div class="section-header">
                    <span class="section-title">Next 7 Days — <?php echo date('d/m/Y'); ?> to <?php echo date('d/m/Y', strtotime('+6 days')); ?></span>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr><th>Date</th><th>Route</th><th>Vehicle</th><th>Driver</th><th>Students</th><th>Status</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($total_upcoming > 0): ?>
                                <?php while ($trip = mysqli_fetch_assoc($trips_result)): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($trip['trip_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($trip['route_name']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['plate_no']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['driver_fname'] . ' ' . $trip['driver_lname']); ?></td>
                                    <td><?php echo $trip['student_count']; ?></td>
                                    <td><span class="badge <?php echo $trip['status']=='PENDING'?'badge-warning':'badge-info'; ?>"><?php echo htmlspecialchars(str_replace('_',' ',$trip['status'])); ?></span></td>
                                    <td><button class="btn btn-primary btn-sm" onclick="viewStudents(<?php echo $trip['trip_id']; ?>)">View Students</button></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="table-empty">No upcoming trips for the next 7 days.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- STUDENTS MODAL -->
<div id="studentModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeStudentModal()">&times;</button>
        <div class="modal-header"><span class="modal-title">Students on Trip</span></div>
        <div id="modalBody"><p class="text-muted text-center">Loading...</p></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeStudentModal()">Close</button></div>
    </div>
</div>

<script>
    function toggleSidebar(){var s=document.getElementById("sidebar"),o=document.getElementById("sidebarOverlay");if(s.className.indexOf("open")!==-1){s.className="sidebar";o.className="sidebar-overlay";}else{s.className="sidebar open";o.className="sidebar-overlay active";}}
    function closeSidebar(){document.getElementById("sidebar").className="sidebar";document.getElementById("sidebarOverlay").className="sidebar-overlay";}
    function viewStudents(tripId){
        document.getElementById("studentModal").className="modal active";
        document.getElementById("modalBody").innerHTML="<p class='text-muted text-center'>Loading...</p>";
        var xhr=new XMLHttpRequest();
        xhr.open("GET","upcoming_trips.php?ajax=get_students&trip_id="+tripId,true);
        xhr.onload=function(){if(xhr.status===200){document.getElementById("modalBody").innerHTML=xhr.responseText;}else{document.getElementById("modalBody").innerHTML="<p class='text-danger text-center'>Error loading data.</p>";}};
        xhr.onerror=function(){document.getElementById("modalBody").innerHTML="<p class='text-danger text-center'>Error loading data.</p>";};
        xhr.send();
    }
    function closeStudentModal(){document.getElementById("studentModal").className="modal";}
    window.onclick=function(e){var m=document.getElementById("studentModal");if(e.target==m)m.className="modal";};
</script>
</body>
</html>
