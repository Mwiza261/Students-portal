<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: StaffLogin.php');
    exit;
}

$mysqli = db_connect();

$registrations_query = "
    SELECT sc.*, u.first_name, u.surname, u.email, c.course_code, c.course_name, c.credits
    FROM student_courses sc
    JOIN users u ON sc.student_id = u.id
    JOIN courses c ON sc.course_id = c.id
    ORDER BY sc.registration_date DESC
";
$registrations = $mysqli->query($registrations_query);

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Registrations | Staff Portal</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100%;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
        }
        
        .sidebar-header { padding: 1.5rem; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-nav { padding: 1rem 0; }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            gap: 0.8rem;
        }
        .sidebar-nav a:hover, .sidebar-nav a.active {
            background: rgba(99, 102, 241, 0.2);
            color: white;
            border-left: 3px solid #6366f1;
        }
        
        .main-content { margin-left: 260px; padding: 1.5rem; }
        .top-bar {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        .section-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 0.75rem; background: #f8fafc; font-weight: 600; }
        td { padding: 0.75rem; border-bottom: 1px solid #e2e8f0; }
        .status-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        .status-registered { background: #d4edda; color: #155724; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div style="font-size: 2rem;">📚</div>
            <h3>Chigoneka School</h3>
        </div>
        <div class="sidebar-nav">
            <a href="StaffDashboard.php">📊 Dashboard</a>
            <a href="manage_courses.php">📖 Manage Courses</a>
            <a href="manage_students.php">👨‍🎓 Manage Students</a>
            <a href="view_registrations.php" class="active">📝 Course Registrations</a>
            <a href="logout.php">🚪 Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>Course Registrations</h1>
        </div>
        
        <div class="section-card">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Credits</th>
                        <th>Registration Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($reg = $registrations->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($reg['first_name'] . ' ' . $reg['surname']); ?></td>
                        <td><?php echo htmlspecialchars($reg['email']); ?></td>
                        <td><?php echo htmlspecialchars($reg['course_code'] . ' - ' . $reg['course_name']); ?></td>
                        <td><?php echo $reg['credits']; ?></td>
                        <td><?php echo date('d M Y H:i', strtotime($reg['registration_date'])); ?></td>
                        <td><span class="status-badge status-registered"><?php echo ucfirst($reg['status']); ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>