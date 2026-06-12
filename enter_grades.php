<?php
session_start();
require_once __DIR__ . '/db.php';

// Auth guard - only staff can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: StaffLogin.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Teacher';
$current_page = basename($_SERVER['PHP_SELF']);
$currentYear = (int) date('Y');

if (!function_exists('e')) {
    function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
}

$mysqli = db_connect();
$message = '';
$messageType = '';

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grade'])) {
    $course_id = intval($_POST['course_id']);
    $student_id = intval($_POST['student_id']);
    $grade = strtoupper(trim($_POST['grade']));
    $remarks = trim($_POST['remarks'] ?? '');
    
    // Calculate grade points
    $grade_points = 0;
    switch ($grade) {
        case 'A': $grade_points = 4.00; break;
        case 'B+': $grade_points = 3.50; break;
        case 'B': $grade_points = 3.00; break;
        case 'C+': $grade_points = 2.50; break;
        case 'C': $grade_points = 2.00; break;
        case 'D': $grade_points = 1.00; break;
        case 'F': $grade_points = 0.00; break;
        default: $grade_points = 0.00;
    }
    
    // Update student_courses table
    $stmt = $mysqli->prepare("
        UPDATE student_courses 
        SET grade = ?, grade_points = ?, remarks = ?, graded_at = NOW(), graded_by = ?
        WHERE course_id = ? AND student_id = ?
    ");
    $stmt->bind_param("sdsiii", $grade, $grade_points, $remarks, $teacher_id, $course_id, $student_id);
    
    if ($stmt->execute()) {
        $message = "Grade saved successfully!";
        $messageType = "success";
    } else {
        $message = "Failed to save grade: " . $stmt->error;
        $messageType = "error";
    }
    $stmt->close();
}

// FIRST: Get all courses assigned to this teacher
$courses = [];
$courses_query = "
    SELECT DISTINCT 
        c.id, 
        c.course_code, 
        c.course_name, 
        c.credits,
        c.department
    FROM courses c
    INNER JOIN teacher_course_assignments tca ON c.id = tca.course_id
    WHERE tca.teacher_id = ? AND tca.status = 'active'
    ORDER BY c.course_code
";

