<?php
require_once('../../../helpers/startSession.php');
startRoleSession('admin');
require_once('../../../database/dbhelper.php');

$category_ID = '';
$category_name = '';

// Xử lý khi gửi form
if (!empty($_POST)) { // Kiểm tra nếu form đã được submit
    $category_name = trim($_POST['category_name'] ?? ''); // Dùng trim để loại bỏ khoảng trắng thừa
    $category_ID = $_POST['category_ID'] ?? '';
    $created_at = $updated_at = date('Y-m-d H:i:s');

    $alert_type = '';
    $alert_message = '';

    if (empty($category_name)) {
        $alert_type = 'error';
        $alert_message = 'Tên danh mục không được để trống!';
        $_SESSION['swal_alert'] = ['type' => $alert_type, 'message' => $alert_message];
        // Không exit ở đây để form vẫn hiển thị
    } else {
        // SQL Injection: Nên dùng prepared statements thay vì addslashes/str_replace
        // Tuy nhiên, để giữ cấu trúc code hiện tại, tôi sẽ dùng addslashes
        $category_name_escaped = addslashes($category_name);

        if ($category_ID == '') {
            // Thêm mới
            // Kiểm tra trùng tên khi thêm mới (chỉ kiểm tra các danh mục CHƯA bị xóa)
            $checkSql = 'SELECT * FROM Category WHERE category_name = "' . $category_name_escaped . '" AND is_deleted = 0';
            $existing = executeSingleResult($checkSql);

            if ($existing) {
                $alert_type = 'error';
                $alert_message = 'Danh mục này đã tồn tại!';
                $_SESSION['swal_alert'] = ['type' => $alert_type, 'message' => $alert_message];
                // Không exit ở đây để form vẫn hiển thị
            } else {
                $sql = 'INSERT INTO Category (category_name, created_at, updated_at)
                        VALUES ("' . $category_name_escaped . '", "' . $created_at . '", "' . $updated_at . '")';
                execute($sql);
                $alert_type = 'success';
                $alert_message = 'Đã thêm danh mục thành công!';
                $_SESSION['swal_alert'] = ['type' => $alert_type, 'message' => $alert_message];
                header('Location: list_category_product.php');
                exit(); // RẤT QUAN TRỌNG: Dừng script sau khi chuyển hướng
            }
        } else {
            // Cập nhật
            // Kiểm tra trùng tên khi cập nhật (ngoại trừ chính danh mục đang sửa)
            $checkSql = 'SELECT * FROM Category WHERE category_name = "' . $category_name_escaped . '" AND category_ID != ' . intval($category_ID) . ' AND is_deleted = 0';
            $existing = executeSingleResult($checkSql);

            if ($existing) {
                $alert_type = 'error';
                $alert_message = 'Tên danh mục này đã tồn tại cho danh mục khác!';
                $_SESSION['swal_alert'] = ['type' => $alert_type, 'message' => $alert_message];
                // Không exit ở đây
            } else {
                $sql = 'UPDATE Category 
                        SET category_name="' . $category_name_escaped . '", updated_at="' . $updated_at . '" 
                        WHERE category_ID=' . intval($category_ID);
                execute($sql);
                $alert_type = 'success';
                $alert_message = 'Đã sửa danh mục thành công!';
                $_SESSION['swal_alert'] = ['type' => $alert_type, 'message' => $alert_message];
                header('Location: list_category_product.php');
                exit(); // RẤT QUAN TRỌNG: Dừng script sau khi chuyển hướng
            }
        }
    }
}

// Lấy thông tin khi sửa
if (isset($_GET['category_ID']) && !empty($_GET['category_ID'])) {
    $category_ID = intval($_GET['category_ID']); // Chuyển sang số nguyên để an toàn
    $sql = 'SELECT * FROM Category WHERE category_ID = ' . $category_ID;
    $category = executeSingleResult($sql);
    if ($category != null) {
        $category_name = $category['category_name'];
    } else {
        // Nếu category_ID không hợp lệ hoặc không tìm thấy, reset về chế độ thêm mới
        $category_ID = '';
        $category_name = '';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= $category_ID ? 'Sửa' : 'Thêm' ?> Danh Mục Sản Phẩm</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-info text-white text-center">
            <h4><?= $category_ID ? 'Sửa' : 'Thêm' ?> Danh Mục Sản Phẩm</h4>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="category_ID" value="<?= htmlspecialchars($category_ID) ?>">
                <div class="form-group">
                    <label for="category_name">Tên Danh Mục:</label>
                    <input required type="text" class="form-control" id="category_name" name="category_name" value="<?= htmlspecialchars($category_name) ?>">
                </div>
                <button type="submit" class="btn btn-success">
                    <?= $category_ID ? 'Cập nhật' : 'Thêm mới' ?>
                </button>
                <a href="list_category_product.php" class="btn btn-warning">Trở về</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
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