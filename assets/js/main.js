// Main JavaScript file for ECommerce application

$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(event) {
        var target = $(this.getAttribute('href'));
        if( target.length ) {
            event.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 1000);
        }
    });

    // Add to cart functionality
    $('.add-to-cart').on('click', function(e) {
        e.preventDefault();

        const productId = $(this).data('product-id');
        const quantity = $(this).closest('.product-card').find('.quantity-input').val() || 1;
        const button = $(this);

        // Show loading
        const originalText = button.html();
        button.html('<span class="loading"></span> Adding...');
        button.prop('disabled', true);

        $.ajax({
            url: '/ecommerce/ajax/add_to_cart.php',
            method: 'POST',
            data: {
                product_id: productId,
                quantity: quantity
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    updateCartCount();

                    // Animation effect
                    button.html('<i class="fas fa-check"></i> Added!');
                    setTimeout(() => {
                        button.html(originalText);
                        button.prop('disabled', false);
                    }, 2000);
                } else {
                    toastr.error(response.message);
                    button.html(originalText);
                    button.prop('disabled', false);
                }
            },
            error: function() {
                toastr.error('An error occurred. Please try again.');
                button.html(originalText);
                button.prop('disabled', false);
            }
        });
    });

    // Update cart quantity
    $('.update-cart-quantity').on('change', function() {
        const cartItemId = $(this).data('cart-item-id');
        const quantity = $(this).val();
        const row = $(this).closest('tr');

        $.ajax({
            url: '/ecommerce/ajax/update_cart.php',
            method: 'POST',
            data: {
                cart_item_id: cartItemId,
                quantity: quantity
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update subtotal
                    row.find('.subtotal').text('$' + response.subtotal.toFixed(2));

                    // Update cart totals
                    $('.cart-total').text('$' + response.cart_total.toFixed(2));
                    updateCartCount();

                    toastr.success('Cart updated successfully');
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('An error occurred. Please try again.');
            }
        });
    });

    // Remove from cart
    $('.remove-from-cart').on('click', function(e) {
        e.preventDefault();

        const cartItemId = $(this).data('cart-item-id');
        const row = $(this).closest('tr');

        if (confirm('Are you sure you want to remove this item from cart?')) {
            $.ajax({
                url: '/ecommerce/ajax/remove_from_cart.php',
                method: 'POST',
                data: {
                    cart_item_id: cartItemId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        row.fadeOut(300, function() {
                            $(this).remove();
                            updateCartCount();

                            // Update cart total
                            $('.cart-total').text('$' + response.cart_total.toFixed(2));

                            if (response.cart_total === 0) {
                                location.reload();
                            }
                        });
                        toastr.success('Item removed from cart');
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('An error occurred. Please try again.');
                }
            });
        }
    });

    // Product search
    $('#product-search').on('input', function() {
        const searchTerm = $(this).val();

        if (searchTerm.length >= 2) {
            $.ajax({
                url: '/ecommerce/ajax/search_products.php',
                method: 'GET',
                data: {
                    q: searchTerm
                },
                dataType: 'json',
                success: function(response) {
                    displaySearchResults(response.products);
                }
            });
        } else {
            $('#search-results').hide();
        }
    });

    // Product filtering
    $('.filter-checkbox, .filter-radio').on('change', function() {
        applyFilters();
    });

    // Price range filter
    $('#price-min, #price-max').on('change', function() {
        applyFilters();
    });

    // Product sorting
    $('#sort-select').on('change', function() {
        const sortBy = $(this).val();
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('sort', sortBy);
        window.location = currentUrl;
    });

    // Image upload preview
    $('.image-upload-input').on('change', function() {
        const files = this.files;
        const previewContainer = $(this).siblings('.image-preview');

        previewContainer.empty();

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const reader = new FileReader();

            reader.onload = function(e) {
                const imageHtml = `
                    <div class="image-preview-item">
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="remove-image" data-index="${i}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                previewContainer.append(imageHtml);
            };

            reader.readAsDataURL(file);
        }
    });

    // Remove image preview
    $(document).on('click', '.remove-image', function() {
        $(this).closest('.image-preview-item').remove();
    });

    // Form validation
    $('.needs-validation').on('submit', function(e) {
        const form = this;

        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }

        $(form).addClass('was-validated');
    });

    // Auto-hide alerts
    $('.alert:not(.alert-permanent)').delay(5000).fadeOut(300);

    // Quantity controls
    $('.quantity-minus').on('click', function() {
        const input = $(this).siblings('.quantity-input');
        const currentValue = parseInt(input.val());

        if (currentValue > 1) {
            input.val(currentValue - 1);
            input.trigger('change');
        }
    });

    $('.quantity-plus').on('click', function() {
        const input = $(this).siblings('.quantity-input');
        const currentValue = parseInt(input.val());
        const maxValue = parseInt(input.attr('max')) || 999;

        if (currentValue < maxValue) {
            input.val(currentValue + 1);
            input.trigger('change');
        }
    });

    // Lazy loading for images
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
});

// Helper functions
function updateCartCount() {
    $.get('/ecommerce/ajax/get_cart_count.php', function(data) {
        $('#cart-count').text(data.count);
    });
}

function displaySearchResults(products) {
    const resultsContainer = $('#search-results');

    if (products.length === 0) {
        resultsContainer.html('<div class="p-3 text-muted">No products found</div>');
    } else {
        let html = '';
        products.forEach(product => {
            html += `
                <div class="search-result-item p-2 border-bottom">
                    <a href="/ecommerce/user/product_details.php?id=${product.id}" class="text-decoration-none">
                        <div class="d-flex align-items-center">
                            <img src="/ecommerce/assets/images/products/${product.main_image_url}" 
                                 alt="${product.name}" class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                            <div>
                                <div class="fw-bold">${product.name}</div>
                                <div class="text-primary">$${parseFloat(product.price).toFixed(2)}</div>
                            </div>
                        </div>
                    </a>
                </div>
            `;
        });
        resultsContainer.html(html);
    }

    resultsContainer.show();
}

function applyFilters() {
    const filters = {
        categories: [],
        price_min: $('#price-min').val(),
        price_max: $('#price-max').val(),
        featured: $('#featured-filter').is(':checked'),
        in_stock: $('#stock-filter').is(':checked')
    };

    $('.category-filter:checked').each(function() {
        filters.categories.push($(this).val());
    });

    // Build URL with filters
    const url = new URL(window.location);
    Object.keys(filters).forEach(key => {
        if (filters[key]) {
            if (Array.isArray(filters[key])) {
                url.searchParams.set(key, filters[key].join(','));
            } else {
                url.searchParams.set(key, filters[key]);
            }
        } else {
            url.searchParams.delete(key);
        }
    });

    window.location = url;
}

function formatPrice(price) {
    return '$' + parseFloat(price).toFixed(2);
}

function showLoading(element) {
    const originalHtml = element.html();
    element.data('original-html', originalHtml);
    element.html('<span class="loading"></span>');
    element.prop('disabled', true);
}

function hideLoading(element) {
    const originalHtml = element.data('original-html');
    element.html(originalHtml);
    element.prop('disabled', false);
}

// Export functions for use in other scripts
window.ecommerce = {
    updateCartCount,
    displaySearchResults,
    applyFilters,
    formatPrice,
    showLoading,
    hideLoading
};