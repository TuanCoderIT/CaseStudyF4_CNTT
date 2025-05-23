<?php

define("uniLatVinh", 18.65782);
define("unitLngVinh", 105.69636);
function splitAndTrim(string $input): array
{
    if (empty($input)) return [];
    $parts = explode(',', $input);
    // Loại bỏ khoảng trắng đầu/cuối
    $trimmed = array_map('trim', $parts);
    // Nếu muốn bỏ luôn các phần tử rỗng:
    // $trimmed = array_filter($trimmed, fn($item) => $item !== '');
    return $trimmed;
}
function haversine($lat1, $lng1, $lat2, $lng2)
{
    $R = 6371; // bán kính Trái đất (km)
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) * sin($dLat / 2)
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
        * sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * asin(min(1, sqrt($a)));
    return $R * $c;
}
function handleGetRoomByIP($roomBases, $uniLat, $uniLng)
{
    // Giả sử $rooms là mảng associative kết quả từ SELECT id, name, lat, lng FROM rooms
    $nearby = [];

    foreach ($roomBases as $nearest_room) {
        $latlng = explode(',', $nearest_room['latlng']);
        $lat = $latlng[0];
        $lng = $latlng[1];
        $dist = haversine($uniLat, $uniLng, $lat, $lng);
        // Gán thêm trường distance để tiện hiển thị/sắp xếp
        $nearest_room['distance'] = round($dist, 2);
        if ($dist <= 3) {         // chỉ lấy trọ trong bán kính 3 km
            $nearby[] = $nearest_room;
        }
    }
    usort($nearby, function ($a, $b) {
        return $a['distance'] <=> $b['distance'];
    });
    return $nearby;
}
