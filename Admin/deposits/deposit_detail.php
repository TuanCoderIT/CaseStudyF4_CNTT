<?php
session_start();
require_once '../../config/db.php';

// Kiểm tra quyền truy cập (chỉ admin mới được phép)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    header("Location: ../../auth/login.php");
    exit;
}

// Lấy ID đặt cọc từ tham số URL
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($booking_id == 0) {
    header("Location: manage_deposits.php");
    exit;
}

// Lấy thông tin chi tiết đặt cọc
$sql = "SELECT b.*, 
        m.title AS motel_title, 
        m.price AS motel_price,
        m.address AS motel_address,
        m.images AS motel_image,
        m.user_id AS motel_user_id,
        u.name AS renter_name, 
        u.email AS renter_email,
        u.phone AS renter_phone,
        u.avatar AS renter_avatar,
        o.name AS owner_name,
        o.email AS owner_email,
        o.phone AS owner_phone,
        o.avatar AS owner_avatar
        FROM bookings b
        JOIN motel m ON b.motel_id = m.id
        JOIN users u ON b.user_id = u.id
        JOIN users o ON m.user_id = o.id
        WHERE b.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_deposits.php?error=not_found");
    exit;
}

$booking = $result->fetch_assoc();

// Xử lý các thao tác
$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Xử lý phê duyệt yêu cầu hoàn tiền
    if (isset($_POST['approve_refund'])) {
        // Kiểm tra trạng thái phải là REFUND_REQUESTED
        if ($booking['status'] != 'REFUND_REQUESTED') {
            $error_message = "Không thể phê duyệt hoàn tiền cho trạng thái hiện tại!";
        } else {
            $conn->begin_transaction();
            try {
                // Cập nhật trạng thái
                $update_sql = "UPDATE bookings SET status = 'REFUNDED', updated_at = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $booking_id);
                $update_stmt->execute();

                // Tạo thông báo cho người thuê
                $renter_title = "Yêu cầu hoàn tiền được chấp nhận";
                $renter_message = "Yêu cầu hoàn tiền cọc của bạn cho phòng \"" . htmlspecialchars($booking['motel_title']) . "\" đã được chấp nhận. Tiền cọc sẽ được hoàn trả vào tài khoản của bạn.";

                $notify_sql = "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)";
                $notify_stmt = $conn->prepare($notify_sql);
                $notify_stmt->bind_param("iss", $booking['user_id'], $renter_title, $renter_message);
                $notify_stmt->execute();

                // Gửi email thông báo cho người thuê
                $renterInfo = [
                    'email' => $booking['renter_email'],
                    'phone' => $booking['renter_phone']
                ];
                sendUserNotification($renterInfo, $renter_title, $renter_message);

                $conn->commit();
                $success_message = "Đã phê duyệt hoàn tiền thành công!";

                // Refresh để cập nhật dữ liệu
                header("Location: deposit_detail.php?id=$booking_id&success=" . urlencode($success_message));
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Lỗi khi phê duyệt hoàn tiền: " . $e->getMessage();
            }
        }
    }

    // Xử lý từ chối yêu cầu hoàn tiền
    if (isset($_POST['reject_refund'])) {
        // Kiểm tra trạng thái phải là REFUND_REQUESTED
        if ($booking['status'] != 'REFUND_REQUESTED') {
            $error_message = "Không thể từ chối hoàn tiền cho trạng thái hiện tại!";
        } else {
            $reject_reason = isset($_POST['reject_reason']) ? $_POST['reject_reason'] : "Yêu cầu không hợp lệ";

            $conn->begin_transaction();
            try {
                // Cập nhật trạng thái về SUCCESS
                $update_sql = "UPDATE bookings SET status = 'SUCCESS', updated_at = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $booking_id);
                $update_stmt->execute();

                // Tạo thông báo cho người thuê
                $renter_title = "Yêu cầu hoàn tiền bị từ chối";
                $renter_message = "Yêu cầu hoàn tiền cọc của bạn cho phòng \"" . htmlspecialchars($booking['motel_title']) . "\" đã bị từ chối. Lý do: " . htmlspecialchars($reject_reason);

                $notify_sql = "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)";
                $notify_stmt = $conn->prepare($notify_sql);
                $notify_stmt->bind_param("iss", $booking['user_id'], $renter_title, $renter_message);
                $notify_stmt->execute();

                // Gửi email thông báo cho người thuê
                $renterInfo = [
                    'email' => $booking['renter_email'],
                    'phone' => $booking['renter_phone']
                ];
                sendUserNotification($renterInfo, $renter_title, $renter_message);

                $conn->commit();
                $success_message = "Đã từ chối yêu cầu hoàn tiền!";

                // Refresh để cập nhật dữ liệu
                header("Location: deposit_detail.php?id=$booking_id&success=" . urlencode($success_message));
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Lỗi khi từ chối hoàn tiền: " . $e->getMessage();
            }
        }
    }

    // Xử lý giải ngân tiền cọc
    if (isset($_POST['release_deposit'])) {
        // Kiểm tra trạng thái phải là SUCCESS
        if ($booking['status'] != 'SUCCESS') {
            $error_message = "Không thể giải ngân tiền cọc ở trạng thái hiện tại!";
        } else {
            $conn->begin_transaction();
            try {
                // Cập nhật trạng thái
                $update_sql = "UPDATE bookings SET status = 'RELEASED', updated_at = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $booking_id);
                $update_stmt->execute();

                // Tính hoa hồng (nếu có)
                $commission = isset($booking['commission_pct']) ? ($booking['deposit_amount'] * $booking['commission_pct'] / 100) : 0;
                $net_amount = $booking['deposit_amount'] - $commission;

                // Thông báo cho chủ trọ
                $owner_title = "Tiền cọc đã được giải ngân";
                $owner_message = "Tiền cọc từ " . htmlspecialchars($booking['renter_name']) . " cho phòng \"" . htmlspecialchars($booking['motel_title']) . "\" đã được giải ngân. Số tiền: " . number_format($net_amount) . "đ sẽ được chuyển vào tài khoản của bạn.";

                $notify_sql = "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)";
                $notify_stmt = $conn->prepare($notify_sql);
                $owner_id = $booking['owner_id'] ?? 0;
                if (empty($owner_id) && isset($booking['motel_user_id'])) {
                    $owner_id = $booking['motel_user_id'];
                }
                $notify_stmt->bind_param("iss", $owner_id, $owner_title, $owner_message);
                $notify_stmt->execute();

                // Thông báo cho người thuê
                $renter_title = "Tiền cọc đã được giải ngân cho chủ trọ";
                $renter_message = "Tiền cọc của bạn cho phòng \"" . htmlspecialchars($booking['motel_title']) . "\" đã được giải ngân cho chủ trọ.";

                $notify_sql = "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)";
                $notify_stmt = $conn->prepare($notify_sql);
                $notify_stmt->bind_param("iss", $booking['user_id'], $renter_title, $renter_message);
                $notify_stmt->execute();

                $conn->commit();
                $success_message = "Đã giải ngân tiền cọc thành công!";

                // Refresh để cập nhật dữ liệu
                header("Location: deposit_detail.php?id=$booking_id&success=" . urlencode($success_message));
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Lỗi khi giải ngân tiền cọc: " . $e->getMessage();
            }
        }
    }
}

