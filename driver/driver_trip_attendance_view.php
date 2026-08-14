<?php
/**
 * driver_trip_attendance_view.php
 *
 * DISPLAY ONLY - no database queries, no business logic.
 *
 * Included by driver_trip_attendance.php after that file has already
 * run every query and prepared these variables for us to display:
 *
 *     $trip             (array)         - trip_date, route_name for the info bar
 *     $students_result  (mysqli result) - students on this trip, for the table
 *
 * The only PHP allowed in this file is: echo-ing the variables above,
 * and a simple if/while loop to display them.
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Attendance - Driver</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body class="trip-execution-page">
<div class="trip-execution-card">

    <div class="page-header">
        <h1>Trip Attendance</h1>
        <a href="driver_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="trip-info-bar">
        <div><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($trip['trip_date'])); ?></div>
        <div><strong>Route:</strong> <?php echo htmlspecialchars($trip['route_name']); ?></div>
    </div>

    <div class="content-area">
        <?php if ($students_result && mysqli_num_rows($students_result) > 0): ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Student Name</th><th>Grade</th><th>Stop</th></tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($students_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></td>
                        <td><?php echo htmlspecialchars($row['grade']); ?></td>
                        <td><?php echo htmlspecialchars($row['stop_name']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="table-empty">No students assigned to this trip.</p>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
