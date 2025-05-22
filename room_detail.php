<?php
// Khởi tạo phiên làm việc
session_start();

// Kiểm tra xem người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: ./auth/login.php');
    exit;
}

// Kết nối đến CSDL
require_once('./config/db.php');

// Khởi tạo mảng favorite_rooms từ CSDL
require_once('./config/favorites.php');

// Khởi tạo session viewed_rooms nếu chưa có
if (!isset($_SESSION['viewed_rooms'])) {
    $_SESSION['viewed_rooms'] = array();
}

// Kiểm tra id phòng trọ trong URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$room_id = $_GET['id'];

// Cập nhật lượt xem nếu người dùng chưa xem phòng này trong phiên làm việc hiện tại
if (!in_array($room_id, $_SESSION['viewed_rooms'])) {
    // Tăng lượt xem lên 1
    $update_view = $conn->prepare("UPDATE motel SET count_view = count_view + 1 WHERE id = ?");
    $update_view->bind_param("i", $room_id);
    $update_view->execute();

    // Thêm phòng vào danh sách đã xem
    $_SESSION['viewed_rooms'][] = $room_id;
}

// Xử lý thêm/xóa khỏi danh sách yêu thích
$favorite_message = '';
$message_type = '';
$user_id = $_SESSION['user_id'];

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'favorite') {
        // Kiểm tra xem phòng đã được yêu thích chưa
        $check_stmt = $conn->prepare("SELECT id FROM user_wishlist WHERE user_id = ? AND motel_id = ?");
        $check_stmt->bind_param("ii", $user_id, $room_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows == 0) {
            // Thêm vào danh sách yêu thích trong CSDL
            $insert_stmt = $conn->prepare("INSERT INTO user_wishlist (user_id, motel_id) VALUES (?, ?)");
            $insert_stmt->bind_param("ii", $user_id, $room_id);

            if ($insert_stmt->execute()) {
                // Cập nhật số lượt yêu thích trên phòng
                $update_motel = $conn->prepare("UPDATE motel SET wishlist = wishlist + 1 WHERE id = ?");
                $update_motel->bind_param("i", $room_id);
                $update_motel->execute();

                // Cập nhật lại session
                if (!in_array($room_id, $_SESSION['favorite_rooms'])) {
                    $_SESSION['favorite_rooms'][] = $room_id;
                }

                $favorite_message = 'Đã thêm phòng trọ vào danh sách yêu thích!';
                $message_type = 'success';
            } else {
                $favorite_message = 'Có lỗi xảy ra khi thêm vào yêu thích!';
                $message_type = 'danger';
            }
        }
    } elseif ($_GET['action'] === 'unfavorite') {
        // Xóa khỏi danh sách yêu thích trong CSDL
        $delete_stmt = $conn->prepare("DELETE FROM user_wishlist WHERE user_id = ? AND motel_id = ?");
        $delete_stmt->bind_param("ii", $user_id, $room_id);
        $delete_stmt->execute();

        // Kiểm tra xem record đã bị xóa (tồn tại trong DB) hoặc phòng chỉ có trong session
        if ($delete_stmt->affected_rows > 0) {
            // Phòng tồn tại trong DB và đã được xóa thành công
            // Cập nhật số lượt yêu thích trên phòng
            $update_motel = $conn->prepare("UPDATE motel SET wishlist = wishlist - 1 WHERE id = ? AND wishlist > 0");
            $update_motel->bind_param("i", $room_id);
            $update_motel->execute();
        }

        // Luôn cập nhật session bất kể phòng có trong DB hay không
        if (($key = array_search($room_id, $_SESSION['favorite_rooms'])) !== false) {
            unset($_SESSION['favorite_rooms'][$key]);
            // Sắp xếp lại mảng
            $_SESSION['favorite_rooms'] = array_values($_SESSION['favorite_rooms']);
        }

        // Luôn trả về thông báo thành công vì session đã được cập nhật
        $favorite_message = 'Đã xóa phòng trọ khỏi danh sách yêu thích!';
        $message_type = 'warning';
    }

    // Chuyển hướng để loại bỏ tham số action khỏi URL
    header("Location: room_detail.php?id=$room_id" .
        (!empty($favorite_message) ? "&message=" . urlencode($favorite_message) . "&type=" . $message_type : ""));
    exit;
}

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

// Lấy ảnh banner (ảnh đại diện)
$images = [];
if (!empty($room['images'])) {
    $images[] = $room['images'];
}

