<?php
require_once('../../../helpers/startSession.php');
startRoleSession('admin'); 
require_once('../../../database/dbhelper.php');

// Danh sách danh mục tin tức chỉ lấy những danh mục chưa bị xóa
$categories = executeResult("SELECT * FROM CategoryNews WHERE is_deleted = 0 ORDER BY name ASC");

// Xử lý phân trang và lọc
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$start = ($page - 1) * $limit;

$categoryFilter = $_GET['category_ID'] ?? '';
$where = 'WHERE 1=1 '; // Điều kiện mặc định
if (!empty($categoryFilter)) {
    $where .= "AND n.CategoryNews_ID = " . intval($categoryFilter) . " ";
}



// Lấy tổng số bản ghi cho phân trang
$countWhere = 'WHERE 1=1 '; // Điều kiện riêng cho đếm tổng số bản ghi
if (!empty($categoryFilter)) {
    $countWhere .= "AND n.CategoryNews_ID = " . intval($categoryFilter) . " ";
}

$countSQL = "SELECT COUNT(*) AS total FROM News n $countWhere";
$countResult = executeSingleResult($countSQL);
$totalItems = $countResult ? $countResult['total'] : 0;
$totalPages = ceil($totalItems / $limit);


// Truy vấn tin tức kèm tên danh mục
$sql = "SELECT n.*, c.name AS category_news_name 
        FROM News n 
        LEFT JOIN CategoryNews c ON n.CategoryNews_ID = c.CategoryNews_ID
        $where
        ORDER BY n.created_at DESC
        LIMIT $start, $limit";
