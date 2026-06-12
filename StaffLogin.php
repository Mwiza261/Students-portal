<?php
session_start();
require_once __DIR__ . '/db.php';

$loginError   = '';
$loginSuccess = false;

// Check for saved cookies
$saved_username = $_COOKIE['saved_staff_username'] ?? '';
$saved_password = $_COOKIE['saved_staff_password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']) ? true : false;

    if ($username === '' || $password === '') {
        $loginError = 'Please enter both username and password.';
    } else {
        $user = get_user_by_username($username, 'staff');

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $loginError = 'Invalid username or password.';
        } else {
            session_regenerate_id(true);
            $_SESSION['authenticated'] = true;
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['full_name']     = $user['first_name'] . ' ' . $user['surname'];
            $_SESSION['first_name']    = $user['first_name'];
            $_SESSION['surname']       = $user['surname'];
            $_SESSION['email']         = $user['email'];
            $_SESSION['phone']         = $user['phone'];
            $_SESSION['role']          = 'staff';
            $_SESSION['login_time']    = time();
            $loginSuccess              = true;
            
            if ($remember_me) {
                setcookie('saved_staff_username', $username, time() + (86400 * 30), "/", "", false, true);
                setcookie('saved_staff_password', $password, time() + (86400 * 30), "/", "", false, true);
                setcookie('saved_username', '', time() - 3600, "/");
                setcookie('saved_password', '', time() - 3600, "/");
            } else {
                setcookie('saved_staff_username', '', time() - 3600, "/");
                setcookie('saved_staff_password', '', time() - 3600, "/");
                setcookie('saved_username', '', time() - 3600, "/");
                setcookie('saved_password', '', time() - 3600, "/");
            }
            
            header('Location: StaffDashboard.php');
            exit;
        }
    }
}

function e($v): string {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login | Chigoneka School</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #1e293b;
        }

        .card {
            width: min(420px, 100%);
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 2.25rem 2.5rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
        }

        .logo {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: #1a3a6b;
            display: grid;
            place-items: center;
            font-size: 1.1rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 1.25rem;
        }

        h1 { margin: 0 0 0.35rem; font-size: 1.6rem; font-weight: 700; color: #1a3a6b; }

        .subtitle { margin: 0 0 1.75rem; font-size: 0.875rem; color: #64748b; line-height: 1.6; }

        .field { margin-bottom: 1rem; }

        .field label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.45rem;
        }

        .input-wrap { position: relative; display: flex; align-items: center; }

        .input-wrap svg {
            position: absolute;
            left: 0.9rem;
            width: 16px;
            height: 16px;
            color: #94a3b8;
            pointer-events: none;
        }

        .input-wrap input {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.5rem;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            background: #ffffff;
            color: #1e293b;
            font-size: 0.875rem;
            font-family: inherit;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .input-wrap input::placeholder { color: #94a3b8; }

        .input-wrap input:focus {
            outline: none;
            border-color: #1a3a6b;
            box-shadow: 0 0 0 3px rgba(26,58,107,0.1);
        }

        .toggle-pw {
            position: absolute;
            right: 0.9rem;
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0;
            line-height: 0;
        }

        .toggle-pw:hover { color: #64748b; }

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            line-height: 1.55;
        }

        .alert svg { width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px; }
        .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .alert-verifying { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }

        .btn-submit {
            width: 100%;
            padding: 0.9rem;
            border: none;
            border-radius: 12px;
            background: #1a3a6b;
            color: #fff;
            font-size: 0.9rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: opacity 0.15s, transform 0.15s;
            margin-top: 0.25rem;
        }

        .btn-submit:hover { opacity: 0.9; transform: translateY(-1px); background: #14305a; }

        .checkbox-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 1rem 0 0.5rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 0.8rem;
            color: #475569;
        }

        .checkbox-label input {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #1a3a6b;
        }

        .forgot-link {
            color: #1a3a6b;
            font-size: 0.8rem;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-link:hover { color: #0f2a4f; text-decoration: underline; }

        .links {
            margin-top: 1.25rem;
            text-align: center;
            font-size: 0.8rem;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .links a { color: #1a3a6b; text-decoration: none; font-weight: 500; }
        .links a:hover { color: #0f2a4f; text-decoration: underline; }

        .card-footer {
            margin-top: 1rem;
            text-align: center;
            font-size: 0.8rem;
            color: #64748b;
        }

        .card-footer a { color: #1a3a6b; text-decoration: none; font-weight: 500; }
        .card-footer a:hover { color: #0f2a4f; text-decoration: underline; }

        .verifying-text {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.5rem;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">CS</div>
    <h1>Staff Login</h1>
    <p class="subtitle">Enter your username and password to access the staff portal.</p>

    <div class="alert alert-verifying" id="verifyingBanner" style="display: none;">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        <span>Verifying credentials... <span id="dots"></span></span>
    </div>

    <?php if ($loginError): ?>
        <div class="alert alert-error">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <?php echo e($loginError); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="StaffLogin.php" novalidate id="loginForm">
        <div class="field">
            <label for="username">Username</label>
            <div class="input-wrap">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="e.g. john.banda"
                    autocomplete="username"
                    value="<?php echo e($saved_username); ?>"
                    required
                >
            </div>
        </div>

        <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c1.657 0 3-1.343 3-3V5a3 3 0 10-6 0v3c0 1.657 1.343 3 3 3z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 11h14a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2z"/>
                </svg>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Your password"
                    autocomplete="current-password"
                    value="<?php echo e($saved_password); ?>"
                    required
                >
                <button type="button" class="toggle-pw" onclick="togglePw()" aria-label="Show password">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="checkbox-group">
            <label class="checkbox-label">
                <input type="checkbox" name="remember_me" <?php echo (!empty($saved_username) && !empty($saved_password)) ? 'checked' : ''; ?>>
                <span>Remember me</span>
            </label>
            <a href="#" class="forgot-link">Forgot password?</a>
        </div>

        <button type="submit" class="btn-submit" id="signInBtn">Sign In</button>
    </form>
    
    <div class="links">
        <a href="staffregister.php">Create an account</a>
    </div>

    <div class="card-footer">
        <a href="index.php">← Back to Home</a>
    </div>
</div>

<script>
function togglePw() {
    const input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
}

let dotInterval = null;
let dotCount = 0;

function startDots() {
    dotCount = 0;
    if (dotInterval) clearInterval(dotInterval);
    dotInterval = setInterval(function() {
        dotCount = (dotCount + 1) % 4;
        let dots = '';
        for (let i = 0; i < dotCount; i++) dots += '.';
        const dotsSpan = document.getElementById('dots');
        if (dotsSpan) dotsSpan.textContent = dots;
    }, 500);
}

function stopDots() {
    if (dotInterval) {
        clearInterval(dotInterval);
        dotInterval = null;
    }
}

const form = document.getElementById('loginForm');
const signInBtn = document.getElementById('signInBtn');
const verifyingBanner = document.getElementById('verifyingBanner');

if (form) {
    form.addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        
        if (username !== '' && password !== '') {
            signInBtn.disabled = true;
            signInBtn.textContent = 'Signing in...';
            verifyingBanner.style.display = 'flex';
            startDots();
        }
    });
}
</script>
</body>
</html>