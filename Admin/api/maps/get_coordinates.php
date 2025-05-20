<?php
// File lấy tọa độ từ địa chỉ
header('Content-Type: application/json');
session_start();
require_once '../../../config/db.php';

// Kiểm tra đăng nhập với quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra xem có dữ liệu địa chỉ thành phần không
    if (isset($_POST['province_name']) || isset($_POST['district_name']) || isset($_POST['ward_name']) || isset($_POST['address_detail'])) {
        // Lấy dữ liệu từ form
        $address_detail = isset($_POST['address_detail']) ? $_POST['address_detail'] : '';
        $ward_name = isset($_POST['ward_name']) ? $_POST['ward_name'] : '';
        $district_name = isset($_POST['district_name']) ? $_POST['district_name'] : '';
        $province_name = isset($_POST['province_name']) ? $_POST['province_name'] : '';

        // Tạo địa chỉ đầy đủ
        $full_address = "";
        if (!empty($address_detail)) $full_address .= $address_detail;
        if (!empty($ward_name)) $full_address .= (!empty($full_address) ? ", " : "") . $ward_name;
        if (!empty($district_name)) $full_address .= (!empty($full_address) ? ", " : "") . $district_name;
        if (!empty($province_name)) $full_address .= (!empty($full_address) ? ", " : "") . $province_name;
        $full_address .= ", Vietnam"; // Thêm quốc gia để tăng độ chính xác

        $address = urlencode($full_address);
    }
    // Nếu không có địa chỉ thành phần, kiểm tra địa chỉ đầy đủ
    else if (isset($_POST['address'])) {
        $address = urlencode($_POST['address']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Địa chỉ không được cung cấp']);
        exit;
    }

    // Dùng Google Geocoding API (bạn cần thay API_KEY bằng key của bạn)
    // Nếu không có API key, bạn có thể sử dụng Nominatim OpenStreetMap (miễn phí nhưng hạn chế request)
    // $api_url = "https://maps.googleapis.com/maps/api/geocode/json?address=$address&key=AIzaSyAQ7hhG0JUMfN_fRK1dQl7ajyQ0-LtxYT0";

    // Sử dụng Nominatim OpenStreetMap (miễn phí)
    $api_url = "https://nominatim.openstreetmap.org/search?format=json&q=$address&limit=1";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PhongTroApplication/1.0'); // OpenStreetMap yêu cầu User-Agent

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo json_encode(['success' => false, 'message' => 'Curl error: ' . curl_error($ch)]);
        exit;
    }

    curl_close($ch);
    $data = json_decode($response, true);

    // Xử lý kết quả từ Nominatim
    if (is_array($data) && count($data) > 0) {
        $result = [
            'success' => true,
            'lat' => $data[0]['lat'],
            'lng' => $data[0]['lon'],
            'formatted_address' => $data[0]['display_name']
        ];
        echo json_encode($result);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể tìm thấy tọa độ cho địa chỉ này.',
            'data' => $data
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Địa chỉ không được cung cấp']);
}
