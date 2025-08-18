<?php
 require_once('../../../helpers/startSession.php');
startRoleSession('admin');
header('Content-Type: application/json');
require_once('../../../database/dbhelper.php');

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-1 month'));
$to = $_GET['to'] ?? date('Y-m-d');
$type = $_GET['type'] ?? 'day'; // day, month, year

if (!$from || !$to) {
    echo json_encode(['error' => 'Thiếu ngày bắt đầu hoặc kết thúc']);
    exit;
}

switch ($type) {
    case 'month':
        $groupBy = "DATE_FORMAT(order_date, '%Y-%m')";
        $from = date('Y-m-01', strtotime($from)); // Đầu tháng
        $to = date('Y-m-t', strtotime($to));      // Cuối tháng
        break;

    case 'year':
        $groupBy = "YEAR(order_date)";
        $from = $from . '-01-01';
        $to = $to . '-12-31';
        break;

    default:
        $groupBy = "DATE(order_date)";
        // giữ nguyên $from, $to (đã là YYYY-MM-DD)
        break;
}

$sql = "
    SELECT $groupBy AS time_label, SUM(final_total) AS revenue
    FROM Orders
    WHERE DATE(order_date) BETWEEN '$from' AND '$to'
      AND status IN ('Đã giao hàng', 'Hoàn tất')
      AND final_total > 0
    GROUP BY time_label
    ORDER BY time_label ASC
";

$data = executeResult($sql);

$labels = [];
$revenues = [];

foreach ($data as $row) {
    $labels[] = $row['time_label'];
    $revenues[] = $row['revenue'];
}

echo json_encode([
    'labels' => $labels,
    'revenues' => $revenues
]);
