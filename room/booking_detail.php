<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['bookingId']) || !is_numeric($_GET['bookingId'])) {
    header("Location: my_bookings.php");
    exit;
}

$booking_id = intval($_GET['bookingId']);

// Kết nối cơ sở dữ liệu
require_once '../config/db.php';
$mysqli = $conn;

$sql = "SELECT b.*, 
               m.title AS motel_title, 
               m.price AS motel_price,
               m.images AS motel_images,
               renter.name AS renter_name, 
               renter.email AS renter_email,
               renter.phone AS renter_phone,
               owner.name AS owner_name,
               owner.email AS owner_email,
               owner.phone AS owner_phone
        FROM bookings b
        JOIN motel m ON b.motel_id = m.id
        JOIN users renter ON b.user_id = renter.id
        JOIN users owner ON m.user_id = owner.id
        WHERE b.id = ? AND (b.user_id = ? OR m.user_id = ?)";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("iii", $booking_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: my_bookings.php?error=not_found");
    exit;
}

$booking = $result->fetch_assoc();

function formatDate($dateString)
{
    if (!$dateString) return "N/A";
    $date = new DateTime($dateString);
    return $date->format('d/m/Y H:i:s');
}


$successMessage = '';
if (isset($_SESSION['flash_message'])) {
    $successMessage = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

$isRenter = ($user_id == $booking['user_id']);

$motelImage = '';
if (!empty($booking['motel_images'])) {
    // Kiểm tra nếu đường dẫn đã bắt đầu bằng http hoặc https
    if (strpos($booking['motel_images'], 'http') === 0) {
        $motelImage = $booking['motel_images'];
    } else {
        // Nếu là đường dẫn tương đối, thêm đường dẫn đúng
        $motelImage = '../uploads/' . basename($booking['motel_images']);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đặt phòng #<?php echo $booking_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .status-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }

        .booking-image {
            max-height: 250px;
            object-fit: cover;
        }

        .detail-row {
            margin-bottom: 10px;
        }

        .section-divider {
            margin: 30px 0 20px;
            border-top: 1px solid #e5e5e5;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($successMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?> <div class="row mb-4">
            <div class="col">
                <h1 class="mb-4 fw-bold text-primary">
                    <i class="fas fa-file-invoice me-2"></i>Chi tiết đặt phòng #<?php echo $booking_id; ?>
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb p-3 bg-light rounded shadow-sm">
                        <li class="breadcrumb-item">
                            <a href="../index.php" class="text-decoration-none">
                                <i class="fas fa-home me-1"></i>Trang chủ
                            </a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="my_bookings.php" class="text-decoration-none">
                                <i class="fas fa-list-alt me-1"></i>Đặt phòng của tôi
                            </a>
                        </li>
                        <li class="breadcrumb-item active fw-bold" aria-current="page">
                            <i class="fas fa-info-circle me-1"></i>Chi tiết đặt phòng #<?php echo $booking_id; ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-bookmark me-2"></i>Thông tin đặt phòng
                </h5> <?php
                        $statusClass = '';
                        switch ($booking['status']) {
                            case 'PENDING':
                                $statusClass = 'bg-warning';
                                break;
                            case 'SUCCESS':
                                $statusClass = 'bg-success';
                                break;
                            case 'FAILED':
                                $statusClass = 'bg-danger';
                                break;
                            case 'REFUND_REQUESTED':
                                $statusClass = 'bg-info';
                                break;
                            case 'RELEASED':
                                $statusClass = 'bg-primary';
                                break;
                            case 'REFUNDED':
                                $statusClass = 'bg-secondary';
                                break;
                        }
                        ?> <span class="badge <?php echo $statusClass; ?> status-badge">
                    <?php
                    switch ($booking['status']) {
                        case 'PENDING':
                            echo 'Đang chờ xử lý';
                            break;
                        case 'SUCCESS':
                            echo 'Đặt cọc thành công';
                            break;
                        case 'FAILED':
                            echo 'Đặt cọc thất bại';
                            break;
                        case 'REFUND_REQUESTED':
                            echo 'Yêu cầu hoàn tiền';
                            break;
                        case 'RELEASED':
                            echo 'Đã giải ngân';
                            break;
                        case 'REFUNDED':
                            echo 'Đã hoàn tiền';
                            break;
                        default:
                            echo $booking['status'];
                    }
                    ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Motel Information Column -->
                    <div class="col-md-5">
                        <div class="mb-4">
                            <?php if (!empty($motelImage)): ?>
                                <img src="<?php echo htmlspecialchars($motelImage); ?>" class="img-fluid rounded booking-image w-100" alt="Room Image">
                            <?php else: ?>
                                <div class="bg-light rounded booking-image d-flex align-items-center justify-content-center">
                                    <p class="text-muted">No image available</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h4 class="mb-3"><?php echo htmlspecialchars($booking['motel_title']); ?></h4>

                        <div class="detail-row">
                            <strong><i class="fas fa-money-bill-wave me-2"></i>Giá phòng:</strong>
                            <span class="text-primary fw-bold"><?php echo number_format($booking['motel_price']); ?> VNĐ/tháng</span>
                        </div>

                        <div class="detail-row">
                            <strong><i class="fas fa-hand-holding-usd me-2"></i>Tiền đặt cọc:</strong>
                            <span class="text-danger fw-bold"><?php echo number_format($booking['deposit_amount']); ?> VNĐ</span>
                        </div>

                        <div class="detail-row">
                            <strong><i class="fas fa-percentage me-2"></i>Phí hoa hồng:</strong>
                            <span><?php echo $booking['commission_pct']; ?>%</span>
                        </div>
                    </div>

                    <!-- Booking Details Column -->
                    <div class="col-md-7">
                        <div class="card border-light mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3"><i class="fas fa-calendar-alt me-2"></i>Thông tin thời gian</h5>

                                <div class="detail-row">
                                    <strong>Ngày đặt:</strong>
                                    <span><?php echo formatDate($booking['created_at']); ?></span>
                                </div>

                                <div class="detail-row">
                                    <strong>Cập nhật lần cuối:</strong>
                                    <span><?php echo formatDate($booking['updated_at']); ?></span>
                                </div>

                                <?php if ($booking['status'] == 'REFUND_REQUESTED' && $booking['request_refund_at']): ?>
                                    <div class="detail-row">
                                        <strong>Yêu cầu hoàn tiền lúc:</strong>
                                        <span><?php echo formatDate($booking['request_refund_at']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Renter Information -->
                            <div class="col-md-6">
                                <div class="card border-light mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3"><i class="fas fa-user me-2"></i>Người thuê</h5>
                                        <p class="mb-1"><strong>Tên:</strong> <?php echo htmlspecialchars($booking['renter_name']); ?></p>
                                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($booking['renter_email']); ?></p>
                                        <p class="mb-0"><strong>SĐT:</strong> <?php echo htmlspecialchars($booking['renter_phone']); ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Owner Information -->
                            <div class="col-md-6">
                                <div class="card border-light mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3"><i class="fas fa-home me-2"></i>Chủ trọ</h5>
                                        <p class="mb-1"><strong>Tên:</strong> <?php echo htmlspecialchars($booking['owner_name']); ?></p>
                                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($booking['owner_email']); ?></p>
                                        <p class="mb-0"><strong>SĐT:</strong> <?php echo htmlspecialchars($booking['owner_phone']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transaction ID if available -->
                        <?php if (!empty($booking['vnp_transaction_id'])): ?>
                            <div class="detail-row">
                                <strong><i class="fas fa-receipt me-2"></i>Mã giao dịch:</strong>
                                <span class="text-monospace"><?php echo htmlspecialchars($booking['vnp_transaction_id']); ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Status-specific information and actions -->
                        <div class="section-divider"></div>
                        <div class="mt-3">
                            <?php if ($isRenter): ?>
                                <?php if ($booking['status'] == 'SUCCESS'): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>Bạn đã đặt cọc thành công, vui lòng liên hệ chủ trọ để sắp xếp ngày nhận phòng.
                                    </div>
                                    <div class="d-flex gap-2">
                                        <form action="booking_action.php" method="post">
                                            <input type="hidden" name="bookingId" value="<?php echo $booking_id; ?>">
                                            <input type="hidden" name="action" value="refund">
                                            <button type="submit" class="btn btn-warning">
                                                <i class="fas fa-undo me-2"></i>Yêu cầu hoàn tiền
                                            </button>
                                        </form>

                                        <form action="booking_action.php" method="post">
                                            <input type="hidden" name="bookingId" value="<?php echo $booking_id; ?>">
                                            <input type="hidden" name="action" value="release">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-check-circle me-2"></i>Xác nhận đã thuê
                                            </button>
                                        </form>
                                    </div>
                                <?php elseif ($booking['status'] == 'REFUND_REQUESTED'): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-clock me-2"></i>Đang chờ xử lý hoàn tiền
                                    </div>
                                <?php elseif ($booking['status'] == 'REFUNDED'): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i>Hoàn tiền xong
                                    </div>
                                <?php elseif ($booking['status'] == 'RELEASED'): ?>
                                    <div class="alert alert-dark">
                                        <i class="fas fa-check-double me-2"></i>Đã giải ngân tiền cọc
                                    </div>
                                <?php elseif ($booking['status'] == 'PENDING'): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-spinner me-2"></i>Đang chờ xác nhận thanh toán
                                    </div>
                                <?php elseif ($booking['status'] == 'FAILED'): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-times-circle me-2"></i>Đặt cọc thất bại
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Content for motel owner -->
                                <?php if ($booking['status'] == 'SUCCESS'): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>Người thuê đã đặt cọc thành công, vui lòng liên hệ để sắp xếp ngày nhận phòng.
                                    </div>
                                <?php elseif ($booking['status'] == 'REFUND_REQUESTED'): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-clock me-2"></i>Người thuê đã yêu cầu hoàn tiền. Hệ thống sẽ xử lý trong vòng 24 giờ.
                                    </div>
                                <?php elseif ($booking['status'] == 'REFUNDED'): ?>
                                    <div class="alert alert-secondary">
                                        <i class="fas fa-undo me-2"></i>Đã hoàn tiền cho người thuê.
                                    </div>
                                <?php elseif ($booking['status'] == 'RELEASED'): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-double me-2"></i>Đã giải ngân tiền cọc.
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
// Close the database connection
$mysqli->close();
?>