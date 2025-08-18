<?php
require_once('helpers/startSession.php');
startRoleSession('customer');
require_once('database/dbhelper.php');
require_once('utils/utility.php');
require_once('layout/header.php');

$account_ID = $_SESSION['account_ID'] ?? null;
$role = $_SESSION['role'] ?? null;
$customer_ID = null;

if ($account_ID && $role === 'customer') {
    $user = executeSingleResult("SELECT customer_ID FROM customer WHERE account_ID = ?", [$account_ID]);
    if ($user && isset($user['customer_ID'])) {
        $customer_ID = $user['customer_ID'];
    }
}

$notLoggedIn = !$customer_ID;

if ($notLoggedIn) {
    echo '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        Swal.fire({
            icon: "warning",
            title: "Bạn chưa đăng nhập",
            text: "Vui lòng đăng nhập để xem lịch sử đơn hàng.",
            confirmButtonText: "Đăng nhập ngay",
            confirmButtonColor: "#3085d6"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "login/login.php";
            } else {
                window.location.href = "index.php";
            }
        });
    </script>';
    exit();
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$start = ($page - 1) * $limit;
$selectedStatus = $_GET['status'] ?? '';
$statusFilter = $selectedStatus != '' ? addslashes($selectedStatus) : null;

// Đếm tổng đơn hàng
$countSql = "SELECT COUNT(*) AS total FROM Orders WHERE customer_ID = ?";
$params = [$customer_ID];
if ($statusFilter) {
    $countSql .= " AND status = ?";
    $params[] = $statusFilter;
}
$countResult = executeSingleResult($countSql, $params);
$totalOrders = $countResult['total'] ?? 0;
$totalPages = ceil($totalOrders / $limit);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch sử đơn hàng</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="plugin/fontawesome/css/all.css">
    <link rel="stylesheet" href="css/header.css">
</head>
<body>
<div style="max-width: 900px; margin: 40px auto; padding: 0 15px;">
    <h2 class="text-center mb-4">Lịch sử đơn hàng</h2>

    <form method="get" class="form-inline mb-4 justify-content-center">
        <label for="status" class="mr-2">Lọc theo trạng thái đơn hàng:</label>
        <select name="status" id="status" class="form-control mr-2" onchange="this.form.submit()">
            <option value="">Tất cả</option>
            <?php
            $statusList = [
                'Chờ xác nhận', 'Đã xác nhận', 'Đang chuẩn bị hàng', 'Đang giao hàng',
                'Đã giao hàng', 'Hoàn tất', 'Đã hủy', 'Trả hàng/Hoàn tiền', 'Thất bại'
            ];
            foreach ($statusList as $statusOption) {
                $selected = ($selectedStatus == $statusOption) ? 'selected' : '';
                echo "<option value=\"$statusOption\" $selected>$statusOption</option>";
            }
            ?>
        </select>
    </form>

    <?php
    // Truy vấn đơn hàng
    $ordersSql = "
        SELECT o.*, v.code AS voucher_code
        FROM Orders o
        LEFT JOIN VoucherUsage vu ON o.order_ID = vu.order_ID
        LEFT JOIN Voucher v ON vu.voucher_ID = v.voucher_ID
        WHERE o.customer_ID = ?";
    $params = [$customer_ID];
    if ($statusFilter) {
        $ordersSql .= " AND o.status = ?";
        $params[] = $statusFilter;
    }
    $ordersSql .= " ORDER BY o.order_date DESC LIMIT $start, $limit";
    $orders = executeResult($ordersSql, $params);

    if (empty($orders)) {
        echo '<p class="text-center">Không có đơn hàng nào.</p>';
    } else {
        echo '<div class="order-wrapper"><div class="order-list">';
        foreach ($orders as $order) {
            $orderID = $order['order_ID'];
            $status = $order['status'];
            $orderDate = date('d/m/Y H:i', strtotime($order['order_date']));
            $totalAmount = $order['total_amount'];
            $voucherCode = $order['voucher_code'] ?? null;
            $discount = $order['discount_amount'] ?? 0;
            $shippingFee = $order['shipping_fee'] ?? 0;
            $finalTotal = $totalAmount + $shippingFee - $discount;

            $colorClass = match ($status) {
                'Chờ xác nhận' => 'text-warning',
                'Đã xác nhận' => 'text-primary',
                'Đang chuẩn bị hàng' => 'text-info',
                'Đang giao hàng' => 'text-dark',
                'Đã giao hàng', 'Hoàn tất' => 'text-success',
                'Đã hủy', 'Trả hàng/Hoàn tiền', 'Thất bại' => 'text-danger',
                default => 'text-secondary',
            };

            echo '<div class="order-card">';
            echo "<div class='order-header'>Đơn hàng #$orderID | Ngày đặt: $orderDate | Trạng thái: <span class='$colorClass'><strong>" . htmlspecialchars($status) . "</strong></span></div>";

            echo "<table class='table table-bordered mt-2'>
                    <thead>
                        <tr>
                            <th>Ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th>Giá</th>
                            <th>Số lượng</th>
                            <th>Tổng cộng</th>
                        </tr>
                    </thead>
                    <tbody>";

            $itemSql = "SELECT p.product_name, oi.unitPrice, oi.quantity, pi.image_url
                        FROM OrderItem oi
                        JOIN Product p ON oi.product_ID = p.product_ID
                        LEFT JOIN ProductImage pi ON p.product_ID = pi.product_ID AND pi.is_primary = 1
                        WHERE oi.order_ID = ?";
            $items = executeResult($itemSql, [$orderID]);

            foreach ($items as $item) {
                $img = $item['image_url'];
                $imgPath = $img ? "images/uploads/product/" . $img : "images/no-image.jpg";
                $name = htmlspecialchars($item['product_name']);
                $price = number_format($item['unitPrice'], 0, ',', '.') . ' VNĐ';
                $quantity = $item['quantity'];
                $total = number_format($item['unitPrice'] * $quantity, 0, ',', '.') . ' VNĐ';

                echo "<tr>
                        <td><img src='$imgPath' class='product-img'></td>
                        <td>$name</td>
                        <td class='price'>$price</td>
                        <td>$quantity</td>
                        <td class='red'>$total</td>
                      </tr>";
            }

            echo "</tbody></table>";

            echo "<div class='order-footer'>";
            echo "<div>Tổng tiền sản phẩm: <strong>" . number_format($totalAmount, 0, ',', '.') . " VNĐ</strong></div>";
            echo "<div>Phí vận chuyển: <strong>" . number_format($shippingFee, 0, ',', '.') . " VNĐ</strong></div>";
            if ($voucherCode) {
                echo "<div>Mã giảm giá: <strong>$voucherCode</strong></div>";
            }
            echo "<div>Giảm giá: <strong>" . number_format($discount, 0, ',', '.') . " VNĐ</strong></div>";
            echo "<div>Tổng thanh toán: <strong class='total'>" . number_format($finalTotal, 0, ',', '.') . " VNĐ</strong></div>";
            echo "</div></div>";
        }
        echo '</div></div>';
    }
    ?>

    <!-- PHÂN TRANG -->
    <div class="mt-4">
        <ul class="pagination justify-content-center">
            <?php
            $queryString = $_GET;
            unset($queryString['page']);
            for ($i = 1; $i <= $totalPages; $i++) {
                $queryString['page'] = $i;
                $link = '?' . http_build_query($queryString);
                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                        <a class="page-link" href="' . $link . '">' . $i . '</a>
                      </li>';
            }
            ?>
        </ul>
    </div>
