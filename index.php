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

// Lấy danh sách phòng trọ xem nhiều nhất
$stmt_most_viewed = $conn->prepare("
    SELECT m.*, u.name as owner_name 
    FROM motel m 
    LEFT JOIN users u ON m.user_id = u.id 
    WHERE m.approve = 1 
    ORDER BY m.count_view DESC 
    LIMIT 4
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
    LIMIT 4
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
    LIMIT 4
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
    <link rel="stylesheet" href="./Assets/client/css/style.css">
    <!-- Link tới thư viện Swiper cho slider -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">

</head>

<body class="home-body">
    <?php include './components/header.php' ?>

    <!-- Banner tìm kiếm -->
    <?php include './components/banner_search.php' ?>
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
                    <?php if ($most_viewed_rooms->num_rows > 0): ?> <?php while ($room = $most_viewed_rooms->fetch_assoc()): ?>
                            <div class="col-md-3 mb-4">
                                <div class="card room-card four-col h-100">
                                    <div class="room-image">
                                        <img src="./<?php echo $room['images']; ?>" class="card-img-top" alt="<?php echo $room['title']; ?>">
                                        <span class="price-tag"><?php echo number_format($room['price']); ?> đ/tháng</span>
                                        <span class="view-count"><i class="fas fa-eye me-1"></i><?php echo number_format($room['count_view']); ?></span>
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
                    <?php if ($newest_rooms->num_rows > 0): ?> <?php while ($room = $newest_rooms->fetch_assoc()): ?>
                            <div class="col-md-3 mb-4">
                                <div class="card room-card four-col h-100">
                                    <div class="room-image">
                                        <img src="./<?php echo $room['images']; ?>" class="card-img-top" alt="<?php echo $room['title']; ?>">
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
                    <?php if ($nearest_rooms->num_rows > 0): ?> <?php while ($room = $nearest_rooms->fetch_assoc()): ?>
                            <div class="col-md-3 mb-4">
                                <div class="card room-card four-col h-100">
                                    <div class="room-image">
                                        <img src="./<?php echo $room['images']; ?>" class="card-img-top" alt="<?php echo $room['title']; ?>">
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

    <?php include './components/footer.php' ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script src="/Assets/admin/js/main.js"></script>
    <script>
        if (<?= isset($_SESSION['user_id']) && !isset($_SESSION['lat']) ? 'true' : 'false' ?>) {
            // Đảm bảo xử lý kết quả bất đồng bộ đúng cách
            getLocation().then(data => {
                console.log("Vị trí:", data.raw.position.lat, data.raw.position.lng);

                if (data.success) {
                    // alert("Tọa độ của bạn là: " + data.lat + ", " + data.lng);
                    // Lưu tọa độ vào session
                    fetch('/api/location/save_coordinates.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            lat: data.raw.position.lat,
                            lng: data.raw.position.lng,
                        })
                    }).then(response => response.json()).then(result => {
                        if (result.success) {
                            alert();
                        } else {
                            console.error("Lỗi khi lưu tọa độ:", result.message);
                        }
                    });
                } else {
                    console.error("Lỗi khi lấy tọa độ:", data.message);
                }
            }).catch(error => {
                console.error("Lỗi khi lấy vị trí:", error);
            });
        }
    </script>
</body>

</html>