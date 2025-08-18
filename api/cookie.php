<?php
require_once('../utils/utility.php');
session_start();
header('Content-Type: application/json'); // Luôn trả về JSON

$action = $_POST['action'] ?? '';
$id = intval($_POST['id'] ?? 0);
$num = intval($_POST['num'] ?? 1);

if (!empty($_POST)) {
    $cart = [];
    if(isset($_COOKIE['cart'])) {
        $json = $_COOKIE['cart'];
        $cart = json_decode($json, true);
    }

    switch ($action) {
        case 'add':
            $isFind = false;
            for ($i=0; $i < count($cart); $i++) { 
                if($cart[$i]['id'] == $id) {
                    $cart[$i]['num'] += $num;
                    $isFind = true;
                    break;
                }
            }
            if(!$isFind) {
                $cart[] = ['id'=>$id, 'num'=>$num];
            }
            setcookie('cart', json_encode($cart), time() + 30*24*60*60, '/');

            // Session
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id] += $num;
            } else {
                $_SESSION['cart'][$id] = $num;
            }

            echo json_encode(['status'=>'success', 'message'=>'Đã thêm sản phẩm vào giỏ hàng']);
            exit;

        case 'delete':
            for ($i=0; $i < count($cart); $i++) { 
                if($cart[$i]['id'] == $id) {
                    array_splice($cart, $i, 1);
                    break;
                }
            }
            setcookie('cart', json_encode($cart), time() + 30*24*60*60, '/');
            echo json_encode(['status'=>'success', 'message'=>'Đã xóa sản phẩm']);
            exit;

        case 'update':
            foreach ($cart as &$item) {
                if ($item['id'] == $id) {
                    $item['num'] = $num;
                    break;
                }
            }
            setcookie('cart', json_encode($cart), time() + 7*24*60*60, '/');
            echo json_encode(['status'=>'success', 'message'=>'Cập nhật số lượng thành công']);
            exit;

        default:
            echo json_encode(['status'=>'error', 'message'=>'Hành động không hợp lệ']);
            exit;
    }
}

echo json_encode(['status'=>'error', 'message'=>'Không có dữ liệu POST']);
