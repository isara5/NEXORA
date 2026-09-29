<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') { header('Allow: GET'); json_response(['error' => 'Method not allowed.'], 405); }
try{$services=db()->query('SELECT service_key,title,description,sort_order FROM services WHERE is_active=1 ORDER BY sort_order,id')->fetchAll();json_response(['available'=>true,'services'=>$services]);}catch(Throwable $e){json_response(['available'=>false,'services'=>[]]);}
