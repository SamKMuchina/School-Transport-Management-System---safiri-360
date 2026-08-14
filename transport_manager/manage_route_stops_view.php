<?php
/**
 * manage_route_stops_view.php
 *
 * DISPLAY ONLY - no database queries, no business logic.
 *
 * Included by manage_route_stops.php after that file has already:
 *   - handled move-stop / add / edit stop submissions
 *   - run every database query
 *   - prepared these variables for us to display:
 *
 *       $username       (string) - logged-in user's name, for header
 *       $success_message (string) - success alert text, or ''
 *       $error_message  (string) - error alert text, or ''
 *       $routes         (array)  - all routes for this school
 *       $route_stops    (array)  - stops for each route, keyed by route_id
 *       $total_routes   (int)    - (not currently shown, kept for future stat card)
 *       $total_stops    (int)    - (not currently shown, kept for future stat card)
 *
 * Up/Down are plain forms now - each submits a normal POST back to
 * manage_route_stops.php and the page reloads with the new order.
 *
 * 
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Route Stops - Transport Manager</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <!-- Collapsible Sidebar Toggle -->
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">&#9776;</button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<div class="dashboard-container">
    <div class="sidebar">
        <div class="sidebar-brand">School<span>Track</span><span class="sidebar-subtitle">Transport Manager</span></div>
        <div class="menu-section"><div class="menu-section-title">Main</div><a href="transport_manager_dashboard.php" class="menu-item">Dashboard</a></div>
        <div class="menu-section"><div class="menu-section-title">Student Management</div><a href="manage_students.php" class="menu-item">Students</a><a href="student_assignments.php" class="menu-item">Assignments</a></div>
        <div class="menu-section"><div class="menu-section-title">Staff Management</div><a href="manage_drivers.php" class="menu-item">Drivers</a><a href="manage_attendants.php" class="menu-item">Attendants</a></div>
        <div class="menu-section"><div class="menu-section-title">Fleet and Routes</div><a href="manage_vehicles.php" class="menu-item">Vehicles</a><a href="manage_routes.php" class="menu-item">Routes</a><a href="manage_route_stops.php" class="menu-item active">Route Stops</a></div>
        <div class="menu-section"><div class="menu-section-title">Operations</div><a href="manage_trips.php" class="menu-item">Trips</a><a href="trip_monitoring.php" class="menu-item">Monitoring</a><a href="manage_incidents.php" class="menu-item">Incidents</a></div>
        <div class="menu-section"><div class="menu-section-title">Reports</div><a href="manager_reports.php" class="menu-item">Reports</a></div>
        <div class="menu-section"><div class="menu-section-title">Account</div><a href="../logout.php" class="menu-item">Logout</a></div>
    </div>
    <div class="main-wrapper">
        <div class="top-header">
            <div class="header-left"><h1>Manage Route Stops</h1></div>
            <div class="header-right"><span class="user-name"><?php echo htmlspecialchars($username); ?></span><a href="../logout.php" class="logout-btn">Logout</a></div>
        </div>
        <div class="dashboard-content">
            <?php if (!empty($success_message)): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
            <?php if (!empty($error_message)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>
            <div class="content-section">
                <div class="section-header"><span class="section-title">Routes and Stops Management</span></div>

                <?php if (empty($routes)): ?>
                    <div class="info-box">No routes found. Please create a route first from the <a href="manage_routes.php">Manage Routes</a> page.</div>
                <?php else: ?>
                    <?php foreach ($routes as $route): ?>
                    <div class="route-card">
                        <div class="route-header" onclick="toggleRoute(<?php echo $route['route_id']; ?>)">
                            <div>
                                <span class="route-name">
                                    <?php echo htmlspecialchars($route['route_name']); ?>
                                    <span class="route-badge"><?php echo count($route_stops[$route['route_id']]); ?> stops</span>
                                </span>
                                <div class="route-desc"><?php echo htmlspecialchars($route['description'] ?? 'No description'); ?></div>
                            </div>
                            <span class="route-chevron" id="chevron-<?php echo $route['route_id']; ?>">&#9660;</span>
                        </div>
                        <div class="route-body" id="route-body-<?php echo $route['route_id']; ?>">
                            <div class="route-actions">
                                <button class="btn btn-warning btn-sm"
                                        onclick="openAddStopModal(<?php echo $route['route_id']; ?>, '<?php echo htmlspecialchars($route['route_name'], ENT_QUOTES); ?>')">
                                    Add Stop
                                </button>
                            </div>
                            <?php if (empty($route_stops[$route['route_id']])): ?>
                                <div class="info-box">No stops added yet. Click "Add Stop" to get started.</div>
                            <?php else: ?>
                                <div class="table-wrapper">
                                    <table class="data-table">
                                        <thead>
                                            <tr><th style="width:60px;">Order</th><th>Stop Name</th><th>Address</th><th style="width:150px;">Actions</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($route_stops[$route['route_id']] as $stop): ?>
                                            <tr>
                                                <td><span class="stop-order-badge"><?php echo $stop['stop_order']; ?></span></td>
                                                <td><?php echo htmlspecialchars($stop['stop_name']); ?></td>
                                                <td><?php echo htmlspecialchars($stop['address']); ?></td>
                                                <td>
                                                    <div class="button-group">
                                                        <button class="btn btn-success btn-sm"
                                                                onclick="openEditStopModal(<?php echo $stop['stop_id']; ?>, <?php echo $route['route_id']; ?>, '<?php echo htmlspecialchars($stop['stop_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($stop['address'], ENT_QUOTES); ?>')">
                                                            Edit
                                                        </button>
                                                        <form method="POST" action="manage_route_stops.php" class="inline-form">
                                                            <input type="hidden" name="move_stop" value="1">
                                                            <input type="hidden" name="stop_id" value="<?php echo $stop['stop_id']; ?>">
                                                            <input type="hidden" name="route_id" value="<?php echo $route['route_id']; ?>">
                                                            <input type="hidden" name="direction" value="up">
                                                            <button type="submit" class="btn btn-secondary btn-sm">Up</button>
                                                        </form>
                                                        <form method="POST" action="manage_route_stops.php" class="inline-form">
                                                            <input type="hidden" name="move_stop" value="1">
                                                            <input type="hidden" name="stop_id" value="<?php echo $stop['stop_id']; ?>">
                                                            <input type="hidden" name="route_id" value="<?php echo $route['route_id']; ?>">
                                                            <input type="hidden" name="direction" value="down">
                                                            <button type="submit" class="btn btn-secondary btn-sm">Down</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ADD STOP MODAL -->
<div id="addStopModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeAddStopModal()">&times;</button>
        <div class="modal-header">
            <span class="modal-title">Add Stop to <span id="add_route_name_display"></span></span>
        </div>
        <form method="POST" action="manage_route_stops.php" name="addStopForm" onsubmit="return validateAddStopForm()">
            <input type="hidden" name="route_id" id="add_route_id">
            <input type="hidden" name="add_stop" value="1">
            <div class="form-group">
                <label class="form-label">Stop Name</label>
                <input type="text" name="stop_name" id="add_stop_name" class="form-input" placeholder="e.g., Main Street Junction">
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea name="address" id="add_stop_address" class="form-textarea" placeholder="Enter the full address of the stop"></textarea>
            </div>
            <div class="info-box">Stop order will be assigned automatically as the next number.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddStopModal()">Cancel</button>
                <button type="submit" class="btn btn-warning">Add Stop</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT STOP MODAL -->
<div id="editStopModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeEditStopModal()">&times;</button>
        <div class="modal-header"><span class="modal-title">Edit Stop</span></div>
        <form method="POST" action="manage_route_stops.php" name="editStopForm" onsubmit="return validateEditStopForm()">
            <input type="hidden" name="stop_id" id="edit_stop_id">
            <input type="hidden" name="route_id" id="edit_stop_route_id">
            <input type="hidden" name="edit_stop" value="1">
            <div class="form-group">
                <label class="form-label">Stop Name</label>
                <input type="text" name="stop_name" id="edit_stop_name" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea name="address" id="edit_stop_address" class="form-textarea"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditStopModal()">Cancel</button>
                <button type="submit" class="btn btn-success">Update Stop</button>
            </div>
        </form>
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
    // ADD STOP FORM VALIDATION
    // ============================================================

    function validateAddStopForm() {
        var rtned = true;
        rtned = validateAddStopName();
        if (rtned == true) rtned = validateAddStopAddress();
        return rtned;
    }

    function validateAddStopName() {
        var v = document.getElementById("add_stop_name").value;
        if (v.length == 0) { alert("Stop name is required."); document.getElementById("add_stop_name").focus(); return false; }
        return true;
    }

    function validateAddStopAddress() {
        var v = document.getElementById("add_stop_address").value;
        if (v.length == 0) { alert("Address is required."); document.getElementById("add_stop_address").focus(); return false; }
        return true;
    }

    // ============================================================
    // EDIT STOP FORM VALIDATION
    // ============================================================

    function validateEditStopForm() {
        var rtned = true;
        rtned = validateEditStopName();
        if (rtned == true) rtned = validateEditStopAddress();
        return rtned;
    }

    function validateEditStopName() {
        var v = document.getElementById("edit_stop_name").value;
        if (v.length == 0) { alert("Stop name is required."); document.getElementById("edit_stop_name").focus(); return false; }
        return true;
    }

    function validateEditStopAddress() {
        var v = document.getElementById("edit_stop_address").value;
        if (v.length == 0) { alert("Address is required."); document.getElementById("edit_stop_address").focus(); return false; }
        return true;
    }

    // ============================================================
    // ROUTE TOGGLE
    // ============================================================

    function toggleRoute(routeId) {
        var body    = document.getElementById("route-body-" + routeId);
        var chevron = document.getElementById("chevron-" + routeId);
        if (body.className.indexOf("show") !== -1) {
            body.className = "route-body";
            chevron.innerHTML = "&#9660;";
        } else {
            body.className = "route-body show";
            chevron.innerHTML = "&#9650;";
        }
    }

    // ============================================================
    // MODAL FUNCTIONS - ADD STOP
    // ============================================================

    function openAddStopModal(routeId, routeName) {
        document.getElementById("add_route_id").value              = routeId;
        document.getElementById("add_route_name_display").innerHTML = routeName;
        document.getElementById("addStopModal").className           = "modal active";
    }

    function closeAddStopModal() {
        document.getElementById("addStopModal").className = "modal";
    }

    // ============================================================
    // MODAL FUNCTIONS - EDIT STOP
    // ============================================================

    function openEditStopModal(stopId, routeId, stopName, address) {
        document.getElementById("edit_stop_id").value        = stopId;
        document.getElementById("edit_stop_route_id").value  = routeId;
        document.getElementById("edit_stop_name").value      = stopName;
        document.getElementById("edit_stop_address").value   = address;
        document.getElementById("editStopModal").className   = "modal active";
    }

    function closeEditStopModal() {
        document.getElementById("editStopModal").className = "modal";
    }

    // ============================================================
    // CLOSE MODALS WHEN CLICKING OUTSIDE
    // ============================================================

    window.onclick = function(event) {
        var addModal  = document.getElementById("addStopModal");
        var editModal = document.getElementById("editStopModal");
        if (event.target == addModal)  addModal.className  = "modal";
        if (event.target == editModal) editModal.className = "modal";
    };
</script>
</body>
</html>
