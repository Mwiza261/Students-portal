<?php
session_start();
require_once __DIR__ . '/db.php';

// Auth guard - only staff can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: StaffLogin.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Teacher';
$current_page = basename($_SERVER['PHP_SELF']);
$currentYear = (int) date('Y');

if (!function_exists('e')) {
    function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
}

function isActive(string $page, string $current): string {
    return basename($page) === $current ? 'active' : '';
}

$mysqli = db_connect();
$message = '';
$messageType = '';

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grades'])) {
    $course_id = intval($_POST['course_id']);
    $student_id = intval($_POST['student_id']);
    $grade = strtoupper(trim($_POST['grade']));
    $remarks = trim($_POST['remarks'] ?? '');
    
    // Calculate grade points based on grade
    $grade_points = 0;
    switch ($grade) {
        case 'A': $grade_points = 4.00; break;
        case 'B+': $grade_points = 3.50; break;
        case 'B': $grade_points = 3.00; break;
        case 'C+': $grade_points = 2.50; break;
        case 'C': $grade_points = 2.00; break;
        case 'D': $grade_points = 1.00; break;
        case 'F': $grade_points = 0.00; break;
        default: $grade_points = 0.00;
    }
    
    // Check if grade column exists
    $check_column = $mysqli->query("SHOW COLUMNS FROM course_registrations LIKE 'grade'");
    $has_grade_column = $check_column && $check_column->num_rows > 0;
    
    if ($has_grade_column) {
        $stmt = $mysqli->prepare("
            UPDATE course_registrations 
            SET grade = ?, grade_points = ?, graded_by = ?, graded_at = NOW(), remarks = ?
            WHERE course_id = ? AND student_id = ? AND status = 'enrolled'
        ");
        $stmt->bind_param("sdiisi", $grade, $grade_points, $_SESSION['user_id'], $remarks, $course_id, $student_id);
    } else {
        $stmt = $mysqli->prepare("
            UPDATE student_courses 
            SET grade = ?, remarks = ?
            WHERE course_id = ? AND student_id = ?
        ");
        $stmt->bind_param("ssii", $grade, $remarks, $course_id, $student_id);
    }
    
    if ($stmt && $stmt->execute()) {
        $message = "Grade submitted successfully!";
        $messageType = "success";
    } else {
        $message = "Failed to submit grade: " . ($stmt ? $stmt->error : "Unknown error");
        $messageType = "error";
    }
    if ($stmt) $stmt->close();
}

// Get assigned courses for this teacher
$courses = [];
$courseQuery = "
    SELECT DISTINCT c.id, c.course_code, c.course_name, c.credits
    FROM courses c
    JOIN teacher_course_assignments tca ON c.id = tca.course_id
    WHERE tca.teacher_id = ? AND tca.status = 'active'
    ORDER BY c.course_code
";
$stmt = $mysqli->prepare($courseQuery);
if ($stmt) {
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Get selected course students
$selected_course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$students = [];
$course_info = null;

if ($selected_course_id > 0) {
    // Get course info
    $course_stmt = $mysqli->prepare("SELECT course_code, course_name FROM courses WHERE id = ?");
    $course_stmt->bind_param("i", $selected_course_id);
    $course_stmt->execute();
    $course_info = $course_stmt->get_result()->fetch_assoc();
    $course_stmt->close();
    
    // Get students enrolled in this course
    $table_check = $mysqli->query("SHOW TABLES LIKE 'course_registrations'");
    $use_registrations_table = $table_check && $table_check->num_rows > 0;
    
    if ($use_registrations_table) {
        $query = "
            SELECT u.id, u.username, u.first_name, u.surname, u.email, 
                   cr.grade, cr.remarks, cr.graded_at
            FROM users u
            INNER JOIN course_registrations cr ON u.id = cr.student_id
            WHERE cr.course_id = ? AND cr.status = 'enrolled' AND u.role = 'student'
            ORDER BY u.surname, u.first_name
        ";
        $stmt = $mysqli->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $selected_course_id);
            $stmt->execute();
            $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    } else {
        $query = "
            SELECT u.id, u.username, u.first_name, u.surname, u.email, 
                   sc.grade, sc.remarks
            FROM users u
            INNER JOIN student_courses sc ON u.id = sc.student_id
            WHERE sc.course_id = ? AND u.role = 'student'
            ORDER BY u.surname, u.first_name
        ";
        $stmt = $mysqli->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $selected_course_id);
            $stmt->execute();
            $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
}

$mysqli->close();

// Navigation sections
$nav_sections = [
    [
        'title' => 'Main',
        'items' => [
            ['href' => 'teacher_dashboard.php', 'label' => 'Dashboard', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
        ],
    ],
    [
        'title' => 'Courses',
        'items' => [
            ['href' => 'assign_course_to_students.php', 'label' => 'Assign to Students', 'icon' => 'M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['href' => 'manage_course_students.php', 'label' => 'Manage Students', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
            ['href' => 'view_course_requests.php', 'label' => 'Course Requests', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ],
    ],
    [
        'title' => 'Grades',
        'items' => [
            ['href' => 'enter_grades.php', 'label' => 'Enter Grades', 'icon' => 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z'],
            ['href' => 'staff_grades.php', 'label' => 'Manage Grades', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['href' => 'view_student_grades.php', 'label' => 'View Student Grades', 'icon' => 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ],
    ],
    [
        'title' => 'Account',
        'items' => [
            ['href' => 'change_password.php', 'label' => 'Change Password', 'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
        ],
    ],
];

$initials = strtoupper(substr($teacher_name, 0, 1));
$greeting = date('H') < 12 ? 'Good morning' : (date('H') < 17 ? 'Good afternoon' : 'Good evening');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Grades | Staff Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:      #0f2342;
            --navy-mid:  #1a3a6b;
            --navy-lite: #2a4e8a;
            --gold:      #c9a84c;
            --gold-lite: #f5d882;
            --bg:        #eef2f7;
            --surface:   #fff;
            --border:    #dde3ed;
            --text:      #1a202c;
            --muted:     #64748b;
            --sidebar-w: 260px;
        }

        html, body { height: 100%; }
        body {
            font-family: 'DM Sans', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
        }

        a { color: inherit; text-decoration: none; }
        button { font: inherit; cursor: pointer; border: none; background: none; }

        /* Sidebar */
        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--sidebar-w);
            background: var(--navy);
            display: flex;
            flex-direction: column;
            z-index: 200;
            overflow: hidden;
            transition: transform .3s cubic-bezier(.4,0,.2,1);
        }

        .sb-brand {
            background: var(--navy-mid);
            padding: 1.1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: .75rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
            flex-shrink: 0;
        }

        .sb-logo {
            width: 36px; height: 36px; border-radius: 10px;
            background: var(--gold);
            display: grid; place-items: center;
            font-family: 'Playfair Display', serif;
            font-weight: 900; font-size: .78rem;
            color: var(--navy);
            flex-shrink: 0;
        }

        .sb-brand-text .name {
            font-family: 'Playfair Display', serif;
            font-size: .88rem; font-weight: 700;
            color: #fff; line-height: 1.1;
        }

        .sb-brand-text .role {
            font-size: .68rem; color: rgba(255,255,255,.45);
            margin-top: 1px;
        }

        .sb-profile {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,.07);
            display: flex; align-items: center; gap: .75rem;
            flex-shrink: 0;
        }

        .sb-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), #e8c56a);
            display: grid; place-items: center;
            font-weight: 700; font-size: .85rem;
            color: var(--navy);
            flex-shrink: 0;
            box-shadow: 0 0 0 2px rgba(201,168,76,.3);
        }

        .sb-profile-info .teacher-name {
            font-size: .8rem; font-weight: 600;
            color: #e2e8f0; line-height: 1.2;
        }

        .sb-profile-info .teacher-meta {
            font-size: .68rem; color: rgba(255,255,255,.38);
            margin-top: 1px;
        }

        .sb-nav {
            flex: 1;
            overflow-y: auto;
            padding: .75rem 0 1rem;
            scrollbar-width: thin;
        }

        .sb-nav::-webkit-scrollbar { width: 4px; }
        .sb-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 4px; }

        .sb-section { margin-bottom: .25rem; }

        .sb-section-title {
            padding: .6rem 1.25rem .3rem;
            font-size: .62rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: rgba(255,255,255,.28);
        }

        .sb-item {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .58rem 1.25rem;
            color: rgba(255,255,255,.55);
            font-size: .82rem;
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: all .15s;
        }

        .sb-item:hover {
            color: #fff;
            background: rgba(255,255,255,.06);
            border-left-color: rgba(201,168,76,.4);
        }

        .sb-item.active {
            color: #fff;
            background: rgba(201,168,76,.12);
            border-left-color: var(--gold);
        }

        .sb-item svg { width: 15px; height: 15px; flex-shrink: 0; opacity: .8; }
        .sb-item.active svg { opacity: 1; }

        .sb-divider {
            border: none;
            border-top: 1px solid rgba(255,255,255,.07);
            margin: .5rem 1.25rem;
        }

        .sb-logout {
            flex-shrink: 0;
            padding: .75rem 1.25rem;
            border-top: 1px solid rgba(255,255,255,.07);
        }

        .sb-logout a {
            display: flex; align-items: center; gap: .6rem;
            padding: .6rem .85rem;
            border-radius: 8px;
            background: rgba(239,68,68,.1);
            border: 1px solid rgba(239,68,68,.2);
            color: #fca5a5;
            font-size: .8rem; font-weight: 600;
            transition: background .15s;
        }

        .sb-logout a:hover { background: rgba(239,68,68,.18); }
        .sb-logout svg { width: 14px; height: 14px; }

        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0; z-index: 190;
            background: rgba(0,0,0,.5);
        }

        /* Topbar */
        .topbar {
            position: fixed;
            top: 0; left: var(--sidebar-w); right: 0;
            height: 58px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            z-index: 100;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }

        .topbar-left {
            display: flex; align-items: center; gap: .85rem;
        }

        .hamburger {
            display: none;
            width: 36px; height: 36px; border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg);
            align-items: center; justify-content: center;
            color: var(--muted);
        }

        .hamburger svg { width: 18px; height: 18px; }

        .breadcrumb {
            display: flex; align-items: center; gap: .4rem;
            font-size: .78rem; color: var(--muted);
        }

        .breadcrumb .sep { color: #cbd5e1; }
        .breadcrumb .current { color: var(--text); font-weight: 600; }

        .topbar-right {
            display: flex; align-items: center; gap: .75rem;
        }

        .tb-date {
            font-size: .78rem; color: var(--muted);
            padding: .35rem .75rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .tb-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, var(--navy-mid), var(--navy-lite));
            display: grid; place-items: center;
            font-weight: 700; font-size: .78rem;
            color: #fff;
            border: 2px solid var(--gold);
        }

        /* Main Content */
        .main {
            margin-left: var(--sidebar-w);
            padding-top: 58px;
            min-height: 100vh;
        }

        .main-inner {
            padding: 1.5rem 1.75rem;
            max-width: 1280px;
        }

        .page-header {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-lite) 100%);
            border-radius: 14px;
            padding: 1.5rem 1.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .page-header-text h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.35rem; font-weight: 700;
            color: #fff; margin-bottom: .2rem;
        }

        .page-header-text p {
            font-size: .8rem; color: rgba(255,255,255,.6);
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
        }

        .card-head {
            padding: .9rem 1.4rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: #fafbfd;
        }

        .card-head h2 {
            font-size: .95rem; font-weight: 700;
            color: var(--navy);
            display: flex; align-items: center; gap: .5rem;
        }

        .card-head h2 svg { width: 17px; height: 17px; color: var(--gold); }

        .card-body { padding: 1.25rem 1.4rem; }

        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        select, input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            background: var(--surface);
        }
        select:focus, input:focus, textarea:focus {
            outline: none;
            border-color: var(--gold);
        }

        .btn {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .55rem 1rem; border-radius: 8px;
            font-size: .8rem; font-weight: 600;
            transition: all .15s; border: 1px solid transparent;
            white-space: nowrap;
        }
        .btn-green { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
        .btn-green:hover { background: #a7f3d0; }
        .btn-sm { padding: .35rem .7rem; font-size: .74rem; }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .grades-table { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #f8fafc; font-weight: 600; color: var(--navy); }
        .grade-input { width: 100px; padding: 8px; }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-graded { background: #28a745; color: white; }
        .badge-pending { background: #ffc107; color: #333; }

        .empty-state {
            text-align: center; padding: 2.5rem 1rem;
            color: var(--muted);
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.open { display: block; }
            .topbar { left: 0; }
            .main { margin-left: 0; }
            .hamburger { display: flex; }
            .main-inner { padding: 1rem; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sb-brand">
        <div class="sb-logo">CS</div>
        <div class="sb-brand-text">
            <div class="name">Chigoneka School</div>
            <div class="role">Staff Portal</div>
        </div>
    </div>

    <div class="sb-profile">
        <div class="sb-avatar"><?= e($initials) ?></div>
        <div class="sb-profile-info">
            <div class="teacher-name"><?= e($teacher_name) ?></div>
            <div class="teacher-meta">Class Teacher &nbsp;·&nbsp; <?= $currentYear ?></div>
        </div>
    </div>

    <nav class="sb-nav">
        <?php foreach ($nav_sections as $si => $section): ?>
            <?php if ($si > 0): ?><hr class="sb-divider"><?php endif; ?>
            <div class="sb-section">
                <div class="sb-section-title"><?= e($section['title']) ?></div>
                <?php foreach ($section['items'] as $item): ?>
                    <a href="<?= e($item['href']) ?>" class="sb-item <?= isActive($item['href'], $current_page) ?>">
                        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?= e($item['icon']) ?>"/>
                        </svg>
                        <?= e($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="sb-logout">
        <a href="logout.php">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Sign Out
        </a>
    </div>
</aside>

<header class="topbar">
    <div class="topbar-left">
        <button class="hamburger" id="hamburger" onclick="openSidebar()" aria-label="Open menu">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <div class="breadcrumb">
            <span>Staff Portal</span>
            <span class="sep">/</span>
            <span class="current">Manage Grades</span>
        </div>
    </div>
    <div class="topbar-right">
        <div class="tb-date"><?= date('D, d M Y') ?></div>
        <div class="tb-avatar" title="<?= e($teacher_name) ?>"><?= e($initials) ?></div>
    </div>
</header>

<main class="main">
<div class="main-inner">

    <div class="page-header">
        <div class="page-header-text">
            <h1>📝 Manage Student Grades</h1>
            <p><?= e($greeting) ?>, <?= e(explode(' ', $teacher_name)[0]) ?>! Enter and manage student grades</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Select Course Card -->
    <div class="card">
        <div class="card-head">
            <h2>
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                Select Course
            </h2>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="form-group">
                    <label for="course_id">Choose Course to Grade</label>
                    <select name="course_id" id="course_id" onchange="this.form.submit()">
                        <option value="">-- Select a course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" <?php echo $selected_course_id == $course['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_code']); ?> - <?php echo htmlspecialchars($course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Grade Entry Card -->
    <?php if ($selected_course_id > 0 && !empty($students)): ?>
        <div class="card">
            <div class="card-head">
                <h2>
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                    Enter Grades for <?php echo htmlspecialchars($course_info['course_code'] ?? ''); ?> - <?php echo htmlspecialchars($course_info['course_name'] ?? ''); ?>
                </h2>
                <a href="enter_grades.php?course_id=<?php echo $selected_course_id; ?>" class="btn btn-green btn-sm">Quick Grade Entry</a>
            </div>
            <div class="card-body">
                <div class="grades-table">
                    <form method="POST" action="">
                        <input type="hidden" name="course_id" value="<?php echo $selected_course_id; ?>">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Email</th>
                                    <th>Current Grade</th>
                                    <th>New Grade</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <form method="POST" action="" style="margin:0;" id="form_<?php echo $student['id']; ?>">
                                            <input type="hidden" name="course_id" value="<?php echo $selected_course_id; ?>">
                                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                            <td><?php echo htmlspecialchars($student['username']); ?></td>
                                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['surname']); ?></td>
                                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                                            <td>
                                                <?php if (!empty($student['grade'])): ?>
                                                    <span class="badge badge-graded"><?php echo htmlspecialchars($student['grade']); ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-pending">Not graded</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <select name="grade" class="grade-input" required>
                                                    <option value="">Select Grade</option>
                                                    <option value="A" <?php echo ($student['grade'] ?? '') == 'A' ? 'selected' : ''; ?>>A (80-100)</option>
                                                    <option value="B+" <?php echo ($student['grade'] ?? '') == 'B+' ? 'selected' : ''; ?>>B+ (75-79)</option>
                                                    <option value="B" <?php echo ($student['grade'] ?? '') == 'B' ? 'selected' : ''; ?>>B (70-74)</option>
                                                    <option value="C+" <?php echo ($student['grade'] ?? '') == 'C+' ? 'selected' : ''; ?>>C+ (65-69)</option>
                                                    <option value="C" <?php echo ($student['grade'] ?? '') == 'C' ? 'selected' : ''; ?>>C (60-64)</option>
                                                    <option value="D" <?php echo ($student['grade'] ?? '') == 'D' ? 'selected' : ''; ?>>D (50-59)</option>
                                                    <option value="F" <?php echo ($student['grade'] ?? '') == 'F' ? 'selected' : ''; ?>>F (0-49)</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="remarks" placeholder="Remarks" value="<?php echo htmlspecialchars($student['remarks'] ?? ''); ?>" style="width: 150px;">
                                            </td>
                                            <td>
                                                <button type="submit" name="save_grades" class="btn btn-green btn-sm">Save Grade</button>
                                            </td>
                                        </form>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>
        </div>
    <?php elseif ($selected_course_id > 0 && empty($students)): ?>
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <p>No students enrolled in this course yet.</p>
                </div>
            </div>
        </div>
    <?php elseif ($selected_course_id > 0 && empty($courses)): ?>
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <p>You haven't been assigned any courses yet. Please contact the administrator.</p>
                    <a href="teacher_dashboard.php" style="color: var(--navy-lite);">← Back to Dashboard</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>
</main>

<script>
function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('open');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSidebar();
});
</script>
</body>
</html>