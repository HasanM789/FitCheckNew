<?php 
// Start session and include db_config FIRST
require_once('db_config.php'); 

$session_id = $_SESSION['cart_session_id'] ?? session_id();

// Handle remove item - MUST be before ANY HTML output
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    foreach ($_SESSION['cart'] as $key => $quantity) {
        $parts = explode('_', $key);
        $product_id = (int)$parts[0];
        if ($product_id === $remove_id) {
            unset($_SESSION['cart'][$key]);
            break;
        }
    }
    header("Location: cart.php");
    exit();
}

// Handle increment - MUST be before ANY HTML output
if (isset($_GET['increment']) && is_numeric($_GET['increment'])) {
    $product_id = (int)$_GET['increment'];
    foreach ($_SESSION['cart'] as $key => $quantity) {
        $parts = explode('_', $key);
        $pid = (int)$parts[0];
        if ($pid === $product_id) {
            $_SESSION['cart'][$key]++;
            break;
        }
    }
    header("Location: cart.php");
    exit();
}

// Handle decrement - MUST be before ANY HTML output
if (isset($_GET['decrement']) && is_numeric($_GET['decrement'])) {
    $product_id = (int)$_GET['decrement'];
    foreach ($_SESSION['cart'] as $key => $quantity) {
        $parts = explode('_', $key);
        $pid = (int)$parts[0];
        if ($pid === $product_id) {
            $_SESSION['cart'][$key]--;
            if ($_SESSION['cart'][$key] <= 0) {
                unset($_SESSION['cart'][$key]);
            }
            break;
        }
    }
    header("Location: cart.php");
    exit();
}

// Clear cart - MUST be before ANY HTML output
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    header("Location: cart.php");
    exit();
}

// NOW include header AFTER all redirects are handled
include('header.php');

// Calculate total and get cart items
$total = 0;
$cart_items = [];
$cart_count = 0;

if (!empty($_SESSION['cart'])) {
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
            $cart_count += $quantity;
            $cart_items[] = $item;
        }
    }
}
?>

<div class="cart-page">
    <div class="cart-header">
        <h1>Shopping Cart</h1>
        <span class="cart-count"><?php echo $cart_count; ?> items</span>
    </div>

    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <div class="empty-cart-icon">🛒</div>
            <h2>Your cart is empty</h2>
            <p>Looks like you haven't added any items to your cart yet.</p>
            <a href="catalog.php" class="continue-shopping-btn">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="cart-content">
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <div class="cart-item-image">
                            <?php 
                            $image_path = !empty($item['image_url']) ? $item['image_url'] : '';
                            if (!empty($image_path) && filter_var($image_path, FILTER_VALIDATE_URL)): ?>
                                <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width:80px;height:80px;object-fit:cover;border-radius:4px;background:#0f1115;">
                            <?php elseif (!empty($image_path) && file_exists($image_path)): ?>
                                <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width:80px;height:80px;object-fit:cover;border-radius:4px;background:#0f1115;">
                            <?php else: ?>
                                <div class="image-placeholder" style="width:80px;height:80px;display:flex;align-items:center;justify-content:center;background:#0f1115;border-radius:4px;font-size:24px;color:#dc3545;font-weight:bold;">
                                    <?php echo strtoupper(substr($item['name'], 0, 2)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="cart-item-details">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                            <p style="color: #94a3b8; font-size: 13px;">Size: <strong style="color: #fff;"><?php echo htmlspecialchars($item['selected_size']); ?></strong></p>
                            <span class="item-price"><?php echo number_format($item['price'], 2); ?> BD</span>
                        </div>
                        <div class="cart-item-actions">
                            <div class="quantity-control">
                                <a href="cart.php?decrement=<?php echo $item['id']; ?>" class="qty-btn">−</a>
                                <span class="qty-display"><?php echo $item['quantity']; ?></span>
                                <a href="cart.php?increment=<?php echo $item['id']; ?>" class="qty-btn">+</a>
                            </div>
                            <a href="cart.php?remove=<?php echo $item['id']; ?>" class="remove-btn">Remove</a>
                        </div>
                        <div class="cart-item-total">
                            <span class="subtotal-label">SUBTOTAL</span>
                            <span class="subtotal-amount"><?php echo number_format($item['subtotal'], 2); ?> BD</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary" style="background: var(--card-bg, #1a1c22); padding: 25px 30px; border-radius: 8px; border: 1px solid var(--border-color, #2d2d2d); height: fit-content; position: sticky; top: 120px;">
                <h3 style="color: var(--text-primary, #ffffff); font-size: 18px; font-weight: 600; margin: 0 0 20px 0; padding-bottom: 15px; border-bottom: 1px solid var(--border-color, #2d2d2d);">Order Summary</h3>
                
                <div style="display: flex; justify-content: space-between; padding: 10px 0; color: var(--text-secondary, #94a3b8); font-size: 14px; border-bottom: 1px solid var(--border-color, #2d2d2d);">
                    <span>Subtotal</span>
                    <span><?php echo number_format($total, 2); ?> BD</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; padding: 10px 0; color: var(--text-secondary, #94a3b8); font-size: 14px; border-bottom: 1px solid var(--border-color, #2d2d2d);">
                    <span>Shipping</span>
                    <span style="color: #28a745; font-weight: 500;">Free</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; padding: 15px 0; font-size: 18px; font-weight: 700; color: var(--text-primary, #ffffff);">
                    <span>Total</span>
                    <span style="color: #dc3545; font-size: 22px;"><?php echo number_format($total, 2); ?> BD</span>
                </div>
                
                <div style="margin-top: 25px; display: flex; flex-direction: column; gap: 12px;">
                    <a href="catalog.php" style="color: var(--text-secondary, #94a3b8); text-decoration: none; font-size: 13px; transition: all 0.3s ease; text-align: center;">← Continue Shopping</a>
                    
                    <a href="cart.php?clear=1" style="display: block; padding: 10px; text-align: center; color: #dc3545; text-decoration: none; border: 1px solid #dc3545; border-radius: 4px; font-size: 13px; font-weight: 600; transition: all 0.3s ease;" onclick="return confirm('Clear all items from cart?');">Clear Cart</a>
                    
                    <a href="checkout.php" style="display: block; padding: 16px; text-align: center; background: #dc3545; color: white; text-decoration: none; border-radius: 4px; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s ease;">Proceed to Checkout →</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include('footer.php'); ?>