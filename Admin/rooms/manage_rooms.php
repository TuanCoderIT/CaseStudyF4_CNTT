<?php
session_start();
require_once '../../config/db.php';

// Kiểm tra đăng nhập với quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header('Location: ../../Auth/login.php?message=Bạn cần đăng nhập với tài khoản admin');
    exit();
}

// Xử lý xóa phòng trọ
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

    $_SESSION['success'] = "Đã xóa phòng trọ thành công!";
    header('Location: manage_rooms.php');
    exit();
}

// Xử lý duyệt phòng trọ
if (isset($_GET['approve']) && !empty($_GET['approve'])) {
    $id = mysqli_real_escape_string($conn, $_GET['approve']);
    mysqli_query($conn, "UPDATE motel SET approve = 1 WHERE id = '$id'");

    $_SESSION['success'] = "Đã duyệt phòng trọ thành công!";
    header('Location: manage_rooms.php');
    exit();
}

// Phân trang
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Xây dựng truy vấn với bộ lọc
$where_clauses = [];
$params = [];

// Lọc theo danh mục
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category_id = mysqli_real_escape_string($conn, $_GET['category']);
    $where_clauses[] = "m.category_id = '$category_id'";
    $params[] = "category=$category_id";
}

// Lọc theo khu vực
if (isset($_GET['district']) && !empty($_GET['district'])) {
    $district_id = mysqli_real_escape_string($conn, $_GET['district']);
    $where_clauses[] = "m.district_id = '$district_id'";
    $params[] = "district=$district_id";
}

// Lọc theo trạng thái
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    $where_clauses[] = "m.approve = '$status'";
    $params[] = "status=$status";
}

// Tìm kiếm theo từ khóa
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where_clauses[] = "(m.title LIKE '%$search%' OR m.address LIKE '%$search%')";
    $params[] = "search=$search";
}

// Tạo mệnh đề WHERE
$where_clause = '';
if (!empty($where_clauses)) {
    $where_clause = "WHERE " . implode(" AND ", $where_clauses);
}

// Tạo chuỗi tham số cho phân trang
$pagination_params = !empty($params) ? '&' . implode('&', $params) : '';

// Truy vấn danh sách phòng trọ với phân trang và bộ lọc
$query = "SELECT m.*, c.name as category_name, d.name as district_name, u.name as user_name 
          FROM motel m 
          LEFT JOIN categories c ON m.category_id = c.id 
          LEFT JOIN districts d ON m.district_id = d.id 
          LEFT JOIN users u ON m.user_id = u.id 
          $where_clause
          ORDER BY m.created_at DESC 
          LIMIT $start, $limit";

$result = mysqli_query($conn, $query);

// Đếm tổng số phòng trọ để tính số trang (áp dụng cùng bộ lọc)
$count_query = "SELECT COUNT(*) as count FROM motel m 
                LEFT JOIN categories c ON m.category_id = c.id 
                LEFT JOIN districts d ON m.district_id = d.id 
                LEFT JOIN users u ON m.user_id = u.id 
                $where_clause";
$count_result = mysqli_query($conn, $count_query);
$count_data = mysqli_fetch_assoc($count_result);
$total_pages = ceil($count_data['count'] / $limit);

$page_title = "Quản lý phòng trọ";
include_once ('../../Components/admin_header.php');
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2><i class="fas fa-building mr-2"></i> Quản lý phòng trọ</h2>
        <a href="add_room.php" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus-circle mr-2"></i>Thêm phòng trọ mới
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

