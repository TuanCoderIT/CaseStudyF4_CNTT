<?php
session_start();

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/db.php';
require_once '../classes/DualConfirmationSystem.php';

$user_id = $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if ($booking_id === 0) {
    header("Location: ../room/my_bookings.php?error=invalid_booking");
    exit;
}

$confirmationSystem = new DualConfirmationSystem($conn);
$booking = $confirmationSystem->getBookingWithConfirmations($booking_id, $user_id);

if (!$booking) {
    header("Location: ../room/my_bookings.php?error=booking_not_found");
    exit;
}

// Kiểm tra xem user có phải là landlord không
$is_landlord = ($booking['landlord_id'] == $user_id);
$is_tenant = ($booking['user_id'] == $user_id);

if (!$is_landlord && !$is_tenant) {
    header("Location: ../room/my_bookings.php?error=not_authorized");
    exit;
}

// Xử lý form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $confirmation = $_POST['confirmation'];
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

    if ($action === 'landlord_confirm' && $is_landlord) {
        $result = $confirmationSystem->processLandlordConfirmation($booking_id, $user_id, $confirmation, $notes);
        $_SESSION['flash_message'] = $result['message'];
        $_SESSION['flash_type'] = $result['success'] ? 'success' : 'error';
    } elseif ($action === 'tenant_confirm' && $is_tenant) {
        $result = $confirmationSystem->processTenantConfirmation($booking_id, $user_id, $confirmation, $notes);
        $_SESSION['flash_message'] = $result['message'];
        $_SESSION['flash_type'] = $result['success'] ? 'success' : 'error';
    }

    header("Location: confirmation.php?booking_id=$booking_id");
    exit;
}

