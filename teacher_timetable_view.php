<?php
session_start();
require_once __DIR__ . '/db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: StaffLogin.php');
    exit;
}

$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];
$current_page = basename($_SERVER['PHP_SELF']);
$mysqli = db_connect();

$selected_class = $_GET['class'] ?? 'Form 1';
$selected_term = $_GET['term'] ?? 'Term 1';
$classes = ['Form 1', 'Form 2', 'Form 3', 'Form 4'];
$terms = ['Term 1', 'Term 2', 'Term 3'];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Get timetable for selected class
$timetable = [];
$stmt = $mysqli->prepare("SELECT * FROM timetables WHERE class_level = ? AND term = ? AND academic_year = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), period");
$currentYear = date('Y');
$stmt->bind_param('ssi', $selected_class, $selected_term, $currentYear);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $timetable[$row['day_of_week']][] = $row;
}
$stmt->close();
$mysqli->close();

function e($v) { 
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); 
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
    <title>View Timetable | Staff Portal</title>
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
            overflow-y: auto;
            z-index: 100;
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
        
        .nav-section-title { 
            padding: 0.5rem 1.5rem; 
            font-size: 0.7rem; 
            text-transform: uppercase; 
            color: #94a3b8; 
            font-weight: 600;
        }
        
        .sidebar-nav a {
            display: flex; 
            align-items: center; 
            padding: 0.7rem 1.5rem; 
            color: #cbd5e1;
            text-decoration: none; 
            gap: 0.8rem; 
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .sidebar-nav a:hover, 
        .sidebar-nav a.active { 
            background: rgba(99, 102, 241, 0.2); 
            color: white; 
            border-left: 3px solid #6366f1; 
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
        
        .user-avatar {
            width: 40px; 
            height: 40px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: bold;
        }
        
        /* Cards */
        .card { 
            background: white; 
            border-radius: 12px; 
            padding: 1.5rem; 
            margin-bottom: 1.5rem; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); 
        }
        
        .card h2 { 
            margin-bottom: 1rem; 
            color: #1e293b; 
            font-size: 1.2rem; 
            border-bottom: 2px solid #e2e8f0; 
            padding-bottom: 0.5rem; 
        }
        
        /* Filter Bar */
        .filter-bar { 
            display: flex; 
            gap: 1rem; 
            flex-wrap: wrap; 
            align-items: center; 
        }
        
        .filter-bar select, 
        .filter-bar button { 
            padding: 0.5rem 1rem; 
            border: 1px solid #e2e8f0; 
            border-radius: 8px; 
            font-size: 0.9rem;
        }
        
        .btn-primary { 
            background: #6366f1; 
            color: white; 
            border: none; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }
        
        .btn-primary:hover {
            background: #4f46e5;
        }
        
        .btn-success {
            background: #22c55e;
            color: white;
        }
        
        .btn-success:hover {
            background: #16a34a;
        }
        
        /* Timetable Table */
        .timetable-container { 
            overflow-x: auto; 
        }
        
        .timetable-table { 
            width: 100%; 
            border-collapse: collapse; 
            min-width: 800px; 
        }
        
        .timetable-table th, 
        .timetable-table td { 
            border: 1px solid #e2e8f0; 
            padding: 0.75rem; 
            vertical-align: top; 
        }
        
        .timetable-table th { 
            background: #1e293b; 
            color: white; 
            text-align: center; 
            font-weight: 600;
        }
        
        .period-cell { 
            background: #f8fafc; 
            font-weight: 600; 
            width: 80px; 
            text-align: center; 
        }
        
        .subject-cell { 
            min-width: 150px; 
        }
        
        .time-info { 
            font-size: 0.7rem; 
            color: #64748b; 
            margin-top: 0.25rem; 
        }
        
        .empty-cell { 
            text-align: center; 
            color: #94a3b8; 
            padding: 1rem; 
        }
        
        /* Responsive */
        @media (max-width: 768px) { 
            .main-content { 
                margin-left: 0; 
            } 
            .sidebar { 
                transform: translateX(-100%);
                position: fixed;
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
            <p style="font-size: 0.75rem;">Staff Portal</p>
        </div>
        <div class="sidebar-nav">
            <div class="nav-section-title">MAIN</div>
            <a href="StaffDashboard.php">📊 Dashboard</a>
            
            <div class="nav-section-title">TIMETABLE</div>
            <a href="teacher_timetable.php">📅 Manage Timetable</a>
            <a href="teacher_timetable_create.php">➕ Create Timetable</a>
            <a href="teacher_timetable_view.php" class="active">👁️ View Timetable</a>
            
            <div class="nav-section-title">ACCOUNT</div>
            <a href="logout.php">🚪 Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1>View Timetable</h1>
                <p>View timetable by class and term</p>
            </div>
            <div class="user-menu">
                <span><?php echo date('l, F j, Y'); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($full_name, 0, 1)); ?></div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card">
            <form method="GET" action="" class="filter-bar">
                <select name="class">
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class; ?>" <?php echo $selected_class == $class ? 'selected' : ''; ?>>
                            <?php echo $class; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="term">
                    <?php foreach ($terms as $term): ?>
                        <option value="<?php echo $term; ?>" <?php echo $selected_term == $term ? 'selected' : ''; ?>>
                            <?php echo $term; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-primary">View Timetable</button>
                <a href="teacher_timetable_create.php" class="btn-primary btn-success" style="background: #22c55e;">+ Add New Entry</a>
            </form>
        </div>

        <!-- Timetable Display Card -->
        <div class="card">
            <h2>📅 <?php echo e($selected_class); ?> - <?php echo e($selected_term); ?> Timetable (<?php echo date('Y'); ?>)</h2>
            
            <?php if (empty($timetable)): ?>
                <div class="empty-cell" style="padding: 3rem;">
                    <p>⚠️ No timetable entries found for <?php echo e($selected_class); ?> - <?php echo e($selected_term); ?>.</p>
                    <p style="margin-top: 1rem;">
                        <a href="teacher_timetable_create.php" class="btn-primary" style="background: #22c55e;">Click here to add timetable entries</a>
                    </p>
                </div>
            <?php else: ?>
                <div class="timetable-container">
                    <table class="timetable-table">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Time</th>
                                <?php foreach ($days as $day): ?>
                                    <th><?php echo $day; ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $period_times = [
                                1 => '08:00 - 08:40',
                                2 => '08:45 - 09:25',
                                3 => '09:30 - 10:10',
                                4 => '10:30 - 11:10',
                                5 => '11:15 - 11:55',
                                6 => '14:00 - 14:40'
                            ];
                            
                            for ($period = 1; $period <= 6; $period++): 
                            ?>
                                <tr>
                                    <td class="period-cell">Period <?php echo $period; ?></td>
                                    <td class="period-cell"><?php echo $period_times[$period]; ?></td>
                                    
                                    <?php foreach ($days as $day): ?>
                                        <td class="subject-cell">
                                            <?php
                                            $found = false;
                                            if (isset($timetable[$day])) {
                                                foreach ($timetable[$day] as $entry) {
                                                    if ($entry['period'] == $period) {
                                                        echo "<strong>" . e($entry['subject']) . "</strong>";
                                                        echo "<div class='time-info'>👨‍🏫 " . e($entry['teacher_name']) . "</div>";
                                                        echo "<div class='time-info'>🏫 Room: " . e($entry['room']) . "</div>";
                                                        $found = true;
                                                        break;
                                                    }
                                                }
                                            }
                                            if (!$found) {
                                                echo "<span style='color: #94a3b8;'>— Free Period —</span>";
                                            }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Legend Card -->
        <div class="card">
            <h2>📖 Legend</h2>
            <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                <div><strong>👨‍🏫</strong> - Teacher Name</div>
                <div><strong>🏫</strong> - Room Number</div>
                <div><strong>— Free Period —</strong> - No class scheduled</div>
            </div>
        </div>
    </div>
</body>
</html>