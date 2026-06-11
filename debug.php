<?php
session_start();
echo "<h2>Session Debug</h2>";
echo "user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
echo "role: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";
echo "username: " . ($_SESSION['username'] ?? 'NOT SET') . "<br>";
echo "full_name: " . ($_SESSION['full_name'] ?? 'NOT SET') . "<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>