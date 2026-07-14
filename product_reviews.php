<?php
require_once('db_config.php');
include('header.php');

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id == 0) {
    header("Location: catalog.php");
    exit();
}

// Get product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    echo "<p style='text-align:center;padding:40px;color:#dc3545;'>Product not found.</p>";
    include('footer.php');
    exit();
}

// Get reviews
$stmt = $conn->prepare("
    SELECT r.*, u.username 
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$reviews = $stmt->get_result();

// Get average rating
$stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$avg_rating = round($stats['avg_rating'] ?? 0, 1);
$review_count = $stats['review_count'] ?? 0;
?>

<div style="max-width: 900px; margin: 40px auto; padding: 0 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="color: #fff; font-size: 28px; margin: 0;">📝 <?php echo htmlspecialchars($product['name']); ?></h1>
            <p style="color: #94a3b8; margin-top: 5px;">Customer Reviews</p>
        </div>
        <a href="catalog.php" style="color: #94a3b8; text-decoration: none; padding: 8px 20px; border: 1px solid #2d2d2d; border-radius: 4px;">← Back to Catalog</a>
    </div>

    <!-- Rating Summary -->
    <div style="background: #1a1c22; border-radius: 8px; border: 1px solid #2d2d2d; padding: 25px; margin-bottom: 30px;">
        <div style="display: flex; align-items: center; gap: 30px; flex-wrap: wrap;">
            <div style="text-align: center;">
                <div style="font-size: 48px; font-weight: 700; color: #ffc107;"><?php echo $avg_rating; ?></div>
                <div style="font-size: 24px; color: #ffc107;">
                    <?php 
                    $full_stars = floor($avg_rating);
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= $full_stars ? '★' : '☆';
                    }
                    ?>
                </div>
                <div style="color: #94a3b8; font-size: 14px;"><?php echo $review_count; ?> reviews</div>
            </div>
            <div style="flex: 1;">
                <?php 
                // Rating breakdown
                for ($r = 5; $r >= 1; $r--) {
                    $count = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE product_id = $product_id AND rating = $r")->fetch_assoc()['count'];
                    $percentage = $review_count > 0 ? round(($count / $review_count) * 100) : 0;
                ?>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                        <span style="color: #94a3b8; font-size: 13px; min-width: 30px;"><?php echo $r; ?> ★</span>
                        <div style="flex: 1; height: 8px; background: #0f1115; border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: <?php echo $percentage; ?>%; background: #ffc107; border-radius: 4px;"></div>
                        </div>
                        <span style="color: #94a3b8; font-size: 12px; min-width: 40px;"><?php echo $percentage; ?>%</span>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- Reviews List -->
    <div style="background: #1a1c22; border-radius: 8px; border: 1px solid #2d2d2d; padding: 25px;">
        <h3 style="color: #fff; margin-bottom: 20px;">All Reviews</h3>
        
        <?php if ($reviews->num_rows > 0): ?>
            <?php while ($review = $reviews->fetch_assoc()): ?>
                <div style="border-bottom: 1px solid #2d2d2d; padding: 15px 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <span style="color: #fff; font-weight: 600;"><?php echo htmlspecialchars($review['username']); ?></span>
                            <span style="color: #ffc107; font-size: 18px; margin-left: 10px;">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $review['rating'] ? '★' : '☆';
                                }
                                ?>
                            </span>
                        </div>
                        <span style="color: #666; font-size: 12px;"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                    </div>
                    <p style="color: #94a3b8; margin-top: 8px; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color: #94a3b8; text-align: center; padding: 20px 0;">No reviews yet. Be the first to review this product!</p>
        <?php endif; ?>
    </div>
</div>

<?php include('footer.php'); ?>