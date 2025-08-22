<?php
ob_start();  // Bắt đầu output buffering ở đầu file

session_start();  // Đảm bảo session được bắt đầu ngay từ đầu

require_once('../helpers/startSession.php');
require_once(__DIR__ . '/../database/config.php');
require_once(__DIR__ . '/../database/dbhelper.php');
require_once(__DIR__ . '/../utils/utility.php');
require_once('../layout/header.php');

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_COOKIE['username'])) {
    $_SESSION['swal_message'] = [
        'icon' => 'error',
        'title' => 'Lỗi',
        'text' => 'Vui lòng đăng nhập lại!',
        'redirect' => 'login.php'
    ];
    header('Location: login.php');
    exit();
}

$successMsg = '';
$errorMsg = '';

// Xử lý khi gửi form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST["password"];
    $passwordnew = $_POST["password-new"];
    $repasswordnew = $_POST["repassword-new"];
    $username = $_COOKIE['username'];

    $conn = mysqli_connect(HOST, USERNAME, PASSWORD, DATABASE);
    if (!$conn) {
        die("Kết nối thất bại: " . mysqli_connect_error());
    }

    // Lấy mật khẩu hiện tại từ DB
    $stmt = $conn->prepare("SELECT password FROM account WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Kiểm tra mật khẩu hiện tại
    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['swal_message'] = [
            'icon' => 'error',
            'title' => 'Lỗi',
            'text' => 'Mật khẩu hiện tại không đúng!',
            'redirect' => 'changePass.php'
        ];
        header('Location: changePass.php');
        exit();
    }

    // Kiểm tra mật khẩu mới và mật khẩu nhập lại
    if ($passwordnew !== $repasswordnew) {
        $_SESSION['swal_message'] = [
            'icon' => 'error',
            'title' => 'Lỗi',
            'text' => 'Mật khẩu mới không khớp!',
            'redirect' => 'changePass.php'
        ];
        header('Location: changePass.php');
        exit();
    }

    // Kiểm tra độ dài mật khẩu mới
    if (strlen($passwordnew) < 8) {
        $_SESSION['swal_message'] = [
            'icon' => 'error',
            'title' => 'Lỗi',
            'text' => 'Mật khẩu mới phải có ít nhất 8 ký tự!',
            'redirect' => 'changePass.php'
        ];
        header('Location: changePass.php');
        exit();
    }

    // Mã hóa mật khẩu mới
    $hashedNewPass = password_hash($passwordnew, PASSWORD_DEFAULT);

    // Cập nhật mật khẩu mới vào DB
    $update = $conn->prepare("UPDATE account SET password = ? WHERE username = ?");
    $update->bind_param("ss", $hashedNewPass, $username);
    // Cập nhật mật khẩu mới thành công
if ($update->execute()) {
    $_SESSION['swal_message'] = [
        'icon' => 'success',
        'title' => 'Thành công',
        'text' => 'Đổi mật khẩu thành công!',
        // Đổi đường dẫn về trang gốc
        'redirect' => '/ShopAnVat/index.php'
    ];
    header('Location: /ShopAnVat/login/changePass.php');
    exit();  // Dừng thực thi script sau khi chuyển hướng
} else {
    $_SESSION['swal_message'] = [
        'icon' => 'error',
        'title' => 'Lỗi',
        'text' => 'Có lỗi xảy ra. Vui lòng thử lại!',
        'redirect' => '/ShopAnVat/login/changePass.php'
    ];
    header('Location: /ShopAnVat/login/changePass.php');
    exit();  // Dừng thực thi script sau khi chuyển hướng
}

}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đổi mật khẩu</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/header.css">
  <link rel="stylesheet" href="../css/index.css">
  <link rel="stylesheet" href="../css/footer.css">
  <!-- SweetAlert2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.24/dist/sweetalert2.min.css">
  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.24/dist/sweetalert2.min.js"></script>
</head>
<body>

<style>
  body {
    background: linear-gradient(to right, #74ebd5, #ACB6E5);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  .change-pass-box {
    background: #fff;
    padding: 40px 30px;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 500px;
    animation: fadeIn 0.5s ease-in-out;
  }

  .change-pass-box h1 {
    font-size: 28px;
    font-weight: bold;
    text-align: center;
    margin-bottom: 25px;
    color: #333;
  }

  .form-group label {
    font-weight: 500;
  }

  .form-check-label {
    font-size: 14px;
  }

  .btn-primary {
    background-color: #007bff;
    border: none;
    transition: 0.3s;
    font-weight: 500;
  }

  .btn-primary:hover {
    background-color: #0056b3;
  }

  .form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
</style>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
  <form action="" method="POST" class="change-pass-box">
    <h1>Đổi mật khẩu</h1>

    <div class="form-group">
      <label>Mật khẩu hiện tại:</label>
      <input type="password" name="password" class="form-control" placeholder="Mật khẩu hiện tại" required>
    </div>

    <div class="form-group">
      <label>Mật khẩu mới (ít nhất 8 ký tự):</label>
      <input type="password" name="password-new" class="form-control" placeholder="Mật khẩu mới" required minlength="8">
    </div>

    <div class="form-group">
      <label>Nhập lại mật khẩu mới:</label>
      <input type="password" name="repassword-new" class="form-control" placeholder="Nhập lại mật khẩu mới" required minlength="8">
    </div>

    <div class="form-check mb-3">
      <input type="checkbox" class="form-check-input" id="agree" required>
      <label class="form-check-label" for="agree">Tôi đồng ý với các điều khoản</label>
    </div>

    <div class="text-right">
      <input type="submit" name="submit" class="btn btn-primary px-4 py-2" value="Xác nhận">
    </div>
  </form>
</div>


<!-- Thông báo bằng SweetAlert -->
<?php
if (isset($_SESSION['swal_message'])) {
    $swal_message = $_SESSION['swal_message'];
    echo "<script>
            Swal.fire({
                icon: '{$swal_message['icon']}',
                title: '{$swal_message['title']}',
                text: '{$swal_message['text']}',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location = '{$swal_message['redirect']}';
            });
          </script>";
    unset($_SESSION['swal_message']); // Xóa thông báo sau khi hiển thị
}
?>

</body>
</html>

<?php
ob_end_flush();  // Kết thúc output buffering
?>
