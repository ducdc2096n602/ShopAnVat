<?php
require_once('../../../../helpers/startSession.php');
startRoleSession('admin'); // Hàm này khả năng cao đã gọi session_start() bên trong.
require_once('../../../../database/dbhelper.php');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý nhân viên</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .btn-sm { margin: 2px 0; width: 90px; white-space: nowrap; }
        .action-buttons { display: flex; justify-content: center; gap: 0.25rem; }
        .table th, .table td { vertical-align: middle !important; white-space: nowrap; }
        .table-striped tbody tr:nth-of-type(odd) { background-color: #ffffff; }
        .table-striped tbody tr:nth-of-type(even) { background-color: #e0e0e0; }
        .table-hover tbody tr:hover { background-color: #cfe2ff; }
        .inactive-cell td:not(.action-buttons):not(.status-cell) {
            opacity: 0.5;
        }
        .dropdown-menu { z-index: 1050; }
        .btn-edit { background-color: #ffc107; color: #000; }
        /* Giới hạn chiều rộng cột trạng thái */
.table th:nth-child(7), 
.table td:nth-child(7) {
  max-width: 130px;
  width: 120px;
}

/* Giới hạn chiều rộng cột thao tác */
.table th:nth-child(8), 
.table td:nth-child(8) {
  max-width: 150px;
  width: 150px;
}

/* Cho phép text và nút co lại */
.table td {
  white-space: normal; /* Không ép một dòng ở 2 cột cuối */
}

.action-buttons {
  flex-wrap: wrap; /* Cho nút xuống hàng nếu chật */
  gap: 4px;
}

/* Viền bảng đậm */
.table-bordered th,
.table-bordered td {
  border: 2px solid #000 !important;
}

.table-bordered {
  border: 2px solid #000 !important;
}

/* Viền đậm cho các ô lọc */
.form-control {
  border: 2px solid #000 !important;
  box-shadow: none;
}

.form-control:focus {
  border-color: #000 !important;
  box-shadow: 0 0 3px rgba(0,0,0,0.5);
}

    </style>
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="../../index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Trang chủ
            </a>
            <a href="add.php" class="btn btn-success ml-2">
                <i class="fas fa-plus"></i> Thêm mới
            </a>
        </div>
        <form class="form-inline" method="GET">
            <input type="text" name="keyword" class="form-control mr-2" placeholder="Tìm theo tên, email, sđt" value="<?= $_GET['keyword'] ?? '' ?>">
            <select name="status" class="form-control mr-2">
                <option value="">-- Trạng thái --</option>
                <option value="1" <?= ($_GET['status'] ?? '') === '1' ? 'selected' : '' ?>>Đang hoạt động</option>
                <option value="2" <?= ($_GET['status'] ?? '') === '2' ? 'selected' : '' ?>>Vô hiệu hóa</option>
            </select>
            <input type="text" name="position" class="form-control mr-2" placeholder="Vị trí" value="<?= $_GET['position'] ?? '' ?>">
            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
        </form>
    </div>

    <?php
    // THAY THẾ KHỐI HIỂN THỊ THÔNG BÁO BẰNG SWEETALERT2
    if (isset($_SESSION['message'])) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: '{$_SESSION['message']}',
                showConfirmButton: false,
                timer: 3000
            });
        </script>";
        unset($_SESSION['message']); // Xóa thông báo sau khi đã hiển thị
    }
    // Hiển thị thông báo lỗi từ editstaff.php (nếu có, ví dụ không tìm thấy user để edit)
    if (isset($_SESSION['message_error'])) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: '{$_SESSION['message_error']}',
                confirmButtonText: 'Đóng'
            });
        </script>";
        unset($_SESSION['message_error']);
    }
    ?>

    <div class="table-responsive">
        <table class="table table-bordered table-hover table-striped text-center">
            <thead>
                <tr style="background-color: #e9ecef;">
                    <th colspan="9" class="text-center" style="padding: 20px 0; font-size: 22px;">Quản lý Nhân viên</th>
                </tr>
                <tr class="thead-light">
                    <th>STT</th>
                    <th>Username</th>
                    <th>Họ tên</th>
                    <th>SĐT</th>
                    <th>Email</th>
                    <th>Vị trí</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = 10;
                $start = ($page - 1) * $limit;

                $keyword = $_GET['keyword'] ?? '';
                $status = $_GET['status'] ?? '';
                $position = $_GET['position'] ?? '';

                $conditions = [];
                if (!empty($keyword)) {
                    $kw = addslashes($keyword);
                    $conditions[] = "(a.fullname LIKE '%$kw%' OR a.email LIKE '%$kw%' OR a.phone_number LIKE '%$kw%')";
                }
                if ($status !== '') {
                    $conditions[] = "a.status = " . intval($status);
                }
                if (!empty($position)) {
                    $pos = addslashes($position);
                    $conditions[] = "s.position LIKE '%$pos%'";
                }

                $whereSQL = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

                $sql = "SELECT s.*, a.username, a.fullname, a.phone_number, a.email, a.status, a.created_at
                        FROM Staff s
                        JOIN Account a ON s.account_ID = a.account_ID
                        $whereSQL
                        ORDER BY a.created_at DESC
                        LIMIT $start, $limit";
                $staffList = executeResult($sql);
                $index = $start + 1;

                if (empty($staffList)) {
                    echo '<tr><td colspan="8" class="text-danger">Không có nhân viên nào.</td></tr>';
                } else {
                    foreach ($staffList as $row):
                        $isInactive = $row['status'] == 2;
                        $badge = $isInactive
                            ? '<span class="badge badge-secondary"><i class="fas fa-ban"></i> Vô hiệu hóa</span>'
                            : '<span class="badge badge-success"><i class="fas fa-check"></i> Đang hoạt động</span>';
            ?>
                <tr id="row-<?= $row['account_ID'] ?>" class="<?= $isInactive ? 'inactive-cell' : '' ?>">
                    <td><?= $index++ ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                    <td><?= htmlspecialchars($row['phone_number']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['position']) ?></td>
                    <td class="status-cell"><?= $badge ?></td>
                    <td class="action-buttons">
                        <a href="editstaff.php?account_ID=<?= $row['account_ID'] ?>" class="btn btn-edit btn-sm">
                            <i class="fas fa-edit"></i> Sửa
                        </a>
                        <div class="dropdown position-static">
                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                Tuỳ chọn
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item toggle-btn" href="#"
                                   data-id="<?= $row['account_ID'] ?>"
                                   onclick="toggleStatus(<?= $row['account_ID'] ?>, <?= $isInactive ? 1 : 2 ?>)">
                                    <?= $isInactive
                                        ? '<i class="fas fa-check-circle text-success"></i> Kích hoạt lại'
                                        : '<i class="fas fa-ban text-danger"></i> Vô hiệu hóa' ?>
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; } ?>
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        <ul class="pagination justify-content-center">
        <?php
            $countQuery = "SELECT COUNT(*) AS total FROM Staff s JOIN Account a ON s.account_ID = a.account_ID $whereSQL";
            $countResult = executeSingleResult($countQuery);
            $total = $countResult['total'] ?? 0;
            $totalPages = ceil($total / $limit);

            $queryString = $_GET;
            unset($queryString['page']);

            for ($i = 1; $i <= $totalPages; $i++):
                $queryString['page'] = $i;
                $link = '?' . http_build_query($queryString);
        ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="<?= $link ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        </ul>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>