// Lấy các ảnh khác từ bảng motel_images
$motel_id = $room['id'];
$sql_images = "SELECT image_path FROM motel_images WHERE motel_id = ? ORDER BY display_order ASC, id ASC";
$stmt_images = $conn->prepare($sql_images);
$stmt_images->bind_param("i", $motel_id);
$stmt_images->execute();
$result_images = $stmt_images->get_result();
while ($img_row = $result_images->fetch_assoc()) {
    $images[] = $img_row['image_path'];
}

// Xử lý tiện ích
$utilities = [];
if (!empty($room['utilities'])) {
    $utilities = explode(',', $room['utilities']);
}

// Định dạng giá thành
$formatted_price = number_format($room['price']) . ' đ/tháng';

// Truy vấn phòng trọ tương tự (cùng quận, loại phòng tương tự, mức giá tương tự)
$similar_rooms = null;
$sql_similar = "SELECT m.*, u.name as owner_name, u.avatar as owner_avatar 
                FROM motel m 
                LEFT JOIN users u ON m.user_id = u.id 
                WHERE m.id != ? AND m.approve = 1
                AND m.district_id = ?
                AND m.price BETWEEN ? AND ? 
                ORDER BY m.created_at DESC 
                LIMIT 3";

$min_price = $room['price'] * 0.7; // Giảm 30%
$max_price = $room['price'] * 1.3; // Tăng 30%

$stmt_similar = $conn->prepare($sql_similar);
$stmt_similar->bind_param("iidd", $room_id, $room['district_id'], $min_price, $max_price);
$stmt_similar->execute();
$similar_rooms = $stmt_similar->get_result();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $room['title']; ?> - Phòng trọ sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../assets/client/css/style.css">
    <!-- Link tới thư viện Swiper cho slider -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
    <!-- Link tới thư viện Animate.css cho các hiệu ứng -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>

