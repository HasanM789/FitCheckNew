<?php
require_once('db_config.php');
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}
include('header.php');

$user_id = $_SESSION['user_id'];

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id == 0) {
    header("Location: orders.php");
    exit();
}

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, u.username 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo "<p style='text-align:center;padding:40px;color:#dc3545;'>Order not found.</p>";
    include('footer.php');
    exit();
}

// Get order items
$stmt = $conn->prepare("
    SELECT oi.*, p.name, p.price 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order_items = $result->fetch_all(MYSQLI_ASSOC);

// Get user details
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Status definitions
$statuses = [
    'pending' => ['label' => 'Order Placed', 'icon' => '📦', 'color' => '#ffc107'],
    'processing' => ['label' => 'Processing', 'icon' => '⚙️', 'color' => '#17a2b8'],
    'shipped' => ['label' => 'Shipped', 'icon' => '🚚', 'color' => '#28a745'],
    'delivered' => ['label' => 'Delivered', 'icon' => '✅', 'color' => '#28a745'],
    'cancelled' => ['label' => 'Cancelled', 'icon' => '❌', 'color' => '#dc3545']
];

$current_status = $order['status'] ?? 'pending';
$status_keys = array_keys($statuses);
$current_index = array_search($current_status, $status_keys);
$order_date = date('M d, Y', strtotime($order['order_date']));

// Calculate estimated delivery (3 days after order)
$estimated_delivery = date('M d, Y', strtotime($order['order_date'] . ' +3 days'));

// Total items
$total_items = array_sum(array_column($order_items, 'quantity'));

// Progress steps
$progress_steps = [
    'pending' => 'Order Placed',
    'processing' => 'Processing', 
    'shipped' => 'Shipped',
    'delivered' => 'Delivered'
];
?>

<div style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
    
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="color: #fff; font-size: 26px; margin: 0;">📦 Track Order</h1>
            <p style="color: #94a3b8; margin-top: 4px; font-size: 14px;">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?> • <?php echo count($order_items); ?> items • <?php echo $total_items; ?> pieces</p>
        </div>
        <a href="orders.php" style="color: #94a3b8; text-decoration: none; padding: 8px 20px; border: 1px solid #2d2d2d; border-radius: 4px; font-size: 13px;">← Back to Orders</a>
    </div>

    <!-- Top Stats Row -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px;">
        <div style="background: #1a1c22; border-radius: 8px; border: 1px solid #2d2d2d; padding: 16px 20px;">
            <p style="color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin: 0;">Status</p>
            <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                <span style="font-size: 20px;"><?php echo $statuses[$current_status]['icon'] ?? '📦'; ?></span>
                <span style="color: #fff; font-weight: 600; font-size: 16px;"><?php echo $statuses[$current_status]['label'] ?? ucfirst($current_status); ?></span>
            </div>
        </div>
        <div style="background: #1a1c22; border-radius: 8px; border: 1px solid #2d2d2d; padding: 16px 20px;">
            <p style="color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin: 0;">Order Date</p>
            <p style="color: #fff; font-weight: 600; font-size: 16px; margin-top: 4px;"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></p>
        </div>
        <div style="background: #1a1c22; border-radius: 8px; border: 1px solid #2d2d2d; padding: 16px 20px;">
            <p style="color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin: 0;">Total Amount</p>
            <p style="color: #dc3545; font-weight: 700; font-size: 20px; margin-top: 4px;"><?php echo number_format($order['total_price'], 2); ?> BD</p>
        </div>
        <div style="background: #1a1c22; border-radius: 8px; border: 1px solid #2d2d2d; padding: 16px 20px;">
            <p style="color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin: 0;">Est. Delivery</p>
            <p style="color: #28a745; font-weight: 600; font-size: 16px; margin-top: 4px;"><?php echo $estimated_delivery; ?></p>
        </div>
    </div>

    <!-- HORIZONTAL TIMELINE -->
    <div style="background: #1a1c22; border-radius: 8px; border: 1px solid #2d2d2d; padding: 30px 40px; margin-bottom: 30px;">
        <h3 style="color: #fff; font-size: 16px; margin: 0 0 30px 0; font-weight: 500;">📍 Shipment Progress</h3>
        
        <div style="display: flex; justify-content: space-between; align-items: flex-start; position: relative; padding: 0 10px;">
            
            <!-- Progress Line -->
            <div style="position: absolute; top: 18px; left: 40px; right: 40px; height: 3px; background: #2d2d2d; z-index: 0;">
                <?php 
                $step_count = 0;
                $total_steps = count($progress_steps);
                $progress_percentage = ($current_index / ($total_steps - 1)) * 100;
                ?>
                <div style="height: 100%; width: <?php echo $progress_percentage; ?>%; background: #28a745; transition: width 0.5s ease;"></div>
            </div>
            
            <?php foreach ($progress_steps as $key => $label):
                $is_completed = $current_index >= array_search($key, $status_keys);
                $is_current = $key == $current_status;
            ?>
                <div style="display: flex; flex-direction: column; align-items: center; position: relative; z-index: 1; flex: 1; text-align: center;">
                    
                    <!-- Circle -->
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: <?php echo $is_completed ? '#28a745' : '#2d2d2d'; ?>; display: flex; align-items: center; justify-content: center; border: 3px solid <?php echo $is_current ? '#28a745' : 'transparent'; ?>; box-shadow: <?php echo $is_current ? '0 0 25px rgba(40,167,69,0.5)' : 'none'; ?>; transition: all 0.3s ease;">
                        <?php if ($is_completed): ?>
                            <span style="color: #fff; font-size: 16px;">✓</span>
                        <?php else: ?>
                            <span style="color: #666; font-size: 14px;">●</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Label -->
                    <span style="color: <?php echo $is_completed ? '#fff' : '#666'; ?>; font-weight: <?php echo $is_completed ? '600' : '400'; ?>; font-size: 14px; margin-top: 10px;">
                        <?php echo $label; ?>
                    </span>
                    
                    <!-- Status -->
                    <span style="color: <?php echo $is_completed ? '#28a745' : '#666'; ?>; font-size: 11px; margin-top: 3px;">
                        <?php if ($is_current && $current_status != 'delivered' && $current_status != 'cancelled'): ?>
                            <span style="background: #28a745; color: #fff; padding: 0px 10px; border-radius: 10px; font-size: 9px;">In Progress</span>
                        <?php elseif ($is_completed): ?>
                            ✓ Done
                        <?php else: ?>
                            Pending
                        <?php endif; ?>
                    </span>
                    
                    <!-- Date -->
                    <?php if ($is_completed): ?>
                        <span style="color: #666; font-size: 11px; margin-top: 2px;"><?php echo $order_date; ?></span>
                    <?php endif; ?>
                    
                </div>
            <?php endforeach; ?>
            
        </div>
        
        <!-- Current Status Indicator -->
        <div style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #2d2d2d; text-align: center;">
            <p style="color: #94a3b8; font-size: 13px; margin: 0;">
                Current Status: <span style="color: #fff; font-weight: 600;"><?php echo $statuses[$current_status]['label'] ?? ucfirst($current_status); ?></span>
                <span style="color: #28a745; margin-left: 10px;">●</span>
            </p>
        </div>
    </div>

    <!-- Bottom Section - Two Column -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        
        <!-- LEFT COLUMN: Order Items -->
        <div style="background: #1a1c22; border-radius: 8px; border: 1px solid #2d2d2d; padding: 22px 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="color: #fff; font-size: 15px; margin: 0;">🛍️ Order Items</h3>
                <span style="color: #94a3b8; font-size: 13px;"><?php echo count($order_items); ?> items</span>
            </div>
            <?php foreach ($order_items as $item): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #2d2d2d;">
                    <div>
                        <span style="color: #fff; font-size: 14px;"><?php echo htmlspecialchars($item['name']); ?></span>
                        <span style="color: #94a3b8; font-size: 12px; margin-left: 8px;">(<?php echo htmlspecialchars($item['selected_size']); ?>)</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="color: #94a3b8; font-size: 13px;">× <?php echo $item['quantity']; ?></span>
                        <span style="color: #94a3b8; font-size: 13px;"><?php echo number_format($item['price'], 2); ?> BD</span>
                    </div>
                </div>
            <?php endforeach; ?>
            <div style="display: flex; justify-content: space-between; padding-top: 12px; border-top: 1px solid #2d2d2d; margin-top: 4px;">
                <span style="color: #fff; font-weight: 600;">Total</span>
                <span style="color: #dc3545; font-weight: 700; font-size: 18px;"><?php echo number_format($order['total_price'], 2); ?> BD</span>
            </div>
        </div>

        <!-- RIGHT COLUMN: Shipping & Delivery -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            
            <!-- Shipping Address -->
            <div style="background: #1a1c22; border-radius: 8px; border: 1px solid #2d2d2d; padding: 18px 24px;">
                <p style="color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 6px 0;">Shipping Address</p>
                <p style="color: #fff; margin: 0; font-size: 14px; line-height: 1.6;">
                    <?php echo htmlspecialchars($user_data['username'] ?? 'Customer'); ?><br>
                    <?php echo htmlspecialchars($user_data['email'] ?? ''); ?>
                </p>
            </div>

            <!-- Delivery Info -->
            <div style="background: #1a1c22; border-radius: 8px; border: 1px solid #2d2d2d; padding: 16px 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <p style="color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin: 0;">Delivery Method</p>
                        <p style="color: #fff; font-size: 14px; margin: 4px 0 0 0;">Standard Shipping</p>
                    </div>
                    <div style="text-align: right;">
                        <p style="color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin: 0;">Est. Delivery</p>
                        <p style="color: #28a745; font-weight: 600; font-size: 14px; margin: 4px 0 0 0;"><?php echo $estimated_delivery; ?></p>
                    </div>
                </div>
            </div>

            <!-- Track Button -->
            <button onclick="alert('Live tracking coming soon!')" style="background: #dc3545; color: #fff; border: none; padding: 14px; border-radius: 6px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; width: 100%;">
                📍 Track Live Shipment
            </button>

        </div>
    </div>
</div>

<?php include('footer.php'); ?>