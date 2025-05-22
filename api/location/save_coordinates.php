<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Nhận JSON từ body
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['lat']) && isset($data['lng'])) {
    $address = $data['address'] ?? null;
    $lat = $data['lat'];
    $lng = $data['lng'];

    $_SESSION['lat'] = $lat;
    $_SESSION['lng'] = $lng;

    echo json_encode([
        'success' => true,
        'message' => 'Coordinates saved successfully.',
        'coordinates' => [
            'lat' => $lat,
            'lng' => $lng,
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters.',
        'debug' => $data // để bạn kiểm tra nếu cần
    ]);
    exit;
}
