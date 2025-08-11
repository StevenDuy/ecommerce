<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

$pageTitle = 'Privacy Policy - ECommerce';
require_once '../includes/header.php';
?>

<div class="container">
    <!-- Hero Section -->
    <div class="hero-section text-center mb-5">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Privacy Policy</h1>
            <p class="lead">How we collect, use, and protect your information</p>
            <small class="text-muted">Last updated: <?php echo date('F d, Y'); ?></small>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body">
                    <section class="mb-5">
                        <h2>1. Information We Collect</h2>
                        <p>We collect information you provide directly to us, such as when you:</p>
                        <ul>
                            <li>Create an account</li>
                            <li>Make a purchase</li>
                            <li>Contact customer support</li>
                            <li>Subscribe to our newsletter</li>
                            <li>Participate in surveys or promotions</li>
                        </ul>

                        <h4>Personal Information</h4>
                        <p>This may include:</p>
                        <ul>
                            <li>Name and contact information</li>
                            <li>Email address and phone number</li>
                            <li>Shipping and billing addresses</li>
                            <li>Payment information</li>
                            <li>Account credentials</li>
                        </ul>
                    </section>

                    <section class="mb-5">
                        <h2>2. How We Use Your Information</h2>
                        <p>We use the information we collect to:</p>
                        <ul>
                            <li>Process and fulfill your orders</li>
                            <li>Communicate with you about your account or transactions</li>
                            <li>Provide customer support</li>
                            <li>Improve our services and user experience</li>
                            <li>Send marketing communications (with your consent)</li>
                            <li>Prevent fraud and enhance security</li>
                            <li>Comply with legal obligations</li>
                        </ul>
                    </section>

                    <section class="mb-5">
                        <h2>3. Information Sharing</h2>
                        <p>We do not sell, trade, or rent your personal information to third parties. We may share your information in the following circumstances:</p>

                        <h4>With Sellers</h4>
                        <p>When you make a purchase, we share necessary information with the seller to fulfill your order, including your name and shipping address.</p>

                        <h4>Service Providers</h4>
                        <p>We may share information with trusted service providers who assist us in operating our platform, such as payment processors and shipping companies.</p>

                        <h4>Legal Requirements</h4>
                        <p>We may disclose information when required by law or to protect our rights, property, or safety.</p>
                    </section>

                    <section class="mb-5">
                        <h2>4. Data Security</h2>
                        <p>We implement appropriate technical and organizational measures to protect your personal information, including:</p>
                        <ul>
                            <li>SSL encryption for data transmission</li>
                            <li>Secure server infrastructure</li>
                            <li>Regular security audits</li>
                            <li>Access controls and authentication</li>
                            <li>Employee training on data protection</li>
                        </ul>
                        <p>However, no method of transmission over the internet is 100% secure, and we cannot guarantee absolute security.</p>
                    </section>

                    <section class="mb-5">
                        <h2>5. Cookies and Tracking</h2>
                        <p>We use cookies and similar technologies to:</p>
                        <ul>
                            <li>Remember your preferences</li>
                            <li>Keep you logged in</li>
                            <li>Analyze site usage</li>
                            <li>Provide personalized content</li>
                        </ul>
                        <p>You can control cookie settings through your browser, but disabling cookies may affect site functionality.</p>
                    </section>

                    <section class="mb-5">
                        <h2>6. Your Rights</h2>
                        <p>You have the right to:</p>
                        <ul>
                            <li>Access your personal information</li>
                            <li>Update or correct your information</li>
                            <li>Delete your account and personal data</li>
                            <li>Opt-out of marketing communications</li>
                            <li>Request data portability</li>
                            <li>Lodge a complaint with supervisory authorities</li>
                        </ul>
                        <p>To exercise these rights, please contact us using the information provided below.</p>
                    </section>

                    <section class="mb-5">
                        <h2>7. Data Retention</h2>
                        <p>We retain your personal information for as long as necessary to:</p>
                        <ul>
                            <li>Provide our services</li>
                            <li>Comply with legal obligations</li>
                            <li>Resolve disputes</li>
                            <li>Enforce our agreements</li>
                        </ul>
                        <p>When information is no longer needed, we securely delete or anonymize it.</p>
                    </section>

                    <section class="mb-5">
                        <h2>8. Children's Privacy</h2>
                        <p>Our services are not intended for children under 13. We do not knowingly collect personal information from children under 13. If we become aware that we have collected such information, we will delete it promptly.</p>
                    </section>

                    <section class="mb-5">
                        <h2>9. International Transfers</h2>
                        <p>Your information may be transferred to and processed in countries other than your own. We ensure appropriate safeguards are in place to protect your information during such transfers.</p>
                    </section>

                    <section class="mb-5">
                        <h2>10. Changes to This Policy</h2>
                        <p>We may update this privacy policy from time to time. We will notify you of any material changes by posting the new policy on this page and updating the "Last updated" date.</p>
                    </section>

                    <section class="mb-5">
                        <h2>11. Contact Us</h2>
                        <p>If you have any questions about this privacy policy or our data practices, please contact us:</p>
                        <ul class="list-unstyled">
                            <li><strong>Email:</strong> privacy@ecommerce.com</li>
                            <li><strong>Phone:</strong> +1 (234) 567-890</li>
                            <li><strong>Address:</strong> 123 Business Street, Commerce City, CC 12345</li>
                        </ul>
                    </section>
                </div>
            </div>
        </div>

        <!-- Quick Navigation -->
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Navigation</h5>
                </div>
                <div class="card-body">
                    <nav class="nav flex-column">
                        <a class="nav-link" href="#section1">Information We Collect</a>
                        <a class="nav-link" href="#section2">How We Use Information</a>
                        <a class="nav-link" href="#section3">Information Sharing</a>
                        <a class="nav-link" href="#section4">Data Security</a>
                        <a class="nav-link" href="#section5">Cookies & Tracking</a>
                        <a class="nav-link" href="#section6">Your Rights</a>
                        <a class="nav-link" href="#section7">Data Retention</a>
                        <a class="nav-link" href="#section8">Children's Privacy</a>
                        <a class="nav-link" href="#section9">International Transfers</a>
                        <a class="nav-link" href="#section10">Policy Changes</a>
                        <a class="nav-link" href="#section11">Contact Us</a>
                    </nav>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Related Pages</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="/ecommerce/pages/terms.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-file-contract me-2"></i>Terms of Service
                        </a>
                        <a href="/ecommerce/pages/contact.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-envelope me-2"></i>Contact Us
                        </a>
                        <a href="/ecommerce/pages/faq.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-question-circle me-2"></i>FAQ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>