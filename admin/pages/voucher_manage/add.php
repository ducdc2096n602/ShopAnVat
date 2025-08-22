<?php
session_start();
require_once('../../../database/dbhelper.php');

$voucher_ID = $code = $description = $discount_type = '';
$discount_value = $max_discount = $min_order_amount = '';
$start_date = $end_date = $usage_limit = $image_url = '';
$save_path = '';

// Khởi tạo một biến cờ để biết có cần chuyển hướng sau thông báo không
$should_redirect_after_success = false;

if (!empty($_POST['code'])) {
    $voucher_ID = $_POST['voucher_ID'] ?? '';
    $code = addslashes($_POST['code']);
    $description = $_POST['description'];
    $discount_type = $_POST['discount_type'];

    $discount_value = (int)str_replace(['.', ','], '', $_POST['discount_value']);
    $max_discount = (int)str_replace(['.', ','], '', $_POST['max_discount']);
    $min_order_amount = (int)str_replace(['.', ','], '', $_POST['min_order_amount']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $usage_limit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;

    if (!empty($_FILES['image_url']['name'])) {
        $upload_dir = "../../../images/uploads/vouchers/";
        $file_name = time() . '_' . basename($_FILES["image_url"]["name"]);
        $target_file = $upload_dir . $file_name;
        $save_path = $file_name;

        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowtypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxfilesize = 2 * 1024 * 1024;

        if ($_FILES["image_url"]["size"] > $maxfilesize) {
            $_SESSION['swal_alert'] = [
                'type' => 'error',
                'title' => 'Lỗi tải ảnh',
                'message' => "File ảnh quá lớn! Kích thước tối đa là 2MB."
            ];
            
        } elseif (!in_array($imageFileType, $allowtypes)) {
            $_SESSION['swal_alert'] = [
                'type' => 'error',
                'title' => 'Lỗi định dạng ảnh',
                'message' => "Chỉ cho phép ảnh JPG, PNG, GIF, WebP,..."
            ];
            
        } elseif (!move_uploaded_file($_FILES["image_url"]["tmp_name"], $target_file)) {
            $_SESSION['swal_alert'] = [
                'type' => 'error',
                'title' => 'Lỗi tải lên',
                'message' => "Lỗi khi upload ảnh!"
            ];
            
        }
    }

    // Chỉ thực hiện INSERT/UPDATE nếu không có lỗi upload
    if (!isset($_SESSION['swal_alert'])) { // Kiểm tra nếu không có lỗi nào được đặt trong quá trình upload
        if ($voucher_ID == '') {
            $sql = "INSERT INTO voucher(code, description, discount_type, discount_value, max_discount, min_order_amount, start_date, end_date, usage_limit, image_url, created_at)
                    VALUES ('$code', '$description', '$discount_type', '$discount_value', '$max_discount', '$min_order_amount', '$start_date', '$end_date', " . ($usage_limit === null ? "NULL" : "'$usage_limit'") . ", '$save_path', NOW())";
            $_SESSION['swal_alert'] = [
                'type' => 'success',
                'title' => 'Thành công!',
                'message' => "Thêm mới Voucher thành công!"
            ];
            $should_redirect_after_success = true; // Đặt cờ để chuyển hướng sau thông báo
        } else {
            if (!empty($save_path)) {
                $old_image_sql = "SELECT image_url FROM voucher WHERE voucher_ID = $voucher_ID";
                $old_image_result = executeSingleResult($old_image_sql);
                if ($old_image_result && !empty($old_image_result['image_url'])) {
                    $upload_dir = "../../../images/uploads/vouchers/";
                    $old_image_path = $upload_dir . $old_image_result['image_url'];
                    if (file_exists($old_image_path) && is_file($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
                $sql = "UPDATE voucher SET code='$code', description='$description', discount_type='$discount_type',
                        discount_value='$discount_value', max_discount='$max_discount', min_order_amount='$min_order_amount',
                        start_date='$start_date', end_date='$end_date', usage_limit=" . ($usage_limit === null ? "NULL" : "'$usage_limit'") . ",
                        image_url='$save_path', updated_at=NOW() WHERE voucher_ID=$voucher_ID";
            } else {
                $sql = "UPDATE voucher SET code='$code', description='$description', discount_type='$discount_type',
                        discount_value='$discount_value', max_discount='$max_discount', min_order_amount='$min_order_amount',
                        start_date='$start_date', end_date='$end_date', usage_limit=" . ($usage_limit === null ? "NULL" : "'$usage_limit'") . ",
                        updated_at=NOW() WHERE voucher_ID=$voucher_ID";
            }
            $_SESSION['swal_alert'] = [
                'type' => 'success',
                'title' => 'Thành công!',
                'message' => "Cập nhật Voucher thành công!"
            ];
            $should_redirect_after_success = true; // Đặt cờ để chuyển hướng sau thông báo
        }
        execute($sql);
    }
}


$swal_alert_data = null;
if (isset($_SESSION['swal_alert'])) {
    $swal_alert_data = $_SESSION['swal_alert'];
    unset($_SESSION['swal_alert']); 
}

if (isset($_GET['voucher_ID'])) {
    $voucher_ID = $_GET['voucher_ID'];
    $sql = "SELECT * FROM voucher WHERE voucher_ID = $voucher_ID";
    $voucher = executeSingleResult($sql);
    if ($voucher) {
        extract($voucher);
    }
}

// Khởi tạo biến nếu không có voucher (thêm mới)
if (!isset($voucher)) {
    $voucher_ID = '';
    $code = '';
    $description = '';
    $discount_type = 'percent';
    $discount_value = 0;
    $max_discount = 0;
    $min_order_amount = 0;
    $start_date = '';
    $end_date = '';
    $usage_limit = '';
    $image_url = '';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= $voucher_ID ? 'Chỉnh sửa' : 'Thêm mới' ?> Voucher</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
<a href="javascript:history.back()" class="btn btn-primary m-2">
    <i class="fas fa-arrow-left mr-1"></i> Quay Lại
</a>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-info text-white text-center">
            <h4><?= $voucher_ID ? 'Chỉnh sửa' : 'Thêm mới' ?> Voucher</h4>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="voucher_ID" value="<?= htmlspecialchars($voucher_ID) ?>">

                <div class="form-group">
                    <label>Mã Voucher</label>
                    <input type="text" class="form-control" name="code" required value="<?= htmlspecialchars($code) ?>">
                </div>

                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea class="form-control" name="description" id="description"><?= htmlspecialchars($description) ?></textarea>
                </div>

                <div class="form-group">
                    <label>Loại giảm</label>
                    <select class="form-control" name="discount_type" required>
                        <option value="percent" <?= $discount_type == 'percent' ? 'selected' : '' ?>>Phần trăm (%)</option>
                        <option value="amount" <?= $discount_type == 'amount' ? 'selected' : '' ?>>Số tiền (₫)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Giá trị giảm</label>
                    <input type="text" class="form-control number-format" name="discount_value" required value="<?= number_format($discount_value) ?>">
                </div>

                <div class="form-group">
                    <label>Giảm tối đa (₫)</label>
                    <input type="text" class="form-control number-format" name="max_discount" value="<?= number_format($max_discount) ?>">
                </div>

                <div class="form-group">
                    <label>Đơn tối thiểu (₫)</label>
                    <input type="text" class="form-control number-format" name="min_order_amount" value="<?= number_format($min_order_amount) ?>">
                </div>

                <div class="form-group">
                    <label>Ngày bắt đầu</label>
                    <input type="datetime-local" name="start_date" class="form-control"
                        value="<?= !empty($start_date) ? date('Y-m-d\TH:i', strtotime($start_date)) : '' ?>">
                </div>

                <div class="form-group">
                    <label>Ngày kết thúc</label>
                    <input type="datetime-local" name="end_date" class="form-control"
                        value="<?= !empty($end_date) ? date('Y-m-d\TH:i', strtotime($end_date)) : '' ?>">
                </div>

                <div class="form-group">
                    <label>Giới hạn lượt dùng</label>
                    <input type="number" class="form-control" name="usage_limit" value="<?= htmlspecialchars($usage_limit) ?>">
                </div>

                <div class="form-group">
                    <label>Ảnh đại diện</label>
                    <input type="file" class="form-control-file" name="image_url" id="imageInput">
                    <br>
                    <img id="imagePreview"
                        src="<?= !empty($image_url) ? '/ShopAnVat/images/uploads/vouchers/' . htmlspecialchars(basename($image_url)) : '' ?>"
                        style="max-width: 200px; <?= empty($image_url) ? 'display:none;' : '' ?>">
                </div>

                <button type="submit" class="btn btn-success">Thêm</button>
                <a href="listvoucher.php" class="btn btn-warning">Trở về</a>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {
    $('#description').summernote({
        height: 200,
        placeholder: 'Nhập mô tả voucher...',
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough', 'superscript', 'subscript']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link']],
            ['view', ['fullscreen', 'codeview']]
        ]
    });

    
    <?php if ($swal_alert_data): ?>
        Swal.fire({
            icon: '<?= $swal_alert_data['type'] ?>',
            title: '<?= $swal_alert_data['title'] ?? 'Thông báo' ?>',
            text: '<?= $swal_alert_data['message'] ?>',
            confirmButtonText: 'Đóng'
        }).then((result) => {
            <?php if ($should_redirect_after_success): ?>
                window.location.href = 'listvoucher.php';
            <?php endif; ?>
        });
    <?php endif; ?>
});

document.querySelectorAll('.number-format').forEach(function (input) {
    input.addEventListener('input', function () {
        let rawValue = this.value.replace(/\D/g, '');
        this.value = rawValue === '' ? '' : Number(rawValue).toLocaleString('vi-VN');
    });
});

document.querySelector('select[name="discount_type"]').addEventListener('change', validateDiscountValue);
document.querySelector('input[name="discount_value"]').addEventListener('input', validateDiscountValue);

function validateDiscountValue() {
    const discountType = document.querySelector('select[name="discount_type"]').value;
    const discountInput = document.querySelector('input[name="discount_value"]');
    let value = parseInt(discountInput.value.replace(/\D/g, '')) || 0;

    if (discountType === 'percent' && value > 100) {
        Swal.fire({
            icon: 'warning',
            title: 'Giá trị không hợp lệ',
            text: 'Giá trị giảm theo % không được vượt quá 100%',
            confirmButtonText: 'Đóng'
        });
        value = 100;
    }

    discountInput.value = value.toLocaleString('vi-VN');
}

document.getElementById('imageInput').addEventListener('change', function () {
    const file = this.files[0];
    const preview = document.getElementById('imagePreview');
    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
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
</body>
</html>