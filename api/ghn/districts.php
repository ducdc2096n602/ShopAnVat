<?php
require 'ghn_config.php';

$province_id = isset($_GET['province_id']) ? intval($_GET['province_id']) : 0;

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => GHN_API . "master-data/district",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode(['province_id' => $province_id]),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Token: " . GHN_TOKEN
    ],
]);

$response = curl_exec($curl);
curl_close($curl);

$data = json_decode($response, true);
echo json_encode($data['data']);
