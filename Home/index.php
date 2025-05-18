<?php
// Khởi tạo phiên làm việc
session_start();

// Kiểm tra xem người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Auth/login.php');
    exit;
}

// Kết nối đến CSDL
require_once('../config/db.php');

// Lấy danh sách phòng trọ xem nhiều nhất
$stmt_most_viewed = $conn->prepare("
    SELECT m.*, u.name as owner_name 
    FROM motel m 
    LEFT JOIN users u ON m.user_id = u.id 
    WHERE m.approve = 1 
    ORDER BY m.count_view DESC 
    LIMIT 6
");
$stmt_most_viewed->execute();
$most_viewed_rooms = $stmt_most_viewed->get_result();

// Lấy danh sách phòng trọ mới đăng tải
$stmt_newest = $conn->prepare("
    SELECT m.*, u.name as owner_name 
    FROM motel m 
    LEFT JOIN users u ON m.user_id = u.id 
    WHERE m.approve = 1 
    ORDER BY m.created_at DESC 
    LIMIT 6
");
$stmt_newest->execute();
$newest_rooms = $stmt_newest->get_result();

// Lấy danh sách phòng trọ gần trường ĐH Vinh
$stmt_nearest = $conn->prepare("
    SELECT m.*, u.name as owner_name 
    FROM motel m 
    LEFT JOIN users u ON m.user_id = u.id 
    WHERE m.approve = 1 
    ORDER BY CAST(m.latlng AS DECIMAL(10,6)) 
    LIMIT 6
");
$stmt_nearest->execute();
$nearest_rooms = $stmt_nearest->get_result();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ - Phòng trọ sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Assets/style.css">
    <!-- Link tới thư viện Swiper cho slider -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
</head>
<body class="home-body">
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
            <div class="container">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-home me-2"></i>Phòng trọ sinh viên
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">Trang chủ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="search.php">Tìm kiếm</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="post.php">Đăng tin</a>
                        </li>
                    </ul>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <?php
                                // Lấy thông tin người dùng
                                $user_id = $_SESSION['user_id'];
                                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                                $stmt->bind_param("i", $user_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $user = $result->fetch_assoc();
                                
                                echo '<img src="../' . $user['avatar'] . '" class="avatar-small me-2" alt="Avatar"> ';
                                echo htmlspecialchars($user['name']);
                                ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="../Auth/edit_profile.php"><i class="fas fa-user-edit me-2"></i>Chỉnh sửa hồ sơ</a></li>
                                <li><a class="dropdown-item" href="my_rooms.php"><i class="fas fa-list me-2"></i>Phòng trọ của tôi</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../Auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Banner tìm kiếm -->
    <section class="search-banner">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="search-container">
                        <h1 class="text-center mb-4">Tìm phòng trọ phù hợp với bạn</h1>
                        <form action="search.php" method="GET" class="search-form">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        <select name="district" class="form-select">
                                            <option value="">Chọn khu vực</option>
                                            <option value="1">Quận Hồng Bàng</option>
                                            <option value="2">Quận Lê Chân</option>
                                            <option value="3">Quận Ngô Quyền</option>
                                            <option value="4">Quận Kiến An</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                                        <select name="price" class="form-select">
                                            <option value="">Chọn khoảng giá</option>
                                            <option value="0-1000000">Dưới 1 triệu</option>
                                            <option value="1000000-2000000">1 - 2 triệu</option>
                                            <option value="2000000-3000000">2 - 3 triệu</option>
                                            <option value="3000000-5000000">3 - 5 triệu</option>
                                            <option value="5000000-999999999">Trên 5 triệu</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-2"></i>Tìm kiếm
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Phần nội dung chính -->
    <main class="py-5">
        <div class="container">
            <!-- Phòng trọ xem nhiều nhất -->
            <section class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title"><i class="fas fa-fire me-2 text-danger"></i>Phòng trọ xem nhiều nhất</h2>
                    <a href="search.php?sort=view" class="btn btn-outline-primary btn-sm">Xem tất cả</a>
                </div>
                
                <div class="row">
                    <?php if ($most_viewed_rooms->num_rows > 0): ?>
                        <?php while($room = $most_viewed_rooms->fetch_assoc()): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card room-card h-100">
                                    <div class="room-image">
                                        <img src="../<?php echo $room['images']; ?>" class="card-img-top" alt="<?php echo $room['title']; ?>">
                                        <span class="price-tag"><?php echo number_format($room['price']); ?> đ/tháng</span>
                                        <span class="view-count"><i class="fas fa-eye me-1"></i><?php echo $room['count_view']; ?></span>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="room_detail.php?id=<?php echo $room['id']; ?>"><?php echo $room['title']; ?></a>
                                        </h5>
                                        <p class="card-text address"><i class="fas fa-map-marker-alt me-2"></i><?php echo $room['address']; ?></p>
                                        <div class="room-info">
                                            <span><i class="fas fa-expand me-1"></i><?php echo $room['area']; ?> m²</span>
                                            <span><i class="fas fa-bolt me-1"></i>
                                                <?php 
                                                    $utilities = explode(',', $room['utilities']);
                                                    echo count($utilities) . ' tiện ích';
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <small class="text-muted">Đăng bởi: <?php echo $room['owner_name']; ?></small>
                                        <small class="text-muted float-end">
                                            <i class="far fa-clock me-1"></i>
                                            <?php 
                                                $date = new DateTime($room['created_at']);
                                                echo $date->format('d/m/Y'); 
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">Chưa có phòng trọ nào.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Phòng trọ mới đăng tải -->
            <section class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title"><i class="fas fa-clock me-2 text-success"></i>Phòng trọ mới đăng tải</h2>
                    <a href="search.php?sort=newest" class="btn btn-outline-primary btn-sm">Xem tất cả</a>
                </div>
                
                <div class="row">
                    <?php if ($newest_rooms->num_rows > 0): ?>
                        <?php while($room = $newest_rooms->fetch_assoc()): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card room-card h-100">
                                    <div class="room-image">
                                        <img src="../<?php echo $room['images']; ?>" class="card-img-top" alt="<?php echo $room['title']; ?>">
                                        <span class="price-tag"><?php echo number_format($room['price']); ?> đ/tháng</span>
                                        <span class="new-tag">Mới</span>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="room_detail.php?id=<?php echo $room['id']; ?>"><?php echo $room['title']; ?></a>
                                        </h5>
                                        <p class="card-text address"><i class="fas fa-map-marker-alt me-2"></i><?php echo $room['address']; ?></p>
                                        <div class="room-info">
                                            <span><i class="fas fa-expand me-1"></i><?php echo $room['area']; ?> m²</span>
                                            <span><i class="fas fa-bolt me-1"></i>
                                                <?php 
                                                    $utilities = explode(',', $room['utilities']);
                                                    echo count($utilities) . ' tiện ích';
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <small class="text-muted">Đăng bởi: <?php echo $room['owner_name']; ?></small>
                                        <small class="text-muted float-end">
                                            <i class="far fa-clock me-1"></i>
                                            <?php 
                                                $date = new DateTime($room['created_at']);
                                                echo $date->format('d/m/Y'); 
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">Chưa có phòng trọ mới.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Phòng trọ gần trường ĐH Vinh -->
            <section class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title"><i class="fas fa-university me-2 text-primary"></i>Phòng trọ gần trường ĐH Vinh</h2>
                    <a href="search.php?sort=nearest" class="btn btn-outline-primary btn-sm">Xem tất cả</a>
                </div>
                
                <div class="row">
                    <?php if ($nearest_rooms->num_rows > 0): ?>
                        <?php while($room = $nearest_rooms->fetch_assoc()): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card room-card h-100">
                                    <div class="room-image">
                                        <img src="../<?php echo $room['images']; ?>" class="card-img-top" alt="<?php echo $room['title']; ?>">
                                        <span class="price-tag"><?php echo number_format($room['price']); ?> đ/tháng</span>
                                        <span class="distance-tag"><i class="fas fa-walking me-1"></i><?php echo $room['latlng']; ?> km</span>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="room_detail.php?id=<?php echo $room['id']; ?>"><?php echo $room['title']; ?></a>
                                        </h5>
                                        <p class="card-text address"><i class="fas fa-map-marker-alt me-2"></i><?php echo $room['address']; ?></p>
                                        <div class="room-info">
                                            <span><i class="fas fa-expand me-1"></i><?php echo $room['area']; ?> m²</span>
                                            <span><i class="fas fa-bolt me-1"></i>
                                                <?php 
                                                    $utilities = explode(',', $room['utilities']);
                                                    echo count($utilities) . ' tiện ích';
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <small class="text-muted">Đăng bởi: <?php echo $room['owner_name']; ?></small>
                                        <small class="text-muted float-end">
                                            <i class="far fa-clock me-1"></i>
                                            <?php 
                                                $date = new DateTime($room['created_at']);
                                                echo $date->format('d/m/Y'); 
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">Không tìm thấy phòng trọ gần trường.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <footer class="py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Phòng trọ sinh viên</h5>
                    <p class="text-muted">Trang web tìm kiếm phòng trọ dành cho sinh viên trường Đại học Vinh.</p>
                    <div class="social-links">
                        <a href="#" class="me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="me-2"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Liên kết</h5>
                    <ul class="list-unstyled">
                        <li><a href="#"><i class="fas fa-angle-right me-2"></i>Trang chủ</a></li>
                        <li><a href="#"><i class="fas fa-angle-right me-2"></i>Tìm phòng trọ</a></li>
                        <li><a href="#"><i class="fas fa-angle-right me-2"></i>Đăng tin</a></li>
                        <li><a href="#"><i class="fas fa-angle-right me-2"></i>Liên hệ</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Liên hệ với chúng tôi</h5>
                    <ul class="list-unstyled contact-info">
                        <li><i class="fas fa-map-marker-alt me-2"></i>182 Lê Duẩn, TP. Vinh, Nghệ An</li>
                        <li><i class="fas fa-phone me-2"></i>0123 456 789</li>
                        <li><i class="fas fa-envelope me-2"></i>info@phongtrodhvinh.com</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col">
                    <p class="text-center mb-0">&copy; <?php echo date('Y'); ?> Phòng trọ sinh viên. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script src="../Assets/main.js"></script>
</body>
</html>
