<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'staff') {
    header('Location: StaffLogin.php');
    exit;
}

if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

function table_exists(mysqli $mysqli, string $table): bool
{
    $result = $mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    return $result && $result->num_rows > 0;
}

function ensure_grade_columns(mysqli $mysqli, string $table): void
{
    if (!table_exists($mysqli, $table)) {
        return;
    }

    $columns = [
        'grade' => "ALTER TABLE `{$table}` ADD COLUMN grade VARCHAR(10) DEFAULT NULL",
        'grade_points' => "ALTER TABLE `{$table}` ADD COLUMN grade_points DECIMAL(4,2) DEFAULT NULL",
        'graded_by' => "ALTER TABLE `{$table}` ADD COLUMN graded_by INT DEFAULT NULL",
        'graded_at' => "ALTER TABLE `{$table}` ADD COLUMN graded_at TIMESTAMP NULL DEFAULT NULL",
        'remarks' => "ALTER TABLE `{$table}` ADD COLUMN remarks TEXT DEFAULT NULL",
    ];

    foreach ($columns as $column => $sql) {
        $check = $mysqli->query("SHOW COLUMNS FROM `{$table}` LIKE '" . $mysqli->real_escape_string($column) . "'");
        if (!$check || $check->num_rows === 0) {
            $mysqli->query($sql);
        }
    }
}

function grade_points_for(string $grade): float
{
    return match ($grade) {
        'A' => 4.00,
        'B+' => 3.50,
        'B' => 3.00,
        'C+' => 2.50,
        'C' => 2.00,
        'D' => 1.00,
        'F' => 0.00,
        default => 0.00,
    };
}

$teacher_id = (int) $_SESSION['user_id'];
$teacher_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Teacher';
$initials = strtoupper(substr($teacher_name, 0, 1));
$message = '';
$messageType = '';
$current_page = basename($_SERVER['PHP_SELF']);

$mysqli = db_connect();
ensure_grade_columns($mysqli, 'student_course_registration');
ensure_grade_columns($mysqli, 'course_registrations');

if (isset($_SESSION['flash'])) {
    $message = $_SESSION['flash']['message'] ?? '';
    $messageType = $_SESSION['flash']['type'] ?? '';
    unset($_SESSION['flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grade'])) {
    $course_id = (int) ($_POST['course_id'] ?? 0);
    $student_id = (int) ($_POST['student_id'] ?? 0);
    $grade = strtoupper(trim($_POST['grade'] ?? ''));
    $remarks = trim($_POST['remarks'] ?? '');
    $enrollment_table = $_POST['enrollment_table'] ?? 'student_course_registration';
    $grade_points = grade_points_for($grade);

    if (!in_array($enrollment_table, ['student_course_registration', 'course_registrations', 'student_courses'], true) || !table_exists($mysqli, $enrollment_table)) {
        $enrollment_table = 'student_course_registration';
    }

    if ($enrollment_table === 'student_courses') {
        $stmt = $mysqli->prepare("UPDATE student_courses SET grade = ?, remarks = ? WHERE course_id = ? AND student_id = ?");
        if ($stmt) {
            $stmt->bind_param('ssii', $grade, $remarks, $course_id, $student_id);
        }
    } else {
        $stmt = $mysqli->prepare("UPDATE `{$enrollment_table}` SET grade = ?, grade_points = ?, remarks = ?, graded_by = ?, graded_at = NOW() WHERE course_id = ? AND student_id = ? AND status = 'enrolled'");
        if ($stmt) {
            $stmt->bind_param('sdsiii', $grade, $grade_points, $remarks, $teacher_id, $course_id, $student_id);
        }
    }

    if (isset($stmt) && $stmt) {
        if ($stmt->execute() && $stmt->affected_rows >= 0) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Grade saved successfully.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to save grade: ' . $stmt->error];
        }
        $stmt->close();
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Unable to prepare grade update.'];
    }

    header('Location: manage_grades.php?course_id=' . $course_id);
    exit;
}

$selected_course_id = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
$course_info = null;
$students = [];
$enrollment_table = '';

$availableTables = [];
foreach (['student_course_registration', 'course_registrations', 'student_courses'] as $candidate) {
    if (table_exists($mysqli, $candidate)) {
        $availableTables[] = $candidate;
    }
}

