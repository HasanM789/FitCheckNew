<?php
// Start session and include db_config FIRST
require_once('db_config.php');

// Check if user is logged in - MUST be before ANY HTML output
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id == 0) {
    header("Location: orders.php");
    exit();
}

// Verify order belongs to user
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: orders.php");
    exit();
}

// Handle review submission - MUST be before ANY HTML output
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $product_id = (int)$_POST['product_id'];
    $rating = (int)$_POST['rating'];
    $review = trim($_POST['review']);
    
    if ($rating < 1 || $rating > 5) {
        $error = "Please select a rating (1-5 stars).";
    } elseif (empty($review)) {
        $error = "Please write a review.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ? AND order_id = ?");
        $stmt->bind_param("iii", $user_id, $product_id, $order_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "You already reviewed this product.";
        } else {
            $stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, order_id, rating, review) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiis", $product_id, $user_id, $order_id, $rating, $review);
            $stmt->execute();
            $success = true;
            header("Location: orders.php?reviewed=1");
            exit();
        }
    }
}

// NOW include header AFTER all redirects are handled
include('header.php');

// Get products from this order
$stmt = $conn->prepare("
    SELECT oi.product_id, p.name, p.price, p.image_url
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

// If no items found, show helpful message
if ($result->num_rows == 0) {
    ?>
    <div style="max-width: 700px; margin: 40px auto; padding: 0 20px;">
        <div style="background: #1a1c22; border-radius: 8px; border: 1px solid #2d2d2d; padding: 30px; text-align:center;">
            <div style="font-size:48px; margin-bottom:15px;">📦</div>
            <h2 style="color: #fff;">No products found in this order</h2>
            <p style="color: #94a3b8;">This order doesn't have any items to review.</p>
            <p style="color: #666; font-size: 13px; margin-top: 10px;">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></p>
            <a href="orders.php" style="display:inline-block; margin-top:15px; background:#dc3545; color:#fff; padding:10px 30px; border-radius:4px; text-decoration:none;">Back to Orders</a>
        </div>
    </div>
    <?php
    include('footer.php');
    exit();
}
?>

<div style="max-width: 700px; margin: 40px auto; padding: 0 20px;">
    <div style="background: #1a1c22; border-radius: 8px; border: 1px solid #2d2d2d; padding: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="color: #fff; font-size: 24px; margin: 0;">⭐ Write a Review</h1>
            <a href="orders.php" style="color: #94a3b8; text-decoration: none; padding: 8px 16px; border: 1px solid #2d2d2d; border-radius: 4px;">← Back</a>
        </div>
        
        <p style="color: #94a3b8; margin-bottom: 20px;">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></p>
        
        <?php if (isset($error)): ?>
            <div style="background: rgba(220, 53, 69, 0.1); border: 1px solid #dc3545; padding: 15px; border-radius: 4px; color: #dc3545; margin-bottom: 20px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['reviewed']) && $_GET['reviewed'] == 1): ?>
            <div style="background: rgba(40, 167, 69, 0.1); border: 1px solid #28a745; padding: 15px; border-radius: 4px; color: #28a745; margin-bottom: 20px;">
                ✅ Review submitted successfully!
            </div>
        <?php endif; ?>
        
        <?php 
        $has_unreviewed = false;
        while ($product = $result->fetch_assoc()): 
            $stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ? AND order_id = ?");
            $stmt->bind_param("iii", $user_id, $product['product_id'], $order_id);
            $stmt->execute();
            $already_reviewed = $stmt->get_result()->num_rows > 0;
            
            if ($already_reviewed) continue;
            $has_unreviewed = true;
        ?>
            <div style="border-top: 1px solid #2d2d2d; padding-top: 20px; margin-top: 20px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <?php 
                    $image_path = !empty($product['image_url']) ? $product['image_url'] : '';
                    if (!empty($image_path) && filter_var($image_path, FILTER_VALIDATE_URL)): ?>
                        <img src="<?php echo htmlspecialchars($image_path); ?>" style="width:60px;height:60px;object-fit:cover;border-radius:4px;background:#0f1115;">
                    <?php else: ?>
                        <div style="width:60px;height:60px;background:#0f1115;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#dc3545;">👕</div>
                    <?php endif; ?>
                    <div>
                        <h3 style="color: #fff; margin-bottom: 2px;"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p style="color: #94a3b8; font-size: 14px;"><?php echo number_format($product['price'], 2); ?> BD</p>
                    </div>
                </div>
                
                <form method="POST" style="margin-top: 15px;">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    
                    <label style="color: #94a3b8; display: block; margin-bottom: 8px;">Your Rating</label>
                    <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-direction: row-reverse; justify-content: flex-end;">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <label style="cursor: pointer; font-size: 30px; color: #555; transition: all 0.2s;">
                                <input type="radio" name="rating" value="<?php echo $i; ?>" required style="display: none;">
                                <span class="star" data-value="<?php echo $i; ?>">☆</span>
                            </label>
                        <?php endfor; ?>
                    </div>
                    
                    <label style="color: #94a3b8; display: block; margin-bottom: 8px;">Your Review</label>
                    <textarea name="review" rows="4" required placeholder="Share your experience with this product..." style="width:100%; padding:12px; background:#0f1115; border:1px solid #2d2d2d; color:#fff; border-radius:4px; resize:vertical;"></textarea>
                    
                    <button type="submit" name="submit_review" style="margin-top: 15px; background: #dc3545; color: #fff; border: none; padding: 12px 30px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                        ⭐ Submit Review
                    </button>
                </form>
            </div>
        <?php endwhile; ?>
        
        <?php if (!$has_unreviewed): ?>
            <div style="text-align:center; padding: 30px 0;">
                <div style="font-size:48px; margin-bottom:15px;">🎉</div>
                <h2 style="color: #fff;">All products reviewed!</h2>
                <p style="color: #94a3b8;">Thank you for sharing your feedback.</p>
                <a href="orders.php" style="display:inline-block; margin-top:15px; background:#28a745; color:#fff; padding:10px 30px; border-radius:4px; text-decoration:none;">Back to Orders</a>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<script>
document.querySelectorAll('.star').forEach(function(star) {
    star.addEventListener('click', function() {
        var value = this.dataset.value;
        var stars = this.closest('form').querySelectorAll('.star');
        stars.forEach(function(s) {
            if (parseInt(s.dataset.value) <= parseInt(value)) {
                s.textContent = '★';
                s.style.color = '#ffc107';
            } else {
                s.textContent = '☆';
                s.style.color = '#555';
            }
        });
        this.closest('form').querySelector('input[type="radio"]').checked = true;
    });
});
</script>

<?php include('footer.php'); ?>