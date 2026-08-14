<?php
/**
 * schools_management_view.php
 *
 * DISPLAY ONLY - no database queries, no business logic.
 *
 * Included by schools_management.php after that file has already:
 *   - handled add / edit / toggle-status form submissions
 *   - run every database query
 *   - prepared these variables for us to display:
 *
 *       $username         (string)        - logged-in user's name, for header
 *       $success_message  (string)        - success alert text, or ''
 *       $error_message    (string)        - error alert text, or ''
 *       $search_term      (string)        - current search box value
 *       $total_schools    (int)           - (not currently shown, kept for future stat card)
 *       $schools_result   (mysqli result) - the school rows to list
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schools Management - System Admin</title>
    <!-- Styles: ../assets/css/style.css -->
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <!-- Collapsible Sidebar Toggle -->
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">&#9776;</button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>


    <div class="dashboard-container">

        <!-- ============================================================
             SIDEBAR NAVIGATION
        ============================================================ -->
        <div class="sidebar" id="sidebar">

            <div class="sidebar-brand">
                School<span>Track</span>
                <span class="sidebar-subtitle">System Administration</span>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Main</div>
                <a href="system_admin_dashboard.php" class="menu-item">Dashboard</a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">School Management</div>
                <a href="schools_management.php" class="menu-item active">Schools Management</a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">User Management</div>
                <a href="user_management.php" class="menu-item">User Management</a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Reports</div>
                <a href="system_reports.php" class="menu-item">System Reports</a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Account</div>
                <a href="../logout.php" class="menu-item">Logout</a>
            </div>

        </div>

        <!-- ============================================================
             MAIN CONTENT WRAPPER
        ============================================================ -->
        <div class="main-wrapper">

            <!-- TOP HEADER -->
            <div class="top-header">
                <div class="header-left">
                    <h1>Schools Management</h1>
                </div>
                <div class="header-right">
                    <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
                    <a href="../logout.php" class="logout-btn">Logout</a>
                </div>
            </div>

            <!-- DASHBOARD CONTENT -->
            <div class="dashboard-content">

                <!-- Success / Error Messages -->
                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>

                <!-- ===== STATS CARD ===== -->
                <!-- ===== SCHOOLS TABLE ===== -->
                <div class="content-section">
                    <div class="section-header">
                        <span class="section-title">All Schools</span>
                        <button class="btn btn-warning" onclick="openAddModal()">Add New School</button>
                    </div>

                    <!-- Search Bar -->
                    <div class="filter-bar">
                        <form method="GET" action="schools_management.php" name="searchForm" onsubmit="return validateSearchForm()">
                            <div class="filter-group">
                                <label>Search by School Name</label>
                                <input type="text"
                                       name="search"
                                       id="search_input"
                                       class="form-input"
                                       placeholder="Search school by name..."
                                       value="<?php echo htmlspecialchars($search_term); ?>">
                            </div>
                            <div class="filter-group filter-group-actions">
                                <label>&nbsp;</label>
                                <div class="button-group">
                                    <button type="submit" class="btn btn-primary">Search</button>
                                    <?php if (!empty($search_term)): ?>
                                    <a href="schools_management.php" class="btn btn-secondary">Clear</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Table -->
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>School Name</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Users</th>
                                    <th>Students</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($schools_result && mysqli_num_rows($schools_result) > 0): ?>
                                    <?php while ($school = mysqli_fetch_assoc($schools_result)): ?>
                                    <tr>
                                        <td><?php echo $school['school_id']; ?></td>
                                        <td><?php echo htmlspecialchars($school['school_name']); ?></td>
                                        <td><?php echo htmlspecialchars($school['address'] ?: 'N/A'); ?></td>
                                        <td>
                                            <?php if ($school['is_active'] == 1): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($school['created_at'])); ?></td>
                                        <td><?php echo $school['total_users']; ?></td>
                                        <td><?php echo $school['total_students']; ?></td>
                                        <td>
                                            <div class="button-group">
                                                <button class="btn btn-success btn-sm"
                                                        onclick='openEditModal(<?php echo json_encode($school); ?>)'>
                                                    Edit
                                                </button>
                                                <form method="POST" action="schools_management.php" style="display:inline;">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="school_id" value="<?php echo $school['school_id']; ?>">
                                                    <input type="hidden" name="new_status" value="<?php echo $school['is_active'] == 1 ? 0 : 1; ?>">
                                                    <?php if ($school['is_active'] == 1): ?>
                                                        <button type="submit" class="btn btn-warning btn-sm"
                                                                onclick="return confirmToggle('deactivate')">
                                                            Deactivate
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" class="btn btn-success btn-sm"
                                                                onclick="return confirmToggle('activate')">
                                                            Activate
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="table-empty">
                                            <?php if (!empty($search_term)): ?>
                                                No schools found matching "<?php echo htmlspecialchars($search_term); ?>".
                                            <?php else: ?>
                                                No schools found. Click "Add New School" to get started.
                                            <?php endif; ?>
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
         ADD SCHOOL MODAL
    ============================================================ -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeAddModal()">&times;</button>
            <div class="modal-header">
                <span class="modal-title">Add New School</span>
            </div>
            <form method="POST" action="schools_management.php" name="addSchoolForm" onsubmit="return validateAddForm()">
                <input type="hidden" name="action" value="add_school">
                <div class="form-group">
                    <label class="form-label">School Name</label>
                    <input type="text"
                           name="school_name"
                           id="add_school_name"
                           class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" id="add_address" class="form-textarea"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-warning">Add School</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
         EDIT SCHOOL MODAL
    ============================================================ -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
            <div class="modal-header">
                <span class="modal-title">Edit School</span>
            </div>
            <form method="POST" action="schools_management.php" name="editSchoolForm" onsubmit="return validateEditForm()">
                <input type="hidden" name="action" value="edit_school">
                <input type="hidden" name="school_id" id="edit_school_id">
                <div class="form-group">
                    <label class="form-label">School Name</label>
                    <input type="text"
                           name="school_name"
                           id="edit_school_name"
                           class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" id="edit_address" class="form-textarea"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Update School</button>
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
    // SEARCH FORM VALIDATION
    // ============================================================

    // Master validation function for search form
    function validateSearchForm() {
        var rtned = true;
        rtned = validateSearchInput();
        return rtned;
    }

    // Validate search input - must not be empty
    function validateSearchInput() {
        var search = document.getElementById("search_input").value;
        if (search.length == 0) {
            alert("Please enter a school name to search.");
            document.getElementById("search_input").focus();
            return false;
        }
        return true;
    }

    // ============================================================
    // ADD SCHOOL FORM VALIDATION
    // ============================================================

    // Master validation function for add form
    function validateAddForm() {
        var rtned = true;
        rtned = validateAddSchoolName();
        return rtned;
    }

    // Validate school name field in add form
    function validateAddSchoolName() {
        var name = document.getElementById("add_school_name").value;
        if (name.length == 0) {
            alert("School name is required.");
            document.getElementById("add_school_name").focus();
            return false;
        }
        return true;
    }

    // ============================================================
    // EDIT SCHOOL FORM VALIDATION
    // ============================================================

    // Master validation function for edit form
    function validateEditForm() {
        var rtned = true;
        rtned = validateEditSchoolName();
        return rtned;
    }

    // Validate school name field in edit form
    function validateEditSchoolName() {
        var name = document.getElementById("edit_school_name").value;
        if (name.length == 0) {
            alert("School name is required.");
            document.getElementById("edit_school_name").focus();
            return false;
        }
        return true;
    }

    // ============================================================
    // TOGGLE STATUS CONFIRMATION
    // ============================================================

    // Confirm before toggling school status
    function confirmToggle(action) {
        return confirm("Are you sure you want to " + action + " this school?");
    }

    // ============================================================
    // MODAL FUNCTIONS - ADD
    // ============================================================

    function openAddModal() {
        document.getElementById("addModal").className = "modal active";
    }

    function closeAddModal() {
        document.getElementById("addModal").className = "modal";
    }

    // ============================================================
    // MODAL FUNCTIONS - EDIT
    // ============================================================

    function openEditModal(school) {
        document.getElementById("edit_school_id").value   = school.school_id;
        document.getElementById("edit_school_name").value = school.school_name;
        document.getElementById("edit_address").value     = school.address || '';
        document.getElementById("editModal").className    = "modal active";
    }

    function closeEditModal() {
        document.getElementById("editModal").className = "modal";
    }

    // ============================================================
    // CLOSE MODALS WHEN CLICKING OUTSIDE
    // ============================================================

    window.onclick = function(event) {
        var addModal  = document.getElementById("addModal");
        var editModal = document.getElementById("editModal");

        if (event.target == addModal)  addModal.className  = "modal";
        if (event.target == editModal) editModal.className = "modal";
    };

</script>
</body>
</html>
