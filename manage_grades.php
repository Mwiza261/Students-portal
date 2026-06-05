<?php
session_start();
require_once __DIR__ . '/db.php';

// Check if user is logged in and is teacher/staff
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'staff' && $_SESSION['role'] !== 'teacher')) {
    header('Location: StaffLogin.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

$mysqli = db_connect();

// Handle grade submission/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_grades'])) {
        $student_id = intval($_POST['student_id']);
        $course_id = intval($_POST['course_id']);
        $grade = strtoupper(trim($_POST['grade']));
        $score = floatval($_POST['score']);
        $semester = $_POST['semester'];
        $academic_year = $_POST['academic_year'];
        $assessment_type = $_POST['assessment_type'];
        $comments = trim($_POST['comments']);
        
        // Validate grade
        $valid_grades = ['A', 'B', 'C', 'D', 'F', 'I', 'W'];
        if (!in_array($grade, $valid_grades)) {
            $error_message = "Invalid grade. Please enter A, B, C, D, F, I, or W.";
        } elseif ($score < 0 || $score > 100) {
            $error_message = "Score must be between 0 and 100.";
        } else {
            // Check if grade already exists
            $check_stmt = $mysqli->prepare("
                SELECT id FROM grades 
                WHERE student_id = ? AND course_id = ? AND semester = ? AND academic_year = ?
            ");
            $check_stmt->bind_param("iiss", $student_id, $course_id, $semester, $academic_year);
            $check_stmt->execute();
            $exists = $check_stmt->get_result()->num_rows > 0;
            $check_stmt->close();
            
            if ($exists) {
                // Update existing grade
                $stmt = $mysqli->prepare("
                    UPDATE grades 
                    SET grade = ?, score = ?, assessment_type = ?, comments = ?, last_modified = NOW()
                    WHERE student_id = ? AND course_id = ? AND semester = ? AND academic_year = ?
                ");
                $stmt->bind_param("sdssiiss", $grade, $score, $assessment_type, $comments, $student_id, $course_id, $semester, $academic_year);
            } else {
                // Insert new grade
                $stmt = $mysqli->prepare("
                    INSERT INTO grades (student_id, course_id, teacher_id, grade, score, semester, academic_year, assessment_type, comments, entered_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("iiisdssssi", $student_id, $course_id, $teacher_id, $grade, $score, $semester, $academic_year, $assessment_type, $comments, $teacher_id);
            }
            
            if ($stmt->execute()) {
                $success_message = "Grade saved successfully!";
            } else {
                $error_message = "Failed to save grade: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    
    // Handle bulk grade upload
    if (isset($_POST['bulk_save'])) {
        $course_id = intval($_POST['course_id']);
        $semester = $_POST['semester'];
        $academic_year = $_POST['academic_year'];
        $assessment_type = $_POST['assessment_type'];
        
        $saved_count = 0;
        $error_count = 0;
        
        foreach ($_POST['grades'] as $student_id => $data) {
            $grade = strtoupper(trim($data['grade']));
            $score = floatval($data['score']);
            $comments = trim($data['comments']);
            
            $valid_grades = ['A', 'B', 'C', 'D', 'F', 'I', 'W'];
            if (in_array($grade, $valid_grades) && $score >= 0 && $score <= 100) {
                // Check if exists
                $check_stmt = $mysqli->prepare("
                    SELECT id FROM grades 
                    WHERE student_id = ? AND course_id = ? AND semester = ? AND academic_year = ?
                ");
                $check_stmt->bind_param("iiss", $student_id, $course_id, $semester, $academic_year);
                $check_stmt->execute();
                $exists = $check_stmt->get_result()->num_rows > 0;
                $check_stmt->close();
                
                if ($exists) {
                    $stmt = $mysqli->prepare("
                        UPDATE grades 
                        SET grade = ?, score = ?, assessment_type = ?, comments = ?, last_modified = NOW()
                        WHERE student_id = ? AND course_id = ? AND semester = ? AND academic_year = ?
                    ");
                    $stmt->bind_param("sdssiiss", $grade, $score, $assessment_type, $comments, $student_id, $course_id, $semester, $academic_year);
                } else {
                    $stmt = $mysqli->prepare("
                        INSERT INTO grades (student_id, course_id, teacher_id, grade, score, semester, academic_year, assessment_type, comments, entered_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("iiisdssssi", $student_id, $course_id, $teacher_id, $grade, $score, $semester, $academic_year, $assessment_type, $comments, $teacher_id);
                }
                
                if ($stmt->execute()) {
                    $saved_count++;
                } else {
                    $error_count++;
                }
                $stmt->close();
            } else {
                $error_count++;
            }
        }
        
        $success_message = "Bulk save completed! Saved: $saved_count, Errors: $error_count";
    }
}

// Get teacher's courses
$courses_query = "
    SELECT DISTINCT c.* 
    FROM courses c
    LEFT JOIN teacher_course_assignments tca ON c.id = tca.course_id
    WHERE tca.teacher_id = ? OR c.teacher_id = ?
    ORDER BY c.course_code
";
$stmt = $mysqli->prepare($courses_query);
$stmt->bind_param("ii", $teacher_id, $teacher_id);
$stmt->execute();
$courses = $stmt->get_result();

// Get selected course's students
$selected_course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$selected_semester = isset($_GET['semester']) ? $_GET['semester'] : 'Semester 1';
$selected_academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : date('Y');
$students_list = [];

if ($selected_course_id > 0) {
    $students_query = "
        SELECT DISTINCT u.id, u.username, u.first_name, u.surname, u.email,
               g.grade, g.score, g.comments, g.assessment_type, g.id as grade_id
        FROM users u
        JOIN student_courses sc ON u.id = sc.student_id
        LEFT JOIN grades g ON u.id = g.student_id 
            AND g.course_id = ? 
            AND g.semester = ? 
            AND g.academic_year = ?
        WHERE sc.course_id = ? 
        AND sc.status = 'registered'
        AND u.role = 'student'
        ORDER BY u.surname, u.first_name
    ";
    $stmt = $mysqli->prepare($students_query);
    $stmt->bind_param("issi", $selected_course_id, $selected_semester, $selected_academic_year, $selected_course_id);
    $stmt->execute();
    $students_list = $stmt->get_result();
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
    <title>Manage Grades | Teacher Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            color: #333;
        }

        /* Navigation */
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 1.5rem;
            padding: 0.5rem;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.1);
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .card-header h2 {
            color: #1e293b;
            font-size: 1.3rem;
        }

        /* Forms */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #475569;
        }

        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.7rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        /* Buttons */
        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-1px);
        }

        .btn-success {
            background: #48bb78;
            color: white;
        }

        .btn-success:hover {
            background: #38a169;
        }

        .btn-warning {
            background: #ed8936;
            color: white;
        }

        .btn-info {
            background: #4299e1;
            color: white;
        }

        /* Table */
        .grades-table {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8fafc;
            padding: 0.8rem;
            text-align: left;
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 0.8rem;
            border-bottom: 1px solid #e2e8f0;
        }

        tr:hover {
            background: #f8fafc;
        }

        .grade-input {
            width: 70px;
            text-align: center;
        }

        .score-input {
            width: 80px;
            text-align: center;
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-box {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.85rem;
            margin-top: 0.3rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h2>📚 Grade Management System</h2>
        <div class="nav-links">
            <span>👋 Welcome, <?php echo e($_SESSION['username']); ?></span>
            <a href="StaffDashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo e($success_message); ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo e($error_message); ?></div>
        <?php endif; ?>

        <!-- Course Selection -->
        <div class="card">
            <div class="card-header">
                <h2>📖 Select Course to Manage Grades</h2>
            </div>
            <form method="GET" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="course_id">Course</label>
                        <select name="course_id" id="course_id" required onchange="this.form.submit()">
                            <option value="">-- Select Course --</option>
                            <?php while ($course = $courses->fetch_assoc()): ?>
                            <option value="<?php echo $course['id']; ?>" <?php echo $selected_course_id == $course['id'] ? 'selected' : ''; ?>>
                                <?php echo e($course['course_code']); ?> - <?php echo e($course['course_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="semester">Semester</label>
                        <select name="semester" id="semester" onchange="this.form.submit()">
                            <option value="Semester 1" <?php echo $selected_semester == 'Semester 1' ? 'selected' : ''; ?>>Semester 1</option>
                            <option value="Semester 2" <?php echo $selected_semester == 'Semester 2' ? 'selected' : ''; ?>>Semester 2</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="academic_year">Academic Year</label>
                        <select name="academic_year" id="academic_year" onchange="this.form.submit()">
                            <option value="<?php echo date('Y'); ?>" <?php echo $selected_academic_year == date('Y') ? 'selected' : ''; ?>><?php echo date('Y'); ?></option>
                            <option value="<?php echo date('Y') - 1; ?>" <?php echo $selected_academic_year == date('Y') - 1 ? 'selected' : ''; ?>><?php echo date('Y') - 1; ?></option>
                            <option value="<?php echo date('Y') + 1; ?>" <?php echo $selected_academic_year == date('Y') + 1 ? 'selected' : ''; ?>><?php echo date('Y') + 1; ?></option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($selected_course_id > 0 && $students_list && $students_list->num_rows > 0): 
            // Calculate statistics
            $total_students = 0;
            $grades_distribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];
            $total_score = 0;
            $scores_count = 0;
            
            $students_array = [];
            while ($student = $students_list->fetch_assoc()) {
                $students_array[] = $student;
                $total_students++;
                if ($student['grade']) {
                    $grades_distribution[$student['grade']]++;
                }
                if ($student['score']) {
                    $total_score += $student['score'];
                    $scores_count++;
                }
            }
            $average_score = $scores_count > 0 ? round($total_score / $scores_count, 2) : 0;
        ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value"><?php echo $total_students; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $average_score; ?>%</div>
                <div class="stat-label">Average Score</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">
                    <?php 
                    $pass_count = ($grades_distribution['A'] + $grades_distribution['B'] + $grades_distribution['C']);
                    $pass_rate = $total_students > 0 ? round(($pass_count / $total_students) * 100, 1) : 0;
                    echo $pass_rate . '%';
                    ?>
                </div>
                <div class="stat-label">Pass Rate</div>
            </div>
        </div>

        <!-- Single Grade Entry Form -->
        <div class="card">
            <div class="card-header">
                <h2>✏️ Quick Grade Entry</h2>
                <button onclick="toggleBulkMode()" class="btn btn-info">Switch to Bulk Mode</button>
            </div>
            <div id="single-mode">
                <form method="POST" action="">
                    <input type="hidden" name="course_id" value="<?php echo $selected_course_id; ?>">
                    <input type="hidden" name="semester" value="<?php echo $selected_semester; ?>">
                    <input type="hidden" name="academic_year" value="<?php echo $selected_academic_year; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="student_id">Select Student</label>
                            <select name="student_id" id="student_id" required>
                                <option value="">-- Select Student --</option>
                                <?php foreach ($students_array as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo e($student['surname'] . ' ' . $student['first_name']); ?> (<?php echo e($student['username']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="grade">Grade (A-F)</label>
                            <select name="grade" id="grade" required>
                                <option value="">-- Select Grade --</option>
                                <option value="A">A (Excellent) 80-100%</option>
                                <option value="B">B (Good) 70-79%</option>
                                <option value="C">C (Satisfactory) 60-69%</option>
                                <option value="D">D (Pass) 50-59%</option>
                                <option value="F">F (Fail) Below 50%</option>
                                <option value="I">I (Incomplete)</option>
                                <option value="W">W (Withdrawn)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="score">Score (0-100)</label>
                            <input type="number" name="score" id="score" step="0.01" min="0" max="100" required>
                        </div>
                        <div class="form-group">
                            <label for="assessment_type">Assessment Type</label>
                            <select name="assessment_type" id="assessment_type">
                                <option value="continuous_assessment">Continuous Assessment</option>
                                <option value="mid_exam">Mid-Semester Exam</option>
                                <option value="final_exam">Final Exam</option>
                                <option value="total">Total Score</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="comments">Comments (Optional)</label>
                        <textarea name="comments" id="comments" rows="2" placeholder="Additional comments about student performance..."></textarea>
                    </div>
                    <button type="submit" name="save_grades" class="btn btn-primary">Save Grade</button>
                </form>
            </div>
            
            <!-- Bulk Grade Entry Mode -->
            <div id="bulk-mode" style="display: none;">
                <form method="POST" action="">
                    <input type="hidden" name="course_id" value="<?php echo $selected_course_id; ?>">
                    <input type="hidden" name="semester" value="<?php echo $selected_semester; ?>">
                    <input type="hidden" name="academic_year" value="<?php echo $selected_academic_year; ?>">
                    <input type="hidden" name="assessment_type" value="total">
                    
                    <div class="grades-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Username</th>
                                    <th>Grade</th>
                                    <th>Score (%)</th>
                                    <th>Comments</th>
                                    <th>Current Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students_array as $student): ?>
                                <tr>
                                    <td><?php echo e($student['surname'] . ' ' . $student['first_name']); ?></td>
                                    <td><?php echo e($student['username']); ?></td>
                                    <td>
                                        <select name="grades[<?php echo $student['id']; ?>][grade]" class="grade-input">
                                            <option value="">--</option>
                                            <option value="A" <?php echo $student['grade'] == 'A' ? 'selected' : ''; ?>>A</option>
                                            <option value="B" <?php echo $student['grade'] == 'B' ? 'selected' : ''; ?>>B</option>
                                            <option value="C" <?php echo $student['grade'] == 'C' ? 'selected' : ''; ?>>C</option>
                                            <option value="D" <?php echo $student['grade'] == 'D' ? 'selected' : ''; ?>>D</option>
                                            <option value="F" <?php echo $student['grade'] == 'F' ? 'selected' : ''; ?>>F</option>
                                            <option value="I" <?php echo $student['grade'] == 'I' ? 'selected' : ''; ?>>I</option>
                                            <option value="W" <?php echo $student['grade'] == 'W' ? 'selected' : ''; ?>>W</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="grades[<?php echo $student['id']; ?>][score]" 
                                               value="<?php echo $student['score']; ?>" 
                                               class="score-input" step="0.01" min="0" max="100">
                                    </td>
                                    <td>
                                        <input type="text" name="grades[<?php echo $student['id']; ?>][comments]" 
                                               value="<?php echo e($student['comments']); ?>" 
                                               style="width: 100%; padding: 0.3rem;">
                                    </td>
                                    <td>
                                        <?php if ($student['grade']): ?>
                                            <span style="font-weight: bold; color: <?php echo $student['grade'] == 'F' ? 'red' : 'green'; ?>">
                                                <?php echo $student['grade'] . ' (' . $student['score'] . '%)'; ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #999;">Not graded</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 1rem;">
                        <button type="submit" name="bulk_save" class="btn btn-success">Save All Grades</button>
                        <button type="button" onclick="toggleBulkMode()" class="btn btn-warning">Switch to Single Mode</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Grade Table View -->
        <div class="card">
            <div class="card-header">
                <h2>📊 Current Grades</h2>
                <button onclick="exportToCSV()" class="btn btn-success">Export to CSV</button>
            </div>
            <div class="grades-table">
                <table id="grades-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Username</th>
                            <th>Grade</th>
                            <th>Score</th>
                            <th>Assessment Type</th>
                            <th>Comments</th>
                            <th>Last Modified</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php foreach ($students_array as $student): ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><?php echo e($student['surname'] . ' ' . $student['first_name']); ?></td>
                            <td><?php echo e($student['username']); ?></td>
                            <td>
                                <?php if ($student['grade']): ?>
                                    <span style="font-weight: bold; font-size: 1.1rem; color: <?php 
                                        echo $student['grade'] == 'A' ? '#48bb78' : 
                                            ($student['grade'] == 'F' ? '#f56565' : '#667eea'); 
                                    ?>;">
                                        <?php echo $student['grade']; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #999;">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $student['score'] ? $student['score'] . '%' : '-'; ?></td>
                            <td><?php echo str_replace('_', ' ', $student['assessment_type'] ?? '-'); ?></td>
                            <td><?php echo e($student['comments'] ?: '-'); ?></td>
                            <td><?php echo $student['grade_id'] ? 'Updated' : 'Not set'; ?></td>
                            <td>
                                <button onclick="editGrade(<?php echo $student['id']; ?>, '<?php echo $student['grade']; ?>', <?php echo $student['score']; ?>)" 
                                        class="btn btn-primary" style="padding: 0.3rem 0.6rem;">Edit</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php elseif ($selected_course_id > 0): ?>
            <div class="alert alert-error">
                No students found registered for this course. Please make sure students are registered.
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleBulkMode() {
            var singleMode = document.getElementById('single-mode');
            var bulkMode = document.getElementById('bulk-mode');
            
            if (singleMode.style.display === 'none') {
                singleMode.style.display = 'block';
                bulkMode.style.display = 'none';
            } else {
                singleMode.style.display = 'none';
                bulkMode.style.display = 'block';
            }
        }
        
        function editGrade(studentId, currentGrade, currentScore) {
            document.getElementById('student_id').value = studentId;
            document.getElementById('grade').value = currentGrade;
            document.getElementById('score').value = currentScore;
            document.getElementById('single-mode').scrollIntoView({ behavior: 'smooth' });
        }
        
        function exportToCSV() {
            var table = document.getElementById('grades-table');
            var rows = table.querySelectorAll('tr');
            var csv = [];
            
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll('td, th');
                for (var j = 0; j < cols.length; j++) {
                    var text = cols[j].innerText.replace(/,/g, '');
                    row.push('"' + text + '"');
                }
                csv.push(row.join(','));
            }
            
            var blob = new Blob([csv.join('\n')], { type: 'text/csv' });
            var link = document.createElement('a');
            var url = URL.createObjectURL(blob);
            link.href = url;
            link.download = 'grades_<?php echo $selected_course_id; ?>.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }
        
        // Auto-calculate grade based on score
        document.getElementById('score')?.addEventListener('change', function() {
            var score = parseFloat(this.value);
            var gradeSelect = document.getElementById('grade');
            
            if (!isNaN(score)) {
                if (score >= 80) gradeSelect.value = 'A';
                else if (score >= 70) gradeSelect.value = 'B';
                else if (score >= 60) gradeSelect.value = 'C';
                else if (score >= 50) gradeSelect.value = 'D';
                else if (score < 50 && score >= 0) gradeSelect.value = 'F';
            }
        });
    </script>
</body>
</html>