// Hiển thị thông báo thành công từ URL
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

/**
 * Gửi thông báo đến người dùng qua email hoặc tin nhắn
 * @param array $userInfo Thông tin người dùng (email, phone)
 * @param string $title Tiêu đề thông báo
 * @param string $message Nội dung thông báo
 * @return bool Kết quả gửi thông báo
 */
function sendUserNotification($userInfo, $title, $message)
{
    // Gửi email nếu có địa chỉ email
    if (!empty($userInfo['email'])) {
        // Cài đặt tiêu đề email
        $emailSubject = "[$title] - Thông báo từ hệ thống Phòng trọ";

        // Cài đặt header email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: PhoTro <noreply@photro.com>" . "\r\n";

        // Tạo nội dung email
        $emailContent = '
        <html>
        <head>
            <title>' . $title . '</title>
        </head>
        <body>
            <div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
                <div style="background: #0d6efd; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0;">
                    <h2 style="margin:0;">' . $title . '</h2>
                </div>
                <div style="border: 1px solid #ddd; border-top: none; padding: 20px; border-radius: 0 0 5px 5px;">
                    <p>' . $message . '</p>
                    <p style="margin-top: 30px; font-size: 14px; color: #666;">Email này được gửi tự động, vui lòng không trả lời.</p>
                </div>
                <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #888;">
                    &copy; ' . date('Y') . ' Hệ thống Phòng trọ
                </div>
            </div>
        </body>
        </html>';

        // Gửi email
        @mail($userInfo['email'], $emailSubject, $emailContent, $headers);
    }

    // Trong tương lai có thể thêm gửi SMS nếu cần

    return true;
}