$courses = [];
if (!empty($availableTables)) {
    $unionParts = [];
    foreach ($availableTables as $table) {
        if ($table === 'student_courses') {
            $unionParts[] = "SELECT course_id FROM student_courses";
        } else {
            $unionParts[] = "SELECT course_id FROM `{$table}` WHERE status = 'enrolled'";
        }
    }

    $courseQuery = "
        SELECT c.id, c.course_code, c.course_name, c.credits, COUNT(*) AS student_count
        FROM courses c
        INNER JOIN (
            " . implode(" UNION ALL ", $unionParts) . "
        ) reg ON reg.course_id = c.id
        GROUP BY c.id, c.course_code, c.course_name, c.credits
        ORDER BY c.course_code
    ";

    $courseStmt = $mysqli->prepare($courseQuery);
    if ($courseStmt) {
        $courseStmt->execute();
        $courses = $courseStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $courseStmt->close();
    }
}

if ($selected_course_id > 0) {
    $courseStmt = $mysqli->prepare('SELECT id, course_code, course_name, credits FROM courses WHERE id = ?');
    $courseStmt->bind_param('i', $selected_course_id);
    $courseStmt->execute();
    $course_info = $courseStmt->get_result()->fetch_assoc();
    $courseStmt->close();

    foreach ($availableTables as $candidate) {
        if ($candidate === 'student_courses') {
            $countStmt = $mysqli->prepare('SELECT COUNT(*) FROM student_courses WHERE course_id = ?');
        } else {
            $countStmt = $mysqli->prepare("SELECT COUNT(*) FROM `{$candidate}` WHERE course_id = ? AND status = 'enrolled'");
        }

        if (!$countStmt) {
            continue;
        }

        $countStmt->bind_param('i', $selected_course_id);
        $countStmt->execute();
        $countStmt->bind_result($rowCount);
        $countStmt->fetch();
        $countStmt->close();

        if ($rowCount > 0) {
            $enrollment_table = $candidate;
            break;
        }
    }

    if ($enrollment_table === '') {
        $enrollment_table = 'student_course_registration';
    }

    if ($enrollment_table === 'student_courses') {
        $studentsQuery = "
            SELECT u.id, u.username, u.first_name, u.surname, u.email,
                   COALESCE(sc.grade, '') AS grade,
                   COALESCE(sc.remarks, '') AS remarks
            FROM users u
            INNER JOIN student_courses sc ON u.id = sc.student_id
            WHERE sc.course_id = ? AND u.role = 'student'
            ORDER BY u.surname, u.first_name
        ";
    } else {
        $studentsQuery = "
            SELECT u.id, u.username, u.first_name, u.surname, u.email,
                   COALESCE(r.grade, '') AS grade,
                   COALESCE(r.remarks, '') AS remarks,
                   COALESCE(r.grade_points, 0) AS grade_points
            FROM users u
            INNER JOIN `{$enrollment_table}` r ON u.id = r.student_id
            WHERE r.course_id = ? AND r.status = 'enrolled' AND u.role = 'student'
            ORDER BY u.surname, u.first_name
        ";
    }

    $studentsStmt = $mysqli->prepare($studentsQuery);
    if ($studentsStmt) {
        $studentsStmt->bind_param('i', $selected_course_id);
        $studentsStmt->execute();
        $students = $studentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $studentsStmt->close();
    }
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Grades | Staff Portal</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Segoe UI, Tahoma, sans-serif; background: #f3f6fb; color: #1f2937; }
        .main { min-height: 100vh; padding: 24px; }
        .top { background: #fff; padding: 18px 20px; border-radius: 14px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .avatar { width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center; background: #6366f1; color: #fff; font-weight: 700; }
        .card { background: #fff; border-radius: 14px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(15,23,42,.06); }
        .message { padding: 14px 16px; border-radius: 10px; margin-bottom: 16px; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
        .warning { background: #fef3c7; color: #92400e; }
        label { display: block; margin-bottom: 8px; font-weight: 600; }
        select, input { width: 100%; padding: 10px 12px; border: 1px solid #dbe3ef; border-radius: 10px; }
        .course-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
        .student-card { border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px; background: #fff; }
        .student-name { font-weight: 700; margin-bottom: 4px; }
        .student-email { color: #64748b; font-size: 14px; margin-bottom: 12px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .badge-graded { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .row { margin-bottom: 12px; }
        .actions { display: flex; gap: 10px; align-items: center; justify-content: space-between; }
        .btn { display: inline-block; padding: 10px 16px; border: 0; border-radius: 10px; cursor: pointer; text-decoration: none; font-weight: 600; }
        .btn-primary { background: #6366f1; color: #fff; }
        .btn-success { background: #10b981; color: #fff; }
    </style>
</head>
<body>
    <main class="main">
        <div class="top">
            <div>
                <h1 style="margin:0;">Manage Grades</h1>
                <div style="color:#64748b;">Select a course, then enter grades for the students enrolled in it.</div>
            </div>
            <div class="avatar"><?php echo e($initials); ?></div>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo e($messageType); ?>"><?php echo e($message); ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="GET" action="manage_grades.php">
                <label for="course_id">Choose Course to Grade</label>
                <select name="course_id" id="course_id" onchange="this.form.submit()">
                    <option value="">-- Select a course --</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo (int) $course['id']; ?>" <?php echo $selected_course_id === (int) $course['id'] ? 'selected' : ''; ?>>
                            <?php echo e($course['course_code']); ?> - <?php echo e($course['course_name']); ?> (<?php echo (int) $course['student_count']; ?> students)
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($selected_course_id > 0 && $course_info): ?>
            <div class="card">
                <h2 style="margin-top:0;">Enter Grades for <?php echo e($course_info['course_code']); ?> - <?php echo e($course_info['course_name']); ?></h2>
                <p style="color:#64748b; margin-top:-6px; margin-bottom:16px;">
                    <?php echo count($students); ?> students enrolled
                </p>

                <?php if (!empty($students)): ?>
                    <div class="course-grid">
                        <?php foreach ($students as $student): ?>
                            <div class="student-card">
                                <div class="student-name"><?php echo e($student['first_name'] . ' ' . $student['surname']); ?></div>
                                <div class="student-email"><?php echo e($student['email']); ?></div>
                                <div class="row">
                                    <?php if (!empty($student['grade'])): ?>
                                        <span class="badge badge-graded"><?php echo e($student['grade']); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-pending">Not graded</span>
                                    <?php endif; ?>
                                </div>
                                <form method="POST" action="manage_grades.php?course_id=<?php echo (int) $selected_course_id; ?>">
                                    <input type="hidden" name="save_grade" value="1">
                                    <input type="hidden" name="course_id" value="<?php echo (int) $selected_course_id; ?>">
                                    <input type="hidden" name="student_id" value="<?php echo (int) $student['id']; ?>">
                                    <input type="hidden" name="enrollment_table" value="<?php echo e($enrollment_table); ?>">
                                    <div class="row">
                                        <label>New Grade</label>
                                        <select name="grade" required>
                                            <option value="">Select Grade</option>
                                            <option value="A" <?php echo ($student['grade'] ?? '') === 'A' ? 'selected' : ''; ?>>A</option>
                                            <option value="B+" <?php echo ($student['grade'] ?? '') === 'B+' ? 'selected' : ''; ?>>B+</option>
                                            <option value="B" <?php echo ($student['grade'] ?? '') === 'B' ? 'selected' : ''; ?>>B</option>
                                            <option value="C+" <?php echo ($student['grade'] ?? '') === 'C+' ? 'selected' : ''; ?>>C+</option>
                                            <option value="C" <?php echo ($student['grade'] ?? '') === 'C' ? 'selected' : ''; ?>>C</option>
                                            <option value="D" <?php echo ($student['grade'] ?? '') === 'D' ? 'selected' : ''; ?>>D</option>
                                            <option value="F" <?php echo ($student['grade'] ?? '') === 'F' ? 'selected' : ''; ?>>F</option>
                                        </select>
                                    </div>
                                    <div class="row">
                                        <label>Remarks</label>
                                        <input type="text" name="remarks" value="<?php echo e($student['remarks'] ?? ''); ?>" placeholder="Remarks">
                                    </div>
                                    <div class="actions">
                                        <button class="btn btn-success" type="submit">Save Grade</button>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="message warning">No students enrolled in this course yet.</div>
                <?php endif; ?>
            </div>
        <?php elseif ($selected_course_id > 0): ?>
            <div class="card">
                <div class="message warning">No students found for the selected course.</div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
