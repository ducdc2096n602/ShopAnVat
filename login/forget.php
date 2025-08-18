<?php
ob_start();  // Bắt đầu output buffering
require_once(__DIR__ . '/../helpers/startSession.php');
require_once(__DIR__ . '/../database/config.php');
require_once(__DIR__ . '/../PHPMailer-master/src/PHPMailer.php');
require_once(__DIR__ . '/../PHPMailer-master/src/SMTP.php');
require_once(__DIR__ . '/../PHPMailer-master/src/Exception.php');
date_default_timezone_set('Asia/Ho_Chi_Minh');

use PHPMailer\PHPMailer\PHPMailer;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quên mật khẩu</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(to right, #74ebd5, #ACB6E5);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .forgot-box {
      background-color: #fff;
      padding: 40px 30px;
      border-radius: 12px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
      width: 100%;
      max-width: 500px;
      animation: fadeIn 0.6s ease-in-out;
    }

    h3 {
      font-weight: 600;
      text-align: center;
      margin-bottom: 25px;
      color: #333;
    }

    .form-group label {
      font-weight: 500;
    }

    .btn {
      font-weight: 500;
    }

    .btn-primary {
      background-color: #007bff;
      border: none;
      transition: 0.3s;
    }

    .btn-primary:hover {
      background-color: #0056b3;
    }

    .btn-secondary {
      transition: 0.3s;
    }

    .btn-secondary:hover {
      background-color: #6c757d;
    }
  </style>
</head>
<body>
  <div class="forgot-box">
    <form method="POST" action="">
      <h3>Khôi phục mật khẩu</h3>

      <div class="form-group">
        <label>Nhập email đã đăng ký:</label>
        <input class="form-control" type="email" name="email" placeholder="you@example.com" required />
      </div>

      <div class="text-right">
        <button type="submit" name="send" class="btn btn-primary">Gửi yêu cầu</button>
        <a href="login.php" class="btn btn-secondary">Quay lại đăng nhập</a>
      </div>
    </form>
  </div>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = $_POST['email'];
    $conn = mysqli_connect(HOST, USERNAME, PASSWORD, DATABASE);
    mysqli_set_charset($conn, 'utf8');

    // Tìm tài khoản theo email
    $stmt = $conn->prepare("SELECT username FROM account WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $account = $result->fetch_assoc();

    if (!$account) {
        // Hiển thị thông báo lỗi nếu không tìm thấy email
        echo "<script>
                window.onload = function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi!',
                        text: 'Email không tồn tại trong hệ thống!',
                        confirmButtonColor: '#d33',
                        background: '#ffebeb',
                        showClass: {
                            popup: 'animate__animated animate__fadeInDown'
                        },
                        hideClass: {
                            popup: 'animate__animated animate__fadeOutUp'
                        }
                    });
                };
              </script>";
    } else {
        // Tạo token và thời hạn
        $token = bin2hex(random_bytes(32));
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $expired_at = date('Y-m-d H:i:s', time() + 3600);

        // Cập nhật vào bảng account
        $stmt = $conn->prepare("UPDATE account SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $stmt->bind_param("sss", $token, $expired_at, $email);
        $stmt->execute();

        // Tạo link reset
        $resetLink = "http://localhost:8080/ShopAnVat/login/reset_password.php?token=$token";

        // Gửi email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->CharSet = "utf-8";
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ducdc2096n602@vlvh.ctu.edu.vn'; // thay bằng email thật
            $mail->Password = 'ojdm dwzo ulhc lalt'; // thay bằng mật khẩu ứng dụng
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom('ducdc2096n602@vlvh.ctu.edu.vn', 'Hệ thống Shop Ăn Vặt');
            $mail->addAddress($email, $account['username']);
            $mail->isHTML(true);
            $mail->Subject = "Yêu cầu đặt lại mật khẩu";
            $mail->Body = "
                <p>Xin chào <strong>{$account['username']}</strong>,</p>
                <p>Bạn đã yêu cầu đặt lại mật khẩu. Nhấn vào link bên dưới để thiết lập lại:</p>
                <p><a href='$resetLink'>$resetLink</a></p>
                <p>Link sẽ hết hạn sau 1 giờ.</p>
            ";

            $mail->send();
            echo "<script>
                    window.onload = function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công!',
                            text: 'Đã gửi email đặt lại mật khẩu! Vui lòng kiểm tra hộp thư.',
                            confirmButtonColor: '#28a745',
                            background: '#e7f5e7',
                            showClass: {
                                popup: 'animate__animated animate__fadeInDown'
                            },
                            hideClass: {
                                popup: 'animate__animated animate__fadeOutUp'
                            }
                        });
                    };
                  </script>";
        } catch (Exception $e) {
            echo "<script>
                    window.onload = function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi!',
                            text: 'Gửi email thất bại: " . $mail->ErrorInfo . "',
                            confirmButtonColor: '#d33',
                            background: '#ffebeb',
                            showClass: {
                                popup: 'animate__animated animate__fadeInDown'
                            },
                            hideClass: {
                                popup: 'animate__animated animate__fadeOutUp'
                            }
                        });
                    };
                  </script>";
        }
    }
}
?>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.24/dist/sweetalert2.min.js"></script>
<!-- Add Animate.css for animations -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

</body>
</html>
