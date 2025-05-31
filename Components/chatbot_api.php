<?php
// Khởi tạo phiên làm việc nếu chưa có
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kết nối đến CSDL
require_once __DIR__ . '/../config/db.php';

// Khởi tạo mảng lịch sử hội thoại nếu chưa có
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

// Kiểm tra xem yêu cầu có phải là POST request không
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit;
}

// Lấy nội dung gửi đến từ client
$input = json_decode(file_get_contents('php://input'), true);
$message = isset($input['message']) ? trim($input['message']) : '';

if (empty($message)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Message is required']);
    exit;
}

// Thêm tin nhắn hiện tại vào lịch sử
$_SESSION['chat_history'][] = [
    'role' => 'user',
    'message' => $message,
    'timestamp' => time()
];

// Giới hạn lịch sử chat để không quá dài (giữ 10 tin nhắn gần nhất)
if (count($_SESSION['chat_history']) > 20) {
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -20);
}

// API key của Gemini (bạn cần đăng ký và lấy key từ Google AI Studio)
$api_key = 'AIzaSyBMMXlCByExCKwayYYRmziPrNUgOvxOYhM'; // Thay thế bằng API key thực tế của bạn

// Hàm gọi Gemini API
function callGeminiAPI($message, $context = '', $chat_history = [])
{
    global $api_key;

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $api_key;

    // Xây dựng ngữ cảnh hội thoại từ lịch sử
    $conversation_context = '';
    if (!empty($chat_history)) {
        $conversation_context = "Dưới đây là lịch sử trò chuyện gần đây:\n\n";
        foreach ($chat_history as $i => $entry) {
            if ($i >= count($chat_history) - 5) { // Chỉ sử dụng 5 tin nhắn gần nhất
                $role = $entry['role'] === 'user' ? 'Người dùng' : 'Trợ lý';
                $conversation_context .= $role . ": " . $entry['message'] . "\n";
            }
        }
        $conversation_context .= "\n";
    }

    $data = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => "Bạn là trợ lý ảo của website F4 Key Study, một trang web cho phép người dùng tìm kiếm và đăng tin phòng trọ. 
                        
                        Thông tin về website:
                        - Website có các danh mục phòng trọ khác nhau
                        - Người dùng có thể tìm kiếm phòng trọ theo khu vực, giá cả, diện tích, v.v.
                        - Chủ trọ có thể đăng tin cho thuê phòng trọ
                        - nếu tìm không có thì báo là ko có chứ không được báo là đang xử lý.
                        Nếu người dùng hỏi về dữ liệu thực tế như phòng trọ cụ thể, hãy trả lời rằng bạn đang xử lý yêu cầu của họ.
                        - Bạn chỉ thông báo là phòng trọ phục vụ cho kiếm trọ tại thành phố Vinh thôi nhé.
                        - QUAN TRỌNG: KHÔNG ĐƯỢC TRẢ VỀ DANH SÁCH PHÒNG TRỌ CỤ THỂ DƯỚI DẠNG TEXT. Hệ thống sẽ hiển thị các phòng trọ bằng hình ảnh riêng.
                        - Nếu người dùng yêu cầu xem danh sách phòng trọ, hãy nói rằng bạn đang tìm kiếm và sẽ hiển thị kết quả bên dưới.
                        
                        QUY TẮC BỔ SUNG:
                        - lời nói phải ngọt như gái 18 tuổi
                        - Không liệt kê phòng trọ dưới dạng text
                        - Không tạo danh sách số thứ tự về phòng trọ
                        - Không cung cấp chi tiết về phòng cụ thể
                        - Giữ câu trả lời đầy đủ, hay
                        - Không tạo mô tả tưởng tượng
                        - Không mô phỏng giao diện bằng ký tự đặc biệt
                        
                        $conversation_context
                        
                        Dưới đây là thông tin bổ sung (nếu có): 
                        $context
                        
                        Yêu cầu hiện tại của người dùng: $message
                        
                        Hãy trả lời dựa trên toàn bộ ngữ cảnh cuộc trò chuyện, không chỉ dựa vào yêu cầu hiện tại."
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.2,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 1000,
        ]
    ];

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data)
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === FALSE) {
        return "Xin lỗi, tôi không thể kết nối đến dịch vụ AI lúc này. Vui lòng thử lại sau.";
    }

    $responseData = json_decode($response, true);

    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        $aiResponse = $responseData['candidates'][0]['content']['parts'][0]['text'];

        // Lưu câu trả lời vào lịch sử
        global $_SESSION;
        $_SESSION['chat_history'][] = [
            'role' => 'assistant',
            'message' => $aiResponse,
            'timestamp' => time()
        ];

        return $aiResponse;
    } else {
        return "Xin lỗi, tôi không thể xử lý yêu cầu của bạn lúc này.";
    }
}

