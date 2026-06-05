<?php
session_start();
require_once __DIR__ . '/db.php';

// Check if user is logged in
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: Login.php');
    exit;
}

$student_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle course dropping
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['drop_course'])) {
    $course_id = intval($_POST['course_id']);
    
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("UPDATE course_registrations SET status = 'dropped' WHERE student_id = ? AND course_id = ? AND status = 'enrolled'");
    $stmt->bind_param("ii", $student_id, $course_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        // Update enrolled count
        $updateStmt = $mysqli->prepare("UPDATE courses SET enrolled_count = enrolled_count - 1 WHERE id = ?");
        $updateStmt->bind_param("i", $course_id);
        $updateStmt->execute();
        $updateStmt->close();
        
        $message = "Course dropped successfully!";
        $messageType = "success";
    } else {
        $message = "Failed to drop course.";
        $messageType = "error";
    }
    $stmt->close();
    $mysqli->close();
}

// Get student's registered courses
$mysqli = db_connect();
$query = "
    SELECT c.*, cr.registration_date, cr.status as registration_status, cr.grade
    FROM courses c
    INNER JOIN course_registrations cr ON c.id = cr.course_id
    WHERE cr.student_id = ? AND cr.status = 'enrolled'
    ORDER BY cr.registration_date DESC
";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$registered_courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Student Portal</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f3fb; color: #333; }
        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #1a1a2e; color: #fff; padding: 30px 20px; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar h2 { font-size: 24px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #16213e; }
        .sidebar nav ul { list-style: none; }
        .sidebar nav ul li { margin-bottom: 10px; }
        .sidebar nav ul li a { color: #aaa; text-decoration: none; display: block; padding: 12px 15px; border-radius: 8px; transition: all 0.3s ease; }
        .sidebar nav ul li a:hover, .sidebar nav ul li a.active { background: #16213e; color: #fff; }
        .main-content { flex: 1; margin-left: 280px; padding: 30px; }
        .header { background: #fff; padding: 20px 30px; border-radius: 12px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 28px; color: #1a1a2e; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .logout-btn { background: #dc3545; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; }
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        .courses-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        .course-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .course-code { font-size: 14px; color: #666; margin-bottom: 5px; }
        .course-name { font-size: 20px; font-weight: 600; color: #1a1a2e; margin-bottom: 10px; }
        .course-info p { margin: 8px 0; font-size: 14px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; background: #28a745; color: white; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; width: 100%; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-primary { background: #007bff; color: white; text-decoration: none; display: inline-block; text-align: center; }
        .empty-state { text-align: center; padding: 60px 20px; background: #fff; border-radius: 12px; }
        @media (max-width: 768px) { .sidebar { width: 100%; position: relative; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2>🎓 Student Portal</h2>
            <nav>
                <ul>
                    <li><a href="dashboard.php">📊 Dashboard</a></li>
                    <li><a href="my_courses.php" class="active">📚 My Courses</a></li>
                    <li><a href="register_courses.php">➕ Register Courses</a></li>
                    <li><a href="exam_results.php">📝 Exam Results</a></li>
                    <li><a href="assessments.php">📋 Assessments</a></li>
                    <li><a href="timetable.php">📅 Timetable</a></li>
                    <li><a href="payments.php">💰 Payments</a></li>
                    <li><a href="support.php">💬 Support</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>📚 My Enrolled Courses</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo e($_SESSION['username']); ?></span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>"><?php echo e($message); ?></div>
            <?php endif; ?>

            <?php if (count($registered_courses) > 0): ?>
                <div class="courses-grid">
                    <?php foreach ($registered_courses as $course): ?>
                        <?php
                        $display_name = $course['Description'] ?? $course['course_name'] ?? $course['Column'] ?? 'Course';
                        $display_code = $course['course_code'] ?? $course['Column'] ?? '#' . $course['id'];
                        ?>
                        <div class="course-card">
                            <div class="course-code"><?php echo e($display_code); ?></div>
                            <div class="course-name"><?php echo e($display_name); ?></div>
                            <div class="course-info">
                                <p><i>📖 Credits:</i> <?php echo e($course['credits'] ?? 'N/A'); ?></p>
                                <p><i>🏛️ Department:</i> <?php echo e($course['department'] ?? 'N/A'); ?></p>
                                <p><i>📅 Registered:</i> <?php echo date('M d, Y', strtotime($course['registration_date'])); ?></p>
                                <?php if ($course['grade']): ?>
                                    <p><i>⭐ Grade:</i> <strong><?php echo e($course['grade']); ?></strong></p>
                                <?php endif; ?>
                            </div>
                            <div class="badge">Enrolled</div>
                            <form method="POST" style="margin-top: 15px;" onsubmit="return confirm('Are you sure you want to drop this course?');">
                                <input type="hidden" name="course_id" value="<?php echo e($course['id']); ?>">
                                <button type="submit" name="drop_course" class="btn btn-danger">Drop Course</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>📭 You haven't registered for any courses yet.</p>
                    <a href="register_courses.php" class="btn btn-primary">Browse Available Courses</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>