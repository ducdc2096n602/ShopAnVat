<?php
require_once('../../../helpers/startSession.php');
startRoleSession('admin');
require_once('../../../database/dbhelper.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['action'])) {
        $action = $_POST['action'];
        $category_ID = intval($_POST['category_ID'] ?? 0);

        if ($action == 'toggle' && $category_ID > 0) {
            $status = isset($_POST['status']) ? intval($_POST['status']) : 1; // 1: vô hiệu hóa, 0: kích hoạt lại
            $sql = "UPDATE Category SET is_deleted = $status WHERE category_ID = $category_ID";
            execute($sql);
            echo 'success';
            exit;
        }
    }
}
echo 'error';
