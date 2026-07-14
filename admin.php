<?php
require_once('db_config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

// Check if user is admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || $user['is_admin'] != 1) {
    die("⛔ Access denied. Admin only. <a href='account.php'>Back to Account</a>");
}

// Handle delete
if (isset($_GET['delete_product']) && is_numeric($_GET['delete_product'])) {
    $id = (int)$_GET['delete_product'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin.php?deleted=1&tab=products");
    exit();
}

// Bulk delete products
if (isset($_POST['bulk_delete']) && isset($_POST['product_ids'])) {
    $ids = $_POST['product_ids'];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare("DELETE FROM products WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    header("Location: admin.php?bulk_deleted=1&tab=products");
    exit();
}

// Update order status
if (isset($_GET['update_order_status']) && isset($_GET['order_id']) && isset($_GET['status'])) {
    $order_id = (int)$_GET['order_id'];
    $status = $_GET['status'];
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();
    header("Location: admin.php?order_updated=1&tab=orders");
    exit();
}

// Delete user
if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
    $id = (int)$_GET['delete_user'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin.php?user_deleted=1&tab=users");
    exit();
}

// Make user admin / Remove admin
if (isset($_GET['toggle_admin']) && is_numeric($_GET['toggle_admin'])) {
    $id = (int)$_GET['toggle_admin'];
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $new_admin_status = $user_data['is_admin'] == 1 ? 0 : 1;
    $stmt = $conn->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_admin_status, $id);
    $stmt->execute();
    header("Location: admin.php?admin_toggled=1&tab=users");
    exit();
}

// Get data
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT COALESCE(SUM(total_price), 0) as total FROM orders")->fetch_assoc()['total'];

// Get order counts by status
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch_assoc()['count'];
$processing_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'processing'")->fetch_assoc()['count'];
$shipped_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'shipped'")->fetch_assoc()['count'];
$delivered_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'delivered'")->fetch_assoc()['count'];
$cancelled_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'")->fetch_assoc()['count'];
$total_orders = $pending_orders + $processing_orders + $shipped_orders + $delivered_orders + $cancelled_orders;

// Get recent orders
$recent_orders = $conn->query("
    SELECT o.*, u.username 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.order_date DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$products = $conn->query("SELECT * FROM products ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$users = $conn->query("SELECT id, username, email, is_admin, created_at FROM users ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$all_orders = $conn->query("
    SELECT o.*, u.username 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.order_date DESC
")->fetch_all(MYSQLI_ASSOC);

$daily_revenue = $conn->query("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE DATE(order_date) = CURDATE()")->fetch_assoc()['total'];
$weekly_revenue = $conn->query("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE WEEK(order_date) = WEEK(CURDATE())")->fetch_assoc()['total'];
$monthly_revenue = $conn->query("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE MONTH(order_date) = MONTH(CURDATE())")->fetch_assoc()['total'];

// Get total reviews
$total_reviews = $conn->query("SELECT COUNT(*) as count FROM reviews")->fetch_assoc()['count'];
$avg_rating = $conn->query("SELECT AVG(rating) as avg FROM reviews")->fetch_assoc()['avg'];

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

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

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - FitCheck</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #0f1115; color: #fff; font-family: 'Inter', Arial, sans-serif; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .header h1 { font-size: 28px; color: #dc3545; }
        .admin-badge { background: #dc3545; color: #fff; padding: 2px 14px; border-radius: 12px; font-size: 12px; margin-left: 10px; }
        
        .btn { background: #dc3545; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; border: none; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #b02a37; }
        .btn-secondary { background: transparent; border: 1px solid #333; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; cursor: pointer; font-size: 14px; }
        .btn-secondary:hover { border-color: #dc3545; color: #dc3545; }
        .btn-success { background: #28a745; color: #fff; padding: 8px 16px; text-decoration: none; border-radius: 4px; display: inline-block; border: none; cursor: pointer; font-size: 13px; }
        .btn-success:hover { background: #1e7e34; }
        .btn-danger { background: #dc3545; color: #fff; padding: 8px 16px; text-decoration: none; border-radius: 4px; display: inline-block; border: none; cursor: pointer; font-size: 13px; }
        .btn-danger:hover { background: #b02a37; }
        .btn-sm { padding: 5px 12px; font-size: 12px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: #1a1c22; padding: 18px 20px; border-radius: 8px; border: 1px solid #2d2d2d; }
        .stat-card .number { font-size: 26px; font-weight: 700; color: #dc3545; }
        .stat-card .label { color: #94a3b8; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
        .stat-card .icon { font-size: 18px; margin-right: 8px; }
        .stat-card .sub-text { font-size: 12px; color: #94a3b8; margin-top: 2px; }
        
        .tabs { display: flex; gap: 5px; margin-bottom: 25px; flex-wrap: wrap; background: #1a1c22; padding: 8px; border-radius: 8px; border: 1px solid #2d2d2d; }
        .tab { padding: 10px 20px; color: #94a3b8; text-decoration: none; border-radius: 4px; font-size: 14px; }
        .tab:hover { background: #0f1115; color: #fff; }
        .tab.active { background: #dc3545; color: #fff; }
        
        .table-wrapper { overflow-x: auto; background: #1a1c22; border-radius: 8px; border: 1px solid #2d2d2d; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0f1115; padding: 12px 15px; text-align: left; color: #94a3b8; border-bottom: 1px solid #2d2d2d; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 12px 15px; border-bottom: 1px solid #2d2d2d; vertical-align: middle; }
        tr:hover { background: #0f1115; }
        
        .status-badge { padding: 3px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
        .status-pending { background: rgba(255, 193, 7, 0.15); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.2); }
        .status-processing { background: rgba(23, 162, 184, 0.15); color: #17a2b8; border: 1px solid rgba(23, 162, 184, 0.2); }
        .status-shipped { background: rgba(40, 167, 69, 0.15); color: #28a745; border: 1px solid rgba(40, 167, 69, 0.2); }
        .status-completed { background: rgba(40, 167, 69, 0.15); color: #28a745; border: 1px solid rgba(40, 167, 69, 0.2); }
        .status-cancelled { background: rgba(220, 53, 69, 0.15); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.2); }
        
        .product-image { width: 50px; height: 50px; object-fit: contain; border-radius: 4px; background: #0f1115; }
        .image-placeholder { width: 50px; height: 50px; background: #0f1115; display: flex; align-items: center; justify-content: center; border-radius: 4px; font-size: 18px; color: #dc3545; font-weight: bold; }
        
        .delete-btn { color: #dc3545; text-decoration: none; padding: 4px 10px; border: 1px solid #dc3545; border-radius: 4px; font-size: 11px; display: inline-block; }
        .delete-btn:hover { background: #dc3545; color: #fff; }
        .edit-btn { color: #28a745; text-decoration: none; padding: 4px 10px; border: 1px solid #28a745; border-radius: 4px; font-size: 11px; display: inline-block; }
        .edit-btn:hover { background: #28a745; color: #fff; }
        .admin-btn { color: #ffc107; text-decoration: none; padding: 4px 10px; border: 1px solid #ffc107; border-radius: 4px; font-size: 11px; display: inline-block; }
        .admin-btn:hover { background: #ffc107; color: #000; }
        
        .success { background: rgba(40, 167, 69, 0.1); padding: 12px 18px; border-radius: 4px; margin-bottom: 15px; color: #28a745; border: 1px solid #28a745; }
        .empty { text-align: center; color: #94a3b8; padding: 40px 20px; }
        .empty .icon { font-size: 36px; margin-bottom: 10px; opacity: 0.5; }
        
        .footer-actions { display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; flex-wrap: wrap; gap: 10px; }
        .footer-actions .info { color: #94a3b8; font-size: 13px; }
        .bulk-actions { display: flex; gap: 10px; align-items: center; }
        .bulk-actions input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; accent-color: #dc3545; }
        
        .section-title { font-size: 20px; font-weight: 600; margin: 20px 0 10px 0; color: #fff; }
        .section-title .count { color: #94a3b8; font-size: 14px; font-weight: 400; }
        .revenue-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 25px; }
        .revenue-card { background: #1a1c22; padding: 15px 20px; border-radius: 8px; border: 1px solid #2d2d2d; text-align: center; }
        .revenue-card .amount { font-size: 24px; font-weight: 700; color: #28a745; }
        .revenue-card .label { color: #94a3b8; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
        
        .stock-low { color: #ffc107; }
        .stock-out { color: #dc3545; }
        .stock-high { color: #28a745; }
        
        @media (max-width: 768px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 480px) { .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛍️ Admin Dashboard <span class="admin-badge">ADMIN</span></h1>
            <div>
                <a href="add_product.php" class="btn">➕ Add Product</a>
                <a href="catalog.php" class="btn-secondary">← Catalog</a>
                <a href="account.php" class="btn-secondary">Account</a>
                <a href="logout.php" class="btn-secondary" style="color: #dc3545; border-color: #dc3545;">Logout</a>
            </div>
        </div>

        <!-- Success Messages -->
        <?php if (isset($_GET['deleted']) || isset($_GET['bulk_deleted'])): ?>
            <div class="success">✅ <?php echo isset($_GET['bulk_deleted']) ? 'Selected products' : 'Product'; ?> deleted successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['order_updated'])): ?>
            <div class="success">✅ Order status updated successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['user_deleted'])): ?>
            <div class="success">✅ User deleted successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['admin_toggled'])): ?>
            <div class="success">✅ Admin status updated successfully!</div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="tabs">
            <a href="?tab=dashboard" class="tab <?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>">📊 Dashboard</a>
            <a href="?tab=products" class="tab <?php echo $active_tab == 'products' ? 'active' : ''; ?>">📦 Products</a>
            <a href="?tab=orders" class="tab <?php echo $active_tab == 'orders' ? 'active' : ''; ?>">🛒 Orders</a>
            <a href="?tab=users" class="tab <?php echo $active_tab == 'users' ? 'active' : ''; ?>">👤 Users</a>
            <a href="?tab=reports" class="tab <?php echo $active_tab == 'reports' ? 'active' : ''; ?>">📈 Reports</a>
        </div>

        <!-- ============================================ -->
        <!-- TAB 1: DASHBOARD -->
        <!-- ============================================ -->
        <?php if ($active_tab == 'dashboard'): ?>

        <div class="stats-grid">
            <div class="stat-card"><div class="number"><span class="icon">📦</span><?php echo $total_products; ?></div><div class="label">Total Products</div></div>
            <div class="stat-card"><div class="number"><span class="icon">🛒</span><?php echo $total_orders; ?></div><div class="label">Total Orders</div></div>
            <div class="stat-card"><div class="number"><span class="icon">👤</span><?php echo $total_users; ?></div><div class="label">Total Users</div></div>
            <div class="stat-card"><div class="number"><span class="icon">💰</span><?php echo number_format($total_revenue, 2); ?> BD</div><div class="label">Total Revenue</div></div>
            <div class="stat-card"><div class="number"><span class="icon">⭐</span><?php echo $total_reviews; ?></div><div class="label">Total Reviews</div></div>
            <div class="stat-card" style="border-color: <?php echo $pending_orders > 0 ? '#ffc107' : '#28a745'; ?>">
                <div class="number"><span class="icon">⏳</span><?php echo $pending_orders; ?></div>
                <div class="label">Pending Orders</div>
                <?php if ($processing_orders > 0): ?>
                    <div class="sub-text">⚙️ <?php echo $processing_orders; ?> processing</div>
                <?php endif; ?>
                <?php if ($shipped_orders > 0): ?>
                    <div class="sub-text">🚚 <?php echo $shipped_orders; ?> shipped</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section-title">💰 Revenue Breakdown</div>
        <div class="revenue-grid">
            <div class="revenue-card"><div class="amount"><?php echo number_format($daily_revenue, 2); ?> BD</div><div class="label">Today</div></div>
            <div class="revenue-card"><div class="amount"><?php echo number_format($weekly_revenue, 2); ?> BD</div><div class="label">This Week</div></div>
            <div class="revenue-card"><div class="amount"><?php echo number_format($monthly_revenue, 2); ?> BD</div><div class="label">This Month</div></div>
        </div>

        <div class="section-title">📋 Recent Orders <span class="count">(Last 5)</span></div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (count($recent_orders) > 0): ?>
                        <?php foreach ($recent_orders as $order): 
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
                                <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td><?php echo number_format($order['total_price'], 2); ?> BD</td>
                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo getStatusBadge($status); ?>">
                                        <?php echo $status_labels[$status] ?? ucfirst($status); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5"><div class="empty">No orders yet</div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php endif; ?>

        <!-- ============================================ -->
        <!-- TAB 2: PRODUCTS -->
        <!-- ============================================ -->
        <?php if ($active_tab == 'products'): ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
            <div class="section-title" style="margin: 0;">📦 All Products <span class="count">(<?php echo $total_products; ?>)</span></div>
            <a href="add_product.php" class="btn">➕ Add New Product</a>
        </div>

        <div class="table-wrapper">
            <form method="POST" id="bulkForm">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 35px;"><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
                            <th>Image</th>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Sizes</th>
                            <th>Stock</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) > 0): ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><input type="checkbox" name="product_ids[]" value="<?php echo $product['id']; ?>" class="product-checkbox"></td>
                                    <td>
                                        <?php 
                                        $image_path = !empty($product['image_url']) ? $product['image_url'] : '';
                                        if (!empty($image_path) && filter_var($image_path, FILTER_VALIDATE_URL)): ?>
                                            <img src="<?php echo htmlspecialchars($image_path); ?>" class="product-image">
                                        <?php elseif (!empty($image_path) && file_exists($image_path)): ?>
                                            <img src="<?php echo htmlspecialchars($image_path); ?>" class="product-image">
                                        <?php else: ?>
                                            <div class="image-placeholder"><?php echo strtoupper(substr($product['name'], 0, 2)); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>#<?php echo $product['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                    <td style="color: #dc3545; font-weight: 600;"><?php echo number_format($product['price'], 2); ?> BD</td>
                                    <td><span style="background: #0f1115; padding: 2px 10px; border-radius: 12px; font-size: 12px;"><?php echo $product['category']; ?></span></td>
                                    <td>
                                        <?php 
                                        $sizes = !empty($product['sizes']) ? explode(',', $product['sizes']) : ['S', 'M', 'L', 'XL'];
                                        $size_type = $product['size_type'] ?? 'clothing';
                                        foreach ($sizes as $s): 
                                            $display = ($size_type === 'shoes') ? 'EU ' . trim($s) : trim($s);
                                        ?>
                                            <span style="background:#0f1115;padding:1px 10px;border-radius:4px;font-size:11px;margin:2px;display:inline-block;border:1px solid #2d2d2d;">
                                                <?php echo $display; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $stock = $product['stock'] ?? 0;
                                        if ($stock > 10): ?>
                                            <span class="stock-high"><?php echo $stock; ?></span>
                                        <?php elseif ($stock > 0 && $stock <= 10): ?>
                                            <span class="stock-low"><?php echo $stock; ?></span>
                                        <?php else: ?>
                                            <span class="stock-out">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="edit-btn">✏️</a>
                                        <a href="?delete_product=<?php echo $product['id']; ?>&tab=products" class="delete-btn" onclick="return confirm('Delete this product?')">🗑️</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9"><div class="empty"><div class="icon">📭</div><p>No products found</p></div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="footer-actions">
                    <div class="info">Showing <?php echo count($products); ?> products</div>
                    <div class="bulk-actions">
                        <button type="submit" name="bulk_delete" class="btn-danger btn-sm" onclick="return confirm('Delete selected products?')">🗑️ Delete Selected</button>
                    </div>
                </div>
            </form>
        </div>

        <?php endif; ?>

        <!-- ============================================ -->
        <!-- TAB 3: ORDERS -->
        <!-- ============================================ -->
        <?php if ($active_tab == 'orders'): ?>

        <div class="section-title">🛒 All Orders <span class="count">(<?php echo $total_orders; ?>)</span></div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($all_orders) > 0): ?>
                        <?php foreach ($all_orders as $order): 
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
                                <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td><?php echo number_format($order['total_price'], 2); ?> BD</td>
                                <td><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo getStatusBadge($status); ?>">
                                        <?php echo $status_labels[$status] ?? ucfirst($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($status == 'cancelled'): ?>
                                        <span style="color: #94a3b8; font-size: 12px;">Cancelled</span>
                                    <?php else: ?>
                                        <form method="GET" style="display: inline-block; margin: 0;">
                                            <input type="hidden" name="tab" value="orders">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="update_order_status" value="1">
                                            <select name="status" onchange="this.form.submit()" style="background: #0f1115; color: #fff; border: 1px solid #2d2d2d; padding: 4px 8px; border-radius: 4px; font-size: 12px; cursor: pointer;">
                                                <option value="">-- Change Status --</option>
                                                <option value="pending" <?php if($status == 'pending') echo 'selected'; ?>>📦 Pending</option>
                                                <option value="processing" <?php if($status == 'processing') echo 'selected'; ?>>⚙️ Processing</option>
                                                <option value="shipped" <?php if($status == 'shipped') echo 'selected'; ?>>🚚 Shipped</option>
                                                <option value="delivered" <?php if($status == 'delivered') echo 'selected'; ?>>✅ Delivered</option>
                                                <option value="cancelled" <?php if($status == 'cancelled') echo 'selected'; ?>>❌ Cancelled</option>
                                            </select>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6"><div class="empty">No orders yet</div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php endif; ?>

        <!-- ============================================ -->
        <!-- TAB 4: USERS -->
        <!-- ============================================ -->
        <?php if ($active_tab == 'users'): ?>

        <div class="section-title">👤 All Users <span class="count">(<?php echo $total_users; ?>)</span></div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>#<?php echo $user['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['is_admin'] == 1): ?>
                                        <span class="status-badge status-completed">Admin</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">User</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="?toggle_admin=<?php echo $user['id']; ?>&tab=users" class="admin-btn">
                                            <?php echo $user['is_admin'] == 1 ? 'Remove Admin' : 'Make Admin'; ?>
                                        </a>
                                        <a href="?delete_user=<?php echo $user['id']; ?>&tab=users" class="delete-btn" onclick="return confirm('Delete this user?')">🗑️</a>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 12px;">(You)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6"><div class="empty">No users found</div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php endif; ?>

        <!-- ============================================ -->
        <!-- TAB 5: REPORTS -->
        <!-- ============================================ -->
        <?php if ($active_tab == 'reports'): ?>

        <div class="section-title">📈 Sales Reports</div>

        <div class="revenue-grid">
            <div class="revenue-card"><div class="amount"><?php echo number_format($daily_revenue, 2); ?> BD</div><div class="label">Today's Revenue</div></div>
            <div class="revenue-card"><div class="amount"><?php echo number_format($weekly_revenue, 2); ?> BD</div><div class="label">This Week's Revenue</div></div>
            <div class="revenue-card"><div class="amount"><?php echo number_format($monthly_revenue, 2); ?> BD</div><div class="label">This Month's Revenue</div></div>
        </div>

        <div class="section-title">📊 Order Statistics</div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Metric</th><th>Count</th></tr></thead>
                <tbody>
                    <tr><td>Total Orders</td><td><strong><?php echo $total_orders; ?></strong></td></tr>
                    <tr><td>Total Users</td><td><strong><?php echo $total_users; ?></strong></td></tr>
                    <tr><td>Total Revenue</td><td><strong><?php echo number_format($total_revenue, 2); ?> BD</strong></td></tr>
                    <tr><td>Average Order Value</td><td><strong><?php echo $total_orders > 0 ? number_format($total_revenue / $total_orders, 2) : '0.00'; ?> BD</strong></td></tr>
                    <tr><td>Total Products</td><td><strong><?php echo $total_products; ?></strong></td></tr>
                    <tr><td>Total Reviews</td><td><strong><?php echo $total_reviews; ?></strong></td></tr>
                    <tr><td>Average Rating</td><td><strong><?php echo $avg_rating ? number_format($avg_rating, 1) : '0.0'; ?> ★</strong></td></tr>
                    <tr><td>Pending Orders</td><td><strong><?php echo $pending_orders; ?></strong></td></tr>
                    <tr><td>Processing Orders</td><td><strong><?php echo $processing_orders; ?></strong></td></tr>
                    <tr><td>Shipped Orders</td><td><strong><?php echo $shipped_orders; ?></strong></td></tr>
                    <tr><td>Delivered Orders</td><td><strong><?php echo $delivered_orders; ?></strong></td></tr>
                    <tr><td>Cancelled Orders</td><td><strong><?php echo $cancelled_orders; ?></strong></td></tr>
                </tbody>
            </table>
        </div>

        <?php endif; ?>

    </div>

    <script>
        function toggleAll(source) {
            var checkboxes = document.querySelectorAll('.product-checkbox');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
    </script>
</body>
</html>