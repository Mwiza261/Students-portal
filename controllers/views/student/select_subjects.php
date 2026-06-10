<?php
/* ──────────────────────────────────────────────
   Student Class Selection View
   File: student_select_class.php
   ────────────────────────────────────────────── */

session_start();
require_once __DIR__ . '/db.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['authenticated'], $_SESSION['user_id']) || $_SESSION['authenticated'] !== true) {
    header('Location: Login.php');
    exit;
}

$user = get_user_by_id((int) $_SESSION['user_id']);
if (!$user || $user['role'] !== 'student') {
    session_unset();
    session_destroy();
    header('Location: Login.php');
    exit;
}

$mysqli = db_connect();
$student_id = (int) $user['id'];
$currentYear = date('Y');

// Check if class already selected
$class_stmt = $mysqli->prepare(
    "SELECT class_level FROM student_class_selections 
     WHERE student_id = ? AND academic_year = ?"
);
$class_stmt->bind_param('ii', $student_id, $currentYear);
$class_stmt->execute();
$class_result = $class_stmt->get_result();
$existing_class = $class_result->fetch_assoc();
$class_stmt->close();

// If class already selected, redirect to dashboard
if ($existing_class) {
    header('Location: index.php');
    exit;
}

$mysqli->close();

$full_name = htmlspecialchars($user['first_name'] . ' ' . $user['surname']);
$initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['surname'], 0, 1));
$greeting = date('H') < 12 ? 'Good morning' : (date('H') < 17 ? 'Good afternoon' : 'Good evening');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Your Class | Chigoneka School Student Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            width: 100%;
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

        .card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .card-header {
            background: linear-gradient(135deg, #1a3a6b 0%, #2c5282 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .card-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .card-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .student-info {
            background: #f7fafc;
            padding: 20px 30px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .student-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 24px;
        }

        .student-details h3 {
            font-size: 18px;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .student-details p {
            font-size: 13px;
            color: #718096;
        }

        .card-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .class-dropdown {
            width: 100%;
            padding: 14px 16px;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .class-dropdown:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .info-box {
            background: #ebf8ff;
            border-left: 4px solid #3182ce;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .info-box p {
            font-size: 13px;
            color: #2c5282;
            line-height: 1.5;
        }

        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
            margin-top: 10px;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .flash-message {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            animation: slideDown 0.3s ease-out;
        }

        .flash-error {
            background: #fed7d7;
            color: #c53030;
            border-left: 4px solid #c53030;
        }

        .flash-success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #38a169;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 640px) {
            .card-header {
                padding: 30px 20px;
            }
            
            .card-body {
                padding: 25px 20px;
            }
            
            .card-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>🎓 Select Your Class Level</h1>
                <p>Choose your class for the <?php echo $currentYear; ?> academic year</p>
            </div>

            <div class="student-info">
                <div class="student-avatar">
                    <?php echo $initials; ?>
                </div>
                <div class="student-details">
                    <h3><?php echo $greeting . ', ' . htmlspecialchars($user['first_name']); ?>!</h3>
                    <p><?php echo htmlspecialchars($user['username']); ?> • <?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>

            <div class="card-body">
                <?php if (isset($_SESSION['flash'])): ?>
                    <div class="flash-message flash-<?php echo $_SESSION['flash']['type']; ?>">
                        <?php echo htmlspecialchars($_SESSION['flash']['message']); ?>
                    </div>
                    <?php unset($_SESSION['flash']); ?>
                <?php endif; ?>

                <form method="POST" action="process_class_selection.php">
                    <div class="form-group">
                        <label>Select Your Class Level *</label>
                        <select name="class_level" class="class-dropdown" required>
                            <option value="">-- Select Class Level --</option>
                            <option value="Form 1">📚 Form 1 - First Year (Foundation)</option>
                            <option value="Form 2">📖 Form 2 - Second Year (Development)</option>
                            <option value="Form 3">🔬 Form 3 - Third Year (Specialization)</option>
                            <option value="Form 4">🎯 Form 4 - Final Year (MSCE Preparation)</option>
                        </select>
                    </div>

                    <div class="info-box">
                        <p>
                            <strong>ℹ️ Important Information:</strong><br>
                            • After selecting your class, you will be able to choose your subjects.<br>
                            • Some subjects are compulsory and will be automatically selected.<br>
                            • You can select between 6-10 subjects depending on your class level.<br>
                            • Your selection can be changed during the registration period.
                        </p>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Continue to Subject Selection →
                    </button>
                    
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">
                        Cancel
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>