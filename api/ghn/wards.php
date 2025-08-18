<?php
require 'ghn_config.php';

$district_id = isset($_GET['district_id']) ? intval($_GET['district_id']) : 0;

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => GHN_API . "master-data/ward?district_id=" . $district_id,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Token: " . GHN_TOKEN
    ],
]);

$response = curl_exec($curl);
curl_close($curl);

header('Content-Type: application/json');
echo $response; 
    