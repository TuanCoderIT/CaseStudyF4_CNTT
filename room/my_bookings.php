<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    header('Location: ../Auth/login.php');
    exit;
}

// 1. Lấy danh sách booking của user
$stmt = $conn->prepare("
  SELECT 
    b.id             AS booking_id,
    b.deposit_amount AS deposit_amount,
    b.status         AS status,
    b.created_at     AS booked_at,
    m.id             AS motel_id,
    m.title          AS title,
    m.price          AS price,
    m.default_deposit AS default_deposit,
    m.images         AS images
  FROM bookings b
  JOIN motel m ON m.id = b.motel_id
  WHERE b.user_id = ?
  ORDER BY b.created_at DESC
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Phòng đã đặt cọc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #f72585;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --light-text: #6c757d;
            --card-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            --hover-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
            --header-gradient: linear-gradient(45deg, #4361ee, #3a0ca3);
            --success-color: #38b000;
            --warning-color: #ffaa00;
            --info-color: #4cc9f0;
            --danger-color: #d90429;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
            line-height: 1.6;
        }

        .page-header {
            background: var(--header-gradient);
            padding: 3rem 0;
            margin-bottom: 2.5rem;
            margin-top: 3rem;
            position: relative;
            overflow: hidden;
            color: white;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .page-header::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            bottom: -150px;
            right: -100px;
        }

        .page-header::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            top: -100px;
            left: -50px;
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .header-title {
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 2.5rem;
        }

        .header-subtitle {
            opacity: 0.9;
            font-weight: 300;
            font-size: 1.1rem;
        }

        .booking-counter {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 0.5rem 1rem;
            display: inline-block;
            margin-top: 1rem;
            font-weight: 500;
        }

        .booking-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            height: 100%;
            background-color: white;
            display: flex;
            flex-direction: column;
        }

        .booking-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--hover-shadow);
        }

        .card-img-container {
            position: relative;
            overflow: hidden;
            height: 200px;
            flex-shrink: 0;
        }

        .booking-thumb {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .booking-card:hover .booking-thumb {
            transform: scale(1.05);
        }

        .status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.4rem 1rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }

        .card-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .card-title {
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            color: var(--dark-text);
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.4;
            height: 3.1rem;
        }

        .price-section {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1.2rem;
            padding: 1rem;
            background-color: rgba(67, 97, 238, 0.05);
            border-radius: 10px;
        }

        .price-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .price-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--light-text);
        }

        .price-value {
            font-weight: 700;
            font-size: 1rem;
            color: var(--primary-color);
        }

        .monthly-price {
            font-size: 1.2rem;
        }

        .booking-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--light-text);
            font-size: 0.85rem;
            margin-bottom: 1.2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .button-group {
            display: flex;
            gap: 0.75rem;
            margin-top: auto;
        }

        .btn {
            border-radius: 10px;
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.85rem;
            letter-spacing: 0.3px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-purple {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .btn-purple:hover {
            background: linear-gradient(45deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
            color: white;
        }

        .btn-refund {
            background: linear-gradient(45deg, var(--accent-color), #ff6b9d);
            color: white;
        }

        .btn-refund:hover {
            background: linear-gradient(45deg, #ff6b9d, var(--accent-color));
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
            color: white;
        }

        .btn-info {
            background: linear-gradient(45deg, var(--info-color), #4361ee);
            color: white;
        }

        .btn-info:hover {
            background: linear-gradient(45deg, #4361ee, var(--info-color));
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
            color: white;
        }

        border-radius: 30px;
        padding: 0.6rem 1.2rem;
        font-weight: 500;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .btn-purple {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }

        .btn-purple:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
            color: white;
        }

        .btn-refund {
            background-color: white;
            border: 1px solid var(--warning-color);
            color: var(--warning-color);
        }

        .btn-refund:hover {
            background-color: var(--warning-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 170, 0, 0.2);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background-color: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--primary-color);
            opacity: 0.7;
            margin-bottom: 1.5rem;
        }

        .empty-title {
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--dark-text);
        }

        .empty-description {
            color: var(--light-text);
            margin-bottom: 2rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        .divider {
            height: 1px;
            background-color: rgba(0, 0, 0, 0.05);
            margin: 1.5rem 0;
        }

        .footer {
            background-color: white;
            padding: 1.5rem 0;
            text-align: center;
            color: var(--light-text);
            margin-top: 3rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        /* Màu cho badge trạng thái */
        .badge.bg-success {
            background-color: var(--success-color) !important;
        }

        .badge.bg-warning {
            background-color: var(--warning-color) !important;
        }

        .badge.bg-info {
            background-color: var(--info-color) !important;
        }

        .badge.bg-secondary {
            background-color: var(--light-text) !important;
        }

        .badge.bg-dark {
            background-color: var(--dark-text) !important;
        }

        .badge.bg-danger {
            background-color: var(--danger-color) !important;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="page-header">
        <div class="container">
            <div class="header-content">
                <h1 class="header-title">Phòng đã đặt cọc</h1>
                <p class="header-subtitle">Quản lý tất cả các phòng bạn đã đặt cọc</p>
                <?php if (!empty($bookings)): ?>
                    <div class="booking-counter">
                        <i class="fas fa-list-check me-2"></i>
                        Tổng cộng: <?= count($bookings) ?> đơn đặt cọc
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container py-3">
        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <i class="fas fa-home empty-icon pulse-animation"></i>
                <h3 class="empty-title">Bạn chưa có đơn đặt cọc nào</h3>
                <p class="empty-description">
                    Hãy tìm kiếm và đặt cọc phòng để quản lý chúng ở đây.
                    Đặt cọc phòng giúp bạn đảm bảo quyền thuê phòng.
                </p>
                <a href="../index.php" class="btn btn-purple">
                    <i class="fas fa-search me-2"></i> Tìm phòng ngay
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4"> <?php foreach ($bookings as $b):
                                        $map = [
                                            'PENDING'             => ['warning', 'Chờ thanh toán'],
                                            'SUCCESS'             => ['success', 'Đã đặt cọc'],
                                            'FAILED'              => ['danger', 'Đặt cọc thất bại'],
                                            'REFUND_REQUESTED'    => ['info', 'Đã yêu cầu hoàn tiền'],
                                            'REFUNDED'            => ['secondary', 'Đã hoàn tiền'],
                                            'RELEASED'            => ['primary', 'Đã giải ngân'],
                                            'AWAITING_CONFIRMATION' => ['info', 'Chờ xác nhận'],
                                            'CONFIRMED_RENTAL'    => ['success', 'Đã xác nhận thuê'],
                                            'AUTO_REFUNDED'       => ['warning', 'Tự động hoàn tiền'],
                                        ];
                                        [$cls, $label] = $map[$b['status']] ?? ['dark', $b['status']];
                                        // chọn ảnh thumbnail (lấy file đầu tiên trong images comma-separated)
                                        $thumb = explode(',', $b['images'])[0] ?? 'placeholder.jpg';

                                        // Tính ngày từ khi đặt cọc
                                        $booked_date = new DateTime($b['booked_at']);
                                        $now = new DateTime();
                                        $days_since = $now->diff($booked_date)->days;
                                        $days_text = $days_since > 0 ? "($days_since ngày trước)" : "(Hôm nay)";
                                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="booking-card">
                            <div class="card-img-container">
                                <img src="../<?= htmlspecialchars(trim($thumb), ENT_QUOTES) ?>"
                                    class="booking-thumb" alt="Ảnh phòng trọ">
                                <span class="badge bg-<?= $cls ?> status-badge">
                                    <?php                                    // Icon cho từng loại trạng thái
                                        $statusIcons = [
                                            'PENDING' => 'fa-clock',
                                            'SUCCESS' => 'fa-check-circle',
                                            'FAILED' => 'fa-times',
                                            'REFUND_REQUESTED' => 'fa-rotate-left',
                                            'REFUNDED' => 'fa-undo-alt',
                                            'RELEASED' => 'fa-money-bill-transfer',
                                            'AWAITING_CONFIRMATION' => 'fa-handshake',
                                            'CONFIRMED_RENTAL' => 'fa-house-circle-check',
                                            'AUTO_REFUNDED' => 'fa-clock-rotate-left'
                                        ];
                                        $icon = $statusIcons[$b['status']] ?? 'fa-info-circle';
                                    ?>
                                    <i class="fas <?= $icon ?> me-1"></i> <?= $label ?>
                                </span>
                            </div>

                            <div class="card-body">
                                <h5 class="card-title" title="<?= htmlspecialchars($b['title'], ENT_QUOTES) ?>">
                                    <?= htmlspecialchars($b['title'], ENT_QUOTES) ?>
                                </h5>

                                <div class="price-section">
                                    <div class="price-item">
                                        <div class="price-label">
                                            <i class="fas fa-money-bill-wave"></i>
                                            Tiền đặt cọc
                                        </div>
                                        <div class="price-value">
                                            <?= number_format($b['deposit_amount'], 0, ',', '.') ?>₫
                                        </div>
                                    </div>

                                    <div class="price-item">
                                        <div class="price-label">
                                            <i class="fas fa-home"></i>
                                            Giá phòng hàng tháng
                                        </div>
                                        <div class="price-value monthly-price">
                                            <?= number_format($b['price'], 0, ',', '.') ?>₫
                                        </div>
                                    </div>
                                </div>

                                <div class="booking-date">
                                    <i class="far fa-calendar-alt"></i>
                                    <span>Đặt ngày: <?= date('d/m/Y H:i', strtotime($b['booked_at'])) ?> <?= $days_text ?></span>
                                </div>
                                <div class="button-group">
                                    <a href="booking_detail.php?bookingId=<?= $b['booking_id'] ?>"
                                        class="btn btn-sm btn-purple flex-fill">
                                        <i class="fas fa-info-circle"></i> Chi tiết
                                    </a>
                                    <?php if ($b['status'] === 'SUCCESS'): ?>
                                        <form action="booking_action.php" method="post" class="flex-fill mb-0">
                                            <input type="hidden" name="bookingId" value="<?= $b['booking_id'] ?>">
                                            <button name="action" value="refund" type="submit"
                                                class="btn btn-sm btn-refund w-100">
                                                <i class="fas fa-undo-alt"></i> Yêu cầu hoàn tiền
                                            </button>
                                        </form>
                                    <?php elseif ($b['status'] === 'AWAITING_CONFIRMATION'): ?>
                                        <a href="confirmation.php?booking_id=<?= $b['booking_id'] ?>"
                                            class="btn btn-sm btn-info flex-fill">
                                            <i class="fas fa-handshake"></i> Xác nhận thuê
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <div class="container">
            <p class="mb-0">© <?= date('Y') ?> Hệ thống đặt phòng trọ | Thiết kế bởi Team F4</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>