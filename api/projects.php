<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') { header('Allow: GET'); json_response(['error' => 'Method not allowed.'], 405); }
try {
    $pdo = db();
    $projects = $pdo->query("SELECT id,title,slug,category,description,cover_image,project_url,technologies,created_at FROM projects WHERE status='published' ORDER BY featured DESC, created_at DESC LIMIT 60")->fetchAll();
    $galleries = [];
    if ($projects) {
        $images = $pdo->query("SELECT pi.project_id,pi.image_path,pi.alt_text FROM project_images pi INNER JOIN projects p ON p.id=pi.project_id WHERE p.status='published' ORDER BY pi.project_id,pi.sort_order,pi.id")->fetchAll();
        foreach ($images as $image) $galleries[(int)$image['project_id']][] = ['image_path' => $image['image_path'], 'alt_text' => $image['alt_text']];
    }
    foreach ($projects as &$project) {
        $project['gallery'] = $galleries[(int)$project['id']] ?? [];
        $project['technologies'] = array_values(array_filter(array_map('trim', explode(',', (string)$project['technologies']))));
    }
    unset($project);
    json_response(['projects' => $projects]);
} catch (Throwable $exception) {
    error_log('NEXORA project API failure: ' . $exception->getMessage());
    json_response(['projects' => []], 200);
}