function formatCurrency($amount)
{
    return number_format($amount, 0, ',', '.') . ' đ';
}

function getStatusBadge($status)
{
    switch ($status) {
        case 'PENDING':
            return '<span class="badge bg-secondary">Chờ thanh toán</span>';
        case 'SUCCESS':
            return '<span class="badge bg-success">Đặt cọc thành công</span>';
        case 'REFUND_REQUESTED':
            return '<span class="badge bg-warning text-dark">Yêu cầu hoàn tiền</span>';
        case 'REFUNDED':
            return '<span class="badge bg-info text-dark">Đã hoàn tiền</span>';
        case 'RELEASED':
            return '<span class="badge bg-primary">Đã giải ngân cho chủ trọ</span>';
        case 'FAILED':
            return '<span class="badge bg-danger">Thanh toán thất bại</span>';
        default:
            return '<span class="badge bg-secondary">' . $status . '</span>';
    }
}

$page_title = "Chi tiết tiền cọc #" . $booking_id;
include_once '../../components/admin_header.php';
?>

<div class="container-fluid pt-3">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">
                    <i class="fas fa-money-check-alt me-2"></i>
                    Chi tiết tiền cọc #<?php echo $booking_id; ?>
                </h2>
                <a href="manage_deposits.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Quay lại
                </a>
            </div>
            <nav aria-label="breadcrumb" class="mt-2">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="manage_deposits.php">Quản lý tiền cọc</a></li>
                    <li class="breadcrumb-item active">Chi tiết tiền cọc #<?php echo $booking_id; ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Hướng dẫn gửi thông báo -->
    <div class="alert alert-info mb-4">
        <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Hướng dẫn xử lý giao dịch</h5>
        <p>Hệ thống sẽ tự động gửi thông báo đến người dùng khi bạn thực hiện các thao tác sau:</p>
        <ul class="mb-0">
            <li><strong>Phê duyệt hoàn tiền:</strong> Gửi thông báo cho người thuê biết yêu cầu hoàn tiền đã được chấp nhận.</li>
            <li><strong>Từ chối hoàn tiền:</strong> Gửi thông báo cho người thuê biết yêu cầu hoàn tiền đã bị từ chối, kèm lý do.</li>
            <li><strong>Giải ngân tiền cọc:</strong> Gửi thông báo cho cả chủ trọ (nhận được tiền) và người thuê (tiền đã được chuyển).</li>
        </ul>
        <hr>
        <p class="mb-0">Các thông báo được lưu vào bảng <code>notifications</code> trong cơ sở dữ liệu và hiển thị trong mục thông báo của người dùng.</p>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Thông tin đặt cọc</h5>
                <div>
                    <?php echo getStatusBadge($booking['status']); ?>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="border-bottom pb-2 mb-3">Thông tin phòng trọ</h6>
                    <p><strong>Tên phòng:</strong> <?php echo htmlspecialchars($booking['motel_title']); ?></p>
                    <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($booking['motel_address']); ?></p>
                    <p><strong>Giá phòng:</strong> <?php echo formatCurrency($booking['motel_price']); ?>/tháng</p>
                    <p><strong>Tiền cọc:</strong> <?php echo formatCurrency($booking['deposit_amount']); ?></p>

                    <?php if (!empty($booking['motel_image'])):
                        $images = explode(',', $booking['motel_image']);
                        $first_image = trim($images[0]);
                    ?>
                        <div class="mb-3">
                            <img src="/<?php echo $first_image; ?>" alt="Phòng trọ" class="img-fluid rounded" style="max-height: 200px;">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <h6 class="border-bottom pb-2 mb-3">Thông tin giao dịch</h6>
                    <p><strong>Mã đặt cọc:</strong> #<?php echo $booking['id']; ?></p>
                    <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></p>
                    <p><strong>Trạng thái:</strong> <?php echo getStatusBadge($booking['status']); ?></p>
                    <p><strong>Phương thức thanh toán:</strong> VNPay</p>

                    <?php if (!empty($booking['vnp_transaction_id'])): ?>
                        <p><strong>Mã giao dịch VNPay:</strong> <code><?php echo htmlspecialchars($booking['vnp_transaction_id']); ?></code></p>
                    <?php endif; ?>

                    <?php
                    if (isset($booking['commission_pct']) && $booking['commission_pct'] > 0) {
                        $commission = $booking['deposit_amount'] * $booking['commission_pct'] / 100;
                        $net_amount = $booking['deposit_amount'] - $commission;
                    ?>
                        <p><strong>Phí hoa hồng (<?php echo $booking['commission_pct']; ?>%):</strong> <?php echo formatCurrency($commission); ?></p>
                        <p><strong>Số tiền thực nhận:</strong> <?php echo formatCurrency($net_amount); ?></p>
                    <?php } ?>

                    <?php if ($booking['status'] == 'REFUND_REQUESTED'): ?>
                        <p><strong>Ngày yêu cầu hoàn tiền:</strong>
                            <?php echo !empty($booking['request_refund_at']) ? date('d/m/Y H:i', strtotime($booking['request_refund_at'])) : 'N/A'; ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($booking['status'] == 'REFUNDED' || $booking['status'] == 'RELEASED'): ?>
                        <p><strong>Ngày cập nhật:</strong> <?php echo date('d/m/Y H:i', strtotime($booking['updated_at'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <hr>

            <div class="row mt-4">
                <div class="col-md-6">
                    <h6 class="border-bottom pb-2 mb-3">Thông tin người thuê</h6>
                    <div class="d-flex mb-3">
                        <?php if (!empty($booking['renter_avatar'])): ?>
                            <img src="/uploads/avatar/<?php echo $booking['renter_avatar']; ?>" alt="Avatar" class="rounded-circle me-3" style="width: 64px; height: 64px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary me-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="fas fa-user text-white"></i>
                            </div>
                        <?php endif; ?>

                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($booking['renter_name']); ?></h6>
                            <p class="mb-0 text-muted small">Người thuê</p>
                        </div>
                    </div>

                    <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['renter_email']); ?></p>
                    <p><strong>Điện thoại:</strong> <?php echo htmlspecialchars($booking['renter_phone']); ?></p>
                </div>

                <div class="col-md-6">
                    <h6 class="border-bottom pb-2 mb-3">Thông tin chủ trọ</h6>
                    <div class="d-flex mb-3">
                        <?php if (!empty($booking['owner_avatar'])): ?>
                            <img src="../../uploads/avatar/<?php echo $booking['owner_avatar']; ?>" alt="Avatar" class="rounded-circle me-3" style="width: 64px; height: 64px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary me-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="fas fa-user text-white"></i>
                            </div>
                        <?php endif; ?>

                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($booking['owner_name']); ?></h6>
                            <p class="mb-0 text-muted small">Chủ trọ</p>
                        </div>
                    </div>

                    <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['owner_email']); ?></p>
                    <p><strong>Điện thoại:</strong> <?php echo htmlspecialchars($booking['owner_phone']); ?></p>
                </div>
            </div>
        </div>

        <div class="card-footer">
            <div class="d-flex justify-content-between">
                <div>
                    <?php if ($booking['status'] == 'REFUND_REQUESTED'): ?>
                        <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#approveRefundModal">
                            <i class="fas fa-check me-1"></i> Chấp nhận hoàn tiền
                        </button>
                        <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#rejectRefundModal">
                            <i class="fas fa-times me-1"></i> Từ chối hoàn tiền
                        </button>
                    <?php endif; ?>

                    <?php if ($booking['status'] == 'SUCCESS'): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#releaseDepositModal">
                            <i class="fas fa-money-bill-wave me-1"></i> Giải ngân tiền cọc
                        </button>
                    <?php endif; ?>
                </div>

                <a href="manage_deposits.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Quay lại danh sách
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal xác nhận hoàn tiền -->
<div class="modal fade" id="approveRefundModal" tabindex="-1" aria-labelledby="approveRefundModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveRefundModalLabel">Xác nhận hoàn tiền</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn hoàn tiền cọc cho giao dịch này?</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Số tiền hoàn trả: <strong><?php echo formatCurrency($booking['deposit_amount']); ?></strong>
                    </div>
                    <p class="text-muted small">Hành động này không thể hoàn tác. Tiền cọc sẽ được hoàn trả về tài khoản của người thuê.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" name="approve_refund" class="btn btn-success">Xác nhận hoàn tiền</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal từ chối hoàn tiền -->
<div class="modal fade" id="rejectRefundModal" tabindex="-1" aria-labelledby="rejectRefundModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectRefundModalLabel">Từ chối hoàn tiền</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reject_reason" class="form-label">Lý do từ chối</label>
                        <textarea class="form-control" id="reject_reason" name="reject_reason" rows="3" required></textarea>
                        <div class="form-text">Lý do này sẽ được thông báo đến người thuê.</div>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Khi từ chối, trạng thái sẽ trở về "Đã đặt cọc" và tiền cọc sẽ không được hoàn trả.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" name="reject_refund" class="btn btn-danger">Xác nhận từ chối</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal giải ngân tiền cọc -->
<div class="modal fade" id="releaseDepositModal" tabindex="-1" aria-labelledby="releaseDepositModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="releaseDepositModalLabel">Giải ngân tiền cọc</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn giải ngân tiền cọc cho chủ trọ?</p>

                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between">
                            <span>Tiền cọc:</span>
                            <strong><?php echo formatCurrency($booking['deposit_amount']); ?></strong>
                        </div>
                        <?php if (isset($booking['commission_pct']) && $booking['commission_pct'] > 0):
                            $commission = $booking['deposit_amount'] * $booking['commission_pct'] / 100;
                            $net_amount = $booking['deposit_amount'] - $commission;
                        ?>
                            <div class="d-flex justify-content-between">
                                <span>Phí hoa hồng (<?php echo $booking['commission_pct']; ?>%):</span>
                                <strong>-<?php echo formatCurrency($commission); ?></strong>
                            </div>
                            <hr class="my-1">
                            <div class="d-flex justify-content-between">
                                <span>Số tiền thực nhận:</span>
                                <strong><?php echo formatCurrency($net_amount); ?></strong>
                            </div>
                        <?php else: ?>
                            <div class="d-flex justify-content-between">
                                <span>Số tiền thực nhận:</span>
                                <strong><?php echo formatCurrency($booking['deposit_amount']); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <p class="text-muted small">Hành động này không thể hoàn tác. Tiền cọc sẽ được chuyển cho chủ trọ.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" name="release_deposit" class="btn btn-primary">Xác nhận giải ngân</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once '../../components/admin_footer.php'; ?>