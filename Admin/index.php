<?php
session_start();
require_once '../config/db.php';

// Kiểm tra đăng nhập với quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header('Location: ../auth/login.php?message=Bạn cần đăng nhập với tài khoản admin');
    exit();
}

// Lấy tổng số phòng trọ
$query = "SELECT COUNT(*) as total FROM motel";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$total_rooms = $row['total'];

// Lấy số phòng chưa duyệt
$query = "SELECT COUNT(*) as pending FROM motel WHERE approve = 0";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$pending_rooms = $row['pending'];

// Lấy tổng số người dùng
$query = "SELECT COUNT(*) as total_users FROM users";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$total_users = $row['total_users'];

// Lấy số lượng phòng theo danh mục
$categories_query = "SELECT c.name, COUNT(m.id) as count 
                    FROM categories c 
                    LEFT JOIN motel m ON c.id = m.category_id 
                    GROUP BY c.id 
                    ORDER BY count DESC 
                    LIMIT 5";
$categories_result = mysqli_query($conn, $categories_query);

// Lấy phòng mới nhất
$recent_rooms_query = "SELECT m.*, c.name as category_name, d.name as district_name, u.name as user_name 
                      FROM motel m 
                      LEFT JOIN categories c ON m.category_id = c.id 
                      LEFT JOIN districts d ON m.district_id = d.id 
                      LEFT JOIN users u ON m.user_id = u.id 
                      ORDER BY m.created_at DESC 
                      LIMIT 5";
$recent_rooms_result = mysqli_query($conn, $recent_rooms_query);

$page_title = "Bảng điều khiển admin";
include_once '../Components/admin_header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Bảng điều khiển</h1>
</div>

<div class="row">
    <!-- Tổng số phòng trọ -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-primary">
            <div class="card-body">
                <div class="card-title">Tổng số phòng trọ</div>
                <div class="card-value"><?php echo $total_rooms; ?></div>
                <i class="fas fa-building fa-2x card-icon"></i>
            </div>
            <div class="card-footer bg-transparent border-0 p-3">
                <a href="rooms/manage_rooms.php" class="text-primary small">Xem chi tiết <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
        </div>
    </div> <!-- Phòng chờ duyệt -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-warning">
            <div class="card-body">
                <div class="card-title">Phòng chờ duyệt</div>
                <div class="card-value"><?php echo $pending_rooms; ?></div>
                <i class="fas fa-clipboard-check fa-2x card-icon"></i>
            </div>
            <div class="card-footer bg-transparent border-0 p-3">
                <a href="rooms/pending_rooms.php" class="text-warning small">Xem chi tiết <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
        </div>
    </div>

    <!-- Phòng đã hủy -->
    <?php
    // Lấy số phòng đã hủy
    $query = "SELECT COUNT(*) as cancelled FROM motel WHERE approve = 2";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $cancelled_rooms = $row['cancelled'];
    ?>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-danger">
            <div class="card-body">
                <div class="card-title">Phòng đã hủy</div>
                <div class="card-value"><?php echo $cancelled_rooms; ?></div>
                <i class="fas fa-ban fa-2x card-icon"></i>
            </div>
            <div class="card-footer bg-transparent border-0 p-3">
                <a href="rooms/manage_rooms.php?status=2" class="text-danger small">Xem chi tiết <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
        </div>
    </div>

    <!-- Tổng số người dùng -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-success">
            <div class="card-body">
                <div class="card-title">Tổng người dùng</div>
                <div class="card-value"><?php echo $total_users; ?></div>
                <i class="fas fa-users fa-2x card-icon"></i>
            </div>
            <div class="card-footer bg-transparent border-0 p-3">
                <a href="users/manage_users.php" class="text-success small">Xem chi tiết <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
        </div>
    </div>

    <!-- Tỷ lệ duyệt -->
    <div class="col-xl-3 col-md-6 mb-4">
        <?php
        $approve_rate = ($total_rooms > 0) ? round(($total_rooms - $pending_rooms) / $total_rooms * 100) : 0;
        ?>
        <div class="card stat-card stat-card-info">
            <div class="card-body">
                <div class="card-title">Tỷ lệ đã duyệt</div>
                <div class="card-value"><?php echo $approve_rate; ?>%</div>
                <i class="fas fa-chart-pie fa-2x card-icon"></i>
            </div>
            <div class="card-footer bg-transparent border-0 p-3">
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo $approve_rate; ?>%"
                        aria-valuenow="<?php echo $approve_rate; ?>" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Phòng trọ theo danh mục -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Phòng trọ theo danh mục</h6>
            </div>
            <div class="card-body">
                <?php while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                    <?php
                    $percent = ($total_rooms > 0) ? round($cat['count'] / $total_rooms * 100) : 0;
                    ?>
                    <h4 class="small font-weight-bold">
                        <?php echo $cat['name']; ?>
                        <span class="float-right"><?php echo $cat['count']; ?> phòng (<?php echo $percent; ?>%)</span>
                    </h4>
                    <div class="progress mb-4">
                        <div class="progress-bar" role="progressbar" style="width: <?php echo $percent; ?>%"
                            aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                <?php endwhile; ?>

                <div class="mt-3 text-center">
                    <a href="categories/manage_categories.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-list mr-1"></i> Quản lý danh mục
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Các phòng mới nhất -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Phòng trọ mới nhất</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Tiêu đề</th>
                                <th>Giá</th>
                                <th>Trạng thái</th>
                                <th>Ngày đăng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($room = mysqli_fetch_assoc($recent_rooms_result)): ?>
                                <tr>
                                    <td>
                                        <a href="/admin/rooms/edit_room.php?id=<?php echo $room['id']; ?>">
                                            <?php echo mb_strimwidth($room['title'], 0, 30, "..."); ?>
                                        </a>
                                    </td>
                                    <td><?php echo number_format($room['price']); ?></td>
                                    <td>
                                        <?php if ($room['approve'] == 1): ?>
                                            <span class="badge badge-success">Đã duyệt</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Chờ duyệt</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($room['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 text-center">
                    <a href="admin/rooms/manage_rooms.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-building mr-1"></i> Xem tất cả phòng
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Các liên kết nhanh -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Truy cập nhanh</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3 col-sm-6 mb-4">
                        <a href="admin/rooms/add_room.php" class="btn btn-primary btn-block">
                            <i class="fas fa-plus-circle fa-2x mb-2"></i><br>
                            Thêm phòng mới
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <a href="/admin/rooms/pending_rooms.php" class="btn btn-warning btn-block">
                            <i class="fas fa-clipboard-check fa-2x mb-2"></i><br>
                            Duyệt phòng trọ
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <a href="/admin/users/manage_users.php" class="btn btn-success btn-block">
                            <i class="fas fa-users fa-2x mb-2"></i><br>
                            Quản lý người dùng
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <a href="/" target="_blank" class="btn btn-info btn-block">
                            <i class="fas fa-external-link-alt fa-2x mb-2"></i><br>
                            Xem trang chủ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../Components/admin_footer.php'; ?>