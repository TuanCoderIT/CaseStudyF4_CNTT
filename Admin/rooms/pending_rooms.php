<?php
session_start();
require_once '../../config/db.php';

// Kiểm tra đăng nhập với quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header('Location: ../../Auth/login.php?message=Bạn cần đăng nhập với tài khoản admin');
    exit();
}

// Xử lý duyệt phòng trọ
if (isset($_GET['approve']) && !empty($_GET['approve'])) {
    $id = mysqli_real_escape_string($conn, $_GET['approve']);
    mysqli_query($conn, "UPDATE motel SET approve = 1 WHERE id = '$id'");

    $_SESSION['success'] = "Đã duyệt phòng trọ thành công!";
    header('Location: pending_rooms.php');
    exit();
}

// Xử lý từ chối/xóa phòng trọ
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);

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
include_once '../../Components/admin_header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">Duyệt phòng trọ</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

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
                                        <img src="../<?php echo $room['images']; ?>" class="img-fluid img-thumbnail" alt="<?php echo $room['title']; ?>">
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
                                <a href="?delete=<?php echo $room['id']; ?>" class="btn btn-danger" onclick="return confirm('Bạn có chắc muốn từ chối và xóa phòng trọ này?')">
                                    <i class="fas fa-times"></i> Từ chối
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

<?php include_once '../../Components/admin_footer.php'; ?>