<?php
require_once('../helpers/startSession.php');
startRoleSession('customer');
require_once('../database/config.php');
require_once('../database/dbhelper.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $errors = []; // Mảng chứa lỗi

  // Kiểm tra các trường không để trống
  if (
    isset($_POST['submit']) &&
    !empty($_POST['name']) &&
    !empty($_POST['username']) &&
    !empty($_POST['password']) &&
    !empty($_POST['repassword']) &&
    !empty($_POST['phone_number']) &&
    !empty($_POST['email'])
  ) {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $repassword = $_POST['repassword'];
    $phone_number = $_POST['phone_number'];
    $email = $_POST['email'];

    // Kiểm tra định dạng email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors['email'] = "Địa chỉ email không hợp lệ!";
    }

    // Kiểm tra định dạng số điện thoại (10 số, bắt đầu bằng 0)
    if (!preg_match('/^0[0-9]{9}$/', $phone_number)) {
      $errors['phone_number'] = "Số điện thoại không hợp lệ. Vui lòng nhập 10 hoặc 11 chữ số.";
    }

    // Kiểm tra mật khẩu: ít nhất 8 ký tự, có chữ cái và số
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
      $errors['password'] = "Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ cái và số.";
    }

    // Kiểm tra mật khẩu có khớp không
    if ($password !== $repassword) {
      $errors['password'] = "Mật khẩu xác nhận không khớp.";
    }

    // Kết nối cơ sở dữ liệu
    $conn = mysqli_connect(HOST, USERNAME, PASSWORD, DATABASE);
    if (!$conn) {
      die("Connection failed: " . mysqli_connect_error());
    }

    // Kiểm tra tài khoản đã tồn tại
    $stmt_username = $conn->prepare("SELECT * FROM account WHERE username = ?");
    $stmt_username->bind_param("s", $username);
    $stmt_username->execute();
    $result_username = $stmt_username->get_result();
    if ($result_username->num_rows > 0) {
      $errors['account'] = "Tài khoản đã tồn tại!";
    }

    // Kiểm tra email đã tồn tại
    $stmt_email = $conn->prepare("SELECT * FROM account WHERE email = ?");
    $stmt_email->bind_param("s", $email);
    $stmt_email->execute();
    $result_email = $stmt_email->get_result();
    if ($result_email->num_rows > 0) {
      $errors['email'] = "Email đã được sử dụng. Vui lòng dùng email khác";
    }

    // Kiểm tra số điện thoại đã tồn tại
    $stmt_phone = $conn->prepare("SELECT * FROM account WHERE phone_number = ?");
    $stmt_phone->bind_param("s", $phone_number);
    $stmt_phone->execute();
    $result_phone = $stmt_phone->get_result();
    if ($result_phone->num_rows > 0) {
      $errors['phone_number'] = "Số điện thoại đã tồn tại!";
    }

    // Nếu không có lỗi, thực hiện đăng ký
    if (empty($errors)) {
      // Mã hóa mật khẩu
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
      $defaultRoleId = 3; // Khách hàng

      $stmt = $conn->prepare("INSERT INTO account (fullname, username, password, phone_number, email, role_ID)
                              VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("sssssi", $name, $username, $hashedPassword, $phone_number, $email, $defaultRoleId);
      if ($stmt->execute()) {
        $account_ID = $conn->insert_id;

        // Nếu là khách hàng thì thêm vào bảng Customer
        if ($defaultRoleId == 3) {
          $stmt2 = $conn->prepare("INSERT INTO customer (account_ID) VALUES (?)");
          $stmt2->bind_param("i", $account_ID);
          $stmt2->execute();
        }

        $successMessage = "Đăng ký thành công! Vui lòng đăng nhập.";
      } else {
        $errors['register'] = "Đăng ký thất bại! Vui lòng thử lại.";
      }
    }
  } else {
    $errors['empty'] = "Vui lòng nhập đầy đủ thông tin!";
  }
}
?>


<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đăng ký tài khoản</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(120deg, #89f7fe, #66a6ff);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .register-box {
      background-color: #fff;
      padding: 40px 30px;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 500px;
    }

    .register-box h2 {
      text-align: center;
      margin-bottom: 25px;
      font-weight: 600;
      color: #333;
    }

    .form-group label {
      font-weight: 500;
    }

    .required::after {
      content: " *";
      color: red;
    }

    .btn-primary {
      background-color: #007bff;
      border: none;
      font-weight: 500;
      transition: background-color 0.3s ease;
    }

    .btn-primary:hover {
      background-color: #0056b3;
    }

    .text-danger {
      font-size: 14px;
    }

    .login-link {
      margin-top: 20px;
      text-align: center;
    }

    .login-link a {
      color: #007bff;
      text-decoration: none;
    }

    .login-link a:hover {
      text-decoration: underline;
    }

    .register-box input[type="text"],
    .register-box input[type="email"],
    .register-box input[type="password"],
    .register-box input[type="tel"],
    .register-box select {
  border: 2px solid #000;   /* viền đen đậm */
  padding: 10px;
  border-radius: 6px;
  font-size: 15px;
}

  </style>
</head>
<body>

<div class="register-box">
  <h2>Đăng ký tài khoản</h2>
  <form action="reg.php" method="POST">
    <div class="form-group">
      <label class="required">Họ và tên:</label>
      <input type="text" name="name" class="form-control" value="<?= isset($name) ? $name : '' ?>" required>
      <?php if (isset($errors['name'])): ?>
        <div class="text-danger"><?= $errors['name'] ?></div>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label class="required">Tài khoản:</label>
      <input type="text" name="username" class="form-control" value="<?= isset($username) ? $username : '' ?>" required>
      <?php if (isset($errors['account'])): ?>
        <div class="text-danger"><?= $errors['account'] ?></div>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label class="required">Mật khẩu:</label>
      <input type="password" name="password" class="form-control" required>
      <?php if (isset($errors['password'])): ?>
        <div class="text-danger"><?= $errors['password'] ?></div>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label class="required">Nhập lại mật khẩu:</label>
      <input type="password" name="repassword" class="form-control" required>
      <?php if (isset($errors['password'])): ?>
        <div class="text-danger"><?= $errors['password'] ?></div>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label class="required">Số điện thoại:</label>
      <input type="text" name="phone_number" class="form-control" value="<?= isset($phone_number) ? $phone_number : '' ?>" required>
      <?php if (isset($errors['phone_number'])): ?>
        <div class="text-danger"><?= $errors['phone_number'] ?></div>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label class="required">Email:</label>
      <input type="email" name="email" class="form-control" value="<?= isset($email) ? $email : '' ?>" required>
      <?php if (isset($errors['email'])): ?>
        <div class="text-danger"><?= $errors['email'] ?></div>
      <?php endif; ?>
    </div>

    <button type="submit" name="submit" class="btn btn-primary btn-block">Đăng ký</button>

    <?php if (isset($errors['empty'])): ?>
      <div class="text-danger text-center mt-3"><?= $errors['empty'] ?></div>
    <?php endif; ?>

    <div class="login-link">
      <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
    </div>
  </form>
</div>

<?php if (isset($successMessage)): ?>
  <script>
    Swal.fire({
      icon: 'success',
      title: 'Thành công!',
      text: '<?= $successMessage ?>',
      confirmButtonText: 'Đăng nhập ngay'
    }).then(() => {
      window.location.href = 'login.php';
    });
  </script>
<?php endif; ?>

</body>
</html>
