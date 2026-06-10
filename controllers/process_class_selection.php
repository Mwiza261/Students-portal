<?php
/* ──────────────────────────────────────────────
   Process Class Selection
   File: process_class_selection.php
   ────────────────────────────────────────────── */

session_start();
require_once __DIR__ . '/db.php';

// Check if user is logged in
if (!isset($_SESSION['authenticated'], $_SESSION['user_id']) || $_SESSION['authenticated'] !== true) {
    header('Location: Login.php');
    exit;
}

$user = get_user_by_id((int) $_SESSION['user_id']);
if (!$user || $user['role'] !== 'student') {
    session_unset();
    session_destroy();
    header('Location: Login.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: select_class.php');
    exit;
}

$classLevel = $_POST['class_level'] ?? '';
$validClasses = ['Form 1', 'Form 2', 'Form 3', 'Form 4'];

if (!in_array($classLevel, $validClasses)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please select a valid class level.'];
    header('Location: select_class.php');
    exit;
}

$studentId = (int) $_SESSION['user_id'];
$currentYear = date('Y');

$mysqli = db_connect();

// Check if class already selected for this year
$check_stmt = $mysqli->prepare(
    "SELECT id FROM student_class_selections 
     WHERE student_id = ? AND academic_year = ?"
);
$check_stmt->bind_param('ii', $studentId, $currentYear);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $check_stmt->close();
    $mysqli->close();
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'You have already selected your class for this academic year.'];
    header('Location: index.php');
    exit;
}
$check_stmt->close();

// Save class selection
$insert_stmt = $mysqli->prepare(
    "INSERT INTO student_class_selections (student_id, class_level, academic_year, selected_at) 
     VALUES (?, ?, ?, NOW())"
);
$insert_stmt->bind_param('isi', $studentId, $classLevel, $currentYear);

if ($insert_stmt->execute()) {
    // Also update the users table if you have a class_level column
    $update_stmt = $mysqli->prepare("UPDATE users SET class_level = ? WHERE id = ?");
    $update_stmt->bind_param('si', $classLevel, $studentId);
    $update_stmt->execute();
    $update_stmt->close();
    
    $_SESSION['class_level'] = $classLevel;
    $_SESSION['flash'] = ['type' => 'success', 'message' => "You have successfully selected {$classLevel}!"];
    
    $insert_stmt->close();
    $mysqli->close();
    
    // Redirect to subject selection page
    header('Location: select_subjects.php');
    exit;
} else {
    $insert_stmt->close();
    $mysqli->close();
    
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'An error occurred. Please try again.'];
    header('Location: select_class.php');
    exit;
}
?>