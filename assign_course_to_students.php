<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: StaffLogin.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

$mysqli = db_connect();

// Get teacher's courses
$courses_query = "
    SELECT c.* FROM teacher_course_assignments tca
    JOIN courses c ON tca.course_id = c.id
    WHERE tca.teacher_id = ? AND tca.status = 'active'
";
$stmt = $mysqli->prepare($courses_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$courses = $stmt->get_result();

// Get all students
$students_query = "SELECT id, username, first_name, surname, email FROM users WHERE role = 'student' ORDER BY first_name";
$students = $mysqli->query($students_query);

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_courses'])) {
    $selected_course_id = intval($_POST['course_id']);
    $selected_students = $_POST['students'] ?? [];
    $academic_year = $_POST['academic_year'] ?? date('Y');
    $semester = $_POST['semester'] ?? 'Semester 1';
    
    if (empty($selected_students)) {
        $error_message = 'Please select at least one student.';
    } else {
        $assigned_count = 0;
        $existing_count = 0;
        
        foreach ($selected_students as $student_id) {
            // Check if already assigned
            $check_stmt = $mysqli->prepare("
                SELECT id FROM student_course_eligibility 
                WHERE student_id = ? AND course_id = ? AND academic_year = ? AND semester = ?
            ");
            $check_stmt->bind_param("iiss", $student_id, $selected_course_id, $academic_year, $semester);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows == 0) {
                // Assign course to student
                $insert_stmt = $mysqli->prepare("
                    INSERT INTO student_course_eligibility (student_id, course_id, teacher_id, academic_year, semester, status)
                    VALUES (?, ?, ?, ?, ?, 'pending')
                ");
                $insert_stmt->bind_param("iiiss", $student_id, $selected_course_id, $teacher_id, $academic_year, $semester);
                
                if ($insert_stmt->execute()) {
                    $assigned_count++;
                }
                $insert_stmt->close();
            } else {
                $existing_count++;
            }
            $check_stmt->close();
        }
        
        if ($assigned_count > 0) {
            $success_message = "Successfully assigned $assigned_count student(s) to the course. $existing_count were already assigned.";
        } else {
            $error_message = "No new assignments were made. All selected students may already be assigned.";
        }
    }
}

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
    <title>Assign Course to Students</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; padding: 2rem; }
        
        .container { max-width: 900px; margin: 0 auto; }
        .card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        h1 { margin-bottom: 1rem; color: #1e293b; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333; }
        select, input { width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 6px; }
        
        .students-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
        }
        
        .student-item {
            padding: 0.5rem;
            margin: 0.25rem 0;
            border-radius: 6px;
            transition: background 0.2s;
        }
        
        .student-item:hover { background: #f8fafc; }
        .student-item input { width: auto; margin-right: 0.5rem; }
        
        .btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
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
        
        .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>📤 Assign Course to Students</h1>
            <p style="color: #666; margin-bottom: 1.5rem;">Select a course and choose students to assign eligibility for registration.</p>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo e($success_message); ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo e($error_message); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="course_id">Select Course *</label>
                    <select name="course_id" id="course_id" required>
                        <option value="">-- Select Course --</option>
                        <?php while ($course = $courses->fetch_assoc()): ?>
                        <option value="<?php echo $course['id']; ?>">
                            <?php echo e($course['course_code']); ?> - <?php echo e($course['course_name']); ?> (<?php echo $course['credits']; ?> credits)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Academic Year</label>
                    <input type="text" name="academic_year" value="<?php echo date('Y'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Semester</label>
                    <select name="semester" required>
                        <option value="Semester 1">Semester 1</option>
                        <option value="Semester 2">Semester 2</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Select Students to Assign *</label>
                    <div class="students-list">
                        <?php while ($student = $students->fetch_assoc()): ?>
                        <div class="student-item">
                            <label>
                                <input type="checkbox" name="students[]" value="<?php echo $student['id']; ?>">
                                <strong><?php echo e($student['first_name'] . ' ' . $student['surname']); ?></strong>
                                (<?php echo e($student['username']); ?>) - <?php echo e($student['email']); ?>
                            </label>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <button type="submit" name="assign_courses" class="btn btn-primary">Assign Course to Selected Students</button>
            </form>
            
            <a href="teacher_dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>