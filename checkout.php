<?php
require_once('database/dbhelper.php');
require_once('utils/utility.php');
require_once('api/checkout-form.php');
require_once('layout/header.php');

$orderSuccess = false;
$order_ID = '';
$total_weight = 0;
$userInfo = [];

if (isset($_COOKIE['username'])) {
    $username = $_COOKIE['username'];
   $sql = "SELECT fullname, email, phone_number, address 
        FROM account 
        WHERE username = '$username'";

    $result = executeResult($sql);

    if ($result && count($result) > 0) {
        $userInfo = $result[0];
    }
}

$cart = [];
if (isset($_COOKIE['cart'])) {
    $json = $_COOKIE['cart'];
    $cart = json_decode($json, true);
}
$idList = [];
foreach ($cart as $item) {
    $idList[] = $item['id'];
}
if (count($idList) > 0) {
    $idList = implode(',', $idList);
    $sql = "SELECT p.product_ID, p.product_name,p.weight, p.base_price, pi.image_url
        FROM product p
        LEFT JOIN productimage pi ON p.product_ID = pi.product_ID AND pi.is_primary = 1
        WHERE p.product_ID IN ($idList)";

    $cartList = executeResult($sql);
} else {
    $cartList = [];
}


$savedAddresses = [];
if (isset($_COOKIE['username'])) {
    $username = $_COOKIE['username'];
    $account = executeResult("SELECT account_ID FROM account WHERE username = '$username'");
    if ($account && count($account) > 0) {
        $account_ID = $account[0]['account_ID'];
        $customer = executeResult("SELECT customer_ID FROM customer WHERE account_ID = $account_ID");
        if ($customer && count($customer) > 0) {
            $customer_ID = $customer[0]['customer_ID'];
            $sql = "SELECT detail_address, ward_code, ward_name, district_ID, district_name, province_ID, province_name
                    FROM saveaddress
                    WHERE customer_ID = $customer_ID";
            $savedAddresses = executeResult($sql);

            // Lọc địa chỉ trùng lặp
            $uniqueAddresses = [];
            $seen = [];

            foreach ($savedAddresses as $address) {
                $key = strtolower(trim($address['detail_address'] . '|' . $address['ward_name'] . '|' . $address['district_name'] . '|' . $address['province_name']));
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $uniqueAddresses[] = $address;
                }
            }

            // Gán lại
            $savedAddresses = $uniqueAddresses;
        }
    }
}



$savedVouchers = [];
if (isset($_COOKIE['username'])) {
    $username = $_COOKIE['username'];
    $account = executeResult("SELECT account_ID FROM account WHERE username = '$username'");
    if ($account && count($account) > 0) {
        $account_ID = $account[0]['account_ID'];
        $sql = "SELECT v.voucher_ID, v.code, v.description, v.end_date
        FROM SavedVoucher sv
        JOIN Voucher v ON sv.voucher_ID = v.voucher_ID
        WHERE sv.account_ID = $account_ID";
        $savedVouchers = executeResult($sql);
        
    }
}

$orderSuccess = false;
$order_ID = '';
$total_weight = 0;
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Giỏ hàng</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<?php
if (!isset($_COOKIE['username'])) {
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: "warning",
                title: "Bạn chưa đăng nhập",
                text: "Vui lòng đăng nhập để tiếp tục mua hàng.",
                confirmButtonText: "Đăng nhập ngay"
            }).then(() => {
                window.location.href = "login/login.php";
            });
        });
    </script>';
    exit(); // Dừng xử lý tiếp nếu chưa đăng nhập
}
?>

<body>
<div id="wrapper">
    <?php
    $count = 0;
    foreach ($cart as $item) {
        $count += $item['num'];
    }
    ?>
</div>
<?php require_once('layout/header.php'); ?>
<main>
    <section class="cart">
        <form action="" method="POST">
            <div class="container mt-5">
                <?php if (empty($cartList)) : ?>
        <div class="alert alert-warning text-center">
            Giỏ hàng của bạn đang trống. Vui lòng thêm sản phẩm để tiếp tục thanh toán.
        </div>
    <?php else: ?>
                <div class="row">
                    <div class="panel panel-primary col-md-6">
                        <h4 style="padding: 2rem 0; border-bottom:1px solid black;">Nhập thông tin mua hàng </h4>
                        <div class="form-group">
                            <label for="usr">Họ và tên:</label>
                            <input required="true" type="text" class="form-control" id="usr" name="fullname" 
                                value="<?php echo $userInfo['fullname'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input required="true" type="email" class="form-control" id="email" name="email" 
                                value="<?php echo $userInfo['email'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone_number">Số điện thoại:</label>
                            <input required="true" type="text" class="form-control" id="phone_number" name="phone_number" 
                                value="<?php echo $userInfo['phone_number'] ?? ''; ?>">
                        </div>
 <select id="saved_address_select" class="form-control">
    <option value="">-- Chọn địa chỉ đã lưu --</option>
    <?php foreach ($savedAddresses as $address): ?>
        <option 
            value="<?php echo htmlspecialchars($address['detail_address']); ?>" 
            data-json='<?php echo htmlspecialchars(json_encode([
                'province_ID' => $address['province_ID'],
                'province_name' => $address['province_name'],
                'district_ID' => $address['district_ID'],
                'district_name' => $address['district_name'],
                'ward_code' => $address['ward_code'],
                'ward_name' => $address['ward_name'],
                'detail_address' => $address['detail_address']
            ]), ENT_QUOTES, 'UTF-8'); ?>'>
            <?php echo htmlspecialchars($address['detail_address'] . ', ' . $address['ward_name'] . ', ' . $address['district_name'] . ', ' . $address['province_name']); ?>
        </option>
    <?php endforeach; ?>
