<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

// If user is already logged in, redirect based on role
if (isLoggedIn()) {
    redirectByRole();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $role = sanitizeInput($_POST['role']);

    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all fields.';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, ['user', 'seller'])) {
        $error = 'Please select a valid account type.';
    } else {
        try {
            $db = new Database();

            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $db->execute($stmt, [$email]);

            if ($stmt->fetch()) {
                $error = 'Email address is already registered.';
            } else {
                // Create new user
                $hashedPassword = hashPassword($password);

                $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'active')");
                $db->execute($stmt, [$name, $email, $hashedPassword, $role]);

                $success = 'Account created successfully! You can now sign in.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again.';
            error_log($e->getMessage());
        }
    }
}

$pageTitle = 'Register - ECommerce';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/ecommerce/assets/css/style.css">

    <style>
        .auth-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }

        .auth-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }

        .auth-left {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }

        .auth-right {
            padding: 3rem;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .role-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .role-card:hover {
            border-color: #667eea;
            background-color: #f8f9ff;
        }

        .role-card.selected {
            border-color: #667eea;
            background-color: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="container">
            <div class="auth-card">
                <div class="row g-0">
                    <div class="col-lg-5 auth-left">
                        <div>
                            <h2 class="mb-4">
                                <i class="fas fa-store me-2"></i>
                                ECommerce
                            </h2>
                            <h4 class="mb-3">Join Us Today!</h4>
                            <p class="mb-4">Create your account and start your journey with us. Whether you're here to shop or sell, we've got you covered.</p>
                            <div class="text-center">
                                <i class="fas fa-users" style="font-size: 80px; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7 auth-right">
                        <h3 class="mb-4 text-center">Create Account</h3>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="name" class="form-label">
                                    <i class="fas fa-user me-2"></i>Full Name
                                </label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                <div class="invalid-feedback">
                                    Please enter your full name.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Email Address
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">
                                            <i class="fas fa-lock me-2"></i>Password
                                        </label>
                                        <input type="password" class="form-control" id="password" name="password" 
                                               minlength="6" required>
                                        <div class="invalid-feedback">
                                            Password must be at least 6 characters long.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">
                                            <i class="fas fa-lock me-2"></i>Confirm Password
                                        </label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                               minlength="6" required>
                                        <div class="invalid-feedback">
                                            Please confirm your password.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-user-tag me-2"></i>Account Type
                                </label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="role-card" data-role="user">
                                            <input type="radio" name="role" value="user" id="role_user" class="d-none" required>
                                            <div class="text-center">
                                                <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                                <h6>Customer</h6>
                                                <small class="text-muted">Shop for products</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="role-card" data-role="seller">
                                            <input type="radio" name="role" value="seller" id="role_seller" class="d-none" required>
                                            <div class="text-center">
                                                <i class="fas fa-store fa-2x mb-2"></i>
                                                <h6>Seller</h6>
                                                <small class="text-muted">Sell your products</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="invalid-feedback">
                                    Please select an account type.
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="/ecommerce/pages/terms.php" target="_blank">Terms of Service</a> 
                                    and <a href="/ecommerce/pages/privacy.php" target="_blank">Privacy Policy</a>
                                </label>
                                <div class="invalid-feedback">
                                    You must agree to the terms and conditions.
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-register">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="mb-0">Already have an account? 
                                <a href="login.php" class="text-decoration-none fw-bold">Sign In</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Role selection
        document.querySelectorAll('.role-card').forEach(card => {
            card.addEventListener('click', function() {
                // Remove selected class from all cards
                document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));

                // Add selected class to clicked card
                this.classList.add('selected');

                // Check the corresponding radio button
                const role = this.dataset.role;
                document.getElementById('role_' + role).checked = true;
            });
        });

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;

            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html>