<?php
require_once('../../../helpers/startSession.php');
startRoleSession('admin');
require_once('../../../database/dbhelper.php');
require_once(__DIR__ . '/../../../PHPMailer-master/src/PHPMailer.php');
require_once(__DIR__ . '/../../../PHPMailer-master/src/SMTP.php');
require_once(__DIR__ . '/../../../PHPMailer-master/src/Exception.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$order_ID = $_GET['order_ID'] ?? 0;
$from_status = $_GET['from_status'] ?? '';

$sql = "SELECT 
            o.*, 
            kh.fullname AS customer_name,
            kh.phone_number AS customer_phone,
            kh.address AS customer_address,
            kh.email AS customer_email,
            nv.fullname AS staff_name,
            nv.phone_number AS staff_phone
        FROM Orders o
        JOIN Customer c ON o.customer_ID = c.customer_ID
        JOIN Account kh ON c.account_ID = kh.account_ID
        LEFT JOIN Staff s ON o.staff_ID = s.staff_ID
        LEFT JOIN Account nv ON s.account_ID = nv.account_ID
        WHERE o.order_ID = $order_ID";

$order = executeSingleResult($sql);

if (!$order) {
    echo '<div class="alert alert-danger">Không tìm thấy đơn hàng!</div>';
    exit();
}

// Lấy danh sách sản phẩm
$items = executeResult("SELECT oi.*, p.product_name, p.weight 
FROM OrderItem oi 
JOIN Product p ON oi.product_ID = p.product_ID 
WHERE oi.order_ID = $order_ID
");

// Trạng thái hiện tại
$currentStatus = executeSingleResult("SELECT status FROM OrderStatusHistory 
                                      WHERE order_ID = $order_ID 
                                      ORDER BY changed_at DESC LIMIT 1");

$currentStatusName = $currentStatus['status'] ?? '';

$statusOrder = [
    'Chờ xác nhận' => 1,
    'Đã xác nhận' => 2,
    'Đang chuẩn bị hàng' => 3,
    'Đang giao hàng' => 4,
    'Đã giao hàng' => 5,
    'Hoàn tất' => 6,
    'Đã hủy' => 7,
    'Trả hàng/Hoàn tiền' => 8,
    'Thất bại' => 9
];

// Cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
    $newStatus = $_POST['new_status'];
    $cancelReason = $_POST['cancel_reason'] ?? '';
    $now = date('Y-m-d H:i:s');

    if (isset($statusOrder[$newStatus], $statusOrder[$currentStatusName])) {
        if ($statusOrder[$newStatus] < $statusOrder[$currentStatusName]
            && $newStatus !== 'Đã hủy'
            && $newStatus !== 'Trả hàng/Hoàn tiền') {
            $_SESSION['error'] = "Không thể cập nhật trạng thái từ \"{$currentStatusName}\" về \"{$newStatus}\".";
            header("Location: " . $_SERVER['REQUEST_URI']); // quay lại trang hiện tại
            exit();
        }
    }

    // Cập nhật trạng thái đơn
    $updateQuery = "UPDATE Orders SET status = '$newStatus'";
if ($newStatus === 'Đã hủy') {
    $reasonEscaped = addslashes($cancelReason);
    $updateQuery .= ", cancel_reason = '$reasonEscaped'";
} elseif ($newStatus === 'Hoàn tất') {
    $updateQuery .= ", completed_date = '$now'";
}
$updateQuery .= " WHERE order_ID = $order_ID";

    execute($updateQuery);

    // Ghi log
    execute("INSERT INTO OrderStatusHistory (order_ID, status, staff_ID, changed_at) 
             VALUES ($order_ID, '$newStatus', NULL, '$now')");

    // Nếu là trạng thái hủy, gửi email
    if ($newStatus === 'Đã hủy') {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->CharSet = "utf-8";
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ducdc2096n602@vlvh.ctu.edu.vn';
            $mail->Password = 'ojdm dwzo ulhc lalt';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom('ducdc2096n602@vlvh.ctu.edu.vn', 'Hệ thống Shop Ăn Vặt');
            $mail->addAddress($order['customer_email'], $order['customer_name']);
            $mail->isHTML(true);
            $mail->Subject = "Thông báo từ chối đơn hàng #$order_ID";

            $mail->Body = "
                <p>Xin chào <strong>" . htmlspecialchars($order['customer_name']) . "</strong>,</p>
                <p>Đơn hàng <strong>#$order_ID</strong> của bạn đã bị <span style='color:red;'>hủy</span>.</p>
                <p><strong>Lý do hủy:</strong></p>
                <blockquote style='background:#f8f8f8;padding:10px;border-left:3px solid red;'>" . nl2br(htmlspecialchars($cancelReason)) . "</blockquote>
                <p>Nếu bạn cần hỗ trợ thêm, vui lòng liên hệ CSKH của chúng tôi.</p>
                <p>Trân trọng,<br>Shop Ăn Vặt</p>
            ";
            $mail->send();
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gửi email thất bại: ' . $mail->ErrorInfo;
        }
    }

    $_SESSION['success_message'] = "Cập nhật trạng thái đơn hàng #$order_ID thành công!";
    header("Location: listorder.php" . (!empty($from_status) ? "?status=" . urlencode($from_status) : ""));
    exit();
}


?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn hàng #<?= $order_ID ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <style>
        body {
            background: #f8f9fa;
            padding: 30px;
        }
        .order-box {
            background: #fff;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .order-box h3 {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .order-info p {
            margin-bottom: 5px;
            font-size: 16px;
        }
        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }
        .text-danger {
            font-size: 18px;
        }
        #cancelReasonGroup textarea {
    border-left: 5px solid red;
}
    </style>
