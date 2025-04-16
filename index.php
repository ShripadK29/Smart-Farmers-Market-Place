<?php
session_start();
require_once 'C:\xampp\htdocs\cp\includes\functions.php';
$page_title = 'Home';
include 'includes\header.php';
?>

<div class="hero-section bg-light py-5">
    <div class="container text-center">
        <h1 class="display-4">Welcome to Smart Farmers Marketplace</h1>
        <p class="lead">Connecting you directly with local farmers for fresh, high-quality produce</p>
        <a href="marketplace/products.php" class="btn btn-success btn-lg mt-3">Browse Products</a>
    </div>
</div>

<div class="container my-5">
    <h2 class="text-center mb-4">Featured Categories</h2>
    <div class="row">
        <?php
        $categories = getCategories();
        foreach ($categories as $category): 
            $products = getProductsByCategory($category['category_id']);
            if (!empty($products)):
        ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($category['description']); ?></p>
                        <a href="marketplace/products.php?category=<?php echo $category['category_id']; ?>" class="btn btn-outline-success">
                            View Products (<?php echo count($products); ?>)
                        </a>
                    </div>
                </div>
            </div>
        <?php 
            endif;
        endforeach; 
        ?>
    </div>
</div>

<div class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-4">Popular Products</h2>
        <div class="row">
            <?php
            $products = getAllProducts();
            $popular_products = array_slice($products, 0, 3); // Just show first 3 for demo
            foreach ($popular_products as $product): 
            ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="../assets/images/products/<?php echo htmlspecialchars($product['image'] ?? 'default.jpg'); ?>" class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-success fw-bold">$<?php echo number_format($product['price'], 2); ?></span>
                                <small class="text-muted">Sold by: <?php echo htmlspecialchars($product['farm_name']); ?></small>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="marketplace/product_detail.php?id=<?php echo $product['product_id']; ?>" class="btn btn-success btn-sm">View Details</a>
                            <?php if (isBuyer()): ?>
                                <button class="btn btn-success add-to-cart" data-product-id="<?php echo $product['product_id']; ?>">
    Add to Cart
</button>                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-3">
            <a href="marketplace/products.php" class="btn btn-success">View All Products</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>