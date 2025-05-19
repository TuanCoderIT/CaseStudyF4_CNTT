<?php
session_start();
require_once '../../config/db.php';

// Kiểm tra đăng nhập với quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header('Location: ../../Auth/login.php?message=Bạn cần đăng nhập với tài khoản admin');
    exit();
}

// Xử lý xóa người dùng
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);

    // Không cho phép xóa chính mình
    if ($id == $_SESSION['user_id']) {
        $_SESSION['error'] = "Bạn không thể xóa tài khoản của chính mình!";
    } else {
        // Kiểm tra xem người dùng có phòng trọ không
        $check_query = "SELECT COUNT(*) as count FROM motel WHERE user_id = '$id'";
        $check_result = mysqli_query($conn, $check_query);
        $check_data = mysqli_fetch_assoc($check_result);

        if ($check_data['count'] > 0) {
            // Nếu có phòng trọ, hỏi người dùng muốn xóa hết hay chuyển quyền sở hữu
            $_SESSION['error'] = "Người dùng này có " . $check_data['count'] . " phòng trọ. Vui lòng chuyển phòng trọ sang người dùng khác hoặc xóa phòng trọ trước khi xóa người dùng.";
        } else {
            // Lấy thông tin avatar để xóa file (nếu có)
            $get_avatar = mysqli_query($conn, "SELECT avatar FROM users WHERE id = '$id'");
            $avatar_data = mysqli_fetch_assoc($get_avatar);

            if (!empty($avatar_data['avatar']) && $avatar_data['avatar'] != 'default-avatar.jpg') {
                $avatar_path = '../' . $avatar_data['avatar'];
                if (file_exists($avatar_path)) {
                    unlink($avatar_path);
                }
            }

            // Xóa người dùng
            mysqli_query($conn, "DELETE FROM users WHERE id = '$id'");
            $_SESSION['success'] = "Đã xóa người dùng thành công!";
        }
    }

    header('Location: manage_users.php');
    exit();
}

// Xử lý thay đổi vai trò
if (isset($_GET['change_role']) && !empty($_GET['change_role']) && isset($_GET['role'])) {
    $user_id = mysqli_real_escape_string($conn, $_GET['change_role']);
    $new_role = (int)$_GET['role'];

    // Không cho phép thay đổi vai trò của chính mình
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['error'] = "Bạn không thể thay đổi vai trò của chính mình!";
    } else {
        $query = "UPDATE users SET role = '$new_role' WHERE id = '$user_id'";

        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Đã thay đổi vai trò người dùng thành công!";
        } else {
            $_SESSION['error'] = "Lỗi khi thay đổi vai trò: " . mysqli_error($conn);
        }
    }

    header('Location: manage_users.php');
    exit();
}

// Phân trang
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Xây dựng truy vấn với bộ lọc
$where_clauses = [];
$params = [];

// Lọc theo vai trò
if (isset($_GET['role']) && !empty($_GET['role'])) {
    $role = mysqli_real_escape_string($conn, $_GET['role']);
    $where_clauses[] = "u.role = '$role'";
    $params[] = "role=$role";
}

// Lọc theo từ khóa tìm kiếm
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where_clauses[] = "(u.name LIKE '%$search%' OR u.email LIKE '%$search%' OR u.username LIKE '%$search%' OR u.phone LIKE '%$search%')";
    $params[] = "search=" . urlencode($_GET['search']);
}

// Lọc theo có phòng hay không
if (isset($_GET['has_rooms']) && !empty($_GET['has_rooms'])) {
    if ($_GET['has_rooms'] == 'yes') {
        $where_clauses[] = "COUNT(m.id) > 0";
        $params[] = "has_rooms=yes";
    } else if ($_GET['has_rooms'] == 'no') {
        $where_clauses[] = "COUNT(m.id) = 0";
        $params[] = "has_rooms=no";
    }
}

// Tạo mệnh đề WHERE
$where_clause = '';
if (!empty($where_clauses)) {
    $where_clause = "HAVING " . implode(" AND ", $where_clauses);
}

// Tạo chuỗi tham số cho phân trang
$pagination_params = !empty($params) ? '&' . implode('&', $params) : '';

// Truy vấn danh sách người dùng với phân trang và bộ lọc
$query = "SELECT u.*, COUNT(m.id) as room_count 
          FROM users u 
          LEFT JOIN motel m ON u.id = m.user_id 
          GROUP BY u.id
          $where_clause
          ORDER BY u.id
          LIMIT $start, $limit";
$result = mysqli_query($conn, $query);

// Đếm tổng số người dùng để tính số trang (sử dụng các bộ lọc tương tự)
$count_query = "SELECT COUNT(*) as count FROM (
                SELECT u.id, COUNT(m.id) as room_count 
                FROM users u 
                LEFT JOIN motel m ON u.id = m.user_id 
                GROUP BY u.id
                $where_clause
                ) as filtered_users";
