<?php
require_once __DIR__ . '/db.php';

function setupDatabase(): array
{
    $result = [
        'success' => false,
        'messages' => [],
        'error' => '',
    ];

    $root = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($root->connect_errno) {
        $result['error'] = 'Database setup failed: ' . $root->connect_error;
        return $result;
    }

    $createDbSql = 'CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    if (!$root->query($createDbSql)) {
        $result['error'] = 'Unable to create database: ' . $root->error;
        $root->close();
        return $result;
    }

    if (!$root->select_db(DB_NAME)) {
        $result['error'] = 'Unable to select database: ' . $root->error;
        $root->close();
        return $result;
    }

    $createTableSql = <<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student','staff') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_email (email),
    UNIQUE KEY unique_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

    if (!$root->query($createTableSql)) {
        $result['error'] = 'Unable to create users table: ' . $root->error;
        $root->close();
        return $result;
    }

    $alterStatements = [
        "ALTER TABLE users ADD COLUMN first_name VARCHAR(100) NOT NULL DEFAULT '' AFTER username",
        "ALTER TABLE users ADD COLUMN surname VARCHAR(100) NOT NULL DEFAULT '' AFTER first_name",
        "ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT NULL AFTER surname",
        "ALTER TABLE users ADD COLUMN phone VARCHAR(50) DEFAULT NULL AFTER email",
        "ALTER TABLE users ADD UNIQUE INDEX unique_email (email)",
        "ALTER TABLE users ADD UNIQUE INDEX unique_phone (phone)"
    ];

    foreach ($alterStatements as $statement) {
        if (!$root->query($statement) && !in_array($root->errno, [1060, 1061], true)) {
            $result['error'] = 'Unable to update users table: ' . $root->error;
            $root->close();
            return $result;
        }
    }

    $insertSql = 'INSERT INTO users (username, first_name, surname, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), first_name = VALUES(first_name), surname = VALUES(surname), email = VALUES(email), phone = VALUES(phone), role = VALUES(role)';
    $stmt = $root->prepare($insertSql);
    if (!$stmt) {
        $result['error'] = 'Unable to prepare insert statement: ' . $root->error;
        $root->close();
        return $result;
    }

    $studentUsername = 'student';
    $studentFirst = 'Default';
    $studentSurname = 'Student';
    $studentEmail = 'student@chigoneka.local';
    $studentPhone = '+265100000000';
    $studentHash = password_hash('StudentPass1', PASSWORD_DEFAULT);
    $studentRole = 'student';
    $stmt->bind_param('sssssss', $studentUsername, $studentFirst, $studentSurname, $studentEmail, $studentPhone, $studentHash, $studentRole);
    $stmt->execute();

    $staffUsername = 'staff';
    $staffFirst = 'Default';
    $staffSurname = 'Staff';
    $staffEmail = null;
    $staffPhone = null;
    $staffHash = password_hash('StaffPass123', PASSWORD_DEFAULT);
    $staffRole = 'staff';
    $stmt->bind_param('sssssss', $staffUsername, $staffFirst, $staffSurname, $staffEmail, $staffPhone, $staffHash, $staffRole);
    $stmt->execute();

    $stmt->close();
    $root->close();

    $result['success'] = true;
    $result['messages'] = [
        'Database created or already exists: ' . DB_NAME,
        'Table created or already exists: users',
        'Seeded users: student / Staff / default credentials updated if already present',
    ];

    return $result;
}

if (php_sapi_name() === 'cli') {
    $result = setupDatabase();
    if (!$result['success']) {
        fwrite(STDERR, $result['error'] . "\n");
        exit(1);
    }

    foreach ($result['messages'] as $message) {
        echo $message . "\n";
    }
    echo "\nStudent login: student / StudentPass1\n";
    echo "Staff login: staff / StaffPass123\n";
    exit(0);
}

$setupResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $setupResult = setupDatabase();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chigoneka Database Setup</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #0f172a 100%);
            color: #e2e8f0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            width: min(560px, calc(100% - 32px));
            padding: 32px;
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(148, 163, 184, 0.16);
            border-radius: 24px;
            box-shadow: 0 24px 80px rgba(12, 35, 80, 0.35);
        }
        h1 {
            margin: 0 0 16px;
            font-size: 2rem;
            color: #f8fafc;
        }
        p {
            margin: 0 0 24px;
            color: #cbd5e1;
            line-height: 1.8;
        }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 22px;
            border-radius: 999px;
            border: none;
            background: #4f46e5;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
        }
        .button:hover {
            background: #6366f1;
            transform: translateY(-1px);
        }
        .message {
            margin-bottom: 16px;
            padding: 16px;
            border-radius: 18px;
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(148, 163, 184, 0.16);
        }
        .success {
            color: #a7f3d0;
            border-color: rgba(34, 197, 94, 0.35);
            background: rgba(16, 185, 129, 0.1);
        }
        .error {
            color: #fecaca;
            border-color: rgba(248, 113, 113, 0.35);
            background: rgba(248, 113, 113, 0.1);
        }
        .status-list {
            margin: 0;
            padding-left: 1.25rem;
            color: #cbd5e1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Chigoneka Database Setup</h1>
        <p>Click the button below once to create the MySQL database, the <code>users</code> table, and the default student/staff accounts. Student logins support email address or phone number.</p>

        <?php if (isset($setupResult)): ?>
            <div class="message <?php echo $setupResult['success'] ? 'success' : 'error'; ?>">
                <?php if ($setupResult['success']): ?>
                    <strong>Setup completed successfully.</strong>
                    <ul class="status-list">
                        <?php foreach ($setupResult['messages'] as $message): ?>
                            <li><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p><strong>Student login:</strong> student / StudentPass1<br><strong>Staff login:</strong> staff / StaffPass123</p>
                <?php else: ?>
                    <strong>Setup failed:</strong>
                    <p><?php echo htmlspecialchars($setupResult['error'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <button type="submit" class="button">Initialize Database</button>
        </form>
    </div>
</body>
</html>
