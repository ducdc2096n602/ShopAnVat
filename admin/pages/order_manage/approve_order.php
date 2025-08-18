<?php
define('ROOT_PATH', dirname(__DIR__, 3) . DIRECTORY_SEPARATOR); 
define('BASE_URL', 'http://localhost:8080/ShopAnVat/'); 
require_once ROOT_PATH . 'helpers' . DIRECTORY_SEPARATOR . 'startSession.php';
startRoleSession('admin');
require_once ROOT_PATH . 'database' . DIRECTORY_SEPARATOR . 'dbhelper.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP; 

// Include thủ công các file PHPMailer
require_once ROOT_PATH . 'PHPMailer-master' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'PHPMailer.php';
require_once ROOT_PATH . 'PHPMailer-master' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'SMTP.php';
require_once ROOT_PATH . 'PHPMailer-master' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Exception.php';

// --- Bắt đầu logic xử lý PHP ---

// Kiểm tra đăng nhập & quyền
if (!isset($_SESSION['account_ID']) || $_SESSION['role_ID'] != 1) {
    $_SESSION['error_message'] = 'Bạn không có quyền truy cập trang này.';
    header('Location: ' . BASE_URL . 'login/login.php'); 
    exit();
}

$order_ID = isset($_GET['order_ID']) ? (int)$_GET['order_ID'] : 0;

if ($order_ID <= 0) {
    $_SESSION['error_message'] = 'Đơn hàng không hợp lệ!';
    header("Location: " . BASE_URL . "admin/pages/order_manage/listorder.php"); 
    exit();
}

