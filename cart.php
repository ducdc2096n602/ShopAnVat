<?php
require_once('helpers/startSession.php');
startRoleSession('customer');
require_once('database/dbhelper.php');
require_once('utils/utility.php');
require_once('layout/header.php');

$cart = [];
if (isset($_COOKIE['cart'])) {
    $json = $_COOKIE['cart'];
    $cart = json_decode($json, true);
}

$quantityMap = [];
foreach ($cart as $value) {
    if (isset($value['id']) && isset($value['num'])) {
        $quantityMap[$value['id']] = max(1, intval($value['num']));
    }
}

$idList = [];
foreach ($cart as $item) {
    $idList[] = $item['id'];
}

if (count($idList) > 0) {
    $idList = implode(',', $idList);
    $sql = "
        SELECT 
            p.product_ID,
            p.product_name,
            p.base_price,
            i.image_url
        FROM 
            Product p
        LEFT JOIN 
            ProductImage i ON p.product_ID = i.product_ID AND i.is_primary = 1
        WHERE 
            p.product_ID IN ($idList)
    ";
    $cartList = executeResult($sql);
} else {
    $cartList = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Giỏ hàng</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <link rel="stylesheet" href="css/header.css">
    <style>
        .b-500 { font-weight: 500; }
        .bold { font-weight: bold; }
        .red { color: rgba(207, 16, 16, 0.815); }

        .quantity-control {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .quantity-control button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 6px 10px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            height: 38px;
            width: 38px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .quantity-control input {
            width: 50px;
            height: 38px;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-weight: bold;
            font-size: 16px;
            padding: 0;
        }

        .cart-box {
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            margin-top: 2rem;
        }
        .btn-group .btn {
    border-radius: 8px;
    padding: 0.5rem 1.5rem;
    font-weight: 500;
}

.btn-group .btn.active {
    background-color: #28a745;
    color: white;
    border-color: #28a745;
}

td.b-500.red {
    white-space: nowrap;
    vertical-align: middle;
}

.table-bordered {
    border: 2px solid #000 !important;
}

.table-bordered th,
.table-bordered td {
    border: 2px solid #000 !important;
}

    </style>
</head>
<body>
    <div id="wrapper">
        <?php require_once('layout/header.php'); ?>

        <main style="background: #f5f5f5; padding: 2rem 0 4rem;">
    <div style="display: flex; justify-content: center;">
        <div class="cart-box" style="max-width: 960px; width: 100%; background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 0 20px rgba(0,0,0,0.05);">
           <div class="text-center mb-4">
    <div class="btn-group" role="group" aria-label="Tabs">
        <a href="cart.php" class="btn btn-outline-success <?= basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'active' : '' ?>">Giỏ hàng</a>
        <a href="history.php" class="btn btn-outline-success <?= basename($_SERVER['PHP_SELF']) == 'history.php' ? 'active' : '' ?>">Lịch sử mua hàng</a>
    </div>
</div>


            <h2 class="mb-4">Giỏ hàng</h2>

            <table class="table table-bordered table-hover text-center">
                <thead class="thead-light">
                    <tr>
                        <th width="50px">STT</th>
                        <th>Ảnh</th>
                        <th>Tên Sản Phẩm</th>
                        <th>Giá</th>
                        <th>Số lượng</th>
                        <th>Tổng tiền</th>
                        <th width="50px"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $count = 0;
                    $total = 0;
                    foreach ($cartList as $item) {
                        $num = $quantityMap[$item['product_ID']] ?? 1;
                        $total += $num * $item['base_price'];
                        echo '
                        <tr>
                            <td>' . (++$count) . '</td>
                            <td><img src="images/uploads/product/' . $item['image_url'] . '" style="width: 50px"></td>
                            <td>' . $item['product_name'] . '</td>
                            <td id="price_' . $item['product_ID'] . '" class="b-500 red">' . number_format($item['base_price'], 0, ',', '.') . ' VNĐ</td>
                            <td>
                                <div class="quantity-control">
                                    <button onclick="changeQuantity(' . $item['product_ID'] . ', -1)">-</button>
                                    <input type="text" id="qty_' . $item['product_ID'] . '" value="' . $num . '" readonly>
                                    <button onclick="changeQuantity(' . $item['product_ID'] . ', 1)">+</button>
                                </div>
                            </td>
                            <td id="total_' . $item['product_ID'] . '" class="b-500 red">' . number_format($num * $item['base_price'], 0, ',', '.') . ' VNĐ</td>
                            <td>
                                <button class="btn btn-danger btn-sm" onclick="deleteCart(' . $item['product_ID'] . ')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>';
                    }
                    ?>
                </tbody>
            </table>

            <div class="mt-3 text-right b-500">
                Tổng đơn hàng: <span class="red bold" id="total_order"><?= number_format($total, 0, ',', '.') ?> VNĐ</span>
            </div>

            <div class="text-right mt-4">
                <a href="checkout.php" class="btn btn-success btn-lg">Thanh toán</a>
            </div>
        </div>
    </div>
</main>


        <?php require_once('layout/footer.php'); ?>
    </div>

    <script>
        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        function deleteCart(id) {
            $.post('api/cookie.php', { action: 'delete', id: id }, function() {
                location.reload();
            });
        }

        function changeQuantity(id, change) {
            const input = document.getElementById('qty_' + id);
            let qty = parseInt(input.value);
            if (isNaN(qty)) qty = 1;
            qty += change;
            if (qty < 1) qty = 1;
            input.value = qty;

            $.post('api/cookie.php', {
                action: 'update',
                id: id,
                num: qty
            });

            const price = parseInt(document.getElementById('price_' + id).innerText.replace(/[^\d]/g, ''));
            const totalItem = price * qty;
            document.getElementById('total_' + id).innerText = numberWithCommas(totalItem) + ' VNĐ';

            let newTotal = 0;
            document.querySelectorAll('input[id^="qty_"]').forEach(function(el) {
                const pid = el.id.replace('qty_', '');
                const qty = parseInt(el.value);
                const price = parseInt(document.getElementById('price_' + pid).innerText.replace(/[^\d]/g, ''));
                newTotal += qty * price;
            });
            document.getElementById('total_order').innerText = numberWithCommas(newTotal) + ' VNĐ';
        }
    </script>
</body>
</html>
<?php include('chatbot.php'); ?>