<?php
// Start session at the very top (uncommented)
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/functions.php';

// Debugging - check if we reach this point
echo "<!-- Debug: Script started -->";

if (isLoggedIn()) {
    // Debug before redirect
    echo "<!-- Debug: User is logged in, redirecting -->";
    redirect('index.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug received POST data
    echo "<!-- Debug: POST received -->";
    print_r($_POST);

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug database query
        echo "<!-- Debug: User query executed -->";
        
        if ($user && password_verify($password, $user['password'])) {
            // Debug before session assignment
            echo "<!-- Debug: Password verified -->";
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            
            // Debug session values
            echo "<!-- Debug: Session set -->";
            print_r($_SESSION);
            
            // Fix path separators for Windows/Linux compatibility
            $redirect_path = ($user['user_type'] === 'farmer') 
                ? 'farmer/dashboard.php' 
                : 'buyer/dashboard.php';
            
            // Debug before redirect
            echo "<!-- Debug: Redirecting to $redirect_path -->";
            redirect($redirect_path);
            exit();
        } else {
            $error = 'Invalid username or password.';
            // Debug failed login
            echo "<!-- Debug: Login failed -->";
        }
    }
}

$page_title = 'Login';
include 'includes/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Login</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username or Email</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">Login</button>
                        </div>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <p>Don't have an account? <a href="register.php">Register here</a></p>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>