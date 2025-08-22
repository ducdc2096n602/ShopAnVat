<?php
require_once('../../../helpers/startSession.php');
startRoleSession('admin');
require_once('../../../database/dbhelper.php');

if (!empty($_POST['image_ID'])) {
    $image_ID = intval($_POST['image_ID']);

    // Xóa ảnh trong database không xóa file vật lý
    execute("DELETE FROM ProductImage WHERE image_ID = $image_ID");

    echo json_encode(['status' => 'success']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy ảnh']);
