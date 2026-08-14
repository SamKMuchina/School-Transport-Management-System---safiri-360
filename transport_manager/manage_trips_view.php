<?php
/**
 * manage_trips_view.php
 *
 * DISPLAY ONLY - no database queries, no business logic.
 *
 * Included by manage_trips.php after that file has already:
 *   - handled create, edit and delete form submissions
 *   - run the search query for PENDING trips on the searched date
 *   - fetched dropdown data for vehicles, routes, drivers, attendants
 *   - prepared these variables for us to display:
 *
 *       $username          (string) - logged-in user's name
 *       $success_message   (string) - success alert text, or ''
 *       $error_message     (string) - error alert text, or ''
 *       $search_date       (string) - current search date value, or ''
 *       $search_performed  (bool)   - true once a valid search date is submitted
 *       $trips_list        (array)  - matching PENDING trips, empty if none
 *       $vehicles_list     (array)  - vehicles dropdown options
 *       $routes_list       (array)  - routes dropdown options
 *       $drivers_list      (array)  - drivers dropdown options
 *       $attendants_list   (array)  - attendants dropdown options
 *
 * PAGE LAYOUT:
 * - Section 1: Create New Trip form
 * - Section 2: Find and Manage Trips (search by date, shows PENDING only)
 * - Edit Trip Modal (opened by clicking Edit on a search result)
 *
 * DATE FORMAT: All date inputs use dd/mm/yyyy format.
 * The validateDate() function follows the supervisor's validation blueprint:
 * checks "/" separator, splits using split("/"), checks isNaN(), checks
 * day <= 31 and month <= 12. The onmouseout event triggers per-field
 * validation as the user moves out of each date field.
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Trips - Transport Manager</title>
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
        <div class="menu-section"><div class="menu-section-title">Operations</div><a href="manage_trips.php" class="menu-item active">Trips</a><a href="trip_monitoring.php" class="menu-item">Monitoring</a><a href="manage_incidents.php" class="menu-item">Incidents</a></div>
        <div class="menu-section"><div class="menu-section-title">Reports</div><a href="manager_reports.php" class="menu-item">Reports</a></div>
        <div class="menu-section"><div class="menu-section-title">Account</div><a href="../logout.php" class="menu-item">Logout</a></div>
    </div>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">

        <!-- TOP HEADER -->
        <div class="top-header">
            <div class="header-left"><h1>Manage Trips</h1></div>
            <div class="header-right">
                <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="dashboard-content">

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <!-- ===== SECTION 1: CREATE TRIP FORM ===== -->
            <div class="content-section">
                <div class="section-header">
                    <span class="section-title">Create New Trip</span>
                </div>
                <div class="form-section">
                    <form method="POST" action="manage_trips.php" name="createTripForm" onsubmit="return validateCreateTripForm()">
                        <input type="hidden" name="action" value="create_trip">

                        <div class="form-group">
                            <label class="form-label">From Date (dd/mm/yyyy)</label>
                            <input type="text" name="date_from" id="create_date_from" class="form-input"
                                   placeholder="dd/mm/yyyy" maxlength="10" onmouseout="validateDateFrom()">
                        </div>

                        <div class="form-group">
                            <label class="form-label">To Date (dd/mm/yyyy)</label>
                            <input type="text" name="date_to" id="create_date_to" class="form-input"
                                   placeholder="dd/mm/yyyy" maxlength="10" onmouseout="validateDateTo()">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Vehicle</label>
                            <select name="vehicle_id" id="create_vehicle_id" class="form-select">
                                <option value="">Select Vehicle</option>
                                <?php foreach ($vehicles_list as $v): ?>
                                    <option value="<?php echo $v['vehicle_id']; ?>">
                                        <?php echo htmlspecialchars($v['plate_no']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Route</label>
                            <select name="route_id" id="create_route_id" class="form-select">
                                <option value="">Select Route</option>
                                <?php foreach ($routes_list as $r): ?>
                                    <option value="<?php echo $r['route_id']; ?>">
                                        <?php echo htmlspecialchars($r['route_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Driver</label>
                            <select name="driver_id" id="create_driver_id" class="form-select">
                                <option value="">Select Driver</option>
                                <?php foreach ($drivers_list as $d): ?>
                                    <option value="<?php echo $d['driver_id']; ?>">
                                        <?php echo htmlspecialchars($d['fname'] . ' ' . $d['lname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Attendant</label>
                            <select name="attendant_id" id="create_attendant_id" class="form-select">
                                <option value="">Select Attendant</option>
                                <?php foreach ($attendants_list as $a): ?>
                                    <option value="<?php echo $a['attendant_id']; ?>">
                                        <?php echo htmlspecialchars($a['fname'] . ' ' . $a['lname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-warning">Create Trip</button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- ===== SECTION 2: FIND AND MANAGE PENDING TRIPS ===== -->
            <div class="content-section">
                <div class="section-header">
                    <span class="section-title">Find and Manage Trips</span>
                </div>

                <!-- Date search bar - submits via GET so the search date stays in the URL -->
                <form method="GET" action="manage_trips.php" name="searchForm" onsubmit="return validateSearchDate()">
                    <div class="filter-bar">
                        <div class="filter-group">
                            <label>Search by Trip Date (dd/mm/yyyy)</label>
                            <input type="text" name="search_date" id="search_date" class="form-input"
                                   placeholder="dd/mm/yyyy" maxlength="10"
                                   value="<?php echo htmlspecialchars($search_date); ?>"
                                   onmouseout="validateSearchDateField()">
                        </div>
                        <div class="filter-group filter-group-actions"><label>&nbsp;</label>
                            <div class="button-group">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="manage_trips.php" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Results table - only shown after a search has been submitted -->
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Trip Date</th>
                                <th>Route</th>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Attendant</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$search_performed): ?>
                                <!-- Default state: no search done yet -->
                                <tr>
                                    <td colspan="6" class="table-empty">
                                        Enter a date above and click Search to find pending trips.
                                    </td>
                                </tr>

                            <?php elseif (!empty($trips_list)): ?>
                                <!-- Search results: show each pending trip with Edit and Delete buttons -->
                                <?php foreach ($trips_list as $trip): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($trip['trip_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($trip['route_name']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['plate_no']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['driver_fname'] . ' ' . $trip['driver_lname']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['attendant_fname'] . ' ' . $trip['attendant_lname']); ?></td>
                                    <td>
                                        <div class="button-group">

                                            <!-- Edit button: passes all trip data to JS which opens the edit modal -->
                                            <button class="btn btn-success btn-sm"
                                                    onclick='openEditModal(<?php echo json_encode($trip); ?>)'>
                                                Edit
                                            </button>

                                            <!-- Delete button: plain form POST with a confirmation prompt -->
                                            <form method="POST" action="manage_trips.php" class="inline-form">
                                                <input type="hidden" name="action"  value="delete_trip">
                                                <input type="hidden" name="trip_id" value="<?php echo $trip['trip_id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Delete this trip? This cannot be undone.')">
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
                                    <td colspan="6" class="table-empty">
                                        No pending trips found for <?php echo htmlspecialchars($search_date); ?>.
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
     EDIT TRIP MODAL
     Opened by clicking Edit on a search result row.
     Populated with the selected trip's current data via JavaScript.
============================================================ -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeEditModal()">&times;</button>
        <div class="modal-header"><span class="modal-title">Edit Trip</span></div>
        <form method="POST" action="manage_trips.php" name="editTripForm" onsubmit="return validateEditTripForm()">
            <input type="hidden" name="action"  value="edit_trip">
            <input type="hidden" name="trip_id" id="edit_trip_id">

            <div class="form-group">
                <label class="form-label">Trip Date (dd/mm/yyyy)</label>
                <input type="text" name="trip_date" id="edit_trip_date" class="form-input"
                       placeholder="dd/mm/yyyy" maxlength="10" onmouseout="validateEditDate()">
            </div>

            <div class="form-group">
                <label class="form-label">Vehicle</label>
                <select name="vehicle_id" id="edit_vehicle_id" class="form-select">
                    <option value="">Select Vehicle</option>
                    <?php foreach ($vehicles_list as $v): ?>
                        <option value="<?php echo $v['vehicle_id']; ?>">
                            <?php echo htmlspecialchars($v['plate_no']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Route</label>
                <select name="route_id" id="edit_route_id" class="form-select">
                    <option value="">Select Route</option>
                    <?php foreach ($routes_list as $r): ?>
                        <option value="<?php echo $r['route_id']; ?>">
                            <?php echo htmlspecialchars($r['route_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Driver</label>
                <select name="driver_id" id="edit_driver_id" class="form-select">
                    <option value="">Select Driver</option>
                    <?php foreach ($drivers_list as $d): ?>
                        <option value="<?php echo $d['driver_id']; ?>">
                            <?php echo htmlspecialchars($d['fname'] . ' ' . $d['lname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Attendant</label>
                <select name="attendant_id" id="edit_attendant_id" class="form-select">
                    <option value="">Select Attendant</option>
                    <?php foreach ($attendants_list as $a): ?>
                        <option value="<?php echo $a['attendant_id']; ?>">
                            <?php echo htmlspecialchars($a['fname'] . ' ' . $a['lname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-success">Update Trip</button>
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
    // DATE VALIDATION - follows supervisor's validation blueprint
    // ============================================================

    /*
     * Validates a date entered as dd/mm/yyyy.
     * Steps match the supervisor's document exactly:
     * 1. Check "/" separator is present
     * 2. Split into components using split("/")
     * 3. Check 3 components exist and year is 4 digits
     * 4. Check all components are numbers using isNaN()
     * 5. Check day is not greater than 31
     * 6. Check month is not greater than 12
     * Returns true if valid, false and shows alert if not.
     */
    function validateDate(cdate) {
        if (cdate.indexOf("/") == -1) {
            alert("Date must be entered in the format dd/mm/yyyy");
            return false;
        }
        var comps = cdate.split("/");
        if (comps.length < 3 || comps[0].length < 1 || comps[1].length < 1 || comps[2].length != 4) {
            alert("Date must be entered in the format dd/mm/yyyy");
            return false;
        }
        if (isNaN(comps[0]) || isNaN(comps[1]) || isNaN(comps[2])) {
            alert("Date components must be numbers");
            return false;
        }
        if (comps[0] > 31) {
            alert("Day value is out of range. Must be between 1 and 31");
            return false;
        }
        if (comps[1] > 12) {
            alert("Month value must be in the range of 1 to 12");
            return false;
        }
        return true;
    }

    // ============================================================
    // PER-FIELD VALIDATORS (triggered by onmouseout on each field)
    // ============================================================

    function validateDateFrom() {
        var d = document.getElementById("create_date_from").value;
        // Only validate if user has typed something - empty is caught on submit
        if (d.length == 0) return true;
        return validateDate(d);
    }

    function validateDateTo() {
        var d = document.getElementById("create_date_to").value;
        if (d.length == 0) return true;
        return validateDate(d);
    }

    function validateSearchDateField() {
        var d = document.getElementById("search_date").value;
        if (d.length == 0) return true;
        return validateDate(d);
    }

    function validateEditDate() {
        var d = document.getElementById("edit_trip_date").value;
        if (d.length == 0) return true;
        return validateDate(d);
    }

    // ============================================================
    // CREATE TRIP FORM VALIDATION (triggered by onsubmit)
    // ============================================================

    function validateCreateTripForm() {

        // Check From Date
        var from = document.getElementById("create_date_from").value;
        if (from.length == 0) { alert("From date is required."); document.getElementById("create_date_from").focus(); return false; }
        if (!validateDate(from)) return false;

        // Check To Date
        var to = document.getElementById("create_date_to").value;
        if (to.length == 0) { alert("To date is required."); document.getElementById("create_date_to").focus(); return false; }
        if (!validateDate(to)) return false;

        // Check Vehicle
        var sel = document.getElementById("create_vehicle_id");
        if (sel.options[sel.selectedIndex].value == "") { alert("Please select a vehicle."); sel.focus(); return false; }

        // Check Route
        sel = document.getElementById("create_route_id");
        if (sel.options[sel.selectedIndex].value == "") { alert("Please select a route."); sel.focus(); return false; }

        // Check Driver
        sel = document.getElementById("create_driver_id");
        if (sel.options[sel.selectedIndex].value == "") { alert("Please select a driver."); sel.focus(); return false; }

        // Check Attendant
        sel = document.getElementById("create_attendant_id");
        if (sel.options[sel.selectedIndex].value == "") { alert("Please select an attendant."); sel.focus(); return false; }

        return true;
    }

    // ============================================================
    // SEARCH FORM VALIDATION (triggered by onsubmit)
    // ============================================================

    function validateSearchDate() {
        var d = document.getElementById("search_date").value;
        if (d.length == 0) { alert("Please enter a date to search."); document.getElementById("search_date").focus(); return false; }
        return validateDate(d);
    }

    // ============================================================
    // EDIT TRIP FORM VALIDATION (triggered by onsubmit)
    // ============================================================

    function validateEditTripForm() {

        // Check Trip Date
        var d = document.getElementById("edit_trip_date").value;
        if (d.length == 0) { alert("Trip date is required."); document.getElementById("edit_trip_date").focus(); return false; }
        if (!validateDate(d)) return false;

        // Check Vehicle
        var sel = document.getElementById("edit_vehicle_id");
        if (sel.options[sel.selectedIndex].value == "") { alert("Please select a vehicle."); sel.focus(); return false; }

        // Check Route
        sel = document.getElementById("edit_route_id");
        if (sel.options[sel.selectedIndex].value == "") { alert("Please select a route."); sel.focus(); return false; }

        // Check Driver
        sel = document.getElementById("edit_driver_id");
        if (sel.options[sel.selectedIndex].value == "") { alert("Please select a driver."); sel.focus(); return false; }

        // Check Attendant
        sel = document.getElementById("edit_attendant_id");
        if (sel.options[sel.selectedIndex].value == "") { alert("Please select an attendant."); sel.focus(); return false; }

        return true;
    }

    // ============================================================
    // EDIT MODAL FUNCTIONS
    // ============================================================

    /*
     * Opens the edit modal and populates it with the selected trip's
     * current data. The trip_date comes from MySQL as YYYY-MM-DD so we
     * convert it to dd/mm/yyyy before showing it in the date field.
     */
    function openEditModal(trip) {

        // Convert YYYY-MM-DD from database back to dd/mm/yyyy for display
        var parts       = trip.trip_date.split("-");
        var displayDate = parts[2] + "/" + parts[1] + "/" + parts[0];

        document.getElementById("edit_trip_id").value      = trip.trip_id;
        document.getElementById("edit_trip_date").value    = displayDate;
        document.getElementById("edit_vehicle_id").value   = trip.vehicle_id;
        document.getElementById("edit_route_id").value     = trip.route_id;
        document.getElementById("edit_driver_id").value    = trip.driver_id;
        document.getElementById("edit_attendant_id").value = trip.attendant_id;
        document.getElementById("editModal").className     = "modal active";
    }

    function closeEditModal() {
        document.getElementById("editModal").className = "modal";
    }

    // Close modal when clicking the dark overlay behind it
    window.onclick = function(event) {
        var modal = document.getElementById("editModal");
        if (event.target == modal) modal.className = "modal";
    };

</script>
</body>
</html>
