<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /ecommerce/auth/login.php');
        exit();
    }
}

function requireRole($role) {
    if (!isLoggedIn() || $_SESSION['role'] !== $role) {
        header('Location: /ecommerce/index.php');
        exit();
    }
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function getUserName() {
    return $_SESSION['user_name'] ?? null;
}

function redirectByRole() {
    if (!isLoggedIn()) {
        header('Location: /ecommerce/auth/login.php');
        exit();
    }

    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: /ecommerce/admin/index.php');
            break;
        case 'seller':
            header('Location: /ecommerce/seller/index.php');
            break;
        case 'user':
            header('Location: /ecommerce/user/index.php');
            break;
        default:
            header('Location: /ecommerce/auth/login.php');
    }
    exit();
}

function logout() {
    session_destroy();
    header('Location: /ecommerce/auth/login.php');
    exit();
}
?>