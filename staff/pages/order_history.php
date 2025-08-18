<?php
require_once('../../helpers/startSession.php');
startRoleSession('staff');
require_once('../../database/dbhelper.php');


if (!isset($_SESSION['account_ID']) || $_SESSION['role_ID'] != 2) {
    header('Location: ../../login/login.php');
    exit();
}

$account_ID = $_SESSION['account_ID'];
$staff = executeSingleResult("SELECT staff_ID FROM Staff WHERE account_ID = $account_ID");
if (!$staff) {
    echo '<div class="alert alert-danger">Không tìm thấy nhân viên.</div>';
    exit;
}
$staff_ID = $staff['staff_ID'];

// Bộ lọc tìm kiếm
$keyword = trim($_GET['keyword'] ?? '');
$date = $_GET['date'] ?? '';

$where = "WHERE osh.staff_ID = $staff_ID";
if (!empty($keyword)) {
    $kw = addslashes($keyword);
    $where .= " AND (osh.order_ID LIKE '%$kw%' OR cacc.fullname LIKE '%$kw%')";
}
if (!empty($date)) {
    $where .= " AND DATE(osh.changed_at) = '$date'";
}

// Truy vấn lịch sử xử lý
$sql = "
    SELECT osh.*, acc.fullname AS staff_name, cacc.fullname AS customer_name, o.cancel_reason
    FROM OrderStatusHistory osh
    JOIN Orders o ON osh.order_ID = o.order_ID
    JOIN Customer c ON o.customer_ID = c.customer_ID
    JOIN Account cacc ON c.account_ID = cacc.account_ID
    JOIN Staff s ON osh.staff_ID = s.staff_ID
    JOIN Account acc ON s.account_ID = acc.account_ID
    $where
    ORDER BY osh.changed_at DESC
";
$history = executeResult($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch sử xử lý đơn hàng</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; padding: 30px; }
        .table thead { background-color: #0d6efd; color: white; }
    </style>
</head>
<body>
<div class="container">
    <!-- Nút Trang chủ -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-home"></i> Trang chủ
        </a>
        <h2 class="text-center flex-grow-1">🕘 Lịch sử xử lý đơn hàng</h2>
        <div style="width: 120px;"></div> <!-- giữ cho canh giữa -->
    </div>

    <!-- Bộ lọc -->
    <form class="row g-3 mb-4" method="GET">
        <div class="col-md-4">
            <input type="text" name="keyword" class="form-control" placeholder="Mã đơn hoặc tên khách hàng" value="<?= htmlspecialchars($keyword) ?>">
        </div>
        <div class="col-md-3">
            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>">
        </div>
        <div class="col-md-2">
            <button class="btn btn-success w-100" type="submit">Lọc</button>
        </div>
        <div class="col-md-2">
            <a href="order_history.php" class="btn btn-secondary w-100">Đặt lại</a>
        </div>
    </form>

    <!-- Bảng lịch sử -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="text-center">
                <tr>
                    <th>#Đơn</th>
                    <th>Khách hàng</th>
                    <th>Trạng thái</th>
                    <th>Lần cập nhật cuối</th>
                    <th>Ghi chú (Lí do hủy đơn)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)): ?>
                    <tr><td colspan="5" class="text-center">Không có dữ liệu.</td></tr>
                <?php else: ?>
                    <?php foreach ($history as $h): ?>
                        <tr class="text-center">
                            <td>#<?= $h['order_ID'] ?></td>
                            <td><?= htmlspecialchars($h['customer_name']) ?></td>
                            <td><?= htmlspecialchars($h['status']) ?></td>
                            <td><?= date('H:i d/m/Y', strtotime($h['changed_at'])) ?></td>
                            <td>
                                <?php if ($h['status'] === 'Đã hủy'): ?>
                                    <?= !empty($h['cancel_reason']) ? htmlspecialchars($h['cancel_reason']) : '<em class="text-muted">Không có lý do</em>' ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                <?php endif ?>
            </tbody>
        </table>
    </div>
</div>

<!-- FontAwesome icon (cho nút home) -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
