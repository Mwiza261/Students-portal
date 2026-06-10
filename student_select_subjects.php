<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/db.php';

// Check if user is logged in
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

// Get selected class
$class_level = null;
$class_stmt = $mysqli->prepare("SELECT class_level FROM student_class_selections WHERE student_id = ? AND academic_year = ?");
if ($class_stmt) {
    $class_stmt->bind_param('ii', $student_id, $currentYear);
    $class_stmt->execute();
    $class_result = $class_stmt->get_result();
    if ($row = $class_result->fetch_assoc()) {
        $class_level = $row['class_level'];
    }
    $class_stmt->close();
}

if (!$class_level) {
    header('Location: student_select_class.php');
    exit;
}

// Get available subjects (simplified - you can expand this)
$subjects = [
    'Form 1' => ['Mathematics', 'English', 'Chichewa', 'Science', 'Social Studies', 'Agriculture'],
    'Form 2' => ['Mathematics', 'English', 'Chichewa', 'Biology', 'Chemistry', 'Physics', 'History'],
    'Form 3' => ['Mathematics', 'English', 'Chichewa', 'Biology', 'Chemistry', 'Physics', 'History', 'Geography'],
    'Form 4' => ['Mathematics', 'English', 'Chichewa', 'Biology', 'Chemistry', 'Physics', 'History', 'Geography', 'Commerce']
];

$available_subjects = $subjects[$class_level] ?? $subjects['Form 1'];
$mysqli->close();

$full_name = htmlspecialchars($user['first_name'] . ' ' . $user['surname']);
$initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['surname'], 0, 1));

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_subjects = $_POST['subjects'] ?? [];
    
    if (count($selected_subjects) < 4) {
        $message = "Please select at least 4 subjects.";
        $message_type = "error";
    } else {
        $_SESSION['subjects_selected'] = true;
        $_SESSION['selected_subjects'] = $selected_subjects;
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Your Subjects | Chigoneka School</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .card-header {
            background: linear-gradient(135deg, #1a3a6b 0%, #2c5282 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .card-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .info-bar {
            background: #f7fafc;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-item {
            text-align: center;
        }

        .info-label {
            font-size: 12px;
            color: #718096;
        }

        .info-value {
            font-size: 18px;
            font-weight: bold;
            color: #1a3a6b;
        }

        .card-body {
            padding: 30px;
        }

        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .subject-checkbox {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            background: #f7fafc;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid #e2e8f0;
        }

        .subject-checkbox:hover {
            background: #edf2f7;
            border-color: #667eea;
        }

        .subject-checkbox input {
            margin-right: 12px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .subject-checkbox label {
            cursor: pointer;
            flex: 1;
            font-weight: 500;
        }

        .info-box {
            background: #ebf8ff;
            border-left: 4px solid #3182ce;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: inherit;
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

        .message {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .message.error {
            background: #fed7d7;
            color: #c53030;
            border-left: 4px solid #c53030;
        }

        .counter {
            text-align: right;
            font-size: 14px;
            color: #718096;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>📚 Select Your Subjects</h1>
                <p>Choose the subjects you want to study for <?php echo htmlspecialchars($class_level); ?></p>
            </div>

            <div class="info-bar">
                <div class="info-item">
                    <div class="info-label">Your Class</div>
                    <div class="info-value"><?php echo htmlspecialchars($class_level); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Academic Year</div>
                    <div class="info-value"><?php echo $currentYear; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Minimum Subjects</div>
                    <div class="info-value">4</div>
                </div>
            </div>

            <div class="card-body">
                <?php if ($message): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="counter" id="counter">Selected: 0 / <?php echo count($available_subjects); ?></div>
                    
                    <div class="subjects-grid">
                        <?php foreach ($available_subjects as $subject): ?>
                        <div class="subject-checkbox">
                            <input type="checkbox" name="subjects[]" value="<?php echo htmlspecialchars($subject); ?>" id="subj_<?php echo md5($subject); ?>" onchange="updateCounter()">
                            <label for="subj_<?php echo md5($subject); ?>">📖 <?php echo htmlspecialchars($subject); ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="info-box">
                        <p>
                            <strong>ℹ️ Note:</strong><br>
                            • Select at least 4 subjects to continue.<br>
                            • You can change your subject selection during the registration period.<br>
                            • Contact the academic office if you need assistance.
                        </p>
                    </div>

                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        Complete Registration →
                    </button>
                    
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">
                        Cancel
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function updateCounter() {
            const checkboxes = document.querySelectorAll('input[name="subjects[]"]');
            let checked = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) checked++;
            });
            document.getElementById('counter').innerHTML = `Selected: ${checked} / ${checkboxes.length}`;
            
            const submitBtn = document.getElementById('submitBtn');
            if (checked < 4) {
                submitBtn.style.opacity = '0.5';
                submitBtn.title = 'Please select at least 4 subjects';
            } else {
                submitBtn.style.opacity = '1';
                submitBtn.title = '';
            }
        }
        
        updateCounter();
    </script>
</body>
</html>