<?php
session_start();
require_once __DIR__ . '/db.php';

class RegistrationValidator {
    private $errors = [];
    private $data = [];
    
    public function validate($postData) {
        $this->data['first_name'] = trim($postData['first_name'] ?? '');
        $this->data['surname'] = trim($postData['surname'] ?? '');
        $this->data['identifier'] = trim($postData['identifier'] ?? '');
        $this->data['password'] = $postData['password'] ?? '';
        $this->data['confirm_password'] = $postData['confirm_password'] ?? '';
        $this->data['role'] = $postData['role'] ?? 'student'; // Get role from form
        
        $this->validateRequiredFields();
        $this->validatePassword();
        $this->validateIdentifier();
        
        return [
            'errors' => $this->errors,
            'data' => $this->data
        ];
    }
    
    private function validateRequiredFields() {
        if ($this->data['first_name'] === '') {
            $this->errors['first_name'] = 'Please enter your first name.';
        } elseif (strlen($this->data['first_name']) < 2) {
            $this->errors['first_name'] = 'First name must be at least 2 characters.';
        }
        
        if ($this->data['surname'] === '') {
            $this->errors['surname'] = 'Please enter your surname.';
        } elseif (strlen($this->data['surname']) < 2) {
            $this->errors['surname'] = 'Surname must be at least 2 characters.';
        }
        
        if ($this->data['identifier'] === '') {
            $this->errors['identifier'] = 'Please enter an email address or phone number.';
        }
    }
    
    private function validatePassword() {
        if ($this->data['password'] === '' || $this->data['confirm_password'] === '') {
            $this->errors['password'] = 'Please enter and confirm your password.';
        } elseif ($this->data['password'] !== $this->data['confirm_password']) {
            $this->errors['password'] = 'Passwords do not match.';
        } elseif (strlen($this->data['password']) < 8) {
            $this->errors['password'] = 'Password must be at least 8 characters long.';
        } elseif (!preg_match('/[A-Z]/', $this->data['password'])) {
            $this->errors['password'] = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[a-z]/', $this->data['password'])) {
            $this->errors['password'] = 'Password must contain at least one lowercase letter.';
        } elseif (!preg_match('/[0-9]/', $this->data['password'])) {
            $this->errors['password'] = 'Password must contain at least one number.';
        }
    }
    
    private function validateIdentifier() {
        if ($this->data['identifier'] === '') return;
        
        if (filter_var($this->data['identifier'], FILTER_VALIDATE_EMAIL)) {
            $this->data['email'] = strtolower($this->data['identifier']);
            $this->data['username'] = $this->data['email'];
            $this->data['phone'] = null;
        } else {
            $normalizedPhone = normalize_phone($this->data['identifier']);
            if ($normalizedPhone === '' || !preg_match('/^[+0-9]{7,25}$/', $normalizedPhone)) {
                $this->errors['identifier'] = 'Please enter a valid email address or phone number.';
            } else {
                $this->data['phone'] = $normalizedPhone;
                $this->data['username'] = $normalizedPhone;
                $this->data['email'] = null;
            }
        }
    }
    
    public function getProcessedData() {
        return $this->data;
    }
}

class UserRegistration {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function register($userData) {
        // Check if user already exists
        if ($this->userExists($userData['identifier'], $userData['role'])) {
            return ['success' => false, 'error' => 'This email address or phone number is already registered.'];
        }
        
        // Prepare SQL statement
        $stmt = $this->mysqli->prepare('INSERT INTO users (username, first_name, surname, email, phone, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        
        if (!$stmt) {
            error_log("Database prepare error: " . $this->mysqli->error);
            return ['success' => false, 'error' => 'Unable to create your account. Please try again later.'];
        }
        
        $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
        $role = $userData['role']; // Use the role from form
        
        $stmt->bind_param('sssssss', 
            $userData['username'], 
            $userData['first_name'], 
            $userData['surname'], 
            $userData['email'], 
            $userData['phone'], 
            $passwordHash, 
            $role
        );
        
        if ($stmt->execute()) {
            $userId = $this->mysqli->insert_id;
            $stmt->close();
            
            // Log the registration
            $this->logRegistration($userId, $userData['username'], $role);
            
            return ['success' => true, 'user_id' => $userId, 'role' => $role];
        } else {
            $error = $stmt->error;
            $stmt->close();
            
            if ($this->mysqli->errno === 1062) {
                return ['success' => false, 'error' => 'This email address or phone number is already in use.'];
            }
            
            error_log("User registration failed: " . $error);
            return ['success' => false, 'error' => 'Unable to create your account. Please try again later.'];
        }
    }
    
