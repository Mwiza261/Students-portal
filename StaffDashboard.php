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

// Initialize variables with default values
$total_students = 0;
$total_courses = 0;
$total_registrations = 0;
$pending_payments = 0;
$recent_registrations = [];
$popular_courses = [];

$mysqli = null;
try {
    $mysqli = db_connect();
    
    // Check if connection is successful
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
    // Continue with default values
} finally {
    if ($mysqli) {
        $mysqli->close();
    }
}

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | Chigoneka School</title>
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
            width: 260px;
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

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s;
            gap: 0.8rem;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(99, 102, 241, 0.2);
            color: white;
            border-left: 3px solid #6366f1;
        }

        .sidebar-nav a i {
            width: 24px;
            font-style: normal;
            font-size: 1.2rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #1e293b;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        /* Section Cards */
        .section-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .section-header h2 {
            font-size: 1.2rem;
            color: #1e293b;
        }

        .section-header a {
            color: #6366f1;
            text-decoration: none;
            font-size: 0.85rem;
        }

        /* Tables */
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
            padding: 0.75rem;
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.85rem;
        }

        td {
            padding: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.9rem;
        }

        tr:hover {
            background: #f8fafc;
        }

        .status-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-registered {
            background: #d4edda;
            color: #155724;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            color: #333;
        }

        .action-btn:hover {
            background: #6366f1;
            color: white;
            transform: translateY(-2px);
        }

        .action-icon {
            font-size: 1.5rem;
        }

        .action-label {
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Responsive */
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
            <a href="StaffDashboard.php" class="active">
                <i>📊</i> Dashboard
            </a>
            <a href="manage_courses.php">
                <i>📖</i> Manage Courses
            </a>
            <a href="manage_students.php">
                <i>👨‍🎓</i> Manage Students
            </a>
            <a href="staff_grades.php">
                <i>📝</i> Manage Grades
            </a>
            <a href="view_registrations.php">
                <i>📋</i> Course Registrations
            </a>
            <a href="logout.php">
                <i>🚪</i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                <h1>Welcome back, <?php echo e($full_name); ?>!</h1>
                <p>Here's what's happening with your school today.</p>
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
                <div class="stat-icon">📝</div>
                <div class="stat-value"><?php echo $total_registrations; ?></div>
                <div class="stat-label">Registrations (Current Semester)</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value"><?php echo $pending_payments; ?></div>
                <div class="stat-label">Pending Payments</div>
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
                <a href="view_registrations.php" class="action-btn">
                    <span class="action-icon">📋</span>
                    <span class="action-label">View Registrations</span>
                </a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 1.5rem;">
            <!-- Recent Registrations -->
            <div class="section-card">
                <div class="section-header">
                    <h2>Recent Course Registrations</h2>
                    <a href="view_registrations.php">View all →</a>
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
</body>
</html>