<!-- Dashboard Cards -->
<div class="row mb-4">
    <!-- Tổng số phòng -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-primary h-100">
            <div class="card-body">
                <div class="card-title">Tổng số phòng</div>
                <div class="card-value"><?php echo $count_data['count']; ?></div>
                <i class="fas fa-building fa-2x card-icon"></i>
            </div>
        </div>
    </div>

    <!-- Phòng đã duyệt -->
    <?php
    $approved_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM motel WHERE approve = 1");
    $approved_data = mysqli_fetch_assoc($approved_query);
    ?>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-success h-100">
            <div class="card-body">
                <div class="card-title">Phòng đã duyệt</div>
                <div class="card-value"><?php echo $approved_data['count']; ?></div>
                <i class="fas fa-check-circle fa-2x card-icon"></i>
            </div>
        </div>
    </div>

    <!-- Phòng chờ duyệt -->
    <?php
    $pending_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM motel WHERE approve = 0");
    $pending_data = mysqli_fetch_assoc($pending_query);
    ?>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-warning h-100">
            <div class="card-body">
                <div class="card-title">Chờ duyệt</div>
                <div class="card-value"><?php echo $pending_data['count']; ?></div>
                <i class="fas fa-clock fa-2x card-icon"></i>
            </div>
        </div>
    </div>

    <!-- Giá trung bình -->
    <?php
    $avg_query = mysqli_query($conn, "SELECT AVG(price) as avg_price FROM motel");
    $avg_data = mysqli_fetch_assoc($avg_query);
    ?>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-info h-100">
            <div class="card-body">
                <div class="card-title">Giá trung bình</div>
                <div class="card-value"><?php echo number_format($avg_data['avg_price']); ?></div>
                <i class="fas fa-money-bill-wave fa-2x card-icon"></i>
            </div>
        </div>
    </div>
</div>