$count_result = mysqli_query($conn, $count_query);
$count_data = mysqli_fetch_assoc($count_result);
$total_pages = ceil($count_data['count'] / $limit);

$page_title = "Quản lý người dùng";
include_once '../../Components/admin_header.php';
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2><i class="fas fa-users mr-2"></i> Quản lý người dùng</h2>
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

<?php
// Lấy tổng số người dùng
$total_users = $count_data['count'];

// Lấy tổng số admin
$admin_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 1");
$admin_data = mysqli_fetch_assoc($admin_query);
$total_admins = $admin_data['count'];

// Lấy tổng số người dùng thường
$normal_users = $total_users - $total_admins;

// Lấy người dùng có nhiều phòng nhất
$most_active_query = "SELECT u.name, COUNT(m.id) as room_count 
                     FROM users u 
                     INNER JOIN motel m ON u.id = m.user_id 
                     GROUP BY u.id 
                     ORDER BY room_count DESC 
                     LIMIT 1";
$most_active_result = mysqli_query($conn, $most_active_query);
$most_active = mysqli_fetch_assoc($most_active_result);
?>

<div class="row mb-4">
    <!-- Tổng số người dùng -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-primary h-100">
            <div class="card-body">
                <div class="card-title">Tổng người dùng</div>
                <div class="card-value"><?php echo $total_users; ?></div>
                <i class="fas fa-users fa-2x card-icon"></i>
            </div>
        </div>
    </div>

    <!-- Số Admin -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-danger h-100">
            <div class="card-body">
                <div class="card-title">Số Admin</div>
                <div class="card-value"><?php echo $total_admins; ?></div>
                <i class="fas fa-user-shield fa-2x card-icon"></i>
            </div>
        </div>
    </div>

    <!-- Người dùng thường -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-success h-100">
            <div class="card-body">
                <div class="card-title">Người dùng thường</div>
                <div class="card-value"><?php echo $normal_users; ?></div>
                <i class="fas fa-user fa-2x card-icon"></i>
            </div>
        </div>
    </div>

    <!-- Người dùng tích cực nhất -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-info h-100">
            <div class="card-body">
                <div class="card-title">Tích cực nhất</div>
                <div class="card-value">
                    <?php if ($most_active): ?>
                        <?php echo mb_strimwidth($most_active['name'], 0, 15, "..."); ?>
                        <div class="small text-white mt-1"><?php echo $most_active['room_count']; ?> phòng</div>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
                <i class="fas fa-award fa-2x card-icon"></i>
            </div>
        </div>
    </div>
</div>

