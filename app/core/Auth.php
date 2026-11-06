<?php

declare(strict_types=1);

final class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function requireLogin(array $config): void
    {
        if (!self::check()) {
            header('Location: ' . url($config, '?page=login'));
            exit;
        }
    }

    public static function isAdministrator(PDO $db): bool
    {
        if (!self::check()) {
            return false;
        }

        $query = $db->prepare(
            'SELECT 1 FROM user_roles ur
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = :user_id AND r.name = "administrator"
             LIMIT 1'
        );
        $query->execute(['user_id' => self::user()['id']]);
        return (bool) $query->fetchColumn();
    }

    public static function requireAdministrator(array $config, PDO $db): void
    {
        self::requireLogin($config);
        if (!self::isAdministrator($db)) {
            flash('error', 'Administrator access is required for that page.');
            redirect($config, '?page=dashboard');
        }
    }
}
