<?php
/**
 * manage_incidents_view.php
 *
 * DISPLAY ONLY - no database queries, no business logic.
 *
 * Included by manage_incidents.php after that file has prepared:
 *
 *     $username         (string) - logged-in user's name
 *     $success_message  (string) - success alert, or ''
 *     $error_message    (string) - error alert, or ''
 *     $filter_date      (string) - current date filter value, or ''
 *     $trips_list       (array)  - trips for the selected date, or empty
 *
 * HOW IT WORKS:
 * 1. Manager enters a date and clicks Filter
 * 2. Page reloads with trips for that date loaded in the dropdown
 * 3. Manager selects a trip, fills in type and description, submits
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Incident - Transport Manager</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">&#9776;</button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="dashboard-container">
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">School<span>Track</span><span class="sidebar-subtitle">Transport Manager</span></div>
        <div class="menu-section"><div class="menu-section-title">Main</div><a href="transport_manager_dashboard.php" class="menu-item">Dashboard</a></div>
        <div class="menu-section"><div class="menu-section-title">Student Management</div><a href="manage_students.php" class="menu-item">Students</a><a href="student_assignments.php" class="menu-item">Assignments</a></div>
        <div class="menu-section"><div class="menu-section-title">Staff Management</div><a href="manage_drivers.php" class="menu-item">Drivers</a><a href="manage_attendants.php" class="menu-item">Attendants</a></div>
        <div class="menu-section"><div class="menu-section-title">Fleet and Routes</div><a href="manage_vehicles.php" class="menu-item">Vehicles</a><a href="manage_routes.php" class="menu-item">Routes</a><a href="manage_route_stops.php" class="menu-item">Route Stops</a></div>
        <div class="menu-section"><div class="menu-section-title">Operations</div><a href="manage_trips.php" class="menu-item">Trips</a><a href="trip_monitoring.php" class="menu-item">Monitoring</a><a href="manage_incidents.php" class="menu-item active">Incidents</a></div>
        <div class="menu-section"><div class="menu-section-title">Reports</div><a href="manager_reports.php" class="menu-item">Reports</a></div>
        <div class="menu-section"><div class="menu-section-title">Account</div><a href="../logout.php" class="menu-item">Logout</a></div>
    </div>

    <div class="main-wrapper">
        <div class="top-header">
            <div class="header-left"><h1>Report Incident</h1></div>
            <div class="header-right">
                <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="dashboard-content">

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="content-section">
                <div class="section-header">
                    <span class="section-title">Report New Incident</span>
                </div>
                <div class="form-section">

                    <!-- STEP 1: Date filter - GET form, reloads page with trips for that date -->
                    <form method="GET" action="manage_incidents.php" name="dateFilterForm" onsubmit="return validateDateFilter()">
                        <div class="filter-bar">
                            <div class="filter-group">
                                <label class="form-label">Select Trip Date (dd/mm/yyyy)</label>
                                <input type="text" name="filter_date" id="filter_date" class="form-input"
                                       style="max-width:200px;" placeholder="dd/mm/yyyy" maxlength="10"
                                       value="<?php echo htmlspecialchars($filter_date); ?>"
                                       onmouseout="validateDateFilter()">
                            </div>
                            <div class="filter-group" style="justify-content:flex-end;"><label>&nbsp;</label>
                                <div style="display:flex;gap:0.5rem;">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="manage_incidents.php" class="btn btn-secondary">Reset</a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- STEP 2: Incident form - only shown once a date has been filtered -->
                    <?php if (!empty($filter_date)): ?>
                    <form method="POST" action="manage_incidents.php" name="incidentForm" onsubmit="return validateIncidentForm()">
                        <input type="hidden" name="action" value="create_incident">

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

    // ============================================================
    // DATE VALIDATION - follows supervisor's validation blueprint
    // ============================================================

    function validateDateFilter() {
        var date = document.getElementById("filter_date").value;
        if (date.length == 0) { alert("Please enter a date."); document.getElementById("filter_date").focus(); return false; }
        if (date.indexOf("/") == -1) { alert("Date must be in dd/mm/yyyy format."); return false; }
        var comps = date.split("/");
        if (comps.length < 3 || comps[0].length < 1 || comps[1].length < 1 || comps[2].length != 4) { alert("Date must be in dd/mm/yyyy format."); return false; }
        if (isNaN(comps[0]) || isNaN(comps[1]) || isNaN(comps[2])) { alert("Date components must be numbers."); return false; }
        if (comps[0] > 31) { alert("Day must be between 1 and 31."); return false; }
        if (comps[1] > 12) { alert("Month must be between 1 and 12."); return false; }
        return true;
    }

    // ============================================================
    // INCIDENT FORM VALIDATION
    // ============================================================

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
