<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/db.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['authenticated'], $_SESSION['user_id']) || $_SESSION['authenticated'] !== true) {
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

$mysqli = db_connect();
$student_id = (int) $user['id'];
$currentYear = date('Y');

// Get student's class level from student_class_selections table
$class_level = null;
$class_stmt = $mysqli->prepare("SELECT class_level FROM student_class_selections WHERE student_id = ? AND academic_year = ?");
if ($class_stmt) {
    $class_stmt->bind_param('ii', $student_id, $currentYear);
    $class_stmt->execute();
    $class_result = $class_stmt->get_result();
    if ($row = $class_result->fetch_assoc()) {
        $class_level = trim($row['class_level']); // Trim any extra spaces
    }
    $class_stmt->close();
}

// Get selected day and term from URL parameters
$selected_day = $_GET['day'] ?? date('l'); // Today by default
$selected_term_param = $_GET['term'] ?? 'Term 1';

// Map term names to integers (matching your database)
$term_map = [
    'Term 1' => 1,
    'Term 2' => 2,
    'Term 3' => 3
];
$term_value = $term_map[$selected_term_param] ?? 1;

// Get timetable for student's class - REMOVE term filter for now to test
$timetable = [];
if ($class_level) {
    // First try with term filter
    $stmt = $mysqli->prepare("
        SELECT * FROM timetables 
        WHERE class_level = ? AND academic_year = ? AND term = ?
        ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), period
    ");
    $stmt->bind_param('sii', $class_level, $currentYear, $term_value);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $timetable[] = $row;
    }
    $stmt->close();
    
    // DEBUG: If no results, try without term filter
    if (empty($timetable)) {
        $stmt2 = $mysqli->prepare("
            SELECT * FROM timetables 
            WHERE class_level = ? AND academic_year = ?
            ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), period
        ");
        $stmt2->bind_param('si', $class_level, $currentYear);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        while ($row2 = $result2->fetch_assoc()) {
            $timetable[] = $row2;
        }
        $stmt2->close();
    }
}

$mysqli->close();

$full_name = htmlspecialchars($user['first_name'] . ' ' . $user['surname']);
$initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['surname'], 0, 1));
$greeting = date('H') < 12 ? 'Good morning' : (date('H') < 17 ? 'Good afternoon' : 'Good evening');
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$terms = ['Term 1', 'Term 2', 'Term 3'];

