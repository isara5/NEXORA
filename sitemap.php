<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
header('Content-Type: application/xml; charset=utf-8');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$origin = rtrim(APP_URL ?: ($scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')), '/');
$home = htmlspecialchars($origin . '/', ENT_XML1 | ENT_QUOTES, 'UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?= $home ?></loc>
    <changefreq>monthly</changefreq>
    <priority>1.0</priority>
  </url>
</urlset>
