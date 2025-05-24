<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once PROJECT_ROOT . '/config/config.php';
$user_id = $_SESSION['user_id'] ?? 0;
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM notifications 
    WHERE user_id = ? AND is_read = 0
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($unreadCount);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("
    SELECT id, title, message, created_at, is_read
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 2
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<link rel="stylesheet" href="/assets/client/css/style.css">
<header>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class=" container">
            <a class="navbar-brand text-white me-5" href="/">
                <i class="fas fa-home me-2"></i>F4 Case Study
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="text-white nav-link" href="/">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="text-white nav-link" href="/room/search.php">Tìm kiếm</a>
                    </li>
                    <li class="nav-item">
                        <a class="text-white nav-link" href="/room/post.php">Đăng tin</a>
                    </li>
                    <li class="nav-item">
                        <a class="text-white nav-link" href="/room/my_rooms.php">

                            <i class="fas fa-heart me-1 text-danger"></i>Yêu thích
                            <?php
                            // Đếm số lượng yêu thích từ cơ sở dữ liệu
                            $favorite_count = 0;
                            if (isset($_SESSION['user_id'])) {
                                $user_id = $_SESSION['user_id'];
                                $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM user_wishlist WHERE user_id = ?");
                                $count_stmt->bind_param("i", $user_id);
                                $count_stmt->execute();
                                $count_result = $count_stmt->get_result();
                                if ($count_row = $count_result->fetch_assoc()) {
                                    $favorite_count = $count_row['total'];
                                }
                            }

                            if ($favorite_count > 0):
                            ?>
                                <span
                                    class="badge rounded-pill bg-danger favorite-counter animate__animated <?php echo isset($_GET['action']) && in_array($_GET['action'], ['favorite', 'unfavorite']) ? 'animate__heartBeat' : ''; ?>">
                                    <?php echo $favorite_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <!-- Notification dropdown -->
                    <li class="nav-item dropdown mt-3">
                        <a class="nav-link position-relative" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell fa-lg"></i>
                            <?php if ($unreadCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                                    <?= $unreadCount ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notifDropdown">
                            <div class="notification-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-bell fa-lg me-2"></i>
                                        <h6 class="mb-0">Thông báo của bạn</h6>
                                    </div>
                                    <?php if ($unreadCount > 0): ?>
                                        <span class="badge bg-light text-primary"><?= $unreadCount ?> mới</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="notifications-body" style="max-height: 400px; overflow-y: auto;">
                                <?php if (empty($notes)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-bell-slash fa-2x text-muted mb-3"></i>
                                        <p class="text-muted mb-0">Chưa có thông báo nào</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($notes as $note): ?>
                                        <a href="/room/notification_detail.php?id=<?= $note['id'] ?>"
                                            class="notification-item d-block text-decoration-none <?= !$note['is_read'] ? 'unread' : '' ?>">
                                            <div class="d-flex align-items-center">
                                                <div class="notification-icon">
                                                    <i class="fas <?= !$note['is_read'] ? 'fa-bell' : 'fa-check' ?>"></i>
                                                </div>
                                                <div class="ms-3">
                                                    <h6 class="mb-1 text-dark <?= !$note['is_read'] ? 'fw-bold' : '' ?>">
                                                        <?= htmlspecialchars($note['title']) ?>
                                                    </h6>
                                                    <p class="text-muted small text-truncate mb-1" style="max-width: 250px;">
                                                        <?= htmlspecialchars($note['message']) ?>
                                                    </p>
                                                    <div class="d-flex align-items-center">
                                                        <i class="far fa-clock text-muted me-1"></i>
                                                        <small class="text-muted">
                                                            <?= date('H:i d/m/Y', strtotime($note['created_at'])) ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($notes)): ?>
                                <div class="p-2 border-top text-center">
                                    <a href="/room/notifications.php" class="btn btn-light btn-sm w-100">
                                        <i class="fas fa-list-ul me-1"></i>
                                        Xem tất cả thông báo
                                    </a>
                                </div>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdown" role="button"
                            data-bs-toggle="dropdown">
                            <?php
                            // Lấy thông tin người dùng
                            $user_id = $_SESSION['user_id'];
                            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $user = $result->fetch_assoc();

                            echo '<img src="/' . $user['avatar'] . '" class="avatar-header me-2" alt="Avatar">';
                            echo htmlspecialchars($user['name']);
                            ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../auth/edit_profile.php"><i class="fas fa-user-edit me-2"></i>Chỉnh sửa hồ sơ</a></li>
                            <li><a class="dropdown-item" href="/room/my_rooms.php"><i class="fas fa-heart me-2"></i>Danh sách yêu thích</a></li>
                            <li><a class="dropdown-item" href="/room/my_posted_rooms.php"><i class="fas fa-list me-2"></i>Phòng trọ đã đăng</a></li>
                            <li><a class="dropdown-item" href="/room/my_bookings.php"><i class="fas fa-bookmark me-2"></i>Phòng đã đặt cọc</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 1): ?>
                                <li><a class="dropdown-item" href="../admin/index.php"><i class="fas fa-cogs me-2"></i>Quản lý admin</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>