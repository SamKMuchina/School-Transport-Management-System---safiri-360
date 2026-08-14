<?php
/**
 * manage_attendants_view.php
 *
 * DISPLAY ONLY - no database queries, no business logic.
 *
 * Included by manage_attendants.php after that file has already:
 *   - run every database query
 *   - handled add / edit / toggle-status form submissions
 *   - prepared these variables for us to display:
 *
 *       $username         (string)             - logged-in user's name, for header
 *       $success_message  (string)             - success alert text, or ''
 *       $error_message    (string)             - error alert text, or ''
 *       $search_query     (string)             - current search box value, or ''
 *       $total_attendants    (int)                - total attendants for this school
 *       $attendants_result   (mysqli result|null) - attendant rows to list, or null if
 *                                                no search has been done yet
 *
 * The only PHP allowed in this file is: echo-ing the variables above,
 * and simple if/while loops to display them. No mysqli_query() calls,
 * no INSERT/UPDATE/SELECT, no validation logic belongs in this file.
 *
 * BLANK DEFAULT STATE: $attendants_result is null when no search has been
 * submitted. The table shows a prompt to search instead of all attendants.
 * Once a search is submitted, results appear filtered by name or phone.
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Attendants - Transport Manager</title>
    <!-- Styles: ../assets/css/style.css -->
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <!-- Collapsible Sidebar Toggle -->
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">&#9776;</button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            School<span>Track</span>
            <span class="sidebar-subtitle">Transport Manager</span>
        </div>
        <div class="menu-section">
            <div class="menu-section-title">Main</div>
            <a href="transport_manager_dashboard.php" class="menu-item">Dashboard</a>
        </div>
        <div class="menu-section">
            <div class="menu-section-title">Student Management</div>
            <a href="manage_students.php" class="menu-item">Students</a>
            <a href="student_assignments.php" class="menu-item">Assignments</a>
        </div>
        <div class="menu-section">
            <div class="menu-section-title">Staff Management</div>
            <a href="manage_drivers.php" class="menu-item">Drivers</a>
            <a href="manage_attendants.php" class="menu-item active">Attendants</a>
        </div>
        <div class="menu-section">
            <div class="menu-section-title">Fleet and Routes</div>
            <a href="manage_vehicles.php" class="menu-item">Vehicles</a>
            <a href="manage_routes.php" class="menu-item">Routes</a>
            <a href="manage_route_stops.php" class="menu-item">Route Stops</a>
        </div>
        <div class="menu-section">
            <div class="menu-section-title">Operations</div>
            <a href="manage_trips.php" class="menu-item">Trips</a>
            <a href="trip_monitoring.php" class="menu-item">Monitoring</a>
            <a href="manage_incidents.php" class="menu-item">Incidents</a>
        </div>
        <div class="menu-section">
            <div class="menu-section-title">Reports</div>
            <a href="manager_reports.php" class="menu-item">Reports</a>
        </div>
        <div class="menu-section">
            <div class="menu-section-title">Account</div>
            <a href="../logout.php" class="menu-item">Logout</a>
        </div>
    </div>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">

        <!-- TOP HEADER -->
        <div class="top-header">
            <div class="header-left"><h1>Manage Attendants</h1></div>
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

            <div class="content-section">
                <div class="section-header">
                    <span class="section-title">Attendants (<?php echo $total_attendants; ?> total)</span>
                    <button class="btn btn-warning" onclick="openAddModal()">Add Attendant</button>
                </div>

                <!-- SEARCH BAR -->
                <!-- Submits via GET so the search term stays in the URL -->
                <div class="filter-bar">
                    <form method="GET" action="manage_attendants.php" name="searchForm" onsubmit="return validateAttendantSearch()">
                        <div class="filter-group">
                            <label>Search by Name or Phone</label>
                            <input type="text"
                                   name="search"
                                   id="attendant_search"
                                   class="form-input"
                                   placeholder="Enter name or phone..."
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        <div class="filter-group" style="justify-content:flex-end;">
                            <label>&nbsp;</label>
                            <div style="display:flex; gap:0.5rem;">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="manage_attendants.php" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- RESULTS TABLE -->
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Attendant Name</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (is_null($attendants_result)): ?>
                                <!-- Default blank state - no search done yet -->
                                <tr>
                                    <td colspan="4" class="table-empty">
                                        Enter a name or phone number above and click Search to view attendants.
                                    </td>
                                </tr>

                            <?php elseif (mysqli_num_rows($attendants_result) > 0): ?>
                                <!-- Search results -->
                                <?php while ($attendant = mysqli_fetch_assoc($attendants_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($attendant['fname'] . ' ' . $attendant['lname']); ?></td>
                                    <td><?php echo htmlspecialchars($attendant['phone']); ?></td>
                                    <td>
                                        <?php if ($attendant['is_active'] == 1): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:0.4rem; flex-wrap:wrap;">
                                            <!-- Edit button passes attendant data to JS which opens the modal -->
                                            <button class="btn btn-success btn-sm"
                                                    onclick='openEditModal(<?php echo json_encode($attendant); ?>)'>
                                                Edit
                                            </button>

                                            <!-- Toggle status - plain form POST, no AJAX needed -->
                                            <?php if ($attendant['is_active'] == 1): ?>
                                                <form method="POST" action="manage_attendants.php" style="display:inline;">
                                                    <input type="hidden" name="action"     value="toggle_status">
                                                    <input type="hidden" name="user_id"    value="<?php echo $attendant['user_id']; ?>">
                                                    <input type="hidden" name="new_status" value="0">
                                                    <button type="submit" class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Deactivate this attendant?')">
                                                        Deactivate
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" action="manage_attendants.php" style="display:inline;">
                                                    <input type="hidden" name="action"     value="toggle_status">
                                                    <input type="hidden" name="user_id"    value="<?php echo $attendant['user_id']; ?>">
                                                    <input type="hidden" name="new_status" value="1">
                                                    <button type="submit" class="btn btn-success btn-sm">Activate</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>

                            <?php else: ?>
                                <!-- Search was done but no results found -->
                                <tr>
                                    <td colspan="4" class="table-empty">
                                        No attendants found matching "<?php echo htmlspecialchars($search_query); ?>".
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
     ADD ATTENDANT MODAL
============================================================ -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeAddModal()">&times;</button>
        <div class="modal-header"><span class="modal-title">Add New Attendant</span></div>
        <form method="POST" action="manage_attendants.php" name="addAttendantForm" onsubmit="return validateAddAttendantForm()">
            <input type="hidden" name="action" value="add_attendant">
            <div class="form-group">
                <label class="form-label">First Name</label>
                <input type="text" name="fname" id="add_fname" class="form-input" placeholder="e.g. John">
            </div>
            <div class="form-group">
                <label class="form-label">Last Name</label>
                <input type="text" name="lname" id="add_lname" class="form-input" placeholder="e.g. Doe">
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" id="add_phone" class="form-input" placeholder="e.g. 0712345678">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn btn-warning">Add Attendant</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     EDIT ATTENDANT MODAL
============================================================ -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeEditModal()">&times;</button>
        <div class="modal-header"><span class="modal-title">Edit Attendant</span></div>
        <form method="POST" action="manage_attendants.php" name="editAttendantForm" onsubmit="return validateEditAttendantForm()">
            <input type="hidden" name="action"    value="edit_attendant">
            <input type="hidden" name="attendant_id" id="edit_attendant_id">
            <div class="form-group">
                <label class="form-label">First Name</label>
                <!-- Read-only: names cannot be changed after registration -->
                <input type="text" id="edit_fname" class="form-input" readonly style="background-color:#f0f0f0;">
            </div>
            <div class="form-group">
                <label class="form-label">Last Name</label>
                <input type="text" id="edit_lname" class="form-input" readonly style="background-color:#f0f0f0;">
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" id="edit_phone" class="form-input">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-success">Update Attendant</button>
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

    function validateAttendantSearch() {
        var s = document.getElementById("attendant_search").value;
        if (s.length == 0) {
            alert("Please enter a name or phone number to search.");
            document.getElementById("attendant_search").focus();
            return false;
        }
        return true;
    }

    // ============================================================
    // ADD ATTENDANT FORM VALIDATION
    // ============================================================

    function validateAddAttendantForm() {
        var rtned = true;
        rtned = validateAddFname();
        if (rtned == true) rtned = validateAddLname();
        if (rtned == true) rtned = validateAddPhone();
        return rtned;
    }

    function validateAddFname() {
        var v = document.getElementById("add_fname").value;
        if (v.length == 0) { alert("First name is required."); document.getElementById("add_fname").focus(); return false; }
        return true;
    }

    function validateAddLname() {
        var v = document.getElementById("add_lname").value;
        if (v.length == 0) { alert("Last name is required."); document.getElementById("add_lname").focus(); return false; }
        return true;
    }

    function validateAddPhone() {
        var v = document.getElementById("add_phone").value;
        if (v.length == 0) { alert("Phone number is required."); document.getElementById("add_phone").focus(); return false; }
        return true;
    }

    // ============================================================
    // EDIT ATTENDANT FORM VALIDATION
    // ============================================================

    function validateEditAttendantForm() {
        var v = document.getElementById("edit_phone").value;
        if (v.length == 0) { alert("Phone number is required."); document.getElementById("edit_phone").focus(); return false; }
        return true;
    }

    // ============================================================
    // MODAL FUNCTIONS
    // ============================================================

    function openAddModal() {
        document.getElementById("addModal").className = "modal active";
    }

    function closeAddModal() {
        document.getElementById("addModal").className = "modal";
    }

    function openEditModal(attendant) {
        // Populate the edit modal with the selected attendant's current data
        document.getElementById("edit_attendant_id").value = attendant.attendant_id;
        document.getElementById("edit_fname").value     = attendant.fname;
        document.getElementById("edit_lname").value     = attendant.lname;
        document.getElementById("edit_phone").value     = attendant.phone;
        document.getElementById("editModal").className  = "modal active";
    }

    function closeEditModal() {
        document.getElementById("editModal").className = "modal";
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        var addModal  = document.getElementById("addModal");
        var editModal = document.getElementById("editModal");
        if (event.target == addModal)  addModal.className  = "modal";
        if (event.target == editModal) editModal.className = "modal";
    };

</script>
</body>
</html>
