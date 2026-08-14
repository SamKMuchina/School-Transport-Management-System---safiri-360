<?php
/**
 * manage_students.php
 *
 * Transport Manager can search, filter, add, edit and delete students.
 *
 * Location  : transport_manager/manage_students.php
 * Includes  : ../includes/db.php
 * Stylesheet: ../assets/css/style.css (linked in manage_students_view.php)
 *
 * Access: TRANSPORT_MANAGER only
 *
 * Features:
 * - Search students by name (plain GET form, page reload)
 * - Filter students by grade
 * - Add new student with Parent 1 (required) and Parent 2 (optional)
 * - Edit student and parent information (plain GET link + page reload)
 * - Delete student with confirmation (simple form POST, page reloads)
 *
 * Business Rules:
 * - A student must always have at least one parent (Parent 1)
 * - Parent 2 is optional - can be added later during an edit
 * - Deleting a student also removes their parent_students links
 *
 * Database tables used:
 * - students, parents, parent_students
 *
 * NOTE ON QUERIES: This file uses mysqli_query() instead of prepared
 * statements (per project requirement). Numeric values (school_id,
 * student_id, parent_id) are cast with (int) before use in a query.
 * Text values (names, grade, phone, email, search term) are passed
 * through mysqli_real_escape_string() before use in a query.
 *
 * FILE STRUCTURE: This file contains ALL PHP logic - access control,
 * form handling, and database queries. It stores the results in plain
 * variables, then includes manage_students_view.php, which contains
 * ONLY the HTML display - no queries, no business logic.
 */

// ============================================================
// SECTION 1: SESSION & ACCESS CONTROL
// ============================================================

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TRANSPORT_MANAGER') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db.php';

$school_id = (int)$_SESSION['school_id'];
$username  = $_SESSION['username'];

// ============================================================
// SECTION 2: HANDLE ADD STUDENT (POST)
// ============================================================

/*
 * Inserts a new student with Parent 1 (required) and Parent 2
 * (optional). Uses a transaction so all records are saved together.
 */
$add_success = '';
$add_error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_student') {

    $fname = trim(isset($_POST['fname']) ? $_POST['fname'] : '');
    $lname = trim(isset($_POST['lname']) ? $_POST['lname'] : '');
    $grade = trim(isset($_POST['grade']) ? $_POST['grade'] : '');

    $p1_fname = trim(isset($_POST['p1_fname']) ? $_POST['p1_fname'] : '');
    $p1_lname = trim(isset($_POST['p1_lname']) ? $_POST['p1_lname'] : '');
    $p1_phone = trim(isset($_POST['p1_phone']) ? $_POST['p1_phone'] : '');
    $p1_email = trim(isset($_POST['p1_email']) ? $_POST['p1_email'] : '');

    $p2_fname = trim(isset($_POST['p2_fname']) ? $_POST['p2_fname'] : '');
    $p2_lname = trim(isset($_POST['p2_lname']) ? $_POST['p2_lname'] : '');
    $p2_phone = trim(isset($_POST['p2_phone']) ? $_POST['p2_phone'] : '');
    $p2_email = trim(isset($_POST['p2_email']) ? $_POST['p2_email'] : '');

    if (empty($fname) || empty($lname) || empty($grade)) {
        $add_error = 'Student first name, last name and grade are required.';
    } elseif (empty($p1_fname) || empty($p1_lname) || empty($p1_phone)) {
        $add_error = 'Parent 1 first name, last name and phone are required.';
    } else {

        $fname_safe = mysqli_real_escape_string($conn, $fname);
        $lname_safe = mysqli_real_escape_string($conn, $lname);
        $grade_safe = mysqli_real_escape_string($conn, $grade);

        $conn->begin_transaction();

        try {
            // Insert student
            $insert_student_sql = "INSERT INTO students (school_id, fname, lname, grade) 
                                    VALUES ($school_id, '$fname_safe', '$lname_safe', '$grade_safe')";
            if (!mysqli_query($conn, $insert_student_sql)) throw new Exception('Failed to add student.');
            $new_student_id = $conn->insert_id;

            // Insert Parent 1 (required)
            $p1_fname_safe = mysqli_real_escape_string($conn, $p1_fname);
            $p1_lname_safe = mysqli_real_escape_string($conn, $p1_lname);
            $p1_phone_safe = mysqli_real_escape_string($conn, $p1_phone);
            $p1_email_safe = mysqli_real_escape_string($conn, $p1_email);

            $insert_p1_sql = "INSERT INTO parents (fname, lname, phone, email) 
                               VALUES ('$p1_fname_safe', '$p1_lname_safe', '$p1_phone_safe', '$p1_email_safe')";
            if (!mysqli_query($conn, $insert_p1_sql)) throw new Exception('Failed to add Parent 1.');
            $new_p1_id = $conn->insert_id;

            $link_p1_sql = "INSERT INTO parent_students (parent_id, student_id) VALUES ($new_p1_id, $new_student_id)";
            if (!mysqli_query($conn, $link_p1_sql)) throw new Exception('Failed to link Parent 1 to student.');

            // Insert Parent 2 only if all three required parent fields are filled in
            if (!empty($p2_fname) && !empty($p2_lname) && !empty($p2_phone)) {
                $p2_fname_safe = mysqli_real_escape_string($conn, $p2_fname);
                $p2_lname_safe = mysqli_real_escape_string($conn, $p2_lname);
                $p2_phone_safe = mysqli_real_escape_string($conn, $p2_phone);
                $p2_email_safe = mysqli_real_escape_string($conn, $p2_email);

                $insert_p2_sql = "INSERT INTO parents (fname, lname, phone, email) 
                                   VALUES ('$p2_fname_safe', '$p2_lname_safe', '$p2_phone_safe', '$p2_email_safe')";
                if (!mysqli_query($conn, $insert_p2_sql)) throw new Exception('Failed to add Parent 2.');
                $new_p2_id = $conn->insert_id;

                $link_p2_sql = "INSERT INTO parent_students (parent_id, student_id) VALUES ($new_p2_id, $new_student_id)";
                if (!mysqli_query($conn, $link_p2_sql)) throw new Exception('Failed to link Parent 2 to student.');
            }

            $conn->commit();
            $add_success = 'Student added successfully!';

        } catch (Exception $e) {
            $conn->rollback();
            $add_error = $e->getMessage();
        }
    }
}

