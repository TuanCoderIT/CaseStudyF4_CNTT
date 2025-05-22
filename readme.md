// hàm tính toán khoảng cách giữa 2 tọa độ

function haversineDistance($lat1, $lng1, $lat2, $lng2, $unit = 'km')
{
    $earthRadius = ($unit === 'm') ? 6371000 : 6371; // m hoặc km

    $lat1 = deg2rad($lat1);
    $lat2 = deg2rad($lat2);
    $lng1 = deg2rad($lng1);
    $lng2 = deg2rad($lng2);

    $deltaLat = $lat2 - $lat1;
    $deltaLng = $lng2 - $lng1;

    $a = sin($deltaLat / 2) ** 2 +
         cos($lat1) * cos($lat2) * sin($deltaLng / 2) ** 2;

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;

}

$lat1 = 21.0285;
$lng1 = 105.8542;

$lat2 = 10.762622;
$lng2 = 106.660172;

$distance = haversineDistance($lat1, $lng1, $lat2, $lng2); // đơn vị: km

echo "Khoảng cách: " . round($distance, 2) . " km";


$distance = haversineDistance($userLat, $userLng, $motelLat, $motelLng);