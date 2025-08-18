<?php
require_once('../../../database/dbhelper.php');
require_once('../../../helpers/startSession.php');
startRoleSession('admin');

// Lấy thông báo SweetAlert2 từ session nếu có, sau đó xóa nó
$swal_alert_data = null;
if (isset($_SESSION['swal_alert'])) {
    $swal_alert_data = $_SESSION['swal_alert'];
    unset($_SESSION['swal_alert']); // Rất quan trọng: xóa session sau khi đã lấy
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản Lý Voucher</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <style>
        .btn-sm {
            margin: 2px 0;
            width: 90px;
            white-space: nowrap;
        }
        .thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .table th, .table td {
            vertical-align: middle !important;
            white-space: nowrap; /* Prevent wrapping for better alignment, can adjust if content is too long */
        }
        .description-cell {
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: normal; /* Allow description to wrap if needed */
        }
        .detail-row {
            display: none;
            background-color: #f9f9f9 !important;
        }
        .gap-1 > * {
            margin-right: 4px;
        }
        .gap-1 > *:last-child {
            margin-right: 0;
        }
        /* Custom status badges */
        .badge-active { background-color: #28a745; color: white; } /* Success green */
        .badge-expired { background-color: #dc3545; color: white; } /* Danger red */
        .badge-limit { background-color: #ffc100; color: #343a40; } /* Warning yellow */
        .badge-disabled { background-color: #6c757d; color: white; } /* Secondary grey */
    </style>
</head>
<body>
<div class="container mt-4">
    <div>
        <a href="../index.php" class="btn btn-primary"><i class="fas fa-home"></i> Trang chủ</a>
        <a href="add.php" class="btn btn-success ml-2"><i class="fas fa-plus"></i> Thêm mới</a>
    </div>

    <?php /* if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success text-center"><?= $_SESSION['message'] ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; */ ?>

    <div class="card shadow-sm mb-0 mt-3">
        <div class="card-header text-white text-center" style="background-color: #00a0b0; padding: 20px;">
            <h4 class="mb-0">Quản lý Voucher</h4>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover text-center mb-0">
            <thead>
                <tr class="thead-light">
                    <th>STT</th>
                    <th>Ảnh</th>
                    <th>Mã code</th>
                    <th>Mô tả</th>
                    <th>Đã sử dụng</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $page = $_GET['page'] ?? 1;
                $limit = 10;
                $start = ($page - 1) * $limit;
                $now = date('Y-m-d H:i:s'); // Changed to include time for more precise comparison

                $sql = "SELECT * FROM Voucher ORDER BY created_at DESC LIMIT $start, $limit";
                $voucherList = executeResult($sql);
                $index = $start + 1;

                if (empty($voucherList)) {
                    echo '<tr><td colspan="8" class="text-center text-danger py-4">Không có voucher nào!</td></tr>';
                } else {
                    foreach ($voucherList as $item):
                        $isExpired = strtotime($item['end_date']) < strtotime($now); // Compare timestamps
                        $isOutOfUsage = $item['usage_limit'] !== null && $item['usage_count'] >= $item['usage_limit'];
                        $isDeleted = $item['is_deleted'] == 1;

                        $statusBadges = [];
                        $rowClass = '';

                        if ($isDeleted) {
                            $statusBadges[] = '<span class="badge badge-disabled"><i class="fas fa-ban"></i> Vô hiệu hóa</span>';
                            $rowClass = 'table-secondary';
                        } else {
                            if ($isExpired) {
                                $statusBadges[] = '<span class="badge badge-expired"><i class="fas fa-clock"></i> Hết hạn</span>';
                                $rowClass = 'table-danger';
                            }
                            if ($isOutOfUsage) {
                                $statusBadges[] = '<span class="badge badge-limit"><i class="fas fa-exclamation-triangle"></i> Hết lượt dùng</span>';
                                if (!$isExpired) $rowClass = 'table-warning'; // Yellow if not expired but out of usage
                            }
                            if (empty($statusBadges)) {
                                $statusBadges[] = '<span class="badge badge-active"><i class="fas fa-check-circle"></i> Đang hiệu lực</span>';
                            }
                        }

                        $statusHtml = implode(' ', $statusBadges); // Join multiple badges
                ?>
                <tr class="main-row <?= $rowClass ?>">
                    <td><?= $index++ ?></td>
                    <td>
                        <?php if (!empty($item['image_url'])): ?>
                            <img src="../../../images/uploads/vouchers/<?= htmlspecialchars($item['image_url']) ?>" class="thumbnail" alt="Voucher Image">
                        <?php else: ?>
                            <span class="text-muted">Không có</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($item['code']) ?></td>
                    <td class="description-cell"><?= strip_tags($item['description']) ?></td>
                    <td><?= $item['usage_count'] ?> / <?= $item['usage_limit'] ?? '∞' ?></td>
                    <td><?= $statusHtml ?></td>
                    <td>
                        <div class="d-flex justify-content-center gap-1">
                            <a href="add.php?voucher_ID=<?= htmlspecialchars($item['voucher_ID']) ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Sửa
                            </a>
                            <div class="dropdown position-static">
                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                    Tuỳ chọn
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item" href="#" onclick="toggleVoucher(<?= htmlspecialchars($item['voucher_ID']) ?>, <?= $isDeleted ? 0 : 1 ?>)">
                                        <?= $isDeleted
                                            ? '<i class="fas fa-check-circle text-success"></i> Kích hoạt lại'
                                            : '<i class="fas fa-ban text-danger"></i> Vô hiệu hóa' ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <button class="btn btn-info btn-sm" onclick="toggleDetail(<?= htmlspecialchars($item['voucher_ID']) ?>)">Chi tiết</button>
                    </td>
                </tr>
                <tr id="detail-<?= htmlspecialchars($item['voucher_ID']) ?>" class="detail-row">
                    <td colspan="8" class="text-left">
                        <strong>Kiểu giảm:</strong> <?= $item['discount_type'] === 'percent' ? 'Phần trăm (%)' : 'Số tiền (₫)' ?> |
                        <strong>Giá trị giảm:</strong> <?= $item['discount_type'] === 'percent'
                            ? number_format($item['discount_value']) . '%'
                            : number_format($item['discount_value']) . ' ₫' ?><br>
                        <strong>Giảm tối đa:</strong> <?= number_format($item['max_discount']) ?> ₫ |
                        <strong>Đơn tối thiểu:</strong> <?= number_format($item['min_order_amount']) ?> ₫<br>
                        <strong>Ngày bắt đầu:</strong> <?= date('H:i d/m/Y', strtotime($item['start_date'])) ?> |
                        <strong>Ngày kết thúc:</strong> <?= date('H:i d/m/Y', strtotime($item['end_date'])) ?><br>
                        <strong>Ngày tạo:</strong> <?= date('H:i d/m/Y', strtotime($item['created_at'])) ?><br>
                        <strong>Cập nhật lần cuối:</strong> <?= date('H:i d/m/Y', strtotime($item['updated_at'])) ?>
                    </td>
                </tr>
                <?php endforeach; } ?>
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        <ul class="pagination justify-content-center">
            <?php
            $result = executeSingleResult("SELECT COUNT(*) AS total FROM Voucher");
            $totalItems = $result['total'] ?? 0;
            $totalPages = ceil($totalItems / $limit);
            for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hiển thị SweetAlert2 nếu có thông báo từ PHP Session (ví dụ từ add.php)
    <?php if ($swal_alert_data): ?>
        Swal.fire({
            icon: '<?= $swal_alert_data['type'] ?>',
            title: 'Thông báo',
            text: '<?= $swal_alert_data['message'] ?>',
            confirmButtonText: 'Đóng'
        });
    <?php endif; ?>
});

function toggleVoucher(voucher_ID, status) {
    const actionText = status === 1 ? 'vô hiệu hóa' : 'kích hoạt lại';
    const confirmText = status === 1 ? 'Bạn có chắc chắn muốn vô hiệu hóa voucher này không?' : 'Bạn có chắc chắn muốn kích hoạt lại voucher này không?';
    const successMessage = status === 1 ? 'Voucher đã được vô hiệu hóa thành công!' : 'Voucher đã được kích hoạt lại thành công!';
    const iconType = status === 1 ? 'warning' : 'question'; // Dùng 'question' cho kích hoạt lại

    Swal.fire({
        title: `Xác nhận ${actionText}?`,
        text: confirmText,
        icon: iconType,
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Đồng ý',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('ajax.php', {
                voucher_ID: voucher_ID,
                status: status,
                action: 'toggle'
            }, function(response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.status === 'success') {
                        // Sử dụng SweetAlert2 để thông báo thành công và sau đó reload trang
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công!',
                            text: data.message || successMessage,
                            confirmButtonText: 'Đóng'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Thất bại!',
                            text: data.message || 'Có lỗi xảy ra khi cập nhật trạng thái voucher.',
                            confirmButtonText: 'Đóng'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi!',
                        text: 'Lỗi xử lý phản hồi từ server. Vui lòng kiểm tra console.',
                        confirmButtonText: 'Đóng'
                    });
                    console.error("Lỗi parse JSON hoặc phản hồi không hợp lệ:", response, e);
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi AJAX!',
                    text: 'Yêu cầu AJAX thất bại: ' + textStatus + ', ' + errorThrown,
                    confirmButtonText: 'Đóng'
                });
                console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
            });
        }
    });
}

function toggleDetail(id) {
    const detailRow = document.getElementById('detail-' + id);
    detailRow.style.display = (detailRow.style.display === 'none' || detailRow.style.display === '') ? 'table-row' : 'none';
}
</script>
</body>
</html>