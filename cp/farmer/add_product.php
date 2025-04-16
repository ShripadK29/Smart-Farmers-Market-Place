<?php
session_start();
require_once '../includes/functions.php';

if (!isFarmer()) {
    header("Location: ../login.php");
    exit();
}

$categories = getCategories();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category_id = $_POST['category_id'];
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $is_organic = isset($_POST['is_organic']) ? 1 : 0;
    
    // Validation
    if (empty($name)) {
        $errors['name'] = 'Product name is required.';
    }
    
    if (empty($category_id) || !in_array($category_id, array_column($categories, 'category_id'))) {
        $errors['category_id'] = 'Please select a valid category.';
    }
    
    if (empty($description)) {
        $errors['description'] = 'Description is required.';
    }
    
    if (empty($price) || !is_numeric($price) || $price <= 0) {
        $errors['price'] = 'Please enter a valid price.';
    }
    
    if (empty($quantity) || !is_numeric($quantity) || $quantity < 0) {
        $errors['quantity'] = 'Please enter a valid quantity.';
    }
    
    if (empty($unit)) {
        $errors['unit'] = 'Please specify the unit.';
    }
    
    // Handle image upload
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors['image'] = 'Only JPG, PNG, and GIF images are allowed.';
        } else {
            $upload_dir = '../assets/images/products';
            $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('product_') . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                $image = $file_name;
            } else {
                $errors['image'] = 'Failed to upload image.';
            }
        }
    } else {
        $errors['image'] = 'Product image is required.';
    }
    
    if (empty($errors)) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO products (farmer_id, category_id, name, description, price, quantity, unit, image, is_organic) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $category_id, $name, $description, $price, $quantity, $unit, $image, $is_organic])) {
            $success = true;
        } else {
            $errors['general'] = 'Failed to add product. Please try again.';
        }
    }
}

$page_title = 'Add Product';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="card-title text-center mb-4">Add New Product</h2>
                    
                    <?php if (!empty($errors['general'])): ?>
                        <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            Product added successfully! 
                            <a href="dashboard.php" class="alert-link">Return to Dashboard</a> or 
                            <a href="add_product.php" class="alert-link">Add Another Product</a>
                        </div>
                    <?php else: ?>
                        <form action="add_product.php" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Product Name</label>
                                    <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                                           id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                    <?php if (isset($errors['name'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="category_id" class="form-label">Category</label>
                                    <select class="form-select <?php echo isset($errors['category_id']) ? 'is-invalid' : ''; ?>" 
                                            id="category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['category_id']; ?>" 
                                                <?php echo ($_POST['category_id'] ?? '') == $category['category_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['category_id'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['category_id']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" 
                                          id="description" name="description" rows="3" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                <?php if (isset($errors['description'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['description']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="price" class="form-label">Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="0.01" class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : ''; ?>" 
                                               id="price" name="price" value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" required>
                                        <?php if (isset($errors['price'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['price']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="quantity" class="form-label">Quantity Available</label>
                                    <input type="number" class="form-control <?php echo isset($errors['quantity']) ? 'is-invalid' : ''; ?>" 
                                           id="quantity" name="quantity" value="<?php echo htmlspecialchars($_POST['quantity'] ?? ''); ?>" required>
                                    <?php if (isset($errors['quantity'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['quantity']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="unit" class="form-label">Unit</label>
                                    <input type="text" class="form-control <?php echo isset($errors['unit']) ? 'is-invalid' : ''; ?>" 
                                           id="unit" name="unit" value="<?php echo htmlspecialchars($_POST['unit'] ?? ''); ?>" placeholder="e.g., lb, kg, each" required>
                                    <?php if (isset($errors['unit'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['unit']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_organic" name="is_organic" 
                                           <?php echo isset($_POST['is_organic']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_organic">
                                        Organic Product
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="image" class="form-label">Product Image</label>
                                <input type="file" class="form-control <?php echo isset($errors['image']) ? 'is-invalid' : ''; ?>" 
                                       id="image" name="image" accept="image/*" required>
                                <?php if (isset($errors['image'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['image']; ?></div>
                                <?php endif; ?>
                                <div class="form-text">Upload a clear image of your product (JPEG, PNG, or GIF).</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success">Add Product</button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>