<?php
// Khởi tạo mảng favorite_rooms từ dữ liệu trong CSDL
if (!isset($_SESSION['favorite_rooms']) && isset($_SESSION['user_id'])) {
    $_SESSION['favorite_rooms'] = array();

    // Lấy danh sách phòng yêu thích từ CSDL
    $user_id = $_SESSION['user_id'];
    $wishlist_stmt = $conn->prepare("SELECT motel_id FROM user_wishlist WHERE user_id = ?");
    $wishlist_stmt->bind_param("i", $user_id);
    $wishlist_stmt->execute();
    $wishlist_result = $wishlist_stmt->get_result();

    while ($row = $wishlist_result->fetch_assoc()) {
        $_SESSION['favorite_rooms'][] = (int)$row['motel_id'];
    }
}