$newsList = executeResult($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản Lý Tin Tức</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .card-header { background-color: #00a0b0; color: white; padding: 20px; font-weight: bold; }
        .thumbnail { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
        .table th, .table td { vertical-align: middle !important; }
        .inactive-cell { opacity: 0.5; }
        .gap-1 > * { margin-right: 0.25rem; }
        .gap-1 > *:last-child { margin-right: 0; }
        .dropdown-menu { z-index: 1050; }
        .table-striped tbody tr:nth-child(odd) { background-color: #ffffff; }
        .table-striped tbody tr:nth-child(even) { background-color: #e0e0e0; }
        .table-hover tbody tr:hover { background-color: #cfe2ff; }
        .btn-edit {
            background-color: #ffc107;
            color: #000;
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
                <i class="fas fa-plus"></i> Thêm tin tức mới
            </a>
        </div>
        <form method="GET" class="form-inline">
            <label class="mr-2 font-weight-bold">Danh mục:</label>
            <select name="category_ID" class="form-control mr-2" onchange="this.form.submit()">
                <option value="">-- Tất cả --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['CategoryNews_ID']) ?>" <?= ($categoryFilter == $cat['CategoryNews_ID']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="card shadow-sm">
        <div class="card-header text-center">
            Danh sách tin tức
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover table-striped text-center m-0">
                <thead class="thead-light">
                    <tr>
                        <th width="60px">STT</th>
                        <th width="70px">Ảnh</th>
                        <th width="200px">Tiêu đề</th>
                        <th width="150px">Danh mục</th>
                        <th>Nội dung</th>
                        <th width="180px">Trạng thái</th>
                        <th width="200px">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($newsList)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-danger py-4">Không có tin tức nào được tìm thấy.</td>
                        </tr>
                    <?php else: ?>
                        <?php
                        $index = $start + 1;
                        foreach ($newsList as $item):
                            $isDeleted = $item['is_deleted'] == 1; // Giả định cột 'is_deleted' tồn tại
                            $badge = $isDeleted 
                                ? '<span class="badge badge-secondary"><i class="fas fa-ban"></i> Đã vô hiệu hóa</span>'
                                : '<span class="badge badge-success"><i class="fas fa-check"></i> Đang hoạt động</span>';
                        ?>
                        <tr>
                            <td><?= $index++ ?></td>
                            <td>
                                <?php if (!empty($item['thumbnail'])): ?>
                                    <img src="/ShopAnVat/images/uploads/newsupload/<?= htmlspecialchars($item['thumbnail']) ?>" class="thumbnail" alt="Thumbnail">
                                <?php else: ?>
                                    Không ảnh
                                <?php endif; ?>
                            </td>
                            <td class="text-left pl-3 <?= $isDeleted ? 'inactive-cell' : '' ?>"><?= htmlspecialchars($item['title']) ?></td>
                            <td class="text-center <?= $isDeleted ? 'inactive-cell' : '' ?>"><?= htmlspecialchars($item['category_news_name']) ?></td>
                            <td class="text-left"><?= htmlspecialchars(mb_substr(strip_tags($item['content']), 0, 100)) ?>...</td>
                            <td><?= $badge ?></td>
                            <td>
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="add.php?news_ID=<?= htmlspecialchars($item['news_ID']) ?>" class="btn btn-edit btn-sm">
                                        <i class="fas fa-edit"></i> Sửa
                                    </a>
                                    <div class="dropdown position-static">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                            Tuỳ chọn
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="#" onclick="toggleNews(<?= htmlspecialchars($item['news_ID']) ?>, <?= $isDeleted ? 0 : 1 ?>)">
                                                <?= $isDeleted
                                                    ? '<i class="fas fa-check-circle text-success"></i> Kích hoạt lại'
                                                    : '<i class="fas fa-ban text-danger"></i> Vô hiệu hóa' ?>
                                            </a>
                                            </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <ul class="pagination justify-content-center">
            <?php
            // Hiển thị nút phân trang
            for ($i = 1; $i <= $totalPages; $i++):
                $query = $_GET;
                $query['page'] = $i;
                $link = '?' . http_build_query($query);
            ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($link) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

$(document).ready(function() {
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
        unset($_SESSION['swal_alert']);
    }
    ?>
});


function toggleNews(news_ID, status) {
    Swal.fire({
        title: 'Xác nhận thay đổi trạng thái?',
        text: status == 1 ? 'Bạn có muốn vô hiệu hóa tin tức này không?' : 'Bạn có muốn kích hoạt lại tin tức này không?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Đồng ý',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('ajax.php', {
                news_ID: news_ID,
                action: 'toggle',
                status: status
            }, function(response) {
                try {
                    const res = typeof response === 'string' ? JSON.parse(response) : response;
                    if (res.status === 'success') {
                        // Lưu thông báo vào session và tải lại trang
                        sessionStorage.setItem('swal_alert', JSON.stringify({
                            type: 'success',
                            message: res.message || 'Thao tác thành công!'
                        }));
                        location.reload(); 
                    } else {
                        Swal.fire('Thất bại', res.message || 'Thao tác thất bại.', 'error');
                    }
                } catch (e) {
                    Swal.fire('Lỗi', 'Lỗi phản hồi từ server.', 'error');
                    console.error(response);
                }
            });
        }
    });
}


function deleteNews(news_ID) {
    Swal.fire({
        title: 'Bạn có chắc chắn muốn xóa?',
        text: 'Bạn sẽ không thể khôi phục tin tức này sau khi xóa!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Xóa ngay!',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('ajax.php', {
                news_ID: news_ID,
                action: 'delete'
            }, function(response) {
                try {
                    const res = typeof response === 'string' ? JSON.parse(response) : response;
                    if (res.status === 'success') {
                        sessionStorage.setItem('swal_alert', JSON.stringify({
                            type: 'success',
                            message: res.message || 'Tin tức đã được xóa.'
                        }));
                        location.reload(); 
                    } else {
                        Swal.fire('Thất bại', res.message || 'Xóa thất bại.', 'error');
                    }
                } catch (e) {
                    Swal.fire('Lỗi', 'Lỗi xử lý phản hồi từ server.', 'error');
                    console.error(response);
                }
            });
        }
    });
}

</script>

</body>
</html>