<?php
session_start();
require_once __DIR__ . '/db.php';

// Check if user is logged in
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: Login.php');
    exit;
}

$user = get_user_by_id((int) $_SESSION['user_id']);
if (!$user || $user['role'] !== 'student') {
    session_unset();
    session_destroy();
    header('Location: Login.php');
    exit;
}

$student_id = $_SESSION['user_id'];
$mysqli = db_connect();

// Check which tables exist
$has_course_registrations = $mysqli->query("SHOW TABLES LIKE 'course_registrations'")->num_rows > 0;
$has_student_courses = $mysqli->query("SHOW TABLES LIKE 'student_courses'")->num_rows > 0;

// Get student's results from appropriate table
$results = [];

if ($has_course_registrations) {
    $query = "
        SELECT 
            c.id as course_id,
            c.course_code,
            c.course_name,
            c.credits,
            cr.grade,
            cr.remarks,
            cr.graded_at
        FROM course_registrations cr
        INNER JOIN courses c ON cr.course_id = c.id
        WHERE cr.student_id = ? AND cr.status = 'enrolled'
        ORDER BY c.course_code
    ";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} elseif ($has_student_courses) {
    $query = "
        SELECT 
            c.id as course_id,
            c.course_code,
            c.course_name,
            c.credits,
            sc.grade,
            sc.remarks
        FROM student_courses sc
        INNER JOIN courses c ON sc.course_id = c.id
        WHERE sc.student_id = ?
        ORDER BY c.course_code
    ";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Calculate GPA
$total_points = 0;
$total_credits = 0;
foreach ($results as $result) {
    if (!empty($result['grade'])) {
        $grade_points = 0;
        switch ($result['grade']) {
            case 'A': $grade_points = 4.00; break;
            case 'B+': $grade_points = 3.50; break;
            case 'B': $grade_points = 3.00; break;
            case 'C+': $grade_points = 2.50; break;
            case 'C': $grade_points = 2.00; break;
            case 'D': $grade_points = 1.00; break;
            case 'F': $grade_points = 0.00; break;
        }
        $total_points += $grade_points * ($result['credits'] ?? 3);
        $total_credits += ($result['credits'] ?? 3);
    }
}
$gpa = $total_credits > 0 ? round($total_points / $total_credits, 2) : 0;

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results - Student Portal</title>
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
        .gpa-card {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        .gpa-value {
            font-size: 48px;
            font-weight: bold;
            margin: 10px 0;
        }
        .results-table {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
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
        .grade-A, .grade-Bplus, .grade-B { color: #28a745; font-weight: bold; }
        .grade-Cplus, .grade-C, .grade-D { color: #ffc107; font-weight: bold; }
        .grade-F { color: #dc3545; font-weight: bold; }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-excellent { background: #28a745; color: white; }
        .badge-good { background: #17a2b8; color: white; }
        .badge-average { background: #ffc107; color: #333; }
        .badge-fail { background: #dc3545; color: white; }
        .badge-pending { background: #6c757d; color: white; }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 12px;
        }
        @media (max-width: 768px) {
            .sidebar { width: 100%; position: relative; height: auto; }
            .main-content { margin-left: 0; }
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
                <li><a href="register_courses.php">➕ Register Courses</a></li>
                <!-- Exam Results link removed as requested -->
                <li><a href="assessments.php">📋 Assessments</a></li>
                <li><a href="timetable.php">📅 Timetable</a></li>
                <li><a href="payments.php">💰 Payments</a></li>
                <li><a href="support.php">💬 Support</a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1>📝 My Exam Results</h1>
            <div>
                <span style="margin-right: 15px;">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <?php if (!empty($results)): ?>
            <div class="gpa-card">
                <h3>Your Cumulative GPA</h3>
                <div class="gpa-value"><?php echo number_format($gpa, 2); ?></div>
                <p>Total Credits: <?php echo $total_credits; ?> | Courses Graded: <?php echo count(array_filter($results, function($r) { return !empty($r['grade']); })); ?></p>
            </div>

            <div class="results-table">
                <h3 style="margin-bottom: 20px;">Course Results</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Credits</th>
                            <th>Grade</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($result['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($result['course_name']); ?></td>
                                <td><?php echo $result['credits'] ?? 3; ?></td>
                                <td class="grade-<?php echo str_replace('+', 'plus', $result['grade'] ?? ''); ?>">
                                    <?php echo !empty($result['grade']) ? htmlspecialchars($result['grade']) : '<span class="badge badge-pending">Not graded</span>'; ?>
                                </td>
                                <td><?php echo htmlspecialchars($result['remarks'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>📭 No results available yet. Once your grades are published, they will appear here.</p>
                <p style="margin-top: 10px; font-size: 14px; color: #666;">Please check back after your instructors have uploaded grades.</p>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>