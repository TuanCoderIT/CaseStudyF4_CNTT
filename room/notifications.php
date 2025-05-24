<?php
session_start();
require_once __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'] ?? 0;

// Đánh dấu tất cả đã đọc khi vào trang
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");

// Lấy danh sách thông báo
$stmt = $conn->prepare("
    SELECT id, title, message, created_at 
    FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Thông báo của bạn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f0f2f5;
        }

        .notifications-card {
            max-width: 800px;
            margin: 2rem auto;
            border: none;
            border-radius: .75rem;
            overflow: hidden;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            margin-top: 7rem !important;
        }

        .notifications-header {
            background: linear-gradient(90deg, #667eea, #764ba2);
            color: #fff;
        }

        .notification-item {
            transition: background .2s;
        }

        .notification-item:hover {
            background: #f8f9fa;
        }

        .notification-title {
            font-weight: 500;
            color: #343a40;
        }

        .notification-time {
            font-size: .85rem;
            color: #6c757d;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <?php include  __DIR__ . '/../components/header.php'; ?>

    <div class="card notifications-card">
        <div class="card-header notifications-header d-flex align-items-center">
            <i class="fas fa-bell fa-lg me-3"></i>
            <h3 class="mb-0 text-light">Thông báo của bạn</h3>
        </div>
        <div class="card-body p-0">
            <?php if (empty($notes)): ?>
                <div class="empty-state">
                    <i class="far fa-inbox fa-4x mb-3"></i>
                    <p class="lead">Bạn chưa có thông báo nào.</p>
                </div>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($notes as $note): ?>
                        <li class="list-group-item notification-item px-4 py-3">
                            <a href="notification_detail.php?id=<?= $note['id'] ?>" class="text-decoration-none d-flex justify-content-between">
                                <div>
                                    <div class="notification-title">
                                        <?= htmlspecialchars($note['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="small text-truncate" style="max-width: 600px;">
                                        <?= nl2br(htmlspecialchars($note['message'], ENT_QUOTES, 'UTF-8')) ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="notification-time">
                                        <i class="far fa-clock me-1"></i>
                                        <?= date('H:i d/m/Y', strtotime($note['created_at'])) ?>
                                    </div>
                                    <?php if (!$note['is_read']): ?>
                                        <span class="badge bg-primary mt-1">Mới</span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php if (!empty($notes)): ?>
            <div class="card-footer text-center">
                <a href="/room/notifications.php" class="text-decoration-none">Xem lại trang</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>

</html>