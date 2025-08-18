<?php
ob_start(); // Bắt đầu output buffering
require_once('helpers/startSession.php');
startRoleSession('customer');
require_once('layout/header.php');
require_once('database/dbhelper.php');

$orderId = isset($_GET['order_ID']) && is_numeric($_GET['order_ID']) ? intval($_GET['order_ID']) : null;
$order = null;
$message = ''; // Biến để lưu thông báo từ session
$message_type = ''; // Biến để lưu loại thông báo (success/error)

if ($orderId) {
    // Lấy thông tin đơn hàng
    $sql = "SELECT * FROM Orders WHERE order_ID = ?";
    $orderResult = executeResult($sql, [$orderId]);
    if ($orderResult && count($orderResult) > 0) {
        $order = $orderResult[0];
    }
}

// Xử lý xác nhận chuyển khoản nếu có POST request từ nút "Tôi đã chuyển khoản"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_bank_transfer']) && $orderId) {
    // Kiểm tra lại trạng thái đơn hàng và phương thức thanh toán trước khi cập nhật
    if ($order && $order['payment_method'] === 'bank_transfer' && $order['status'] === 'Chờ thanh toán') {
        $update_sql = "UPDATE Orders SET status = 'Chờ xác nhận' WHERE order_ID = ?";
        if (execute($update_sql, [$orderId])) {
            // Ghi log trạng thái (có thể thêm staff_ID nếu có)
            execute("INSERT INTO OrderStatusHistory (order_ID, status) VALUES (?, 'Chờ xác nhận')", [$orderId]);

            $_SESSION['message'] = 'Bạn đã xác nhận chuyển khoản thành công. Đơn hàng của bạn đang chờ được kiểm tra.';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Đã có lỗi xảy ra khi cập nhật trạng thái đơn hàng. Vui lòng thử lại.';
            $_SESSION['message_type'] = 'error';
        }
    } else {
        $_SESSION['message'] = 'Đơn hàng không hợp lệ hoặc không ở trạng thái chờ thanh toán để xác nhận.';
        $_SESSION['message_type'] = 'error';
    }
    // Chuyển hướng để ngăn chặn gửi lại form và cập nhật giao diện
    header('Location: camon.php?order_ID=' . $orderId);
    exit();
}

// Lấy thông báo từ session nếu có và xóa nó đi để không hiển thị lại
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<link rel="stylesheet" href="./css/camon.css">
<main>
    <div class="container">
        <div class="thank-you">
            <?php
            // Hiển thị thông báo chung từ session (nếu có)
            if (!empty($message)) {
                $alert_class = ($message_type === 'success') ? 'alert-success' : 'alert-danger';
                echo '<div class="alert ' . $alert_class . ' text-center" role="alert">' . htmlspecialchars($message) . '</div>';
            }
            ?>

            <?php if ($order): ?>
                <h1> Đơn hàng của bạn đã được ghi nhận!</h1>
                <p>Mã đơn hàng: <strong>#<?= $order['order_ID'] ?></strong></p>

                <?php
                // Biến này để kiểm soát việc hiển thị các nút điều hướng chung (Trang chủ, Lịch sử mua hàng)
                $displayStandardButtons = true;

                if ($order['status'] === 'Đã hủy') {
                    // Trường hợp đơn hàng đã bị hủy
                    echo '<p class="text-danger mt-3">Đơn hàng của bạn đã bị huỷ. Nếu bạn đã chuyển khoản, vui lòng liên hệ bộ phận hỗ trợ để được giải quyết.</p>';
                } elseif ($order['payment_method'] === 'bank_transfer') {
                    // Xử lý riêng cho phương thức thanh toán "chuyển khoản ngân hàng"
                    if ($order['status'] === 'Chờ thanh toán') {
                        // Hiển thị thông tin chuyển khoản và nút xác nhận
                        $amount = $order['final_total'];
                        $accountNumber = "0123456789"; // ví dụ
                        $accountName = "TRẦN NGỌC MINH ĐỨC";
                        $bankName = "Vietcombank";
                        $note = "THANH TOAN DON HANG #$orderId";
                        $qrUrl = "https://img.vietqr.io/image/VCB-" . $accountNumber . "-compact.png?amount=" . $amount . "&addInfo=" . urlencode($note) . "&accountName=" . urlencode($accountName);
                        ?>
                        <p class="lead text-danger mt-3">Đơn hàng của bạn đang chờ thanh toán.</p>
                        <p>Vui lòng chuyển khoản theo thông tin bên dưới:</p>

                        <div class="bank-info mt-3" style="text-align: left; max-width: 500px; margin: 0 auto;">
                            <p><strong>Ngân hàng:</strong> <?= $bankName ?></p>
                            <p><strong>Số tài khoản:</strong> <?= $accountNumber ?></p>
                            <p><strong>Chủ tài khoản:</strong> <?= $accountName ?></p>
                            <p><strong>Số tiền:</strong> <?= number_format($amount, 0, ',', '.') ?> VNĐ</p>
                            <p><strong>Nội dung chuyển khoản:</strong> <?= $note ?></p>
                        </div>

                        <div class="qr-section mt-4" style="display: flex; justify-content: center; align-items: center; flex-direction: column;">
                            <p><strong>Hoặc quét mã QR để chuyển khoản nhanh:</strong></p>
                            <img src="<?= $qrUrl ?>" alt="QR chuyển khoản" width="240" height="240">
                        </div>

                        <form method="POST" action="">
                            <input type="hidden" name="confirm_bank_transfer" value="1">
                            <button type="submit" class="btn mt-3">Tôi đã chuyển khoản</button>
                        </form>
                        <?php
                        // Ẩn các nút điều hướng chung khi người dùng cần tập trung vào việc chuyển khoản
                        $displayStandardButtons = false;
                    } elseif ($order['status'] === 'Chờ xác nhận') {
                        // Trạng thái chờ xác nhận sau khi người dùng đã bấm "Tôi đã chuyển khoản"
                        echo '<p class="text-success mt-3">Bạn đã xác nhận đã chuyển khoản. Đơn hàng sẽ được kiểm tra và xử lý sớm nhất.</p>';
                    } else {
                        // Các trạng thái khác của chuyển khoản (ví dụ: đã thanh toán, đang giao, đã hoàn thành...)
                        echo '<p class="text-success mt-3">Đơn hàng của bạn đang được xử lý. Cảm ơn bạn!</p>';
                        echo '<p class="text-info">Phương thức thanh toán: <strong>Chuyển khoản ngân hàng</strong></p>';
                    }
                } else {
                    // Xử lý chung cho tất cả các phương thức thanh toán khác (COD, Momo, PayPal...)
                    echo '<p class="text-success mt-3">Cảm ơn bạn đã đặt hàng! Đơn hàng của bạn đang được xử lý.</p>';
                    echo '<p class="text-info">Phương thức thanh toán: <strong>' . htmlspecialchars($order['payment_method']) . '</strong></p>';
                }
                ?>
            <?php else: ?>
                <p class="text-danger">Không tìm thấy thông tin đơn hàng.</p>
            <?php endif; ?>

            <?php
            // Hiển thị các nút điều hướng chung nếu biến $displayStandardButtons không phải là false
            if ($displayStandardButtons):
            ?>
                <a href="index.php" class="btn"> Quay về trang chủ</a>
                <a href="history.php" class="btn"> Xem lịch sử mua hàng</a>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once('layout/footer.php'); ?>

<?php
ob_end_flush(); // Kết thúc output buffering
?>
