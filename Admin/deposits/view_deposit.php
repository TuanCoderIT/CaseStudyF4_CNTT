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
        m.description AS motel_description,
        m.address AS motel_address,
        m.images AS motel_image,
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

// Lấy lịch sử cập nhật của đặt cọc (có thể thêm bảng booking_log nếu cần)
// Trong trường hợp này, chúng ta sẽ giả lập một số log để hiển thị
$booking_logs = [
    [
        'action' => 'CREATE',
        'status' => 'PENDING',
        'timestamp' => $booking['created_at'],
        'description' => 'Người dùng tạo đặt cọc'
    ]
];

if ($booking['status'] != 'PENDING') {
    $booking_logs[] = [
        'action' => 'UPDATE',
        'status' => 'SUCCESS',
        'timestamp' => date('Y-m-d H:i:s', strtotime($booking['created_at'] . ' +5 minutes')),
        'description' => 'Thanh toán tiền cọc thành công'
    ];
}

if ($booking['status'] == 'REFUND_REQUESTED') {
    $booking_logs[] = [
        'action' => 'UPDATE',
        'status' => 'REFUND_REQUESTED',
        'timestamp' => $booking['request_refund_at'],
        'description' => 'Người thuê yêu cầu hoàn tiền cọc'
    ];
}

if ($booking['status'] == 'RELEASED') {
    $booking_logs[] = [
        'action' => 'UPDATE',
        'status' => 'RELEASED',
        'timestamp' => $booking['updated_at'],
        'description' => 'Tiền cọc đã được giải ngân cho chủ trọ'
    ];
}

if ($booking['status'] == 'REFUNDED') {
    $booking_logs[] = [
        'action' => 'UPDATE',
        'status' => 'REFUNDED',
        'timestamp' => $booking['updated_at'],
        'description' => 'Tiền cọc đã được hoàn trả cho người thuê'
    ];
}

function formatCurrency($amount)
{
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}

