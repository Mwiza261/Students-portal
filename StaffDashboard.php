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

// Function to check active page
function isActive($page, $current) {
    return $page === $current ? 'active' : '';
}

// Get unread messages count
$unread_messages = 0;
$mysqli = null;
try {
    $mysqli = db_connect();
    if ($mysqli && !$mysqli->connect_error) {
        $msg_query = "SELECT COUNT(*) as count FROM contact_messages WHERE status = 'unread'";
        $msg_result = $mysqli->query($msg_query);
        if ($msg_result && $msg_result->num_rows > 0) {
            $unread_messages = $msg_result->fetch_assoc()['count'];
        }
    }
} catch (Exception $e) {
    error_log("Message count error: " . $e->getMessage());
}

// Initialize variables with default values
$total_students = 0;
$total_courses = 0;
$total_registrations = 0;
$pending_payments = 0;
$recent_registrations = [];
$popular_courses = [];
$timetable_count = 0;

$mysqli = null;
try {
    $mysqli = db_connect();
    
    if ($mysqli && !$mysqli->connect_error) {
        
        // Get total students
        $students_query = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
        $students_result = $mysqli->query($students_query);
        if ($students_result && $students_result->num_rows > 0) {
            $total_students = $students_result->fetch_assoc()['total'];
        }
        
        // Check if courses table exists
        $table_check = $mysqli->query("SHOW TABLES LIKE 'courses'");
        if ($table_check && $table_check->num_rows > 0) {
            // Get total courses
            $courses_query = "SELECT COUNT(*) as total FROM courses";
            $courses_result = $mysqli->query($courses_query);
            if ($courses_result && $courses_result->num_rows > 0) {
                $total_courses = $courses_result->fetch_assoc()['total'];
            }
            
            // Get total registrations for current semester
            $current_semester = date('Y') . '-Semester1';
            $registrations_query = "SELECT COUNT(*) as total FROM student_courses WHERE semester = ?";
            $registrations_stmt = $mysqli->prepare($registrations_query);
            if ($registrations_stmt) {
                $registrations_stmt->bind_param("s", $current_semester);
                $registrations_stmt->execute();
                $registrations_result = $registrations_stmt->get_result();
                if ($registrations_result && $registrations_result->num_rows > 0) {
                    $total_registrations = $registrations_result->fetch_assoc()['total'];
                }
                $registrations_stmt->close();
            }
            
            // Get recent registrations
            $recent_query = "
                SELECT sc.*, u.first_name, u.surname, u.email, c.course_code, c.course_name
                FROM student_courses sc
                JOIN users u ON sc.student_id = u.id
                JOIN courses c ON sc.course_id = c.id
                ORDER BY sc.registration_date DESC
                LIMIT 10
            ";
            $recent_result = $mysqli->query($recent_query);
            if ($recent_result && $recent_result->num_rows > 0) {
                while ($row = $recent_result->fetch_assoc()) {
                    $recent_registrations[] = $row;
                }
            }
            
            // Get popular courses
            $popular_query = "
                SELECT c.course_code, c.course_name, COUNT(sc.id) as student_count
                FROM courses c
                LEFT JOIN student_courses sc ON c.id = sc.course_id
                GROUP BY c.id
                ORDER BY student_count DESC
                LIMIT 5
            ";
            $popular_result = $mysqli->query($popular_query);
            if ($popular_result && $popular_result->num_rows > 0) {
                while ($row = $popular_result->fetch_assoc()) {
                    $popular_courses[] = $row;
                }
            }
        }
        
        // Check if timetables table exists
        $timetable_check = $mysqli->query("SHOW TABLES LIKE 'timetables'");
        if ($timetable_check && $timetable_check->num_rows > 0) {
            $timetable_query = "SELECT COUNT(*) as total FROM timetables";
            $timetable_result = $mysqli->query($timetable_query);
            if ($timetable_result && $timetable_result->num_rows > 0) {
                $timetable_count = $timetable_result->fetch_assoc()['total'];
            }
        }
        
        // Check if payments table exists for pending payments
        $payments_check = $mysqli->query("SHOW TABLES LIKE 'payments'");
        if ($payments_check && $payments_check->num_rows > 0) {
            $payments_query = "SELECT COUNT(*) as total FROM payments WHERE status = 'pending'";
            $payments_result = $mysqli->query($payments_query);
            if ($payments_result && $payments_result->num_rows > 0) {
                $pending_payments = $payments_result->fetch_assoc()['total'];
            }
        }
    }
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
} finally {
    if ($mysqli) {
        $mysqli->close();
    }
}

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function activeStaffGroup(string $currentPage, array $groups): string {
    foreach ($groups as $group) {
        foreach ($group['items'] as $item) {
            if (basename($item['href']) === $currentPage) {
                return $group['id'];
            }
        }
    }

    return 'main';
}

