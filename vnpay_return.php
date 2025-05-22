// return_vnpay.php

<?php
session_start();

// 1. Kết nối DB
require_once('./config/db.php');

// 2. Khóa bí mật
$vnp_HashSecret = "FZJ7KT64QMGB48NTW0HQG1DBKPTLG8N6";

// 3. Kiểm tra chữ ký trả về
if (!isset($_GET['vnp_SecureHash'])) {
    die("<h3>❌ Không có thông tin thanh toán trả về</h3>");
}

$inputData = [];
foreach ($_GET as $key => $value) {
    if ($key !== 'vnp_SecureHash' && $key !== 'vnp_SecureHashType') {
        $inputData[$key] = $value;
    }
}

ksort($inputData);
$hashData  = urldecode(http_build_query($inputData));
$secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

// 4. So sánh chữ ký
if ($secureHash !== $_GET['vnp_SecureHash']) {
    die("<h3>⚠️ Sai chữ ký!</h3>");
}

// 5. Lấy booking_id và mã giao dịch
$bookingId        = intval($_GET['vnp_TxnRef']);
$transactionId    = $_GET['vnp_TransactionNo'] ?? null;
$responseCode     = $_GET['vnp_ResponseCode']   ?? null;

// 6. Cập nhật trạng thái trong DB
if ($bookingId && $transactionId) {
    if ($responseCode === '00') {
        $status = 'SUCCESS';
    } else {
        $status = 'FAILED';
    }

    $stmt = $conn->prepare("
        UPDATE bookings
        SET status = ?, vnp_transaction_id = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $status, $transactionId, $bookingId);
    $stmt->execute();
    $stmt->close();

    // 7. Hiển thị kết quả
    if ($status === 'SUCCESS') {
        echo "<h3>✅ Giao dịch thành công!</h3>";
    } else {
        echo "<h3>❌ Giao dịch thất bại! Mã lỗi: $responseCode</h3>";
    }
} else {
    echo "<h3>⚠️ Thông tin booking không hợp lệ.</h3>";
}