    private function userExists($identifier, $role) {
        // Check if user exists with the given role
        $stmt = $this->mysqli->prepare("SELECT id FROM users WHERE (email = ? OR phone = ?) AND role = ?");
        $email = $phone = $identifier;
        $stmt->bind_param('sss', $email, $phone, $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    private function logRegistration($userId, $username, $role) {
        $logFile = __DIR__ . '/logs/registrations.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logEntry = date('Y-m-d H:i:s') . " - User ID: $userId, Username: $username, Role: $role, IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

// Process registration
$errors = [];
$formData = ['first_name' => '', 'surname' => '', 'identifier' => '', 'role' => 'staff']; // Default to staff
$registrationSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
        $errors['general'] = 'Invalid form submission. Please try again.';
    } else {
        $validator = new RegistrationValidator();
        $validationResult = $validator->validate($_POST);
        $errors = $validationResult['errors'];
        $formData = $validationResult['data'];
        
        if (empty($errors)) {
            $mysqli = db_connect();
            $registration = new UserRegistration($mysqli);
            $result = $registration->register($validator->getProcessedData());
            $mysqli->close();
            
            if ($result['success']) {
                $_SESSION['registration_success'] = true;
                $_SESSION['registered_role'] = $result['role'];
                $_SESSION['registered_username'] = $formData['username'];
                
                // Redirect to StaffLogin.php for staff accounts
                if ($result['role'] === 'staff') {
                    header('Location: StaffLogin.php?registered=1&role=staff');
                } else {
                    // For students, redirect to regular Login.php
                    header('Location: Login.php?registered=1');
                }
                exit;
            } else {
                $errors['general'] = $result['error'];
            }
        }
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function getFieldError($errors, $field) {
    return isset($errors[$field]) ? $errors[$field] : null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Registration | Chigoneka School</title>
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            max-width: 520px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 20px 70px rgba(12, 35, 80, 0.2);
            padding: 36px;
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .card h1 {
            margin: 0 0 8px;
            font-size: 30px;
            color: #081f4b;
        }
        
        .badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
            vertical-align: middle;
        }
        
        .subtitle {
            color: #5f6e8a;
            margin-bottom: 28px;
            font-size: 14px;
        }
        
        .field {
            margin-bottom: 20px;
        }
        
        .field label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #5f6e8a;
        }
        
        .field input, .field select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            outline: none;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        
        .field select {
            background: white;
            cursor: pointer;
        }
        
        .field input:focus, .field select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .field input.error {
            border-color: #f56565;
        }
        
        .field-error {
            color: #f56565;
            font-size: 12px;
            margin-top: 6px;
            display: block;
        }
        
        .password-strength {
            margin-top: 8px;
            font-size: 12px;
        }
        
        .strength-bar {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .strength-bar-fill {
            height: 100%;
            width: 0;
            transition: width 0.3s ease, background 0.3s ease;
        }
        
        .button {
            width: 100%;
            padding: 14px 16px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .button:active {
            transform: translateY(0);
        }
        
        .message {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 14px;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message.error {
            background: #fed7d7;
            color: #9b2c2c;
            border-left: 4px solid #f56565;
        }
        
        .message.success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #48bb78;
        }
        
        .info-box {
            background: #ebf8ff;
            border-left: 4px solid #4299e1;
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 13px;
            color: #2c5282;
        }
        
        .info-box strong {
            display: block;
            margin-bottom: 6px;
        }
        
        .links {
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .links a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        @media (max-width: 640px) {
            .card {
                padding: 24px;
            }
            
            .card h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="card">
            <h1>
                Staff Registration 
                <span class="badge">Staff Account</span>
            </h1>
            <div class="subtitle">Create your staff account to access the staff portal</div>

            <?php if (isset($errors['general'])): ?>
                <div class="message error">
                    <?php echo e($errors['general']); ?>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <strong>📋 Staff Registration Information:</strong>
                This account will be created with staff privileges. After registration, you'll be redirected to the staff login page where you can access staff-only features.
            </div>

            <form method="post" action="<?php echo e($_SERVER['PHP_SELF']); ?>" id="registrationForm">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="role" value="staff">
                
                <div class="field">
                    <label for="first_name">First Name</label>
                    <input id="first_name" name="first_name" type="text" 
                           placeholder="Enter your first name" 
                           value="<?php echo e($formData['first_name']); ?>" 
                           autocomplete="given-name"
                           class="<?php echo getFieldError($errors, 'first_name') ? 'error' : ''; ?>">
                    <?php if ($error = getFieldError($errors, 'first_name')): ?>
                        <span class="field-error"><?php echo e($error); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="field">
                    <label for="surname">Last Name</label>
                    <input id="surname" name="surname" type="text" 
                           placeholder="Enter your last name" 
                           value="<?php echo e($formData['surname']); ?>" 
                           autocomplete="family-name"
                           class="<?php echo getFieldError($errors, 'surname') ? 'error' : ''; ?>">
                    <?php if ($error = getFieldError($errors, 'surname')): ?>
                        <span class="field-error"><?php echo e($error); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="field">
                    <label for="identifier">Email or Phone</label>
                    <input id="identifier" name="identifier" type="text" 
                           placeholder="your.email@school.com or +1234567890" 
                           value="<?php echo e($formData['identifier']); ?>" 
                           autocomplete="email"
                           class="<?php echo getFieldError($errors, 'identifier') ? 'error' : ''; ?>">
                    <?php if ($error = getFieldError($errors, 'identifier')): ?>
                        <span class="field-error"><?php echo e($error); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" 
                           placeholder="Create a strong password" 
                           autocomplete="new-password"
                           class="<?php echo getFieldError($errors, 'password') ? 'error' : ''; ?>">
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-bar-fill" id="strengthBar"></div>
                        </div>
                        <span id="strengthText"></span>
                    </div>
                    <?php if ($error = getFieldError($errors, 'password')): ?>
                        <span class="field-error"><?php echo e($error); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="field">
                    <label for="confirm_password">Confirm Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" 
                           placeholder="Confirm your password" 
                           autocomplete="new-password">
                </div>

                <button type="submit" class="button">Create Staff Account</button>
            </form>

            <div class="links">
                <a href="StaffLogin.php">✓ Already have a staff account? Sign in</a>
                <a href="Home.php">← Back to Home</a>
            </div>
        </section>
    </main>

    <script>
        // Password strength meter
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        function checkPasswordStrength(password) {
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            const strengthLevels = [
                { text: 'Very Weak', color: '#f56565', width: '20%' },
                { text: 'Weak', color: '#ed8936', width: '40%' },
                { text: 'Fair', color: '#ecc94b', width: '60%' },
                { text: 'Good', color: '#48bb78', width: '80%' },
                { text: 'Strong', color: '#38a169', width: '100%' }
            ];
            
            let index = Math.min(Math.floor(strength / 2), 4);
            if (password.length === 0) index = -1;
            
            if (index >= 0) {
                strengthBar.style.width = strengthLevels[index].width;
                strengthBar.style.background = strengthLevels[index].color;
                strengthText.textContent = strengthLevels[index].text;
                strengthText.style.color = strengthLevels[index].color;
            } else {
                strengthBar.style.width = '0';
                strengthText.textContent = '';
            }
        }
        
        // Real-time password confirmation check
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (confirm.length > 0 && password !== confirm) {
                confirmInput.style.borderColor = '#f56565';
            } else {
                confirmInput.style.borderColor = '#e2e8f0';
            }
        }
        
        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            checkPasswordMatch();
        });
        
        confirmInput.addEventListener('input', checkPasswordMatch);
        
        // Form validation before submit
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!\n\nRequirements:\n• At least 8 characters\n• One uppercase letter\n• One lowercase letter\n• One number');
                return false;
            }
            
            if (!password.match(/[A-Z]/)) {
                e.preventDefault();
                alert('Password must contain at least one uppercase letter!');
                return false;
            }
            
            if (!password.match(/[0-9]/)) {
                e.preventDefault();
                alert('Password must contain at least one number!');
                return false;
            }
        });
        
        // Display success message if redirected with registered parameter
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('registered') === '1') {
            const successDiv = document.createElement('div');
            successDiv.className = 'message success';
            successDiv.innerHTML = '✓ Staff account created successfully! You can now sign in.';
            const form = document.getElementById('registrationForm');
            form.parentNode.insertBefore(successDiv, form);
        }
    </script>
</body>
</html>