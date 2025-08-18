<?php
require_once(__DIR__ . '/../database/dbhelper.php');

$avatarPath = '/ShopAnVat/admin/assets/img/default-avatar.png';
$username = '';

// Nếu có cookie username
if (isset($_COOKIE['username'])) {
    $username = htmlspecialchars($_COOKIE['username']);

    // Lấy avatar từ DB
    $account = executeSingleResult("SELECT avatar FROM Account WHERE username = '$username'");

    if ($account && !empty($account['avatar']) && file_exists(__DIR__ . '/../uploads/avatar/' . $account['avatar'])) {
        $avatarPath = '/ShopAnVat/uploads/avatar/' . $account['avatar'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ăn vặt DHL</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS -->
    <link rel="stylesheet" href="/ShopAnVat/css/header.css">
    <link rel="stylesheet" href="/ShopAnVat/css/index.css">
    <!-- Font Awesome 6.5 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- JS -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .account-dropdown {
            position: relative;
        }
        .dropdown-trigger {
            display: flex;
            align-items: center;
            cursor: pointer;
            color: #000;
        }
        .dropdown-trigger img {
            border-radius: 50%;
            width: 32px;
            height: 32px;
            object-fit: cover;
            margin-right: 8px;
        }
        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 120%;
            display: none;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 5px;
            z-index: 999;
        }
        .dropdown-menu.show {
            display: block;
        }
        .dropdown-menu a {
            display: block;
            padding: 10px 16px;
            color: #333;
            text-decoration: none;
            white-space: nowrap;
        }
        .dropdown-menu a:hover {
            background-color: #f0f0f0;
        }
    </style>
</head>

<body>
<div id="wrapper">
    <header>
        <div class="container">
            <!-- Logo -->
            <section class="logo">
                <img src="/ShopAnVat/images/logo2.png" alt="Logo" style="height: 50px;">
            </section>

            <!-- Menu -->
            <nav>
                <ul>
                    <li><a href="/ShopAnVat/index.php">Trang chủ</a></li>
                    <li><a href="/ShopAnVat/about.php">Về chúng tôi</a></li>
                    <li><a href="/ShopAnVat/news.php">Tin tức</a></li>
                    <li><a href="/ShopAnVat/voucher.php">Voucher</a></li>
                </ul>
            </nav>

            <!-- Giỏ hàng + Tài khoản -->
            <section class="menu-right">
                <!-- Cart -->
                <div class="cart">
                    <a href="/ShopAnVat/cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart" id="cart-icon"></i>
                        <span class="cart-count" id="cart-count">0</span>
                    </a>
                </div>

                <!-- Account -->
                <div class="account-dropdown">
                    <?php if (!empty($username)): ?>
                        <div class="dropdown-trigger" onclick="toggleDropdownMenu()">
                            <img src="<?= $avatarPath ?>" alt="Avatar"
                                 onerror="this.onerror=null;this.src='/ShopAnVat/admin/assets/img/default-avatar.png';">
                            <?= $username ?> <i class="fas fa-caret-down small-arrow ml-1"></i>
                        </div>
                        <div class="dropdown-menu" id="dropdownMenu">
                            <a href="/ShopAnVat/login/profile.php"><i class="fas fa-user-circle"></i> Thông tin tài khoản</a>
                            <a href="/ShopAnVat/history.php"><i class="fas fa-history"></i> Lịch sử đặt hàng</a>
                            <a href="/ShopAnVat/my_voucher.php"><i class="fas fa-ticket-alt"></i> Ví Voucher</a>
                            <a href="/ShopAnVat/login/changePass.php"><i class="fas fa-key"></i> Đổi mật khẩu</a>
                            <a href="#" class="text-danger" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                        </div>
                    <?php else: ?>
                        <a href="/ShopAnVat/login/login.php"><i class="fas fa-user"></i> Đăng nhập</a>
                    <?php endif; ?>
                </div>

                <script>
                    function toggleDropdownMenu() {
                        document.getElementById("dropdownMenu").classList.toggle("show");
                    }

                    document.addEventListener("click", function (e) {
                        const trigger = document.querySelector(".dropdown-trigger");
                        const menu = document.getElementById("dropdownMenu");

                        if (trigger && menu && !trigger.contains(e.target) && !menu.contains(e.target)) {
                            menu.classList.remove("show");
                        }
                    });

                </script>

                <script>
                document.getElementById('logoutLink')?.addEventListener('click', function (e) {
                    e.preventDefault();

                    Swal.fire({
                        title: 'Bạn có chắc chắn muốn đăng xuất?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Đăng xuất',
                        cancelButtonText: 'Hủy',
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "/ShopAnVat/login/logout.php";
                        }
                    });
                });
                </script>

            </section>
        </div>
    </header>
</div>
