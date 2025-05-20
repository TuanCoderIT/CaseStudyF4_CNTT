<link rel="stylesheet" href="../Assets/style.css">
<header>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class=" container">
            <a class="navbar-brand text-white me-5" href="../Home/index.php">
                <i class="fas fa-home me-2"></i>F4 Case Study
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="text-white nav-link" href="index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="text-white nav-link" href="search.php">Tìm kiếm</a>
                    </li>
                    <li class="nav-item">
                        <a class="text-white nav-link" href="post.php">Đăng tin</a>
                    </li>
                    <li class="nav-item">
                        <a class="text-white nav-link" href="my_rooms.php">
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
                                <span class="badge rounded-pill bg-danger favorite-counter animate__animated <?php echo isset($_GET['action']) && in_array($_GET['action'], ['favorite', 'unfavorite']) ? 'animate__heartBeat' : ''; ?>">
                                    <?php echo $favorite_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
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

                            echo '<img src="../' . $user['avatar'] . '" class="avatar-header me-2" alt="Avatar"> ';
                            echo htmlspecialchars($user['name']);
                            ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../Auth/edit_profile.php"><i class="fas fa-user-edit me-2"></i>Chỉnh sửa hồ sơ</a></li>
                            <li><a class="dropdown-item" href="my_rooms.php"><i class="fas fa-heart me-2"></i>Danh sách yêu thích</a></li>
                            <li><a class="dropdown-item" href="my_posted_rooms.php"><i class="fas fa-list me-2"></i>Phòng trọ đã đăng</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 1): ?>
                                <li><a class="dropdown-item" href="../Admin/index.php"><i class="fas fa-cogs me-2"></i>Quản lý admin</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="../Auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>