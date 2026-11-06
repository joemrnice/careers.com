<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/app/config/config.php';
header('Content-Type: text/plain; charset=UTF-8');

echo "User-agent: *\n";
echo "Disallow: /?page=admin\n";
echo "Disallow: /?page=admin-path\n";
echo "Disallow: /?page=users\n";
echo "Disallow: /?page=moderation\n";
echo "Disallow: /?page=dashboard\n";
echo "Disallow: /?page=profile\n";
echo 'Sitemap: ' . rtrim($config['base_url'], '/') . "/sitemap.xml\n";
