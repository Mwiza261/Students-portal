<?php
session_start();
require_once __DIR__ . '/db.php';

// Auth guard - only staff can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: StaffLogin.php');
    exit;
}

$mysqli = db_connect();
$message = '';
$messageType = '';

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grades'])) {
    $course_id = intval($_POST['course_id']);
    $student_id = intval($_POST['student_id']);
    $grade = strtoupper(trim($_POST['grade']));
    $remarks = trim($_POST['remarks'] ?? '');
    
    // Calculate grade points based on grade
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
    
    // Check if grade column exists, if not use a different approach
    $check_column = $mysqli->query("SHOW COLUMNS FROM course_registrations LIKE 'grade'");
    $has_grade_column = $check_column && $check_column->num_rows > 0;
    
    if ($has_grade_column) {
        // Update with grade columns
        $stmt = $mysqli->prepare("
            UPDATE course_registrations 
            SET grade = ?, grade_points = ?, graded_by = ?, graded_at = NOW(), remarks = ?
            WHERE course_id = ? AND student_id = ? AND status = 'enrolled'
        ");
        $stmt->bind_param("sdiisi", $grade, $grade_points, $_SESSION['user_id'], $remarks, $course_id, $student_id);
    } else {
        // Fallback - update student_courses table instead
        $stmt = $mysqli->prepare("
            UPDATE student_courses 
            SET grade = ?, remarks = ?
            WHERE course_id = ? AND student_id = ?
        ");
        $stmt->bind_param("ssii", $grade, $remarks, $course_id, $student_id);
    }
    
    if ($stmt && $stmt->execute()) {
        $message = "Grade submitted successfully!";
        $messageType = "success";
    } else {
        $message = "Failed to submit grade: " . ($stmt ? $stmt->error : "Unknown error");
        $messageType = "error";
    }
    if ($stmt) $stmt->close();
}

// Get all courses
$courses = [];
$courseResult = $mysqli->query("SELECT id, course_code, course_name FROM courses ORDER BY course_code");
if ($courseResult) {
    while ($row = $courseResult->fetch_assoc()) {
        $courses[] = $row;
    }
}

// Get selected course students
$selected_course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$students = [];

if ($selected_course_id > 0) {
    // Check which table to use
    $table_check = $mysqli->query("SHOW TABLES LIKE 'course_registrations'");
    $use_registrations_table = $table_check && $table_check->num_rows > 0;
    
    if ($use_registrations_table) {
        // Try with course_registrations table
        $query = "
            SELECT u.id, u.username, u.first_name, u.surname, u.email, 
                   cr.grade, cr.remarks, cr.graded_at,
                   c.course_name, c.course_code
            FROM users u
            INNER JOIN course_registrations cr ON u.id = cr.student_id
            INNER JOIN courses c ON cr.course_id = c.id
            WHERE cr.course_id = ? AND cr.status = 'enrolled' AND u.role = 'student'
            ORDER BY u.surname, u.first_name
        ";
        $stmt = $mysqli->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $selected_course_id);
            $stmt->execute();
            $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    } else {
        // Fallback to student_courses table
        $query = "
            SELECT u.id, u.username, u.first_name, u.surname, u.email, 
                   sc.grade, sc.remarks,
                   c.course_name, c.course_code
            FROM users u
            INNER JOIN student_courses sc ON u.id = sc.student_id
            INNER JOIN courses c ON sc.course_id = c.id
            WHERE sc.course_id = ? AND u.role = 'student'
            ORDER BY u.surname, u.first_name
        ";
        $stmt = $mysqli->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $selected_course_id);
            $stmt->execute();
            $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Grades - Staff Portal</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f3fb;
            color: #333;
        }
        .container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            background: #1a1a2e;
            color: #fff;
            padding: 30px 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar h2 { font-size: 24px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #16213e; }
        .sidebar nav ul { list-style: none; }
        .sidebar nav ul li { margin-bottom: 10px; }
        .sidebar nav ul li a {
            color: #aaa;
            text-decoration: none;
            display: block;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .sidebar nav ul li a:hover, .sidebar nav ul li a.active { background: #16213e; color: #fff; }
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .header h1 { font-size: 28px; color: #1a1a2e; }
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .card h3 {
            margin-bottom: 20px;
            color: #1a1a2e;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f3fb;
        }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        select, input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        button {
            background: #4f46e5;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        button:hover { background: #4338ca; }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .grades-table {
            width: 100%;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #1a1a2e;
        }
        .grade-input {
            width: 100px;
            padding: 8px;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-graded { background: #28a745; color: white; }
        .badge-pending { background: #ffc107; color: #333; }
        @media (max-width: 768px) {
            .sidebar { width: 100%; position: relative; height: auto; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <h2>🎓 Staff Portal</h2>
        <nav>
            <ul>
                <li><a href="StaffDashboard.php">📊 Dashboard</a></li>
                <li><a href="manage_courses.php">📚 Manage Courses</a></li>
                <li><a href="manage_students.php">👨‍🎓 Manage Students</a></li>
                <li><a href="staff_grades.php" class="active">📝 Manage Grades</a></li>
                <li><a href="view_registrations.php">📋 Registrations</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1>📝 Manage Student Grades</h1>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Select Course</h3>
            <form method="GET" action="">
                <div class="form-group">
                    <label for="course_id">Choose Course</label>
                    <select name="course_id" id="course_id" onchange="this.form.submit()">
                        <option value="">-- Select a course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" <?php echo $selected_course_id == $course['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_code']); ?> - <?php echo htmlspecialchars($course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($selected_course_id > 0 && !empty($students)): ?>
            <div class="card">
                <h3>Enter Grades for <?php echo htmlspecialchars($students[0]['course_code']); ?> - <?php echo htmlspecialchars($students[0]['course_name']); ?></h3>
                
                <div class="grades-table">
                    <form method="POST" action="">
                        <input type="hidden" name="course_id" value="<?php echo $selected_course_id; ?>">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student ID</th>
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
                                    <tr>
                                        <form method="POST" action="" style="margin:0;" id="form_<?php echo $student['id']; ?>">
                                            <input type="hidden" name="course_id" value="<?php echo $selected_course_id; ?>">
                                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                            <td><?php echo htmlspecialchars($student['username']); ?></td>
                                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['surname']); ?></td>
                                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                                            <td>
                                                <?php if (!empty($student['grade'])): ?>
                                                    <span class="badge badge-graded"><?php echo htmlspecialchars($student['grade']); ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-pending">Not graded</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <select name="grade" class="grade-input" required>
                                                    <option value="">Select Grade</option>
                                                    <option value="A" <?php echo ($student['grade'] ?? '') == 'A' ? 'selected' : ''; ?>>A (80-100)</option>
                                                    <option value="B+" <?php echo ($student['grade'] ?? '') == 'B+' ? 'selected' : ''; ?>>B+ (75-79)</option>
                                                    <option value="B" <?php echo ($student['grade'] ?? '') == 'B' ? 'selected' : ''; ?>>B (70-74)</option>
                                                    <option value="C+" <?php echo ($student['grade'] ?? '') == 'C+' ? 'selected' : ''; ?>>C+ (65-69)</option>
                                                    <option value="C" <?php echo ($student['grade'] ?? '') == 'C' ? 'selected' : ''; ?>>C (60-64)</option>
                                                    <option value="D" <?php echo ($student['grade'] ?? '') == 'D' ? 'selected' : ''; ?>>D (50-59)</option>
                                                    <option value="F" <?php echo ($student['grade'] ?? '') == 'F' ? 'selected' : ''; ?>>F (0-49)</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="remarks" placeholder="Remarks" value="<?php echo htmlspecialchars($student['remarks'] ?? ''); ?>" style="width: 150px;">
                                            </td>
                                            <td>
                                                <button type="submit" name="save_grades" class="btn-small">Save Grade</button>
                                            </td>
                                        </form>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>
        <?php elseif ($selected_course_id > 0 && empty($students)): ?>
            <div class="card">
                <p>No students enrolled in this course yet.</p>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>