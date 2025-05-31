<?php
session_start();
require_once '../../config/db.php';

// Kiểm tra đăng nhập với quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header('Location: ../../auth/login.php?message=Bạn cần đăng nhập với tài khoản admin');
    exit();
}

// Xử lý thêm danh mục mới
if (isset($_POST['add_category'])) {
    $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);

    if (!empty($category_name)) {
        $query = "INSERT INTO categories (name) VALUES ('$category_name')";
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Thêm danh mục thành công!";
            header('Location: manage_categories.php');
            exit();
        } else {
            $error = "Lỗi: " . mysqli_error($conn);
        }
    } else {
        $error = "Tên danh mục không được để trống!";
    }
}

// Xử lý xóa danh mục
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $search = isset($_GET['search']) ? urlencode($_GET['search']) : '';

    // Kiểm tra xem danh mục có đang được sử dụng không
    $check_query = "SELECT COUNT(*) as count FROM motel WHERE category_id = '$id'";
    $check_result = mysqli_query($conn, $check_query);
    $check_data = mysqli_fetch_assoc($check_result);

    if ($check_data['count'] > 0) {
        $_SESSION['error'] = "Không thể xóa danh mục này vì đang có phòng trọ sử dụng!";
    } else {
        $delete_query = "DELETE FROM categories WHERE id = '$id'";
        if (mysqli_query($conn, $delete_query)) {
            $_SESSION['success'] = "Xóa danh mục thành công!";
        } else {
            $_SESSION['error'] = "Lỗi khi xóa danh mục: " . mysqli_error($conn);
        }
    }

    // Tạo URL redirect với các tham số
    $redirect_url = "manage_categories.php?page=$page";
    if (!empty($search)) {
        $redirect_url .= "&search=$search";
    }

    header("Location: $redirect_url");
    exit();
}

// Xử lý cập nhật danh mục
if (isset($_POST['update_category'])) {
    $id = mysqli_real_escape_string($conn, $_POST['category_id']);
    $name = mysqli_real_escape_string($conn, $_POST['category_name']);
    $current_page = isset($_POST['current_page']) ? intval($_POST['current_page']) : 1;
    $search = isset($_POST['search']) ? $_POST['search'] : '';

    if (!empty($name)) {
        $query = "UPDATE categories SET name = '$name' WHERE id = '$id'";
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Cập nhật danh mục thành công!";
        } else {
            $_SESSION['error'] = "Lỗi khi cập nhật danh mục: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "Tên danh mục không được để trống!";
    }

    // Tạo URL redirect với các tham số
    $redirect_url = "manage_categories.php?page=$current_page";
    if (!empty($search)) {
        $redirect_url .= "&search=" . urlencode($search);
    }

    header("Location: $redirect_url");
    exit();
}

// Thiết lập phân trang
$records_per_page = 10; // Số bản ghi trên mỗi trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $records_per_page;

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search_esc = mysqli_real_escape_string($conn, $search);
    $search_condition = "WHERE c.name LIKE '%$search_esc%'";
}

// Đếm tổng số bản ghi để tính số trang
$count_query = "SELECT COUNT(*) as total FROM categories c $search_condition";
$count_result = mysqli_query($conn, $count_query);

if (!$count_result) {
    $error_message = "Lỗi truy vấn: " . mysqli_error($conn);
    $total_records = 0;
    $total_pages = 1;
} else {
    $total_records = mysqli_fetch_assoc($count_result)['total'];
    $total_pages = ceil($total_records / $records_per_page);
}

// Đảm bảo page không vượt quá tổng số trang
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $records_per_page;
}

// Lấy danh sách danh mục với phân trang
$query = "SELECT c.*, COUNT(m.id) as room_count 
          FROM categories c 
          LEFT JOIN motel m ON c.id = m.category_id AND m.isExist = 1
          $search_condition
          GROUP BY c.id
          ORDER BY c.name
          LIMIT $offset, $records_per_page";
