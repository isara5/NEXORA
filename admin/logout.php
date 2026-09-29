<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid($_POST['csrf_token'] ?? null)) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) { $params = session_get_cookie_params(); setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']); }
    session_destroy(); header('Location: /admin/login.php'); exit;
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/admin/admin.css"><title>Sign out · NEXORA</title></head><body class="login-body"><form method="post" class="login-card"><h1>Sign out?</h1><p class="login-sub">End this admin session securely.</p><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="admin-button">SIGN OUT</button><a class="back-home" href="/admin/dashboard.php">Cancel</a></form></body></html>