</select>






<p>Chọn địa chỉ mới:</p>
<div class="form-row">
  <div class="form-group col-md-4">
    <label for="province">Tỉnh/Thành phố</label>
    <select id="province" class="form-control">
      <option value="">Chọn tỉnh</option>
    </select>
    <input type="hidden" name="province_ID" id="province_ID">
    <input type="hidden" name="province_name" id="province_name">
  </div>

  <div class="form-group col-md-4">
    <label for="district">Quận/Huyện</label>
    <select id="district" class="form-control">
      <option value="">Chọn quận/huyện</option>
    </select>
    <input type="hidden" name="district_ID" id="district_ID">
    <input type="hidden" name="district_name" id="district_name">
  </div>

  <div class="form-group col-md-4">
    <label for="ward">Phường/Xã</label>
    <select id="ward" class="form-control">
      <option value="">Chọn phường/xã</option>
    </select>
    <input type="hidden" name="ward_code" id="ward_code">
    <input type="hidden" name="ward_name" id="ward_name">
  </div>
</div>

<div class="form-row align-items-end">
  <div class="form-group col-md-8">
    <label for="detail_address">Số nhà, tên đường</label>
    <input type="text" name="detail_address" id="detail_address" class="form-control" required>

  </div>
  <div class="form-group col-md-4">
    <div class="form-check mt-4">
      <input class="form-check-input" type="checkbox" name="save_address" id="save_address">
      <label class="form-check-label" for="save_address">Lưu địa chỉ</label>
    </div>
  </div>
</div>



                        <div class="form-group">
                            <label for="note">Ghi chú:</label>
                            <textarea class="form-control" rows="3" name="note" id="note"></textarea>
                        </div>
                    </div>
                    <div class="panel panel-primary col-md-6">
                        <h4 style="padding: 2rem 0; border-bottom:1px solid black;">Đơn hàng</h4>
                        <table class="table table-bordered table-hover none">
                            <thead>
                                <tr style="font-weight: 500;text-align: center;">
                                    <td width="50px">STT</td>
                                    <td>Tên Sản Phẩm</td>
                                    <td>Khối lượng</td>
                                    <td>Số lượng</td>
                                    <td>Tổng tiền(VNĐ)</td>
                                    <td></td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $weight=0;
                                $count = 0;
                                $total = 0;
                                foreach ($cartList as $item) {
                                    $num = 0;
                                    foreach ($cart as $value) {
                                        if ($value['id'] == $item['product_ID']) {
                                            $num = $value['num'];
                                            break;
                                        }
                                    }
                                    $total += $num * $item['base_price'];
                                ?>


                                <tr style="text-align: center;">
                                    <td><?php echo ++$count; ?></td>
                                   <td style="text-align: left;"><?php echo $item['product_name']; ?></td>
                                   <td id="weight_<?php echo $item['product_ID']; ?>">
                                    
                <?php
                $itemWeight = $item['weight'];
                $totalWeight = $itemWeight * $num;
                echo $totalWeight . 'g';
                ?>


</td>


                                    <td width="130px">
                                        <div class="quantity-control">
                                            <button type="button" onclick="changeQuantity(<?php echo $item['product_ID']; ?>, -1)">-</button>                      
                                            <input type="text" id="qty_<?php echo $item['product_ID']; ?>" value="<?php echo $num; ?>" readonly
                                             data-weight="<?php echo $item['weight']; ?>" 
       data-weight-id="weight_<?php echo $item['product_ID']; ?>">

                                            
                                            <button type="button" onclick="changeQuantity(<?php echo $item['product_ID']; ?>, 1)">+</button>
                                        </div>
                                    </td>
                                    <td class="b-500 red item-total" id="price_<?php echo $item['product_ID']; ?>"
                                        data-price="<?php echo $item['base_price']; ?>"
                                        data-qty-id="qty_<?php echo $item['product_ID']; ?>">
                                        <?php echo number_format($num * $item['base_price'], 0, ',', '.'); ?> VNĐ
                                    </td>
                                    <td style="text-align: center;">
    <button type="button" class="btn btn-danger btn-sm" onclick="removeFromCart(<?php echo $item['product_ID']; ?>)">
        <i class="fas fa-trash-alt"></i>
    </button>
</td>

                                </tr>
                                
                                <?php } ?>
                            </tbody>
                        </table>
                       
                        
                      <div class="form-group">
 <!-- Dropdown chọn voucher đã lưu -->
