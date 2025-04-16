<?php
session_start();
require_once 'functions.php';

header('Content-Type: application/json');

if (!isBuyer()) {
    echo json_encode(['success' => false, 'message' => 'Please login as buyer']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? 0;
    $action = $_POST['action'] ?? 'add';
    $quantity = $_POST['quantity'] ?? 1;
    $buyer_id = $_SESSION['user_id'];

    try {
        global $pdo;
        
        // Verify product exists and is available
        $stmt = $pdo->prepare("SELECT quantity FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            throw new Exception('Product not found');
        }

        if ($action === 'add') {
            // Check if already in cart
            $stmt = $pdo->prepare("SELECT * FROM cart WHERE buyer_id = ? AND product_id = ?");
            $stmt->execute([$buyer_id, $product_id]);
            $existing = $stmt->fetch();

            if ($existing) {
                $new_quantity = $existing['quantity'] + $quantity;
                if ($new_quantity > $product['quantity']) {
                    throw new Exception('Not enough stock available');
                }
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
                $stmt->execute([$new_quantity, $existing['cart_id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO cart (buyer_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$buyer_id, $product_id, $quantity]);
            }
        }

        // Get updated cart count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE buyer_id = ?");
        $stmt->execute([$buyer_id]);
        $cart_count = $stmt->fetchColumn();

        echo json_encode(['success' => true, 'cart_count' => $cart_count]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}