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

// Kiểm tra id phòng trọ trong URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$room_id = $_GET['id'];

// Truy vấn thông tin phòng trọ
$stmt = $conn->prepare("
    SELECT m.*, u.name as owner_name, u.phone as owner_phone, u.avatar as owner_avatar, 
           c.name as category_name, d.name as district_name 
    FROM motel m 
    LEFT JOIN users u ON m.user_id = u.id 
    LEFT JOIN categories c ON m.category_id = c.id
    LEFT JOIN districts d ON m.district_id = d.id
    WHERE m.id = ? AND m.approve = 1
");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

// Kiểm tra xem phòng trọ có tồn tại không
if ($result->num_rows == 0) {
    header('Location: index.php');
    exit;
}

$room = $result->fetch_assoc();

// Tăng lượt xem
$stmt_update_view = $conn->prepare("UPDATE motel SET count_view = count_view + 1 WHERE id = ?");
$stmt_update_view->bind_param("i", $room_id);
$stmt_update_view->execute();

// Lấy phòng trọ tương tự (cùng khu vực hoặc cùng khoảng giá)
$stmt_similar = $conn->prepare("
    SELECT m.*, u.name as owner_name 
    FROM motel m 
    LEFT JOIN users u ON m.user_id = u.id 
    WHERE m.id != ? 
    AND m.approve = 1 
    AND (m.district_id = ? OR (m.price BETWEEN ? AND ?))
    ORDER BY m.count_view DESC 
    LIMIT 3
");

$price_min = $room['price'] * 0.8;  // Lấy phòng có giá từ 80% của phòng hiện tại
$price_max = $room['price'] * 1.2;  // Đến 120% giá của phòng hiện tại

$stmt_similar->bind_param("iiii", $room_id, $room['district_id'], $price_min, $price_max);
$stmt_similar->execute();
$similar_rooms = $stmt_similar->get_result();

// Xử lý ảnh phòng trọ (nếu có nhiều ảnh)
$images = [$room['images']]; // Mặc định có một ảnh
if (strpos($room['images'], ',') !== false) {
    $images = explode(',', $room['images']);
}

// Xử lý tiện ích
$utilities = [];
if (!empty($room['utilities'])) {
    $utilities = explode(',', $room['utilities']);
}

// Định dạng giá thành
$formatted_price = number_format($room['price']) . ' đ/tháng';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $room['title']; ?> - Phòng trọ sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Assets/style.css">
    <!-- Link tới thư viện Swiper cho slider -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
</head>

<body class="room-detail-body">
    <?php include '../Components/header.php' ?>

    <section class="room-detail-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Gallery -->
                    <div class="room-gallery swiper">
                        <div class="swiper-wrapper">
                            <?php foreach ($images as $image): ?>
                                <div class="swiper-slide">
                                    <img src="../<?php echo $image; ?>" alt="<?php echo $room['title']; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="swiper-pagination"></div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>

                    <!-- Thông tin chính -->
                    <div class="room-main-info">
                        <h1 class="room-title"><?php echo $room['title']; ?></h1>
                        <p class="room-price"><?php echo $formatted_price; ?></p>
                        <p class="room-address">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <?php echo $room['address']; ?>
                            <?php if ($room['district_name']): ?>
                                <span class="badge bg-light text-dark ms-2"><?php echo $room['district_name']; ?></span>
                            <?php endif; ?>
                        </p>

                        <!-- Tính năng chính -->
                        <div class="room-features">
                            <div class="room-features-item">
                                <i class="fas fa-expand"></i>
                                <div>
                                    <strong>Diện tích</strong>
                                    <div><?php echo $room['area']; ?> m²</div>
                                </div>
                            </div>
                            <?php if ($room['category_name']): ?>
                                <div class="room-features-item">
                                    <i class="fas fa-th-large"></i>
                                    <div>
                                        <strong>Loại phòng</strong>
                                        <div><?php echo $room['category_name']; ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="room-features-item">
                                <i class="fas fa-eye"></i>
                                <div>
                                    <strong>Lượt xem</strong>
                                    <div><?php echo $room['count_view']; ?> lượt</div>
                                </div>
                            </div>
                            <?php if ($room['latlng']): ?>
                                <div class="room-features-item">
                                    <i class="fas fa-university"></i>
                                    <div>
                                        <strong>Khoảng cách</strong>
                                        <div><?php echo $room['latlng']; ?> km đến ĐH Vinh</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Mô tả chi tiết -->
                        <div class="mt-4">
                            <h5 class="mb-3"><i class="fas fa-file-alt me-2 text-primary"></i>Mô tả chi tiết</h5>
                            <div class="room-description">
                                <?php echo nl2br($room['description'] ?? 'Chưa có mô tả chi tiết.'); ?>
                            </div>
                        </div>

                        <!-- Tiện ích -->
                        <?php if (!empty($utilities)): ?>
                            <div class="mt-4">
                                <h5 class="mb-3"><i class="fas fa-bolt me-2 text-primary"></i>Tiện ích</h5>
                                <div class="utilities-list">
                                    <?php foreach ($utilities as $utility): ?>
                                        <span class="utility-badge">
                                            <?php
                                            $icon = 'fas fa-star'; // Mặc định
                                            if (stripos($utility, 'wifi') !== false) $icon = 'fas fa-wifi';
                                            else if (stripos($utility, 'giặt') !== false) $icon = 'fas fa-tshirt';
                                            else if (stripos($utility, 'trường') !== false) $icon = 'fas fa-school';
                                            else if (stripos($utility, 'điều hòa') !== false) $icon = 'fas fa-snowflake';
                                            else if (stripos($utility, 'tủ lạnh') !== false) $icon = 'fas fa-cube';
                                            else if (stripos($utility, 'gửi xe') !== false) $icon = 'fas fa-motorcycle';
                                            else if (stripos($utility, 'an ninh') !== false) $icon = 'fas fa-shield-alt';
                                            else if (stripos($utility, 'nước') !== false) $icon = 'fas fa-tint';
                                            else if (stripos($utility, 'điện') !== false) $icon = 'fas fa-bolt';
                                            ?>
                                            <i class="<?php echo $icon; ?>"></i>
                                            <?php echo trim($utility); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Thông tin chủ trọ -->
                    <div class="owner-profile mb-4">
                        <img src="../<?php echo $room['owner_avatar'] ?? 'images/default_avatar.jpg'; ?>" alt="<?php echo $room['owner_name']; ?>" class="owner-avatar">
                        <div>
                            <h5 class="mb-1"><?php echo $room['owner_name']; ?></h5>
                            <p class="mb-3 text-muted small">Chủ phòng trọ</p>
                            <a href="tel:<?php echo $room['owner_phone'] ?? $room['phone']; ?>" class="btn contact-btn btn-primary">
                                <i class="fas fa-phone-alt me-2"></i>Liên hệ ngay
                            </a>
                        </div>
                    </div>

                    <!-- Thông tin đăng tải -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Thông tin đăng tải</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span><i class="far fa-calendar-alt me-2 text-muted"></i>Ngày đăng</span>
                                    <span class="fw-bold">
                                        <?php
                                        $date = new DateTime($room['created_at']);
                                        echo $date->format('d/m/Y');
                                        ?>
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span><i class="fas fa-eye me-2 text-muted"></i>Lượt xem</span>
                                    <span class="fw-bold"><?php echo $room['count_view']; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span><i class="fas fa-phone-alt me-2 text-muted"></i>Số điện thoại</span>
                                    <span class="fw-bold"><?php echo $room['phone']; ?></span>
                                </li>
                            </ul>
                            <div class="mt-3">
                                <a href="javascript:void(0)" class="btn btn-outline-primary btn-sm w-100" onclick="shareRoom()">
                                    <i class="fas fa-share-alt me-2"></i>Chia sẻ phòng trọ
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Back to search -->
                    <div class="d-grid gap-2 mb-4">
                        <a href="search.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại tìm kiếm
                        </a>
                    </div>
                </div>
            </div>

            <!-- Phòng trọ tương tự -->
            <?php if ($similar_rooms->num_rows > 0): ?>
                <div class="mt-5">
                    <h3 class="similar-rooms-title">Phòng trọ tương tự</h3>
                    <div class="row">
                        <?php while ($similar = $similar_rooms->fetch_assoc()): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card room-card h-100">
                                    <div class="room-image">
                                        <img src="../<?php echo $similar['images']; ?>" class="card-img-top" alt="<?php echo $similar['title']; ?>">
                                        <span class="price-tag"><?php echo number_format($similar['price']); ?> đ/tháng</span>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="room_detail.php?id=<?php echo $similar['id']; ?>"><?php echo $similar['title']; ?></a>
                                        </h5>
                                        <p class="card-text address"><i class="fas fa-map-marker-alt me-2"></i><?php echo $similar['address']; ?></p>
                                        <div class="room-info">
                                            <span><i class="fas fa-expand me-1"></i><?php echo $similar['area']; ?> m²</span>
                                            <?php if (!empty($similar['utilities'])): ?>
                                                <span><i class="fas fa-bolt me-1"></i>
                                                    <?php
                                                    $sim_utilities = explode(',', $similar['utilities']);
                                                    echo count($sim_utilities) . ' tiện ích';
                                                    ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <small class="text-muted">Đăng bởi: <?php echo $similar['owner_name']; ?></small>
                                        <small class="text-muted float-end">
                                            <i class="far fa-clock me-1"></i>
                                            <?php
                                            $date = new DateTime($similar['created_at']);
                                            echo $date->format('d/m/Y');
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../Components/footer.php' ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script>
        // Initialize Swiper with more options
        const swiper = new Swiper('.room-gallery', {
            loop: true,
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
                dynamicBullets: true,
            },
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
        });

        // Add ScrollReveal for smooth animations on scroll
        window.addEventListener('DOMContentLoaded', function() {
            // Add active class to elements to trigger animations
            const animatedElements = document.querySelectorAll('.room-main-info, .owner-profile, .room-gallery, .card');

            animatedElements.forEach(function(el) {
                setTimeout(() => {
                    el.classList.add('animated');
                }, 300);
            });

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        });

        // Enhanced sharing functionality
        function shareRoom() {
            const roomTitle = '<?php echo addslashes($room['title']); ?>';
            const roomAddress = '<?php echo addslashes($room['address']); ?>';
            const pageUrl = window.location.href;

            if (navigator.share) {
                navigator.share({
                        title: roomTitle,
                        text: 'Xem phòng trọ: ' + roomTitle + ' tại ' + roomAddress,
                        url: pageUrl
                    })
                    .catch((error) => console.log('Không thể chia sẻ', error));
            } else {
                // Enhanced fallback with copy to clipboard
                try {
                    const tempInput = document.createElement('input');
                    tempInput.value = pageUrl;
                    document.body.appendChild(tempInput);
                    tempInput.select();
                    document.execCommand('copy');
                    document.body.removeChild(tempInput);

                    alert('Đã sao chép link vào clipboard:\n' + pageUrl);
                } catch (err) {
                    alert('Sao chép link và chia sẻ:\n' + pageUrl);
                }
            }
        }
    </script>
</body>

</html>