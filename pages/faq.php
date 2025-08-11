<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

$pageTitle = 'FAQ - ECommerce';
require_once '../includes/header.php';
?>

<div class="container">
    <!-- Hero Section -->
    <div class="hero-section text-center mb-5">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Frequently Asked Questions</h1>
            <p class="lead">Find answers to common questions about our platform</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="accordion" id="faqAccordion">

                <!-- General Questions -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">General Questions</h5>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq1">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                            What is ECommerce platform?
                        </button>
                    </h2>
                    <div id="collapse1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            ECommerce is an online marketplace that connects buyers and sellers worldwide. We provide a secure platform where individuals and businesses can buy and sell products safely and efficiently.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq2">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                            How do I create an account?
                        </button>
                    </h2>
                    <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Click on "Register" in the top navigation menu, choose whether you want to be a customer or seller, fill in your details, and verify your email address. It's that simple!
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq3">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                            Is my personal information secure?
                        </button>
                    </h2>
                    <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes, we use industry-standard security measures including SSL encryption, secure payment processing, and regular security audits to protect your personal and financial information.
                        </div>
                    </div>
                </div>

                <!-- Shopping Questions -->
                <div class="card mb-3 mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Shopping Questions</h5>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq4">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4">
                            How do I place an order?
                        </button>
                    </h2>
                    <div id="collapse4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Browse products, add items to your cart, go to checkout, select your shipping address, choose payment method, and confirm your order. You'll receive an order confirmation email.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq5">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse5">
                            What payment methods do you accept?
                        </button>
                    </h2>
                    <div id="collapse5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Currently, we accept Cash on Delivery (COD). We're working on adding more payment options including credit cards, PayPal, and digital wallets.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq6">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse6">
                            Can I cancel my order?
                        </button>
                    </h2>
                    <div id="collapse6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes, you can cancel your order if it's still in "Pending" status. Once the order is confirmed or shipped, cancellation may not be possible. Check your order status in your account.
                        </div>
                    </div>
                </div>

                <!-- Selling Questions -->
                <div class="card mb-3 mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Selling Questions</h5>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq7">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse7">
                            How do I become a seller?
                        </button>
                    </h2>
                    <div id="collapse7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Register for a seller account, verify your email, complete your profile, and start adding products. There are no upfront fees to become a seller on our platform.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq8">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse8">
                            What are the seller fees?
                        </button>
                    </h2>
                    <div id="collapse8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            We charge a small commission on each successful sale. The exact percentage depends on the product category. There are no listing fees or monthly charges.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq9">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse9">
                            How do I add products?
                        </button>
                    </h2>
                    <div id="collapse9" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            In your seller dashboard, go to "Products", click "Add Product", fill in the product details, upload images, set pricing and inventory, then publish your listing.
                        </div>
                    </div>
                </div>

                <!-- Technical Questions -->
                <div class="card mb-3 mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Technical Support</h5>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq10">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse10">
                            I forgot my password. What should I do?
                        </button>
                    </h2>
                    <div id="collapse10" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Click on "Forgot Password" on the login page, enter your email address, and follow the instructions in the password reset email we'll send you.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq11">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse11">
                            The website is not working properly. What should I do?
                        </button>
                    </h2>
                    <div id="collapse11" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Try refreshing the page, clearing your browser cache, or using a different browser. If the problem persists, contact our technical support team with details about the issue.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq12">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse12">
                            How can I contact customer support?
                        </button>
                    </h2>
                    <div id="collapse12" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            You can reach us through our contact page, email us at support@ecommerce.com, or call us at +1 (234) 567-890 during business hours.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Help Sidebar -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Help</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="/ecommerce/pages/contact.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-envelope me-2 text-primary"></i>Contact Support
                        </a>
                        <a href="/ecommerce/auth/register.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-user-plus me-2 text-success"></i>Create Account
                        </a>
                        <a href="/ecommerce/user/products.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-shopping-bag me-2 text-info"></i>Browse Products
                        </a>
                        <a href="/ecommerce/pages/about.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-info-circle me-2 text-warning"></i>About Us
                        </a>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Still Need Help?</h5>
                </div>
                <div class="card-body text-center">
                    <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                    <h6>24/7 Customer Support</h6>
                    <p class="text-muted small">Our support team is here to help you with any questions or issues.</p>
                    <a href="/ecommerce/pages/contact.php" class="btn btn-primary">
                        <i class="fas fa-comment me-2"></i>Contact Us
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>