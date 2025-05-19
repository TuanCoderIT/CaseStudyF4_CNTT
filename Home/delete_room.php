<?php
// Khởi tạo phiên làm việc
session_start();

// Kiểm tra xem người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Auth/login.php');
    exit;
}

// Kết nối đến CSDL
require_once('../config/db.php');

// Kiểm tra ID phòng trọ
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID phòng trọ không hợp lệ.";
    header('Location: my_posted_rooms.php');
    exit;
}

$room_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Kiểm tra xem phòng trọ có thuộc về người dùng hiện tại không (hoặc là admin)
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] == 1;

if (!$is_admin) {
    // Người dùng thường chỉ có thể xóa phòng của họ
    $check_stmt = $conn->prepare("SELECT * FROM motel WHERE id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $room_id, $user_id);
} else {
    // Admin có thể xóa bất kỳ phòng nào
    $check_stmt = $conn->prepare("SELECT * FROM motel WHERE id = ?");
    $check_stmt->bind_param("i", $room_id);
}

$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Bạn không có quyền xóa phòng trọ này hoặc phòng trọ không tồn tại.";
    header('Location: ' . ($is_admin ? '../Admin/rooms/manage_rooms.php' : 'my_posted_rooms.php'));
    exit;
}

// Lấy thông tin ảnh để xóa file
$room = $result->fetch_assoc();
$images = explode(',', $room['images']);

// Thực hiện xóa phòng trọ
$delete_stmt = $conn->prepare("DELETE FROM motel WHERE id = ?");
$delete_stmt->bind_param("i", $room_id);

if ($delete_stmt->execute()) {
    // Xóa ảnh khỏi thư mục uploads
    foreach ($images as $image) {
        $image_path = '../' . $image;
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    // Xóa khỏi danh sách yêu thích của người dùng
    $conn->query("DELETE FROM user_wishlist WHERE motel_id = $room_id");

    $_SESSION['success'] = "Đã xóa phòng trọ thành công!";
} else {
    $_SESSION['error'] = "Có lỗi xảy ra khi xóa phòng trọ: " . $conn->error;
}

// Chuyển hướng về trang thích hợp
if ($is_admin) {
    header('Location: ../Admin/rooms/manage_rooms.php');
} else {
    header('Location: my_posted_rooms.php');
}
exit;
