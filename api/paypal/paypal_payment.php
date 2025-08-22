<?php
session_start();
require_once('../../database/dbhelper.php');

// Lấy order_ID từ URL
$order_ID = $_GET['order_ID'] ?? '';
if (!$order_ID) {
    die("Thiếu mã đơn hàng");
}

// Lấy thông tin đơn hàng
$order = executeSingleResult("SELECT * FROM Orders WHERE order_ID = ?", [$order_ID]);
if (!$order) {
    die("Không tìm thấy đơn hàng");
}

// Lấy thông tin khách hàng
$customer_ID = $order['customer_ID'];
$customer = executeSingleResult("
    SELECT a.fullname, a.email, a.address 
    FROM Customer c 
    JOIN Account a ON c.account_ID = a.account_ID 
    WHERE c.customer_ID = ?", [$customer_ID]);

$fullname = $customer['fullname'] ?? 'Khách hàng';
$email = $customer['email'] ?? 'example@example.com';
$delivery_address = $order['delivery_address'] ?? '';

// ====== Cấu hình PayPal Sandbox ======
$paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
$return_url = "http://localhost:8080/ShopAnVat/api/paypal/paypal_success.php";
$cancel_url = "http://localhost:8080/ShopAnVat/api/paypal/paypal_cancel.php";
//$notify_url = "http://localhost:8080/ShopAnVat/api/paypal/ipn_listener.php";

// Tài khoản business sandbox
$business_email = "ducdc2096n602@business.example.com";

// Đổi tiền từ VND sang USD (tạm tính)
$total_vnd = (float)$order['final_total'];
$exchange_rate = 26000;
$usd_total = round($total_vnd / $exchange_rate, 2);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Redirecting to PayPal...</title>
</head>
<body onload="document.forms['paypalForm'].submit();">
    <h3>Đang chuyển hướng đến PayPal, vui lòng chờ...</h3>

    <form name="paypalForm" method="post" action="<?= $paypal_url ?>">
        <!-- Thông tin cơ bản -->
        <input type="hidden" name="business" value="<?= $business_email ?>">
        <input type="hidden" name="cmd" value="_xclick">
        <input type="hidden" name="item_name" value="Đơn hàng #<?= $order_ID ?>">
        <input type="hidden" name="item_number" value="<?= $order_ID ?>">
        <input type="hidden" name="amount" value="<?= $usd_total ?>">
        <input type="hidden" name="currency_code" value="USD">

        <!-- Thông tin người nhận -->
        <input type="hidden" name="first_name" value="<?= htmlspecialchars($fullname) ?>">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
        <input type="hidden" name="address1" value="<?= htmlspecialchars($delivery_address) ?>">
        <input type="hidden" name="address_override" value="1">
        <input type="hidden" name="no_shipping" value="1">
        <input type="hidden" name="city" value=".">
        <input type="hidden" name="state" value=".">
        <input type="hidden" name="zip" value=".">
        <input type="hidden" name="country" value="VN">

        <!-- URL điều hướng -->
        <input type="hidden" name="return" value="<?= $return_url ?>?order_ID=<?= $order_ID ?>">
        <input type="hidden" name="cancel_return" value="<?= $cancel_url ?>?order_ID=<?= $order_ID ?>">
        <input type="hidden" name="notify_url" value="<?= $notify_url ?>">

        <!-- Custom (dùng để xác định đơn hàng ở server) -->
        <input type="hidden" name="custom" value="<?= $order_ID ?>">
    </form>
</body>
</html>
