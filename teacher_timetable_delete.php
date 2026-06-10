<?php
session_start();
require_once __DIR__ . '/db.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: StaffLogin.php');
    exit;
}

$id = $_GET['id'] ?? 0;

if ($id) {
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("DELETE FROM timetables WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Timetable entry deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting entry: " . $stmt->error;
        $_SESSION['message_type'] = "error";
    }
    $stmt->close();
    $mysqli->close();
}

header('Location: teacher_timetable_create.php');
exit;
?>