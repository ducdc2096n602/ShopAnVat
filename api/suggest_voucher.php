<?php
require_once '../helpers/startSession.php';
startRoleSession('customer');
require_once '../database/dbhelper.php';
require_once '../utils/utility.php';


header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

$total = floatval($_POST['total'] ?? 0);
if ($total <= 0) {
    echo json_encode(['success' => false, 'message' => 'Tổng đơn hàng không hợp lệ']);
    exit;
}

if (!isset($_SESSION['customer'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập']);
    exit;
}

$account_ID = $_SESSION['account_ID'] ?? null;


if (!$account_ID) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản']);
    exit;
}

// Lấy danh sách voucher đã lưu
$sql = "
    SELECT v.*, sv.save_at
FROM SavedVoucher sv
JOIN Voucher v ON sv.voucher_ID = v.voucher_ID
WHERE sv.account_ID = $account_ID
    AND CURDATE() BETWEEN v.start_date AND v.end_date
    AND (v.usage_limit IS NULL OR v.usage_count < v.usage_limit)
    AND v.is_deleted = 0
";
$vouchers = executeResult($sql);

// Tìm voucher có giá trị giảm cao nhất
$bestVoucher = null;
$maxDiscount = 0;

foreach ($vouchers as $voucher) {
    if ($total < $voucher['min_order_amount']) {
        continue;
    }

    $discount = 0;
    if ($voucher['discount_type'] === 'percent') {
        $discount = $total * ($voucher['discount_value'] / 100);
        if ($voucher['max_discount']) {
            $discount = min($discount, $voucher['max_discount']);
        }
    } elseif ($voucher['discount_type'] === 'flat') {
        $discount = $voucher['discount_value'];
    }

    if ($discount > $maxDiscount) {
        $maxDiscount = $discount;
        $bestVoucher = $voucher;
    }
}

if ($bestVoucher) {
    echo json_encode([
        'success' => true,
        'voucher' => [
            'code' => $bestVoucher['code'],
            'description' => $bestVoucher['description'],
            'discount_type' => $bestVoucher['discount_type'],
            'discount_value' => $bestVoucher['discount_value'],
            'max_discount' => $bestVoucher['max_discount'],
            'min_order_amount' => $bestVoucher['min_order_amount']
        ],
        'expected_discount' => $maxDiscount
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy mã giảm giá phù hợp']);
}