$result = mysqli_query($conn, $query);

if (!$result) {
    $error_message = "Lỗi truy vấn: " . mysqli_error($conn);
}

// Lấy tất cả dữ liệu (không phân trang) để hiển thị thống kê
$stats_query = "SELECT c.*, COUNT(m.id) as room_count 
               FROM categories c 
               LEFT JOIN motel m ON c.id = m.category_id AND m.isExist = 1
               GROUP BY c.id";
$stats_result = mysqli_query($conn, $stats_query);
$total_categories = mysqli_num_rows($stats_result);

// Lấy tổng số phòng đã phân loại
$total_categorized_rooms_query = "SELECT COUNT(*) as count FROM motel WHERE category_id IS NOT NULL AND category_id > 0 AND isExist = 1";
$total_categorized_rooms_result = mysqli_query($conn, $total_categorized_rooms_query);
$total_categorized_rooms = mysqli_fetch_assoc($total_categorized_rooms_result)['count'];

// Lấy tổng số phòng chưa phân loại
$total_uncategorized_rooms_query = "SELECT COUNT(*) as count FROM motel WHERE (category_id IS NULL OR category_id = 0) AND isExist = 1";
$total_uncategorized_rooms_result = mysqli_query($conn, $total_uncategorized_rooms_query);
$total_uncategorized_rooms = mysqli_fetch_assoc($total_uncategorized_rooms_result)['count'];

// Đếm số danh mục chưa có phòng nào
$empty_categories_query = "SELECT COUNT(*) as count FROM categories c 
                         LEFT JOIN motel m ON c.id = m.category_id AND m.isExist = 1
                         WHERE m.id IS NULL";
$empty_categories_result = mysqli_query($conn, $empty_categories_query);
$empty_categories = mysqli_fetch_assoc($empty_categories_result)['count'];

// Cập nhật số phòng "chưa phân loại" để bao gồm cả số danh mục trống
$total_uncategorized = $total_uncategorized_rooms + $empty_categories;

// Lấy danh mục phổ biến nhất
$most_popular_query = "SELECT c.name, COUNT(m.id) as room_count 
                      FROM categories c 
                      INNER JOIN motel m ON c.id = m.category_id AND m.isExist = 1
                      GROUP BY c.id 
                      ORDER BY room_count DESC 
                      LIMIT 1";
$most_popular_result = mysqli_query($conn, $most_popular_query);
$most_popular = mysqli_fetch_assoc($most_popular_result);

$page_title = "Quản lý danh mục";
include_once '../../components/admin_header.php';
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center mt-2">
        <h2><i class="fas fa-list mr-2"></i> Quản lý danh mục</h2>
        <button type="button" class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addCategoryModal">
            <i class="fas fa-plus-circle mr-2"></i>Thêm danh mục mới
        </button>
    </div>
</div>

<!-- Form tìm kiếm -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="GET" action="" class="row align-items-center">
            <div class="col-md-6 col-lg-8">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-primary"></i></span>
                    </div>
                    <input type="text" class="form-control" name="search" placeholder="Tìm kiếm danh mục..." value="<?php echo htmlspecialchars($search); ?>">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                        <?php if (!empty($search)): ?>
                            <a href="manage_categories.php" class="btn btn-outline-secondary">Hủy tìm kiếm</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($search)): ?>
                    <div class="mt-2 text-muted small">
                        <i class="fas fa-info-circle"></i> Đang tìm kiếm: <strong><?php echo htmlspecialchars($search); ?></strong>
                        (<?php echo $total_records; ?> kết quả)
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6 col-lg-4 mt-3 mt-md-0 text-right">
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addCategoryModal">
                    <i class="fas fa-plus-circle mr-1"></i> Thêm danh mục
                </button>
                <a href="/admin/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Quay lại
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?php echo $error_message; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<?php if (!empty($search)): ?>
    <div class="alert alert-info">
        <i class="fas fa-search mr-2"></i>
        Kết quả tìm kiếm cho: <strong><?php echo htmlspecialchars($search); ?></strong>
        <span class="badge badge-pill badge-primary ml-2"><?php echo $total_records; ?> kết quả</span>
        <a href="manage_categories.php" class="btn btn-sm btn-outline-secondary ml-2">
            <i class="fas fa-times mr-1"></i> Xóa bộ lọc
        </a>
    </div>
