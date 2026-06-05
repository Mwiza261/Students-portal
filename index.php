<?php
session_start();
require_once __DIR__ . '/db.php';

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

$mysqli     = db_connect();
$student_id = (int) $user['id'];

// Count enrolled courses
$course_count = 0;
$stmt = $mysqli->prepare(
    'SELECT COUNT(*) FROM course_registrations WHERE student_id = ? AND status = "enrolled"'
);
if ($stmt) {
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $stmt->bind_result($course_count);
    $stmt->fetch();
    $stmt->close();
}

// Count pending results
$pending_count = 0;
$stmt = $mysqli->prepare(
    'SELECT COUNT(*) FROM course_registrations
     WHERE student_id = ? AND status = "enrolled" AND (grade IS NULL OR grade = "")'
);
if ($stmt) {
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $stmt->bind_result($pending_count);
    $stmt->fetch();
    $stmt->close();
}

// Latest notices
$notices = [];
$check = $mysqli->query("SHOW TABLES LIKE 'notices'");
if ($check && $check->num_rows > 0) {
    $res = $mysqli->query(
        "SELECT title, body, created_at FROM notices ORDER BY created_at DESC LIMIT 5"
    );
    while ($row = $res->fetch_assoc()) $notices[] = $row;
}

$mysqli->close();

$full_name = e($user['first_name'] . ' ' . $user['surname']);
$initials  = strtoupper(substr($user['first_name'], 0, 1) . substr($user['surname'], 0, 1));
$greeting  = date('H') < 12 ? 'Good morning' : (date('H') < 17 ? 'Good afternoon' : 'Good evening');
$current   = basename($_SERVER['PHP_SELF']);

if (!function_exists('nav_icon')) {
    function nav_icon(string $name): string {
        $icons = [
            'home'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
            'book'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
            'plus'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            'clipboard'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>',
            'chart'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
            'results'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
            'checklist'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
            'eye'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>',
            'star'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>',
            'calendar'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
            'user'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
            'lock'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>',
            'records'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>',
            'support'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>',
            'policy'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
            'payment'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>',
            'chevron'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>',
        ];
        return $icons[$name] ?? '';
    }
}

