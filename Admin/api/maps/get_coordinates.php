<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$address = isset($_POST['address']) ? trim($_POST['address']) : '';

if (empty($address)) {
    echo json_encode(['success' => false, 'message' => 'Address is required']);
    exit;
}

// Hàm chuẩn hóa địa chỉ
function formatAddress($address) {
    $address = mb_strtolower($address, 'UTF-8');
    $address = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $address);
    $address = preg_replace('/[^a-zA-Z0-9\s,.-]/', '', $address);

    $replaceMap = [
        'tp.' => 'thanh pho',
        't.p.' => 'thanh pho',
        'tp ' => 'thanh pho ',
        't.p ' => 'thanh pho ',
        'p.' => 'phuong',
        'p ' => 'phuong ',
        'f.' => 'phuong',
        'f ' => 'phuong ',
        'q.' => 'quan',
        'q ' => 'quan ',
        'duong' => 'duong',
        'pho' => 'duong',
        'tinh' => '',
        'xa' => 'xa',
        'huyen' => 'huyen',
        'nghe an' => 'nghe an'
    ];

    foreach ($replaceMap as $search => $replace) {
        $address = str_replace($search, $replace, $address);
    }

    $address = preg_replace('/\s+/', ' ', $address);
    $address = trim($address);
    $address = preg_replace('/\s*,\s*/', ', ', $address);
    $address = preg_replace('/\s*\.\s*/', '. ', $address);
    $address = rtrim($address, ',.');

    return $address;
}

// Hàm gọi API Google Maps
function queryGoogleMaps($address, $api_key) {
    $encoded = urlencode($address);
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$encoded}&components=country:VN&key={$api_key}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    
    // Thêm thông tin debug
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    
    // Thêm thông tin debug vào response
    if (isset($data['error_message'])) {
        $data['debug_info'] = [
            'http_code' => $http_code,
            'curl_error' => $error,
            'request_url' => $url
        ];
    }

    return $data;
}

// Bắt đầu xử lý
$api_key = 'AIzaSyAZLD0ujNK_I-2BCE8w4j76Ko6aUPiFXs4';
$formatted = formatAddress($address);
$data = queryGoogleMaps($formatted, $api_key);

// Xử lý các trường hợp lỗi cụ thể
if ($data['status'] === 'REQUEST_DENIED') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'API key không hợp lệ hoặc chưa được cấu hình đúng',
        'status' => $data['status'],
        'error_message' => $data['error_message'] ?? 'Không có thông tin lỗi chi tiết',
        'debug_info' => $data['debug_info'] ?? null
    ]);
    exit;
}

// Nếu không tìm thấy thì thử "làm mềm" địa chỉ
if ($data['status'] !== 'OK') {
    $soft = preg_replace('/^\s*\d+\s*(ngõ|ngo|hem|ngo|ngach)?\s*\S*\s*/i', '', $formatted);
    $data = queryGoogleMaps($soft, $api_key);
}

// Trả về kết quả nếu có
if ($data['status'] === 'OK' && !empty($data['results'])) {
    $result = $data['results'][0];
    
    echo json_encode([
        'success' => true,
        'coordinates' => [
            'lat' => $result['geometry']['location']['lat'],
            'lng' => $result['geometry']['location']['lng'],
            'address' => $result['formatted_address'],
            'confidence' => $result['geometry']['location_type']
        ]
    ]);
} else {
    http_response_code(404);
    echo json_encode([
        'success' => false, 
        'message' => 'Không tìm thấy tọa độ cho địa chỉ này',
        'status' => $data['status'] ?? 'UNKNOWN_ERROR',
        'error_message' => $data['error_message'] ?? null,
        'debug_info' => $data['debug_info'] ?? null
    ]);
}
