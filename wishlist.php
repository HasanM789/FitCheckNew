<?php
// Start session and include db_config FIRST
require_once('db_config.php');

// Check if user is logged in - MUST be before ANY HTML output
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$user_id = $_SESSION['user_id'];

// Handle add to wishlist - MUST be before ANY HTML output
if (isset($_GET['add']) && is_numeric($_GET['add'])) {
    $product_id = (int)$_GET['add'];
    
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $added = true;
    } else {
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $removed = true;
    }
    
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'wishlist.php'));
    exit();
}

// Handle remove from wishlist - MUST be before ANY HTML output
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $product_id = (int)$_GET['remove'];
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    header("Location: wishlist.php?removed=1");
    exit();
}

// NOW include header AFTER all redirects are handled
include('header.php');

// Get wishlist items
$stmt = $conn->prepare("
    SELECT p.*, w.added_at 
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id = ?
    ORDER BY w.added_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$wishlist_items = $result->fetch_all(MYSQLI_ASSOC);
$wishlist_count = count($wishlist_items);
?>

<div class="wishlist-page" style="max-width: 1400px; margin: 0 auto; padding: 40px 5%;">
    
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color, #2d2d2d); flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 700; color: var(--text-primary, #ffffff); margin: 0;">❤️ My Wishlist</h1>
            <p style="color: var(--text-secondary, #94a3b8); margin-top: 8px; font-size: 14px;"><?php echo $wishlist_count; ?> items saved</p>
        </div>
        <a href="catalog.php" style="display: inline-block; padding: 10px 24px; background: transparent; border: 1px solid var(--border-color, #2d2d2d); color: var(--text-primary, #ffffff); text-decoration: none; border-radius: 4px; font-size: 14px; transition: all 0.3s ease; white-space: nowrap;">
            ← Continue Shopping
        </a>
    </div>

    <?php if (isset($_GET['removed'])): ?>
        <div style="background: rgba(40, 167, 69, 0.1); border: 1px solid #28a745; padding: 15px; border-radius: 4px; margin-bottom: 20px; color: #28a745;">
            ✅ Item removed from wishlist!
        </div>
    <?php endif; ?>

    <?php if (count($wishlist_items) > 0): ?>
        <!-- GRID VIEW - 4 items per row -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px;">
            <?php foreach ($wishlist_items as $item): ?>
                <div style="background: var(--card-bg, #1a1c22); border-radius: 8px; border: 1px solid var(--border-color, #2d2d2d); overflow: hidden; transition: all 0.3s ease; display: flex; flex-direction: column;">
                    <!-- Image -->
                    <div style="position: relative; height: 200px; background: var(--bg-primary, #0f1115); display: flex; align-items: center; justify-content: center;">
                        <?php 
                        $image_path = !empty($item['image_url']) ? $item['image_url'] : '';
                        if (!empty($image_path) && file_exists($image_path)): ?>
                            <img src="<?php echo htmlspecialchars($image_path); ?>" style="width: 100%; height: 100%; object-fit: contain; background: var(--bg-primary, #0f1115);">
                        <?php else: ?>
                            <span style="font-size: 48px; color: var(--accent, #dc3545);">👕</span>
                        <?php endif; ?>
                        
                        <!-- Remove Button -->
                        <button onclick="location.href='wishlist.php?remove=<?php echo $item['id']; ?>'" 
                                style="position: absolute; top: 8px; right: 8px; background: rgba(220, 53, 69, 0.85); color: #fff; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                            ✕
                        </button>
                    </div>
                    
                    <!-- Details -->
                    <div style="padding: 15px; flex: 1; display: flex; flex-direction: column;">
                        <h3 style="font-size: 15px; color: var(--text-primary, #ffffff); margin: 0 0 6px 0; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </h3>
                        <p style="color: var(--text-secondary, #94a3b8); font-size: 12px; margin: 0 0 10px 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4; flex: 1;">
                            <?php echo htmlspecialchars($item['description']); ?>
                        </p>
                        
                        <!-- Price & Add to Cart -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 10px; border-top: 1px solid var(--border-color, #2d2d2d);">
                            <span style="color: var(--accent, #dc3545); font-weight: 700; font-size: 16px;"><?php echo number_format($item['price'], 2); ?> BD</span>
                            <form action="cart_action.php" method="POST" style="margin: 0;">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="selected_size" value="M">
                                <button type="submit" style="background: var(--accent, #dc3545); color: #fff; border: none; padding: 6px 14px; border-radius: 4px; cursor: pointer; font-size: 12px; transition: all 0.3s ease; white-space: nowrap;">
                                    🛒 Add
                                </button>
                            </form>
                        </div>
                        
                        <!-- Added Date -->
                        <div style="margin-top: 6px; font-size: 10px; color: #555;">
                            Added: <?php echo date('M d, Y', strtotime($item['added_at'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div style="text-align: center; padding: 80px 20px; background: var(--card-bg, #1a1c22); border-radius: 8px; border: 1px solid var(--border-color, #2d2d2d);">
            <div style="font-size: 64px; margin-bottom: 20px; opacity: 0.5;">💔</div>
            <h2 style="color: var(--text-primary, #ffffff); font-size: 24px; margin-bottom: 10px;">Your wishlist is empty</h2>
            <p style="color: var(--text-secondary, #94a3b8); margin-bottom: 30px; font-size: 16px;">Start saving your favorite items by clicking the heart icon on products!</p>
            <a href="catalog.php" style="display: inline-block; padding: 14px 40px; background: var(--accent, #dc3545); color: #fff; text-decoration: none; border-radius: 4px; font-weight: 600; transition: all 0.3s ease;">
                Browse Products
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include('footer.php'); ?>