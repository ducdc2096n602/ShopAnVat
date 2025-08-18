<?php
session_start();
require_once('../../layout/header.php');
require_once('../../database/dbhelper.php');

$order_ID = $_GET['order_ID'] ?? null;

if ($order_ID) {
    $reason = "Người dùng đã hủy thanh toán qua PayPal.";
    $sql = "UPDATE Orders 
            SET status = 'Đã hủy', cancel_reason = '$reason' 
            WHERE order_ID = $order_ID AND status = 'Chờ thanh toán'";
    execute($sql);
}
?>

<!-- Giao diện -->
<link rel="stylesheet" href="/ShopAnVat/css/camon.css">
<link rel="stylesheet" href="/ShopAnVat/css/footer.css">

<style>
/* Style riêng cho trạng thái hủy/thất bại */
.thank-you.failed h1 {
    color: #d32f2f;
}
.thank-you.failed p {
    color: #444;
}
.thank-you.failed .btn {
    background-color: #d32f2f;
}
.thank-you.failed .btn:hover {
    background-color: #b71c1c;
}
</style>

<main>
<div class="container">
    <div class="thank-you failed">
        <h1>❌ Giao dịch thất bại hoặc đã bị hủy!</h1>
        <p>Đơn hàng của bạn <strong>#<?= htmlspecialchars($order_ID) ?></strong> chưa được thanh toán qua PayPal.</p>
        <p>Lý do: Người dùng đã hủy quá trình thanh toán.</p>

        <div style="margin-top: 20px;">
            <a href="/ShopAnVat/index.php" class="btn">⬅️ Quay về trang chủ</a>
            <a href="/ShopAnVat/history.php" class="btn">🛍 Xem lịch sử mua hàng</a>
        </div>
    </div>
</div>
</main>

<?php require_once('../../layout/footer.php'); ?>
