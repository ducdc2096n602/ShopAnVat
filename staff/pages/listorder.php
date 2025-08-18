<?php
require_once('../../helpers/startSession.php');
startRoleSession('staff');
require_once('../../database/dbhelper.php');

if (!isset($_SESSION['account_ID']) || $_SESSION['role_ID'] != 2) {
    header('Location: ../../login/login.php');
    exit();
}
if (!isset($_SESSION['staff_ID'])) {
    die('Không xác định được nhân viên đang đăng nhập. Vui lòng đăng nhập lại.');
}
$staff_ID = intval($_SESSION['staff_ID']); // không cần check isset nữa vì đã kiểm tra ở trên


$statusList = ['Chờ xác nhận', 'Đã xác nhận', 'Đang chuẩn bị hàng', 'Đang giao hàng', 'Hoàn tất', 'Đã hủy'];

$filterStatus = $_GET['status'] ?? '';
$searchKeyword = $_GET['search'] ?? '';
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$start = ($page - 1) * $limit;

$showDateWarning = false;
if (!empty($fromDate) && !empty($toDate) && strtotime($fromDate) > strtotime($toDate)) {
    $showDateWarning = true;
}

$staff_ID = isset($_SESSION['staff_ID']) ? intval($_SESSION['staff_ID']) : 0;

$where = 'WHERE 1';
$where .= " AND (
    (o.staff_ID IS NULL AND o.status = 'Chờ xác nhận') 
    OR o.staff_ID = $staff_ID
)";


if (!$showDateWarning) {
    if (!empty($filterStatus) && in_array($filterStatus, $statusList)) {
        $where .= " AND o.status = '$filterStatus'";
    }
    if (!empty($searchKeyword)) {
        $searchKeyword = addslashes($searchKeyword);
        $where .= " AND (a.fullname LIKE '%$searchKeyword%' OR a.phone_number LIKE '%$searchKeyword%')";
    }
    if (!empty($fromDate)) {
        $from = date('Y-m-d 00:00:00', strtotime($fromDate));
        $where .= " AND o.order_date >= '$from'";
    }
    if (!empty($toDate)) {
        $to = date('Y-m-d 23:59:59', strtotime($toDate));
        $where .= " AND o.order_date <= '$to'";
    }
}

$sql = "SELECT o.*, a.fullname, a.phone_number 
        FROM Orders o
        JOIN Customer c ON o.customer_ID = c.customer_ID
        JOIN Account a ON c.account_ID = a.account_ID
        $where
        ORDER BY o.order_date DESC
        LIMIT $start, $limit";
$orders = $showDateWarning ? [] : executeResult($sql);

$countSql = "SELECT COUNT(*) AS total 
             FROM Orders o
             JOIN Customer c ON o.customer_ID = c.customer_ID
             JOIN Account a ON c.account_ID = a.account_ID
             $where";
$result = $showDateWarning ? ['total' => 0] : executeSingleResult($countSql);
$total = $result['total'] ?? 0;
$total_pages = ceil($total / $limit);

$statusCountsSql = "SELECT o.status, COUNT(*) as count
                    FROM Orders o
                    JOIN Customer c ON o.customer_ID = c.customer_ID
                    JOIN Account a ON c.account_ID = a.account_ID
                    WHERE 
                        ( (o.staff_ID IS NULL AND o.status = 'Chờ xác nhận') OR o.staff_ID = $staff_ID )
                        " . 
                        ((!$showDateWarning && !empty($searchKeyword)) ? " AND (a.fullname LIKE '%$searchKeyword%' OR a.phone_number LIKE '%$searchKeyword%')" : "") . 
                        ((!$showDateWarning && !empty($fromDate)) ? " AND o.order_date >= '" . date('Y-m-d 00:00:00', strtotime($fromDate)) . "'" : "") . 
                        ((!$showDateWarning && !empty($toDate)) ? " AND o.order_date <= '" . date('Y-m-d 23:59:59', strtotime($toDate)) . "'" : "") .
                    " GROUP BY o.status";

$statusCountsRaw = executeResult($statusCountsSql);

