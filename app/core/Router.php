<?php

declare(strict_types=1);

final class Router
{
    public static function page(): string
    {
        $page = (string) ($_GET['page'] ?? 'home');
        return preg_match('/^[a-z0-9-]+$/', $page) ? $page : '404';
    }
}
