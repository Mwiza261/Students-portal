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

// Handle course registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_course'])) {
    $course_id = intval($_POST['course_id']);
    
    $mysqli = db_connect();
    
    // Check if already registered
    $checkStmt = $mysqli->prepare("SELECT id FROM course_registrations WHERE student_id = ? AND course_id = ? AND status = 'enrolled'");
    $checkStmt->bind_param("ii", $student_id, $course_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $message = "You are already registered for this course!";
        $messageType = "error";
    } else {
        // Check if course has capacity
        $capStmt = $mysqli->prepare("SELECT capacity, enrolled_count FROM courses WHERE id = ?");
        $capStmt->bind_param("i", $course_id);
        $capStmt->execute();
        $course = $capStmt->get_result()->fetch_assoc();
        
        if ($course && $course['enrolled_count'] < $course['capacity']) {
            // Register student
            $stmt = $mysqli->prepare("INSERT INTO course_registrations (student_id, course_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $student_id, $course_id);
            
            if ($stmt->execute()) {
                // Update enrolled count
                $updateStmt = $mysqli->prepare("UPDATE courses SET enrolled_count = enrolled_count + 1 WHERE id = ?");
                $updateStmt->bind_param("i", $course_id);
                $updateStmt->execute();
                $updateStmt->close();
                
                $message = "Successfully registered for the course!";
                $messageType = "success";
            } else {
                $message = "Registration failed. Please try again.";
                $messageType = "error";
            }
            $stmt->close();
        } else {
            $message = "Course is full!";
            $messageType = "error";
        }
        $capStmt->close();
    }
    $checkStmt->close();
    $mysqli->close();
}

// Get all available courses (not registered yet)
$mysqli = db_connect();

// First, let's check what columns actually exist in the courses table
$columns_result = $mysqli->query("SHOW COLUMNS FROM courses");
$columns = [];
while ($col = $columns_result->fetch_assoc()) {
    $columns[] = $col['Field'];
}

// Determine the correct column names based on your table structure
$course_name_field = in_array('Description', $columns) ? 'Description' : (in_array('course_name', $columns) ? 'course_name' : 'Column');
$course_code_field = in_array('course_code', $columns) ? 'course_code' : (in_array('Column', $columns) ? 'Column' : 'id');

$query = "
    SELECT c.* 
    FROM courses c
    WHERE c.status = 'active'
    AND c.id NOT IN (
        SELECT course_id FROM course_registrations 
        WHERE student_id = ? AND status = 'enrolled'
    )
    ORDER BY c.{$course_code_field}
";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$available_courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register for Courses - Student Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f3fb;
            color: #333;
        }
        .container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 280px;
            background: #1a1a2e;
            color: #fff;
            padding: 30px 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar h2 {
            font-size: 24px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #16213e;
        }
        .sidebar nav ul {
            list-style: none;
        }
        .sidebar nav ul li {
            margin-bottom: 10px;
        }
        .sidebar nav ul li a {
            color: #aaa;
            text-decoration: none;
            display: block;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .sidebar nav ul li a:hover,
        .sidebar nav ul li a.active {
            background: #16213e;
            color: #fff;
        }
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }
        .header {
            background: #fff;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            font-size: 28px;
            color: #1a1a2e;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
        }
        .logout-btn:hover {
            background: #c82333;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        .course-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .course-code {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .course-name {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 10px;
        }
        .course-info {
            margin: 15px 0;
            color: #555;
        }
        .course-info p {
            margin: 8px 0;
            font-size: 14px;
        }
        .availability {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .availability-open {
            background: #28a745;
            color: white;
        }
        .availability-closed {
            background: #dc3545;
            color: white;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            width: 100%;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 12px;
        }
        .empty-state p {
            font-size: 18px;
            color: #666;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2>🎓 Student Portal</h2>
            <nav>
                <ul>
                    <li><a href="dashboard.php">📊 Dashboard</a></li>
                    <li><a href="my_courses.php">📚 My Courses</a></li>
                    <li><a href="register_courses.php" class="active">➕ Register Courses</a></li>
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
                <h1>➕ Register for Courses</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo e($_SESSION['username']); ?></span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo e($message); ?>
                </div>
            <?php endif; ?>

            <?php if (count($available_courses) > 0): ?>
                <div class="courses-grid">
                    <?php foreach ($available_courses as $course): ?>
                        <div class="course-card">
                            <?php
                            // Get the course name from whatever column exists
                            $display_name = $course['Description'] ?? $course['course_name'] ?? $course['Column'] ?? 'Course';
                            $display_code = $course['course_code'] ?? $course['Column'] ?? '#' . $course['id'];
                            ?>
                            <div class="course-code"><?php echo e($display_code); ?></div>
                            <div class="course-name"><?php echo e($display_name); ?></div>
                            <div class="availability availability-open">
                                <?php 
                                $capacity = $course['capacity'] ?? $course['max_students'] ?? 50;
                                $enrolled = $course['enrolled_count'] ?? 0;
                                echo $enrolled . '/' . $capacity . ' spots available';
                                ?>
                            </div>
                            <div class="course-info">
                                <p><i>📖 Credits:</i> <?php echo e($course['credits'] ?? 'N/A'); ?></p>
                                <p><i>🏛️ Department:</i> <?php echo e($course['department'] ?? 'N/A'); ?></p>
                                <p><i>📚 Semester:</i> <?php echo e($course['semester'] ?? 'Current'); ?></p>
                                <p><i>📝 Description:</i> <?php echo e($course['Description'] ?? 'No description available'); ?></p>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="course_id" value="<?php echo e($course['id']); ?>">
                                <button type="submit" name="register_course" class="btn btn-success">Register for this Course</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>🎉 Great news! You're either registered for all available courses or no courses are currently open.</p>
                    <a href="my_courses.php" class="btn btn-secondary">View My Enrolled Courses</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>