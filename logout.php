<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Clear remember me cookies
if (isset($_COOKIE['remember_token_student'])) {
    setcookie('remember_token_student', '', time() - 3600, '/');
}
if (isset($_COOKIE['saved_student_identifier'])) {
    setcookie('saved_student_identifier', '', time() - 3600, '/');
}
if (isset($_COOKIE['saved_student_password'])) {
    setcookie('saved_student_password', '', time() - 3600, '/');
}
if (isset($_COOKIE['saved_staff_username'])) {
    setcookie('saved_staff_username', '', time() - 3600, '/');
}
if (isset($_COOKIE['saved_staff_password'])) {
    setcookie('saved_staff_password', '', time() - 3600, '/');
}
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}
if (isset($_COOKIE['saved_identifier'])) {
    setcookie('saved_identifier', '', time() - 3600, '/');
}
if (isset($_COOKIE['saved_password'])) {
    setcookie('saved_password', '', time() - 3600, '/');
}
if (isset($_COOKIE['saved_username'])) {
    setcookie('saved_username', '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to home page (index.php)
header('Location: index.php');
exit;
?>