<?php
require_once('../helpers/startSession.php');
startRoleSession();


// Xác định vai trò hiện tại từ cookie hoặc session
$role = $_SESSION['role_name'] ?? ($_COOKIE['role_ID'] ?? 'default');

// Chuyển role_ID về chữ nếu cần
$roleName = match((int)$role) {
    1 => 'admin',
    2 => 'staff',
    3 => 'customer',
    default => 'default'
};

// Khởi động lại đúng session theo tên
startRoleSession($roleName);

// Xoá session
session_unset();
session_destroy();

// Xóa cookies
setcookie("username", "", time() - 3600, "/");
setcookie("role_ID", "", time() - 3600, "/");

// Chuyển về trang chính
header("Location: ../index.php");
exit();
