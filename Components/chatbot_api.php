<?php
// Khởi tạo phiên làm việc nếu chưa có
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kết nối đến CSDL
require_once '../config/db.php';

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
                        - Nếu người dùng hỏi về dữ liệu thực tế như phòng trọ cụ thể thì phải lấy ra từ cơ sở dữ liệu.
                        - Ưu tiên lấy dữ liệu từ cơ sở dữ liệu và không tạo mô tả tưởng tượng.
                        - Nếu không có dữ liệu trong cơ sở dữ liệu, hãy thông báo rằng không có phòng trọ nào phù hợp với yêu cầu.
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

    // Bắt lỗi khi gọi API
    $maxRetries = 2; // Số lần thử lại tối đa
    $retryCount = 0;
    $backoffTime = 500; // Thời gian chờ giữa các lần thử lại (ms)

    while ($retryCount <= $maxRetries) {
        try {
            if ($retryCount > 0) {
                // Log và chờ trước khi thử lại
                error_log("Chatbot Gemini API: Retry attempt $retryCount");
                usleep($backoffTime * 1000); // Chuyển đổi milliseconds thành microseconds
                $backoffTime *= 2; // Tăng thời gian chờ theo cấp số nhân
            }

            $response = file_get_contents($url, false, $context);

            if ($response === FALSE) {
                error_log("Chatbot Gemini API Error: Unable to connect to API (Attempt " . ($retryCount + 1) . ")");
                if ($retryCount >= $maxRetries) {
                    return "Xin lỗi, tôi không thể kết nối đến dịch vụ AI lúc này. Vui lòng thử lại sau.";
                }
                $retryCount++;
                continue;
            }

            $responseData = json_decode($response, true);

            // Ghi log lỗi nếu phản hồi không như mong đợi
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Chatbot Gemini API Error: Invalid JSON response: " . json_last_error_msg());
                if ($retryCount >= $maxRetries) {
                    return "Xin lỗi, có lỗi khi xử lý phản hồi từ dịch vụ AI. Vui lòng thử lại sau.";
                }
                $retryCount++;
                continue;
            }

            // Kiểm tra lỗi từ API Gemini
            if (isset($responseData['error'])) {
                $errorCode = $responseData['error']['code'] ?? 0;
                $errorMessage = $responseData['error']['message'] ?? "Lỗi không xác định";

                error_log("Chatbot Gemini API Error: " . json_encode($responseData['error']));

                // Xử lý theo mã lỗi cụ thể
                if ($errorCode == 429) {
                    // Lỗi Rate Limit
                    if ($retryCount < $maxRetries) {
                        $retryCount++;
                        // Tăng thời gian chờ lên khi gặp rate limit
                        $backoffTime *= 2;
                        continue;
                    }
                    return "Xin lỗi, hệ thống đang nhận quá nhiều yêu cầu. Vui lòng thử lại sau ít phút.";
                } else if ($errorCode == 503) {
                    // Lỗi Service Unavailable
                    if ($retryCount < $maxRetries) {
                        $retryCount++;
                        continue;
                    }
                    return "Xin lỗi, dịch vụ AI tạm thời không khả dụng. Vui lòng thử lại sau.";
                } else if (strpos(strtolower($errorMessage), 'quota') !== false) {
                    // Lỗi hạn ngạch API
                    error_log("Chatbot API Quota Error: " . $errorMessage);
                    return "Xin lỗi, hệ thống đã vượt quá hạn ngạch sử dụng API. Vui lòng liên hệ quản trị viên.";
                } else {
                    return "Xin lỗi, có lỗi xảy ra với dịch vụ AI: " . $errorMessage;
                }
            }

            // Nếu không có lỗi, thoát khỏi vòng lặp
            break;
        } catch (Exception $e) {
            error_log("Chatbot Gemini API Exception: " . $e->getMessage() . " (Attempt " . ($retryCount + 1) . ")");
            if ($retryCount >= $maxRetries) {
                return "Xin lỗi, đã xảy ra lỗi khi gọi dịch vụ AI. Vui lòng thử lại sau.";
            }
            $retryCount++;
        }
    }

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
    if (!is_array($rooms) || empty($rooms)) {
        error_log("generateRoomHTML: Invalid or empty rooms data");
        return '<div class="error-message">Không tìm thấy phòng trọ phù hợp.</div>';
    }

    error_log("generateRoomHTML: Starting to generate HTML for " . count($rooms) . " rooms");
    $html = '<div class="chatbot-room-cards-container">';

    foreach ($rooms as $room) {
        // Safety checks for required fields
        if (!isset($room['title']) || !isset($room['id'])) {
            error_log("generateRoomHTML: Room missing required fields");
            continue;
        }

        // Xử lý hình ảnh - ensure we have a default image
        $defaultImage = 'https://huythanhhome.com/upload/filemanager/Tin%20t%E1%BB%A9c/th%C3%A1ng%2011/thi%E1%BA%BFt%20k%E1%BA%BF%20nh%C3%A0%20tr%E1%BB%8D%20cao%20t%E1%BA%A7ng/Frame%20219.jpg';
        $image = (!empty($room['images'])) ? '/' . $room['images'] : $defaultImage;

        // Safe access to fields with defaults
        $title = isset($room['title']) ? htmlspecialchars($room['title']) : 'Phòng trọ';
        $address = isset($room['address']) ? htmlspecialchars($room['address']) : 'Địa chỉ không xác định';
        $price = isset($room['price']) ? formatCurrency($room['price']) : 'Liên hệ';
        $count_view = isset($room['count_view']) ? $room['count_view'] : 0;
        $area = isset($room['area']) ? $room['area'] : 'N/A';

        $html .= '
        <div class="chatbot-room-card">
            <div class="chatbot-room-card-image-container">
                <img src="' . $image . '" class="chatbot-room-card-image" alt="' . $title . '">
                <div class="chatbot-room-card-price-tag">' . $price . '</div>
            </div>
            <div class="chatbot-room-card-body">
                <h3 class="chatbot-room-card-title">' . $title . '</h3>
                <div class="chatbot-room-card-location"><i class="fas fa-map-marker-alt"></i> ' . $address . '</div>
                <div class="chatbot-room-card-stats">
                    <span><i class="fas fa-eye"></i> ' . $count_view . ' lượt xem</span>
                    <span><i class="fas fa-expand"></i> ' . $area . ' m²</span>
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

            // Phòng theo tiện ích
            'có tiện ích' => 'get_rooms_by_utilities',
            'có wifi' => 'get_rooms_by_utilities',
            'có máy lạnh' => 'get_rooms_by_utilities',
            'có điều hòa' => 'get_rooms_by_utilities',
            'có tủ lạnh' => 'get_rooms_by_utilities',
            'có gác lửng' => 'get_rooms_by_utilities',
            'có ban công' => 'get_rooms_by_utilities',
            'có máy giặt' => 'get_rooms_by_utilities',
            'có nóng lạnh' => 'get_rooms_by_utilities',
            'có bảo vệ' => 'get_rooms_by_utilities',
            'tiện nghi' => 'get_rooms_by_utilities',

            // Phòng được yêu thích nhiều
            'yêu thích nhiều' => 'get_most_favorited_rooms',
            'được yêu thích' => 'get_most_favorited_rooms',
            'nhiều người thích' => 'get_most_favorited_rooms',
            'lượt thích cao' => 'get_most_favorited_rooms',
            'phòng hot' => 'get_most_favorited_rooms',

            // Phòng theo danh mục
            'nhà trọ' => 'get_rooms_by_category',
            'chung cư mini' => 'get_rooms_by_category',
            'phòng cho sinh viên' => 'get_rooms_by_category',
            'ký túc xá' => 'get_rooms_by_category',
            'nhà nguyên căn' => 'get_rooms_by_category',
            'phòng đơn' => 'get_rooms_by_category',
            'phòng đôi' => 'get_rooms_by_category',
            'loại phòng' => 'get_rooms_by_category',
            'danh mục' => 'get_rooms_by_category',
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
        'Đại học Vinh' => 'get_nearby_university',
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

        // Phòng theo tiện ích
        'có tiện ích' => 'get_rooms_by_utilities',
        'có wifi' => 'get_rooms_by_utilities',
        'có máy lạnh' => 'get_rooms_by_utilities',
        'có điều hòa' => 'get_rooms_by_utilities',
        'có tủ lạnh' => 'get_rooms_by_utilities',
        'có gác lửng' => 'get_rooms_by_utilities',
        'có ban công' => 'get_rooms_by_utilities',
        'có máy giặt' => 'get_rooms_by_utilities',
        'có nóng lạnh' => 'get_rooms_by_utilities',
        'có bảo vệ' => 'get_rooms_by_utilities',
        'tiện nghi' => 'get_rooms_by_utilities',

        // Phòng được yêu thích nhiều
        'yêu thích nhiều' => 'get_most_favorited_rooms',
        'được yêu thích' => 'get_most_favorited_rooms',
        'nhiều người thích' => 'get_most_favorited_rooms',
        'lượt thích cao' => 'get_most_favorited_rooms',
        'phòng hot' => 'get_most_favorited_rooms',

        // Phòng theo danh mục
        'nhà trọ' => 'get_rooms_by_category',
        'chung cư mini' => 'get_rooms_by_category',
        'phòng cho sinh viên' => 'get_rooms_by_category',
        'ký túc xá' => 'get_rooms_by_category',
        'nhà nguyên căn' => 'get_rooms_by_category',
        'phòng đơn' => 'get_rooms_by_category',
        'phòng đôi' => 'get_rooms_by_category',
        'loại phòng' => 'get_rooms_by_category',
        'danh mục' => 'get_rooms_by_category',

        // Phòng hiện có sẵn
        'phòng còn trống' => 'get_available_rooms',
        'phòng trống' => 'get_available_rooms',
        'còn phòng' => 'get_available_rooms',
        'phòng đang trống' => 'get_available_rooms',
        'phòng hiện có' => 'get_available_rooms',
        'phòng khả dụng' => 'get_available_rooms',

        // Lịch sử đặt phòng
        'đặt phòng của tôi' => 'get_user_bookings',
        'lịch sử đặt phòng' => 'get_user_bookings',
        'phòng đã đặt' => 'get_user_bookings',
        'phòng tôi đã đặt' => 'get_user_bookings',
        'booking của tôi' => 'get_user_bookings',
        'đặt cọc của tôi' => 'get_user_bookings',

        // Phòng theo thời gian đăng
        'phòng đăng trong tháng' => 'get_rooms_by_date_range',
        'phòng đăng gần đây' => 'get_rooms_by_date_range',
        'phòng mới đăng trong' => 'get_rooms_by_date_range',
        'phòng trong tuần này' => 'get_rooms_by_date_range',
        'phòng trong tháng này' => 'get_rooms_by_date_range',

        // Thông báo của người dùng
        'thông báo của tôi' => 'get_user_notifications',
        'thông báo mới' => 'get_user_notifications',
        'tin nhắn hệ thống' => 'get_user_notifications',
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
                 JOIN districts d ON m.district_id = d.id 
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
                 LIMIT 3";

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
        // Debug log
        error_log("Starting get_nearby_university case");

        // Sử dụng hàm haversine để tính khoảng cách từ trọ đến Đại học Vinh
        try {
            // Define all possible paths to the haversine.php file
            $possiblePaths = [
                '../utils/haversine.php',
                __DIR__ . '/../utils/haversine.php',
                dirname(__DIR__) . '/utils/haversine.php',
                '/Users/huynh04/Dev/phongtro/CaseStudyF4_CNTT/utils/haversine.php', // Absolute path
                $_SERVER['DOCUMENT_ROOT'] . '/utils/haversine.php'
            ];

            $fileFound = false;
            foreach ($possiblePaths as $path) {
                error_log("Checking for haversine.php at path: " . $path);
                if (file_exists($path)) {
                    error_log("Found haversine.php at path: " . $path);
                    require_once $path;
                    $fileFound = true;
                    break;
                }
            }

            if (!$fileFound) {
                error_log("ERROR: haversine.php file not found in any of the checked paths");
                throw new Exception("Haversine.php file not found");
            }

            // Check if the constants and function are defined
            if (!defined('uniLatVinh') || !defined('unitLngVinh')) {
                error_log("ERROR: Haversine constants are not defined correctly");
                throw new Exception("Haversine constants not defined");
            }

            if (!function_exists('handleGetRoomByIP')) {
                error_log("ERROR: handleGetRoomByIP function is not defined");
                throw new Exception("Haversine functions not defined");
            }

            // Check if the constants and function are defined
            if (!defined('uniLatVinh') || !defined('unitLngVinh')) {
                error_log("ERROR: Haversine constants are not defined correctly");
                throw new Exception("Haversine constants not defined");
            }

            if (!function_exists('handleGetRoomByIP')) {
                error_log("ERROR: handleGetRoomByIP function is not defined");
                throw new Exception("Haversine functions not defined");
            }

            // Sử dụng tọa độ của Đại học Vinh từ file haversine.php
            $uniLat = uniLatVinh; // 18.65782
            $uniLng = unitLngVinh; // 105.69636

            error_log("University coordinates: Lat={$uniLat}, Lng={$uniLng}");

            // Truy vấn tất cả phòng trọ có tọa độ
            $query = "SELECT m.*, d.name as district_name 
                     FROM motel m 
                     LEFT JOIN districts d ON m.district_id = d.id 
                     WHERE m.approve = 1 AND m.isExist = 1 AND m.latlng IS NOT NULL AND m.latlng != ''
                     ORDER BY m.created_at DESC";

            error_log("Query for rooms: " . $query);
            $result = $conn->query($query);

            if (!$result) {
                error_log("Database query error: " . $conn->error);
                throw new Exception("Database query failed: " . $conn->error);
            }

            $allRooms = $result->fetch_all(MYSQLI_ASSOC);
            error_log("Found " . count($allRooms) . " rooms with coordinates");

            // Log the first room's coordinates for debugging
            if (count($allRooms) > 0) {
                error_log("First room latlng: " . $allRooms[0]['latlng']);
            }

            // Sử dụng hàm an toàn để tìm phòng trọ gần Đại học Vinh
            $nearbyRooms = safeHandleGetRoomByIP($allRooms, $uniLat, $uniLng);
            error_log("Found " . count($nearbyRooms) . " rooms near university");
        } catch (Exception $e) {
            error_log("Error in get_nearby_university: " . $e->getMessage());
            $nearbyRooms = [];
        }

        // Giới hạn số lượng phòng hiển thị
        $nearbyRooms = array_slice($nearbyRooms, 0, 3);
        error_log("Limited to " . count($nearbyRooms) . " rooms for display");

        if (count($nearbyRooms) > 0) {
            error_log("Generating HTML for nearby rooms");
            $htmlResponse = addChatbotHeader();
            $htmlResponse .= '<p>Đây là những phòng trọ gần Đại học Vinh (trong bán kính 3km):</p>';

            // Debug the generateRoomHTML function
            error_log("Before calling generateRoomHTML");
            try {
                $roomHTML = generateRoomHTML($nearbyRooms);
                error_log("Generated room HTML length: " . strlen($roomHTML));
                $htmlResponse .= $roomHTML;
            } catch (Exception $e) {
                error_log("Error generating room HTML: " . $e->getMessage());
                $htmlResponse .= '<p>Error generating room list: ' . $e->getMessage() . '</p>';
            }

            // Tạo ngữ cảnh cho Gemini
            $roomInfo = [];
            foreach ($nearbyRooms as $room) {
                $roomInfo[] = $room['title'] . ' - ' . formatCurrency($room['price']) . ' - ' . $room['address'] . ' (cách ' . $room['distance'] . ' km)';
            }
            $context = "Phòng trọ gần Đại học Vinh: " . implode("; ", $roomInfo);
            error_log("Generated context: " . $context);
        } else {
            error_log("No nearby rooms found, showing fallback options");
            $htmlResponse = addChatbotHeader();
            $htmlResponse .= '<p>Hiện tại không có thông tin về phòng trọ nào trong bán kính 3km quanh Đại học Vinh.</p>';

            // Hiển thị các phòng có trong cơ sở dữ liệu nếu không tìm thấy phòng gần
            $query = "SELECT m.*, d.name as district_name 
                     FROM motel m 
                     LEFT JOIN districts d ON m.district_id = d.id 
                     WHERE m.approve = 1 AND m.isExist = 1
                     ORDER BY m.created_at DESC 
                     LIMIT 3";

            error_log("Fallback query: " . $query);
            try {
                $result = $conn->query($query);

                if ($result && $result->num_rows > 0) {
                    $otherRooms = $result->fetch_all(MYSQLI_ASSOC);
                    error_log("Found " . count($otherRooms) . " other rooms to display");
                    $htmlResponse .= '<p>Bạn có thể xem các phòng trọ khác:</p>';
                    try {
                        $roomHTML = generateRoomHTML($otherRooms);
                        error_log("Generated fallback room HTML length: " . strlen($roomHTML));
                        $htmlResponse .= $roomHTML;
                    } catch (Exception $e) {
                        error_log("Error generating fallback room HTML: " . $e->getMessage());
                        $htmlResponse .= '<p>Error generating room list: ' . $e->getMessage() . '</p>';
                    }

                    // Create context for Gemini with fallback rooms
                    $roomInfo = [];
                    foreach ($otherRooms as $room) {
                        $roomInfo[] = $room['title'] . ' - ' . formatCurrency($room['price']) . ' - ' . $room['address'];
                    }
                    $context = "Không tìm thấy phòng trọ gần Đại học Vinh. Các phòng trọ khác: " . implode("; ", $roomInfo);
                } else {
                    error_log("No other rooms found either");
                    $context = "Không có phòng trọ nào gần Đại học Vinh và không tìm thấy phòng trọ nào khác.";
                    $htmlResponse .= '<p><strong>Không có phòng trọ nào hiện đang có sẵn.</strong> Vui lòng quay lại sau.</p>';
                }
            } catch (Exception $e) {
                error_log("Error in fallback query: " . $e->getMessage());
                $htmlResponse .= '<p>Đã xảy ra lỗi khi tìm kiếm phòng trọ. Vui lòng thử lại sau.</p>';
                $context = "Đã xảy ra lỗi khi tìm kiếm phòng trọ: " . $e->getMessage();
            }
        }

        // Debug the final HTML response
        error_log("Final HTML response length: " . strlen($htmlResponse ?? ''));
        if (empty($htmlResponse)) {
            error_log("WARNING: HTML response is empty");
        }
        break;

    case 'get_newest_rooms':
        // Truy vấn cơ sở dữ liệu để lấy phòng trọ mới nhất
        $query = "SELECT m.*, d.name as district_name 
                 FROM motel m 
                 LEFT JOIN districts d ON m.district_id = d.id 
                 WHERE m.approve = 1 AND m.isExist = 1 
                 ORDER BY m.created_at DESC 
                 LIMIT 3";

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
                 LIMIT 3";

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
                     LIMIT 3";

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

    case 'get_rooms_by_utilities':
        // Xác định tiện ích cần tìm
        $utility = '';
        $utilityNames = [
            'wifi' => 'wifi',
            'máy lạnh' => 'máy lạnh',
            'điều hòa' => 'điều hòa',
            'tủ lạnh' => 'tủ lạnh',
            'gác lửng' => 'gác lửng',
            'ban công' => 'ban công',
            'máy giặt' => 'máy giặt',
            'nóng lạnh' => 'nóng lạnh',
            'bảo vệ' => 'bảo vệ'
        ];

        // Tìm tiện ích từ tin nhắn hiện tại
        foreach ($utilityNames as $key => $value) {
            if (strpos($lowercaseMessage, $key) !== false) {
                $utility = $value;
                break;
            }
        }

        // Nếu không tìm thấy tiện ích cụ thể, hiển thị danh sách các tiện ích phổ biến
        if (empty($utility)) {
            $htmlResponse = '<p>Bạn có thể tìm phòng trọ với các tiện ích sau:</p>';
            $htmlResponse .= '<ul>';
            foreach ($utilityNames as $name) {
                $htmlResponse .= '<li><a href="#" onclick="document.getElementById(\'chatInput\').value=\'Cho tôi xem phòng có ' . $name . '\'; return false;">Phòng có ' . $name . '</a></li>';
            }
            $htmlResponse .= '</ul>';
            $htmlResponse .= '<p>Hãy chọn một tiện ích cụ thể để tìm kiếm phòng phù hợp.</p>';

            $context = "Danh sách các tiện ích phổ biến: " . implode(", ", $utilityNames);
        } else {
            // Tìm phòng có tiện ích được chọn
            $query = "SELECT m.*, d.name as district_name 
                     FROM motel m 
                     LEFT JOIN districts d ON m.district_id = d.id 
                     WHERE m.approve = 1 AND m.isExist = 1 AND m.utilities LIKE ?
                     ORDER BY m.created_at DESC 
                     LIMIT 3";

            $searchPattern = '%' . $utility . '%';
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $searchPattern);
            $stmt->execute();
            $result = $stmt->get_result();
            $utilityRooms = $result->fetch_all(MYSQLI_ASSOC);

            if (count($utilityRooms) > 0) {
                $htmlResponse = '<p>Đây là những phòng trọ có tiện ích <strong>' . $utility . '</strong>:</p>';
                $htmlResponse .= generateRoomHTML($utilityRooms);

                // Tạo ngữ cảnh cho Gemini
                $roomInfo = [];
                foreach ($utilityRooms as $room) {
                    $roomInfo[] = $room['title'] . ' - ' . formatCurrency($room['price']) . ' - ' . $room['area'] . 'm²';
                }
                $context = "Phòng trọ có tiện ích " . $utility . ": " . implode("; ", $roomInfo);
            } else {
                $htmlResponse = '<p>Hiện tại không có phòng trọ nào có tiện ích <strong>' . $utility . '</strong>.</p>';
                $htmlResponse .= '<p>Bạn có thể tìm phòng với các tiện ích khác:</p>';
                $htmlResponse .= '<ul>';
                foreach ($utilityNames as $name) {
                    if ($name != $utility) {
                        $htmlResponse .= '<li><a href="#" onclick="document.getElementById(\'chatInput\').value=\'Cho tôi xem phòng có ' . $name . '\'; return false;">Phòng có ' . $name . '</a></li>';
                    }
                }
                $htmlResponse .= '</ul>';
            }
        }
        break;

    case 'get_most_favorited_rooms':
        // Truy vấn cơ sở dữ liệu để lấy phòng trọ có nhiều lượt yêu thích nhất
        $query = "SELECT m.*, d.name as district_name, COUNT(w.id) as favorite_count
                 FROM motel m 
                 LEFT JOIN districts d ON m.district_id = d.id 
                 LEFT JOIN user_wishlist w ON m.id = w.motel_id
                 WHERE m.approve = 1 AND m.isExist = 1
                 GROUP BY m.id
                 ORDER BY favorite_count DESC, m.wishlist DESC
                 LIMIT 3";

        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        $favoriteRooms = $result->fetch_all(MYSQLI_ASSOC);

        if (count($favoriteRooms) > 0) {
            $htmlResponse = '<p>Đây là những phòng trọ được yêu thích nhiều nhất:</p>';
            $htmlResponse .= generateRoomHTML($favoriteRooms);

            // Tạo ngữ cảnh cho Gemini
            $roomInfo = [];
            foreach ($favoriteRooms as $room) {
                $favorite_count = isset($room['favorite_count']) ? $room['favorite_count'] : $room['wishlist'];
                $roomInfo[] = $room['title'] . ' - ' . formatCurrency($room['price']) . ' - ' . $favorite_count . ' lượt yêu thích';
            }
            $context = "Phòng trọ được yêu thích nhiều nhất: " . implode("; ", $roomInfo);
        } else {
            $htmlResponse = '<p>Hiện tại chưa có thông tin về phòng trọ được yêu thích.</p>';
        }
        break;

    case 'get_rooms_by_category':
        // Tìm loại phòng từ tin nhắn
        $category_id = null;
        $category_name = null;

        // Lấy danh sách các danh mục 
        $categories_query = "SELECT id, name FROM categories ORDER BY name";
        $categories_result = $conn->query($categories_query);

        if ($categories_result) {
            $categories = [];
            while ($category = $categories_result->fetch_assoc()) {
                $categories[$category['id']] = $category['name'];
                // Kiểm tra xem tên danh mục có xuất hiện trong tin nhắn không
                if (stripos($lowercaseMessage, strtolower($category['name'])) !== false) {
                    $category_id = $category['id'];
                    $category_name = $category['name'];
                    break;
                }
            }

            // Nếu không tìm thấy danh mục cụ thể
            if ($category_id === null) {
                // Hiển thị danh sách các danh mục có sẵn
                $htmlResponse = '<p>Bạn có thể tìm phòng trọ theo các loại phòng sau:</p>';
                $htmlResponse .= '<ul>';

                // Đếm số lượng phòng trong mỗi danh mục
                $category_count_query = "SELECT c.id, c.name, COUNT(m.id) as room_count 
                                       FROM categories c 
                                       LEFT JOIN motel m ON c.id = m.category_id AND m.approve = 1 AND m.isExist = 1
                                       GROUP BY c.id
                                       HAVING room_count > 0
                                       ORDER BY room_count DESC";

                $category_count_result = $conn->query($category_count_query);

                if ($category_count_result && $category_count_result->num_rows > 0) {
                    while ($cat = $category_count_result->fetch_assoc()) {
                        $htmlResponse .= '<li><a href="#" onclick="document.getElementById(\'chatInput\').value=\'Cho tôi xem phòng loại ' . $cat['name'] . '\'; return false;">' . $cat['name'] . ' (' . $cat['room_count'] . ' phòng)</a></li>';
                    }
                } else {
                    foreach ($categories as $id => $name) {
                        $htmlResponse .= '<li><a href="#" onclick="document.getElementById(\'chatInput\').value=\'Cho tôi xem phòng loại ' . $name . '\'; return false;">' . $name . '</a></li>';
                    }
                }

                $htmlResponse .= '</ul>';
                $htmlResponse .= '<p>Hãy chọn một loại phòng để xem danh sách.</p>';

                $context = "Danh sách các loại phòng: " . implode(", ", $categories);
            } else {
                // Tìm phòng theo danh mục đã chọn
                $query = "SELECT m.*, d.name as district_name 
                         FROM motel m 
                         LEFT JOIN districts d ON m.district_id = d.id 
                         WHERE m.approve = 1 AND m.isExist = 1 AND m.category_id = ?
                         ORDER BY m.created_at DESC 
                         LIMIT 3";

                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $category_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $categoryRooms = $result->fetch_all(MYSQLI_ASSOC);

                if (count($categoryRooms) > 0) {
                    $htmlResponse = '<p>Đây là những phòng trọ thuộc danh mục <strong>' . $category_name . '</strong>:</p>';
                    $htmlResponse .= generateRoomHTML($categoryRooms);

                    // Tạo ngữ cảnh cho Gemini
                    $roomInfo = [];
                    foreach ($categoryRooms as $room) {
                        $roomInfo[] = $room['title'] . ' - ' . formatCurrency($room['price']) . ' - ' . $room['area'] . 'm²';
                    }
                    $context = "Phòng trọ thuộc danh mục " . $category_name . ": " . implode("; ", $roomInfo);
                } else {
                    $htmlResponse = '<p>Hiện tại không có phòng trọ nào thuộc danh mục <strong>' . $category_name . '</strong>.</p>';
                    $htmlResponse .= '<p>Bạn có thể tìm phòng với các danh mục khác:</p>';

                    // Hiển thị các danh mục khác có phòng
                    $category_count_query = "SELECT c.id, c.name, COUNT(m.id) as room_count 
                                           FROM categories c 
                                           LEFT JOIN motel m ON c.id = m.category_id AND m.approve = 1 AND m.isExist = 1
                                           WHERE c.id != ?
                                           GROUP BY c.id
                                           HAVING room_count > 0
                                           ORDER BY room_count DESC";

                    $category_count_stmt = $conn->prepare($category_count_query);
                    $category_count_stmt->bind_param("i", $category_id);
                    $category_count_stmt->execute();
                    $category_count_result = $category_count_stmt->get_result();

                    $htmlResponse .= '<ul>';
                    while ($cat = $category_count_result->fetch_assoc()) {
                        $htmlResponse .= '<li><a href="#" onclick="document.getElementById(\'chatInput\').value=\'Cho tôi xem phòng loại ' . $cat['name'] . '\'; return false;">' . $cat['name'] . ' (' . $cat['room_count'] . ' phòng)</a></li>';
                    }
                    $htmlResponse .= '</ul>';
                }
            }
        } else {
            $htmlResponse = '<p>Hiện tại không thể truy vấn thông tin về loại phòng.</p>';
        }
        break;

    case 'get_available_rooms':
        // Truy vấn cơ sở dữ liệu để lấy các phòng trọ còn trống
        $query = "SELECT m.*, d.name as district_name 
                 FROM motel m 
                 LEFT JOIN districts d ON m.district_id = d.id 
                 WHERE m.approve = 1 AND m.isExist = 1 
                 ORDER BY m.created_at DESC 
                 LIMIT 3";

        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        $availableRooms = $result->fetch_all(MYSQLI_ASSOC);

        if (count($availableRooms) > 0) {
            $htmlResponse = '<p>Đây là những phòng trọ hiện đang còn trống:</p>';
            $htmlResponse .= generateRoomHTML($availableRooms);

            // Tạo ngữ cảnh cho Gemini
            $roomInfo = [];
            foreach ($availableRooms as $room) {
                $roomInfo[] = $room['title'] . ' - ' . formatCurrency($room['price']) . ' - ' . $room['area'] . 'm²';
            }
            $context = "Phòng trọ hiện đang còn trống: " . implode("; ", $roomInfo);
        } else {
            $htmlResponse = '<p>Hiện tại không có phòng trọ nào còn trống.</p>';
        }
        break;

    case 'get_user_bookings':
        // Kiểm tra xem người dùng đã đăng nhập chưa
        if (!isset($_SESSION['user_id'])) {
            $htmlResponse = '<p>Bạn cần <a href="/auth/login.php">đăng nhập</a> để xem lịch sử đặt phòng của mình.</p>';
            $context = "Người dùng cần đăng nhập để xem lịch sử đặt phòng.";
            break;
        }

        $user_id = $_SESSION['user_id'];

        // Truy vấn cơ sở dữ liệu để lấy lịch sử đặt phòng của người dùng
        $query = "SELECT b.*, m.title, m.price, m.area, m.images, m.address, d.name as district_name
                 FROM bookings b
                 JOIN motel m ON b.motel_id = m.id
                 LEFT JOIN districts d ON m.district_id = d.id
                 WHERE b.user_id = ?
                 ORDER BY b.created_at DESC
                 LIMIT 3";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $bookings = $result->fetch_all(MYSQLI_ASSOC);

        if (count($bookings) > 0) {
            $htmlResponse = '<p>Đây là lịch sử đặt phòng của bạn:</p>';

            // Tạo HTML cho lịch sử đặt phòng
            $htmlResponse .= '<div class="chatbot-booking-history">';
            foreach ($bookings as $booking) {
                $status_class = '';
                $status_text = '';

                switch ($booking['status']) {
                    case 'PENDING':
                        $status_class = 'pending';
                        $status_text = 'Đang chờ';
                        break;
                    case 'SUCCESS':
                        $status_class = 'success';
                        $status_text = 'Thành công';
                        break;
                    case 'FAILED':
                        $status_class = 'failed';
                        $status_text = 'Thất bại';
                        break;
                    case 'REFUND_REQUESTED':
                        $status_class = 'refund';
                        $status_text = 'Yêu cầu hoàn tiền';
                        break;
                    case 'RELEASED':
                        $status_class = 'released';
                        $status_text = 'Đã giải ngân';
                        break;
                    case 'REFUNDED':
                        $status_class = 'refunded';
                        $status_text = 'Đã hoàn tiền';
                        break;
                }

                // Lấy hình ảnh phòng
                $image = !empty($booking['images']) ? '/' . $booking['images'] : 'https://huythanhhome.com/upload/filemanager/Tin%20t%E1%BB%A9c/th%C3%A1ng%2011/thi%E1%BA%BFt%20k%E1%BA%BF%20nh%C3%A0%20tr%E1%BB%8D%20cao%20t%E1%BA%A7ng/Frame%20219.jpg';

                $htmlResponse .= '
                <div class="chatbot-booking-item">
                    <div class="chatbot-booking-image">
                        <img src="' . $image . '" alt="' . htmlspecialchars($booking['title']) . '">
                    </div>
                    <div class="chatbot-booking-info">
                        <h4>' . htmlspecialchars($booking['title']) . '</h4>
                        <p>Tiền cọc: ' . formatCurrency($booking['deposit_amount']) . '</p>
                        <p>Ngày đặt: ' . date('d/m/Y', strtotime($booking['created_at'])) . '</p>
                        <span class="chatbot-booking-status ' . $status_class . '">' . $status_text . '</span>
                        <a href="/room/booking_detail.php?id=' . $booking['id'] . '" class="chatbot-booking-link" target="_blank">Chi tiết</a>
                    </div>
                </div>';
            }
            $htmlResponse .= '</div>';

            // Thêm CSS cho lịch sử đặt phòng
            $htmlResponse .= '
            <style>
            .chatbot-booking-history {
                display: flex;
                flex-direction: column;
                gap: 15px;
                margin-top: 15px;
            }
            .chatbot-booking-item {
                display: flex;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                background: white;
            }
            .chatbot-booking-image {
                width: 100px;
                height: 100px;
                flex-shrink: 0;
            }
            .chatbot-booking-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .chatbot-booking-info {
                padding: 10px 15px;
                flex-grow: 1;
                position: relative;
            }
            .chatbot-booking-info h4 {
                margin: 0 0 8px;
                font-size: 14px;
                font-weight: bold;
            }
            .chatbot-booking-info p {
                margin: 4px 0;
                font-size: 13px;
                color: #666;
            }
            .chatbot-booking-status {
                position: absolute;
                top: 10px;
                right: 15px;
                font-size: 12px;
                padding: 3px 8px;
                border-radius: 10px;
                font-weight: 500;
            }
            .chatbot-booking-status.pending { background: #FFF8E1; color: #F57F17; }
            .chatbot-booking-status.success { background: #E8F5E9; color: #2E7D32; }
            .chatbot-booking-status.failed { background: #FFEBEE; color: #C62828; }
            .chatbot-booking-status.refund { background: #F3E5F5; color: #7B1FA2; }
            .chatbot-booking-status.released { background: #E1F5FE; color: #0277BD; }
            .chatbot-booking-status.refunded { background: #E0F2F1; color: #00796B; }
            .chatbot-booking-link {
                display: inline-block;
                margin-top: 8px;
                padding: 4px 12px;
                background: #4e73df;
                color: white;
                border-radius: 4px;
                text-decoration: none;
                font-size: 12px;
            }
            </style>';

            // Tạo ngữ cảnh cho Gemini
            $bookingInfo = [];
            foreach ($bookings as $booking) {
                $bookingInfo[] = $booking['title'] . ' - ' . formatCurrency($booking['deposit_amount']) . ' - ' . $booking['status'];
            }
            $context = "Lịch sử đặt phòng của người dùng: " . implode("; ", $bookingInfo);
        } else {
            $htmlResponse = '<p>Bạn chưa có lịch sử đặt phòng trọ nào.</p>';
            $htmlResponse .= '<p>Bạn có thể tìm và đặt phòng trọ từ <a href="/Home/index.php">trang chủ</a>.</p>';

            $context = "Người dùng chưa có lịch sử đặt phòng nào.";
        }
        break;

    case 'get_rooms_by_date_range':
        // Xác định khoảng thời gian (mặc định là 1 tháng gần đây)
        $days = 30; // Mặc định 30 ngày

        if (strpos($lowercaseMessage, 'tuần') !== false) {
            $days = 7;
        } else if (strpos($lowercaseMessage, 'tháng') !== false) {
            $days = 30;
        } else if (preg_match('/(\d+)\s*(?:ngày|ngay)/', $lowercaseMessage, $matches)) {
            $days = intval($matches[1]);
        }

        // Tính ngày bắt đầu và kết thúc
        $end_date = date('Y-m-d H:i:s');
        $start_date = date('Y-m-d H:i:s', strtotime("-$days days"));

        // Truy vấn cơ sở dữ liệu để lấy các phòng trọ đăng trong khoảng thời gian
        $query = "SELECT m.*, d.name as district_name 
                 FROM motel m 
                 LEFT JOIN districts d ON m.district_id = d.id 
                 WHERE m.approve = 1 AND m.isExist = 1 AND m.created_at BETWEEN ? AND ?
                 ORDER BY m.created_at DESC 
                 LIMIT 3";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        $dateRangeRooms = $result->fetch_all(MYSQLI_ASSOC);

        if (count($dateRangeRooms) > 0) {
            $period = $days == 7 ? 'tuần' : ($days == 30 ? 'tháng' : $days . ' ngày');
            $htmlResponse = '<div class="result-status success"><i class="fas fa-check-circle"></i> Đã tìm thấy ' . count($dateRangeRooms) . ' phòng trọ</div>';
            $htmlResponse .= '<p>Đây là những phòng trọ được đăng trong ' . $period . ' qua:</p>';
            $htmlResponse .= generateRoomHTML($dateRangeRooms);

            // Thêm CSS cho trạng thái kết quả
            $htmlResponse .= '
            <style>
            .result-status {
                padding: 8px 12px;
                border-radius: 4px;
                margin-bottom: 10px;
                font-size: 14px;
                display: flex;
                align-items: center;
            }
            .result-status.success {
                background-color: #e7f7ee;
                color: #1d976c;
            }
            .result-status i {
                margin-right: 8px;
                font-size: 16px;
            }
            </style>';

            // Tạo ngữ cảnh cho Gemini
            $roomInfo = [];
            foreach ($dateRangeRooms as $room) {
                $roomInfo[] = $room['title'] . ' - ' . formatCurrency($room['price']) . ' - ' . formatDate($room['created_at']);
            }
            $context = "Phòng trọ đăng trong $period qua: " . implode("; ", $roomInfo);
        } else {
            $period = $days == 7 ? 'tuần' : ($days == 30 ? 'tháng' : $days . ' ngày');
            $htmlResponse = '<div class="result-status empty"><i class="fas fa-info-circle"></i> Không tìm thấy kết quả</div>';
            $htmlResponse .= '<p>Không có phòng trọ nào được đăng trong ' . $period . ' qua.</p>';
            $htmlResponse .= '<p>Bạn có thể xem <a href="#" onclick="document.getElementById(\'chatInput\').value=\'Cho tôi xem phòng mới đăng\'; return false;">phòng mới đăng gần đây</a> hoặc tìm kiếm với các tiêu chí khác.</p>';

            // Thêm CSS cho trạng thái kết quả
            $htmlResponse .= '
            <style>
            .result-status {
                padding: 8px 12px;
                border-radius: 4px;
                margin-bottom: 10px;
                font-size: 14px;
                display: flex;
                align-items: center;
            }
            .result-status.empty {
                background-color: #f8f9fc;
                color: #5a6268;
            }
            .result-status i {
                margin-right: 8px;
                font-size: 16px;
            }
            </style>';
        }
        break;

    case 'get_user_notifications':
        // Kiểm tra xem người dùng đã đăng nhập chưa
        if (!isset($_SESSION['user_id'])) {
            $htmlResponse = '<p>Bạn cần <a href="/auth/login.php">đăng nhập</a> để xem thông báo của mình.</p>';
            $context = "Người dùng cần đăng nhập để xem thông báo.";
            break;
        }

        $user_id = $_SESSION['user_id'];

        // Truy vấn cơ sở dữ liệu để lấy thông báo của người dùng
        $query = "SELECT * FROM notifications 
                 WHERE user_id = ?
                 ORDER BY created_at DESC 
                 LIMIT 10";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = $result->fetch_all(MYSQLI_ASSOC);

        if (count($notifications) > 0) {
            $htmlResponse = '<p>Đây là thông báo của bạn:</p>';

            // Tạo HTML cho thông báo
            $htmlResponse .= '<div class="chatbot-notification-list">';
            foreach ($notifications as $notification) {
                $read_class = $notification['is_read'] ? 'read' : 'unread';
                $date = date('d/m/Y', strtotime($notification['created_at']));

                $htmlResponse .= '
                <div class="chatbot-notification-item ' . $read_class . '">
                    <div class="chatbot-notification-content">
                        <h4>' . htmlspecialchars($notification['title']) . '</h4>
                        <p>' . htmlspecialchars($notification['message']) . '</p>
                        <span class="chatbot-notification-time">' . $date . '</span>
                    </div>
                </div>';
            }
            $htmlResponse .= '</div>';
            $htmlResponse .= '<p><a href="/room/notifications.php" target="_blank">Xem tất cả thông báo</a></p>';

            // Thêm CSS cho thông báo
            $htmlResponse .= '
            <style>
            .chatbot-notification-list {
                display: flex;
                flex-direction: column;
                gap: 10px;
                margin-top: 15px;
                margin-bottom: 15px;
            }
            .chatbot-notification-item {
                padding: 12px 15px;
                border-radius: 8px;
                border-left: 3px solid #4e73df;
                background: white;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .chatbot-notification-item.unread {
                background: #F8F9FC;
                border-left-color: #4e73df;
            }
            .chatbot-notification-item.read {
                border-left-color: #858796;
                opacity: 0.8;
            }
            .chatbot-notification-content h4 {
                margin: 0 0 5px;
                font-size: 14px;
                color: #4e73df;
            }
            .chatbot-notification-item.read .chatbot-notification-content h4 {
                color: #3a3b45;
            }
            .chatbot-notification-content p {
                margin: 0 0 8px;
                font-size: 13px;
                color: #5a5c69;
                line-height: 1.4;
            }
            .chatbot-notification-time {
                font-size: 11px;
                color: #858796;
                display: block;
                text-align: right;
            }
            </style>';

            // Tạo ngữ cảnh cho Gemini
            $notificationInfo = [];
            foreach ($notifications as $notification) {
                $notificationInfo[] = $notification['title'] . ': ' . substr($notification['message'], 0, 50) . '...';
            }
            $context = "Thông báo của người dùng: " . implode("; ", $notificationInfo);
        } else {
            $htmlResponse = '<p>Bạn chưa có thông báo nào.</p>';
            $context = "Người dùng chưa có thông báo nào.";
        }
        break;
}

// Hàm để thêm tiêu đề cho phần chatbot
function addChatbotHeader()
{
    // Return a minimal valid HTML container that will properly wrap our content
    return '<div class="chatbot-response-container">';
}

// Thêm một wrapper function để xử lý lỗi từ handleGetRoomByIP
function safeHandleGetRoomByIP($roomBases, $uniLat, $uniLng)
{
    if (!is_array($roomBases) || empty($roomBases)) {
        error_log("safeHandleGetRoomByIP: Empty or invalid room data");
        return [];
    }

    if (!is_numeric($uniLat) || !is_numeric($uniLng)) {
        error_log("safeHandleGetRoomByIP: Invalid university coordinates: Lat=$uniLat, Lng=$uniLng");
        return [];
    }

    $validRooms = [];
    $errorCount = 0;

    // Validate the room data before passing to handleGetRoomByIP
    foreach ($roomBases as $room) {
        if (empty($room['latlng'])) {
            error_log("Room ID " . ($room['id'] ?? 'unknown') . " has empty latlng");
            $errorCount++;
            continue;
        }

        $latlng = explode(',', $room['latlng']);
        if (
            count($latlng) < 2 || !is_numeric($latlng[0]) || !is_numeric($latlng[1]) ||
            abs((float)$latlng[0]) > 90 || abs((float)$latlng[1]) > 180
        ) {
            error_log("Room ID " . ($room['id'] ?? 'unknown') . " has invalid latlng format: " . $room['latlng']);
            $errorCount++;
            continue;
        }

        // Add to valid rooms
        $validRooms[] = $room;
    }

    error_log("Validated rooms: " . count($validRooms) . " valid, " . $errorCount . " errors");

    if (empty($validRooms)) {
        error_log("safeHandleGetRoomByIP: No valid rooms to process");
        return [];
    }

    try {
        // Pass only valid rooms to the haversine function
        $nearbyRooms = handleGetRoomByIP($validRooms, $uniLat, $uniLng);

        if (!is_array($nearbyRooms)) {
            error_log("handleGetRoomByIP returned non-array result");
            return [];
        }

        error_log("handleGetRoomByIP returned " . count($nearbyRooms) . " nearby rooms");
        return $nearbyRooms;
    } catch (Exception $e) {
        error_log("Error in handleGetRoomByIP: " . $e->getMessage());
        return [];
    }
}

// Ensure htmlResponse is initialized
if (!isset($htmlResponse)) {
    error_log("WARNING: htmlResponse was not set before final response");
    $htmlResponse = ''; // Initialize to prevent undefined variable errors
}

// Ensure context is initialized
if (!isset($context)) {
    error_log("WARNING: context was not set before final response");
    $context = ''; // Initialize to prevent undefined variable errors
}

// Debug final values
error_log("Final values before response - htmlResponse length: " . strlen($htmlResponse ?? ''));
error_log("Final values before response - context length: " . strlen($context ?? ''));

// Thiết lập xử lý lỗi để bắt mọi ngoại lệ
try {
    // Luôn gọi Gemini API nhưng truyền ngữ cảnh nếu có HTML từ cơ sở dữ liệu
    $aiResponse = '';
    if (!empty($context)) {
        // Sử dụng thông tin từ truy vấn cơ sở dữ liệu làm ngữ cảnh cho AI
        $aiResponse = callGeminiAPI($message, $context, $_SESSION['chat_history']);
        error_log("Called Gemini API with context");
    } else {
        $aiResponse = callGeminiAPI($message, '', $_SESSION['chat_history']);
        error_log("Called Gemini API without context");
    }

    // Trả về phản hồi cho client với JSON header
    header('Content-Type: application/json');

    // Prepare response data
    $responseData = [
        'response' => $aiResponse,
        'html' => $htmlResponse,
        'timestamp' => time()
    ];

    // Debug response data
    error_log("Response data: html length = " . strlen($responseData['html']));

    // Ensure valid JSON output
    echo json_encode($responseData, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    // Ghi log lỗi
    error_log('Chatbot Error: ' . $e->getMessage());

    // Trả về thông báo lỗi cho người dùng
    header('Content-Type: application/json');
    echo json_encode([
        'error' => true,
        'response' => 'Xin lỗi, đã có lỗi xảy ra khi xử lý yêu cầu của bạn. Vui lòng thử lại sau.',
        'timestamp' => time()
    ]);
}
