<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required.';
}

if (empty($email)) {
    $errors[] = 'Email is required.';
} elseif (!is_email($email)) {
    $errors[] = 'Please enter a valid email address.';
}

if (empty($message)) {
    $errors[] = 'Message is required.';
} elseif (strlen($message) < 10) {
    $errors[] = 'Message must be at least 10 characters.';
}

if (!empty($phone)) {
    $phone = normalize_phone($phone);
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

$mysqli = db_connect();

$ip_address = $_SERVER['REMOTE_ADDR'];
$check_sql = "SELECT COUNT(*) as count FROM contact_messages 
              WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$check_stmt = $mysqli->prepare($check_sql);
$check_stmt->bind_param('s', $ip_address);
$check_stmt->execute();
$result = $check_stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] >= 5) {
    echo json_encode(['success' => false, 'message' => 'Too many messages. Please try again later.']);
    $check_stmt->close();
    $mysqli->close();
    exit;
}
$check_stmt->close();

$user_id = null;
$user_check = $mysqli->prepare("SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1");
$user_check->bind_param('ss', $email, $phone);
$user_check->execute();
$user_result = $user_check->get_result();
if ($user_row = $user_result->fetch_assoc()) {
    $user_id = $user_row['id'];
}
$user_check->close();

$sql = "INSERT INTO contact_messages (name, email, phone, message, user_id, ip_address, user_agent, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'unread')";

$stmt = $mysqli->prepare($sql);
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$stmt->bind_param('ssssiss', $name, $email, $phone, $message, $user_id, $ip_address, $user_agent);

if ($stmt->execute()) {
    $to = "mwizamvula261@gmail.com";
    $subject = "New Contact Message from $name";
    $email_body = "Name: $name\nEmail: $email\nPhone: " . ($phone ?: 'Not provided') . "\n\nMessage:\n$message";
    @mail($to, $subject, $email_body, "From: $email\r\nReply-To: $email\r\n");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Thank you! Your message has been sent successfully. We\'ll get back to you within 24 hours.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}

$stmt->close();
$mysqli->close();
?>