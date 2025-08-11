<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

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
        showNotification('success', 'Thank you for your message! We will get back to you soon.');
        $_POST = [];
    } else {
        showNotification('error', implode(' ', $errors));
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <!-- Hero Section -->
    <div class="hero-section text-center mb-5">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Contact Us</h1>
            <p class="lead">Get in touch with our team - we're here to help!</p>
        </div>
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
                                    <label for="name" class="form-label">Your Name</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter your name.</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter a valid email.</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="">Choose a subject...</option>
                                <option value="General Inquiry">General Inquiry</option>
                                <option value="Order Support">Order Support</option>
                                <option value="Product Question">Product Question</option>
                                <option value="Technical Issue">Technical Issue</option>
                                <option value="Other">Other</option>
                            </select>
                            <div class="invalid-feedback">Please select a subject.</div>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="6" 
                                      placeholder="Your message..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            <div class="invalid-feedback">Please enter your message.</div>
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
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="contact-item mb-4">
                        <h6><i class="fas fa-map-marker-alt text-primary me-2"></i>Address</h6>
                        <p class="text-muted">123 Business Street<br>Commerce City, CC 12345<br>United States</p>
                    </div>

                    <div class="contact-item mb-4">
                        <h6><i class="fas fa-phone text-success me-2"></i>Phone</h6>
                        <p class="text-muted">+1 (234) 567-890<br><small>Mon-Fri 9AM-6PM EST</small></p>
                    </div>

                    <div class="contact-item mb-4">
                        <h6><i class="fas fa-envelope text-info me-2"></i>Email</h6>
                        <p class="text-muted">support@ecommerce.com<br><small>We respond within 24 hours</small></p>
                    </div>

                    <div class="contact-item">
                        <h6><i class="fas fa-clock text-warning me-2"></i>Business Hours</h6>
                        <p class="text-muted">Monday - Friday: 9AM - 6PM<br>Saturday: 10AM - 4PM<br>Sunday: Closed</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>