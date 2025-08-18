<?php
require_once('../database/dbhelper.php');
header('Content-Type: application/json');



$question = strtolower(trim($_POST['question'] ?? ''));
if ($question == '') {
    echo json_encode(['answer' => '❓ Vui lòng nhập câu hỏi.']);
    exit;
}

function normalizeText($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace(
        [
            "/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/",
            "/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/",
            "/(ì|í|ị|ỉ|ĩ)/",
            "/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/",
            "/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/",
            "/(ỳ|ý|ỵ|ỷ|ỹ)/",
            "/(đ)/"
        ],
        ["a","e","i","o","u","y","d"],
        $text
    );
    return preg_replace('/\s+/', ' ', trim($text));
}

$normalizedQuestion = normalizeText($question);

// ==== 1. Trả lời chào ====
if (strpos($question, 'xin chào') !== false) {
    echo json_encode(['answer' => ' Thế giới ăn vặt xin chào, mình có thể tư vấn cho bạn về sản phẩm, voucher, chính sách vận chuyển, phí vận chuyển, phương thức thanh toán,..']);
    exit;
}

// ==== 1.1 Trả lời chi tiết một sản phẩm ====
if (preg_match('/(thành phần|nguyên liệu|mô tả|gồm những gì)/i', $question)) {
    $products = executeResult("SELECT product_name, description FROM product WHERE is_deleted = 0");
    foreach ($products as $p) {
        if (strpos($question, strtolower($p['product_name'])) !== false) {
            $desc = $p['description'] ?: '📄 Món này chưa có mô tả chi tiết.';
            echo json_encode([
               'answer' => '<strong>' . htmlspecialchars($p['product_name']) . '</strong>:<br>' . $desc
            ]);
            exit;
        }
    }
}

// ==== 1.2 Trả lời về thanh toán ====
$paymentKeywords = ['thanh toán', 'thánh toán', 'cách trả tiền', 'trả tiền thế nào', 'payment'];
foreach ($paymentKeywords as $kw) {
    if (strpos($question, $kw) !== false) {
        $html = "💳 Hiện tại Thế Giới Ăn Vặt hỗ trợ các phương thức thanh toán:<br>" .
                " - Tiền mặt khi nhận hàng (COD)<br>" .
                " - Chuyển khoản ngân hàng<br>" .
                " - Thanh toán qua ví điện tử (Momo, ZaloPay...)<br>" .
                "Bạn có thể chọn hình thức phù hợp khi đặt hàng.";
        echo json_encode(['answer' => $html]);
        exit;
    }
}