<div class="form-group">
    <label for="saved_voucher_dropdown"><strong>Chọn mã giảm giá đã lưu:</strong></label>
    <select id="saved_voucher_dropdown" class="form-control mb-2" onchange="selectSavedVoucher(this.value)">
        <option value="">-- Chọn mã giảm giá --</option>
        <?php foreach ($savedVouchers as $voucher): ?>
            <?php
                $isExpired = strtotime($voucher['end_date']) < time();
                $label = $voucher['code'] . ' - ' . $voucher['description'];
                if ($isExpired) {
                    $label = '[HẾT HẠN] ' . $label;
                }
            ?>
            <option value="<?= $voucher['code'] ?>" <?= $isExpired ? 'style="color:red;" disabled' : '' ?>>
                <?= $label ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>



<!-- Nhập tay mã giảm giá -->
<div class="form-group">
    <label for="voucher_code"><strong>Nhập mã giảm giá (nếu có):</strong></label>
    <div class="input-group">
        <input type="text" class="form-control" id="voucher_code" name="code" placeholder="Nhập mã giảm giá">
        <div class="input-group-append">
            <button type="button" class="btn btn-primary" onclick="applyVoucher()">Áp dụng</button>
        </div>
    </div>
    <small id="voucher_message" class="form-text text-muted"></small>
</div>

<!-- Tự động gợi ý mã tốt nhất -->
<div id="best_voucher_suggestion" class="alert alert-success mt-2" style="display: none;">
    🔍 Mã tốt nhất gợi ý: <strong id="best_voucher_code"></strong> - Giảm <span id="best_voucher_discount"></span>
</div>

 <p>Tổng đơn hàng: <span class="bold red" id="total-amount"><?php echo number_format($total, 0, ',', '.'); ?> VNĐ</span></p>
                        <p>Tổng khối lượng: <span class="bold" id="total-weight-display">0g</span></p>
                        <div>
                        <div>
                            Phí vận chuyển: <span class="bold" id="shipping_fee">0 VNĐ</span>
                            <input type="hidden" id="shipping_fee_input" name="shipping_fee" value="0">
                        </div>



                        <p id="discount-info" style="font-weight: bold; color: green;"></p>
                        <p>Giảm giá: <span class="bold" id="discount-amount">0</span> VNĐ</p>
                        <p>Tổng phải thanh toán: <span class="bold red" id="final-amount"><?php echo number_format($total, 0, ',', '.'); ?> VNĐ</span></p>

<h4>Phương thức thanh toán</h4>
<form id="payment-form" method="GET">
  <input type="hidden" name="order_ID" value="<?= $order_ID ?>">

  <div id="payment-methods">

    <label class="payment-card selected">
      <input type="radio" name="payment_method" value="COD" checked>
      <img src="https://cdn-icons-png.freepik.com/512/9198/9198191.png" alt="COD" >
      <span>Thanh toán khi nhận hàng (COD)</span>
    </label>

    <label class="payment-card">
      <input type="radio" name="payment_method" value="bank_transfer">
      <img src="https://cdn-icons-png.flaticon.com/512/204/204189.png" alt="Bank">
      <span>Chuyển khoản qua ngân hàng</span>
    </label>

    <label class="payment-card">
      <input type="radio" name="payment_method" value="momo">
      <img src="https://upload.wikimedia.org/wikipedia/vi/f/fe/MoMo_Logo.png" width="50" alt="MoMo">
      <span>Thanh toán bằng ví MoMo</span>
    </label>
  </div>

    <label class="payment-card">
    <input type="radio" name="payment_method" value="paypal">
    <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" width="50" alt="PayPal">
    <span>Thanh toán qua PayPal</span>
    </label>
<!-- Gợi ý giá khi chọn PayPal -->
<div id="paypal-note" style="display: none; margin-top: 10px; color: #0070ba;"></div>


  <button type="submit" class="btn btn-success mt-3">Đặt hàng</button>
</form>


<?php endif; ?>
                    </div>
                </div>
            </div>
            <input type="hidden" id="total_weight" value="<?= $total_weight ?>">
            <input type="hidden" name="voucher_ID" id="voucher_ID" value="">
            <input type="hidden" name="original_total" id="original_total" value="<?php echo $total; ?>">
            <input type="hidden" name="discount_amount" id="discount_amount" value="0">
            <input type="hidden" name="final_total" id="final_total" value="<?php echo $total; ?>">
            <input type="hidden" name="applied_voucher_code" id="applied_voucher_code" value="">
           
        </form>
    </section>
