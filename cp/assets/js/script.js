document.addEventListener('DOMContentLoaded', function() {
    // Add to cart functionality
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const quantity = this.closest('form')?.querySelector('.quantity')?.value || 1;
            
            fetch('includes/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}&action=add`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count display
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    } else {
                        // Create cart count if it doesn't exist
                        const cartLink = document.querySelector('a[href*="cart.php"]');
                        if (cartLink) {
                            const badge = document.createElement('span');
                            badge.className = 'cart-count badge bg-danger';
                            badge.textContent = data.cart_count;
                            cartLink.appendChild(badge);
                        }
                    }
                    
                    // Show success message
                    alert('Product added to cart!');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add to cart');
            });
        });
    });
});
    
    // Cart quantity adjustments
    const quantityInputs = document.querySelectorAll('.cart-quantity');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const productId = this.getAttribute('data-product-id');
            const newQuantity = this.value;
            
            fetch('../includes/update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${newQuantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update subtotal
                    const subtotalElement = document.querySelector(`.subtotal-${productId}`);
                    if (subtotalElement) {
                        subtotalElement.textContent = '$' + data.subtotal.toFixed(2);
                    }
                    
                    // Update total
                    const totalElement = document.querySelector('.cart-total');
                    if (totalElement) {
                        totalElement.textContent = '$' + data.total.toFixed(2);
                    }
                } else {
                    alert('Error: ' + data.message);
                    // Reset to original value
                    this.value = this.defaultValue;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating quantity.');
                this.value = this.defaultValue;
            });
        });
    });
    
    // Remove from cart buttons
    const removeButtons = document.querySelectorAll('.remove-from-cart');
    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                fetch('../includes/remove_from_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove item from DOM
                        const cartItem = document.querySelector(`.cart-item-${productId}`);
                        if (cartItem) {
                            cartItem.remove();
                        }
                        
                        // Update cart count in navbar
                        const cartCount = document.querySelector('.cart-count');
                        if (cartCount) {
                            cartCount.textContent = data.cart_count;
                        }
                        
                        // Update total
                        const totalElement = document.querySelector('.cart-total');
                        if (totalElement) {
                            totalElement.textContent = '$' + data.total.toFixed(2);
                        }
                        
                        // If cart is empty, show message
                        if (data.cart_count == 0) {
                            const cartContainer = document.querySelector('.cart-container');
                            if (cartContainer) {
                                cartContainer.innerHTML = `
                                    <div class="alert alert-info text-center">
                                        Your cart is empty. <a href="../marketplace/products.php">Browse products</a> to add items.
                                    </div>
                                `;
                            }
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while removing item.');
                });
            }
        });
    });
    
    // Wishlist toggle
    const wishlistButtons = document.querySelectorAll('.wishlist-toggle');
    wishlistButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const isInWishlist = this.classList.contains('active');
            
            fetch('../includes/toggle_wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&action=${isInWishlist ? 'remove' : 'add'}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Toggle button appearance
                    if (isInWishlist) {
                        this.classList.remove('active');
                        this.innerHTML = '<i class="far fa-heart"></i> Add to Wishlist';
                    } else {
                        this.classList.add('active');
                        this.innerHTML = '<i class="fas fa-heart"></i> In Wishlist';
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating wishlist.');
            });
        });
    });
    
    // Favorite seller toggle
    const favoriteButtons = document.querySelectorAll('.favorite-toggle');
    favoriteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const farmerId = this.getAttribute('data-farmer-id');
            const isFavorite = this.classList.contains('active');
            
            fetch('../includes/toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `farmer_id=${farmerId}&action=${isFavorite ? 'remove' : 'add'}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Toggle button appearance
                    if (isFavorite) {
                        this.classList.remove('active');
                        this.innerHTML = '<i class="far fa-star"></i> Add to Favorites';
                    } else {
                        this.classList.add('active');
                        this.innerHTML = '<i class="fas fa-star"></i> Favorite';
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating favorites.');
            });
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
});