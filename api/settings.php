<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') { header('Allow: GET'); json_response(['error' => 'Method not allowed.'], 405); }
try {
    $settings = setting_map(db());
    json_response(['settings' => $settings]);
} catch (Throwable $exception) {
    json_response(['settings' => COMPANY_DEFAULTS]);
}
