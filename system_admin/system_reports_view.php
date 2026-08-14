<?php
/**
 * system_reports_view.php
 *
 * DISPLAY ONLY - no database queries, no business logic.
 *
 * Included by system_reports.php after that file has already:
 *   - fetched the correct data for whichever tab is active
 *   - prepared these variables for us to display:
 *
 *       $username          (string) - logged-in user's name, for header
 *       $active_tab        (string) - which tab is currently open
 *       $school_filter     (int)    - currently selected school filter (0 = all)
 *       $dashboard_data    (array)  - platform dashboard tab stats
 *       $school_data       (array)  - school performance tab rows
 *       $user_roles        (array)  - user activity tab role breakdown
 *       $user_totals       (array)  - user activity tab totals row
 *       $users_in_school   (array)  - user activity tab full user list (if school selected)
 *       $incident_summary  (array)  - incident summary tab rows
 *       $schools_list      (array)  - all schools, for the filter dropdown
 *
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports - School Transport Management</title>
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
                <a href="schools_management.php" class="menu-item">Schools Management</a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">User Management</div>
                <a href="user_management.php" class="menu-item">User Management</a>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Reports</div>
                <a href="system_reports.php" class="menu-item active">System Reports</a>
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
                    <h1>System Reports</h1>
                </div>
                <div class="header-right">
                    <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
                    <a href="../logout.php" class="logout-btn">Logout</a>
                </div>
            </div>

            <!-- DASHBOARD CONTENT -->
            <div class="dashboard-content">

                <!-- ===== TAB NAVIGATION - plain links, preserves school filter in URL ===== -->
                <div class="report-tabs">
                    <a href="system_reports.php?tab=dashboard"
                       class="tab-link <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">Platform Dashboard</a>
                    <a href="system_reports.php?tab=schools"
                       class="tab-link <?php echo $active_tab === 'schools' ? 'active' : ''; ?>">School Performance</a>
                    <a href="system_reports.php?tab=users&school_id=<?php echo $school_filter; ?>"
                       class="tab-link <?php echo $active_tab === 'users' ? 'active' : ''; ?>">User Activity</a>
                    <a href="system_reports.php?tab=incidents&school_id=<?php echo $school_filter; ?>"
                       class="tab-link <?php echo $active_tab === 'incidents' ? 'active' : ''; ?>">Incident Summary</a>
                </div>

                <!-- TAB 1: PLATFORM DASHBOARD - simple stat cards, no filter -->
                <?php if ($active_tab === 'dashboard'): ?>
                <div class="tab-panel">
                    <div class="compact-grid">
                        <div class="compact-card">
                            <span class="label">Total Schools</span>
                            <span class="value"><?php echo number_format($dashboard_data['schools']['total'] ?? 0); ?></span>
                        </div>
                        <div class="compact-card">
                            <span class="label">Active Schools</span>
                            <span class="value"><?php echo number_format($dashboard_data['schools']['active'] ?? 0); ?></span>
                        </div>
                        <div class="compact-card">
                            <span class="label">Total Users</span>
                            <span class="value"><?php echo number_format($dashboard_data['users']['total'] ?? 0); ?></span>
                        </div>
                        <div class="compact-card">
                            <span class="label">Active Users</span>
                            <span class="value"><?php echo number_format($dashboard_data['users']['active'] ?? 0); ?></span>
                        </div>
                        <div class="compact-card">
                            <span class="label">Total Students</span>
                            <span class="value"><?php echo number_format($dashboard_data['students'] ?? 0); ?></span>
                        </div>
                        <div class="compact-card">
                            <span class="label">Total Vehicles</span>
                            <span class="value"><?php echo number_format($dashboard_data['vehicles'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: SCHOOL PERFORMANCE - no filter, one table -->
                <?php elseif ($active_tab === 'schools'): ?>
                <div class="tab-panel">
                    <?php if (!empty($school_data)): ?>
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>School Name</th>
                                        <th>Completed Trips</th>
                                        <th>Total Students</th>
                                        <th>Total Drivers</th>
                                        <th>Total Attendants</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($school_data as $school): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($school['school_name']); ?></td>
                                        <td><?php echo $school['completed_trips']; ?></td>
                                        <td><?php echo $school['students']; ?></td>
                                        <td><?php echo $school['drivers']; ?></td>
                                        <td><?php echo $school['attendants']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No school data found.</p>
                    <?php endif; ?>
                </div>

                <!-- TAB 3: USER ACTIVITY - own filter form (school dropdown), role breakdown + totals row -->
                <?php elseif ($active_tab === 'users'): ?>
                <div class="tab-panel">
                    <form method="GET" action="system_reports.php" name="userFilterForm">
                        <input type="hidden" name="tab" value="users">
                        <div class="filter-bar">
                            <div class="filter-group">
                                <label>Filter by School</label>
                                <select name="school_id" class="form-select">
                                    <option value="0">All Schools</option>
                                    <?php foreach ($schools_list as $school): ?>
                                        <option value="<?php echo $school['school_id']; ?>"
                                            <?php echo $school_filter == $school['school_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($school['school_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group filter-group-actions"><label>&nbsp;</label>
                                <div class="button-group">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="system_reports.php?tab=users" class="btn btn-secondary">Reset</a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <?php if (!empty($user_roles)): ?>
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Role</th>
                                        <th>Active Users</th>
                                        <th>Inactive Users</th>
                                        <th>Total Users</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_roles as $role): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(str_replace('_', ' ', $role['role'])); ?></td>
                                        <td><?php echo $role['active']; ?></td>
                                        <td><?php echo $role['inactive']; ?></td>
                                        <td><?php echo $role['total']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <?php if (!empty($user_totals)): ?>
                                <tfoot>
                                    <tr class="total-row">
                                        <td>TOTAL</td>
                                        <td><?php echo $user_totals['total_active']; ?></td>
                                        <td><?php echo $user_totals['total_inactive']; ?></td>
                                        <td><?php echo $user_totals['total_all']; ?></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No user data found for the selected filter.</p>
                    <?php endif; ?>

                    <!-- Users in selected school -->
                    <?php if ($school_filter > 0 && !empty($users_in_school)): ?>
                        <?php
                            $school_name_filter = '';
                            foreach ($schools_list as $s) {
                                if ($s['school_id'] == $school_filter) {
                                    $school_name_filter = $s['school_name'];
                                    break;
                                }
                            }
                        ?>
                        <div class="sub-table-section">
                            <h3>Users in <?php echo htmlspecialchars($school_name_filter); ?></h3>
                            <div class="table-wrapper">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users_in_school as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars(str_replace('_', ' ', $user['role'])); ?></td>
                                            <td>
                                                <?php if ($user['status'] === 'Active'): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php elseif ($school_filter > 0): ?>
                        <p class="no-data">No users found in the selected school.</p>
                    <?php endif; ?>
                </div>

                <!-- TAB 4: INCIDENT SUMMARY - own filter form (school dropdown), incident type breakdown -->
                <?php elseif ($active_tab === 'incidents'): ?>
                <div class="tab-panel">
                    <form method="GET" action="system_reports.php" name="incidentFilterForm">
                        <input type="hidden" name="tab" value="incidents">
                        <div class="filter-bar">
                            <div class="filter-group">
                                <label>Filter by School</label>
                                <select name="school_id" class="form-select">
                                    <option value="0">All Schools</option>
                                    <?php foreach ($schools_list as $school): ?>
                                        <option value="<?php echo $school['school_id']; ?>"
                                            <?php echo $school_filter == $school['school_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($school['school_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group filter-group-actions"><label>&nbsp;</label>
                                <div class="button-group">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="system_reports.php?tab=incidents" class="btn btn-secondary">Reset</a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <?php if (!empty($incident_summary)): ?>
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Incident Type</th>
                                        <th>Number of Incidents</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($incident_summary as $inc): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($inc['incident_type']); ?></td>
                                        <td><?php echo $inc['count']; ?></td>
                                        <td><?php echo $inc['percentage']; ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No incidents found for the selected filter.</p>
                    <?php endif; ?>
                </div>

                <?php endif; ?>

            </div>
        </div>
    </div>

<script>
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
