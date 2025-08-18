<?php
require_once('../../../helpers/startSession.php');
startRoleSession('admin');
require_once('../../../database/dbhelper.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = isset($_POST['CategoryNews_ID']) ? intval($_POST['CategoryNews_ID']) : 0;
    $status = isset($_POST['status']) ? intval($_POST['status']) : 0;

    if ($action === 'toggle' && $id > 0) {
        $sql = "UPDATE CategoryNews SET is_deleted = $status WHERE CategoryNews_ID = $id";
        try {
            execute($sql);
            echo json_encode('success');
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}

// Nếu không đúng điều kiện
echo json_encode('error');