$sidebar_groups = [
    [
        'id' => 'main',
        'label' => 'Main',
        'icon' => '📊',
        'items' => [
            ['href' => 'StaffDashboard.php', 'label' => 'Dashboard'],
            ['href' => 'admin_messages.php', 'label' => 'Messages', 'count' => $unread_messages],
        ],
    ],
    [
        'id' => 'management',
        'label' => 'Course Management',
        'icon' => '📖',
        'items' => [
            ['href' => 'manage_courses.php', 'label' => 'Manage Courses'],
            ['href' => 'manage_students.php', 'label' => 'Manage Students'],
            ['href' => 'manage_grades.php', 'label' => 'Manage Grades'],
        ],
    ],
    [
        'id' => 'teacher',
        'label' => 'Teacher Portal',
        'icon' => '👨‍🏫',
        'items' => [
            ['href' => 'teacher_dashboard.php', 'label' => 'Teacher Dashboard'],
            ['href' => 'teacher_timetable.php', 'label' => 'Manage Timetable'],
            ['href' => 'teacher_timetable_create.php', 'label' => 'Create Timetable'],
            ['href' => 'teacher_timetable_view.php', 'label' => 'View Timetable'],
            ['href' => 'teacher_timetable_delete.php', 'label' => 'Delete Timetable'],
        ],
    ],
    [
        'id' => 'setup',
        'label' => 'Registration & Setup',
        'icon' => '⚙️',
        'items' => [
            ['href' => 'staffregister.php', 'label' => 'Staff Registration'],
            ['href' => 'manager_courser.php', 'label' => 'Course Manager'],
        ],
    ],
    [
        'id' => 'reports',
        'label' => 'Reports',
        'icon' => '📈',
        'items' => [
            ['href' => 'staff_grades.php', 'label' => 'Grade Reports'],
        ],
    ],
    [
        'id' => 'account',
        'label' => 'Account',
        'icon' => '🔐',
        'items' => [
            ['href' => 'change_password.php', 'label' => 'Change Password'],
            ['href' => 'logout.php', 'label' => 'Logout'],
        ],
    ],
];

