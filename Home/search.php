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

// Xử lý tìm kiếm
$where_clause = "WHERE m.approve = 1";
$params = [];
$types = "";

// Tìm kiếm theo địa điểm
if (isset($_GET['district']) && !empty($_GET['district'])) {
    $district_id = $_GET['district'];
    $where_clause .= " AND m.district_id = ?";
    $params[] = $district_id;
    $types .= "i";
}

// Tìm kiếm theo khoảng giá
if (isset($_GET['price']) && !empty($_GET['price'])) {
    $price_range = explode('-', $_GET['price']);
    if (count($price_range) == 2) {
        $min_price = $price_range[0];
        $max_price = $price_range[1];
        $where_clause .= " AND m.price BETWEEN ? AND ?";
        $params[] = $min_price;
        $params[] = $max_price;
        $types .= "ii";
    }
}

// Tìm kiếm theo tiện ích
if (isset($_GET['utilities']) && !empty($_GET['utilities'])) {
    $utilities = $_GET['utilities'];
    if (is_array($utilities)) {
        foreach ($utilities as $utility) {
            $where_clause .= " AND FIND_IN_SET(?, m.utilities)";
            $params[] = $utility;
            $types .= "s";
        }
    }
}

// Sắp xếp kết quả
$order_clause = "ORDER BY m.created_at DESC";

if (isset($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'price_asc':
            $order_clause = "ORDER BY m.price ASC";
            break;
        case 'price_desc':
            $order_clause = "ORDER BY m.price DESC";
            break;
        case 'newest':
            $order_clause = "ORDER BY m.created_at DESC";
            break;
        case 'view':
            $order_clause = "ORDER BY m.count_view DESC";
            break;
        case 'nearest':
            $order_clause = "ORDER BY CAST(m.latlng AS DECIMAL(10,6)) ASC";
            break;
        default:
            $order_clause = "ORDER BY m.created_at DESC";
    }
}

