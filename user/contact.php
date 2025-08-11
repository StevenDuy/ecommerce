<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

requireRole('user');

$pageTitle = 'Contact Us - ECommerce';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $subject = sanitizeInput($_POST['subject']);
    $message = sanitizeInput($_POST['message']);

    $errors = [];

    if (empty($name)) {
        $errors[] = 'Name is required.';
    }

    if (empty($email) || !validateEmail($email)) {
        $errors[] = 'Valid email is required.';
    }

    if (empty($subject)) {
        $errors[] = 'Subject is required.';
    }

    if (empty($message)) {
        $errors[] = 'Message is required.';
    }

    if (empty($errors)) {
        // In a real application, you would send this via email
        // For now, we'll just show a success message

        // You can integrate with email services like PHPMailer, SendGrid, etc.
        $to = 'support@ecommerce.com';
        $emailSubject = 'Contact Form: ' . $subject;
        $emailBody = "Name: $name
Email: $email

Message:
$message";
        $headers = "From: $email
Reply-To: $email
";

        // Uncomment the line below to actually send email
        // mail($to, $emailSubject, $emailBody, $headers);

        showNotification('success', 'Thank you for your message! We will get back to you soon.');

        // Clear form data
        $_POST = [];
    } else {
        showNotification('error', implode(' ', $errors));
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Contact Us</li>
        </ol>
    </nav>

    <!-- Contact Header -->
    <div class="text-center mb-5">
        <h2 class="section-title">Get In Touch</h2>
        <p class="lead">Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
    </div>

    <div class="row">
        <!-- Contact Form -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-envelope me-2"></i>Send us a Message
                    </h4>
                </div>

                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">
                                        <i class="fas fa-user me-1"></i>Your Name
                                    </label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($_POST['name'] ?? getUserName()); ?>" required>
                                    <div class="invalid-feedback">Please enter your name.</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>Email Address
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">
                                <i class="fas fa-tag me-1"></i>Subject
                            </label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="">Choose a subject...</option>
                                <option value="General Inquiry" <?php echo ($_POST['subject'] ?? '') === 'General Inquiry' ? 'selected' : ''; ?>>General Inquiry</option>
                                <option value="Order Support" <?php echo ($_POST['subject'] ?? '') === 'Order Support' ? 'selected' : ''; ?>>Order Support</option>
                                <option value="Product Question" <?php echo ($_POST['subject'] ?? '') === 'Product Question' ? 'selected' : ''; ?>>Product Question</option>
                                <option value="Technical Issue" <?php echo ($_POST['subject'] ?? '') === 'Technical Issue' ? 'selected' : ''; ?>>Technical Issue</option>
                                <option value="Billing Question" <?php echo ($_POST['subject'] ?? '') === 'Billing Question' ? 'selected' : ''; ?>>Billing Question</option>
                                <option value="Seller Application" <?php echo ($_POST['subject'] ?? '') === 'Seller Application' ? 'selected' : ''; ?>>Seller Application</option>
                                <option value="Partnership" <?php echo ($_POST['subject'] ?? '') === 'Partnership' ? 'selected' : ''; ?>>Partnership</option>
                                <option value="Feedback" <?php echo ($_POST['subject'] ?? '') === 'Feedback' ? 'selected' : ''; ?>>Feedback</option>
                                <option value="Other" <?php echo ($_POST['subject'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                            <div class="invalid-feedback">Please select a subject.</div>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">
                                <i class="fas fa-comment me-1"></i>Message
                            </label>
                            <textarea class="form-control" id="message" name="message" rows="6" 
                                      placeholder="Please describe your inquiry in detail..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            <div class="invalid-feedback">Please enter your message.</div>
                            <div class="form-text">Minimum 10 characters required.</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="col-lg-4">
            <!-- Contact Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Contact Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="contact-item mb-3">
                        <div class="d-flex align-items-center">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt text-primary"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-1">Address</h6>
                                <p class="text-muted mb-0">
                                    123 Business Street<br>
                                    Commerce City, CC 12345<br>
                                    United States
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="contact-item mb-3">
                        <div class="d-flex align-items-center">
                            <div class="contact-icon">
                                <i class="fas fa-phone text-success"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-1">Phone</h6>
                                <p class="text-muted mb-0">
                                    <a href="tel:+1234567890" class="text-decoration-none">+1 (234) 567-890</a><br>
                                    <small>Mon-Fri 9AM-6PM EST</small>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="contact-item mb-3">
                        <div class="d-flex align-items-center">
                            <div class="contact-icon">
                                <i class="fas fa-envelope text-info"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-1">Email</h6>
                                <p class="text-muted mb-0">
                                    <a href="mailto:support@ecommerce.com" class="text-decoration-none">support@ecommerce.com</a><br>
                                    <small>We respond within 24 hours</small>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="d-flex align-items-center">
                            <div class="contact-icon">
                                <i class="fas fa-comments text-warning"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-1">Live Chat</h6>
                                <p class="text-muted mb-0">
                                    <button class="btn btn-sm btn-outline-primary">Start Chat</button><br>
                                    <small>Available 24/7</small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-question-circle me-2"></i>Quick Help
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="/ecommerce/pages/faq.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-chevron-right me-2 text-muted"></i>
                            Frequently Asked Questions
                        </a>
                        <a href="/ecommerce/pages/shipping.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-chevron-right me-2 text-muted"></i>
                            Shipping Information
                        </a>
                        <a href="/ecommerce/pages/returns.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-chevron-right me-2 text-muted"></i>
                            Returns & Refunds
                        </a>
                        <a href="/ecommerce/pages/privacy.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-chevron-right me-2 text-muted"></i>
                            Privacy Policy
                        </a>
                        <a href="/ecommerce/pages/terms.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-chevron-right me-2 text-muted"></i>
                            Terms of Service
                        </a>
                    </div>
                </div>
            </div>

            <!-- Business Hours -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>Business Hours
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-sm">
                        <div class="col-6">
                            <strong>Monday - Friday:</strong>
                        </div>
                        <div class="col-6">
                            9:00 AM - 6:00 PM
                        </div>
                    </div>
                    <div class="row text-sm">
                        <div class="col-6">
                            <strong>Saturday:</strong>
                        </div>
                        <div class="col-6">
                            10:00 AM - 4:00 PM
                        </div>
                    </div>
                    <div class="row text-sm">
                        <div class="col-6">
                            <strong>Sunday:</strong>
                        </div>
                        <div class="col-6">
                            Closed
                        </div>
                    </div>
                    <hr>
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        All times are in Eastern Standard Time (EST)
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-map me-2"></i>Find Us
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="map-container" style="height: 300px; background: #f8f9fa; position: relative;">
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-center">
                                <i class="fas fa-map-marked-alt fa-3x text-muted mb-3"></i>
                                <h5>Interactive Map</h5>
                                <p class="text-muted">
                                    Integrate with Google Maps or other mapping services<br>
                                    to show your business location.
                                </p>
                                <button class="btn btn-primary">
                                    <i class="fas fa-directions me-2"></i>Get Directions
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.contact-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 50%;
}

.contact-item {
    padding: 1rem 0;
    border-bottom: 1px solid #f1f1f1;
}

.contact-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.map-container {
    border-radius: 0 0 0.375rem 0.375rem;
}
</style>

<script>
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

// Message length validation
document.getElementById('message').addEventListener('input', function() {
    const message = this.value;
    const minLength = 10;

    if (message.length < minLength && message.length > 0) {
        this.setCustomValidity(`Message must be at least ${minLength} characters long.`);
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>