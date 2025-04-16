<?php
//session_start();
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$product_id = $_GET['product_id'] ?? 0;
$farmer_id = $_GET['farmer_id'] ?? 0;

// Validate if the user can review
$can_review_product = false;
$can_review_farmer = false;

if ($product_id) {
    // Check if user has purchased this product
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items oi 
                          JOIN orders o ON oi.order_id = o.order_id 
                          WHERE o.buyer_id = ? AND oi.product_id = ? AND o.status = 'delivered'");
    $stmt->execute([$user_id, $product_id]);
    $can_review_product = $stmt->fetchColumn() > 0;
    
    if (!$can_review_product) {
        $_SESSION['error_message'] = 'You need to purchase this product before reviewing it';
        redirect('../marketplace/product_detail.php?id=' . $product_id);
    }
    
    // Check if already reviewed
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_reviews WHERE buyer_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error_message'] = 'You have already reviewed this product';
        redirect('../marketplace/product_detail.php?id=' . $product_id);
    }
    
    $product = getProductById($product_id);
    $page_title = 'Review Product: ' . $product['name'];
} elseif ($farmer_id) {
    // Check if user has purchased from this farmer
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items oi 
                          JOIN orders o ON oi.order_id = o.order_id 
                          WHERE o.buyer_id = ? AND oi.farmer_id = ? AND o.status = 'delivered'");
    $stmt->execute([$user_id, $farmer_id]);
    $can_review_farmer = $stmt->fetchColumn() > 0;
    
    if (!$can_review_farmer) {
        $_SESSION['error_message'] = 'You need to purchase products from this farmer before reviewing';
        redirect('view_profile.php?id=' . $farmer_id);
    }
    
    // Check if already reviewed
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM farmer_reviews WHERE buyer_id = ? AND farmer_id = ?");
    $stmt->execute([$user_id, $farmer_id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error_message'] = 'You have already reviewed this farmer';
        redirect('view_profile.php?id=' . $farmer_id);
    }
    
    $farmer = getUserData($farmer_id);
    $page_title = 'Review Farmer: ' . $farmer['farm_name'];
} else {
    redirect('../index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'] ?? 0;
    $review = trim($_POST['review'] ?? '');
    
    // Validation
    if ($rating < 1 || $rating > 5) {
        $errors['rating'] = 'Please select a rating between 1 and 5';
    }
    
    if (empty($review)) {
        $errors['review'] = 'Review text cannot be empty';
    }
    
    if (empty($errors)) {
        if ($product_id) {
            // Submit product review
            $stmt = $pdo->prepare("INSERT INTO product_reviews (product_id, buyer_id, rating, review) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([$product_id, $user_id, $rating, $review]);
            
            $_SESSION['success_message'] = 'Product review submitted successfully';
            redirect('../marketplace/product_detail.php?id=' . $product_id);
        } elseif ($farmer_id) {
            // Submit farmer review
            $stmt = $pdo->prepare("INSERT INTO farmer_reviews (farmer_id, buyer_id, rating, review) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([$farmer_id, $user_id, $rating, $review]);
            
            $_SESSION['success_message'] = 'Farmer review submitted successfully';
            redirect('view_profile.php?id=' . $farmer_id);
        }
    }
}

include '../includes/header.php';

// Display error message if any
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><?php echo $page_title; ?></h4>
                </div>
                <div class="card-body">
                    <?php if ($product_id): ?>
                        <div class="d-flex align-items-center mb-4">
                            <img src="../assets/images/products/<?php echo htmlspecialchars($product['image'] ?? 'default.jpg'); ?>" 
                                 class="img-thumbnail me-3" style="width: 80px; height: 80px; object-fit: cover;">
                            <div>
                                <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="text-muted mb-0">Sold by: <?php echo htmlspecialchars($product['farm_name']); ?></p>
                            </div>
                        </div>
                    <?php elseif ($farmer_id): ?>
                        <div class="d-flex align-items-center mb-4">
                            <img src="../assets/images/profiles/<?php echo htmlspecialchars($farmer['profile_image'] ?? 'default.jpg'); ?>" 
                                 class="img-thumbnail rounded-circle me-3" style="width: 80px; height: 80px; object-fit: cover;">
                            <div>
                                <h5><?php echo htmlspecialchars($farmer['farm_name']); ?></h5>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($farmer['farm_location']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Rating</label>
                            <div class="rating-input">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="far fa-star" data-rating="<?php echo $i; ?>"></i>
                                <?php endfor; ?>
                                <input type="hidden" name="rating" id="rating-value" required>
                            </div>
                            <?php if (isset($errors['rating'])): ?>
                                <div class="text-danger"><?php echo $errors['rating']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="review" class="form-label">Review</label>
                            <textarea class="form-control <?php echo isset($errors['review']) ? 'is-invalid' : ''; ?>" 
                                      id="review" name="review" rows="5" required></textarea>
                            <?php if (isset($errors['review'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['review']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">Submit Review</button>
                        </div>
                    </form>
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
});
</script>

<style>
.rating-input i {
    cursor: pointer;
    font-size: 2rem;
    color: #ffc107;
}

.rating-input i.hover {
    color: #ffc107;
    opacity: 0.7;
}
</style>

<?php include '../includes/footer.php'; ?>