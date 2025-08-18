<?php
require_once('../../../../helpers/startSession.php');
startRoleSession('admin');
require_once('../../../../database/dbhelper.php');

$account_ID = isset($_GET['account_ID']) ? (int)$_GET['account_ID'] : 0;
$errors = [];
$data = [
    'username'     => '',
    'fullname'     => '',
    'phone_number' => '',
    'email'        => '',
    'status'       => 1
];

// Khi submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_ID   = (int)($_POST['account_ID'] ?? 0);
    $fullname     = addslashes($_POST['fullname'] ?? '');
    $phone_number = addslashes($_POST['phone_number'] ?? '');
    $email        = addslashes($_POST['email'] ?? '');
    $status       = intval($_POST['status'] ?? 1);
    $password     = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    // Lấy username hiện tại
    $currentAccount = executeSingleResult("SELECT username FROM Account WHERE account_ID = $account_ID");
    if ($currentAccount) {
        $data['username'] = $currentAccount['username'];
    }

    // Giữ dữ liệu nhập
    $data = array_merge($data, compact('fullname', 'phone_number', 'email', 'status'));

    // --- Ràng buộc ---
    if (empty($fullname)) {
        $errors['fullname'] = 'Họ tên không được để trống.';
    } elseif (strlen($fullname) > 100) {
        $errors['fullname'] = 'Họ tên không được vượt quá 100 ký tự.';
    }

    if (empty($phone_number)) {
        $errors['phone_number'] = 'Số điện thoại không được để trống.';
    } elseif (!preg_match('/^[0-9]{10,11}$/', $phone_number)) {
        $errors['phone_number'] = 'Số điện thoại không hợp lệ. Vui lòng nhập 10 hoặc 11 chữ số.';
    } else {
        $existingPhone = executeSingleResult("SELECT account_ID FROM Account WHERE phone_number = '$phone_number' AND account_ID != $account_ID");
        if ($existingPhone) {
            $errors['phone_number'] = 'Số điện thoại này đã được sử dụng bởi tài khoản khác.';
        }
    }

    if (empty($email)) {
        $errors['email'] = 'Email không được để trống.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Địa chỉ email không hợp lệ.';
    } else {
        $existingEmail = executeSingleResult("SELECT account_ID FROM Account WHERE email = '$email' AND account_ID != $account_ID");
        if ($existingEmail) {
            $errors['email'] = 'Email này đã được sử dụng bởi tài khoản khác.';
        }
    }

    $passwordSQL = '';
    if (empty($password)) {
    $errors['password'] = 'Mật khẩu mới không được để trống.';
} elseif (strlen($password) < 8) {
    $errors['password'] = 'Mật khẩu phải có ít nhất 8 ký tự.';
} elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]+$/', $password)) {
    $errors['password'] = 'Mật khẩu phải chứa ít nhất một chữ cái và một số.';
}


    if (empty($confirm_pass)) {
        $errors['confirm_password'] = 'Xác nhận mật khẩu không được để trống.';
    } elseif ($password !== $confirm_pass) {
        $errors['confirm_password'] = 'Mật khẩu xác nhận không khớp.';
    }

    if (!isset($errors['password']) && !isset($errors['confirm_password'])) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $passwordSQL = ", password = '$hash'";
    }

    // --- Nếu không có lỗi ---
    if (empty($errors)) {
        $sqlAcc = "UPDATE Account SET
                        fullname = '$fullname',
                        phone_number = '$phone_number',
                        email = '$email',
                        status = $status
                        $passwordSQL
                   WHERE account_ID = $account_ID";
        execute($sqlAcc);

        // Kiểm tra đã có bản ghi Customer chưa
        $customer = executeSingleResult("SELECT * FROM Customer WHERE account_ID = $account_ID");
        if (!$customer) {
            execute("INSERT INTO Customer (account_ID) VALUES ($account_ID)");
        }

        $_SESSION['message'] = 'Cập nhật thông tin khách hàng thành công!';
        header('Location: listcustomer.php');
        exit();
    }
}

// Load dữ liệu lần đầu
if ($account_ID > 0 && empty($_POST)) {
    $acc = executeSingleResult("SELECT username, fullname, phone_number, email, status FROM Account WHERE account_ID = $account_ID");

    if (!$acc) {
        $_SESSION['message_error'] = 'Không tìm thấy khách hàng cần sửa!';
        header('Location: listcustomer.php');
        exit();
    }

    $data = array_merge($data, $acc);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa thông tin khách hàng</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .error-message { color: red; font-size: 0.875em; margin-top: 5px; }
        .form-control.is-invalid + .invalid-feedback { display: block; }
    </style>
</head>
<body>
<div class="container mt-5">
    <a href="listcustomer.php" class="btn btn-primary mb-3">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách
    </a>
    <h3 class="text-primary text-center">Sửa thông tin khách hàng</h3>

    <?php
    if (isset($_SESSION['message_error'])) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: '{$_SESSION['message_error']}',
                confirmButtonText: 'Đóng'
            });
        </script>";
        unset($_SESSION['message_error']);
    }
    ?>

    <form method="POST">
        <input type="hidden" name="account_ID" value="<?= $account_ID ?>">

        <div class="form-group">
            <label>Tên đăng nhập:</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($data['username']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Mật khẩu mới:</label>
            <input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" placeholder="Ít nhất 8 ký tự" required>
            <?php if (isset($errors['password'])): ?>
                <div class="error-message invalid-feedback"><?= $errors['password'] ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Nhập lại mật khẩu mới:</label>
            <input type="password" name="confirm_password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" placeholder="Xác nhận mật khẩu mới" required>
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
            <label>Trạng thái:</label>
            <select name="status" class="form-control">
                <option value="1" <?= $data['status'] == 1 ? 'selected' : '' ?>>Đang hoạt động</option>
                <option value="2" <?= $data['status'] == 2 ? 'selected' : '' ?>>Vô hiệu hóa</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
        <a href="listcustomer.php" class="btn btn-secondary ml-2">← Quay lại</a>
    </form>
</div>
</body>
</html>
