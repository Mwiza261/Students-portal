<?php
require_once __DIR__ . '/db.php';

function page_count(mysqli $mysqli, string $sql): int
{
    $result = $mysqli->query($sql);
    if ($result && ($row = $result->fetch_row())) {
        return (int) $row[0];
    }

    return 0;
}

$school_name = 'Chigoneka School';
$school_tagline = 'Learning, discipline, and growth for every student.';
$school_intro = 'Chigoneka School is committed to delivering quality education, strong values, and a supportive learning environment for students and staff.';
$school_address = 'Lilongwe, Malawi';
$school_phone = '+265 999 000 111';
$school_email = 'info@chigoneka.edu.mw';

$metrics = [
    'students' => 0,
    'staff' => 0,
    'teachers' => 0,
    'courses' => 0,
    'registrations' => 0,
    'messages' => 0,
];
$latest_notices = [];
$popular_courses = [];
$db_error = null;

try {
    $mysqli = db_connect();

    if ($mysqli->query("SHOW TABLES LIKE 'users'") && $mysqli->query("SHOW TABLES LIKE 'users'")->num_rows > 0) {
        $metrics['students'] = page_count($mysqli, "SELECT COUNT(*) FROM users WHERE role = 'student'");
        $metrics['staff'] = page_count($mysqli, "SELECT COUNT(*) FROM users WHERE role = 'staff'");
        $metrics['teachers'] = page_count($mysqli, "SELECT COUNT(*) FROM users WHERE role = 'teacher'");
    }

    if ($mysqli->query("SHOW TABLES LIKE 'courses'") && $mysqli->query("SHOW TABLES LIKE 'courses'")->num_rows > 0) {
        $metrics['courses'] = page_count($mysqli, 'SELECT COUNT(*) FROM courses');

        $courses_result = $mysqli->query(
            'SELECT course_code, course_name, current_students
             FROM courses
             ORDER BY current_students DESC, course_name ASC
             LIMIT 4'
        );
        if ($courses_result) {
            while ($row = $courses_result->fetch_assoc()) {
                $popular_courses[] = $row;
            }
        }
    }

    if ($mysqli->query("SHOW TABLES LIKE 'student_courses'") && $mysqli->query("SHOW TABLES LIKE 'student_courses'")->num_rows > 0) {
        $metrics['registrations'] = page_count($mysqli, "SELECT COUNT(*) FROM student_courses WHERE status = 'registered'");
    }

    if ($mysqli->query("SHOW TABLES LIKE 'contact_messages'") && $mysqli->query("SHOW TABLES LIKE 'contact_messages'")->num_rows > 0) {
        $metrics['messages'] = page_count($mysqli, "SELECT COUNT(*) FROM contact_messages");
    }

    if ($mysqli->query("SHOW TABLES LIKE 'notices'") && $mysqli->query("SHOW TABLES LIKE 'notices'")->num_rows > 0) {
        $notice_result = $mysqli->query(
            'SELECT title, body, created_at
             FROM notices
             ORDER BY created_at DESC
             LIMIT 3'
        );
        if ($notice_result) {
            while ($row = $notice_result->fetch_assoc()) {
                $latest_notices[] = $row;
            }
        }
    }

    $mysqli->close();
} catch (Throwable $e) {
    $db_error = 'Database content is temporarily unavailable.';
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Chigoneka School</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f0f2f5;
            --navy: #1a3a6b;
            --navy-2: #12295a;
            --navy-3: #2250a0;
            --gold: #c9a84c;
            --gold-2: #e8c86a;
            --surface: #ffffff;
            --surface-soft: #f8fafc;
            --border: #dbe3ef;
            --text: #10233d;
            --muted: #64748b;
            --shadow: 0 20px 50px rgba(15, 35, 61, 0.08);
            --shadow-soft: 0 10px 30px rgba(15, 35, 61, 0.06);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: radial-gradient(circle at top left, rgba(201, 168, 76, 0.14), transparent 26%), linear-gradient(180deg, #f7f9fc 0%, var(--bg) 100%);
            color: var(--text);
            line-height: 1.55;
        }
        a { color: inherit; text-decoration: none; }

        .page-shell {
            max-width: 1180px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        .hero {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-3) 100%);
            color: #fff;
            border-radius: 28px;
            padding: 2rem;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            margin-bottom: 1.4rem;
        }

        .hero::after {
            content: '';
            position: absolute;
            right: -40px;
            top: -40px;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201, 168, 76, 0.18), transparent 70%);
        }

        .hero-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .brand-mark {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-2) 100%);
            color: var(--navy);
            display: grid;
            place-items: center;
            font-weight: 800;
            box-shadow: 0 14px 24px rgba(201, 168, 76, 0.25);
        }

        .brand h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 4vw, 3.1rem);
            line-height: 1.05;
            margin-bottom: 0.35rem;
        }

        .brand p {
            color: rgba(255, 255, 255, 0.78);
            max-width: 680px;
        }

        .hero-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.8rem 1.15rem;
            border-radius: 999px;
            font-weight: 700;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .btn:hover { transform: translateY(-1px); }
        .btn-primary { background: var(--gold); color: var(--navy); }
        .btn-secondary { background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.16); }

        .content-grid {
            display: grid;
            grid-template-columns: 1.45fr 0.95fr;
            gap: 1.4rem;
        }

        .card {
            background: var(--surface);
            border: 1px solid rgba(219, 227, 239, 0.9);
            border-radius: 24px;
            box-shadow: var(--shadow-soft);
            padding: 1.35rem;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.45rem;
            color: var(--navy);
            margin-bottom: 0.9rem;
        }

        .intro {
            color: var(--muted);
            margin-bottom: 1rem;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.9rem;
            margin-top: 1.1rem;
        }

        .metric {
            border-radius: 18px;
            padding: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid var(--border);
        }

        .metric .value {
            font-size: 1.9rem;
            font-weight: 800;
            color: var(--navy);
            line-height: 1;
        }

        .metric .label {
            margin-top: 0.25rem;
            color: var(--muted);
            font-size: 0.88rem;
        }

        .highlight-box {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(26, 58, 107, 0.06), rgba(201, 168, 76, 0.12));
            border: 1px solid rgba(26, 58, 107, 0.08);
        }

        .list {
            list-style: none;
            display: grid;
            gap: 0.7rem;
            margin-top: 0.85rem;
        }

        .list li {
            display: flex;
            gap: 0.7rem;
            align-items: flex-start;
            color: var(--muted);
        }

        .list li::before {
            content: '•';
            color: var(--gold);
            font-size: 1.2rem;
            line-height: 1;
            margin-top: -0.08rem;
        }

        .info-grid {
            display: grid;
            gap: 0.9rem;
        }

        .pill-row {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
            margin-top: 0.9rem;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: #eef2ff;
            color: #3730a3;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .notice {
            border-radius: 18px;
            padding: 0.95rem 1rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .notice h3 {
            font-size: 1rem;
            color: var(--navy);
            margin-bottom: 0.35rem;
        }

        .notice .date {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 0.35rem;
        }

        .split-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.4rem;
            margin-top: 1.4rem;
        }

        .contact-card {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);
            color: #fff;
        }

        .contact-card .section-title,
        .contact-card .intro,
        .contact-card li {
            color: rgba(255, 255, 255, 0.88);
        }

        .contact-card .section-title { color: #fff; }
        .contact-card .pill { background: rgba(255,255,255,0.12); color: #fff; }

        .footer-note {
            text-align: center;
            color: var(--muted);
            font-size: 0.88rem;
            margin: 1.5rem 0 0.5rem;
            padding-top: 1rem;
        }

        .error-box {
            margin-bottom: 1rem;
            padding: 0.9rem 1rem;
            border-radius: 16px;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        @media (max-width: 900px) {
            .content-grid,
            .split-grid,
            .metrics-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .page-shell { padding: 1rem; }
            .hero { padding: 1.4rem; border-radius: 22px; }
            .card { padding: 1.1rem; border-radius: 20px; }
            .hero-actions { width: 100%; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <section class="hero">
            <div class="hero-top">
                <div class="brand">
                    <div class="brand-mark">CS</div>
                    <div>
                        <h1>About <?php echo e($school_name); ?></h1>
                        <p><?php echo e($school_tagline); ?></p>
                    </div>
                </div>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="contact.php">Contact Us</a>
                    <a class="btn btn-secondary" href="index.php">Back to Home</a>
                </div>
            </div>
        </section>

        <?php if ($db_error): ?>
            <div class="error-box"><?php echo e($db_error); ?></div>
        <?php endif; ?>

        <div class="content-grid">
            <section class="card">
                <h2 class="section-title">Our Story</h2>
                <p class="intro"><?php echo e($school_intro); ?></p>
                <p>We support students through academic growth, discipline, and practical learning. The portal is connected to live school data so students, staff, and administrators can see up-to-date information about courses, registrations, and announcements.</p>

                <div class="metrics-grid">
                    <div class="metric">
                        <div class="value"><?php echo (int) $metrics['students']; ?></div>
                        <div class="label">Students</div>
                    </div>
                    <div class="metric">
                        <div class="value"><?php echo (int) $metrics['courses']; ?></div>
                        <div class="label">Courses</div>
                    </div>
                    <div class="metric">
                        <div class="value"><?php echo (int) $metrics['registrations']; ?></div>
                        <div class="label">Active Registrations</div>
                    </div>
                </div>

                <div class="highlight-box">
                    <strong>What drives us</strong>
                    <ul class="list">
                        <li>Quality teaching and strong academic support</li>
                        <li>Clear communication between students, teachers, and staff</li>
                        <li>Real-time school records from the database</li>
                    </ul>
                </div>
            </section>

            <aside class="card info-grid">
                <div>
                    <h2 class="section-title">School Snapshot</h2>
                    <div class="pill-row">
                        <span class="pill"><?php echo (int) $metrics['staff']; ?> Staff</span>
                        <span class="pill"><?php echo (int) $metrics['teachers']; ?> Teachers</span>
                        <span class="pill"><?php echo (int) $metrics['messages']; ?> Messages</span>
                    </div>
                    <ul class="list">
                        <li><strong>Address:</strong> <?php echo e($school_address); ?></li>
                        <li><strong>Phone:</strong> <?php echo e($school_phone); ?></li>
                        <li><strong>Email:</strong> <?php echo e($school_email); ?></li>
                    </ul>
                </div>
            </aside>
        </div>

        <div class="split-grid">
            <section class="card">
                <h2 class="section-title">Latest Notices</h2>
                <?php if (!empty($latest_notices)): ?>
                    <div class="info-grid">
                        <?php foreach ($latest_notices as $notice): ?>
                            <div class="notice">
                                <h3><?php echo e($notice['title']); ?></h3>
                                <p><?php echo e(mb_strimwidth($notice['body'], 0, 180, '...')); ?></p>
                                <div class="date"><?php echo e(date('d M Y', strtotime($notice['created_at']))); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="intro">No notices have been posted yet.</p>
                <?php endif; ?>
            </section>

            <section class="card contact-card">
                <h2 class="section-title">Popular Courses</h2>
                <?php if (!empty($popular_courses)): ?>
                    <ul class="list">
                        <?php foreach ($popular_courses as $course): ?>
                            <li>
                                <span><strong><?php echo e($course['course_code']); ?></strong> - <?php echo e($course['course_name']); ?><?php if (isset($course['current_students'])): ?> (<?php echo (int) $course['current_students']; ?> students)<?php endif; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="intro">Course data will appear here once courses are created in the database.</p>
                <?php endif; ?>

                <div class="pill-row">
                    <span class="pill">Live database</span>
                    <span class="pill">School records</span>
                    <span class="pill">Current notices</span>
                </div>
            </section>
        </div>

        <div class="footer-note">
            Connected to the school database and showing live portal data.
        </div>
    </div>
</body>
</html>
