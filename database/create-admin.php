<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/config/config.php';
$email = strtolower(trim((string)(getenv('NEXORA_ADMIN_EMAIL') ?: '')));
$password = (string)(getenv('NEXORA_ADMIN_PASSWORD') ?: '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 14) { fwrite(STDERR, "Set NEXORA_ADMIN_EMAIL and NEXORA_ADMIN_PASSWORD (14+ characters) in the environment, then run this CLI script.\n"); exit(1); }
try { $stmt = db()->prepare('INSERT INTO admins(email,password_hash) VALUES(?,?) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)'); $stmt->execute([$email,password_hash($password,PASSWORD_DEFAULT)]); fwrite(STDOUT, "Admin account created/updated for {$email}. Remove the environment password after setup.\n"); }
catch (Throwable $exception) { error_log($exception->getMessage()); fwrite(STDERR, "Could not create the admin account. Check database configuration and schema.\n"); exit(1); }