<?php endif; ?>

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

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?php echo $error; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <!-- Tổng số danh mục -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-primary h-100">
            <div class="card-body">
                <div class="card-title">Tổng danh mục</div>
                <div class="card-value"><?php echo $total_categories; ?></div>
                <i class="fas fa-list fa-2x card-icon"></i>
            </div>
        </div>
    </div>

    <!-- Phòng đã phân loại -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-success h-100">
            <div class="card-body">
                <div class="card-title">Đã phân loại</div>
                <div class="card-value"><?php echo $total_categorized_rooms; ?></div>
                <i class="fas fa-check-square fa-2x card-icon"></i>
            </div>
        </div>
    </div>

    <!-- Phòng chưa phân loại -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-warning h-100">
            <div class="card-body">
                <div class="card-title">Chưa phân loại</div>
                <div class="card-value"><?php echo $total_uncategorized; ?></div>
                <i class="fas fa-question-circle fa-2x card-icon"></i>
            </div>
        </div>
    </div>

    <!-- Danh mục phổ biến nhất -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-info h-100">
            <div class="card-body">
                <div class="card-title">Phổ biến nhất</div>
                <div class="card-value">
                    <?php if ($most_popular): ?>
                        <?php echo $most_popular['name']; ?>
                        <div class="small text-white mt-1"><?php echo $most_popular['room_count']; ?> phòng</div>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
                <i class="fas fa-crown fa-2x card-icon"></i>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="m-0 font-weight-bold"><i class="fas fa-folder mr-2"></i>Danh sách danh mục</h5>
        <div class="d-flex align-items-center">
            <span class="badge badge-light badge-pill mr-2">
                <?php echo $total_records; ?> danh mục
            </span>
            <span class="badge badge-light badge-pill">
                Trang <?php echo $page; ?>/<?php echo max(1, $total_pages); ?>
            </span>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="bg-light">
                    <tr>
                        <th width="5%" class="text-center">ID</th>
                        <th>Tên danh mục</th>
                        <th width="15%" class="text-center">Số phòng trọ</th>
                        <th width="15%" class="text-center">Phần trăm</th>
                        <th width="20%" class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php
                        // Lấy tổng số phòng trọ
                        $total_rooms_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM motel WHERE isExist = 1");
                        $total_rooms = mysqli_fetch_assoc($total_rooms_query)['total'];

                        while ($category = mysqli_fetch_assoc($result)):
                            // Tính phần trăm
                            $percent = ($total_rooms > 0) ? round(($category['room_count'] / $total_rooms) * 100) : 0;

                            // Xác định màu của progress bar dựa trên số lượng phòng
                            if ($category['room_count'] == 0) {
                                $progress_color = 'bg-secondary';
                            } elseif ($percent < 10) {
                                $progress_color = 'bg-info';
                            } elseif ($percent < 25) {
                                $progress_color = 'bg-primary';
                            } elseif ($percent < 50) {
                                $progress_color = 'bg-warning';
                            } else {
                                $progress_color = 'bg-success';
                            }
                        ?>
                            <tr>
                                <td class="text-center"><?php echo $category['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-folder text-primary mr-2"></i>
                                        <span class="font-weight-bold"><?php echo $category['name']; ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-pill badge-primary"><?php echo $category['room_count']; ?></span>
                                </td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar <?php echo $progress_color; ?>" role="progressbar" style="width: <?php echo $percent; ?>%;"
                                            aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                            <?php echo $percent; ?>%
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        data-toggle="modal"
                                        data-target="#editCategoryModal"
                                        data-id="<?php echo $category['id']; ?>"
                                        data-name="<?php echo $category['name']; ?>"
                                        data-roomcount="<?php echo $category['room_count']; ?>">
                                        <i class="fas fa-edit"></i> Sửa
                                    </button>

                                    <?php if ($category['room_count'] == 0): ?>
                                        <a href="?delete=<?php echo $category['id']; ?>&page=<?php echo $page; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Bạn có chắc muốn xóa danh mục này?')">
                                            <i class="fas fa-trash"></i> Xóa
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary" disabled
                                            data-toggle="tooltip"
                                            title="Không thể xóa danh mục đang được sử dụng bởi <?php echo $category['room_count']; ?> phòng trọ">
                                            <i class="fas fa-trash"></i> Xóa
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Chưa có danh mục nào được tạo</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="mt-4">
                <nav aria-label="Phân trang">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Trang đầu">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Trang trước">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-angle-double-left"></i></span>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-angle-left"></i></span>
                            </li>
                        <?php endif; ?>

                        <?php
                        // Hiển thị tối đa 5 trang
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $start_page + 4);

                        if ($end_page - $start_page < 4) {
                            $start_page = max(1, $end_page - 4);
                        }

                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Trang sau">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Trang cuối">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-angle-right"></i></span>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-angle-double-right"></i></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <div class="text-center text-muted small">
                    Hiển thị <?php echo min($records_per_page, mysqli_num_rows($result)); ?> trên tổng số <?php echo $total_records; ?> danh mục
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-4 text-center">
    <button type="button" class="btn btn-primary mr-2" data-toggle="modal" data-target="#addCategoryModal">
        <i class="fas fa-plus-circle mr-1"></i> Thêm danh mục mới
    </button>
    <a href="/admin/index.php" class="btn btn-info">
        <i class="fas fa-tachometer-alt mr-1"></i> Quay lại bảng điều khiển
    </a>
