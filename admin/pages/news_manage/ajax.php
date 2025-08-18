<?php
require_once('../../../helpers/startSession.php');
startRoleSession('admin');
require_once('../../../database/dbhelper.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $news_ID = intval($_POST['news_ID'] ?? 0);
    $status = intval($_POST['status'] ?? 0);

    if ($action === 'toggle' && $news_ID > 0) {
        $sql = "UPDATE News SET is_deleted = $status WHERE news_ID = $news_ID";
        execute($sql);
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action === 'delete' && $news_ID > 0) {
        $sql = "UPDATE News SET is_deleted = 1 WHERE news_ID = $news_ID";
        execute($sql);
        echo json_encode(['status' => 'success']);
        exit;
    }
}

echo json_encode(['status' => 'error']);
