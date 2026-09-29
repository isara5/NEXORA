<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/config.php';
require_admin();
function admin_flash(string $message, string $kind = 'success'): void { $_SESSION['admin_flash'] = ['message' => $message, 'kind' => $kind]; }
function take_admin_flash(): ?array { $flash = $_SESSION['admin_flash'] ?? null; unset($_SESSION['admin_flash']); return $flash; }
