<?php
require_once('../../../helpers/startSession.php');
startRoleSession('admin');
require_once('../../../database/dbhelper.php');

$CategoryNews_ID = '';
$name = '';

// Xử lý khi gửi form
if (!empty($_POST['name'])) {
    $name = str_replace('"', '\\"', $_POST['name']);
    $CategoryNews_ID = $_POST['CategoryNews_ID'] ?? '';
    $created_at = $updated_at = date('Y-m-d H:i:s');

    if ($CategoryNews_ID == '') {
        // Kiểm tra trùng tên khi thêm mới
        $checkSql = 'SELECT * FROM categorynews WHERE name = "' . $name . '"';
        $existing = executeSingleResult($checkSql);

        if ($existing) {
            $_SESSION['swal_alert'] = ['type' => 'error', 'message' => 'Danh mục tin tức này đã tồn tại!'];
           
        } else {
            // Thêm mới
            $sql = 'INSERT INTO categorynews(name, created_at, updated_at) 
                    VALUES ("' . $name . '", "' . $created_at . '", "' . $updated_at . '")';
            execute($sql);
            $_SESSION['swal_alert'] = ['type' => 'success', 'message' => 'Đã thêm danh mục tin tức thành công!'];
            header('Location: list_category_news.php'); 
            exit();
        }
    } else {
        // Cập nhật
        $sql = 'UPDATE categorynews SET name="' . $name . '", updated_at="' . $updated_at . '" 
                WHERE CategoryNews_ID=' . $CategoryNews_ID;
        execute($sql);
        $_SESSION['swal_alert'] = ['type' => 'success', 'message' => 'Đã sửa danh mục tin tức thành công!'];
        header('Location: list_category_news.php'); 
        exit();
    }
}

// Lấy thông tin khi sửa
if (isset($_GET['CategoryNews_ID'])) {
    $CategoryNews_ID = $_GET['CategoryNews_ID'];
    $sql = 'SELECT * FROM categorynews WHERE CategoryNews_ID = ' . $CategoryNews_ID;
    $categorynews = executeSingleResult($sql);
    if ($categorynews != null) {
        $name = $categorynews['name'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $CategoryNews_ID ? 'Sửa' : 'Thêm' ?> Danh Mục Tin Tức</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-info text-white text-center">
            <h4><?= $CategoryNews_ID ? 'Sửa' : 'Thêm' ?> Danh Mục Tin Tức</h4>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="CategoryNews_ID" value="<?= $CategoryNews_ID ?>">
                <div class="form-group">
                    <label for="name">Tên Danh Mục:</label>
                    <input required type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($name) ?>">
                </div>
                <button type="submit" class="btn btn-success">
                    <?= $CategoryNews_ID ? 'Cập nhật' : 'Thêm mới' ?>
                </button>
                <a href="list_category_news.php" class="btn btn-warning">Trở về</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
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
</script>
</body>
</html>