// Refresh booking data after potential updates
$booking = $confirmationSystem->getBookingWithConfirmations($booking_id, $user_id);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận thuê phòng - Hệ thống phòng trọ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .confirmation-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
        }

        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background-color: #d1e7dd;
            color: #0a3622;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #58151c;
        }

        .confirmation-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .timeline-item {
            border-left: 3px solid #dee2e6;
            padding-left: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }

        .timeline-item.completed {
            border-left-color: #28a745;
        }

        .timeline-item.rejected {
            border-left-color: #dc3545;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 0;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #dee2e6;
        }

        .timeline-item.completed::before {
            background: #28a745;
        }

        .timeline-item.rejected::before {
            background: #dc3545;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-handshake me-2"></i>Xác nhận thuê phòng</h2>
                    <a href="../room/my_bookings.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Quay lại
                    </a>
                </div>

                <!-- Flash Messages -->
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible fade show">
                        <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                <?php endif; ?>

                <!-- Booking Information -->
                <div class="card confirmation-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-home me-2"></i>Thông tin đặt cọc</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted">Thông tin phòng</h6>
                                <p class="mb-1"><strong>Tên phòng:</strong> <?php echo htmlspecialchars($booking['motel_title']); ?></p>
                                <p class="mb-1"><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($booking['motel_address']); ?></p>
                                <p class="mb-1"><strong>Giá thuê:</strong> <?php echo number_format($booking['motel_price']); ?> VNĐ/tháng</p>
                                <p class="mb-1"><strong>Tiền cọc:</strong> <?php echo number_format($booking['deposit_amount']); ?> VNĐ</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Thông tin liên quan</h6>
                                <p class="mb-1"><strong>Người thuê:</strong> <?php echo htmlspecialchars($booking['tenant_name']); ?></p>
                                <p class="mb-1"><strong>Chủ trọ:</strong> <?php echo htmlspecialchars($booking['landlord_name']); ?></p>
                                <p class="mb-1"><strong>Ngày đặt cọc:</strong> <?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></p>
                                <p class="mb-1">
                                    <strong>Trạng thái:</strong>
                                    <span class="status-badge 
                                        <?php
                                        echo $booking['status'] === 'AWAITING_CONFIRMATION' ? 'status-pending' : ($booking['status'] === 'CONFIRMED_RENTAL' ? 'status-confirmed' : ($booking['status'] === 'AUTO_REFUNDED' ? 'status-rejected' : 'status-pending'));
                                        ?>">
                                        <?php
                                        switch ($booking['status']) {
                                            case 'AWAITING_CONFIRMATION':
                                                echo 'Chờ xác nhận';
                                                break;
                                            case 'CONFIRMED_RENTAL':
                                                echo 'Đã xác nhận thuê';
                                                break;
                                            case 'AUTO_REFUNDED':
                                                echo 'Đã hoàn tiền';
                                                break;
                                            default:
                                                echo ucfirst(strtolower($booking['status']));
                                        }
                                        ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirmation Status -->
                <div class="card confirmation-card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-check-double me-2"></i>Trạng thái xác nhận</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="timeline-item <?php echo $booking['landlord_confirmation'] === 'CONFIRMED' ? 'completed' : ($booking['landlord_confirmation'] === 'REJECTED' ? 'rejected' : ''); ?>">
                                    <h6>Xác nhận từ chủ trọ</h6>
                                    <span class="status-badge 
                                        <?php
                                        echo $booking['landlord_confirmation'] === 'PENDING' ? 'status-pending' : ($booking['landlord_confirmation'] === 'CONFIRMED' ? 'status-confirmed' : 'status-rejected');
                                        ?>">
                                        <?php
                                        switch ($booking['landlord_confirmation']) {
                                            case 'PENDING':
                                                echo 'Chờ xác nhận';
                                                break;
                                            case 'CONFIRMED':
                                                echo 'Đã xác nhận';
                                                break;
                                            case 'REJECTED':
                                                echo 'Đã từ chối';
                                                break;
                                        }
                                        ?>
                                    </span>
                                    <?php if ($booking['landlord_confirmation_at']): ?>
                                        <small class="text-muted d-block">
                                            <?php echo date('d/m/Y H:i', strtotime($booking['landlord_confirmation_at'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="timeline-item <?php echo $booking['tenant_confirmation'] === 'CONFIRMED' ? 'completed' : ($booking['tenant_confirmation'] === 'REJECTED' ? 'rejected' : ''); ?>">
                                    <h6>Xác nhận từ người thuê</h6>
                                    <span class="status-badge 
                                        <?php
                                        echo $booking['tenant_confirmation'] === 'PENDING' ? 'status-pending' : ($booking['tenant_confirmation'] === 'CONFIRMED' ? 'status-confirmed' : 'status-rejected');
                                        ?>">
                                        <?php
                                        switch ($booking['tenant_confirmation']) {
                                            case 'PENDING':
                                                echo 'Chờ xác nhận';
                                                break;
                                            case 'CONFIRMED':
                                                echo 'Đã xác nhận';
                                                break;
                                            case 'REJECTED':
                                                echo 'Đã từ chối';
                                                break;
                                        }
                                        ?>
                                    </span>
                                    <?php if ($booking['tenant_confirmation_at']): ?>
                                        <small class="text-muted d-block">
                                            <?php echo date('d/m/Y H:i', strtotime($booking['tenant_confirmation_at'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($booking['confirmation_notes']): ?>
                            <div class="mt-3">
                                <h6>Ghi chú:</h6>
                                <div class="bg-light p-3 rounded">
                                    <?php echo nl2br(htmlspecialchars($booking['confirmation_notes'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Confirmation Actions -->
                <?php if ($booking['status'] === 'AWAITING_CONFIRMATION'): ?>
                    <!-- Landlord Confirmation Form -->
                    <?php if ($is_landlord && $booking['landlord_confirmation'] === 'PENDING'): ?>
                        <div class="card confirmation-card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Xác nhận từ chủ trọ</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="landlord_confirm">

                                    <div class="mb-3">
                                        <label class="form-label">Quyết định của bạn:</label>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check p-3 border rounded">
                                                    <input class="form-check-input" type="radio" name="confirmation" value="CONFIRMED" id="landlord_confirm" required>
                                                    <label class="form-check-label w-100" for="landlord_confirm">
                                                        <i class="fas fa-check text-success me-2"></i>
                                                        <strong>Xác nhận cho thuê</strong>
                                                        <small class="d-block text-muted">Tôi xác nhận đã cho người này thuê phòng</small>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check p-3 border rounded">
                                                    <input class="form-check-input" type="radio" name="confirmation" value="REJECTED" id="landlord_reject" required>
                                                    <label class="form-check-label w-100" for="landlord_reject">
                                                        <i class="fas fa-times text-danger me-2"></i>
                                                        <strong>Từ chối</strong>
                                                        <small class="d-block text-muted">Tôi không cho thuê phòng này</small>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Ghi chú (không bắt buộc):</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Nhập ghi chú của bạn..."></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-1"></i>Gửi xác nhận
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tenant Confirmation Form -->
                    <?php if ($is_tenant && $booking['tenant_confirmation'] === 'PENDING'): ?>
                        <div class="card confirmation-card mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Xác nhận từ người thuê</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="tenant_confirm">

                                    <div class="mb-3">
                                        <label class="form-label">Quyết định của bạn:</label>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check p-3 border rounded">
                                                    <input class="form-check-input" type="radio" name="confirmation" value="CONFIRMED" id="tenant_confirm" required>
                                                    <label class="form-check-label w-100" for="tenant_confirm">
                                                        <i class="fas fa-check text-success me-2"></i>
                                                        <strong>Xác nhận đã thuê</strong>
                                                        <small class="d-block text-muted">Tôi xác nhận đã thuê phòng này</small>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check p-3 border rounded">
                                                    <input class="form-check-input" type="radio" name="confirmation" value="REJECTED" id="tenant_reject" required>
                                                    <label class="form-check-label w-100" for="tenant_reject">
                                                        <i class="fas fa-times text-danger me-2"></i>
                                                        <strong>Từ chối</strong>
                                                        <small class="d-block text-muted">Tôi không thuê phòng này</small>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Ghi chú (không bắt buộc):</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Nhập ghi chú của bạn..."></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-1"></i>Gửi xác nhận
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Result Information -->
                <?php if ($booking['status'] === 'CONFIRMED_RENTAL' || $booking['status'] === 'AUTO_REFUNDED'): ?>
                    <div class="card confirmation-card">
                        <div class="card-header <?php echo $booking['status'] === 'CONFIRMED_RENTAL' ? 'bg-success' : 'bg-danger'; ?> text-white">
                            <h5 class="mb-0">
                                <i class="fas <?php echo $booking['status'] === 'CONFIRMED_RENTAL' ? 'fa-check-circle' : 'fa-times-circle'; ?> me-2"></i>
                                <?php echo $booking['status'] === 'CONFIRMED_RENTAL' ? 'Thuê thành công' : 'Đã hoàn tiền'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($booking['status'] === 'CONFIRMED_RENTAL'): ?>
                                <div class="alert alert-success">
                                    <h6><i class="fas fa-info-circle me-2"></i>Kết quả xử lý:</h6>
                                    <ul class="mb-0">
                                        <li>Cả hai bên đã xác nhận việc cho thuê/thuê phòng</li>
                                        <li><strong><?php echo number_format($booking['deposit_amount'] * 0.9); ?> VNĐ</strong> (90% tiền cọc) đã được chuyển cho chủ trọ</li>
                                        <li>Phòng đã được đóng và không còn hiển thị trên hệ thống</li>
                                        <li>Thời gian xử lý: <?php echo date('d/m/Y H:i', strtotime($booking['auto_processed_at'])); ?></li>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Kết quả xử lý:</h6>
                                    <ul class="mb-0">
                                        <li>Do một trong hai bên không đồng ý, giao dịch đã bị hủy</li>
                                        <li><strong><?php echo number_format($booking['deposit_amount'] * 0.9); ?> VNĐ</strong> (90% tiền cọc) đã được hoàn trả cho người thuê</li>
                                        <li>Phòng vẫn có sẵn để cho thuê</li>
                                        <li>Thời gian xử lý: <?php echo date('d/m/Y H:i', strtotime($booking['auto_processed_at'])); ?></li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>