// Get current day of week for highlighting
$today = date('l');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Timetable | Chigoneka School</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f0f2f5;
            color: #1a202c;
        }
        .app-container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            background: #1a1a2e;
            color: #fff;
            padding: 30px 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar h2 { font-size: 22px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #16213e; }
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
        .main-content { flex: 1; margin-left: 280px; padding: 20px 30px; }
        .header {
            background: linear-gradient(135deg, #1a3a6b, #2563eb);
            border-radius: 12px;
            padding: 20px 25px;
            margin-bottom: 25px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo { font-size: 20px; font-weight: bold; text-decoration: none; color: white; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .avatar {
            width: 40px; height: 40px; background: #c9a84c; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-weight: bold; color: #1a3a6b;
        }
        .logout-btn {
            background: #dc3545; color: white; padding: 8px 16px; border-radius: 6px;
            text-decoration: none; font-size: 14px; transition: background 0.2s;
        }
        .logout-btn:hover { background: #c82333; }
        .class-info {
            background: #f0fdf4; border-left: 4px solid #22c55e;
            padding: 12px 20px; border-radius: 8px; margin-bottom: 20px;
        }
        .filter-bar {
            background: white; border-radius: 12px; padding: 20px;
            margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap;
            align-items: center;
        }
        .filter-group { display: flex; align-items: center; gap: 10px; }
        .filter-group label { font-weight: 600; color: #4a5568; }
        select, .btn-secondary {
            padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
            font-size: 14px; background: white; cursor: pointer;
        }
        .day-buttons {
            display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px;
        }
        .day-btn {
            padding: 10px 20px; background: white; border: 1px solid #e2e8f0;
            border-radius: 8px; cursor: pointer; text-decoration: none;
            color: #4a5568; font-weight: 500; transition: all 0.2s;
        }
        .day-btn.active { background: #1a3a6b; color: white; border-color: #1a3a6b; }
        .day-btn.today { border: 2px solid #c9a84c; }
        .day-btn:hover:not(.active) { background: #f7fafc; border-color: #cbd5e0; }
        .timetable-card {
            background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .timetable-header {
            background: #1a3a6b; color: white; padding: 15px 20px; font-size: 18px; font-weight: 600;
        }
        .timetable-table { width: 100%; border-collapse: collapse; }
        .timetable-table th, .timetable-table td {
            padding: 15px; text-align: left; border-bottom: 1px solid #e2e8f0;
        }
        .timetable-table th { background: #f7fafc; font-weight: 600; color: #1a3a6b; }
        .subject-badge {
            background: #e0e7ff; color: #3730a3; padding: 4px 8px;
            border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block;
        }
        .empty-message { text-align: center; padding: 40px; color: #718096; }
        .back-btn {
            display: inline-block; margin-top: 20px; padding: 10px 20px;
            background: #1a3a6b; color: white; text-decoration: none; border-radius: 8px;
        }
        .back-btn:hover { background: #2563eb; }
        @media (max-width: 768px) {
            .sidebar { width: 100%; position: relative; height: auto; }
            .main-content { margin-left: 0; }
            .timetable-table th, .timetable-table td { padding: 10px; font-size: 12px; }
        }
        .debug-info { background: #fef3c7; padding: 10px; margin-bottom: 15px; border-radius: 8px; font-size: 12px; display: none; }
    </style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <h2>🎓 Student Portal</h2>
        <nav>
            <ul>
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="my_courses.php">📚 My Courses</a></li>
                <li><a href="register_courses.php">➕ Register Courses</a></li>
                <li><a href="student_assessments.php">📋 Assessments</a></li>
                <li><a href="student_timetable.php" class="active">📅 My Timetable</a></li>
                <li><a href="student_results.php">📝 Exam Results</a></li>
                <li><a href="view_student_grades.php">⭐ View Grades</a></li>
                <li><a href="support.php">💬 Support</a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <div class="header">
            <div>
                <h2>📅 My Timetable</h2>
                <p style="font-size: 14px; opacity: 0.9;"><?php echo htmlspecialchars($class_level ?: 'Class not selected'); ?> • <?php echo $currentYear; ?> Academic Year</p>
            </div>
            <div class="user-info">
                <span><?php echo $greeting; ?>, <?php echo htmlspecialchars($user['first_name']); ?></span>
                <div class="avatar"><?php echo $initials; ?></div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <?php if (!$class_level): ?>
            <div class="timetable-card">
                <div class="empty-message">
                    <p>⚠️ You haven't selected your class yet.</p>
                    <a href="student_select_class.php" class="back-btn">Select Class First →</a>
                </div>
            </div>
        <?php else: ?>
            <div class="class-info">
                <strong>📖 Current Class:</strong> <?php echo htmlspecialchars($class_level); ?> | 
                <strong>📅 Academic Year:</strong> <?php echo $currentYear; ?> |
                <strong>📌 Term:</strong> <?php echo htmlspecialchars($selected_term_param); ?>
            </div>

            <div class="filter-bar">
                <div class="filter-group">
                    <label>Select Term:</label>
                    <select id="termSelect">
                        <?php foreach ($terms as $term): ?>
                            <option value="<?php echo $term; ?>" <?php echo $selected_term_param === $term ? 'selected' : ''; ?>><?php echo $term; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="day-buttons">
                <?php foreach ($days as $day): ?>
                    <a href="?day=<?php echo urlencode($day); ?>&term=<?php echo urlencode($selected_term_param); ?>" 
                       class="day-btn <?php echo $selected_day === $day ? 'active' : ''; ?> <?php echo $today === $day && $selected_day !== $day ? 'today' : ''; ?>">
                        <?php echo $day; ?>
                        <?php if ($today === $day): ?>
                            <span style="font-size: 10px;">(Today)</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="timetable-card">
                <div class="timetable-header">
                    📖 Schedule for <?php echo htmlspecialchars($selected_day); ?> (<?php echo htmlspecialchars($class_level); ?>)
                </div>
                <table class="timetable-table">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Time</th>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Room</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Filter entries for the selected day
                        $day_entries = array_filter($timetable, function($entry) use ($selected_day) {
                            return trim($entry['day_of_week']) === trim($selected_day);
                        });
                        
                        // Sort by period number
                        usort($day_entries, function($a, $b) {
                            return ($a['period'] ?? 0) - ($b['period'] ?? 0);
                        });
                        
                        if (empty($day_entries)):
                        ?>
                            <tr>
                                <td colspan="5" class="empty-message">
                                    📭 No classes scheduled for <?php echo htmlspecialchars($selected_day); ?>.
                                    <br><small>Please check another day or contact your teacher.</small>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($day_entries as $entry): ?>
                                <tr>
                                    <td><strong>Period <?php echo htmlspecialchars($entry['period']); ?></strong></td>
                                    <td>
                                        <?php 
                                        if (!empty($entry['start_time']) && !empty($entry['end_time']) && $entry['start_time'] !== '00:00:00' && $entry['end_time'] !== '00:00:00') {
                                            echo date('h:i A', strtotime($entry['start_time'])) . ' - ' . date('h:i A', strtotime($entry['end_time']));
                                        } else {
                                            echo 'Time TBA';
                                        }
                                        ?>
                                    </td>
                                    <td><span class="subject-badge"><?php echo htmlspecialchars($entry['subject']); ?></span></td>
                                    <td><?php echo htmlspecialchars($entry['teacher_name']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['room']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>

<script>
    document.getElementById('termSelect')?.addEventListener('change', function() {
        const currentDay = '<?php echo $selected_day; ?>';
        window.location.href = `?day=${currentDay}&term=${this.value}`;
    });
</script>

</body>
</html>