<?php
require_once('../../helpers/startSession.php');
startRoleSession('staff');
require_once('../../database/dbhelper.php');


// Kiểm tra đăng nhập & vai trò nhân viên
if (!isset($_SESSION['account_ID']) || $_SESSION['role_ID'] != 2) {
    header('Location: ../../login/login.php');
    exit();
}

$order_ID = isset($_GET['order_ID']) ? intval($_GET['order_ID']) : 0;

// Lấy thông tin đơn hàng
$sql = "SELECT 
            o.*, 
            acc_customer.fullname AS customer_name,
            acc_customer.phone_number,
            acc_staff.fullname AS staff_name
        FROM Orders o
        JOIN Customer c ON o.customer_ID = c.customer_ID
        JOIN Account acc_customer ON c.account_ID = acc_customer.account_ID
        LEFT JOIN Staff st ON o.staff_ID = st.staff_ID
        LEFT JOIN Account acc_staff ON st.account_ID = acc_staff.account_ID
        WHERE o.order_ID = $order_ID";
$order = executeSingleResult($sql);
if (!$order) {
    echo '<div class="alert alert-danger">Không tìm thấy đơn hàng!</div>';
    exit();
}

// Lấy sản phẩm trong đơn
$items = executeResult("SELECT oi.*, p.product_name, p.weight 
    FROM OrderItem oi 
    JOIN Product p ON oi.product_ID = p.product_ID 
    WHERE oi.order_ID = $order_ID");

// Lấy trạng thái hiện tại mới nhất
$currentStatus = executeSingleResult("SELECT status FROM OrderStatusHistory 
    WHERE order_ID = $order_ID 
    ORDER BY changed_at DESC LIMIT 1");

// Định nghĩa flow trạng thái hợp lệ
$statusFlow = [
    'Chờ xác nhận' => 0,
    'Đã xác nhận' => 1,
    'Đang chuẩn bị hàng' => 2,
    'Đang giao hàng' => 3,
    'Hoàn tất' => 4,
    'Đã hủy' => 5
];

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
    $newStatus = $_POST['new_status'];
    if ($newStatus === 'Đã hủy') {
    echo '<div class="alert alert-danger">Bạn không có quyền cập nhật trạng thái sang "Đã hủy".</div>';
    exit();
}

    $now = date('Y-m-d H:i:s');
    $account_ID = $_SESSION['account_ID'];

    $staff = executeSingleResult("SELECT staff_ID FROM Staff WHERE account_ID = $account_ID");
    if (!$staff) {
        echo '<div class="alert alert-danger">Không tìm thấy nhân viên tương ứng.</div>';
        exit();
    }
    $staff_ID = $staff['staff_ID'];

    $currentStatusName = $currentStatus ? $currentStatus['status'] : $order['status'];

    if ($statusFlow[$newStatus] < $statusFlow[$currentStatusName]) {
        echo '<div class="alert alert-danger">Không thể cập nhật từ "' . $currentStatusName . '" về "' . $newStatus . '".</div>';
        exit();
    }

    if ($order['status'] === 'Chờ xác nhận' && $newStatus === 'Đã xác nhận') {
    execute("UPDATE Orders SET status = '$newStatus', staff_ID = $staff_ID WHERE order_ID = $order_ID");
} elseif ($newStatus === 'Hoàn tất') {
    execute("UPDATE Orders SET status = '$newStatus', completed_date = '$now' WHERE order_ID = $order_ID");
} else {
    execute("UPDATE Orders SET status = '$newStatus' WHERE order_ID = $order_ID");
}


    execute("INSERT INTO OrderStatusHistory (order_ID, status, staff_ID, changed_at) 
             VALUES ($order_ID, '$newStatus', $staff_ID, '$now')");

    $_SESSION['success_message'] = "Cập nhật trạng thái đơn hàng #$order_ID thành công!";
 header("Location: listorder.php?status=" . urlencode($newStatus));
    exit();

}
?>

<!-- HTML giao diện -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn hàng #<?= $order_ID ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
</head>
<body style="background: #f8f9fa; padding: 30px;">
<div class="container bg-white p-4 rounded shadow-sm">
    <h3>Chi tiết đơn hàng <span class="text-primary">#<?= $order_ID ?></span></h3>

    <div class="row mb-3">
        <div class="col-md-6">
            <p><strong>👤 Khách hàng:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
            <p><strong>📞 SĐT:</strong> <?= htmlspecialchars($order['phone_number']) ?></p>
        </div>
        <div class="col-md-6">
            <p><strong>📍 Địa chỉ giao hàng:</strong> <?= htmlspecialchars($order['delivery_address']) ?></p>
            <p><strong>📅 Ngày đặt:</strong> <?= date('H:i d/m/Y', strtotime($order['order_date'])) ?></p>
            <?php if (!empty($order['staff_name'])): ?>
                <p><strong>👨‍💼 Nhân viên phụ trách:</strong> <?= htmlspecialchars($order['staff_name']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <table class="table table-bordered">
        <thead class="thead-light text-center">
        <tr>
            <th>Sản phẩm</th>
            <th>Khối lượng</th>
            <th>Số lượng</th>
            <th>Đơn giá</th>
            <th>Thành tiền</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr class="text-center">
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td><?= $item['weight'] ?>g</td>
                <td><?= $item['quantity'] ?></td>
                <td><?= number_format($item['unitPrice'], 0, ',', '.') ?>đ</td>
                <td><?= number_format($item['unitPrice'] * $item['quantity'], 0, ',', '.') ?>đ</td>
            </tr>
        <?php endforeach; ?>
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
            <label><strong>📝 Cập nhật trạng thái:</strong></label>
            <select name="new_status" class="form-control w-50" required>
                <option value="">-- Chọn trạng thái --</option>
                <?php foreach ($statusFlow as $key => $val): ?>
    <?php if ($key === 'Đã hủy') continue; // Ẩn "Đã hủy" ?>
    <option value="<?= $key ?>" <?= ($currentStatus && $currentStatus['status'] == $key) ? 'selected' : '' ?>>
        <?= $key ?>
    </option>
<?php endforeach; ?>

            </select>
        </div>
        <button type="submit" class="btn btn-success">Cập nhật trạng thái</button>
        <a href="listorder.php" class="btn btn-secondary ml-2">← Quay lại danh sách</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function(){
    // Lấy dữ liệu trạng thái hiện tại từ PHP (fallback sang $order['status'] nếu không có lịch sử)
    const currentStatus = <?php echo json_encode($currentStatus ? $currentStatus['status'] : $order['status'], JSON_UNESCAPED_UNICODE); ?>;
    // Lấy statusFlow giống PHP để kiểm tra client-side (giữ nguyên thứ tự)
    const statusFlow = <?php echo json_encode($statusFlow, JSON_UNESCAPED_UNICODE); ?>;

    // Tìm form và select (không thay đổi HTML gốc)
    const form = document.querySelector('form[method="POST"]');
    if (!form) return;
    const select = form.querySelector('select[name="new_status"]');
    if (!select) return;

    // (Tuỳ chọn) khi user đổi select, nếu chọn trạng thái lùi thì báo ngay
    select.addEventListener('change', function () {
        const newStatus = select.value;
        if (!newStatus) return;
        if (typeof statusFlow[newStatus] === 'undefined' || typeof statusFlow[currentStatus] === 'undefined') return;
        if (statusFlow[newStatus] < statusFlow[currentStatus]) {
            Swal.fire({
                icon: 'warning',
                title: 'Không hợp lệ',
                html: `Không thể cập nhật từ "<strong>${currentStatus}</strong>" về "<strong>${newStatus}</strong>".`,
                confirmButtonText: 'Đã hiểu'
            });
        }
    });

    // Chặn submit mặc định, hiện hộp thoại xác nhận, trước khi gửi lên server
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const newStatus = select.value;
        if (!newStatus) {
            Swal.fire({
                icon: 'warning',
                title: 'Chưa chọn trạng thái',
                text: 'Vui lòng chọn trạng thái cần cập nhật.',
                confirmButtonText: 'Đã hiểu'
            });
            return;
        }

        // Kiểm tra client-side không cho lùi trạng thái
        if (typeof statusFlow[newStatus] === 'undefined' || typeof statusFlow[currentStatus] === 'undefined') {
            // nếu không xác định thì vẫn cho tiến (server sẽ kiểm tra tiếp)
        } else {
            if (statusFlow[newStatus] < statusFlow[currentStatus]) {
                Swal.fire({
                    icon: 'error',
                    title: 'Không thể cập nhật trạng thái',
                    html: `Không thể cập nhật từ "<strong>${currentStatus}</strong>" về "<strong>${newStatus}</strong>".`,
                    confirmButtonText: 'Đã hiểu'
                });
                return;
            }
        }

        // Xác nhận trước khi submit
        Swal.fire({
            title: 'Xác nhận cập nhật trạng thái?',
            html: `Bạn sắp cập nhật trạng thái từ <strong>${currentStatus}</strong> → <strong>${newStatus}</strong>.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Hủy',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Gọi submit chương trình (không kích hoạt lại event listener)
                form.submit();
            }
        });
    });
})();
</script>
</body>
</html>