$active_group = activeStaffGroup($current_page, $sidebar_groups);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | Chigoneka School</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f0f2f5;
            --surface: #ffffff;
            --surface-alt: #f8fafc;
            --border: #dbe3ef;
            --text: #10233d;
            --muted: #64748b;
            --navy: #1a3a6b;
            --navy-2: #12295a;
            --navy-3: #2250a0;
            --gold: #c9a84c;
            --gold-2: #e8c86a;
            --shadow: 0 20px 50px rgba(15, 35, 61, 0.08);
            --shadow-soft: 0 10px 30px rgba(15, 35, 61, 0.06);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { min-height: 100%; }
        body {
            font-family: 'DM Sans', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(201, 168, 76, 0.14), transparent 26%),
                linear-gradient(180deg, #f6f8fb 0%, var(--bg) 100%);
            color: var(--text);
        }

        a { color: inherit; text-decoration: none; }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100%;
            background: var(--surface);
            color: var(--text);
            box-shadow: 2px 0 18px rgba(26,58,107,0.08);
            transition: transform 0.3s;
            z-index: 100;
            overflow-y: auto;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 1.25rem 1rem 1.1rem;
            text-align: center;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-3) 100%);
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-header h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.45rem;
            margin-top: 0.6rem;
            letter-spacing: 0.2px;
            color: #fff;
        }

        .sidebar-header p {
            color: rgba(255,255,255,0.68) !important;
        }

        .sidebar-nav {
            padding: 0;
            flex: 1;
        }

        .nav-group {
            border-bottom: 1px solid #eef2f7;
        }

        .nav-group-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 0.7rem 1rem;
            background: none;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--navy);
            letter-spacing: 0.04em;
            text-transform: uppercase;
            transition: background 0.15s ease, color 0.15s ease;
            cursor: pointer;
            border: 0;
        }

        .nav-group-btn:hover {
            background: #f7fafc;
        }

        .nav-group-btn.open {
            background: #eef2ff;
            color: #3730a3;
        }

        .nav-group-left {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-group-left span:first-child {
            font-size: 0.95rem;
        }

        .nav-chevron {
            width: 12px;
            height: 12px;
            transition: transform 0.2s ease;
            color: #94a3b8;
            flex-shrink: 0;
        }

        .nav-chevron.rot {
            transform: rotate(90deg);
        }

        .nav-items {
            display: none;
            background: #fafbff;
        }

        .nav-items.open {
            display: block;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.56rem 1rem 0.56rem 1.85rem;
            font-size: 0.92rem;
            color: var(--muted);
            border-left: 3px solid transparent;
            transition: all 0.15s ease;
        }

        .nav-link::before {
            content: '▸';
            font-size: 0.58rem;
            color: #cbd5e1;
            margin-right: 1px;
        }

        .nav-link:hover {
            background: #eef2ff;
            color: #3730a3;
            border-left-color: #6366f1;
        }

        .nav-link.active {
            background: #e0e7ff;
            color: #3730a3;
            font-weight: 600;
            border-left-color: #4f46e5;
        }

        .nav-link i {
            font-style: normal;
            margin-left: auto;
            background: rgba(255,255,255,0.92);
            color: var(--navy);
            border-radius: 999px;
            padding: 0.08rem 0.45rem;
            font-size: 0.68rem;
            font-weight: 700;
        }

        .badge {
            background: #ef4444;
            color: white;
            border-radius: 999px;
            padding: 0.18rem 0.55rem;
            font-size: 0.7rem;
            font-weight: 700;
            margin-left: auto;
        }

        .main-content {
            margin-left: 280px;
            padding: 1.5rem;
        }

        .top-bar {
            background: rgba(255,255,255,0.84);
            backdrop-filter: blur(10px);
            padding: 1.15rem 1.5rem;
            border-radius: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(219,227,239,0.8);
        }

        .page-title h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: var(--navy);
            line-height: 1.1;
        }

        .page-title p {
            color: var(--muted);
            font-size: 0.92rem;
            margin-top: 0.25rem;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .user-avatar {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-2) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1rem;
            color: var(--navy);
            box-shadow: 0 10px 20px rgba(201,168,76,0.25);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(248,250,252,0.96));
            padding: 1.45rem 1.35rem;
            border-radius: 18px;
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(219,227,239,0.82);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow);
            border-color: rgba(99,102,241,0.22);
        }

        .stat-card::after {
            content: '';
            position: absolute;
            right: -28px;
            top: -28px;
            width: 108px;
            height: 108px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201,168,76,0.12), transparent 70%);
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(99,102,241,0.12), rgba(201,168,76,0.12));
            font-size: 1.25rem;
            margin-bottom: 0.6rem;
        }

        .stat-value {
            font-size: 2.1rem;
            font-weight: 800;
            color: var(--navy);
            line-height: 1;
        }

        .stat-label {
            color: var(--muted);
            font-size: 0.88rem;
            margin-top: 0.35rem;
        }

        .section-card {
            background: rgba(255,255,255,0.96);
            border-radius: 18px;
            padding: 1.45rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(219,227,239,0.82);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .section-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            color: var(--navy);
        }

        .section-header a {
            color: var(--navy-3);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 700;
        }

        .data-table {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 0.8rem;
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: 0.84rem;
            letter-spacing: 0.2px;
        }

        td {
            padding: 0.8rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.9rem;
        }

        tr:hover {
            background: #f8fafc;
        }

        .status-badge {
            display: inline-block;
            padding: 0.22rem 0.65rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-registered {
            background: #dcfce7;
            color: #166534;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            background: linear-gradient(180deg, #ffffff, #f8fafc);
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1rem;
            text-align: center;
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            color: #243447;
            min-height: 104px;
        }

        .action-btn:hover {
            background: linear-gradient(135deg, var(--navy-3), var(--navy));
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(31,74,130,0.18);
        }

        .action-icon {
            font-size: 1.5rem;
        }

        .action-label {
            font-size: 0.85rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div style="font-size: 2rem;">📚</div>
            <h3>Chigoneka School</h3>
            <p style="font-size: 0.78rem;">Staff Portal</p>
        </div>
        <div class="sidebar-nav">
            <?php foreach ($sidebar_groups as $group): ?>
            <div class="nav-group">
                <button class="nav-group-btn <?php echo $group['id'] === $active_group ? 'open' : ''; ?>" type="button" data-target="group-<?php echo e($group['id']); ?>">
                    <span class="nav-group-left">
                        <span><?php echo e($group['icon']); ?></span>
                        <span><?php echo e($group['label']); ?></span>
                    </span>
                    <svg class="nav-chevron <?php echo $group['id'] === $active_group ? 'rot' : ''; ?>" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
                <div class="nav-items <?php echo $group['id'] === $active_group ? 'open' : ''; ?>" id="group-<?php echo e($group['id']); ?>">
                    <?php foreach ($group['items'] as $item): ?>
                        <a href="<?php echo e($item['href']); ?>" class="nav-link <?php echo basename($item['href']) === $current_page ? 'active' : ''; ?>">
                            <?php echo e($item['label']); ?>
                            <?php if (isset($item['count'])): ?>
                                <i><?php echo (int) $item['count']; ?></i>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                <h1>Welcome back, <?php echo e($full_name); ?>!</h1>
                <p>Staff Dashboard - Chigoneka School Management System</p>
            </div>
            <div class="user-menu">
                <span><?php echo date('l, F j, Y'); ?></span>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👨‍🎓</div>
                <div class="stat-value"><?php echo $total_students; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-value"><?php echo $total_courses; ?></div>
                <div class="stat-label">Active Courses</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-value"><?php echo $timetable_count; ?></div>
                <div class="stat-label">Timetable Entries</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💬</div>
                <div class="stat-value"><?php echo $unread_messages; ?></div>
                <div class="stat-label">Unread Messages</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="section-card">
            <div class="section-header">
                <h2>Quick Actions</h2>
            </div>
            <div class="quick-actions">
                <a href="manage_courses.php" class="action-btn">
                    <span class="action-icon">➕</span>
                    <span class="action-label">Add New Course</span>
                </a>
                <a href="manage_students.php" class="action-btn">
                    <span class="action-icon">👨‍🎓</span>
                    <span class="action-label">Manage Students</span>
                </a>
                <a href="staff_grades.php" class="action-btn">
                    <span class="action-icon">📝</span>
                    <span class="action-label">Enter Grades</span>
                </a>
                <a href="teacher_timetable_create.php" class="action-btn">
                    <span class="action-icon">📅</span>
                    <span class="action-label">Create Timetable</span>
                </a>
                <a href="admin_messages.php" class="action-btn">
                    <span class="action-icon">💬</span>
                    <span class="action-label">View Messages</span>
                    <?php if ($unread_messages > 0): ?>
                        <span class="badge" style="background: #ef4444; margin-top: 5px;"><?php echo $unread_messages; ?> new</span>
                    <?php endif; ?>
                </a>
                <a href="teacher_dashboard.php" class="action-btn">
                    <span class="action-icon">👨‍🏫</span>
                    <span class="action-label">Teacher Dashboard</span>
                </a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 1.5rem;">
            <!-- Recent Registrations -->
            <div class="section-card">
                <div class="section-header">
                    <h2>Recent Course Registrations</h2>
                </div>
                <div class="data-table">
                    <?php if (count($recent_registrations) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_registrations as $reg): ?>
                                    <tr>
                                        <td><?php echo e($reg['first_name'] . ' ' . $reg['surname']); ?></td>
                                        <td><?php echo e($reg['course_code'] . ' - ' . $reg['course_name']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($reg['registration_date'])); ?></td>
                                        <td>
                                            <span class="status-badge status-registered">
                                                <?php echo ucfirst($reg['status'] ?? 'registered'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; padding: 2rem; color: #999;">No recent registrations found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Popular Courses -->
            <div class="section-card">
                <div class="section-header">
                    <h2>Most Popular Courses</h2>
                    <a href="manage_courses.php">Manage courses →</a>
                </div>
                <div class="data-table">
                    <?php if (count($popular_courses) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Course Code</th>
                                    <th>Course Name</th>
                                    <th>Students</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popular_courses as $course): ?>
                                    <tr>
                                        <td><strong><?php echo e($course['course_code']); ?></strong></td>
                                        <td><?php echo e($course['course_name']); ?></td>
                                        <td><?php echo $course['student_count']; ?> enrolled</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; padding: 2rem; color: #999;">No courses available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="section-card">
            <div class="section-header">
                <h2>System Information</h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div>
                    <strong>Current Semester:</strong><br>
                    <?php echo date('Y'); ?> - Semester 1
                </div>
                <div>
                    <strong>Academic Year:</strong><br>
                    <?php echo date('Y'); ?>
                </div>
                <div>
                    <strong>Last Login:</strong><br>
                    <?php 
                    if (isset($_SESSION['login_time'])) {
                        echo date('d M Y H:i', $_SESSION['login_time']);
                    } else {
                        echo 'First login today';
                    }
                    ?>
                </div>
                <div>
                    <strong>System Version:</strong><br>
                    2.0.0
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.nav-group-btn').forEach(function(btn){
            btn.addEventListener('click', function(){
                var targetId = btn.getAttribute('data-target');
                var panel = document.getElementById(targetId);
                var chevron = btn.querySelector('.nav-chevron');
                var isOpen = panel && panel.classList.contains('open');

                document.querySelectorAll('.nav-items').forEach(function(panelItem){
                    panelItem.classList.remove('open');
                });
                document.querySelectorAll('.nav-group-btn').forEach(function(groupButton){
                    groupButton.classList.remove('open');
                    var groupChevron = groupButton.querySelector('.nav-chevron');
                    if (groupChevron) {
                        groupChevron.classList.remove('rot');
                    }
                });

                if (!isOpen && panel) {
                    panel.classList.add('open');
                    btn.classList.add('open');
                    if (chevron) {
                        chevron.classList.add('rot');
                    }
                }
            });
        });
    </script>
</body>
</html>