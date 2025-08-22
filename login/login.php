<?php
require_once('../helpers/startSession.php'); // Không gọi startRoleSession ở đây
require_once('../database/config.php');
require_once('../database/dbhelper.php');

// Kết nối CSDL
$con = mysqli_connect(HOST, USERNAME, PASSWORD, DATABASE);
if (!$con) {
    die(" Không kết nối được CSDL: " . mysqli_connect_error());
}

// Biến mặc định
$username = $password = '';
$login_error = '';
$redirect = $_GET['redirect'] ?? '';

//  Xử lý đăng nhập
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $redirect = $_POST['redirect'] ?? '';

    $stmt = $con->prepare("SELECT a.*, r.role_name FROM Account a 
                           JOIN Role r ON a.role_ID = r.role_ID 
                           WHERE a.username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        if ((int)$user['status'] === 2) {
            $login_error = "Tài khoản đã bị vô hiệu hóa, vui lòng liên hệ quản trị viên";
        } elseif (!password_verify($password, $user['password'])) {
            $login_error = "Sai mật khẩu.";
        } else {
            //  Xác định vai trò
            $role = match ((int)$user['role_ID']) {
                1 => 'admin',
                2 => 'staff',
                default => 'customer'
            };

            //  Bắt đầu session phù hợp với vai trò
            startRoleSession($role);

            //  Gán session
            $_SESSION['role'] = $role;
            $_SESSION['account_ID'] = $user['account_ID'];
            $_SESSION['username'] = $username;
            $_SESSION['role_ID'] = $user['role_ID'];
            $_SESSION['role_name'] = $user['role_name'];
            // Nếu là customer → lấy customer_ID và lưu vào session
            if ($role === 'customer') {
                $customer = executeSingleResult("
    SELECT 
    c.customer_ID, 
    a.fullname, 
    a.email 
FROM Customer c
JOIN Account a ON c.account_ID = a.account_ID
WHERE c.account_ID = {$user['account_ID']}
");

                if ($customer) {
                    $_SESSION['customer'] = $customer;
                }
            }

            // Nếu là staff → thêm staff_ID
            if ($role === 'staff') {
                $staff = executeSingleResult("SELECT staff_ID FROM Staff WHERE account_ID = {$user['account_ID']}");
                if ($staff) {
                    $_SESSION['staff_ID'] = $staff['staff_ID'];
                }
            }

            // Ghi cookie
            setcookie("username", $username, time() + 30 * 24 * 60 * 60, '/');
            setcookie("role_ID", $user['role_ID'], time() + 30 * 24 * 60 * 60, '/');

            //  Chuyển hướng
            if ($role === 'customer' && $redirect && !str_contains($redirect, 'http')) {
                header("Location: $redirect");
            } else {
                $target = match ($role) {
                    'admin' => '../admin/pages/index.php',
                    'staff' => '../staff/pages/index.php',
                    default => '../index.php'
                };
                header("Location: $target");
            }
            exit();
        }
    } else {
        $login_error = "Sai tên tài khoản hoặc mật khẩu";
    }
}



?>

<!-- Giao diện đăng nhập -->
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đăng nhập</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(to right, #00c6ff, #0072ff);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-container {
      background-color: #fff;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      width: 100%;
      max-width: 450px;
    }

    .login-container h2 {
      font-weight: 600;
      margin-bottom: 30px;
      color: #333;
    }

    .form-group label {
      font-weight: 500;
    }

    .btn-primary {
      background-color: #007bff;
      border: none;
      font-weight: 500;
      transition: 0.3s ease;
    }

    .btn-primary:hover {
      background-color: #0056b3;
    }

    .form-links {
      margin-top: 20px;
      text-align: center;
    }

    .form-links a {
      color: #007bff;
      text-decoration: none;
      transition: 0.3s;
    }

    .form-links a:hover {
      text-decoration: underline;
    }

    .error-message {
      font-size: 14px;
      color: #dc3545;
      margin-top: 8px;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h2 class="text-center">Đăng nhập</h2>

    <form action="login.php<?= $redirect ? '?redirect=' . urlencode($redirect) : '' ?>" method="POST">
      <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

      <div class="form-group">
        <label for="username">Tài khoản:</label>
        <input type="text" id="username" name="username" class="form-control" required>
      </div>

      <div class="form-group">
        <label for="password">Mật khẩu:</label>
        <input type="password" id="password" name="password" class="form-control" required>
      </div>

      <?php if ($login_error): ?>
        <div class="error-message">
          <?= htmlspecialchars($login_error) ?>
        </div>
      <?php endif; ?>

      <button type="submit" class="btn btn-primary btn-block mt-3">Đăng nhập</button>

      <div class="form-links">
        <a href="reg.php">Đăng ký</a> | <a href="forget.php">Quên mật khẩu?</a>
      </div>
    </form>
  </div>
</body>
</html>

