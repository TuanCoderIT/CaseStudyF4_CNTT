<?php
// Khởi tạo phiên làm việc
session_start();

// Kiểm tra xem người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Auth/login.php');
    exit;
}

// Kiểm tra tham số ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$room_id = $_GET['id'];

// Kết nối đến CSDL
require_once('../config/db.php');

// Tăng lượt xem cho phòng trọ
$stmt_update_view = $conn->prepare("UPDATE motel SET count_view = count_view + 1 WHERE id = ?");
$stmt_update_view->bind_param("i", $room_id);
$stmt_update_view->execute();

// Lấy thông tin chi tiết phòng trọ
$stmt = $conn->prepare("
    SELECT m.*, u.name as owner_name, u.phone as owner_phone, u.avatar as owner_avatar
    FROM motel m 
    LEFT JOIN users u ON m.user_id = u.id 
    WHERE m.id = ? AND m.approve = 1
");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: index.php');
    exit;
}

$room = $result->fetch_assoc();

// Lấy các phòng tương tự (cùng khu vực, mức giá tương đương)
$stmt_similar = $conn->prepare("
    SELECT m.*, u.name as owner_name 
    FROM motel m 
    LEFT JOIN users u ON m.user_id = u.id 
    WHERE m.id != ? 
    AND m.district_id = ? 
    AND m.approve = 1 
    AND m.price BETWEEN ? - 500000 AND ? + 500000
    LIMIT 3
");
$price = $room['price'];
$min_price = $price - 500000;
$max_price = $price + 500000;
$stmt_similar->bind_param("iiii", $room_id, $room['district_id'], $min_price, $max_price);
$stmt_similar->execute();
$similar_rooms = $stmt_similar->get_result();

// Xử lý tiện ích
$utilities = explode(',', $room['utilities']);

// Chuẩn bị hình ảnh
$images = explode(',', $room['images']); // Giả sử hình ảnh được lưu dưới dạng chuỗi các đường dẫn ngăn cách bằng dấu phẩy
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
    <!-- Thư viện Lightbox cho hình ảnh -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
    <!-- Thư viện Swiper cho slider -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
    <style>
        /* CSS riêng cho trang chi tiết phòng trọ */
        .room-detail-header {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('../<?php echo $images[0]; ?>');
            background-size: cover;
            background-position: center;
            padding: 100px 0 50px;
            color: white;
            margin-bottom: 30px;
            border-radius: 0 0 50px 50px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .room-gallery {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .swiper {
            width: 100%;
            border-radius: 10px;
            height: 400px;
        }
        
        .swiper-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .room-price {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .room-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            color: #6c757d;
        }
        
        .meta-item i {
            margin-right: 8px;
            color: var(--primary-color);
        }
        
        .utilities-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .utility-item {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }
        
        .utility-item i {
            margin-right: 10px;
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        
        .owner-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .owner-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .owner-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .contact-btn {
            display: block;
            padding: 12px;
            text-align: center;
            margin-bottom: 10px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .map-container {
            height: 300px;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .similar-heading {
            font-weight: 700;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .similar-heading:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 3px;
        }
        
        .thumbnail-gallery {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .thumbnail-item {
            height: 60px;
            overflow: hidden;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            opacity: 0.7;
        }
        
        .thumbnail-item.active {
            opacity: 1;
            border: 2px solid var(--primary-color);
        }
        
        .thumbnail-item:hover {
            opacity: 1;
        }
        
        .thumbnail-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body class="room-detail-body">
    <?php include '../Components/header.php' ?>

    <!-- Banner chi tiết -->
    <section class="room-detail-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 mx-auto text-center">
                    <h1 class="mb-3"><?php echo $room['title']; ?></h1>
                    <p class="lead mb-0"><i class="fas fa-map-marker-alt me-2"></i><?php echo $room['address']; ?></p>
                </div>
            </div>
        </div>
    </section>

    <main class="py-5">
        <div class="container">
            <div class="row">
                <!-- Thông tin chi tiết phòng trọ -->
                <div class="col-lg-8">
                    <!-- Gallery / Slider -->
                    <div class="room-gallery">
                        <div class="swiper mySwiper">
                            <div class="swiper-wrapper">
                                <?php foreach ($images as $index => $image): ?>
                                    <div class="swiper-slide">
                                        <img src="../<?php echo $image; ?>" alt="Hình ảnh phòng trọ <?php echo $index + 1; ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                            <div class="swiper-pagination"></div>
                        </div>
                        
                        <div class="thumbnail-gallery">
                            <?php foreach ($images as $index => $image): ?>
                                <div class="thumbnail-item <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                                    <img src="../<?php echo $image; ?>" alt="Thumbnail <?php echo $index + 1; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Thông tin cơ bản -->
                    <div class="room-basic-info mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2><?php echo $room['title']; ?></h2>
                            <div class="room-price"><?php echo number_format($room['price']); ?> đ/tháng</div>
                        </div>
                        <div class="room-meta mb-3">
                            <div class="meta-item">
                                <i class="fas fa-expand"></i>
                                <span><?php echo $room['area']; ?> m²</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo $room['address']; ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-eye"></i>
                                <span><?php echo $room['count_view']; ?> lượt xem</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-walking"></i>
                                <span><?php echo $room['latlng']; ?> km đến ĐH Vinh</span>
                            </div>
                        </div>
                    </div>

                    <!-- Mô tả chi tiết -->
                    <div class="room-description mb-4">
                        <h3 class="mb-3">Mô tả chi tiết</h3>
                        <div class="description-content">
                            <?php echo nl2br($room['description']); ?>
                        </div>
                    </div>

                    <!-- Tiện ích -->
                    <div class="room-utilities mb-4">
                        <h3 class="mb-3">Tiện ích</h3>
                        <div class="utilities-list">
                            <?php foreach ($utilities as $utility): ?>
                            <div class="utility-item">
                                <i class="fas fa-check-circle"></i>
                                <span><?php echo $utility; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Bản đồ -->
                    <div class="room-location mb-4">
                        <h3 class="mb-3">Vị trí trên bản đồ</h3>
                        <div class="map-container">
                            <iframe 
                                width="100%" 
                                height="100%" 
                                frameborder="0" 
                                scrolling="no" 
                                marginheight="0" 
                                marginwidth="0" 
                                src="https://maps.google.com/maps?q=<?php echo urlencode($room['address']); ?>&z=15&output=embed" 
                                style="border:0;" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Thông tin chủ phòng trọ -->
                    <div class="owner-card">
                        <h3 class="mb-3">Thông tin chủ phòng</h3>
                        <div class="owner-info">
                            <img src="../<?php echo $room['owner_avatar']; ?>" alt="Chủ phòng" class="owner-avatar">
                            <div>
                                <h4 class="mb-1"><?php echo $room['owner_name']; ?></h4>
                                <p class="m-0 text-muted small">Chủ phòng trọ</p>
                            </div>
                        </div>
                        <a href="tel:<?php echo $room['owner_phone']; ?>" class="btn btn-primary contact-btn">
                            <i class="fas fa-phone-alt me-2"></i>
                            <?php echo $room['owner_phone']; ?>
                        </a>
                        <a href="sms:<?php echo $room['owner_phone']; ?>" class="btn btn-outline-primary contact-btn">
                            <i class="fas fa-sms me-2"></i>
                            Nhắn tin
                        </a>
                        <a href="https://zalo.me/<?php echo $room['owner_phone']; ?>" target="_blank" class="btn btn-info contact-btn text-white">
                            <i class="fas fa-comment-alt me-2"></i>
                            Nhắn qua Zalo
                        </a>
                    </div>

                    <!-- Báo cáo tin đăng -->
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="card-title">Báo cáo tin đăng</h5>
                            <p class="card-text small">Nếu bạn thấy tin đăng này có vấn đề, hãy báo cáo cho chúng tôi.</p>
                            <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#reportModal">
                                <i class="fas fa-flag me-2"></i>Báo cáo tin đăng
                            </button>
                        </div>
                    </div>

                    <!-- Chia sẻ -->
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="card-title">Chia sẻ tin đăng</h5>
                            <div class="d-flex gap-2 mt-3">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" target="_blank" class="btn btn-sm btn-primary flex-grow-1">
                                    <i class="fab fa-facebook-f me-2"></i>Facebook
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" target="_blank" class="btn btn-sm btn-info text-white flex-grow-1">
                                    <i class="fab fa-twitter me-2"></i>Twitter
                                </a>
                                <button class="btn btn-sm btn-success flex-grow-1" id="copyLinkBtn">
                                    <i class="fas fa-link me-2"></i>Copy Link
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Phòng trọ tương tự -->
            <section class="similar-rooms mt-5">
                <h2 class="similar-heading mb-4">Phòng trọ tương tự</h2>
                
                <div class="row">
                    <?php if ($similar_rooms->num_rows > 0): ?>
                        <?php while($sim_room = $similar_rooms->fetch_assoc()): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card room-card h-100">
                                    <div class="room-image">
                                        <img src="../<?php echo $sim_room['images']; ?>" class="card-img-top" alt="<?php echo $sim_room['title']; ?>">
                                        <span class="price-tag"><?php echo number_format($sim_room['price']); ?> đ/tháng</span>
                                        <span class="distance-tag"><i class="fas fa-walking me-1"></i><?php echo $sim_room['latlng']; ?> km</span>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="room_detail.php?id=<?php echo $sim_room['id']; ?>"><?php echo $sim_room['title']; ?></a>
                                        </h5>
                                        <p class="card-text address"><i class="fas fa-map-marker-alt me-2"></i><?php echo $sim_room['address']; ?></p>
                                        <div class="room-info">
                                            <span><i class="fas fa-expand me-1"></i><?php echo $sim_room['area']; ?> m²</span>
                                            <span><i class="fas fa-bolt me-1"></i>
                                                <?php 
                                                    $sim_utilities = explode(',', $sim_room['utilities']);
                                                    echo count($sim_utilities) . ' tiện ích';
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <small class="text-muted">Đăng bởi: <?php echo $sim_room['owner_name']; ?></small>
                                        <small class="text-muted float-end">
                                            <i class="far fa-clock me-1"></i>
                                            <?php 
                                                $date = new DateTime($sim_room['created_at']);
                                                echo $date->format('d/m/Y'); 
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">Không tìm thấy phòng trọ tương tự.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Modal báo cáo -->
    <div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Báo cáo tin đăng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="reportForm">
                        <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                        <div class="mb-3">
                            <label for="reportReason" class="form-label">Lý do báo cáo</label>
                            <select class="form-select" id="reportReason" name="reason" required>
                                <option value="">-- Chọn lý do --</option>
                                <option value="fake">Thông tin giả mạo</option>
                                <option value="sold">Phòng đã cho thuê nhưng chưa cập nhật</option>
                                <option value="wrong_price">Giá không chính xác</option>
                                <option value="spam">Spam / Lặp lại nhiều lần</option>
                                <option value="other">Lý do khác</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="reportDescription" class="form-label">Mô tả chi tiết</label>
                            <textarea class="form-control" id="reportDescription" name="description" rows="4" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger" id="submitReport">Gửi báo cáo</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../Components/footer.php' ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script src="../Assets/main.js"></script>
    <script>
        $(document).ready(function() {
            // Khởi tạo Swiper
            var swiper = new Swiper(".mySwiper", {
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
                pagination: {
                    el: ".swiper-pagination",
                    clickable: true,
                },
                loop: true,
            });

            // Xử lý click vào thumbnail
            $('.thumbnail-item').click(function() {
                var index = $(this).data('index');
                swiper.slideTo(index + 1);
                $('.thumbnail-item').removeClass('active');
                $(this).addClass('active');
            });

            // Cập nhật thumbnail khi slide thay đổi
            swiper.on('slideChange', function () {
                var realIndex = swiper.realIndex;
                $('.thumbnail-item').removeClass('active');
                $('.thumbnail-item[data-index="' + realIndex + '"]').addClass('active');
            });

            // Nút copy link
            $('#copyLinkBtn').click(function() {
                var dummy = document.createElement("textarea");
                document.body.appendChild(dummy);
                dummy.value = window.location.href;
                dummy.select();
                document.execCommand("copy");
                document.body.removeChild(dummy);
                
                // Hiển thị thông báo đã copy
                var originalText = $(this).html();
                $(this).html('<i class="fas fa-check me-2"></i>Đã copy');
                setTimeout(function() {
                    $('#copyLinkBtn').html(originalText);
                }, 2000);
            });

            // Gửi báo cáo
            $('#submitReport').click(function() {
                // Kiểm tra form
                var form = document.getElementById('reportForm');
                if (form.checkValidity()) {
                    // Đóng modal
                    $('#reportModal').modal('hide');
                    
                    // Hiển thị thông báo
                    alert('Cảm ơn bạn đã báo cáo. Chúng tôi sẽ xem xét tin đăng này trong thời gian sớm nhất.');
                    
                    // Reset form
                    $('#reportForm').trigger('reset');
                } else {
                    form.reportValidity();
                }
            });
        });
    </script>
</body>
</html>
