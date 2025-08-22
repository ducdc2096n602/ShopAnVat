<?php
// Tệp này mô phỏng một IPN từ MoMo chỉ để thử nghiệm phát triển.
// KHÔNG TRIỂN KHAI TỆP NÀY LÊN MÔI TRƯỜNG SẢN XUẤT.

// 1. Lấy Order ID từ URL và Cấu hình
// Lấy order_ID từ tham số URL 'order_id'. Nếu không có, mặc định là 108.
$your_order_ID_to_test = $_GET['order_id'] ?? 108;
// Ép kiểu về số nguyên để đảm bảo an toàn và chính xác.
$your_order_ID_to_test = (int)$your_order_ID_to_test;

// Cấu hình MoMo (phải khớp với momo_payment.php và momo_ipn.php)
$partnerCode = "MOMOBKUN20180529";
$accessKey = "klm05TvNBzhg7h7j";
$secretKey = "at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa";

//  2. Nhúng SweetAlert2 cho thông báo ---
echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
echo '<script>'; // Bắt đầu khối script cho các lệnh Swal.fire

//  3. Lấy thông tin đơn hàng từ Database
require_once('../../database/dbhelper.php');
$order = executeSingleResult("SELECT final_total FROM Orders WHERE order_ID = ?", [$your_order_ID_to_test]);

if (!$order) {
    // Nếu không tìm thấy đơn hàng, hiển thị SweetAlert2 lỗi và dừng.
    echo 'Swal.fire({
            icon: "error",
            title: "Lỗi!",
            text: "Không tìm thấy đơn hàng #' . $your_order_ID_to_test . ' trong cơ sở dữ liệu. Vui lòng tạo một đơn hàng với trạng thái \'Chờ thanh toán\' trước và cung cấp ID đơn hàng hợp lệ trên URL.",
            footer: \'<a href="/ShopAnVat/index.php">Quay về trang chủ</a>\'
          }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "/ShopAnVat/index.php";
            }
          });';
    echo '</script>'; // Kết thúc khối script
    exit(); // Dừng thực thi script PHP
}

// --- 4. Chuẩn bị Dữ liệu IPN Giả lập ---
$amount = (string)intval($order['final_total']);
$orderInfo = "Thanh toán đơn hàng #" . $your_order_ID_to_test;
$requestId = time() . "_sim";
$orderIdMoMo = "ORDER" . time() . "_sim";
$transId = "TRANS" . time();
$extraData = ""; // Giữ trống trừ khi bạn cần truyền thêm dữ liệu
$message = "Successful.";
$localMessage = "Giao dịch thành công.";
$responseTime = (string)round(microtime(true) * 1000);
$resultCode = "0"; // MÃ KẾT QUẢ THÀNH CÔNG


$rawHash = "accessKey=" . $accessKey .
           "&amount=" . $amount .
           "&extraData=" . $extraData .
           "&message=" . $message .
           "&orderId=" . $orderIdMoMo .
           "&orderInfo=" . $orderInfo .
           "&partnerCode=" . $partnerCode .
           "&requestId=" . $requestId .
           "&responseTime=" . $responseTime .
           "&resultCode=" . $resultCode .
           "&transId=" . $transId;

$signature = hash_hmac("sha256", $rawHash, $secretKey);

$simulated_ipn_data = [
    'partnerCode' => $partnerCode,
    'accessKey' => $accessKey,
    'requestId' => $requestId,
    'amount' => $amount,
    'orderId' => $orderIdMoMo,
    'orderInfo' => $orderInfo,
    'message' => $message,
    'localMessage' => $localMessage,
    'responseTime' => $responseTime,
    'errorCode' => $resultCode, // Thường được bao gồm để tương thích ngược
    'resultCode' => $resultCode,
    'transId' => $transId,
    'payType' => 'qr', // Có thể được bao gồm trong tải trọng JSON, nhưng KHÔNG trong rawHash
    'extraData' => $extraData,
    'signature' => $signature,
];

// 5. Gửi yêu cầu POST mô phỏng đến momo_ipn.php 
$ipn_url = "http://localhost:8080/ShopAnVat/api/momo/momo_ipn.php";

$ch = curl_init($ipn_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($simulated_ipn_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

curl_close($ch);
header('Location: /ShopAnVat/camon.php?order_ID=' . $your_order_ID_to_test);
exit();
?>