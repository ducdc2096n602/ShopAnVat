<?php
require_once('../../helpers/startSession.php');
startRoleSession('staff');
require_once('../../database/config.php');
require_once('../../database/dbhelper.php');


if (!isset($_SESSION['account_ID']) || $_SESSION['role_ID'] != 2) {
    header('Location: ../../login/login.php');
    exit();
}

$successMsg = '';
$errorMsg = '';

// Xử lý khi gửi form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST["password"];
    $passwordnew = $_POST["password-new"];
    $repasswordnew = $_POST["repassword-new"];
    $account_ID = $_SESSION['account_ID'];

    $user = executeSingleResult("SELECT password FROM account WHERE account_ID = $account_ID");

    if (!$user || !password_verify($password, $user['password'])) {
        $errorMsg = "Mật khẩu hiện tại không đúng!";
    } elseif ($passwordnew !== $repasswordnew) {
        $errorMsg = "Mật khẩu mới không khớp!";
    } elseif (strlen($passwordnew) < 8) {
        $errorMsg = "Mật khẩu mới phải có ít nhất 8 ký tự!";
    } else {
        $hashedNewPass = password_hash($passwordnew, PASSWORD_DEFAULT);
        execute("UPDATE account SET password = '$hashedNewPass' WHERE account_ID = $account_ID");
        $successMsg = "Đổi mật khẩu thành công!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đổi mật khẩu</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    <p class="mt-3 text-right"><a href="reg.php">Quên mật khẩu?</a></p>
  </form>
</div>


<!-- Thông báo bằng SweetAlert -->
<?php if ($errorMsg): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Lỗi',
        text: <?= json_encode($errorMsg) ?>,
        confirmButtonText: 'Thử lại'
    });
</script>
<?php elseif ($successMsg): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Thành công',
        text: <?= json_encode($successMsg) ?>,
        confirmButtonText: 'OK'
    }).then(() => {
        window.location = 'profile.php';
    });
</script>
<?php endif; ?>
</body>
</html>