</main>
        <?php require_once('layout/footer.php'); ?>
    </div>



    
    <script type="text/javascript">

        let voucherApplied = false;
        function deleteCart(id) {
            $.post('api/cookie.php', {
                'action': 'delete',
                'id': id
            }, function(data) {
                location.reload()
            })
        }


function applyVoucher() {
    const code = document.getElementById('voucher_code').value.trim();
    const totalStr = document.getElementById('total-amount').innerText.replace(/\D/g, '');
    const total = parseFloat(totalStr);

    if (code === '') return;

    const shippingFeeStr = document.getElementById('shipping_fee_input').value || '0';
    const shippingFee = parseFloat(shippingFeeStr);

    $.ajax({
        url: 'api/apply_voucher.php',
        type: 'POST',
        data: {
            code: code,
            total: total,
            shipping_fee: shippingFee
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                const discount = parseFloat(response.discount.replace(/,/g, ''));
                const shipping = parseFloat(response.shipping_fee.replace(/,/g, '')) || 0;
                const newTotal = parseFloat(response.final_total.replace(/,/g, ''));

                // Cập nhật input hidden
                document.getElementById('voucher_ID').value = response.voucher_ID ?? '';
                document.getElementById('discount_amount').value = discount;
                document.getElementById('shipping_fee_input').value = shipping;
                document.getElementById('applied_voucher_code').value = code;

                // Cập nhật hiển thị
                document.getElementById('discount-info').innerText = response.message;
                document.getElementById('discount-amount').innerText = discount.toLocaleString('vi-VN');
                document.getElementById('shipping_fee').innerText = shipping.toLocaleString('vi-VN') + ' VNĐ';

                // Gọi lại tính tổng sau khi cập nhật đủ input
                recalculateFinalTotal();
            } else {
                // Reset nếu lỗi và hiện cảnh báo
                document.getElementById('discount-info').innerText = response.message;
                document.getElementById('discount-amount').innerText = '0';
                document.getElementById('applied_voucher_code').value = '';
                document.getElementById('voucher_ID').value = '';
                document.getElementById('discount_amount').value = 0;

                recalculateFinalTotal();

                Swal.fire({
                    icon: 'warning',
                    title: 'Không áp dụng được voucher',
                    text: response.message
                });
            }
        },
        error: function () {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi hệ thống',
                text: 'Đã xảy ra lỗi khi áp dụng voucher. Vui lòng thử lại sau.'
            });
        }
    });
}





function changeQuantity(productId, delta) {
    const qtyInput = document.getElementById('qty_' + productId);
    let currentQty = parseInt(qtyInput.value);

    if (isNaN(currentQty)) currentQty = 1;
    let newQty = currentQty + delta;
    if (newQty < 1) newQty = 1;

    qtyInput.value = newQty;
updateTotalAmount(); // cập nhật lại original_total
recalculateFinalTotal(); // cập nhật final
    // Gửi cập nhật lên server
    $.post('api/cookie.php', {
        action: 'update',
        id: productId,
        num: newQty
    }, function(response) {
        const priceElement = document.getElementById('price_' + productId);
        const basePrice = parseFloat(priceElement.getAttribute('data-price'));
        const itemTotal = basePrice * newQty;

        priceElement.innerText = itemTotal.toLocaleString('vi-VN') + ' VNĐ';

        updateTotalAmount();

        // Tự động áp lại voucher nếu có
        const voucherCode = document.getElementById('applied_voucher_code').value.trim();
        if (voucherCode !== '') {
            applyVoucher();
        }
    });

    // Cập nhật khối lượng
    const weight = parseInt(qtyInput.getAttribute('data-weight'));
    const weightCellId = qtyInput.getAttribute('data-weight-id');
    const weightCell = document.getElementById(weightCellId);
    weightCell.innerText = `${weight * newQty}g`;

    updateTotalWeight();

    // Nếu đã chọn đầy đủ địa chỉ, tính lại phí ship
const wardCode = document.getElementById('ward_code').value;
if (wardCode) {
    document.getElementById('ward').dispatchEvent(new Event('change'));
}

}



function updateTotalAmount() {
    let total = 0;
    const itemTotals = document.querySelectorAll('.item-total');

    itemTotals.forEach(function(el) {
        const price = parseFloat(el.getAttribute('data-price'));
        const qtyId = el.getAttribute('data-qty-id');
        const qty = parseInt(document.getElementById(qtyId).value);
        total += price * qty;
    });

    // Cập nhật lại các giá trị hiển thị
    document.getElementById('total-amount').innerText = total.toLocaleString('vi-VN') + ' VNĐ';
    //document.getElementById('final-amount').innerText = total.toLocaleString('vi-VN') + ' VNĐ';

    // Cập nhật các input hidden
    document.getElementById('original_total').value = total;
   // document.getElementById('final_total').value = total;
   recalculateFinalTotal();
   suggestBestVoucher(total);
}


