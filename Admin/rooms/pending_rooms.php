<?php
session_start();
require_once '../../config/db.php';

// Kiểm tra đăng nhập với quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header('Location: ../../auth/login.php?message=Bạn cần đăng nhập với tài khoản admin');
    exit();
}

// Xử lý duyệt phòng trọ
if (isset($_GET['approve']) && !empty($_GET['approve'])) {
    $id = mysqli_real_escape_string($conn, $_GET['approve']);

    // Lấy thông tin phòng trọ và người đăng
    $room_query = mysqli_query($conn, "SELECT m.*, u.id as user_id, u.name as user_name, m.title 
                                      FROM motel m 
                                      JOIN users u ON m.user_id = u.id 
                                      WHERE m.id = '$id'");
    $room_data = mysqli_fetch_assoc($room_query);

    // Cập nhật trạng thái phòng trọ
    mysqli_query($conn, "UPDATE motel SET approve = 1 WHERE id = '$id'");

    // Tạo thông báo cho chủ phòng trọ
    if (!empty($room_data)) {
        $user_id = $room_data['user_id'];
        $room_title = $room_data['title'];
        $notification_title = "Phòng trọ đã được phê duyệt";
        $notification_message = "Phòng trọ \"$room_title\" của bạn đã được phê duyệt và hiển thị công khai trên hệ thống.";

        mysqli_query($conn, "INSERT INTO notifications (user_id, title, message) 
                           VALUES ('$user_id', '$notification_title', '$notification_message')");
    }

    $_SESSION['success'] = "Đã duyệt phòng trọ thành công!";
    header('Location: pending_rooms.php');
    exit();
}

// Xử lý từ chối có lý do
if (isset($_POST['reject_with_reason']) && isset($_POST['room_id']) && !empty($_POST['room_id'])) {
    $id = mysqli_real_escape_string($conn, $_POST['room_id']);
    $reason = mysqli_real_escape_string($conn, $_POST['reject_reason']);

    // Lấy thông tin phòng trọ và người đăng trước khi xóa
    $room_query = mysqli_query($conn, "SELECT m.*, u.id as user_id, u.name as user_name, m.title 
                                      FROM motel m 
                                      JOIN users u ON m.user_id = u.id 
                                      WHERE m.id = '$id'");
    $room_data = mysqli_fetch_assoc($room_query);

    // Lấy thông tin ảnh để xóa file
    $get_image = mysqli_query($conn, "SELECT images FROM motel WHERE id = '$id'");
    $image_data = mysqli_fetch_assoc($get_image);

    if (!empty($image_data['images'])) {
        $image_path = '../' . $image_data['images'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    // Xóa phòng trọ
    mysqli_query($conn, "DELETE FROM motel WHERE id = '$id'");

    // Tạo thông báo cho chủ phòng trọ về việc từ chối
    if (!empty($room_data)) {
        $user_id = $room_data['user_id'];
        $room_title = $room_data['title'];
        $notification_title = "Phòng trọ không được phê duyệt";
        $notification_message = "Phòng trọ \"$room_title\" của bạn không được phê duyệt. Lý do: $reason";

        mysqli_query($conn, "INSERT INTO notifications (user_id, title, message) 
                           VALUES ('$user_id', '$notification_title', '$notification_message')");
    }

    $_SESSION['success'] = "Đã từ chối phòng trọ với lý do đã cung cấp!";
    header('Location: pending_rooms.php');
    exit();
}

// Xử lý từ chối/xóa phòng trọ (không có lý do)
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);

    // Lấy thông tin phòng trọ và người đăng trước khi xóa
    $room_query = mysqli_query($conn, "SELECT m.*, u.id as user_id, u.name as user_name, m.title 
                                      FROM motel m 
                                      JOIN users u ON m.user_id = u.id 
                                      WHERE m.id = '$id'");
    $room_data = mysqli_fetch_assoc($room_query);

    // Lấy thông tin ảnh để xóa file
    $get_image = mysqli_query($conn, "SELECT images FROM motel WHERE id = '$id'");
    $image_data = mysqli_fetch_assoc($get_image);

    if (!empty($image_data['images'])) {
        $image_path = '../' . $image_data['images'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    // Xóa phòng trọ
    mysqli_query($conn, "DELETE FROM motel WHERE id = '$id'");

    // Tạo thông báo cho chủ phòng trọ về việc từ chối
    if (!empty($room_data)) {
        $user_id = $room_data['user_id'];
        $room_title = $room_data['title'];
        $notification_title = "Phòng trọ không được phê duyệt";
        $notification_message = "Phòng trọ \"$room_title\" của bạn không đáp ứng tiêu chuẩn của hệ thống và đã bị từ chối. Vui lòng liên hệ admin để biết thêm chi tiết.";

        mysqli_query($conn, "INSERT INTO notifications (user_id, title, message) 
                           VALUES ('$user_id', '$notification_title', '$notification_message')");
    }

    $_SESSION['success'] = "Đã từ chối và xóa phòng trọ!";
    header('Location: pending_rooms.php');
    exit();
}

// Truy vấn danh sách phòng trọ chưa duyệt
$query = "SELECT m.*, c.name as category_name, d.name as district_name, u.name as user_name, u.phone as user_phone 
          FROM motel m 
          LEFT JOIN categories c ON m.category_id = c.id 
          LEFT JOIN districts d ON m.district_id = d.id 
          LEFT JOIN users u ON m.user_id = u.id 
          WHERE m.approve = 0
          ORDER BY m.created_at DESC";

$result = mysqli_query($conn, $query);

$page_title = "Duyệt phòng trọ";
include_once '../../components/admin_header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Duyệt phòng trọ</h2>
        <span class="badge bg-warning fs-6">
            <i class="fas fa-clock me-1"></i> <?php echo mysqli_num_rows($result); ?> phòng chờ duyệt
        </span>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Lọc và tìm kiếm -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="searchPending" placeholder="Tìm kiếm theo tiêu đề, địa chỉ, người đăng...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="filterPendingCategory">
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
                    <select class="form-control" id="filterPendingDistrict">
                        <option value="">Tất cả khu vực</option>
                        <?php
                        $districts_query = mysqli_query($conn, "SELECT * FROM districts ORDER BY name");
                        while ($dist = mysqli_fetch_assoc($districts_query)) {
                            echo '<option value="' . $dist['name'] . '">' . $dist['name'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($room = mysqli_fetch_assoc($result)): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <h5><?php echo $room['title']; ?></h5>
                            <span class="badge badge-warning">Chưa duyệt</span>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-5">
                                    <?php if (!empty($room['images'])): ?>
                                        <img src="/<?php echo $room['images']; ?>" class="img-fluid img-thumbnail" alt="<?php echo $room['title']; ?>">
                                    <?php else: ?>
                                        <div class="text-center p-3 bg-light">Không có ảnh</div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-7">
                                    <p><strong>Giá:</strong> <?php echo number_format($room['price']); ?> VNĐ</p>
                                    <p><strong>Diện tích:</strong> <?php echo $room['area']; ?> m²</p>
                                    <p><strong>Địa chỉ:</strong> <?php echo $room['address']; ?></p>
                                    <p><strong>Danh mục:</strong> <?php echo $room['category_name'] ?? 'Chưa phân loại'; ?></p>
                                    <p><strong>Khu vực:</strong> <?php echo $room['district_name'] ?? 'Chưa xác định'; ?></p>
                                    <p><strong>Người đăng:</strong> <?php echo $room['user_name']; ?> (<?php echo $room['user_phone']; ?>)</p>
                                    <p><strong>Ngày đăng:</strong> <?php echo date('d/m/Y H:i', strtotime($room['created_at'])); ?></p>
                                </div>
                            </div>

                            <?php if (!empty($room['description'])): ?>
                                <div class="mt-3">
                                    <h6>Mô tả:</h6>
                                    <p><?php echo nl2br($room['description']); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($room['utilities'])): ?>
                                <div class="mt-2">
                                    <h6>Tiện ích:</h6>
                                    <p><?php echo $room['utilities']; ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-footer d-flex justify-content-between">
                            <div>
                                <a href="?approve=<?php echo $room['id']; ?>" class="btn btn-success" onclick="return confirm('Bạn có chắc muốn duyệt phòng trọ này?')">
                                    <i class="fas fa-check"></i> Duyệt
                                </a>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal" data-room-id="<?php echo $room['id']; ?>">
                                    <i class="fas fa-times"></i> Từ chối với lý do
                                </button>
                                <a href="?delete=<?php echo $room['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Bạn có chắc muốn từ chối và xóa phòng trọ này không cần cung cấp lý do?')">
                                    <i class="fas fa-ban"></i> Từ chối trực tiếp
                                </a>
                            </div>
                            <a href="edit_room.php?id=<?php echo $room['id']; ?>" class="btn btn-info">
                                <i class="fas fa-edit"></i> Chỉnh sửa
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    Không có phòng trọ nào đang chờ duyệt.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-4">
        <a href="../index.php" class="btn btn-secondary">Quay lại</a>
    </div>
</div>

<!-- Modal từ chối phòng trọ -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">Từ chối phòng trọ</h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <input type="hidden" name="room_id" id="rejectRoomId">
                    <div class="form-group">
                        <label for="rejectReason">Lý do từ chối:</label>
                        <textarea class="form-control" id="rejectReason" name="reject_reason" rows="3" required placeholder="Nhập lý do từ chối phòng trọ này..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" name="reject_with_reason" class="btn btn-danger">Từ chối</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Lọc và tìm kiếm phòng đang chờ duyệt
        const searchPending = document.getElementById('searchPending');
        const filterPendingCategory = document.getElementById('filterPendingCategory');
        const filterPendingDistrict = document.getElementById('filterPendingDistrict');

        function filterPendingRooms() {
            const keyword = searchPending.value.toLowerCase();
            const category = filterPendingCategory.value.toLowerCase();
            const district = filterPendingDistrict.value.toLowerCase();

            document.querySelectorAll('.col-lg-6.mb-4').forEach(room => {
                const title = room.querySelector('.card-header h5').textContent.toLowerCase();
                const address = room.querySelector('p:nth-child(3)').textContent.toLowerCase();
                const userName = room.querySelector('p:nth-child(6)').textContent.toLowerCase();
                const roomCategory = room.querySelector('p:nth-child(4)').textContent.toLowerCase();
                const roomDistrict = room.querySelector('p:nth-child(5)').textContent.toLowerCase();

                // Kiểm tra điều kiện lọc
                const matchesKeyword = title.includes(keyword) ||
                    address.includes(keyword) ||
                    userName.includes(keyword);

                const matchesCategory = category === '' ||
                    roomCategory.includes(category.toLowerCase());

                const matchesDistrict = district === '' ||
                    roomDistrict.includes(district.toLowerCase());

                // Hiển thị hoặc ẩn phòng
                if (matchesKeyword && matchesCategory && matchesDistrict) {
                    room.style.display = '';
                } else {
                    room.style.display = 'none';
                }
            });
        }

        // Gắn sự kiện lắng nghe
        if (searchPending) {
            searchPending.addEventListener('input', filterPendingRooms);
        }
        if (filterPendingCategory) {
            filterPendingCategory.addEventListener('change', filterPendingRooms);
        }
        if (filterPendingDistrict) {
            filterPendingDistrict.addEventListener('change', filterPendingRooms);
        }

        // Xử lý modal từ chối phòng trọ
        const rejectModal = document.getElementById('rejectModal');
        if (rejectModal) {
            rejectModal.addEventListener('show.bs.modal', function(event) {
                // Lấy ID phòng từ nút "Từ chối"
                const button = event.relatedTarget;
                const roomId = button.getAttribute('data-room-id');

                // Đặt ID phòng vào form
                const roomIdInput = document.getElementById('rejectRoomId');
                if (roomIdInput) {
                    roomIdInput.value = roomId;
                }
            });
        }

        // Hiệu ứng fadeIn cho các phòng
        document.querySelectorAll('.col-lg-6.mb-4').forEach((el, index) => {
            el.style.opacity = '0';
            setTimeout(() => {
                el.style.transition = 'opacity 0.5s ease-in-out';
                el.style.opacity = '1';
            }, index * 100);
        });
    });
</script>

<?php include_once '../../components/admin_footer.php'; ?>