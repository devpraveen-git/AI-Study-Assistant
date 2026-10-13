<?php
declare(strict_types=1);

namespace App\Core;

use App\Config\Database;

final class Auth
{
    public static function user(): ?array
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        return [
            'id' => (int) $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'User',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'student',
        ];
    }

    public static function id(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function isAdmin(): bool
    {
        return self::check() && ($_SESSION['user_role'] ?? '') === 'admin';
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('warning', 'Please sign in to access your study assistant.');
            redirect('login.php');
        }
    }

    public static function requireAdmin(): void
    {
        if (!self::isAdmin()) {
            flash('danger', 'Access denied. Administrator privileges required.');
            redirect('login.php');
        }
    }

    public static function attempt(string $email, string $password): ?array
    {
        $user = Database::fetchOne("SELECT id, name, email, password, role, is_active FROM users WHERE email = ? LIMIT 1", [$email]);
        if ($user && password_verify($password, $user['password']) && (int) $user['is_active'] === 1) {
            self::login($user);
            return $user;
        }
        return null;
    }

    public static function login(array $user): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id(true);
        }
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }
}
