<?php
session_start();

// 1. Kết nối DB
require_once __DIR__ . '/config/db.php';

// 2. Khóa bí mật (hash secret) do VNPAY cấp
$vnp_HashSecret = "FZJ7KT64QMGB48NTW0HQG1DBKPTLG8N6";

// 3. Bật hiển thị lỗi khi debug (có thể tắt sau khi deploy)
// ini_set('display_errors',1);
// error_reporting(E_ALL);

// 4. Lấy chữ ký từ VNPAY & loại bỏ trước khi tính hash lại
if (!isset($_GET['vnp_SecureHash'])) {
    die("<h3>❌ Không tìm thấy chữ ký (vnp_SecureHash)</h3>");
}
$vnp_SecureHash = $_GET['vnp_SecureHash'];
unset($_GET['vnp_SecureHash'], $_GET['vnp_SecureHashType']);

// 5. Sắp xếp tham số theo key và build chuỗi có urlencode
ksort($_GET);
$hashData = [];
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) === "vnp_") {
        // urlencode để khớp đúng encode của VNPAY
        $hashData[] = $key . '=' . urlencode($value);
    }
}
$hashString   = implode('&', $hashData);
$computedHash = hash_hmac('sha512', $hashString, $vnp_HashSecret);

// 6. So sánh chữ ký
if ($computedHash !== $vnp_SecureHash) {
    die("<h3>⚠️ Sai chữ ký!</h3>");
}

// 7. Đọc các tham số cần thiết
$txnRef            = intval($_GET['vnp_TxnRef']);      // booking id
$responseCode      = $_GET['vnp_ResponseCode'];        // mã kết quả VNPAY
$transactionStatus = $_GET['vnp_TransactionStatus'];    // trạng thái giao dịch
$bankCode          = $_GET['vnp_BankCode'];            // ngân hàng
$transactionNo     = $_GET['vnp_TransactionNo'];       // mã giao dịch VNPAY
$amount            = ($_GET['vnp_Amount'] ?? 0) / 100;  // giá trị thực tế

// 8. Xác định status mới
$newStatus = ($responseCode === "00" && $transactionStatus === "00") ? 'SUCCESS' : 'FAILED';

// 9. Cập nhật DB với đúng cột hiện có
$sql = "UPDATE bookings 
        SET status = ?, 
            vnp_transaction_id = ?
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssi', $newStatus, $transactionNo, $txnRef);
$stmt->execute();
$stmt->close();

// 9. Hiển thị kết quả hoặc redirect
if ($newStatus === 'SUCCESS') {
    $sql = "
      SELECT 
        b.user_id       AS renter_id,
        m.user_id       AS owner_id,
        m.title         AS motel_title,
        u.name          AS renter_name
      FROM bookings b
      JOIN motel   m ON m.id = b.motel_id
      JOIN users   u ON u.id = b.user_id
      WHERE b.id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $txnRef);    // $txnRef là booking ID
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Không tìm thấy booking – lỗi hiếm khi xảy ra
        die("<h3>⚠️ Lỗi: không tìm thấy thông tin booking.</h3>");
    }

    $info = $result->fetch_assoc();
    $stmt->close();

    // 2. Chuẩn bị 2 notification
    $notifications = [
        [
            'user_id' => $info['owner_id'],
            'title'   => 'Có đặt cọc mới!',
            'message' => sprintf(
                'Phòng "%s" vừa được đặt cọc thành công bởi %s. Số tiền cọc: %s₫',
                $info['motel_title'],
                htmlspecialchars($info['renter_name'], ENT_QUOTES, 'UTF-8'),
                number_format($amount)
            )
        ],
        [
            'user_id' => $info['renter_id'],
            'title'   => 'Đặt cọc thành công!',
            'message' => sprintf(
                'Bạn đã đặt cọc thành công phòng "%s". Số tiền cọc: %s₫',
                $info['motel_title'],
                number_format($amount)
            )
        ]
    ];

    // 3. Lưu notification
    $insertSql = "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)";
    $notifyStmt = $conn->prepare($insertSql);

    foreach ($notifications as $n) {
        $notifyStmt->bind_param('iss', $n['user_id'], $n['title'], $n['message']);
        if (!$notifyStmt->execute()) {
            error_log("Notify insert failed: " . $notifyStmt->error);
        }
    }
    $notifyStmt->close();
    echo "<h3>✅ Thanh toán thành công!</h3>";
    echo "<p>Booking ID: {$txnRef}<br>" .
        "Phòng: " . htmlspecialchars($info['motel_title'], ENT_QUOTES, 'UTF-8') . "<br>" .
        "Số tiền: " . number_format($amount) . "₫<br>" .
        "Ngân hàng: {$bankCode}</p>";
    echo "<p><a href='/index.php'>Quay về trang chủ</a></p>";
    $conn->close();
} else {
    echo "<h3>❌ Thanh toán thất bại!</h3>";
    echo "<p>Mã lỗi VNPAY: {$responseCode}<br>Trạng thái: {$transactionStatus}</p>";
    // header("Location: fail.php?order={$txnRef}");
    // exit;
}
