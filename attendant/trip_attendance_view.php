<?php
/**
 * trip_attendance_view.php
 *
 * DISPLAY ONLY - no database queries, no business logic.
 *
 * Included by trip_attendance.php after that file has already run
 * every query and prepared these variables for us to display:
 *
 *     $trip            (array)         - trip_date, route_name for the info bar
 *     $back_dashboard  (string)        - link back to the correct dashboard for this role
 *     $students        (mysqli result) - the students on this trip
 */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Attendance - School Transport Management</title>
    <!-- Styles: ../assets/css/style.css -->
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
    <style>
        /* Page-specific trip attendance styles */
        body { background: linear-gradient(135deg, #2f3b4f 0%, #4a5f7f 100%); min-height: 100vh; display: flex; justify-content: center; align-items: flex-start; padding: 2rem 1rem; }
        .container { background-color: #ffffff; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); width: 100%; max-width: 800px; overflow: hidden; }
        .page-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; background-color: #f8f9fa; border-bottom: 1px solid #e0e0e0; flex-wrap: wrap; gap: 0.5rem; }
        .page-header h1 { color: #2f3b4f; font-size: 1.3rem; font-weight: 600; }
        .trip-info-bar { background-color: #E8F4FD; padding: 0.8rem 1.5rem; border-bottom: 4px solid #2196F3; display: flex; flex-wrap: wrap; gap: 1.5rem; font-size: 13px; color: #2f3b4f; }
        .trip-info-bar strong { font-weight: 600; }
        .content-area { padding: 1.5rem; }
    </style>
</head>
<body>

<div class="container">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <h1>Trip Attendance</h1>
        <a href="<?php echo $back_dashboard; ?>" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <!-- TRIP INFO BAR -->
    <div class="trip-info-bar">
        <div><strong>Date:</strong> <?php echo date('F j, Y', strtotime($trip['trip_date'])); ?></div>
        <div><strong>Route:</strong> <?php echo htmlspecialchars($trip['route_name']); ?></div>
    </div>

    <!-- CONTENT AREA -->
    <div class="content-area">

        <?php if ($students && mysqli_num_rows($students) > 0): ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Grade</th>
                        <th>Stop</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($students)): ?>
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
