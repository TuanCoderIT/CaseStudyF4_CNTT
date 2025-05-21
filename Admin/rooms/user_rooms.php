<?php
session_start();
require_once '../config/db.php';

// Kiểm tra đăng nhập với quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header('Location: ../../Auth/login.php?message=Bạn cần đăng nhập với tài khoản admin');
    exit();
}

// Kiểm tra user_id
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    header('Location: ../users/manage_users.php?error=ID người dùng không hợp lệ');
    exit();
}

$user_id = mysqli_real_escape_string($conn, $_GET['user_id']);

// Lấy thông tin người dùng
$user_query = "SELECT * FROM users WHERE id = '$user_id'";
$user_result = mysqli_query($conn, $user_query);

if (mysqli_num_rows($user_result) == 0) {
    header('Location: ../users/manage_users.php?error=Không tìm thấy người dùng');
    exit();
}

$user = mysqli_fetch_assoc($user_result);

// Xử lý xóa phòng trọ
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $room_id = mysqli_real_escape_string($conn, $_GET['delete']);

    // Lấy thông tin ảnh để xóa file
    $get_image = mysqli_query($conn, "SELECT images FROM motel WHERE id = '$room_id' AND user_id = '$user_id'");

    if (mysqli_num_rows($get_image) > 0) {
        $image_data = mysqli_fetch_assoc($get_image);

        if (!empty($image_data['images'])) {
            $image_path = '../' . $image_data['images'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        // Xóa phòng trọ
        mysqli_query($conn, "DELETE FROM motel WHERE id = '$room_id' AND user_id = '$user_id'");

        $_SESSION['success'] = "Đã xóa phòng trọ thành công!";
    } else {
        $_SESSION['error'] = "Không tìm thấy phòng trọ hoặc phòng này không thuộc người dùng này!";
    }

    header("Location: user_rooms.php?user_id=$user_id");
    exit();
}

// Xử lý chuyển quyền sở hữu phòng trọ
if (isset($_POST['transfer_ownership'])) {
    $room_id = mysqli_real_escape_string($conn, $_POST['room_id']);
    $new_owner_id = mysqli_real_escape_string($conn, $_POST['new_owner_id']);

    $query = "UPDATE motel SET user_id = '$new_owner_id' WHERE id = '$room_id' AND user_id = '$user_id'";

    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Đã chuyển quyền sở hữu phòng trọ thành công!";
    } else {
        $_SESSION['error'] = "Lỗi khi chuyển quyền sở hữu: " . mysqli_error($conn);
    }

    header("Location: user_rooms.php?user_id=$user_id");
    exit();
}

// Lấy danh sách phòng trọ của người dùng
$rooms_query = "SELECT m.*, c.name as category_name, d.name as district_name 
               FROM motel m 
               LEFT JOIN categories c ON m.category_id = c.id 
               LEFT JOIN districts d ON m.district_id = d.id 
               WHERE m.user_id = '$user_id'
               ORDER BY m.created_at DESC";
$rooms_result = mysqli_query($conn, $rooms_query);

// Lấy danh sách người dùng khác để chuyển quyền sở hữu
$users_query = "SELECT id, name, username FROM users WHERE id != '$user_id' ORDER BY name";
$users_result = mysqli_query($conn, $users_query);

