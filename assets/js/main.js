/**
 * Lisingo C2C E-Commerce Platform - Main JavaScript
 */

$(document).ready(function() {

    // === Add to Cart (AJAX) ===
    $(document).on('click', '.btn-add-cart', function(e) {
        e.preventDefault();
        var productId = $(this).data('product-id');
        var qty = $(this).data('qty') || 1;
        var btn = $(this);

        btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Adding...');

        $.ajax({
            url: siteUrl + '/api/cart.php',
            method: 'POST',
            data: { action: 'add', product_id: productId, quantity: qty },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    btn.html('<i class="bi bi-check-lg"></i> Added!').addClass('btn-success').removeClass('btn-primary');
                    // Update cart badge
                    updateCartBadge(response.cart_count);
                    setTimeout(function() {
                        btn.html('<i class="bi bi-cart-plus"></i> Add to Cart').removeClass('btn-success').addClass('btn-primary').prop('disabled', false);
                    }, 2000);
                } else {
                    alert(response.message || 'Failed to add to cart');
                    btn.html('<i class="bi bi-cart-plus"></i> Add to Cart').prop('disabled', false);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                btn.html('<i class="bi bi-cart-plus"></i> Add to Cart').prop('disabled', false);
            }
        });
    });

    // === Update Cart Quantity ===
    $(document).on('change', '.cart-qty', function() {
        var productId = $(this).data('product-id');
        var qty = $(this).val();

        $.ajax({
            url: siteUrl + '/api/cart.php',
            method: 'POST',
            data: { action: 'update', product_id: productId, quantity: qty },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });

    // === Remove from Cart ===
    $(document).on('click', '.btn-remove-cart', function(e) {
        e.preventDefault();
        if (!confirm('Remove this item from your cart?')) return;

        var productId = $(this).data('product-id');

        $.ajax({
            url: siteUrl + '/api/cart.php',
            method: 'POST',
            data: { action: 'remove', product_id: productId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });

    // === Update Cart Badge ===
    function updateCartBadge(count) {
        var badge = $('.bi-cart3').parent().find('.badge');
        if (count > 0) {
            if (badge.length) {
                badge.text(count);
            } else {
                $('.bi-cart3').parent().append('<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">' + count + '</span>');
            }
        } else {
            badge.remove();
        }
    }

    // === Product Image Gallery ===
    $(document).on('click', '.product-thumbnails img', function() {
        var src = $(this).attr('src');
        $('.product-main-image').attr('src', src);
        $('.product-thumbnails img').removeClass('active');
        $(this).addClass('active');
    });

    // === Image Preview for Upload ===
    $(document).on('change', '.image-upload', function() {
        var input = this;
        var previewId = $(this).data('preview');

        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#' + previewId).html('<img src="' + e.target.result + '" alt="Preview">');
            };
            reader.readAsDataURL(input.files[0]);
        }
    });

    // === Search Autocomplete Delay ===
    var searchTimer;
    $('input[name="q"]').on('input', function() {
        clearTimeout(searchTimer);
        var query = $(this).val();
        if (query.length < 2) return;

        searchTimer = setTimeout(function() {
            // Could add AJAX autocomplete here in future
        }, 300);
    });

    // === Confirm Delete ===
    $(document).on('click', '.btn-delete-confirm', function(e) {
        if (!confirm('Are you sure you want to delete this? This action cannot be undone.')) {
            e.preventDefault();
        }
    });

    // === Tooltips ===
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(el) {
        return new bootstrap.Tooltip(el);
    });

    // === Form Validation ===
    (function() {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();

    // === Password Strength Indicator ===
    $('#password, #reg-password').on('input', function() {
        var password = $(this).val();
        var strength = 0;
        var indicator = $(this).siblings('.password-strength');

        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;

        var labels = ['Weak', 'Fair', 'Good', 'Strong'];
        var colors = ['danger', 'warning', 'info', 'success'];

        if (password.length > 0 && indicator.length) {
            indicator.html('<small class="text-' + colors[strength - 1] + '">' + labels[strength - 1] + '</small>');
        } else if (indicator.length) {
            indicator.html('');
        }
    });

    // === Smooth Scroll ===
    $('a[href^="#"]').on('click', function(e) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({ scrollTop: target.offset().top - 80 }, 500);
        }
    });
});
