<?php 
require_once('db_config.php'); 
include('header.php'); 

$selected_category = $_GET['category'] ?? 'All';
$price_filter = $_GET['price_filter'] ?? 'All';
?>

<style>
/* Modern Catalog Styles */
.product-image-container {
    width: 100%;
    height: 280px;
    background: var(--bg-primary, #0f1115);
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--border-color, #2d2d2d);
    position: relative;
}

.product-image-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
    background: var(--bg-primary, #0f1115);
}

.premium-item-card:hover .product-image-container img {
    transform: scale(1.05);
}

.image-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    background: var(--bg-primary, #0f1115);
    color: #dc3545;
    font-size: 52px;
    font-weight: 700;
}

.image-placeholder .placeholder-icon {
    font-size: 48px;
    opacity: 0.6;
}

.image-placeholder .placeholder-code {
    font-size: 32px;
    font-weight: 800;
    color: #dc3545;
}

/* Size Options - Modern */
.size-option {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 6px;
    border: 1px solid var(--border-color, #2d2d2d);
    font-size: 12px;
    color: var(--text-secondary, #94a3b8);
    cursor: pointer;
    transition: all 0.3s ease;
    background: var(--bg-input, #0f1115);
    font-weight: 500;
}

.size-option:hover {
    background: var(--border-color, #2d2d2d);
    color: var(--text-primary, #ffffff);
}

.size-option.active {
    background: #dc3545;
    color: #fff;
    border-color: #dc3545;
}

/* Modern Buttons */
.btn-add-to-cart {
    width: 100%;
    padding: 12px;
    background: #dc3545;
    color: white;
    border: none;
    cursor: pointer;
    font-weight: 600;
    margin-top: 15px;
    border-radius: 6px;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 1px;
    transition: all 0.3s ease;
}

.btn-add-to-cart:hover {
    background: #b02a37;
    transform: scale(1.02);
}

.btn-add-to-cart:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.btn-out-of-stock {
    width: 100%;
    padding: 12px;
    background: #555;
    color: #666;
    border: none;
    cursor: not-allowed;
    font-weight: 600;
    margin-top: 15px;
    border-radius: 6px;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 1px;
    opacity: 0.6;
}

/* Stock Badge - Modern */
.stock-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    padding: 4px 0;
}

.stock-in {
    color: #28a745;
}

.stock-out {
    color: #dc3545;
}

/* Filter Menu */
.filter-menu-bar {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 25px;
    margin-bottom: 40px;
    padding: 20px 5% 30px;
    border-bottom: 1px solid var(--border-color, #2d2d2d);
    background: var(--bg-primary, #0f1115);
}

.category-capsule {
    color: var(--text-secondary, #94a3b8);
    text-decoration: none;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 1px;
    padding: 8px 20px;
    border: 1px solid transparent;
    transition: all 0.3s ease;
    border-radius: 20px;
    font-weight: 500;
}

.category-capsule:hover {
    color: var(--text-primary, #ffffff);
    border-color: #dc3545;
}

.category-capsule.active {
    color: #dc3545;
    border-color: #dc3545;
}

/* Product Grid */
.modern-storefront-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
    padding: 0 8% 50px;
    max-width: 1400px;
    margin: 0 auto;
    width: 100%;
}

.premium-item-card {
    background: var(--card-bg, #1a1c22);
    padding: 20px;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.3s ease;
    border: 1px solid var(--border-color, #2d2d2d);
    position: relative;
}

.premium-item-card:hover {
    transform: translateY(-5px);
    border-color: #dc3545;
    box-shadow: 0 10px 30px rgba(220, 53, 69, 0.1);
}

.product-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.product-info h3 {
    font-size: 1.1rem;
    margin-bottom: 8px;
    color: var(--text-primary, #ffffff);
}

.product-info p {
    color: var(--text-secondary, #94a3b8);
    font-size: 0.9rem;
    margin-bottom: 15px;
}

.product-price {
    font-weight: bold;
    color: #dc3545;
    margin: 10px 0;
    font-size: 1.2rem;
}

/* Reviews */
.product-reviews {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 8px 0;
    flex-wrap: wrap;
}

.star-rating {
    color: #ffc107;
    font-size: 14px;
    letter-spacing: 1px;
}

.review-link {
    color: #dc3545;
    font-size: 12px;
    text-decoration: none;
    font-weight: 500;
}

.review-link:hover {
    text-decoration: underline;
}
</style>

<div class="filter-menu-bar">
    
    <!-- Category Capsules -->
    <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
        <?php $categories = ['All', 'Tops', 'Bottoms', 'Hoodies', 'Outerwear', 'Dresses', 'Shoes', 'Accessories']; ?>
        <?php foreach ($categories as $cat): ?>
            <a href="catalog.php?category=<?php echo $cat; ?>&price_filter=<?php echo urlencode($price_filter); ?>" 
               class="category-capsule <?php echo $selected_category == $cat ? 'active' : ''; ?>">
               <?php echo $cat; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Price Filter -->
    <form action="catalog.php" method="GET" style="display: flex; gap: 12px; align-items: center; background: var(--bg-secondary, #1a1c22); padding: 6px 18px 6px 22px; border: 1px solid var(--border-color, #2d2d2d); border-radius: 8px;">
        <input type="hidden" name="category" value="<?php echo htmlspecialchars($selected_category); ?>">
        <label style="color: var(--text-secondary, #94a3b8); font-size: 12px; font-weight: 500;">Price</label>
        <div style="position: relative;">
            <select name="price_filter" onchange="this.form.submit()" style="
                background: var(--bg-input, #0f1115);
                color: var(--text-primary, #fff);
                border: 1px solid var(--border-color, #2d2d2d);
                border-radius: 6px;
                padding: 8px 35px 8px 14px;
                font-size: 14px;
                cursor: pointer;
                outline: none;
                appearance: none;
                -webkit-appearance: none;
                -moz-appearance: none;
                min-width: 130px;
                transition: all 0.3s ease;
            ">
                <option value="All" style="background: var(--bg-primary, #111); color: var(--text-primary, #fff);" <?php if($price_filter == 'All') echo 'selected'; ?>>All Prices</option>
                <option value="0-5" style="background: var(--bg-primary, #111); color: var(--text-primary, #fff);" <?php if($price_filter == '0-5') echo 'selected'; ?>>0 - 5 BD</option>
                <option value="5-10" style="background: var(--bg-primary, #111); color: var(--text-primary, #fff);" <?php if($price_filter == '5-10') echo 'selected'; ?>>5 - 10 BD</option>
                <option value="10-20" style="background: var(--bg-primary, #111); color: var(--text-primary, #fff);" <?php if($price_filter == '10-20') echo 'selected'; ?>>10 - 20 BD</option>
                <option value="20+" style="background: var(--bg-primary, #111); color: var(--text-primary, #fff);" <?php if($price_filter == '20+') echo 'selected'; ?>>20 BD & Above</option>
            </select>
            <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--text-secondary, #94a3b8); font-size: 10px; pointer-events: none;">▼</span>
        </div>
    </form>
</div>

<div class="modern-storefront-grid">
    <?php
    $sql = "SELECT * FROM products WHERE 1=1";
    
    if ($selected_category !== 'All') {
        $sql .= " AND category = '" . $conn->real_escape_string($selected_category) . "'";
    }
    
    if ($price_filter == '0-5') { 
        $sql .= " AND price <= 5"; 
    } elseif ($price_filter == '5-10') { 
        $sql .= " AND price > 5 AND price <= 10"; 
    } elseif ($price_filter == '10-20') { 
        $sql .= " AND price > 10 AND price <= 20"; 
    } elseif ($price_filter == '20+') { 
        $sql .= " AND price > 20"; 
    }

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while($item = $result->fetch_assoc()) {
            $stock = $item['stock'] ?? 0;
            $in_stock = $stock > 0;
            
            // Check if in wishlist
            $in_wishlist = false;
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
                $stmt->bind_param("ii", $user_id, $item['id']);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $in_wishlist = true;
                }
            }
            ?>
            <div class="premium-item-card">
                <!-- Out of Stock Overlay -->
                <?php if (!$in_stock): ?>
                    <div style="position: absolute; top: 12px; right: 12px; background: #dc3545; color: #fff; padding: 4px 14px; border-radius: 6px; font-size: 11px; font-weight: 600; letter-spacing: 0.5px; z-index: 10;">
                        Out of Stock
                    </div>
                <?php endif; ?>
                
                <div class="product-image-container">
                    <?php 
                    $image_path = !empty($item['image_url']) ? $item['image_url'] : '';
                    if (!empty($image_path) && filter_var($image_path, FILTER_VALIDATE_URL)): ?>
                        <img src="<?php echo htmlspecialchars($image_path); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             loading="lazy">
                    <?php elseif (!empty($image_path) && file_exists($image_path)): ?>
                        <img src="<?php echo htmlspecialchars($image_path); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             loading="lazy">
                    <?php else: ?>
                        <div class="image-placeholder">
                            <span class="placeholder-icon">👕</span>
                            <span class="placeholder-code"><?php echo strtoupper(substr($item['name'], 0, 2)); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="product-info">
                    <div style="font-size: 10px; letter-spacing: 2px; color: #dc3545; margin-bottom: 8px; font-weight: 700; text-transform: uppercase;">
                        Premium Overview
                    </div>
                    
                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                    <p><?php echo htmlspecialchars($item['description']); ?></p>
                    
                    <div class="product-price"><?php echo number_format($item['price'], 2); ?> BD</div>
                    
                    <!-- Stock Status - Modern -->
                    <div style="margin: 4px 0 8px 0;">
                        <?php if ($in_stock): ?>
                            <span class="stock-badge stock-in">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 6L9 17l-5-5"/>
                                </svg>
                                In Stock (<?php echo $stock; ?>)
                            </span>
                        <?php else: ?>
                            <span class="stock-badge stock-out">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc3545" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="15" y1="9" x2="9" y2="15"/>
                                    <line x1="9" y1="9" x2="15" y2="15"/>
                                </svg>
                                Out of Stock
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Product Reviews -->
                    <div class="product-reviews">
                        <?php 
                        $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE product_id = ?");
                        $stmt->bind_param("i", $item['id']);
                        $stmt->execute();
                        $review_data = $stmt->get_result()->fetch_assoc();
                        $avg_rating = round($review_data['avg_rating'] ?? 0, 1);
                        $review_count = $review_data['review_count'] ?? 0;
                        
                        if ($review_count > 0):
                        ?>
                            <span class="star-rating">
                                <?php 
                                $full_stars = floor($avg_rating);
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $full_stars ? '★' : '☆';
                                }
                                ?>
                            </span>
                            <span style="color: var(--text-secondary, #94a3b8); font-size: 13px;">
                                <?php echo $avg_rating; ?> (<?php echo $review_count; ?>)
                            </span>
                            <a href="product_reviews.php?id=<?php echo $item['id']; ?>" class="review-link">View all</a>
                        <?php else: ?>
                            <span style="color: var(--text-secondary, #666); font-size: 13px;">No reviews yet</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Size Selection -->
                    <div style="display: flex; gap: 6px; flex-wrap: wrap; margin: 8px 0;">
                        <?php 
                        $sizes = !empty($item['sizes']) ? explode(',', $item['sizes']) : ['S', 'M', 'L', 'XL'];
                        $size_type = $item['size_type'] ?? 'clothing';
                        foreach ($sizes as $size): 
                            $display = ($size_type === 'shoes') ? 'EU ' . trim($size) : trim($size);
                        ?>
                            <span class="size-option" 
                                  data-product-id="<?php echo $item['id']; ?>" 
                                  data-size="<?php echo trim($size); ?>"
                                  onclick="selectSize(this)">
                                <?php echo $display; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Selected Size Display -->
                    <div id="selected-size-<?php echo $item['id']; ?>" style="font-size: 12px; color: #28a745; margin-bottom: 8px; min-height: 20px;">
                        Select a size above
                    </div>

                    <!-- Wishlist Button -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div style="margin: 6px 0 4px;">
                            <a href="wishlist.php?add=<?php echo $item['id']; ?>" style="text-decoration: none; font-size: 14px; color: <?php echo $in_wishlist ? '#dc3545' : 'var(--text-secondary, #94a3b8)'; ?>; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 6px;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="<?php echo $in_wishlist ? '#dc3545' : 'none'; ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                </svg>
                                <?php echo $in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="margin: 6px 0 4px;">
                            <a href="login.php" style="text-decoration: none; font-size: 14px; color: var(--text-secondary, #94a3b8); display: inline-flex; align-items: center; gap: 6px;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                </svg>
                                Login to Wishlist
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <form action="cart_action.php" method="POST" id="cart-form-<?php echo $item['id']; ?>">
                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                    <input type="hidden" name="selected_size" id="size-input-<?php echo $item['id']; ?>" value="">
                    <?php if ($in_stock): ?>
                        <button type="submit" class="btn-add-to-cart" id="add-btn-<?php echo $item['id']; ?>" disabled>
                            Select Size to Add
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn-out-of-stock">
                            Out of Stock
                        </button>
                    <?php endif; ?>
                </form>
            </div>
            <?php
        }
    } else {
        echo "<p style='text-align: center; width: 100%; color: var(--text-secondary, #94a3b8); padding: 40px 0;'>No items match your filters.</p>";
    }
    ?>
</div>

<script>
function selectSize(element) {
    var productId = element.dataset.productId;
    var size = element.dataset.size;
    
    document.getElementById('size-input-' + productId).value = size;
    document.getElementById('selected-size-' + productId).innerHTML = '✓ Selected: <strong>' + size + '</strong>';
    
    var btn = document.getElementById('add-btn-' + productId);
    if (btn) {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
        btn.innerHTML = 'Add to Cart';
    }
    
    var allSizes = document.querySelectorAll('.size-option[data-product-id="' + productId + '"]');
    allSizes.forEach(function(s) {
        s.classList.remove('active');
    });
    
    element.classList.add('active');
}
</script>

<?php include('footer.php'); ?>