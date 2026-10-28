<?php
declare(strict_types=1);

/*
 * Local defaults for XAMPP. Before live deployment, move credentials to
 * environment variables and set secure cookies / HTTPS.
 */
const APP_NAME = 'LibraryHub';
const APP_BASE_PATH = '/libraryhub/public';
const DB_HOST = '127.0.0.1';
const DB_NAME = 'libraryhub';
const DB_USER = 'root';
const DB_PASS = '';
const DEFAULT_LOAN_DAYS = 14;
const DEFAULT_DAILY_FINE = 5.00; // Set to 0 to disable charges.
const CURRENCY_CODE = 'NPR';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $https,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrf_check(): void {
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Invalid or expired form token. Go back and try again.');
    }
}
function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}
function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}
