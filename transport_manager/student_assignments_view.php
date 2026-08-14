<?php
/**
 * student_assignments_view.php
 *
 * DISPLAY ONLY - no database queries, no business logic.
 *
 * Included by student_assignments.php after that file has already:
 *   - handled assign / edit / unassign form submissions
 *   - run every database query
 *   - prepared these variables for us to display:
 *
 *       $username               (string) - logged-in user's name, for header
 *       $success_message       (string) - success alert text, or ''
 *       $error_message         (string) - error alert text, or ''
 *       $total_students        (int)    - (not currently shown, kept for future stat card)
 *       $assigned_students     (int)    - (not currently shown, kept for future stat card)
 *       $unassigned_students   (int)    - (not currently shown, kept for future stat card)
 *       $unassigned_result     (mysqli result) - students with no route assignment
 *       $routes_with_students  (array)  - each route plus its assigned students
 *       $routes_list           (array)  - all routes, for the Assign/Edit dropdowns
 *       $stops_by_route        (array)  - every route's stops, keyed by route_id
 *
 * NO AJAX: Every route's stops are already rendered into a hidden
 * <select> per route (see "STOP OPTION SOURCES" below). When a route
 * is picked in the Assign/Edit modal, loadStops() just copies the
 * matching hidden <select>'s innerHTML into the visible Stop dropdown -
 * all the HTML came from PHP, JS only moves it, there's no server
 * round-trip and nothing is built from JSON.
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Assignments - Transport Manager</title>
    <!-- Styles: ../assets/css/style.css -->
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <!-- Collapsible Sidebar Toggle -->
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">&#9776;</button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-brand">School<span>Track</span><span class="sidebar-subtitle">Transport Manager</span></div>
        <div class="menu-section"><div class="menu-section-title">Main</div><a href="transport_manager_dashboard.php" class="menu-item">Dashboard</a></div>
        <div class="menu-section"><div class="menu-section-title">Student Management</div><a href="manage_students.php" class="menu-item">Students</a><a href="student_assignments.php" class="menu-item active">Assignments</a></div>
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
            <div class="header-left"><h1>Student Assignments</h1></div>
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

            <!-- ===== STAT CARDS ===== -->
            <!-- ===== UNASSIGNED STUDENTS TABLE ===== -->
            <div class="content-section">
                <div class="section-header">
                    <span class="section-title">Students Without Route Assignment</span>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Grade</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($unassigned_result && mysqli_num_rows($unassigned_result) > 0): ?>
                                <?php while ($student = mysqli_fetch_assoc($unassigned_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['fname'] . ' ' . $student['lname']); ?></td>
                                    <td><?php echo htmlspecialchars($student['grade']); ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm"
                                                onclick="openAssignModal(<?php echo $student['student_id']; ?>, '<?php echo htmlspecialchars($student['fname'] . ' ' . $student['lname'], ENT_QUOTES); ?>')">
                                            Assign
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="table-empty">All students are assigned to routes.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ===== ASSIGNED STUDENTS - ROUTE ACCORDION ===== -->
            <div class="content-section">
                <div class="section-header">
                    <span class="section-title">Students by Route</span>
                </div>

                <!-- Search Bar -->
                <div class="filter-bar mb-2">
                    <div class="filter-group">
                        <label>Search Student by Name</label>
                        <input type="text" id="studentSearchBar" class="form-input"
                               placeholder="Type student name to find their route..."
                               oninput="filterStudentRows()">
                    </div>
                    <div class="filter-group filter-group-actions">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-secondary" onclick="clearStudentSearch()">Clear</button>
                    </div>
                </div>

                <!-- Route Accordion -->
                <?php if (!empty($routes_with_students)): ?>
                    <?php foreach ($routes_with_students as $route): ?>
                    <div class="route-card" id="route-card-<?php echo $route['route_id']; ?>">
                        <div class="route-header" onclick="toggleRoute(<?php echo $route['route_id']; ?>)">
                            <div>
                                <span class="route-name">
                                    <?php echo htmlspecialchars($route['route_name']); ?>
                                    <span class="route-badge"><?php echo count($route['students']); ?> students</span>
                                </span>
                                <?php if (!empty($route['description'])): ?>
                                <div class="route-desc"><?php echo htmlspecialchars($route['description']); ?></div>
                                <?php endif; ?>
                            </div>
                            <span class="route-chevron" id="chevron-<?php echo $route['route_id']; ?>">&#9660;</span>
                        </div>
                        <div class="route-body" id="route-body-<?php echo $route['route_id']; ?>">
                            <?php if (!empty($route['students'])): ?>
                            <div class="table-wrapper">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Grade</th>
                                            <th>Stop</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($route['students'] as $assignment): ?>
                                        <tr class="student-row" data-name="<?php echo strtolower(htmlspecialchars($assignment['fname'] . ' ' . $assignment['lname'])); ?>">
                                            <td><?php echo htmlspecialchars($assignment['fname'] . ' ' . $assignment['lname']); ?></td>
                                            <td><?php echo htmlspecialchars($assignment['grade']); ?></td>
                                            <td><?php echo htmlspecialchars($assignment['stop_name']); ?></td>
                                            <td>
                                                <div class="button-group">
                                                    <button class="btn btn-success btn-sm"
                                                            onclick='openEditModal(<?php echo json_encode($assignment); ?>)'>
                                                        Edit
                                                    </button>
                                                    <form method="POST" action="student_assignments.php" class="inline-form">
                                                        <input type="hidden" name="action" value="unassign_student">
                                                        <input type="hidden" name="assignment_id" value="<?php echo $assignment['assignment_id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm"
                                                                onclick="return confirm('Are you sure you want to unassign this student?')">
                                                            Unassign
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                                <div class="info-box">No students assigned to this route yet.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="info-box">No routes found. Please create routes first.</div>
                <?php endif; ?>

            </div>

        </div>
    </div>
</div>

<!-- ============================================================
     STOP OPTION SOURCES
     One hidden <select> per route, each already filled in by PHP
     with that route's stops as <option> tags. loadStops() below just
     copies the matching one's innerHTML into the visible Stop
     dropdown when a route is picked - no AJAX, no JS building HTML.
============================================================ -->
<?php foreach ($routes_list as $route): ?>
<select class="stop-options-source" id="stops-for-route-<?php echo $route['route_id']; ?>">
    <option value="">Select Stop</option>
    <?php foreach ($stops_by_route[$route['route_id']] as $stop): ?>
    <option value="<?php echo $stop['stop_id']; ?>"><?php echo htmlspecialchars($stop['stop_name']); ?></option>
    <?php endforeach; ?>
</select>
<?php endforeach; ?>

<!-- ============================================================
     ASSIGN STUDENT MODAL
============================================================ -->
<div id="assignModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeAssignModal()">&times;</button>
        <div class="modal-header">
            <span class="modal-title">Assign Student</span>
            <span class="modal-subtitle" id="assign_student_name"></span>
        </div>
        <form method="POST" action="student_assignments.php" name="assignForm" onsubmit="return validateAssignForm()">
            <input type="hidden" name="action" value="assign_student">
            <input type="hidden" name="student_id" id="assign_student_id">
            <div class="form-group">
                <label class="form-label">Select Route</label>
                <select name="route_id" id="assign_route_id" class="form-select" onchange="loadStops(this.value, 'assign')">
                    <option value="">Select Route</option>
                    <?php foreach ($routes_list as $route): ?>
                        <option value="<?php echo $route['route_id']; ?>">
                            <?php echo htmlspecialchars($route['route_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Select Stop</label>
                <select name="stop_id" id="assign_stop_id" class="form-select">
                    <option value="">Select Route First</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAssignModal()">Cancel</button>
                <button type="submit" class="btn btn-warning">Assign Student</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     EDIT ASSIGNMENT MODAL
============================================================ -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeEditModal()">&times;</button>
        <div class="modal-header">
            <span class="modal-title">Edit Assignment</span>
            <span class="modal-subtitle" id="edit_student_name"></span>
        </div>
        <form method="POST" action="student_assignments.php" name="editAssignForm" onsubmit="return validateEditAssignForm()">
            <input type="hidden" name="action" value="edit_assignment">
            <input type="hidden" name="assignment_id" id="edit_assignment_id">
            <div class="form-group">
                <label class="form-label">Select Route</label>
                <select name="route_id" id="edit_route_id" class="form-select" onchange="loadStops(this.value, 'edit')">
                    <option value="">Select Route</option>
                    <?php foreach ($routes_list as $route): ?>
                        <option value="<?php echo $route['route_id']; ?>">
                            <?php echo htmlspecialchars($route['route_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Select Stop</label>
                <select name="stop_id" id="edit_stop_id" class="form-select">
                    <option value="">Select Route First</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-success">Update Assignment</button>
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
</script>

<!-- ============================================================
     JAVASCRIPT SECTION
============================================================ -->
<script>

    // ============================================================
    // ROUTE ACCORDION TOGGLE
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
    // STUDENT SEARCH - FILTER ROWS ACROSS ALL ROUTES
    // ============================================================

    /*
     * Filters student rows across all route accordions by name.
     * If a match is found in a route it automatically expands that route.
     * If search is cleared all routes collapse back.
     */
    function filterStudentRows() {
        var term      = document.getElementById("studentSearchBar").value.toLowerCase().trim();
        var routeCards = document.querySelectorAll(".route-card");

        for (var i = 0; i < routeCards.length; i++) {
            var card     = routeCards[i];
            var routeId  = card.id.replace("route-card-", "");
            var rows     = card.querySelectorAll(".student-row");
            var hasMatch = false;

            for (var j = 0; j < rows.length; j++) {
                var name = rows[j].getAttribute("data-name");
                if (term.length == 0 || name.indexOf(term) !== -1) {
                    rows[j].style.display = "";
                    if (term.length > 0) hasMatch = true;
                } else {
                    rows[j].style.display = "none";
                }
            }

            // Auto expand route if it has a matching student
            if (term.length > 0 && hasMatch) {
                document.getElementById("route-body-" + routeId).className = "route-body show";
                document.getElementById("chevron-" + routeId).innerHTML    = "&#9650;";
            } else if (term.length == 0) {
                document.getElementById("route-body-" + routeId).className = "route-body";
                document.getElementById("chevron-" + routeId).innerHTML    = "&#9660;";
            }
        }
    }

    function clearStudentSearch() {
        document.getElementById("studentSearchBar").value = "";
        filterStudentRows();
    }

    // ============================================================
    // ASSIGN FORM VALIDATION
    // ============================================================

    function validateAssignForm() {
        var rtned = true;
        rtned = validateAssignRoute();
        if (rtned == true) rtned = validateAssignStop();
        return rtned;
    }

    function validateAssignRoute() {
        var sel = document.getElementById("assign_route_id");
        if (sel.options[sel.selectedIndex].value == "") {
            alert("Please select a route.");
            sel.focus();
            return false;
        }
        return true;
    }

    function validateAssignStop() {
        var sel = document.getElementById("assign_stop_id");
        if (sel.options[sel.selectedIndex].value == "") {
            alert("Please select a stop.");
            sel.focus();
            return false;
        }
        return true;
    }

    // ============================================================
    // EDIT ASSIGNMENT FORM VALIDATION
    // ============================================================

    function validateEditAssignForm() {
        var rtned = true;
        rtned = validateEditRoute();
        if (rtned == true) rtned = validateEditStop();
        return rtned;
    }

    function validateEditRoute() {
        var sel = document.getElementById("edit_route_id");
        if (sel.options[sel.selectedIndex].value == "") {
            alert("Please select a route.");
            sel.focus();
            return false;
        }
        return true;
    }

    function validateEditStop() {
        var sel = document.getElementById("edit_stop_id");
        if (sel.options[sel.selectedIndex].value == "") {
            alert("Please select a stop.");
            sel.focus();
            return false;
        }
        return true;
    }

    // ============================================================
    // LOAD STOPS WHEN ROUTE IS SELECTED 
    // ============================================================

    /*
     * Every route's stops are already rendered on the page, one
     * hidden <select id="stops-for-route-X"> per route (see the
     * "STOP OPTION SOURCES" block). This just copies that select's
     * already-built <option> tags into the visible Stop dropdown -
     * no server round-trip, and no JavaScript building HTML.
     *
     * Optional selectedStopId pre-selects the current stop in edit
     * mode, since the copied markup itself has no "selected" set.
     */
    function loadStops(routeId, modalType, selectedStopId) {
        var stopSelect = document.getElementById(modalType + "_stop_id");

        if (!routeId) {
            stopSelect.innerHTML = "<option value=''>Select Route First</option>";
            return;
        }

        var source = document.getElementById("stops-for-route-" + routeId);

        if (!source) {
            stopSelect.innerHTML = "<option value=''>No stops found</option>";
            return;
        }

        stopSelect.innerHTML = source.innerHTML;

        // If editing, pre-select the stop the student is currently on
        if (selectedStopId) {
            stopSelect.value = selectedStopId;
        }
    }

    // ============================================================
    // MODAL FUNCTIONS - ASSIGN
    // ============================================================

    function openAssignModal(studentId, studentName) {
        document.getElementById("assign_student_id").value      = studentId;
        document.getElementById("assign_student_name").innerHTML = "Student: " + studentName;
        document.getElementById("assign_route_id").value         = "";
        document.getElementById("assign_stop_id").innerHTML      = "<option value=''>Select Route First</option>";
        document.getElementById("assignModal").className         = "modal active";
    }

    function closeAssignModal() {
        document.getElementById("assignModal").className = "modal";
    }

    // ============================================================
    // MODAL FUNCTIONS - EDIT
    // ============================================================

    function openEditModal(assignment) {
        document.getElementById("edit_assignment_id").value       = assignment.assignment_id;
        document.getElementById("edit_student_name").innerHTML    = "Student: " + assignment.fname + " " + assignment.lname;
        document.getElementById("edit_route_id").value            = assignment.route_id;
        document.getElementById("editModal").className            = "modal active";

        // Load stops and pre-select current stop
        loadStops(assignment.route_id, "edit", assignment.stop_id);
    }

    function closeEditModal() {
        document.getElementById("editModal").className = "modal";
    }

    // ============================================================
    // CLOSE MODALS WHEN CLICKING OUTSIDE
    // ============================================================

    window.onclick = function(event) {
        var assignModal = document.getElementById("assignModal");
        var editModal   = document.getElementById("editModal");
        if (event.target == assignModal) assignModal.className = "modal";
        if (event.target == editModal)   editModal.className   = "modal";
    };

</script>
</body>
</html>
