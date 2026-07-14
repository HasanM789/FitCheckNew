<?php
require_once('db_config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id == 0) {
    header("Location: orders.php");
    exit();
}

// Check if order exists and belongs to user
$stmt = $conn->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    header("Location: orders.php");
    exit();
}

// ONLY ALLOW CANCELLATION IF STATUS IS 'pending'
if ($order['status'] != 'pending') {
    header("Location: orders.php?error=cant_cancel");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Update order status to cancelled
    $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    
    // Restore stock for each item in the order
    $stmt = $conn->prepare("
        UPDATE products p 
        JOIN order_items oi ON p.id = oi.product_id 
        SET p.stock = p.stock + oi.quantity 
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    
    $conn->commit();
    
    header("Location: orders.php?cancelled=1");
    exit();
    
} catch (Exception $e) {
    $conn->rollback();
    header("Location: orders.php?error=cancel_failed");
    exit();
}
?>