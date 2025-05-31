<?php
require_once './utils/haversine.php';

// Coordinates of Vinh University
$uniLat = uniLatVinh; // 18.65782
$uniLng = unitLngVinh; // 105.69636

echo "Testing distances from Vinh University ($uniLat, $uniLng):\n\n";

// Array of room data (ID, Title, Lat, Lng)
$rooms = [
    ['id' => 16, 'title' => 'Chung cư CT 2B Quang Trung', 'latlng' => '18.6763,105.67613'],
    ['id' => 17, 'title' => 'Phòng trọ dạng căn hộ mini cao cấp', 'latlng' => '18.663709,105.701212'],
    ['id' => 18, 'title' => 'THANH DAT HOME - hệ thống căn hộ dịch vụ', 'latlng' => '18.66798,105.7059'],
    ['id' => 19, 'title' => 'Phòng trọ khép kín cao tầng', 'latlng' => '18.65915,105.70024'],
    ['id' => 23, 'title' => 'Chung cư', 'latlng' => '18.66414,105.69822'],
    ['id' => 24, 'title' => 'Tìm bạn nữ ở cùng', 'latlng' => '18.68452,105.69663'],
    ['id' => 25, 'title' => 'Chung cư Vinhomes Quang Trung', 'latlng' => '18.66694,105.68566'],
    ['id' => 26, 'title' => 'Chung cư mini', 'latlng' => '18.69795,105.67504'],
    ['id' => 27, 'title' => 'trọ giá rẻ', 'latlng' => '18.65273,105.69671'],
    ['id' => 28, 'title' => 'Chung cư Green View 3', 'latlng' => '18.68015,105.67055'],
    ['id' => 29, 'title' => 'chung cư Handico 3D', 'latlng' => '18.67231,105.70571'],
    ['id' => 30, 'title' => 'Phòng trọ khép kín', 'latlng' => '18.66112,105.69713'],
    ['id' => 31, 'title' => 'Chung Cư Cửa Tiền Home', 'latlng' => '18.67884,105.67566'],
    ['id' => 33, 'title' => 'Một tập lưu tranh 2 trái tim vàng', 'latlng' => '18.65817,105.70093'],
    ['id' => 34, 'title' => 'kkkkkkkkkkkk', 'latlng' => '18.66833,105.69428'],
    ['id' => 35, 'title' => 'Trọ khép kín', 'latlng' => '18.67019,105.70586'],
    ['id' => 36, 'title' => 'Căn hộ 2 phòng', 'latlng' => '18.67165,105.68959'],
    ['id' => 37, 'title' => 'Căn hộ 3 phòng', 'latlng' => '18.67337,105.6994'],
    ['id' => 38, 'title' => 'Homestay dài hạn', 'latlng' => '18.66595,105.66754'],
    ['id' => 39, 'title' => 'Chung cư mini', 'latlng' => '18.65864,105.69103'],
    ['id' => 40, 'title' => 'Chung Cư Cao Cấp', 'latlng' => '18.68149,105.67455'],
    ['id' => 41, 'title' => 'Phòng Trọ Khép Kín', 'latlng' => '18.66502,105.69981'],
];

// Get nearby rooms using the same function as in chatbot_api.php
$nearbyRooms = handleGetRoomByIP($rooms, $uniLat, $uniLng);

// Display results
echo "Found " . count($nearbyRooms) . " rooms within 3km of Vinh University:\n\n";

foreach ($nearbyRooms as $room) {
    echo "ID: " . $room['id'] . "\n";
    echo "Title: " . $room['title'] . "\n";
    echo "Distance: " . $room['distance'] . " km\n";
    echo "Coordinates: " . $room['latlng'] . "\n";
    echo "------------------------------\n";
}
