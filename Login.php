<?php
session_start();
require_once __DIR__ . '/db.php';

$errors = [];
$identifier = '';

if (!function_exists('e')) {
    function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
}

/* ── Remember-me auto-login using token ── */
if (!isset($_SESSION['authenticated']) && isset($_COOKIE['remember_token_student'])) {
  $token  = $_COOKIE['remember_token_student'];
    $mysqli = db_connect();
    $mysqli->query("CREATE TABLE IF NOT EXISTS `user_tokens` (
        `id` INT NOT NULL AUTO_INCREMENT, 
        `user_id` INT NOT NULL,
        `token` VARCHAR(255) NOT NULL, 
        `expires_at` DATETIME NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`), 
        UNIQUE KEY `token`(`token`), 
        KEY `user_id`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $stmt = $mysqli->prepare("SELECT user_id FROM user_tokens WHERE token=? AND expires_at > NOW()");
    $stmt->bind_param('s', $token); 
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $user = get_user_by_id($row['user_id']);
        if ($user && $user['role'] === 'student') {
            $_SESSION['authenticated'] = true;
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['surname']    = $user['surname'];
            $_SESSION['role']       = 'student';
            $_SESSION['full_name']  = $user['first_name'].' '.$user['surname'];
            $_SESSION['login_time'] = time();
            header('Location: student_dashboard.php'); 
            exit;
        }
    }
    $stmt->close(); 
    $mysqli->close();
}

/* ── Get saved credentials from cookies for form prefill ── */
$saved_identifier = isset($_COOKIE['saved_student_identifier']) ? $_COOKIE['saved_student_identifier'] : '';
$saved_password   = isset($_COOKIE['saved_student_password']) ? $_COOKIE['saved_student_password'] : '';
$remember_checked = ($saved_identifier !== '' && $saved_password !== '');

/* ── Already logged in ── */
if (!empty($_SESSION['authenticated']) && $_SESSION['authenticated'] === true && ($_SESSION['role'] ?? '') === 'student') {
    header('Location: student_dashboard.php'); 
    exit;
}

/* ── Process Login Form ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';
    $remember   = isset($_POST['remember']);

    if ($identifier === '' || $password === '') {
        $errors[] = 'Please enter your email/phone and password.';
    }

    if (empty($errors)) {
        $user = get_user_by_identifier($identifier, 'student');
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Invalid email/phone or password. Please try again.';
        }
    }

    if (empty($errors)) {
        // Set session
        $_SESSION['authenticated'] = true;
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['surname']    = $user['surname'];
        $_SESSION['role']       = 'student';
        $_SESSION['full_name']  = $user['first_name'].' '.$user['surname'];
        $_SESSION['login_time'] = time();

        if ($remember) {
            // Save credentials in cookies for 30 days
          setcookie('saved_student_identifier', $identifier, time() + (86400 * 30), "/");
          setcookie('saved_student_password', $password, time() + (86400 * 30), "/");
          setcookie('saved_identifier', '', time() - 3600, "/");
          setcookie('saved_password', '', time() - 3600, "/");
            
            // Create remember token for auto-login
            $token      = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
            $mysqli = db_connect();
            
            // Create table if not exists
            $mysqli->query("CREATE TABLE IF NOT EXISTS `user_tokens` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `user_id` INT NOT NULL,
                `token` VARCHAR(255) NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `token`(`token`),
                KEY `user_id`(`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            // Delete old tokens for this user
            $stmt = $mysqli->prepare("DELETE FROM user_tokens WHERE user_id = ?");
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();
            $stmt->close();
            
            // Insert new token
            $stmt = $mysqli->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param('iss', $user['id'], $token, $expires_at);
            $stmt->execute();
            $stmt->close();
            $mysqli->close();
            
            // Set remember token cookie
            setcookie('remember_token_student', $token, time() + (86400 * 30), "/");
            setcookie('remember_token', '', time() - 3600, "/");
            
        } else {
            // Clear saved credentials if not remembering
            setcookie('saved_student_identifier', '', time() - 3600, "/");
            setcookie('saved_student_password', '', time() - 3600, "/");
            setcookie('remember_token_student', '', time() - 3600, "/");
            setcookie('saved_identifier', '', time() - 3600, "/");
            setcookie('saved_password', '', time() - 3600, "/");
            setcookie('remember_token', '', time() - 3600, "/");
            
            // Also delete from database
            $mysqli = db_connect();
            $stmt = $mysqli->prepare("DELETE FROM user_tokens WHERE user_id = ?");
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();
            $stmt->close();
            $mysqli->close();
        }
        
        header('Location: student_dashboard.php'); 
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Sign In | Chigoneka School</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{
  --navy:#1a3a6b;
  --gold:#c9a84c;
  --border:#e2e8f0;
  --bg:#f0f4f8;
  --text:#0f172a;
  --muted:#64748b;
  --hint:#94a3b8;
  --red:#dc2626;
  --red-bg:#fef2f2;
  --red-border:#fecaca;
  --blue:#2563eb;
  --blue-bg:#eff6ff;
  --blue-border:#bfdbfe;
}
html,body{height:100%;}
body{
  font-family:'DM Sans',system-ui,sans-serif;
  background:var(--bg);
  min-height:100vh;
  display:flex;align-items:center;justify-content:center;
  padding:1.5rem 1rem;
}

/* ── Card ── */
.card{
  width:100%;max-width:420px;
  background:#fff;
  border-radius:20px;
  border:1px solid var(--border);
  box-shadow:0 8px 32px rgba(26,58,107,.10), 0 2px 8px rgba(0,0,0,.05);
  overflow:hidden;
}

/* ── Card header ── */
.card-header{
  background:var(--navy);
  padding:2rem 2rem 1.85rem;
  text-align:center;
}
.header-icon{
  width:52px;height:52px;border-radius:14px;
  background:var(--gold);
  display:flex;align-items:center;justify-content:center;
  margin:0 auto .95rem;
  color:var(--navy);font-size:1.5rem;
}
.header-school{
  font-size:1rem;font-weight:600;color:#fff;
  letter-spacing:.01em;margin-bottom:3px;
}
.header-sub{
  font-size:.75rem;color:rgba(255,255,255,.45);
}

/* ── Card body ── */
.card-body{padding:1.85rem 1.85rem 2rem;}

/* ── Welcome message (instead of tabs) ── */
.welcome-message{
  text-align:center;
  margin-bottom:1.5rem;
  padding-bottom:0.75rem;
  border-bottom:2px solid var(--gold);
}
.welcome-message h2{
  font-size:1.2rem;
  font-weight:600;
  color:var(--navy);
  margin-bottom:0.25rem;
}
.welcome-message p{
  font-size:0.75rem;
  color:var(--muted);
}

/* ── Alert ── */
.alert{
  border-radius:9px;padding:.7rem .85rem;
  margin-bottom:1.25rem;font-size:.8rem;line-height:1.55;
  display:flex;align-items:flex-start;gap:.55rem;
  border-left:3px solid;
}
.alert i{font-size:15px;flex-shrink:0;margin-top:1px;}
.alert.error  {background:var(--red-bg); border-color:var(--red); color:#991b1b;}
.alert.info   {background:var(--blue-bg);border-color:var(--blue);color:#1e40af;}

/* ── Fields ── */
.field{margin-bottom:1rem;}
.field-head{
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:.45rem;
}
.field-head label{font-size:.8rem;font-weight:500;color:#334155;}
.field-head a{
  font-size:.75rem;color:var(--navy);
  transition:opacity .15s;text-decoration:none;
}
.field-head a:hover{opacity:.7;text-decoration:underline;}

.input-group{position:relative;}
.input-group .fi{
  position:absolute;left:11px;top:50%;transform:translateY(-50%);
  font-size:16px;color:var(--hint);pointer-events:none;
}
.input-group input{
  width:100%;padding:.65rem .85rem .65rem 2.25rem;
  border:1.5px solid var(--border);border-radius:9px;
  font-size:.875rem;color:var(--text);
  background:#fafbfc;font-family:inherit;
  transition:border-color .18s,box-shadow .18s,background .18s;
  outline:none;
}
.input-group input:focus{
  border-color:var(--navy);
  box-shadow:0 0 0 3px rgba(26,58,107,.1);
  background:#fff;
}
.input-group input::placeholder{color:var(--hint);}
.eye-btn{
  position:absolute;right:10px;top:50%;transform:translateY(-50%);
  background:none;border:none;cursor:pointer;
  color:var(--hint);padding:3px;
  transition:color .15s;line-height:1;
}
.eye-btn:hover{color:var(--muted);}
.eye-btn i{font-size:16px;}

/* ── Remember ── */
.remember-row{
  display:flex;align-items:center;gap:.5rem;
  margin-bottom:1.4rem;
}
.remember-row input[type=checkbox]{
  width:15px;height:15px;accent-color:var(--navy);
  cursor:pointer;flex-shrink:0;
}
.remember-row label{
  font-size:.78rem;color:var(--muted);cursor:pointer;
}

/* ── Turnstile ── */
.cf-row{
  display:flex;align-items:center;gap:.5rem;
  padding:.6rem .8rem;border-radius:9px;
  background:#f0fdf4;border:1px solid #bbf7d0;
  margin-bottom:1rem;
}
.cf-row i{font-size:15px;color:#22c55e;flex-shrink:0;}
.cf-row span{font-size:.76rem;color:#166534;font-weight:500;}
.turnstile-wrap{display:flex;justify-content:center;margin-bottom:1.1rem;}

/* ── Submit ── */
.btn-submit{
  width:100%;padding:.78rem;
  border-radius:9px;
  background:var(--navy);color:#fff;
  font-size:.875rem;font-weight:600;
  border:none;cursor:pointer;
  display:flex;align-items:center;justify-content:center;gap:.5rem;
  transition:background .18s,transform .12s,box-shadow .18s;
  box-shadow:0 4px 14px rgba(26,58,107,.28);
  font-family:inherit;
}
.btn-submit i{font-size:17px;}
.btn-submit:hover{background:#14305a;box-shadow:0 6px 20px rgba(26,58,107,.36);}
.btn-submit:active{transform:scale(.99);}

/* ── Footer ── */
.card-footer{
  margin-top:1.25rem;padding-top:1.1rem;
  border-top:1px solid var(--border);
  display:flex;align-items:center;justify-content:center;
  gap:.4rem;font-size:.78rem;color:var(--muted);
}
.card-footer a{
  color:var(--navy);font-weight:500;
  text-decoration:none;transition:opacity .15s;
  display:inline-flex;align-items:center;gap:2px;
}
.card-footer a:hover{opacity:.7;text-decoration:underline;}
.card-footer a i{font-size:13px;}

/* ── Back link ── */
.back-link{
  display:flex;align-items:center;justify-content:center;gap:.35rem;
  margin-top:1rem;font-size:.75rem;color:var(--muted);
  text-decoration:none;transition:color .15s;
}
.back-link:hover{color:var(--navy);}
.back-link i{font-size:14px;}
</style>
</head>
<body>

<div>
  <!-- Card -->
  <div class="card">

    <!-- Header -->
    <div class="card-header">
      <div class="header-icon">
        <i class="ti ti-school" aria-hidden="true"></i>
      </div>
      <div class="header-school">Chigoneka School</div>
      <div class="header-sub">Student Portal</div>
    </div>

    <!-- Body -->
    <div class="card-body">

      <!-- Welcome Message (replaces role tabs) -->
      <div class="welcome-message">
        <h2>Welcome Back!</h2>
        <p>Sign in to access your student dashboard</p>
      </div>

      <!-- Prefill notice -->
      <?php if ($remember_checked): ?>
      <div class="alert info" role="status">
        <i class="ti ti-info-circle" aria-hidden="true"></i>
        Saved credentials loaded — click <strong>Sign in</strong> to continue.
      </div>
      <?php endif; ?>

      <!-- Errors -->
      <?php if (!empty($errors)): ?>
      <div class="alert error" role="alert">
        <i class="ti ti-alert-triangle" aria-hidden="true"></i>
        <div><?= e(implode('<br>', array_map('e', $errors))) ?></div>
      </div>
      <?php endif; ?>

      <!-- Form -->
      <form method="POST" action="<?= e($_SERVER['PHP_SELF']) ?>" novalidate>

        <!-- Identifier -->
        <div class="field">
          <div class="field-head">
            <label for="identifier">Email or phone number</label>
          </div>
          <div class="input-group">
            <i class="ti ti-at fi" aria-hidden="true"></i>
            <input id="identifier" name="identifier" type="text"
                   placeholder="student@chigoneka.edu or 0995123456"
                   value="<?= e($saved_identifier ?: $identifier) ?>"
                   autocomplete="username" required>
          </div>
        </div>

        <!-- Password -->
        <div class="field">
          <div class="field-head">
            <label for="password">Password</label>
            <a href="forgot-password.php">Forgot password?</a>
          </div>
          <div class="input-group">
            <i class="ti ti-lock fi" aria-hidden="true"></i>
            <input id="password" name="password" type="password"
                   placeholder="Enter your password"
                   value="<?= e($saved_password) ?>"
                   autocomplete="current-password" required>
            <button type="button" class="eye-btn" id="eye-btn" aria-label="Show password">
              <i class="ti ti-eye" id="eye-icon" aria-hidden="true"></i>
            </button>
          </div>
        </div>

        <!-- Remember -->
        <div class="remember-row">
          <input type="checkbox" name="remember" id="remember" value="1" <?= $remember_checked ? 'checked' : '' ?>>
          <label for="remember">Remember me for 30 days</label>
        </div>

        <!-- Cloudflare -->
        <div class="cf-row">
          <i class="ti ti-shield-check" aria-hidden="true"></i>
          <span>Security verification required to continue</span>
        </div>
        <div class="turnstile-wrap">
          <div class="cf-turnstile" data-sitekey="your-site-key-here"></div>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-submit">
          <i class="ti ti-login" aria-hidden="true"></i>
          Sign in to Portal
        </button>

      </form>

      <!-- Footer -->
      <div class="card-footer">
        <span>No account yet?</span>
        <a href="register.php">Create one <i class="ti ti-arrow-right" aria-hidden="true"></i></a>
      </div>

    </div><!-- /card-body -->
  </div><!-- /card -->

  <!-- Back -->
  <a href="index.php" class="back-link">
    <i class="ti ti-arrow-left" aria-hidden="true"></i>
    Back to Chigoneka School Home
  </a>
</div>

<script>
(function(){
  var btn   = document.getElementById('eye-btn');
  var input = document.getElementById('password');
  var icon  = document.getElementById('eye-icon');
  btn.addEventListener('click', function(){
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    icon.className = show ? 'ti ti-eye-off' : 'ti ti-eye';
    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
  });
})();
</script>
</body>
</html>