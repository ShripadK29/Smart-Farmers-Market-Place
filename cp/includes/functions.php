<?php
require_once(__DIR__ . '/../config/db.php');
// Redirect to specified page
function redirect($page) {
    header("Location: $page");
    exit();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is a farmer
function isFarmer() {
    return isLoggedIn() && $_SESSION['user_type'] === 'farmer';
}

// Check if user is a buyer
function isBuyer() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'buyer'; 
}

// Get user data
function getUserData($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all categories
function getCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get farmer's products
function getFarmerProducts($farmer_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p 
                          JOIN categories c ON p.category_id = c.category_id 
                          WHERE p.farmer_id = ?");
    $stmt->execute([$farmer_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get product by ID
function getProductById($product_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT p.*, u.username as farmer_username, u.farm_name, c.name as category_name 
                          FROM products p 
                          JOIN users u ON p.farmer_id = u.user_id 
                          JOIN categories c ON p.category_id = c.category_id 
                          WHERE p.product_id = ?");
    $stmt->execute([$product_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get products by category
function getProductsByCategory($category_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT p.*, u.username as farmer_username, u.farm_name 
                          FROM products p 
                          JOIN users u ON p.farmer_id = u.user_id 
                          WHERE p.category_id = ? AND p.quantity > 0");
    $stmt->execute([$category_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all available products
function getAllProducts() {
    global $pdo;
    $stmt = $pdo->query("SELECT p.*, u.username as farmer_username, u.farm_name, c.name as category_name 
                        FROM products p 
                        JOIN users u ON p.farmer_id = u.user_id 
                        JOIN categories c ON p.category_id = c.category_id 
                        WHERE p.quantity > 0");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Search products
function searchProducts($query) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT p.*, u.username as farmer_username, u.farm_name, c.name as category_name 
                          FROM products p 
                          JOIN users u ON p.farmer_id = u.user_id 
                          JOIN categories c ON p.category_id = c.category_id 
                          WHERE (p.name LIKE ? OR p.description LIKE ?) AND p.quantity > 0");
    $stmt->execute(["%$query%", "%$query%"]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get cart items for buyer
function getCartItems($buyer_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.image, p.quantity as available_quantity 
                          FROM cart c 
                          JOIN products p ON c.product_id = p.product_id 
                          WHERE c.buyer_id = ?");
    $stmt->execute([$buyer_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get wishlist items for buyer
function getWishlistItems($buyer_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT w.*, p.name, p.price, p.image, p.quantity as available_quantity 
                          FROM wishlist w 
                          JOIN products p ON w.product_id = p.product_id 
                          WHERE w.buyer_id = ?");
    $stmt->execute([$buyer_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get favorite farmers for buyer
function getFavoriteFarmers($buyer_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT f.*, u.username, u.farm_name, u.profile_image 
                          FROM favorite_sellers f 
                          JOIN users u ON f.farmer_id = u.user_id 
                          WHERE f.buyer_id = ?");
    $stmt->execute([$buyer_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get buyer orders
function getBuyerOrders($buyer_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT o.*, 
                          (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count 
                          FROM orders o 
                          WHERE o.buyer_id = ? 
                          ORDER BY o.order_date DESC");
    $stmt->execute([$buyer_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get farmer orders
function getFarmerOrders($farmer_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT oi.*, o.order_date, o.status as order_status, o.total_amount, 
                          p.name as product_name, u.username as buyer_username 
                          FROM order_items oi 
                          JOIN orders o ON oi.order_id = o.order_id 
                          JOIN products p ON oi.product_id = p.product_id 
                          JOIN users u ON o.buyer_id = u.user_id 
                          WHERE oi.farmer_id = ? 
                          ORDER BY o.order_date DESC");
    $stmt->execute([$farmer_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get order details
function getOrderDetails($order_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT o.*, u.username as buyer_username, u.email as buyer_email 
                          FROM orders o 
                          JOIN users u ON o.buyer_id = u.user_id 
                          WHERE o.order_id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        $stmt = $pdo->prepare("SELECT oi.*, p.name, p.image, u.username as farmer_username, u.farm_name 
                              FROM order_items oi 
                              JOIN products p ON oi.product_id = p.product_id 
                              JOIN users u ON oi.farmer_id = u.user_id 
                              WHERE oi.order_id = ?");
        $stmt->execute([$order_id]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $order;
}

// Get product reviews
function getProductReviews($product_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT pr.*, u.username 
                          FROM product_reviews pr 
                          JOIN users u ON pr.buyer_id = u.user_id 
                          WHERE pr.product_id = ? 
                          ORDER BY pr.created_at DESC");
    $stmt->execute([$product_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get farmer reviews
function getFarmerReviews($farmer_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT fr.*, u.username 
                          FROM farmer_reviews fr 
                          JOIN users u ON fr.buyer_id = u.user_id 
                          WHERE fr.farmer_id = ? 
                          ORDER BY fr.created_at DESC");
    $stmt->execute([$farmer_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate average rating for product
function getProductAverageRating($product_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM product_reviews WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['avg_rating'] ? round($result['avg_rating'], 1) : 0;
}

// Calculate average rating for farmer
function getFarmerAverageRating($farmer_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM farmer_reviews WHERE farmer_id = ?");
    $stmt->execute([$farmer_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['avg_rating'] ? round($result['avg_rating'], 1) : 0;
}

// Get farmer sales analytics
function getFarmerSalesAnalytics($farmer_id) {
    global $pdo;
    
    // Total revenue
    $stmt = $pdo->prepare("SELECT SUM(oi.quantity * oi.price) as total_revenue 
                          FROM order_items oi 
                          JOIN orders o ON oi.order_id = o.order_id 
                          WHERE oi.farmer_id = ? AND o.payment_status = 'completed'");
    $stmt->execute([$farmer_id]);
    $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;
    
    // Top products
    $stmt = $pdo->prepare("SELECT p.product_id, p.name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as total_revenue 
                          FROM order_items oi 
                          JOIN products p ON oi.product_id = p.product_id 
                          JOIN orders o ON oi.order_id = o.order_id 
                          WHERE oi.farmer_id = ? AND o.payment_status = 'completed' 
                          GROUP BY p.product_id 
                          ORDER BY total_sold DESC 
                          LIMIT 5");
    $stmt->execute([$farmer_id]);
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Orders per day/week
    $stmt = $pdo->prepare("SELECT DATE(o.order_date) as order_day, COUNT(*) as order_count 
                          FROM order_items oi 
                          JOIN orders o ON oi.order_id = o.order_id 
                          WHERE oi.farmer_id = ? 
                          GROUP BY order_day 
                          ORDER BY order_day DESC 
                          LIMIT 7");
    $stmt->execute([$farmer_id]);
    $daily_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'total_revenue' => $total_revenue,
        'top_products' => $top_products,
        'daily_orders' => $daily_orders
    ];
}
function buildQueryString($new_params) {
    $params = $_GET;
    foreach ($new_params as $key => $value) {
        $params[$key] = $value;
    }
    return http_build_query($params);
}
function getProductsByFarmer($farmer_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                          FROM products p 
                          JOIN categories c ON p.category_id = c.category_id 
                          WHERE p.farmer_id = ? AND p.quantity > 0");
    $stmt->execute([$farmer_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
/**
 * Sort products array based on criteria
 */
function sortProducts($products, $sort) {
    switch ($sort) {
        case 'price_low':
            usort($products, function($a, $b) {
                return $a['price'] <=> $b['price'];
            });
            break;
            
        case 'price_high':
            usort($products, function($a, $b) {
                return $b['price'] <=> $a['price'];
            });
            break;
            
        case 'rating':
            usort($products, function($a, $b) {
                $rating_a = getProductAverageRating($a['product_id']);
                $rating_b = getProductAverageRating($b['product_id']);
                return $rating_b <=> $rating_a;
            });
            break;
            
        case 'newest':
        default:
            usort($products, function($a, $b) {
                return strtotime($b['created_at']) <=> strtotime($a['created_at']);
            });
    }
    return $products;
}
/**
 * Get category by ID
 */
function getCategoryById($category_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->execute([$category_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
/**
 * Check if product is in user's wishlist
 */
function isProductInWishlist($buyer_id, $product_id) {
    if (!$buyer_id) return false;
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE buyer_id = ? AND product_id = ?");
    $stmt->execute([$buyer_id, $product_id]);
    return $stmt->fetchColumn() > 0;
}
/**
 * Check if farmer is in user's favorites
 */
function isFavoriteFarmer($buyer_id, $farmer_id) {
    if (!$buyer_id) return false;
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorite_sellers WHERE buyer_id = ? AND farmer_id = ?");
    $stmt->execute([$buyer_id, $farmer_id]);
    return $stmt->fetchColumn() > 0;
}

?>