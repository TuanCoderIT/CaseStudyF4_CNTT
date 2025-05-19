<?php
// Yêu cầu kết nối cơ sở dữ liệu
require_once '../config/db.php';
header('Content-Type: application/json');

/**
 * Hàm để gọi API lấy dữ liệu địa điểm từ API provinces.open-api.vn
 */
function callLocationAPI($endpoint)
{
    // Kiểm tra nếu CURL được cài đặt
    if (!function_exists('curl_init')) {
        error_log('CURL is not installed on this server');
        return json_encode(['success' => false, 'message' => 'CURL is not installed on this server']);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'User-Agent: Mozilla/5.0'
    ));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        error_log('Curl Error: ' . curl_error($ch));
        return json_encode(['success' => false, 'message' => 'Curl Error: ' . curl_error($ch)]);
    }

    if ($httpCode != 200) {
        error_log('API Error. HTTP Code: ' . $httpCode . ', Response: ' . $response);
        return json_encode(['success' => false, 'message' => 'API Error', 'code' => $httpCode, 'response' => $response]);
    }

    curl_close($ch);
    return $response;
}

// Lấy danh sách tỉnh/thành phố
if (isset($_GET['action']) && $_GET['action'] === 'get_provinces') {
    $response = callLocationAPI('https://provinces.open-api.vn/api/p/');

    // Kiểm tra xem phản hồi có phải là lỗi không
    $responseData = json_decode($response, true);
    if (isset($responseData['success']) && $responseData['success'] === false) {
        // Fallback data khi API không hoạt động
        $fallbackProvinces = [
            ['code' => '01', 'name' => 'Hà Nội'],
            ['code' => '79', 'name' => 'TP. Hồ Chí Minh'],
            ['code' => '48', 'name' => 'Đà Nẵng'],
            ['code' => '92', 'name' => 'Cần Thơ'],
            ['code' => '31', 'name' => 'Hải Phòng'],
            ['code' => '40', 'name' => 'Nghệ An'],
            ['code' => '42', 'name' => 'Hà Tĩnh'],
            ['code' => '49', 'name' => 'Quảng Nam'],
            ['code' => '74', 'name' => 'Bình Dương'],
            ['code' => '75', 'name' => 'Đồng Nai']
        ];
        echo json_encode($fallbackProvinces);
    } else {
        echo $response;
    }
}

// Xử lý lấy danh sách quận/huyện theo tỉnh/thành phố
if (isset($_GET['action']) && $_GET['action'] === 'get_districts') {
    $province_code = isset($_GET['province_code']) ? $_GET['province_code'] : null;

    if ($province_code) {
        $response = callLocationAPI("https://provinces.open-api.vn/api/p/$province_code?depth=2");

        // Kiểm tra xem phản hồi có phải là lỗi không
        $responseData = json_decode($response, true);
        if (isset($responseData['success']) && $responseData['success'] === false) {
            // Fallback data khi API không hoạt động
            $fallbackData = [
                'name' => 'Tỉnh/Thành phố mẫu',
                'code' => $province_code,
                'districts' => [
                    ['name' => 'Quận 1', 'code' => $province_code . '001'],
                    ['name' => 'Quận 2', 'code' => $province_code . '002'],
                    ['name' => 'Quận 3', 'code' => $province_code . '003'],
                    ['name' => 'Quận 4', 'code' => $province_code . '004'],
                    ['name' => 'Quận 5', 'code' => $province_code . '005'],
                ]
            ];
            echo json_encode($fallbackData);
        } else {
            echo $response;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Mã tỉnh/thành phố không hợp lệ']);
    }
}

// Xử lý lấy danh sách phường/xã theo quận/huyện
if (isset($_GET['action']) && $_GET['action'] === 'get_wards') {
    $district_code = isset($_GET['district_code']) ? $_GET['district_code'] : null;

    if ($district_code) {
        $response = callLocationAPI("https://provinces.open-api.vn/api/d/$district_code?depth=2");

        // Kiểm tra xem phản hồi có phải là lỗi không
        $responseData = json_decode($response, true);
        if (isset($responseData['success']) && $responseData['success'] === false) {
            // Fallback data khi API không hoạt động
            $fallbackData = [
                'name' => 'Quận/Huyện mẫu',
                'code' => $district_code,
                'wards' => [
                    ['name' => 'Phường 1', 'code' => $district_code . '001'],
                    ['name' => 'Phường 2', 'code' => $district_code . '002'],
                    ['name' => 'Phường 3', 'code' => $district_code . '003'],
                    ['name' => 'Phường 4', 'code' => $district_code . '004'],
                    ['name' => 'Phường 5', 'code' => $district_code . '005'],
                ]
            ];
            echo json_encode($fallbackData);
        } else {
            echo $response;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Mã quận/huyện không hợp lệ']);
    }
}

// Xử lý lấy district_id từ tên quận/huyện
if (isset($_POST['action']) && $_POST['action'] === 'get_district_id') {
    $district_name = isset($_POST['district_name']) ? $_POST['district_name'] : null;

    if ($district_name) {
        // Tìm district_id dựa trên tên
        $stmt = $conn->prepare("SELECT id FROM districts WHERE name = ? LIMIT 1");
        $stmt->bind_param("s", $district_name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Đã tìm thấy quận/huyện
            $row = $result->fetch_assoc();
            echo json_encode(['success' => true, 'district_id' => $row['id']]);
        } else {
            // Không tìm thấy, thêm mới vào bảng districts
            $stmt = $conn->prepare("INSERT INTO districts (name) VALUES (?)");
            $stmt->bind_param("s", $district_name);

            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                echo json_encode(['success' => true, 'district_id' => $new_id, 'message' => 'Đã tạo mới quận/huyện']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể tạo quận/huyện mới: ' . $conn->error]);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Tên quận/huyện không được cung cấp']);
    }
}
