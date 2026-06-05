<?php
session_start();
require_once __DIR__ . '/db.php';

$errors = [];
$identifier = '';
$loginSuccess = false;

// Check for "remember me" cookie
if (!isset($_SESSION['authenticated']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("SELECT user_id FROM user_tokens WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $user = get_user_by_id($row['user_id']);
        if ($user) {
            $_SESSION['authenticated'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
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
        $errors[] = 'Please enter both email or phone and password.';
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
        $_SESSION['role'] = 'student';
        
        // Handle "Remember Me"
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            $mysqli = db_connect();
            // Delete old tokens for this user
            $stmt = $mysqli->prepare("DELETE FROM user_tokens WHERE user_id = ?");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            
            // Insert new token
            $stmt = $mysqli->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user['id'], $token, $expires);
            $stmt->execute();
            $stmt->close();
            $mysqli->close();
            
            // Set cookie (30 days)
            setcookie('remember_token', $token, time() + (86400 * 30), "/", "", false, true);
        }
        
        header('Location: index.php');
        exit;
    }
}

if (isset($_SESSION['authenticated'], $_SESSION['user_id']) && $_SESSION['authenticated'] === true) {
    header('Location: index.php');
    exit;
}

// e() function is now provided by db.php - DO NOT redeclare it here!
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f3fb;
            color: #333;
        }
        .page {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 20px 70px rgba(12, 35, 80, 0.12);
            padding: 32px;
        }
        .card h1 {
            margin: 0 0 20px;
            font-size: 28px;
            color: #081f4b;
        }
        .field {
            margin-bottom: 18px;
        }
        .field label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #5f6e8a;
        }
        .field input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #d9e1ef;
            border-radius: 12px;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s ease;
            box-sizing: border-box;
        }
        .field input:focus {
            border-color: #1b4fef;
            box-shadow: 0 0 0 3px rgba(27, 79, 239, 0.12);
        }
        .checkbox-field {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
        }
        .checkbox-field input {
            width: auto;
            margin: 0;
        }
        .checkbox-field label {
            margin: 0;
            cursor: pointer;
        }
        .button {
            width: 100%;
            padding: 14px 16px;
            border: none;
            border-radius: 12px;
            background: #1028b7;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .button:hover {
            background: #0d21a4;
        }
        .links {
            margin-top: 18px;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }
        .links a {
            color: #1028b7;
            text-decoration: none;
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
            background: #ffe8e8;
            color: #8c1f2a;
            border: 1px solid #f5c2c2;
        }
        .message.success {
            background: #e7f8ef;
            color: #1a6c43;
            border: 1px solid #c0e6cf;
        }
        .cf-box {
            margin: 18px 0;
            padding: 14px 16px;
            border-radius: 14px;
            background: #f6fcf8;
            border: 1px solid #d0f0db;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #134f2f;
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
            content: '✔';
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #1d8b4e;
            color: #fff;
            font-size: 16px;
            flex-shrink: 0;
        }
    </style>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>
    <main class="page">
        <section class="card">
            <h1>Sign In</h1>

            <?php if (!empty($errors)): ?>
                <div class="messages">
                    <?php foreach ($errors as $error): ?>
                        <div class="message error"><?php echo e($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($loginSuccess): ?>
                <div class="message success">
                    Welcome back, <?php echo e($user['first_name'] . ' ' . $user['surname']); ?>! You have successfully signed in.
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo e($_SERVER['PHP_SELF']); ?>">
                <div class="field">
                    <label for="identifier">Email or Phone</label>
                    <input id="identifier" name="identifier" type="text" placeholder="Email or phone" value="<?php echo e($identifier); ?>" autocomplete="username">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" placeholder="Password" autocomplete="current-password">
                </div>

                <div class="checkbox-field">
                    <input type="checkbox" name="remember" id="remember" value="1">
                    <label for="remember">Remember me (stay logged in for 30 days)</label>
                </div>

                <div class="cf-box">
                    <strong>Cloudflare verification</strong>
                    <small>Complete the verification before signing in.</small>
                </div>

                <div class="field">
                    <div class="cf-turnstile" data-sitekey="your-site-key-here"></div>
                </div>

                <button type="submit" class="button">SIGN IN</button>
            </form>

            <div class="links">
                <a href="register.php">Create an account</a>
                <a href="forgot-password.php">Forgot password?</a>
            </div>
        </section>
    </main>
</body>
</html>