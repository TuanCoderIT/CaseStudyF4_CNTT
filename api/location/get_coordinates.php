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

// Hàm chuẩn hóa địa chỉ cho Nominatim (OpenStreetMap)
function formatAddress($address)
{
    // Viết thường
    $address = mb_strtolower($address, 'UTF-8');

    // Giữ nguyên dấu tiếng Việt vì Nominatim hỗ trợ Unicode
    // Loại ký tự lạ nhưng giữ chữ có dấu, số, dấu phẩy, dấu chấm
    $address = preg_replace('/[^a-zA-Z0-9\sÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẾỀỆỈỊỌỎỐỒỘƠớởợụưăêôơư,.-]/u', '', $address);

    // Thay từ viết tắt thông dụng (ví dụ: tp. → thành phố)
    $replaceMap = [
        'tp.' => 'thành phố',
        't.p.' => 'thành phố',
        'tp ' => 'thành phố ',
        't.p ' => 'thành phố ',
        'p.' => 'phường',
        'f.' => 'phường',
        'q.' => 'quận',
        'duong' => 'đường',
        'pho' => 'đường'
    ];

    foreach ($replaceMap as $search => $replace) {
        $address = str_replace($search, $replace, $address);
    }

    // Làm sạch dấu cách thừa
    $address = preg_replace('/\s+/', ' ', $address);
    $address = trim($address);
    $address = preg_replace('/\s*,\s*/', ', ', $address);
    $address = preg_replace('/\s*\.\s*/', '. ', $address);
    $address = rtrim($address, ',.');

    return $address;
}

// Gọi HERE API
function queryHereGeocoding($address, $api_key)
{

    $encoded = rawurlencode($address);  // <- dùng cái này thay vì urlencode
    $url = "https://geocode.search.hereapi.com/v1/geocode?q={$encoded}&lang=vi&apiKey=$api_key";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $data['debug_info'] = [
        'http_code' => $http_code,
        'curl_error' => $error,
        'request_url' => $url
    ];

    return $data;
}

// Bắt đầu xử lý
$api_key = 'Q6UwbGeT3kz6Yky98HUABsnjt_oLw-AaTo04Q1sY8I8'; // 🔁 THAY bằng key HERE thật của bạn
$formatted = formatAddress($address);
$data = queryHereGeocoding($formatted, $api_key);
// Tìm kết quả gần giống nhất từ danh sách items
function normalizeForCompare($str)
{
    // Viết thường
    $str = mb_strtolower($str, 'UTF-8');

    // Bỏ dấu tiếng Việt
    $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);

    // Loại bỏ ký tự đặc biệt
    $str = preg_replace('/[^a-z0-9\s]/', '', $str);

    // Chuẩn hóa khoảng trắng
    $str = preg_replace('/\s+/', ' ', $str);

    return trim($str);
}

function getResultTypePriority($resultType)
{
    // Ưu tiên: house > street > locality > district > others
    $priorityMap = [
        'house' => 1,
        'street' => 2, // hoặc 'alley' nếu HERE có
        'alley' => 2,
        'locality' => 3, // phường/xã
        'district' => 4,
        // Thêm các loại khác nếu cần
    ];
    return $priorityMap[strtolower($resultType)] ?? 99;
}

function findMostSimilarItem($items, $formattedInput)
{
    $normalizedInput = normalizeForCompare($formattedInput);
    $candidates = [];

    foreach ($items as $item) {
        $title = $item['title'] ?? '';
        $normalizedTitle = normalizeForCompare($title);
        $distance = levenshtein($normalizedInput, $normalizedTitle);
        $maxLen = max(strlen($normalizedInput), strlen($normalizedTitle));
        $similarity = $maxLen > 0 ? (1 - $distance / $maxLen) * 100 : 0;
        $priority = getResultTypePriority($item['resultType'] ?? '');

        $candidates[] = [
            'item' => $item,
            'score' => $similarity,
            'priority' => $priority,
            'distance' => $distance
        ];
    }

    // Sắp xếp: ưu tiên priority thấp nhất (ưu tiên cao nhất), rồi đến similarity cao nhất
    usort($candidates, function ($a, $b) {
        if ($a['priority'] !== $b['priority']) {
            return $a['priority'] - $b['priority'];
        }
        // Nếu cùng priority, ưu tiên similarity cao hơn
        if ($b['score'] !== $a['score']) {
            return $b['score'] <=> $a['score'];
        }
        // Nếu similarity bằng nhau, ưu tiên distance nhỏ hơn
        return $a['distance'] - $b['distance'];
    });

    return $candidates[0] ?? null;
}

if (!empty($data['items'])) {
    $match = findMostSimilarItem($data['items'], $formatted);
    $item = $match['item'];
    $score = $match['score'];

    echo json_encode([
        'success' => true,
        'coordinates' => [
            'lat' => $item['position']['lat'],
            'lng' => $item['position']['lng'],
            'address' => $item['title'],
            'confidence' => $item['resultType'] ?? 'unknown',
            'match_score' => round($score, 2) . '%'
        ]
    ]);
} else {
    // Không tìm thấy kết quả hoặc có lỗi
    echo json_encode([
        'success' => false,
        'message' => 'Không tìm thấy tọa độ cho địa chỉ này',
        'debug_info' => $data['debug_info'] ?? []
    ]);
}