// Load danh sách tỉnh thành
async function loadProvinces() {
    const res = await fetch('http://localhost:8080/SHOPANVAT/api/ghn/provinces.php');
    const provinces = (await res.json()).data;
    const select = document.getElementById('province');

    select.innerHTML = ''; // Xóa tỉnh cũ nếu có

    provinces.forEach(p => {
        const opt = new Option(p.ProvinceName, p.ProvinceID);
        select.add(opt);
    });
}

// Khi chọn tỉnh, load quận, lưu tên + id
document.getElementById('province').addEventListener('change', async e => {
    const selectedOption = e.target.options[e.target.selectedIndex];
    const provinceId = selectedOption.value;
    const provinceName = selectedOption.text;

    // Gán vào hidden
    document.getElementById('province_ID').value = provinceId;
    document.getElementById('province_name').value = provinceName;

    // Load quận/huyện
    const res = await fetch(`http://localhost:8080/SHOPANVAT/api/ghn/districts.php?province_id=${provinceId}`);
    const districts = await res.json();
    const districtSelect = document.getElementById('district');
    districtSelect.innerHTML = ''; // Clear danh sách cũ

    districts.forEach(d => {
        const opt = new Option(d.DistrictName, d.DistrictID);
        districtSelect.add(opt);
    });

    // Xóa ward nếu có
    document.getElementById('ward').innerHTML = '';
});

// Khi chọn quận, load phường, lưu tên + id
document.getElementById('district').addEventListener('change', async e => {
    const selectedOption = e.target.options[e.target.selectedIndex];
    const districtId = selectedOption.value;
    const districtName = selectedOption.text;

    // Gán vào hidden
    document.getElementById('district_ID').value = districtId;
    document.getElementById('district_name').value = districtName;

    // Load phường/xã
    const res = await fetch(`http://localhost:8080/SHOPANVAT/api/ghn/wards.php?district_id=${districtId}`);
    const wards = (await res.json()).data;
    const wardSelect = document.getElementById('ward');
    if (!wardSelect) return; // tránh lỗi khi element chưa render
    wardSelect.innerHTML = '';

    wards.forEach(w => {
        const opt = new Option(w.WardName, w.WardCode);
        wardSelect.add(opt);
    });
});

// Khi chọn phường → lưu tên + code, tính phí
document.getElementById('ward').addEventListener('change', async e => {
    const selectedOption = e.target.options[e.target.selectedIndex];
    const wardCode = selectedOption.value;
    const wardName = selectedOption.text;
    const districtId = document.getElementById('district_ID').value;

    // Gán vào hidden
    document.getElementById('ward_code').value = wardCode;
    document.getElementById('ward_name').value = wardName;

    const weight = parseInt(document.getElementById('total_weight').value);

    // Gọi API tính phí
    const res = await fetch('http://localhost:8080/SHOPANVAT/api/ghn/calculate_fee.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            district_id: districtId,
            ward_code: wardCode,
                       weight: weight // Cân nặng mặc định là 1000g, bạn có thể tính tổng từ giỏ hàng nếu muốn
        })
    });

   const text = await res.text();
console.log("Phản hồi từ calculate_fee.php:", text); // Xem trong console

let data;
try {
    data = JSON.parse(text);
} catch (e) {
    console.error("Lỗi parse JSON:", e);
    return;
}
// Kiểm tra dữ liệu hợp lệ
if (!data || data.error) {
    console.error("Lỗi từ API GHN:", data.response || data.error);
    return;
}

const fee = parseInt(data.fee ?? data.data?.total ?? 0); // fallback an toàn


    // Hiển thị phí vận chuyển
    document.getElementById('shipping_fee').innerText = fee.toLocaleString('vi-VN') + ' VNĐ';

    // Gán vào input ẩn
    document.getElementById('shipping_fee_input').value = fee;
recalculateFinalTotal();
    // Tính lại tổng tiền
    const originalTotal = parseFloat(document.getElementById('original_total').value);
    const discount = parseFloat(document.getElementById('discount_amount').value);
    const finalTotal = originalTotal + fee - discount;

    document.getElementById('final-amount').innerText = finalTotal.toLocaleString('vi-VN') + ' VNĐ';
    document.getElementById('final_total').value = finalTotal;
});
const totalWeight = parseFloat(document.getElementById('total_weight').value) || 0;


// Khởi động
loadProvinces();



function updateTotalWeight() {
    let totalWeight = 0;
    const qtyInputs = document.querySelectorAll('[id^="qty_"]');

    qtyInputs.forEach(function(input) {
        const qty = parseInt(input.value);
        const weight = parseInt(input.getAttribute('data-weight'));
        totalWeight += qty * weight;
    });

    document.getElementById('total-weight-display').innerText = totalWeight + 'g';
    document.getElementById('total_weight').value = totalWeight; // cập nhật input hidden
}
document.addEventListener('DOMContentLoaded', function () {
    updateTotalWeight();
});


