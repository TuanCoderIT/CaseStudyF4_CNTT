<?php
// Khởi tạo phiên làm việc
session_start();

// Kiểm tra vai trò admin
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../Auth/login.php');
    exit;
}

// Kết nối đến CSDL
require_once('../config/db.php');

// Xử lý request cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_id']) && isset($_POST['status'])) {
    $room_id = $_POST['room_id'];
    $status = $_POST['status'];

    // Kiểm tra và xử lý giá trị status
    if ($status < 0 || $status > 2) {
        $_SESSION['error'] = "Trạng thái không hợp lệ!";
        header('Location: manage_rooms.php');
        exit;
    }
    // Lấy thông tin phòng trước khi cập nhật
    $get_room = $conn->prepare("SELECT title FROM motel WHERE id = ?");
    $get_room->bind_param("i", $room_id);
    $get_room->execute();
    $room_result = $get_room->get_result();
    $room_data = $room_result->fetch_assoc();
    $room_title = $room_data['title'];
    $get_room->close();

    // Cập nhật trạng thái phòng
    $stmt = $conn->prepare("UPDATE motel SET approve = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $room_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Đã cập nhật trạng thái phòng thành công!";

        // Lưu thông tin thay đổi trạng thái để hiển thị notification
        $_SESSION['room_status_change'] = [
            'id' => $room_id,
            'title' => $room_title,
            'status' => $status,
            'timestamp' => time()
        ];
    } else {
        $_SESSION['error'] = "Có lỗi xảy ra: " . $conn->error;
    }

    $stmt->close();

    // Chuyển hướng về trang quản lý phòng
    header('Location: manage_rooms.php');
    exit;
}

// Nếu không có dữ liệu POST hoặc thiếu thông tin, chuyển hướng về trang quản lý
header('Location: manage_rooms.php');
exit;