// Lấy thông tin đơn hàng
// CHỈ LẤY ĐƠN HÀNG CÓ TRẠNG THÁI 'Chờ xác nhận' ĐỂ XỬ LÝ Ở TRANG NÀY
$order = executeSingleResult("
    SELECT o.*, a.fullname, a.phone_number, a.email 
    FROM Orders o 
    JOIN Customer c ON o.customer_ID = c.customer_ID 
    JOIN Account a ON c.account_ID = a.account_ID 
    WHERE o.order_ID = $order_ID AND o.status = 'Chờ xác nhận'
");



// Lấy sản phẩm trong đơn
$items = executeResult("
    SELECT oi.*, p.product_name, p.weight 
    FROM OrderItem oi 
    JOIN Product p ON oi.product_ID = p.product_ID 
    WHERE oi.order_ID = $order_ID
");

// Xử lý POST khi form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $now = date('Y-m-d H:i:s');
    $action = $_POST['action'];
    $cancelReason = $_POST['cancel_reason'] ?? '';
    $new_status = '';
    $success_message_text = '';
    // Đường dẫn chuyển hướng sau khi xử lý POST thành công
    $redirect_url = BASE_URL . "admin/pages/order_manage/listorder.php";

    if ($action === 'approve') {
        $new_status = 'Đã xác nhận';
        execute("UPDATE Orders SET status = '$new_status' WHERE order_ID = $order_ID");
        $success_message_text = "Đơn hàng #$order_ID đã được duyệt thành công!";
    } elseif ($action === 'reject') {
        $new_status = 'Đã hủy';
        $reasonEscaped = addslashes($cancelReason); 
        execute("UPDATE Orders SET status = '$new_status', cancel_reason = '$reasonEscaped' WHERE order_ID = $order_ID");
        $success_message_text = "Đơn hàng #$order_ID đã được từ chối.";

        // Gửi email khi bị từ chối
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->CharSet = "utf-8";
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ducdc2096n602@vlvh.ctu.edu.vn';
            $mail->Password = 'ojdm dwzo ulhc lalt'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
            $mail->Port = 465; 

            $mail->setFrom('ducdc2096n602@vlvh.ctu.edu.vn', 'Hệ thống Shop Ăn Vặt');
            $mail->addAddress($order['email'], $order['fullname']);
            $mail->isHTML(true);
            $mail->Subject = "Thông báo từ chối đơn hàng #$order_ID từ Shop Ăn Vặt";

            $reasonToSend = !empty($cancelReason) ? $cancelReason : 'Không có lý do cụ thể được cung cấp.';
            $mail->Body = "
                <p>Xin chào <strong>" . htmlspecialchars($order['fullname']) . "</strong>,</p>
                <p>Đơn hàng <strong>#$order_ID</strong> của bạn đã bị <span style='color:red; font-weight:bold;'>TỪ CHỐI</span>.</p>
                <p><strong>Lý do từ chối:</strong></p>
                <blockquote style='background:#fff0f0;padding:10px;border-left:4px solid red;'>" . nl2br(htmlspecialchars($reasonToSend)) . "</blockquote>
                <p>Chúng tôi rất tiếc về sự bất tiện này. Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với bộ phận hỗ trợ khách hàng của chúng tôi.</p>
                <p>Trân trọng,<br>Đội ngũ Shop Ăn Vặt</p>
            ";
            $mail->send();
        } catch (Exception $e) {
            error_log("Lỗi gửi email cho đơn hàng #$order_ID: " . $mail->ErrorInfo);
            $_SESSION['error_message'] = "Đã cập nhật trạng thái đơn hàng nhưng không thể gửi email thông báo: {$mail->ErrorInfo}";
        }
    }

    // Ghi lịch sử
    execute("INSERT INTO OrderStatusHistory (order_ID, status, changed_at) 
              VALUES ($order_ID, '$new_status', '$now')");

    $_SESSION['success_message'] = $success_message_text;
    header("Location: " . $redirect_url); 
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn hàng #<?= $order_ID ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
        }
        .card-header {
            background-color: #007bff; 
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
            text-align: center;
            padding: 20px;
        }
        .table th {
            background-color: #e9ecef;
        }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <a href="<?= BASE_URL ?>admin/pages/order_manage/listorder.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Quay lại Danh sách đơn hàng</a>

    <div class="card shadow-sm">
        <div class="card-header">
            <h4>Chi tiết đơn hàng #<?= $order_ID ?> - Trạng thái: <?= htmlspecialchars($order['status']) ?></h4>
        </div>
        <div class="card-body">
            <h5 class="mb-3">Thông tin khách hàng:</h5>
            <p><strong>Khách hàng:</strong> <?= htmlspecialchars($order['fullname']) ?> (<?= $order['phone_number'] ?>)</p>
            <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
            <p><strong>Ngày đặt:</strong> <?= date('H:i d/m/Y', strtotime($order['order_date'])) ?></p>
            <p><strong>Địa chỉ giao hàng:</strong> <?= htmlspecialchars($order['delivery_address']) ?></p>

            <hr>
            <h5 class="mb-3">Sản phẩm trong đơn:</h5>
            <table class="table table-bordered table-striped">
                <thead class="thead-light">
                <tr>
                    <th>Tên sản phẩm</th>
                    <th>Khối lượng</th>
                    <th>Số lượng</th>
                    <th>Đơn giá</th>
                    <th>Thành tiền</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="5" class="text-center">Không có sản phẩm nào trong đơn hàng này.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td><?= $item['weight'] ?>g</td>
                            <td><?= $item['quantity'] ?></td>
                            <td><?= number_format($item['unitPrice'], 0, ',', '.') ?>đ</td>
                            <td><?= number_format($item['unitPrice'] * $item['quantity'], 0, ',', '.') ?>đ</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

            <div class="text-right mt-3">
                <p><strong>Tổng tiền sản phẩm:</strong> <?= number_format($order['total_amount'], 0, ',', '.') ?>đ</p>
                <p><strong>Phí vận chuyển:</strong> <?= number_format($order['shipping_fee'], 0, ',', '.') ?>đ</p>
                <p><strong>Giảm giá:</strong> -<?= number_format($order['discount_amount'], 0, ',', '.') ?>đ</p>
                <h4 class="text-danger"><strong>Tổng thanh toán cuối cùng:</strong> <?= number_format($order['final_total'], 0, ',', '.') ?>đ</h4>
            </div>

            <hr>

            <?php if ($order['status'] === 'Chờ xác nhận'): ?>
            <form method="POST" class="mt-4" id="orderActionForm">
                <div class="form-group" id="cancelReasonGroup" style="display:none;">
                    <label for="cancel_reason">Lý do từ chối đơn hàng:</label>
                    <textarea name="cancel_reason" id="cancel_reason" rows="3" class="form-control" placeholder="Nhập lý do từ chối đơn hàng..."></textarea>
                </div>

                <div class="mt-3">
                    <button type="button" class="btn btn-success" id="approveButton">
                        <i class="fas fa-check"></i> Duyệt đơn
                    </button>

                    <button type="button" class="btn btn-danger" id="rejectTriggerButton">
                        <i class="fas fa-times"></i> Từ chối đơn
                    </button>

                    <button type="submit" name="action" value="reject" id="confirmRejectButton" class="btn btn-danger" style="display:none;">
                        <i class="fas fa-paper-plane"></i> Xác nhận từ chối
                    </button>
                </div>
            </form>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    Đơn hàng này đang ở trạng thái "<?= htmlspecialchars($order['status']) ?>". Bạn chỉ có thể duyệt hoặc từ chối đơn hàng khi trạng thái là "Chờ xác nhận".
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const cancelReasonGroup = document.getElementById('cancelReasonGroup');
    const cancelReasonInput = document.getElementById('cancel_reason');
    const rejectTriggerButton = document.getElementById('rejectTriggerButton');
    const confirmRejectButton = document.getElementById('confirmRejectButton');
    const approveButton = document.getElementById('approveButton');
    const orderActionForm = document.getElementById('orderActionForm');

    // Hàm ẩn/hiện lý do từ chối
    function toggleReason(show) {
        cancelReasonGroup.style.display = show ? 'block' : 'none';
        cancelReasonInput.readOnly = !show;
        rejectTriggerButton.style.display = show ? 'none' : 'inline-block';
        confirmRejectButton.style.display = show ? 'inline-block' : 'none';
        approveButton.style.display = show ? 'none' : 'inline-block';
    }

    // Nút "Từ chối đơn"
    rejectTriggerButton.addEventListener('click', function () {
        toggleReason(true);
    });

    // Nút "Duyệt đơn"
    approveButton.addEventListener('click', function (event) {
        event.preventDefault();
        Swal.fire({
            title: 'Xác nhận duyệt đơn hàng?',
            text: 'Bạn có chắc chắn muốn duyệt đơn hàng này?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Đồng ý duyệt!',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'action';
                hiddenInput.value = 'approve';
                orderActionForm.appendChild(hiddenInput);
                orderActionForm.submit();
            }
        });
    });

    // Nút "Xác nhận từ chối"
    confirmRejectButton.addEventListener('click', function (event) {
        event.preventDefault(); // Chặn submit trước khi xác nhận
        const reason = cancelReasonInput.value.trim();

        if (reason === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Thiếu lý do từ chối',
                text: 'Vui lòng nhập lý do từ chối đơn hàng!',
                confirmButtonText: 'Đã hiểu'
            }).then(() => {
                cancelReasonInput.focus();
            });
            return;
        }

        Swal.fire({
            title: 'Xác nhận từ chối đơn hàng?',
            text: 'Bạn có chắc chắn muốn từ chối đơn hàng này? Lý do sẽ được gửi cho khách hàng.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Từ chối đơn',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                orderActionForm.submit();
            }
        });
    });

    // SweetAlert từ session message
    <?php if (isset($_SESSION['success_message'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'Thành công!',
            text: '<?= $_SESSION['success_message'] ?>',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false
        });
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Lỗi!',
            text: '<?= $_SESSION['error_message'] ?>',
            confirmButtonText: 'Đã hiểu'
        });
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
});
</script>

</body>
</html>
