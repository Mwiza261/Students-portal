<?php
/* ──────────────────────────────────────────────
   Chigoneka School – Home Page
   Routes: index.php / Home.php / /
   ────────────────────────────────────────────── */

$socialLinks = [
    ['label' => 'Twitter',   'href' => '#', 'icon' => 'M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.7 5.5 4.3 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z'],
    ['label' => 'Facebook',  'href' => '#', 'icon' => 'M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z'],
    ['label' => 'Instagram', 'href' => '#', 'icon' => 'M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z M4 6a2 2 0 100-4 2 2 0 000 4z'],
    ['label' => 'LinkedIn',  'href' => '#', 'icon' => 'M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-4 0v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z M4 6a2 2 0 100-4 2 2 0 000 4z'],
];

$utilityLinks = [];

$navLinks = [
    ['label' => 'Home',       'href' => 'index.php'],
    ['label' => 'About',      'href' => 'about.php'],
    ['label' => 'Contact',    'href' => 'Contact.php'],
];

$heroSlides = [
    [
        'title'      => 'A Community for Learning & Growth',
        'subtitle'   => 'Chigoneka School connects students and staff through smart digital portals built for Malawi.',
        'cta'        => 'Student Portal',
        'ctaLink'    => 'Login.php',
        'bg'         => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=1600&q=80',
        'accent'     => '#facc15',
    ],
    [
        'title'      => 'Staff Resources Built for Efficiency',
        'subtitle'   => 'Manage schedules, grades, and school news all in one powerful staff dashboard.',
        'cta'        => 'Staff Portal',
        'ctaLink'    => 'StaffLogin.php',
        'bg'         => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1600&q=80',
        'accent'     => '#38bdf8',
    ],
    [
        'title'      => 'Digital Learning for Every Student',
        'subtitle'   => 'Access courses, assignments, and progress tracking from anywhere in Malawi.',
        'cta'        => 'Student Login',
        'ctaLink'    => 'Login.php',
        'bg'         => 'https://images.unsplash.com/photo-1494438639946-1ebd1d20bf85?auto=format&fit=crop&w=1600&q=80',
        'accent'     => '#a3e635',
    ],
];

$features = [
    [
        'title' => 'Student Portal',
        'desc'  => 'View timetables, assignments, grades, and campus updates in one student-friendly dashboard.',
        'href'  => 'Login.php',
        'icon'  => 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z',
        'color' => '#6366f1',
        'bg'    => 'rgba(99,102,241,0.12)',
    ],
    [
        'title' => 'Staff Portal',
        'desc'  => 'Streamline administration, enter grades, manage classes, and communicate with students.',
        'href'  => 'StaffLogin.php',
        'icon'  => 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2 M23 21v-2a4 4 0 00-3-3.87 M16 3.13a4 4 0 010 7.75 M9 7a4 4 0 100 8 4 4 0 000-8z',
        'color' => '#38bdf8',
        'bg'    => 'rgba(56,189,248,0.12)',
    ],
    [
        'title' => 'E-Learning',
        'desc'  => 'Join interactive lessons, submit coursework, and access learning resources wherever you are.',
        'href'  => 'Login.php',
        'icon'  => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
        'color' => '#a3e635',
        'bg'    => 'rgba(163,230,53,0.12)',
    ],
    [
        'title' => 'Library',
        'desc'  => 'Search materials, browse library hours, and access digital archives for research support.',
        'href'  => 'library.php',
        'icon'  => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        'color' => '#fb923c',
        'bg'    => 'rgba(251,146,60,0.12)',
    ],
];

$stats = [
    ['value' => '1,200+', 'label' => 'Enrolled Students'],
    ['value' => '80+',    'label' => 'Teaching Staff'],
    ['value' => '30+',    'label' => 'Academic Programs'],
    ['value' => '15+',    'label' => 'Years of Excellence'],
];

$footerLinks = [
    ['label' => 'Privacy Policy',  'href' => 'privacy.php'],
    ['label' => 'Terms of Service','href' => 'terms.php'],
    ['label' => 'Accessibility',   'href' => 'accessibility.php'],
];

/* ── helpers ── */
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

