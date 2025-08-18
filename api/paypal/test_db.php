<?php
require_once('../../database/dbhelper.php'); // Đảm bảo đường dẫn đúng
error_log("--- Test DB update: " . date('Y-m-d H:i:s') . " ---");

$test_order_ID = 113; // Sử dụng một Order ID có sẵn trong bảng Orders của bạn.
                   // Nếu không chắc chắn, dùng 1 hoặc 2, hoặc order_ID cuối cùng bạn thấy.
$new_status = 'Test Status IPN';
$test_txn_id = 'TEST_TXN_ID_123'; // Một TXN ID giả định

// Test cập nhật trạng thái đơn hàng và paypal_trans_id
$sql = "UPDATE Orders SET status = ?, paypal_trans_id = ? WHERE order_ID = ?";
$update_success = execute($sql, [$new_status, $test_txn_id, $test_order_ID]);

if ($update_success) {
    echo "Cập nhật thành công cho Order ID #" . $test_order_ID . ".\n";
    error_log("Test Success: Đơn hàng #" . $test_order_ID . " đã được cập nhật trạng thái thành '" . $new_status . "'.");
} else {
    echo "LỖI: Không thể cập nhật Order ID #" . $test_order_ID . ".\n";
    error_log("Test Error: Không thể cập nhật đơn hàng #" . $test_order_ID . " trong DB.");
    // Ghi thêm lỗi nếu có thể để debug
    // Lưu ý: mysqli_error($conn) chỉ hoạt động ngay sau lệnh query
    // Lỗi cụ thể hơn sẽ nằm trong log từ hàm execute() đã chỉnh sửa
}
error_log("---------------------------------------");
?>