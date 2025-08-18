<?php
function startRoleSession($role = 'auto') {
    // Nếu session đã được khởi tạo, thì không làm gì nữa
    if (session_status() === PHP_SESSION_ACTIVE) return;

    if ($role === 'auto') {
        $path = $_SERVER['PHP_SELF'];
        if (str_contains($path, '/admin/')) $role = 'admin';
        elseif (str_contains($path, '/staff/')) $role = 'staff';
        else $role = 'customer';
    }

    $name = match($role) {
        'admin' => 'admin_session',
        'staff' => 'staff_session',
        'customer' => 'customer_session',
        default => 'PHPSESSID'
    };

    if (!headers_sent()) {
        session_name($name);
        session_start();
    } else {
        // Ngăn trắng trang nếu headers đã gửi
        echo "<p style='color:red'> Lỗi: headers đã được gửi trước khi khởi tạo session ($name).</p>";
        exit();
    }
}
