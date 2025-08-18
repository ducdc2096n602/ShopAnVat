<?php
require_once('../../../helpers/startSession.php');
startRoleSession('admin');
require_once('../../../database/dbhelper.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Xoá mềm sản phẩm
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $images = executeResult("SELECT image_url FROM ProductImage WHERE product_ID = $id");
            foreach ($images as $img) {
                $filePath = '../../../images/uploads/product/' . $img['image_url'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            execute("UPDATE Product SET is_deleted = 1 WHERE product_ID = $id");
            echo json_encode(['status' => 'success']);
            exit;
        }
    }

    // Toggle trạng thái hoạt động / vô hiệu hóa
    if ($action === 'toggle') {
        $product_ID = intval($_POST['product_ID'] ?? 0);
        $status = intval($_POST['status'] ?? 0);

        if ($product_ID > 0) {
            execute("UPDATE Product SET is_deleted = $status WHERE product_ID = $product_ID");
            echo json_encode(['status' => 'success']);
            exit;
        }
    }
}

echo json_encode(['status' => 'error']);
exit;
