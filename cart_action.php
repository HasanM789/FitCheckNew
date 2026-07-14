<?php
require_once('db_config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $selected_size = $_POST['selected_size'] ?? 'M';
    
    // Verify product exists and check stock
    $result = $conn->query("SELECT id, stock FROM products WHERE id = $product_id");
    if ($result->num_rows === 0) {
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'catalog.php'));
        exit();
    }
    
    $product = $result->fetch_assoc();
    $stock = $product['stock'] ?? 0;
    
    // Check if in stock
    if ($stock <= 0) {
        // Redirect back with error
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'catalog.php') . "?error=outofstock");
        exit();
    }
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Store product with size in cart
    $cart_key = $product_id . '_' . $selected_size;
    
    // Check if adding more than stock
    $current_quantity = $_SESSION['cart'][$cart_key] ?? 0;
    if ($current_quantity + 1 > $stock) {
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'catalog.php') . "?error=stocklimit");
        exit();
    }
    
    if (isset($_SESSION['cart'][$cart_key])) {
        $_SESSION['cart'][$cart_key]++;
    } else {
        $_SESSION['cart'][$cart_key] = 1;
    }
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'catalog.php'));
exit();
?>