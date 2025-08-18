<?php
 require_once('../../../helpers/startSession.php');
startRoleSession('admin');
// Gửi header JSON
header('Content-Type: application/json');


// Kết nối CSDL
require_once('../../../database/dbhelper.php');

// Lấy doanh thu và số đơn hàng theo tháng
$sql = "
SELECT 
    DATE_FORMAT(order_date, '%Y-%m') AS month,
    COUNT(*) AS total_orders,
    SUM(final_total) AS total_revenue
FROM Orders
WHERE status IN ('Đã giao hàng', 'Hoàn tất')
GROUP BY month
ORDER BY month
AND order_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
";

$data = executeResult($sql);

// Chuẩn bị mảng JSON trả về
$labels = [];
$orderCounts = [];
$revenues = [];

foreach ($data as $row) {
    $labels[] = $row['month'];
    $orderCounts[] = (int)$row['total_orders'];
    $revenues[] = (float)$row['total_revenue'];
}

echo json_encode([
    'labels' => $labels,
    'orderCounts' => $orderCounts,
    'revenues' => $revenues
]);