<!-- Bộ lọc -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="m-0 font-weight-bold"><i class="fas fa-filter mr-2"></i>Tìm kiếm</h5>
        <button class="btn btn-outline-light btn-sm" type="button" data-toggle="collapse" data-target="#filterCollapse">
            <i class="fas fa-plus-minus"></i>
        </button>
    </div>
    <div class="collapse show" id="filterCollapse">
        <div class="card-body">
            <form method="get" class="row">
                <div class="col-md-3 mb-3">
                    <label for="role">Vai trò</label>
                    <select name="role" id="role" class="form-control custom-select">
                        <option value="">Tất cả vai trò</option>
                        <option value="1" <?php echo (isset($_GET['role']) && $_GET['role'] == '1') ? 'selected' : ''; ?>>Admin</option>
                        <option value="2" <?php echo (isset($_GET['role']) && $_GET['role'] == '2') ? 'selected' : ''; ?>>Người dùng thường</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="search">Tìm kiếm</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Tên, email, username..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label for="has_rooms">Có phòng trọ</label>
                    <select name="has_rooms" id="has_rooms" class="form-control custom-select">
                        <option value="">Tất cả</option>
                        <option value="yes" <?php echo (isset($_GET['has_rooms']) && $_GET['has_rooms'] == 'yes') ? 'selected' : ''; ?>>Có phòng trọ</option>
                        <option value="no" <?php echo (isset($_GET['has_rooms']) && $_GET['has_rooms'] == 'no') ? 'selected' : ''; ?>>Không có phòng trọ</option>
                    </select>
                </div>

                <div class="col-12 text-center">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search mr-1"></i> Tìm kiếm
                    </button>
                    <a href="manage_users.php" class="btn btn-secondary ml-2">
                        <i class="fas fa-sync-alt mr-1"></i> Đặt lại
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Danh sách người dùng -->
<div class="card shadow-sm">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="m-0 font-weight-bold"><i class="fas fa-user-friends mr-2"></i>Danh sách người dùng</h5>
        <span class="badge badge-light badge-pill">
            <?php echo $count_data['count']; ?> người dùng
        </span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="bg-light">
                    <tr>
                        <th width="50" class="text-center">ID</th>
                        <th width="70">Ảnh</th>
                        <th>Tên</th>
                        <th>Thông tin liên hệ</th>
                        <th width="100">Vai trò</th>
                        <th width="80" class="text-center">Phòng</th>
                        <th width="200" class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($user = mysqli_fetch_assoc($result)): ?>
                            <tr class="<?php echo ($user['id'] == $_SESSION['user_id']) ? 'bg-light font-weight-bold' : ''; ?>">
                                <td class="text-center"><?php echo $user['id']; ?></td>
                                <td>
                                    <?php if (!empty($user['avatar'])): ?>
                                        <img src="../<?php echo $user['avatar']; ?>" class="img-profile rounded-circle" width="50" height="50" style="object-fit: cover;">
                                    <?php else: ?>
                                        <img src="../images/default-avatar.jpg" class="img-profile rounded-circle" width="50" height="50" style="object-fit: cover;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="font-weight-bold"><?php echo $user['name']; ?></div>
                                    <div class="small text-muted"><?php echo $user['username']; ?></div>
                                </td>
                                <td>
                                    <div><i class="fas fa-envelope text-muted mr-1"></i> <?php echo $user['email']; ?></div>
                                    <div><i class="fas fa-phone text-muted mr-1"></i> <?php echo $user['phone'] ? $user['phone'] : 'N/A'; ?></div>
                                </td>
                                <td class="text-center">
                                    <?php if ($user['role'] == 1): ?>
                                        <span class="badge badge-pill badge-primary"><i class="fas fa-user-shield mr-1"></i> Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-pill badge-secondary"><i class="fas fa-user mr-1"></i> Người dùng</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($user['room_count'] > 0): ?>
                                        <span class="badge badge-pill badge-success"><?php echo $user['room_count']; ?> phòng</span>
                                    <?php else: ?>
                                        <span class="badge badge-pill badge-light">0 phòng</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <?php if ($user['room_count'] > 0): ?>
                                            <a href="user_rooms.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-info" data-toggle="tooltip" title="Xem phòng trọ">
                                                <i class="fas fa-home"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <!-- Nút thay đổi vai trò -->
                                            <?php if ($user['role'] == 1): ?>
                                                <a href="?change_role=<?php echo $user['id']; ?>&role=2" class="btn btn-sm btn-outline-warning"
                                                    onclick="return confirm('Bạn có chắc muốn hạ quyền người dùng này thành người dùng thường?')"
                                                    data-toggle="tooltip" title="Hạ quyền">
                                                    <i class="fas fa-level-down-alt"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="?change_role=<?php echo $user['id']; ?>&role=1" class="btn btn-sm btn-outline-success"
                                                    onclick="return confirm('Bạn có chắc muốn nâng quyền người dùng này thành admin?')"
                                                    data-toggle="tooltip" title="Nâng quyền">
                                                    <i class="fas fa-level-up-alt"></i>
                                                </a>
                                            <?php endif; ?>

                                            <!-- Nút xóa người dùng -->
                                            <?php if ($user['room_count'] == 0): ?>
                                                <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Bạn có chắc muốn xóa người dùng này?')"
                                                    data-toggle="tooltip" title="Xóa người dùng">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary" disabled
                                                    data-toggle="tooltip" title="Không thể xóa người dùng có phòng trọ">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge badge-info"><i class="fas fa-user-check"></i> Bạn đang đăng nhập</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Không có người dùng nào phù hợp với điều kiện tìm kiếm</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Phân trang -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-4">
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-circle justify-content-center">
                        <!-- Previous button -->
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $pagination_params; ?>" aria-label="Previous">
                                <span aria-hidden="true"><i class="fas fa-chevron-left"></i></span>
                            </a>
                        </li>

                        <?php
                        // Hiển thị phân trang thông minh
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        // Hiển thị trang đầu nếu cần
                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        // Hiển thị các trang ở giữa
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            echo '<li class="page-item ' . (($i == $page) ? 'active' : '') . '">';
                            echo '<a class="page-link" href="?page=' . $i . '">' . $i . '</a>';
                            echo '</li>';
                        }

                        // Hiển thị trang cuối nếu cần
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                        }
                        ?>

                        <!-- Next button -->
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                <span aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Action Buttons -->
<div class="mt-4 mb-4 text-center">
    <a href="index.php" class="btn btn-info">
        <i class="fas fa-tachometer-alt mr-1"></i> Quay lại bảng điều khiển
    </a>
    <a href="pending_rooms.php" class="btn btn-warning ml-2">
        <i class="fas fa-clipboard-check mr-1"></i> Duyệt phòng
        <?php
        $pending_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM motel WHERE approve = 0");
        $pending = mysqli_fetch_assoc($pending_count);
        if ($pending['count'] > 0):
        ?>
            <span class="badge badge-light ml-1"><?php echo $pending['count']; ?></span>
        <?php endif; ?>
    </a>
    <a href="manage_rooms.php" class="btn btn-primary ml-2">
        <i class="fas fa-building mr-1"></i> Quản lý phòng trọ
    </a>
</div>
</div>

<?php include_once '../../Components/admin_footer.php'; ?>