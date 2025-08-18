<?php
require_once('../../../helpers/startSession.php');
startRoleSession('admin');
require_once('../../../database/dbhelper.php');
// Đặt ngay sau startRoleSession('admin');
$swal_alert_data = null;
if (isset($_SESSION['swal_alert'])) {
    $swal_alert_data = $_SESSION['swal_alert'];
    unset($_SESSION['swal_alert']);
}

$id = $product_name = $base_price = $weight = $description = $category_ID = "";
$thumbnailList = [];


// Nếu có GET product_ID => load dữ liệu để sửa
if (!empty($_GET['product_ID'])) {
    $id = intval($_GET['product_ID']);
    $sql = "SELECT * FROM Product WHERE product_ID = $id LIMIT 1";
    $product = executeSingleResult($sql);
    if ($product) {
        $product_name = $product['product_name'];
        $base_price   = $product['base_price'];
        $weight       = $product['weight'];
        $description  = $product['description'];
        $category_ID  = $product['category_ID'];

        // Lấy danh sách ảnh sản phẩm
        $thumbnailList = executeResult("SELECT * FROM ProductImage WHERE product_ID = $id");
    } else {
        $_SESSION['swal_alert'] = [
            'type' => 'error',
            'message' => 'Sản phẩm không tồn tại.'
        ];
        header('Location: listproduct.php');
        exit();
    }
}

// Phần xử lý POST thêm/sửa sản phẩm (đoạn bạn đã có sẵn) ...

