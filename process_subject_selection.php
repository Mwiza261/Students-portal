<?php
session_start();
require_once __DIR__ . '/db.php';

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: student_select_subjects.php');
    exit;
}

$studentId = (int) $_SESSION['user_id'];
$currentYear = date('Y');
$selectedCourses = $_POST['courses'] ?? [];

if (empty($selectedCourses)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please select at least one subject.'];
    header('Location: student_select_subjects.php');
    exit;
}

$mysqli = db_connect();

// Save subject selections
$insert_stmt = $mysqli->prepare(
    "INSERT INTO student_course_registration 
     (student_id, course_id, academic_year, semester, status, registered_at) 
     VALUES (?, ?, ?, 'Full Year', 'enrolled', NOW())"
);

foreach ($selectedCourses as $courseId) {
    $insert_stmt->bind_param('iii', $studentId, $courseId, $currentYear);
    $insert_stmt->execute();
}
$insert_stmt->close();
$mysqli->close();

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Your subjects have been successfully registered!'];
header('Location: student_dashboard.php');
exit;
?>