$stmt = $mysqli->prepare($courses_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$courses_result = $stmt->get_result();

if ($courses_result && $courses_result->num_rows > 0) {
    while ($course = $courses_result->fetch_assoc()) {
        $courses[] = $course;
    }
}
$stmt->close();

// If no courses found, try without teacher_course_assignments (get all courses for testing)
if (empty($courses)) {
    $all_courses_query = "SELECT id, course_code, course_name, credits FROM courses ORDER BY course_code LIMIT 20";
    $all_courses_result = $mysqli->query($all_courses_query);
    if ($all_courses_result && $all_courses_result->num_rows > 0) {
        while ($course = $all_courses_result->fetch_assoc()) {
            $courses[] = $course;
        }
        $message = "Showing all courses (no teacher assignments found)";
        $messageType = "warning";
    }
}

// Get selected course students
$selected_course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : (isset($_POST['course_id']) ? intval($_POST['course_id']) : 0);
$students = [];
$course_info = null;

if ($selected_course_id > 0) {
    // Get course info
    $course_stmt = $mysqli->prepare("SELECT course_code, course_name, credits FROM courses WHERE id = ?");
    $course_stmt->bind_param("i", $selected_course_id);
    $course_stmt->execute();
    $course_info = $course_stmt->get_result()->fetch_assoc();
    $course_stmt->close();
    
    // Get enrolled students for this course
    $students_query = "
        SELECT u.id, u.username, u.first_name, u.surname, u.email, 
               COALESCE(sc.grade, '') as grade, 
               COALESCE(sc.remarks, '') as remarks,
               COALESCE(sc.grade_points, 0) as grade_points
        FROM users u
        INNER JOIN student_courses sc ON u.id = sc.student_id
        WHERE sc.course_id = ? AND u.role = 'student'
        ORDER BY u.surname, u.first_name
    ";
    $stmt = $mysqli->prepare($students_query);
    $stmt->bind_param("i", $selected_course_id);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$mysqli->close();

$initials = strtoupper(substr($teacher_name, 0, 1));
$greeting = date('H') < 12 ? 'Good morning' : (date('H') < 17 ? 'Good afternoon' : 'Good evening');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Grades | Staff Portal</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            color: #333;
        }

        .container { display: flex; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h3 { font-size: 1.3rem; margin-top: 0.5rem; }

        .sidebar-nav { padding: 1rem 0; }
        .nav-section { margin-bottom: 1rem; }
        .nav-section-title {
            padding: 0.5rem 1.5rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 600;
        }
        .sidebar-nav a {
            display: block;
            padding: 0.7rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .sidebar-nav a:hover, .sidebar-nav a.active {
            background: rgba(99,102,241,0.2);
            color: white;
            border-left: 3px solid #6366f1;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 1.5rem;
        }

        .top-bar {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .page-title h1 { font-size: 1.5rem; color: #1e293b; }
        .page-title p { color: #64748b; font-size: 0.85rem; margin-top: 0.25rem; }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .card h3 {
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
            color: #1e293b;
        }

        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; }
        select, input, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        select:focus, input:focus, textarea:focus {
            outline: none;
            border-color: #6366f1;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #4f46e5; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-sm { padding: 0.3rem 0.8rem; font-size: 0.8rem; }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message.warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 600; color: #475569; }
        tr:hover { background: #f8fafc; }

        .grade-select { width: 120px; padding: 0.4rem; }
        .badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-graded { background: #d4edda; color: #155724; }
        .badge-pending { background: #fef3c7; color: #92400e; }

        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .course-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
        }
        .course-card:hover {
            transform: translateY(-2px);
            border-color: #6366f1;
            background: #eef2ff;
        }
        .course-code { font-weight: 700; color: #1e293b; font-size: 1rem; }
        .course-name { font-size: 0.85rem; color: #64748b; margin-top: 0.25rem; }
        .course-stats { font-size: 0.75rem; color: #6366f1; margin-top: 0.5rem; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); position: fixed; z-index: 1000; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div style="font-size: 2rem;">📚</div>
            <h3>Chigoneka School</h3>
            <p style="font-size: 0.75rem;">Staff Portal</p>
        </div>
        <div class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">MAIN</div>
                <a href="StaffDashboard.php">Dashboard</a>
                <a href="admin_messages.php">Messages</a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">COURSES</div>
                <a href="assign_course_to_students.php">Assign to Students</a>
                <a href="manage_students.php">Manage Students</a>
                <a href="view_course_requests.php">Course Requests</a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">GRADES</div>
                <a href="enter_grades.php" class="active">✏️ Enter Grades</a>
                <a href="manage_grades.php">📝 Manage Grades</a>
                <a href="view_student_grades.php">👁️ View Student Grades</a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">ACCOUNT</div>
                <a href="change_password.php">🔒 Change Password</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1>✏️ Enter Student Grades</h1>
                <p><?php echo $greeting; ?>, <?php echo e($teacher_name); ?>! Select a course to enter grades</p>
            </div>
            <div class="user-avatar">
                <?php echo $initials; ?>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo e($message); ?></div>
        <?php endif; ?>

        <!-- Course Selection - Show as cards -->
        <?php if ($selected_course_id == 0): ?>
            <div class="card">
                <h3>📖 Select a Course to Enter Grades</h3>
                <?php if (!empty($courses)): ?>
                    <div class="course-grid">
                        <?php foreach ($courses as $course): ?>
                            <a href="?course_id=<?php echo $course['id']; ?>" class="course-card">
                                <div class="course-code"><?php echo e($course['course_code']); ?></div>
                                <div class="course-name"><?php echo e($course['course_name']); ?></div>
                                <div class="course-stats">
                                    Credits: <?php echo $course['credits']; ?> | 
                                    Department: <?php echo $course['department'] ?? 'General'; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #64748b; padding: 2rem;">
                        ❌ No courses found.<br><br>
                        <strong>Possible reasons:</strong><br>
                        1. No courses have been assigned to you yet<br>
                        2. The courses table is empty<br>
                        3. The teacher_course_assignments table is empty<br><br>
                        <a href="manage_courses.php" style="color: #6366f1;">→ Manage Courses</a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Grade Entry Form for Selected Course -->
        <?php if ($selected_course_id > 0 && $course_info): ?>
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="margin: 0; border: none;">
                        📝 <?php echo e($course_info['course_code']); ?> - <?php echo e($course_info['course_name']); ?>
                        <span style="font-size: 0.8rem; font-weight: normal; color: #64748b;">
                            (<?php echo count($students); ?> students enrolled)
                        </span>
                    </h3>
                    <a href="enter_grades.php" class="btn btn-primary btn-sm">← Choose Different Course</a>
                </div>
                
                <?php if (!empty($students)): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Email</th>
                                    <th>Current Grade</th>
                                    <th>New Grade</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <form method="POST" action="" style="margin: 0;">
                                        <input type="hidden" name="course_id" value="<?php echo $selected_course_id; ?>">
                                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                        <tr>
                                            <td><strong><?php echo e($student['first_name'] . ' ' . $student['surname']); ?></strong></td>
                                            <td><?php echo e($student['email']); ?></td>
                                            <td>
                                                <?php if (!empty($student['grade'])): ?>
                                                    <span class="badge badge-graded"><?php echo e($student['grade']); ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-pending">Not graded</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <select name="grade" class="grade-select" required>
                                                    <option value="">Select Grade</option>
                                                    <option value="A">A (80-100%) - Excellent</option>
                                                    <option value="B+">B+ (75-79%) - Very Good</option>
                                                    <option value="B">B (70-74%) - Good</option>
                                                    <option value="C+">C+ (65-69%) - Satisfactory Plus</option>
                                                    <option value="C">C (60-64%) - Satisfactory</option>
                                                    <option value="D">D (50-59%) - Pass</option>
                                                    <option value="F">F (0-49%) - Fail</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="remarks" value="<?php echo e($student['remarks'] ?? ''); ?>" 
                                                       placeholder="Remarks" style="width: 150px;">
                                            </td>
                                            <td>
                                                <button type="submit" name="save_grade" class="btn btn-success btn-sm">Save Grade</button>
                                            </td>
                                        </tr>
                                    </form>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #64748b; padding: 2rem;">
                        ❌ No students enrolled in this course yet.
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Grade Scale Reference -->
        <div class="card">
            <h3>📊 Grade Scale Reference</h3>
            <div class="table-wrapper">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Grade</th>
                            <th>Percentage</th>
                            <th>Grade Points</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>A</strong></td><td>80-100%</td><td>4.00</td><td>Excellent</td></tr>
                        <tr><td><strong>B+</strong></td><td>75-79%</td><td>3.50</td><td>Very Good</td></tr>
                        <tr><td><strong>B</strong></td><td>70-74%</td><td>3.00</td><td>Good</td></tr>
                        <tr><td><strong>C+</strong></td><td>65-69%</td><td>2.50</td><td>Satisfactory Plus</td></tr>
                        <tr><td><strong>C</strong></td><td>60-64%</td><td>2.00</td><td>Satisfactory</td></tr>
                        <tr><td><strong>D</strong></td><td>50-59%</td><td>1.00</td><td>Pass</td></tr>
                        <tr><td><strong>F</strong></td><td>0-49%</td><td>0.00</td><td>Fail</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>