// Lấy danh sách các tiện ích để hiển thị bộ lọc
$stmt_utilities = $conn->prepare("SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(m.utilities, ',', numbers.n), ',', -1) as utility 
                                FROM motel m 
                                CROSS JOIN (
                                    SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
                                    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
                                ) numbers
                                WHERE LENGTH(REPLACE(m.utilities, ',', '')) > LENGTH(m.utilities) - n
                                GROUP BY utility
                                ORDER BY utility");
$stmt_utilities->execute();
$utility_result = $stmt_utilities->get_result();
$all_utilities = [];
while ($row = $utility_result->fetch_assoc()) {
    $all_utilities[] = $row['utility'];
}

// Thực thi truy vấn tìm kiếm
$sql = "SELECT m.*, u.name as owner_name 
        FROM motel m 
        LEFT JOIN users u ON m.user_id = u.id 
        $where_clause 
        $order_clause";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$rooms = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tìm kiếm phòng trọ - Phòng trọ sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Assets/style.css">
    <!-- Range slider CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.3.1/css/ion.rangeSlider.min.css"/>
</head>
<body class="search-body">
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
                            <a class="nav-link" href="index.php">Trang chủ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="search.php">Tìm kiếm</a>
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

    <main class="py-5 mt-5">
        <div class="container">
            <div class="row">
                <!-- Bộ lọc tìm kiếm -->
                <div class="col-lg-3">
                    <div class="filter-container">
                        <h3 class="filter-title">Bộ lọc tìm kiếm</h3>
                        <form action="search.php" method="GET" id="filterForm">
                            <!-- Tìm kiếm theo khoảng giá -->
                            <div class="filter-section">
                                <h4 class="filter-subtitle">Khoảng giá</h4>
                                <div class="price-slider">
                                    <input type="text" class="js-range-slider" id="price_range" name="price_range" value="" />
                                    <div class="price-inputs mt-2">
                                        <div class="row">
                                            <div class="col-6">
                                                <input type="text" class="form-control form-control-sm" id="min_price" placeholder="Từ" readonly>
                                            </div>
                                            <div class="col-6">
                                                <input type="text" class="form-control form-control-sm" id="max_price" placeholder="Đến" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="price" id="price_hidden">
                            </div>

                            <!-- Tìm kiếm theo địa điểm -->
                            <div class="filter-section">
                                <h4 class="filter-subtitle">Địa điểm</h4>
                                <select name="district" class="form-select">
                                    <option value="">Tất cả</option>
                                    <option value="1" <?php echo isset($_GET['district']) && $_GET['district'] == '1' ? 'selected' : ''; ?>>Quận Hồng Bàng</option>
                                    <option value="2" <?php echo isset($_GET['district']) && $_GET['district'] == '2' ? 'selected' : ''; ?>>Quận Lê Chân</option>
                                    <option value="3" <?php echo isset($_GET['district']) && $_GET['district'] == '3' ? 'selected' : ''; ?>>Quận Ngô Quyền</option>
                                    <option value="4" <?php echo isset($_GET['district']) && $_GET['district'] == '4' ? 'selected' : ''; ?>>Quận Kiến An</option>
                                </select>
                            </div>

                            <!-- Tìm kiếm theo tiện ích -->
                            <div class="filter-section">
                                <h4 class="filter-subtitle">Tiện ích</h4>
                                <div class="utilities-filter">
                                    <?php foreach ($all_utilities as $utility): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="utilities[]" 
                                                   value="<?php echo $utility; ?>" 
                                                   id="utility_<?php echo $utility; ?>"
                                                   <?php echo (isset($_GET['utilities']) && in_array($utility, (array)$_GET['utilities'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="utility_<?php echo $utility; ?>">
                                                <?php echo $utility; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Sắp xếp kết quả -->
                            <div class="filter-section">
                                <h4 class="filter-subtitle">Sắp xếp theo</h4>
                                <select name="sort" class="form-select">
                                    <option value="newest" <?php echo (!isset($_GET['sort']) || $_GET['sort'] == 'newest') ? 'selected' : ''; ?>>Mới nhất</option>
                                    <option value="price_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_asc') ? 'selected' : ''; ?>>Giá thấp đến cao</option>
                                    <option value="price_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_desc') ? 'selected' : ''; ?>>Giá cao đến thấp</option>
                                    <option value="view" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'view') ? 'selected' : ''; ?>>Lượt xem nhiều nhất</option>
                                    <option value="nearest" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'nearest') ? 'selected' : ''; ?>>Gần trường ĐH Vinh</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mt-3">
                                <i class="fas fa-filter me-2"></i>Lọc kết quả
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Kết quả tìm kiếm -->
                <div class="col-lg-9">
                    <div class="search-results">
                        <div class="search-summary">
                            <h2 class="mb-3">Kết quả tìm kiếm</h2>
                            <p>Tìm thấy <?php echo $rooms->num_rows; ?> phòng trọ phù hợp</p>
                        </div>

                        <div class="result-list">
                            <?php if ($rooms->num_rows > 0): ?>
                                <div class="row">
                                    <?php while($room = $rooms->fetch_assoc()): ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="card room-card h-100">
                                                <div class="room-image">
                                                    <img src="../<?php echo $room['images']; ?>" class="card-img-top" alt="<?php echo $room['title']; ?>">
                                                    <span class="price-tag"><?php echo number_format($room['price']); ?> đ/tháng</span>
                                                    <?php if (isset($_GET['sort']) && $_GET['sort'] == 'view'): ?>
                                                        <span class="view-count"><i class="fas fa-eye me-1"></i><?php echo $room['count_view']; ?></span>
                                                    <?php elseif (isset($_GET['sort']) && $_GET['sort'] == 'nearest'): ?>
                                                        <span class="distance-tag"><i class="fas fa-walking me-1"></i><?php echo $room['latlng']; ?> km</span>
                                                    <?php endif; ?>
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
                                                    <div class="room-utilities-preview mt-2">
                                                        <?php 
                                                        $utilities = explode(',', $room['utilities']);
                                                        $count = 0;
                                                        foreach ($utilities as $utility):
                                                            if ($count < 3): 
                                                                $count++;
                                                        ?>
                                                            <span class="utility-tag"><?php echo $utility; ?></span>
                                                        <?php 
                                                            endif;
                                                        endforeach;
                                                        
                                                        if (count($utilities) > 3):
                                                        ?>
                                                            <span class="utility-tag">+<?php echo count($utilities) - 3; ?></span>
                                                        <?php endif; ?>
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
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>Không tìm thấy phòng trọ nào phù hợp với tiêu chí tìm kiếm. Vui lòng thử lại với tiêu chí khác.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
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
                        <li><a href="index.php"><i class="fas fa-angle-right me-2"></i>Trang chủ</a></li>
                        <li><a href="search.php"><i class="fas fa-angle-right me-2"></i>Tìm phòng trọ</a></li>
                        <li><a href="post.php"><i class="fas fa-angle-right me-2"></i>Đăng tin</a></li>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.3.1/js/ion.rangeSlider.min.js"></script>
    <script src="../Assets/main.js"></script>
    <script>
        $(document).ready(function() {
            // Range slider cho khoảng giá
            var $range = $(".js-range-slider");
            var $inputFrom = $("#min_price");
            var $inputTo = $("#max_price");
            var $hiddenInput = $("#price_hidden");
            var min = 0;
            var max = 10000000;
            
            $range.ionRangeSlider({
                skin: "round",
                type: "double",
                min: min,
                max: max,
                from: <?php echo isset($min_price) ? $min_price : 0; ?>,
                to: <?php echo isset($max_price) ? $max_price : 10000000; ?>,
                grid: true,
                grid_num: 5,
                prefix: "",
                postfix: " đ",
                prettify_separator: ".",
                onStart: updateInputs,
                onChange: updateInputs,
                onFinish: updateInputs
            });
            
            function updateInputs(data) {
                from = data.from;
                to = data.to;
                
                $inputFrom.val(from.toLocaleString('vi-VN') + ' đ');
                $inputTo.val(to.toLocaleString('vi-VN') + ' đ');
                $hiddenInput.val(from + '-' + to);
            }
            
            // Hiển thị thêm tiện ích
            $('.view-more-utilities').on('click', function(e) {
                e.preventDefault();
                $('.utilities-filter').toggleClass('show-all');
                $(this).text($(this).text() == 'Xem thêm' ? 'Thu gọn' : 'Xem thêm');
            });
        });
    </script>
</body>
</html>
