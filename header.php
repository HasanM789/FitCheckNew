<?php
// Ensure session is started for cart/login state
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
$is_admin = false;
if (isset($_SESSION['user_id'])) {
    require_once('db_config.php');
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $is_admin = $user && $user['is_admin'] == 1;
}

// Get wishlist count
$wishlist_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $result = $conn->query("SELECT COUNT(*) as count FROM wishlist WHERE user_id = $user_id");
    $wishlist_count = $result->fetch_assoc()['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitCheck — Premium Apparel</title>
    <link rel="stylesheet" href="styles.css">
    
    <!-- Load theme before page renders -->
    <script>
    (function() {
        var saved = localStorage.getItem('theme');
        if (saved) {
            document.documentElement.setAttribute('data-theme', saved);
        } else {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    })();
    </script>
</head>
<body>

<header class="fc-navbar-main">
    <a href="index.php" class="fc-logo-container">
        <svg class="fc-logo-svg" viewBox="0 0 100 100" width="38" height="38" xmlns="http://www.w3.org/2000/svg">
            <rect class="fc-box" x="5" y="5" width="90" height="90" fill="#dc3545"/>
            <polyline class="fc-check" points="20,50 40,70 80,30" fill="none" stroke="white" stroke-width="12" stroke-linecap="square"/>
        </svg>
        <div class="fc-logo-text-group">
            <span class="fc-logo-main">Fit Check.</span>
            <span class="fc-logo-sub">YOUR DAILY ESSENTIALS.</span>
        </div>
    </a>
    
    <nav class="fc-nav-links-group">
        <a href="index.php" class="fc-nav-item">Home</a>
        <a href="catalog.php" class="fc-nav-item">Catalog</a>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="fc-account-wrapper">
                <a href="account.php" class="fc-nav-item">Account</a>
                <div class="fc-account-dropdown">
                    <div class="fc-dropdown-user">
                        <span class="dropdown-username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <?php if ($is_admin): ?>
                            <span style="background: #dc3545; color: #fff; padding: 0px 8px; border-radius: 10px; font-size: 9px; margin-left: 5px;">ADMIN</span>
                        <?php endif; ?>
                    </div>
                    <div class="fc-dropdown-divider"></div>
                    <a href="account.php" class="fc-dropdown-item">Dashboard</a>
                    <a href="orders.php" class="fc-dropdown-item">My Orders</a>
                    <a href="wishlist.php" class="fc-dropdown-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:8px;">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                        Wishlist
                        <?php if ($wishlist_count > 0): ?>
                            <span style="background: #dc3545; color: #fff; border-radius: 50%; padding: 0px 6px; font-size: 9px; min-width: 16px; display: inline-block; text-align: center; margin-left: 4px;">
                                <?php echo $wishlist_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="profile.php" class="fc-dropdown-item">Profile Settings</a>
                    <?php if ($is_admin): ?>
                        <div class="fc-dropdown-divider"></div>
                        <a href="admin.php" class="fc-dropdown-item" style="color: #dc3545;">⚙️ Admin Panel</a>
                        <a href="add_product.php" class="fc-dropdown-item" style="color: #28a745;">➕ Add Product</a>
                    <?php endif; ?>
                    <div class="fc-dropdown-divider"></div>
                    <a href="logout.php" class="fc-dropdown-item fc-dropdown-logout">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <div class="fc-account-wrapper">
                <a href="#" class="fc-nav-item">Account</a>
                <div class="fc-account-dropdown">
                    <div class="fc-dropdown-header">Welcome to FitCheck</div>
                    <div class="fc-dropdown-actions">
                        <a href="login.php" class="fc-btn-auth fc-btn-signin">Sign In</a>
                        <a href="register.php" class="fc-btn-auth fc-btn-join">Join</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- WISHLIST HEART ICON - RIGHT OF ACCOUNT -->
        <a href="wishlist.php" class="fc-nav-item" style="display: flex; align-items: center; gap: 4px; padding: 8px 4px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-primary);">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
            <?php if ($wishlist_count > 0): ?>
                <span style="background: #dc3545; color: #fff; border-radius: 50%; padding: 0px 5px; font-size: 9px; min-width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; line-height: 1;">
                    <?php echo $wishlist_count; ?>
                </span>
            <?php endif; ?>
        </a>

        <!-- THEME TOGGLE BUTTON -->
        <button id="theme-toggle" onclick="toggleTheme()" 
                style="background: transparent; border: none; color: var(--text-primary); font-size: 18px; cursor: pointer; padding: 8px 4px; border-radius: 50%; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center;">
            ☀️
        </button>

        <!-- Cart Button -->
        <a href="cart.php" class="fc-nav-item fc-nav-cart-icon <?php echo isset($_SESSION['cart']) && count($_SESSION['cart']) > 0 ? 'has-items' : ''; ?>">
            <div class="cart-icon-wrapper">
                <svg class="cart-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"/>
                    <circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                <span class="cart-badge <?php echo isset($_SESSION['cart']) && count($_SESSION['cart']) > 0 ? 'has-items' : ''; ?>">
                    <?php 
                    $total_items = 0;
                    if (isset($_SESSION['cart'])) {
                        foreach ($_SESSION['cart'] as $qty) {
                            $total_items += $qty;
                        }
                    }
                    echo $total_items; 
                    ?>
                </span>
            </div>
        </a>
    </nav>
</header>

<!-- Theme toggle script -->
<script src="theme.js"></script>