// ============================================================
// SECTION 3: HANDLE EDIT STUDENT (POST)
// ============================================================

/*
 * Updates student fname/lname/grade.
 * Parent 1: always updated (a student always has a Parent 1).
 * Parent 2: if parent2_id already exists, update that parent.
 *           If parent2_id is empty but parent2 fields are filled in,
 *           insert a new parent and link them (adding Parent 2 during
 *           an edit). If parent2 fields are left empty, do nothing -
 *           the student simply continues to have no second parent.
 */
$edit_success = '';
$edit_error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_student') {

    $student_id = (int)(isset($_POST['student_id']) ? $_POST['student_id'] : 0);

    $fname = trim(isset($_POST['student_fname']) ? $_POST['student_fname'] : '');
    $lname = trim(isset($_POST['student_lname']) ? $_POST['student_lname'] : '');
    $grade = trim(isset($_POST['grade'])          ? $_POST['grade']          : '');

    $parent1_id = (int)(isset($_POST['parent1_id']) ? $_POST['parent1_id'] : 0);
    $p1_fname   = trim(isset($_POST['parent1_fname']) ? $_POST['parent1_fname'] : '');
    $p1_lname   = trim(isset($_POST['parent1_lname']) ? $_POST['parent1_lname'] : '');
    $p1_phone   = trim(isset($_POST['parent1_phone']) ? $_POST['parent1_phone'] : '');
    $p1_email   = trim(isset($_POST['parent1_email']) ? $_POST['parent1_email'] : '');

    $parent2_id = (int)(isset($_POST['parent2_id']) ? $_POST['parent2_id'] : 0);
    $p2_fname   = trim(isset($_POST['parent2_fname']) ? $_POST['parent2_fname'] : '');
    $p2_lname   = trim(isset($_POST['parent2_lname']) ? $_POST['parent2_lname'] : '');
    $p2_phone   = trim(isset($_POST['parent2_phone']) ? $_POST['parent2_phone'] : '');
    $p2_email   = trim(isset($_POST['parent2_email']) ? $_POST['parent2_email'] : '');

    if (empty($fname) || empty($lname) || empty($grade)) {
        $edit_error = 'Student first name, last name and grade are required.';
    } elseif (empty($p1_fname) || empty($p1_lname) || empty($p1_phone)) {
        $edit_error = 'Parent 1 first name, last name and phone are required.';
    } else {

        $fname_safe = mysqli_real_escape_string($conn, $fname);
        $lname_safe = mysqli_real_escape_string($conn, $lname);
        $grade_safe = mysqli_real_escape_string($conn, $grade);

        $conn->begin_transaction();

        try {
            // Update student
            $update_student_sql = "UPDATE students SET fname = '$fname_safe', lname = '$lname_safe', grade = '$grade_safe' 
                                    WHERE student_id = $student_id AND school_id = $school_id";
            if (!mysqli_query($conn, $update_student_sql)) throw new Exception('Failed to update student.');

            // Update Parent 1 (always exists)
            $p1_fname_safe = mysqli_real_escape_string($conn, $p1_fname);
            $p1_lname_safe = mysqli_real_escape_string($conn, $p1_lname);
            $p1_phone_safe = mysqli_real_escape_string($conn, $p1_phone);
            $p1_email_safe = mysqli_real_escape_string($conn, $p1_email);

            $update_p1_sql = "UPDATE parents SET fname = '$p1_fname_safe', lname = '$p1_lname_safe', 
                               phone = '$p1_phone_safe', email = '$p1_email_safe' 
                               WHERE parent_id = $parent1_id";
            if (!mysqli_query($conn, $update_p1_sql)) throw new Exception('Failed to update Parent 1.');

            // Parent 2 handling
            if ($parent2_id > 0) {
                // Parent 2 already exists - update their info
                $p2_fname_safe = mysqli_real_escape_string($conn, $p2_fname);
                $p2_lname_safe = mysqli_real_escape_string($conn, $p2_lname);
                $p2_phone_safe = mysqli_real_escape_string($conn, $p2_phone);
                $p2_email_safe = mysqli_real_escape_string($conn, $p2_email);

                $update_p2_sql = "UPDATE parents SET fname = '$p2_fname_safe', lname = '$p2_lname_safe', 
                                   phone = '$p2_phone_safe', email = '$p2_email_safe' 
                                   WHERE parent_id = $parent2_id";
                if (!mysqli_query($conn, $update_p2_sql)) throw new Exception('Failed to update Parent 2.');

            } elseif (!empty($p2_fname) && !empty($p2_lname) && !empty($p2_phone)) {
                // No Parent 2 yet, but fields are filled in - add Parent 2 now
                $p2_fname_safe = mysqli_real_escape_string($conn, $p2_fname);
                $p2_lname_safe = mysqli_real_escape_string($conn, $p2_lname);
                $p2_phone_safe = mysqli_real_escape_string($conn, $p2_phone);
                $p2_email_safe = mysqli_real_escape_string($conn, $p2_email);

                $insert_p2_sql = "INSERT INTO parents (fname, lname, phone, email) 
                                   VALUES ('$p2_fname_safe', '$p2_lname_safe', '$p2_phone_safe', '$p2_email_safe')";
                if (!mysqli_query($conn, $insert_p2_sql)) throw new Exception('Failed to add Parent 2.');
                $new_p2_id = $conn->insert_id;

                $link_p2_sql = "INSERT INTO parent_students (parent_id, student_id) VALUES ($new_p2_id, $student_id)";
                if (!mysqli_query($conn, $link_p2_sql)) throw new Exception('Failed to link Parent 2 to student.');
            }
            // If parent2_id is empty AND parent2 fields are empty, do nothing - no second parent, as intended.

            $conn->commit();
            $edit_success = 'Student updated successfully!';

        } catch (Exception $e) {
            $conn->rollback();
            $edit_error = $e->getMessage();
        }
    }
}

