<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>About Us | Lilongwe · Area 47 | Central Region Capital</title>
    <!-- Google Fonts & simple reset for clean typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7fc;
            color: #1f2a3e;
            line-height: 1.5;
            scroll-behavior: smooth;
        }

        /* subtle warm accent inspired by malawi landscape */
        :root {
            --primary: #1e6f5c;
            --primary-dark: #0f4c3f;
            --accent-gold: #e6b422;
            --light-bg: #ffffff;
            --shadow-sm: 0 8px 20px rgba(0, 0, 0, 0.03), 0 2px 6px rgba(0, 0, 0, 0.05);
            --border-light: #e2edf2;
        }

        /* container & layout */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem 1.5rem 3rem;
        }

        /* header / nav placeholder (simple but elegant) */
        .page-header {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border-light);
            padding-bottom: 1rem;
        }

        .page-header h1 {
            font-size: 2.4rem;
            font-weight: 700;
            letter-spacing: -0.3px;
            background: linear-gradient(135deg, #1e6f5c 0%, #2c8a73 100%);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            display: inline-block;
        }

        .page-header .tagline {
            color: #4b5e77;
            margin-top: 0.5rem;
            font-weight: 400;
        }

        /* about card grid */
        .about-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-top: 1.5rem;
        }

        .main-content {
            flex: 2;
            min-width: 260px;
        }

        .info-card {
            flex: 1.2;
            min-width: 260px;
        }

        /* cards */
        .card {
            background: var(--light-bg);
            border-radius: 28px;
            box-shadow: var(--shadow-sm);
            padding: 1.8rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-light);
            transition: transform 0.2s ease, box-shadow 0.2s;
        }

        .card:hover {
            box-shadow: 0 20px 30px -12px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 1rem;
            border-left: 5px solid var(--primary);
            padding-left: 1rem;
            color: #1e2f41;
        }

        .card h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 1.2rem 0 0.5rem;
            color: var(--primary-dark);
        }

        .intro-text {
            font-size: 1.05rem;
            margin-bottom: 1rem;
            color: #2c3e4e;
        }

        /* location highlight */
        .location-block {
            background: #eef3f0;
            border-radius: 24px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-left: 5px solid var(--accent-gold);
        }

        .location-block p {
            margin: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .location-icon {
            font-size: 1.4rem;
        }

        .badge {
            background: var(--primary);
            color: white;
            border-radius: 60px;
            padding: 0.2rem 0.9rem;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
            margin-right: 0.6rem;
        }

        .contact-simple {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px dashed var(--border-light);
        }

        /* feature list */
        .feature-list {
            list-style: none;
            margin-top: 0.8rem;
        }

        .feature-list li {
            margin-bottom: 0.75rem;
            display: flex;
            align-items: baseline;
            gap: 0.6rem;
        }

        .feature-list li::before {
            content: "📍";
            font-size: 1rem;
            color: var(--primary);
        }

        hr {
            margin: 1rem 0;
            border: 0;
            height: 1px;
            background: linear-gradient(to right, #cbdde6, transparent);
        }

        /* stats/mini highlights */
        .stat-grid {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin: 1rem 0;
            flex-wrap: wrap;
        }

        .stat-item {
            background: #f9fdfb;
            border-radius: 18px;
            padding: 0.8rem;
            text-align: center;
            flex: 1;
        }

        /* responsiveness */
        @media (max-width: 780px) {
            .container {
                padding: 1.2rem;
            }
            .card h2 {
                font-size: 1.4rem;
            }
            .page-header h1 {
                font-size: 2rem;
            }
        }

        /* footer style */
        .footer-note {
            margin-top: 3rem;
            text-align: center;
            font-size: 0.85rem;
            color: #6c86a3;
            border-top: 1px solid var(--border-light);
            padding-top: 1.5rem;
        }

        .btn-outline {
            display: inline-block;
            background: transparent;
            border: 1.5px solid var(--primary);
            color: var(--primary-dark);
            padding: 0.5rem 1.2rem;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
            margin-top: 0.8rem;
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- header section -->
        <div class="page-header">
            <h1>About us · Mzindawathu</h1>
            <div class="tagline">Rooted in Malawi's heart — serving with purpose</div>
        </div>

        <div class="about-grid">
            <!-- left side: core narrative -->
            <div class="main-content">
                <div class="card">
                    <h2>Our story</h2>
                    <p class="intro-text">Founded and operated from the vibrant capital city, Lilongwe — specifically Area 47 — we blend innovation with community. Our journey began in the central region, embracing the energy of Malawi's political and cultural heartbeat.</p>
                    <p>We are a collective of thinkers, creators, and problem-solvers dedicated to delivering quality digital solutions, authentic services, and local impact. Being positioned in Area 47 gives us a unique vantage point: close to key infrastructure, yet deeply connected to the neighbourhood rhythm that defines Lilongwe's spirit.</p>
                    
                    <!-- location explicit block (main narrative) -->
                    <div class="location-block">
                        <p><span class="location-icon">📍</span> <strong>Our homebase:</strong> <span style="font-weight:600;">Lilongwe, Area 47</span> — Central Region, Capital City, Malawi</p>
                        <p><span class="location-icon">🏛️</span> From the political hub to local enterprises, we serve clients across the central region and beyond.</p>
                        <p><span class="location-icon">🌍</span> Area 47 represents innovation, accessibility and the future of local tech & creative industries.</p>
                    </div>
                    
                    <h3>What drives us</h3>
                    <p>We believe that location matters. Operating directly from Lilongwe's central district means we understand the local market, the infrastructure nuances, and the opportunities that arise from being in the country’s administrative and economic centre. Whether it’s collaboration with city stakeholders or reaching out to rural communities, Area 47 is our strategic anchor.</p>
                    <div class="stat-grid">
                        <div class="stat-item">✔️ 100% local team</div>
                        <div class="stat-item">✔️ Central region focused</div>
                        <div class="stat-item">✔️ Capital city presence</div>
                    </div>
                    <a href="#" class="btn-outline" aria-label="Learn more about our mission">Learn more →</a>
                </div>
                
                <div class="card">
                    <h2>Why Lilongwe · Area 47</h2>
                    <p>Area 47 is more than a postal address — it’s a dynamic corridor in the capital. Being located here allows us to engage with grassroots initiatives, government agencies, NGOs, and private partners who share the same urban landscape. The central region acts as a bridge between the northern and southern territories, and we harness that connectivity.</p>
                    <ul class="feature-list">
                        <li>Strategic accessibility to downtown Lilongwe</li>
                        <li>Close to major transport routes & business districts</li>
                        <li>Hub of emerging tech and creative spaces</li>
                        <li>Authentic community relationships built over years</li>
                    </ul>
                </div>
            </div>

            <!-- right side: location summary + contact / direct details -->
            <div class="info-card">
                <div class="card">
                    <h2>📍 Headquarters</h2>
                    <div style="background:#f1f5f9; border-radius: 20px; padding: 0.2rem 0.8rem; margin-bottom: 1rem;">
                        <p style="font-weight: 700; margin:0.6rem 0;">Lilongwe, Area 47</p>
                        <p style="font-size:0.9rem;">Central Region, Capital City, Malawi</p>
                    </div>
                    <p><strong>🗺️ Exact location:</strong> Area 47, along the capital's central corridor, near major landmarks and community centers.</p>
                    <hr>
                    <h3>Contact & presence</h3>
                    <p>📞 +265 (0) 123 456 789 <br> ✉️ hello@area47hub.mw</p>
                    <p>🕒 Mon–Fri: 08:00 – 17:00 (CAT)</p>
                    <div class="contact-simple">
                        <span class="badge">Central region</span>
                        <span class="badge" style="background: #e6b422; color:#2d2b1f;">Capital city</span>
                        <span class="badge" style="background: #2c5a4a;">Area 47 hub</span>
                    </div>
                </div>
                
                <div class="card">
                    <h2>🌟 Regional footprint</h2>
                    <p>Our operations extend across the central region, but our roots remain firmly planted in Lilongwe — the capital city. From Area 47 we coordinate initiatives that touch:</p>
                    <ul style="margin-top: 0.8rem; list-style-type: none; padding-left: 0;">
                        <li style="margin-bottom: 0.4rem;">✓ Lilongwe City Council partnerships</li>
                        <li style="margin-bottom: 0.4rem;">✓ Central region innovation clusters</li>
                        <li style="margin-bottom: 0.4rem;">✓ Cross-sector collaboration (Dowa, Dedza, Salima)</li>
                        <li style="margin-bottom: 0.4rem;">✓ Community empowerment programs</li>
                    </ul>
                </div>

                <div class="card">
                    <h2>📌 Quick facts</h2>
                    <p><strong>Capital:</strong> Lilongwe <br> <strong>Zone:</strong> Area 47 <br> <strong>Region:</strong> Central Malawi <br> <strong>Since:</strong> 2019</p>
                    <p style="margin-top: 1rem; font-size: 0.9rem; background: #fef7e0; padding: 0.7rem; border-radius: 16px;">✨ “We don’t just operate in the capital — we shape solutions from the heart of the central region, Area 47.”</p>
                </div>
            </div>
        </div>

        <!-- additional micro section: directions / local flavour -->
        <div style="margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 1rem; justify-content: space-between;">
            <div style="flex:1; background:#FFFFFF; border-radius: 24px; padding: 1.3rem; border:1px solid #e2edf2;">
                <h3 style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">🚗 Getting here (Area 47)</h3>
                <p>Located just off the main M1 road that traverses Lilongwe, Area 47 is easily accessible from the city centre and the Kamuzu International Airport corridor. Our neighbours include local markets, green spaces and collaborative workspaces.</p>
            </div>
            <div style="flex:1; background:#FFFFFF; border-radius: 24px; padding: 1.3rem; border:1px solid #e2edf2;">
                <h3 style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">🤝 Community commitment</h3>
                <p>From Area 47 to the wider central region, we actively invest in local talent, digital literacy workshops, and sustainable initiatives that mirror the resilience of Malawi's capital city.</p>
            </div>
        </div>

        <!-- footer note including location echo -->
        <div class="footer-note">
            <p>📍 Lilongwe, Area 47 | Central Region | Capital City of Malawi — where purpose meets place.<br>
            &copy; 2025 Area47 Hub — rooted in the heart of the nation.</p>
        </div>
    </div>
</body>
</html>