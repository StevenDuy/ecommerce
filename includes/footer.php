    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light mt-5 py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <h5><i class="fas fa-store"></i> ECommerce</h5>
                    <p>Your trusted online marketplace for quality products at great prices.</p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>

                <div class="col-md-2">
                    <h6>Company</h6>
                    <ul class="list-unstyled">
                        <li><a href="/ecommerce/pages/about.php" class="text-light text-decoration-none">About Us</a></li>
                        <li><a href="/ecommerce/pages/careers.php" class="text-light text-decoration-none">Careers</a></li>
                        <li><a href="/ecommerce/pages/blog.php" class="text-light text-decoration-none">Blog</a></li>
                        <li><a href="/ecommerce/pages/support.php" class="text-light text-decoration-none">Support</a></li>
                    </ul>
                </div>

                <div class="col-md-2">
                    <h6>Customer Service</h6>
                    <ul class="list-unstyled">
                        <li><a href="/ecommerce/pages/contact.php" class="text-light text-decoration-none">Contact Us</a></li>
                        <li><a href="/ecommerce/pages/faq.php" class="text-light text-decoration-none">FAQ</a></li>
                        <li><a href="/ecommerce/pages/privacy.php" class="text-light text-decoration-none">Privacy Policy</a></li>
                        <li><a href="/ecommerce/pages/terms.php" class="text-light text-decoration-none">Terms of Service</a></li>
                    </ul>
                </div>

                <div class="col-md-2">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="/ecommerce/user/products.php" class="text-light text-decoration-none">All Products</a></li>
                        <li><a href="/ecommerce/user/products.php?featured=1" class="text-light text-decoration-none">Featured</a></li>
                        <li><a href="/ecommerce/user/products.php?new=1" class="text-light text-decoration-none">New Arrivals</a></li>
                        <li><a href="/ecommerce/user/products.php?sale=1" class="text-light text-decoration-none">Best Sellers</a></li>
                    </ul>
                </div>

                <div class="col-md-3">
                    <h6>Newsletter</h6>
                    <p>Subscribe to get updates on new products and offers.</p>
                    <form class="d-flex">
                        <input type="email" class="form-control me-2" placeholder="Your email">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>

            <hr class="my-4">

            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2024 ECommerce. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-end">
                    <img src="/ecommerce/assets/images/payment-methods.png" alt="Payment Methods" class="img-fluid" style="max-height: 30px;">
                </div>
            </div>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <!-- Custom JS -->
    <script src="/ecommerce/assets/js/main.js"></script>

    <script>
        // Update cart count on page load
        $(document).ready(function() {
            updateCartCount();
            
            // Initialize dropdowns - chỉ thực hiện nếu Bootstrap đã được khởi tạo
            if (typeof bootstrap !== 'undefined') {
                const dropdownElements = document.querySelectorAll('.dropdown-toggle[data-bs-toggle="dropdown"]');
                dropdownElements.forEach(function(element) {
                    // Kiểm tra xem dropdown đã được khởi tạo chưa
                    if (!element._dropdown) {
                        const dropdown = new bootstrap.Dropdown(element);
                        element._dropdown = dropdown;
                        
                        // Update aria-expanded when dropdown is shown/hidden
                        element.addEventListener('shown.bs.dropdown', function () {
                            element.setAttribute('aria-expanded', 'true');
                        });
                        
                        element.addEventListener('hidden.bs.dropdown', function () {
                            element.setAttribute('aria-expanded', 'false');
                        });
                    }
                });
            }
        });

        function updateCartCount() {
            <?php if (isLoggedIn() && getUserRole() === 'user'): ?>
            $.get('/ecommerce/ajax/get_cart_count.php', function(data) {
                $('#cart-count').text(data.count);
            });
            <?php endif; ?>
        }

        // Show notifications
        <?php if (isset($_SESSION['notification'])): ?>
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "5000"
        };

        toastr.<?php echo $_SESSION['notification']['type']; ?>("<?php echo $_SESSION['notification']['message']; ?>");
        <?php unset($_SESSION['notification']); ?>
        <?php endif; ?>
    </script>
</body>
</html>