<?php
/**
 * system_admin_dashboard_view.php
 *
 * DISPLAY ONLY - no database queries, no business logic.
 *
 * Included by system_admin_dashboard.php after that file has already
 * run every query and prepared these variables for us to display:
 *
 *     $username            (string)        - logged-in user's name, for header
 *     $total_schools       (int)           - stat card
 *     $total_active_users  (int)           - stat card
 *     $total_students      (int)           - stat card
 *     $active_trips        (int)           - stat card
 *     $schools_result      (mysqli result) - last 5 schools, for overview table
 *     $users_result        (mysqli result) - last 5 transport managers, for overview table
 *
 * The only PHP allowed in this file is: echo-ing the variables above,
 * and simple if/while loops to display them.
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Admin Dashboard - School Transport Management</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <!-- Collapsible Sidebar Toggle -->
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">&#9776;</button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>


    <!-- ============================================================
         SIDEBAR NAVIGATION
    ============================================================ -->
    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">

            <div class="sidebar-brand">
                School<span>Track</span>
                <span class="sidebar-subtitle">System Administration</span>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Main</div>
                <a href="system_admin_dashboard.php" class="menu-item active">Dashboard</a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">School Management</div>
                <a href="schools_management.php" class="menu-item">Schools Management</a>
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
                    <h1>System Administrator Dashboard</h1>
                </div>
                <div class="header-right">
                    <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
                    <a href="../logout.php" class="logout-btn">Logout</a>
                </div>
            </div>

            <!-- DASHBOARD CONTENT -->
            <div class="dashboard-content">

                <!-- ===== STATISTICS CARDS ===== -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-label">Total Schools</span>
                        <span class="stat-value"><?php echo $total_schools; ?></span>
                    </div>
                    <div class="stat-card stat-success">
                        <span class="stat-label">Active Users</span>
                        <span class="stat-value"><?php echo $total_active_users; ?></span>
                    </div>
                    <div class="stat-card stat-warning">
                        <span class="stat-label">Total Students</span>
                        <span class="stat-value"><?php echo $total_students; ?></span>
                    </div>
                    <div class="stat-card stat-info">
                        <span class="stat-label">Active Trips</span>
                        <span class="stat-value"><?php echo $active_trips; ?></span>
                    </div>
                </div>

                <!-- ===== SCHOOLS OVERVIEW TABLE ===== -->
                <div class="content-section">
                    <div class="section-header">
                        <span class="section-title">Schools Overview</span>
                        <a href="schools_management.php" class="view-all-link">View All</a>
                    </div>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>School Name</th>
                                    <th>Transport Manager</th>
                                    <th>Total Students</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($schools_result && mysqli_num_rows($schools_result) > 0): ?>
                                    <?php while ($school = mysqli_fetch_assoc($schools_result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($school['school_name']); ?></td>
                                        <td><?php echo htmlspecialchars($school['transport_manager'] ?? 'Not assigned'); ?></td>
                                        <td><?php echo $school['total_students']; ?></td>
                                        <td>
                                            <?php if ($school['is_active'] == 1): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="table-empty">No schools found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ===== TRANSPORT MANAGERS OVERVIEW TABLE ===== -->
                <div class="content-section">
                    <div class="section-header">
                        <span class="section-title">Transport Managers Overview</span>
                        <a href="user_management.php" class="view-all-link">View All</a>
                    </div>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>School</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($users_result && mysqli_num_rows($users_result) > 0): ?>
                                    <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['school_name'] ?: 'N/A'); ?></td>
                                        <td>
                                            <?php if ($user['is_active'] == 1): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="table-empty">No transport managers found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
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


</body>
</html>
