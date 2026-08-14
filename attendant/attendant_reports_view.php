<?php
/**
 * attendant_reports_view.php - DISPLAY ONLY
 *
 * Variables: $username, $active_tab, $date_from, $date_to,
 *            $filter_applied, $trips_data, $incidents_data
 *
 * CHANGES:
 * - Date range defaults to blank
 * - Both tabs share the same top date filter
 * - Both tabs show blank state until dates are entered
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports - Attendant</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">&#9776;</button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<div class="dashboard-container">
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">School<span>Track</span><span class="sidebar-subtitle">Attendant Portal</span></div>
        <div class="menu-section"><div class="menu-section-title">Main</div><a href="attendant_dashboard.php" class="menu-item">Dashboard</a></div>
        <div class="menu-section"><div class="menu-section-title">Trip Management</div><a href="upcoming_trips.php" class="menu-item">Upcoming Trips</a></div>
        <div class="menu-section"><div class="menu-section-title">Safety</div><a href="report_incident.php" class="menu-item">Report Incident</a></div>
        <div class="menu-section"><div class="menu-section-title">Reports</div><a href="attendant_reports.php" class="menu-item active">My Reports</a></div>
        <div class="menu-section"><div class="menu-section-title">Account</div><a href="../logout.php" class="menu-item">Logout</a></div>
    </div>
    <div class="main-wrapper">
        <div class="top-header">
            <div class="header-left"><h1>My Reports</h1></div>
            <div class="header-right"><span class="user-name"><?php echo htmlspecialchars($username); ?></span><a href="../logout.php" class="logout-btn">Logout</a></div>
        </div>
        <div class="dashboard-content">

            <!-- SHARED DATE FILTER - applies to both tabs simultaneously -->
            <form method="GET" action="attendant_reports.php" name="filterForm" onsubmit="return validateFilterForm()">
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
                <div class="filter-bar mb-1">
                    <div class="filter-group">
                        <label>From Date (dd/mm/yyyy)</label>
                        <input type="text" name="date_from" id="date_from" class="form-input"
                               placeholder="dd/mm/yyyy" maxlength="10"
                               value="<?php echo htmlspecialchars($date_from); ?>"
                               onmouseout="validateFromDate()">
                    </div>
                    <div class="filter-group">
                        <label>To Date (dd/mm/yyyy)</label>
                        <input type="text" name="date_to" id="date_to" class="form-input"
                               placeholder="dd/mm/yyyy" maxlength="10"
                               value="<?php echo htmlspecialchars($date_to); ?>"
                               onmouseout="validateToDate()">
                    </div>
                    <div class="filter-group filter-group-actions"><label>&nbsp;</label>
                        <div class="button-group">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="attendant_reports.php?tab=<?php echo $active_tab; ?>" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </div>
            </form>

            <!-- TAB NAVIGATION - plain links, preserves date filter in URL -->
            <div class="report-tabs">
                <a href="attendant_reports.php?tab=trips&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>"
                   class="tab-link <?php echo $active_tab==='trips'?'active':''; ?>">Trip History</a>
                <a href="attendant_reports.php?tab=incidents&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>"
                   class="tab-link <?php echo $active_tab==='incidents'?'active':''; ?>">Incident Log</a>
            </div>

            <!-- TRIPS TAB -->
            <div class="tab-panel <?php echo $active_tab==='trips' ? '' : 'tab-hidden'; ?>">
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead><tr><th>Trip Date</th><th>Route</th><th>Vehicle</th><th>Start Time</th><th>End Time</th><th>Status</th><th>Students</th></tr></thead>
                        <tbody>
                            <?php if (!$filter_applied): ?>
                                <tr><td colspan="7" class="table-empty">Enter a date range above and click Filter to view trip history.</td></tr>
                            <?php elseif (count($trips_data) > 0): ?>
                                <?php foreach ($trips_data as $trip): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($trip['trip_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($trip['route_name']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['plate_no']); ?></td>
                                    <td><?php echo $trip['start_time'] ? date('H:i', strtotime($trip['start_time'])) : '-'; ?></td>
                                    <td><?php echo $trip['end_time']   ? date('H:i', strtotime($trip['end_time']))   : '-'; ?></td>
                                    <td><?php $s=$trip['status'];$b='badge-secondary';if($s=='COMPLETED')$b='badge-success';if($s=='IN_PROGRESS')$b='badge-info';if($s=='PENDING')$b='badge-warning';?><span class="badge <?php echo $b;?>"><?php echo htmlspecialchars($s);?></span></td>
                                    <td><?php echo $trip['student_count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="table-empty">No trips found for the selected date range.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- INCIDENTS TAB -->
            <div class="tab-panel <?php echo $active_tab==='incidents' ? '' : 'tab-hidden'; ?>">
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead><tr><th>Reported At</th><th>Trip Date</th><th>Route</th><th>Incident Type</th><th>Description</th></tr></thead>
                        <tbody>
                            <?php if (!$filter_applied): ?>
                                <tr><td colspan="5" class="table-empty">Enter a date range above and click Filter to view incident log.</td></tr>
                            <?php elseif (count($incidents_data) > 0): ?>
                                <?php foreach ($incidents_data as $inc): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($inc['reported_at'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($inc['trip_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($inc['route_name']); ?></td>
                                    <td><?php echo htmlspecialchars($inc['incident_type']); ?></td>
                                    <td><?php echo htmlspecialchars($inc['description']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="table-empty">No incidents found for the selected date range.</td></tr>
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

    function validateDate(d) {
        if (d.indexOf("/") == -1) { alert("Date must be in dd/mm/yyyy format."); return false; }
        var c = d.split("/");
        if (c.length < 3 || c[2].length != 4) { alert("Date must be in dd/mm/yyyy format."); return false; }
        if (isNaN(c[0]) || isNaN(c[1]) || isNaN(c[2])) { alert("Date components must be numbers."); return false; }
        if (c[0] > 31) { alert("Day must be between 1 and 31."); return false; }
        if (c[1] > 12) { alert("Month must be between 1 and 12."); return false; }
        return true;
    }
    function validateFromDate() { var d=document.getElementById("date_from").value; if(d.length>0) return validateDate(d); return true; }
    function validateToDate()   { var d=document.getElementById("date_to").value;   if(d.length>0) return validateDate(d); return true; }
    function validateFilterForm() {
        var from = document.getElementById("date_from").value;
        var to   = document.getElementById("date_to").value;
        if (from.length == 0 || to.length == 0) { alert("Please enter both From Date and To Date."); return false; }
        if (!validateDate(from)) return false;
        if (!validateDate(to))   return false;
        return true;
    }
</script>
</body>
</html>
