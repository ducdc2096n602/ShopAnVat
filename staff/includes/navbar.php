<?php
require_once('../../helpers/startSession.php');
startRoleSession('staff');
require_once('../../database/dbhelper.php');


$account_ID = $_SESSION['account_ID'] ?? null;
$fullname = 'Nhân viên'; // Mặc định
$avatarPath = '/ShopAnVat/staff/assets/img/default-avatar.png'; // Avatar mặc định

if ($account_ID) {
    $sql = "SELECT fullname, avatar FROM Account WHERE account_ID = $account_ID";
    $data = executeSingleResult($sql);

    if ($data) {
        $fullname = $data['fullname'] ?? 'Nhân viên';
        $avatar = $data['avatar'] ?? '';

        // Kiểm tra file tồn tại trước khi gán avatar
        $avatarFile = $_SERVER['DOCUMENT_ROOT'] . "/ShopAnVat/uploads/avatar/" . $avatar;
        if (!empty($avatar) && file_exists($avatarFile)) {
            $avatarPath = "/ShopAnVat/uploads/avatar/" . $avatar;
        }
    }
}
?>



<nav class="navbar navbar-light bg-light px-4 justify-content-between">
    <span class="navbar-brand mb-0 h5"></span>
<h1>Trang Nhân Viên Duyệt Đơn</h1>
    <!-- Dropdown tài khoản admin -->
    <div class="dropdown">
        <a class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle"
           href="#" role="button" id="adminDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
           style="max-width: 220px;">
            <img src="<?= $avatarPath ?>" width="60" height="60"
                 class="rounded-circle mr-2 border"
                 alt="Avatar"
                 style="object-fit: cover;"
                 onerror="this.onerror=null;this.src='../assets/img/default-avatar.png';">
            <strong class="text-truncate"><?= htmlspecialchars($fullname) ?></strong>
        </a>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="adminDropdown">
            <a class="dropdown-item" href="../login/profile.php"><i class="fas fa-user-circle"></i> Thông tin cá nhân</a>
            <a class="dropdown-item" href="/ShopAnVat/staff/login/changepassword.php"><i class="fas fa-key"></i> Đổi mật khẩu</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item text-danger" href="../../login/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </div>
</nav>


<!-- JS cần thiết cho Bootstrap dropdown -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