</head>
<body>

<div class="container order-box">
    <h3>Chi tiết đơn hàng <span class="text-primary">#<?= $order_ID ?></span></h3>
    <div class="row order-info">
        <div class="col-md-6">
            <p><strong>👤 Khách hàng:</strong> <?= $order['customer_name'] ?></p>
            <p><strong>📞 SĐT:</strong> <?= $order['customer_phone'] ?></p>
        </div>
        <div class="col-md-6">
            <p><strong>📍 Địa chỉ giao hàng:</strong> <?= $order['delivery_address'] ?></p>
            <p><strong>📅 Ngày đặt:</strong> <?= date('H:i d/m/Y', strtotime($order['order_date'])) ?></p>
            <p><strong>👨‍💼 Nhân viên phụ trách:</strong> <?= $order['staff_name'] ?? '<i>Admin</i>' ?></p>

        </div>
    </div>

    <hr>

    <table class="table table-bordered mt-4">
        <thead class="thead-light">
            <tr>
                <th>Sản phẩm</th>
                <th>Khối lượng</th>
                <th>Số lượng</th>
                <th>Đơn giá</th>
                <th>Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total = 0;
            foreach ($items as $item):
                
                $subtotal = $item['quantity'] * $item['unitPrice'];
                $total += $subtotal;
            ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td><?= $item['weight'] ?></td>
                <td><?= $item['quantity'] ?></td>
                <td><?= number_format($item['unitPrice'], 0, ',', '.') ?>đ</td>
                <td><?= number_format($subtotal, 0, ',', '.') ?>đ</td>
            </tr>
            <?php endforeach; ?>
            <tr>
            <tr>
        <td colspan="4" class="text-right font-weight-bold">🛒 Tổng tiền sản phẩm:</td>
        <td class="text-dark"><?= number_format($order['total_amount'], 0, ',', '.') ?>đ</td>
        </tr>
        <tr>
        <td colspan="4" class="text-right font-weight-bold">🚚 Phí vận chuyển:</td>
        <td class="text-dark"><?= number_format($order['shipping_fee'], 0, ',', '.') ?>đ</td>
        </tr>
        <tr>
        <td colspan="4" class="text-right font-weight-bold">🏷️ Mã giảm giá:</td>
        <td class="text-success">-<?= number_format($order['discount_amount'], 0, ',', '.') ?>đ</td>
        </tr>
        <tr>
        <td colspan="4" class="text-right font-weight-bold text-danger">💰 Tổng thanh toán:</td>
        <td class="text-danger font-weight-bold"><?= number_format($order['final_total'], 0, ',', '.') ?>đ</td>
        </tr>


        </tbody>
    </table>

    <form method="POST" class="mt-4">
        <div class="form-group">
            <label for="new_status"><strong>📝 Cập nhật trạng thái:</strong></label>
            <select name="new_status" class="form-control w-50" required>
                <option value="">-- Chọn trạng thái --</option>
                <?php
               $statusOptions = [
                'Chờ xác nhận',
    'Đã xác nhận',
    'Đang chuẩn bị hàng',
    'Đang giao hàng',
    'Đã giao hàng',
    'Hoàn tất',
    'Đã hủy',
    'Trả hàng/Hoàn tiền',
    'Thất bại'
];

                foreach ($statusOptions as $st) {
                    $selected = ($currentStatus && $currentStatus['status'] == $st) ? 'selected' : '';
                    echo "<option value=\"$st\" $selected>$st</option>";
                }
                ?>
            </select>
        </div>
        <div id="cancelReasonGroup" style="display:none;" class="form-group">
            <label>Lý do hủy đơn:</label>
            <textarea name="cancel_reason" id="cancel_reason" rows="3" class="form-control" placeholder="Nhập lý do..."></textarea>
        </div>

        <button type="submit" class="btn btn-success" id="submitBtn"> Cập nhật trạng thái</button>
        <a href="listorder.php" class="btn btn-secondary ml-2">← Quay lại danh sách</a>
    </form>
