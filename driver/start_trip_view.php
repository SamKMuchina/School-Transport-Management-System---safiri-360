<?php
/**
 * start_trip_view.php (DRIVER copy)
 *
 * DISPLAY ONLY - no database queries, no business logic.
 *
 * Included by start_trip.php after that file has already:
 *   - handled whichever form action was submitted, if any
 *   - fetched the current trip and student data
 *   - prepared these variables for us to display:
 *
 *       $username        (string)  - logged-in user's name
 *       $trip_id         (int)     - current trip
 *       $trip            (array)   - trip_date, route_name, plate_no, driver_name, tracking_link
 *       $status          (string)  - PENDING / IN_PROGRESS / COMPLETED
 *       $trip_type       (string)  - 'morning' or 'evening', once the trip has started
 *       $show_form       (bool)    - true if the Start Trip form should be shown
 *       $students_rows   (array)   - students on this trip, for the checklist
 *       $all_handled     (bool)    - true once every student is marked or absent
 *       $back_dashboard  (string)  - link back to the attendant dashboard
 *       $error_message   (string)  - error text to show, or ''
 *
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Execution - Driver</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body class="trip-execution-page">

<div class="trip-execution-card">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <h1>Trip Execution - Driver</h1>
        <a href="<?php echo $back_dashboard; ?>" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <!-- TRIP INFO BAR -->
    <div class="trip-info-bar">
        <div><strong>Date:</strong> <?php echo date('F j, Y', strtotime($trip['trip_date'])); ?></div>
        <div><strong>Route:</strong> <?php echo htmlspecialchars($trip['route_name']); ?></div>
        <div><strong>Vehicle:</strong> <?php echo htmlspecialchars($trip['plate_no']); ?></div>
        <div><strong>Driver:</strong> <?php echo htmlspecialchars($trip['driver_name']); ?></div>
        <div><strong>Status:</strong> <?php echo htmlspecialchars($status); ?></div>
    </div>

    <!-- CONTENT AREA -->
    <div class="content-area">

        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($show_form): ?>

        <!-- START TRIP FORM -->
        <div class="modal-card">
            <div class="modal-card-header">
                <h2>Start Trip</h2>
                <a href="<?php echo $back_dashboard; ?>" class="modal-close-link">&times;</a>
            </div>
            <form method="POST" action="start_trip.php?trip_id=<?php echo $trip_id; ?>">
                <input type="hidden" name="action" value="start_trip">
                <div class="modal-card-body">
                    <div class="form-group">
                        <label class="form-label">Trip Type</label>
                        <div class="radio-group">
                            <input type="radio" name="trip_type" id="type_morning" value="morning">
                            <label for="type_morning">Morning Pickup</label>
                            <input type="radio" name="trip_type" id="type_evening" value="evening">
                            <label for="type_evening">Evening Drop-off</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Google Maps Tracking Link</label>
                        <input type="text" name="tracking_link" class="form-input" placeholder="https://maps.google.com/...">
                    </div>
                </div>
                <div class="modal-card-footer">
                    <button type="submit" class="btn btn-warning w-100">Start Trip</button>
                </div>
            </form>
        </div>

        <?php elseif ($status === 'IN_PROGRESS'): ?>

        <!-- ATTENDANCE CHECKLIST -->
        <?php if ($tracking_link): ?>
        <div class="info-box">
            Live tracking:
            <a href="<?php echo htmlspecialchars($tracking_link); ?>" target="_blank">
                <?php echo htmlspecialchars($tracking_link); ?>
            </a>
        </div>
        <?php endif; ?>

        <div class="table-wrapper">
            <table class="data-table students-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Grade</th>
                        <th>Stop</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($students_rows) > 0): ?>
                        <?php foreach ($students_rows as $row): ?>
                        <?php
                        $student_id = $row['student_id'];
                        $is_done    = ($trip_type === 'morning') ? ($row['boarded'] == 1) : ($row['dropped'] == 1);
                        $is_absent  = ($row['absent'] == 1);
                        $field      = ($trip_type === 'morning') ? 'boarded' : 'dropped';
                        $btn_label  = ($trip_type === 'morning') ? 'Pickup' : 'Drop';
                        $done_label = ($trip_type === 'morning') ? 'Picked Up' : 'Dropped Off';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></td>
                            <td><?php echo htmlspecialchars($row['grade']); ?></td>
                            <td><?php echo htmlspecialchars($row['stop_name']); ?></td>
                            <td>
                                <div class="button-group">
                                    <?php if ($is_absent): ?>
                                        <button class="action-btn absent-btn done" disabled>Absent</button>
                                    <?php elseif ($is_done): ?>
                                        <button class="action-btn pickup done" disabled><?php echo $done_label; ?></button>
                                        <button class="action-btn absent-btn" disabled>Absent</button>
                                    <?php else: ?>
                                        <form method="POST" action="start_trip.php?trip_id=<?php echo $trip_id; ?>" class="inline-form">
                                            <input type="hidden" name="action" value="mark_student">
                                            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                                            <input type="hidden" name="field" value="<?php echo $field; ?>">
                                            <button type="submit" class="action-btn pickup"><?php echo $btn_label; ?></button>
                                        </form>
                                        <form method="POST" action="start_trip.php?trip_id=<?php echo $trip_id; ?>" class="inline-form">
                                            <input type="hidden" name="action" value="mark_absent">
                                            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                                            <button type="submit" class="action-btn absent-btn">Absent</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="table-empty">No students assigned to this trip.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="end-trip-area">
            <form method="POST" action="start_trip.php?trip_id=<?php echo $trip_id; ?>" onsubmit="return confirm('Are you sure you want to end this trip?')">
                <input type="hidden" name="action" value="end_trip">
                <button type="submit" class="btn btn-success" <?php if (!$all_handled) echo 'disabled'; ?>>
                    End Trip
                </button>
            </form>
            <?php if (!$all_handled): ?>
                <p class="text-muted text-small mt-sm">
                    Mark all students before ending the trip.
                </p>
            <?php endif; ?>
        </div>

        <?php elseif ($status === 'COMPLETED'): ?>
        <div class="info-box">This trip has already been completed.</div>
        <div class="text-center">
            <a href="<?php echo $back_dashboard; ?>" class="btn btn-primary">Go to Dashboard</a>
        </div>

        <?php else: ?>
        <div class="info-box">Trip status is <?php echo htmlspecialchars($status); ?>. No actions available.</div>
        <div class="text-center">
            <a href="<?php echo $back_dashboard; ?>" class="btn btn-primary">Go to Dashboard</a>
        </div>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