if (!empty($_POST['product_name'])) {
    // Lấy dữ liệu từ form
    $product_name = str_replace('"', '\\"', $_POST['product_name']);
    $id = $_POST['product_ID'] ?? '';
    $base_price = $_POST['base_price'] ?? '';
    $weight = $_POST['weight'] ?? '';
    $description = $_POST['description'] ?? '';
    $category_ID = $_POST['category_ID'] ?? '';
    $created_at = $updated_at = date('Y-m-d H:i:s');

    // Kiểm tra tính hợp lệ của các trường thông tin
   // if (empty($product_name) || empty($base_price) || empty($weight) || empty($category_ID)) {
     //   $_SESSION['swal_alert'] = [
     //       'type' => 'error',
     //       'message' => 'Thông tin sản phẩm không hợp lệ. Vui lòng kiểm tra lại.'
     //   ];
     //   header('Location: ' . $_SERVER['HTTP_REFERER']);
     //   exit();
   // }

    if (!is_numeric($base_price) || $base_price <= 0) {
        $_SESSION['swal_alert'] = [
            'type' => 'error',
            'message' => 'Giá sản phẩm không hợp lệ. Vui lòng nhập giá sản phẩm lớn hơn 0.'
        ];
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }

    if (!is_numeric($weight) || $weight <= 0) {
        $_SESSION['swal_alert'] = [
            'type' => 'error',
            'message' => 'Khối lượng sản phẩm không hợp lệ. Vui lòng nhập khối lượng lớn hơn 0'
        ];
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Nếu là thêm mới sản phẩm
    if (empty($id)) {
        $sql = 'INSERT INTO Product (product_name, base_price, weight, description, category_ID, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)';
        $params = [$product_name, $base_price, $weight, $description, $category_ID, $created_at, $updated_at];
        execute($sql, $params);

        $result = executeSingleResult('SELECT MAX(product_ID) as max_id FROM Product');
        $product_ID = $result['max_id'];

        // Thiết lập thông báo thành công
        $_SESSION['swal_alert'] = [
            'type' => 'success',
            'message' => 'Thêm sản phẩm thành công!'
        ];
    } else {
        $sql = 'UPDATE Product SET product_name = ?, base_price = ?, weight = ?, description = ?, category_ID = ?, updated_at = ?
                WHERE product_ID = ?';
        $params = [$product_name, $base_price, $weight, $description, $category_ID, $updated_at, $id];
        execute($sql, $params);

        $product_ID = $id;

        // Thiết lập thông báo thành công
        $_SESSION['swal_alert'] = [
            'type' => 'success',
            'message' => 'Cập nhật sản phẩm thành công!'
        ];
    }

    // Xử lý hình ảnh sản phẩm
    if (!empty($_FILES['images']['name'][0])) {
        $target_dir = "../../../images/uploads/product/";
        $allowtypes = ['jpg', 'jpeg', 'png', 'gif'];
        $maxfilesize = 2 * 1024 * 1024; //2mb

        foreach ($_FILES['images']['name'] as $key => $name) {
            $tmp_name = $_FILES['images']['tmp_name'][$key];
            $size = $_FILES['images']['size'][$key];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            // Kiểm tra định dạng và kích thước hình ảnh
            if ($size > $maxfilesize) {
                $_SESSION['swal_alert'] = [
                    'type' => 'error',
                    'message' => 'Kích thước ảnh không hợp lệ. Vui lòng chọn ảnh có kích thước dưới 2mb'
                ];
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit();
            }

            if (!in_array($ext, $allowtypes)) {
                $_SESSION['swal_alert'] = [
                    'type' => 'error',
                    'message' => 'Định dạng hình ảnh không hợp lệ. Vui lòng tải lên hình ảnh có định dạng jpg, jpeg, png, gif.'
                ];
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit();
            }

            $originalName = basename($name);
            $filename = preg_replace('/[^A-Za-z0-9_.-]/', '_', $originalName);
            $target_file = $target_dir . $filename;

            // Kiểm tra tên file trùng
            $i = 1;
            while (file_exists($target_file)) {
                $filename = pathinfo($originalName, PATHINFO_FILENAME) . "_{$i}." . $ext;
                $target_file = $target_dir . $filename;
                $i++;
            }

            if (move_uploaded_file($tmp_name, $target_file)) {
                $is_primary = 0;
                $hasPrimary = executeSingleResult("SELECT * FROM ProductImage WHERE product_ID = $product_ID AND is_primary = 1");
                if (!$hasPrimary) $is_primary = 1;

                execute("INSERT INTO ProductImage(product_ID, image_url, is_primary) VALUES ($product_ID, '$filename', $is_primary)");
            }
        }
    }

    // Chuyển hướng sau khi set session
    header('Location: ../product_manage/listproduct.php');
    die();
}
?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= empty($id) ? 'Thêm mới' : 'Chỉnh sửa' ?> Sản phẩm</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
<a href="javascript:history.back()" class="btn btn-primary m-2">
    <i class="fas fa-arrow-left mr-1"></i> Quay Lại
</a>

<div class="container mt-3">
    <div class="card">
        <div class="card-header bg-info text-white text-center">
            <h4><?= empty($id) ? 'Thêm mới' : 'Chỉnh sửa' ?> Sản Phẩm</h4>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="product_ID" value="<?= htmlspecialchars($id) ?>">

                <div class="form-group">
                    <label>Tên sản phẩm</label>
                    <input type="text" class="form-control" name="product_name" required value="<?= htmlspecialchars($product_name) ?>">
                </div>

                <div class="form-group">
                    <label>Danh mục</label>
                    <select class="form-control" name="category_ID" required>
                        <option value="">-- Chọn danh mục --</option>
                        <?php
                        $categoryList = executeResult("SELECT * FROM Category");
                        foreach ($categoryList as $item) {
                            $selected = ($item['category_ID'] == $category_ID) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($item['category_ID']) . '" ' . $selected . '>' . htmlspecialchars($item['category_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Giá sản phẩm</label>
                    <input type="text" class="form-control currency-format" id="formatted_base_price" required value="<?= $base_price !== '' ? number_format($base_price) : '' ?>">
                    <input type="hidden" name="base_price" id="base_price" value="<?= htmlspecialchars($base_price) ?>">
                </div>

                <div class="form-group">
                    <label>Khối lượng (gram)</label>
                    <input type="text" class="form-control currency-format" id="formatted_weight" required value="<?= $weight !== '' ? number_format($weight) : '' ?>">
                    <input type="hidden" name="weight" id="weight" value="<?= htmlspecialchars($weight) ?>">
                </div>

                <div class="form-group">
                    <label>Ảnh sản phẩm (có thể chọn nhiều)</label>
                    <input type="file" class="form-control-file" name="images[]" multiple>
                </div>

                <div id="previewArea" class="mt-2 d-flex flex-wrap"></div>

                <?php if (!empty($thumbnailList)) : ?>
                    <div class="mb-3">
                        <label>Ảnh hiện tại:</label><br>
                        <?php foreach ($thumbnailList as $img): 
                            $img_src = '../../../images/uploads/product/' . htmlspecialchars($img['image_url']);
                        ?>
                            <div class="img-wrapper" style="position: relative; display: inline-block; margin: 5px;">
                                <img src="<?= $img_src ?>" style="max-width: 100px; border: 1px solid #ccc;">
                                <button type="button" class="btn btn-sm btn-danger btn-delete-img"
                                        data-id="<?= htmlspecialchars($img['image_ID']) ?>"
                                        style="position: absolute; top: 0; right: 0;">&times;</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Mô tả sản phẩm</label>
                    <textarea id="description" name="description"><?= htmlspecialchars($description) ?></textarea>
                </div>

                <button type="submit" class="btn <?= empty($id) ? 'btn-success' : 'btn-primary' ?>">
                    <?= empty($id) ? 'Thêm' : 'Lưu' ?>
                </button>

                <a href="javascript:history.back()" class="btn btn-warning">Trở về</a>
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
        height: 250,
        placeholder: 'Nhập mô tả chi tiết...',
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough', 'superscript', 'subscript']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link', 'picture']],
            ['view', ['fullscreen', 'codeview']]
        ]
    });
});

