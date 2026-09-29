<?php
declare(strict_types=1);

// Configure NEXORA through environment variables on the hosting account.
define('APP_ROOT', dirname(__DIR__));
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_URL', rtrim(getenv('APP_URL') ?: '', '/'));
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'nexora');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('UPLOAD_MAX_BYTES', 5 * 1024 * 1024);
define('UPLOAD_DIR', APP_ROOT . '/uploads');
define('COMPANY_DEFAULTS', [
    'company_name' => 'NEXORA', 'tagline' => 'DIGITAL SOLUTIONS',
    'whatsapp' => '+94 72 483 2444', 'email' => '', 'phone' => '+94 72 483 2444',
    'address' => '', 'hero_heading' => 'WE BUILD|DIGITAL EXPERIENCES.',
    'hero_description' => 'NEXORA transforms ideas into powerful digital experiences through modern web development, app development, digital marketing and creative solutions.',
    'social_links' => '{}', 'stat_projects' => '[ADD YOUR INFORMATION]',
    'stat_clients' => '[ADD YOUR INFORMATION]', 'stat_solutions' => '[ADD YOUR INFORMATION]',
    'stat_experience' => '[ADD YOUR INFORMATION]'
]);

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('nexora_session');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    if (DB_USER === '' || DB_NAME === '') throw new RuntimeException('Database is not configured.');
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    return $pdo;
}

function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function text_length(string $value): int {
    if (function_exists('mb_strlen')) return mb_strlen($value, 'UTF-8');
    $count = preg_match_all('/./us', $value, $matches);
    return $count === false ? strlen($value) : $count;
}
function request_string(array $source, string $key): string {
    $value = $source[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}
function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrf_valid(?string $token): bool {
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
function require_admin(): void {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    if (!empty($_SESSION['admin_id']) && isset($_SESSION['last_activity']) && time() - (int)$_SESSION['last_activity'] > 1800) {
        unset($_SESSION['admin_id'], $_SESSION['admin_email'], $_SESSION['csrf_token']);
        session_regenerate_id(true);
    }
    if (empty($_SESSION['admin_id'])) { header('Location: /admin/login.php'); exit; }
    $_SESSION['last_activity'] = time();
}
function setting_map(PDO $pdo): array {
    try {
        $rows = $pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll();
        $settings = COMPANY_DEFAULTS;
        foreach ($rows as $row) $settings[$row['setting_key']] = $row['setting_value'];
        return $settings;
    } catch (Throwable $exception) { return COMPANY_DEFAULTS; }
}
function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45); }
