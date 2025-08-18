<?php
require_once('../../layout/header.php');
require_once('../../database/dbhelper.php');

$order_ID = $_GET['order_ID'] ?? null;
$resultCode = $_GET['resultCode'] ?? -1;
$message = $_GET['message'] ?? 'Không xác định';

$order = null;
if ($order_ID && is_numeric($order_ID)) {
    $result = executeResult("SELECT * FROM Orders WHERE order_ID = $order_ID");
    if ($result && count($result) > 0) {
        $order = $result[0];
    }
}
?>

<link rel="stylesheet" href="/ShopAnVat/css/camon.css">
<link rel="stylesheet" href="/ShopAnVat/css/footer.css">

<style>
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
    <div class="thank-you <?= ($resultCode != '0' ? 'failed' : '') ?>">
        <?php if ($resultCode == '0' && $order): ?>
            <h1>🎉 Giao dịch thành công!</h1>
            <p>Đơn hàng của bạn <strong>#<?= $order['order_ID'] ?></strong> đã được thanh toán qua MoMo.</p>
            <p>Số tiền: <strong><?= number_format($order['final_total'], 0, ',', '.') ?> VNĐ</strong></p>

            <?php
            if ($order['status'] != 'Đã thanh toán') {
                execute("UPDATE Orders SET status = 'Đã thanh toán' WHERE order_ID = $order_ID");
            }
            ?>

        <?php else: ?>
            <h1>❌ Giao dịch thất bại hoặc bị huỷ!</h1>
            <p>Mã đơn hàng: <strong>#<?= htmlspecialchars($order_ID) ?></strong></p>
            <p>Lý do: <?= htmlspecialchars(urldecode($message)) ?></p>

            <?php
            if ($order && $order['status'] === 'Chờ thanh toán') {
                $cancelReason = $message !== '' ? urldecode($message) : 'Giao dịch MoMo bị hủy hoặc thất bại.';
                execute("UPDATE Orders SET status = 'Đã hủy', cancel_reason = '".addslashes($cancelReason)."' WHERE order_ID = $order_ID");
            }
            ?>
        <?php endif; ?>

        <div style="margin-top: 20px;">
            <a href="/ShopAnVat/index.php" class="btn">⬅️ Quay về trang chủ</a>
            <a href="/ShopAnVat/history.php" class="btn">🛍 Xem lịch sử mua hàng</a>
        </div>
    </div>
</div>
</main>

<?php require_once('../../layout/footer.php'); ?>
