<?php
// Khởi tạo phiên làm việc
session_start();

// Kiểm tra xem người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Kết nối đến CSDL
require_once('../config/db.php');

// Khởi tạo mảng favorite_rooms từ CSDL
require_once('../config/favorites.php');

// Lấy thông tin người dùng hiện tại
$user_id = $_SESSION['user_id'];

// Debugging: Kiểm tra giá trị approve của các phòng
$debug_stmt = $conn->prepare("
    SELECT id, title, approve 
    FROM motel 
    WHERE user_id = ?
");
$debug_stmt->bind_param("i", $user_id);
$debug_stmt->execute();
$debug_result = $debug_stmt->get_result();

echo "<!-- Debug info: -->";
while ($debug_room = $debug_result->fetch_assoc()) {
    $status_text = "";
    if ($debug_room['approve'] == 0) $status_text = "Chờ duyệt";
    else if ($debug_room['approve'] == 1) $status_text = "Đã duyệt";
    else if ($debug_room['approve'] == 2) $status_text = "Đã hủy";
    else $status_text = "Không rõ (" . $debug_room['approve'] . ")";

    echo "<!-- Room ID: " . $debug_room['id'] . ", Title: " . $debug_room['title'] . ", Approve status: " . $debug_room['approve'] . " (" . $status_text . ") -->";
}

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
    <style>
        .approval-badge.cancelled {
            background-color: #dc3545;
            color: white;
        }

        .border-danger .card-title a {
            color: #dc3545;
        }

        .room-item:not(.animated) {
            transition: all 0.3s ease;
        }

        .filter-notice {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .room-card.border-danger {
            opacity: 0.85;
        }

        .empty-state {
            transition: opacity 0.3s ease-in-out;
        }

        .empty-state-visible {
            display: block !important;
            animation: fadeIn 0.5s;
        }

        .filter-empty-notice {
            display: none;
            margin: 20px auto;
            padding: 15px;
            border-radius: 8px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            max-width: 600px;
            text-align: center;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes statusPulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        .status-changed {
            animation: statusPulse 1s ease;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        }

        .tab-pane {
            min-height: 200px;
        }

        .room-card.border-success:hover {
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.4);
        }

        .room-card.border-warning:hover {
            box-shadow: 0 0 15px rgba(255, 193, 7, 0.4);
        }

        .room-card.border-danger:hover {
            box-shadow: 0 0 15px rgba(220, 53, 69, 0.4);
        }
    </style>
</head>

<body class="home-body">
    <?php include '../components/header.php' ?>

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

            <?php include '../components/room_status_notification.php'; ?>

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
                            <div class="col-12 mt-2">
                                <button type="button" id="resetFilters" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo me-1"></i>Xóa bộ lọc
                                </button>
                            </div>
                        </form>
                    </div>
                </div> <!-- Tabs điều hướng -->
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
                            <span class="badge bg-warning ms-1" id="pending-count">
                                <?php
                                $pending_count = 0;
                                foreach ($result as $room) {
                                    if ($room['approve'] == 0) {
                                        $pending_count++;
                                    }
                                }
                                echo $pending_count;
                                ?>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#approved-rooms">
                            <i class="fas fa-check-circle me-1"></i>Đã duyệt
                            <span class="badge bg-success ms-1" id="approved-count">
                                <?php
                                $approved_count = 0;
                                foreach ($result as $room) {
                                    if ($room['approve'] == 1) {
                                        $approved_count++;
                                    }
                                }
                                echo $approved_count;
                                ?>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#cancelled-rooms">
                            <i class="fas fa-ban me-1"></i>Đã hủy
                            <span class="badge bg-danger ms-1" id="cancelled-count">
                                <?php
                                $cancelled_count = 0;
                                foreach ($result as $room) {
                                    if ($room['approve'] == 2) {
                                        $cancelled_count++;
                                    }
                                }
                                echo $cancelled_count;
                                ?>
                            </span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Thông báo khi không có kết quả lọc -->
                    <div id="filter-empty-notice" class="filter-empty-notice">
                        <i class="fas fa-search me-2 fa-2x text-muted"></i>
                        <h4 class="mt-3">Không tìm thấy phòng nào</h4>
                        <p class="text-muted">Không tìm thấy kết quả nào phù hợp với tiêu chí tìm kiếm của bạn.<br>Vui lòng thử lại với các từ khóa hoặc bộ lọc khác.</p>
                    </div>

                    <!-- Tất cả phòng -->
                    <div class="tab-pane fade show active" id="all-rooms">
                        <div class="row">
                            <?php while ($room = $result->fetch_assoc()): ?>
                                <div class="col-md-6 mb-4 animated-element room-item"
                                    data-status="<?php
                                                    if ($room['approve'] == 1) {
                                                        echo 'approved';
                                                    } elseif ($room['approve'] == 2) {
                                                        echo 'cancelled';
                                                    } else {
                                                        echo 'pending';
                                                    }
                                                    ?>">
                                    <div class="card room-card h-100 <?php
                                                                        if ($room['approve'] == 1) {
                                                                            echo 'border-success';
                                                                        } elseif ($room['approve'] == 2) {
                                                                            echo 'border-danger';
                                                                        } else {
                                                                            echo 'border-warning';
                                                                        }
                                                                        ?>">
                                        <div class="room-image">
                                            <?php
                                            $images = explode(',', $room['images']);
                                            $firstImage = $images[0];
                                            ?>
                                            <img src="../<?php echo $firstImage; ?>" class="card-img-top" alt="<?php echo $room['title']; ?>">
                                            <span class="price-tag"><?php echo number_format($room['price']); ?> đ/tháng</span>
                                            <span class="view-count"><i class="fas fa-eye me-1"></i><?php echo number_format($room['count_view']); ?></span> <!-- Badge status -->
                                            <?php if ($room['approve'] == 1): ?>
                                                <span class="approval-badge approved">
                                                    <i class="fas fa-check-circle"></i> Đã duyệt
                                                </span>
                                            <?php elseif ($room['approve'] == 2): ?>
                                                <span class="approval-badge cancelled" style="background-color: #dc3545;">
                                                    <i class="fas fa-ban"></i> Đã hủy
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
                                        <div class="card-footer d-flex justify-content-between align-items-center"> <small class="text-muted">
                                                <?php if ($room['approve'] == 1): ?>
                                                    <span class="text-success"><i class="fas fa-check-circle me-1"></i>Đã duyệt</span>
                                                <?php elseif ($room['approve'] == 2): ?>
                                                    <span class="text-danger"><i class="fas fa-ban me-1"></i>Đã hủy</span>
                                                <?php else: ?>
                                                    <span class="text-warning"><i class="fas fa-clock me-1"></i>Chờ duyệt</span>
                                                <?php endif; ?>
                                            </small>
                                            <div> <a href="room_detail.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-primary me-1" title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_room.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-warning me-1" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="#" class="btn btn-sm btn-danger delete-room" data-id="<?php echo $room['id']; ?>" data-bs-toggle="modal" data-bs-target="#deleteModal" title="Xóa phòng">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
                                                    <a href="../admin/manage_rooms.php" class="btn btn-sm btn-secondary ms-1" title="Quản lý phòng">
                                                        <i class="fas fa-cog"></i>
                                                    </a>
                                                <?php endif; ?>
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
                        <div id="no-pending-rooms" class="empty-state text-center py-5" style="display: none;">
                            <div class="mb-4">
                                <i class="fas fa-clock text-warning fa-4x"></i>
                            </div>
                            <h3>Không có phòng nào đang chờ duyệt</h3>
                            <p class="text-muted mb-4">Tất cả các phòng trọ của bạn đã được duyệt hoặc bị hủy</p>
                        </div>
                    </div>

                    <!-- Phòng đã duyệt -->
                    <div class="tab-pane fade" id="approved-rooms">
                        <div class="row" id="approved-container"></div>
                        <div id="no-approved-rooms" class="empty-state text-center py-5" style="display: none;">
                            <div class="mb-4">
                                <i class="fas fa-check-circle text-success fa-4x"></i>
                            </div>
                            <h3>Không có phòng nào đã được duyệt</h3>
                            <p class="text-muted mb-4">Các phòng trọ của bạn đang chờ được quản trị viên duyệt</p>
                            <a href="post.php" class="btn btn-success">
                                <i class="fas fa-plus-circle me-2"></i>Đăng tin mới
                            </a>
                        </div>
                    </div>

                    <!-- Phòng đã hủy -->
                    <div class="tab-pane fade" id="cancelled-rooms">
                        <div class="row" id="cancelled-container"></div>
                        <div id="no-cancelled-rooms" class="empty-state text-center py-5" style="display: none;">
                            <div class="mb-4">
                                <i class="fas fa-ban text-danger fa-4x"></i>
                            </div>
                            <h3>Không có phòng nào bị hủy</h3>
                            <p class="text-muted mb-4">Tất cả các phòng của bạn đang hoạt động bình thường</p>
                        </div>
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

    <?php include '../components/footer.php' ?>
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

            // Debug - Hiển thị thông tin phòng
            console.log("Tổng số phòng: " + document.querySelectorAll('.room-item').length);

            // Phân loại phòng theo trạng thái
            const allRooms = document.querySelectorAll('.room-item');
            const pendingContainer = document.getElementById('pending-container');
            const approvedContainer = document.getElementById('approved-container');
            const cancelledContainer = document.getElementById('cancelled-container');
            const noPendingRooms = document.getElementById('no-pending-rooms');
            const noApprovedRooms = document.getElementById('no-approved-rooms');
            const noCancelledRooms = document.getElementById('no-cancelled-rooms');
            const filterEmptyNotice = document.getElementById('filter-empty-notice');

            let pendingCount = 0;
            let approvedCount = 0;
            let cancelledCount = 0;

            allRooms.forEach(room => {
                // Lấy giá trị thuộc tính data-status
                const status = room.getAttribute('data-status');
                // Debug - Kiểm tra trạng thái
                console.log("Room status: " + status + " - " + room.querySelector('.card-title').textContent);

                // Tạo bản sao phòng để thêm vào tab tương ứng
                const newRoomElement = document.createElement('div');
                newRoomElement.className = room.className;
                newRoomElement.innerHTML = room.innerHTML;

                // Gán lại các event listeners cho các nút trong phòng được sao chép
                const deleteBtn = newRoomElement.querySelector('.delete-room');
                if (deleteBtn) {
                    const roomId = deleteBtn.getAttribute('data-id');
                    deleteBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        document.getElementById('confirmDelete').href = 'delete_room.php?id=' + roomId;
                    });
                }

                // Chỉ định vào đúng container dựa trên trạng thái và đếm số lượng
                if (status === 'pending') {
                    pendingContainer.appendChild(newRoomElement);
                    pendingCount++;
                } else if (status === 'approved') {
                    approvedContainer.appendChild(newRoomElement);
                    approvedCount++;
                } else if (status === 'cancelled') {
                    cancelledContainer.appendChild(newRoomElement);
                    cancelledCount++;
                } else {
                    console.warn("Unknown status for room: " + status);
                }
            });

            // Hiển thị thông báo trống nếu không có phòng
            function updateEmptyStates() {
                // Cập nhật số lượng hiển thị
                document.getElementById('pending-count').textContent = pendingCount;
                document.getElementById('approved-count').textContent = approvedCount;
                document.getElementById('cancelled-count').textContent = cancelledCount;

                // Hiển thị thông báo trống phù hợp
                noPendingRooms.style.display = (pendingCount === 0) ? 'block' : 'none';
                noApprovedRooms.style.display = (approvedCount === 0) ? 'block' : 'none';
                noCancelledRooms.style.display = (cancelledCount === 0) ? 'block' : 'none';
            }

            updateEmptyStates();

            // Xử lý xóa phòng trong tab "Tất cả"
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
                const keyword = searchKeyword.value.toLowerCase().trim();
                const category = filterCategory.value.toLowerCase();
                const district = filterDistrict.value.toLowerCase();

                // Lấy tab đang active để lọc đúng container
                const activeTab = document.querySelector('.tab-pane.fade.show.active');
                const roomsInActiveTab = activeTab.querySelectorAll('.room-item');

                console.log("Filtering in tab:", activeTab.id, "with", roomsInActiveTab.length, "rooms");

                // Đếm số phòng được hiển thị sau khi lọc
                let visibleCount = 0;

                roomsInActiveTab.forEach(room => {
                    const title = room.querySelector('.card-title').textContent.toLowerCase();
                    const address = room.querySelector('.address').textContent.toLowerCase();
                    const roomCategory = room.querySelector('.badge.bg-light.text-dark:last-child')?.textContent.toLowerCase() || '';
                    const roomDistrict = room.querySelector('.badge.bg-light.text-dark:first-child')?.textContent.toLowerCase() || '';

                    // Kiểm tra điều kiện lọc
                    const matchesKeyword = keyword === '' || title.includes(keyword) || address.includes(keyword);
                    const matchesCategory = category === '' || roomCategory.includes(category);
                    const matchesDistrict = district === '' || roomDistrict.includes(district);

                    // Hiển thị hoặc ẩn phòng
                    if (matchesKeyword && matchesCategory && matchesDistrict) {
                        room.style.display = '';
                        visibleCount++;
                    } else {
                        room.style.display = 'none';
                    }
                });

                // Hiển thị hoặc ẩn thông báo không có kết quả
                const emptyStateElement = activeTab.querySelector('.empty-state');

                // Chỉ hiển thị thông báo lọc trống khi có điều kiện lọc
                const hasFilterCondition = keyword !== '' || category !== '' || district !== '';

                // Ẩn thông báo trạng thái trống nếu đang lọc
                if (emptyStateElement) {
                    emptyStateElement.style.display = hasFilterCondition ? 'none' : ((visibleCount === 0) ? 'block' : 'none');
                }

                // Hiển thị thông báo lọc trống
                filterEmptyNotice.style.display = (visibleCount === 0 && hasFilterCondition) ? 'block' : 'none';

                console.log("Filter results:", visibleCount, "visible rooms");
            }

            // Gắn sự kiện lắng nghe
            searchKeyword.addEventListener('input', filterRooms);
            filterCategory.addEventListener('change', filterRooms);
            filterDistrict.addEventListener('change', filterRooms);

            // Nút xóa bộ lọc
            const clearFiltersButton = document.createElement('button');
            clearFiltersButton.className = 'btn btn-outline-secondary mt-3';
            clearFiltersButton.innerHTML = '<i class="fas fa-times me-2"></i>Xóa bộ lọc';
            clearFiltersButton.style.display = 'none';

            filterEmptyNotice.appendChild(clearFiltersButton);

            clearFiltersButton.addEventListener('click', function() {
                searchKeyword.value = '';
                filterCategory.value = '';
                filterDistrict.value = '';
                filterRooms();
            });
            // Xử lý nút reset bộ lọc
            const resetFiltersBtn = document.getElementById('resetFilters');

            resetFiltersBtn.addEventListener('click', function() {
                searchKeyword.value = '';
                filterCategory.value = '';
                filterDistrict.value = '';
                filterRooms();

                // Đặt lại hiển thị thông báo trạng thái trống
                document.querySelectorAll('.empty-state').forEach(state => {
                    const containerId = state.id.replace('no-', '').replace('-rooms', '-container');
                    const container = document.getElementById(containerId);
                    if (container && container.children.length === 0) {
                        state.style.display = 'block';
                    } else {
                        state.style.display = 'none';
                    }
                });

                // Ẩn thông báo lọc trống
                filterEmptyNotice.style.display = 'none';
            });

            // Theo dõi trạng thái bộ lọc
            function updateFilterButtonVisibility() {
                const hasFilter = searchKeyword.value || filterCategory.value || filterDistrict.value;
                clearFiltersButton.style.display = hasFilter ? 'inline-block' : 'none';
                resetFiltersBtn.classList.toggle('disabled', !hasFilter);
            }

            searchKeyword.addEventListener('input', updateFilterButtonVisibility);
            filterCategory.addEventListener('change', updateFilterButtonVisibility);
            filterDistrict.addEventListener('change', updateFilterButtonVisibility);

            // Thêm event listener cho các tab khi chuyển tab
            document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(tab => {
                tab.addEventListener('shown.bs.tab', function(event) {
                    console.log("Tab switched to:", event.target.getAttribute('href'));
                    filterRooms();
                });
            });
        });
    </script>
</body>

</html>