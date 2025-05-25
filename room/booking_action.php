<?php
session_start();

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_POST['bookingId']) || !isset($_POST['action'])) {
    header("Location: my_bookings.php?error=missing_params");
    exit;
}

$booking_id = intval($_POST['bookingId']);
$action = $_POST['action'];

// Kiểm tra hành động hợp lệ
if ($action !== 'refund' && $action !== 'release') {
    header("Location: my_bookings.php?error=invalid_action");
    exit;
}


require_once '../config/db.php';
$mysqli = $conn;

$sql = "SELECT b.*, 
               m.user_id AS owner_id,
               m.title AS motel_title,
               u_renter.name AS renter_name,
               u_owner.name AS owner_name  
        FROM bookings b
        JOIN motel m ON b.motel_id = m.id
        JOIN users u_renter ON b.user_id = u_renter.id
        JOIN users u_owner ON m.user_id = u_owner.id
        WHERE b.id = ? AND b.status = 'SUCCESS' AND b.user_id = ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Booking không tồn tại, không ở trạng thái SUCCESS hoặc người dùng không có quyền
    header("Location: booking_detail.php?bookingId=$booking_id&error=not_authorized");
    exit;
}

$booking = $result->fetch_assoc();
$owner_id = $booking['owner_id'];
$motel_title = $booking['motel_title'];
$renter_name = $booking['renter_name'];
$owner_name = $booking['owner_name'];

// Xử lý hành động
if ($action === 'refund') {
    $update_sql = "UPDATE bookings 
                  SET status = 'REFUND_REQUESTED', request_refund_at = NOW() 
                  WHERE id = ?";

    $update_stmt = $mysqli->prepare($update_sql);
    $update_stmt->bind_param("i", $booking_id);

    if ($update_stmt->execute()) {
        $renter_title = "Yêu cầu hoàn tiền đã được gửi";
        $renter_message = "Yêu cầu hoàn tiền đặt cọc cho phòng \"" . htmlspecialchars($motel_title) . "\" đã được gửi. Chúng tôi sẽ xử lý trong vòng 24 giờ.";

        $insert_renter_notif = "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)";
        $notif_stmt = $mysqli->prepare($insert_renter_notif);
        $notif_stmt->bind_param("iss", $user_id, $renter_title, $renter_message);
        $notif_stmt->execute();

        $owner_title = "Có yêu cầu hoàn tiền mới";
        $owner_message = "Người thuê " . htmlspecialchars($renter_name) . " đã yêu cầu hoàn tiền đặt cọc cho phòng \"" . htmlspecialchars($motel_title) . "\". Hệ thống sẽ xử lý trong vòng 24 giờ.";

        $insert_owner_notif = "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)";
        $notif_stmt = $mysqli->prepare($insert_owner_notif);
        $notif_stmt->bind_param("iss", $owner_id, $owner_title, $owner_message);
        $notif_stmt->execute();

        $_SESSION['flash_message'] = "Yêu cầu hoàn tiền đã được gửi thành công.";
    } else {
        $_SESSION['flash_message'] = "Có lỗi xảy ra khi gửi yêu cầu hoàn tiền.";
    }

    $update_stmt->close();
} elseif ($action === 'release') {
    $update_sql = "UPDATE bookings 
                  SET status = 'RELEASED'
                  WHERE id = ?";

    $update_stmt = $mysqli->prepare($update_sql);
    $update_stmt->bind_param("i", $booking_id);

    if ($update_stmt->execute()) {
        // Cập nhật trạng thái phòng đã cho thuê
        $update_motel_sql = "UPDATE motel SET isExist = 0 WHERE id = ?";
        $update_motel_stmt = $mysqli->prepare($update_motel_sql);
        $update_motel_stmt->bind_param("i", $booking['motel_id']);
        $update_motel_stmt->execute();
        $update_motel_stmt->close();

        $renter_title = "Xác nhận giải ngân tiền cọc";
        $renter_message = "Bạn đã xác nhận giải ngân tiền cọc cho phòng \"" . htmlspecialchars($motel_title) . "\". Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi.";

        $insert_renter_notif = "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)";
        $notif_stmt = $mysqli->prepare($insert_renter_notif);
        $notif_stmt->bind_param("iss", $user_id, $renter_title, $renter_message);
        $notif_stmt->execute();

        $owner_title = "Tiền cọc đã được giải ngân";
        $owner_message = "Người thuê " . htmlspecialchars($renter_name) . " đã xác nhận giải ngân tiền cọc cho phòng \"" . htmlspecialchars($motel_title) . "\". Tiền đặt cọc đã được chuyển cho bạn.";

        $insert_owner_notif = "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)";
        $notif_stmt = $mysqli->prepare($insert_owner_notif);
        $notif_stmt->bind_param("iss", $owner_id, $owner_title, $owner_message);
        $notif_stmt->execute();

        // Đặt thông báo thành công
        $_SESSION['flash_message'] = "Xác nhận giải ngân tiền cọc thành công.";
    } else {
        // Đặt thông báo lỗi
        $_SESSION['flash_message'] = "Có lỗi xảy ra khi xác nhận thuê phòng.";
    }

    $update_stmt->close();
}

// Đóng kết nối
$mysqli->close();

header("Location: booking_detail.php?bookingId=$booking_id");
exit;
