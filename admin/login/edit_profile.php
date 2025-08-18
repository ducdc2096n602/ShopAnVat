<?php
require_once('../../database/dbhelper.php');
require_once('../../helpers/startSession.php');
startRoleSession('admin');

// Đảm bảo admin đã đăng nhập (role_ID = 1)
if (!isset($_SESSION['account_ID']) || $_SESSION['role_ID'] != 1) {
    header('Location: ../../login/login.php');
    exit();
}

$account_ID = $_SESSION['account_ID'];
$sql = "SELECT * FROM Account WHERE account_ID = $account_ID";
$account = executeSingleResult($sql);

if (!$account) {
    echo 'Không tìm thấy tài khoản.';
    exit();
}

// Xử lý khi gửi form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = addslashes(trim($_POST['fullname'] ?? ''));
    $email = addslashes(trim($_POST['email'] ?? ''));
    $phone_number = addslashes(trim($_POST['phone_number'] ?? ''));
    $address = addslashes(trim($_POST['address'] ?? ''));
    $avatar = $account['avatar']; // giữ nguyên nếu không thay đổi

    // Mảng lưu thông báo lỗi
    $errors = [];

    // Kiểm tra Họ tên
    if (empty($fullname)) {
        $errors['fullname'] = 'Họ tên không được để trống.';
    }

    // Kiểm tra Email
    if (empty($email)) {
        $errors['email'] = 'Email không được để trống.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email không hợp lệ.';
    } else {
        // Kiểm tra email đã tồn tại
        $sqlEmail = "SELECT * FROM Account WHERE email = '$email' AND account_ID != $account_ID";
        $existingEmail = executeSingleResult($sqlEmail);
        if ($existingEmail) {
            $errors['email'] = 'Email này đã được sử dụng.';
        }
    }

    // Kiểm tra Số điện thoại (không cho phép trống)
    if (empty($phone_number)) {
        $errors['phone_number'] = 'Số điện thoại không được để trống.';
    } elseif (!preg_match('/^0[0-9]{9}$/', $phone_number)) {
        $errors['phone_number'] = 'Số điện thoại không hợp lệ (Ví dụ: 0912345678).';
    } else {
        // Kiểm tra số điện thoại đã tồn tại
        $sqlPhone = "SELECT * FROM Account WHERE phone_number = '$phone_number' AND account_ID != $account_ID";
        $existingPhone = executeSingleResult($sqlPhone);
        if ($existingPhone) {
            $errors['phone_number'] = 'Số điện thoại này đã được sử dụng.';
        }
    }

    // Kiểm tra Avatar (nếu có thay đổi)
    if (!empty($_FILES['avatar']['name'])) {
        $file = $_FILES['avatar'];
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            $errors['avatar'] = 'Chỉ chấp nhận các định dạng: jpg, jpeg, png, gif.';
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            $errors['avatar'] = 'Kích thước ảnh phải nhỏ hơn 5MB.';
        }
    }

    // Nếu không có lỗi, thực hiện cập nhật
    if (empty($errors)) {
        // Nếu có thay đổi avatar
        if (!empty($_FILES['avatar']['name'])) {
            // Upload avatar mới
            $uploadDir = '../../uploads/avatar/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $originalName = basename($_FILES['avatar']['name']);
            $newFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9_.]/', '_', $originalName);
            $targetFile = $uploadDir . $newFileName;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
                $avatar = $newFileName;
            }
        }

        // Cập nhật thông tin vào database
        $sqlUpdate = "UPDATE Account SET 
            fullname = '$fullname',
            email = '$email',
            phone_number = '$phone_number',
            address = '$address',
            avatar = '$avatar',
            updated_at = NOW()
            WHERE account_ID = $account_ID";
        
        execute($sqlUpdate);

        $_SESSION['message'] = 'Cập nhật thông tin thành công.';
        header('Location: profile.php');
        exit();
    }
}

// Xử lý đường dẫn avatar
$avatarFile = !empty($account['avatar']) && file_exists("../../uploads/avatar/" . $account['avatar'])
    ? "../../uploads/avatar/" . $account['avatar']
    : "../assets/img/default-avatar.png";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa thông tin Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .edit-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .avatar-wrapper {
            position: relative;
            width: 130px;
            height: 130px;
            margin: 0 auto 20px;
        }
        .avatar-wrapper img {
            width: 130px;
            height: 130px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #ccc;
        }
        .avatar-wrapper label {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #007bff;
            color: #fff;
            border-radius: 50%;
            padding: 6px;
            cursor: pointer;
        }
        .avatar-wrapper input {
            display: none;
        }
        .text-danger {
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
<div class="container edit-container">
    <h4 class="text-center mb-4"><i class="fas fa-user-edit"></i> Chỉnh sửa thông tin Admin</h4>

    <form method="POST" enctype="multipart/form-data">
        <div class="avatar-wrapper">
            <img id="avatarPreview" src="<?= $avatarFile ?>?t=<?= time() ?>" alt="Avatar"
                 onerror="this.onerror=null;this.src='../assets/img/default-avatar.png';">
            <label for="avatar"><i class="fas fa-camera"></i></label>
            <input type="file" name="avatar" id="avatar" accept="image/*" onchange="previewAvatar(event)">
        </div>

        <div class="form-group">
            <label>Tên đăng nhập</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($account['username']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Họ tên</label>
            <input type="text" name="fullname" class="form-control" required value="<?= htmlspecialchars($account['fullname']) ?>">
            <?php if (isset($errors['fullname'])): ?>
                <div class="text-danger"><?= $errors['fullname'] ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($account['email']) ?>">
            <?php if (isset($errors['email'])): ?>
                <div class="text-danger"><?= $errors['email'] ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Số điện thoại</label>
            <input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($account['phone_number']) ?>">
            <?php if (isset($errors['phone_number'])): ?>
                <div class="text-danger"><?= $errors['phone_number'] ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Địa chỉ</label>
            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($account['address']) ?>">
        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Lưu thay đổi</button>
            <a href="profile.php" class="btn btn-secondary ml-2"><i class="fas fa-arrow-left"></i> Quay lại</a>
        </div>
    </form>
</div>

<script>
    function previewAvatar(event) {
        const reader = new FileReader();
        reader.onload = function() {
            document.getElementById('avatarPreview').src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>
</body>
</html>
