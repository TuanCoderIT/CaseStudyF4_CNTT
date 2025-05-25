<?php
session_start();

// 1. Kết nối DB
require_once __DIR__ . '/../config/db.php';


$user_id = $_SESSION['user_id'];
$motelId      = $_POST['motel_id']      ?? null;
$depositAmount = $_POST['deposit_amount']    ?? null;
if (!$user_id || !$motelId || !$depositAmount) {
    die("Thiếu thông tin cần thiết để đặt cọc.");
}
$stmt = $conn->prepare("SELECT user_id FROM motel WHERE id = ?");
$stmt->bind_param('i', $motelId);
$stmt->execute();
$stmt->bind_result($ownerId);
$stmt->fetch();
$stmt->close();

if ($ownerId === $user_id) {
    // Chủ trọ tự đặt cọc phòng mình
    $redirectUrl  = '/index.php';
    $redirectTime = 3;
    echo "<div class='alert alert-danger fade show' role='alert'>
            <strong>⚠ Lỗi:</strong> Bạn không thể đặt cọc phòng của chính mình!
          </div>
          <meta http-equiv='refresh' content='{$redirectTime};url={$redirectUrl}'>";
    exit;
}
// Kiểm tra xem user đã đặt cọc thành công phòng này chưa
$sqlCheck = "
    SELECT id, status 
    FROM bookings 
    WHERE user_id = ? 
        AND motel_id = ? 
    ORDER BY id DESC
";
$stmt = $conn->prepare($sqlCheck);
$stmt->bind_param('ii', $user_id, $motelId);
$stmt->execute();
$stmt->bind_result($bookingIdFound, $bookingStatus);
$found = false;
$bookingIdToUse = null;
$hasSuccess = false;
$hasPending = false;
while ($stmt->fetch()) {
    if ($bookingStatus === 'SUCCESS') {
        $hasSuccess = true;
        break;
    }
    if ($bookingStatus === 'PENDING') {
        $hasPending = true;
        $bookingIdToUse = $bookingIdFound;
    }
    if ($bookingStatus === 'FAILED' || $bookingStatus === 'CANCELLED') {
        $bookingIdToUse = $bookingIdFound;
    }
}
$stmt->close();

if ($hasSuccess) {
    $redirectUrl = '/index.php';
    $redirectTime = 3;
    echo "<div class='alert alert-warning fade show' role='alert'>
            <div class='d-flex align-items-center'>
                <i class='fas fa-exclamation-triangle me-2'></i>
                <strong>Thông báo:</strong>&nbsp;Bạn đã đặt cọc thành công cho phòng này rồi.
            </div>
            <hr>
            <div class='d-flex justify-content-between align-items-center'>
                <div>
                    <small>Bạn sẽ được chuyển hướng sau <span id='countdown'>$redirectTime</span> giây</small>
                </div>
                <div class='spinner-border spinner-border-sm text-warning' role='status'>
                    <span class='visually-hidden'>Đang chuyển hướng...</span>
                </div>
            </div>
          </div>";
    echo "
    <script>
      let timeLeft = $redirectTime;
      const countdownEl = document.getElementById('countdown');
      const timer = setInterval(function() {
        timeLeft--;
        countdownEl.textContent = timeLeft;
        if (timeLeft <= 0) {
          clearInterval(timer);
          window.location.href = '$redirectUrl';
        }
      }, 1000);
    </script>
    ";
    exit;
}

if ($bookingIdToUse) {
    // Nếu có booking PENDING/FAILED/CANCELLED thì update lại thành PENDING và dùng lại booking id
    $stmt = $conn->prepare("UPDATE bookings SET status = 'PENDING', deposit_amount = ? WHERE id = ?");
    $stmt->bind_param('ii', $depositAmount, $bookingIdToUse);
    $stmt->execute();
    $stmt->close();
    $_SESSION['booking_id'] = $bookingIdToUse;
    $bookingId = $bookingIdToUse;
} else {
    // Tạo bản ghi booking mới với trạng thái PENDING
    $stmt = $conn->prepare("
        INSERT INTO bookings (user_id, motel_id, deposit_amount)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iii", $user_id, $motelId, $depositAmount);
    $stmt->execute();
    $bookingId = $stmt->insert_id;
    $_SESSION['booking_id'] = $bookingId;
    $stmt->close();
}

// 4. Xây dựng URL VNPay
date_default_timezone_set('Asia/Ho_Chi_Minh');

$vnp_TmnCode   = "3B838ZBS";
$vnp_HashSecret = "FZJ7KT64QMGB48NTW0HQG1DBKPTLG8N6";
$vnp_Url       = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . $host;
$returnUrl = $baseUrl . "/room/vnpay_return.php";

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
    "vnp_ReturnUrl"      => $returnUrl,
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
