<?php
/**
 * manage_students_view.php
 *
 * DISPLAY ONLY - no database queries, no business logic.
 *
 * Included by manage_students.php after that file has already:
 *   - handled add / edit / delete form submissions
 *   - run the search query (if search/grade filters were submitted)
 *   - run the edit lookup query (if ?edit=<id> was in the URL)
 *   - prepared these variables for us to display:
 *
 *       $username          (string)        - logged-in user's name, for header
 *       $add_success       (string)        - success alert text for add form, or ''
 *       $add_error         (string)        - error alert text for add form, or ''
 *       $edit_success      (string)        - success alert text for edit form, or ''
 *       $edit_error        (string)        - error alert text for edit form, or ''
 *       $delete_error      (string)        - error alert text for delete, or ''
 *       $grades            (array)         - distinct grades, for the filter dropdown
 *       $search            (string)        - current search box value
 *       $grade             (string)        - current grade filter value
 *       $search_performed  (bool)          - true once a search/filter has been submitted
 *       $students_result   (mysqli result) - matching students, or null
 *       $edit_student_id   (int)           - student_id being edited, 0 if none
 *       $edit_student      (array|null)    - student row being edited
 *       $edit_parent1      (array|null)    - Parent 1 row being edited
 *       $edit_parent2      (array|null)    - Parent 2 row being edited, if any
 *
 * Search and Edit both work as plain page reloads now (GET form and
 * GET link respectively) - no AJAX, no JavaScript building HTML.
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Transport Manager</title>
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
    <div class="sidebar">

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
            <a href="manage_students.php" class="menu-item active">Students</a>
            <a href="student_assignments.php" class="menu-item">Assignments</a>
        </div>

        <div class="menu-section">
            <div class="menu-section-title">Staff Management</div>
            <a href="manage_drivers.php" class="menu-item">Drivers</a>
            <a href="manage_attendants.php" class="menu-item">Attendants</a>
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

    <!-- ============================================================
         MAIN CONTENT WRAPPER
    ============================================================ -->
    <div class="main-wrapper">

        <!-- TOP HEADER -->
        <div class="top-header">
            <div class="header-left">
                <h1>Manage Students</h1>
            </div>
            <div class="header-right">
                <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <!-- DASHBOARD CONTENT -->
        <div class="dashboard-content">

            <?php if (!empty($add_success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($add_success); ?></div>
            <?php endif; ?>

            <?php if (!empty($add_error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($add_error); ?></div>
            <?php endif; ?>

            <?php if (!empty($edit_success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($edit_success); ?></div>
            <?php endif; ?>

            <?php if (!empty($edit_error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($edit_error); ?></div>
            <?php endif; ?>

            <?php if (!empty($delete_error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($delete_error); ?></div>
            <?php endif; ?>

            <!-- ===== FILTER BAR ===== -->
            <div class="content-section">
                <div class="section-header">
                    <span class="section-title">Students</span>
                    <button class="btn btn-warning" onclick="openAddStudentModal()">Add New Student</button>
                </div>
                <form method="GET" action="manage_students.php" name="studentSearchForm">
                    <div class="filter-bar">
                        <div class="filter-group">
                            <label>Search by Name</label>
                            <input type="text"
                                   name="search"
                                   class="form-input"
                                   placeholder="Type student name..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <label>Filter by Grade</label>
                            <select name="grade" class="form-select">
                                <option value="">All Grades</option>
                                <?php foreach ($grades as $g): ?>
                                    <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $grade === $g ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($g); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group filter-group-actions">
                            <label>&nbsp;</label>
                            <div class="button-group">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="manage_students.php" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- ===== STUDENTS TABLE ===== -->
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Grade</th>
                                <th>Parent(s)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$search_performed): ?>
                                <tr><td colspan="4" class="table-empty">Use the search bar to find students.</td></tr>
                            <?php elseif ($students_result && mysqli_num_rows($students_result) > 0): ?>
                                <?php while ($student = mysqli_fetch_assoc($students_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['fname'] . ' ' . $student['lname']); ?></td>
                                    <td><?php echo htmlspecialchars($student['grade']); ?></td>
                                    <td><?php echo htmlspecialchars($student['parents'] ?: 'No parents'); ?></td>
                                    <td>
                                        <div class="button-group">
                                            <a href="manage_students.php?edit=<?php echo (int)$student['student_id']; ?>" class="btn btn-success btn-sm">Edit</a>
                                            <form method="POST" action="manage_students.php" class="inline-form" onsubmit="return confirm('WARNING: This will permanently delete the student and all parent links. This action cannot be undone. Are you sure?')">
                                                <input type="hidden" name="action" value="delete_student">
                                                <input type="hidden" name="student_id" value="<?php echo (int)$student['student_id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="table-empty">No students found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ============================================================
     ADD STUDENT MODAL
============================================================ -->
<div id="addStudentModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeAddStudentModal()">&times;</button>
        <div class="modal-header">
            <span class="modal-title">Add New Student</span>
            <span class="modal-subtitle">Parent 1 is required. Parent 2 is optional.</span>
        </div>
        <form method="POST" action="manage_students.php" name="addStudentForm" onsubmit="return validateAddStudentForm()">
            <input type="hidden" name="action" value="add_student">
            <div class="card mb-2">
                <span class="card-title">Student Information</span>
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="fname" id="add_student_fname" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="lname" id="add_student_lname" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Grade</label>
                    <input type="text" name="grade" id="add_student_grade" class="form-input" placeholder="e.g. Grade 5">
                </div>
            </div>
            <div class="card mb-2">
                <span class="card-title">Parent 1 (Required)</span>
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="p1_fname" id="add_p1_fname" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="p1_lname" id="add_p1_lname" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="p1_phone" id="add_p1_phone" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="text" name="p1_email" class="form-input">
                </div>
            </div>
            <div class="card mb-2">
                <span class="card-title">Parent 2 (Optional)</span>
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="p2_fname" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="p2_lname" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="p2_phone" id="add_p2_phone" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="text" name="p2_email" class="form-input">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddStudentModal()">Cancel</button>
                <button type="submit" class="btn btn-warning">Add Student</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     EDIT STUDENT MODAL
     Rendered "active" by PHP when ?edit=<student_id> is present and
     found - no JS or AJAX involved, the controller already looked up
     the student and parent rows and passed them down as plain variables.
============================================================ -->
<div id="editModal" class="modal <?php echo $edit_student ? 'active' : ''; ?>">
    <div class="modal-content">
        <a href="manage_students.php" class="modal-close">&times;</a>
        <div class="modal-header">
            <span class="modal-title">Edit Student</span>
        </div>
        <?php if ($edit_student): ?>
        <form method="POST" action="manage_students.php">
            <input type="hidden" name="action" value="edit_student">
            <input type="hidden" name="student_id" value="<?php echo (int)$edit_student['student_id']; ?>">

            <div class="card mb-2">
                <span class="card-title">Student Information</span>
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="student_fname" id="edit_student_fname" class="form-input" value="<?php echo htmlspecialchars($edit_student['fname']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="student_lname" id="edit_student_lname" class="form-input" value="<?php echo htmlspecialchars($edit_student['lname']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Grade</label>
                    <input type="text" name="grade" id="edit_grade" class="form-input" value="<?php echo htmlspecialchars($edit_student['grade']); ?>">
                </div>
            </div>

            <div class="card mb-2">
                <span class="card-title">Parent 1 (Required)</span>
                <input type="hidden" name="parent1_id" value="<?php echo $edit_parent1 ? (int)$edit_parent1['parent_id'] : ''; ?>">
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="parent1_fname" id="edit_p1_fname" class="form-input" value="<?php echo $edit_parent1 ? htmlspecialchars($edit_parent1['fname']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="parent1_lname" id="edit_p1_lname" class="form-input" value="<?php echo $edit_parent1 ? htmlspecialchars($edit_parent1['lname']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="parent1_phone" id="edit_p1_phone" class="form-input" value="<?php echo $edit_parent1 ? htmlspecialchars($edit_parent1['phone']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="text" name="parent1_email" id="edit_p1_email" class="form-input" value="<?php echo $edit_parent1 ? htmlspecialchars($edit_parent1['email']) : ''; ?>">
                </div>
            </div>

            <div class="card mb-2">
                <span class="card-title">Parent 2 (Optional)</span>
                <input type="hidden" name="parent2_id" value="<?php echo $edit_parent2 ? (int)$edit_parent2['parent_id'] : ''; ?>">
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="parent2_fname" id="edit_p2_fname" class="form-input" value="<?php echo $edit_parent2 ? htmlspecialchars($edit_parent2['fname']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="parent2_lname" id="edit_p2_lname" class="form-input" value="<?php echo $edit_parent2 ? htmlspecialchars($edit_parent2['lname']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="parent2_phone" id="edit_p2_phone" class="form-input" value="<?php echo $edit_parent2 ? htmlspecialchars($edit_parent2['phone']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="text" name="parent2_email" id="edit_p2_email" class="form-input" value="<?php echo $edit_parent2 ? htmlspecialchars($edit_parent2['email']) : ''; ?>">
                </div>
            </div>

            <div class="modal-footer">
                <a href="manage_students.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-success">Update Student</button>
            </div>
        </form>
        <?php elseif ($edit_student_id > 0): ?>
        <p class="text-danger">Student not found.</p>
        <?php endif; ?>
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
    // ADD STUDENT MODAL
    // ============================================================

    function openAddStudentModal() {
        document.getElementById("addStudentModal").className = "modal active";
    }

    function closeAddStudentModal() {
        document.getElementById("addStudentModal").className = "modal";
    }

    // ============================================================
    // ADD STUDENT FORM VALIDATION
    // ============================================================

    function validateAddStudentForm() {
        var rtned = true;
        rtned = validateAddStudentFname();
        if (rtned == true) rtned = validateAddStudentLname();
        if (rtned == true) rtned = validateAddStudentGrade();
        if (rtned == true) rtned = validateAddParent1Fname();
        if (rtned == true) rtned = validateAddParent1Lname();
        if (rtned == true) rtned = validateAddParent1Phone();
        return rtned;
    }

    function validateAddStudentFname() {
        var v = document.getElementById("add_student_fname").value;
        if (v.length == 0) {
            alert("Student first name is required.");
            document.getElementById("add_student_fname").focus();
            return false;
        }
        return true;
    }

    function validateAddStudentLname() {
        var v = document.getElementById("add_student_lname").value;
        if (v.length == 0) {
            alert("Student last name is required.");
            document.getElementById("add_student_lname").focus();
            return false;
        }
        return true;
    }

    function validateAddStudentGrade() {
        var v = document.getElementById("add_student_grade").value;
        if (v.length == 0) {
            alert("Grade is required.");
            document.getElementById("add_student_grade").focus();
            return false;
        }
        return true;
    }

    function validateAddParent1Fname() {
        var v = document.getElementById("add_p1_fname").value;
        if (v.length == 0) {
            alert("Parent 1 first name is required.");
            document.getElementById("add_p1_fname").focus();
            return false;
        }
        return true;
    }

    function validateAddParent1Lname() {
        var v = document.getElementById("add_p1_lname").value;
        if (v.length == 0) {
            alert("Parent 1 last name is required.");
            document.getElementById("add_p1_lname").focus();
            return false;
        }
        return true;
    }

    function validateAddParent1Phone() {
        var v = document.getElementById("add_p1_phone").value;
        if (v.length == 0) {
            alert("Parent 1 phone is required.");
            document.getElementById("add_p1_phone").focus();
            return false;
        }
        if (isNaN(v)) {
            alert("Parent 1 phone must be numbers only.");
            document.getElementById("add_p1_phone").focus();
            return false;
        }
        return true;
    }

    // ============================================================
    // CLOSE ADD MODAL WHEN CLICKING OUTSIDE
    // (Edit modal isn't included here - it's opened/closed by PHP via
    // the ?edit= URL parameter, not by JS toggling a class.)
    // ============================================================

    window.onclick = function(event) {
        var addModal = document.getElementById("addStudentModal");
        if (event.target == addModal) addModal.className = "modal";
    };

</script>
</body>
</html>
