<?php
require_once('../../database/dbhelper.php');
require_once('../../helpers/startSession.php');
startRoleSession('admin');

if (!isset($_SESSION['account_ID']) || $_SESSION['role_ID'] != 1) {
    header('Location: ../../login/login.php');
    exit();
}

$account_ID = $_SESSION['account_ID'];
$sql = "SELECT a.*, r.role_name FROM Account a 
        LEFT JOIN Role r ON a.role_ID = r.role_ID 
        WHERE a.account_ID = $account_ID";
$account = executeSingleResult($sql);

if (!$account) {
    echo 'Không tìm thấy tài khoản.';
    exit();
}

$avatarPath = '../../assets/img/default-avatar.png';
if (!empty($account['avatar']) && file_exists('../../uploads/avatar/' . $account['avatar'])) {
    $avatarPath = '../../uploads/avatar/' . $account['avatar'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Thông tin tài khoản (Admin)</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    body {
      background-color: #f2f4f8;
    }
    .profile-container {
      max-width: 900px;
      margin: 50px auto;
    }
    .profile-header {
      text-align: center;
      margin-bottom: 30px;
    }
    .avatar-img {
      width: 130px;
      height: 130px;
      object-fit: cover;
      border-radius: 50%;
      border: 4px solid #fff;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      margin-bottom: 15px;
    }
    .card {
      border: 1px solid #ddd;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .info-label {
      font-weight: 500;
      color: #555;
    }
    .info-value {
      font-weight: 600;
      color: #222;
    }
    .btn-group-custom {
      text-align: center;
      margin-top: 25px;
    }
    .btn-custom {
      min-width: 150px;
    }
  </style>
</head>
<body>

<div class="container profile-container">
  <a href="../pages/index.php" class="btn btn-outline-primary mb-3"><i class="fas fa-home"></i> Trang chủ</a>

  <div class="profile-header">
    <img src="<?= $avatarPath ?>" alt="Avatar" class="avatar-img"
         onerror="this.onerror=null;this.src='../../assets/img/default-avatar.png';">
    <h3 class="mt-2">Thông tin Quản trị viên</h3>
    <p class="text-muted mb-0"><?= htmlspecialchars($account['role_name']) ?></p>
  </div>

  <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success text-center"><?= $_SESSION['message'] ?></div>
    <?php unset($_SESSION['message']); ?>
  <?php endif; ?>

  <div class="card p-4">
    <div class="row mb-3">
      <div class="col-md-4 info-label">Họ tên:</div>
      <div class="col-md-8 info-value"><?= htmlspecialchars($account['fullname'] ?? 'Chưa có') ?></div>
    </div>

    <div class="row mb-3">
      <div class="col-md-4 info-label">Tên đăng nhập:</div>
      <div class="col-md-8 info-value"><?= htmlspecialchars($account['username']) ?></div>
    </div>

    <div class="row mb-3">
      <div class="col-md-4 info-label">Email:</div>
      <div class="col-md-8 info-value"><?= htmlspecialchars($account['email']) ?></div>
    </div>

    <div class="row mb-3">
      <div class="col-md-4 info-label">Số điện thoại:</div>
      <div class="col-md-8 info-value"><?= htmlspecialchars($account['phone_number'] ?? 'Chưa có') ?></div>
    </div>

    <div class="row mb-3">
      <div class="col-md-4 info-label">Địa chỉ:</div>
      <div class="col-md-8 info-value"><?= htmlspecialchars($account['address'] ?? 'Chưa có') ?></div>
    </div>

    <div class="btn-group-custom">
      <a href="edit_profile.php" class="btn btn-primary btn-custom"><i class="fas fa-edit"></i> Chỉnh sửa thông tin</a>
     
    </div>
  </div>
</div>

</body>
</html>
