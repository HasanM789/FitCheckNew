<?php
require_once('db_config.php');
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}
include('header.php');

$user_id = $_SESSION['user_id'];

// Check if user is admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$is_admin = $user && $user['is_admin'] == 1;

// Get order counts by status
$pending_count = $conn->query("SELECT COUNT(*) as count FROM orders WHERE user_id = $user_id AND status = 'pending'")->fetch_assoc()['count'];
$processing_count = $conn->query("SELECT COUNT(*) as count FROM orders WHERE user_id = $user_id AND status = 'processing'")->fetch_assoc()['count'];
$shipped_count = $conn->query("SELECT COUNT(*) as count FROM orders WHERE user_id = $user_id AND status = 'shipped'")->fetch_assoc()['count'];
$delivered_count = $conn->query("SELECT COUNT(*) as count FROM orders WHERE user_id = $user_id AND status = 'delivered'")->fetch_assoc()['count'];
$total_orders = $pending_count + $processing_count + $shipped_count + $delivered_count;

// Get total spent
$total_spent = $conn->query("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE user_id = $user_id")->fetch_assoc()['total'];

// Get wishlist count
$wishlist_count = $conn->query("SELECT COUNT(*) as count FROM wishlist WHERE user_id = $user_id")->fetch_assoc()['count'];

// Get total reviews
$total_reviews = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE user_id = $user_id")->fetch_assoc()['count'];

// Get recent orders
$orders_result = $conn->query("
    SELECT o.*, COUNT(oi.id) as item_count 
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = $user_id
    GROUP BY o.id
    ORDER BY o.order_date DESC
    LIMIT 5
");

// Status badge function
function getStatusBadge($status) {
    $status = $status ?? 'pending';
    $colors = [
        'pending' => 'status-pending',
        'processing' => 'status-processing',
        'shipped' => 'status-shipped',
        'delivered' => 'status-completed',
        'cancelled' => 'status-cancelled'
    ];
    return $colors[$status] ?? 'status-pending';
}
?>

<div class="account-container">
    <!-- Sidebar -->
    <aside class="account-sidebar">
        <div class="sidebar-header">
            <h3>My Account</h3>
            <?php if ($is_admin): ?>
                <div style="margin-top: 8px;">
                    <span style="background: #dc3545; color: #fff; padding: 2px 12px; border-radius: 12px; font-size: 11px;">🔑 Admin</span>
                </div>
            <?php endif; ?>
        </div>
        <nav class="sidebar-nav">
            <a href="account.php" class="active">
                <span class="nav-icon">◆</span> Overview
            </a>
            <a href="orders.php">
                <span class="nav-icon">◈</span> My Orders
            </a>
            <a href="wishlist.php">
                <span class="nav-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </span> My Wishlist
                <?php if ($wishlist_count > 0): ?>
                    <span style="background: #dc3545; color: #fff; border-radius: 50%; padding: 0px 6px; font-size: 10px; margin-left: auto; min-width: 18px; text-align: center;">
                        <?php echo $wishlist_count; ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="profile.php">
                <span class="nav-icon">◇</span> Profile Settings
            </a>
            <?php if ($is_admin): ?>
                <a href="admin.php" style="border-left-color: #dc3545; color: #dc3545;">
                    <span class="nav-icon">⚙️</span> Admin Panel
                </a>
                <a href="add_product.php" style="border-left-color: #28a745; color: #28a745;">
                    <span class="nav-icon">➕</span> Add Product
                </a>
            <?php endif; ?>
            <a href="logout.php" class="logout-link">
                <span class="nav-icon">◉</span> Logout
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="account-main">
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🛍</div>
                <div class="stat-content">
                    <h4>Total Orders</h4>
                    <p class="stat-number"><?php echo $total_orders; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-content">
                    <h4>Pending</h4>
                    <p class="stat-number" style="color: #ffc107;"><?php echo $pending_count; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">❤️</div>
                <div class="stat-content">
                    <h4>Wishlist</h4>
                    <p class="stat-number"><?php echo $wishlist_count; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-content">
                    <h4>Reviews</h4>
                    <p class="stat-number"><?php echo $total_reviews; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">$</div>
                <div class="stat-content">
                    <h4>Total Spent</h4>
                    <p class="stat-number"><?php echo number_format($total_spent, 2); ?> <span class="currency">BD</span></p>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="orders-section">
            <div class="section-header">
                <h3>Recent Orders</h3>
                <a href="orders.php" class="view-all">View All →</a>
            </div>
            
            <div class="orders-table-wrapper">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Items</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                            <?php while($order = $orders_result->fetch_assoc()): 
                                $status = $order['status'] ?? 'pending';
                                $status_labels = [
                                    'pending' => '📦 Pending',
                                    'processing' => '⚙️ Processing',
                                    'shipped' => '🚚 Shipped',
                                    'delivered' => '✅ Delivered',
                                    'cancelled' => '❌ Cancelled'
                                ];
                            ?>
                                <tr>
                                    <td class="order-id">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td class="order-total"><?php echo number_format($order['total_price'], 2); ?> BD</td>
                                    <td>
                                        <span class="status-badge <?php echo getStatusBadge($status); ?>">
                                            <?php echo $status_labels[$status] ?? ucfirst($status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $order['item_count']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <div class="empty-state-content">
                                        <div class="empty-icon-line"></div>
                                        <p>No orders yet</p>
                                        <small>Start shopping to see your orders here</small>
                                        <a href="catalog.php" class="shop-now-btn">Shop Now</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include('footer.php'); ?>