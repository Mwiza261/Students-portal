<?php
session_start();
require_once __DIR__ . '/db.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: Login.php');
    exit;
}

$student_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

$mysqli = db_connect();

// Handle course registration request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_registration'])) {
    $eligibility_id = intval($_POST['eligibility_id']);
    
    // Update status to requested (if you want students to request first)
    // Or directly register them. Here we'll allow direct registration
    
    // Check if already registered in student_courses
    $check_stmt = $mysqli->prepare("
        SELECT id FROM student_courses 
        WHERE student_id = ? AND course_id = (SELECT course_id FROM student_course_eligibility WHERE id = ?)
    ");
    $check_stmt->bind_param("ii", $student_id, $eligibility_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows == 0) {
        // Register the student
        $insert_stmt = $mysqli->prepare("
            INSERT INTO student_courses (student_id, course_id, semester, academic_year, status)
            SELECT student_id, course_id, academic_year, semester, 'registered'
            FROM student_course_eligibility
            WHERE id = ? AND student_id = ?
        ");
        $insert_stmt->bind_param("ii", $eligibility_id, $student_id);
        
        if ($insert_stmt->execute()) {
            $success_message = "Successfully registered for the course!";
        } else {
            $error_message = "Failed to register. Please try again.";
        }
        $insert_stmt->close();
    } else {
        $error_message = "You are already registered for this course.";
    }
    $check_stmt->close();
}

// Get courses assigned to student by teachers
$available_courses_query = "
    SELECT sce.*, c.course_code, c.course_name, c.description, c.credits, 
           u.first_name as teacher_first, u.surname as teacher_last
    FROM student_course_eligibility sce
    JOIN courses c ON sce.course_id = c.id
    JOIN users u ON sce.teacher_id = u.id
    WHERE sce.student_id = ? AND sce.status = 'pending'
    AND NOT EXISTS (
        SELECT 1 FROM student_courses sc 
        WHERE sc.student_id = sce.student_id 
        AND sc.course_id = sce.course_id
        AND sc.semester = sce.semester
    )
    ORDER BY sce.assigned_date DESC
";
$stmt = $mysqli->prepare($available_courses_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$available_courses = $stmt->get_result();

// Get already registered courses
$registered_courses_query = "
    SELECT sc.*, c.course_code, c.course_name, c.credits
    FROM student_courses sc
    JOIN courses c ON sc.course_id = c.id
    WHERE sc.student_id = ? AND sc.status = 'registered'
    ORDER BY sc.registration_date DESC
";
$stmt = $mysqli->prepare($registered_courses_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$registered_courses = $stmt->get_result();

$mysqli->close();

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Registration | Student Portal</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        
        .section-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .section-header {
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .course-card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1.2rem;
            transition: all 0.3s;
        }
        
        .course-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateY(-2px); }
        
        .course-code { color: #667eea; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; }
        .course-name { font-size: 1.2rem; font-weight: 600; margin: 0.5rem 0; }
        .course-details { margin: 0.8rem 0; font-size: 0.9rem; color: #666; }
        
        .badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        
        .badge-available { background: #d4edda; color: #155724; }
        .badge-registered { background: #cce5ff; color: #004085; }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .btn-primary { background: #667eea; color: white; width: 100%; }
        .btn-primary:hover { background: #5a67d8; }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        
        @media (max-width: 768px) {
            .container { padding: 0 1rem; }
            .course-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h2>📚 Course Registration Portal</h2>
        <div>
            <span>👋 Welcome, <?php echo e($_SESSION['username']); ?></span>
            <a href="logout.php" style="color: white; margin-left: 1rem;">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo e($success_message); ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo e($error_message); ?></div>
        <?php endif; ?>
        
        <!-- Available Courses for Registration -->
        <div class="section-card">
            <div class="section-header">
                <h2>📖 Courses Available for Registration</h2>
                <p style="color: #666; margin-top: 0.5rem;">These courses have been assigned to you by your teachers.</p>
            </div>
            
            <?php if ($available_courses->num_rows > 0): ?>
            <div class="course-grid">
                <?php while ($course = $available_courses->fetch_assoc()): ?>
                <div class="course-card">
                    <div class="course-code"><?php echo e($course['course_code']); ?></div>
                    <div class="course-name"><?php echo e($course['course_name']); ?></div>
                    <div class="course-details">
                        <p><?php echo nl2br(e(substr($course['description'], 0, 100))); ?>...</p>
                        <p><strong>Credits:</strong> <?php echo $course['credits']; ?></p>
                        <p><strong>Teacher:</strong> <?php echo e($course['teacher_first'] . ' ' . $course['teacher_last']); ?></p>
                        <p><strong>Semester:</strong> <?php echo e($course['semester']); ?></p>
                        <p><strong>Academic Year:</strong> <?php echo e($course['academic_year']); ?></p>
                    </div>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to register for <?php echo e($course['course_name']); ?>?');">
                        <input type="hidden" name="eligibility_id" value="<?php echo $course['id']; ?>">
                        <button type="submit" name="request_registration" class="btn btn-primary">
                            Register for this Course
                        </button>
                    </form>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <p style="text-align: center; padding: 2rem; color: #999;">
                No courses have been assigned to you yet. Please check back later when your teachers assign courses.
            </p>
            <?php endif; ?>
        </div>
        
        <!-- My Registered Courses -->
        <div class="section-card">
            <div class="section-header">
                <h2>✅ My Registered Courses</h2>
            </div>
            
            <?php if ($registered_courses->num_rows > 0): ?>
            <div class="course-grid">
                <?php while ($course = $registered_courses->fetch_assoc()): ?>
                <div class="course-card" style="background: #f8fafc;">
                    <div class="course-code"><?php echo e($course['course_code']); ?></div>
                    <div class="course-name"><?php echo e($course['course_name']); ?></div>
                    <div class="course-details">
                        <p><strong>Credits:</strong> <?php echo $course['credits']; ?></p>
                        <p><strong>Registered on:</strong> <?php echo date('d M Y', strtotime($course['registration_date'])); ?></p>
                        <p><strong>Status:</strong> <span class="badge badge-registered">Registered</span></p>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <p style="text-align: center; padding: 2rem; color: #999;">
                You haven't registered for any courses yet. Select courses above to register.
            </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>