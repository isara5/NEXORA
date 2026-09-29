<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
if (!empty($_SESSION['admin_id'])) { header('Location: /admin/dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? null)) { $error = 'Session expired. Refresh and try again.'; }
    else {
        try {
            $pdo = db(); $ip = hash('sha256', client_ip());
            $stmt = $pdo->prepare("SELECT hits, reset_at FROM rate_limits WHERE bucket='admin_login' AND key_hash=?"); $stmt->execute([$ip]); $attempt = $stmt->fetch();
            if ($attempt && (int)$attempt['hits'] >= 8 && strtotime($attempt['reset_at'] . ' UTC') > time()) { $error = 'Too many login attempts. Please wait before trying again.'; }
            else {
                $email = strtolower(request_string($_POST, 'email')); $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
                $find = $pdo->prepare('SELECT id,email,password_hash FROM admins WHERE email=? LIMIT 1'); $find->execute([$email]); $admin = $find->fetch();
                if ($admin && password_verify($password, $admin['password_hash'])) {
                    if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) { $upd = $pdo->prepare('UPDATE admins SET password_hash=? WHERE id=?'); $upd->execute([password_hash($password, PASSWORD_DEFAULT), $admin['id']]); }
                    session_regenerate_id(true); $_SESSION['admin_id'] = (int)$admin['id']; $_SESSION['admin_email'] = $admin['email']; $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); $_SESSION['last_activity'] = time();
                    $clear = $pdo->prepare("DELETE FROM rate_limits WHERE bucket='admin_login' AND key_hash=?"); $clear->execute([$ip]); header('Location: /admin/dashboard.php'); exit;
                }
                $hit = $pdo->prepare("INSERT INTO rate_limits(bucket,key_hash,hits,reset_at) VALUES('admin_login',?,1,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 15 MINUTE)) ON DUPLICATE KEY UPDATE hits=IF(reset_at<UTC_TIMESTAMP(),1,hits+1),reset_at=IF(reset_at<UTC_TIMESTAMP(),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 15 MINUTE),reset_at)"); $hit->execute([$ip]);
                $error = 'Email or password was not recognized.';
            }
        } catch (Throwable $exception) { error_log('NEXORA admin login error: ' . $exception->getMessage()); $error = 'Admin access is unavailable. Check database setup.'; }
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin sign in · NEXORA</title><link rel="stylesheet" href="/admin/admin.css"></head><body class="login-body"><form class="login-card" method="post"><a class="admin-brand" href="/"><img src="/assets/images/nexora-mark.svg" alt=""><span>NEXORA<small>STUDIO ADMIN</small></span></a><p class="login-eyebrow">CONTROL ROOM / SECURE ACCESS</p><h1>Welcome back.</h1><p class="login-sub">Sign in to manage the NEXORA website.</p><?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Email address<input name="email" type="email" autocomplete="username" maxlength="190" required></label><label>Password<input name="password" type="password" autocomplete="current-password" maxlength="200" required></label><button class="admin-button" type="submit">SIGN IN <span>↗</span></button><a class="back-home" href="/">← Back to NEXORA website</a></form></body></html>