//function updateFinalTotal(shippingFee) {
   // const totalPrice = parseInt(document.getElementById('total_price_hidden').value);
    //const discount = parseInt(document.getElementById('discount_amount').value) || 0;
    //const finalTotal = totalPrice + shippingFee - discount;

    //document.getElementById('final_total_display').innerText = finalTotal.toLocaleString() + 'đ';
    //document.getElementById('final_total_input').value = finalTotal;
//}
document.getElementById('saved_address_select').addEventListener('change', async function () {
    const value = this.value;
    if (!value || value.trim() === '') return;

    let address = {};
    try {
        address = JSON.parse(this.options[this.selectedIndex].getAttribute('data-json'));
    } catch (e) {
        console.error("Không thể parse data-json:", e);
        return;
    }

    // Gán input ẩn
    document.getElementById('province_ID').value = address.province_ID || '';
    document.getElementById('district_ID').value = address.district_ID || '';
    document.getElementById('ward_code').value = address.ward_code || '';
    document.getElementById('ward_name').value = address.ward_name || '';
    document.getElementById('district_name').value = address.district_name || '';
    document.getElementById('province_name').value = address.province_name || '';
    document.querySelector('input[name="detail_address"]').value = address.detail_address || '';

    // Cập nhật dropdown
    await loadProvinces();
    const provinceSelect = document.getElementById('province');
    provinceSelect.value = address.province_ID;

    // Gọi load quận
    const districtRes = await fetch(`http://localhost:8080/SHOPANVAT/api/ghn/districts.php?province_id=${address.province_ID}`);
    const districts = await districtRes.json();
    const districtSelect = document.getElementById('district');
    districtSelect.innerHTML = '';
    districts.forEach(d => {
        const opt = new Option(d.DistrictName, d.DistrictID);
        districtSelect.add(opt);
    });
    districtSelect.value = address.district_ID;

    // Gọi load phường
    const wardRes = await fetch(`http://localhost:8080/SHOPANVAT/api/ghn/wards.php?district_id=${address.district_ID}`);
    const wards = (await wardRes.json()).data;
    const wardSelect = document.getElementById('ward');
    wardSelect.innerHTML = '';
    wards.forEach(w => {
        const opt = new Option(w.WardName, w.WardCode);
        wardSelect.add(opt);
    });
    wardSelect.value = address.ward_code;

    // Sau khi load xong hết → gọi lại GHN để tính phí ship
    const weight = parseInt(document.getElementById('total_weight').value);
    fetch('http://localhost:8080/SHOPANVAT/api/ghn/calculate_fee.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            district_id: address.district_ID,
            ward_code: address.ward_code,
            weight: weight
        })
    })
    .then(res => res.json())
    .then(data => {
        const fee = parseInt(data.fee ?? data.data?.total ?? 0);
        document.getElementById('shipping_fee').innerText = fee.toLocaleString('vi-VN') + ' VNĐ';
        document.getElementById('shipping_fee_input').value = fee;
recalculateFinalTotal();
        const originalTotal = parseFloat(document.getElementById('original_total').value);
        const discount = parseFloat(document.getElementById('discount_amount').value);
        const finalTotal = originalTotal + fee - discount;

        document.getElementById('final-amount').innerText = finalTotal.toLocaleString('vi-VN') + ' VNĐ';
        document.getElementById('final_total').value = finalTotal;
    })
    .catch(err => console.error("Lỗi khi gọi phí GHN:", err));
});

function recalculateFinalTotal() {
    const originalTotal = parseFloat(document.getElementById('original_total').value) || 0;
    const discount = parseFloat(document.getElementById('discount_amount').value) || 0;
    const shipping = parseFloat(document.getElementById('shipping_fee_input').value) || 0;

    const finalTotal = originalTotal + shipping - discount;

    document.getElementById('final-amount').innerText = finalTotal.toLocaleString('vi-VN') + ' VNĐ';
    document.getElementById('final_total').value = finalTotal;
}

function selectSavedVoucher(code) {
    const input = document.getElementById('voucher_code');
    if (code) {
        input.value = code; // Gán vào ô nhập tay
    } else {
        input.value = ''; // Xoá nếu chọn dòng đầu tiên
    }
}



