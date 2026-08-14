<?php
/**
 * report_incident_view.php - DISPLAY ONLY (Attendant)
 *
 * Simple form: date filter → trips dropdown → incident type → description.
 * Incident history completely removed.
 * Variables: $username, $error, $success, $filter_date, $trips_list
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Incident - Driver</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">&#9776;</button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<div class="dashboard-container">
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">School<span>Track</span><span class="sidebar-subtitle">Driver Portal</span></div>
        <div class="menu-section"><div class="menu-section-title">Main</div><a href="driver_dashboard.php" class="menu-item">Dashboard</a></div>
        <div class="menu-section"><div class="menu-section-title">Trip Management</div><a href="driver_upcoming_trips.php" class="menu-item">Upcoming Trips</a></div>
        <div class="menu-section"><div class="menu-section-title">Safety</div><a href="driver_report_incident.php" class="menu-item active">Report Incident</a></div>
        <div class="menu-section"><div class="menu-section-title">Reports</div><a href="driver_reports.php" class="menu-item">My Reports</a></div>
        <div class="menu-section"><div class="menu-section-title">Account</div><a href="../logout.php" class="menu-item">Logout</a></div>
    </div>
    <div class="main-wrapper">
        <div class="top-header">
            <div class="header-left"><h1>Report Incident</h1></div>
            <div class="header-right"><span class="user-name"><?php echo htmlspecialchars($username); ?></span><a href="../logout.php" class="logout-btn">Logout</a></div>
        </div>
        <div class="dashboard-content">
            <?php if (!empty($error)):   ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if (!empty($success)): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

            <div class="content-section">
                <div class="section-header"><span class="section-title">Report New Incident</span></div>
                <div class="form-section">

                    <!-- STEP 1: Date filter -->
                    <form method="GET" action="driver_report_incident.php" name="dateForm" onsubmit="return validateDateFilter()">
                        <div class="filter-bar">
                            <div class="filter-group">
                                <label class="form-label">Select Trip Date (dd/mm/yyyy)</label>
                                <input type="text" name="filter_date" id="filter_date" class="form-input"
                                       style="max-width:200px;" placeholder="dd/mm/yyyy" maxlength="10"
                                       value="<?php echo htmlspecialchars($filter_date); ?>">
                            </div>
                            <div class="filter-group" style="justify-content:flex-end;"><label>&nbsp;</label>
                                <div style="display:flex;gap:0.5rem;">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="driver_report_incident.php" class="btn btn-secondary">Reset</a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- STEP 2: Incident form shown after date is filtered -->
                    <?php if (!empty($filter_date)): ?>
                    <form method="POST" action="driver_report_incident.php" name="incidentForm" onsubmit="return validateIncidentForm()">
                        <input type="hidden" name="action" value="report">
                        <div class="form-group">
                            <label class="form-label">Select Trip</label>
                            <select name="trip_id" id="incident_trip_id" class="form-select">
                                <option value="">Select Trip</option>
                                <?php if (count($trips_list) > 0): ?>
                                    <?php foreach ($trips_list as $trip): ?>
                                        <option value="<?php echo $trip['trip_id']; ?>">
                                            <?php echo htmlspecialchars(date('d/m/Y', strtotime($trip['trip_date'])) . ' - ' . $trip['route_name'] . ' (' . $trip['plate_no'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>No trips found for this date</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Incident Type</label>
                            <select name="incident_type" id="incident_type_sel" class="form-select">
                                <option value="">Select Type</option>
                                <option value="Traffic Delay">Traffic Delay</option>
                                <option value="Vehicle Breakdown">Vehicle Breakdown</option>
                                <option value="Student Misconduct">Student Misconduct</option>
                                <option value="Minor Accident">Minor Accident</option>
                                <option value="Major Accident">Major Accident</option>
                                <option value="Route Deviation">Route Deviation</option>
                                <option value="Medical Emergency">Medical Emergency</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="incident_desc" class="form-textarea"
                                      placeholder="Provide a detailed description of the incident..."></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-danger">Report Incident</button>
                        </div>
                    </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function toggleSidebar(){var s=document.getElementById("sidebar"),o=document.getElementById("sidebarOverlay");if(s.className.indexOf("open")!==-1){s.className="sidebar";o.className="sidebar-overlay";}else{s.className="sidebar open";o.className="sidebar-overlay active";}}
    function closeSidebar(){document.getElementById("sidebar").className="sidebar";document.getElementById("sidebarOverlay").className="sidebar-overlay";}

    function validateDateFilter() {
        var d = document.getElementById("filter_date").value;
        if (d.length == 0) { alert("Please enter a date."); document.getElementById("filter_date").focus(); return false; }
        if (d.indexOf("/") == -1) { alert("Date must be in dd/mm/yyyy format."); return false; }
        var c = d.split("/");
        if (c.length < 3 || c[2].length != 4) { alert("Date must be in dd/mm/yyyy format."); return false; }
        if (isNaN(c[0]) || isNaN(c[1]) || isNaN(c[2])) { alert("Date components must be numbers."); return false; }
        if (c[0] > 31) { alert("Day must be between 1 and 31."); return false; }
        if (c[1] > 12) { alert("Month must be between 1 and 12."); return false; }
        return true;
    }

    function validateIncidentForm() {
        var trip = document.getElementById("incident_trip_id");
        if (trip.options[trip.selectedIndex].value == "") { alert("Please select a trip."); trip.focus(); return false; }
        var sel = document.getElementById("incident_type_sel");
        if (sel.options[sel.selectedIndex].value == "") { alert("Please select an incident type."); sel.focus(); return false; }
        var desc = document.getElementById("incident_desc").value;
        if (desc.length == 0) { alert("Please provide a description."); document.getElementById("incident_desc").focus(); return false; }
        return true;
    }
</script>
</body>
</html>
