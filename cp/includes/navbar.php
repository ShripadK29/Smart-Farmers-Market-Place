<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="../index.php">Smart Farmers Marketplace</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'products.php' ? 'active' : ''; ?>" href="../marketplace/products.php">Products</a>
                </li>
                <?php if (isLoggedIn()): ?>
                    <?php if (isFarmer()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($current_page, 'farmer/') !== false ? 'active' : ''; ?>" href="farmer\dashboard.php">Dashboard</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($current_page, 'buyer/') !== false ? 'active' : ''; ?>" href="../buyer/dashboard.php">Dashboard</a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <?php if (isBuyer()): ?>
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="buyer/cart.php">Cart
                                <i class="fas fa-shopping-cart"></i>
                                <?php 
                                $cart_count = count(getCartItems($_SESSION['user_id']));
                                if ($cart_count > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $cart_count; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <!-- <li class="nav-item">
                            <a class="nav-link" href="../buyer/wishlist.php">Wishlist
                                <i class="fas fa-heart"></i>
                            </a>
                        </li> -->
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../profile/view_profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'login.php' ? 'active' : ''; ?>" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'register.php' ? 'active' : ''; ?>" href="register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>