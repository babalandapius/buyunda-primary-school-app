<?php
/**
 * Authentication and role validation helpers.
 * Provides session-based login, logout, and access checks for Admin and Teacher roles.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['user']['role'] ?? '') === 'ADMIN';
}

function isTeacher(): bool
{
    return isLoggedIn() && ($_SESSION['user']['role'] ?? '') === 'TEACHER';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireRole(string $role): void
{
    requireLogin();

    if ((currentUser()['role'] ?? '') !== $role) {
        redirect('login.php');
    }
}

function loginUser(string $email, string $password): ?array
{
    $email = strtolower(trim($email));
    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return null;
    }

    unset($user['password_hash']);
    $_SESSION['user'] = $user;

    return $user;
}

function logoutUser(): void
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