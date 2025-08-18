<?php
require_once('helpers/startSession.php');
startRoleSession('customer');

require_once('database/config.php');
require_once('database/dbhelper.php');
require_once('layout/header.php');

$account_id = $_SESSION['account_ID'] ?? null;
if (!$account_id) {
    echo "<div class='container mt-4 alert alert-warning'>Vui lòng đăng nhập để xem voucher đã lưu.</div>";
    exit;
}

$sql = "
    SELECT v.*
    FROM SavedVoucher sv
    JOIN Voucher v ON sv.voucher_ID = v.voucher_ID
    WHERE sv.account_ID = " . intval($account_id) . "
    ORDER BY v.end_date ASC
";
$vouchers = executeResult($sql);

function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . 'đ';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Voucher đã lưu</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .voucher-card {
            position: relative;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: #fff;
            padding: 10px;
            transition: 0.3s;
            display: flex;
            flex-direction: row;
            height: 100%;
        }
        .voucher-card.expired { opacity: 0.6; }

        .voucher-img-wrapper {
            width: 120px; height: 120px;
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            background: #f8f8f8;
            border-radius: 8px;
        }

        .voucher-img {
            max-width: 100%; max-height: 100%;
            object-fit: contain;
        }

        .voucher-body {
            padding-left: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .label-container {
            position: absolute;
            top: 10px; right: 10px;
            display: flex; flex-direction: column; gap: 4px;
            z-index: 3;
        }

        .label-badge {
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 4px;
            color: white;
        }

        .label-expired { background: #dc3545; }
        .label-out { background: #ffc107; color: black; }

        .tooltip-overlay {
            display: none;
            position: fixed; top: 0; left: 0;
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
    </style>
</head>
<body>

<div class="container mt-4">
    <?php if (empty($vouchers)): ?>
        <div class="alert alert-info">Bạn chưa lưu voucher nào.</div>
    <?php endif; ?>

    <div class="row">
        <?php foreach ($vouchers as $v): ?>
            <?php
                $isExpired = strtotime($v['end_date']) < strtotime(date('Y-m-d'));
                $hasUsageLimit = is_numeric($v['usage_limit']);
                $isOutOfStock = $hasUsageLimit && $v['usage_count'] >= $v['usage_limit'];
                $cardClass = ($isExpired || $isOutOfStock) ? 'expired' : '';
            ?>
            <div class="col-12 col-md-6 d-flex mb-4">
                <div class="voucher-card w-100 <?= $cardClass ?>">
                    <?php if ($isExpired || $isOutOfStock): ?>
                        <div class="label-container">
                            <?php if ($isExpired): ?>
                                <span class="label-badge label-expired">Đã hết hạn</span>
                            <?php endif; ?>
                            <?php if ($isOutOfStock): ?>
                                <span class="label-badge label-out">Hết lượt</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="voucher-img-wrapper">
                        <?php if (!empty($v['image_url'])): ?>
                            <img src="images/uploads/vouchers/<?= htmlspecialchars($v['image_url']) ?>" alt="Voucher" class="voucher-img">
                        <?php endif; ?>
                    </div>

                    <div class="voucher-body">
                        <h5 class="font-weight-bold"><?= htmlspecialchars($v['code']) ?></h5>
                        <p>
                            <?= $v['discount_type'] === 'percent'
                                ? 'Giảm ' . $v['discount_value'] . '%'
                                : 'Giảm ' . formatCurrency($v['discount_value']) ?>
                        </p>
                        <p>Đơn tối thiểu: <?= formatCurrency($v['min_order_amount']) ?></p>
                        <p>HSD: Từ <?= date('d/m/Y', strtotime($v['start_date'])) ?> đến <?= date('d/m/Y', strtotime($v['end_date'])) ?></p>

                        <div class="d-flex align-items-center mt-auto">
                            <button class="btn btn-sm btn-success" disabled>
                                <i class="fa-solid fa-ticket"></i> Đã lưu
                            </button>
                            <button class="btn btn-sm btn-danger ml-2" onclick="unsaveVoucher(<?= $v['voucher_ID'] ?>)">
                                <i class="fa-solid fa-xmark"></i> Hủy lưu
                            </button>
                            <span class="ml-auto text-primary" style="cursor:pointer;" onclick="showTooltip(
                                '<?= htmlspecialchars($v['code']) ?>',
                                '<?= date('d/m/Y', strtotime($v['start_date'])) ?>',
                                '<?= date('d/m/Y', strtotime($v['end_date'])) ?>',
                                ['<?= $v['discount_type'] === 'percent'
                                    ? "Giảm {$v['discount_value']}% cho đơn từ " . formatCurrency($v['min_order_amount'])
                                    : "Giảm " . formatCurrency($v['discount_value']) . " cho đơn từ " . formatCurrency($v['min_order_amount']) ?>',
                                 'Chỉ sử dụng 1 lần/người']
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

<!-- Tooltip -->
<div class="tooltip-overlay" onclick="hideTooltip()"></div>

<div class="tooltip-popup" id="voucherDetail">
    <button class="close-btn" onclick="hideTooltip()">×</button>
    <h4>Mã: <span id="voucherCode">---</span></h4>
    <p><strong>Hạn sử dụng:</strong> <span id="voucherExpiry">---</span></p>
    <p><strong>Điều kiện:</strong></p>
    <ul id="voucherConditions"></ul>
</div>

<script>
function showTooltip(code, startDate, endDate, conditions) {
    document.getElementById('voucherCode').textContent = code;
    document.getElementById('voucherExpiry').textContent = `Từ ${startDate} đến ${endDate}`;

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

function unsaveVoucher(voucher_ID) {
    fetch('/ShopAnVat/api/unsave_voucher.php?voucher_ID=' + voucher_ID, {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        Swal.fire({
            icon: data.status === 'success' ? 'success' : 'error',
            title: data.status === 'success' ? 'Thành công' : 'Lỗi',
            text: data.message,
            confirmButtonText: 'OK'
        }).then(() => {
            if (data.status === 'success') {
                location.reload();
            }
        });
    })
    .catch(error => {
        console.error('Lỗi khi hủy lưu voucher:', error);
    });
}
</script>

</body>
</html>

<?php require('layout/footer.php'); ?>
<?php include('chatbot.php'); ?>
