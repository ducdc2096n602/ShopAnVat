<?php
require_once('../../../database/dbhelper.php');
require_once('../../../helpers/startSession.php');
startRoleSession('admin');

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Yêu cầu không hợp lệ.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $voucher_ID = (int)($_POST['voucher_ID'] ?? 0);

    if ($voucher_ID <= 0) {
        $response['message'] = 'ID Voucher không hợp lệ.';
        echo json_encode($response);
        exit(); // Thoát sớm nếu ID không hợp lệ
    }

    switch ($action) {
        case 'toggle':
            $status = (int)($_POST['status'] ?? 0); // 0 để kích hoạt lại, 1 để vô hiệu hóa
            $sql = "UPDATE Voucher SET is_deleted = ?, updated_at = NOW() WHERE voucher_ID = ?";
            // Sử dụng prepared statements để tránh SQL Injection
            $result = execute($sql, [$status, $voucher_ID]);

            if ($result !== false) { // execute trả về true/false hoặc số hàng bị ảnh hưởng
                $response['status'] = 'success';
                if ($status == 1) {
                    $response['message'] = 'Voucher đã được vô hiệu hóa thành công!';
                } else {
                    $response['message'] = 'Voucher đã được kích hoạt lại thành công!';
                }
            } else {
                $response['message'] = 'Không thể cập nhật trạng thái Voucher. Vui lòng thử lại.';
            }
            break;

        case 'delete':
            // Có thể thêm xác nhận hoặc logic phức tạp hơn ở đây nếu cần xóa cứng
            $sql = "DELETE FROM Voucher WHERE voucher_ID = ?";
            $result = execute($sql, [$voucher_ID]);

            if ($result !== false) {
                $response['status'] = 'success';
                $response['message'] = 'Voucher đã được xóa thành công!';
            } else {
                $response['message'] = 'Không thể xóa Voucher. Vui lòng thử lại.';
            }
            break;

        default:
            $response['message'] = 'Hành động không hợp lệ.';
            break;
    }
}

echo json_encode($response);
exit(); 
?>