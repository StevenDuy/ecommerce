<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

$pageTitle = 'Terms of Service - ECommerce';
require_once '../includes/header.php';
?>

<div class="container">
    <!-- Hero Section -->
    <div class="hero-section text-center mb-5">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Terms of Service</h1>
            <p class="lead">Terms and conditions for using our platform</p>
            <small class="text-muted">Last updated: <?php echo date('F d, Y'); ?></small>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body">
                    <section class="mb-5">
                        <h2>1. Acceptance of Terms</h2>
                        <p>By accessing and using the ECommerce platform, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>
                    </section>

                    <section class="mb-5">
                        <h2>2. Use License</h2>
                        <p>Permission is granted to temporarily download one copy of the materials on ECommerce's website for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:</p>
                        <ul>
                            <li>Modify or copy the materials</li>
                            <li>Use the materials for any commercial purpose or for any public display</li>
                            <li>Attempt to reverse engineer any software contained on the website</li>
                            <li>Remove any copyright or other proprietary notations from the materials</li>
                        </ul>
                    </section>

                    <section class="mb-5">
                        <h2>3. User Accounts</h2>
                        <h4>Account Creation</h4>
                        <p>To use certain features of our platform, you must create an account. You agree to:</p>
                        <ul>
                            <li>Provide accurate and complete information</li>
                            <li>Keep your account information up to date</li>
                            <li>Maintain the security of your password</li>
                            <li>Accept responsibility for all activities under your account</li>
                        </ul>

                        <h4>Account Termination</h4>
                        <p>We reserve the right to terminate accounts that violate these terms or engage in fraudulent activities.</p>
                    </section>

                    <section class="mb-5">
                        <h2>4. Buying and Selling</h2>
                        <h4>For Buyers</h4>
                        <ul>
                            <li>You agree to pay for all items you purchase</li>
                            <li>Prices are subject to change without notice</li>
                            <li>We reserve the right to refuse service to anyone</li>
                            <li>Returns are subject to our return policy</li>
                        </ul>

                        <h4>For Sellers</h4>
                        <ul>
                            <li>You must provide accurate product descriptions</li>
                            <li>You are responsible for fulfilling orders promptly</li>
                            <li>You must comply with all applicable laws</li>
                            <li>You agree to our commission structure</li>
                        </ul>
                    </section>

                    <section class="mb-5">
                        <h2>5. Prohibited Uses</h2>
                        <p>You may not use our platform for:</p>
                        <ul>
                            <li>Illegal activities or to violate any laws</li>
                            <li>Selling prohibited or restricted items</li>
                            <li>Harassment or harm to other users</li>
                            <li>Spamming or unsolicited communications</li>
                            <li>Intellectual property infringement</li>
                            <li>Fraud or deceptive practices</li>
                            <li>Interfering with platform operations</li>
                        </ul>
                    </section>

                    <section class="mb-5">
                        <h2>6. Payment Terms</h2>
                        <h4>Payment Processing</h4>
                        <p>All payments are processed securely through our payment partners. We currently accept:</p>
                        <ul>
                            <li>Cash on Delivery (COD)</li>
                            <li>Other payment methods (coming soon)</li>
                        </ul>

                        <h4>Fees and Commissions</h4>
                        <p>Sellers agree to pay applicable fees and commissions as outlined in our fee schedule.</p>
                    </section>

                    <section class="mb-5">
                        <h2>7. Shipping and Delivery</h2>
                        <ul>
                            <li>Sellers are responsible for shipping products to buyers</li>
                            <li>Delivery times are estimates and not guaranteed</li>
                            <li>Risk of loss transfers to buyer upon delivery</li>
                            <li>We are not responsible for shipping delays or damages</li>
                        </ul>
                    </section>

                    <section class="mb-5">
                        <h2>8. Returns and Refunds</h2>
                        <ul>
                            <li>Return policies are set by individual sellers</li>
                            <li>Buyers should review return policies before purchasing</li>
                            <li>We may facilitate dispute resolution between buyers and sellers</li>
                            <li>Refunds are processed according to seller policies</li>
                        </ul>
                    </section>

                    <section class="mb-5">
                        <h2>9. Intellectual Property</h2>
                        <p>The platform and its original content, features, and functionality are owned by ECommerce and are protected by international copyright, trademark, patent, trade secret, and other intellectual property laws.</p>
                    </section>

                    <section class="mb-5">
                        <h2>10. Privacy Policy</h2>
                        <p>Your privacy is important to us. Please review our Privacy Policy, which also governs your use of our services, to understand our practices.</p>
                    </section>

                    <section class="mb-5">
                        <h2>11. Disclaimers</h2>
                        <p>The information on this website is provided on an "as is" basis. To the fullest extent permitted by law, this Company:</p>
                        <ul>
                            <li>Excludes all representations and warranties relating to this website and its contents</li>
                            <li>Does not guarantee the accuracy or completeness of information</li>
                            <li>Is not responsible for third-party content or services</li>
                            <li>Does not warrant that the website will be continuously available</li>
                        </ul>
                    </section>

                    <section class="mb-5">
                        <h2>12. Limitation of Liability</h2>
                        <p>In no event shall ECommerce or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use our platform, even if we have been notified orally or in writing of the possibility of such damage.</p>
                    </section>

                    <section class="mb-5">
                        <h2>13. Indemnification</h2>
                        <p>You agree to indemnify and hold harmless ECommerce and its affiliates from any claims, damages, or expenses arising from your use of the platform or violation of these terms.</p>
                    </section>

                    <section class="mb-5">
                        <h2>14. Governing Law</h2>
                        <p>These terms and conditions are governed by and construed in accordance with the laws of the jurisdiction in which ECommerce operates, and you irrevocably submit to the exclusive jurisdiction of the courts in that location.</p>
                    </section>

                    <section class="mb-5">
                        <h2>15. Changes to Terms</h2>
                        <p>We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting. Your continued use of the platform after any changes constitutes acceptance of the new terms.</p>
                    </section>

                    <section class="mb-5">
                        <h2>16. Contact Information</h2>
                        <p>If you have any questions about these Terms of Service, please contact us:</p>
                        <ul class="list-unstyled">
                            <li><strong>Email:</strong> legal@ecommerce.com</li>
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
                        <a class="nav-link" href="#section1">Acceptance of Terms</a>
                        <a class="nav-link" href="#section2">Use License</a>
                        <a class="nav-link" href="#section3">User Accounts</a>
                        <a class="nav-link" href="#section4">Buying and Selling</a>
                        <a class="nav-link" href="#section5">Prohibited Uses</a>
                        <a class="nav-link" href="#section6">Payment Terms</a>
                        <a class="nav-link" href="#section7">Shipping & Delivery</a>
                        <a class="nav-link" href="#section8">Returns & Refunds</a>
                        <a class="nav-link" href="#section9">Intellectual Property</a>
                        <a class="nav-link" href="#section10">Privacy Policy</a>
                        <a class="nav-link" href="#section11">Disclaimers</a>
                        <a class="nav-link" href="#section12">Limitation of Liability</a>
                        <a class="nav-link" href="#section13">Indemnification</a>
                        <a class="nav-link" href="#section14">Governing Law</a>
                        <a class="nav-link" href="#section15">Changes to Terms</a>
                        <a class="nav-link" href="#section16">Contact Information</a>
                    </nav>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Legal Documents</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="/ecommerce/pages/privacy.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-shield-alt me-2"></i>Privacy Policy
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