<body class="room-detail-body"> <?php include './components/header.php' ?>

    <?php if (isset($_GET['message'])): ?>
        <div class="container mt-4">
            <div class="alert alert-<?php echo isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'info'; ?> alert-dismissible fade show animate__animated animate__fadeIn"
                role="alert">
                <i
                    class="fas <?php echo ($_GET['type'] == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($_GET['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <section class="room-detail-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Gallery -->
                    <div class="room-gallery swiper">
                        <div class="swiper-wrapper">
                            <?php foreach ($images as $image): ?>
                                <div class="swiper-slide">
                                    <img src="./<?php echo $image; ?>" alt="<?php echo $room['title']; ?>">
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
                        <p class="room-price">
                            <?php echo $formatted_price; ?>
                            <span class="view-count-badge ms-3">
                                <i class="fas fa-eye me-1"></i><?php echo number_format($room['count_view']); ?> lượt
                                xem
                            </span>
                        </p>
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
                        <img src="./<?php echo $room['owner_avatar'] ?? 'images/default_avatar.jpg'; ?>" alt="<?php echo $room['owner_name']; ?>" class="owner-avatar">

                        <div>
                            <h5 class="mb-1"><?php echo $room['owner_name']; ?></h5>
                            <p class="mb-3 text-muted small">Chủ phòng trọ</p>
                            <a href="tel:<?php echo $room['owner_phone'] ?? $room['phone']; ?>"
                                class="btn contact-btn btn-primary">
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
                            <div class="mt-3 d-flex gap-2">
                                <a href="javascript:void(0)" class="btn btn-outline-primary btn-sm flex-grow-1"
                                    onclick="shareRoom()">
                                    <i class="fas fa-share-alt me-2"></i>Chia sẻ
                                </a>
                                <?php if (in_array($room_id, $_SESSION['favorite_rooms'])): ?>
                                    <a href="room_detail.php?id=<?php echo $room_id; ?>&action=unfavorite"
                                        class="btn btn-danger btn-sm flex-grow-1 favorite-btn">
                                        <i class="fas fa-heart me-2 animate__animated animate__heartBeat"></i>Bỏ thích
                                    </a>
                                <?php else: ?>
                                    <a href="room_detail.php?id=<?php echo $room_id; ?>&action=favorite"
                                        class="btn btn-outline-danger btn-sm flex-grow-1 favorite-btn">
                                        <i class="far fa-heart me-2"></i>Yêu thích
                                    </a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-success btn-sm flex-grow-1" data-bs-toggle="modal"
                                    data-bs-target="#depositModal">
                                    <i class="fas fa-wallet me-2"></i>Đặt cọc
                                </button>
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
                                        <img src="/<?php echo $similar['images']; ?>" class="card-img-top"
                                            alt="<?php echo $similar['title']; ?>">
                                        <span class="price-tag"><?php echo number_format($similar['price']); ?> đ/tháng</span>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a
                                                href="room_detail.php?id=<?php echo $similar['id']; ?>"><?php echo $similar['title']; ?></a>
                                        </h5>
                                        <p class="card-text address"><i
                                                class="fas fa-map-marker-alt me-2"></i><?php echo $similar['address']; ?></p>
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

    <?php include './components/footer.php' ?>
    <!-- Modal Đặt cọc -->
    <div class="modal fade" id="depositModal" tabindex="-1" aria-labelledby="depositModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">

                    <h5 class="modal-title" id="depositModalLabel"><i class="fas fa-wallet me-2 text-success"></i>Xác
                        nhận đặt cọc</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Bạn muốn đặt cọc phòng <strong><?php echo htmlspecialchars($room['title']); ?></strong>?</p>

                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><i class="fas fa-info-circle me-2"></i>Số tiền cọc:</span>
                            <span class="fw-bold fs-5">
                                <?php
                                // Nếu có default_deposit thì hiển thị, nếu không thì tính 50% giá thuê
                                $deposit_amount = !empty($room['default_deposit']) ? $room['default_deposit'] : round($room['price'] * 0.5);
                                echo number_format($deposit_amount) . ' đ';
                                ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Giá thuê hàng tháng:</span>
                            <span><?php echo number_format($room['price']); ?> đ</span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <h6><i class="fas fa-credit-card me-2"></i>Phương thức thanh toán:</h6>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="radio" name="paymentMethod" id="vnpay" value="vnpay"
                                checked>
                            <label class="form-check-label" for="vnpay">VNPay</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="paymentMethod" id="cash" value="cash">
                            <label class="form-check-label" for="cash">Tiền mặt</label>
                        </div>
                    </div>

                    <p class="text-muted small mt-3 mb-0">Sau khi đặt cọc, chủ phòng sẽ liên hệ với bạn để xác nhận
                        thông tin.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-success" onclick="processPayment()">
                        <i class="fas fa-money-bill-wave me-2"></i>Tiến hành thanh toán
                    </button>
                </div>
            </div>
        </div>
    </div>
    <form id="bookingForm" action="../components/create_booking.php" method="POST" style="display:none">
        <input type="hidden" name="motel_id" value="<?= $room['id'] ?>">
        <input type="hidden" name="deposit_amount" value="<?= $deposit_amount ?>">
    </form>
    <script>
        function processPayment() {
            const method = document.querySelector('input[name="paymentMethod"]:checked').value;

            if (method === 'vnpay') {
                document.getElementById('bookingForm').submit();
            } else if (method === 'cash') {
                window.location.href = 'cash_payment_info.php?motel_id=<?= $room['id'] ?>';
            }
        }
    </script>
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
            const animatedElements = document.querySelectorAll(
                '.room-main-info, .owner-profile, .room-gallery, .card');

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

        // Khởi tạo hiệu ứng cho nút yêu thích
        document.addEventListener('DOMContentLoaded', function() {
            // Hiệu ứng nút yêu thích với hiệu ứng nâng cao
            const favoriteBtn = document.querySelector('.favorite-btn');
            if (favoriteBtn) {
                favoriteBtn.addEventListener('click', function(e) {
                    // Hiệu ứng nhấn nút
                    this.classList.add('btn-pulse');

                    // Thêm hiệu ứng cho icon
                    const icon = this.querySelector('i');
                    if (icon.classList.contains('far')) { // Nếu đang thêm vào yêu thích
                        icon.classList.add('animate__animated', 'animate__heartBeat');
                    } else { // Nếu đang xóa khỏi yêu thích
                        icon.classList.add('animate__animated', 'animate__fadeOut');
                    }
                });
            }
        });
    </script>
    <!-- Script để xử lý animation cho số lượt xem -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Kiểm tra xem đây có phải là lượt xem mới hay không
            <?php if (
                isset($_SESSION['viewed_rooms']) &&
                in_array($room_id, $_SESSION['viewed_rooms']) &&
                count($_SESSION['viewed_rooms']) <= 1
            ): ?>
                // Hiệu ứng cho view count nếu là lượt xem đầu tiên
                const viewCountBadge = document.querySelector('.view-count-badge');
                if (viewCountBadge) {
                    setTimeout(function() {
                        viewCountBadge.classList.add('animate__animated', 'animate__heartBeat');
                    }, 500);
                }
            <?php endif; ?>
        });
    </script>
</body>

</html>