function isActive(string $href, string $current): bool {
    $base = basename(parse_url($current, PHP_URL_PATH));
    if ($base === '' || $base === 'index.php') $base = 'index.php';
    return $href === $base;
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitle = 'Chigoneka School';
if ($currentPage == 'about.php') $pageTitle = 'About Us | Chigoneka School';
if ($currentPage == 'Contact.php') $pageTitle = 'Contact Us | Chigoneka School';
if ($currentPage == 'register.php') $pageTitle = 'Register | Chigoneka School';
if ($currentPage == 'Login.php') $pageTitle = 'Student Login | Chigoneka School';
if ($currentPage == 'StaffLogin.php') $pageTitle = 'Staff Login | Chigoneka School';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $pageTitle; ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg:      #04070f;
      --surface: #0c1120;
      --border:  rgba(148,163,184,.10);
      --text:    #e2e8f0;
      --muted:   #7a8da8;
      --accent:  #facc15;
      --indigo:  #6366f1;
      font-size: 16px;
    }
    html, body { min-height: 100%; background: var(--bg); color: var(--text); scroll-behavior: smooth; }
    body { font-family: 'DM Sans', system-ui, sans-serif; line-height: 1.7; }
    a { color: inherit; text-decoration: none; }
    button { font: inherit; cursor: pointer; border: none; background: none; }

    .back-button-container {
      max-width: 1200px;
      margin: 1rem auto 0;
      padding: 0 1.25rem;
    }
    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.6rem 1.2rem;
      background: rgba(255,255,255,0.05);
      border: 1px solid var(--border);
      border-radius: 40px;
      color: var(--muted);
      font-size: 0.85rem;
      font-weight: 500;
      transition: all 0.2s ease;
      cursor: pointer;
    }
    .back-btn:hover {
      background: rgba(99,102,241,0.15);
      color: var(--accent);
      border-color: rgba(99,102,241,0.3);
      transform: translateX(-3px);
    }
    .back-btn svg { width: 18px; height: 18px; }

    .utility-bar {
      background: #02040e;
      border-bottom: 1px solid var(--border);
      font-size: .78rem;
      color: var(--muted);
    }
    .utility-bar .inner {
      max-width: 1200px; margin: 0 auto;
      padding: .5rem 1.25rem;
      display: flex; align-items: center; justify-content: flex-end;
      gap: 1rem;
    }
    .utility-bar .social { display: flex; gap: .75rem; align-items: center; }
    .utility-bar .social a {
      display: grid; place-items: center;
      width: 26px; height: 26px; border-radius: 6px;
      background: rgba(255,255,255,.04);
      border: 1px solid var(--border);
      transition: background .2s, color .2s;
    }
    .utility-bar .social a:hover { background: var(--indigo); color: #fff; }

    .navbar {
      position: sticky; top: 0; z-index: 100;
      background: rgba(4,7,15,.88);
      backdrop-filter: blur(20px) saturate(180%);
      border-bottom: 1px solid var(--border);
    }
    .navbar .inner {
      max-width: 1200px; margin: 0 auto;
      padding: .85rem 1.25rem;
      display: flex; align-items: center; justify-content: space-between; gap: 1.5rem;
    }
    .brand { display: flex; align-items: center; gap: .9rem; }
    .brand-logo {
      width: 42px; height: 42px; border-radius: 14px; flex-shrink: 0;
      background: linear-gradient(135deg, #4f46e5, #7c3aed);
      display: grid; place-items: center;
      box-shadow: 0 0 0 1px rgba(99,102,241,.4), 0 4px 16px rgba(99,102,241,.3);
    }
    .brand-logo svg { width: 22px; height: 22px; color: #fff; }
    .brand-name { font-family: 'Playfair Display', serif; font-size: 1rem; font-weight: 700; color: #fff; line-height: 1.1; }
    .brand-sub  { font-size: .72rem; color: var(--muted); }

    .nav-links { display: none; align-items: center; gap: .1rem; }
    .nav-link {
      padding: .65rem .95rem; border-radius: 10px;
      font-size: .875rem; font-weight: 500; color: var(--muted);
      position: relative; transition: color .2s, background .2s;
    }
    .nav-link::after {
      content: ''; position: absolute; bottom: 4px; left: 50%; transform: translateX(-50%) scaleX(0);
      width: 16px; height: 2px; background: var(--accent); border-radius: 2px;
      transition: transform .2s;
    }
    .nav-link:hover { color: #fff; background: rgba(255,255,255,.04); }
    .nav-link.active { color: var(--accent); }
    .nav-link.active::after { transform: translateX(-50%) scaleX(1); }

    .nav-ctas { display: none; align-items: center; gap: .6rem; }
    .btn-ghost {
      padding: .6rem 1.1rem; border-radius: 10px;
      border: 1px solid var(--border);
      font-size: .82rem; font-weight: 600; color: var(--text);
      transition: border-color .2s, background .2s;
    }
    .btn-ghost:hover { border-color: rgba(99,102,241,.5); background: rgba(99,102,241,.08); }
    .btn-solid {
      padding: .6rem 1.2rem; border-radius: 10px;
      background: var(--indigo); color: #fff;
      font-size: .82rem; font-weight: 700;
      transition: background .2s, transform .15s;
      box-shadow: 0 0 0 1px rgba(99,102,241,.4);
    }
    .btn-solid:hover { background: #4f46e5; transform: translateY(-1px); }

    .hamburger {
      width: 38px; height: 38px; border-radius: 10px;
      display: grid; place-items: center;
      border: 1px solid var(--border);
      background: rgba(255,255,255,.04);
      color: var(--muted); transition: color .2s, background .2s;
    }
    .hamburger:hover { color: #fff; background: rgba(255,255,255,.08); }

    #mobile-menu {
      display: none;
      background: rgba(4,7,15,.97);
      border-top: 1px solid var(--border);
      padding: 1rem 1.25rem 1.5rem;
    }
    #mobile-menu .m-link {
      display: block; padding: .8rem 0;
      border-bottom: 1px solid var(--border);
      color: var(--muted); font-weight: 500; font-size: .92rem;
      transition: color .2s;
    }
    #mobile-menu .m-link:last-child { border-bottom: none; }
    #mobile-menu .m-link:hover, #mobile-menu .m-link.active { color: var(--accent); }
    #mobile-menu .m-ctas { display: flex; flex-direction: column; gap: .6rem; margin-top: 1rem; }
    #mobile-menu .btn-solid, #mobile-menu .btn-ghost { width: 100%; text-align: center; display: block; padding: .85rem; }

    .hero {
      position: relative;
      height: 92vh; min-height: 540px; max-height: 900px;
      overflow: hidden;
    }
    .slide {
      position: absolute; inset: 0;
      background-size: cover; background-position: center center;
      opacity: 0;
      transition: opacity .8s cubic-bezier(.4,0,.2,1);
    }
    .slide.active { opacity: 1; }
    .slide-img {
      position: absolute; inset: -6%;
      background-size: cover; background-position: center center;
      animation: none;
    }
    .slide.active .slide-img { animation: kenburns 8s ease-out forwards; }
    @keyframes kenburns {
      0%   { transform: scale(1.08) translate(1%, 0.5%); }
      100% { transform: scale(1.0)  translate(0%, 0%);   }
    }
    .slide-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(105deg, rgba(4,7,15,.88) 0%, rgba(4,7,15,.55) 45%, rgba(4,7,15,.15) 100%);
    }
    .hero-body {
      position: relative; z-index: 4;
      height: 100%;
      display: flex; flex-direction: column; justify-content: center;
      max-width: 1200px; margin: 0 auto;
      padding: 2rem 1.5rem;
    }
    .hero-eyebrow {
      display: inline-flex; align-items: center; gap: .5rem;
      padding: .45rem .9rem; border-radius: 999px;
      background: rgba(250,204,21,.12);
      border: 1px solid rgba(250,204,21,.3);
      color: var(--accent);
      font-size: .72rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
      margin-bottom: 1.4rem;
      width: fit-content;
      opacity: 0; transform: translateY(12px);
      animation: fadeUp .5s .1s ease forwards;
    }
    .hero-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(2.4rem, 4.5vw, 5rem);
      font-weight: 900; line-height: 1.04;
      color: #fff; max-width: 700px;
      margin-bottom: 1.25rem;
      opacity: 0; transform: translateY(14px);
      animation: fadeUp .55s .2s ease forwards;
    }
    .hero-text {
      max-width: 580px; color: rgba(226,232,240,.82);
      font-size: 1.05rem; line-height: 1.8;
      margin-bottom: 2.25rem;
      opacity: 0; transform: translateY(14px);
      animation: fadeUp .55s .32s ease forwards;
    }
    .hero-actions {
      display: flex; flex-wrap: wrap; gap: .9rem;
      opacity: 0; transform: translateY(12px);
      animation: fadeUp .55s .42s ease forwards;
    }
    .hero-btn-primary {
      padding: .9rem 1.8rem; border-radius: 12px;
      background: var(--accent); color: #0f0a00;
      font-weight: 700; font-size: .92rem;
      transition: transform .2s, box-shadow .2s;
      box-shadow: 0 6px 30px rgba(250,204,21,.35);
    }
    .hero-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 40px rgba(250,204,21,.45); }
    .hero-btn-secondary {
      padding: .9rem 1.6rem; border-radius: 12px;
      border: 1px solid rgba(255,255,255,.2);
      color: #fff; font-weight: 600; font-size: .92rem;
      backdrop-filter: blur(6px);
      background: rgba(255,255,255,.07);
      transition: background .2s, border-color .2s;
    }
    .hero-btn-secondary:hover { background: rgba(255,255,255,.13); border-color: rgba(255,255,255,.35); }
    @keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }

    .hero-progress { position: absolute; top: 0; left: 0; z-index: 5; height: 3px; background: rgba(255,255,255,.08); width: 100%; }
    .hero-progress-fill { height: 100%; background: var(--accent); width: 0%; transition: width linear; }
    .hero-controls { position: absolute; bottom: 2rem; right: 1.5rem; z-index: 5; display: flex; gap: .6rem; align-items: center; }
    .hero-arrow { width: 44px; height: 44px; border-radius: 12px; background: rgba(0,0,0,.4); color: #fff; border: 1px solid rgba(255,255,255,.15); display: grid; place-items: center; backdrop-filter: blur(8px); transition: background .2s, transform .15s; }
    .hero-arrow:hover { background: rgba(0,0,0,.65); transform: scale(1.05); }
    .hero-dots { position: absolute; bottom: 2.1rem; left: 50%; transform: translateX(-50%); display: flex; gap: .5rem; align-items: center; z-index: 5; }
    .hero-dot { width: 8px; height: 8px; border-radius: 999px; background: rgba(255,255,255,.25); border: none; padding: 0; transition: width .3s, background .3s; }
    .hero-dot.active { width: 28px; background: var(--accent); }
    .hero-counter { position: absolute; bottom: 2.35rem; right: 7.5rem; z-index: 5; color: rgba(255,255,255,.45); font-size: .78rem; font-variant-numeric: tabular-nums; }

    .stats-ribbon { background: var(--surface); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
    .stats-ribbon .inner { max-width: 1200px; margin: 0 auto; padding: 2.25rem 1.25rem; display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
    .stat { text-align: center; }
    .stat-value { font-family: 'Playfair Display', serif; font-size: 2.2rem; font-weight: 900; color: #fff; line-height: 1; }
    .stat-label { font-size: .8rem; color: var(--muted); margin-top: .35rem; }

    .features { padding: 5rem 1.25rem; background: var(--bg); }
    .features .inner { max-width: 1200px; margin: 0 auto; }
    .section-label { font-size: .72rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--indigo); margin-bottom: .75rem; }
    .section-title { font-family: 'Playfair Display', serif; font-size: clamp(1.8rem, 2.5vw, 2.6rem); color: #fff; font-weight: 900; line-height: 1.12; max-width: 540px; margin-bottom: 1rem; }
    .section-sub { color: var(--muted); max-width: 560px; margin-bottom: 3rem; }
    .feature-grid { display: grid; gap: 1.1rem; grid-template-columns: 1fr; }
    .feature-card { display: block; background: var(--surface); border: 1px solid var(--border); border-radius: 20px; padding: 2rem 1.75rem; transition: transform .25s, border-color .25s, box-shadow .25s; position: relative; overflow: hidden; }
    .feature-card::before { content: ''; position: absolute; top: -1px; left: 2rem; height: 2px; width: 40px; background: var(--card-accent, var(--indigo)); border-radius: 2px; transition: width .3s; }
    .feature-card:hover { transform: translateY(-5px); border-color: rgba(99,102,241,.25); box-shadow: 0 24px 60px rgba(0,0,0,.35); }
    .feature-card:hover::before { width: 80px; }
    .feat-icon { width: 50px; height: 50px; border-radius: 14px; display: grid; place-items: center; margin-bottom: 1.25rem; border: 1px solid var(--border); }
    .feat-icon svg { width: 22px; height: 22px; }
    .feat-title { font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: .6rem; }
    .feat-desc { color: var(--muted); font-size: .9rem; line-height: 1.75; }
    .feat-arrow { margin-top: 1.25rem; font-size: .8rem; font-weight: 600; display: flex; align-items: center; gap: .4rem; transition: gap .2s; }
    .feature-card:hover .feat-arrow { gap: .7rem; }

    .footer { background: #020510; border-top: 1px solid var(--border); padding: 4rem 1.25rem 2rem; }
    .footer .inner { max-width: 1200px; margin: 0 auto; }
    .footer-grid { display: grid; gap: 2.5rem; grid-template-columns: 1fr; margin-bottom: 3rem; }
    .footer-brand-name { font-family: 'Playfair Display', serif; font-size: 1.15rem; font-weight: 700; color: #fff; margin-bottom: .5rem; }
    .footer-brand-desc { color: var(--muted); font-size: .88rem; line-height: 1.7; max-width: 280px; }
    .footer-col-title { font-size: .72rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: rgba(255,255,255,.35); margin-bottom: 1rem; }
    .footer-list { list-style: none; display: grid; gap: .7rem; }
    .footer-list a { color: var(--muted); font-size: .88rem; transition: color .2s; }
    .footer-list a:hover { color: var(--accent); }
    .footer-bottom { border-top: 1px solid var(--border); padding-top: 1.5rem; display: flex; flex-direction: column; gap: .75rem; align-items: center; justify-content: space-between; text-align: center; }
    .footer-copy { color: var(--muted); font-size: .82rem; }
    .footer-legal { display: flex; flex-wrap: wrap; justify-content: center; gap: 1.25rem; }
    .footer-legal a { color: var(--muted); font-size: .82rem; transition: color .2s; }
    .footer-legal a:hover { color: var(--accent); }

    @media (min-width: 640px) {
      .stats-ribbon .inner { grid-template-columns: repeat(4, 1fr); }
      .feature-grid { grid-template-columns: repeat(2, 1fr); }
      .footer-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (min-width: 768px) {
      .nav-links, .nav-ctas { display: flex; }
      .hamburger { display: none; }
    }
    @media (min-width: 1024px) {
      .feature-grid { grid-template-columns: repeat(4, 1fr); }
      .footer-grid { grid-template-columns: 1.5fr 1fr 1fr 1fr; }
      .footer-bottom { flex-direction: row; text-align: left; }
    }
  </style>
</head>
<body>

<div class="back-button-container" id="backButtonContainer" style="display: none;">
  <button class="back-btn" onclick="goBack()">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
    </svg>
    Back
  </button>
</div>

<div class="utility-bar">
  <div class="inner">
    <div class="social">
      <?php foreach ($socialLinks as $s): ?>
        <a href="<?= e($s['href']) ?>" aria-label="<?= e($s['label']) ?>" title="<?= e($s['label']) ?>">
          <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="<?= e($s['icon']) ?>"/>
          </svg>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<nav class="navbar" aria-label="Main navigation">
  <div class="inner">
    <a href="index.php" class="brand">
      <div class="brand-logo">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 10v12H2V10"/><path d="M1 4h22l-1 6H2L1 4z"/><path d="M9 22V12h6v10"/>
        </svg>
      </div>
      <div>
        <div class="brand-name">Chigoneka School</div>
        <div class="brand-sub">Student Information Portal</div>
      </div>
    </a>

    <div class="nav-links">
      <?php foreach ($navLinks as $n): ?>
        <a class="nav-link<?= isActive($n['href'], $currentPath) ? ' active' : '' ?>"
           href="<?= e($n['href']) ?>"><?= e($n['label']) ?></a>
      <?php endforeach; ?>
    </div>
    <div class="nav-ctas">
      <a class="btn-ghost" href="Login.php">Student Login</a>
      <a class="btn-solid" href="StaffLogin.php">Staff Portal</a>
    </div>

    <button class="hamburger" id="hamburger" aria-expanded="false" aria-label="Toggle menu">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>
  </div>

  <div id="mobile-menu" aria-hidden="true">
    <?php foreach ($navLinks as $n): ?>
      <a class="m-link<?= isActive($n['href'], $currentPath) ? ' active' : '' ?>"
         href="<?= e($n['href']) ?>"><?= e($n['label']) ?></a>
    <?php endforeach; ?>
    <div class="m-ctas">
      <a class="btn-ghost" href="Login.php">Student Login</a>
      <a class="btn-solid" href="StaffLogin.php">Staff Portal</a>
    </div>
  </div>
</nav>

<?php if (basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == 'Home.php'): ?>
<section class="hero" id="hero">
  <div class="hero-progress"><div class="hero-progress-fill" id="progress-fill"></div></div>

  <?php foreach ($heroSlides as $i => $slide): ?>
    <div class="slide<?= $i === 0 ? ' active' : '' ?>" data-index="<?= $i ?>">
      <div class="slide-img" style="background-image:url('<?= e($slide['bg']) ?>');"></div>
      <div class="slide-overlay"></div>
    </div>
  <?php endforeach; ?>

  <div class="hero-body" id="hero-body">
    <span class="hero-eyebrow">✦ Chigoneka School</span>
    <h1 class="hero-title" id="hero-title"><?= e($heroSlides[0]['title']) ?></h1>
    <p class="hero-text" id="hero-text"><?= e($heroSlides[0]['subtitle']) ?></p>
    <div class="hero-actions">
      <a id="hero-cta" class="hero-btn-primary" href="<?= e($heroSlides[0]['ctaLink']) ?>"><?= e($heroSlides[0]['cta']) ?></a>
      <a class="hero-btn-secondary" href="about.php">About Us →</a>
    </div>
  </div>

  <div class="hero-dots" id="hero-dots">
    <?php foreach ($heroSlides as $i => $slide): ?>
      <button class="hero-dot<?= $i === 0 ? ' active' : '' ?>" data-index="<?= $i ?>" aria-label="Slide <?= $i+1 ?>"></button>
    <?php endforeach; ?>
  </div>

  <div class="hero-counter" id="hero-counter">1 / <?= count($heroSlides) ?></div>

  <div class="hero-controls">
    <button class="hero-arrow" id="prev-btn" aria-label="Previous slide">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
    </button>
    <button class="hero-arrow" id="next-btn" aria-label="Next slide">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
    </button>
  </div>
</section>

<div class="stats-ribbon">
  <div class="inner">
    <?php foreach ($stats as $stat): ?>
      <div class="stat">
        <div class="stat-value"><?= e($stat['value']) ?></div>
        <div class="stat-label"><?= e($stat['label']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<section class="features">
  <div class="inner">
    <div class="section-label">Our Portals</div>
    <h2 class="section-title">Connect with Excellence</h2>
    <p class="section-sub">Everything you need in one place — for students, staff, and school administration.</p>

    <div class="feature-grid">
      <?php foreach ($features as $f): ?>
        <a class="feature-card" href="<?= e($f['href']) ?>" style="--card-accent:<?= e($f['color']) ?>;">
          <div class="feat-icon" style="background:<?= e($f['bg']) ?>; color:<?= e($f['color']) ?>;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <?php foreach (explode(' M', $f['icon']) as $pi => $segment): ?>
                <path d="<?= $pi === 0 ? e($segment) : 'M' . e($segment) ?>"/>
              <?php endforeach; ?>
            </svg>
          </div>
          <div class="feat-title"><?= e($f['title']) ?></div>
          <div class="feat-desc"><?= e($f['desc']) ?></div>
          <div class="feat-arrow" style="color:<?= e($f['color']) ?>;">Learn more <span>→</span></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<footer class="footer">
  <div class="inner">
    <div class="footer-grid">
      <div>
        <div class="footer-brand-name">Chigoneka School</div>
        <p class="footer-brand-desc">Empowering students and staff with seamless access to academic information and resources in Malawi.</p>
      </div>
      <div>
        <div class="footer-col-title">Quick Links</div>
        <ul class="footer-list">
          <?php foreach ($navLinks as $n): ?>
            <li><a href="<?= e($n['href']) ?>"><?= e($n['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <div class="footer-col-title">Portals</div>
        <ul class="footer-list">
          <li><a href="Login.php">Student Portal</a></li>
          <li><a href="StaffLogin.php">Staff Portal</a></li>
          <li><a href="elearning.php">E-Learning</a></li>
          <li><a href="library.php">Library</a></li>
        </ul>
      </div>
      <div>
        <div class="footer-col-title">Contact</div>
        <ul class="footer-list">
          <li><a href="mailto:info@chigoneka.ac.mw">info@chigoneka.ac.mw</a></li>
          <li>+265 1 000 000</li>
          <li>Chigoneka, Malawi</li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p class="footer-copy">&copy; <?= date('Y') ?> Chigoneka School. All rights reserved.</p>
      <div class="footer-legal">
        <?php foreach ($footerLinks as $fl): ?>
          <a href="<?= e($fl['href']) ?>"><?= e($fl['label']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</footer>

<script>
function goBack() {
    if (document.referrer && document.referrer.indexOf(window.location.hostname) !== -1) {
        window.history.back();
    } else {
        window.location.href = 'index.php';
    }
}

const currentPage = window.location.pathname.split('/').pop();
if (currentPage !== 'index.php' && currentPage !== 'Home.php' && currentPage !== '') {
    const backBtnContainer = document.getElementById('backButtonContainer');
    if (backBtnContainer) {
        backBtnContainer.style.display = 'block';
    }
}

<?php if (basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == 'Home.php'): ?>
(function () {
  var SLIDES   = <?= json_encode($heroSlides, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var INTERVAL = 6000;
  var current  = 0;
  var timer    = null;

  var slideEls  = document.querySelectorAll('.slide');
  var dotEls    = document.querySelectorAll('.hero-dot');
  var titleEl   = document.getElementById('hero-title');
  var textEl    = document.getElementById('hero-text');
  var ctaEl     = document.getElementById('hero-cta');
  var counterEl = document.getElementById('hero-counter');
  var fillEl    = document.getElementById('progress-fill');

  function startProgress() {
    if (fillEl) {
      fillEl.style.transition = 'none';
      fillEl.style.width = '0%';
      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          fillEl.style.transition = 'width ' + INTERVAL + 'ms linear';
          fillEl.style.width = '100%';
        });
      });
    }
  }

  function swapContent(index) {
    var s = SLIDES[index];
    [titleEl, textEl, ctaEl].forEach(function (el) {
      if (!el) return;
      el.style.opacity = '0';
      el.style.transform = 'translateY(8px)';
      el.style.transition = 'opacity .35s ease, transform .35s ease';
    });
    setTimeout(function () {
      if (titleEl) titleEl.textContent = s.title;
      if (textEl) textEl.textContent = s.subtitle;
      if (ctaEl) {
        ctaEl.textContent = s.cta;
        ctaEl.href = s.ctaLink;
      }
      if (counterEl) counterEl.textContent = (index + 1) + ' / ' + SLIDES.length;
      [titleEl, textEl, ctaEl].forEach(function (el) {
        if (!el) return;
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
      });
    }, 320);
  }

  function goTo(index) {
    if (index < 0) index = SLIDES.length - 1;
    if (index >= SLIDES.length) index = 0;

    if (slideEls[current]) slideEls[current].classList.remove('active');
    if (dotEls[current]) dotEls[current].classList.remove('active');

    current = index;

    if (slideEls[current]) slideEls[current].classList.add('active');
    if (dotEls[current]) dotEls[current].classList.add('active');

    var imgEl = slideEls[current]?.querySelector('.slide-img');
    if (imgEl) {
      imgEl.style.animation = 'none';
      imgEl.offsetHeight;
      imgEl.style.animation = '';
    }

    swapContent(current);
    startProgress();
    resetAutoplay();
  }

  function resetAutoplay() {
    clearInterval(timer);
    timer = setInterval(function () { goTo(current + 1); }, INTERVAL);
  }

  var prevBtn = document.getElementById('prev-btn');
  var nextBtn = document.getElementById('next-btn');
  if (prevBtn) prevBtn.addEventListener('click', function () { goTo(current - 1); });
  if (nextBtn) nextBtn.addEventListener('click', function () { goTo(current + 1); });

  dotEls.forEach(function (dot) {
    dot.addEventListener('click', function () {
      goTo(Number(dot.getAttribute('data-index')));
    });
  });

  var heroEl = document.getElementById('hero');
  if (heroEl) {
    heroEl.addEventListener('mouseenter', function () { clearInterval(timer); });
    heroEl.addEventListener('mouseleave', function () { resetAutoplay(); startProgress(); });
  }

  goTo(0);
})();
<?php endif; ?>

var hamburger = document.getElementById('hamburger');
var mobileMenu = document.getElementById('mobile-menu');
if (hamburger && mobileMenu) {
  hamburger.addEventListener('click', function () {
    var open = mobileMenu.style.display === 'block';
    mobileMenu.style.display = open ? 'none' : 'block';
    hamburger.setAttribute('aria-expanded', String(!open));
    mobileMenu.setAttribute('aria-hidden', String(open));
  });
}
</script>
</body>
</html>