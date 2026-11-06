<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$config = require $root . '/app/config/config.php';
require $root . '/app/core/Database.php';

header('Content-Type: application/xml; charset=UTF-8');

$urls = [
    rtrim($config['base_url'], '/') . '/',
    rtrim($config['base_url'], '/') . '/?page=careers',
    rtrim($config['base_url'], '/') . '/?page=learning',
    rtrim($config['base_url'], '/') . '/?page=articles',
    rtrim($config['base_url'], '/') . '/?page=resources',
    rtrim($config['base_url'], '/') . '/?page=assessment',
    rtrim($config['base_url'], '/') . '/?page=about',
    rtrim($config['base_url'], '/') . '/?page=contact',
];

try {
    $db = Database::connect($config);
    foreach ($db->query('SELECT slug FROM careers ORDER BY id')->fetchAll(PDO::FETCH_COLUMN) as $slug) {
        $urls[] = rtrim($config['base_url'], '/') . '/?page=career&slug=' . rawurlencode((string) $slug);
    }
    foreach ($db->query('SELECT slug FROM learning_paths WHERE published = 1 ORDER BY id')->fetchAll(PDO::FETCH_COLUMN) as $slug) {
        $urls[] = rtrim($config['base_url'], '/') . '/?page=path&slug=' . rawurlencode((string) $slug);
    }
    foreach ($db->query('SELECT slug FROM articles WHERE status = "published" ORDER BY id')->fetchAll(PDO::FETCH_COLUMN) as $slug) {
        $urls[] = rtrim($config['base_url'], '/') . '/?page=article&slug=' . rawurlencode((string) $slug);
    }
} catch (PDOException $exception) {
    http_response_code(503);
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach (array_unique($urls) as $url) {
    echo '  <url><loc>' . htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc></url>\n";
}
echo "</urlset>\n";
