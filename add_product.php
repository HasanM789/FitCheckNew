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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $size_type = $_POST['size_type'] ?? 'clothing';
    $sizes = isset($_POST['sizes']) ? implode(',', $_POST['sizes']) : 'S,M,L,XL';
    $stock = (int)($_POST['stock'] ?? 0);
    $image_url = 'product.jpg';
    
    // ============================================
    // IMAGE UPLOAD HANDLING - FIXED
    // ============================================
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "images/";
        
        // Create images folder if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['product_image']['name']);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if it's a real image
        $check = getimagesize($_FILES['product_image']['tmp_name']);
        if ($check !== false) {
            // Allow certain formats
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($imageFileType, $allowed_types)) {
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
                    $image_url = $target_file;
                    $upload_success = "✅ Image uploaded successfully!";
                } else {
                    $error = "❌ Error uploading file. Check folder permissions.";
                }
            } else {
                $error = "❌ Only JPG, JPEG, PNG, GIF & WEBP files are allowed.";
            }
        } else {
            $error = "❌ File is not an image.";
        }
    } elseif (isset($_POST['image_url']) && !empty($_POST['image_url'])) {
        $image_url = $_POST['image_url'];
    }
    
    if (!isset($error)) {
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, image_url, category, sizes, size_type, stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdssssi", $name, $description, $price, $image_url, $category, $sizes, $size_type, $stock);
        
        if ($stmt->execute()) {
            $success = "✅ Product added successfully!";
            if (isset($upload_success)) {
                $success .= " " . $upload_success;
            }
            header("refresh:2;url=admin.php?tab=products");
        } else {
            $error = "❌ Error: " . $conn->error;
        }
    }
}

