<?php 
require_once('../../../helpers/startSession.php');
startRoleSession('admin');
require_once('../../../database/dbhelper.php');


$swal_alert_data = null;
if (isset($_SESSION['swal_alert'])) { 
    $swal_alert_data = $_SESSION['swal_alert'];
    unset($_SESSION['swal_alert']);
}

// Kiểm tra nếu có chọn danh mục để lọc
$categoryFilter = '';
if (isset($_GET['category_ID']) && $_GET['category_ID'] > 0) {
    $categoryId = intval($_GET['category_ID']);
    $categoryFilter = " AND p.category_ID = " . $categoryId;
}

// Cấu hình phân trang
$page = isset($_GET['page']) && $_GET['page'] > 0 ? intval($_GET['page']) : 1;
$limit = 10;
$start = ($page - 1) * $limit;

// Lấy tổng số sản phẩm để phân trang 
$countSql = "SELECT COUNT(*) AS total FROM product p WHERE 1=1" . $categoryFilter;
$result = executeSingleResult($countSql);
$total = $result['total'] ?? 0;
$total_pages = ceil($total / $limit);

$sql = "SELECT p.*, c.category_name, pi.image_url
        FROM product p 
        LEFT JOIN category c ON p.category_ID = c.category_ID
        LEFT JOIN productimage pi ON p.product_ID = pi.product_ID AND pi.is_primary = 1
        WHERE 1=1" . $categoryFilter . " 
        ORDER BY p.updated_at DESC, p.product_ID DESC
        LIMIT $start, $limit";
$productList = executeResult($sql);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản Lý Sản Phẩm</title>
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
        .thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
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
                <i class="fas fa-plus"></i> Thêm sản phẩm
            </a>
        </div>
        <form method="GET" action="" class="d-flex">
            <select name="category_ID" class="form-control" onchange="this.form.submit()">
                <option value="">Tất cả danh mục</option>
                <?php
                  $categorySql = "SELECT * FROM category ORDER BY category_name ASC";
                  $categories = executeResult($categorySql);
                  foreach ($categories as $category) {
                    $selected = (isset($_GET['category_ID']) && $_GET['category_ID'] == $category['category_ID']) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($category['category_ID']) . '" ' . $selected . '>' . htmlspecialchars($category['category_name']) . '</option>';
                  }
                ?>
            </select>
        </form>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            Danh sách sản phẩm
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover table-striped m-0 text-center">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 60px;">STT</th>
                        <th>Ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Giá (VNĐ)</th>
                        <th>Danh mục</th>
                        <th>Trạng thái</th>
                        <th style="width: 200px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    $index = $start + 1;
                    if (empty($productList)) {
                        echo '<tr><td colspan="7" class="text-danger py-4">Không có sản phẩm nào.</td></tr>';
                    } else {
                        foreach ($productList as $item) {
                            $isDeleted = $item['is_deleted'] == 1; 
                            $badge = $isDeleted 
                                ? '<span class="badge badge-secondary"><i class="fas fa-ban"></i> Đã ẩn</span>'
                                : '<span class="badge badge-success"><i class="fas fa-check"></i> Đang bày bán</span>';

                            // Logic kiểm tra và hiển thị ảnh
                            $image_filename = isset($item['image_url']) ? trim($item['image_url']) : '';

                            if (!empty($image_filename)) {
                                $image_src = '../../../images/uploads/product/' . $image_filename;
                            } else {
                                $image_src = 'https://via.placeholder.com/60x60?text=No+Image';
                            }

                            
                            echo '<tr>
                                    <td>' . $index++ . '</td>
                                    <td><img src="' . $image_src . '" class="thumbnail" alt="' . htmlspecialchars($item['product_name']) . '"></td>
                                    <td class="text-left pl-3 ' . ($isDeleted ? 'inactive-cell' : '') . '">' . htmlspecialchars($item['product_name']) . '</td>
                                    <td class="' . ($isDeleted ? 'inactive-cell' : '') . '">' . number_format($item['base_price'], 0, ',', '.') . '</td>
                                    <td class="' . ($isDeleted ? 'inactive-cell' : '') . '">' . htmlspecialchars($item['category_name']) . '</td>
                                    <td class="' . ($isDeleted ? 'inactive-cell' : '') . '">' . $badge . '</td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="add.php?product_ID=' . htmlspecialchars($item['product_ID']) . '" class="btn btn-edit btn-sm">
                                                <i class="fas fa-edit"></i> Sửa
                                            </a>
                                            <div class="dropdown position-static">
                                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                                    Tuỳ chọn
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a class="dropdown-item" href="#" onclick="toggleProduct(' . htmlspecialchars($item['product_ID']) . ', ' . ($isDeleted ? 0 : 1) . ')">
                                                        ' . ($isDeleted
                                                            ? '<i class="fas fa-check-circle text-success"></i> Kích hoạt lại'
                                                            : '<i class="fas fa-ban text-danger"></i> Xóa sản phẩm') . '
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
            for ($i = 1; $i <= $total_pages; $i++) {
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
    const swalAlertFromSessionStorage = sessionStorage.getItem('swal_alert');
    if (swalAlertFromSessionStorage) {
        const alertData = JSON.parse(swalAlertFromSessionStorage);
        Swal.fire({
            icon: alertData.type,
            title: 'Thông báo',
            text: alertData.message,
            confirmButtonText: 'Đóng'
        });
        sessionStorage.removeItem('swal_alert'); 
    }

    <?php if ($swal_alert_data): ?>
        Swal.fire({
            icon: '<?= $swal_alert_data['type'] ?>',
            title: 'Thông báo',
            text: '<?= $swal_alert_data['message'] ?>',
            confirmButtonText: 'Đóng'
        });
    <?php endif; ?>
});

function toggleProduct(id, status) {
    const actionText = status == 1 ? 'vô hiệu hóa' : 'kích hoạt lại';
    const confirmText = status == 1 ? 'Bạn có muốn vô hiệu hóa sản phẩm này không?' : 'Bạn có muốn kích hoạt lại sản phẩm này không?';
    const successMessage = status == 1 ? 'Sản phẩm đã bị vô hiệu hóa!' : 'Sản phẩm đã được kích hoạt lại!';
    const iconType = status == 1 ? 'warning' : 'question'; 

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
           
            $.post("ajax.php", { 
                action: "toggle", 
                product_ID: id,
                status: status
            }, function(response) {
                try {
                    const res = typeof response === 'string' ? JSON.parse(response) : response;
                    if (res.status === 'success') {
                        sessionStorage.setItem('swal_alert', JSON.stringify({
                            type: 'success',
                            message: res.message || successMessage
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