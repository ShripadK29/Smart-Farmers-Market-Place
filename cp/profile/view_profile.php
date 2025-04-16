<?php
session_start();
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$user_id = $_GET['id'] ?? $_SESSION['user_id'];
$profile_user = getUserData($user_id);
$current_user = $_SESSION['user_id'] == $user_id;

if (!$profile_user) {
    redirect('../index.php');
}

$page_title = $current_user ? 'My Profile' : $profile_user['username'] . "'s Profile";
include '../includes/header.php';

// Get reviews if viewing a farmer's profile
if ($profile_user['user_type'] === 'farmer') {
    $reviews = getFarmerReviews($user_id);
    $average_rating = getFarmerAverageRating($user_id);
}
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="../assets/images/profiles/<?php echo htmlspecialchars($profile_user['profile_image'] ?? 'default.jpg'); ?>" 
                         class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    
                    <h3><?php echo htmlspecialchars($profile_user['first_name'] . ' ' . $profile_user['last_name']); ?></h3>
                    <p class="text-muted">@<?php echo htmlspecialchars($profile_user['username']); ?></p>
                    
                    <?php if ($profile_user['user_type'] === 'farmer'): ?>
                        <h5 class="mt-3"><?php echo htmlspecialchars($profile_user['farm_name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($profile_user['farm_location']); ?></p>
                        
                        <div class="rating mb-3">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo $i > $average_rating ? '-half-alt' : ''; ?>"></i>
                            <?php endfor; ?>
                            <span>(<?php echo $average_rating; ?>)</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($current_user): ?>
                        <a href="edit_profile.php" class="btn btn-success mt-2">Edit Profile</a>
                    <?php elseif (isBuyer() && $profile_user['user_type'] === 'farmer'): ?>
                        <?php 
                        $is_favorite = isFavoriteFarmer($_SESSION['user_id'], $user_id);
                        ?>
                        <button class="btn btn-<?php echo $is_favorite ? 'warning' : 'outline-warning'; ?> mt-2 favorite-toggle" 
                                data-farmer-id="<?php echo $user_id; ?>">
                            <i class="fas fa-star"></i> <?php echo $is_favorite ? 'Favorite' : 'Add to Favorites'; ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($profile_user['user_type'] === 'farmer'): ?>
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Farm Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Farm Name:</strong> <?php echo htmlspecialchars($profile_user['farm_name']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($profile_user['farm_location']); ?></p>
                        <?php if (!empty($profile_user['bio'])): ?>
                            <hr>
                            <p><?php echo nl2br(htmlspecialchars($profile_user['bio'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($profile_user['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($profile_user['phone'] ?? 'Not provided'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Address:</strong></p>
                            <address>
                                <?php echo nl2br(htmlspecialchars(
                                    $profile_user['address'] . "\n" .
                                    $profile_user['city'] . ', ' . $profile_user['state'] . ' ' . $profile_user['zip_code'] . "\n" .
                                    $profile_user['country']
                                )); ?>
                            </address>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($profile_user['user_type'] === 'farmer'): ?>
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Products</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        $products = getFarmerProducts($user_id);
                        if (empty($products)): ?>
                            <p>No products listed yet.</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach (array_slice($products, 0, 4) as $product): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <img src="../assets/images/products/<?php echo htmlspecialchars($product['image'] ?? 'default.jpg'); ?>" 
                                                 class="card-img-top" style="height: 120px; object-fit: cover;">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                <p class="card-text text-success">$<?php echo number_format($product['price'], 2); ?></p>
                                            </div>
                                            <div class="card-footer bg-white">
                                                <a href="../marketplace/product_detail.php?id=<?php echo $product['product_id']; ?>" 
                                                   class="btn btn-sm btn-outline-success">View</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="../marketplace/products.php?farmer=<?php echo $user_id; ?>" class="btn btn-success">
                                    View All Products
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Reviews</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($reviews)): ?>
                            <p>No reviews yet.</p>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="mb-4 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between mb-2">
                                        <h6><?php echo htmlspecialchars($review['username']); ?></h6>
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
                        
                        <?php if (isBuyer() && !$current_user && hasPurchasedFromFarmer($_SESSION['user_id'], $user_id)): ?>
                            <div class="mt-4">
                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                    Write a Review
                                </button>
                            </div>
                        <?php elseif (isBuyer() && !$current_user): ?>
                            <div class="alert alert-info mt-3">
                                You need to purchase products from this farmer to leave a review.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Review Modal -->
<?php if (isBuyer() && !$current_user && $profile_user['user_type'] === 'farmer'): ?>
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Review <?php echo htmlspecialchars($profile_user['farm_name']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="../includes/submit_farmer_review.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="farmer_id" value="<?php echo $user_id; ?>">
                    
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Rating stars interaction
    const stars = document.querySelectorAll('.rating-input i');
    const ratingInput = document.getElementById('rating-value');
    
    if (stars.length > 0) {
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
    }
    
    // Favorite toggle
    const favoriteBtn = document.querySelector('.favorite-toggle');
    if (favoriteBtn) {
        favoriteBtn.addEventListener('click', function() {
            const farmerId = this.getAttribute('data-farmer-id');
            const isFavorite = this.classList.contains('btn-warning');
            
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
                    if (isFavorite) {
                        this.classList.remove('btn-warning');
                        this.classList.add('btn-outline-warning');
                        this.innerHTML = '<i class="fas fa-star"></i> Add to Favorites';
                    } else {
                        this.classList.remove('btn-outline-warning');
                        this.classList.add('btn-warning');
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
    }
});
</script>

<style>
.rating {
    color: #ffc107;
}

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

<?php 
// Add to functions.php:
/*
function hasPurchasedFromFarmer($buyer_id, $farmer_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items oi 
                          JOIN orders o ON oi.order_id = o.order_id 
                          WHERE o.buyer_id = ? AND oi.farmer_id = ? AND o.status = 'delivered'");
    $stmt->execute([$buyer_id, $farmer_id]);
    return $stmt->fetchColumn() > 0;
}
*/

include '../includes/footer.php'; ?>