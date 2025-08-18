<?php
require_once('../../helpers/startSession.php');
startRoleSession('staff');
require_once('../../database/dbhelper.php');
require_once(__DIR__ . '/../../PHPMailer-master/src/PHPMailer.php');
require_once(__DIR__ . '/../../PHPMailer-master/src/SMTP.php');
require_once(__DIR__ . '/../../PHPMailer-master/src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Kiểm tra đăng nhập & quyền nhân viên
if (!isset($_SESSION['account_ID']) || $_SESSION['role_ID'] != 2) {
    header('Location: ../../login/login.php');
    exit();
}

$account_ID = $_SESSION['account_ID'];

// Lấy staff_ID từ account_ID
$staffRow = executeSingleResult("SELECT staff_ID FROM Staff WHERE account_ID = $account_ID");
if (!$staffRow) {
    $_SESSION['error'] = 'Không tìm thấy nhân viên tương ứng.';
    header("Location: list_pending_orders.php");
    exit();
}
$staff_ID = $staffRow['staff_ID'];

$order_ID = isset($_GET['order_ID']) ? (int)$_GET['order_ID'] : 0;
if ($order_ID <= 0) {
    $_SESSION['error'] = 'Đơn hàng không hợp lệ!';
    header("Location: list_pending_orders.php");
    exit();
}

// Lấy thông tin đơn hàng
$order = executeSingleResult("
    SELECT o.*, a.fullname, a.phone_number, a.email 
    FROM Orders o 
    JOIN Customer c ON o.customer_ID = c.customer_ID 
    JOIN Account a ON c.account_ID = a.account_ID 
    WHERE o.order_ID = $order_ID");

if (!$order) {
    $_SESSION['error'] = 'Không tìm thấy đơn hàng.';
    header("Location: list_pending_orders.php");
    exit();
}

// Lấy sản phẩm trong đơn
$items = executeResult("
    SELECT oi.*, p.product_name, p.weight 
    FROM OrderItem oi 
    JOIN Product p ON oi.product_ID = p.product_ID 
    WHERE oi.order_ID = $order_ID");

// Xử lý POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $now = date('Y-m-d H:i:s');
    $action = $_POST['action'];
    $cancelReason = $_POST['cancel_reason'] ?? '';  // Nếu không có lý do từ chối, gán giá trị rỗng
    $new_status = '';

    if ($action === 'approve') {
        $new_status = 'Đã xác nhận';
        // Cập nhật trạng thái đơn hàng thành "Đã xác nhận"
        $updateQuery = "UPDATE Orders SET status = '$new_status', staff_ID = $staff_ID WHERE order_ID = $order_ID";
        $updateResult = execute($updateQuery);  // Thực thi câu lệnh

        if (!$updateResult) {
            $_SESSION['error'] = 'Duyệt đơn hàng thất bại. Vui lòng thử lại sau.';
            header("Location: list_pending_orders.php");
            exit();
        }

        // Thông báo thành công duyệt đơn
        $_SESSION['success_message'] = "Đã duyệt đơn hàng #$order_ID thành công!";
    } elseif ($action === 'reject') {
        $new_status = 'Đã hủy';
        $reasonEscaped = addslashes($cancelReason);  // Đảm bảo lý do từ chối không có ký tự đặc biệt

        // Cập nhật trạng thái đơn hàng thành "Đã hủy" và lý do từ chối
        $updateQuery = "UPDATE Orders SET status = '$new_status', cancel_reason = '$reasonEscaped', staff_ID = $staff_ID WHERE order_ID = $order_ID";
        $updateResult = execute($updateQuery);  // Thực thi câu lệnh

        if (!$updateResult) {
            $_SESSION['error'] = 'Từ chối đơn hàng thất bại. Vui lòng thử lại sau.';
            header("Location: list_pending_orders.php");
            exit();
        }

        // Thông báo thành công từ chối đơn
        $_SESSION['success_message'] = "Đã từ chối đơn hàng #$order_ID thành công!";
    }

    // Ghi lịch sử thay đổi trạng thái
    $historyQuery = "INSERT INTO OrderStatusHistory (order_ID, status, staff_ID, changed_at) 
                     VALUES ($order_ID, '$new_status', $staff_ID, '$now')";
    execute($historyQuery);  // Ghi lại lịch sử

    // Nếu đơn hàng bị từ chối, gửi email thông báo cho khách hàng
    if ($new_status === 'Đã hủy') {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->CharSet = "utf-8";
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ducdc2096n602@vlvh.ctu.edu.vn';  // Thay bằng email của bạn
            $mail->Password = 'ojdm dwzo ulhc lalt';  // Thay bằng mật khẩu ứng dụng
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom('ducdc2096n602@vlvh.ctu.edu.vn', 'Hệ thống Shop Ăn Vặt');
            $mail->addAddress($order['email'], $order['fullname']);
            $mail->isHTML(true);
            $mail->Subject = "Thông báo từ chối đơn hàng #$order_ID";

            $reasonToSend = $cancelReason ?: $order['cancel_reason'];
            $mail->Body = "
                <p>Xin chào <strong>" . htmlspecialchars($order['fullname']) . "</strong>,</p>
                <p>Đơn hàng <strong>#$order_ID</strong> của bạn đã bị <span style='color:red;'>từ chối</span>.</p>
                <p><strong>Lý do từ chối:</strong></p>
                <blockquote style='background:#f8f8f8;padding:10px;border-left:3px solid red;'>" . nl2br(htmlspecialchars($reasonToSend)) . "</blockquote>
                <p>Nếu bạn cần hỗ trợ thêm, vui lòng liên hệ CSKH của chúng tôi.</p>
                <p>Trân trọng,<br>Shop Ăn Vặt</p>
            ";
            $mail->send();
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gửi email thông báo từ chối thất bại. Vui lòng thử lại.';
            header("Location: list_pending_orders.php");
            exit();
        }
    }

    // Chuyển hướng về danh sách đơn hàng sau khi xử lý thành công
    header("Location: list_pending_orders.php");
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
</head>
<body class="bg-light">
<!-- Hiển thị thông báo lỗi nếu có -->
<?php
if (isset($_SESSION['error'])) {
    echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: '{$_SESSION['error']}',
                confirmButtonText: 'Đã hiểu'
            });
          </script>";
    unset($_SESSION['error']);
}
?>

