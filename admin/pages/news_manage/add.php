<?php
require_once('../../../helpers/startSession.php');
startRoleSession('admin');
require_once('../../../database/dbhelper.php');

$news_ID = $title = $thumbnail = $content = $CategoryNews_ID = "";

// Xử lý submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['title'])) {
    $title = addslashes($_POST['title']);
    $news_ID = $_POST['news_ID'] ?? '';
    $content = $_POST['content'];
    $CategoryNews_ID = $_POST['CategoryNews_ID'];

    $alert_type = '';
    $alert_message = '';

    $upload_dir = "../../../images/uploads/newsupload/";
    $thumbnail = '';

    // Xử lý upload ảnh
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $tmpFile = $_FILES['thumbnail']['tmp_name'];
        $file_extension = strtolower(pathinfo($_FILES["thumbnail"]["name"], PATHINFO_EXTENSION));
        $allowtypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'avif', 'jfif'];
        $maxfilesize = 2 * 1024 * 1024; // 2MB

        if ($_FILES["thumbnail"]["size"] > $maxfilesize) {
            $alert_type = 'error';
            $alert_message = "Kích thước tệp quá lớn! Vui lòng chọn tệp dưới 2MB.";
        } elseif (!in_array($file_extension, $allowtypes)) {
            $alert_type = 'error';
            $alert_message = "Loại tệp không hợp lệ. Chỉ cho phép JPG, PNG, GIF, WebP...";
        } else {
            // Tính hash md5 để kiểm tra trùng
            $fileHash = md5_file($tmpFile);
            $foundFile = '';

            foreach (glob($upload_dir . "*") as $existingFile) {
                if (md5_file($existingFile) === $fileHash) {
                    $foundFile = basename($existingFile);
                    break;
                }
            }

            if ($foundFile) {
                // Nếu ảnh đã tồn tại thì dùng lại
                $thumbnail = $foundFile;
            } else {
                // Nếu chưa có thì lưu file mới
                $file_name_unique = uniqid() . '_' . time() . '.' . $file_extension;
                $target_file = $upload_dir . $file_name_unique;

                if (move_uploaded_file($tmpFile, $target_file)) {
                    $thumbnail = $file_name_unique;
                } else {
                    $alert_type = 'error';
                    $alert_message = "Lỗi khi tải lên ảnh.";
                }
            }
        }
    } else {
        // Nếu không upload ảnh mới thì lấy ảnh cũ (nếu sửa)
        if (!empty($news_ID)) {
            $sql_get_old_thumbnail = "SELECT thumbnail FROM news WHERE news_ID=" . intval($news_ID);
            $old_news = executeSingleResult($sql_get_old_thumbnail);
            if ($old_news) {
                $thumbnail = $old_news['thumbnail'];
            }
        }
    }

    // Nếu không có lỗi thì lưu DB
    if (empty($alert_message)) {
        if (empty($news_ID)) {
            // Thêm mới
            $sql = "INSERT INTO news(title, thumbnail, content, CategoryNews_ID) 
                    VALUES ('$title', '$thumbnail', '$content', '$CategoryNews_ID')";
            execute($sql);
            $alert_type = 'success';
            $alert_message = 'Thêm tin tức thành công!';
        } else {
            // Cập nhật
            $sql = "UPDATE news SET 
                        title='$title',
                        thumbnail='$thumbnail',
                        content='$content',
                        CategoryNews_ID='$CategoryNews_ID'
                    WHERE news_ID=" . intval($news_ID);
            execute($sql);
            $alert_type = 'success';
            $alert_message = 'Cập nhật tin tức thành công!';
        }

        $_SESSION['swal_alert'] = ['type' => $alert_type, 'message' => $alert_message];
        header('Location: ../news_manage/listnews.php');
        exit;
    } else {
        $_SESSION['swal_alert'] = ['type' => $alert_type, 'message' => $alert_message];
    }
}

// Load dữ liệu khi sửa
if (isset($_GET['news_ID'])) {
    $news_ID = $_GET['news_ID'];
    $sql = "SELECT * FROM news WHERE news_ID=" . intval($news_ID);
    $news = executeSingleResult($sql);
    if ($news) {
        $title = $news['title'];
        $thumbnail = $news['thumbnail'];
        $content = $news['content'];
        $CategoryNews_ID = $news['CategoryNews_ID'];
    }
}
?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= $news_ID ? 'Chỉnh sửa' : 'Thêm mới' ?> Tin Tức</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
    <a href="javascript:history.back()" class="btn btn-primary">
        <i class="fas fa-arrow-left mr-1"></i> Quay Lại
    </a>
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-info text-white text-center">
            <h4><?= $news_ID ? 'Chỉnh sửa' : 'Thêm mới' ?> Tin Tức</h4>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="news_ID" value="<?= $news_ID ?>">

                <div class="form-group">
                    <label>Tiêu đề</label>
                    <input type="text" class="form-control" name="title" required value="<?= htmlspecialchars($title) ?>">
                </div>

                <div class="form-group">
                    <label>Danh Mục</label>
                    <select class="form-control" name="CategoryNews_ID" required>
                        <option value="">-- Chọn danh mục --</option>
                        <?php
                        $sql = 'SELECT * FROM categorynews';
                        $categoryList = executeResult($sql);
                        foreach ($categoryList as $item) {
                            $selected = ($item['CategoryNews_ID'] == $CategoryNews_ID) ? 'selected' : '';
                            echo '<option value="' . $item['CategoryNews_ID'] . '" ' . $selected . '>' . $item['name'] . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Thumbnail</label>
                    <input type="file" class="form-control-file" name="thumbnail" id="thumbnailInput">
                    <br>
                    <img id="thumbnailPreview"
                                src="<?= !empty($thumbnail) ? '/ShopAnVat/images/uploads/newsupload/' . htmlspecialchars($thumbnail) : '' ?>"
                                style="max-width: 200px; margin-top: 10px; <?= empty($thumbnail) ? 'display: none;' : '' ?>">
                </div>

                <div class="form-group">
                    <label for="content">Nội dung</label>
                    <textarea id="content" name="content"><?= htmlspecialchars($content) ?></textarea>
                </div>

                <button type="submit" class="btn <?= empty($id) ? 'btn-success' : 'btn-primary' ?>">
                    <?= empty($id) ? 'Thêm' : 'Lưu' ?>
                </button>
                <a href="../news_manage/listnews.php" class="btn btn-warning">Trở về</a>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('thumbnailInput').addEventListener('change', function(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('thumbnailPreview');

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.src = '';
        preview.style.display = 'none';
    }
});
</script>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    $('#content').summernote({
        height: 300,
        placeholder: 'Nhập nội dung bài viết...',
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough', 'superscript', 'subscript']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link', 'picture']],
            ['view', ['fullscreen', 'codeview']]
        ]
    });

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
</script>
</body>
</html>