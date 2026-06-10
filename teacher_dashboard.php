<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: StaffLogin.php');
    exit;
}

$teacher_id   = $_SESSION['user_id'];
$teacher_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Teacher';
$current_page = basename($_SERVER['PHP_SELF']);
$currentYear  = (int) date('Y');

if (!function_exists('e')) {
    function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
}

function isActive(string $page, string $current): string {
    return basename($page) === $current ? 'active' : '';
}

$mysqli = db_connect();

/* ── Assigned courses ── */
$assigned_courses = [];
$stmt = $mysqli->prepare("
    SELECT c.id, c.course_code, c.course_name, c.credits,
           tca.academic_year, tca.semester,
           COUNT(DISTINCT scr.student_id) AS enrolled_students
    FROM teacher_course_assignments tca
    JOIN courses c ON tca.course_id = c.id
    LEFT JOIN student_course_registration scr
           ON c.id = scr.course_id AND scr.status = 'enrolled'
    WHERE tca.teacher_id = ? AND tca.status = 'active'
    GROUP BY c.id, tca.academic_year, tca.semester
    ORDER BY c.course_code
");
if ($stmt) {
    $stmt->bind_param('i', $teacher_id);
    $stmt->execute();
    $assigned_courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

/* ── Pending requests ── */
$pending_requests = [];
$stmt = $mysqli->prepare("
    SELECT sce.id, sce.assigned_date,
           u.first_name, u.surname, u.email,
           c.course_code, c.course_name
    FROM student_course_eligibility sce
    JOIN users u  ON sce.student_id  = u.id
    JOIN courses c ON sce.course_id  = c.id
    WHERE sce.teacher_id = ? AND sce.status = 'pending'
    ORDER BY sce.assigned_date DESC
");
if ($stmt) {
    $stmt->bind_param('i', $teacher_id);
    $stmt->execute();
    $pending_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$mysqli->close();

/* ── Nav structure ── */
$nav_sections = [
    [
        'title' => 'Main',
        'icon'  => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        'items' => [
            ['href' => 'teacher_dashboard.php',  'label' => 'Dashboard',  'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
        ],
    ],
    [
        'title' => 'Courses',
        'items' => [
            ['href' => 'assign_course_to_students.php', 'label' => 'Assign to Students', 'icon' => 'M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['href' => 'manage_course_students.php',    'label' => 'Manage Students',     'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
            ['href' => 'view_course_requests.php',      'label' => 'Course Requests',      'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'badge' => count($pending_requests)],
        ],
    ],
    [
        'title' => 'Grades',
        'items' => [
            ['href' => 'staff_grades.php',           'label' => 'Manage Grades',      'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
            ['href' => 'enter_grades.php',           'label' => 'Enter Grades',       'icon' => 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z'],
            ['href' => 'view_student_grades.php',    'label' => 'View Student Grades','icon' => 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
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
    <title>Teacher Dashboard | Chigoneka School</title>
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

        /* ═══════════════════════════════
           SIDEBAR
        ═══════════════════════════════ */
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

        /* Top branding strip */
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

        /* Teacher profile card */
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

        /* Scrollable nav area */
        .sb-nav {
            flex: 1;
            overflow-y: auto;
            padding: .75rem 0 1rem;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,.1) transparent;
        }

        .sb-nav::-webkit-scrollbar { width: 4px; }
        .sb-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 4px; }

        /* Section */
        .sb-section { margin-bottom: .25rem; }

        .sb-section-title {
            padding: .6rem 1.25rem .3rem;
            font-size: .62rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: rgba(255,255,255,.28);
        }

        /* Nav item */
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
            position: relative;
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

        /* Badge */
        .sb-badge {
            margin-left: auto;
            background: #ef4444;
            color: #fff;
            font-size: .62rem;
            font-weight: 800;
            padding: 1px 6px;
            border-radius: 999px;
            line-height: 1.6;
        }

        /* Divider */
        .sb-divider {
            border: none;
            border-top: 1px solid rgba(255,255,255,.07);
            margin: .5rem 1.25rem;
        }

        /* Logout */
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

        /* Mobile overlay */
        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0; z-index: 190;
            background: rgba(0,0,0,.5);
        }

        /* ═══════════════════════════════
           TOPBAR
        ═══════════════════════════════ */
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

        /* Hamburger (mobile) */
        .hamburger {
            display: none;
            width: 36px; height: 36px; border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg);
            align-items: center; justify-content: center;
            color: var(--muted);
        }

        .hamburger svg { width: 18px; height: 18px; }

        /* Breadcrumb */
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

        /* ═══════════════════════════════
           MAIN
        ═══════════════════════════════ */
        .main {
            margin-left: var(--sidebar-w);
            padding-top: 58px;
            min-height: 100vh;
        }

        .main-inner {
            padding: 1.5rem 1.75rem;
            max-width: 1280px;
        }

        /* ── Page header ── */
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

        .page-header-badge {
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 10px;
            padding: .55rem 1.1rem;
            text-align: center;
        }

        .page-header-badge .val {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem; font-weight: 700;
            color: var(--gold-lite);
        }

        .page-header-badge .lbl {
            font-size: .68rem; color: rgba(255,255,255,.5);
            margin-top: 1px;
        }

        /* ── Stats ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.1rem 1.25rem;
            display: flex; align-items: center; gap: .9rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
            transition: box-shadow .15s, transform .15s;
        }

        .stat-card:hover {
            box-shadow: 0 4px 14px rgba(0,0,0,.08);
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: grid; place-items: center; flex-shrink: 0;
        }

        .stat-icon svg { width: 22px; height: 22px; }
        .stat-icon.blue   { background: #dbeafe; color: #1d4ed8; }
        .stat-icon.amber  { background: #fef3c7; color: #b45309; }
        .stat-icon.indigo { background: #ede9fe; color: #6d28d9; }

        .stat-val {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem; font-weight: 900;
            color: var(--navy); line-height: 1;
        }

        .stat-lbl {
            font-size: .72rem; color: var(--muted);
            font-weight: 500; margin-top: 2px;
        }

        /* ── Section card ── */
        .s-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
        }

        .s-card-head {
            padding: .9rem 1.4rem;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center;
            justify-content: space-between; gap: 1rem;
            background: #fafbfd;
        }

        .s-card-head h2 {
            font-size: .95rem; font-weight: 700;
            color: var(--navy);
            display: flex; align-items: center; gap: .5rem;
        }

        .s-card-head h2 svg { width: 17px; height: 17px; color: var(--gold); }

        .s-card-body { padding: 1.25rem 1.4rem; }

        /* Quick actions */
        .quick-actions {
            display: flex; flex-wrap: wrap; gap: .65rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .55rem 1rem; border-radius: 8px;
            font-size: .8rem; font-weight: 600;
            transition: all .15s; border: 1px solid transparent;
            white-space: nowrap;
        }

        .btn svg { width: 14px; height: 14px; }

        .btn-navy   { background: var(--navy); color: #fff; }
        .btn-navy:hover  { background: var(--navy-lite); }
        .btn-gold   { background: var(--gold); color: var(--navy); }
        .btn-gold:hover  { background: #d4b55a; }
        .btn-ghost  { background: var(--bg); border-color: var(--border); color: var(--muted); }
        .btn-ghost:hover { color: var(--text); border-color: #b0bdd0; background: #e8ecf2; }
        .btn-green  { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
        .btn-green:hover { background: #a7f3d0; }
        .btn-red    { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
        .btn-red:hover   { background: #fecaca; }
        .btn-amber  { background: #fef3c7; color: #92400e; border-color: #fde68a; }
        .btn-amber:hover { background: #fde68a; }
        .btn-sm { padding: .35rem .7rem; font-size: .74rem; }

        /* Course grid */
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 1rem;
        }

        .course-card {
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            background: var(--surface);
            transition: box-shadow .15s, transform .15s;
        }

        .course-card:hover {
            box-shadow: 0 6px 20px rgba(0,0,0,.1);
            transform: translateY(-2px);
        }

        .cc-top {
            background: linear-gradient(135deg, var(--navy), var(--navy-lite));
            padding: .85rem 1.1rem;
        }

        .cc-code {
            font-size: .68rem; font-weight: 700; letter-spacing: .08em;
            text-transform: uppercase; color: var(--gold-lite);
            margin-bottom: 2px;
        }

        .cc-name {
            font-size: .95rem; font-weight: 700; color: #fff;
            line-height: 1.25;
        }

        .cc-body { padding: .9rem 1.1rem; }

        .cc-meta {
            display: flex; flex-wrap: wrap; gap: .4rem .75rem;
            margin-bottom: .85rem;
        }

        .cc-chip {
            display: inline-flex; align-items: center; gap: .3rem;
            font-size: .72rem; color: var(--muted);
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 6px; padding: .22rem .55rem;
        }

        .cc-chip svg { width: 11px; height: 11px; }

        .cc-actions { display: flex; gap: .45rem; flex-wrap: wrap; }

        /* Table */
        .tbl-wrap { overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; }

        thead th {
            background: #f8fafc;
            padding: .65rem 1rem;
            text-align: left;
            font-size: .72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .06em;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
        }

        tbody td {
            padding: .75rem 1rem;
            font-size: .82rem;
            color: var(--text);
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #f8fafc; }

        .td-name { font-weight: 600; color: var(--navy); }
        .td-email { color: var(--muted); font-size: .76rem; }

        /* Status pill */
        .pill {
            display: inline-block; padding: .18rem .6rem;
            border-radius: 999px; font-size: .68rem; font-weight: 700;
        }

        .pill-pending { background: #fef3c7; color: #92400e; }

        /* Empty state */
        .empty-state {
            text-align: center; padding: 2.5rem 1rem;
            color: var(--muted);
        }

        .empty-state svg { width: 40px; height: 40px; margin: 0 auto .75rem; opacity: .3; }
        .empty-state p { font-size: .85rem; }
        .empty-state a { color: var(--navy-lite); font-weight: 600; }

        /* ── Responsive ── */
        @media (max-width: 1024px) {
            .stats-row { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.open { display: block; }
            .topbar { left: 0; }
            .main { margin-left: 0; }
            .hamburger { display: flex; }
            .stats-row { grid-template-columns: 1fr; }
            .main-inner { padding: 1rem; }
            .page-header { padding: 1.1rem; }
        }

        @media (max-width: 480px) {
            .course-grid { grid-template-columns: 1fr; }
            .tb-date { display: none; }
        }
    </style>
</head>
<body>

<!-- ═══ SIDEBAR ═══ -->
<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <!-- Brand -->
    <div class="sb-brand">
        <div class="sb-logo">CS</div>
        <div class="sb-brand-text">
            <div class="name">Chigoneka School</div>
            <div class="role">Staff Portal</div>
        </div>
    </div>

    <!-- Teacher profile -->
    <div class="sb-profile">
        <div class="sb-avatar"><?= e($initials) ?></div>
        <div class="sb-profile-info">
            <div class="teacher-name"><?= e($teacher_name) ?></div>
            <div class="teacher-meta">Class Teacher &nbsp;·&nbsp; <?= $currentYear ?></div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sb-nav">
        <?php foreach ($nav_sections as $si => $section): ?>
            <?php if ($si > 0): ?><hr class="sb-divider"><?php endif; ?>
            <div class="sb-section">
                <div class="sb-section-title"><?= e($section['title']) ?></div>
                <?php foreach ($section['items'] as $item): ?>
                    <a href="<?= e($item['href']) ?>"
                       class="sb-item <?= isActive($item['href'], $current_page) ?>">
                        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?= e($item['icon']) ?>"/>
                        </svg>
                        <?= e($item['label']) ?>
                        <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
                            <span class="sb-badge"><?= (int)$item['badge'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    <!-- Logout -->
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

<!-- ═══ TOPBAR ═══ -->
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
            <span class="current">Dashboard</span>
        </div>
    </div>
    <div class="topbar-right">
        <div class="tb-date"><?= date('D, d M Y') ?></div>
        <div class="tb-avatar" title="<?= e($teacher_name) ?>"><?= e($initials) ?></div>
    </div>
</header>

<!-- ═══ MAIN ═══ -->
<main class="main">
<div class="main-inner">

    <!-- Page header -->
    <div class="page-header">
        <div class="page-header-text">
            <h1><?= e($greeting) ?>, <?= e(explode(' ', $teacher_name)[0]) ?>!</h1>
            <p>Manage your courses, grades and student requests</p>
        </div>
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
            <div class="page-header-badge">
                <div class="val"><?= count($assigned_courses) ?></div>
                <div class="lbl">Courses Assigned</div>
            </div>
            <div class="page-header-badge">
                <div class="val"><?= count($pending_requests) ?></div>
                <div class="lbl">Pending Requests</div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon blue">
                <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <div>
                <div class="stat-val"><?= count($assigned_courses) ?></div>
                <div class="stat-lbl">My Assigned Courses</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber">
                <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div>
                <div class="stat-val"><?= count($pending_requests) ?></div>
                <div class="stat-lbl">Pending Student Requests</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon indigo">
                <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
            </div>
            <div>
                <div class="stat-val"><?= array_sum(array_column($assigned_courses, 'enrolled_students')) ?></div>
                <div class="stat-lbl">Total Enrolled Students</div>
            </div>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="s-card">
        <div class="s-card-head">
            <h2>
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Quick Actions
            </h2>
        </div>
        <div class="s-card-body">
            <div class="quick-actions">
                <a href="assign_course_to_students.php" class="btn btn-navy">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Assign Course
                </a>
                <a href="enter_grades.php" class="btn btn-navy">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                    Enter Grades
                </a>
                <a href="view_course_requests.php" class="btn btn-amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    View Requests
                    <?php if (count($pending_requests) > 0): ?>
                        <span style="background:#b45309;color:#fff;font-size:.6rem;padding:1px 5px;border-radius:999px;margin-left:2px;">
                            <?= count($pending_requests) ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Assigned courses -->
    <div class="s-card">
        <div class="s-card-head">
            <h2>
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                My Assigned Courses
            </h2>
            <a href="assign_course_to_students.php" class="btn btn-gold btn-sm">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Assign New
            </a>
        </div>
        <div class="s-card-body">
            <?php if (!empty($assigned_courses)): ?>
                <div class="course-grid">
                    <?php foreach ($assigned_courses as $course): ?>
                        <div class="course-card">
                            <div class="cc-top">
                                <div class="cc-code"><?= e($course['course_code']) ?></div>
                                <div class="cc-name"><?= e($course['course_name']) ?></div>
                            </div>
                            <div class="cc-body">
                                <div class="cc-meta">
                                    <span class="cc-chip">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <?= (int)$course['enrolled_students'] ?> students
                                    </span>
                                    <span class="cc-chip">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                        </svg>
                                        <?= (int)$course['credits'] ?> credits
                                    </span>
                                    <span class="cc-chip">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <?= e($course['semester']) ?>
                                    </span>
                                </div>
                                <div class="cc-actions">
                                    <a href="manage_course_students.php?course_id=<?= (int)$course['id'] ?>" class="btn btn-ghost btn-sm">Students</a>
                                    <a href="enter_grades.php?course_id=<?= (int)$course['id'] ?>" class="btn btn-green btn-sm">Grades</a>
                                    <a href="view_course_requests.php?course_id=<?= (int)$course['id'] ?>" class="btn btn-amber btn-sm">Requests</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <p>No courses assigned yet. <a href="assign_course_to_students.php">Assign your first course →</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pending requests table -->
    <?php if (!empty($pending_requests)): ?>
    <div class="s-card">
        <div class="s-card-head">
            <h2>
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Pending Student Requests
                <span style="background:#fef3c7;color:#92400e;font-size:.68rem;padding:2px 8px;border-radius:999px;font-weight:700;margin-left:4px;">
                    <?= count($pending_requests) ?>
                </span>
            </h2>
            <a href="view_course_requests.php" class="btn btn-ghost btn-sm">View All</a>
        </div>
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Requested</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_requests as $req): ?>
                    <tr>
                        <td>
                            <div class="td-name"><?= e($req['first_name'] . ' ' . $req['surname']) ?></div>
                            <div class="td-email"><?= e($req['email']) ?></div>
                        </td>
                        <td><?= e($req['course_code'] . ' — ' . $req['course_name']) ?></td>
                        <td><?= date('d M Y', strtotime($req['assigned_date'])) ?></td>
                        <td><span class="pill pill-pending">Pending</span></td>
                        <td style="display:flex;gap:.4rem;align-items:center;">
                            <a href="process_request.php?id=<?= (int)$req['id'] ?>&action=approve"
                               class="btn btn-green btn-sm"
                               onclick="return confirm('Approve this request?')">Approve</a>
                            <a href="process_request.php?id=<?= (int)$req['id'] ?>&action=reject"
                               class="btn btn-red btn-sm"
                               onclick="return confirm('Reject this request?')">Reject</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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

/* Close on Escape */
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSidebar();
});
</script>
</body>
</html>