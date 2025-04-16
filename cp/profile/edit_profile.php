<?php
session_start();
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$user = getUserData($_SESSION['user_id']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zip_code = trim($_POST['zip_code']);
    $country = trim($_POST['country']);
    $bio = trim($_POST['bio']);
    
    // For farmers
    $farm_name = trim($_POST['farm_name'] ?? '');
    $farm_location = trim($_POST['farm_location'] ?? '');
    
    // Validation
    if (empty($first_name)) {
        $errors['first_name'] = 'First name is required';
    }
    
    if (empty($last_name)) {
        $errors['last_name'] = 'Last name is required';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    // Check if email is already taken by another user
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
    $stmt->execute([$email, $_SESSION['user_id']]);
    if ($stmt->fetchColumn() > 0) {
        $errors['email'] = 'Email is already taken';
    }
    
    // Handle file upload
    $profile_image = $user['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../assets/images/profiles/';
            $file_ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $new_filename)) {
                // Delete old image if it's not the default
                if ($profile_image && $profile_image !== 'default.jpg') {
                    @unlink($upload_dir . $profile_image);
                }
                $profile_image = $new_filename;
            } else {
                $errors['profile_image'] = 'Failed to upload image';
            }
        } else {
            $errors['profile_image'] = 'Only JPG, PNG, and GIF files are allowed';
        }
    }
    
    if (empty($errors)) {
        // Update user in database
        $stmt = $pdo->prepare("UPDATE users SET 
                              first_name = ?, last_name = ?, email = ?, phone = ?, 
                              address = ?, city = ?, state = ?, zip_code = ?, country = ?, 
                              profile_image = ?, bio = ?, farm_name = ?, farm_location = ?, 
                              updated_at = NOW() 
                              WHERE user_id = ?");
        
        $stmt->execute([
            $first_name, $last_name, $email, $phone,
            $address, $city, $state, $zip_code, $country,
            $profile_image, $bio, $farm_name, $farm_location,
            $_SESSION['user_id']
        ]);
        
        $_SESSION['success_message'] = 'Profile updated successfully';
        redirect('view_profile.php');
    }
}

$page_title = 'Edit Profile';
include '../includes/header.php';

// Display success message if redirected after update
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
?>

<div class="container py-4">
    <div class="card">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0">Edit Profile</h4>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" 
                                   id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? $user['first_name']); ?>" required>
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['first_name']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" 
                                   id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? $user['last_name']); ?>" required>
                            <?php if (isset($errors['last_name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['last_name']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                           id="email" name="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? $user['email']); ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="tel" class="form-control" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? $user['phone']); ?>">
                </div>
                
                <div class="mb-3">
                    <label for="profile_image" class="form-label">Profile Image</label>
                    <input type="file" class="form-control <?php echo isset($errors['profile_image']) ? 'is-invalid' : ''; ?>" 
                           id="profile_image" name="profile_image" accept="image/*">
                    <?php if (isset($errors['profile_image'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['profile_image']; ?></div>
                    <?php endif; ?>
                    <?php if ($user['profile_image']): ?>
                        <div class="mt-2">
                            <img src="../assets/images/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                 class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($user['user_type'] === 'farmer'): ?>
                    <div class="mb-3">
                        <label for="farm_name" class="form-label">Farm Name</label>
                        <input type="text" class="form-control" id="farm_name" name="farm_name" 
                               value="<?php echo htmlspecialchars($_POST['farm_name'] ?? $user['farm_name']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="farm_location" class="form-label">Farm Location</label>
                        <input type="text" class="form-control" id="farm_location" name="farm_location" 
                               value="<?php echo htmlspecialchars($_POST['farm_location'] ?? $user['farm_location']); ?>">
                    </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" class="form-control" id="address" name="address" 
                           value="<?php echo htmlspecialchars($_POST['address'] ?? $user['address']); ?>">
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" 
                                   value="<?php echo htmlspecialchars($_POST['city'] ?? $user['city']); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="state" class="form-label">State</label>
                            <input type="text" class="form-control" id="state" name="state" 
                                   value="<?php echo htmlspecialchars($_POST['state'] ?? $user['state']); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="zip_code" class="form-label">Zip Code</label>
                            <input type="text" class="form-control" id="zip_code" name="zip_code" 
                                   value="<?php echo htmlspecialchars($_POST['zip_code'] ?? $user['zip_code']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="country" class="form-label">Country</label>
                    <input type="text" class="form-control" id="country" name="country" 
                           value="<?php echo htmlspecialchars($_POST['country'] ?? $user['country']); ?>">
                </div>
                
                <div class="mb-3">
                    <label for="bio" class="form-label">Bio</label>
                    <textarea class="form-control" id="bio" name="bio" rows="3"><?php 
                        echo htmlspecialchars($_POST['bio'] ?? $user['bio']); 
                    ?></textarea>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-success">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>