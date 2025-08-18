<?php
 require_once('../../../helpers/startSession.php');
startRoleSession('admin');
require_once('../../../database/dbhelper.php');


$from = $_GET['from'] ?? date('Y-m-d', strtotime('-1 month'));
$to = $_GET['to'] ?? date('Y-m-d');
$type = $_GET['type'] ?? 'month';

// Nếu chưa có khoảng thời gian → mặc định 30 ngày gần nhất
if (!$from || !$to) {
    $to = date('Y-m-d');
    $from = date('Y-m-d', strtotime('-30 days'));
    $type = 'day'; // Chia theo ngày
}

$labels = [];
$orders = [];

try {
    if (!$from || !$to) {
        throw new Exception("Thiếu thời gian từ hoặc đến.");
    }

    switch ($type) {
        case 'day':
            $sql = "SELECT DATE(order_date) as time, COUNT(*) as total
                    FROM Orders
                    WHERE DATE(order_date) BETWEEN '$from' AND '$to'
                    GROUP BY DATE(order_date)
                    ORDER BY time";
            break;

        case 'month':
            $sql = "SELECT DATE_FORMAT(order_date, '%Y-%m') as time, COUNT(*) as total
        FROM Orders
        WHERE order_date BETWEEN '$from-01' AND '$to-31'
        GROUP BY DATE_FORMAT(order_date, '%Y-%m')
        ORDER BY time";
            break;

        case 'year':
         $sql = "SELECT YEAR(order_date) as time, COUNT(*) as total
        FROM Orders
        WHERE YEAR(order_date) BETWEEN '$from' AND '$to'
        GROUP BY YEAR(order_date)
        ORDER BY time";
            break;

        default:
            throw new Exception("Loại không hợp lệ.");
    }

    $result = executeResult($sql);
    foreach ($result as $row) {
        $labels[] = $row['time'];
        $orders[] = (int)$row['total'];
    }

    echo json_encode([
        'labels' => $labels,
        'orders' => $orders
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
