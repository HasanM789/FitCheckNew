<?php
// Start session and include db_config FIRST
require_once('db_config.php');

// Check if user is logged in - MUST be before ANY HTML output
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

// Check if cart is empty - MUST be before ANY HTML output
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$session_id = $_SESSION['cart_session_id'] ?? session_id();

// Process checkout - MUST be before ANY HTML output
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $user_id = $_SESSION['user_id'];
    $total = 0;
    $cart_items = [];
    $stock_error = false;
    $error_message = '';
    
    // Get cart items with sizes and check stock
    foreach ($_SESSION['cart'] as $key => $quantity) {
        $parts = explode('_', $key);
        $product_id = (int)$parts[0];
        $selected_size = $parts[1] ?? 'M';
        
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        
        if ($item) {
            // Check stock before ordering
            $stock = $item['stock'] ?? 0;
            if ($quantity > $stock) {
                $stock_error = true;
                $error_message = "❌ Not enough stock for " . $item['name'] . ". Available: " . $stock;
                break;
            }
            $item['quantity'] = $quantity;
            $item['selected_size'] = $selected_size;
            $item['subtotal'] = $item['price'] * $quantity;
            $total += $item['subtotal'];
            $cart_items[] = $item;
        }
    }
    
    // Create order if no error
    if (!$stock_error) {
        $conn->begin_transaction();
        try {
            // Insert order with status 'pending'
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, status) VALUES (?, ?, 'pending')");
            $stmt->bind_param("id", $user_id, $total);
            $stmt->execute();
            $order_id = $conn->insert_id;
            
            // Add order items with size
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, selected_size, selected_color) VALUES (?, ?, ?, ?, ?)");
            $default_color = 'Black';
            
            // Update stock for each item
            $stock_stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            
            foreach ($cart_items as $item) {
                // Add order item
                $stmt->bind_param("iiiss", $order_id, $item['id'], $item['quantity'], $item['selected_size'], $default_color);
                $stmt->execute();
                
                // Update stock
                $stock_stmt->bind_param("ii", $item['quantity'], $item['id']);
                $stock_stmt->execute();
            }
            
            $conn->commit();
            
            // Clear cart
            $_SESSION['cart'] = [];
            
            header("Location: account.php?order=success");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Order failed: " . $e->getMessage();
        }
    }
}

// NOW include header AFTER all redirects are handled
include('header.php');

// Get cart items for display
$cart_items = [];
$total = 0;
foreach ($_SESSION['cart'] as $key => $quantity) {
    $parts = explode('_', $key);
    $product_id = (int)$parts[0];
    $selected_size = $parts[1] ?? 'M';
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    
    if ($item) {
        $item['quantity'] = $quantity;
        $item['selected_size'] = $selected_size;
        $item['subtotal'] = $item['price'] * $quantity;
        $total += $item['subtotal'];
        $cart_items[] = $item;
    }
}
?>

<div class="checkout-container">
    <div class="checkout-header">
        <h1>Checkout</h1>
        <p class="checkout-subtitle">Review your order and confirm to complete your purchase</p>
    </div>

    <?php if (isset($error_message) && !empty($error_message)): ?>
        <div class="error-message" style="background: rgba(220, 53, 69, 0.1); border: 1px solid #dc3545; padding: 15px; border-radius: 4px; color: #dc3545; margin-bottom: 20px;">
            <span class="error-icon">⚠</span>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="checkout-grid">
        <div class="order-summary-card">
            <div class="card-header">
                <h2>Order Summary</h2>
                <span class="item-count"><?php echo count($cart_items); ?> items</span>
            </div>
            
            <div class="order-items-list">
                <?php foreach ($cart_items as $item): ?>
                    <div class="order-item">
                        <div class="order-item-info">
                            <div class="item-details">
                                <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                <span class="item-quantity">× <?php echo $item['quantity']; ?></span>
                                <span style="color: #94a3b8; font-size: 12px; margin-left: 5px;">Size: <?php echo htmlspecialchars($item['selected_size']); ?></span>
                            </div>
                            <span class="item-price"><?php echo number_format($item['subtotal'], 2); ?> BD</span>
                        </div>
                        <div style="font-size: 12px; color: #94a3b8;">
                            Stock: <?php echo $item['stock'] ?? 0; ?> available
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="order-divider"></div>

            <div class="order-totals">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span><?php echo number_format($total, 2); ?> BD</span>
                </div>
                <div class="total-row shipping-row">
                    <span>Shipping</span>
                    <span class="shipping-free">Free</span>
                </div>
                <div class="total-divider"></div>
                <div class="total-row grand-total">
                    <span><strong>Total</strong></span>
                    <span class="grand-total-amount"><strong><?php echo number_format($total, 2); ?> BD</strong></span>
                </div>
            </div>
        </div>

        <div class="confirm-order-card">
            <div class="card-header">
                <h2>Confirm Order</h2>
            </div>
            
            <div class="customer-info-section">
                <div class="info-group">
                    <span class="info-label">Customer</span>
                    <span class="info-value"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Items</span>
                    <span class="info-value"><?php echo count($cart_items); ?> product(s)</span>
                </div>
                <div class="info-group highlight-group">
                    <span class="info-label">Total Amount</span>
                    <span class="info-value highlight"><?php echo number_format($total, 2); ?> BD</span>
                </div>
            </div>

            <div class="order-actions">
                <form method="POST" class="place-order-form">
                    <button type="submit" name="place_order" class="place-order-btn">Place Order</button>
                </form>
                <a href="cart.php" class="back-to-cart">← Back to Cart</a>
            </div>

            <div class="order-note">
                <p>By placing your order, you agree to our <a href="#" class="terms-link">Terms & Conditions</a>.</p>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>