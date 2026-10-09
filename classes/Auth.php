<?php
// Everything about "who is logged in": sessions, login, logout, page guards, flash messages.
class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function id(): int
    {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    public static function name(): string
    {
        return (string)($_SESSION['name'] ?? '');
    }

    public static function login(int $id, string $name): void
    {
        session_regenerate_id(true); // new session ID on login (stops session fixation)
        $_SESSION['user_id'] = $id;
        $_SESSION['name'] = $name;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    // Pages for members only: visitors are sent to the login page.
    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: login.php');
            exit;
        }
    }

    // Login and register pages: members are sent to the home page.
    public static function requireGuest(): void
    {
        if (self::check()) {
            header('Location: index.php');
            exit;
        }
    }

    // A one-time message shown on the next page (like "Recipe posted.").
    public static function flash(string $message, string $type = 'ok'): void
    {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    }

    public static function takeFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}