// Chuyển thành mảng dễ truy cập
$statusCounts = [];
foreach ($statusCountsRaw as $row) {
    $statusCounts[$row['status']] = $row['count'];
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý đơn hàng</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
        }
        .card-header {
            background-color: #00a0b0;
            font-size: 1.2rem;
            font-weight: bold;
            text-align: center;
            padding: 20px;
            color: white;
        }
        .table th {
            background-color: #e9ecef;
        }
        .table td {
            vertical-align: middle !important;
        }
        .table-striped tbody tr:nth-child(odd) {
            background-color: #fff;
        }
        .table-striped tbody tr:nth-child(even) {
            background-color: #e0e0e0;
        }
        .status { font-weight: 500; }
        .status.pending   { color: orange; }
        .status.confirmed { color: #198754; }
        .status.preparing { color: #0dcaf0; }
        .status.shipping  { color: #0d6efd; }
        .status.completed { color: green; }
        .status.cancelled { color: red; }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="mb-3 d-flex justify-content-start gap-2">
        <a href="../pages/index.php" class="btn btn-primary">
            <i class="fas fa-home"></i> Trang chủ
        </a>
    </div>
    
<?php if (isset($_SESSION['success_message'])): ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'success',
                title: 'Thành công',
                text: '<?= addslashes($_SESSION['success_message']) ?>',
                confirmButtonText: 'OK',
                timer: 2500,
                timerProgressBar: true
            });
        });
    </script>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>


    <form method="GET" class="row g-3 align-items-end mb-2" id="filter-form">
        <div class="col-md-2">
            <label class="form-label">Trạng thái</label>
            <select name="status" id="status-filter" class="form-control">
                <option value="">-- Tất cả --</option>
                <?php foreach ($statusList as $status): ?>
                    <?php
                        $count = $statusCounts[$status] ?? 0;
                        $label = $count > 0 ? "$status ($count)" : $status;
                    ?>
                    <option value="<?= $status ?>" <?= $status === $filterStatus ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>

                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tìm khách hàng</label>
            <input type="text" name="search" class="form-control"
                   placeholder="Tên hoặc SĐT" value="<?= htmlspecialchars($searchKeyword) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Từ ngày</label>
            <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($fromDate) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Đến ngày</label>
            <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($toDate) ?>">
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-success w-100"><i class="fas fa-search"></i></button>
        </div>
        <div class="col-md-2">
            <a href="listorder.php" class="btn btn-secondary w-100">Đặt lại</a>
        </div>
    </form>

    <div class="alert alert-warning text-center <?= $showDateWarning ? '' : 'd-none' ?>" id="date-warning">
        ⚠️ Ngày bắt đầu không thể lớn hơn ngày kết thúc. Vui lòng chọn lại.
    </div>

    <div class="card shadow-sm mb-0">
        <div class="card-header">Danh sách đơn hàng</div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-bordered table-hover table-striped m-0">
                <thead class="thead-light text-center">
                <tr>
                    <th>#Đơn</th>
                    <th>Khách hàng</th>
                    <th>Ngày đặt</th>
                    <th>Trạng thái</th>
                    <th>Tổng thanh toán</th>
                    <th>Hành động</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="6" class="text-center">Không có đơn hàng nào phù hợp.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $order):
                        $statusClass = 'pending';
                        switch ($order['status']) {
                            case 'Đã xác nhận': $statusClass = 'confirmed'; break;
                            case 'Đang chuẩn bị hàng': $statusClass = 'preparing'; break;
                            case 'Đang giao hàng': $statusClass = 'shipping'; break;
                            case 'Hoàn tất': $statusClass = 'completed'; break;
                            case 'Đã hủy': $statusClass = 'cancelled'; break;
                        }
                        $orderID = $order['order_ID'];
                        $status = $order['status'];
                        $fromStatusParam = urlencode($filterStatus);
                        $detailLink = ($status === 'Chờ xác nhận') 
                            ? "approve_order.php?order_ID=$orderID&from_status=$fromStatusParam"
                            : "edit.php?order_ID=$orderID&from_status=$fromStatusParam";

                    ?>
                        <tr class="text-center">
                            <td>#<?= $orderID ?></td>
                            <td class="text-left pl-3">
                                <?= htmlspecialchars($order['fullname']) ?><br>
                                <small><?= $order['phone_number'] ?></small>
                            </td>
                            <td><?= date('H:i d/m/Y', strtotime($order['order_date'])) ?></td>
                            <td><span class="status <?= $statusClass ?>"><?= $status ?></span></td>
                            <td class="text-end"><?= number_format($order['final_total'], 0, ',', '.') ?> VNĐ</td>
                            <td>
                                <a href="edit.php?order_ID=<?= $order['order_ID'] ?>&from_status=<?= urlencode($filterStatus) ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Xem
                                </a>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($total_pages > 1): ?>
        <ul class="pagination justify-content-center mt-3">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    <?php endif; ?>
</div>

<!-- JS tự động submit + kiểm tra ngày -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const statusSelect = document.getElementById('status-filter');
    const form = document.getElementById('filter-form');

    if (statusSelect) {
        statusSelect.addEventListener('change', function () {
            form.submit();
        });
    }

    const fromInput = document.querySelector('input[name="from_date"]');
    const toInput = document.querySelector('input[name="to_date"]');
    const warning = document.getElementById('date-warning');

    function validateDates() {
        if (fromInput.value && toInput.value) {
            const from = new Date(fromInput.value);
            const to = new Date(toInput.value);
            if (from > to) {
                warning.classList.remove('d-none');
            } else {
                warning.classList.add('d-none');
            }
        }
    }

    fromInput.addEventListener('change', validateDates);
    toInput.addEventListener('change', validateDates);
});
</script>
</body>
</html>
