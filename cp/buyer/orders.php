<?php
session_start();
require_once '../includes/functions.php';

if (!isBuyer()) {
    redirect('../login.php');
}

$orders = getBuyerOrders($_SESSION['user_id']);

$page_title = 'My Orders';
include '../includes/header.php';
?>

<div class="container py-4">
    <h2 class="mb-4">My Orders</h2>

    <?php if (empty($orders)): ?>
        <div class="alert alert-info">
            You haven't placed any orders yet. <a href="../marketplace/products.php">Browse products</a> to get started.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
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
                            <?php foreach ($orders as $order): ?>
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
                                        <?php if ($order['status'] === 'pending' || $order['status'] === 'processing'): ?>
                                            <button class="btn btn-sm btn-outline-danger cancel-order" data-order-id="<?php echo $order['order_id']; ?>">Cancel</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cancel order buttons
    const cancelButtons = document.querySelectorAll('.cancel-order');
    cancelButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            
            if (confirm('Are you sure you want to cancel this order?')) {
                fetch('../includes/cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `order_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload the page to show updated status
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while canceling the order.');
                });
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>