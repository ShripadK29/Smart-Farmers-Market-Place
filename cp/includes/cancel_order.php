<?php
//session_start();
require_once 'functions.php';

if (!isBuyer()) {
    echo json_encode(['success' => false, 'message' => 'Please login as buyer to cancel orders']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? 0;
    $buyer_id = $_SESSION['user_id'];
    
    // Verify order belongs to buyer
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND buyer_id = ?");
    $stmt->execute([$order_id, $buyer_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    // Only allow canceling pending or processing orders
    if (!in_array($order['status'], ['pending', 'processing'])) {
        echo json_encode(['success' => false, 'message' => 'Order cannot be canceled at this stage']);
        exit;
    }
    
    // Update order status
    $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
    $stmt->execute([$order_id]);
    
    // Restore product quantities
    $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();
    
    foreach ($items as $item) {
        $stmt = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE product_id = ?");
        $stmt->execute([$item['quantity'], $item['product_id']]);
    }
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>