</div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const statusSelect = document.querySelector('[name="new_status"]');
    const reasonGroup = document.getElementById('cancelReasonGroup');
    const reasonInput = document.getElementById('cancel_reason');
    const submitBtn = document.getElementById('submitBtn');

    // Lấy trạng thái hiện tại và danh sách thứ tự từ PHP đã khai báo ở PHP
    const currentStatus = <?= json_encode($currentStatusName) ?>;
    const statusOrderList = <?= json_encode(array_keys($statusOrder)) ?>;

    // Hiển thị/ẩn lý do hủy khi chọn
    statusSelect.addEventListener('change', function () {
        const selected = this.value;
        if (selected === 'Đã hủy') {
            reasonGroup.style.display = 'block';
            reasonInput.required = true;
            submitBtn.textContent = 'Gửi';
            submitBtn.classList.remove('btn-success');
            submitBtn.classList.add('btn-danger');
        } else {
            reasonGroup.style.display = 'none';
            reasonInput.required = false;
            submitBtn.textContent = 'Cập nhật trạng thái';
            submitBtn.classList.remove('btn-danger');
            submitBtn.classList.add('btn-success');
        }
    });

    // Xác nhận trước khi gửi, và kiểm tra ngược trạng thái client-side
    document.querySelector('form').addEventListener('submit', function (e) {
        e.preventDefault();

        const selectedStatus = statusSelect.value;
        const currentIndex = statusOrderList.indexOf(currentStatus);
        const selectedIndex = statusOrderList.indexOf(selectedStatus);

        // Kiểm tra cập nhật ngược (client-side) — để UX tốt hơn; server vẫn kiểm tra lại
        if (selectedIndex < currentIndex && selectedStatus !== 'Đã hủy' && selectedStatus !== 'Trả hàng/Hoàn tiền') {
            Swal.fire({
                icon: 'error',
                title: 'Không thể cập nhật',
                text: `Không thể cập nhật từ "${currentStatus}" về "${selectedStatus}".`
            });
            return;
        }

        // Nếu hủy, bắt buộc có lý do
        if (selectedStatus === 'Đã hủy' && reasonInput.value.trim() === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Thiếu lý do',
                text: 'Vui lòng nhập lý do hủy đơn.'
            });
            return;
        }

        // Hiển thị confirm
        Swal.fire({
            title: 'Xác nhận cập nhật trạng thái?',
            text: `Bạn có chắc muốn chuyển đơn #<?= $order_ID ?> sang trạng thái "${selectedStatus}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                e.target.submit();
            }
        });
    });
</script>



</body>
</html>