// ── Navigation groups matching UNIMA structure ───────────────
$nav_groups = [
    [
        'label' => 'Academics',
        'icon'  => 'book',
        'items' => [
            ['href' => 'my_courses.php',           'label' => 'My Courses'],
            ['href' => 'register_courses.php',     'label' => 'Register Courses'],
            ['href' => 'View_registeration.php',   'label' => 'View Registration'],
            ['href' => 'student_assessments.php',  'label' => 'Assessment'],
            ['href' => 'exam_results.php',         'label' => 'Exam Results'],
            ['href' => 'student_results.php',      'label' => 'My Results'],
            ['href' => 'view_student_grades.php',  'label' => 'View Grades'],
            ['href' => 'timetable.php',            'label' => 'Timetables'],
            ['href' => 'academic_calendar.php',    'label' => 'Academic Calendar'],
        ],
    ],
    [
        'label' => 'Registration & Records',
        'icon'  => 'records',
        'items' => [
            ['href' => 'student_course_registration.php', 'label' => 'Course Registration'],
            ['href' => 'View_registeration.php',          'label' => 'View Registration'],
            ['href' => 'register_courses.php',            'label' => 'Register for Courses'],
        ],
    ],
    [
        'label' => 'Personal & Support',
        'icon'  => 'support',
        'items' => [
            ['href' => 'profile.php',         'label' => 'My Profile'],
            ['href' => 'change_password.php', 'label' => 'Change Password'],
            ['href' => 'support.php',         'label' => 'Help & Support'],
        ],
    ],
    [
        'label' => 'Policies & Resources',
        'icon'  => 'policy',
        'items' => [
            ['href' => 'policies.php',   'label' => 'School Policies'],
            ['href' => 'resources.php',  'label' => 'Learning Resources'],
            ['href' => 'downloads.php',  'label' => 'Downloads'],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | Chigoneka School Student Portal</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f0f4f8;
            color: #1a202c;
            font-size: 14px;
        }

        /* ── Top navbar (UNIMA-style blue bar) ── */
        .topnav {
            background: #1a3a6b;
            color: #fff;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .topnav-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
        }

        .topnav-brand-icon {
            width: 36px; height: 36px;
            border-radius: 8px;
            background: #c9a84c;
            display: grid; place-items: center;
            font-weight: 900; font-size: 0.85rem;
            color: #1a3a6b;
        }

        .topnav-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .topnav-icon-btn {
            width: 34px; height: 34px;
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
            display: grid; place-items: center;
            cursor: pointer;
            color: #fff;
            border: none;
            transition: background 0.15s;
        }

        .topnav-icon-btn:hover { background: rgba(255,255,255,0.18); }

        .topnav-user {
            display: flex; align-items: center; gap: 0.6rem;
            font-size: 0.85rem;
            color: #fff;
        }

        .topnav-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: #c9a84c;
            display: grid; place-items: center;
            font-weight: 700; font-size: 0.75rem;
            color: #1a3a6b;
        }

        /* ── Breadcrumb bar ── */
        .breadcrumb-bar {
            background: #c9a84c;
            padding: 0.5rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: #1a3a6b;
            font-weight: 600;
        }

        .breadcrumb-bar svg { width: 14px; height: 14px; }

        /* ── Page layout ── */
        .layout {
            display: grid;
            grid-template-columns: 220px 1fr;
            min-height: calc(100vh - 88px);
        }

        /* ── Left sidebar (UNIMA accordion style) ── */
        .sidebar {
            background: #fff;
            border-right: 1px solid #e2e8f0;
            overflow-y: auto;
        }

        /* Profile block */
        .profile-block {
            padding: 1.5rem 1rem;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }

        .profile-avatar {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: #e8edf5;
            display: grid; place-items: center;
            margin: 0 auto 0.75rem;
            border: 3px solid #1a3a6b;
        }

        .profile-avatar svg {
            width: 36px; height: 36px;
            color: #94a3b8;
        }

        .profile-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: #1a3a6b;
            margin-bottom: 2px;
        }

        .profile-id {
            font-size: 0.72rem;
            color: #64748b;
        }

        /* Accordion nav */
        .nav-group { border-bottom: 1px solid #f1f5f9; }

        .nav-group-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            color: #1a3a6b;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            transition: background 0.15s;
        }

        .nav-group-toggle:hover { background: #f8fafc; }

        .nav-group-toggle.open { background: #eef2ff; color: #4338ca; }

        .nav-group-left {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .nav-group-left svg { width: 15px; height: 15px; flex-shrink: 0; }

        .nav-chevron {
            width: 14px; height: 14px;
            transition: transform 0.2s;
            flex-shrink: 0;
        }

        .nav-chevron.rotated { transform: rotate(90deg); }

        .nav-items {
            display: none;
            background: #fafbff;
            border-top: 1px solid #f1f5f9;
        }

        .nav-items.open { display: block; }

        .nav-items a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1rem 0.6rem 2rem;
            font-size: 0.78rem;
            color: #475569;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.12s;
        }

        .nav-items a::before {
            content: '▸';
            font-size: 0.65rem;
            color: #94a3b8;
        }

        .nav-items a:hover {
            background: #eef2ff;
            color: #4338ca;
            border-left-color: #6366f1;
        }

        .nav-items a.active {
            background: #e0e7ff;
            color: #3730a3;
            font-weight: 600;
            border-left-color: #4f46e5;
        }

        .nav-logout {
            padding: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        .btn-logout {
            display: flex; align-items: center; justify-content: center;
            gap: 0.5rem; width: 100%;
            padding: 0.6rem;
            border-radius: 8px;
            background: #fef2f2;
            color: #dc2626;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid #fecaca;
            transition: background 0.15s;
        }

        .btn-logout:hover { background: #fee2e2; }

        /* ── Main content ── */
        .main {
            padding: 1.5rem;
            overflow-x: hidden;
        }

        /* ── Welcome notice ── */
        .welcome-notice {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 0.85rem 1.1rem;
            font-size: 0.8rem;
            color: #1e40af;
            line-height: 1.65;
            margin-bottom: 1.25rem;
        }

        .welcome-notice strong { color: #1e3a8a; }

        /* ── Info cards row (UNIMA-style 3 cards) ── */
        .info-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 1rem 1.1rem;
            display: flex;
            align-items: flex-start;
            gap: 0.9rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            text-decoration: none;
            color: inherit;
            transition: box-shadow 0.15s, border-color 0.15s;
        }

        .info-card:hover {
            box-shadow: 0 4px 14px rgba(0,0,0,0.09);
            border-color: #c7d2fe;
        }

        .info-card-icon {
            width: 44px; height: 44px;
            border-radius: 10px;
            display: grid; place-items: center;
            flex-shrink: 0;
        }

        .info-card-icon svg { width: 24px; height: 24px; }
        .info-card-icon.blue   { background: #dbeafe; color: #1d4ed8; }
        .info-card-icon.gold   { background: #fef9c3; color: #a16207; }
        .info-card-icon.purple { background: #ede9fe; color: #6d28d9; }

        .info-card-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            margin-bottom: 3px;
        }

        .info-card-value {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
        }

        .info-card-sub {
            font-size: 0.72rem;
            color: #64748b;
            margin-top: 2px;
            line-height: 1.5;
        }

        /* ── Section heading ── */
        .section-heading {
            font-size: 0.9rem;
            font-weight: 700;
            color: #1a3a6b;
            margin: 0 0 0.85rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #c9a84c;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* ── Payment section (UNIMA-style with image cards) ── */
        .payment-section { margin-bottom: 1.5rem; }

        .payment-cards {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
        }

        .payment-card {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .payment-card-header {
            background: #1a3a6b;
            color: #fff;
            padding: 0.65rem 1rem;
            font-size: 0.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .payment-card-header.gold { background: #c9a84c; color: #1a3a6b; }
        .payment-card-header svg  { width: 16px; height: 16px; }

        .payment-steps-list {
            padding: 0.85rem 1rem;
            list-style: none;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .payment-steps-list li {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            font-size: 0.78rem;
            color: #334155;
            line-height: 1.45;
        }

        .ps-num {
            width: 18px; height: 18px;
            border-radius: 50%;
            background: #1a3a6b;
            color: #fff;
            font-size: 0.62rem;
            font-weight: 800;
            display: grid; place-items: center;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .ps-num.gold { background: #c9a84c; color: #1a3a6b; }

        /* ── General notice / notice board ── */
        .notices-section { margin-bottom: 1.5rem; }

        .notice-board {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .notice-row {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.9rem 1.1rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.82rem;
            color: #334155;
            line-height: 1.55;
        }

        .notice-row:last-child { border-bottom: none; }

        .notice-icon {
            width: 28px; height: 28px;
            border-radius: 50%;
            background: #fef3c7;
            color: #d97706;
            display: grid; place-items: center;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .notice-icon svg { width: 14px; height: 14px; }

        .notice-title {
            font-weight: 700;
            color: #1a3a6b;
            margin-bottom: 2px;
            font-size: 0.83rem;
        }

        .notice-link {
            color: #dc2626;
            text-decoration: none;
            font-weight: 600;
        }

        .notice-link:hover { text-decoration: underline; }

        .notice-date {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: 3px;
        }

        /* ── Quick access grid ── */
        .quick-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .quick-card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 0.6rem;
            text-decoration: none;
            color: #1a3a6b;
            transition: all 0.15s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .quick-card:hover {
            border-color: #1a3a6b;
            background: #eff6ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(26,58,107,0.1);
        }

        .qc-icon {
            width: 40px; height: 40px;
            border-radius: 10px;
            background: #dbeafe;
            color: #1d4ed8;
            display: grid; place-items: center;
        }

        .qc-icon svg { width: 20px; height: 20px; }
        .qc-label { font-size: 0.75rem; font-weight: 600; }

        /* ── Footer ── */
        .portal-footer {
            background: #1a3a6b;
            color: #94a3b8;
            text-align: center;
            padding: 0.85rem;
            font-size: 0.75rem;
        }

        .portal-footer a { color: #93c5fd; text-decoration: none; }

        /* ── Responsive ── */
        @media (max-width: 1024px) {
            .info-cards    { grid-template-columns: 1fr 1fr; }
            .payment-cards { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 768px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar { border-right: none; border-bottom: 1px solid #e2e8f0; }
            .profile-block { display: flex; align-items: center; gap: 1rem; text-align: left; }
            .profile-avatar { margin: 0; width: 48px; height: 48px; }
            .info-cards, .payment-cards { grid-template-columns: 1fr; }
            .quick-grid { grid-template-columns: repeat(3, 1fr); }
            .topnav-brand span { display: none; }
        }
    </style>
</head>
<body>

<!-- ── Top Navigation Bar ── -->
<header class="topnav">
    <a class="topnav-brand" href="index.php">
        <div class="topnav-brand-icon">CS</div>
        <span>Student Portal</span>
    </a>
    <div class="topnav-right">
        <button class="topnav-icon-btn" title="Notifications" aria-label="Notifications">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
        </button>
        <button class="topnav-icon-btn" title="Apps" aria-label="Apps">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
        </button>
        <div class="topnav-user">
            <div class="topnav-avatar"><?php echo $initials; ?></div>
            <span><?php echo e($user['first_name']); ?></span>
        </div>
    </div>
</header>

<!-- ── Breadcrumb ── -->
<div class="breadcrumb-bar">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    HOME
</div>

<div class="layout">

    <!-- ── Left Sidebar ── -->
    <aside class="sidebar">

        <!-- Profile -->
        <div class="profile-block">
            <div class="profile-avatar">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div class="profile-name"><?php echo $full_name; ?></div>
            <div class="profile-id"><?php echo e($user['username']); ?></div>
        </div>

        <!-- Accordion nav groups -->
        <?php foreach ($nav_groups as $gi => $group): ?>
        <div class="nav-group">
            <button class="nav-group-toggle" onclick="toggleGroup(<?php echo $gi; ?>)" id="toggle-<?php echo $gi; ?>">
                <span class="nav-group-left">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <?php echo nav_icon($group['icon']); ?>
                    </svg>
                    <?php echo e($group['label']); ?>
                </span>
                <svg class="nav-chevron" id="chevron-<?php echo $gi; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <?php echo nav_icon('chevron'); ?>
                </svg>
            </button>
            <div class="nav-items" id="group-<?php echo $gi; ?>">
                <?php foreach ($group['items'] as $item):
                    $active = ($current === $item['href']) ? 'active' : '';
                ?>
                    <a href="<?php echo e($item['href']); ?>" class="<?php echo $active; ?>">
                        <?php echo e($item['label']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="nav-logout">
            <a class="btn-logout" href="logout.php">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Logout
            </a>
        </div>
    </aside>

    <!-- ── Main Content ── -->
    <main class="main">

        <!-- Welcome notice (matches UNIMA welcome text) -->
        <div class="welcome-notice">
            Welcome to <strong>Chigoneka School Student Portal</strong>. This is a web portal where all information
            and services that students need can be found. Please note that some information on the Portal is for
            active students only. If you are stuck, please visit the Business &amp; ICT Centre or send an email to
            <strong>ict@chigoneka.ac.mw</strong>.
            <strong style="color:#dc2626;"> Always remember to logout from the system when you are done.</strong>
        </div>

        <!-- ── 3 Info cards (UNIMA-style: Results, Courses, Account) ── -->
        <div class="info-cards">

            <a class="info-card" href="student_results.php">
                <div class="info-card-icon blue">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="info-card-label">End of Year Results</div>
                    <div class="info-card-value">
                        <?php echo $pending_count > 0 ? $pending_count . ' Pending' : 'View Results'; ?>
                    </div>
                    <div class="info-card-sub">
                        Semester 1 &nbsp;|&nbsp;
                        <?php echo $pending_count > 0
                            ? "$pending_count result(s) not yet graded"
                            : 'All results available'; ?>
                    </div>
                </div>
            </a>

            <a class="info-card" href="my_courses.php">
                <div class="info-card-icon gold">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    <div class="info-card-label">Registered Courses (Sem 2)</div>
                    <div class="info-card-value"><?php echo $course_count; ?></div>
                    <div class="info-card-sub">
                        Regular Courses &nbsp;: <?php echo $course_count; ?><br>
                        Audit Courses &nbsp;&nbsp;&nbsp;&nbsp;: 0<br>
                        Carryover Courses : 0
                    </div>
                </div>
            </a>

            <div class="info-card">
                <div class="info-card-icon purple">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <div>
                    <div class="info-card-label">Personal Account Number</div>
                    <div class="info-card-value" style="font-size:1rem;">
                        <?php echo e($user['username']); ?>
                    </div>
                    <div class="info-card-sub">
                        <a href="#payment" style="color:#7c3aed;font-weight:600;text-decoration:none;">
                            How to pay? ↓
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Payment section (UNIMA-style 3 method cards) ── -->
        <div class="payment-section" id="payment">
            <h2 class="section-heading">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                Tuition Fee Payment with MPAMBA &amp; MO626
            </h2>
            <div class="payment-cards">

                <!-- Using Account Number -->
                <div class="payment-card">
                    <div class="payment-card-header gold">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Using Account Number
                    </div>
                    <ul class="payment-steps-list">
                        <?php foreach ([
                            'Dial *444#',
                            'Select 4 Payments',
                            'Select 9 Tuition/Fees',
                            'Select Chigoneka School',
                            'Select 1 Tuition Fees',
                            'Enter Student Account Number',
                            'Enter Amount',
                            'Verify &amp; Enter Mpamba PIN',
                        ] as $i => $s): ?>
                            <li>
                                <span class="ps-num gold"><?php echo $i + 1; ?></span>
                                <?php echo $s; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Using Invoice Number -->
                <div class="payment-card">
                    <div class="payment-card-header gold">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Using Invoice Number
                    </div>
                    <ul class="payment-steps-list">
                        <?php foreach ([
                            'Dial *444#',
                            'Select 4 Payments',
                            'Select 9 Tuition/Fees',
                            'Select Chigoneka School',
                            'Select 2 Other',
                            'Enter Student Invoice Number',
                            'Enter Amount',
                            'Verify &amp; Enter Mpamba PIN',
                        ] as $i => $s): ?>
                            <li>
                                <span class="ps-num gold"><?php echo $i + 1; ?></span>
                                <?php echo $s; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Paying on MO626 -->
                <div class="payment-card">
                    <div class="payment-card-header">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        Paying on MO626
                    </div>
                    <ul class="payment-steps-list">
                        <?php foreach ([
                            'Dial *626#',
                            'Enter PIN',
                            'Select option 3 — Make payment',
                            'Select option 6 — Tuition/Fees',
                            'Select option 1 — Chigoneka School',
                            'Enter student account number',
                            'Confirm Name',
                            'Enter amount and PAY',
                        ] as $i => $s): ?>
                            <li>
                                <span class="ps-num"><?php echo $i + 1; ?></span>
                                <?php echo $s; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

            </div>
        </div>

        <!-- ── Notice Board ── -->
        <div class="notices-section">
            <h2 class="section-heading">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
                General Notice
            </h2>
            <div class="notice-board">
                <?php if (!empty($notices)): ?>
                    <?php foreach ($notices as $n): ?>
                        <div class="notice-row">
                            <div class="notice-icon">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                            </div>
                            <div>
                                <div class="notice-title"><?php echo e($n['title']); ?></div>
                                <div><?php echo e($n['body']); ?></div>
                                <div class="notice-date"><?php echo date('d M Y', strtotime($n['created_at'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="notice-row">
                        <div class="notice-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="notice-title">Special Student Data Bundle Registration</div>
                            <div>
                                To be eligible for special student data bundles you need to register your mobile numbers with the college.
                                You can only register one mobile phone number per service provider.
                                <a class="notice-link" href="#">Click here to register.</a>
                            </div>
                            <div class="notice-date"><?php echo date('d M Y'); ?></div>
                        </div>
                    </div>
                    <div class="notice-row">
                        <div class="notice-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="notice-title">Fee Payment Deadline</div>
                            <div>End of semester fee payment deadline is <strong>30 June 2026</strong>. Late payment penalties apply after this date.</div>
                            <div class="notice-date">01 Jun 2026</div>
                        </div>
                    </div>
                    <div class="notice-row">
                        <div class="notice-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                        </div>
                        <div>
                            <div class="notice-title">Download ODeL Welcome Summary</div>
                            <div>Click the link below to view the full communication from the Director of ODeL.</div>
                            <div>
                                <a class="notice-link" href="#">📎 Download ODeL Welcome Summary</a>
                            </div>
                            <div class="notice-date"><?php echo date('d M Y'); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Quick access shortcuts ── -->
        <h2 class="section-heading">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            Quick Access
        </h2>
        <div class="quick-grid">
            <?php
            $shortcuts = [
                ['href' => 'exam_results.php',        'label' => 'Exam Results',     'icon' => 'chart'],
                ['href' => 'student_results.php',     'label' => 'My Results',       'icon' => 'results'],
                ['href' => 'view_student_grades.php', 'label' => 'View Grades',      'icon' => 'star'],
                ['href' => 'my_courses.php',          'label' => 'My Courses',       'icon' => 'book'],
                ['href' => 'register_courses.php',    'label' => 'Register Course',  'icon' => 'plus'],
                ['href' => 'View_registeration.php',  'label' => 'Registration',     'icon' => 'clipboard'],
                ['href' => 'student_assessments.php', 'label' => 'Assessments',      'icon' => 'checklist'],
                ['href' => 'timetable.php',           'label' => 'Timetable',        'icon' => 'calendar'],
                ['href' => 'profile.php',             'label' => 'My Profile',       'icon' => 'user'],
                ['href' => 'change_password.php',     'label' => 'Password',         'icon' => 'lock'],
            ];
            foreach ($shortcuts as $s): ?>
                <a class="quick-card" href="<?php echo e($s['href']); ?>">
                    <div class="qc-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <?php echo nav_icon($s['icon']); ?>
                        </svg>
                    </div>
                    <span class="qc-label"><?php echo e($s['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>

    </main>
</div>

<!-- ── Footer ── -->
<footer class="portal-footer">
    &copy; <?php echo date('Y'); ?> Chigoneka School &mdash; Student Portal &nbsp;|&nbsp;
    <a href="support.php">Help &amp; Support</a> &nbsp;|&nbsp;
    <a href="logout.php">Logout</a>
</footer>

<script>
// ── Accordion toggle ────────────────────────────────────────
function toggleGroup(id) {
    const group   = document.getElementById('group-'   + id);
    const chevron = document.getElementById('chevron-' + id);
    const toggle  = document.getElementById('toggle-'  + id);
    const isOpen  = group.classList.contains('open');

    group.classList.toggle('open', !isOpen);
    chevron.classList.toggle('rotated', !isOpen);
    toggle.classList.toggle('open', !isOpen);
}

// ── Auto-open the group that contains the current active link ─
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.nav-items a.active').forEach(link => {
        const items = link.closest('.nav-items');
        if (!items) return;
        const id = items.id.replace('group-', '');
        toggleGroup(id);
    });

    // Open Academics by default on the home page
    const anyOpen = document.querySelector('.nav-items.open');
    if (!anyOpen) toggleGroup(0);
});
</script>
</body>
</html>