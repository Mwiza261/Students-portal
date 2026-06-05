<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: StaffLogin.php');
    exit;
}

$request_id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($request_id && in_array($action, ['approve', 'reject'])) {
    $mysqli = db_connect();
    
    if ($action === 'approve') {
        // Get the eligibility details
        $get_stmt = $mysqli->prepare("
            SELECT student_id, course_id, semester, academic_year 
            FROM student_course_eligibility 
            WHERE id = ?
        ");
        $get_stmt->bind_param("i", $request_id);
        $get_stmt->execute();
        $eligibility = $get_stmt->get_result()->fetch_assoc();
        
        if ($eligibility) {
            // Register the student
            $insert_stmt = $mysqli->prepare("
                INSERT INTO student_courses (student_id, course_id, semester, academic_year, status)
                VALUES (?, ?, ?, ?, 'registered')
            ");
            $insert_stmt->bind_param("iiss", 
                $eligibility['student_id'], 
                $eligibility['course_id'], 
                $eligibility['semester'], 
                $eligibility['academic_year']
            );
            $insert_stmt->execute();
            $insert_stmt->close();
            
            // Update eligibility status
            $update_stmt = $mysqli->prepare("
                UPDATE student_course_eligibility 
                SET status = 'approved' 
                WHERE id = ?
            ");
            $update_stmt->bind_param("i", $request_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
        $get_stmt->close();
        
        $_SESSION['success'] = "Student request approved and registered successfully!";
    } else {
        // Reject the request
        $update_stmt = $mysqli->prepare("
            UPDATE student_course_eligibility 
            SET status = 'rejected' 
            WHERE id = ?
        ");
        $update_stmt->bind_param("i", $request_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        $_SESSION['success'] = "Student request rejected.";
    }
    
    $mysqli->close();
}

header('Location: teacher_dashboard.php');
exit;
?>