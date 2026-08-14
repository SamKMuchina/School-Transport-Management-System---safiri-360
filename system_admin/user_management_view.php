<?php
/**
 * user_management_view.php
 *
 * DISPLAY ONLY - no database queries, no business logic.
 *
 * Included by user_management.php after that file has already:
 *   - handled all form submissions (add, edit, toggle, reset)
 *   - applied school filter and pagination
 *   - prepared these variables for us to display:
 *
 *       $username          (string)        - logged-in admin's username
 *       $success_message   (string)        - success alert text, or ''
 *       $error_message     (string)        - error alert text, or ''
 *       $filter_school_id  (int)           - currently selected school filter
 *       $current_page      (int)           - current pagination page
 *       $total_pages       (int)           - total number of pages
 *       $total_users       (int)           - total users for selected school
 *       $per_page          (int)           - users per page (8)
 *       $users_result      (mysqli result) - users to display, or null if no school selected
 *       $schools_list      (array)         - all schools for filter dropdown and modals
 *
 * CHANGES FROM ORIGINAL:
 *   - Removed "Created" column from users table
 *   - Added school filter (blank by default, shows users only when school selected)
 *   - Added pagination (8 users per page)
 *   - Added phone and email fields to Add User modal
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - System Admin</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">&#9776;</button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="dashboard-container">
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">School<span>Track</span><span class="sidebar-subtitle">System Administration</span></div>
        <div class="menu-section"><div class="menu-section-title">Main</div><a href="system_admin_dashboard.php" class="menu-item">Dashboard</a></div>
        <div class="menu-section"><div class="menu-section-title">School Management</div><a href="schools_management.php" class="menu-item">Schools Management</a></div>
        <div class="menu-section"><div class="menu-section-title">User Management</div><a href="user_management.php" class="menu-item active">User Management</a></div>
        <div class="menu-section"><div class="menu-section-title">Reports</div><a href="system_reports.php" class="menu-item">System Reports</a></div>
        <div class="menu-section"><div class="menu-section-title">Account</div><a href="../logout.php" class="menu-item">Logout</a></div>
    </div>

    <div class="main-wrapper">
        <div class="top-header">
            <div class="header-left"><h1>User Management</h1></div>
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

            <!-- ===== USERS TABLE ===== -->
            <div class="content-section">
                <div class="section-header">
                    <span class="section-title">All Users</span>
                    <button class="btn btn-warning" onclick="openAddModal()">Add New User</button>
                </div>

                <!-- SCHOOL FILTER -->
                <!-- Submits via GET so the filter stays in the URL and pagination works correctly -->
                <form method="GET" action="user_management.php" name="filterForm">
                    <div class="filter-bar">
                        <div class="filter-group">
                            <label>Filter by School</label>
                            <select name="school_id" id="filter_school_id" class="form-select">
                                <option value="">Select a school to view users</option>
                                <?php foreach ($schools_list as $school): ?>
                                    <option value="<?php echo $school['school_id']; ?>"
                                        <?php echo $filter_school_id == $school['school_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($school['school_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group" style="justify-content:flex-end;"><label>&nbsp;</label>
                            <div style="display:flex;gap:0.5rem;">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="user_management.php" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- USERS TABLE - only shown when a school is selected -->
                <?php if ($filter_school_id > 0): ?>

                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>School</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($users_result && mysqli_num_rows($users_result) > 0): ?>
                                    <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo htmlspecialchars(str_replace('_', ' ', $user['role'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['school_name'] ?: 'N/A'); ?></td>
                                        <td>
                                            <?php if ($user['is_active'] == 1): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display:flex;gap:0.4rem;flex-wrap:wrap;">
                                                <button class="btn btn-success btn-sm"
                                                        onclick='openEditModal(<?php echo json_encode($user); ?>)'>
                                                    Edit
                                                </button>
                                                <form method="POST" action="user_management.php" style="display:inline;">
                                                    <input type="hidden" name="action" value="reset_password">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                    <button type="submit" class="btn btn-warning btn-sm"
                                                            onclick="return confirm('Reset the password for <?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>? They will need to set a new password next time they log in.')">
                                                        Reset Password
                                                    </button>
                                                </form>
                                                <?php if ($user['is_active'] == 1): ?>
                                                    <form method="POST" action="user_management.php" style="display:inline;">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                        <input type="hidden" name="new_status" value="0">
                                                        <button type="submit" class="btn btn-danger btn-sm"
                                                                onclick="return confirmDeactivate()">
                                                            Deactivate
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" action="user_management.php" style="display:inline;">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                        <input type="hidden" name="new_status" value="1">
                                                        <button type="submit" class="btn btn-success btn-sm">Activate</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="table-empty">No users found for this school.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="user_management.php?school_id=<?php echo $filter_school_id; ?>&p=<?php echo $current_page - 1; ?>">Previous</a>
                        <?php else: ?>
                            <span class="disabled">Previous</span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $current_page): ?>
                                <span class="current-page"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="user_management.php?school_id=<?php echo $filter_school_id; ?>&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="user_management.php?school_id=<?php echo $filter_school_id; ?>&p=<?php echo $current_page + 1; ?>">Next</a>
                        <?php else: ?>
                            <span class="disabled">Next</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Default blank state - no school selected yet -->
                    <div class="table-empty" style="padding:3rem;text-align:center;">
                        <p>No school selected.</p>
                        <p style="margin-top:0.5rem;font-size:12px;color:#999;">Select a school from the filter above to view its users.</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     ADD USER MODAL
============================================================ -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeAddModal()">&times;</button>
        <div class="modal-header">
            <span class="modal-title">Add New User</span>
            <span class="modal-subtitle">Role will be set to Transport Manager automatically</span>
        </div>
        <form method="POST" action="user_management.php" name="addUserForm" onsubmit="return validateAddUserForm()">
            <input type="hidden" name="action" value="add_user">
            <div class="form-group">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" id="add_first_name" class="form-input" placeholder="e.g. Samuel">
                <span class="text-muted text-small">Will be used to generate username</span>
            </div>
            <div class="form-group">
                <label class="form-label">Second Name</label>
                <input type="text" name="second_name" id="add_second_name" class="form-input" placeholder="e.g. Muchina">
                <span class="text-muted text-small">Username will be: firstname.secondname</span>
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" id="add_phone" class="form-input" placeholder="e.g. 0712345678">
            </div>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="text" name="email" id="add_email" class="form-input" placeholder="e.g. sam.muchina@school.edu">
            </div>
            <div class="form-group">
                <label class="form-label">School</label>
                <select name="school_id" id="add_school_id" class="form-select">
                    <option value="">Select School</option>
                    <?php foreach ($schools_list as $school): ?>
                        <option value="<?php echo $school['school_id']; ?>">
                            <?php echo htmlspecialchars($school['school_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="is_active" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn btn-warning">Add User</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     EDIT USER MODAL
============================================================ -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeEditModal()">&times;</button>
        <div class="modal-header"><span class="modal-title">Edit User</span></div>
        <form method="POST" action="user_management.php" name="editUserForm" onsubmit="return validateEditUserForm()">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" id="edit_username" class="form-input" readonly style="background-color:#f0f0f0;">
                <span class="text-muted text-small">Username cannot be changed</span>
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <input type="text" id="edit_role" class="form-input" readonly style="background-color:#f0f0f0;">
                <span class="text-muted text-small">Role cannot be changed</span>
            </div>
            <div class="form-group">
                <label class="form-label">School</label>
                <select name="school_id" id="edit_school_id" class="form-select">
                    <?php foreach ($schools_list as $school): ?>
                        <option value="<?php echo $school['school_id']; ?>">
                            <?php echo htmlspecialchars($school['school_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="is_active" id="edit_is_active" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-success">Update User</button>
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
    // ADD USER FORM VALIDATION
    // ============================================================

    function validateAddUserForm() {
        var rtned = true;
        rtned = validateAddFirstName();
        if (rtned == true) rtned = validateAddSecondName();
        if (rtned == true) rtned = validateAddSchool();
        return rtned;
    }

    function validateAddFirstName() {
        var v = document.getElementById("add_first_name").value;
        if (v.length == 0) { alert("First name is required."); document.getElementById("add_first_name").focus(); return false; }
        return true;
    }

    function validateAddSecondName() {
        var v = document.getElementById("add_second_name").value;
        if (v.length == 0) { alert("Second name is required."); document.getElementById("add_second_name").focus(); return false; }
        return true;
    }

    function validateAddSchool() {
        var sel = document.getElementById("add_school_id");
        if (sel.options[sel.selectedIndex].value == "") { alert("Please select a school."); sel.focus(); return false; }
        return true;
    }

    // ============================================================
    // EDIT USER FORM VALIDATION
    // ============================================================

    function validateEditUserForm() {
        var sel = document.getElementById("edit_school_id");
        if (sel.options[sel.selectedIndex].value == "") { alert("Please select a school."); sel.focus(); return false; }
        return true;
    }

    // ============================================================
    // DEACTIVATE CONFIRMATION
    // ============================================================

    function confirmDeactivate() {
        return confirm("Are you sure you want to deactivate this user?");
    }

    // ============================================================
    // MODAL FUNCTIONS
    // ============================================================

    function openAddModal()  { document.getElementById("addModal").className  = "modal active"; }
    function closeAddModal() { document.getElementById("addModal").className  = "modal"; }

    function openEditModal(user) {
        document.getElementById("edit_user_id").value   = user.user_id;
        document.getElementById("edit_username").value  = user.username;
        document.getElementById("edit_role").value      = user.role.replace(/_/g, ' ');
        document.getElementById("edit_school_id").value = user.school_id;
        document.getElementById("edit_is_active").value = user.is_active;
        document.getElementById("editModal").className  = "modal active";
    }
    function closeEditModal() { document.getElementById("editModal").className = "modal"; }

    window.onclick = function(event) {
        var addModal  = document.getElementById("addModal");
        var editModal = document.getElementById("editModal");
        if (event.target == addModal)  addModal.className  = "modal";
        if (event.target == editModal) editModal.className = "modal";
    };
</script>
</body>
</html>
