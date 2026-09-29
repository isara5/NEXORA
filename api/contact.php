<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Method not allowed.'], 405);
if (!csrf_valid($_POST['csrf_token'] ?? null)) json_response(['error' => 'Your session expired. Refresh the page and try again.'], 419);
if (!empty($_POST['website'])) json_response(['message' => 'Thanks — your request has been received.']);
try {
    $pdo = db();
    $key = hash('sha256', client_ip());
    $limit = $pdo->prepare("INSERT INTO rate_limits (bucket, key_hash, hits, reset_at) VALUES ('contact', ?, 1, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 HOUR)) ON DUPLICATE KEY UPDATE hits=IF(reset_at < UTC_TIMESTAMP(),1,hits+1), reset_at=IF(reset_at < UTC_TIMESTAMP(),DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 HOUR),reset_at)");
    $limit->execute([$key]);
    $check = $pdo->prepare("SELECT hits FROM rate_limits WHERE bucket='contact' AND key_hash=?");
    $check->execute([$key]);
    if ((int)$check->fetchColumn() > 5) json_response(['error' => 'Too many requests. Please try again later.'], 429);

    $name = request_string($_POST, 'name');
    $email = request_string($_POST, 'email');
    $phone = request_string($_POST, 'phone');
    $service = request_string($_POST, 'service');
    $budget = request_string($_POST, 'budget');
    $message = request_string($_POST, 'message');
    $allowedServices = ['Web Development', 'App Development', 'Digital Marketing', 'Graphic Design', 'Post Editing / Creative Content', 'Custom Digital Solutions', 'Other'];
    if ($name === '' || text_length($name) > 120 || $email === '' || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($service, $allowedServices, true) || $message === '' || text_length($message) > 4000) {
        json_response(['error' => 'Please check the required fields and try again.'], 422);
    }
    if (text_length($phone) > 40 || text_length($budget) > 100) json_response(['error' => 'One or more fields are too long.'], 422);
    $insert = $pdo->prepare("INSERT INTO contact_messages (name,email,phone,service,budget,message,status) VALUES (:name,:email,:phone,:service,:budget,:message,'new')");
    $insert->execute(['name' => $name, 'email' => $email, 'phone' => $phone, 'service' => $service, 'budget' => $budget, 'message' => $message]);
    json_response(['message' => 'Thanks — your project request has been received. NEXORA will be in touch.'], 201);
} catch (Throwable $exception) {
    error_log('NEXORA contact submission failure: ' . $exception->getMessage());
    json_response(['error' => 'We could not save your request right now. Please try again later.'], 500);
}
