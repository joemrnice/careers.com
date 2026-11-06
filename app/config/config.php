<?php

$configPath = __DIR__ . '/config.local.php';

return file_exists($configPath)
    ? require $configPath
    : require __DIR__ . '/config.example.php';
