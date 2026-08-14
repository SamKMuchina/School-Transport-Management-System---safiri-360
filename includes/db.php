<?php
/**
 * db.php
 * Database connection file for School Transport Management System.
 * Location : includes/db.php
 */
// ============================================================
// DATABASE CONNECTION DETAILS - XAMPP LOCAL
// ============================================================
$host     = "localhost";
$dbname   = "school_transport_db";
$username = "root";
$password = "";
// ============================================================
// CREATE CONNECTION
// ============================================================
$conn = new mysqli($host, $username, $password, $dbname);
// ============================================================
// CHECK CONNECTION
// ============================================================
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Set character set to UTF-8
$conn->set_charset("utf8");
?>
