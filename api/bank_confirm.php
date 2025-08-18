
<?php
require_once('../database/dbhelper.php');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_ID'])) {
    $order_ID = intval($_POST['order_ID']);

    // Cập nhật trạng thái
    execute("UPDATE Orders SET status = 'Chờ xác nhận' WHERE order_ID = ?", [$order_ID]);

    // Ghi log trạng thái (nếu có đăng nhập staff_ID thì thêm)
    execute("INSERT INTO OrderStatusHistory (order_ID, status) VALUES (?, 'Chờ xác nhận')", [$order_ID]);

    $response['success'] = true;
    $response['message'] = 'Xác nhận đã chuyển khoản thành công.';
} else {
    $response['message'] = 'Thiếu order_ID!';
}

echo json_encode($response);
