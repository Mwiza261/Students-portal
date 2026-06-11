\<?php
// Optional: Start session if you want to show user info
session_start();
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Contact | Lilongwe Area 47 — Reach our team</title>
    <!-- Google Fonts + modern styling -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #f6fafc 0%, #edf2f7 100%);
            color: #1a2c3e;
            line-height: 1.5;
            padding: 2rem 1rem;
        }

        .contact-container {
            max-width: 1280px;
            margin: 0 auto;
            background: rgba(255,255,255,0.6);
            border-radius: 2rem;
            backdrop-filter: blur(0px);
            box-shadow: 0 25px 45px -12px rgba(0, 0, 0, 0.1);
        }

        .contact-wrapper {
            background: #ffffff;
            border-radius: 2rem;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9f0f3;
        }

        .contact-header {
            background: linear-gradient(105deg, #1a5f4c 0%, #1e7761 100%);
            padding: 2rem 2rem 1.8rem;
            text-align: center;
            color: white;
        }

        .contact-header h1 {
            font-size: 2.4rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 0.5rem;
        }

        .contact-header p {
            font-size: 1rem;
            opacity: 0.92;
            max-width: 550px;
            margin: 0.5rem auto 0;
        }

        .header-location {
            margin-top: 1rem;
            background: rgba(255,255,255,0.2);
            display: inline-block;
            padding: 0.3rem 1rem;
            border-radius: 60px;
            font-size: 0.85rem;
            font-weight: 500;
            backdrop-filter: blur(4px);
        }

        .contact-grid {
            display: flex;
            flex-wrap: wrap;
        }

        .contact-info {
            flex: 1.2;
            background: #fefefe;
            padding: 2rem 2rem 2rem 2rem;
            border-right: 1px solid #eef3f6;
        }

        .contact-form-area {
            flex: 1.8;
            padding: 2rem 2rem 2rem 2rem;
            background: #ffffff;
        }

        .detail-card {
            background: #f9fdfb;
            border-radius: 1.5rem;
            padding: 1.25rem;
            margin-bottom: 1.8rem;
            transition: all 0.2s ease;
            border: 1px solid #e2edf2;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }

        .detail-card i {
            font-size: 1.8rem;
            color: #1e6f5c;
            width: 2.5rem;
            display: inline-block;
            vertical-align: middle;
        }

        .detail-card h3 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .contact-method {
            margin-top: 0.6rem;
            font-size: 1rem;
            word-break: break-word;
        }

        .phone-numbers {
            background: #eef5f2;
            border-radius: 1rem;
            padding: 0.8rem 1.2rem;
            margin: 0.8rem 0;
        }

        .phone-numbers a, .email-link {
            color: #1a5f4c;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
        }

        .phone-numbers a:hover, .email-link:hover {
            color: #0f3f33;
            text-decoration: underline;
        }

        .social-mini {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-mini a {
            background: #eef3f0;
            width: 38px;
            height: 38px;
            border-radius: 60px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #1e6f5c;
            transition: 0.2s;
            font-size: 1.2rem;
        }

        .social-mini a:hover {
            background: #1e6f5c;
            color: white;
            transform: translateY(-2px);
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            font-weight: 600;
            display: block;
            margin-bottom: 0.4rem;
            color: #1f3b4a;
        }

        input, textarea {
            width: 100%;
            padding: 0.9rem 1rem;
            border-radius: 1rem;
            border: 1px solid #cfdfe6;
            background: #ffffff;
            font-family: 'Inter', monospace;
            font-size: 0.95rem;
            transition: 0.2s;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #1e6f5c;
            box-shadow: 0 0 0 3px rgba(30,111,92,0.2);
        }

        .btn-submit {
            background: #1e6f5c;
            color: white;
            border: none;
            padding: 0.9rem 1.8rem;
            font-weight: 700;
            border-radius: 2rem;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-submit:hover {
            background: #0f5544;
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(0,0,0,0.1);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .note-text {
            font-size: 0.8rem;
            color: #6e8b9c;
            margin-top: 0.8rem;
            text-align: center;
        }

        hr {
            margin: 1rem 0;
            border: 0;
            height: 1px;
            background: linear-gradient(to right, #d4e2e9, transparent);
        }

        .badge-location {
            background: #e6f4ef;
            border-radius: 60px;
            padding: 0.3rem 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #1e6f5c;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .map-placeholder {
            background: #eef3f0;
            border-radius: 1rem;
            padding: 0.8rem;
            text-align: center;
            font-size: 0.85rem;
            color: #2f5a4b;
            margin-top: 1rem;
        }

        @media (max-width: 800px) {
            .contact-info {
                border-right: none;
                border-bottom: 1px solid #eef3f6;
            }
            .contact-header h1 {
                font-size: 1.8rem;
            }
            .contact-grid {
                flex-direction: column;
            }
            .contact-info, .contact-form-area {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
<div class="contact-container">
    <div class="contact-wrapper">
        <div class="contact-header">
            <h1>Let's connect <i class="fas fa-paper-plane" style="font-size: 1.8rem;"></i></h1>
            <p>We're based in Lilongwe, Area 47 — Central Region, Capital City. Reach out for collaborations, support or just to say hello.</p>
            <div class="header-location">
                <i class="fas fa-map-marker-alt"></i> Lilongwe · Area 47 | Central Region | Capital City, Malawi
            </div>
        </div>

        <div class="contact-grid">
            <!-- LEFT: contact details -->
            <div class="contact-info">
                <div class="badge-location"><i class="fas fa-location-dot"></i> Visit / Head office</div>
                
                <div class="detail-card">
                    <h3><i class="fas fa-phone-alt"></i> Call us</h3>
                    <div class="phone-numbers">
                        <p><i class="fas fa-mobile-alt"></i> <strong>Primary mobile:</strong><br>
                        <a href="tel:+265995669356">+265 995 669 356</a> / <a href="tel:+265996696355">0995 669 356</a></p>
                        <p style="margin-top: 0.5rem;"><i class="fas fa-phone"></i> <strong>Secondary line:</strong><br>
                        <a href="tel:+265886836955">+265 886 836 955</a> / <a href="tel:+265886836955">0886 836 955</a></p>
                    </div>
                    <div class="contact-method">
                        <i class="fas fa-clock"></i> Mon–Fri: 8:00 – 17:00 (CAT)<br>
                        <span style="font-size:0.85rem;">WhatsApp & calls welcome</span>
                    </div>
                </div>

                <div class="detail-card">
                    <h3><i class="fas fa-envelope"></i> Email direct</h3>
                    <div class="contact-method">
                        <a href="mailto:mwizamvula261@gmail.com" class="email-link" style="font-size:1.05rem; word-break: break-all;">
                            <i class="fas fa-envelope-open-text"></i> mwizamvula261@gmail.com
                        </a>
                        <p style="margin-top: 0.75rem; font-size:0.9rem;">We reply within 24 hours — guaranteed.</p>
                    </div>
                </div>

                <div class="detail-card">
                    <h3><i class="fas fa-map-pin"></i> Central Region Hub</h3>
                    <p><strong>📍 Area 47, Lilongwe</strong><br> Capital City, Malawi · Central Region</p>
                    <div class="map-placeholder">
                        <i class="fas fa-location-dot"></i> Area 47, near main district road — opposite local market, Lilongwe.
                    </div>
                    <div class="social-mini">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" aria-label="X"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>

            <!-- RIGHT: contact form -->
            <div class="contact-form-area">
                <h2 style="font-size: 1.6rem; font-weight: 700; color: #1c5a49; margin-bottom: 0.5rem;"><i class="fas fa-comment-dots"></i> Send a message</h2>
                <p style="margin-bottom: 1.5rem;">Fill out the form and we'll get back to you using the details above.</p>
                
                <form id="contactForm">
                    <div class="form-group">
                        <label for="name"><i class="fas fa-user"></i> Full name *</label>
                        <input type="text" id="name" name="name" placeholder="e.g. Thandiwe Banda" required>
                    </div>
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email address *</label>
                        <input type="email" id="email" name="email" placeholder="hello@example.com" required>
                    </div>
                    <div class="form-group">
                        <label for="phone"><i class="fas fa-phone"></i> Phone (optional)</label>
                        <input type="tel" id="phone" name="phone" placeholder="0995 669 356 / 0886 836 955">
                    </div>
                    <div class="form-group">
                        <label for="message"><i class="fas fa-pen"></i> Message / Inquiry *</label>
                        <textarea id="message" name="message" rows="5" placeholder="Tell us how we can help..." required></textarea>
                    </div>
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Send message
                    </button>
                    <div id="formMessage" style="margin-top: 1rem; padding: 0.75rem; border-radius: 0.75rem; display: none;"></div>
                    <div class="note-text">
                        <i class="fas fa-shield-alt"></i> We respect your privacy. Your details stay confidential.
                    </div>
                </form>

                <hr>
                <div style="display: flex; gap: 0.8rem; flex-wrap: wrap; justify-content: space-between; align-items: center;">
                    <div><i class="fas fa-check-circle" style="color:#1e6f5c;"></i> 24h response policy</div>
                    <div><i class="fas fa-phone-volume"></i> Direct calls: 0995 669 356 / 0886 836 955</div>
                    <div><i class="fas fa-envelope"></i> mwizamvula261@gmail.com</div>
                </div>
            </div>
        </div>

        <div style="background: #f8fafc; padding: 1rem 2rem; text-align: center; border-top: 1px solid #e9f0f3; font-size: 0.85rem;">
            <p><i class="fas fa-map-marked-alt"></i> Lilongwe, Area 47 — Central Region, Capital City, Malawi  |  Phone: 0995 669 356 / 0886 836 955  |  Email: mwizamvula261@gmail.com</p>
            <p style="margin-top: 0.3rem;">&copy; 2025 — Connect with our Area 47 team | Rooted in the central region</p>
        </div>
    </div>
</div>

<script>
document.getElementById('contactForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const formMessage = document.getElementById('formMessage');
    const originalBtnText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    formMessage.style.display = 'none';
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('contact_handler.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            formMessage.innerHTML = '<i class="fas fa-check-circle"></i> ' + result.message;
            formMessage.style.backgroundColor = '#d4edda';
            formMessage.style.color = '#155724';
            formMessage.style.border = '1px solid #c3e6cb';
            formMessage.style.display = 'block';
            this.reset();
        } else {
            formMessage.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + result.message;
            formMessage.style.backgroundColor = '#f8d7da';
            formMessage.style.color = '#721c24';
            formMessage.style.border = '1px solid #f5c6cb';
            formMessage.style.display = 'block';
        }
    } catch(error) {
        formMessage.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Network error. Please try again.';
        formMessage.style.backgroundColor = '#f8d7da';
        formMessage.style.color = '#721c24';
        formMessage.style.border = '1px solid #f5c6cb';
        formMessage.style.display = 'block';
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        
        setTimeout(() => {
            formMessage.style.display = 'none';
        }, 5000);
    }
});
</script>
</body>
</html>