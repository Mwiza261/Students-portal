<?php
session_start();
require_once __DIR__ . '/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: StaffLogin.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'] ?? $username;
$current_page = basename($_SERVER['PHP_SELF']);

// Database connection
$mysqli = db_connect();
$message = '';
$message_type = '';

// Handle form submission for adding timetable entry
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_level = $_POST['class_level'] ?? '';
    $day_of_week = $_POST['day_of_week'] ?? '';
    $period = (int)($_POST['period'] ?? 0);
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $teacher_name = $_POST['teacher_name'] ?? '';
    $room = $_POST['room'] ?? '';
    $term = $_POST['term'] ?? 'Term 1';
    $academic_year = date('Y');
    
    // Validate inputs
    $errors = [];
    if (empty($class_level)) $errors[] = "Class level is required";
    if (empty($day_of_week)) $errors[] = "Day of week is required";
    if ($period < 1 || $period > 8) $errors[] = "Period must be between 1 and 8";
    if (empty($start_time)) $errors[] = "Start time is required";
    if (empty($end_time)) $errors[] = "End time is required";
    if (empty($subject)) $errors[] = "Subject is required";
    if (empty($teacher_name)) $errors[] = "Teacher name is required";
    if (empty($room)) $errors[] = "Room is required";
    
    if (empty($errors)) {
        // Create timetables table if not exists
        $mysqli->query("CREATE TABLE IF NOT EXISTS `timetables` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `class_level` ENUM('Form 1','Form 2','Form 3','Form 4') NOT NULL,
            `day_of_week` ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
            `period` INT(11) NOT NULL,
            `start_time` TIME NOT NULL,
            `end_time` TIME NOT NULL,
            `subject` VARCHAR(100) NOT NULL,
            `teacher_name` VARCHAR(100) NOT NULL,
            `room` VARCHAR(50) NOT NULL,
            `academic_year` YEAR NOT NULL,
            `term` VARCHAR(20) DEFAULT 'Term 1',
            `created_by` INT(11) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `class_level` (`class_level`),
            KEY `academic_year` (`academic_year`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Check if entry already exists for this class, day, period
        $check_stmt = $mysqli->prepare("SELECT id FROM timetables WHERE class_level = ? AND day_of_week = ? AND period = ? AND academic_year = ?");
        $check_stmt->bind_param('ssii', $class_level, $day_of_week, $period, $academic_year);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $message = "A timetable entry already exists for {$class_level} on {$day_of_week} Period {$period}. Please edit the existing entry instead.";
            $message_type = "error";
        } else {
            // Insert new timetable entry
            $stmt = $mysqli->prepare("INSERT INTO timetables (class_level, day_of_week, period, start_time, end_time, subject, teacher_name, room, academic_year, term, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssissssssis', $class_level, $day_of_week, $period, $start_time, $end_time, $subject, $teacher_name, $room, $academic_year, $term, $user_id);
            
            if ($stmt->execute()) {
                $message = "Timetable entry added successfully!";
                $message_type = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
        $check_stmt->close();
    } else {
        $message = implode(", ", $errors);
        $message_type = "error";
    }
}

// Get existing timetable entries for display
$timetables = [];
$result = $mysqli->query("SELECT * FROM timetables WHERE academic_year = " . date('Y') . " ORDER BY class_level, FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), period");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $timetables[] = $row;
    }
}

// Get unique classes for filter
$classes = ['Form 1', 'Form 2', 'Form 3', 'Form 4'];

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function isActive($page, $current) {
    return $page === $current ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Timetable | Staff Portal</title>
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

        /* Sidebar Navigation */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100%;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            z-index: 100;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h3 {
            font-size: 1.3rem;
            margin-top: 0.5rem;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-section {
            margin-bottom: 1rem;
        }

        .nav-section-title {
            padding: 0.5rem 1.5rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            font-weight: 600;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 0.7rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s;
            gap: 0.8rem;
            font-size: 0.9rem;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(99, 102, 241, 0.2);
            color: white;
            border-left: 3px solid #6366f1;
        }

        .sidebar-nav a i {
            width: 28px;
            font-style: normal;
            font-size: 1.1rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 1.5rem;
        }

        /* Top Bar */
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

        .page-title h1 {
            font-size: 1.5rem;
            color: #1e293b;
        }

        .page-title p {
            color: #64748b;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
        }

        /* Form Styles */
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .form-card h2 {
            margin-bottom: 1.5rem;
            color: #1e293b;
            font-size: 1.2rem;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            color: #475569;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #6366f1;
            color: white;
        }

        .btn-primary:hover {
            background: #4f46e5;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Table Styles */
        .data-table {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
        }

        tr:hover {
            background: #f8fafc;
        }

        .delete-btn {
            background: #ef4444;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.8rem;
        }

        .delete-btn:hover {
            background: #dc2626;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div style="font-size: 2rem;">📚</div>
            <h3>Chigoneka School</h3>
            <p style="font-size: 0.75rem; color: #94a3b8;">Staff Portal</p>
        </div>
        <div class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">MAIN</div>
                <a href="StaffDashboard.php">📊 Dashboard</a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">COURSE MANAGEMENT</div>
                <a href="manage_courses.php">📖 Manage Courses</a>
                <a href="manage_students.php">👨‍🎓 Manage Students</a>
                <a href="view_registrations.php">📋 Course Registrations</a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">TIMETABLE</div>
                <a href="teacher_timetable.php">📅 Manage Timetable</a>
                <a href="teacher_timetable_create.php" class="active">➕ Create Timetable</a>
                <a href="teacher_timetable_view.php">👁️ View Timetable</a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">GRADES</div>
                <a href="staff_grades.php">📝 Manage Grades</a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">ACCOUNT</div>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1>Create Timetable</h1>
                <p>Add new timetable entries for classes</p>
            </div>
            <div class="user-menu">
                <span><?php echo date('l, F j, Y'); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($full_name, 0, 1)); ?></div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo e($message); ?></div>
        <?php endif; ?>

        <!-- Add Timetable Entry Form -->
        <div class="form-card">
            <h2>➕ Add New Timetable Entry</h2>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Class Level *</label>
                        <select name="class_level" required>
                            <option value="">Select Class</option>
                            <option value="Form 1">Form 1</option>
                            <option value="Form 2">Form 2</option>
                            <option value="Form 3">Form 3</option>
                            <option value="Form 4">Form 4</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Day of Week *</label>
                        <select name="day_of_week" required>
                            <option value="">Select Day</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Period Number *</label>
                        <input type="number" name="period" min="1" max="8" required placeholder="1-8">
                    </div>
                    <div class="form-group">
                        <label>Start Time *</label>
                        <input type="time" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label>End Time *</label>
                        <input type="time" name="end_time" required>
                    </div>
                    <div class="form-group">
                        <label>Term *</label>
                        <select name="term" required>
                            <option value="Term 1">Term 1</option>
                            <option value="Term 2">Term 2</option>
                            <option value="Term 3">Term 3</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subject *</label>
                        <input type="text" name="subject" required placeholder="e.g., Mathematics">
                    </div>
                    <div class="form-group">
                        <label>Teacher Name *</label>
                        <input type="text" name="teacher_name" required placeholder="Full name">
                    </div>
                    <div class="form-group">
                        <label>Room *</label>
                        <input type="text" name="room" required placeholder="e.g., Room 101">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">➕ Add Timetable Entry</button>
            </form>
        </div>

        <!-- Current Timetable Entries -->
        <div class="form-card">
            <h2>📅 Current Timetable Entries (<?php echo date('Y'); ?>)</h2>
            <div class="data-table">
                <?php if (count($timetables) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Day</th>
                                <th>Period</th>
                                <th>Time</th>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Room</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($timetables as $tt): ?>
                                <tr>
                                    <td><?php echo e($tt['class_level']); ?></td>
                                    <td><?php echo e($tt['day_of_week']); ?></td>
                                    <td><?php echo $tt['period']; ?></td>
                                    <td><?php echo date('h:i A', strtotime($tt['start_time'])) . ' - ' . date('h:i A', strtotime($tt['end_time'])); ?></td>
                                    <td><?php echo e($tt['subject']); ?></td>
                                    <td><?php echo e($tt['teacher_name']); ?></td>
                                    <td><?php echo e($tt['room']); ?></td>
                                    <td>
                                        <a href="teacher_timetable_delete.php?id=<?php echo $tt['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this entry?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; padding: 2rem; color: #999;">No timetable entries yet. Use the form above to add entries.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>