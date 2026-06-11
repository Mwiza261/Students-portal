<?php
session_start();
require_once __DIR__ . '/db.php';

// Check if user is logged in as staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: StaffLogin.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Staff';

$mysqli = db_connect();
$result = $mysqli->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = [];
if ($result) {
    $messages = $result->fetch_all(MYSQLI_ASSOC);
}
$mysqli->close();

if (isset($_GET['mark_read'])) {
    $id = intval($_GET['mark_read']);
    $mysqli = db_connect();
    $mysqli->query("UPDATE contact_messages SET status = 'read' WHERE id = $id");
    $mysqli->close();
    header('Location: admin_messages.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $mysqli = db_connect();
    $mysqli->query("DELETE FROM contact_messages WHERE id = $id");
    $mysqli->close();
    header('Location: admin_messages.php');
    exit;
}

$unread_count = count(array_filter($messages, function($m) { return $m['status'] == 'unread'; }));
$current_page = basename($_SERVER['PHP_SELF']);

function isActive($page, $current) {
    return $page === $current ? 'active' : '';
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
    <title>Contact Messages | Chigoneka School</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100%;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
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

        .badge {
            background: #ef4444;
            color: white;
            border-radius: 20px;
            padding: 0.15rem 0.5rem;
            font-size: 0.7rem;
            font-weight: bold;
            margin-left: auto;
        }

        .main-content {
            margin-left: 280px;
            padding: 1.5rem;
        }

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

        .container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .stats {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .stats span {
            font-size: 24px;
            font-weight: bold;
            color: #1e6f5c;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #1e6f5c;
            color: white;
        }

        tr:hover {
            background: #f5f5f5;
        }

        .unread {
            background: #fff3cd;
            font-weight: bold;
        }

        .status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
        }

        .status-unread {
            background: #ffc107;
        }

        .status-read {
            background: #28a745;
            color: white;
        }

        .btn {
            padding: 5px 10px;
            margin: 0 2px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 12px;
            display: inline-block;
        }

        .btn-read {
            background: #007bff;
            color: white;
        }

        .btn-read:hover {
            background: #0056b3;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .message-preview {
            max-width: 300px;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
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
                <a href="admin_messages.php" class="active">💬 Messages
                    <?php if ($unread_count > 0): ?>
                        <span class="badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
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
                <a href="teacher_timetable_create.php">➕ Create Timetable</a>
                <a href="teacher_timetable_view.php">👁️ View Timetable</a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">ACCOUNT</div>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1>Contact Messages</h1>
                <p>View and manage messages from your website contact form</p>
            </div>
            <div class="user-avatar">
                <?php echo strtoupper(substr($full_name, 0, 1)); ?>
            </div>
        </div>

        <div class="container">
            <div class="stats">
                <span><?= count($messages) ?></span> total messages | 
                <span><?= $unread_count ?></span> unread
            </div>
            
            <?php if (count($messages) > 0): ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                        <tr class="<?= $msg['status'] == 'unread' ? 'unread' : '' ?>">
                            <td><?= $msg['id'] ?></td>
                            <td><?= e($msg['name']) ?></td>
                            <td><?= e($msg['email']) ?></td>
                            <td><?= e($msg['phone'] ?: 'N/A') ?></td>
                            <td class="message-preview"><?= e(substr($msg['message'], 0, 100)) ?>...</td>
                            <td><?= date('Y-m-d H:i', strtotime($msg['created_at'])) ?></td>
                            <td><span class="status status-<?= $msg['status'] ?>"><?= $msg['status'] ?></span></td>
                            <td>
                                <?php if ($msg['status'] == 'unread'): ?>
                                    <a href="?mark_read=<?= $msg['id'] ?>" class="btn btn-read">Mark Read</a>
                                <?php endif; ?>
                                <a href="?delete=<?= $msg['id'] ?>" class="btn btn-delete" onclick="return confirm('Delete this message?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p style="text-align: center; padding: 40px; color: #999;">No messages yet. Check back later.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>