<?php
require_once('db_config.php');

// Add debugging
error_log("Chart data requested at " . date('Y-m-d H:i:s'));

// Get monthly sales data for the last 6 months
$monthly_data = [];
$monthly_orders = [];

for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_name = date('M', strtotime("-$i months"));
    
    // Get revenue for this month
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = ?");
    $stmt->bind_param("s", $month);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $monthly_data[] = (float)$row['total'];
    
    // Get order count for this month
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = ?");
    $stmt->bind_param("s", $month);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $monthly_orders[] = (int)$row['count'];
}

// Get month names
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $months[] = date('M', strtotime("-$i months"));
}

// Return data as JSON with debug info
$response = [
    'months' => $months,
    'revenue' => $monthly_data,
    'orders' => $monthly_orders,
    'debug' => [
        'total_revenue' => array_sum($monthly_data),
        'total_orders' => array_sum($monthly_orders),
        'has_data' => array_sum($monthly_data) > 0 || array_sum($monthly_orders) > 0,
        'timestamp' => date('Y-m-d H:i:s')
    ]
];

// Set proper content type
header('Content-Type: application/json');
echo json_encode($response);
?>