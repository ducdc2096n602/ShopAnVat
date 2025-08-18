<?php
require_once('../../../../database/dbhelper.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $account_ID = intval($_POST['account_ID'] ?? 0);

    if ($action === 'toggle_status' && $account_ID > 0) {
        $newStatus = ($_POST['status'] == 2) ? 2 : 1; // chỉ cho phép 1 (hoạt động) hoặc 2 (vô hiệu hóa)
        $sql = "UPDATE Account SET status = ? WHERE account_ID = ?";
        $conn = mysqli_connect(HOST, USERNAME, PASSWORD, DATABASE);
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $newStatus, $account_ID);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            echo json_encode(['status' => 'success']);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Không thể chuẩn bị truy vấn']);
            exit;
        }
    }

    if ($action === 'delete' && $account_ID > 0) {
        // Xoá nhân viên khỏi bảng Staff
        $sql1 = "DELETE FROM Staff WHERE account_ID = $account_ID";
        execute($sql1);

        // Sau đó xoá tài khoản khỏi bảng Account (nếu không còn ràng buộc)
        $sql2 = "DELETE FROM Account WHERE account_ID = $account_ID";
        execute($sql2);

        echo json_encode(['status' => 'success']);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ']);