// Format số có dấu phẩy cho nhiều input
function formatNumber(n) {
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
function unformatNumber(str) {
    return str.replace(/[^0-9]/g, '');
}
document.addEventListener("DOMContentLoaded", function () {
    const formattedInputs = document.querySelectorAll(".currency-format");

    formattedInputs.forEach(function (input) {
        const field = input.id.replace("formatted_", "");
        const hiddenInput = document.getElementById(field);

        let raw = unformatNumber(input.value);
        input.value = formatNumber(raw);
        if (hiddenInput) hiddenInput.value = raw;

        input.addEventListener("input", function () {
            let newRaw = unformatNumber(input.value);
            input.value = formatNumber(newRaw);
            if (hiddenInput) hiddenInput.value = newRaw;
        });
    });
});

// Preview ảnh mới
document.querySelector("input[name='images[]']").addEventListener("change", function (event) {
    const files = event.target.files;
    const previewContainer = document.getElementById("previewArea");
    previewContainer.innerHTML = '';
    Array.from(files).forEach(file => {
        if (file.type.startsWith("image/")) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const img = document.createElement("img");
                img.src = e.target.result;
                img.style.maxWidth = "100px";
                img.style.margin = "5px";
                img.style.border = "1px solid #ccc";
                previewContainer.appendChild(img);
            };
            reader.readAsDataURL(file);
        }
    });
});

// Xoá ảnh cũ
document.querySelectorAll(".btn-delete-img").forEach(button => {
    button.addEventListener("click", function () {
        Swal.fire({
            title: 'Xác nhận xóa?',
            text: 'Bạn có chắc muốn xóa ảnh này không?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Vâng, xóa nó!',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                const imageID = this.dataset.id;
                fetch('/ShopAnVat/admin/pages/product_manage/delete_product_image.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'image_ID=' + imageID
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire(
                            'Đã xóa!',
                            data.message || 'Ảnh đã được xóa thành công.',
                            'success'
                        );
                        this.parentElement.remove();
                    } else {
                        Swal.fire(
                            'Lỗi!',
                            data.message || 'Xóa thất bại.',
                            'error'
                        );
                    }
                })
                .catch(error => {
                    Swal.fire(
                        'Lỗi!',
                        'Có lỗi xảy ra khi gửi yêu cầu xóa ảnh: ' + error,
                        'error'
                    );
                    console.error('Error:', error);
                });
            }
        });
    });
});
</script>
<?php if (!empty($swal_alert_data)): ?>
<script>
Swal.fire({
    icon: '<?= $swal_alert_data['type'] ?>',
    title: 'Thông báo',
    text: '<?= $swal_alert_data['message'] ?>'
});
</script>
<?php endif; ?>

</body>
</html>