<script>
function toggleStatus(account_ID, newStatus) {
    $.post('ajax.php', {
        account_ID: account_ID,
        action: 'toggle_status',
        status: newStatus
    }, function(res) {
        if (res.status === 'success') {
            const row = $('#row-' + account_ID);
            const statusCell = row.find('.status-cell');
            const dropdownMenu = row.find('.dropdown-menu');

            if (newStatus === 1) {
                row.removeClass('inactive-cell');
                statusCell.html('<span class="badge badge-success"><i class="fas fa-check"></i> Đang hoạt động</span>');
                dropdownMenu.html(`
                    <a class="dropdown-item toggle-btn" href="#" onclick="toggleStatus(${account_ID}, 2)">
                        <i class="fas fa-ban text-danger"></i> Vô hiệu hóa
                    </a>
                `);
                // SweetAlert2 cho thông báo kích hoạt thành công
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: 'Tài khoản nhân viên đã được kích hoạt lại.',
                    showConfirmButton: false,
                    timer: 1500
                });
            } else {
                row.addClass('inactive-cell');
                statusCell.html('<span class="badge badge-secondary"><i class="fas fa-ban"></i> Vô hiệu hóa</span>');
                dropdownMenu.html(`
                    <a class="dropdown-item toggle-btn" href="#" onclick="toggleStatus(${account_ID}, 1)">
                        <i class="fas fa-check-circle text-success"></i> Kích hoạt lại
                    </a>
                `);
                // SweetAlert2 cho thông báo vô hiệu hóa thành công
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: 'Tài khoản nhân viên đã bị vô hiệu hóa.',
                    showConfirmButton: false,
                    timer: 1500
                });
            }
        } else {
            // SweetAlert2 cho thông báo thất bại
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: res.message || 'Thao tác thất bại. Vui lòng thử lại.',
                confirmButtonText: 'Đóng'
            });
        }
    }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
        Swal.fire({
            icon: 'error',
            title: 'Lỗi AJAX!',
            text: 'Không thể kết nối đến máy chủ hoặc có lỗi xảy ra: ' + textStatus,
            confirmButtonText: 'Đóng'
        });
    });
}
</script>
</body>
</html>