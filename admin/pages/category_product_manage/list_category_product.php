<?php
require_once('../../../helpers/startSession.php');
startRoleSession('admin');
require_once('../../../database/dbhelper.php');

// Xử lý phân trang
$page = isset($_GET['page']) && $_GET['page'] > 0 ? intval($_GET['page']) : 1;
$limit = 10;
$start = ($page - 1) * $limit;


$countSql = "SELECT COUNT(*) AS total FROM Category WHERE is_deleted = 0";


$result = executeSingleResult($countSql);
$total = $result['total'] ?? 0;
$totalPages = ceil($total / $limit);


$sql = "SELECT * FROM Category WHERE is_deleted = 0 ORDER BY created_at DESC LIMIT $start, $limit";


$categoryList = executeResult($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản Lý Danh Mục Sản Phẩm</title>
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
        .inactive-cell { 
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
                <i class="fas fa-plus"></i> Thêm mới
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            Danh sách danh mục sản phẩm
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
                $index = $start + 1;
                if (empty($categoryList)) {
                    echo '<tr><td colspan="4" class="text-danger py-4">Không có danh mục nào.</td></tr>';
                } else {
                    foreach ($categoryList as $item) {
                        
                        $isDeleted = $item['is_deleted'] == 1;
                        $badge = $isDeleted 
                            ? '<span class="badge badge-secondary"><i class="fas fa-ban"></i> Đã vô hiệu hóa</span>'
                            : '<span class="badge badge-success"><i class="fas fa-check"></i> Đang hoạt động</span>';
                        ?>
                        <tr>
                            <td><?= $index++ ?></td>
                            <td class="text-left pl-3 <?= $isDeleted ? 'inactive-cell' : '' ?>"><?= htmlspecialchars($item['category_name']) ?></td>
                            <td class="<?= $isDeleted ? 'inactive-cell' : '' ?>"><?= $badge ?></td>
                            <td>
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="add.php?category_ID=<?= htmlspecialchars($item['category_ID']) ?>" class="btn btn-edit btn-sm">
                                        <i class="fas fa-edit"></i> Sửa
                                    </a>
                                    <div class="dropdown position-static">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                            Tuỳ chọn
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="#" onclick="toggleCategory(<?= htmlspecialchars($item['category_ID']) ?>, <?= $isDeleted ? 0 : 1 ?>)">
                                                <?= $isDeleted
                                                    ? '<i class="fas fa-check-circle text-success"></i> Kích hoạt lại'
                                                    : '<i class="fas fa-ban text-danger"></i> Vô hiệu hóa' ?>
                                            </a>
                                            </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php }
                } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <ul class="pagination justify-content-center">
            <?php
            // Lấy tổng số danh mục một lần nữa để đảm bảo phân trang hiển thị đúng
            for ($i = 1; $i <= $totalPages; $i++) {
                $query = $_GET;
                $query['page'] = $i;
                $link = '?' . http_build_query($query);
                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                          <a class="page-link" href="' . htmlspecialchars($link) . '">' . $i . '</a>
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

document.addEventListener('DOMContentLoaded', function() {
    <?php
    
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


function toggleCategory(id, status) {
    Swal.fire({
        title: 'Xác nhận thay đổi trạng thái?',
        text: status == 1 ? 'Bạn có muốn vô hiệu hóa danh mục này không?' : 'Bạn có muốn kích hoạt lại danh mục này không?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Đồng ý',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post("ajax.php", {
                action: "toggleCategory", 
                category_ID: id,
                status: status
            }, function(response) {
                try {
                    // Đảm bảo phản hồi là JSON và parse nó
                    const res = typeof response === 'string' ? JSON.parse(response) : response;
                    if (res.status === 'success') {
                        // Lưu thông báo vào sessionStorage và tải lại trang
                        sessionStorage.setItem('swal_alert', JSON.stringify({
                            type: 'success',
                            message: res.message || 'Thao tác thành công!'
                        }));
                        location.reload();
                    } else {
                        Swal.fire('Thất bại', res.message || 'Có lỗi xảy ra khi cập nhật.', 'error');
                    }
                } catch (e) {
                    Swal.fire('Lỗi', 'Lỗi phản hồi từ server. Vui lòng kiểm tra console.', 'error');
                    console.error("Lỗi parse JSON hoặc phản hồi không hợp lệ:", response, e);
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                Swal.fire('Lỗi', 'Yêu cầu AJAX thất bại: ' + textStatus + ', ' + errorThrown, 'error');
                console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
            });
        }
    });
}

</script>
</body>
</html>