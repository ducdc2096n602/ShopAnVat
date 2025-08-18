<?php
require 'ghn_config.php';

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => GHN_API . "master-data/province",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Token: " . GHN_TOKEN
    ],
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

header('Content-Type: application/json');

if ($http_code == 200) {
    echo $response;
} else {
    echo json_encode([
        "success" => false,
        "message" => "GHN API error",
        "status_code" => $http_code,
        "raw_response" => $response
    ]);
}
