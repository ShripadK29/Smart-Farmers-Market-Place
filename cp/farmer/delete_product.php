<?php
session_start();
require_once '../includes/functions.php';

if (!isFarmer()) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$product_id = $_GET['id'];
$product = getProductById($product_id);

// Verify the product belongs to the logged-in farmer
if (!$product || $product['farmer_id'] != $_SESSION['user_id']) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_delete'])) {
        global $pdo;
        
        try {
            $pdo->beginTransaction();
            
            // Delete product image if exists
            if ($product['image']) {
                $image_path = '../assets/images/products/' . $product['image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            // Delete product reviews
            $stmt = $pdo->prepare("DELETE FROM product_reviews WHERE product_id = ?");
            $stmt->execute([$product_id]);
            
            // Delete product from cart and wishlist
            $stmt = $pdo->prepare("DELETE FROM cart WHERE product_id = ?");
            $stmt->execute([$product_id]);
            
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE product_id = ?");
            $stmt->execute([$product_id]);
            
            // Delete the product
            $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ? AND farmer_id = ?");
            $stmt->execute([$product_id, $_SESSION['user_id']]);
            
            $pdo->commit();
            
            $_SESSION['success_message'] = 'Product deleted successfully.';
            header("Location: dashboard.php");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = 'Failed to delete product: ' . $e->getMessage();
            header("Location: dashboard.php");
            exit();
        }
    } else {
        // User cancelled the deletion
        header("Location: dashboard.php");
        exit();
    }
}

$page_title = 'Delete Product';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="card-title text-center mb-4">Delete Product</h2>
                    
                    <div class="alert alert-danger">
                        <h5>Are you sure you want to delete this product?</h5>
                        <p class="mb-0"><strong><?php echo htmlspecialchars($product['name']); ?></strong> - <?php echo htmlspecialchars($product['category_name']); ?></p>
                    </div>
                    
                    <?php if ($product['image']): ?>
                        <div class="text-center mb-3">
                            <img src="../assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-thumbnail" style="max-height: 200px;">
                        </div>
                    <?php endif; ?>
                    
                    <form action="delete_product.php?id=<?php echo $product_id; ?>" method="POST">
                        <div class="d-grid gap-2">
                            <button type="submit" name="confirm_delete" class="btn btn-danger">Yes, Delete Product</button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">No, Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>