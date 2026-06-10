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

/* ── Handle edit submission ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id          = (int)$_POST['edit_id'];
    $class_level = $_POST['class_level'] ?? '';
    $day_of_week = $_POST['day_of_week'] ?? '';
    $period      = (int)($_POST['period'] ?? 0);
    $start_time  = $_POST['start_time'] ?? '';
    $end_time    = $_POST['end_time'] ?? '';
    $subject     = $_POST['subject'] ?? '';
    $t_name      = $_POST['teacher_name'] ?? '';
    $room        = $_POST['room'] ?? '';
    $term        = $_POST['term'] ?? '';

    $stmt = $mysqli->prepare(
        "UPDATE timetables SET class_level=?, day_of_week=?, period=?, start_time=?, end_time=?, subject=?, teacher_name=?, room=?, term=? WHERE id=?"
    );
    $stmt->bind_param('ssissssssi', $class_level, $day_of_week, $period, $start_time, $end_time, $subject, $t_name, $room, $term, $id);
    if ($stmt->execute()) {
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Timetable entry updated successfully.'];
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Failed to update entry.'];
    }
    $stmt->close();
    header('Location: teacher_timetable.php');
    exit;
}

/* ── Handle delete ── */
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $stmt = $mysqli->prepare("DELETE FROM timetables WHERE id = ?");
    $stmt->bind_param('i', $del_id);
    if ($stmt->execute()) {
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Entry deleted.'];
    }
    $stmt->close();
    header('Location: teacher_timetable.php');
    exit;
}

/* ── Filters ── */
$filter_class = $_GET['class'] ?? '';
$filter_day   = $_GET['day']   ?? '';

/* ── Fetch timetables (safe parameterised) ── */
$where  = "academic_year = ?";
$params = [$currentYear];
$types  = 'i';

if ($filter_class !== '') { $where .= " AND class_level = ?"; $params[] = $filter_class; $types .= 's'; }
if ($filter_day   !== '') { $where .= " AND day_of_week = ?"; $params[] = $filter_day;   $types .= 's'; }

$stmt = $mysqli->prepare(
    "SELECT * FROM timetables WHERE $where
     ORDER BY class_level,
              FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),
              period"
);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$timetables = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ── Pending requests count (for sidebar badge) ── */
$pending_count = 0;
$stmt = $mysqli->prepare("SELECT COUNT(*) FROM student_course_eligibility WHERE teacher_id = ? AND status = 'pending'");
if ($stmt) {
    $stmt->bind_param('i', $teacher_id);
    $stmt->execute();
    $stmt->bind_result($pending_count);
    $stmt->fetch();
    $stmt->close();
}

$mysqli->close();

$classes  = ['Form 1', 'Form 2', 'Form 3', 'Form 4'];
$days     = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$initials = strtoupper(substr($teacher_name, 0, 1));
$greeting = date('H') < 12 ? 'Good morning' : (date('H') < 17 ? 'Good afternoon' : 'Good evening');

