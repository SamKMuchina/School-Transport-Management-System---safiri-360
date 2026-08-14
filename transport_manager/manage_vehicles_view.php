<?php
/**
 * manage_vehicles_view.php
 *
 * DISPLAY ONLY - no database queries, no business logic.
 *
 * Included by manage_vehicles.php after that file has already:
 *   - handled add/edit form submissions
 *   - run the search query if a search term was submitted
 *   - prepared these variables for us to display:
 *
 *       $username         (string)             - logged-in user's name
 *       $success_message  (string)             - success alert text, or ''
 *       $error_message    (string)             - error alert text, or ''
 *       $search_query     (string)             - current search box value, or ''
 *       $total_vehicles   (int)                - total vehicles for this school
 *       $vehicles_result  (mysqli result|null) - vehicle rows, or null if no
 *                                                search has been done yet
 *
 * BLANK DEFAULT STATE: $vehicles_result is null when no search has been
 * submitted. The table shows a prompt to search instead of all vehicles.
 * Three states in the table body:
 *   1. null          -> blank state "Enter a plate number to search"
 *   2. rows found    -> shows matching vehicles
 *   3. no rows found -> "No vehicles found matching..."
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vehicles - Transport Manager</title>
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
        <div class="menu-section"><div class="menu-section-title">Fleet and Routes</div><a href="manage_vehicles.php" class="menu-item active">Vehicles</a><a href="manage_routes.php" class="menu-item">Routes</a><a href="manage_route_stops.php" class="menu-item">Route Stops</a></div>
        <div class="menu-section"><div class="menu-section-title">Operations</div><a href="manage_trips.php" class="menu-item">Trips</a><a href="trip_monitoring.php" class="menu-item">Monitoring</a><a href="manage_incidents.php" class="menu-item">Incidents</a></div>
        <div class="menu-section"><div class="menu-section-title">Reports</div><a href="manager_reports.php" class="menu-item">Reports</a></div>
        <div class="menu-section"><div class="menu-section-title">Account</div><a href="../logout.php" class="menu-item">Logout</a></div>
    </div>

    <div class="main-wrapper">
        <div class="top-header">
            <div class="header-left"><h1>Manage Vehicles</h1></div>
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
                    <span class="section-title">Vehicles (<?php echo $total_vehicles; ?> total)</span>
                    <button class="btn btn-warning" onclick="openAddModal()">Add Vehicle</button>
                </div>

                <!-- SEARCH BAR - submits via GET so search term stays in URL -->
                <div class="filter-bar">
                    <form method="GET" action="manage_vehicles.php" name="searchForm" onsubmit="return validateVehicleSearch()">
                        <div class="filter-group">
                            <label>Search by Plate Number</label>
                            <input type="text" name="search" id="vehicle_search" class="form-input"
                                   placeholder="Enter plate number..."
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        <div class="filter-group" style="justify-content:flex-end;"><label>&nbsp;</label>
                            <div style="display:flex;gap:0.5rem;">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="manage_vehicles.php" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- RESULTS TABLE -->
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr><th>Plate Number</th><th>Capacity</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if (is_null($vehicles_result)): ?>
                                <!-- Default blank state - no search done yet -->
                                <tr>
                                    <td colspan="3" class="table-empty">
                                        Enter a plate number above and click Search to view vehicles.
                                    </td>
                                </tr>

                            <?php elseif (mysqli_num_rows($vehicles_result) > 0): ?>
                                <!-- Search results -->
                                <?php while ($vehicle = mysqli_fetch_assoc($vehicles_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($vehicle['plate_no']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['capacity']); ?> passengers</td>
                                    <td>
                                        <button class="btn btn-success btn-sm"
                                                onclick='openEditModal(<?php echo json_encode($vehicle); ?>)'>
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>

                            <?php else: ?>
                                <!-- Search returned no results -->
                                <tr>
                                    <td colspan="3" class="table-empty">
                                        No vehicles found matching "<?php echo htmlspecialchars($search_query); ?>".
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

<!-- ADD VEHICLE MODAL -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeAddModal()">&times;</button>
        <div class="modal-header"><span class="modal-title">Add New Vehicle</span></div>
        <form method="POST" action="manage_vehicles.php" name="addVehicleForm" onsubmit="return validateAddVehicleForm()">
            <input type="hidden" name="action" value="add_vehicle">
            <div class="form-group">
                <label class="form-label">Plate Number</label>
                <input type="text" name="plate_no" id="add_plate_no" class="form-input" placeholder="e.g. KAA 123A">
            </div>
            <div class="form-group">
                <label class="form-label">Capacity (number of passengers)</label>
                <input type="text" name="capacity" id="add_capacity" class="form-input" placeholder="e.g. 30">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn btn-warning">Add Vehicle</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT VEHICLE MODAL -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeEditModal()">&times;</button>
        <div class="modal-header"><span class="modal-title">Edit Vehicle</span></div>
        <form method="POST" action="manage_vehicles.php" name="editVehicleForm" onsubmit="return validateEditVehicleForm()">
            <input type="hidden" name="action"     value="edit_vehicle">
            <input type="hidden" name="vehicle_id" id="edit_vehicle_id">
            <div class="form-group">
                <label class="form-label">Plate Number</label>
                <input type="text" name="plate_no" id="edit_plate_no" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Capacity (number of passengers)</label>
                <input type="text" name="capacity" id="edit_capacity" class="form-input">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-success">Update Vehicle</button>
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

    function validateVehicleSearch() {
        var s = document.getElementById("vehicle_search").value;
        if (s.length == 0) {
            alert("Please enter a plate number to search.");
            document.getElementById("vehicle_search").focus();
            return false;
        }
        return true;
    }

    // ============================================================
    // ADD VEHICLE FORM VALIDATION
    // ============================================================

    function validateAddVehicleForm() {
        var plate = document.getElementById("add_plate_no").value;
        if (plate.length == 0) { alert("Plate number is required."); document.getElementById("add_plate_no").focus(); return false; }
        var cap = document.getElementById("add_capacity").value;
        if (cap.length == 0) { alert("Capacity is required."); document.getElementById("add_capacity").focus(); return false; }
        if (isNaN(cap) || cap <= 0) { alert("Capacity must be a number greater than 0."); document.getElementById("add_capacity").focus(); return false; }
        return true;
    }

    // ============================================================
    // EDIT VEHICLE FORM VALIDATION
    // ============================================================

    function validateEditVehicleForm() {
        var plate = document.getElementById("edit_plate_no").value;
        if (plate.length == 0) { alert("Plate number is required."); document.getElementById("edit_plate_no").focus(); return false; }
        var cap = document.getElementById("edit_capacity").value;
        if (cap.length == 0) { alert("Capacity is required."); document.getElementById("edit_capacity").focus(); return false; }
        if (isNaN(cap) || cap <= 0) { alert("Capacity must be a number greater than 0."); document.getElementById("edit_capacity").focus(); return false; }
        return true;
    }

    // ============================================================
    // MODAL FUNCTIONS
    // ============================================================

    function openAddModal()  { document.getElementById("addModal").className  = "modal active"; }
    function closeAddModal() { document.getElementById("addModal").className  = "modal"; }

    function openEditModal(vehicle) {
        // Populate the edit modal with the selected vehicle's current data
        document.getElementById("edit_vehicle_id").value = vehicle.vehicle_id;
        document.getElementById("edit_plate_no").value   = vehicle.plate_no;
        document.getElementById("edit_capacity").value   = vehicle.capacity;
        document.getElementById("editModal").className   = "modal active";
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
