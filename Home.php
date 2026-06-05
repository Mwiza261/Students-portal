<?php
$socialLinks = [
    ['label' => 'Twitter', 'href' => '#'],
    ['label' => 'Facebook', 'href' => '#'],
    ['label' => 'Instagram', 'href' => '#'],
    ['label' => 'LinkedIn', 'href' => '#'],
];

$utilityLinks = [
    ['label' => 'Admissions', 'href' => '/admissions'],
    ['label' => 'Calendar', 'href' => '/calendar'],
    ['label' => 'Contact', 'href' => '/contact'],
    ['label' => 'Support', 'href' => '/support'],
];

$navLinks = [
    ['label' => 'Home', 'to' => '/'],
    ['label' => 'About', 'to' => '/about'],
    ['label' => 'Programs', 'to' => '/programs'],
    ['label' => 'Admissions', 'to' => '/admissions'],
    ['label' => 'Contact', 'to' => '/contact'],
];

$heroSlides = [
    [
        'title' => 'A community for learning and growth',
        'subtitle' => 'Chigoneka School provides student and staff portals that keep everyone connected.',
        'cta' => 'Register now',
        'ctaLink' => 'register.php',
        'background' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=1500&q=80',
    ],
    [
        'title' => 'Staff resources built for efficiency',
        'subtitle' => 'Manage schedules, reviews, and the latest news in a single online portal.',
        'cta' => 'Visit the Staff Portal',
        'ctaLink' => '/staff-portal',
        'background' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1500&q=80',
    ],
    [
        'title' => 'Digital learning for every student',
        'subtitle' => 'Access courses, assignments, and progress tracking from anywhere.',
        'cta' => 'Start E-Learning',
        'ctaLink' => '/e-learning',
        'background' => 'https://images.unsplash.com/photo-1494438639946-1ebd1d20bf85?auto=format&fit=crop&w=1500&q=80',
    ],
];

$features = [
    [
        'icon' => 'M12 14l9-5-9-5-9 5 9 5z',
        'title' => 'Student Portal',
        'desc' => 'View schedules, assignments, and campus updates in one student-friendly dashboard.',
        'color' => 'feature-blue',
        'href' => 'Login.php',
    ],
    [
        'icon' => 'M5 12h14M12 5l7 7-7 7',
        'title' => 'Staff Portal',
        'desc' => 'Streamline administration tasks and manage classroom workflows online.',
        'color' => 'feature-indigo',
        'href' => 'StaffLogin.php',
    ],
    [
        'icon' => 'M3 7h18M3 13h18M3 19h18',
        'title' => 'E-Learning',
        'desc' => 'Join interactive lessons, submit coursework, and access learning resources wherever you are.',
        'color' => 'feature-emerald',
        'href' => '/e-learning',
    ],
    [
        'icon' => 'M6 3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6a3 3 0 013-3z',
        'title' => 'Library',
        'desc' => 'Search materials, library hours, and digital archives designed to support research.',
        'color' => 'feature-amber',
        'href' => '/library',
    ],
];

$footerLinks = [
    ['label' => 'Privacy Policy', 'href' => '/privacy'],
    ['label' => 'Terms of Service', 'href' => '/terms'],
    ['label' => 'Accessibility', 'href' => '/accessibility'],
];

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

function activeClass($path, $currentPath)
{
    $normalizedCurrent = $currentPath === '' ? '/' : $currentPath;
    if ($path === '/') {
        return in_array($normalizedCurrent, ['/', '/index.php', '/Home.php'], true) ? 'nav-link active' : 'nav-link';
    }

    return $path === $normalizedCurrent ? 'nav-link active' : 'nav-link';
}

