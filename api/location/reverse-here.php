<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;

if (!$lat || !$lng) {
    echo json_encode(['success' => false, 'message' => 'Missing lat/lng']);
    exit;
}

// Hàm gọi HERE API để reverse geocoding
function queryHereGeocoding($lat, $lng, $api_key)
{
    $latlng = rawurlencode($lat . ',' . $lng);
    $url = "https://revgeocode.search.hereapi.com/v1/revgeocode?at={$latlng}&lang=vi&apiKey={$api_key}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return null;

    return json_decode($response, true);
}

// Gọi API
$api_key = 'Q6UwbGeT3kz6Yky98HUABsnjt_oLw-AaTo04Q1sY8I8'; // Thay bằng key thật
$data = queryHereGeocoding($lat, $lng, $api_key);
// echo json_encode($data);
if (!empty($data['items'][0])) {
    $item = $data['items'][0];
    echo json_encode([
        'success' => true,
        'address' => $item['address']['label'] ?? '',
        'raw' => $item
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy địa chỉ']);
}
