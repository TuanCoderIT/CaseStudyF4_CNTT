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
                            <li><a class="dropdown-item" href="../Auth/edit_profile.php"><i
                                        class="fas fa-user-edit me-2"></i>Chỉnh sửa hồ sơ</a></li>
                            <li><a class="dropdown-item" href="my_rooms.php"><i class="fas fa-list me-2"></i>Phòng trọ
                                    của tôi</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="../Auth/logout.php"><i
                                        class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>