<?php
/**
 * logout.php
 * 
 * Handles user logout for the School Transport Management System.
 * 
 * Purpose:
 * - Destroys the current session.
 * - Redirects the user back to the login page.
 * 
 * Location: root folder (accessible by all roles)
 */

// ===== START SESSION =====
session_start();

// ===== DESTROY SESSION =====
session_destroy();

// ===== REDIRECT TO LOGIN =====
header("Location: login.html");
exit;
?>
