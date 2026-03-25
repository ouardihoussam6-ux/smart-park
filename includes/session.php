<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/User.php';

session_start();

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function current_user(): ?array
{
    if (is_logged_in()) {
        return User::findById((int)$_SESSION['user_id']);
    }
    return null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user && $user['role'] === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        die('Accès refusé. Vous devez être administrateur pour voir cette page.');
    }
}

function login_user(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
}

function logout_user(): void
{
    session_unset();
    session_destroy();
}
