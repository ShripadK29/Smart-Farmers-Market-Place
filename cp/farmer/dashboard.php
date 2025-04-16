<?php
session_start();
require_once(__DIR__ . '/../includes/functions.php');
if (!isFarmer()) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = getUserData($user_id);
$analytics = getFarmerSalesAnalytics($user_id);
$recent_orders = getFarmerOrders($user_id);
$products = getFarmerProducts($user_id);

$page_title = 'Farmer Dashboard';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-3">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="../assets/images/profiles/<?php echo htmlspecialchars($user['profile_image'] ?? 'default.jpg'); ?>" 
                         alt="Profile" class="rounded-circle img-fluid" style="width: 150px;">
                    <h5 class="my-3"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                    <p class="text-muted mb-1"><?php echo htmlspecialchars($user['farm_name']); ?></p>
                    <p class="text-muted mb-4"><?php echo htmlspecialchars($user['farm_location']); ?></p>
                    <div class="d-flex justify-content-center mb-2">
                        <a href="../profile/view_profile.php" class="btn btn-outline-success ms-1">Edit Profile</a>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Quick Actions</h5>
                    <ul class="list-unstyled mb-0">
                        <li><a href="add_product.php" class="text-success"><i class="fas fa-plus-circle me-2"></i>Add Product</a></li>
                        <li><a href="products.php" class="text-success"><i class="fas fa-box-open me-2"></i>View Products</a></li>
                        <li><a href="orders.php" class="text-success"><i class="fas fa-shopping-bag me-2"></i>Manage Orders</a></li>
                        <li><a href="reports.php" class="text-success"><i class="fas fa-chart-bar me-2"></i>View Reports</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Farm Rating</h5>
                    <div class="d-flex align-items-center">
                        <div class="rating me-2">
                            <?php
                            $avg_rating = getFarmerAverageRating($user_id);
                            $full_stars = floor($avg_rating);
                            $half_star = ($avg_rating - $full_stars) >= 0.5;
                            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                            
                            for ($i = 0; $i < $full_stars; $i++) {
                                echo '<i class="fas fa-star"></i>';
                            }
                            if ($half_star) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            }
                            for ($i = 0; $i < $empty_stars; $i++) {
                                echo '<i class="far fa-star"></i>';
                            }
                            ?>
                        </div>
                        <span><?php echo number_format($avg_rating, 1); ?>/5.0</span>
                    </div>
                    <a href="../profile/view_profile.php?user_id=<?php echo $user_id; ?>" class="btn btn-sm btn-outline-success mt-2">View Reviews</a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <h5 class="card-title">Total Revenue</h5>
                            <h2 class="card-text">$<?php echo number_format($analytics['total_revenue'], 2); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <h5 class="card-title">Active Products</h5>
                            <h2 class="card-text"><?php echo count($products); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <h5 class="card-title">Pending Orders</h5>
                            <h2 class="card-text">
                                <?php 
                                $pending_count = 0;
                                foreach ($recent_orders as $order) {
                                    if ($order['status'] === 'pending') $pending_count++;
                                }
                                echo $pending_count;
                                ?>
                            </h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Recent Orders</h5>
                    <?php if (!empty($recent_orders)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Product</th>
                                        <th>Buyer</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($recent_orders, 0, 5) as $order): ?>
                                        <tr>
                                            <td><?php echo $order['order_id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                            <td><?php echo htmlspecialchars($order['buyer_username']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php 
                                                    switch($order['status']) {
                                                        case 'pending': echo 'bg-warning'; break;
                                                        case 'processing': echo 'bg-info'; break;
                                                        case 'shipped': echo 'bg-primary'; break;
                                                        case 'delivered': echo 'bg-success'; break;
                                                        case 'cancelled': echo 'bg-danger'; break;
                                                    }
                                                    ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>$<?php echo number_format($order['price'] * $order['quantity'], 2); ?></td>
                                            <td>
                                                <a href="orders.php?action=view&id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-success">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end">
                            <a href="orders.php" class="btn btn-success">View All Orders</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">You have no recent orders.</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Top Products</h5>
                    <?php if (!empty($analytics['top_products'])): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Units Sold</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analytics['top_products'] as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td><?php echo $product['total_sold']; ?></td>
                                            <td>$<?php echo number_format($product['total_revenue'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No sales data available yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>