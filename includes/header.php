<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'ECommerce Store'; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/ecommerce/assets/css/style.css">

    <!-- Toastr for notifications -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/ecommerce/index.php">
                <i class="fas fa-store"></i> ECommerce
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isLoggedIn()): ?>
                        <?php if (getUserRole() === 'user'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/ecommerce/user/index.php">
                                    <i class="fas fa-home"></i> Home
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ecommerce/user/products.php">
                                    <i class="fas fa-box"></i> Products
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ecommerce/user/cart.php">
                                    <i class="fas fa-shopping-cart"></i> Cart
                                    <span class="badge bg-danger" id="cart-count">0</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ecommerce/user/orders.php">
                                    <i class="fas fa-receipt"></i> Orders
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ecommerce/user/contact.php">
                                    <i class="fas fa-envelope"></i> Contact
                                </a>
                            </li>
                        <?php elseif (getUserRole() === 'seller'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/ecommerce/seller/index.php">
                                    <i class="fas fa-home"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ecommerce/seller/products.php">
                                    <i class="fas fa-box"></i> Products
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ecommerce/seller/orders.php">
                                    <i class="fas fa-receipt"></i> Orders
                                </a>
                            </li>
                        <?php elseif (getUserRole() === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/ecommerce/admin/index.php">
                                    <i class="fas fa-home"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ecommerce/admin/products.php">
                                    <i class="fas fa-box"></i> Products
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ecommerce/admin/orders.php">
                                    <i class="fas fa-receipt"></i> Orders
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ecommerce/admin/users.php">
                                    <i class="fas fa-users"></i> Users
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo getUserName(); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/ecommerce/<?php echo getUserRole(); ?>/profile.php">
                                    <i class="fas fa-user-edit"></i> Profile
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/ecommerce/auth/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/ecommerce/auth/login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/ecommerce/auth/register.php">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container-fluid py-4">
        <?php displayNotification(); ?>