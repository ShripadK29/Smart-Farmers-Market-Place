<?php
session_start();
require_once '../includes/functions.php';

if (!isBuyer()) {
    redirect('../login.php');
}

$cart_items = getCartItems($_SESSION['user_id']);
$total = 0;

$page_title = 'Shopping Cart';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Shopping Cart</h2>
        <a href="../marketplace/products.php" class="btn btn-outline-success">Continue Shopping</a>
    </div>

    <?php if (empty($cart_items)): ?>
        <div class="alert alert-info text-center">
            Your cart is empty. <a href="../marketplace/products.php">Browse products</a> to add items.
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): 
                                        $subtotal = $item['price'] * $item['quantity'];
                                        $total += $subtotal;
                                    ?>
                                        <tr class="cart-item-<?php echo $item['product_id']; ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../assets/images/products/<?php echo htmlspecialchars($item['image'] ?? 'default.jpg'); ?>" 
                                                         class="img-thumbnail me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                        <small>Available: <?php echo $item['available_quantity']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                                            <td>
                                                <input type="number" class="form-control cart-quantity" 
                                                       data-product-id="<?php echo $item['product_id']; ?>" 
                                                       value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['available_quantity']; ?>" style="width: 70px;">
                                            </td>
                                            <td class="subtotal-<?php echo $item['product_id']; ?>">
                                                $<?php echo number_format($subtotal, 2); ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-danger remove-from-cart" data-product-id="<?php echo $item['product_id']; ?>">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span>$5.00</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total:</span>
                            <span class="cart-total">$<?php echo number_format($total + 5, 2); ?></span>
                        </div>
                        <a href="../transactions/payment.php" class="btn btn-success w-100 mt-3">Proceed to Checkout</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>