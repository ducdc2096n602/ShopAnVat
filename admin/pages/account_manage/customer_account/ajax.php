<?php
require_once('../../../../database/dbhelper.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $account_ID = intval($_POST['account_ID'] ?? 0);

  if ($action === 'toggle_status' && $account_ID > 0) {
    $newStatus = ($_POST['status'] == 2) ? 2 : 1; // 1: hoạt động, 2: vô hiệu hóa

    // Bảo vệ tránh SQL injection bằng cách ép kiểu và dùng execute
    $sql = "UPDATE Account SET status = $newStatus WHERE account_ID = $account_ID";
    execute($sql);

    echo json_encode(['status' => 'success']);
    exit;
  }
}

echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ']);
