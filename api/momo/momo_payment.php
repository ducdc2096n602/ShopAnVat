<?php
require_once('../../database/dbhelper.php');

$order_ID = $_GET['order_ID'] ?? null;
if (!$order_ID) {
    die("Thiếu order_ID");
}

$order = executeResult("SELECT * FROM Orders WHERE order_ID = $order_ID");
if (!$order || count($order) == 0) die("Không tìm thấy đơn hàng");
$order = $order[0];

// Cấu hình MoMo Sandbox
$endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";
$partnerCode = "MOMOBKUN20180529";
$accessKey = "klm05TvNBzhg7h7j";
$secretKey = "at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa";

$amount = (string)intval($order['final_total']);
$orderInfo = "Thanh toán đơn hàng #" . $order_ID;
$redirectUrl = "http://localhost:8080/ShopAnVat/api/momo/momo_return.php?order_ID=$order_ID&resultCode=0";
$notifyUrl = "http://localhost:8080/ShopAnVat/api/momo/momo_ipn.php";

$requestId = time() . "";
$orderId = "ORDER" . time();
$extraData = "";

// Tạo chữ ký
$rawHash = "accessKey=$accessKey&amount=$amount&extraData=$extraData&ipnUrl=$notifyUrl&orderId=$orderId&orderInfo=$orderInfo&partnerCode=$partnerCode&redirectUrl=$redirectUrl&requestId=$requestId&requestType=captureWallet";
$signature = hash_hmac("sha256", $rawHash, $secretKey);

$data = [
    'partnerCode' => $partnerCode,
    'accessKey' => $accessKey,
    'requestId' => $requestId,
    'amount' => $amount,
    'orderId' => $orderId,
    'orderInfo' => $orderInfo,
    'redirectUrl' => $redirectUrl,
    'ipnUrl' => $notifyUrl,
    'extraData' => $extraData,
    'requestType' => 'captureWallet',
    'signature' => $signature,
    'lang' => 'vi'
];

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
if (isset($result['payUrl'])) {
    header('Location: ' . $result['payUrl']);
    exit();
} else {
    echo "<h3 style='color:red;'>Lỗi tạo giao dịch MoMo</h3><pre>";
    print_r($result);
    echo "</pre>";
}