// ============================================================
// SECTION 4: HANDLE DELETE STUDENT (POST)
// ============================================================

/*
 * Deletes a student and their parent_students links.
 * Simple form POST with page reload (no AJAX) - matches the
 * delete/toggle pattern used on every other page in this system.
 * Verifies student belongs to this school before deleting.
 */
$delete_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_student') {

    $student_id = (int)(isset($_POST['student_id']) ? $_POST['student_id'] : 0);

    $check_sql = "SELECT student_id FROM students WHERE student_id = $student_id AND school_id = $school_id";
    $check_result = mysqli_query($conn, $check_sql);

    if (!$check_result || mysqli_num_rows($check_result) === 0) {
        $delete_error = 'Student not found or access denied.';
    } else {
        $conn->begin_transaction();

        try {
            $delete_links_sql = "DELETE FROM parent_students WHERE student_id = $student_id";
            mysqli_query($conn, $delete_links_sql);

            $delete_student_sql = "DELETE FROM students WHERE student_id = $student_id AND school_id = $school_id";
            mysqli_query($conn, $delete_student_sql);

            $conn->commit();

        } catch (Exception $e) {
            $conn->rollback();
            $delete_error = 'Deletion failed: ' . $e->getMessage();
        }
    }
}

// ============================================================
// SECTION 5: SEARCH / FILTER STUDENTS (?search=&grade=)
// ============================================================

