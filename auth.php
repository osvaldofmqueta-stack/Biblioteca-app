<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['nivel_acesso'] ?? '') === 'admin';
}

function isBibliotecario(): bool
{
    return isLoggedIn() && in_array(
        $_SESSION['nivel_acesso'] ?? '',
        ['bibliotecario', 'admin'],
        strict: true
    );
}

function redirectIfNotLoggedIn(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function redirectIfNotAdmin(): void
{
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

function redirectIfNotBibliotecario(): void
{
    if (!isBibliotecario()) {
        header('Location: dashboard.php');
        exit();
    }
}
