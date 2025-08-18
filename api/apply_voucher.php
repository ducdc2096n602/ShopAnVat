<?php
require_once(__DIR__ . '/../database/config.php');
require_once(__DIR__ . '/../database/dbhelper.php');
$conn = getConnection();
session_start();


$response = ['success' => false, 'message' => ''];

if (!$conn) {
    $response['message'] = 'Kết nối CSDL thất bại';
    echo json_encode($response);
    exit;
}

if (!isset($_POST['code'])) {
    $response['message'] = 'Thiếu mã voucher';
    echo json_encode($response);
    exit;
}

$code = mysqli_real_escape_string($conn, $_POST['code']);

$sql = "SELECT * FROM Voucher 
        WHERE code = '$code' 
        AND start_date <= CURDATE() 
        AND end_date >= CURDATE()";
$result = mysqli_query($conn, $sql);
$voucher = mysqli_fetch_assoc($result);

if (!$voucher) {
    $response['message'] = 'Voucher không hợp lệ hoặc đã hết hạn';
    echo json_encode($response);
    exit;
}

// Lấy account_ID của người dùng từ cookie
$account_ID = null;
if (isset($_COOKIE['username'])) {
    $username = mysqli_real_escape_string($conn, $_COOKIE['username']);
    $account = executeSingleResult("SELECT a.account_ID FROM Account a WHERE a.username = '$username'");
    if ($account) {
        $account_ID = $account['account_ID'];
    }
}

// Kiểm tra nếu user đã dùng voucher này
if ($account_ID) {
    $voucher_ID = $voucher['voucher_ID'];
    $checkUsage = executeSingleResult("SELECT * FROM VoucherUsage WHERE account_ID = '$account_ID' AND voucher_ID = '$voucher_ID'");
    if ($checkUsage) {
        $response['message'] = 'Bạn đã sử dụng mã này rồi.';
        echo json_encode($response);
        exit;
    }
}

// Lấy giỏ hàng từ cookie
$cart = [];
if (isset($_COOKIE['cart'])) {
    $cart = json_decode($_COOKIE['cart'], true);
}

$total = 0;
if (isset($_POST['total'])) {
    $total = floatval($_POST['total']);
} else {
    foreach ($cart as $item) {
        $product_ID = $item['id'];
        $quantity = $item['num'];
        $sql = "SELECT base_price FROM Product WHERE product_ID = $product_ID";
        $product = executeSingleResult($sql);
        if ($product) {
            $total += $product['base_price'] * $quantity;
        }
    }
}

// Kiểm tra điều kiện đơn hàng tối thiểu
if ($total < $voucher['min_order_amount']) {
    $response['message'] = 'Đơn hàng chưa đủ điều kiện áp dụng voucher';
    echo json_encode($response);
    exit;
}

// Tính giảm giá
$discount = 0;
if ($voucher['discount_type'] == 'percent') {
    $discount = ($voucher['discount_value'] / 100.0) * $total;

    $max_discount = $voucher['max_discount'] ?? null;
    if ($max_discount !== null && $discount > $max_discount) {
        $discount = $max_discount;
    }
} else {
    $discount = $voucher['discount_value'] ?? 0;
}


$shipping_fee = 0;
if (isset($_POST['shipping_fee'])) {
    $shipping_fee = floatval($_POST['shipping_fee']);
}

$final_total = $total + $shipping_fee - $discount;

// Trả về dữ liệu
$response = [
    'success' => true,
    'new_total' => number_format((float)($total - $discount), 0, '.', ','),
    'discount' => number_format((float)$discount, 0, '.', ','),
    'shipping_fee' => number_format((float)$shipping_fee, 0, '.', ','),
    'final_total' => number_format((float)$final_total, 0, '.', ','),
    'message' => "Áp dụng mã giảm giá thành công. Giảm " . number_format((float)$discount, 0, ',', '.') . " VNĐ.",
    'discount_type' => $voucher['discount_type'],
    'discount_value' => $voucher['discount_value'],
    'max_discount' => $voucher['max_discount'],
    'min_order_amount' => $voucher['min_order_amount']
];

echo json_encode($response);
?>