</div>
</body>

<style>
body {
    background-color: #f4f6f8;
}
.order-wrapper {
    display: flex;
    justify-content: center;
    margin-bottom: 40px;
}
.order-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
    width: 100%;
    max-width: 750px;
}
.order-card {
    background: linear-gradient(145deg, #ffffff, #f1f1f1);
    border: 1px solid #dcdcdc;
    border-radius: 12px;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
}
.order-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
}
.order-header {
    background-color: #f8f9fa;
    padding: 1rem;
    border-bottom: 1px solid #e1e4e8;
    font-size: 1rem;
    font-weight: 500;
}
.order-footer {
    background-color: #f1f3f5;
    padding: 1rem;
    font-weight: 600;
    font-size: 1rem;
    color: #c0392b;
    border-top: 1px solid #e0e0e0;
}
.table thead {
    background-color: #f0f3f5;
}
.table th, .table td {
    vertical-align: middle !important;
    text-align: center;
    padding: 0.75rem;
}
.product-img {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid #ccc;
}
.price {
    color: #d35400;
    font-weight: 500;
}
.total {
    color: #e74c3c;
    font-weight: 700;
}
.red {
    color: #c0392b;
}
</style>
</html>

<?php require_once('layout/footer.php'); ?>


<style>
main.container {
  background: #fff;
  border-radius: 10px;
  padding: 30px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}
.thumb-img {
  width: 70px;
  height: 70px;
  object-fit: cover;
  margin-bottom: 10px;
  border-radius: 6px;
  border: 2px solid transparent;
  transition: 0.2s;
  cursor: pointer;
}
.thumb-img:hover,
.thumb-img.active {
  border-color: #ff6f00;
  transform: scale(1.05);
}
.main-img {
  width: 100%;
  max-width: 400px;
  height: auto;
  aspect-ratio: 1 / 1;
  object-fit: cover;
  border-radius: 10px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}
.product-info h3 {
  font-size: 28px;
  font-weight: bold;
  margin-bottom: 10px;
}
.product-info p {
  font-size: 16px;
  margin: 5px 0;
}
.gia, #price {
  color: #d32f2f;
  font-weight: bold;
  font-size: 20px;
}
.btn-custom {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border: none;
  font-size: 15px;
  border-radius: 8px;
  transition: 0.3s;
  font-weight: 500;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.btn-cart {
  background-color: #4CAF50;
  color: white;
}
.btn-cart:hover {
  background-color: #45a049;
}
.btn-buy {
  background-color: #f44336;
  color: white;
}
.btn-buy:hover {
  background-color: #e53935;
}
.btn-cart i, .btn-buy i {
  font-size: 18px;
}
.quantity-control {
  display: flex;
  align-items: center;
  gap: 5px;
}
.quantity-control button {
  background-color: #28a745;
  color: white;
  border: none;
  padding: 6px 12px;
  font-size: 18px;
  border-radius: 6px;
  cursor: pointer;
  width: 40px;
  height: 40px;
  font-weight: bold;
  transition: 0.2s;
}
.quantity-control button:hover {
  background-color: #218838;
}
.quantity-control input {
  width: 50px;
  height: 40px;
  text-align: center;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-size: 16px;
  font-weight: bold;
}

/* Voice search mic animation */
button.listening i {
  color: red;
  animation: pulse 1s infinite;
}
@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.3); }
  100% { transform: scale(1); }
}
</style>