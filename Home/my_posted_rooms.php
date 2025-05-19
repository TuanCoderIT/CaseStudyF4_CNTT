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

// Khởi tạo mảng favorite_rooms từ CSDL
require_once('../config/favorites.php');

// Lấy thông tin người dùng hiện tại
$user_id = $_SESSION['user_id'];

// Lấy phòng trọ đã đăng của người dùng
$stmt = $conn->prepare("
    SELECT m.*, c.name as category_name, d.name as district_name
    FROM motel m 
    LEFT JOIN categories c ON m.category_id = c.id
    LEFT JOIN districts d ON m.district_id = d.id
    WHERE m.user_id = ?
    ORDER BY m.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phòng trọ đã đăng - Phòng trọ sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>

<body class="home-body">
    <?php include '../Components/header.php' ?>

    <main class="py-5 mt-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="section-title">
                    <i class="fas fa-list-alt me-2 text-primary"></i>Phòng trọ của tôi
                </h1>
                <a href="post.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Đăng tin mới
                </a>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if ($result->num_rows > 0): ?> <!-- Tìm kiếm và lọc -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form id="searchForm" class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="searchKeyword" placeholder="Tìm kiếm theo tiêu đề, địa chỉ...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="filterCategory">
                                    <option value="">Tất cả loại phòng</option>
                                    <?php
                                    $categories_query = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");
                                    while ($cat = mysqli_fetch_assoc($categories_query)) {
                                        echo '<option value="' . $cat['name'] . '">' . $cat['name'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="filterDistrict">
                                    <option value="">Tất cả khu vực</option>
                                    <?php
                                    $districts_query = mysqli_query($conn, "SELECT * FROM districts ORDER BY name");
                                    while ($dist = mysqli_fetch_assoc($districts_query)) {
                                        echo '<option value="' . $dist['name'] . '">' . $dist['name'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabs điều hướng -->
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#all-rooms">
                            <i class="fas fa-th-list me-1"></i>Tất cả
                            <span class="badge bg-secondary ms-1"><?php echo $result->num_rows; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#pending-rooms">
                            <i class="fas fa-clock me-1"></i>Chờ duyệt
                            <span class="badge bg-warning ms-1" id="pending-count">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#approved-rooms">
                            <i class="fas fa-check-circle me-1"></i>Đã duyệt
                            <span class="badge bg-success ms-1" id="approved-count">0</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Tất cả phòng -->
                    <div class="tab-pane fade show active" id="all-rooms">
                        <div class="row">
                            <?php while ($room = $result->fetch_assoc()): ?>
                                <div class="col-md-6 mb-4 animated-element room-item"
                                    data-status="<?php echo $room['approve'] ? 'approved' : 'pending'; ?>">
                                    <div class="card room-card h-100 <?php echo $room['approve'] ? 'border-success' : 'border-warning'; ?>">
                                        <div class="room-image">
                                            <?php
                                            $images = explode(',', $room['images']);
                                            $firstImage = $images[0];
                                            ?>
                                            <img src="../<?php echo $firstImage; ?>" class="card-img-top" alt="<?php echo $room['title']; ?>">
                                            <span class="price-tag"><?php echo number_format($room['price']); ?> đ/tháng</span>
                                            <span class="view-count"><i class="fas fa-eye me-1"></i><?php echo number_format($room['count_view']); ?></span>

                                            <!-- Badge status -->
                                            <?php if ($room['approve'] == 1): ?>
                                                <span class="approval-badge approved">
                                                    <i class="fas fa-check-circle"></i> Đã duyệt
                                                </span>
                                            <?php else: ?>
                                                <span class="approval-badge pending">
                                                    <i class="fas fa-clock"></i> Chờ duyệt
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <a href="room_detail.php?id=<?php echo $room['id']; ?>"><?php echo $room['title']; ?></a>
                                            </h5>
                                            <p class="card-text address"><i class="fas fa-map-marker-alt me-2"></i><?php echo $room['address']; ?></p>
                                            <div class="room-info">
                                                <span><i class="fas fa-expand me-1"></i><?php echo $room['area']; ?> m²</span>
                                                <span><i class="fas fa-calendar-alt me-1"></i>
                                                    <?php
                                                    $date = new DateTime($room['created_at']);
                                                    echo $date->format('d/m/Y');
                                                    ?>
                                                </span>
                                            </div>
                                            <div class="mt-2">
                                                <?php if ($room['district_name']): ?>
                                                    <span class="badge bg-light text-dark me-1"><?php echo $room['district_name']; ?></span>
                                                <?php endif; ?>
                                                <?php if ($room['category_name']): ?>
                                                    <span class="badge bg-light text-dark"><?php echo $room['category_name']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="card-footer d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <?php if ($room['approve'] == 1): ?>
                                                    <span class="text-success"><i class="fas fa-check-circle me-1"></i>Đã duyệt</span>
                                                <?php else: ?>
                                                    <span class="text-warning"><i class="fas fa-clock me-1"></i>Chờ duyệt</span>
                                                <?php endif; ?>
                                            </small>
                                            <div>
                                                <a href="room_detail.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-primary me-1" title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_room.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-warning me-1" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="#" class="btn btn-sm btn-danger delete-room" data-id="<?php echo $room['id']; ?>" data-bs-toggle="modal" data-bs-target="#deleteModal" title="Xóa phòng">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Phòng chờ duyệt -->
                    <div class="tab-pane fade" id="pending-rooms">
                        <div class="row" id="pending-container"></div>
                    </div>

                    <!-- Phòng đã duyệt -->
                    <div class="tab-pane fade" id="approved-rooms">
                        <div class="row" id="approved-container"></div>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-5 empty-posted-rooms">
                    <div class="mb-4">
                        <i class="fas fa-home text-muted fa-4x"></i>
                    </div>
                    <h3>Bạn chưa đăng tin phòng trọ nào</h3>
                    <p class="text-muted mb-4">Hãy đăng tin ngay để tiếp cận hàng ngàn người tìm trọ</p>
                    <a href="post.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus-circle me-2"></i>Đăng tin ngay
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main> <!-- Modal xác nhận xóa phòng -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Xác nhận xóa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn xóa phòng trọ này không? Hành động này không thể hoàn tác.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <a href="#" id="confirmDelete" class="btn btn-danger">Xóa phòng</a>
                </div>
            </div>
        </div>
    </div>

    <?php include '../Components/footer.php' ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hiệu ứng xuất hiện cho các phòng
            const animatedElements = document.querySelectorAll('.animated-element');

            animatedElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('animated', 'animate__fadeInUp');
                }, index * 100);
            });

            // Phân loại phòng theo trạng thái
            const allRooms = document.querySelectorAll('.room-item');
            const pendingContainer = document.getElementById('pending-container');
            const approvedContainer = document.getElementById('approved-container');
            let pendingCount = 0;
            let approvedCount = 0;

            allRooms.forEach(room => {
                const status = room.getAttribute('data-status');
                const clonedRoom = room.cloneNode(true);

                if (status === 'pending') {
                    pendingContainer.appendChild(clonedRoom);
                    pendingCount++;
                } else if (status === 'approved') {
                    approvedContainer.appendChild(clonedRoom);
                    approvedCount++;
                }
            });
            // Cập nhật số lượng
            document.getElementById('pending-count').textContent = pendingCount;
            document.getElementById('approved-count').textContent = approvedCount;

            // Xử lý xóa phòng
            const deleteButtons = document.querySelectorAll('.delete-room');
            const confirmDeleteButton = document.getElementById('confirmDelete');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const roomId = this.getAttribute('data-id');
                    confirmDeleteButton.href = 'delete_room.php?id=' + roomId;
                });
            });

            // Tìm kiếm và lọc
            const searchKeyword = document.getElementById('searchKeyword');
            const filterCategory = document.getElementById('filterCategory');
            const filterDistrict = document.getElementById('filterDistrict');

            // Hàm lọc phòng
            function filterRooms() {
                const keyword = searchKeyword.value.toLowerCase();
                const category = filterCategory.value.toLowerCase();
                const district = filterDistrict.value.toLowerCase();

                document.querySelectorAll('.room-item').forEach(room => {
                    const title = room.querySelector('.card-title').textContent.toLowerCase();
                    const address = room.querySelector('.address').textContent.toLowerCase();
                    const roomCategory = room.querySelector('.badge.bg-light.text-dark:last-child')?.textContent.toLowerCase() || '';
                    const roomDistrict = room.querySelector('.badge.bg-light.text-dark:first-child')?.textContent.toLowerCase() || '';

                    // Kiểm tra điều kiện lọc
                    const matchesKeyword = title.includes(keyword) || address.includes(keyword);
                    const matchesCategory = category === '' || roomCategory === category;
                    const matchesDistrict = district === '' || roomDistrict === district;

                    // Hiển thị hoặc ẩn phòng
                    if (matchesKeyword && matchesCategory && matchesDistrict) {
                        room.style.display = '';
                    } else {
                        room.style.display = 'none';
                    }
                });
            }

            // Gắn sự kiện lắng nghe
            searchKeyword.addEventListener('input', filterRooms);
            filterCategory.addEventListener('change', filterRooms);
            filterDistrict.addEventListener('change', filterRooms);
        });
    </script>
</body>

</html>