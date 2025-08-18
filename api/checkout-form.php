<?php
require_once('./database/dbhelper.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy thông tin từ cookie giỏ hàng
    $cart = [];
    if (isset($_COOKIE['cart'])) {
        $cart = json_decode($_COOKIE['cart'], true);
    }

    if (!$cart || count($cart) === 0) {
        header('Location: /ShopAnVat/index.php');
        exit();
    }

    // Lấy thông tin người dùng từ cookie
    $customer_ID = null;
    if (isset($_COOKIE['username'])) {
        $username = $_COOKIE['username'];
        $user = executeResult("SELECT c.customer_ID FROM account a JOIN customer c ON c.account_ID = a.account_ID WHERE a.username = '$username'");
        if ($user && count($user) > 0) {
            $customer_ID = $user[0]['customer_ID'];
        }
    }

    if (!$customer_ID) {
        echo 'Không xác định được người dùng!';
        exit();
    }

    // Lấy dữ liệu từ form
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $note = $_POST['note'];
    $payment_method = $_POST['payment_method'];

    $status = ($payment_method === 'COD') ? 'Chờ xác nhận' : 'Chờ thanh toán';

    $original_total = isset($_POST['original_total']) ? (int)$_POST['original_total'] : 0;
    $shipping_fee = isset($_POST['shipping_fee']) ? (int)$_POST['shipping_fee'] : 0;
    $discount_amount = isset($_POST['discount_amount']) ? (int)$_POST['discount_amount'] : 0;
    $final_total = isset($_POST['final_total']) ? (int)$_POST['final_total'] : 0;
    $voucher_code = $_POST['applied_voucher_code'] ?? null;

    $province_ID = $_POST['province_ID'];
    $province_name = $_POST['province_name'];
    $district_ID = $_POST['district_ID'];
    $district_name = $_POST['district_name'];
    $ward_code = $_POST['ward_code'];
    $ward_name = $_POST['ward_name'];
    $detail_address = $_POST['detail_address'];
    $save_address = isset($_POST['save_address']) ? 1 : 0;

    $delivery_address = "$detail_address, $ward_name, $district_name, $province_name";

    // Lấy danh sách sản phẩm
    $idList = array_column($cart, 'id');
    $idListStr = implode(',', $idList);
    $products = executeResult("SELECT product_ID, base_price, weight FROM product WHERE product_ID IN ($idListStr)");

    $total_amount = 0;
    $total_weight = 0;
    foreach ($cart as $item) {
        foreach ($products as $p) {
            if ($item['id'] == $p['product_ID']) {
                $total_amount += $p['base_price'] * $item['num'];
                $total_weight += (isset($p['weight']) && is_numeric($p['weight'])) ? $p['weight'] * $item['num'] : 100 * $item['num'];
                break;
            }
        }
    }

    $con = mysqli_connect("localhost", "root", "", "shopanvat");

    // Tạo đơn hàng
    $sql = "INSERT INTO Orders (
            customer_ID, delivery_address, note, payment_method,
            total_amount, discount_amount, shipping_fee, final_total, status, completed_date
        ) VALUES (
            '$customer_ID', '$delivery_address', '$note', '$payment_method',
            '$total_amount', '$discount_amount', '$shipping_fee', '$final_total', '$status', NULL
        )";

    $result = mysqli_query($con, $sql);

    if ($result) {
        $order_ID = mysqli_insert_id($con);
    } else {
        echo "Không thể tạo đơn hàng, Vui lòng thử lại: " . mysqli_error($con);
        mysqli_close($con);
        exit();
    }

    // Lưu địa chỉ nếu có yêu cầu
    if ($save_address) {
        $sql = "INSERT INTO SaveAddress 
                (customer_ID, province_ID, province_name, district_ID, district_name, ward_code, ward_name, detail_address, is_default)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("isssssss", $customer_ID, $province_ID, $province_name, $district_ID, $district_name, $ward_code, $ward_name, $detail_address);
        $stmt->execute();
    }

    // Áp dụng voucher nếu có
    if (!empty($voucher_code)) {
        $voucherResult = mysqli_query($con, "SELECT voucher_ID, usage_count, usage_limit FROM Voucher WHERE code = '$voucher_code'");
        if ($voucherResult && mysqli_num_rows($voucherResult) > 0) {
            $voucherRow = mysqli_fetch_assoc($voucherResult);
            if ($voucherRow['usage_count'] < $voucherRow['usage_limit']) {
                $voucher_ID = $voucherRow['voucher_ID'];
                $accountResult = mysqli_query($con, "SELECT account_ID FROM Customer WHERE customer_ID = '$customer_ID'");
                if ($accountResult && mysqli_num_rows($accountResult) > 0) {
                    $account_ID = mysqli_fetch_assoc($accountResult)['account_ID'];
                    mysqli_query($con, "INSERT INTO VoucherUsage (account_ID, voucher_ID, order_ID) VALUES ('$account_ID', '$voucher_ID', '$order_ID')");
                    mysqli_query($con, "UPDATE Voucher SET usage_count = usage_count + 1 WHERE voucher_ID = '$voucher_ID'");
                }
            }
        }
    }

    // Lưu từng sản phẩm vào OrderItem
    foreach ($cart as $item) {
        $product_ID = $item['id'];
        $quantity = $item['num'];
        $unitPrice = 0;
        foreach ($products as $p) {
            if ($p['product_ID'] == $product_ID) {
                $unitPrice = $p['base_price'];
                break;
            }
        }
        $sql = "INSERT INTO OrderItem (order_ID, product_ID, quantity, unitPrice)
                VALUES ('$order_ID', '$product_ID', '$quantity', '$unitPrice')";
        mysqli_query($con, $sql);
    }

    mysqli_close($con);

    // Xóa cookie giỏ hàng
    setcookie('cart', '', time() - 3600, '/');

    // Điều hướng thanh toán
    if ($payment_method === 'momo') {
        header("Location: /ShopAnVat/api/momo/momo_payment.php?order_ID=$order_ID");
    } elseif ($payment_method === 'paypal') {
        header("Location: /ShopAnVat/api/paypal/paypal_payment.php?order_ID=$order_ID");
    } else {
        header("Location: /ShopAnVat/camon.php?order_ID=$order_ID");
    }

    exit();
}
?>