<!-- Bộ lọc -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="m-0 font-weight-bold"><i class="fas fa-filter mr-2"></i>Bộ lọc</h5>
        <button class="btn btn-outline-light btn-sm" type="button" data-toggle="collapse" data-target="#filterCollapse">
            <i class="fas fa-plus-minus"></i>
        </button>
    </div>
    <div class="collapse show" id="filterCollapse">
        <div class="card-body">
            <form method="get" class="row">
                <div class="col-md-3 mb-3">
                    <label for="category">Danh mục</label>
                    <select name="category" id="category" class="form-control custom-select">
                        <option value="">Tất cả danh mục</option>
                        <?php
                        $categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");
                        while ($cat = mysqli_fetch_assoc($categories)) {
                            $selected = (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : '';
                            echo '<option value="' . $cat['id'] . '" ' . $selected . '>' . $cat['name'] . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label for="district">Khu vực</label>
                    <select name="district" id="district" class="form-control custom-select">
                        <option value="">Tất cả khu vực</option>
                        <?php
                        $districts = mysqli_query($conn, "SELECT * FROM districts ORDER BY name");
                        while ($dist = mysqli_fetch_assoc($districts)) {
                            $selected = (isset($_GET['district']) && $_GET['district'] == $dist['id']) ? 'selected' : '';
                            echo '<option value="' . $dist['id'] . '" ' . $selected . '>' . $dist['name'] . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label for="status">Trạng thái</label>
                    <select name="status" id="status" class="form-control custom-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="1" <?php echo (isset($_GET['status']) && $_GET['status'] == '1') ? 'selected' : ''; ?>>Đã duyệt</option>
                        <option value="0" <?php echo (isset($_GET['status']) && $_GET['status'] == '0') ? 'selected' : ''; ?>>Chưa duyệt</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label for="search">Tìm kiếm</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Tiêu đề, địa chỉ..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>

                <div class="col-12 text-center">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search mr-1"></i> Tìm kiếm
                    </button>
                    <a href="manage_rooms.php" class="btn btn-secondary ml-2">
                        <i class="fas fa-sync-alt mr-1"></i> Đặt lại
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Danh sách phòng -->
<div class="card shadow-sm">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="m-0 font-weight-bold"><i class="fas fa-list mr-2"></i>Danh sách phòng trọ</h5>
        <span class="badge badge-light badge-pill">
            <?php echo $count_data['count']; ?> phòng
        </span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 50px" class="text-center">ID</th>
                        <th>Tiêu đề</th>
                        <th>Giá (VNĐ)</th>
                        <th>Diện tích</th>
                        <th>Danh mục</th>
                        <th>Khu vực</th>
                        <th>Người đăng</th>
                        <th>Trạng thái</th>
                        <th>Ngày đăng</th>
                        <th style="width: 180px">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td class="text-center"><?php echo $row['id']; ?></td>
                            <td>
                                <a href="edit_room.php?id=<?php echo $row['id']; ?>" class="font-weight-bold text-truncate d-inline-block" style="max-width: 250px;">
                                    <?php echo $row['title']; ?>
                                </a>
                            </td>
                            <td class="text-right font-weight-bold text-primary">
                                <?php echo number_format($row['price']); ?>
                            </td>
                            <td class="text-center"><?php echo $row['area']; ?> m²</td>
                            <td>
                                <span class="badge badge-pill badge-light">
                                    <?php echo $row['category_name'] ?? 'Chưa phân loại'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-pill badge-light">
                                    <?php echo $row['district_name'] ?? 'Chưa xác định'; ?>
                                </span>
                            </td>
                            <td><?php echo $row['user_name']; ?></td>
                            <td class="text-center">
                                <?php if ($row['approve'] == 1): ?>
                                    <span class="badge badge-success">Đã duyệt</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Chưa duyệt</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="edit_room.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary" data-toggle="tooltip" title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <?php if ($row['approve'] == 0): ?>
                                        <a href="?approve=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Bạn có chắc muốn duyệt phòng trọ này?')" data-toggle="tooltip" title="Duyệt">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>

                                    <a href="../Home/room_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info" target="_blank" data-toggle="tooltip" title="Xem">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc muốn xóa phòng trọ này?')" data-toggle="tooltip" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                    <?php if (mysqli_num_rows($result) == 0): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Không tìm thấy phòng trọ nào</p>
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
                            echo '<li class="page-item"><a class="page-link" href="?page=1' . $pagination_params . '">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        // Hiển thị các trang ở giữa
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            echo '<li class="page-item ' . (($i == $page) ? 'active' : '') . '">';
                            echo '<a class="page-link" href="?page=' . $i . $pagination_params . '">' . $i . '</a>';
                            echo '</li>';
                        }

                        // Hiển thị trang cuối nếu cần
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . $pagination_params . '">' . $total_pages . '</a></li>';
                        }
                        ?>

                        <!-- Next button -->
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $pagination_params; ?>" aria-label="Next">
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
    <a href="add_room.php" class="btn btn-primary mr-2">
        <i class="fas fa-plus-circle mr-1"></i> Thêm phòng mới
    </a>
    <a href="pending_rooms.php" class="btn btn-warning mr-2">
        <i class="fas fa-clipboard-check mr-1"></i> Phòng chờ duyệt
        <?php if ($pending_data['count'] > 0): ?>
            <span class="badge badge-light ml-1"><?php echo $pending_data['count']; ?></span>
        <?php endif; ?>
    </a>
    <a href="index.php" class="btn btn-info">
        <i class="fas fa-tachometer-alt mr-1"></i> Quay lại bảng điều khiển
    </a>
</div>
</div>

<script>
    $(document).ready(function() {
        // Khởi tạo tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Highlight dòng khi hover
        $('tbody tr').hover(
            function() {
                $(this).addClass('bg-light');
            },
            function() {
                $(this).removeClass('bg-light');
            }
        );

        // Hiệu ứng đóng/mở cho card filter
        $('#filterCollapse').on('shown.bs.collapse', function() {
            $(this).parent().find('button i').removeClass('fa-plus').addClass('fa-minus');
        });

        $('#filterCollapse').on('hidden.bs.collapse', function() {
            $(this).parent().find('button i').removeClass('fa-minus').addClass('fa-plus');
        });
    });
</script>

<?php include_once '../../Components/admin_footer.php'; ?>