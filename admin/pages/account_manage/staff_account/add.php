<?php
require_once('../../../../helpers/startSession.php');
startRoleSession('admin'); 
require_once('../../../../database/dbhelper.php');


$errors = []; // Thay thế $error bằng một mảng để lưu lỗi theo từng trường
$data = [
    'username' => '',
    'fullname' => '',
    'phone_number' => '',
    'email' => '',
    'status' => 1,
    'position' => '',
    'started_date' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = addslashes($_POST['username'] ?? '');
    $fullname = addslashes($_POST['fullname'] ?? '');
    $phone_number = addslashes($_POST['phone_number'] ?? '');
    $email = addslashes($_POST['email'] ?? '');
    $status = intval($_POST['status'] ?? 1);
    $position = addslashes($_POST['position'] ?? '');
    $started_date = $_POST['started_date'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $data = compact('username', 'fullname', 'phone_number', 'email', 'status', 'position', 'started_date');

    // --- Bắt đầu kiểm tra ràng buộc và lưu lỗi vào mảng $errors ---

    if (empty($username)) {
        $errors['username'] = 'Tên đăng nhập không được để trống.';
    }
    if (empty($password)) {
        $errors['password'] = 'Mật khẩu không được để trống.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Mật khẩu phải có ít nhất 8 ký tự.';
    }
    if (empty($confirm_password)) {
        $errors['confirm_password'] = 'Vui lòng nhập lại mật khẩu.';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Mật khẩu xác nhận không khớp.';
    }
    if (empty($fullname)) {
        $errors['fullname'] = 'Họ tên không được để trống.';
    } elseif (strlen($fullname) > 100) {
        $errors['fullname'] = 'Họ tên không được vượt quá 100 ký tự.';
    }
    if (empty($phone_number)) {
        $errors['phone_number'] = 'Số điện thoại không được để trống.';
    } elseif (!preg_match('/^[0-9]{10,11}$/', $phone_number)) {
        $errors['phone_number'] = 'Số điện thoại không hợp lệ. Vui lòng nhập 10 hoặc 11 chữ số.';
    }
    if (empty($email)) {
        $errors['email'] = 'Email không được để trống.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Địa chỉ email không hợp lệ.';
    }

    // Kiểm tra trùng lặp CHỈ KHI các lỗi cơ bản đã được xử lý (tránh truy vấn DB không cần thiết)
    if (empty($errors['username'])) {
        $existingUsername = executeSingleResult("SELECT account_ID FROM Account WHERE username = '$username'");
        if ($existingUsername) {
            $errors['username'] = 'Tên đăng nhập đã tồn tại! Vui lòng chọn tên đăng nhập khác.';
        }
    }

    if (empty($errors['email'])) {
        $existingEmail = executeSingleResult("SELECT account_ID FROM Account WHERE email = '$email'");
        if ($existingEmail) {
            $errors['email'] = 'Email này đã được sử dụng cho một tài khoản khác.';
        }
    }

    if (empty($errors['phone_number'])) {
        $existingPhone = executeSingleResult("SELECT account_ID FROM Account WHERE phone_number = '$phone_number'");
        if ($existingPhone) {
            $errors['phone_number'] = 'Số điện thoại này đã được sử dụng cho một tài khoản khác.';
        }
    }

    // --- Kết thúc kiểm tra ràng buộc ---

    if (empty($errors)) { // Nếu không có lỗi nào
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO Account (username, password, fullname, phone_number, email, status, role_ID)
                VALUES ('$username', '$hashedPassword', '$fullname', '$phone_number', '$email', $status, 2)";
        execute($sql);

        $account_ID = executeSingleResult("SELECT account_ID FROM Account WHERE username = '$username'")['account_ID'];

        $sql = "INSERT INTO Staff (account_ID, position, started_date)
                VALUES ($account_ID, '$position', '$started_date')";
        execute($sql);

        // Chuyển hướng và thông báo thành công (có thể dùng $_SESSION['message'] và hiển thị thông báo trên liststaff.php)
        $_SESSION['message'] = 'Thêm nhân viên thành công!';
        header('Location: liststaff.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm nhân viên</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .error-message {
            color: red;
            font-size: 0.875em;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <a href="javascript:history.back()" class="btn btn-primary m-3">
        <i class="fas fa-arrow-left mr-1"></i> Quay Lại
    </a>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-info text-white text-center">
                <h4>Thêm nhân viên mới</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label>Tên đăng nhập:</label>
                        <input type="text" name="username" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($data['username']) ?>" required>
                        <?php if (isset($errors['username'])): ?>
                            <div class="error-message invalid-feedback"><?= $errors['username'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Mật khẩu:</label>
                        <input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" placeholder="Ít nhất 8 ký tự" minlength="8" required>
                        <?php if (isset($errors['password'])): ?>
                            <div class="error-message invalid-feedback"><?= $errors['password'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Nhập lại mật khẩu:</label>
                        <input type="password" name="confirm_password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" placeholder="Xác nhận mật khẩu" minlength="8" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="error-message invalid-feedback"><?= $errors['confirm_password'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Họ tên:</label>
                        <input type="text" name="fullname" class="form-control <?= isset($errors['fullname']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($data['fullname']) ?>" required>
                        <?php if (isset($errors['fullname'])): ?>
                            <div class="error-message invalid-feedback"><?= $errors['fullname'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Số điện thoại:</label>
                        <input type="text" name="phone_number" class="form-control <?= isset($errors['phone_number']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($data['phone_number']) ?>" required>
                        <?php if (isset($errors['phone_number'])): ?>
                            <div class="error-message invalid-feedback"><?= $errors['phone_number'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($data['email']) ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="error-message invalid-feedback"><?= $errors['email'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Vị trí (chức vụ):</label>
                        <input type="text" name="position" class="form-control" value="<?= htmlspecialchars($data['position']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Ngày bắt đầu làm việc:</label>
                        <input type="date" name="started_date" class="form-control" value="<?= htmlspecialchars($data['started_date']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Trạng thái:</label>
                        <select name="status" class="form-control">
                            <option value="1" <?= $data['status'] == 1 ? 'selected' : '' ?>>Đang hoạt động</option>
                            <option value="2" <?= $data['status'] == 2 ? 'selected' : '' ?>>Vô hiệu hóa</option>
                        </select>
                    </div>

                    <button type="submit" class="btn <?= empty($id) ? 'btn-success' : 'btn-primary' ?>">
                    <?= empty($id) ? 'Thêm' : 'Lưu' ?>
                </button>
                    <a href="liststaff.php" class="btn btn-secondary ml-2">← Quay lại danh sách</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>