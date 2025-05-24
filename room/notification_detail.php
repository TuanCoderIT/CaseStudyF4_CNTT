<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy ID thông báo từ URL trực tiếp
$note_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Truy vấn thông báo từ cơ sở dữ liệu
$stmt = $conn->prepare("
    SELECT id, title, message, created_at 
    FROM notifications 
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param('ii', $note_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Kiểm tra xem thông báo có tồn tại không
if ($result->num_rows === 0) {
    echo "<script>alert('Không tìm thấy thông báo với ID = " . $note_id . "');</script>";
    echo "<script>window.location.href = 'notifications.php';</script>";
    exit;
}

// Lấy dữ liệu thông báo
$note = $result->fetch_assoc();
$stmt->close();

$update_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
$update_stmt->bind_param('ii', $note_id, $user_id);
$update_stmt->execute();
$update_stmt->close();

$notification_detail = $note;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chi tiết thông báo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
        }

        body {
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .container_1 {
            margin-top: 7rem !important;
        }

        .notification-detail-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-top: 2rem;
        }

        .notification-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem;
            position: relative;
        }

        .notification-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 40px;
            background: white;
            border-radius: 50% 50% 0 0;
        }

        .notification-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .notification-time {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .notification-content {
            padding: 2rem;
            font-size: 1.1rem;
            line-height: 1.8;
            color: #495057;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary-gradient);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 84, 200, 0.3);
            color: white;
        }

        .actions {
            padding: 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>

<body>
    <?php
    // Include header
    include __DIR__ . '/../components/header.php';

    $note = $notification_detail;
    ?>

    <div class="container container_1">
        <div class="notification-detail-card">
            <div class="notification-header">
                <h1 class="notification-title">
                    <?= htmlspecialchars($note['title'], ENT_QUOTES, 'UTF-8') ?>
                </h1>
                <div class="notification-time">
                    <i class="far fa-clock me-2"></i>
                    <?= date('H:i d/m/Y', strtotime($note['created_at'])) ?>
                </div>
            </div>

            <div class="notification-content">
                <?= nl2br(htmlspecialchars($note['message'], ENT_QUOTES, 'UTF-8')) ?>
            </div>

            <div class="actions text-center">
                <a href="notifications.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Quay lại danh sách
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>