function formatDate($dateString)
{
    $date = new DateTime($dateString);
    return $date->format('d/m/Y H:i:s');
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết tiền cọc #<?= $booking_id ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="../../assets/admin/css/admin.css" rel="stylesheet">
    <style>
        .booking-image {
            max-height: 250px;
            object-fit: cover;
            border-radius: 8px;
        }

        .detail-row {
            margin-bottom: 10px;
        }

        .section-divider {
            margin: 30px 0 20px;
            border-top: 1px solid #e9ecef;
        }

        .user-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .timeline {
            position: relative;
            padding-left: 30px;
            margin-top: 20px;
        }

        .timeline:before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }

        .timeline-point {
            position: absolute;
            left: -30px;
            width: 30px;
            height: 30px;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
            border: 2px solid #007bff;
            color: #007bff;
        }

        .timeline-content {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            position: relative;
        }

        .timeline-content:before {
            content: '';
            position: absolute;
            left: -8px;
            top: 10px;
            width: 16px;
            height: 16px;
            background: #f8f9fa;
            transform: rotate(45deg);
        }

        .status-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }

        .float-action-buttons {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 999;
        }

        .float-action-buttons .btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <?php include '../../components/admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-12">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div>
                        <h1 class="h2">Chi tiết tiền cọc #<?= $booking_id ?></h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="manage_deposits.php">Quản lý tiền cọc</a></li>
                                <li class="breadcrumb-item active">Chi tiết tiền cọc #<?= $booking_id ?></li>
                            </ol>
                        </nav>
                    </div>

                    <?php
                    $statusClass = '';
                    $statusText = '';

                    switch ($booking['status']) {
                        case 'PENDING':
                            $statusClass = 'bg-warning';
                            $statusText = 'Đang chờ xử lý';
                            break;
                        case 'SUCCESS':
                            $statusClass = 'bg-success';
                            $statusText = 'Đặt cọc thành công';
                            break;
                        case 'FAILED':
                            $statusClass = 'bg-danger';
                            $statusText = 'Đặt cọc thất bại';
                            break;
                        case 'REFUND_REQUESTED':
                            $statusClass = 'bg-info';
                            $statusText = 'Yêu cầu hoàn tiền';
                            break;
                        case 'RELEASED':
                            $statusClass = 'bg-primary';
                            $statusText = 'Đã giải ngân';
                            break;
                        case 'REFUNDED':
                            $statusClass = 'bg-secondary';
                            $statusText = 'Đã hoàn tiền';
                            break;
                    }
                    ?>

                    <span class="badge <?= $statusClass ?> status-badge">
                        <?= $statusText ?>
                    </span>
                </div>

                <div class="row">
                    <!-- Motel Information Column -->
                    <div class="col-md-5">
                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-home me-2"></i>Thông tin phòng trọ</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <?php if (!empty($booking['motel_image'])): ?>
                                        <img src="../../<?= htmlspecialchars($booking['motel_image']) ?>" class="img-fluid booking-image w-100" alt="Room Image">
                                    <?php else: ?>
                                        <div class="bg-light rounded booking-image d-flex align-items-center justify-content-center">
                                            <p class="text-muted">No image available</p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <h4 class="mb-3"><?= htmlspecialchars($booking['motel_title']) ?></h4>

                                <div class="detail-row">
                                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                    <?= htmlspecialchars($booking['motel_address']) ?>
                                </div>

                                <div class="detail-row">
                                    <strong><i class="fas fa-money-bill-wave me-2"></i>Giá phòng:</strong>
                                    <span class="text-primary fw-bold"><?= formatCurrency($booking['motel_price']) ?>/tháng</span>
                                </div>

                                <div class="detail-row">
                                    <strong><i class="fas fa-hand-holding-usd me-2"></i>Tiền đặt cọc:</strong>
                                    <span class="text-danger fw-bold"><?= formatCurrency($booking['deposit_amount']) ?></span>
                                </div>

                                <div class="detail-row">
                                    <strong><i class="fas fa-percentage me-2"></i>Phí hoa hồng:</strong>
                                    <span><?= $booking['commission_pct'] ?>%</span>
                                </div>
                            </div>
                        </div>

                        <!-- User Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card shadow mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Người thuê</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <img src="../../<?= !empty($booking['renter_avatar']) ? $booking['renter_avatar'] : 'uploads/avatar/default-avatar.jpg' ?>" class="user-avatar mb-3" alt="Renter Avatar">
                                        <h5 class="mb-1"><?= htmlspecialchars($booking['renter_name']) ?></h5>
                                        <p class="mb-1 small"><i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($booking['renter_email']) ?></p>
                                        <p class="mb-0 small"><i class="fas fa-phone me-1"></i> <?= htmlspecialchars($booking['renter_phone']) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card shadow mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-home me-2"></i>Chủ trọ</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <img src="../../<?= !empty($booking['owner_avatar']) ? $booking['owner_avatar'] : 'uploads/avatar/default-avatar.jpg' ?>" class="user-avatar mb-3" alt="Owner Avatar">
                                        <h5 class="mb-1"><?= htmlspecialchars($booking['owner_name']) ?></h5>
                                        <p class="mb-1 small"><i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($booking['owner_email']) ?></p>
                                        <p class="mb-0 small"><i class="fas fa-phone me-1"></i> <?= htmlspecialchars($booking['owner_phone']) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Details Column -->
                    <div class="col-md-7">
                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Thông tin đặt cọc</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>ID đặt cọc:</strong> #<?= $booking['id'] ?></p>
                                        <p><strong>Trạng thái:</strong>
                                            <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                        </p>
                                        <p><strong>Tiền đặt cọc:</strong> <?= formatCurrency($booking['deposit_amount']) ?></p>
                                        <p><strong>Phí hoa hồng:</strong> <?= $booking['commission_pct'] ?>% (<?= formatCurrency($booking['deposit_amount'] * $booking['commission_pct'] / 100) ?>)</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Ngày đặt:</strong> <?= formatDate($booking['created_at']) ?></p>
                                        <p><strong>Cập nhật lần cuối:</strong> <?= formatDate($booking['updated_at']) ?></p>
                                        <?php if ($booking['status'] == 'REFUND_REQUESTED' && $booking['request_refund_at']): ?>
                                            <p><strong>Yêu cầu hoàn tiền lúc:</strong> <?= formatDate($booking['request_refund_at']) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($booking['vnp_transaction_id'])): ?>
                                            <p><strong>Mã giao dịch:</strong> <code><?= htmlspecialchars($booking['vnp_transaction_id']) ?></code></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Lịch sử hoạt động</h5>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <?php foreach ($booking_logs as $log): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-point">
                                                <?php
                                                $icon = 'fa-circle';
                                                switch ($log['status']) {
                                                    case 'SUCCESS':
                                                        $icon = 'fa-check';
                                                        break;
                                                    case 'FAILED':
                                                        $icon = 'fa-times';
                                                        break;
                                                    case 'REFUND_REQUESTED':
                                                        $icon = 'fa-undo';
                                                        break;
                                                    case 'RELEASED':
                                                        $icon = 'fa-check-double';
                                                        break;
                                                    case 'REFUNDED':
                                                        $icon = 'fa-hand-holding-usd';
                                                        break;
                                                }
                                                ?>
                                                <i class="fas <?= $icon ?> fa-sm"></i>
                                            </div>
                                            <div class="timeline-content">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <h6 class="mb-0"><?= $log['description'] ?></h6>
                                                    <span class="text-muted small"><?= formatDate($log['timestamp']) ?></span>
                                                </div>
                                                <?php
                                                $statusBadge = '';
                                                switch ($log['status']) {
                                                    case 'PENDING':
                                                        $statusBadge = '<span class="badge bg-warning">Đang chờ xử lý</span>';
                                                        break;
                                                    case 'SUCCESS':
                                                        $statusBadge = '<span class="badge bg-success">Đặt cọc thành công</span>';
                                                        break;
                                                    case 'FAILED':
                                                        $statusBadge = '<span class="badge bg-danger">Đặt cọc thất bại</span>';
                                                        break;
                                                    case 'REFUND_REQUESTED':
                                                        $statusBadge = '<span class="badge bg-info">Yêu cầu hoàn tiền</span>';
                                                        break;
                                                    case 'RELEASED':
                                                        $statusBadge = '<span class="badge bg-primary">Đã giải ngân</span>';
                                                        break;
                                                    case 'REFUNDED':
                                                        $statusBadge = '<span class="badge bg-secondary">Đã hoàn tiền</span>';
                                                        break;
                                                }
                                                echo $statusBadge;
                                                ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- <div class="float-action-buttons">
                <a href="manage_deposits.php" class="btn btn-secondary" title="Quay lại">
                    <i class="fas fa-arrow-left"></i>
                </a>

                <?php if ($booking['status'] == 'SUCCESS'): ?>
                    <a href="release_deposit.php?id=<?= $booking_id ?>" class="btn btn-success" title="Giải ngân tiền cọc">
                        <i class="fas fa-check-circle"></i>
                    </a>
                <?php endif; ?>

                <?php if ($booking['status'] == 'REFUND_REQUESTED'): ?>
                    <a href="refund_deposit.php?id=<?= $booking_id ?>" class="btn btn-info" title="Hoàn tiền">
                        <i class="fas fa-undo"></i>
                    </a>
                <?php endif; ?>
            </div> -->
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>