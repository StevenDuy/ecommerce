<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/utils.php';

// Check if user is logged in and redirect based on role
if (isLoggedIn()) {
    redirectByRole();
} else {
    // Redirect to login page
    header('Location: /ecommerce/auth/login.php');
    exit();
}
?>