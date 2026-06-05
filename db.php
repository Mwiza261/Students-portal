<?php
/**
 * Database Configuration and Helper Functions
 */

define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_NAME',    'chigoneka');
define('DB_PORT',    3306);
define('DB_CHARSET', 'utf8mb4');

error_reporting(E_ALL);
ini_set('display_errors', 1);

function db_connect(): mysqli
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    if ($mysqli->connect_errno) {
        die('Database connection failed: ' . $mysqli->connect_error);
    }

    $mysqli->set_charset(DB_CHARSET);
    return $mysqli;
}

function is_email(string $value): bool
{
    return filter_var(trim($value), FILTER_VALIDATE_EMAIL) !== false;
}

function normalize_phone(string $value): string
{
    return preg_replace('/[^0-9+]/', '', $value);
}

function get_user_by_id(int $id): ?array
{
    $mysqli = db_connect();
    $stmt   = $mysqli->prepare(
        'SELECT id, username, first_name, surname, email, phone, role, created_at
         FROM users WHERE id = ? LIMIT 1'
    );

    if (!$stmt) { $mysqli->close(); return null; }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    $mysqli->close();

    return $user;
}

function get_user_by_username(string $username, ?string $role = null): ?array
{
    $mysqli = db_connect();

    if ($role) {
        $stmt = $mysqli->prepare(
            'SELECT id, username, first_name, surname, email, phone, password_hash, role, created_at
             FROM users WHERE username = ? AND role = ? LIMIT 1'
        );
        if (!$stmt) { $mysqli->close(); return null; }
        $stmt->bind_param('ss', $username, $role);
    } else {
        $stmt = $mysqli->prepare(
            'SELECT id, username, first_name, surname, email, phone, password_hash, role, created_at
             FROM users WHERE username = ? LIMIT 1'
        );
        if (!$stmt) { $mysqli->close(); return null; }
        $stmt->bind_param('s', $username);
    }

    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    $mysqli->close();

    return $user;
}

function get_user_by_identifier(string $identifier, ?string $role = null): ?array
{
    $mysqli = db_connect();

    if (is_email($identifier)) {
        if ($role) {
            $stmt = $mysqli->prepare(
                'SELECT id, username, first_name, surname, email, phone, password_hash, role, created_at
                 FROM users WHERE email = ? AND role = ? LIMIT 1'
            );
            if (!$stmt) { $mysqli->close(); return null; }
            $stmt->bind_param('ss', $identifier, $role);
        } else {
            $stmt = $mysqli->prepare(
                'SELECT id, username, first_name, surname, email, phone, password_hash, role, created_at
                 FROM users WHERE email = ? LIMIT 1'
            );
            if (!$stmt) { $mysqli->close(); return null; }
            $stmt->bind_param('s', $identifier);
        }
    } else {
        $identifier = normalize_phone($identifier);
        if ($role) {
            $stmt = $mysqli->prepare(
                'SELECT id, username, first_name, surname, email, phone, password_hash, role, created_at
                 FROM users WHERE phone = ? AND role = ? LIMIT 1'
            );
            if (!$stmt) { $mysqli->close(); return null; }
            $stmt->bind_param('ss', $identifier, $role);
        } else {
            $stmt = $mysqli->prepare(
                'SELECT id, username, first_name, surname, email, phone, password_hash, role, created_at
                 FROM users WHERE phone = ? LIMIT 1'
            );
            if (!$stmt) { $mysqli->close(); return null; }
            $stmt->bind_param('s', $identifier);
        }
    }

    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    $mysqli->close();

    return $user;
}

// ── Helpers — defined once here, used everywhere ─────────────

if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('hash_password')) {
    function hash_password(string $plain): string {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}

if (!function_exists('verify_password')) {
    function verify_password(string $plain, string $hash): bool {
        return password_verify($plain, $hash);
    }
}

if (!function_exists('needs_rehash')) {
    function needs_rehash(string $hash): bool {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}