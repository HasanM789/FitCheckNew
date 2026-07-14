<?php
require_once('db_config.php');

// Restrict access to logged-in users only
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}
include('header.php');

$user_id = $_SESSION['user_id'];

// Show success/error messages
if (isset($_GET['cancelled'])): ?>
    <div style="max-width: 1200px; margin: 20px auto 0; padding: 0 20px;">
        <div style="background: rgba(40, 167, 69, 0.1); border: 1px solid #28a745; padding: 15px 20px; border-radius: 6px; color: #28a745; display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            Order cancelled successfully!
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] == 'cant_cancel'): ?>
    <div style="max-width: 1200px; margin: 20px auto 0; padding: 0 20px;">
        <div style="background: rgba(220, 53, 69, 0.1); border: 1px solid #dc3545; padding: 15px 20px; border-radius: 6px; color: #dc3545; display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc3545" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            This order cannot be cancelled. Only pending orders can be cancelled.
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] == 'cancel_failed'): ?>
    <div style="max-width: 1200px; margin: 20px auto 0; padding: 0 20px;">
        <div style="background: rgba(220, 53, 69, 0.1); border: 1px solid #dc3545; padding: 15px 20px; border-radius: 6px; color: #dc3545; display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc3545" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            Failed to cancel order. Please try again.
        </div>
    </div>
<?php endif; ?>

<?php
// Fetch all orders for the logged-in user
$orders_result = $conn->query("
    SELECT o.*, COUNT(oi.id) as item_count 
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = $user_id
    GROUP BY o.id
    ORDER BY o.order_date DESC
");

// Status colors
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

// Get products from an order
function getOrderProducts($conn, $order_id) {
    $stmt = $conn->prepare("
        SELECT oi.product_id, p.name 
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Check if user already reviewed a product from this order
function hasReviewed($conn, $user_id, $product_id, $order_id) {
    $stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ? AND order_id = ?");
    $stmt->bind_param("iii", $user_id, $product_id, $order_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

// Get product count for an order
function getOrderProductCount($conn, $order_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'];
}
?>

<style>
/* Modern Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    letter-spacing: 0.3px;
}

.status-pending {
    background: rgba(255, 193, 7, 0.15);
    color: #f5a623;
    border: 1px solid rgba(255, 193, 7, 0.2);
}

.status-processing {
    background: rgba(23, 162, 184, 0.15);
    color: #17a2b8;
    border: 1px solid rgba(23, 162, 184, 0.2);
}

.status-shipped {
    background: rgba(40, 167, 69, 0.15);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.2);
}

.status-completed {
    background: rgba(40, 167, 69, 0.15);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.2);
}

.status-cancelled {
    background: rgba(220, 53, 69, 0.15);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.2);
}

/* Modern Buttons */
.btn-track {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    background: #28a745;
    color: #fff;
    text-decoration: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-track:hover {
    background: #1e7e34;
    transform: translateY(-1px);
}

.btn-cancel {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    background: transparent;
    color: #dc3545;
    text-decoration: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    border: 1px solid #dc3545;
    transition: all 0.3s ease;
    cursor: pointer;
}

.btn-cancel:hover {
    background: #dc3545;
    color: #fff;
}

.btn-review {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    background: #ffc107;
    color: #000;
    text-decoration: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-review:hover {
    background: #e0a800;
    transform: translateY(-1px);
}

.btn-disabled {
    color: #666;
    font-size: 12px;
}
</style>

<div class="account-container">
    <aside class="account-sidebar">
        <div class="sidebar-header"><h3>My Account</h3></div>
        <nav class="sidebar-nav">
            <a href="account.php"><span class="nav-icon">◆</span> Overview</a>
            <a href="orders.php" class="active"><span class="nav-icon">◈</span> My Orders</a>
            <a href="wishlist.php"><span class="nav-icon">❤️</span> My Wishlist</a>
            <a href="profile.php"><span class="nav-icon">◇</span> Profile Settings</a>
            <a href="logout.php" class="logout-link"><span class="nav-icon">◉</span> Logout</a>
        </nav>
    </aside>

    <main class="account-main">
        <div class="orders-section">
            <div class="section-header">
                <h3>Order History</h3>
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
                            <th>Review</th>
                            <th>Track</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                            <?php while($order = $orders_result->fetch_assoc()): 
                                $status = $order['status'] ?? 'pending';
                                $status_labels = [
                                    'pending' => 'Pending',
                                    'processing' => 'Processing',
                                    'shipped' => 'Shipped',
                                    'delivered' => 'Delivered',
                                    'cancelled' => 'Cancelled'
                                ];
                                $product_count = getOrderProductCount($conn, $order['id']);
                                $can_cancel = ($status == 'pending');
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
                                    <td>
                                        <?php if ($status == 'delivered' || $status == 'completed'): ?>
                                            <?php if ($product_count == 0): ?>
                                                <span class="btn-disabled">No items</span>
                                            <?php else: 
                                                $products = getOrderProducts($conn, $order['id']);
                                                $has_review = false;
                                                while ($product = $products->fetch_assoc()) {
                                                    if (hasReviewed($conn, $user_id, $product['product_id'], $order['id'])) {
                                                        $has_review = true;
                                                        break;
                                                    }
                                                }
                                            ?>
                                                <?php if ($has_review): ?>
                                                    <span style="color: #28a745; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <polyline points="20 6 9 17 4 12"/>
                                                        </svg>
                                                        Reviewed
                                                    </span>
                                                <?php else: ?>
                                                    <a href="write_review.php?order_id=<?php echo $order['id']; ?>" class="btn-review">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                                        </svg>
                                                        Review
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="btn-disabled">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="track_order.php?id=<?php echo $order['id']; ?>" class="btn-track">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                                            </svg>
                                            Track
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($can_cancel): ?>
                                            <a href="cancel_order.php?id=<?php echo $order['id']; ?>" 
                                               class="btn-cancel"
                                               onclick="return confirm('Are you sure you want to cancel this order?')">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="18" y1="6" x2="6" y2="18"/>
                                                    <line x1="6" y1="6" x2="18" y2="18"/>
                                                </svg>
                                                Cancel
                                            </a>
                                        <?php else: ?>
                                            <span class="btn-disabled">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <div class="empty-state-content">
                                        <div class="empty-icon-line"></div>
                                        <p>You haven't placed any orders yet</p>
                                        <small>Explore our high-quality essentials to make your first fit check!</small>
                                        <a href="catalog.php" class="shop-now-btn">Explore Catalog</a>
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