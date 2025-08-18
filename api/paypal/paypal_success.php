<?php
require_once('../../database/dbhelper.php'); // Đảm bảo đường dẫn đúng
session_start();

// Lấy order_ID từ tham số URL
$order_ID = $_GET['order_ID'] ?? '';
if (empty($order_ID) || !is_numeric($order_ID)) {
    die("Không có mã đơn hàng hợp lệ");
}
$order_ID = (int)$order_ID;

// Lấy các tham số phản hồi từ PayPal
$txn_id = $_GET['tx'] ?? null; // Mã giao dịch PayPal
$payment_status_paypal = $_GET['st'] ?? null; // Trạng thái thanh toán từ PayPal (ví dụ: "Completed")
$payment_amount = $_GET['amt'] ?? null;
$currency = $_GET['cc'] ?? null;

// Thêm log để kiểm tra các tham số nhận được
error_log("--- PayPal Success Callback received: " . date('Y-m-d H:i:s') . " ---");
error_log("Order ID: " . $order_ID . ", TXN ID: " . ($txn_id ?? 'N/A') . ", Status from PayPal: " . ($payment_status_paypal ?? 'N/A'));
error_log("-----------------------------------------------------------------");

// Biến để lưu trạng thái cuối cùng sẽ cập nhật vào DB
$status_to_update = 'Chờ xác nhận'; // Mặc định là 'Chờ xác nhận' nếu thanh toán thành công
$log_message = '';

if ($payment_status_paypal === 'Completed') {
    // Nếu PayPal báo Completed, chúng ta sẽ cập nhật trạng thái là 'Chờ xác nhận'
    // và lưu paypal_trans_id
    $sql = "UPDATE Orders SET status = ?, paypal_trans_id = ? WHERE order_ID = ?";
    $update_success = execute($sql, [$status_to_update, $txn_id, $order_ID]);

    if ($update_success) {
        $log_message = "Đơn hàng #" . $order_ID . " đã được cập nhật trạng thái thành '" . $status_to_update . "' và lưu PayPal Transaction ID.";
        error_log($log_message);
        // Thêm bản ghi vào OrderStatusHistory nếu bạn muốn
        execute("INSERT INTO OrderStatusHistory (order_ID, status, staff_ID, changed_at) VALUES (?, ?, NULL, NOW())", [$order_ID, $status_to_update]);
    } else {
        $log_message = "LỖI: Không thể cập nhật trạng thái đơn hàng #" . $order_ID . " thành '" . $status_to_update . "'.";
        error_log($log_message);
    }
} else {
    // Nếu trạng thái từ PayPal không phải 'Completed'
    $status_to_update = 'Thất bại'; // Hoặc một trạng thái khác phù hợp cho thanh toán không thành công
    $sql = "UPDATE Orders SET status = ? WHERE order_ID = ?";
    $update_success = execute($sql, [$status_to_update, $order_ID]);

    $log_message = "Thanh toán PayPal cho đơn hàng #" . $order_ID . " không thành công hoặc chưa hoàn tất. Trạng thái từ PayPal: " . ($payment_status_paypal ?? 'NULL') . ". Đã cập nhật trạng thái thành '" . $status_to_update . "'.";
    error_log($log_message);
    execute("INSERT INTO OrderStatusHistory (order_ID, status, staff_ID, changed_at) VALUES (?, ?, NULL, NOW())", [$order_ID, $status_to_update]);
}

// Sau khi xử lý xong, chuyển về trang cảm ơn
header("Location: /ShopAnVat/camon.php?order_ID=$order_ID&payment_status=" . ($payment_status_paypal === 'Completed' ? 'success' : 'failed'));
exit();
?>