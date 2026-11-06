<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(array $config, string $path = ''): string
{
    return rtrim($config['base_url'], '/') . '/' . ltrim($path, '/');
}

function asset(array $config, string $path): string
{
    return url($config, 'assets/' . ltrim($path, '/'));
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function old_input(string $key): string
{
    return e($_POST[$key] ?? '');
}

function redirect(array $config, string $path): never
{
    header('Location: ' . url($config, $path));
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $value;
}
