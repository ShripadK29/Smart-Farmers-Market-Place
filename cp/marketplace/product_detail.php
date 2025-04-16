<?php
session_start();
require_once '../includes/functions.php';

$product_id = $_GET['id'] ?? 0;
$product = getProductById($product_id);

if (!$product) {
    redirect('products.php');
}

$average_rating = getProductAverageRating($product_id);
$reviews = getProductReviews($product_id);
$is_in_wishlist = isProductInWishlist($_SESSION['user_id'] ?? 0, $product_id);
$is_favorite_farmer = isFavoriteFarmer($_SESSION['user_id'] ?? 0, $product['farmer_id']);

$page_title = $product['name'];
include '../includes/header.php';

// Add to functions.php:
/*
function isFavoriteFarmer($buyer_id, $farmer_id) {
    if (!$buyer_id) return false;
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorite_sellers WHERE buyer_id = ? AND farmer_id = ?");
    $stmt->execute([$buyer_id, $farmer_id]);
    return $stmt->fetchColumn() > 0;
}
*/
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-6">
            <img src="../assets/images/products/<?php echo htmlspecialchars($product['image'] ?? 'default.jpg'); ?>" 
                 class="img-fluid rounded product-detail-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>
        <div class="col-md-6">
            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
            <p class="text-muted">Category: <?php echo htmlspecialchars($product['category_name']); ?></p>
            
            <div class="d-flex align-items-center mb-3">
                <div class="rating me-2">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star<?php echo $i > $average_rating ? '-half-alt' : ''; ?>"></i>
                    <?php endfor; ?>
                    <span>(<?php echo $average_rating; ?>)</span>
                </div>
                <small class="text-muted"><?php echo count($reviews); ?> reviews</small>
            </div>
            
            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="text-success">$<?php echo number_format($product['price'], 2); ?></h4>
                    <p class="text-<?php echo $product['quantity'] > 0 ? 'success' : 'danger'; ?>">
                        <?php echo $product['quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                    </p>
                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                    
                    <?php if ($product['is_organic']): ?>
                        <span class="badge bg-success">Organic</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Sold by:</h6>
                            <h5><?php echo htmlspecialchars($product['farm_name']); ?></h5>
                            <p class="text-muted mb-0">@<?php echo htmlspecialchars($product['farmer_username']); ?></p>
                        </div>
                        <?php if (isLoggedIn() && isBuyer()): ?>
                            <button class="btn btn-sm <?php echo $is_favorite_farmer ? 'btn-warning' : 'btn-outline-warning'; ?> favorite-toggle" 
                                    data-farmer-id="<?php echo $product['farmer_id']; ?>">
                                <i class="fas fa-star"></i> <?php echo $is_favorite_farmer ? 'Favorite' : 'Add to Favorites'; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($product['quantity'] > 0 && isLoggedIn() && isBuyer()): ?>
                <div class="card">
                    <div class="card-body">
                        <form class="add-to-cart-form">
                            <div class="row g-3 align-items-center">
                                <div class="col-auto">
                                    <label for="quantity" class="col-form-label">Quantity:</label>
                                </div>
                                <div class="col-auto">
                                    <input type="number" class="form-control" id="quantity" 
                                           value="1" min="1" max="<?php echo $product['quantity']; ?>">
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-success add-to-cart" 
                                            data-product-id="<?php echo $product['product_id']; ?>">
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </button>
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-outline-danger wishlist-toggle <?php echo $is_in_wishlist ? 'active' : ''; ?>" 
                                            data-product-id="<?php echo $product['product_id']; ?>">
                                        <i class="fas fa-heart"></i> <?php echo $is_in_wishlist ? 'In Wishlist' : 'Wishlist'; ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php elseif (!isLoggedIn()): ?>
                <div class="alert alert-info">
                    Please <a href="../login.php">login</a> as a buyer to purchase products.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Product Reviews</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($reviews)): ?>
                        <p>No reviews yet. Be the first to review this product!</p>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="mb-4 pb-3 border-bottom">
                                <div class="d-flex justify-content-between mb-2">
                                    <h5><?php echo htmlspecialchars($review['username']); ?></h5>
                                    <div class="rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i > $review['rating'] ? '-half-alt' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="text-muted"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></p>
                                <p><?php echo htmlspecialchars($review['review']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (isLoggedIn() && isBuyer()): ?>
                        <div class="mt-4">
                            <h5>Write a Review</h5>
                            <form action="index.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Rating</label>
                                    <div class="rating-input">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="far fa-star" data-rating="<?php echo $i; ?>"></i>
                                        <?php endfor; ?>
                                        <input type="hidden" name="rating" id="rating-value" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="review" class="form-label">Review</label>
                                    <textarea class="form-control" id="review" name="review" rows="3" required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-success">Submit Review</button>
                            </form>
                        </div>
                    <?php elseif (!isLoggedIn()): ?>
                        <div class="alert alert-info">
                            Please <a href="../login.php">login</a> to write a review.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Rating stars interaction
    const stars = document.querySelectorAll('.rating-input i');
    const ratingInput = document.getElementById('rating-value');
    
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.getAttribute('data-rating');
            ratingInput.value = rating;
            
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.classList.remove('far');
                    s.classList.add('fas');
                } else {
                    s.classList.remove('fas');
                    s.classList.add('far');
                }
            });
        });
        
        star.addEventListener('mouseover', function() {
            const rating = this.getAttribute('data-rating');
            
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.classList.add('hover');
                } else {
                    s.classList.remove('hover');
                }
            });
        });
        
        star.addEventListener('mouseout', function() {
            stars.forEach(s => {
                s.classList.remove('hover');
            });
        });
    });
    
    // Add to cart with quantity
    const addToCartBtn = document.querySelector('.add-to-cart');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const quantity = document.getElementById('quantity').value;
            
            fetch('../includes/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}&action=add`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count in navbar
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    } else {
                        // Create cart count badge if it doesn't exist
                        const cartLink = document.querySelector('a[href*="cart.php"]');
                        if (cartLink) {
                            const badge = document.createElement('span');
                            badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-count';
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
                alert('An error occurred while adding to cart.');
            });
        });
    }
});
</script>

<style>
.rating-input i {
    cursor: pointer;
    font-size: 1.5rem;
    color: #ffc107;
}

.rating-input i.hover {
    color: #ffc107;
    opacity: 0.7;
}
</style>

<?php include '../includes/footer.php'; ?>