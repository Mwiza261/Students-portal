<?php
session_start();
require_once __DIR__ . '/db.php';

$errors = [];
$identifier = '';
$loginSuccess = false;

// Check for "remember me" cookie (auto-login)
if (!isset($_SESSION['authenticated']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("SELECT user_id FROM user_tokens WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $user = get_user_by_id($row['user_id']);
        if ($user && $user['role'] === 'student') {
            $_SESSION['authenticated'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['surname'] = $user['surname'];
            $_SESSION['role'] = 'student';
            header('Location: index.php');
            exit;
        }
    }
    $stmt->close();
    $mysqli->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;

    if ($identifier === '' || $password === '') {
        $errors[] = 'Please enter both email/phone and password.';
    }

    if (empty($errors)) {
        $user = get_user_by_identifier($identifier, 'student');

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Invalid email/phone or password.';
        }
    }

    if (empty($errors)) {
        $_SESSION['authenticated'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['surname'] = $user['surname'];
        $_SESSION['role'] = 'student';
        
        // Handle "Remember Me" - same as Staff Login
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            $mysqli = db_connect();
            
            // First, check if user_tokens table exists, if not create it
            $mysqli->query("CREATE TABLE IF NOT EXISTS `user_tokens` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `user_id` INT(11) NOT NULL,
                `token` VARCHAR(255) NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `token` (`token`),
                KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            // Delete old tokens for this user
            $stmt = $mysqli->prepare("DELETE FROM user_tokens WHERE user_id = ?");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            $stmt->close();
            
            // Insert new token
            $stmt = $mysqli->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user['id'], $token, $expires_at);
            $stmt->execute();
            $stmt->close();
            $mysqli->close();
            
            // Set cookie for 30 days (httponly for security)
            setcookie('remember_token', $token, time() + (86400 * 30), "/", "", false, true);
        } else {
            // Clear any existing remember me cookie
            setcookie('remember_token', '', time() - 3600, "/");
        }
        
        header('Location: index.php');
        exit;
    }
}

// If already logged in, redirect to index
if (isset($_SESSION['authenticated'], $_SESSION['user_id']) && $_SESSION['authenticated'] === true && $_SESSION['role'] === 'student') {
    header('Location: index.php');
    exit;
}

// e() function - check if exists (provided by db.php)
if (!function_exists('e')) {
    function e($v): string {
        return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Sign In | Chigoneka School</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 24px;
        }

        .card {
            width: 100%;
            max-width: 450px;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            padding: 40px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1a3a6b;
            margin-bottom: 5px;
        }

        .logo p {
            color: #64748b;
            font-size: 14px;
        }

        .card h2 {
            margin: 0 0 8px;
            font-size: 24px;
            color: #081f4b;
        }

        .card-subtitle {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 28px;
        }

        .field {
            margin-bottom: 20px;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #334155;
        }

        .field input {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            background: #f8fafc;
            box-sizing: border-box;
        }

        .field input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: white;
        }

        .checkbox-field {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }

        .checkbox-field input {
            width: 18px;
            height: 18px;
            margin: 0;
            cursor: pointer;
            accent-color: #3b82f6;
        }

        .checkbox-field label {
            margin: 0;
            cursor: pointer;
            font-size: 14px;
            color: #475569;
        }

        .button {
            width: 100%;
            padding: 14px 16px;
            border: none;
            border-radius: 12px;
            background: #3b82f6;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.15s;
        }

        .button:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .links {
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }

        .links a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .messages {
            margin-bottom: 20px;
        }

        .message {
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 14px;
        }

        .message.error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .cf-box {
            margin: 18px 0;
            padding: 14px 16px;
            border-radius: 14px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #166534;
        }

        .cf-box strong {
            display: block;
            font-size: 14px;
        }

        .cf-box small {
            display: block;
            font-size: 12px;
            color: #3f6b52;
        }

        .cf-box::before {
            content: '✓';
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #22c55e;
            color: #fff;
            font-size: 16px;
            flex-shrink: 0;
        }

        .turnstile-wrapper {
            margin: 15px 0;
            display: flex;
            justify-content: center;
        }
    </style>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>
    <main class="page">
        <div class="card">
            <div class="logo">
                <h1>🎓 Chigoneka School</h1>
                <p>Student Portal Sign In</p>
            </div>
            
            <h2>Welcome back</h2>
            <p class="card-subtitle">Sign in to access your dashboard</p>

            <?php if (!empty($errors)): ?>
                <div class="messages">
                    <?php foreach ($errors as $error): ?>
                        <div class="message error"><?php echo e($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo e($_SERVER['PHP_SELF']); ?>">
                <div class="field">
                    <label for="identifier">Email or Phone Number</label>
                    <input id="identifier" name="identifier" type="text" placeholder="e.g., student@chigoneka.edu or 0995123456" value="<?php echo e($identifier); ?>" autocomplete="username">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password">
                </div>

                <div class="checkbox-field">
                    <input type="checkbox" name="remember" id="remember" value="1">
                    <label for="remember">🔐 Remember me (stay logged in for 30 days)</label>
                </div>

                <div class="cf-box">
                    <strong>Cloudflare Security Check</strong>
                    <small>Complete the verification below to continue</small>
                </div>

                <div class="turnstile-wrapper">
                    <div class="cf-turnstile" data-sitekey="your-site-key-here"></div>
                </div>

                <button type="submit" class="button">Sign In →</button>
            </form>

            <div class="links">
                <a href="register.php">📝 Create an account</a>
                <a href="forgot-password.php">🔒 Forgot password?</a>
            </div>
            
            <div style="margin-top: 20px; text-align: center; font-size: 12px; color: #94a3b8;">
                <a href="Home.php" style="color: #94a3b8;">← Back to Home</a>
            </div>
        </div>
    </main>
</body>
</html>