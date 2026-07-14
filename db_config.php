<?php
// ============================================
// DATABASE CONFIGURATION - AIVEN CLOUD MYSQL
// ============================================

// Get credentials from environment variables (Render) or use defaults
$host = getenv('DB_HOST') ?: 'mysql-1085e61e-fit-check.e.aivencloud.com';
$port = getenv('DB_PORT') ?: 14357;
$db_user = getenv('DB_USER') ?: 'avnadmin';
$db_pass = getenv('DB_PASSWORD') ?: '';  // Password from environment variable
$db_name = getenv('DB_NAME') ?: 'defaultdb';

try {
    // Create MySQL connection with SSL (required for Aiven)
    $conn = new mysqli();
    
    // Enable SSL (REQUIRED for Aiven)
    $conn->ssl_set(null, null, null, null, null);
    $conn->real_connect($host, $db_user, $db_pass, $db_name, $port, null, MYSQLI_CLIENT_SSL);
    
    // Check connection
    if ($conn->connect_error) {
        die("Database Connection Failed: " . $conn->connect_error);
    }
    
    // Set charset to UTF-8
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// ============================================
// SESSION CONFIGURATION
// ============================================

// Set session cookie parameters for longer session
ini_set('session.gc_maxlifetime', 604800); // 7 days
ini_set('session.cookie_lifetime', 604800); // 7 days

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Initialize cart session ID if not exists
if (!isset($_SESSION['cart_session_id'])) {
    $_SESSION['cart_session_id'] = session_id() . '_' . time();
}

// ============================================
// FUNCTION TO GET DATABASE CONNECTION
// ============================================

function getDB() {
    global $conn;
    return $conn;
}

// ============================================
// FUNCTION TO CHECK IF USER IS LOGGED IN
// ============================================

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// ============================================
// FUNCTION TO GET CURRENT USER ID
// ============================================

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

// ============================================
// FUNCTION TO GET CURRENT USERNAME
// ============================================

function getUsername() {
    return $_SESSION['username'] ?? 'Guest';
}

// ============================================
// FUNCTION TO CHECK IF USER IS ADMIN
// ============================================

function isAdmin() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    try {
        global $conn;
        $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        return $user && $user['is_admin'] == 1;
    } catch (Exception $e) {
        return false;
    }
}

// ============================================
// FUNCTION TO GET CART COUNT
// ============================================

function getCartCount() {
    $total = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $qty) {
            $total += $qty;
        }
    }
    return $total;
}

// ============================================
// FUNCTION TO GET CART TOTAL
// ============================================

function getCartTotal() {
    $total = 0;
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        global $conn;
        $ids = array_keys($_SESSION['cart']);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $stmt = $conn->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($item = $result->fetch_assoc()) {
            $total += $item['price'] * $_SESSION['cart'][$item['id']];
        }
    }
    return $total;
}

// ============================================
// FUNCTION TO CLEAN AND SANITIZE INPUT
// ============================================

function sanitize($input) {
    global $conn;
    return $conn->real_escape_string(trim($input));
}

// ============================================
// FUNCTION TO GET PRODUCT BY ID
// ============================================

function getProductById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// ============================================
// FUNCTION TO GET ALL CATEGORIES
// ============================================

function getCategories() {
    global $conn;
    $result = $conn->query("SELECT DISTINCT category FROM products ORDER BY category");
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
    return $categories;
}

// ============================================
// FUNCTION TO GET PRODUCTS BY CATEGORY
// ============================================

function getProductsByCategory($category) {
    global $conn;
    if ($category == 'All') {
        $result = $conn->query("SELECT * FROM products ORDER BY id DESC");
    } else {
        $stmt = $conn->prepare("SELECT * FROM products WHERE category = ? ORDER BY id DESC");
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    return $products;
}

// ============================================
// FUNCTION TO GET ORDER BY ID
// ============================================

function getOrderById($order_id, $user_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT o.*, u.username 
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// ============================================
// FUNCTION TO GET ORDER ITEMS
// ============================================

function getOrderItems($order_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT oi.*, p.name, p.price 
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}

// ============================================
// FUNCTION TO GET USER ORDERS
// ============================================

function getUserOrders($user_id, $limit = null) {
    global $conn;
    $sql = "
        SELECT o.*, COUNT(oi.id) as item_count 
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.order_date DESC
    ";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    return $orders;
}

// ============================================
// FUNCTION TO UPDATE ORDER STATUS
// ============================================

function updateOrderStatus($order_id, $status) {
    global $conn;
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    return $stmt->execute();
}

// ============================================
// FUNCTION TO GET WISHLIST ITEMS
// ============================================

function getWishlistItems($user_id) {
    global $conn;
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
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}

// ============================================
// FUNCTION TO CHECK IF PRODUCT IN WISHLIST
// ============================================

function inWishlist($user_id, $product_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

// ============================================
// FUNCTION TO GET PRODUCT REVIEWS
// ============================================

function getProductReviews($product_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT r.*, u.username 
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.product_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    return $reviews;
}

// ============================================
// FUNCTION TO GET AVERAGE RATING
// ============================================

function getAverageRating($product_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    return [
        'avg' => round($data['avg_rating'] ?? 0, 1),
        'count' => $data['review_count'] ?? 0
    ];
}
?>