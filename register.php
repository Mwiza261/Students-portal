<?php
session_start();
require_once __DIR__ . '/db.php';

$errors = [];
$success = false;
$first_name = '';
$surname = '';
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($first_name === '') {
        $errors[] = 'Please enter your first name.';
    }

    if ($surname === '') {
        $errors[] = 'Please enter your surname.';
    }

    if ($identifier === '') {
        $errors[] = 'Please enter an email address or phone number.';
    }

    if ($password === '' || $confirm_password === '') {
        $errors[] = 'Please enter and confirm your password.';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    $email = null;
    $phone = null;
    if (empty($errors)) {
        if (is_email($identifier)) {
            $email = strtolower($identifier);
            $username = $email;
        } else {
            $normalizedPhone = normalize_phone($identifier);
            if ($normalizedPhone === '' || !preg_match('/^[+0-9]{7,25}$/', $normalizedPhone)) {
                $errors[] = 'Please enter a valid email address or phone number.';
            } else {
                $phone = $normalizedPhone;
                $username = $normalizedPhone;
            }
        }
    }

    if (empty($errors)) {
        if (get_user_by_identifier($identifier, 'student')) {
            $errors[] = 'This email address or phone number is already registered.';
        }
    }

    if (empty($errors)) {
        $mysqli = db_connect();
        $stmt = $mysqli->prepare('INSERT INTO users (username, first_name, surname, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?, ?, ?)');

        if (!$stmt) {
            $errors[] = 'Unable to create your account. Please try again later.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'student';
            $stmt->bind_param('sssssss', $username, $first_name, $surname, $email, $phone, $passwordHash, $role);
            $stmt->execute();

            if ($stmt->errno) {
                if ($stmt->errno === 1062) {
                    $errors[] = 'This email address or phone number is already in use.';
                } else {
                    $errors[] = 'Unable to create your account: ' . $stmt->error;
                }
            } else {
                $stmt->close();
                $mysqli->close();
                header('Location: Login.php');
                exit;
            }

            $stmt->close();
        }

        $mysqli->close();
    }
}

function e($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Chigoneka School</title>
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
            max-width: 520px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 20px 70px rgba(12, 35, 80, 0.12);
            padding: 36px;
        }
        .card h1 {
            margin: 0 0 24px;
            font-size: 30px;
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
        }
        .field input:focus {
            border-color: #1b4fef;
            box-shadow: 0 0 0 3px rgba(27, 79, 239, 0.12);
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
        .message {
            margin-bottom: 20px;
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
    </style>
</head>
<body>
    <main class="page">
        <section class="card">
            <h1>Create Student Account</h1>

            <?php if (!empty($errors)): ?>
                <div class="message error">
                    <ul style="margin:0; padding-left:1.2rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="message success">
                    Your account has been created successfully. You can now <a href="Login.php">sign in</a>.
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo e($_SERVER['PHP_SELF']); ?>">
                <div class="field">
                    <label for="first_name">First Name</label>
                    <input id="first_name" name="first_name" type="text" placeholder="First name" value="<?php echo e($first_name); ?>" autocomplete="given-name">
                </div>
                <div class="field">
                    <label for="surname">Last Name</label>
                    <input id="surname" name="surname" type="text" placeholder="Last name" value="<?php echo e($surname); ?>" autocomplete="family-name">
                </div>
                <div class="field">
                    <label for="identifier">Email or Phone</label>
                    <input id="identifier" name="identifier" type="text" placeholder="Email address or phone number" value="<?php echo e($identifier); ?>" autocomplete="email">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" placeholder="Password" autocomplete="new-password">
                </div>
                <div class="field">
                    <label for="confirm_password">Confirm Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" placeholder="Confirm password" autocomplete="new-password">
                </div>

                <button type="submit" class="button">Create Account</button>
            </form>

            <div class="links">
                <a href="Login.php">Already have an account?</a>
                <a href="Home.php">Back to Home</a>
            </div>
        </section>
    </main>
</body>
</html>