$page_title = "Quản lý phòng trọ của người dùng: " . $user['name'];
include_once '../../Components/admin_header.php';
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2><i class="fas fa-user-cog mr-2"></i> Phòng trọ của người dùng: <?php echo $user['name']; ?></h2>
        <a href="../users/manage_users.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle mr-2"></i>
        <?php
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?php
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-gradient-primary text-white">
        <h5 class="m-0 font-weight-bold"><i class="fas fa-user-circle mr-2"></i>Thông tin người dùng</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-2 text-center mb-3 mb-md-0">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?php echo $user['avatar']; ?>" class="img-profile rounded-circle shadow" style="width:120px; height:120px; object-fit:cover;" alt="<?php echo $user['name']; ?>">
                <?php else: ?>
                    <img src="images/default-avatar.jpg" class="img-profile rounded-circle shadow" style="width:120px; height:120px; object-fit:cover;" alt="<?php echo $user['name']; ?>">
                <?php endif; ?>
            </div>
            <div class="col-md-10">
                <div class="table-responsive">
                    <table class="table table-borderless">
                        <tr>
                            <th width="150" class="text-muted"><i class="fas fa-id-card-alt mr-2"></i>ID:</th>
                            <td><?php echo $user['id']; ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted"><i class="fas fa-user mr-2"></i>Tên:</th>
                            <td class="font-weight-bold"><?php echo $user['name']; ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted"><i class="fas fa-envelope mr-2"></i>Email:</th>
                            <td><?php echo $user['email']; ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted"><i class="fas fa-user-tag mr-2"></i>Tên đăng nhập:</th>
                            <td><?php echo $user['username']; ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted"><i class="fas fa-phone-alt mr-2"></i>Số điện thoại:</th>
                            <td><?php echo $user['phone']; ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted"><i class="fas fa-user-shield mr-2"></i>Vai trò:</th>
                            <td>
                                <?php if ($user['role'] == 1): ?>
                                    <span class="badge badge-pill badge-primary">Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-pill badge-secondary">Người dùng</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="m-0 font-weight-bold"><i class="fas fa-building mr-2"></i>Danh sách phòng trọ</h5>
        <span class="badge badge-light badge-pill">
            <?php echo mysqli_num_rows($rooms_result); ?> phòng
        </span>
    </div>
    <div class="card-body">
        <?php if (mysqli_num_rows($rooms_result) > 0): ?>
            <div class="row">
                <?php while ($room = mysqli_fetch_assoc($rooms_result)): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card room-card h-100 shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center py-2">
                                <h6 class="m-0 font-weight-bold text-truncate" title="<?php echo $room['title']; ?>">
                                    <i class="fas fa-home mr-1"></i> <?php echo $room['title']; ?>
                                </h6>
                                <?php if ($room['approve'] == 1): ?>
                                    <span class="badge badge-success">Đã duyệt</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Chưa duyệt</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-3">
                                <div class="row">
                                    <div class="col-md-5">
                                        <?php if (!empty($room['images'])): ?>
                                            <img src="../../<?php echo $room['images']; ?>" class="img-fluid rounded" alt="<?php echo $room['title']; ?>" style="height: 120px; width: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 120px">
                                                <i class="fas fa-image fa-3x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="room-info">
                                            <p class="mb-1">
                                                <span class="badge badge-primary badge-pill">
                                                    <i class="fas fa-money-bill-wave"></i>
                                                    <?php echo number_format($room['price']); ?> VNĐ
                                                </span>
                                            </p>
                                            <p class="mb-1 small">
                                                <i class="fas fa-th-large text-muted mr-1"></i>
                                                <?php echo $room['category_name'] ?? 'Chưa phân loại'; ?>
                                            </p>
                                            <p class="mb-1 small">
                                                <i class="fas fa-map-marker-alt text-muted mr-1"></i>
                                                <?php echo $room['district_name'] ?? 'Chưa xác định'; ?>
                                            </p>
                                            <p class="mb-1 small">
                                                <i class="far fa-calendar-alt text-muted mr-1"></i>
                                                <?php echo date('d/m/Y', strtotime($room['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent d-flex justify-content-between">
                                <div class="btn-group">
                                    <a href="edit_room.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-outline-primary" data-toggle="tooltip" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="../../Home/room_detail.php?id=<?php echo $room['id']; ?>" target="_blank" class="btn btn-sm btn-outline-info" data-toggle="tooltip" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-warning" type="button" data-toggle="modal" data-target="#transferModal"
                                        data-roomid="<?php echo $room['id']; ?>"
                                        data-roomtitle="<?php echo $room['title']; ?>" data-toggle="tooltip" title="Chuyển quyền sở hữu">
                                        <i class="fas fa-exchange-alt"></i>
                                    </button>
                                </div>
                                <a class="btn btn-sm btn-outline-danger" href="?user_id=<?php echo $user_id; ?>&delete=<?php echo $room['id']; ?>"
                                    onclick="return confirm('Bạn có chắc muốn xóa phòng trọ này?')" data-toggle="tooltip" title="Xóa phòng trọ">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i> Người dùng này chưa có phòng trọ nào.
            </div>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- Modal chuyển quyền sở hữu -->
<div class="modal fade" id="transferModal" tabindex="-1" role="dialog" aria-labelledby="transferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow">
            <form method="POST" action="">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="transferModalLabel">
                        <i class="fas fa-exchange-alt mr-2"></i>Chuyển quyền sở hữu phòng trọ
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="room_id" id="room_id">
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle mr-2"></i>
                        Bạn đang chuyển quyền sở hữu phòng trọ: <strong id="room_title"></strong>
                    </div>

                    <div class="form-group">
                        <label for="new_owner_id">
                            <i class="fas fa-user-plus mr-1"></i> Chọn người dùng mới:
                        </label>
                        <select class="form-control custom-select" id="new_owner_id" name="new_owner_id" required>
                            <option value="">-- Chọn người dùng --</option>
                            <?php while ($other_user = mysqli_fetch_assoc($users_result)): ?>
                                <option value="<?php echo $other_user['id']; ?>">
                                    <?php echo $other_user['name']; ?> (<?php echo $other_user['username']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Đóng
                    </button>
                    <button type="submit" name="transfer_ownership" class="btn btn-primary">
                        <i class="fas fa-check mr-1"></i> Xác nhận chuyển quyền
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Script để truyền dữ liệu vào modal và khởi tạo tooltip
    $(document).ready(function() {
        // Khởi tạo tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Modal transfer data
        $('#transferModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var roomId = button.data('roomid');
            var roomTitle = button.data('roomtitle');

            var modal = $(this);
            modal.find('#room_id').val(roomId);
            modal.find('#room_title').text(roomTitle);
        });

        // Animation for room cards
        $('.room-card').hover(
            function() {
                $(this).addClass('shadow');
                $(this).css('transform', 'translateY(-5px)');
                $(this).css('transition', 'all 0.3s ease');
            },
            function() {
                $(this).removeClass('shadow');
                $(this).css('transform', 'translateY(0)');
                $(this).css('transition', 'all 0.3s ease');
            }
        );
    });
</script>

<?php include_once '../../Components/admin_footer.php'; ?>