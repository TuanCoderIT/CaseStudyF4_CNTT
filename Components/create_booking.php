// vnpay_create_payment.php

<?php
session_start();

// 1. Kết nối DB
require_once('../config/db.php');



$user_id = $_SESSION['user_id'];
$motelId      = $_POST['motel_id']      ?? null;  
$depositAmount= $_POST['deposit_amount']    ?? null;
if (!$user_id || !$motelId || !$depositAmount) {
    die("Thiếu thông tin cần thiết để đặt cọc.");
}

// 3. Tạo bản ghi booking với trạng thái PENDING
$stmt = $conn->prepare("
    INSERT INTO bookings (user_id, motel_id, deposit_amount)
    VALUES (?, ?, ?)
");
$stmt->bind_param("iii", $user_id, $motelId, $depositAmount);
$stmt->execute();

// Lấy ID bản ghi vừa tạo, dùng làm vnp_TxnRef
$bookingId = $stmt->insert_id;
$_SESSION['booking_id'] = $bookingId;

$stmt->close();

// 4. Xây dựng URL VNPay
date_default_timezone_set('Asia/Ho_Chi_Minh');

$vnp_TmnCode   = "3B838ZBS";
$vnp_HashSecret= "FZJ7KT64QMGB48NTW0HQG1DBKPTLG8N6";
$vnp_Url       = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";

$inputData = [
    "vnp_Version"        => "2.1.0",
    "vnp_TmnCode"        => $vnp_TmnCode,
    "vnp_Amount"         => $depositAmount *  100,
    "vnp_Command"        => "pay",
    "vnp_CreateDate"     => date('YmdHis'),
    "vnp_CurrCode"       => "VND",
    "vnp_IpAddr"         => $_SERVER['REMOTE_ADDR'],
    "vnp_Locale"         => "vn",
    "vnp_OrderInfo"      => "Dat coc phong $motelId, booking $bookingId",
    "vnp_OrderType"      => "other",
    "vnp_ReturnUrl"      => "http://localhost/home/return_vnpay.php",
    "vnp_TxnRef"         => $bookingId
];

// Sắp xếp và ghép query/hash
ksort($inputData);
$hashData = '';
$query    = '';
foreach ($inputData as $key => $value) {
    $hashData .= urlencode($key) . '=' . urlencode($value) . '&';
    $query    .= urlencode($key) . '=' . urlencode($value) . '&';
}
$hashData = rtrim($hashData, '&');
$query    = rtrim($query, '&');

// Tạo secure hash
$vnp_SecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

// Redirect
$redirectUrl = $vnp_Url . '?' . $query . '&vnp_SecureHash=' . $vnp_SecureHash;
header('Location: ' . $redirectUrl);
exit;