function removeFromCart(productId) {
    Swal.fire({
        title: 'Xác nhận xóa?',
        text: "Bạn có chắc muốn xóa sản phẩm này khỏi đơn hàng?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            const cartCookie = getCookie("cart");
            if (!cartCookie) return;

            let cart = [];
            try {
                cart = JSON.parse(decodeURIComponent(cartCookie));
            } catch (e) {
                console.error("Lỗi đọc cookie giỏ hàng:", e);
                return;
            }

            // Xoá sản phẩm khỏi mảng
            cart = cart.filter(item => item.id != productId);

            // Ghi đè lại cookie cart mới
            document.cookie = "cart=" + encodeURIComponent(JSON.stringify(cart)) + "; path=/; max-age=86400";

            // Hiển thị thông báo và reload sau khi xóa
            Swal.fire({
                icon: 'success',
                title: 'Đã xóa',
                text: 'Sản phẩm đã được xóa khỏi giỏ hàng.',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        }
    });
}


function getCookie(name) {
    const value = "; " + document.cookie;
    const parts = value.split("; " + name + "=");
    if (parts.length === 2) return parts.pop().split(";").shift();
    return null;
}


    </script>

<script>
document.querySelectorAll('.payment-card').forEach(card => {
  card.addEventListener('click', function () {
    document.querySelectorAll('.payment-card').forEach(c => c.classList.remove('selected'));
    this.classList.add('selected');
    this.querySelector('input[type=radio]').checked = true;

    // Hiển thị khối chuyển khoản nếu chọn bank_transfer
    const bankInfo = document.getElementById('bank-transfer-info');
    if (!bankInfo) return;

    if (this.querySelector('input').value === 'bank_transfer') {
      bankInfo.style.display = 'block';
    } else {
      bankInfo.style.display = 'none';
    }
  });
});
</script>


<script>
document.getElementById('btn-confirm-transfer')?.addEventListener('click', function () {
  const orderID = document.getElementById('order-id-display')?.textContent || '';

  if (!orderID) {
    Swal.fire({
      icon: 'error',
      title: 'Không tìm thấy đơn hàng',
      text: 'Không thể lấy mã đơn hàng để xác nhận chuyển khoản.'
    });
    return;
  }

  Swal.fire({
    title: 'Bạn đã chuyển khoản?',
    text: 'Xác nhận này sẽ giúp chúng tôi kiểm tra đơn hàng của bạn.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Đã chuyển',
    cancelButtonText: 'Hủy'
  }).then(result => {
    if (result.isConfirmed) {
      fetch('api/bank_confirm.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'order_ID=' + encodeURIComponent(orderID)
      })
      .then(res => res.json())
      .then(data => {
        Swal.fire({
          icon: data.success ? 'success' : 'error',
          title: data.success ? 'Thành công' : 'Thất bại',
          text: data.message
        }).then(() => {
          if (data.success) {
            location.reload();
          }
        });
      })
      .catch(error => {
        Swal.fire({
          icon: 'error',
          title: 'Lỗi mạng',
          text: 'Không thể kết nối đến máy chủ.'
        });
        console.error('Lỗi xác nhận:', error);
      });
    }
  });
});
</script>

<input type="hidden" name="final_total" id="final_total" value="<?php echo $total; ?>">

<script>
document.addEventListener("DOMContentLoaded", function () {
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    const paypalNote = document.getElementById('paypal-note');
    const exchangeRate = 26000;

    function updatePaypalNote() {
        const totalVND = parseFloat(document.getElementById('final_total')?.value || 0);
        const usd = (totalVND / exchangeRate).toFixed(2);

        if (paypalNote) {
            paypalNote.innerHTML = `
                <h5>💲 Giá trị thanh toán: ${totalVND.toLocaleString('vi-VN')} VND</h5>
                <p>(~ ${usd} USD theo tỉ giá 1 USD ≈ 26.000 VND)</p>
            `;
            paypalNote.style.display = 'block';
        }
    }

    paymentRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            if (!paypalNote) return;

            if (this.value === 'paypal') {
                updatePaypalNote();
            } else {
                paypalNote.style.display = 'none';
            }
        });
    });

    // Nếu mặc định chọn PayPal thì hiển thị ngay
    const selected = document.querySelector('input[name="payment_method"]:checked');
    if (selected?.value === 'paypal') {
        updatePaypalNote();
    }

    // Nếu muốn cập nhật tự động khi giá thay đổi:
    const observer = new MutationObserver(updatePaypalNote);
    const target = document.getElementById('final_total');
    if (target) {
        observer.observe(target, { attributes: true, attributeFilter: ['value'] });
    }
});
</script>

<script>
document.querySelector('form[action=""]')?.addEventListener('submit', function (e) {
    const provinceID = document.getElementById('province_ID')?.value.trim();
    const districtID = document.getElementById('district_ID')?.value.trim();
    const wardCode = document.getElementById('ward_code')?.value.trim();
    const detailAddress = document.getElementById('detail_address')?.value.trim();

    // Kiểm tra thiếu địa chỉ
    if (!provinceID || !districtID || !wardCode || !detailAddress) {
        e.preventDefault(); // chặn submit
        Swal.fire({
            icon: 'warning',
            title: 'Thiếu thông tin địa chỉ',
            text: 'Vui lòng chọn đầy đủ Tỉnh/Thành, Quận/Huyện, Phường/Xã và nhập địa chỉ cụ thể.'
        });
        return false;
    }

    return true;
});
</script>

