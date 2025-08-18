<?php
// Tệp này mô phỏng một IPN từ MoMo chỉ để thử nghiệm phát triển.
// KHÔNG TRIỂN KHAI TỆP NÀY LÊN MÔI TRƯỜNG SẢN XUẤT.

// --- 1. Lấy Order ID từ URL và Cấu hình ---
// Lấy order_ID từ tham số URL 'order_id'. Nếu không có, mặc định là 108.
$your_order_ID_to_test = $_GET['order_id'] ?? 108;
// Ép kiểu về số nguyên để đảm bảo an toàn và chính xác.
$your_order_ID_to_test = (int)$your_order_ID_to_test;

// Cấu hình MoMo (phải khớp với momo_payment.php và momo_ipn.php)
$partnerCode = "MOMOBKUN20180529";
$accessKey = "klm05TvNBzhg7h7j";
$secretKey = "at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa";

// --- 2. Nhúng SweetAlert2 cho thông báo ---
// Để đảm bảo SweetAlert2 được tải và chạy đúng, ta sẽ nhúng nó ở đây.
// Lưu ý: Trong môi trường production, bạn nên nhúng SweetAlert2 vào layout chính của trang HTML.
echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
echo '<script>'; // Bắt đầu khối script cho các lệnh Swal.fire

// --- 3. Lấy thông tin đơn hàng từ Database ---
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

// --- QUAN TRỌNG: Điều chỉnh RAW HASH cho MoMo V2 IPN (Thứ tự & Tham số chính xác) ---
// Thứ tự và tập hợp các tham số này là rất quan trọng để xác minh chữ ký.
// 'orderType' và 'payType' thường KHÔNG phải là một phần của rawHash cho MoMo V2 IPN.
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

// --- 5. Gửi yêu cầu POST mô phỏng đến momo_ipn.php ---
$ipn_url = "http://localhost:8080/ShopAnVat/api/momo/momo_ipn.php"; // <-- ĐẢM BẢO ĐƯỜNG DẪN NÀY LÀ CHÍNH XÁC

$ch = curl_init($ipn_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($simulated_ipn_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

// --- 6. Xử lý phản hồi và hiển thị thông báo SweetAlert2 ---
//if ($response === false) {
    // Thông báo lỗi Curl
    //echo 'Swal.fire({
           // icon: "error",
            //title: "Lỗi Curl!",
            //text: "Lỗi khi gọi IPN: ' . htmlspecialchars(curl_error($ch)) . '",
            //footer: \'<a href="/ShopAnVat/index.php">Quay về trang chủ</a>\'
          //});';
//} else {
    // Thông báo thành công
   // echo 'Swal.fire({
           // icon: "success",
            //title: "Thành công!",
            //html: "IPN mô phỏng đã được gửi thành công đến <b>' . $ipn_url . '</b><br>Phản hồi từ IPN: <b>' . htmlspecialchars($response) . '</b><br>Kiểm tra logs và trạng thái đơn hàng <b>#' . $your_order_ID_to_test . '</b> trong CSDL của bạn.",
            //footer: \'<a href="/ShopAnVat/index.php">Quay về trang chủ</a> | <a href="/ShopAnVat/history.php">Xem lịch sử mua hàng</a>\'
          //});';
//}

curl_close($ch);
header('Location: /ShopAnVat/camon.php?order_ID=' . $your_order_ID_to_test);
exit();
//echo '</script>'; // Kết thúc khối script chính
?>