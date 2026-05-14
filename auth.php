<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['nivel_acesso'] == 'admin';
}

function isBibliotecario() {
    return isLoggedIn() && ($_SESSION['nivel_acesso'] == 'bibliotecario' || $_SESSION['nivel_acesso'] == 'admin');
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function redirectIfNotAdmin() {
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

function redirectIfNotBibliotecario() {
    if (!isBibliotecario()) {
        header('Location: dashboard.php');
        exit();
    }
}
?>