<script>
function suggestBestVoucher(totalAmount) {
    console.log("Gửi POST tới suggest_voucher.php với total =", totalAmount);

    fetch('api/suggest_voucher.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'total=' + totalAmount
    })
    .then(async res => {
        const text = await res.text();
        console.log("Kết quả từ suggest_voucher.php:", text);
        return JSON.parse(text);
    })
    .then(data => {
        const box = document.getElementById('best_voucher_suggestion');
        const code = document.getElementById('best_voucher_code');
        const discount = document.getElementById('best_voucher_discount');

        if (data.success) {
            code.innerText = data.voucher.code;
            discount.innerText = new Intl.NumberFormat('vi-VN').format(data.expected_discount) + ' VNĐ';
            box.style.display = 'block';
            window.bestVoucherCode = data.voucher.code;
        } else {
            box.style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Lỗi khi gợi ý mã tốt nhất:', error);
    });
}


function selectBestVoucher() {
    if (window.bestVoucherCode) {
        document.getElementById('voucher_code').value = window.bestVoucherCode;
        applyVoucher();
    }
}
</script>

<script>
window.onload = function () {
    const totalElement = document.querySelector("#final-amount"); // Đúng ID đã có trong HTML

    if (totalElement) {
        const totalText = totalElement.innerText.replace(/[^\d]/g, ''); // Loại bỏ chữ và dấu chấm
        const totalAmount = parseInt(totalText);
        console.log("Tổng tiền:", totalAmount); // Debug
        suggestBestVoucher(totalAmount);
    } else {
        console.warn("#final-amount không tồn tại trên trang");
    }
};
</script>



</body>
<style>
    .xemlai {
        font-size: 18px;
        font-weight: 500;
        color: blue;
    }

    .b-500 {
        font-weight: 500;
    }

    .bold {
        font-weight: bold;
    }

    .red {
        color: rgba(207, 16, 16, 0.815);
    }

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
    padding: 4px 8px;
    font-size: 14px;
    border-radius: 4px;
    cursor: pointer;
}

.quantity-control input {
    width: 45px;
    text-align: center;
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 4px;
}

.payment-methods {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.payment-card {
  display: flex;
  align-items: center;
  border: 2px solid #000 !important;   /* Viền đen rõ ràng */
  border-radius: 8px;
  padding: 12px 16px;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  background: #fff;
}


.payment-card:hover {
  border-color: #007bff;
  background-color: #f8f9fa;
}

.payment-card input[type="radio"] {
  margin-right: 16px;
  transform: scale(1.3);
}

.payment-card img {
  width: 36px;
  height: 36px;
  object-fit: contain;
  margin-right: 12px;
}

.payment-card span {
  font-size: 16px;
  font-weight: 500;
}

.payment-card.selected {
  border-color: #007bff;
  background-color: #e7f1ff;
}
#bank-transfer-info {
  display: none;
  transition: all 0.3s ease-in-out;
  opacity: 0;
}

#bank-transfer-info.show {
  display: block;
  opacity: 1;
}

/* Làm viền input/select/textarea rõ hơn */
.form-control {
    border: 1px solid #666 !important;   /* xám đậm */
    box-shadow: none !important;         /* bỏ viền mờ mặc định */
}

/* Khi focus vào ô nhập liệu */
.form-control:focus {
    border-color: #000 !important;       /* đen rõ */
    box-shadow: 0 0 3px rgba(0,0,0,0.3) !important;
}

/* ========== FORM ========== */
.form-control {
    border: 1.5px solid #000 !important;   /* viền đen rõ ràng */
    border-radius: 4px;
    padding: 6px 10px;
}

/* ========== BẢNG SẢN PHẨM ========== */
.table, 
.table th, 
.table td {
    border: 1.5px solid #000 !important;   /* viền đậm cho bảng */
    border-collapse: collapse !important;
}

.table th {
    background-color: #f2f2f2;   /* nền header để dễ nhìn */
    font-weight: bold;
    text-align: center;
}

/* ========== PHƯƠNG THỨC THANH TOÁN ========== */
.payment-method {
    border: 1.5px solid #000 !important;   /* khung đậm */
    padding: 10px;
    margin-top: 10px;
    border-radius: 6px;
}

.payment-method label {
    display: block;
    margin-bottom: 6px;
    cursor: pointer;
}

/* Làm rõ radio button */
.payment-method input[type="radio"] {
    transform: scale(1.2);   /* phóng to để dễ thấy */
    margin-right: 6px;
}

.payment-methods .payment-option {
    border: 2px solid #000 !important;  /* Viền đen rõ ràng */
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 12px;
    background: #fff;
}


</style>

</html>