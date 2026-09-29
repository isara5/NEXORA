<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
header('Location: ' . (isset($_SESSION['admin_id']) ? '/admin/dashboard.php' : '/admin/login.php'));
exit;
