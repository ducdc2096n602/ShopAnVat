<?php
require_once('../helpers/startSession.php');
startRoleSession('customer'); 

require_once('../database/config.php');
require_once('../database/dbhelper.php');

header('Content-Type: application/json');

if (!isset($_SESSION['account_ID']) || !is_numeric($_SESSION['account_ID'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để lưu voucher.']);
    exit;
}

$account_ID = intval($_SESSION['account_ID']);
$voucher_ID = isset($_POST['voucher_ID']) ? intval($_POST['voucher_ID']) : 0;

if ($voucher_ID <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID voucher không hợp lệ.']);
    exit;
}

// Kiểm tra đã lưu chưa
$sql = "SELECT 1 FROM SavedVoucher WHERE account_ID = $account_ID AND voucher_ID = $voucher_ID";
$check = executeResult($sql);

if (!empty($check)) {
    echo json_encode(['success' => false, 'message' => 'Bạn đã lưu voucher này rồi.']);
    exit;
}

// Thêm mới
$insert = "INSERT INTO SavedVoucher(account_ID, voucher_ID) VALUES($account_ID, $voucher_ID)";
execute($insert);

echo json_encode(['success' => true, 'message' => 'Đã lưu voucher thành công!']);
