<?php
session_start();
require_once '../includes/functions.php';

// Ensure these functions exist in functions.php
if (!function_exists('getProductsByFarmer') || !function_exists('sortProducts')) {
    die('System error: Required functions not available');
}

$category_id = $_GET['category'] ?? 0;
$farmer_id = $_GET['farmer'] ?? 0;
$search_query = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

$page_title = 'Browse Products';
include '../includes/header.php';

// Get products based on filters
if (!empty($search_query)) {
    $products = searchProducts($search_query);
    $page_title = "Search Results for: $search_query";
} elseif ($category_id > 0) {
    $products = getProductsByCategory($category_id);
    $category = getCategoryById($category_id);
    $page_title = $category['name'] ?? 'Category Products';
} elseif ($farmer_id > 0) {
    $products = getProductsByFarmer($farmer_id);
    $farmer = getUserData($farmer_id);
    $page_title = $farmer['farm_name'] ?? 'Farm Products';
} else {
    $products = getAllProducts();
}

// Apply sorting
$products = sortProducts($products, $sort);
?>

<!-- [Rest of your HTML remains exactly the same] -->
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?php echo $page_title; ?></h2>
        
        <div class="d-flex">
            <form class="d-flex me-3" action="products.php" method="get">
                <input class="form-control me-2" type="search" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button class="btn btn-outline-success" type="submit">Search</button>
            </form>
            
            <div class="dropdown">
                <button class="btn btn-success dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown">
                    Sort: <?php echo ucfirst(str_replace('_', ' ', $sort)); ?>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="?<?php echo buildQueryString(['sort' => 'newest']); ?>">Newest</a></li>
                    <li><a class="dropdown-item" href="?<?php echo buildQueryString(['sort' => 'price_low']); ?>">Price: Low to High</a></li>
                    <li><a class="dropdown-item" href="?<?php echo buildQueryString(['sort' => 'price_high']); ?>">Price: High to Low</a></li>
                    <li><a class="dropdown-item" href="?<?php echo buildQueryString(['sort' => 'rating']); ?>">Highest Rating</a></li>
                </ul>
            </div>
        </div>
    </div>

    <?php if (empty($products)): ?>
        <div class="alert alert-info">
            No products found. Try adjusting your search or filters.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($products as $product): 
                $average_rating = getProductAverageRating($product['product_id']);
                $is_in_wishlist = isProductInWishlist($_SESSION['user_id'] ?? 0, $product['product_id']);
            ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="../assets/images/products/<?php echo htmlspecialchars($product['image'] ?? 'default.jpg'); ?>" 
                             class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-success fw-bold">$<?php echo number_format($product['price'], 2); ?></span>
                                <small class="text-muted">Available: <?php echo $product['quantity']; ?></small>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i > $average_rating ? '-half-alt' : ''; ?>"></i>
                                    <?php endfor; ?>
                                    <small>(<?php echo $average_rating; ?>)</small>
                                </div>
                                <small>Sold by: <?php echo htmlspecialchars($product['farm_name']); ?></small>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" class="btn btn-success btn-sm">View Details</a>
                            
                            <?php if (isLoggedIn() && isBuyer()): ?>
                                <button class="btn btn-outline-success btn-sm add-to-cart" data-product-id="<?php echo $product['product_id']; ?>">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                                
                                <button class="btn btn-outline-danger btn-sm wishlist-toggle <?php echo $is_in_wishlist ? 'active' : ''; ?>" 
                                        data-product-id="<?php echo $product['product_id']; ?>">
                                    <i class="fas fa-heart"></i> <?php echo $is_in_wishlist ? 'In Wishlist' : 'Wishlist'; ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php 
// Add to functions.php:
/*
function buildQueryString($new_params) {
    $params = $_GET;
    foreach ($new_params as $key => $value) {
        $params[$key] = $value;
    }
    return http_build_query($params);
}

function isProductInWishlist($buyer_id, $product_id) {
    if (!$buyer_id) return false;
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE buyer_id = ? AND product_id = ?");
    $stmt->execute([$buyer_id, $product_id]);
    return $stmt->fetchColumn() > 0;
}
*/
include '../includes/footer.php'; ?>