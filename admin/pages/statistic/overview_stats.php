<?php
 require_once('../../../helpers/startSession.php');
startRoleSession('admin');
header('Content-Type: application/json');
require_once('../../../database/dbhelper.php');

// Tổng số đơn hàng
$total_orders = executeResult("SELECT COUNT(*) AS total_orders FROM Orders")[0]['total_orders'];

// Số đơn chờ xử lý
$pending_orders = executeResult("SELECT COUNT(*) AS pending_orders FROM Orders WHERE status = 'Chờ xác nhận'")[0]['pending_orders'];

// Doanh thu hôm nay
$revenue_today = executeResult("
    SELECT SUM(final_total) AS revenue_today 
    FROM Orders 
    WHERE DATE(completed_date) = CURDATE()
    AND status = 'Hoàn tất'
")[0]['revenue_today'] ?? 0;


// Số lượng khách hàng
$customer_count = executeResult("SELECT COUNT(*) AS customer_count FROM Customer")[0]['customer_count'];

echo json_encode([
    'total_orders' => $total_orders,
    'pending_orders' => $pending_orders,
    'revenue_today' => $revenue_today,
    'customer_count' => $customer_count
]);

