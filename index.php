<?php
/**
 * index.php - Landing Page (Public)
 *
 * Entry point of the School Transport Management System.
 * No database queries on this page.
 *
 * Location: root folder (public - no login required)
 */
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Transport Management System</title>
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
</head>
<body>

    <!-- NAVIGATION BAR -->
    <div class="landing-navbar">
        <div class="landing-logo">School<span class="landing-logo-highlight">Track</span></div>
        <a href="login.html" class="landing-login-btn">Login</a>
    </div>

    <!-- HERO SECTION -->
    <div class="landing-hero">
        <div class="landing-hero-content">
            <h1>Safe, Smart, <span class="landing-hero-highlight">Reliable</span> School Transport</h1>
            <p>Real-time attendance, trip tracking, and incident reporting - all in one platform. Streamline your school transport operations with our comprehensive management system.</p>
            <a href="login.html" class="landing-hero-btn">Get Started</a>
            <div class="landing-hero-image">
                <img src="assets/images/school-bus.jpg" alt="School Bus">
            </div>
        </div>
    </div>

    <!-- HOW IT WORKS -->
    <div class="landing-section landing-section-white">
        <div class="landing-section-title">How It Works<span class="landing-title-underline"></span></div>
        <div class="landing-steps-grid">
            <div class="landing-step-card">
                <div class="landing-step-number">1</div>
                <span class="landing-step-title">School Setup</span>
                <span class="landing-step-desc">School is registered and a transport manager is assigned.</span>
            </div>
            <div class="landing-step-card">
                <div class="landing-step-number">2</div>
                <span class="landing-step-title">Configure</span>
                <span class="landing-step-desc">Add students, routes, vehicles, drivers and attendants.</span>
            </div>
            <div class="landing-step-card">
                <div class="landing-step-number">3</div>
                <span class="landing-step-title">Run Trips</span>
                <span class="landing-step-desc">Start trip, mark attendance and report incidents.</span>
            </div>
            <div class="landing-step-card">
                <div class="landing-step-number">4</div>
                <span class="landing-step-title">Analyze</span>
                <span class="landing-step-desc">View reports, export data and improve operations.</span>
            </div>
        </div>
    </div>

    <!-- KEY FEATURES -->
    <div class="landing-section landing-section-gray">
        <div class="landing-section-title">Key Features<span class="landing-title-underline"></span></div>
        <div class="landing-features-grid">
            <div class="landing-feature-card">
                <span class="landing-feature-title">Live Tracking</span>
                <span class="landing-feature-desc">Shareable Google Maps links for real-time bus location.</span>
            </div>
            <div class="landing-feature-card">
                <span class="landing-feature-title">Digital Attendance</span>
                <span class="landing-feature-desc">Mark pickup and drop-off with timestamps. Absent recording included.</span>
            </div>
            <div class="landing-feature-card">
                <span class="landing-feature-title">Incident Reporting</span>
                <span class="landing-feature-desc">Log delays, breakdowns or emergencies instantly.</span>
            </div>
            <div class="landing-feature-card">
                <span class="landing-feature-title">Powerful Reports</span>
                <span class="landing-feature-desc">Filter by date and export to CSV for all roles.</span>
            </div>
        </div>
    </div>

    <!-- USER ROLES -->
    <div class="landing-section landing-section-white">
        <div class="landing-section-title">Who Uses the System<span class="landing-title-underline"></span></div>
        <div class="landing-roles-grid">
            <div class="landing-role-card">
                <span class="landing-role-title">System Admin</span>
                <span class="landing-role-desc">Oversees all schools, manages schools and platform users.</span>
            </div>
            <div class="landing-role-card">
                <span class="landing-role-title">Transport Manager</span>
                <span class="landing-role-desc">Manages students, routes, vehicles, trips and reports.</span>
            </div>
            <div class="landing-role-card">
                <span class="landing-role-title">Driver</span>
                <span class="landing-role-desc">Starts trips, views student lists and reports incidents.</span>
            </div>
            <div class="landing-role-card">
                <span class="landing-role-title">Attendant</span>
                <span class="landing-role-desc">Manages student boarding and drop-off, marks attendance.</span>
            </div>
        </div>
    </div>

    <!-- CALL TO ACTION -->
    <div class="landing-section landing-section-blue">
        <h2>Ready to streamline your school transport?</h2>
        <a href="login.html" class="landing-cta-btn">Login to Dashboard</a>
    </div>

    <!-- FOOTER -->
    <div class="landing-footer">
        <p>&copy; <?php echo date('Y'); ?> School Transport Management System - Academic Project</p>
    </div>

</body>
</html>