/*
 * Runs when the admin submits the search form (a plain GET request -
 * the page just reloads with these values in the URL). If neither
 * field is filled in, $students_result stays null and the view shows
 * the "use the search bar" placeholder, same as before.
 */
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$grade  = isset($_GET['grade'])  ? trim($_GET['grade'])  : '';
$search_performed = (!empty($search) || !empty($grade));
$students_result   = null;

if ($search_performed) {

    $search_sql = "SELECT s.student_id, s.fname, s.lname, s.grade,
                      GROUP_CONCAT(CONCAT(p.fname, ' ', p.lname, ' (', p.phone, ')') SEPARATOR ' | ') AS parents
               FROM students s
               LEFT JOIN parent_students ps ON s.student_id = ps.student_id
               LEFT JOIN parents p          ON ps.parent_id = p.parent_id
               WHERE s.school_id = $school_id";

    if (!empty($search)) {
        $search_safe = mysqli_real_escape_string($conn, $search);
        $search_sql .= " AND (s.fname LIKE '%$search_safe%' OR s.lname LIKE '%$search_safe%')";
    }

    if (!empty($grade)) {
        $grade_safe = mysqli_real_escape_string($conn, $grade);
        $search_sql .= " AND s.grade = '$grade_safe'";
    }

    $search_sql .= " GROUP BY s.student_id ORDER BY s.fname, s.lname";

    $students_result = mysqli_query($conn, $search_sql);
}

// ============================================================
// SECTION 6: LOAD STUDENT FOR EDIT (?edit=student_id)
// ============================================================

/*
 * Runs when the admin clicks "Edit" on a student row (a plain link to
 * ?edit=<student_id>, page reload - no AJAX). Looks up the student and
 * their parent(s) up front so the edit modal below can be rendered
 * already open and already filled in, straight from PHP.
 */
$edit_student_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit_student     = null;
$edit_parent1     = null;
$edit_parent2     = null;

if ($edit_student_id > 0) {

    $student_sql = "SELECT student_id, fname, lname, grade FROM students 
                     WHERE student_id = $edit_student_id AND school_id = $school_id";
    $student_result = mysqli_query($conn, $student_sql);

    if ($student_result && mysqli_num_rows($student_result) > 0) {
        $edit_student = mysqli_fetch_assoc($student_result);

        $parents_sql = "SELECT p.parent_id, p.fname, p.lname, p.phone, p.email
                         FROM parents p
                         INNER JOIN parent_students ps ON p.parent_id = ps.parent_id
                         WHERE ps.student_id = $edit_student_id
                         ORDER BY p.parent_id ASC";
        $parents_result = mysqli_query($conn, $parents_sql);

        $parents = array();
        while ($row = mysqli_fetch_assoc($parents_result)) {
            $parents[] = $row;
        }

        $edit_parent1 = isset($parents[0]) ? $parents[0] : null;
        $edit_parent2 = isset($parents[1]) ? $parents[1] : null;
    }
}

// ============================================================
// SECTION 7: FETCH DISTINCT GRADES FOR FILTER DROPDOWN
// ============================================================

$grades = array();
$grades_sql = "SELECT DISTINCT grade FROM students WHERE school_id = $school_id ORDER BY grade";
$grades_result = mysqli_query($conn, $grades_sql);

while ($row = mysqli_fetch_assoc($grades_result)) {
    $grades[] = $row['grade'];
}

$conn->close();

// ============================================================
// SECTION 8: LOAD THE VIEW (HTML display only)
// ============================================================

include 'manage_students_view.php';
