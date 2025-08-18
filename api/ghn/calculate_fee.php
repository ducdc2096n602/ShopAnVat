<?php
header('Content-Type: application/json');
$input = json_decode(file_get_contents("php://input"), true);

$input = json_decode(file_get_contents("php://input"), true);
file_put_contents("log_input.txt", print_r($input, true));
$data = json_decode(file_get_contents("php://input"), true);

// Nếu decode thất bại (sai format hoặc rỗng)
if (!$data || !is_array($data)) {
    echo json_encode(['error' => 'Dữ liệu không hợp lệ']);
    exit;
}

// Kiểm tra từng trường
if (!isset($data['district_id'], $data['ward_code'], $data['weight'])) {
    echo json_encode(['error' => 'Thiếu dữ liệu đầu vào']);
    exit;
}


$to_district_id = (int)$input['district_id'];
$to_ward_code = $input['ward_code'];
$weight = (int)$input['weight'];

$token = '0ce1745d-50be-11f0-b5e1-defdee9f0d5d';
$from_district_id = 1574; // Quận shop bạn đã đăng ký
$shop_id = 5854597;

// Step 1: Lấy danh sách dịch vụ giao hàng khả dụng
$service_url = 'https://online-gateway.ghn.vn/shiip/public-api/v2/shipping-order/available-services';
$service_data = [
    "shop_id" => $shop_id,
    "from_district" => $from_district_id,
    "to_district" => $to_district_id
];

$ch = curl_init($service_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($service_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Token: ' . $token
]);

$service_response = curl_exec($ch);
curl_close($ch);

$service_result = json_decode($service_response, true);
if (!isset($service_result['data'][0]['service_id'])) {
    echo json_encode(['error' => 'Không tìm thấy service_id hợp lệ: ' . $service_response]);
    exit;
}

$service_id = $service_result['data'][0]['service_id'];

// Step 2: Tính phí với service_id vừa lấy
$fee_url = 'https://online-gateway.ghn.vn/shiip/public-api/v2/shipping-order/fee';
$fee_data = [
    "service_id" => $service_id,
    "insurance_value" => 500000,
    "from_district_id" => $from_district_id,
    "to_district_id" => $to_district_id,
    "to_ward_code" => $to_ward_code,
    "height" => 15,
    "length" => 15,
    "weight" => $weight,
    "width" => 15
];

$ch = curl_init($fee_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fee_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Token: ' . $token
]);

$fee_response = curl_exec($ch);
curl_close($ch);

$fee_result = json_decode($fee_response, true);

if (!isset($fee_result['data']['total'])) {
    echo json_encode(['error' => 'Không lấy được phí vận chuyển, vui lòng thử lại: ' . $fee_response]);
    exit;
}

echo json_encode(['fee' => $fee_result['data']['total']]);
