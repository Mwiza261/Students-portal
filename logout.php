<?php
session_start();
require_once __DIR__ . '/db.php';

// Clear remember me cookie
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("DELETE FROM user_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->close();
    $mysqli->close();
    
    setcookie('remember_token', '', time() - 3600, "/");
}

// Destroy session
$_SESSION = array();
session_destroy();

// Redirect to login
header('Location: Login.php');
exit;
?>