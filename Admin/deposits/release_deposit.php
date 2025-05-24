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
        u.name AS renter_name, 
        u.email AS renter_email,
        o.name AS owner_name,
        o.email AS owner_email,
        o.id AS owner_id
        FROM bookings b
        JOIN motel m ON b.motel_id = m.id
        JOIN users u ON b.user_id = u.id
        JOIN users o ON m.user_id = o.id
        WHERE b.id = ? AND b.status = 'SUCCESS'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_deposits.php?error=invalid_status");
    exit;
}

$booking = $result->fetch_assoc();
$admin_id = $_SESSION['user_id'];

// Xử lý khi form được gửi đi
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['confirm_release'])) {

        // Bắt đầu giao dịch
        $conn->begin_transaction();

        try {
            // Cập nhật trạng thái đặt cọc thành RELEASED
            $update_sql = "UPDATE bookings SET status = 'RELEASED', updated_at = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $booking_id);
            $update_stmt->execute();

            // Tạo thông báo cho người thuê
            $renter_title = "Tiền cọc đã được giải ngân";
            $renter_message = "Tiền cọc của bạn cho phòng \"{$booking['motel_title']}\" đã được giải ngân cho chủ trọ.";

            $notif_sql = "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)";
            $notif_stmt = $conn->prepare($notif_sql);
            $notif_stmt->bind_param("iss", $booking['user_id'], $renter_title, $renter_message);
            $notif_stmt->execute();

            // Tạo thông báo cho chủ trọ
            $owner_title = "Tiền cọc đã được giải ngân";
            $owner_message = "Tiền cọc từ " . htmlspecialchars($booking['renter_name']) . " cho phòng \"" . htmlspecialchars($booking['motel_title']) . "\" đã được giải ngân vào tài khoản của bạn.";

            $notif_sql = "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)";
            $notif_stmt = $conn->prepare($notif_sql);
            $notif_stmt->bind_param("iss", $booking['owner_id'], $owner_title, $owner_message);
            $notif_stmt->execute();

            // Lưu log hành động (nếu cần)
            // ...

            // Commit giao dịch
            $conn->commit();
            $success = true;
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $conn->rollback();
            $error = 'Đã xảy ra lỗi: ' . $e->getMessage();
        }
    }
}

function formatCurrency($amount)
{
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giải ngân tiền cọc #<?= $booking_id ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="../../assets/admin/css/admin.css" rel="stylesheet">
    <style>
        .release-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .release-amount {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }

        .commission-amount {
            font-size: 18px;
            color: #6c757d;
        }

        .release-notice {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
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
                        <h1 class="h2">Giải ngân tiền cọc #<?= $booking_id ?></h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="manage_deposits.php">Quản lý tiền cọc</a></li>
                                <li class="breadcrumb-item"><a href="view_deposit.php?id=<?= $booking_id ?>">Chi tiết tiền cọc #<?= $booking_id ?></a></li>
                                <li class="breadcrumb-item active">Giải ngân tiền cọc</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <h4 class="alert-heading"><i class="fas fa-check-circle me-2"></i>Giải ngân thành công!</h4>
                        <p>Tiền cọc đã được giải ngân thành công cho chủ trọ.</p>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <p class="mb-0">Thông báo đã được gửi đến người thuê và chủ trọ.</p>
                            <div>
                                <a href="view_deposit.php?id=<?= $booking_id ?>" class="btn btn-sm btn-outline-success me-2">Xem chi tiết</a>
                                <a href="manage_deposits.php" class="btn btn-sm btn-outline-primary">Quay lại danh sách</a>
                            </div>
                        </div>
                    </div>
                <?php elseif (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Có lỗi xảy ra!</h4>
                        <p><?= $error ?></p>
                        <hr>
                        <p class="mb-0">Vui lòng thử lại hoặc liên hệ với kỹ thuật viên.</p>
                    </div>
                <?php else: ?>
                    <div class="card shadow">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Xác nhận giải ngân tiền cọc</h5>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6>Thông tin phòng:</h6>
                                    <p class="mb-1"><strong>Tên phòng:</strong> <?= htmlspecialchars($booking['motel_title']) ?></p>
                                    <p class="mb-1"><strong>Giá phòng:</strong> <?= formatCurrency($booking['motel_price']) ?>/tháng</p>
                                    <p><strong>Tiền cọc:</strong> <?= formatCurrency($booking['deposit_amount']) ?></p>
                                </div>

                                <div class="col-md-6">
                                    <h6>Thông tin người thuê:</h6>
                                    <p class="mb-1"><strong>Họ tên:</strong> <?= htmlspecialchars($booking['renter_name']) ?></p>
                                    <p><strong>Email:</strong> <?= htmlspecialchars($booking['renter_email']) ?></p>

                                    <h6 class="mt-3">Thông tin chủ trọ:</h6>
                                    <p class="mb-1"><strong>Họ tên:</strong> <?= htmlspecialchars($booking['owner_name']) ?></p>
                                    <p><strong>Email:</strong> <?= htmlspecialchars($booking['owner_email']) ?></p>
                                </div>
                            </div>

                            <div class="release-info text-center">
                                <h5>Thông tin giải ngân</h5>
                                <div class="release-amount mb-2">
                                    <?= formatCurrency($booking['deposit_amount']) ?>
                                </div>
                                <div class="commission-amount mb-3">
                                    Phí hoa hồng: <?= $booking['commission_pct'] ?>% (<?= formatCurrency($booking['deposit_amount'] * $booking['commission_pct'] / 100) ?>)
                                </div>
                                <div class="text-muted">
                                    <small>Số tiền thực nhận: <?= formatCurrency($booking['deposit_amount'] - ($booking['deposit_amount'] * $booking['commission_pct'] / 100)) ?></small>
                                </div>
                            </div>

                            <div class="release-notice">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-exclamation-circle me-2 text-warning"></i>
                                    <strong>Lưu ý quan trọng:</strong>
                                </div>
                                <p class="mb-0">Sau khi giải ngân, tiền cọc sẽ được chuyển cho chủ trọ và không thể hoàn tác. Hãy đảm bảo rằng người thuê đã xác nhận việc thuê phòng.</p>
                            </div>

                            <form method="post" class="mt-4">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="confirmCheck" required>
                                    <label class="form-check-label" for="confirmCheck">
                                        Tôi xác nhận rằng thông tin trên là chính xác và muốn giải ngân tiền cọc cho chủ trọ.
                                    </label>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="view_deposit.php?id=<?= $booking_id ?>" class="btn btn-outline-secondary">Hủy bỏ</a>
                                    <button type="submit" name="confirm_release" class="btn btn-success">
                                        <i class="fas fa-check-circle me-2"></i>Xác nhận giải ngân
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>