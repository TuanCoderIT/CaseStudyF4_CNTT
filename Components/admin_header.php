<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Trang quản trị'; ?> - Hệ thống quản lý phòng trọ</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Determine the base path for assets -->

    <!-- admin Custom CSS -->
    <link rel="stylesheet" href="/assets/admin/css/admin.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>

<body>
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <a class="sidebar-brand d-flex align-items-center" href="/admin/index.php">
            <i class="fas fa-home mr-2"></i>
            <div>Admin Phòng Trọ</div>
        </a>

        <hr class="sidebar-divider my-0" style="border-color: rgba(255,255,255,0.15)">

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="/admin/index.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Bảng điều khiển</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_rooms.php' ? 'active' : ''; ?>" href="/admin/rooms/manage_rooms.php">
                    <i class="fas fa-fw fa-building"></i>
                    <span>Quản lý phòng trọ</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_deposits.php' ? 'active' : ''; ?>" href="/admin/deposits/manage_deposits.php">
                    <i class="fas fa-fw fa-building"></i>
                    <span>Quản lý tiền cọc</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pending_rooms.php' ? 'active' : ''; ?>" href="/admin/rooms/pending_rooms.php">
                    <i class="fas fa-fw fa-clipboard-check"></i>
                    <span>Duyệt phòng trọ</span>
                    <?php
                    // Đếm số phòng chờ duyệt
                    $pending_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM motel WHERE approve = 0");
                    $count_data = mysqli_fetch_assoc($pending_count);
                    if ($count_data['count'] > 0):
                    ?>
                        <span class="badge badge-pill badge-warning ml-2"><?php echo $count_data['count']; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_categories.php' ? 'active' : ''; ?>" href="/admin/categories/manage_categories.php">
                    <i class="fas fa-fw fa-list"></i>
                    <span>Quản lý danh mục</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_districts.php' ? 'active' : ''; ?>" href="/admin/districts/manage_districts.php">
                    <i class="fas fa-fw fa-map-marker-alt"></i>
                    <span>Quản lý khu vực</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_users.php' || basename($_SERVER['PHP_SELF']) == 'user_rooms.php' ? 'active' : ''; ?>" href="/admin/users/manage_users.php">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Quản lý người dùng</span>
                </a>
            </li>

            <hr class="sidebar-divider d-none d-md-block mt-3 mb-2" style="border-color: rgba(255,255,255,0.15)">

            <li class="nav-item">

                <a class="nav-link" href="/" target="_blank">

                    <i class="fas fa-fw fa-external-link-alt"></i>
                    <span>Xem trang chủ</span>
                </a>
            </li>
            <li class="nav-item">

                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="/admin/profile.php">

                    <i class="fas fa-fw fa-user-circle"></i>
                    <span>Hồ sơ của tôi</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/auth/logout.php">
                    <i class="fas fa-fw fa-sign-out-alt"></i>
                    <span>Đăng xuất</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Top Bar -->
    <div class="admin-topbar">
        <!-- Mobile Sidebar Toggle -->
        <button id="sidebarToggleBtn" class="btn btn-link d-md-none rounded-circle mr-3">
            <i class="fa fa-bars"></i>
        </button>

        <!-- User Info -->
        <?php
        $admin_id = $_SESSION['user_id'];
        $admin_query = mysqli_query($conn, "SELECT * FROM users WHERE id = '$admin_id'");
        $admin = mysqli_fetch_assoc($admin_query);
        ?>
        <div class="user-info">

            <a href="/admin/profile.php" class="text-decoration-none" title="Xem hồ sơ">

                <span class="mr-2 d-none d-lg-inline text-gray-600"><?php echo $admin['name']; ?></span>
                <?php if (!empty($admin['avatar'])): ?>
                    <?php
                    $avatar_path = $admin['avatar'];
                    if (strpos($avatar_path, '../') === 0) {
                        $avatar_path = substr($avatar_path, 3);
                    }
                    ?>
                    <img class="user-avatar" src="/<?php echo htmlspecialchars($avatar_path); ?>" alt="Avatar">
                <?php else: ?>
                    <img class="user-avatar" src="/uploads/avatar/default-avatar.jpg" alt="Avatar">
                <?php endif; ?>
            </a>
        </div>
    </div>
    Main Content
    <div class="admin-content">

        <div class="container-fluid">