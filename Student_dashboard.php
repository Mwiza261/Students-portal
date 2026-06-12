<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/db.php';

if (!function_exists('e')) {
    function e($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

/* ── Auth ── */
if (!isset($_SESSION['authenticated'], $_SESSION['user_id']) || $_SESSION['authenticated'] !== true) {
    header('Location: Login.php'); exit;
}
$user = get_user_by_id((int) $_SESSION['user_id']);
if (!$user || $user['role'] !== 'student') {
    session_unset(); session_destroy();
    header('Location: Login.php'); exit;
}

$mysqli      = db_connect();
$student_id  = (int) $user['id'];
$currentYear = (int) date('Y');

/* ── Auto-create tables ── */
$mysqli->query("CREATE TABLE IF NOT EXISTS `student_class_selections` (
    `id` INT NOT NULL AUTO_INCREMENT, `student_id` INT NOT NULL,
    `class_level` ENUM('Form 1','Form 2','Form 3','Form 4') NOT NULL,
    `academic_year` YEAR NOT NULL, `selected_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`), UNIQUE KEY `uq_sel` (`student_id`,`academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$mysqli->query("CREATE TABLE IF NOT EXISTS `student_subject_selections` (
    `id` INT NOT NULL AUTO_INCREMENT, `student_id` INT NOT NULL,
    `subject_name` VARCHAR(100) NOT NULL, `academic_year` YEAR NOT NULL,
    `selected_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`), KEY `k_sid` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ── Counts ── */
$course_count = 0;
if ($s = $mysqli->prepare('SELECT COUNT(*) FROM course_registrations WHERE student_id=? AND status="enrolled"')) {
    $s->bind_param('i',$student_id); $s->execute(); $s->bind_result($course_count); $s->fetch(); $s->close();
}
$pending_count = 0;
if ($s = $mysqli->prepare('SELECT COUNT(*) FROM course_registrations WHERE student_id=? AND status="enrolled" AND (grade IS NULL OR grade="")')) {
    $s->bind_param('i',$student_id); $s->execute(); $s->bind_result($pending_count); $s->fetch(); $s->close();
}

/* ── Class / subjects ── */
$has_class = false; $has_subjects = false; $class_level = null;
if ($s = $mysqli->prepare("SELECT class_level FROM student_class_selections WHERE student_id=? AND academic_year=?")) {
    $s->bind_param('ii',$student_id,$currentYear); $s->execute();
    $r = $s->get_result();
    if ($row = $r->fetch_assoc()) { $has_class = true; $class_level = $row['class_level']; }
    $s->close();
}
if ($s = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM student_subject_selections WHERE student_id=? AND academic_year=?")) {
    $s->bind_param('ii',$student_id,$currentYear); $s->execute();
    $r = $s->get_result();
    if ($row = $r->fetch_assoc()) { $has_subjects = $row['cnt'] > 0; }
    $s->close();
}

/* ── Notices ── */
$notices = [];
$chk = $mysqli->query("SHOW TABLES LIKE 'notices'");
if ($chk && $chk->num_rows > 0) {
    $res = $mysqli->query("SELECT title, body, created_at FROM notices ORDER BY created_at DESC LIMIT 5");
    if ($res) while ($row = $res->fetch_assoc()) $notices[] = $row;
}
$mysqli->close();

/* ── Display vars ── */
$full_name   = e($user['first_name'] . ' ' . $user['surname']);
$initials    = strtoupper(substr($user['first_name'],0,1) . substr($user['surname'],0,1));
$greeting    = date('H') < 12 ? 'Good morning' : (date('H') < 17 ? 'Good afternoon' : 'Good evening');
$current_page = basename($_SERVER['PHP_SELF']);

/* ── Nav groups ── */
$nav = [
    ['id'=>'academics','label'=>'Academics','items'=>[
        ['href'=>'my_courses.php',          'label'=>'My Courses',        'icon'=>'book-open'],
        ['href'=>'student_assessments.php', 'label'=>'Assessments',       'icon'=>'clipboard'],
        ['href'=>'student_results.php',     'label'=>'Exam Results',      'icon'=>'bar-chart'],
        ['href'=>'view_student_grades.php', 'label'=>'View Grades',       'icon'=>'star'],
        ['href'=>'student_timetable.php',   'label'=>'Timetable',         'icon'=>'calendar'],
        ['href'=>'academic_calendar.php',   'label'=>'Academic Calendar', 'icon'=>'calendar-days'],
    ]],
    ['id'=>'registration','label'=>'Registration','items'=>[
        ['href'=>'student_select_class.php',    'label'=>'Select Class',       'icon'=>'mortarboard'],
        ['href'=>'student_select_subjects.php', 'label'=>'Select Subjects',    'icon'=>'list-check'],
        ['href'=>'student_course_registration.php','label'=>'Course Registration','icon'=>'pencil-square'],
        ['href'=>'View_registeration.php',      'label'=>'View Registration',  'icon'=>'eye'],
    ]],
    ['id'=>'personal','label'=>'Personal','items'=>[
        ['href'=>'profile.php',         'label'=>'My Profile',      'icon'=>'person-circle'],
        ['href'=>'change_password.php', 'label'=>'Change Password', 'icon'=>'lock'],
        ['href'=>'notifications.php',   'label'=>'Notifications',   'icon'=>'bell'],
        ['href'=>'support.php',         'label'=>'Help & Support',  'icon'=>'question-circle'],
    ]],
];

/* ── Quick links ── */
$quick = [
    ['href'=>'student_select_class.php',    'label'=>'Select Class',    'color'=>'blue'],
    ['href'=>'student_select_subjects.php', 'label'=>'Select Subjects', 'color'=>'purple'],
    ['href'=>'my_courses.php',              'label'=>'My Courses',      'color'=>'teal'],
    ['href'=>'student_timetable.php',       'label'=>'Timetable',       'color'=>'indigo'],
    ['href'=>'student_results.php',         'label'=>'Exam Results',    'color'=>'green'],
    ['href'=>'view_student_grades.php',     'label'=>'View Grades',     'color'=>'amber'],
    ['href'=>'profile.php',                 'label'=>'My Profile',      'color'=>'slate'],
    ['href'=>'support.php',                 'label'=>'Help',            'color'=>'red'],
];

/* Determine which accordion group is active */
function activeGroup(string $current, array $nav): string {
    foreach ($nav as $g) {
        foreach ($g['items'] as $item) {
            if ($item['href'] === $current) return $g['id'];
        }
    }
    return 'academics';
}
$activeGroupId = activeGroup($current_page, $nav);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Home | Chigoneka School Student Portal</title>
<style>
/* ═══════════ RESET & ROOT ═══════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{
  --navy:#1a3a6b;   --navy-d:#12295a;  --navy-l:#2250a0;
  --gold:#c9a84c;   --gold-l:#e8c86a;  --gold-bg:#fef9ee;
  --white:#ffffff;  --bg:#f0f2f5;
  --border:#dde3ed; --border-l:#eef1f6;
  --text:#1a202c;   --text-m:#4a5568;  --text-s:#718096;
  --sidebar:260px;
  --topbar:56px;
}
html,body{height:100%;}
body{font-family:'Segoe UI',system-ui,-apple-system,sans-serif;background:var(--bg);color:var(--text);font-size:13.5px;line-height:1.5;}
a{color:inherit;text-decoration:none;}
button{font:inherit;cursor:pointer;border:none;background:none;}
img{display:block;max-width:100%;}

/* ═══════════ TOPBAR ═══════════ */
.topbar{
  position:fixed;top:0;left:0;right:0;height:var(--topbar);
  background:var(--navy);color:#fff;
  display:flex;align-items:center;justify-content:space-between;
  padding:0 1rem 0 0;
  z-index:300;
  box-shadow:0 2px 8px rgba(0,0,0,.25);
}
/* Left: toggle + brand */
.tb-left{display:flex;align-items:center;height:100%;}
.tb-toggle{
  width:var(--sidebar);height:100%;
  display:flex;align-items:center;gap:.6rem;
  padding:0 1rem;
  background:var(--navy-d);
  border-right:1px solid rgba(255,255,255,.08);
  flex-shrink:0;
  cursor:pointer;
  transition:background .15s;
}
.tb-toggle:hover{background:#0e2147;}
.tb-toggle-icon{display:flex;flex-direction:column;gap:4px;flex-shrink:0;}
.tb-toggle-icon span{display:block;width:18px;height:2px;background:rgba(255,255,255,.7);border-radius:2px;transition:all .2s;}
.tb-brand-wrap{display:flex;align-items:center;gap:.55rem;}
.tb-logo{
  width:30px;height:30px;border-radius:7px;
  background:var(--gold);
  display:grid;place-items:center;
  font-weight:900;font-size:.7rem;color:var(--navy);
  flex-shrink:0;
}
.tb-brand-name{font-size:.9rem;font-weight:700;color:#fff;line-height:1.1;}
.tb-brand-sub{font-size:.62rem;color:rgba(255,255,255,.45);}

/* Right side */
.tb-right{display:flex;align-items:center;gap:.5rem;padding:0 .75rem;}
.tb-icon-btn{
  width:34px;height:34px;border-radius:8px;
  background:rgba(255,255,255,.08);
  border:1px solid rgba(255,255,255,.1);
  display:grid;place-items:center;color:rgba(255,255,255,.75);
  transition:background .15s,color .15s;
  text-decoration:none;
}
.tb-icon-btn:hover{background:rgba(255,255,255,.16);color:#fff;}
.tb-icon-btn svg{width:16px;height:16px;}
.tb-user{display:flex;align-items:center;gap:.5rem;padding:.3rem .6rem;border-radius:8px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);}
.tb-avatar{width:28px;height:28px;border-radius:50%;background:var(--gold);display:grid;place-items:center;font-weight:700;font-size:.65rem;color:var(--navy);flex-shrink:0;}
.tb-username{font-size:.75rem;color:rgba(255,255,255,.85);max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}

/* ═══════════ GOLD BREADCRUMB BAR ═══════════ */
.breadcrumb-bar{
  position:fixed;top:var(--topbar);left:0;right:0;
  background:var(--gold);height:28px;
  display:flex;align-items:center;
  padding:0 1rem 0 calc(var(--sidebar) + 1rem);
  z-index:290;
  box-shadow:0 1px 4px rgba(0,0,0,.1);
}
.breadcrumb-bar span{font-size:.72rem;font-weight:600;color:var(--navy);}
.breadcrumb-bar .sep{margin:0 .35rem;color:rgba(26,58,107,.4);}

/* ═══════════ LAYOUT ═══════════ */
.layout{
  display:flex;
  padding-top:calc(var(--topbar) + 28px);
  min-height:100vh;
}

/* ═══════════ SIDEBAR ═══════════ */
.sidebar{
  position:fixed;
  top:calc(var(--topbar) + 28px);
  left:0;
  width:var(--sidebar);
  bottom:0;
  background:var(--white);
  border-right:1px solid var(--border);
  overflow-y:auto;
  z-index:200;
  display:flex;flex-direction:column;
  transition:transform .25s cubic-bezier(.4,0,.2,1);
  scrollbar-width:thin;
  scrollbar-color:#e2e8f0 transparent;
}
.sidebar::-webkit-scrollbar{width:4px;}
.sidebar::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:4px;}

/* Profile block */
.sb-profile{
  background:linear-gradient(135deg,var(--navy) 0%,var(--navy-l) 100%);
  padding:1.1rem 1rem;
  text-align:center;
  flex-shrink:0;
}
.sb-avatar{
  width:56px;height:56px;border-radius:50%;
  background:var(--gold);
  display:grid;place-items:center;
  margin:0 auto .6rem;
  font-size:1.1rem;font-weight:700;color:var(--navy);
  border:3px solid rgba(255,255,255,.3);
}
.sb-name{font-size:.82rem;font-weight:700;color:#fff;margin-bottom:2px;line-height:1.2;}
.sb-meta{font-size:.68rem;color:rgba(255,255,255,.5);}
.sb-reg-status{
  margin-top:.55rem;
  display:inline-flex;align-items:center;gap:.3rem;
  padding:.22rem .65rem;border-radius:999px;
  font-size:.62rem;font-weight:700;
}
.sb-reg-status.ok  {background:rgba(16,185,129,.18);color:#6ee7b7;}
.sb-reg-status.warn{background:rgba(245,158,11,.18);color:#fde68a;}
.sb-reg-status svg{width:10px;height:10px;}

/* Accordion */
.sb-group{border-bottom:1px solid var(--border-l);}
.sb-group-btn{
  display:flex;align-items:center;justify-content:space-between;
  padding:.65rem 1rem;width:100%;text-align:left;
  background:none;font-size:.73rem;font-weight:700;
  color:var(--navy);
  letter-spacing:.04em;text-transform:uppercase;
  transition:background .12s;
  cursor:pointer;
}
.sb-group-btn:hover{background:#f7fafc;}
.sb-group-btn.open{background:#eef2ff;color:#3730a3;}
.sb-group-left{display:flex;align-items:center;gap:.5rem;}
.sb-group-left svg{width:13px;height:13px;flex-shrink:0;}
.sb-chevron{width:12px;height:12px;transition:transform .2s;flex-shrink:0;color:var(--text-s);}
.sb-chevron.rot{transform:rotate(90deg);}

/* Nav items */
.sb-items{display:none;background:#fafbff;}
.sb-items.open{display:block;}
.sb-link{
  display:flex;align-items:center;gap:.5rem;
  padding:.52rem 1rem .52rem 1.85rem;
  font-size:.78rem;color:var(--text-m);
  border-left:3px solid transparent;
  transition:all .12s;
}
.sb-link::before{content:'▸';font-size:.58rem;color:#cbd5e1;margin-right:1px;}
.sb-link:hover{background:#eef2ff;color:#3730a3;border-left-color:#6366f1;}
.sb-link.active{background:#e0e7ff;color:#3730a3;font-weight:600;border-left-color:#4f46e5;}

/* Logout */
.sb-footer{margin-top:auto;padding:.75rem;border-top:1px solid var(--border);flex-shrink:0;}
.sb-logout{
  display:flex;align-items:center;justify-content:center;gap:.45rem;
  width:100%;padding:.55rem;border-radius:8px;
  background:#fef2f2;color:#dc2626;
  font-size:.78rem;font-weight:600;
  border:1px solid #fecaca;
  transition:background .12s;
  text-decoration:none;
}
.sb-logout:hover{background:#fee2e2;}
.sb-logout svg{width:14px;height:14px;}

/* ═══════════ MAIN ═══════════ */
.main{
  flex:1;
  margin-left:var(--sidebar);
  padding:1.25rem 1.5rem 2rem;
  min-width:0;
}

/* ── Welcome banner ── */
.welcome-banner{
  background:linear-gradient(135deg,var(--navy) 0%,#2563eb 100%);
  border-radius:10px;
  padding:1.1rem 1.4rem;
  margin-bottom:1.1rem;
  display:flex;align-items:center;justify-content:space-between;
  gap:1rem;flex-wrap:wrap;
  box-shadow:0 4px 16px rgba(26,58,107,.25);
}
.wb-text h2{font-size:1rem;font-weight:700;color:#fff;margin-bottom:1px;}
.wb-text p{font-size:.76rem;color:rgba(255,255,255,.6);}
.wb-badge{
  background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);
  border-radius:8px;padding:.45rem .9rem;text-align:center;
}
.wb-badge .val{font-size:.92rem;font-weight:700;color:var(--gold-l);}
.wb-badge .lbl{font-size:.62rem;color:rgba(255,255,255,.45);}

/* ── Alert banners ── */
.alert{
  border-radius:8px;padding:.65rem 1rem;margin-bottom:.85rem;
  display:flex;align-items:center;justify-content:space-between;
  gap:.75rem;flex-wrap:wrap;
  border-left:4px solid;font-size:.8rem;
}
.alert.warn  {background:#fef3c7;border-color:#f59e0b;}
.alert.danger{background:#fee2e2;border-color:#dc2626;}
.alert.ok    {background:#d1fae5;border-color:#10b981;}
.alert-msg   {display:flex;align-items:center;gap:.5rem;}
.alert-msg svg{width:16px;height:16px;flex-shrink:0;}
.alert.warn   .alert-msg{color:#92400e;}
.alert.danger .alert-msg{color:#991b1b;}
.alert.ok     .alert-msg{color:#065f46;}
.alert-link{
  padding:.35rem .85rem;border-radius:6px;
  font-size:.74rem;font-weight:700;white-space:nowrap;
  transition:opacity .15s;
}
.alert-link:hover{opacity:.85;}
.alert.warn  .alert-link{background:#f59e0b;color:#fff;}
.alert.danger .alert-link{background:#dc2626;color:#fff;}

/* ── Stats row ── */
.stats-row{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:.85rem;
  margin-bottom:1.1rem;
}
.stat-card{
  background:var(--white);border:1px solid var(--border);
  border-radius:10px;padding:.9rem 1rem;
  display:flex;align-items:center;gap:.75rem;
  box-shadow:0 1px 3px rgba(0,0,0,.04);
  transition:transform .15s,box-shadow .15s;
  text-decoration:none;color:inherit;
}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 4px 14px rgba(0,0,0,.09);}
.stat-icon{
  width:40px;height:40px;border-radius:10px;
  display:grid;place-items:center;flex-shrink:0;
}
.stat-icon svg{width:20px;height:20px;}
.stat-icon.blue  {background:#dbeafe;color:#1d4ed8;}
.stat-icon.amber {background:#fef3c7;color:#b45309;}
.stat-icon.green {background:#d1fae5;color:#065f46;}
.stat-icon.purple{background:#ede9fe;color:#6d28d9;}
.stat-val{font-size:1.35rem;font-weight:800;color:var(--navy);line-height:1;}
.stat-lbl{font-size:.68rem;color:var(--text-s);margin-top:2px;}

/* ── Section heading (UNIMA gold underline style) ── */
.sec-head{
  font-size:.85rem;font-weight:700;color:var(--navy);
  margin-bottom:.75rem;padding-bottom:.4rem;
  border-bottom:2px solid var(--gold);
  display:flex;align-items:center;justify-content:space-between;
}
.sec-head-left{display:flex;align-items:center;gap:.4rem;}
.sec-head-left svg{width:15px;height:15px;color:var(--gold);}

/* ── Quick links grid ── */
.quick-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(118px,1fr));
  gap:.7rem;
  margin-bottom:1.1rem;
}
.quick-card{
  background:var(--white);border:1px solid var(--border);
  border-radius:10px;padding:.85rem .65rem;
  display:flex;flex-direction:column;align-items:center;
  text-align:center;gap:.45rem;
  transition:all .15s;
  box-shadow:0 1px 3px rgba(0,0,0,.04);
}
.quick-card:hover{border-color:var(--navy);background:#eff6ff;transform:translateY(-2px);box-shadow:0 4px 12px rgba(26,58,107,.1);}
.qc-icon{
  width:36px;height:36px;border-radius:9px;
  display:grid;place-items:center;
}
.qc-icon svg{width:18px;height:18px;}
.qc-icon.blue  {background:#dbeafe;color:#1d4ed8;}
.qc-icon.purple{background:#ede9fe;color:#7c3aed;}
.qc-icon.teal  {background:#ccfbf1;color:#0f766e;}
.qc-icon.indigo{background:#e0e7ff;color:#4338ca;}
.qc-icon.green {background:#d1fae5;color:#065f46;}
.qc-icon.amber {background:#fef3c7;color:#b45309;}
.qc-icon.slate {background:#f1f5f9;color:#475569;}
.qc-icon.red   {background:#fee2e2;color:#dc2626;}
.qc-label{font-size:.7rem;font-weight:600;color:var(--navy);line-height:1.3;}

/* ── Payment/Info cards (UNIMA-style 3-col) ── */
.info-cards{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:.85rem;
  margin-bottom:1.1rem;
}
.info-card{
  background:var(--white);border:1px solid var(--border);
  border-radius:10px;overflow:hidden;
  box-shadow:0 1px 3px rgba(0,0,0,.04);
}
.ic-head{
  background:var(--navy);color:#fff;
  padding:.55rem .9rem;
  font-size:.74rem;font-weight:700;
  display:flex;align-items:center;gap:.4rem;
}
.ic-head.gold{background:var(--gold);color:var(--navy);}
.ic-head svg{width:14px;height:14px;}
.ic-body{padding:.85rem .9rem;}
.ic-row{
  display:flex;align-items:center;gap:.5rem;
  padding:.32rem 0;border-bottom:1px solid var(--border-l);
  font-size:.76rem;color:var(--text-m);
}
.ic-row:last-child{border-bottom:none;}
.ic-num{
  width:18px;height:18px;border-radius:50%;
  background:var(--navy);color:#fff;
  font-size:.58rem;font-weight:800;
  display:grid;place-items:center;flex-shrink:0;
}
.ic-num.gold{background:var(--gold);color:var(--navy);}

/* ── Notice board ── */
.notice-board{
  background:var(--white);border:1px solid var(--border);
  border-radius:10px;overflow:hidden;
  box-shadow:0 1px 3px rgba(0,0,0,.04);
  margin-bottom:1.1rem;
}
.notice-row{
  display:flex;align-items:flex-start;gap:.65rem;
  padding:.8rem 1rem;border-bottom:1px solid var(--border-l);
  font-size:.78rem;color:var(--text-m);line-height:1.55;
}
.notice-row:last-child{border-bottom:none;}
.notice-dot{
  width:26px;height:26px;border-radius:50%;
  background:#fef3c7;color:#d97706;
  display:grid;place-items:center;flex-shrink:0;margin-top:1px;
}
.notice-dot svg{width:13px;height:13px;}
.notice-title{font-weight:700;color:var(--navy);font-size:.8rem;margin-bottom:1px;}
.notice-date{font-size:.66rem;color:#94a3b8;margin-top:2px;}
.notice-empty{padding:1.5rem;text-align:center;color:var(--text-s);font-size:.8rem;}

/* ── Footer ── */
.portal-footer{
  background:var(--navy);color:#94a3b8;
  text-align:center;padding:.75rem;font-size:.7rem;
}
.portal-footer a{color:#93c5fd;text-decoration:none;}
.portal-footer a:hover{text-decoration:underline;}

/* ── Overlay (mobile) ── */
.sidebar-overlay{
  display:none;position:fixed;inset:0;z-index:190;
  background:rgba(0,0,0,.45);
}

/* ═══════════ RESPONSIVE ═══════════ */
@media(max-width:1024px){
  .stats-row{grid-template-columns:repeat(2,1fr);}
  .info-cards{grid-template-columns:1fr 1fr;}
}
@media(max-width:768px){
  :root{--sidebar:260px;}
  .sidebar{transform:translateX(-100%);}
  .sidebar.open{transform:translateX(0);}
  .sidebar-overlay.open{display:block;}
  .tb-toggle{width:56px;}
  .tb-brand-sub,.tb-username{display:none;}
  .breadcrumb-bar{padding-left:1rem;}
  .main{margin-left:0;padding:1rem;}
  .stats-row{grid-template-columns:1fr 1fr;}
  .info-cards{grid-template-columns:1fr;}
  .quick-grid{grid-template-columns:repeat(3,1fr);}
  .welcome-banner{padding:.9rem 1rem;}
}
@media(max-width:480px){
  .stats-row{grid-template-columns:1fr;}
  .quick-grid{grid-template-columns:repeat(2,1fr);}
}
</style>
</head>
<body>

<!-- ═══ TOPBAR ═══ -->
<header class="topbar">
  <div class="tb-left">
    <button class="tb-toggle" id="sb-toggle" aria-label="Toggle sidebar">
      <div class="tb-toggle-icon"><span></span><span></span><span></span></div>
      <div class="tb-brand-wrap">
        <div class="tb-logo">CS</div>
        <div>
          <div class="tb-brand-name">Chigoneka School</div>
          <div class="tb-brand-sub">Student Portal</div>
        </div>
      </div>
    </button>
  </div>
  <div class="tb-right">
    <a href="notifications.php" class="tb-icon-btn" title="Notifications">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
      </svg>
    </a>
    <a href="support.php" class="tb-icon-btn" title="Help">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    </a>
    <div class="tb-user">
      <div class="tb-avatar"><?= e($initials) ?></div>
      <span class="tb-username"><?= $full_name ?></span>
    </div>
  </div>
</header>

<!-- ═══ GOLD BREADCRUMB ═══ -->
<div class="breadcrumb-bar">
  <span>Student Portal</span>
  <span class="sep">›</span>
  <span>Home</span>
</div>

<div class="sidebar-overlay" id="sb-overlay"></div>

<!-- ═══ LAYOUT ═══ -->
<div class="layout">

<!-- ═══ SIDEBAR ═══ -->
<aside class="sidebar" id="sidebar">

  <!-- Profile -->
  <div class="sb-profile">
    <div class="sb-avatar"><?= e($initials) ?></div>
    <div class="sb-name"><?= $full_name ?></div>
    <div class="sb-meta">
      <?= $has_class ? e($class_level).' &nbsp;·&nbsp; ' : '' ?>
      <?= $currentYear ?> Academic Year
    </div>
    <?php if ($has_class && $has_subjects): ?>
      <span class="sb-reg-status ok">
        <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Fully Registered
      </span>
    <?php else: ?>
      <span class="sb-reg-status warn">
        <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01"/></svg>
        Registration Pending
      </span>
    <?php endif; ?>
  </div>

  <!-- Accordion nav -->
  <?php foreach ($nav as $gi => $group): ?>
  <div class="sb-group">
    <button class="sb-group-btn <?= $group['id']===$activeGroupId?'open':'' ?>"
            data-target="grp-<?= e($group['id']) ?>" type="button">
      <span class="sb-group-left">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <?php
          $gicons=['academics'=>'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253','registration'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2','personal'=>'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'];
          echo '<path stroke-linecap="round" stroke-linejoin="round" d="'.e($gicons[$group['id']] ?? $gicons['academics']).'"/>';
          ?>
        </svg>
        <?= e($group['label']) ?>
      </span>
      <svg class="sb-chevron <?= $group['id']===$activeGroupId?'rot':'' ?>" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
      </svg>
    </button>
    <div class="sb-items <?= $group['id']===$activeGroupId?'open':'' ?>" id="grp-<?= e($group['id']) ?>">
      <?php foreach ($group['items'] as $item): ?>
        <a href="<?= e($item['href']) ?>"
           class="sb-link <?= $item['href']===$current_page?'active':'' ?>">
          <?= e($item['label']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Logout -->
  <div class="sb-footer">
    <a href="logout.php" class="sb-logout">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
      </svg>
      Sign Out
    </a>
  </div>
</aside><!-- /sidebar -->

<!-- ═══ MAIN CONTENT ═══ -->
<main class="main">

  <!-- Welcome banner -->
  <div class="welcome-banner">
    <div class="wb-text">
      <h2><?= e($greeting) ?>, <?= e($user['first_name']) ?>!</h2>
      <p>Welcome to your student portal &nbsp;—&nbsp; <?= date('l, d F Y') ?></p>
    </div>
    <?php if ($class_level): ?>
    <div class="wb-badge">
      <div class="val"><?= e($class_level) ?></div>
      <div class="lbl"><?= $currentYear ?> Academic Year</div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Alert banners -->
  <?php if (!$has_class): ?>
  <div class="alert warn">
    <div class="alert-msg">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
      You have not selected your class for <?= $currentYear ?>. Please select your class to continue.
    </div>
    <a href="student_select_class.php" class="alert-link">Select Class →</a>
  </div>
  <?php elseif (!$has_subjects): ?>
  <div class="alert warn">
    <div class="alert-msg">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
      Enrolled in <strong>&nbsp;<?= e($class_level) ?>&nbsp;</strong> — subjects not yet registered for <?= $currentYear ?>.
    </div>
    <a href="student_select_subjects.php" class="alert-link">Select Subjects →</a>
  </div>
  <?php else: ?>
  <div class="alert ok">
    <div class="alert-msg">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      Fully registered for <?= e($class_level) ?> — <?= $currentYear ?> academic year.
    </div>
  </div>
  <?php endif; ?>

  <!-- Stats row -->
  <div class="stats-row">
    <a href="my_courses.php" class="stat-card">
      <div class="stat-icon blue">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
      </div>
      <div><div class="stat-val"><?= $course_count ?></div><div class="stat-lbl">Enrolled Courses</div></div>
    </a>
    <a href="student_results.php" class="stat-card">
      <div class="stat-icon amber">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      </div>
      <div><div class="stat-val"><?= $pending_count ?></div><div class="stat-lbl">Pending Results</div></div>
    </a>
    <a href="<?= $has_class?'my_courses.php':'student_select_class.php' ?>" class="stat-card">
      <div class="stat-icon <?= $has_subjects?'green':'purple' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
      </div>
      <div>
        <div class="stat-val"><?= $has_subjects?'Done':'Pending' ?></div>
        <div class="stat-lbl">Registration Status</div>
      </div>
    </a>
  </div>

  <!-- Quick access -->
  <div class="sec-head">
    <div class="sec-head-left">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      Quick Access
    </div>
  </div>
  <div class="quick-grid">
    <?php
    $qicons=[
      'blue'  =>'M12 14l9-5-9-5-9 5 9 5z',
      'purple'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
      'teal'  =>'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
      'indigo'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
      'green' =>'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
      'amber' =>'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
      'slate' =>'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
      'red'   =>'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    ];
    foreach($quick as $ql): ?>
    <a href="<?= e($ql['href']) ?>" class="quick-card">
      <div class="qc-icon <?= e($ql['color']) ?>">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="<?= e($qicons[$ql['color']] ?? $qicons['blue']) ?>"/>
        </svg>
      </div>
      <span class="qc-label"><?= e($ql['label']) ?></span>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Info cards (UNIMA payment-style) -->
  <div class="sec-head">
    <div class="sec-head-left">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      Portal Information
    </div>
  </div>
  <div class="info-cards">
    <div class="info-card">
      <div class="ic-head">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/></svg>
        Academic Registration
      </div>
      <div class="ic-body">
        <div class="ic-row"><span class="ic-num">1</span> Log in to the student portal</div>
        <div class="ic-row"><span class="ic-num">2</span> Select your Form level</div>
        <div class="ic-row"><span class="ic-num">3</span> Choose compulsory & elective subjects</div>
        <div class="ic-row"><span class="ic-num">4</span> Confirm and submit registration</div>
      </div>
    </div>
    <div class="info-card">
      <div class="ic-head gold">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        Exam & Results
      </div>
      <div class="ic-body">
        <div class="ic-row"><span class="ic-num gold">1</span> Complete all coursework assessments</div>
        <div class="ic-row"><span class="ic-num gold">2</span> Sit end-of-term examinations</div>
        <div class="ic-row"><span class="ic-num gold">3</span> Results published on the portal</div>
        <div class="ic-row"><span class="ic-num gold">4</span> MSCE awarded at Form 4 completion</div>
      </div>
    </div>
    <div class="info-card">
      <div class="ic-head">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        Help & Support
      </div>
      <div class="ic-body">
        <div class="ic-row"><span class="ic-num">1</span> <a href="support.php" style="color:var(--navy-l);font-weight:600;">Visit the Help & Support page</a></div>
        <div class="ic-row"><span class="ic-num">2</span> Contact your class teacher</div>
        <div class="ic-row"><span class="ic-num">3</span> Email: <a href="mailto:portal@chigoneka.ac.mw" style="color:var(--navy-l);">portal@chigoneka.ac.mw</a></div>
        <div class="ic-row"><span class="ic-num">4</span> Phone: +265 1 000 000</div>
      </div>
    </div>
  </div>

  <!-- Notice board -->
  <div class="sec-head">
    <div class="sec-head-left">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
      Notice Board
    </div>
  </div>
  <div class="notice-board">
    <?php if (empty($notices)): ?>
      <div class="notice-empty">No notices at this time. Check back later.</div>
    <?php else: ?>
      <?php foreach ($notices as $n): ?>
      <div class="notice-row">
        <div class="notice-dot">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        </div>
        <div>
          <div class="notice-title"><?= e($n['title']) ?></div>
          <div><?= e($n['body']) ?></div>
          <div class="notice-date"><?= date('d M Y', strtotime($n['created_at'])) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</main>
</div><!-- /.layout -->

<footer class="portal-footer">
  &copy; <?= date('Y') ?> Chigoneka School &nbsp;·&nbsp;
  <a href="support.php">Support</a> &nbsp;·&nbsp;
  <a href="policies.php">Policies</a> &nbsp;·&nbsp;
  <a href="academic_calendar.php">Academic Calendar</a>
</footer>

<script>
/* ── Sidebar toggle ── */
var sidebar  = document.getElementById('sidebar');
var overlay  = document.getElementById('sb-overlay');
var toggleBtn = document.getElementById('sb-toggle');
var sidebarOpen = window.innerWidth >= 768;

function setSidebar(open){
  sidebarOpen = open;
  if(open){ sidebar.classList.remove('open'); overlay.classList.remove('open'); }
  // On mobile open = add class; on desktop sidebar is always visible via margin-left
}

toggleBtn.addEventListener('click', function(){
  if(window.innerWidth < 768){
    var isOpen = sidebar.classList.contains('open');
    sidebar.classList.toggle('open', !isOpen);
    overlay.classList.toggle('open', !isOpen);
  }
  // On desktop the sidebar is always visible; toggle could collapse it (optional)
});

overlay.addEventListener('click', function(){
  sidebar.classList.remove('open');
  overlay.classList.remove('open');
});

document.addEventListener('keydown', function(e){
  if(e.key === 'Escape'){ sidebar.classList.remove('open'); overlay.classList.remove('open'); }
});

/* ── Accordion ── */
document.querySelectorAll('.sb-group-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    var targetId = btn.getAttribute('data-target');
    var panel    = document.getElementById(targetId);
    var chevron  = btn.querySelector('.sb-chevron');
    var isOpen   = panel.classList.contains('open');

    // Close all
    document.querySelectorAll('.sb-items').forEach(function(p){ p.classList.remove('open'); });
    document.querySelectorAll('.sb-group-btn').forEach(function(b){
      b.classList.remove('open');
      b.querySelector('.sb-chevron').classList.remove('rot');
    });

    // Open clicked if it was closed
    if(!isOpen){
      panel.classList.add('open');
      btn.classList.add('open');
      chevron.classList.add('rot');
    }
  });
});
</script>
</body>
</html>