<?php
header('Content-Type: application/json');

/**
 * Lấy vị trí từ IP address và kiểm tra xem có nằm trong địa phận Vinh không
 */

// Hàm lấy IP thực của người dùng
function getRealIpAddr()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        // IP từ shared internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // IP nếu user đang sử dụng proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        // IP của người dùng trực tiếp kết nối
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    // Local development
    if ($ip == '127.0.0.1' || $ip == '::1') {
        echo json_encode([
            'success' => false,
            'message' => 'Địa chỉ IP không hợp lệ'
        ]);
        exit;
    }

    return $ip;
}

// Hàm kiểm tra một địa điểm có nằm trong khu vực Vinh không
// Sử dụng phương pháp đơn giản bằng cách kiểm tra khoảng cách đến trung tâm Vinh
function isLocationInVinh($lat, $lng)
{
    // Tọa độ trung tâm thành phố Vinh
    $vinhLat = 18.6667;
    $vinhLng = 105.6667;

    // Tính khoảng cách (km) giữa hai điểm sử dụng công thức Haversine
    $earthRadius = 6371; // Bán kính Trái Đất (km)

    $dLat = deg2rad($lat - $vinhLat);
    $dLng = deg2rad($lng - $vinhLng);

    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($vinhLat)) * cos(deg2rad($lat)) * sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earthRadius * $c;

    // Kiểm tra nếu khoảng cách <= 15km thì coi là trong Vinh city
    // (Đường kính thành phố Vinh khoảng 30km)
    return $distance <= 15;
}

// Lấy IP của người dùng
$ip = getRealIpAddr();

try {
    // Sử dụng IP-API để lấy thông tin địa lý (miễn phí, không cần API key)
    $url = "http://ip-api.com/json/{$ip}?fields=status,lat,lon,city,regionName,country";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) {
        throw new Exception("Không thể kết nối đến dịch vụ định vị IP");
    }

    $data = json_decode($response, true);

    if (!$data || $data['status'] !== 'success') {
        throw new Exception("Không tìm thấy thông tin vị trí cho IP");
    }

    // Lấy tọa độ từ kết quả
    $lat = $data['lat'];
    $lng = $data['lon'];

    // Kiểm tra xem có nằm trong Vinh không
    $isInVinh = isLocationInVinh($lat, $lng);

    // Lấy thông tin quận/phường từ tọa độ
    // Sử dụng HERE API như trong ví dụ reverse-here.php
    $api_key = 'Q6UwbGeT3kz6Yky98HUABsnjt_oLw-AaTo04Q1sY8I8';
    $latlng = rawurlencode($lat . ',' . $lng);
    $url = "https://revgeocode.search.hereapi.com/v1/revgeocode?at={$latlng}&lang=vi&apiKey={$api_key}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);

    $location_data = json_decode($response, true);

    // Nếu có kết quả và nằm trong Vinh
    if ($isInVinh && !empty($location_data['items'][0])) {
        $item = $location_data['items'][0];
        $address = $item['address'];

        // Xây dựng thông tin địa chỉ chi tiết
        $ward_name = $address['district'] ?? '';
        $district_name = 'Thành phố Vinh';
        $province_name = 'Nghệ An';
        $address_detail = $address['street'] ?? '';
        if (isset($address['houseNumber'])) {
            $address_detail .= ' ' . $address['houseNumber'];
        }

        echo json_encode([
            'success' => true,
            'coordinates' => [
                'lat' => $lat,
                'lng' => $lng
            ],
            'address' => [
                'full_address' => $address['label'] ?? '',
                'address_detail' => $address_detail,
                'ward_name' => $ward_name,
                'district_name' => $district_name,
                'province_name' => $province_name
            ],
            'within_vinh' => true
        ]);
    } else {
        // Trả về thông báo lỗi nếu không nằm trong Vinh
        echo json_encode([
            'success' => false,
            'message' => 'Vị trí của bạn không nằm trong thành phố Vinh.',
            'coordinates' => [
                'lat' => $lat,
                'lng' => $lng
            ],
            'within_vinh' => false
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
