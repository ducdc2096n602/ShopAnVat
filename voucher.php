<?php
require_once('helpers/startSession.php');
startRoleSession('customer');
// Sau khi chắc chắn session đã khởi tạo, mới log:
echo "<script>console.log('PHP sees account_ID = " . ($_SESSION['account_ID'] ?? 'null') . "');</script>";

require_once('database/config.php');
require_once('database/dbhelper.php');
require_once('layout/header.php');

$sql = "SELECT * FROM Voucher WHERE end_date >= CURDATE() ORDER BY end_date ASC";
$vouchers = executeResult($sql);

$savedVouchers = [];
$account_id = $_SESSION['account_ID'] ?? null;
if ($account_id) {
    $sqlSaved = "SELECT voucher_ID FROM SavedVoucher WHERE account_ID = $account_id";
    $saved = executeResult($sqlSaved);
    foreach ($saved as $s) {
        $savedVouchers[] = $s['voucher_ID'];
    }
}

function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . 'đ';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách Voucher</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .voucher-img-wrapper {
            width: 120px;
            flex-shrink: 0;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            border-right: 1px solid #ddd;
        }
        .voucher-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            padding: 10px;
        }
        .tooltip-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 999;
        }
        .tooltip-popup {
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px 24px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 90%;
            z-index: 1000;
            display: none;
        }
        .tooltip-popup .close-btn {
            position: absolute;
            top: 8px; right: 12px;
            background: transparent;
            border: none;
            font-size: 20px;
            cursor: pointer;
        }
        .voucherSwiper img {
            width: 100%;
            height: auto;
            border-radius: 10px;
            object-fit: cover;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<body>

<div class="swiper voucherSwiper mb-4">
    <div class="swiper-wrapper">
        <div class="swiper-slide"><img src="images/uploads/banner/banner_voucher4.png" class="img-fluid" alt="Voucher Banner 4"></div>
        <div class="swiper-slide"><img src="images/uploads/banner/banner_voucher3.jpg" class="img-fluid" alt="Voucher Banner 3"></div>
    </div>
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
    <div class="swiper-pagination"></div>
</div>

<div class="container mt-4">
    <div class="row">
        <?php foreach ($vouchers as $v): ?>
            <?php $isSaved = in_array($v['voucher_ID'], $savedVouchers); ?>
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100 d-flex flex-row overflow-hidden">
                    <div class="voucher-img-wrapper">
                        <?php if (!empty($v['image_url'])): ?>
                            <img src="images/uploads/vouchers/<?= htmlspecialchars($v['image_url']) ?>" alt="Voucher image" class="voucher-img">
                        <?php endif; ?>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-between w-100">
                        <div>
                            <h5 class="card-title font-weight-bold mb-1"><?= htmlspecialchars($v['code']) ?></h5>
                            <p class="mb-1">
                                <?= $v['discount_type'] === 'percent'
                                    ? 'Giảm ' . $v['discount_value'].'%'
                                    : 'Giảm ' . formatCurrency($v['discount_value']) ?>
                            </p>
                            <p class="mb-1">Đơn tối thiểu: <?= formatCurrency($v['min_order_amount']) ?></p>
                            <p class="mb-1">
                                HSD: Từ <?= date('d/m/Y', strtotime($v['start_date'])) ?>
                                đến <?= date('d/m/Y', strtotime($v['end_date'])) ?>
                            </p>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mt-2">
                            <button class="btn btn-sm <?= $isSaved ? 'btn-success' : 'btn-outline-primary' ?> btn-save"
                                    onclick="saveVoucher(<?= $v['voucher_ID'] ?>)"
                                    <?= $isSaved ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-ticket"></i>
                                <?= $isSaved ? 'Đã lưu' : 'Lưu voucher' ?>
                            </button>

                            <span class="info-icon ml-2 text-primary" style="cursor:pointer; font-size: 18px;"
                                onclick='showTooltip(
                                    <?= json_encode($v["code"]) ?>,
                                    <?= json_encode(date("d/m/Y", strtotime($v["start_date"]))) ?>,
                                    <?= json_encode(date("d/m/Y", strtotime($v["end_date"]))) ?>,
                                    <?= json_encode([
                                        ($v["discount_type"] === "percent"
                                            ? "Giảm {$v["discount_value"]}% cho đơn từ " . formatCurrency($v["min_order_amount"])
                                            : "Giảm " . formatCurrency($v["discount_value"]) . " cho đơn từ " . formatCurrency($v["min_order_amount"])
                                        ),
                                        "Chỉ sử dụng 1 lần/người"
                                    ]) ?>,
                                    <?= json_encode(strip_tags($v["description"])) ?>
                                )'

                                )">
                                <i class="fas fa-info-circle"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Tooltip overlay -->
<div class="tooltip-overlay" onclick="hideTooltip()"></div>

<!-- Tooltip popup -->
<div class="tooltip-popup" id="voucherDetail">
    <button class="close-btn" onclick="hideTooltip()">×</button>
    <h4>Mã: <span id="voucherCode">---</span></h4>
    <p><strong>Hạn sử dụng:</strong> <span id="voucherExpiry">---</span></p>
    
    <p><strong>Mô tả:</strong></p>
    <div id="voucherDescription" style="white-space: pre-line;"></div>

    <p class="mt-2"><strong>Điều kiện:</strong></p>
    <ul id="voucherConditions"></ul>
</div>

<script>
const isLoggedIn = <?= $account_id ? 'true' : 'false' ?>;

function saveVoucher(voucher_ID) {
    if (!isLoggedIn) {
        Swal.fire({
            icon: 'warning',
            title: 'Bạn chưa đăng nhập',
            text: 'Vui lòng đăng nhập để lưu voucher.',
            confirmButtonText: 'Đăng nhập ngay'
        }).then(() => {
            window.location.href = '/ShopAnVat/login/login.php';
        });
        return;
    }


fetch('/ShopAnVat/api/save_voucher.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Cache-Control': 'no-cache'
    },
    body: 'voucher_ID=' + encodeURIComponent(voucher_ID),
    credentials: 'include' // 👈 THÊM DÒNG NÀY!
})

.then(response => response.json())
.then(data => {
    Swal.fire({
        icon: data.success ? 'success' : 'error',
        title: data.success ? 'Thành công' : 'Lỗi',
        text: data.message,
        confirmButtonText: 'OK'
    }).then(() => {
        if (data.success) location.reload();
    });
})
.catch(error => {
    console.error('Fetch lỗi:', error);
});

}

function showTooltip(code, startDate, endDate, conditions, description) {
    document.getElementById('voucherCode').textContent = code;
    document.getElementById('voucherExpiry').textContent = `Từ ${startDate} đến ${endDate}`;
    document.getElementById('voucherDescription').textContent = description;

    const list = document.getElementById('voucherConditions');
    list.innerHTML = '';
    conditions.forEach(c => {
        const li = document.createElement('li');
        li.textContent = c;
        list.appendChild(li);
    });

    document.querySelector('.tooltip-overlay').style.display = 'block';
    document.getElementById('voucherDetail').style.display = 'block';
}

function hideTooltip() {
    document.querySelector('.tooltip-overlay').style.display = 'none';
    document.getElementById('voucherDetail').style.display = 'none';
}
</script>

<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script>
const swiper = new Swiper(".voucherSwiper", {
    loop: true,
    autoplay: {
        delay: 4000,
        disableOnInteraction: false,
    },
    pagination: {
        el: ".swiper-pagination",
        clickable: true,
    },
    navigation: {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
    },
});
</script>

</body>
</html>
<?php require('layout/footer.php'); ?>
<?php include('chatbot.php'); ?>