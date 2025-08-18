<?php
$count = 0;
if (isset($_COOKIE['cart'])) {
    $cart = json_decode($_COOKIE['cart'], true);
    foreach ($cart as $item) {
        $count += $item['num'];
    }
}
header('Content-Type: application/json');
echo json_encode(['count' => $count]);
