<?php
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/quill/';
$publicUrlBase = '/uploads/quill/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Lỗi file']);
    exit;
}

$ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($ext, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Chỉ hỗ trợ ảnh jpg, png, gif, webp']);
    exit;
}

$filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '', $_FILES['image']['name']);
$savePath = $uploadDir . $filename;
$publicUrl = $publicUrlBase . $filename;

if (move_uploaded_file($_FILES['image']['tmp_name'], $savePath)) {
    echo json_encode(['url' => $publicUrl]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Không thể lưu ảnh']);
}
    