<?php
require_once('../../../helpers/startSession.php');
startRoleSession('admin');
require_once('../../../database/dbhelper.php');

$news_ID = $title = $thumbnail = $content = $CategoryNews_ID = "";

// Xử lý submit form
if (!empty($_POST['title'])) {
    $title = addslashes($_POST['title']);
    $news_ID = $_POST['news_ID'] ?? '';
    $content = $_POST['content'];
    $CategoryNews_ID = $_POST['CategoryNews_ID'];

    // Biến để lưu trạng thái và thông báo SweetAlert2
    $alert_type = '';
    $alert_message = '';
    
    $is_upload_new_thumbnail = false; // Cờ kiểm tra xem có ảnh mới được upload không

    // Xử lý upload ảnh
    if (!empty($_FILES['thumbnail']['name'])) {
        $upload_dir = "../../../images/uploads/newsupload/"; // Đường dẫn vật lý đến thư mục lưu ảnh
        $file_extension = pathinfo($_FILES["thumbnail"]["name"], PATHINFO_EXTENSION);
        $file_name_unique = uniqid() . '_' . time() . '.' . $file_extension; // Thêm uniqid và timestamp để tránh trùng tên tối đa
        $target_file = $upload_dir . $file_name_unique;

        // Đây là ĐƯỜNG DẪN SẼ LƯU VÀO DATABASE (CHỈ LÀ TÊN FILE)
        $thumbnail_db_value = $file_name_unique; 

        $imageFileType = strtolower($file_extension); 
        $allowtypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'avif', 'jfif'];
        $maxfilesize = 2 * 1024 * 1024; // 2MB

        if ($_FILES["thumbnail"]["size"] > $maxfilesize) {
            $alert_type = 'error';
            $alert_message = "Kích thước tệp quá lớn! Vui lòng chọn tệp dưới 2MB.";
        } elseif (!in_array($imageFileType, $allowtypes)) {
            $alert_type = 'error';
            $alert_message = "Loại tệp không được phép. Chỉ chấp nhận các định dạng ảnh như JPG, PNG, GIF, WebP, v.v.";
        } elseif (!move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $target_file)) {
            $alert_type = 'error';
            $alert_message = "Lỗi khi tải lên ảnh.";
        } else {
            // Nếu upload thành công, gán tên file duy nhất cho $thumbnail để lưu vào DB
            $thumbnail = $thumbnail_db_value; 
            $is_upload_new_thumbnail = true;
        }
    } else {
        // Nếu không upload ảnh mới (chỉ cập nhật thông tin khác hoặc không đổi ảnh)
        // Lấy lại đường dẫn ảnh hiện tại từ database (chỉ tên file)
        if ($news_ID != '') {
            $sql_get_old_thumbnail = 'SELECT thumbnail FROM news WHERE news_ID=' . $news_ID;
            $old_news = executeSingleResult($sql_get_old_thumbnail);
            if ($old_news != null) {
                $thumbnail = $old_news['thumbnail']; // $thumbnail giờ chỉ là tên file
            }
        } else {
            $thumbnail = ''; // Chế độ thêm mới, không có ảnh
        }
    }

    // Chỉ thực hiện lưu vào DB nếu không có lỗi từ upload hoặc các kiểm tra khác
    if (empty($alert_message)) { // Nếu không có lỗi đã phát hiện
        if (!empty($title)) {
            if ($news_ID == '') {
                // Thêm mới
                $sql = "INSERT INTO news(title, thumbnail, content, CategoryNews_ID) VALUES 
                        ('$title', '$thumbnail', '$content', '$CategoryNews_ID')";
                $alert_message = 'Thêm tin tức thành công!';
                $alert_type = 'success';
            } else {
                // Cập nhật
                // Lấy ảnh cũ từ DB để xóa trước khi cập nhật ảnh mới
                if ($is_upload_new_thumbnail) { // Chỉ xóa ảnh cũ nếu có ảnh mới được upload
                    $sql_get_old_thumbnail_for_delete = 'SELECT thumbnail FROM news WHERE news_ID=' . $news_ID;
                    $old_news_data = executeSingleResult($sql_get_old_thumbnail_for_delete);
                     // Log: Kiểm tra dữ liệu ảnh cũ lấy từ DB
   
                    if ($old_news_data && !empty($old_news_data['thumbnail'])) {
                        $old_thumbnail_path = $upload_dir . $old_news_data['thumbnail'];
                        // Debugging: echo "Deleting old file: " . $old_thumbnail_path . "<br>";
                        if (file_exists($old_thumbnail_path) && is_file($old_thumbnail_path)) {
                            if (unlink($old_thumbnail_path)) {
                                // Debugging: echo "Old file deleted successfully.<br>";
                            } else {
                                // Debugging: echo "Failed to delete old file. Check permissions.<br>";
                                $alert_type = 'warning'; // Có thể là cảnh báo thay vì lỗi
                                $alert_message = 'Cập nhật tin tức thành công, nhưng không thể xóa ảnh cũ. Vui lòng kiểm tra quyền thư mục.';
                            }
                        } else {
                            // Debugging: echo "Old file not found: " . $old_thumbnail_path . "<br>";
                        }
                    }
                    $sql = "UPDATE news SET title='$title', thumbnail='$thumbnail', content='$content', CategoryNews_ID='$CategoryNews_ID' WHERE news_ID=$news_ID";
                } else { // Không có ảnh mới được upload, chỉ cập nhật các trường khác
                    $sql = "UPDATE news SET title='$title', content='$content', CategoryNews_ID='$CategoryNews_ID' WHERE news_ID=$news_ID";
                }
                $alert_message = 'Cập nhật tin tức thành công!';
                $alert_type = 'success';
            }
            execute($sql);
            $_SESSION['swal_alert'] = ['type' => $alert_type, 'message' => $alert_message];
            header('Location: ../news_manage/listnews.php'); // Chuyển hướng về trang danh sách
            exit;
        } else {
            $alert_type = 'error';
            $alert_message = 'Tiêu đề không được để trống!';
            $_SESSION['swal_alert'] = ['type' => $alert_type, 'message' => $alert_message]; 
        }
    } else { // Có lỗi từ phần upload ảnh hoặc validation khác
        $_SESSION['swal_alert'] = ['type' => $alert_type, 'message' => $alert_message];
        // Nếu có lỗi, không chuyển hướng mà để hiển thị thông báo trên trang hiện tại
        // Remove: header('Location: ../news_manage/listnews.php');
        // Remove: exit;
    }
}

// Lấy dữ liệu để sửa (khi tải trang lần đầu hoặc sau khi có lỗi)
if (isset($_GET['news_ID'])) {
    $news_ID = $_GET['news_ID'];
    $sql = 'SELECT * FROM news WHERE news_ID=' . $news_ID;
    $news = executeSingleResult($sql);
    if ($news != null) {
        $title = $news['title'];
        $thumbnail = $news['thumbnail']; // $thumbnail ở đây là tên file từ DB (ví dụ: 'abc.jpg')
        $content = $news['content'];
        $CategoryNews_ID = $news['CategoryNews_ID'];
    } else {
        // Trường hợp news_ID không tồn tại, reset biến
        $news_ID = $title = $thumbnail = $content = $CategoryNews_ID = "";
    }
} else {
    // Đảm bảo $thumbnail rỗng khi ở chế độ thêm mới
    $news_ID = $title = $thumbnail = $content = $CategoryNews_ID = "";
}

// Xử lý lại CategoryNews_ID để tránh lỗi nếu không có gì được chọn ban đầu
if (!isset($CategoryNews_ID) || empty($CategoryNews_ID)) {
    $CategoryNews_ID = '';
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