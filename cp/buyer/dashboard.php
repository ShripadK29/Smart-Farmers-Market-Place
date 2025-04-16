<?php
session_start();
require_once '../includes/functions.php';

if (!isBuyer()) {
    redirect('../login.php');
}

$user = getUserData($_SESSION['user_id']);
$recent_orders = getBuyerOrders($_SESSION['user_id'], 5); // Get last 5 orders
$wishlist_count = count(getWishlistItems($_SESSION['user_id']));
$favorite_farmers_count = count(getFavoriteFarmers($_SESSION['user_id']));

$page_title = 'Buyer Dashboard';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4 dashboard-card">
                <div class="card-body text-center">
                    <h5 class="card-title">Recent Orders</h5>
                    <h1 class="display-4"><?php echo count($recent_orders); ?></h1>
                    <a href="orders.php" class="btn btn-outline-success">View All</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4 dashboard-card">
                <div class="card-body text-center">
                    <h5 class="card-title">Wishlist Items</h5>
                    <h1 class="display-4"><?php echo $wishlist_count; ?></h1>
                    <a href="wishlist.php" class="btn btn-outline-success">View Wishlist</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4 dashboard-card">
                <div class="card-body text-center">
                    <h5 class="card-title">Favorite Farmers</h5>
                    <h1 class="display-4"><?php echo $favorite_farmers_count; ?></h1>
                    <a href="favorite_sellers.php" class="btn btn-outline-success">View Farmers</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-success text-white">
            <h4>Recent Orders</h4>
        </div>
        <div class="card-body">
            <?php if (empty($recent_orders)): ?>
                <p>You haven't placed any orders yet. <a href="../marketplace/products.php">Browse products</a> to get started.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><?php echo $order['order_id']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                    <td><?php echo $order['item_count']; ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $order['status'] === 'delivered' ? 'success' : 
                                                 ($order['status'] === 'cancelled' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="../transactions/track_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-success">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>