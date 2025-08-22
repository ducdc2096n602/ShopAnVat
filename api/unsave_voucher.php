<?php
require_once('../helpers/startSession.php');
startRoleSession('customer'); 

require_once('../database/config.php');
require_once('../database/dbhelper.php');

header('Content-Type: application/json');

$account_ID = $_SESSION['account_ID'] ?? null;
$voucher_ID = isset($_GET['voucher_ID']) ? intval($_GET['voucher_ID']) : 0;

if (!$account_ID || $voucher_ID <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu thông tin voucher hoặc chưa đăng nhập.']);
    exit;
}

$sql = "DELETE FROM SavedVoucher WHERE account_ID = $account_ID AND voucher_ID = $voucher_ID";
execute($sql);

echo json_encode(['status' => 'success', 'message' => 'Đã hủy lưu voucher.']);
