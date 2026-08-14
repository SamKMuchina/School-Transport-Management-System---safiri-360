<?php
/**
 * manager_reports_view.php
 *
 * DISPLAY ONLY. 5 tabs, each self-contained with its own filter form
 * and results table. Tabs switch via plain links + a $active_tab check -
 * only the matching tab's HTML block renders.
 *
 * Date range filter (date_from/date_to) is shared by Trip Summary,
 * Driver Performance and Attendant Performance. Other tabs use their
 * own date field. All dates display as dd/mm/yyyy.
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Transport Manager</title>
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
        <div class="menu-section"><div class="menu-section-title">Fleet and Routes</div><a href="manage_vehicles.php" class="menu-item">Vehicles</a><a href="manage_routes.php" class="menu-item">Routes</a><a href="manage_route_stops.php" class="menu-item">Route Stops</a></div>
        <div class="menu-section"><div class="menu-section-title">Operations</div><a href="manage_trips.php" class="menu-item">Trips</a><a href="trip_monitoring.php" class="menu-item">Monitoring</a><a href="manage_incidents.php" class="menu-item">Incidents</a></div>
        <div class="menu-section"><div class="menu-section-title">Reports</div><a href="manager_reports.php" class="menu-item active">Reports</a></div>
        <div class="menu-section"><div class="menu-section-title">Account</div><a href="../logout.php" class="menu-item">Logout</a></div>
    </div>

    <div class="main-wrapper">
        <div class="top-header">
            <div class="header-left"><h1>Transport Manager Reports</h1></div>
            <div class="header-right">
                <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
        <div class="dashboard-content">

            <!-- Tab nav - plain links, $active_tab decides which block below renders -->
            <div class="report-tabs">
                <a href="manager_reports.php?tab=trips"                class="tab-link <?php echo $active_tab==='trips'                ?'active':'';?>">Trip Summary</a>
                <a href="manager_reports.php?tab=driver_performance"   class="tab-link <?php echo $active_tab==='driver_performance'   ?'active':'';?>">Driver Performance</a>
                <a href="manager_reports.php?tab=attendant_performance" class="tab-link <?php echo $active_tab==='attendant_performance'?'active':'';?>">Attendant Performance</a>
                <a href="manager_reports.php?tab=student_attendance"   class="tab-link <?php echo $active_tab==='student_attendance'   ?'active':'';?>">Student Attendance</a>
                <a href="manager_reports.php?tab=incidents"            class="tab-link <?php echo $active_tab==='incidents'            ?'active':'';?>">Incidents</a>
            </div>

            <!-- TAB 1: TRIP SUMMARY - date range + route/vehicle/status, blank until Filter, paginated -->
            <?php if ($active_tab === 'trips'): ?>
            <div class="tab-panel">
                <form method="GET" action="manager_reports.php" name="tripsFilterForm" onsubmit="return validateTripsFilter()">
                    <input type="hidden" name="tab"            value="trips">
                    <input type="hidden" name="filter_applied" value="1">
                    <div class="filter-bar">
                        <div class="filter-group">
                            <label>From Date (dd/mm/yyyy)</label>
                            <input type="text" name="date_from" id="date_from" class="form-input"
                                   placeholder="dd/mm/yyyy" maxlength="10"
                                   value="<?php echo htmlspecialchars($date_from); ?>" onmouseout="validateFromDate()">
                        </div>
                        <div class="filter-group">
                            <label>To Date (dd/mm/yyyy)</label>
                            <input type="text" name="date_to" id="date_to" class="form-input"
                                   placeholder="dd/mm/yyyy" maxlength="10"
                                   value="<?php echo htmlspecialchars($date_to); ?>" onmouseout="validateToDate()">
                        </div>
                        <div class="filter-group">
                            <label>Route</label>
                            <select name="route" class="form-select">
                                <option value="0">All Routes</option>
                                <?php foreach ($routes as $r): ?>
                                    <option value="<?php echo $r['route_id']; ?>" <?php echo $filter_route==$r['route_id']?'selected':''; ?>>
                                        <?php echo htmlspecialchars($r['route_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Vehicle</label>
                            <select name="vehicle" class="form-select">
                                <option value="0">All Vehicles</option>
                                <?php foreach ($vehicles as $v): ?>
                                    <option value="<?php echo $v['vehicle_id']; ?>" <?php echo $filter_vehicle==$v['vehicle_id']?'selected':''; ?>>
                                        <?php echo htmlspecialchars($v['plate_no']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="PENDING"     <?php echo $filter_status==='PENDING'     ?'selected':''; ?>>Pending</option>
                                <option value="IN_PROGRESS" <?php echo $filter_status==='IN_PROGRESS' ?'selected':''; ?>>In Progress</option>
                                <option value="COMPLETED"   <?php echo $filter_status==='COMPLETED'   ?'selected':''; ?>>Completed</option>
                            </select>
                        </div>
                        <div class="filter-group" style="justify-content:flex-end;"><label>&nbsp;</label>
                            <div style="display:flex;gap:0.5rem;">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="manager_reports.php?tab=trips" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr><th>Date</th><th>Route</th><th>Vehicle</th><th>Driver</th><th>Attendant</th><th>Status</th><th>Students</th><th>Boarded</th><th>Dropped</th><th>Absent</th></tr>
                        </thead>
                        <tbody>
                            <?php if (!$filter_applied): ?>
                                <tr><td colspan="10" class="table-empty">Use the filters above and click Filter to view trip records.</td></tr>
                            <?php elseif (count($trips_data) > 0): ?>
                                <?php foreach ($trips_data as $row): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($row['trip_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['route_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['plate_no']); ?></td>
                                    <td><?php echo htmlspecialchars($row['driver']); ?></td>
                                    <td><?php echo htmlspecialchars($row['attendant']); ?></td>
                                    <td>
                                        <?php $s=$row['status'];$b='badge-secondary';if($s=='COMPLETED')$b='badge-success';if($s=='IN_PROGRESS')$b='badge-info';if($s=='PENDING')$b='badge-warning'; ?>
                                        <span class="badge <?php echo $b;?>"><?php echo htmlspecialchars($s);?></span>
                                    </td>
                                    <td><?php echo $row['total_students']; ?></td>
                                    <td><?php echo $row['boarded']; ?></td>
                                    <td><?php echo $row['dropped']; ?></td>
                                    <td><?php echo $row['absent']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="10" class="table-empty">No trips found for the selected filters.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($filter_applied && $total_pages > 1): ?>
                <div class="pagination">
                    <span>Showing <?php echo (($current_page-1)*$per_page)+1; ?> - <?php echo min($current_page*$per_page, $total_trips_count); ?> of <?php echo $total_trips_count; ?> trips</span>
                    <div>
                        <?php $base = 'manager_reports.php?tab=trips&filter_applied=1&date_from=' . urlencode($date_from) . '&date_to=' . urlencode($date_to) . '&route=' . $filter_route . '&vehicle=' . $filter_vehicle . '&status=' . urlencode($filter_status); ?>
                        <?php if ($current_page > 1): ?><a href="<?php echo $base; ?>&p=<?php echo $current_page-1; ?>">Previous</a><?php else: ?><span class="disabled">Previous</span><?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $current_page): ?><span class="current-page"><?php echo $i; ?></span><?php else: ?><a href="<?php echo $base; ?>&p=<?php echo $i; ?>"><?php echo $i; ?></a><?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($current_page < $total_pages): ?><a href="<?php echo $base; ?>&p=<?php echo $current_page+1; ?>">Next</a><?php else: ?><span class="disabled">Next</span><?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- TAB 2: DRIVER PERFORMANCE - date range + driver picker (plain link), paginated via 'dp' -->
            <?php elseif ($active_tab === 'driver_performance'): ?>
            <div class="tab-panel">
                <form method="GET" action="manager_reports.php" name="driverForm" onsubmit="return validateStaffDates()">
                    <input type="hidden" name="tab"        value="driver_performance">
                    <input type="hidden" name="staff_role" value="driver">
                    <input type="hidden" name="staff_id"   value="<?php echo $staff_id; ?>">
                    <div class="filter-bar">
                        <div class="filter-group">
                            <label>From Date (dd/mm/yyyy)</label>
                            <input type="text" name="staff_date_from" id="staff_date_from" class="form-input"
                                   placeholder="dd/mm/yyyy" maxlength="10"
                                   value="<?php echo htmlspecialchars($staff_date_from); ?>" onmouseout="validateStaffFromDate()">
                        </div>
                        <div class="filter-group">
                            <label>To Date (dd/mm/yyyy)</label>
                            <input type="text" name="staff_date_to" id="staff_date_to" class="form-input"
                                   placeholder="dd/mm/yyyy" maxlength="10"
                                   value="<?php echo htmlspecialchars($staff_date_to); ?>" onmouseout="validateStaffToDate()">
                        </div>
                        <div class="filter-group" style="justify-content:flex-end;"><label>&nbsp;</label>
                            <div style="display:flex;gap:0.5rem;">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="manager_reports.php?tab=driver_performance" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="staff-grid">
                    <?php foreach ($driver_list as $d): ?>
                        <a class="staff-link <?php echo ($staff_id==$d['driver_id']&&$staff_role==='driver')?'selected':''; ?>"
                           href="manager_reports.php?tab=driver_performance&staff_id=<?php echo $d['driver_id']; ?>&staff_role=driver&staff_date_from=<?php echo urlencode($staff_date_from); ?>&staff_date_to=<?php echo urlencode($staff_date_to); ?>">
                            <?php echo htmlspecialchars($d['fname'] . ' ' . $d['lname']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php if ($staff_id > 0 && $staff_role === 'driver'): ?>
                    <?php if (count($driver_trips) > 0): ?>
                    <div class="table-wrapper"><table class="data-table">
                        <thead><tr><th>Trip Date</th><th>Route</th><th>Vehicle</th><th>Attendant</th><th>Start</th><th>End</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($driver_trips as $trip): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($trip['trip_date'])); ?></td>
                                <td><?php echo htmlspecialchars($trip['route_name']); ?></td>
                                <td><?php echo htmlspecialchars($trip['plate_no']); ?></td>
                                <td><?php echo htmlspecialchars($trip['attendant']); ?></td>
                                <td><?php echo $trip['start_time'] ? date('H:i', strtotime($trip['start_time'])) : '-'; ?></td>
                                <td><?php echo $trip['end_time']   ? date('H:i', strtotime($trip['end_time']))   : '-'; ?></td>
                                <td><?php echo htmlspecialchars($trip['status']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table></div>

                    <?php if ($driver_total_pages > 1): ?>
                    <div class="pagination">
                        <span>Showing <?php echo (($driver_page-1)*$per_page)+1; ?> - <?php echo min($driver_page*$per_page, $driver_total); ?> of <?php echo $driver_total; ?> trips</span>
                        <div>
                            <?php $dbase = 'manager_reports.php?tab=driver_performance&staff_id='.$staff_id.'&staff_role=driver&staff_date_from='.urlencode($staff_date_from).'&staff_date_to='.urlencode($staff_date_to); ?>
                            <?php if ($driver_page > 1): ?><a href="<?php echo $dbase; ?>&dp=<?php echo $driver_page-1; ?>">Previous</a><?php else: ?><span class="disabled">Previous</span><?php endif; ?>
                            <?php for ($i=1; $i<=$driver_total_pages; $i++): ?>
                                <?php if ($i==$driver_page): ?><span class="current-page"><?php echo $i;?></span><?php else: ?><a href="<?php echo $dbase; ?>&dp=<?php echo $i;?>"><?php echo $i;?></a><?php endif; ?>
                            <?php endfor; ?>
                            <?php if ($driver_page < $driver_total_pages): ?><a href="<?php echo $dbase; ?>&dp=<?php echo $driver_page+1; ?>">Next</a><?php else: ?><span class="disabled">Next</span><?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php else: ?>
                        <p class="table-empty">No trips found for this driver in the selected date range.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="table-empty">Click on a driver name above to view their trips.</p>
                <?php endif; ?>
            </div>

            <!-- TAB 3: ATTENDANT PERFORMANCE - same pattern as Driver Performance, paginated via 'ap' -->
            <?php elseif ($active_tab === 'attendant_performance'): ?>
            <div class="tab-panel">
                <form method="GET" action="manager_reports.php" name="attendantForm" onsubmit="return validateStaffDates()">
                    <input type="hidden" name="tab"        value="attendant_performance">
                    <input type="hidden" name="staff_role" value="attendant">
                    <input type="hidden" name="staff_id"   value="<?php echo $staff_id; ?>">
                    <div class="filter-bar">
                        <div class="filter-group">
                            <label>From Date (dd/mm/yyyy)</label>
                            <input type="text" name="staff_date_from" id="staff_date_from_att" class="form-input"
                                   placeholder="dd/mm/yyyy" maxlength="10"
                                   value="<?php echo htmlspecialchars($staff_date_from); ?>" onmouseout="validateStaffFromDate()">
                        </div>
                        <div class="filter-group">
                            <label>To Date (dd/mm/yyyy)</label>
                            <input type="text" name="staff_date_to" id="staff_date_to_att" class="form-input"
                                   placeholder="dd/mm/yyyy" maxlength="10"
                                   value="<?php echo htmlspecialchars($staff_date_to); ?>" onmouseout="validateStaffToDate()">
                        </div>
                        <div class="filter-group" style="justify-content:flex-end;"><label>&nbsp;</label>
                            <div style="display:flex;gap:0.5rem;">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="manager_reports.php?tab=attendant_performance" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="staff-grid">
                    <?php foreach ($attendant_list as $a): ?>
                        <a class="staff-link <?php echo ($staff_id==$a['attendant_id']&&$staff_role==='attendant')?'selected':''; ?>"
                           href="manager_reports.php?tab=attendant_performance&staff_id=<?php echo $a['attendant_id']; ?>&staff_role=attendant&staff_date_from=<?php echo urlencode($staff_date_from); ?>&staff_date_to=<?php echo urlencode($staff_date_to); ?>">
                            <?php echo htmlspecialchars($a['fname'] . ' ' . $a['lname']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php if ($staff_id > 0 && $staff_role === 'attendant'): ?>
                    <?php if (count($attendant_trips) > 0): ?>
                    <div class="table-wrapper"><table class="data-table">
                        <thead><tr><th>Trip Date</th><th>Route</th><th>Vehicle</th><th>Driver</th><th>Start</th><th>End</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($attendant_trips as $trip): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($trip['trip_date'])); ?></td>
                                <td><?php echo htmlspecialchars($trip['route_name']); ?></td>
                                <td><?php echo htmlspecialchars($trip['plate_no']); ?></td>
                                <td><?php echo htmlspecialchars($trip['driver']); ?></td>
                                <td><?php echo $trip['start_time'] ? date('H:i', strtotime($trip['start_time'])) : '-'; ?></td>
                                <td><?php echo $trip['end_time']   ? date('H:i', strtotime($trip['end_time']))   : '-'; ?></td>
                                <td><?php echo htmlspecialchars($trip['status']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table></div>

                    <?php if ($attendant_total_pages > 1): ?>
                    <div class="pagination">
                        <span>Showing <?php echo (($attendant_page-1)*$per_page)+1; ?> - <?php echo min($attendant_page*$per_page, $attendant_total); ?> of <?php echo $attendant_total; ?> trips</span>
                        <div>
                            <?php $abase = 'manager_reports.php?tab=attendant_performance&staff_id='.$staff_id.'&staff_role=attendant&staff_date_from='.urlencode($staff_date_from).'&staff_date_to='.urlencode($staff_date_to); ?>
                            <?php if ($attendant_page > 1): ?><a href="<?php echo $abase; ?>&ap=<?php echo $attendant_page-1; ?>">Previous</a><?php else: ?><span class="disabled">Previous</span><?php endif; ?>
                            <?php for ($i=1; $i<=$attendant_total_pages; $i++): ?>
                                <?php if ($i==$attendant_page): ?><span class="current-page"><?php echo $i;?></span><?php else: ?><a href="<?php echo $abase; ?>&ap=<?php echo $i;?>"><?php echo $i;?></a><?php endif; ?>
                            <?php endfor; ?>
                            <?php if ($attendant_page < $attendant_total_pages): ?><a href="<?php echo $abase; ?>&ap=<?php echo $attendant_page+1; ?>">Next</a><?php else: ?><span class="disabled">Next</span><?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php else: ?>
                        <p class="table-empty">No trips found for this attendant in the selected date range.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="table-empty">Click on an attendant name above to view their trips.</p>
                <?php endif; ?>
            </div>

            <!-- TAB 4: STUDENT ATTENDANCE - name search, blank by default, one student's history at a time -->
            <?php elseif ($active_tab === 'student_attendance'): ?>
            <div class="tab-panel">
                <form method="GET" action="manager_reports.php" name="studentForm" onsubmit="return validateStudentSearch()">
                    <input type="hidden" name="tab" value="student_attendance">
                    <div class="filter-bar">
                        <div class="filter-group">
                            <label>Search Student by Name</label>
                            <input type="text" name="student_search" id="student_search" class="form-input"
                                   placeholder="Enter student name..." value="<?php echo htmlspecialchars($student_search); ?>">
                        </div>
                        <div class="filter-group">
                            <label>Date (dd/mm/yyyy)</label>
                            <input type="text" name="student_date" id="student_date" class="form-input"
                                   placeholder="dd/mm/yyyy" maxlength="10"
                                   value="<?php echo htmlspecialchars($student_date); ?>" onmouseout="validateStudentDate()">
                        </div>
                        <div class="filter-group" style="justify-content:flex-end;"><label>&nbsp;</label>
                            <div style="display:flex;gap:0.5rem;">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="manager_reports.php?tab=student_attendance" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </div>
                </form>

                <?php if (!empty($student_search)): ?>
                    <?php if (count($students_found) == 0): ?>
                        <p class="table-empty">No students found matching "<?php echo htmlspecialchars($student_search); ?>".</p>
                    <?php elseif (count($students_found) > 1): ?>
                        <!-- More than one match - let the manager pick which one they meant -->
                        <p style="margin-bottom:0.5rem;font-size:13px;color:#757575;">Multiple students found. Please refine your search:</p>
                        <div class="staff-grid">
                            <?php foreach ($students_found as $s): ?>
                                <a class="staff-link" href="manager_reports.php?tab=student_attendance&student_search=<?php echo urlencode($s['fname'].' '.$s['lname']); ?>&student_date=<?php echo urlencode($student_date); ?>">
                                    <?php echo htmlspecialchars($s['fname'] . ' ' . $s['lname']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="margin-bottom:0.5rem;font-size:13px;"><strong>Attendance for:</strong> <?php echo htmlspecialchars($students_found[0]['fname'].' '.$students_found[0]['lname']); ?></p>
                        <?php if (count($student_attendance) > 0): ?>
                        <div class="table-wrapper"><table class="data-table">
                            <thead><tr><th>Trip Date</th><th>Route</th><th>Vehicle</th><th>Trip Type</th><th>Pickup Time</th><th>Drop-off Time</th></tr></thead>
                            <tbody>
                                <?php foreach ($student_attendance as $att): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($att['trip_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($att['route_name']); ?></td>
                                    <td><?php echo htmlspecialchars($att['plate_no']); ?></td>
                                    <td><?php echo ucfirst($att['trip_type']); ?></td>
                                    <td><?php if($att['absent']) echo 'Absent'; elseif($att['trip_type']=='morning'&&$att['boarded']) echo date('H:i',strtotime($att['boarded_time'])); else echo '-'; ?></td>
                                    <td><?php if($att['absent']) echo 'Absent'; elseif($att['trip_type']=='evening'&&$att['dropped']) echo date('H:i',strtotime($att['dropped_time'])); else echo '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table></div>
                        <?php else: ?>
                            <p class="table-empty">No completed trips found for this student.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="table-empty">Enter a student name above and click Search to view their attendance.</p>
                <?php endif; ?>
            </div>

            <!-- TAB 5: INCIDENTS - single date + type filter, shows reporter's role -->
            <?php elseif ($active_tab === 'incidents'): ?>
            <div class="tab-panel">
                <form method="GET" action="manager_reports.php" name="incidentForm">
                    <input type="hidden" name="tab" value="incidents">
                    <div class="filter-bar">
                        <div class="filter-group">
                            <label>Incident Date (dd/mm/yyyy)</label>
                            <input type="text" name="incident_date" id="incident_date" class="form-input"
                                   placeholder="dd/mm/yyyy" maxlength="10"
                                   value="<?php echo htmlspecialchars($incident_date); ?>" onmouseout="validateIncidentDate()">
                        </div>
                        <div class="filter-group">
                            <label>Incident Type</label>
                            <select name="incident_type" class="form-select">
                                <option value="">Choose Incident Type</option>
                                <option value="" <?php echo $incident_type_filter===''?'selected':''; ?>>All Types</option>
                                <?php foreach ($incident_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type['incident_type']); ?>" <?php echo $incident_type_filter===$type['incident_type']?'selected':''; ?>>
                                        <?php echo htmlspecialchars($type['incident_type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group" style="justify-content:flex-end;"><label>&nbsp;</label>
                            <div style="display:flex;gap:0.5rem;">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="manager_reports.php?tab=incidents" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead><tr><th>Date</th><th>Trip Date</th><th>Route</th><th>Type</th><th>Reported By</th><th>Description</th></tr></thead>
                        <tbody>
                            <?php if (empty($incident_date) && empty($incident_type_filter)): ?>
                                <tr><td colspan="6" class="table-empty">Select a date or incident type above and click Filter to view incidents.</td></tr>
                            <?php elseif (count($incident_list) > 0): ?>
                                <?php foreach ($incident_list as $inc): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($inc['reported_at'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($inc['trip_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($inc['route_name']); ?></td>
                                    <td><?php echo htmlspecialchars($inc['incident_type']); ?></td>
                                    <td><?php echo htmlspecialchars($inc['reported_by_role']); ?></td>
                                    <td><?php echo htmlspecialchars($inc['description']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="table-empty">No incidents found for the selected filters.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<script>

    function toggleSidebar() {
        var sidebar = document.getElementById("sidebar");
        var overlay = document.getElementById("sidebarOverlay");
        if (sidebar.className.indexOf("open") !== -1) { sidebar.className = "sidebar"; overlay.className = "sidebar-overlay"; }
        else { sidebar.className = "sidebar open"; overlay.className = "sidebar-overlay active"; }
    }
    function closeSidebar() {
        document.getElementById("sidebar").className = "sidebar";
        document.getElementById("sidebarOverlay").className = "sidebar-overlay";
    }

    // dd/mm/yyyy check: "/" separator, 3 parts, numeric, day<=31, month<=12
    function validateDate(cdate) {
        if (cdate.indexOf("/") == -1) { alert("Date must be entered in the format dd/mm/yyyy"); return false; }
        var comps = cdate.split("/");
        if (comps.length < 3 || comps[0].length < 1 || comps[1].length < 1 || comps[2].length != 4) { alert("Date must be entered in the format dd/mm/yyyy"); return false; }
        if (isNaN(comps[0]) || isNaN(comps[1]) || isNaN(comps[2])) { alert("Date components must be numbers"); return false; }
        if (comps[0] > 31) { alert("Day value is out of range. Must be between 1 and 31"); return false; }
        if (comps[1] > 12) { alert("Month value must be in the range of 1 to 12"); return false; }
        return true;
    }

    // Per-field validators (onmouseout)
    function validateFromDate()      { var d=document.getElementById("date_from");         if(d&&d.value.length>0) return validateDate(d.value); return true; }
    function validateToDate()        { var d=document.getElementById("date_to");           if(d&&d.value.length>0) return validateDate(d.value); return true; }
    function validateStaffFromDate() { var d=document.getElementById("staff_date_from")||document.getElementById("staff_date_from_att"); if(d&&d.value.length>0) return validateDate(d.value); return true; }
    function validateStaffToDate()   { var d=document.getElementById("staff_date_to")  ||document.getElementById("staff_date_to_att");   if(d&&d.value.length>0) return validateDate(d.value); return true; }
    function validateIncidentDate()  { var d=document.getElementById("incident_date");     if(d&&d.value.length>0) return validateDate(d.value); return true; }
    function validateStudentDate()   { var d=document.getElementById("student_date");      if(d&&d.value.length>0) return validateDate(d.value); return true; }

    // Full form validators (onsubmit)
    function validateTripsFilter() { var ok = validateFromDate(); if (ok) ok = validateToDate(); return ok; }
    function validateStaffDates()  { var ok = validateStaffFromDate(); if (ok) ok = validateStaffToDate(); return ok; }

    function validateStudentSearch() {
        var v = document.getElementById("student_search").value;
        if (v.length == 0) { alert("Please enter a student name to search."); document.getElementById("student_search").focus(); return false; }
        return validateStudentDate();
    }

</script>
</body>
</html>
