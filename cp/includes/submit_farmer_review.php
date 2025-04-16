<?php
session_start();
require_once 'functions.php';

if (!isBuyer()) {
    echo json_encode(['success' => false, 'message' => 'Please login as buyer to submit reviews']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $farmer_id = $_POST['farmer_id'] ?? 0;
    $rating = $_POST['rating'] ?? 0;
    $review = trim($_POST['review'] ?? '');
    $buyer_id = $_SESSION['user_id'];
    
    // Validation
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Please select a rating between 1 and 5']);
        exit;
    }
    
    if (empty($review)) {
        echo json_encode(['success' => false, 'message' => 'Review text cannot be empty']);
        exit;
    }
    
    // Check if farmer exists
    $farmer = getUserData($farmer_id);
    if (!$farmer || $farmer['user_type'] !== 'farmer') {
        echo json_encode(['success' => false, 'message' => 'Invalid farmer']);
        exit;
    }
    
    // Check if buyer has purchased from this farmer
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items oi 
                          JOIN orders o ON oi.order_id = o.order_id 
                          WHERE o.buyer_id = ? AND oi.farmer_id = ? AND o.status = 'delivered'");
    $stmt->execute([$buyer_id, $farmer_id]);
    $has_purchased = $stmt->fetchColumn() > 0;
    
    if (!$has_purchased) {
        echo json_encode(['success' => false, 'message' => 'You must purchase from this farmer before reviewing']);
        exit;
    }
    
    // Check if already reviewed
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM farmer_reviews WHERE buyer_id = ? AND farmer_id = ?");
    $stmt->execute([$buyer_id, $farmer_id]);
    $already_reviewed = $stmt->fetchColumn() > 0;
    
    if ($already_reviewed) {
        echo json_encode(['success' => false, 'message' => 'You have already reviewed this farmer']);
        exit;
    }
    
    // Submit review
    $stmt = $pdo->prepare("INSERT INTO farmer_reviews (farmer_id, buyer_id, rating, review) 
                          VALUES (?, ?, ?, ?)");
    $stmt->execute([$farmer_id, $buyer_id, $rating, $review]);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>