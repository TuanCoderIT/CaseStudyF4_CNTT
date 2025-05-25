<?php
session_start();
require_once '../config/db.php'; // Đảm bảo đường dẫn này chính xác

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if (!isset($_GET['room_id'])) {
    header('Location: my_posted_rooms.php');
    exit();
}

$room_id = $_GET['room_id'];
$user_id = $_SESSION['user_id'];

// Kiểm tra xem phòng trọ có thuộc về người dùng hiện tại không
$sql_check_owner = "SELECT user_id, isExist FROM rooms WHERE id = ?";
$stmt_check_owner = $conn->prepare($sql_check_owner);
$stmt_check_owner->bind_param("i", $room_id);
$stmt_check_owner->execute();
$result_check_owner = $stmt_check_owner->get_result();

if ($result_check_owner->num_rows > 0) {
    $room = $result_check_owner->fetch_assoc();
    if ($room['user_id'] == $user_id) {
        // Chuyển đổi trạng thái isExist
        $new_is_exist_status = ($room['isExist'] == 1) ? 0 : 1;

        $sql_update = "UPDATE rooms SET isExist = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ii", $new_is_exist_status, $room_id);

        if ($stmt_update->execute()) {
            $_SESSION['success_message'] = "Cập nhật trạng thái hiển thị phòng trọ thành công.";
        } else {
            $_SESSION['error_message'] = "Có lỗi xảy ra khi cập nhật trạng thái.";
        }
        $stmt_update->close();
    } else {
        $_SESSION['error_message'] = "Bạn không có quyền thay đổi trạng thái của phòng này.";
    }
} else {
    $_SESSION['error_message'] = "Không tìm thấy phòng trọ.";
}

$stmt_check_owner->close();
$conn->close();

header('Location: my_posted_rooms.php');
exit();
