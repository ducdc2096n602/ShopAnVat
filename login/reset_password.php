<?php
require_once(__DIR__ . '/../database/config.php');
require_once(__DIR__ . '/../database/dbhelper.php');
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Lấy token từ URL
$token = $_GET['token'] ?? '';

// Kết nối DB
$conn = mysqli_connect(HOST, USERNAME, PASSWORD, DATABASE);
mysqli_set_charset($conn, 'utf8');

// Kiểm tra token hợp lệ và chưa hết hạn
$stmt = $conn->prepare("SELECT * FROM account WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$account = $result->fetch_assoc();

if (!$account) {
    die('<div class="container text-center mt-5">
            <h3 class="text-danger">Token không hợp lệ hoặc đã hết hạn!</h3>
            <a href="forget.php" class="btn btn-outline-primary mt-3">Quay lại quên mật khẩu</a>
         </div>');
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thiết lập mật khẩu mới</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Quicksand', sans-serif;
            background: linear-gradient(135deg, #f8f9fa, #e0f7fa);
        }

        .form-container {
            background-color: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .form-title {
            font-weight: bold;
            margin-bottom: 30px;
        }

        .btn-primary {
            width: 100%;
        }

        .form-group label {
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="form-container w-100" style="max-width: 500px;">
        <h3 class="form-title text-center">Thiết lập mật khẩu mới</h3>

        <form method="POST" action="">
            <div class="form-group">
                <label>Mật khẩu mới:</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Nhập lại mật khẩu mới:</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" name="reset" class="btn btn-primary mt-3">Xác nhận</button>
        </form>
    </div>
</div>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset'])) {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    echo '<script>';

    if ($newPassword !== $confirmPassword) {
        echo 'Swal.fire("Lỗi", "Mật khẩu không khớp!", "error");';
    } elseif (strlen($newPassword) < 6) {
        echo 'Swal.fire("Lỗi", "Mật khẩu phải có ít nhất 6 ký tự!", "warning");';
    } else {
        // Băm mật khẩu mới
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

        // Cập nhật mật khẩu và xoá token
        $stmt = $conn->prepare("UPDATE account SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
        $stmt->bind_param("ss", $hashed, $token);
        $stmt->execute();

        echo 'Swal.fire({
                title: "Thành công!",
                text: "Mật khẩu đã được cập nhật.",
                icon: "success",
                confirmButtonText: "Đăng nhập",
            }).then(() => {
                window.location.href = "login.php";
            });';
    }

    echo '</script>';
}
?>
</body>
</html>
