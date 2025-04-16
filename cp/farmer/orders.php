<?php
//session_start();
require_once '../includes/functions.php';

if (!isFarmer()) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$orders = getFarmerOrders($user_id);

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $item_id = $_POST['item_id'];
    $new_status = $_POST['status'];
    
    global $pdo;
    $stmt = $pdo->prepare("UPDATE order_items SET status = ? WHERE item_id = ? AND farmer_id = ?");
    if ($stmt->execute([$new_status, $item_id, $user_id])) {
        $_SESSION['success_message'] = 'Order status updated successfully.';
        
        // If all items in the order are delivered, update the main order status
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND status != 'delivered'");
        $stmt->execute([$order_id]);
        $undelivered_items = $stmt->fetchColumn();
        
        if ($undelivered_items == 0) {
            $stmt = $pdo->prepare("UPDATE orders SET status = 'delivered' WHERE order_id = ?");
            $stmt->execute([$order_id]);
        }
        
        header("Location: orders.php");
        exit();
    } else {
        $_SESSION['error_message'] = 'Failed to update order status.';
    }
}

// View single order details
$order_details = null;
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
    $order_id = $_GET['id'];
    $order_details = getOrderDetails($order_id);
    
    // Filter to only show items from this farmer
    if ($order_details) {
        $order_details['items'] = array_filter($order_details['items'], function($item) use ($user_id) {
            return $item['farmer_id'] == $user_id;
        });
        
        if (empty($order_details['items'])) {
            $order_details = null;
        }
    }
}

$page_title = 'Manage Orders';
include '../includes/header.php';

// Display success/error messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Orders</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
    
    <?php if ($order_details): ?>
        <!-- Single Order View -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Order #<?php echo $order_details['order_id']; ?></h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Order Information</h6>
                        <p><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order_details['order_date'])); ?><br>
                        <strong>Status:</strong> <span class="badge 
                            <?php 
                            switch($order_details['order_status']) {
                                case 'pending': echo 'bg-warning'; break;
                                case 'processing': echo 'bg-info'; break;
                                case 'shipped': echo 'bg-primary'; break;
                                case 'delivered': echo 'bg-success'; break;
                                case 'cancelled': echo 'bg-danger'; break;
                            }
                            ?>">
                            <?php echo ucfirst($order_details['order_status']); ?>
                        </span><br>
                        <strong>Payment Method:</strong> <?php echo htmlspecialchars($order_details['payment_method'] ?? 'N/A'); ?><br>
                        <strong>Payment Status:</strong> <?php echo htmlspecialchars(ucfirst($order_details['payment_status'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Buyer Information</h6>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($order_details['buyer_username']); ?><br>
                        <strong>Email:</strong> <?php echo htmlspecialchars($order_details['buyer_email']); ?></p>
                        
                        <h6 class="mt-3">Shipping Address</h6>
                        <p><?php echo nl2br(htmlspecialchars($order_details['shipping_address'])); ?></p>
                    </div>
                </div>
                
                <h6>Order Items</h6>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_details['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($item['image']): ?>
                                                <img src="../assets/images/products/<?php echo htmlspecialchars($item['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>" class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                <small>Sold by: <?php echo htmlspecialchars($item['farmer_username']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                            switch($item['status']) {
                                                case 'pending': echo 'bg-warning'; break;
                                                case 'processing': echo 'bg-info'; break;
                                                case 'shipped': echo 'bg-primary'; break;
                                                case 'delivered': echo 'bg-success'; break;
                                                case 'cancelled': echo 'bg-danger'; break;
                                            }
                                            ?>">
                                            <?php echo ucfirst($item['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-flex">
                                            <input type="hidden" name="order_id" value="<?php echo $order_details['order_id']; ?>">
                                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                            <select name="status" class="form-select form-select-sm me-2">
                                                <option value="pending" <?php echo $item['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="processing" <?php echo $item['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="shipped" <?php echo $item['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="delivered" <?php echo $item['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-sm btn-success">Update</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-end mt-3">
                    <a href="orders.php" class="btn btn-outline-secondary">Back to All Orders</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- All Orders List -->
        <div class="card">
            <div class="card-body">
                <?php if (!empty($orders)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Product</th>
                                    <th>Buyer</th>
                                    <th>Date</th>
                                    <th>Qty</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo $order['order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['buyer_username']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                        <td><?php echo $order['quantity']; ?></td>
                                        <td>$<?php echo number_format($order['price'] * $order['quantity'], 2); ?></td>
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
                                        <td>
                                            <a href="orders.php?action=view&id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-success">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        You have no orders yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>