function escape($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chigoneka School | Home</title>
    <style>
        :root {
            color-scheme: dark;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body { background: #020617; }
        a { color: inherit; text-decoration: none; }
        button { font: inherit; }
        .utility-bar { background: #02072b; color: #c7d2fe; font-size: 0.8rem; }
        .utility-bar .container { display: flex; justify-content: space-between; align-items: center; gap: 1rem; max-width: 1200px; margin: 0 auto; padding: 0.65rem 1rem; }
        .utility-bar a { color: #a5b4fc; transition: color 0.2s ease; }
        .utility-bar a:hover { color: #facc15; }
        .navbar { position: sticky; top: 0; z-index: 20; background: rgba(15,23,42,0.95); backdrop-filter: blur(14px); border-bottom: 1px solid rgba(148,163,184,0.12); }
        .navbar .container { display: flex; align-items: center; justify-content: space-between; gap: 1rem; max-width: 1200px; margin: 0 auto; padding: 1rem; }
        .brand { display: flex; align-items: center; gap: 0.85rem; }
        .brand-logo { width: 44px; height: 44px; border-radius: 16px; background: linear-gradient(135deg,#6366f1,#8b5cf6); display:grid; place-items:center; }
        .brand-logo svg { width: 24px; height: 24px; color: #fff; }
        .brand-text .title { margin: 0; font-size: 0.95rem; font-weight: 700; color: #fff; }
        .brand-text .subtitle { margin: 0; font-size: 0.78rem; color: #94a3b8; }
        .nav-links { display: none; gap: 0.4rem; align-items: center; flex-wrap: wrap; }
        .nav-link { display: inline-flex; align-items: center; padding: 0.8rem 1rem; border-bottom: 2px solid transparent; color: #cbd5e1; font-size: 0.92rem; transition: color 0.2s ease, border-color 0.2s ease; }
        .nav-link:hover { color: #fff; border-color: rgba(255,255,255,0.12); }
        .nav-link.active { color: #facc15; border-color: #facc15; }
        .portal-button { display: inline-flex; align-items: center; justify-content: center; padding: 0.8rem 1.2rem; border-radius: 999px; background: #4338ca; color: #fff; font-weight: 700; transition: background 0.2s ease; }
        .portal-button:hover { background: #4f46e5; }
        .menu-toggle { display: inline-flex; width: 40px; height: 40px; border-radius: 12px; align-items: center; justify-content: center; border: 1px solid rgba(148,163,184,0.18); background: rgba(148,163,184,0.05); color: #cbd5e1; cursor: pointer; }
        .hero { position: relative; min-height: 84vh; overflow: hidden; }
        .slide { position: absolute; inset: 0; opacity: 0; transition: opacity 0.7s ease; background-size: cover; background-position: center; }
        .slide.active { opacity: 1; }
        .hero::before { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, rgba(15,23,42,0.92), rgba(15,23,42,0.45) 40%, rgba(15,23,42,0.15)); }
        .hero-content { position: relative; z-index: 2; display: flex; flex-direction: column; justify-content: center; min-height: 84vh; padding: 4rem 1.5rem; max-width: 1140px; margin: 0 auto; }
        .eyebrow { display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem; padding: 0.75rem 1rem; border-radius: 999px; background: #fde68a; color: #92400e; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
        .hero-title { margin: 0 0 1rem; font-size: clamp(2.3rem, 3.7vw, 4.75rem); line-height: 1.02; color: #fff; max-width: 720px; }
        .hero-text { margin: 0 0 2rem; max-width: 680px; color: #cbd5e1; font-size: 1.05rem; line-height: 1.8; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 1rem; }
        .hero-button, .hero-button-secondary { padding: 1rem 1.5rem; border-radius: 999px; font-weight: 700; transition: transform 0.2s ease, background 0.2s ease; }
        .hero-button { background: #4f46e5; color: #fff; }
        .hero-button:hover { background: #6366f1; }
        .hero-button-secondary { background: rgba(255,255,255,0.12); color: #fff; border: 1px solid rgba(255,255,255,0.18); }
        .hero-button-secondary:hover { background: rgba(255,255,255,0.18); }
        .hero-controls { position: absolute; inset: auto 1.25rem 1.25rem; display: flex; justify-content: space-between; gap: 0.75rem; z-index: 3; }
        .hero-controls button { width: 46px; height: 46px; border-radius: 50%; border: none; background: rgba(0,0,0,0.35); color: #fff; cursor: pointer; transition: background 0.2s ease; }
        .hero-controls button:hover { background: rgba(0,0,0,0.55); }
        .hero-dots { position: absolute; bottom: 1.25rem; left: 50%; transform: translateX(-50%); display: flex; gap: 0.5rem; z-index: 3; }
        .hero-dot { width: 12px; height: 12px; border-radius: 999px; border: 1px solid rgba(255,255,255,0.6); background: transparent; cursor: pointer; transition: background 0.2s ease, transform 0.2s ease; }
        .hero-dot.active { width: 36px; background: #facc15; border-color: #facc15; }
        .hero-counter { position: absolute; bottom: 1.25rem; right: 1.25rem; color: rgba(255,255,255,0.72); font-size: 0.9rem; z-index: 3; }
        .features { background: #020617; padding: 4rem 1rem 5rem; }
        .features .container { max-width: 1200px; margin: 0 auto; }
        .features-header { text-align: center; margin-bottom: 2rem; }
        .features-header h2 { margin: 0; font-size: 2.05rem; color: #f8fafc; }
        .features-header p { margin: 0.75rem auto 0; max-width: 640px; color: #94a3b8; }
        .feature-grid { display: grid; gap: 1.25rem; grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .feature-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(148,163,184,0.12); padding: 1.8rem; border-radius: 32px; transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease; }
        .feature-card:hover { transform: translateY(-4px); border-color: rgba(99,102,241,0.35); box-shadow: 0 24px 60px rgba(56,189,248,0.08); }
        .feature-icon { width: 52px; height: 52px; border-radius: 24px; display: grid; place-items: center; margin-bottom: 1.2rem; }
        .feature-title { margin: 0 0 0.75rem; font-size: 1.05rem; color: #e2e8f0; }
        .feature-desc { margin: 0; color: #94a3b8; line-height: 1.75; }
        .feature-indigo { background: #ede9fe; color: #5b21b6; }
        .feature-blue { background: #e0e7ff; color: #1d4ed8; }
        .feature-emerald { background: #d1fae5; color: #047857; }
        .feature-amber { background: #ffedd5; color: #c2410c; }
        .footer { background: #020617; color: #cbd5e1; padding: 4rem 1rem 2rem; }
        .footer .container { max-width: 1200px; margin: 0 auto; }
        .footer-grid { display: grid; gap: 2rem; grid-template-columns: 1fr; }
        .footer .section-title { margin-bottom: 1rem; color: #fff; font-size: 0.95rem; letter-spacing: 0.1em; text-transform: uppercase; }
        .footer a { color: #94a3b8; }
        .footer a:hover { color: #facc15; }
        .footer-list { list-style: none; padding: 0; margin: 0; display: grid; gap: 0.8rem; }
        .footer-list li { font-size: 0.95rem; }
        .footer-bottom { border-top: 1px solid rgba(148,163,184,0.14); margin-top: 2rem; padding-top: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem; align-items: center; justify-content: space-between; }
        .footer-bottom p { margin: 0; color: #94a3b8; font-size: 0.85rem; }
        .footer-links { display: flex; flex-wrap: wrap; gap: 1rem; }
        .footer-links a { font-size: 0.85rem; }
        @media (min-width: 768px) {
            .nav-links { display: flex; }
            .menu-toggle { display: none; }
            .feature-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .footer-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .footer-bottom { flex-direction: row; }
        }
        @media (min-width: 1024px) {
            .hero { min-height: 88vh; }
            .feature-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .footer-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
    </style>
</head>
<body>
    <div class="utility-bar">
        <div class="container">
            <div>
                <?php foreach ($socialLinks as $item): ?>
                    <a href="<?php echo escape($item['href']); ?>" aria-label="<?php echo escape($item['label']); ?>"><?php echo escape($item['label']); ?></a>
                <?php endforeach; ?>
            </div>
            <div class="utility-links">
                <?php foreach ($utilityLinks as $item): ?>
                    <a href="<?php echo escape($item['href']); ?>"><?php echo escape($item['label']); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <nav class="navbar" aria-label="Main navigation">
        <div class="container">
            <a href="/" class="brand">
                <div class="brand-logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 14l9-5-9-5-9 5 9 5z" />
                        <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                    </svg>
                </div>
                <div class="brand-text">
                    <p class="title">Chigoneka School</p>
                    <p class="subtitle">Student Information Portal</p>
                </div>
            </a>
            <div class="nav-links">
                <?php foreach ($navLinks as $link): ?>
                    <a class="<?php echo activeClass($link['to'], $currentPath); ?>" href="<?php echo escape($link['to']); ?>"><?php echo escape($link['label']); ?></a>
                <?php endforeach; ?>
                <a class="portal-button" href="Login.php">Student Portal</a>
                <a class="portal-button" href="register.php">Register</a>
                <a class="portal-button" href="StaffLogin.php">Staff Portal</a>
                <a class="portal-button" href="setup_db.php">Install</a>
            </div>
            <button class="menu-toggle" type="button" id="mobile-menu-toggle" aria-expanded="false" aria-label="Open menu">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
        <div id="mobile-menu" style="display:none; background: rgba(15,23,42,0.98); border-top:1px solid rgba(148,163,184,0.12);">
            <div class="container" style="flex-direction:column; align-items:flex-start; gap:0.75rem; padding:1rem 0;">
                <?php foreach ($navLinks as $link): ?>
                    <a class="nav-link" href="<?php echo escape($link['to']); ?>"><?php echo escape($link['label']); ?></a>
                <?php endforeach; ?>
                <a class="portal-button" href="Login.php" style="width:100%; text-align:center;">Student Portal</a>
                <a class="portal-button" href="register.php" style="width:100%; text-align:center;">Register</a>
                <a class="portal-button" href="StaffLogin.php" style="width:100%; text-align:center;">Staff Portal</a>
                <a class="portal-button" href="setup_db.php" style="width:100%; text-align:center;">Install</a>
                <div style="width:100%; border-top:1px solid rgba(148,163,184,0.12); padding-top:1rem; display:grid; gap:0.75rem;">
                    <?php foreach ($utilityLinks as $item): ?>
                        <a class="nav-link" href="<?php echo escape($item['href']); ?>"><?php echo escape($item['label']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </nav>
    <section class="hero" id="hero-carousel">
        <?php foreach ($heroSlides as $index => $slide): ?>
            <div class="slide<?php echo $index === 0 ? ' active' : ''; ?>" data-index="<?php echo $index; ?>" style="background-image: linear-gradient(135deg, rgba(15,23,42,0.55), rgba(15,23,42,0.2)), url('<?php echo escape($slide['background']); ?>');"></div>
        <?php endforeach; ?>
        <div class="hero-content" id="hero-content">
            <span class="eyebrow">Chigoneka School</span>
            <h1 class="hero-title" id="hero-title"><?php echo escape($heroSlides[0]['title']); ?></h1>
            <p class="hero-text" id="hero-subtitle"><?php echo escape($heroSlides[0]['subtitle']); ?></p>
            <div class="hero-actions">
                <a href="<?php echo escape($heroSlides[0]['ctaLink']); ?>" class="hero-button" id="hero-cta"><?php echo escape($heroSlides[0]['cta']); ?></a>
                <a href="/about" class="hero-button-secondary">About Us</a>
            </div>
        </div>
        <div class="hero-controls">
            <button type="button" id="prev-slide" aria-label="Previous slide">&#10094;</button>
            <button type="button" id="next-slide" aria-label="Next slide">&#10095;</button>
        </div>
        <div class="hero-dots" id="hero-dots">
            <?php foreach ($heroSlides as $index => $slide): ?>
                <button type="button" class="hero-dot<?php echo $index === 0 ? ' active' : ''; ?>" data-index="<?php echo $index; ?>" aria-label="Go to slide <?php echo $index + 1; ?>"></button>
            <?php endforeach; ?>
        </div>
        <div class="hero-counter" id="hero-counter">1 / <?php echo count($heroSlides); ?></div>
    </section>
    <section class="features">
        <div class="container">
            <div class="features-header">
                <h2>Connect with excellence</h2>
                <p>Everything you need in one place for students, staff, and school administration.</p>
            </div>
            <div class="feature-grid">
                <?php foreach ($features as $feature): ?>
                    <a href="<?php echo escape($feature['href']); ?>" class="feature-card">
                        <div class="feature-icon <?php echo escape($feature['color']); ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24">
                                <path d="<?php echo escape($feature['icon']); ?>" />
                            </svg>
                        </div>
                        <h3 class="feature-title"><?php echo escape($feature['title']); ?></h3>
                        <p class="feature-desc"><?php echo escape($feature['desc']); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <footer class="footer">
        <div class="container footer-grid">
            <div>
                <p class="section-title">Chigoneka School</p>
                <p>Empowering students and staff with seamless access to academic information and resources.</p>
            </div>
            <div>
                <p class="section-title">Quick Links</p>
                <ul class="footer-list">
                    <?php foreach ($navLinks as $link): ?>
                        <li><a href="<?php echo escape($link['to']); ?>">→ <?php echo escape($link['label']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <p class="section-title">Resources</p>
                <ul class="footer-list">
                    <?php foreach ($footerLinks as $item): ?>
                        <li><a href="<?php echo escape($item['href']); ?>"><?php echo escape($item['label']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <p class="section-title">Contact</p>
                <ul class="footer-list" style="color:#94a3b8;">
                    <li>Email: <a href="mailto:info@chigoneka.ac.mw">info@chigoneka.ac.mw</a></li>
                    <li>Phone: +265 1 000 000</li>
                    <li>Location: Chigoneka, Malawi</li>
                </ul>
            </div>
        </div>
        <div class="container footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Chigoneka School. All rights reserved.</p>
            <div class="footer-links">
                <?php foreach ($footerLinks as $item): ?>
                    <a href="<?php echo escape($item['href']); ?>"><?php echo escape($item['label']); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </footer>
    <script>
        (function() {
            var slides = document.querySelectorAll('.slide');
            var dots = document.querySelectorAll('.hero-dot');
            var title = document.getElementById('hero-title');
            var subtitle = document.getElementById('hero-subtitle');
            var cta = document.getElementById('hero-cta');
            var counter = document.getElementById('hero-counter');
            var heroSlides = <?php echo json_encode($heroSlides, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
            var currentIndex = 0;

            function setSlide(index) {
                if (index < 0) index = heroSlides.length - 1;
                if (index >= heroSlides.length) index = 0;
                currentIndex = index;
                slides.forEach(function(slide, slideIndex) {
                    slide.classList.toggle('active', slideIndex === index);
                });
                dots.forEach(function(dot, dotIndex) {
                    dot.classList.toggle('active', dotIndex === index);
                });
                title.textContent = heroSlides[index].title;
                subtitle.textContent = heroSlides[index].subtitle;
                cta.textContent = heroSlides[index].cta;
                cta.href = heroSlides[index].ctaLink;
                counter.textContent = (index + 1) + ' / ' + heroSlides.length;
            }

            document.getElementById('prev-slide').addEventListener('click', function() {
                setSlide(currentIndex - 1);
            });

            document.getElementById('next-slide').addEventListener('click', function() {
                setSlide(currentIndex + 1);
            });

            dots.forEach(function(dot) {
                dot.addEventListener('click', function() {
                    setSlide(Number(dot.getAttribute('data-index')));
                });
            });

            document.getElementById('mobile-menu-toggle').addEventListener('click', function() {
                var menu = document.getElementById('mobile-menu');
                var expanded = this.getAttribute('aria-expanded') === 'true';
                menu.style.display = expanded ? 'none' : 'block';
                this.setAttribute('aria-expanded', String(!expanded));
            });

            setSlide(0);
        })();
    </script>
</body>
</html>