// Hàm format tiền tệ
function formatCurrency($amount)
{
    return number_format($amount, 0, ',', '.') . ' đ';
}

// Hàm tạo HTML cho kết quả phòng trọ
function generateRoomHTML($rooms)
{
    $html = '<div class="chatbot-room-cards-container">';
    foreach ($rooms as $room) {
        // Xử lý hình ảnh
        $image = !empty($room['images']) ? '/' . $room['images'] : 'https://huythanhhome.com/upload/filemanager/Tin%20t%E1%BB%A9c/th%C3%A1ng%2011/thi%E1%BA%BFt%20k%E1%BA%BF%20nh%C3%A0%20tr%E1%BB%8D%20cao%20t%E1%BA%A7ng/Frame%20219.jpg';

        $html .= '
        <div class="chatbot-room-card">
            <div class="chatbot-room-card-image-container">
                <img src="' . $image . '" class="chatbot-room-card-image" alt="' . htmlspecialchars($room['title']) . '">
                <div class="chatbot-room-card-price-tag">' . formatCurrency($room['price']) . '</div>
            </div>
            <div class="chatbot-room-card-body">
                <h3 class="chatbot-room-card-title">' . htmlspecialchars($room['title']) . '</h3>
                <div class="chatbot-room-card-location"><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($room['address']) . '</div>
                <div class="chatbot-room-card-stats">
                    <span><i class="fas fa-eye"></i> ' . $room['count_view'] . ' lượt xem</span>
                    <span><i class="fas fa-expand"></i> ' . $room['area'] . ' m²</span>
                </div>
                <a href="/room/room_detail.php?id=' . $room['id'] . '" class="chatbot-room-card-link" target="_blank">Xem chi tiết</a>
            </div>
        </div>';
    }
    $html .= '</div>';

    // Thêm CSS cho card phòng trọ với phạm vi giới hạn
    $html .= '
    <style>
    .chatbot-room-cards-container {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-top: 15px;
        width: 100%;
    }
    .chatbot-room-card {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        width: 100%;
        background: white;
        transition: transform 0.2s, box-shadow 0.2s;
        margin-bottom: 10px;
        display: flex;
        flex-direction: column;
    }
    .chatbot-room-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .chatbot-room-card-image-container {
        position: relative;
        height: 160px;
        overflow: hidden;
    }
    .chatbot-room-card-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }
    .chatbot-room-card:hover .chatbot-room-card-image {
        transform: scale(1.05);
    }
    .chatbot-room-card-price-tag {
        position: absolute;
        bottom: 0;
        right: 0;
        background: rgba(78, 115, 223, 0.9);
        color: white;
        padding: 5px 10px;
        font-weight: bold;
        border-top-left-radius: 6px;
    }
    .chatbot-room-card-body {
        padding: 15px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .chatbot-room-card-title {
        margin: 0 0 10px 0;
        font-size: 16px;
        font-weight: bold;
        color: #333;
        line-height: 1.3;
        height: 42px;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    .chatbot-room-card-location {
        font-size: 14px;
        color: #666;
        margin-bottom: 10px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .chatbot-room-card-stats {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        color: #777;
        margin-bottom: 15px;
    }
    .chatbot-room-card-link {
        align-self: flex-end;
        margin-top: auto;
        display: inline-block;
        background: #4e73df;
        color: white;
        padding: 8px 16px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 500;
        transition: background 0.2s;
    }
    .chatbot-room-card-link:hover {
        background: #2e59d9;
        text-decoration: none;
        color: white;
    }
    </style>';

    return $html;
}

// Nhận dạng ý định dựa trên tin nhắn hiện tại và lịch sử
$intent = '';
$lowercaseMessage = strtolower($message);
$contextualSearch = false;

// Kiểm tra xem tin nhắn có phải là câu hỏi phụ thuộc vào ngữ cảnh trước đó không
if (preg_match('/^(cho tôi xem|còn|có|những cái đó|và|hoặc|ở đó|tôi muốn xem|có những|hiển thị|chỉ cho tôi)/i', $lowercaseMessage)) {
    $contextualSearch = true;
}

// Kiểm tra từ chỉ định trong câu
if (
    strpos($lowercaseMessage, 'đó') !== false ||
    strpos($lowercaseMessage, 'như vậy') !== false ||
    strpos($lowercaseMessage, 'tương tự') !== false ||
    strpos($lowercaseMessage, 'như thế') !== false
) {
    $contextualSearch = true;
}

// Nếu là câu hỏi ngữ cảnh, lấy ý định từ tin nhắn trước đó
if ($contextualSearch && count($_SESSION['chat_history']) >= 3) {
    // Tìm tin nhắn người dùng gần nhất trước tin nhắn hiện tại
    $prevUserMessages = array_filter($_SESSION['chat_history'], function ($item) {
        return $item['role'] === 'user';
    });

    if (count($prevUserMessages) >= 2) {
        $prevUserMessages = array_values($prevUserMessages);
        $prevMessage = $prevUserMessages[count($prevUserMessages) - 2]['message'];

        // Phân tích ý định từ tin nhắn trước
        $prevLowercaseMessage = strtolower($prevMessage);

        // Các từ khóa để nhận dạng ý định
        $keywords = [
            // Phòng xem nhiều nhất
            'top phòng' => 'get_top_rooms',
            'phòng xem nhiều nhất' => 'get_top_rooms',
            'phòng phổ biến nhất' => 'get_top_rooms',
            'phòng trọ xem nhiều' => 'get_top_rooms',
            'top 3' => 'get_top_rooms',
            'top 5' => 'get_top_rooms',
            'phòng nhiều người xem' => 'get_top_rooms',
            'được ưa chuộng' => 'get_top_rooms',

            // Phòng giá rẻ
            'phòng giá rẻ' => 'get_cheap_rooms',
            'phòng trọ rẻ' => 'get_cheap_rooms',
            'phòng dưới' => 'get_cheap_rooms',
            'giá thấp' => 'get_cheap_rooms',
            'tiết kiệm' => 'get_cheap_rooms',
            'rẻ nhất' => 'get_cheap_rooms',
            'giá tốt' => 'get_cheap_rooms',

            // Phòng gần trường đại học
            'phòng gần' => 'get_nearby_rooms',
            'gần đại học' => 'get_nearby_university',
            'gần trường' => 'get_nearby_university',
            'đại học vinh' => 'get_nearby_university',
            'trường đại học' => 'get_nearby_university',
            'gần nơi học' => 'get_nearby_university',

            // Phòng mới đăng
            'phòng mới' => 'get_newest_rooms',
            'mới đăng' => 'get_newest_rooms',
            'vừa đăng' => 'get_newest_rooms',
            'mới nhất' => 'get_newest_rooms',

            // Phòng có diện tích lớn
            'phòng rộng' => 'get_large_rooms',
            'diện tích lớn' => 'get_large_rooms',
            'phòng lớn' => 'get_large_rooms',

            // Phòng theo khu vực
            'khu vực' => 'get_rooms_by_district',
            'quận' => 'get_rooms_by_district',
            'huyện' => 'get_rooms_by_district',
        ];

        // Xác định ý định dựa trên từ khóa trong tin nhắn trước
        foreach ($keywords as $keyword => $intentValue) {
            if (strpos($prevLowercaseMessage, $keyword) !== false) {
                $intent = $intentValue;
                break;
            }
        }
    }
}

// Nếu không tìm thấy ý định từ ngữ cảnh, kiểm tra tin nhắn hiện tại
if (empty($intent)) {
    // Các từ khóa để nhận dạng ý định
    $keywords = [
        // Phòng xem nhiều nhất
        'top phòng' => 'get_top_rooms',
        'phòng xem nhiều nhất' => 'get_top_rooms',
        'phòng phổ biến nhất' => 'get_top_rooms',
        'phòng trọ xem nhiều' => 'get_top_rooms',
        'top 3' => 'get_top_rooms',
        'top 5' => 'get_top_rooms',
        'phòng nhiều người xem' => 'get_top_rooms',
        'được ưa chuộng' => 'get_top_rooms',

        // Phòng giá rẻ
        'phòng giá rẻ' => 'get_cheap_rooms',
        'phòng trọ rẻ' => 'get_cheap_rooms',
        'phòng dưới' => 'get_cheap_rooms',
        'giá thấp' => 'get_cheap_rooms',
        'tiết kiệm' => 'get_cheap_rooms',
        'rẻ nhất' => 'get_cheap_rooms',
        'giá tốt' => 'get_cheap_rooms',

        // Phòng gần trường đại học
        'phòng gần' => 'get_nearby_rooms',
        'gần đại học' => 'get_nearby_university',
        'gần trường' => 'get_nearby_university',
        'đại học vinh' => 'get_nearby_university',
        'trường đại học' => 'get_nearby_university',
        'gần nơi học' => 'get_nearby_university',

        // Phòng mới đăng
        'phòng mới' => 'get_newest_rooms',
        'mới đăng' => 'get_newest_rooms',
        'vừa đăng' => 'get_newest_rooms',
        'mới nhất' => 'get_newest_rooms',

        // Phòng có diện tích lớn
        'phòng rộng' => 'get_large_rooms',
        'diện tích lớn' => 'get_large_rooms',
        'phòng lớn' => 'get_large_rooms',

        // Phòng theo khu vực
        'khu vực' => 'get_rooms_by_district',
        'quận' => 'get_rooms_by_district',
        'huyện' => 'get_rooms_by_district',
    ];

    // Xác định ý định dựa trên từ khóa
    foreach ($keywords as $keyword => $intentValue) {
        if (strpos($lowercaseMessage, $keyword) !== false) {
            $intent = $intentValue;
            break;
        }
    }
}

// Phân tích thêm các tham số từ câu hỏi
$params = [];

// Phân tích giá cả
if (preg_match('/(\d+(\.\d+)?)\s*(?:triệu|tr|trieu)/i', $message, $matches)) {
    $params['price'] = floatval($matches[1]) * 1000000;
} elseif (preg_match('/(\d+)\s*(?:nghìn|nghin|k)/i', $message, $matches)) {
    $params['price'] = floatval($matches[1]) * 1000;
} elseif (preg_match('/(\d+)\s*(?:đồng|dong|vnd|đ|d)/i', $message, $matches)) {
    $params['price'] = floatval($matches[1]);
}

// Phân tích diện tích
if (preg_match('/(\d+(\.\d+)?)\s*(?:m2|m²|mét vuông|met vuong)/i', $message, $matches)) {
    $params['area'] = floatval($matches[1]);
}

// Phân tích khu vực/quận huyện
$districts_query = "SELECT id, name FROM districts ORDER BY name";
$districts_result = $conn->query($districts_query);
if ($districts_result) {
    while ($district = $districts_result->fetch_assoc()) {
        if (stripos($message, $district['name']) !== false) {
            $params['district_id'] = $district['id'];
            $params['district_name'] = $district['name'];
            break;
        }
    }
}

// Nếu là câu hỏi ngữ cảnh và không tìm thấy tham số trong câu hỏi hiện tại
// thì tìm trong câu hỏi trước đó
if ($contextualSearch && empty($params) && count($_SESSION['chat_history']) >= 3) {
    $prevUserMessages = array_filter($_SESSION['chat_history'], function ($item) {
        return $item['role'] === 'user';
    });

    if (count($prevUserMessages) >= 2) {
        $prevUserMessages = array_values($prevUserMessages);
        $prevMessage = $prevUserMessages[count($prevUserMessages) - 2]['message'];

        // Phân tích giá cả từ tin nhắn trước
        if (preg_match('/(\d+(\.\d+)?)\s*(?:triệu|tr|trieu)/i', $prevMessage, $matches)) {
            $params['price'] = floatval($matches[1]) * 1000000;
        } elseif (preg_match('/(\d+)\s*(?:nghìn|nghin|k)/i', $prevMessage, $matches)) {
            $params['price'] = floatval($matches[1]) * 1000;
        } elseif (preg_match('/(\d+)\s*(?:đồng|dong|vnd|đ|d)/i', $prevMessage, $matches)) {
            $params['price'] = floatval($matches[1]);
        }

        // Phân tích diện tích từ tin nhắn trước
        if (preg_match('/(\d+(\.\d+)?)\s*(?:m2|m²|mét vuông|met vuong)/i', $prevMessage, $matches)) {
            $params['area'] = floatval($matches[1]);
        }

        // Phân tích khu vực/quận huyện từ tin nhắn trước
        $districts_query = "SELECT id, name FROM districts ORDER BY name";
        $districts_result = $conn->query($districts_query);
        if ($districts_result) {
            while ($district = $districts_result->fetch_assoc()) {
                if (stripos($prevMessage, $district['name']) !== false) {
                    $params['district_id'] = $district['id'];
                    $params['district_name'] = $district['name'];
                    break;
                }
            }
        }
    }
}

// Khởi tạo biến chứa dữ liệu bổ sung
$context = '';
$htmlResponse = '';

// Function to format date
function formatDate($datetime)
{
    $date = new DateTime($datetime);
    return $date->format('d/m/Y');
}

// Xử lý các ý định cụ thể
switch ($intent) {
    case 'get_top_rooms':
        // Xác định số lượng phòng trọ cần lấy (mặc định là 3)
        $limit = 3;
        if (strpos($lowercaseMessage, 'top 5') !== false) {
            $limit = 5;
        }

        // Truy vấn cơ sở dữ liệu để lấy top phòng trọ xem nhiều nhất
        $query = "SELECT m.*, d.name as district_name 
                 FROM motel m 
                 LEFT JOIN districts d ON m.district_id = d.id 
                 WHERE m.approve = 1 AND m.isExist = 1 
                 ORDER BY m.count_view DESC 
                 LIMIT ?";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $topRooms = $result->fetch_all(MYSQLI_ASSOC);

        if (count($topRooms) > 0) {
            $htmlResponse = addChatbotHeader();
            $htmlResponse .= '<p>Đây là top ' . $limit . ' phòng trọ được xem nhiều nhất:</p>';
            $htmlResponse .= generateRoomHTML($topRooms);

            // Tạo ngữ cảnh cho Gemini
            $roomInfo = [];
            foreach ($topRooms as $room) {
                $roomInfo[] = $room['title'] . ' - ' . formatCurrency($room['price']) . ' - ' . $room['count_view'] . ' lượt xem';
            }
            $context = "Top " . $limit . " phòng trọ xem nhiều nhất: " . implode("; ", $roomInfo);
        } else {
            $htmlResponse = addChatbotHeader();
            $htmlResponse .= '<p>Hiện tại chưa có thông tin về phòng trọ nào.</p>';
        }
        break;

    case 'get_cheap_rooms':
        // Xác định mức giá tối đa từ tin nhắn (mặc định là 2 triệu)
        $maxPrice = 2000000; // 2 triệu đồng

        if (isset($params['price'])) {
            $maxPrice = $params['price'];
        } else {
            // Tìm kiếm số trong tin nhắn
            preg_match('/\d+(\.\d+)?/', $message, $matches);
            if (!empty($matches[0])) {
                $extractedNumber = floatval($matches[0]);
                // Kiểm tra xem số đó có phải là đơn vị triệu không
                if ($extractedNumber < 100) { // Giả sử nếu số nhỏ hơn 100 thì đó là đơn vị triệu
                    $maxPrice = $extractedNumber * 1000000;
                } else {
                    $maxPrice = $extractedNumber;
                }
            }
        }

        // Truy vấn cơ sở dữ liệu để lấy phòng trọ giá rẻ
        $query = "SELECT m.*, d.name as district_name 
                 FROM motel m 
                 LEFT JOIN districts d ON m.district_id = d.id 
                 WHERE m.approve = 1 AND m.isExist = 1 AND m.price <= ?
                 ORDER BY m.price ASC 
                 LIMIT 5";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $maxPrice);
        $stmt->execute();
        $result = $stmt->get_result();
        $cheapRooms = $result->fetch_all(MYSQLI_ASSOC);

        if (count($cheapRooms) > 0) {
            $formattedPrice = formatCurrency($maxPrice);
            $htmlResponse = '<p>Đây là những phòng trọ có giá dưới ' . $formattedPrice . ':</p>';
            $htmlResponse .= generateRoomHTML($cheapRooms);

            // Tạo ngữ cảnh cho Gemini
            $roomInfo = [];
            foreach ($cheapRooms as $room) {
                $roomInfo[] = $room['title'] . ' - ' . formatCurrency($room['price']);
            }
            $context = "Phòng trọ giá dưới " . $formattedPrice . ": " . implode("; ", $roomInfo);
        } else {
            $htmlResponse = '<p>Hiện tại không có phòng trọ nào có giá dưới ' . formatCurrency($maxPrice) . '.</p>';
        }
        break;

    case 'get_nearby_university':
        // Sử dụng hàm haversine để tính khoảng cách từ trọ đến Đại học Vinh
        require_once __DIR__ . '../utils/haversine.php';

        // Sử dụng tọa độ của Đại học Vinh từ file haversine.php
        $uniLat = uniLatVinh; // 18.65782
        $uniLng = unitLngVinh; // 105.69636

        // Truy vấn tất cả phòng trọ có tọa độ
        $query = "SELECT m.*, d.name as district_name 
                 FROM motel m 
                 LEFT JOIN districts d ON m.district_id = d.id 
                 WHERE m.approve = 1 AND m.isExist = 1 AND m.latlng IS NOT NULL AND m.latlng != ''
                 ORDER BY m.created_at DESC";

        $result = $conn->query($query);
        $allRooms = $result->fetch_all(MYSQLI_ASSOC);

        // Sử dụng hàm từ haversine.php để tìm phòng trọ gần Đại học Vinh
        $nearbyRooms = handleGetRoomByIP($allRooms, $uniLat, $uniLng);

        // Giới hạn số lượng phòng hiển thị
        $nearbyRooms = array_slice($nearbyRooms, 0, 5);

        if (count($nearbyRooms) > 0) {
            $htmlResponse = addChatbotHeader();
            $htmlResponse .= '<p>Đây là những phòng trọ gần Đại học Vinh (trong bán kính 3km):</p>';
            $htmlResponse .= generateRoomHTML($nearbyRooms);

            // Tạo ngữ cảnh cho Gemini
            $roomInfo = [];
            foreach ($nearbyRooms as $room) {
                $roomInfo[] = $room['title'] . ' - ' . formatCurrency($room['price']) . ' - ' . $room['address'] . ' (cách ' . $room['distance'] . ' km)';
            }
            $context = "Phòng trọ gần Đại học Vinh: " . implode("; ", $roomInfo);
        } else {
            $htmlResponse = addChatbotHeader();
            $htmlResponse .= '<p>Hiện tại không có thông tin về phòng trọ nào trong bán kính 3km quanh Đại học Vinh.</p>';

            // Hiển thị các phòng có trong cơ sở dữ liệu nếu không tìm thấy phòng gần
            $query = "SELECT m.*, d.name as district_name 
                     FROM motel m 
                     LEFT JOIN districts d ON m.district_id = d.id 
                     WHERE m.approve = 1 AND m.isExist = 1
                     ORDER BY m.created_at DESC 
                     LIMIT 3";

            $result = $conn->query($query);
            if ($result && $result->num_rows > 0) {
                $otherRooms = $result->fetch_all(MYSQLI_ASSOC);
                $htmlResponse .= '<p>Bạn có thể xem các phòng trọ khác:</p>';
                $htmlResponse .= generateRoomHTML($otherRooms);
            }
        }
        break;

    case 'get_newest_rooms':
        // Truy vấn cơ sở dữ liệu để lấy phòng trọ mới nhất
        $query = "SELECT m.*, d.name as district_name 
                 FROM motel m 
                 LEFT JOIN districts d ON m.district_id = d.id 
                 WHERE m.approve = 1 AND m.isExist = 1 
                 ORDER BY m.created_at DESC 
                 LIMIT 5";

        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        $newestRooms = $result->fetch_all(MYSQLI_ASSOC);

        if (count($newestRooms) > 0) {
            $htmlResponse = '<p>Đây là 5 phòng trọ mới nhất vừa được đăng:</p>';
            $htmlResponse .= generateRoomHTML($newestRooms);

            // Tạo ngữ cảnh cho Gemini
            $roomInfo = [];
            foreach ($newestRooms as $room) {
                $roomInfo[] = $room['title'] . ' - ' . formatCurrency($room['price']) . ' - ' . formatDate($room['created_at']);
            }
            $context = "Phòng trọ mới nhất: " . implode("; ", $roomInfo);
        } else {
            $htmlResponse = '<p>Hiện tại chưa có thông tin về phòng trọ mới.</p>';
        }
        break;

    case 'get_large_rooms':
        // Xác định diện tích tối thiểu (mặc định là 30m2)
        $minArea = isset($params['area']) ? $params['area'] : 30;

        // Truy vấn cơ sở dữ liệu để lấy phòng trọ diện tích lớn
        $query = "SELECT m.*, d.name as district_name 
                 FROM motel m 
                 LEFT JOIN districts d ON m.district_id = d.id 
                 WHERE m.approve = 1 AND m.isExist = 1 AND m.area >= ?
                 ORDER BY m.area DESC 
                 LIMIT 5";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $minArea);
        $stmt->execute();
        $result = $stmt->get_result();
        $largeRooms = $result->fetch_all(MYSQLI_ASSOC);

        if (count($largeRooms) > 0) {
            $htmlResponse = '<p>Đây là những phòng trọ có diện tích từ ' . $minArea . 'm² trở lên:</p>';
            $htmlResponse .= generateRoomHTML($largeRooms);

            // Tạo ngữ cảnh cho Gemini
            $roomInfo = [];
            foreach ($largeRooms as $room) {
                $roomInfo[] = $room['title'] . ' - ' . $room['area'] . 'm² - ' . formatCurrency($room['price']);
            }
            $context = "Phòng trọ diện tích lớn từ " . $minArea . "m²: " . implode("; ", $roomInfo);
        } else {
            $htmlResponse = '<p>Hiện tại không có phòng trọ nào có diện tích từ ' . $minArea . 'm² trở lên.</p>';
        }
        break;

    case 'get_rooms_by_district':
        // Xác định quận/huyện
        if (isset($params['district_id'])) {
            $district_id = $params['district_id'];
            $district_name = $params['district_name'];

            // Truy vấn cơ sở dữ liệu để lấy phòng trọ theo quận/huyện
            $query = "SELECT m.*, d.name as district_name 
                     FROM motel m 
                     LEFT JOIN districts d ON m.district_id = d.id 
                     WHERE m.approve = 1 AND m.isExist = 1 AND m.district_id = ?
                     ORDER BY m.created_at DESC 
                     LIMIT 5";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $district_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $districtRooms = $result->fetch_all(MYSQLI_ASSOC);

            if (count($districtRooms) > 0) {
                $htmlResponse = '<p>Đây là những phòng trọ tại khu vực ' . $district_name . ':</p>';
                $htmlResponse .= generateRoomHTML($districtRooms);

                // Tạo ngữ cảnh cho Gemini
                $roomInfo = [];
                foreach ($districtRooms as $room) {
                    $roomInfo[] = $room['title'] . ' - ' . formatCurrency($room['price']) . ' - ' . $room['address'];
                }
                $context = "Phòng trọ tại " . $district_name . ": " . implode("; ", $roomInfo);
            } else {
                $htmlResponse = '<p>Hiện tại không có phòng trọ nào tại khu vực ' . $district_name . '.</p>';
            }
        } else {
            // Lấy danh sách các quận/huyện để gợi ý
            $districts_query = "SELECT d.id, d.name, COUNT(m.id) as room_count 
                              FROM districts d 
                              LEFT JOIN motel m ON d.id = m.district_id AND m.approve = 1 AND m.isExist = 1 
                              GROUP BY d.id 
                              HAVING room_count > 0 
                              ORDER BY room_count DESC";
            $districts_result = $conn->query($districts_query);

            if ($districts_result && $districts_result->num_rows > 0) {
                $htmlResponse = '<p>Bạn có thể tìm phòng trọ tại các khu vực sau:</p><ul>';
                $district_list = [];

                while ($district = $districts_result->fetch_assoc()) {
                    $htmlResponse .= '<li><strong>' . $district['name'] . '</strong> (' . $district['room_count'] . ' phòng)</li>';
                    $district_list[] = $district['name'] . ' (' . $district['room_count'] . ' phòng)';
                }

                $htmlResponse .= '</ul><p>Bạn có thể hỏi: "Cho tôi xem phòng trọ ở [tên khu vực]"</p>';
                $context = "Các khu vực có phòng trọ: " . implode(", ", $district_list);
            } else {
                $htmlResponse = '<p>Hiện tại chưa có thông tin về phòng trọ theo khu vực.</p>';
            }
        }
        break;

    default:
        // Kiểm tra xem có nhu cầu phức tạp không
        if (isset($params['price']) || isset($params['area']) || isset($params['district_id'])) {
            // Phân tích nhu cầu phức tạp từ tin nhắn để đề xuất phòng phù hợp
            $conditions = [];
            $order_by = "m.created_at DESC";

            // Xác định điều kiện lọc
            if (isset($params['price'])) {
                $conditions[] = "m.price <= " . $params['price'];
            }

            if (isset($params['area'])) {
                $conditions[] = "m.area >= " . $params['area'];
            }

            if (isset($params['district_id'])) {
                $conditions[] = "m.district_id = " . $params['district_id'];
            }

            // Xác định cách sắp xếp
            if (strpos($lowercaseMessage, 'rẻ nhất') !== false || strpos($lowercaseMessage, 'giá thấp') !== false) {
                $order_by = "m.price ASC";
            } elseif (strpos($lowercaseMessage, 'mới nhất') !== false) {
                $order_by = "m.created_at DESC";
            } elseif (strpos($lowercaseMessage, 'xem nhiều') !== false || strpos($lowercaseMessage, 'phổ biến') !== false) {
                $order_by = "m.count_view DESC";
            }

            // Tạo điều kiện WHERE
            $where_clause = "m.approve = 1 AND m.isExist = 1";
            if (!empty($conditions)) {
                $where_clause .= " AND " . implode(" AND ", $conditions);
            }

            // Truy vấn cơ sở dữ liệu
            $query = "SELECT m.*, d.name as district_name 
                     FROM motel m 
                     LEFT JOIN districts d ON m.district_id = d.id 
                     WHERE $where_clause
                     ORDER BY $order_by 
                     LIMIT 5";

            $result = $conn->query($query);

            if ($result && $result->num_rows > 0) {
                $recommendedRooms = $result->fetch_all(MYSQLI_ASSOC);

                $criteria = [];
                if (isset($params['price'])) {
                    $criteria[] = "giá dưới " . formatCurrency($params['price']);
                }
                if (isset($params['area'])) {
                    $criteria[] = "diện tích từ " . $params['area'] . "m² trở lên";
                }
                if (isset($params['district_name'])) {
                    $criteria[] = "tại khu vực " . $params['district_name'];
                }

                $criteria_text = !empty($criteria) ? implode(", ", $criteria) : "phù hợp với nhu cầu của bạn";

                $htmlResponse = '<p>Đây là những phòng trọ ' . $criteria_text . ':</p>';
                $htmlResponse .= generateRoomHTML($recommendedRooms);

                // Tạo ngữ cảnh cho Gemini
                $roomInfo = [];
                foreach ($recommendedRooms as $room) {
                    $roomInfo[] = $room['title'] . ' - ' . formatCurrency($room['price']) . ' - ' . $room['area'] . 'm² - ' . $room['address'];
                }
                $context = "Phòng trọ $criteria_text: " . implode("; ", $roomInfo);
            } else {
                $htmlResponse = '<p>Hiện tại không tìm thấy phòng trọ nào phù hợp với yêu cầu của bạn.</p>';

                // Đề xuất thay thế
                $htmlResponse .= '<p>Bạn có thể thử tìm với các tiêu chí khác:</p>';
                $htmlResponse .= '<ul>';
                $htmlResponse .= '<li><a href="#" onclick="document.getElementById(\'chatInput\').value=\'Cho tôi xem phòng trọ mới nhất\'; return false;">Phòng trọ mới nhất</a></li>';
                $htmlResponse .= '<li><a href="#" onclick="document.getElementById(\'chatInput\').value=\'Phòng trọ xem nhiều nhất\'; return false;">Phòng trọ xem nhiều nhất</a></li>';
                $htmlResponse .= '<li><a href="#" onclick="document.getElementById(\'chatInput\').value=\'Phòng trọ rẻ nhất\'; return false;">Phòng trọ rẻ nhất</a></li>';
                $htmlResponse .= '</ul>';
            }
        } else {
            // Không có ý định cụ thể, sử dụng Gemini API để xử lý
            $aiResponse = callGeminiAPI($message, '', $_SESSION['chat_history']);
            $htmlResponse = addChatbotHeader();
            $htmlResponse .= '<p>' . nl2br(htmlspecialchars($aiResponse)) . '</p>';

            // Thêm gợi ý tìm kiếm
            $htmlResponse .= '<p>Bạn có thể hỏi tôi về các phòng trọ:</p>';
            $htmlResponse .= '<div class="chatbot-suggestions">';
            $htmlResponse .= '<a href="#" class="suggestion-item" onclick="document.getElementById(\'chatInput\').value=\'Top 3 phòng trọ xem nhiều nhất\'; return false;">Top 3 phòng xem nhiều</a>';
            $htmlResponse .= '<a href="#" class="suggestion-item" onclick="document.getElementById(\'chatInput\').value=\'Phòng trọ dưới 2 triệu\'; return false;">Phòng dưới 2 triệu</a>';
            $htmlResponse .= '<a href="#" class="suggestion-item" onclick="document.getElementById(\'chatInput\').value=\'Phòng trọ gần Đại học Vinh\'; return false;">Phòng gần ĐH Vinh</a>';
            $htmlResponse .= '<a href="#" class="suggestion-item" onclick="document.getElementById(\'chatInput\').value=\'Phòng trọ mới nhất\'; return false;">Phòng mới nhất</a>';
            $htmlResponse .= '</div>';

            // Thêm CSS cho các gợi ý
            $htmlResponse .= '<style>
                .chatbot-suggestions {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 8px;
                    margin-top: 10px;
                }
                .suggestion-item {
                    background-color: #e9f0ff;
                    color: #4e73df;
                    padding: 6px 12px;
                    border-radius: 16px;
                    font-size: 12px;
                    text-decoration: none;
                    transition: all 0.2s;
                }
                .suggestion-item:hover {
                    background-color: #4e73df;
                    color: white;
                }
            </style>';
        }
        break;
}

// Nếu có ngữ cảnh và ý định cụ thể, gọi Gemini API với ngữ cảnh
if (!empty($context) && $intent !== '') {
    $aiResponse = callGeminiAPI($message, $context, $_SESSION['chat_history']);
    $htmlResponse .= '<p class="mt-3"><strong>Thông tin thêm:</strong> ' . nl2br(htmlspecialchars($aiResponse)) . '</p>';
}

// Trả về kết quả
header('Content-Type: application/json');
echo json_encode(['response' => $htmlResponse]);

// Hàm thêm header với logo cho chatbot
function addChatbotHeader()
{
    $header = '
    <div class="chatbot-header">
        <div class="chatbot-logo">
            <svg width="40" height="40" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="headerGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#4e73df" />
                        <stop offset="100%" stop-color="#224abe" />
                    </linearGradient>
                </defs>
                <rect width="200" height="200" rx="25" fill="url(#headerGradient)"/>
                <text x="48" y="125" font-family="Arial, sans-serif" font-size="80" font-weight="bold" fill="white">F4</text>
                <text x="35" y="155" font-family="Arial, sans-serif" font-size="28" font-weight="bold" fill="white">CNTT</text>
            </svg>
        </div>
        <div class="chatbot-title">Trợ lý tìm kiếm phòng trọ</div>
    </div>
    <style>
        .chatbot-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #f8f9fc, #eaecf4);
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .chatbot-logo {
            margin-right: 10px;
        }
        .chatbot-title {
            font-weight: bold;
            font-size: 16px;
            color: #4e73df;
        }
    </style>
    ';
    return $header;
}