<div class="container mt-5">
    <a href="list_pending_orders.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Quay lại</a>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4>Đơn hàng #<?= $order_ID ?> - <?= $order['status'] ?></h4>
        </div>
        <div class="card-body">
            <p><strong>Khách hàng:</strong> <?= htmlspecialchars($order['fullname']) ?> (<?= $order['phone_number'] ?>)</p>
            <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
            <p><strong>Ngày đặt:</strong> <?= date('H:i d/m/Y', strtotime($order['order_date'])) ?></p>
            <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['delivery_address']) ?></p>

            <hr>
            <h5>Danh sách sản phẩm:</h5>
            <table class="table table-bordered">
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
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td><?= $item['weight'] ?>g</td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= number_format($item['unitPrice'], 0, ',', '.') ?>đ</td>
                        <td><?= number_format($item['unitPrice'] * $item['quantity'], 0, ',', '.') ?>đ</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <p class="mt-3"><strong>Tổng tiền:</strong> <?= number_format($order['total_amount'], 0, ',', '.') ?>đ</p>
            <p><strong>Phí vận chuyển:</strong> <?= number_format($order['shipping_fee'], 0, ',', '.') ?>đ</p>
            <p><strong>Giảm giá:</strong> -<?= number_format($order['discount_amount'], 0, ',', '.') ?>đ</p>
            <p class="text-danger"><strong>Tổng thanh toán:</strong> <?= number_format($order['final_total'], 0, ',', '.') ?>đ</p>

            <form method="POST" class="mt-4" id="approveRejectForm" onsubmit="return validateCancelReason()">
                <div class="form-group" id="cancelReasonGroup" style="display:none;">
                    <label>Lý do từ chối đơn:</label>
                    <textarea name="cancel_reason" id="cancel_reason" rows="3" class="form-control" placeholder="..." readonly></textarea>
                </div>

                <div class="mt-3">
                    <button type="button" id="approveBtn" class="btn btn-success">
                        <i class="fas fa-check"></i> Duyệt đơn
                    </button>
                    <button type="button" id="rejectTriggerBtn" class="btn btn-danger" onclick="toggleReason(true)">
                        <i class="fas fa-times"></i> Từ chối đơn
                    </button>

                    <button type="submit" name="action" value="reject" id="confirmRejectBtn" class="btn btn-danger" style="display:none;">
                        <i class="fas fa-paper-plane"></i> Xác nhận từ chối
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Hàm toggleReason dùng để hiển thị hoặc ẩn lý do từ chối
    function toggleReason(show) {
        const reasonGroup = document.getElementById('cancelReasonGroup');
        const reasonInput = document.getElementById('cancel_reason');
        const rejectBtn = document.getElementById('rejectTriggerBtn');
        const confirmBtn = document.getElementById('confirmRejectBtn');

        reasonGroup.style.display = show ? 'block' : 'none';
        reasonInput.readOnly = !show;
        rejectBtn.style.display = show ? 'none' : 'inline-block';
        confirmBtn.style.display = show ? 'inline-block' : 'none';
    }

    // Kiểm tra lý do từ chối trước khi gửi form
    function validateCancelReason() {
        const action = document.activeElement.value;
        if (action === 'reject') {
            const reason = document.getElementById('cancel_reason').value.trim();
            if (reason === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Thiếu lý do từ chối',
                    text: 'Vui lòng nhập lý do từ chối đơn hàng',
                    confirmButtonText: 'Đã hiểu'
                }).then(() => {
                    document.getElementById('cancel_reason').focus();
                });
                return false;
            }
        }
        return true;
    }

    // Xử lý khi người dùng nhấn "Duyệt đơn"
    document.getElementById('approveBtn').addEventListener('click', function () {
        Swal.fire({
            icon: 'question',
            title: 'Xác nhận duyệt đơn',
            text: 'Bạn có chắc muốn duyệt đơn hàng này?',
            showCancelButton: true,
            confirmButtonText: 'Duyệt đơn',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('approveRejectForm');
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'action';
                input.value = 'approve';
                form.appendChild(input);
                form.submit();
            }
        });
    });

    // Xử lý khi người dùng nhấn "Xác nhận từ chối"
document.getElementById('confirmRejectBtn').addEventListener('click', function (e) {
    e.preventDefault(); // Ngăn submit ngay lập tức

    Swal.fire({
        icon: 'warning',
        title: 'Xác nhận từ chối đơn',
        text: 'Bạn có chắc chắn muốn từ chối đơn hàng này và gửi email cho khách hàng?',
        showCancelButton: true,
        confirmButtonText: 'Từ chối đơn',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            // Kiểm tra lý do trước khi gửi
            const reason = document.getElementById('cancel_reason').value.trim();
            if (reason === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Thiếu lý do từ chối',
                    text: 'Vui lòng nhập lý do từ chối đơn hàng',
                    confirmButtonText: 'Đã hiểu'
                }).then(() => {
                    document.getElementById('cancel_reason').focus();
                });
                return;
            }

            // Nếu hợp lệ thì submit form
            const form = document.getElementById('approveRejectForm');
            form.submit();
        }
    });
});

</script>

</body>
</html>