/* ── Nav structure (mirrors teacher_dashboard.php exactly) ── */
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
            ['href' => 'manage_course_students.php',    'label' => 'Manage Students',     'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
            ['href' => 'view_course_requests.php',      'label' => 'Course Requests',      'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'badge' => $pending_count],
        ],
    ],
    [
        'title' => 'Timetable',
        'items' => [
            ['href' => 'teacher_timetable.php',        'label' => 'Manage Timetable', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['href' => 'teacher_timetable_create.php', 'label' => 'Create Entry',     'icon' => 'M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['href' => 'teacher_timetable_view.php',   'label' => 'View Timetable',   'icon' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'],
        ],
    ],
    [
        'title' => 'Grades',
        'items' => [
            ['href' => 'staff_grades.php',        'label' => 'Manage Grades',       'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
            ['href' => 'enter_grades.php',        'label' => 'Enter Grades',        'icon' => 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z'],
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

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Timetable | Chigoneka School</title>
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
        body { font-family: 'DM Sans', system-ui, sans-serif; background: var(--bg); color: var(--text); font-size: 14px; }
        a { color: inherit; text-decoration: none; }
        button { font: inherit; cursor: pointer; border: none; background: none; }

        /* ── SIDEBAR ── */
        .sidebar {
            position: fixed; inset: 0 auto 0 0; width: var(--sidebar-w);
            background: var(--navy); display: flex; flex-direction: column;
            z-index: 200; overflow: hidden;
            transition: transform .3s cubic-bezier(.4,0,.2,1);
        }
        .sb-brand {
            background: var(--navy-mid); padding: 1.1rem 1.25rem;
            display: flex; align-items: center; gap: .75rem;
            border-bottom: 1px solid rgba(255,255,255,.08); flex-shrink: 0;
        }
        .sb-logo {
            width: 36px; height: 36px; border-radius: 10px; background: var(--gold);
            display: grid; place-items: center; font-family: 'Playfair Display', serif;
            font-weight: 900; font-size: .78rem; color: var(--navy); flex-shrink: 0;
        }
        .sb-brand-text .name { font-family: 'Playfair Display', serif; font-size: .88rem; font-weight: 700; color: #fff; line-height: 1.1; }
        .sb-brand-text .role { font-size: .68rem; color: rgba(255,255,255,.45); margin-top: 1px; }
        .sb-profile {
            padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,.07);
            display: flex; align-items: center; gap: .75rem; flex-shrink: 0;
        }
        .sb-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), #e8c56a);
            display: grid; place-items: center; font-weight: 700; font-size: .85rem;
            color: var(--navy); flex-shrink: 0; box-shadow: 0 0 0 2px rgba(201,168,76,.3);
        }
        .sb-profile-info .teacher-name { font-size: .8rem; font-weight: 600; color: #e2e8f0; line-height: 1.2; }
        .sb-profile-info .teacher-meta { font-size: .68rem; color: rgba(255,255,255,.38); margin-top: 1px; }
        .sb-nav { flex: 1; overflow-y: auto; padding: .75rem 0 1rem; scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.1) transparent; }
        .sb-nav::-webkit-scrollbar { width: 4px; }
        .sb-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 4px; }
        .sb-section { margin-bottom: .25rem; }
        .sb-section-title { padding: .6rem 1.25rem .3rem; font-size: .62rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: rgba(255,255,255,.28); }
        .sb-item { display: flex; align-items: center; gap: .65rem; padding: .58rem 1.25rem; color: rgba(255,255,255,.55); font-size: .82rem; font-weight: 500; border-left: 3px solid transparent; transition: all .15s; }
        .sb-item:hover { color: #fff; background: rgba(255,255,255,.06); border-left-color: rgba(201,168,76,.4); }
        .sb-item.active { color: #fff; background: rgba(201,168,76,.12); border-left-color: var(--gold); }
        .sb-item svg { width: 15px; height: 15px; flex-shrink: 0; opacity: .8; }
        .sb-item.active svg { opacity: 1; }
        .sb-badge { margin-left: auto; background: #ef4444; color: #fff; font-size: .62rem; font-weight: 800; padding: 1px 6px; border-radius: 999px; line-height: 1.6; }
        .sb-divider { border: none; border-top: 1px solid rgba(255,255,255,.07); margin: .5rem 1.25rem; }
        .sb-logout { flex-shrink: 0; padding: .75rem 1.25rem; border-top: 1px solid rgba(255,255,255,.07); }
        .sb-logout a { display: flex; align-items: center; gap: .6rem; padding: .6rem .85rem; border-radius: 8px; background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.2); color: #fca5a5; font-size: .8rem; font-weight: 600; transition: background .15s; }
        .sb-logout a:hover { background: rgba(239,68,68,.18); }
        .sb-logout svg { width: 14px; height: 14px; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; z-index: 190; background: rgba(0,0,0,.5); }

        /* ── TOPBAR ── */
        .topbar { position: fixed; top: 0; left: var(--sidebar-w); right: 0; height: 58px; background: var(--surface); border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 1.5rem; z-index: 100; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        .topbar-left { display: flex; align-items: center; gap: .85rem; }
        .hamburger { display: none; width: 36px; height: 36px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg); align-items: center; justify-content: center; color: var(--muted); }
        .hamburger svg { width: 18px; height: 18px; }
        .breadcrumb { display: flex; align-items: center; gap: .4rem; font-size: .78rem; color: var(--muted); }
        .breadcrumb .sep { color: #cbd5e1; }
        .breadcrumb .current { color: var(--text); font-weight: 600; }
        .topbar-right { display: flex; align-items: center; gap: .75rem; }
        .tb-date { font-size: .78rem; color: var(--muted); padding: .35rem .75rem; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; }
        .tb-avatar { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, var(--navy-mid), var(--navy-lite)); display: grid; place-items: center; font-weight: 700; font-size: .78rem; color: #fff; border: 2px solid var(--gold); }

        /* ── MAIN ── */
        .main { margin-left: var(--sidebar-w); padding-top: 58px; min-height: 100vh; }
        .main-inner { padding: 1.5rem 1.75rem; max-width: 1280px; }

        /* Page header */
        .page-header { background: linear-gradient(135deg, var(--navy) 0%, var(--navy-lite) 100%); border-radius: 14px; padding: 1.5rem 1.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
        .page-header-text h1 { font-family: 'Playfair Display', serif; font-size: 1.35rem; font-weight: 700; color: #fff; margin-bottom: .2rem; }
        .page-header-text p { font-size: .8rem; color: rgba(255,255,255,.6); }

        /* Flash messages */
        .flash { padding: .85rem 1.2rem; border-radius: 10px; margin-bottom: 1.25rem; font-size: .84rem; font-weight: 500; display: flex; align-items: center; gap: .6rem; }
        .flash.success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .flash.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .flash svg { width: 16px; height: 16px; flex-shrink: 0; }

        /* Section card */
        .s-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; margin-bottom: 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,.04); }
        .s-card-head { padding: .9rem 1.4rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 1rem; background: #fafbfd; }
        .s-card-head h2 { font-size: .95rem; font-weight: 700; color: var(--navy); display: flex; align-items: center; gap: .5rem; }
        .s-card-head h2 svg { width: 17px; height: 17px; color: var(--gold); }
        .s-card-body { padding: 1.25rem 1.4rem; }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; gap: .4rem; padding: .55rem 1rem; border-radius: 8px; font-size: .8rem; font-weight: 600; transition: all .15s; border: 1px solid transparent; white-space: nowrap; cursor: pointer; }
        .btn svg { width: 14px; height: 14px; }
        .btn-navy  { background: var(--navy); color: #fff; }
        .btn-navy:hover { background: var(--navy-lite); }
        .btn-gold  { background: var(--gold); color: var(--navy); }
        .btn-gold:hover { background: #d4b55a; }
        .btn-ghost { background: var(--bg); border-color: var(--border); color: var(--muted); }
        .btn-ghost:hover { color: var(--text); border-color: #b0bdd0; background: #e8ecf2; }
        .btn-green { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
        .btn-green:hover { background: #a7f3d0; }
        .btn-red   { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
        .btn-red:hover { background: #fecaca; }
        .btn-sm { padding: .35rem .7rem; font-size: .74rem; }

        /* Filter bar */
        .filter-row { display: flex; gap: .75rem; flex-wrap: wrap; align-items: flex-end; }
        .filter-row select { padding: .5rem .85rem; border: 1px solid var(--border); border-radius: 8px; background: var(--bg); color: var(--text); font: inherit; font-size: .82rem; cursor: pointer; }
        .filter-row select:focus { outline: none; border-color: var(--navy-lite); }

        /* Table */
        .tbl-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #f8fafc; padding: .65rem 1rem; text-align: left; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); border-bottom: 1px solid var(--border); }
        tbody td { padding: .75rem 1rem; font-size: .82rem; color: var(--text); border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #f8fafc; }
        .td-bold { font-weight: 600; color: var(--navy); }

        /* Empty state */
        .empty-state { text-align: center; padding: 3rem 1rem; color: var(--muted); }
        .empty-state svg { width: 44px; height: 44px; margin: 0 auto .85rem; opacity: .25; }
        .empty-state p { font-size: .88rem; margin-bottom: 1rem; }

        /* ── MODAL ── */
        .modal-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 500; align-items: center; justify-content: center; padding: 1rem; }
        .modal-backdrop.open { display: flex; }
        .modal { background: var(--surface); border-radius: 16px; width: 100%; max-width: 560px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,.2); }
        .modal-head { padding: 1.1rem 1.4rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .modal-head h3 { font-size: 1rem; font-weight: 700; color: var(--navy); }
        .modal-close { width: 30px; height: 30px; border-radius: 8px; background: var(--bg); border: 1px solid var(--border); color: var(--muted); display: grid; place-items: center; cursor: pointer; font-size: 1rem; line-height: 1; }
        .modal-close:hover { background: #e8ecf2; }
        .modal-body { padding: 1.4rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { display: flex; flex-direction: column; gap: .35rem; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: .74rem; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; }
        .form-group input, .form-group select { padding: .55rem .85rem; border: 1px solid var(--border); border-radius: 8px; font: inherit; font-size: .84rem; color: var(--text); background: var(--bg); transition: border-color .15s; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--navy-lite); background: #fff; }
        .modal-foot { padding: 1rem 1.4rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: .65rem; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.open { display: block; }
            .topbar { left: 0; }
            .main { margin-left: 0; }
            .hamburger { display: flex; }
            .main-inner { padding: 1rem; }
            .form-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) { .tb-date { display: none; } }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- ═══ SIDEBAR ═══ -->
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
                        <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
                            <span class="sb-badge"><?= (int)$item['badge'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>
    <div class="sb-logout">
        <a href="logout.php">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Sign Out
        </a>
    </div>
</aside>

<!-- ═══ TOPBAR ═══ -->
<header class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="openSidebar()" aria-label="Open menu">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <div class="breadcrumb">
            <a href="teacher_dashboard.php">Staff Portal</a>
            <span class="sep">/</span>
            <span>Timetable</span>
            <span class="sep">/</span>
            <span class="current">Manage</span>
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
            <h1>Manage Timetable</h1>
            <p>View, edit and delete timetable entries for <?= $currentYear ?></p>
        </div>
        <a href="teacher_timetable_create.php" class="btn btn-gold">
            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add New Entry
        </a>
    </div>

    <!-- Flash message -->
    <?php if ($flash): ?>
    <div class="flash <?= e($flash['type']) ?>">
        <?php if ($flash['type'] === 'success'): ?>
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?php else: ?>
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?php endif; ?>
        <?= e($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="s-card">
        <div class="s-card-head">
            <h2>
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                </svg>
                Filter Entries
            </h2>
        </div>
        <div class="s-card-body">
            <form method="GET" action="" class="filter-row">
                <select name="class">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= e($c) ?>" <?= $filter_class === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="day">
                    <option value="">All Days</option>
                    <?php foreach ($days as $d): ?>
                        <option value="<?= e($d) ?>" <?= $filter_day === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-navy btn-sm">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                    </svg>
                    Filter
                </button>
                <?php if ($filter_class || $filter_day): ?>
                <a href="teacher_timetable.php" class="btn btn-ghost btn-sm">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Timetable table -->
    <div class="s-card">
        <div class="s-card-head">
            <h2>
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Timetable Entries — <?= $currentYear ?>
                <span style="background:var(--bg);border:1px solid var(--border);font-size:.68rem;padding:2px 8px;border-radius:999px;font-weight:700;margin-left:4px;color:var(--muted);">
                    <?= count($timetables) ?>
                </span>
            </h2>
            <a href="teacher_timetable_view.php" class="btn btn-ghost btn-sm">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                View Grid
            </a>
        </div>

        <?php if (!empty($timetables)): ?>
        <div class="tbl-wrap">
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
                        <th>Term</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($timetables as $tt): ?>
                    <tr>
                        <td class="td-bold"><?= e($tt['class_level']) ?></td>
                        <td><?= e($tt['day_of_week']) ?></td>
                        <td><?= (int)$tt['period'] ?></td>
                        <td style="white-space:nowrap;color:var(--muted);">
                            <?= date('h:i A', strtotime($tt['start_time'])) ?> – <?= date('h:i A', strtotime($tt['end_time'])) ?>
                        </td>
                        <td class="td-bold"><?= e($tt['subject']) ?></td>
                        <td><?= e($tt['teacher_name']) ?></td>
                        <td><?= e($tt['room']) ?></td>
                        <td><?= e($tt['term']) ?></td>
                        <td style="display:flex;gap:.4rem;align-items:center;">
                            <button class="btn btn-ghost btn-sm"
                                    onclick='openEdit(<?= htmlspecialchars(json_encode($tt), ENT_QUOTES) ?>)'>
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                                Edit
                            </button>
                            <a href="teacher_timetable.php?delete_id=<?= (int)$tt['id'] ?>"
                               class="btn btn-red btn-sm"
                               onclick="return confirm('Delete this timetable entry?')">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p>No timetable entries found<?= ($filter_class || $filter_day) ? ' for the selected filters' : '' ?>.</p>
            <a href="teacher_timetable_create.php" class="btn btn-navy">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Create First Entry
            </a>
        </div>
        <?php endif; ?>
    </div>

</div>
</main>

<!-- ═══ EDIT MODAL ═══ -->
<div class="modal-backdrop" id="editModal">
    <div class="modal">
        <div class="modal-head">
            <h3>Edit Timetable Entry</h3>
            <button class="modal-close" onclick="closeEdit()">✕</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Class Level</label>
                        <select name="class_level" id="edit_class" required>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= e($c) ?>"><?= e($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Day of Week</label>
                        <select name="day_of_week" id="edit_day" required>
                            <?php foreach ($days as $d): ?>
                                <option value="<?= e($d) ?>"><?= e($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Period</label>
                        <input type="number" name="period" id="edit_period" min="1" max="8" required>
                    </div>
                    <div class="form-group">
                        <label>Term</label>
                        <select name="term" id="edit_term" required>
                            <option value="Term 1">Term 1</option>
                            <option value="Term 2">Term 2</option>
                            <option value="Term 3">Term 3</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="start_time" id="edit_start" required>
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" name="end_time" id="edit_end" required>
                    </div>
                    <div class="form-group full">
                        <label>Subject</label>
                        <input type="text" name="subject" id="edit_subject" required>
                    </div>
                    <div class="form-group full">
                        <label>Teacher Name</label>
                        <input type="text" name="teacher_name" id="edit_teacher" required>
                    </div>
                    <div class="form-group full">
                        <label>Room</label>
                        <input type="text" name="room" id="edit_room">
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" onclick="closeEdit()">Cancel</button>
                <button type="submit" class="btn btn-navy">Save Changes</button>
            </div>
        </form>
    </div>
</div>

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
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeSidebar(); closeEdit(); } });

function openEdit(tt) {
    document.getElementById('edit_id').value      = tt.id;
    document.getElementById('edit_class').value   = tt.class_level;
    document.getElementById('edit_day').value     = tt.day_of_week;
    document.getElementById('edit_period').value  = tt.period;
    document.getElementById('edit_term').value    = tt.term;
    document.getElementById('edit_start').value   = tt.start_time;
    document.getElementById('edit_end').value     = tt.end_time;
    document.getElementById('edit_subject').value = tt.subject;
    document.getElementById('edit_teacher').value = tt.teacher_name;
    document.getElementById('edit_room').value    = tt.room;
    document.getElementById('editModal').classList.add('open');
}
function closeEdit() {
    document.getElementById('editModal').classList.remove('open');
}
// Close modal when clicking backdrop
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEdit();
});
</script>
</body>
</html>