$categories = ['Tops', 'Bottoms', 'Hoodies', 'Shoes', 'Outerwear', 'Dresses', 'Accessories'];
$clothing_sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
$shoe_sizes = ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Add Product - FitCheck</title>
    <style>
        body { background: #0f1115; color: #fff; font-family: Arial, sans-serif; padding: 40px; }
        .container { max-width: 600px; margin: 0 auto; background: #1a1c22; padding: 40px; border-radius: 8px; border: 1px solid #2d2d2d; }
        h1 { color: #dc3545; margin-bottom: 10px; }
        .admin-badge { background: #dc3545; color: #fff; padding: 2px 12px; border-radius: 12px; font-size: 12px; margin-left: 10px; }
        .subtitle { color: #94a3b8; margin-bottom: 30px; }
        label { display: block; color: #94a3b8; margin-bottom: 8px; font-size: 14px; font-weight: 500; }
        input, select, textarea { width: 100%; padding: 12px; margin-bottom: 20px; background: #0f1115; border: 1px solid #2d2d2d; color: #fff; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
        input[type="file"] { padding: 10px; }
        input:focus, select:focus, textarea:focus { border-color: #dc3545; outline: none; }
        .btn { background: #dc3545; color: #fff; border: none; padding: 14px 40px; font-weight: bold; cursor: pointer; border-radius: 4px; font-size: 16px; width: 100%; }
        .btn:hover { background: #b02a37; }
        .success { color: #28a745; background: rgba(40, 167, 69, 0.1); padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #28a745; }
        .error { color: #dc3545; background: rgba(220, 53, 69, 0.1); padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #dc3545; }
        .back-link { display: inline-block; margin-top: 20px; color: #94a3b8; text-decoration: none; }
        .back-link:hover { color: #fff; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .btn-secondary { background: transparent; border: 1px solid #333; color: #fff; padding: 8px 20px; border-radius: 4px; text-decoration: none; font-size: 14px; }
        .btn-secondary:hover { border-color: #dc3545; color: #dc3545; }
        .divider { text-align: center; color: #666; margin: 20px 0; position: relative; }
        .divider::before { content: ''; position: absolute; top: 50%; left: 0; right: 0; height: 1px; background: #2d2d2d; }
        .divider span { background: #1a1c22; padding: 0 15px; position: relative; }
        .size-checkboxes { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; }
        .size-checkboxes label { display: flex; align-items: center; gap: 6px; color: #fff; font-weight: 400; font-size: 14px; cursor: pointer; }
        .size-checkboxes input[type="checkbox"] { width: 18px; height: 18px; margin: 0; accent-color: #dc3545; cursor: pointer; }
        .size-section { display: none; }
        .size-section.active { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; }
        .size-section.active label { display: flex; align-items: center; gap: 6px; color: #fff; font-weight: 400; font-size: 14px; cursor: pointer; }
        .size-section.active input[type="checkbox"] { width: 18px; height: 18px; margin: 0; accent-color: #dc3545; cursor: pointer; }
        .image-preview { max-width: 200px; max-height: 200px; object-fit: contain; border-radius: 4px; margin: 10px 0; display: block; background: #0f1115; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>➕ Add Product <span class="admin-badge">ADMIN</span></h1>
                <p class="subtitle">Add a new product to your store</p>
            </div>
            <a href="admin.php?tab=products" class="btn-secondary">← Back</a>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?> Redirecting to admin panel...</div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <label>Product Name *</label>
            <input type="text" name="name" required placeholder="e.g., Premium Leather Jacket">
            
            <label>Description *</label>
            <textarea name="description" required rows="3" placeholder="Describe your product..."></textarea>
            
            <label>Price (BD) *</label>
            <input type="number" name="price" step="0.01" required placeholder="e.g., 24.99">
            
            <label>Category *</label>
            <select name="category" id="category" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                <?php endforeach; ?>
            </select>
            
            <label>Size Type *</label>
            <select name="size_type" id="size_type" required>
                <option value="clothing">Clothing (XS, S, M, L, XL, XXL)</option>
                <option value="shoes">Shoes (36-46 EU)</option>
            </select>
            
            <label>Available Sizes</label>
            <div id="clothing_sizes" class="size-checkboxes">
                <?php foreach ($clothing_sizes as $size): ?>
                    <label>
                        <input type="checkbox" name="sizes[]" value="<?php echo $size; ?>" checked>
                        <?php echo $size; ?>
                    </label>
                <?php endforeach; ?>
            </div>
            
            <div id="shoe_sizes" class="size-checkboxes" style="display:none;">
                <?php foreach ($shoe_sizes as $size): ?>
                    <label>
                        <input type="checkbox" name="sizes[]" value="<?php echo $size; ?>" checked>
                        EU <?php echo $size; ?>
                    </label>
                <?php endforeach; ?>
            </div>
            
            <label>Stock Quantity *</label>
            <input type="number" name="stock" required min="0" placeholder="e.g., 100">
            
            <label>Upload Image</label>
            <input type="file" name="product_image" accept="image/*" id="imageInput">
            <img id="imagePreview" class="image-preview" style="display:none;">
            
            <div class="divider"><span>OR</span></div>
            
            <label>Image URL (optional)</label>
            <input type="text" name="image_url" placeholder="https://example.com/image.jpg">
            
            <button type="submit" name="add_product" class="btn">Add Product</button>
        </form>
        
        <a href="catalog.php" class="back-link">← Back to Catalog</a>
    </div>

    <script>
        // Image preview
        document.getElementById('imageInput').addEventListener('change', function(e) {
            var preview = document.getElementById('imagePreview');
            var file = e.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });

        // Size type toggle
        document.getElementById('size_type').addEventListener('change', function() {
            var clothingSizes = document.getElementById('clothing_sizes');
            var shoeSizes = document.getElementById('shoe_sizes');
            if (this.value === 'shoes') {
                clothingSizes.style.display = 'none';
                shoeSizes.style.display = 'flex';
            } else {
                clothingSizes.style.display = 'flex';
                shoeSizes.style.display = 'none';
            }
        });
    </script>
</body>
</html>