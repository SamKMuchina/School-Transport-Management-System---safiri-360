<?php
/**
 * manage_routes_view.php
 *
 * DISPLAY ONLY - no database queries, no business logic.
 *
 * Included by manage_routes.php after that file has already:
 *   - handled add/edit form submissions
 *   - run the search query if a search term was submitted
 *   - prepared these variables for us to display:
 *
 *       $username          (string) - logged-in user's name
 *       $success_message   (string) - success alert text, or ''
 *       $error_message     (string) - error alert text, or ''
 *       $search_query      (string) - current search box value, or ''
 *       $total_routes      (int)    - total routes for this school
 *       $search_performed  (bool)   - true once a search has been submitted
 *       $routes_list       (array)  - matching route rows, empty if none
 *
 * BLANK DEFAULT STATE: Three states in the table body:
 *   1. $search_performed is false -> "Enter a route name to search"
 *   2. $routes_list has rows      -> shows matching routes
 *   3. $routes_list is empty      -> "No routes found matching..."
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Routes - Transport Manager</title>
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
        <div class="menu-section"><div class="menu-section-title">Fleet and Routes</div><a href="manage_vehicles.php" class="menu-item">Vehicles</a><a href="manage_routes.php" class="menu-item active">Routes</a><a href="manage_route_stops.php" class="menu-item">Route Stops</a></div>
        <div class="menu-section"><div class="menu-section-title">Operations</div><a href="manage_trips.php" class="menu-item">Trips</a><a href="trip_monitoring.php" class="menu-item">Monitoring</a><a href="manage_incidents.php" class="menu-item">Incidents</a></div>
        <div class="menu-section"><div class="menu-section-title">Reports</div><a href="manager_reports.php" class="menu-item">Reports</a></div>
        <div class="menu-section"><div class="menu-section-title">Account</div><a href="../logout.php" class="menu-item">Logout</a></div>
    </div>

    <div class="main-wrapper">
        <div class="top-header">
            <div class="header-left"><h1>Manage Routes</h1></div>
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
                    <span class="section-title">Routes (<?php echo $total_routes; ?> total)</span>
                    <button class="btn btn-warning" onclick="openAddModal()">Add Route</button>
                </div>

                <!-- SEARCH BAR - submits via GET so search term stays in URL -->
                <div class="filter-bar">
                    <form method="GET" action="manage_routes.php" name="searchForm" onsubmit="return validateRouteSearch()">
                        <div class="filter-group">
                            <label>Search by Route Name</label>
                            <input type="text" name="search" id="route_search" class="form-input"
                                   placeholder="Enter route name..."
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        <div class="filter-group filter-group-actions"><label>&nbsp;</label>
                            <div class="button-group">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="manage_routes.php" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- RESULTS TABLE -->
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr><th>Route Name</th><th>Description</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if (!$search_performed): ?>
                                <!-- Default blank state - no search done yet -->
                                <tr>
                                    <td colspan="3" class="table-empty">
                                        Enter a route name above and click Search to view routes.
                                    </td>
                                </tr>

                            <?php elseif (!empty($routes_list)): ?>
                                <!-- Search results -->
                                <?php foreach ($routes_list as $route): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($route['route_name']); ?></td>
                                    <td><?php echo htmlspecialchars($route['description'] ?: '-'); ?></td>
                                    <td>
                                        <div class="button-group">
                                            <button class="btn btn-success btn-sm"
                                                    onclick='openEditModal(<?php echo json_encode($route); ?>)'>
                                                Edit
                                            </button>
                                            <form method="POST" action="manage_routes.php" class="inline-form">
                                                <input type="hidden" name="action" value="delete_route">
                                                <input type="hidden" name="route_id" value="<?php echo $route['route_id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Delete the route &quot;<?php echo htmlspecialchars($route['route_name'], ENT_QUOTES); ?>&quot;? This cannot be undone.')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>

                            <?php else: ?>
                                <!-- Search returned no results -->
                                <tr>
                                    <td colspan="3" class="table-empty">
                                        No routes found matching "<?php echo htmlspecialchars($search_query); ?>".
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

<!-- ADD ROUTE MODAL -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeAddModal()">&times;</button>
        <div class="modal-header"><span class="modal-title">Add New Route</span></div>
        <form method="POST" action="manage_routes.php" name="addRouteForm" onsubmit="return validateAddRouteForm()">
            <input type="hidden" name="action" value="add_route">
            <div class="form-group">
                <label class="form-label">Route Name</label>
                <input type="text" name="route_name" id="add_route_name" class="form-input" placeholder="e.g. Westlands Route">
            </div>
            <div class="form-group">
                <label class="form-label">Description (optional)</label>
                <textarea name="description" id="add_description" class="form-textarea" placeholder="Brief description of the route..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn btn-warning">Add Route</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT ROUTE MODAL -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeEditModal()">&times;</button>
        <div class="modal-header"><span class="modal-title">Edit Route</span></div>
        <form method="POST" action="manage_routes.php" name="editRouteForm" onsubmit="return validateEditRouteForm()">
            <input type="hidden" name="action"   value="edit_route">
            <input type="hidden" name="route_id" id="edit_route_id">
            <div class="form-group">
                <label class="form-label">Route Name</label>
                <input type="text" name="route_name" id="edit_route_name" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Description (optional)</label>
                <textarea name="description" id="edit_description" class="form-textarea"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-success">Update Route</button>
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
    // SEARCH VALIDATION
    // ============================================================

    function validateRouteSearch() {
        var s = document.getElementById("route_search").value;
        if (s.length == 0) {
            alert("Please enter a route name to search.");
            document.getElementById("route_search").focus();
            return false;
        }
        return true;
    }

    // ============================================================
    // ADD ROUTE FORM VALIDATION
    // ============================================================

    function validateAddRouteForm() {
        var v = document.getElementById("add_route_name").value;
        if (v.length == 0) { alert("Route name is required."); document.getElementById("add_route_name").focus(); return false; }
        return true;
    }

    // ============================================================
    // EDIT ROUTE FORM VALIDATION
    // ============================================================

    function validateEditRouteForm() {
        var v = document.getElementById("edit_route_name").value;
        if (v.length == 0) { alert("Route name is required."); document.getElementById("edit_route_name").focus(); return false; }
        return true;
    }

    // ============================================================
    // MODAL FUNCTIONS
    // ============================================================

    function openAddModal()  { document.getElementById("addModal").className  = "modal active"; }
    function closeAddModal() { document.getElementById("addModal").className  = "modal"; }

    function openEditModal(route) {
        // Populate the edit modal with the selected route's current data
        document.getElementById("edit_route_id").value    = route.route_id;
        document.getElementById("edit_route_name").value  = route.route_name;
        document.getElementById("edit_description").value = route.description || '';
        document.getElementById("editModal").className    = "modal active";
    }

    function closeEditModal() { document.getElementById("editModal").className = "modal"; }

    // Close modal when clicking the dark overlay behind it
    window.onclick = function(event) {
        var addModal  = document.getElementById("addModal");
        var editModal = document.getElementById("editModal");
        if (event.target == addModal)  addModal.className  = "modal";
        if (event.target == editModal) editModal.className = "modal";
    };

</script>
</body>
</html>
