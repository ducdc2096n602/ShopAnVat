<?php
require_once('../../../helpers/startSession.php');
startRoleSession('admin');
require_once('../../../database/dbhelper.php');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản Lý Danh Mục Tin Tức</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
        }
        .card-header {
            background-color: #00a0b0;
            color: white;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
            padding: 20px;
        }
        .btn i {
            margin-right: 5px;
        }
        .btn-edit {
            background-color: #ffc107;
            color: #000;
        }
        .table td {
            vertical-align: middle !important;
        }
        .table th {
            background-color: #e9ecef;
        }
        .table-striped tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }
        .table-striped tbody tr:nth-child(even) {
            background-color: #e0e0e0;
        }
        .inactive {
            opacity: 0.5;
        }
        .gap-1 > * {
            margin-right: 0.25rem;
        }
        .gap-1 > *:last-child {
            margin-right: 0;
        }
        .dropdown-menu {
            z-index: 1050;
        }
        .d-flex.justify-content-center.gap-1 {
            gap: 4px;
        }

        .btn-edit.btn-sm,
        .btn-secondary.btn-sm.dropdown-toggle {
            padding: 6px 12px;
            font-size: 14px;
            height: 36px;
            min-width: 90px;
            line-height: 1.2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="../index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Trang chủ
            </a>
            <a href="add.php" class="btn btn-success ml-2">
                <i class="fas fa-plus"></i> Thêm mới danh mục
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            Danh sách danh mục tin tức
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover table-striped m-0 text-center">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 60px;">STT</th>
                        <th class="text-left">Tên danh mục</th>
                        <th style="width: 160px;">Trạng thái</th>
                        <th style="width: 180px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    $page = isset($_GET['page']) && $_GET['page'] > 0 ? intval($_GET['page']) : 1;
                    $limit = 10;
                    $start = ($page - 1) * $limit;

                    $sql = "SELECT * FROM CategoryNews LIMIT $start, $limit";
                    $categoryList = executeResult($sql);
                    $index = $start + 1;

                    if (empty($categoryList)) {
                        echo '<tr><td colspan="4" class="text-danger">Không có danh mục nào.</td></tr>';
                    } else {
                        foreach ($categoryList as $item) {
                            $id = $item['CategoryNews_ID'];
                            $isDeleted = $item['is_deleted'] == 1;
                            $badge = $isDeleted 
                                ? '<span class="badge badge-secondary"><i class="fas fa-ban"></i> Đã vô hiệu hóa</span>'
                                : '<span class="badge badge-success"><i class="fas fa-check"></i> Đang hoạt động</span>';

                            echo '<tr>
                                    <td>' . $index++ . '</td>
                                    <td class="text-left pl-3 '. ($isDeleted ? 'inactive' : '') .'">' . htmlspecialchars($item['name']) . '</td>
                                    <td>' . $badge . '</td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="add.php?CategoryNews_ID=' . $id . '" class="btn btn-edit btn-sm">
                                                <i class="fas fa-edit"></i> Sửa
                                            </a>
                                            <div class="dropdown position-static">
                                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                                    Tuỳ chọn
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a class="dropdown-item toggle-btn" href="#"
                                                        data-id="' . $id . '"
                                                        onclick="toggleCategoryNews(' . $id . ', ' . ($isDeleted ? 0 : 1) . ')">
                                                        ' . ($isDeleted
                                                            ? '<i class="fas fa-check-circle text-success"></i> Kích hoạt lại'
                                                            : '<i class="fas fa-ban text-danger"></i> Vô hiệu hóa') . '
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>';
                        }
                    }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <ul class="pagination justify-content-center">
            <?php
                $countSql = "SELECT COUNT(*) AS total FROM CategoryNews";
                $result = executeSingleResult($countSql);
                $total = $result['total'] ?? 0;
                $totalPages = ceil($total / $limit);

                $query = $_GET;
                unset($query['page']);

                for ($i = 1; $i <= $totalPages; $i++) {
                    $query['page'] = $i;
                    $link = '?' . http_build_query($query);
                    echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                            <a class="page-link" href="' . $link . '">' . $i . '</a>
                        </li>';
                }
            ?>
        </ul>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function toggleCategoryNews(id, status) {
    // Sử dụng SweetAlert2 cho xác nhận xóa/vô hiệu hóa
    Swal.fire({
        title: 'Xác nhận hành động',
        text: status == 1 ? "Bạn có chắc chắn muốn vô hiệu hóa danh mục này không?" : "Bạn có chắc chắn muốn kích hoạt lại danh mục này không?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Đồng ý',
        cancelButtonText: 'Hủy bỏ'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post("ajax.php", {
                action: "toggle",
                CategoryNews_ID: id,
                status: status
            }, function(data) {
                if (data.trim() === 'success') {
                    const row = $('.toggle-btn[data-id="' + id + '"]').closest('tr');
                    const statusCell = row.find('td:nth-child(3)');
                    const nameCell = row.find('td:nth-child(2)');
                    const dropdownItem = row.find('.toggle-btn'); // Lấy phần tử <a> trong dropdown

                    if (status == 1) { // Đã vô hiệu hóa
                        statusCell.html('<span class="badge badge-secondary"><i class="fas fa-ban"></i> Đã vô hiệu hóa</span>');
                        nameCell.addClass('inactive');
                        dropdownItem.html('<i class="fas fa-check-circle text-success"></i> Kích hoạt lại');
                        dropdownItem.attr('onclick', 'toggleCategoryNews(' + id + ', 0)');
                        Swal.fire(
                            'Đã vô hiệu hóa!',
                            'Danh mục đã được vô hiệu hóa.',
                            'success'
                        );
                    } else { // Đã kích hoạt lại
                        statusCell.html('<span class="badge badge-success"><i class="fas fa-check"></i> Đang hoạt động</span>');
                        nameCell.removeClass('inactive');
                        dropdownItem.html('<i class="fas fa-ban text-danger"></i> Vô hiệu hóa');
                        dropdownItem.attr('onclick', 'toggleCategoryNews(' + id + ', 1)');
                        Swal.fire(
                            'Đã kích hoạt!',
                            'Danh mục đã được kích hoạt lại.',
                            'success'
                        );
                    }
                } else {
                    Swal.fire(
                        'Lỗi!',
                        'Có lỗi xảy ra khi cập nhật trạng thái.',
                        'error'
                    );
                }
            }).fail(function() { // Handle AJAX failure
                Swal.fire(
                    'Lỗi!',
                    'Không thể kết nối đến máy chủ để cập nhật trạng thái.',
                    'error'
                );
            });
        }
    });
}

// Đoạn script này để hiển thị SweetAlert2 từ session, được đặt ở cuối body
$(document).ready(function() {
    <?php
    // Hiển thị thông báo SweetAlert2 nếu có trong session
    if (isset($_SESSION['swal_alert'])) {
        $swal_type = $_SESSION['swal_alert']['type'];
        $swal_message = $_SESSION['swal_alert']['message'];
        echo "Swal.fire({
            icon: '{$swal_type}',
            title: 'Thông báo',
            text: '{$swal_message}',
            confirmButtonText: 'Đóng'
        });";
        unset($_SESSION['swal_alert']); // Xóa biến session sau khi hiển thị
    }
    ?>
});
</script>

</body>
</html>