// ==== 2.1 Trả lời sâu theo danh mục ====
$categories = executeResult("SELECT * FROM category WHERE is_deleted = 0");
foreach ($categories as $cate) {
    $cateName = mb_strtolower($cate['category_name']);
    if (strpos($question, $cateName) !== false) {
        $products = executeResult("
            SELECT product_name, base_price 
            FROM product 
            WHERE is_deleted = 0 AND category_ID = ?
        ", [$cate['category_ID']]);

        if (count($products) == 0) {
            $html = '📭 Hiện chưa có sản phẩm nào trong danh mục <strong>' . htmlspecialchars($cate['category_name']) . '</strong>.';
        } else {
            $html = '📂 <strong>' . htmlspecialchars($cate['category_name']) . '</strong> hiện có các món:<br>';
            foreach ($products as $p) {
                $html .= ' - ' . htmlspecialchars($p['product_name']) . ' (Giá: ' . number_format($p['base_price'], 0, ',', '.') . 'đ)<br>';
            }
        }

        echo json_encode(['answer' => $html]);
        exit;
    }
}

// ==== 2. Trả lời sản phẩm chung ====
if (strpos($question, 'sản phẩm') !== false || strpos($question, 'món') !== false) {
    $html = "🍡 Một vài món bạn có thể thử theo từng danh mục:<br /><br />";
    foreach ($categories as $cate) {
        $html .= '📂 <strong>' . htmlspecialchars($cate['category_name']) . "</strong>:<br />";
        $products = executeResult("
            SELECT product_name, base_price 
            FROM product 
            WHERE is_deleted = 0 AND category_ID = ? 
            ORDER BY RAND() LIMIT 2
        ", [$cate['category_ID']]);

        if (count($products) == 0) {
            $html .= " - (Hiện chưa có món nào)<br />";
        } else {
            foreach ($products as $p) {
                $html .= ' - ' . htmlspecialchars($p['product_name']) . ' (Giá: ' . number_format($p['base_price'], 0, ',', '.') . 'đ)<br />';
            }
        }
        $html .= "<br />";
    }
    echo json_encode(['answer' => $html]);
    exit;
}

// ==== 3. Trả lời về voucher / khuyến mãi ====
if (strpos($question, 'voucher') !== false || strpos($question, 'khuyến mãi') !== false || strpos($question, 'mã giảm') !== false) {
    $today = date('Y-m-d');
    $vouchers = executeResult("
        SELECT code, description, end_date FROM voucher 
        WHERE is_deleted = 0 
          AND end_date >= ? 
          AND (usage_limit IS NULL OR usage_count < usage_limit)
        ORDER BY end_date ASC
        LIMIT 5
    ", [$today]);

    if (count($vouchers) == 0) {
        echo json_encode(['answer' => '📭 Hiện tại không có chương trình khuyến mãi nào đang diễn ra.']);
    } else {
        $html = "🎁 Các voucher hiện có:<br />";
        foreach ($vouchers as $v) {
            $html .= "- Mã: <strong>{$v['code']}</strong><br /> ➤ {$v['description']}<br /> ⏳ HSD: " . date('d/m/Y', strtotime($v['end_date'])) . "<br /><br />";
        }
        echo json_encode(['answer' => $html]);
    }
    exit;
}

// ==== 4. Trả lời về đơn hàng ====
$orderKeywords = ['đơn hàng', 'mã đơn', 'đơn của tôi', 'trạng thái đơn'];
foreach ($orderKeywords as $kw) {
    if (strpos($question, $kw) !== false) {
        session_start();
        if (isset($_SESSION['account_ID']) && ($_SESSION['role'] ?? '') === 'customer') {
            echo json_encode([
                'answer' => '📦 Bạn có thể xem đơn hàng của mình tại <a href="/ShopAnVat/history.php" target="_blank">Đơn hàng của tôi</a>.'
            ]);
        } else {
            echo json_encode([
                'answer' => '🔒 Vui lòng <a href="/ShopAnVat/login/login.php?redirect=/ShopAnVat/history.php" target="_blank">đăng nhập</a> để xem đơn hàng của bạn.'
            ]);
        }
        exit;
    }
}

// ==== 5. Truy vấn bảng knowledgeBase để trả lời ====


$kb = executeSingleResult("
    SELECT answer 
    FROM knowledgebase 
    WHERE is_deleted = 0 
      AND LOWER(REPLACE(question, 'đ', 'd')) LIKE ?
    ORDER BY updated_at DESC 
    LIMIT 1
", ['%' . $normalizedQuestion . '%']);

if ($kb && isset($kb['answer'])) {
    echo json_encode(['answer' => $kb['answer']]);
    exit;
}

// ==== 6. Nếu không tìm thấy câu trả lời → hỏi Gemini ====
$middlewareUrl = 'http://localhost:3001/ask';

$ch = curl_init($middlewareUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['question' => $question]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo json_encode(['answer' => $data['reply'] ?? '🤖 Không nhận được phản hồi.']);
} else {
    echo json_encode([
        'answer' => '🤖 Xin lỗi, mình chưa có thông tin cho câu hỏi đó. Bạn có thể hỏi về sản phẩm, voucher, đơn hàng, giờ mở cửa, chính sách vận chuyển, o vị trí cửa hàng... nhé!'
    ]);
}
exit;
