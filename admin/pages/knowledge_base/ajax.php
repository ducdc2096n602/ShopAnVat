<?php
require_once('../../../helpers/startSession.php');
startRoleSession('admin');
require_once('../../../database/dbhelper.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $status = intval($_POST['status'] ?? 0);

        if ($id > 0) {
            $conn = getConnection();
            $sql = "UPDATE knowledgebase SET is_deleted = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ii', $status, $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                mysqli_close($conn);

                echo json_encode(['status' => 'success']);
                exit;
            }
        }
    }
}

echo json_encode(['status' => 'error']);
exit;
