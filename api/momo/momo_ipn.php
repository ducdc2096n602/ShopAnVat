<?php
require_once('../../database/dbhelper.php'); // Đảm bảo đường dẫn chính xác
// Lấy dữ liệu JSON được gửi từ MoMo
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!$data) {
    error_log("IPN MoMo: Không nhận được dữ liệu hoặc JSON không hợp lệ.");
    http_response_code(400);
    echo "Không nhận được dữ liệu hoặc JSON không hợp lệ";
    exit();
}

// Cấu hình MoMo (phải khớp với momo_payment.php)
$accessKey = "klm05TvNBzhg7h7j";
$secretKey = "at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa";

// Lấy các tham số cần thiết từ $data, sử dụng toán tử null coalescing để tránh cảnh báo "Undefined array key"
$accessKeyFromMoMo = $data['accessKey'] ?? '';
$amount = $data['amount'] ?? '';
$extraData = $data['extraData'] ?? '';
$message = $data['message'] ?? '';
$orderIdMoMo = $data['orderId'] ?? ''; // Order ID của MoMo
$orderInfo = $data['orderInfo'] ?? '';
$partnerCodeFromMoMo = $data['partnerCode'] ?? '';
$requestId = $data['requestId'] ?? '';
$responseTime = $data['responseTime'] ?? '';
$resultCode = $data['resultCode'] ?? '';
$transId = $data['transId'] ?? '';
$signatureFromMoMo = $data['signature'] ?? '';

// --- QUAN TRỌNG: TÁI TẠO RAW HASH CHO MOMO V2 (THỨ TỰ & THAM SỐ CHÍNH XÁC) ---
// Thứ tự và tập hợp các tham số này phải KHỚP CHÍNH XÁC với những gì MoMo sử dụng để tính toán chữ ký IPN.
// 'orderType' và 'payType' thường KHÔNG phải là một phần của raw hash cho MoMo V2 IPN.
$rawHash = "accessKey=" . $accessKeyFromMoMo .
           "&amount=" . $amount .
           "&extraData=" . $extraData .
           "&message=" . $message .
           "&orderId=" . $orderIdMoMo .
           "&orderInfo=" . $orderInfo .
           "&partnerCode=" . $partnerCodeFromMoMo .
           "&requestId=" . $requestId .
           "&responseTime=" . $responseTime .
           "&resultCode=" . $resultCode .
           "&transId=" . $transId;

$calculatedSignature = hash_hmac("sha256", $rawHash, $secretKey);

// So sánh chữ ký
if ($calculatedSignature !== $signatureFromMoMo) {
    error_log("IPN MoMo: Chữ ký không khớp cho đơn hàng: " . $orderInfo . " | Đã tính: " . $calculatedSignature . " | Đã nhận: " . $signatureFromMoMo);
    http_response_code(403);
    echo "Chữ ký không hợp lệ";
    exit();
}

// Trích xuất order_ID của bạn từ orderInfo hoặc extraData
$your_order_ID = null;
if (!empty($extraData) && is_numeric($extraData)) {
    $your_order_ID = intval($extraData);
} elseif (preg_match('/#(\d+)/', $orderInfo, $matches)) {
    $your_order_ID = intval($matches[1]);
}

if (!$your_order_ID) {
    error_log("IPN MoMo: Không thể trích xuất order_ID của bạn từ dữ liệu: " . json_encode($data));
    http_response_code(400);
    echo "ID đơn hàng không hợp lệ trong dữ liệu IPN";
    exit();
}

// Lấy trạng thái đơn hàng hiện tại để tránh cập nhật không cần thiết
$current_order = executeSingleResult("SELECT status FROM Orders WHERE order_ID = ?", [$your_order_ID]);

if (!$current_order) {
    error_log("IPN MoMo: Không tìm thấy đơn hàng " . $your_order_ID . " trong DB.");
    http_response_code(404);
    echo "Không tìm thấy đơn hàng";
    exit();
}

// Cập nhật trạng thái đơn hàng dựa trên resultCode
$new_status = null;
if ((int)$resultCode === 0) {
    // Giao dịch thành công
    // Nếu trạng thái hiện tại là 'Chờ thanh toán', cập nhật thành 'Chờ xác nhận'
    if ($current_order['status'] === 'Chờ thanh toán') {
        $new_status = 'Chờ xác nhận';
    } else {
        error_log("IPN MoMo: Đơn hàng " . $your_order_ID . " đã ở trạng thái " . $current_order['status'] . ", không cần cập nhật cho trường hợp thành công.");
    }
} else {
    // Giao dịch thất bại hoặc bị hủy
    // Nếu trạng thái hiện tại là 'Chờ thanh toán', cập nhật thành 'Đã hủy'
    if ($current_order['status'] === 'Chờ thanh toán') {
        $new_status = 'Đã hủy';
    } else {
        error_log("IPN MoMo: Đơn hàng " . $your_order_ID . " đã ở trạng thái " . $current_order['status'] . ", không cần cập nhật cho giao dịch thất bại.");
    }
}

// Thực hiện cập nhật nếu có trạng thái mới cần đặt
if ($new_status) {
    // Thêm momo_trans_id để tránh xử lý trùng lặp trong tương lai
    execute("UPDATE Orders SET status = ?, momo_trans_id = ? WHERE order_ID = ?", [$new_status, $transId, $your_order_ID]);
    // Ghi log lịch sử trạng thái
    execute("INSERT INTO OrderStatusHistory (order_ID, status, staff_ID, changed_at) VALUES (?, ?, NULL, NOW())", [$your_order_ID, $new_status]);
    error_log("IPN MoMo: Đơn hàng " . $your_order_ID . " đã được cập nhật thành '" . $new_status . "'. MoMo Trans ID: " . $transId);
}

// MoMo yêu cầu phản hồi HTTP 200 OK để xác nhận đã nhận được IPN
http_response_code(200);
echo "Success";
?>