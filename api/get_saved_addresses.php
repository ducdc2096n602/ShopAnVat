<?php
require_once('../database/dbhelper.php');

header('Content-Type: application/json');

if (!isset($_COOKIE['username'])) {
    echo json_encode([]);
    exit;
}

$username = addslashes($_COOKIE['username']); // simple escape nếu chưa có prepared
$sql = "SELECT account_ID FROM account WHERE username = '$username'";
$result = executeResult($sql);

if ($result && count($result) > 0) {
    $accountId = $result[0]['account_ID'];
    $sql = "SELECT address FROM SavedAddress WHERE account_ID = $accountId";
    $addresses = executeResult($sql);
    echo json_encode($addresses);
} else {
    echo json_encode([]);
}
?>
