<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/utils.php';

$pageTitle = 'About Us - ECommerce';
require_once '../includes/header.php';
?>

<div class="container">
    <!-- Hero Section -->
    <div class="hero-section text-center mb-5">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">About ECommerce</h1>
            <p class="lead">Your trusted online marketplace connecting buyers and sellers worldwide</p>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-lg-6">
            <h2 class="section-title text-start">Our Story</h2>
            <p class="lead">Founded with a vision to revolutionize online shopping, ECommerce has grown to become a leading marketplace that connects millions of buyers with sellers around the globe.</p>

            <p>We believe that everyone should have access to quality products at fair prices, whether you're a customer looking for the perfect item or a seller wanting to reach new markets. Our platform provides the tools, security, and support needed to make online commerce simple, safe, and successful.</p>

            <p>Since our inception, we've facilitated thousands of transactions, helped hundreds of businesses grow, and created a community built on trust, quality, and customer satisfaction.</p>
        </div>

        <div class="col-lg-6">
            <img src="/ecommerce/assets/images/about-hero.jpg" alt="About Us" class="img-fluid rounded shadow" 
                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZTllY2VmIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxOCIgZmlsbD0iIzk5OTk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkFib3V0IFVzIEltYWdlPC90ZXh0Pjwvc3ZnPg=='">
        </div>
    </div>

    <!-- Values Section -->
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="section-title">Our Values</h2>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                    <h4>Trust & Security</h4>
                    <p>We prioritize the security of every transaction and protect both buyers and sellers with advanced security measures and fraud protection.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <i class="fas fa-star fa-3x text-warning mb-3"></i>
                    <h4>Quality Excellence</h4>
                    <p>We maintain high standards for products and services, ensuring that every customer receives exactly what they expect and deserve.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <i class="fas fa-users fa-3x text-success mb-3"></i>
                    <h4>Community First</h4>
                    <p>Our community of buyers and sellers is at the heart of everything we do. We foster connections and support business growth.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Mission Section -->
    <div class="row mb-5">
        <div class="col-lg-6 order-lg-2">
            <h2 class="section-title text-start">Our Mission</h2>
            <p class="lead">To democratize commerce by providing a platform where anyone can buy and sell with confidence.</p>

            <ul class="list-unstyled">
                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Empower small businesses and entrepreneurs</li>
                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Provide customers with diverse product choices</li>
                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Ensure fair and transparent marketplace practices</li>
                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Foster innovation in e-commerce technology</li>
                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Support sustainable business practices</li>
            </ul>
        </div>

        <div class="col-lg-6 order-lg-1">
            <img src="/ecommerce/assets/images/mission.jpg" alt="Our Mission" class="img-fluid rounded shadow"
                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZTllY2VmIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxOCIgZmlsbD0iIzk5OTk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk91ciBNaXNzaW9uPC90ZXh0Pjwvc3ZnPg=='">
        </div>
    </div>

    <!-- Team Section -->
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="section-title">Meet Our Team</h2>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <img src="/ecommerce/assets/images/team-1.jpg" alt="Team Member" class="rounded-circle mb-3" 
                         style="width: 100px; height: 100px; object-fit: cover;"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PGNpcmNsZSBjeD0iNTAiIGN5PSI0MCIgcj0iMTUiIGZpbGw9IiM5OTkiLz48cGF0aCBkPSJNMjAgODBjMC0xNiAxMy0zMCAzMC0zMHMzMCAxNCAzMCAzMCIgZmlsbD0iIzk5OSIvPjwvc3ZnPg=='">
                    <h5>John Smith</h5>
                    <p class="text-muted">CEO & Founder</p>
                    <p class="small">Leading the vision and strategy to make e-commerce accessible to everyone.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <img src="/ecommerce/assets/images/team-2.jpg" alt="Team Member" class="rounded-circle mb-3" 
                         style="width: 100px; height: 100px; object-fit: cover;"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PGNpcmNsZSBjeD0iNTAiIGN5PSI0MCIgcj0iMTUiIGZpbGw9IiM5OTkiLz48cGF0aCBkPSJNMjAgODBjMC0xNiAxMy0zMCAzMC0zMHMzMCAxNCAzMCAzMCIgZmlsbD0iIzk5OSIvPjwvc3ZnPg=='">
                    <h5>Sarah Johnson</h5>
                    <p class="text-muted">CTO</p>
                    <p class="small">Building robust technology solutions to power our marketplace platform.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <img src="/ecommerce/assets/images/team-3.jpg" alt="Team Member" class="rounded-circle mb-3" 
                         style="width: 100px; height: 100px; object-fit: cover;"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PGNpcmNsZSBjeD0iNTAiIGN5PSI0MCIgcj0iMTUiIGZpbGw9IiM5OTkiLz48cGF0aCBkPSJNMjAgODBjMC0xNiAxMy0zMCAzMC0zMHMzMCAxNCAzMCAzMCIgZmlsbD0iIzk5OSIvPjwvc3ZnPg=='">
                    <h5>Mike Davis</h5>
                    <p class="text-muted">Head of Operations</p>
                    <p class="small">Ensuring smooth operations and excellent customer service experiences.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <img src="/ecommerce/assets/images/team-4.jpg" alt="Team Member" class="rounded-circle mb-3" 
                         style="width: 100px; height: 100px; object-fit: cover;"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PGNpcmNsZSBjeD0iNTAiIGN5PSI0MCIgcj0iMTUiIGZpbGw9IiM5OTkiLz48cGF0aCBkPSJNMjAgODBjMC0xNiAxMy0zMCAzMC0zMHMzMCAxNCAzMCAzMCIgZmlsbD0iIzk5OSIvPjwvc3ZnPg=='">
                    <h5>Emily Chen</h5>
                    <p class="text-muted">Marketing Director</p>
                    <p class="small">Connecting with our community and growing our marketplace ecosystem.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Section -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <h2 class="display-4">10K+</h2>
                            <p class="mb-0">Happy Customers</p>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <h2 class="display-4">500+</h2>
                            <p class="mb-0">Trusted Sellers</p>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <h2 class="display-4">50K+</h2>
                            <p class="mb-0">Products Available</p>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <h2 class="display-4">25+</h2>
                            <p class="mb-0">Countries Served</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <h3>Ready to Join Our Community?</h3>
                    <p class="lead text-muted">Whether you're looking to buy or sell, we're here to help you succeed.</p>
                    <div class="mt-4">
                        <?php if (!isLoggedIn()): ?>
                        <a href="/ecommerce/auth/register.php" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-user-plus me-2"></i>Join as Customer
                        </a>
                        <a href="/ecommerce/auth/register.php" class="btn btn-success btn-lg">
                            <i class="fas fa-store me-2"></i>Become a Seller
                        </a>
                        <?php else: ?>
                        <a href="/ecommerce/<?php echo getUserRole(); ?>/index.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-home me-2"></i>Go to Dashboard
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>