</div>

<!-- Modal thêm danh mục -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow">
            <form method="POST" action="">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addCategoryModalLabel">
                        <i class="fas fa-plus-circle mr-2"></i>Thêm danh mục mới
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="category_name">
                            <i class="fas fa-tag mr-1"></i> Tên danh mục
                        </label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required
                            placeholder="Nhập tên danh mục..." autofocus>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle mr-1"></i> Đặt tên rõ ràng để dễ tìm kiếm và phân loại phòng trọ.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Đóng
                    </button>
                    <button type="submit" name="add_category" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Thêm danh mục
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal sửa danh mục -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow">
            <form method="POST" action="">
                <input type="hidden" name="current_page" value="<?php echo $page; ?>">
                <?php if (!empty($search)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <?php endif; ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editCategoryModalLabel">
                        <i class="fas fa-edit mr-2"></i>Sửa danh mục
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="category_id" id="edit_category_id">

                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle mr-2"></i>
                        Bạn đang sửa danh mục có <strong id="edit_category_room_count">0</strong> phòng trọ.
                    </div>

                    <div class="form-group">
                        <label for="edit_category_name">
                            <i class="fas fa-tag mr-1"></i> Tên danh mục
                        </label>
                        <input type="text" class="form-control" id="edit_category_name" name="category_name" required
                            placeholder="Nhập tên danh mục mới...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Đóng
                    </button>
                    <button type="submit" name="update_category" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Script để truyền dữ liệu vào modal sửa
    $(document).ready(function() {
        $('#editCategoryModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var name = button.data('name');
            var roomCount = button.data('roomcount');

            var modal = $(this);
            modal.find('#edit_category_id').val(id);
            modal.find('#edit_category_name').val(name);
            modal.find('#edit_category_room_count').text(roomCount);
        });

        // Kích hoạt tooltips
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>

<?php include_once '../../components/admin_footer.php'; ?>