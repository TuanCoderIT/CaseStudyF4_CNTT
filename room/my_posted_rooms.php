<?php
// Khởi tạo phiên làm việc
session_start();

// Function to check if a room has been booked
function isRoomBooked($conn, $room_id)
{
    $query = "SELECT id FROM bookings WHERE motel_id = ? AND status in ('RELEASED', 'SUCCESS')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return ($result->num_rows > 0);
}

// Function to check if a room has been released (giải ngân)
function isRoomReleased($conn, $room_id)
{
    $query = "SELECT id FROM bookings WHERE motel_id = ? AND status = 'RELEASED' LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return ($result->num_rows > 0);
}

// Kiểm tra xem người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Kết nối đến CSDL
require_once('../config/db.php');

// Lấy thông tin người dùng hiện tại
$user_id = $_SESSION['user_id'];

// Xử lý xóa phòng (nếu được yêu cầu)
$delete_message = '';
$delete_status = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $room_id = $_GET['delete'];

    // Kiểm tra xem phòng có thuộc về người dùng hiện tại không
    $check_stmt = $conn->prepare("SELECT id FROM motel WHERE id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $room_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Xóa các hình ảnh phòng
        $img_stmt = $conn->prepare("SELECT image_path FROM motel_images WHERE motel_id = ?");
        $img_stmt->bind_param("i", $room_id);
        $img_stmt->execute();
        $img_result = $img_stmt->get_result();

        while ($img_row = $img_result->fetch_assoc()) {
            $image_path = "../" . $img_row['image_path'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        // Xóa bản ghi trong bảng motel_images
        $delete_img_stmt = $conn->prepare("DELETE FROM motel_images WHERE motel_id = ?");
        $delete_img_stmt->bind_param("i", $room_id);
        $delete_img_stmt->execute();

        // Xóa bản ghi trong bảng user_wishlist
        $delete_wishlist_stmt = $conn->prepare("DELETE FROM user_wishlist WHERE motel_id = ?");
        $delete_wishlist_stmt->bind_param("i", $room_id);
        $delete_wishlist_stmt->execute();

        // Xóa phòng từ bảng motel
        $delete_room_stmt = $conn->prepare("DELETE FROM motel WHERE id = ? AND user_id = ?");
        $delete_room_stmt->bind_param("ii", $room_id, $user_id);
        $delete_room_stmt->execute();

        if ($delete_room_stmt->affected_rows > 0) {
            $delete_status = 'success';
            $delete_message = 'Xóa phòng thành công!';
        } else {
            $delete_status = 'danger';
            $delete_message = 'Xóa phòng thất bại!';
        }
    } else {
        $delete_status = 'danger';
        $delete_message = 'Bạn không có quyền xóa phòng này!';
    }
}

// Xử lý yêu cầu chuyển đổi trạng thái thuê (isExist)
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id']) && is_numeric($_GET['id']) && isset($_GET['new_status']) && is_numeric($_GET['new_status'])) {
    $room_id_to_update = $_GET['id'];
    $new_status_value = $_GET['new_status']; // Should be 0 or 1

    // Kiểm tra xem phòng có thuộc về người dùng hiện tại không
    $check_stmt = $conn->prepare("SELECT id FROM motel WHERE id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $room_id_to_update, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Nếu muốn chuyển sang trạng thái Còn trống (isExist = 1), kiểm tra phòng đã từng được giải ngân chưa
        if ($new_status_value == 1) {
            $check_released_stmt = $conn->prepare("SELECT id FROM bookings WHERE motel_id = ? AND status = 'RELEASED' LIMIT 1");
            $check_released_stmt->bind_param("i", $room_id_to_update);
            $check_released_stmt->execute();
            $released_result = $check_released_stmt->get_result();
            if ($released_result->num_rows > 0) {
                $_SESSION['error_message'] = 'Phòng này đã được xác nhận cho thuê và giải ngân tiền cọc. Không thể chuyển lại trạng thái còn trống!';
                // Chuyển hướng về trang hiện tại (loại bỏ tham số action)
                $redirect_url = $_SERVER['PHP_SELF'] . '?' . http_build_query(array_diff_key($_GET, ['action' => '', 'id' => '', 'new_status' => '']));
                header('Location: ' . $redirect_url);
                exit();
            }
        }
        // Cập nhật trạng thái isExist
        $update_stmt = $conn->prepare("UPDATE motel SET isExist = ? WHERE id = ? AND user_id = ?");
        $update_stmt->bind_param("iii", $new_status_value, $room_id_to_update, $user_id);
        $update_stmt->execute();

        if ($update_stmt->affected_rows > 0) {
            $_SESSION['success_message'] = 'Đã cập nhật trạng thái phòng thành công!';
        } else {
            $_SESSION['error_message'] = 'Không có thay đổi trạng thái hoặc lỗi cập nhật.';
        }
    } else {
        $_SESSION['error_message'] = 'Bạn không có quyền cập nhật trạng thái phòng này!';
    }

    // Chuyển hướng về trang hiện tại (loại bỏ tham số action)
    $redirect_url = $_SERVER['PHP_SELF'] . '?' . http_build_query(array_diff_key($_GET, ['action' => '', 'id' => '', 'new_status' => '']));
    header('Location: ' . $redirect_url);
    exit();
}

// Lấy tổng số phòng đã đăng của người dùng
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM motel WHERE user_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_rooms = $count_row['total'];

// Thiết lập phân trang
$rooms_per_page = 6;
$total_pages = ceil($total_rooms / $rooms_per_page);
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, min($current_page, $total_pages)); // Đảm bảo trang hiện tại nằm trong phạm vi hợp lệ
$offset = ($current_page - 1) * $rooms_per_page;

// Lọc theo trạng thái nếu được yêu cầu
$status_filter = '';
$status_condition = '';
$filter_applied = false;

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'pending') {
        $status_condition = " AND approve = 0";
        $status_filter = 'pending';
        $filter_applied = true;
    } elseif ($_GET['status'] === 'approved') {
        $status_condition = " AND approve = 1";
        $status_filter = 'approved';
        $filter_applied = true;
    }
}

// Lọc theo trạng thái cho thuê
$rental_filter = '';
$rental_condition = '';

if (isset($_GET['rental_status'])) {
    if ($_GET['rental_status'] === 'available') {
        $rental_condition = " AND isExist = 1";
        $rental_filter = 'available';
        $filter_applied = true;
    } elseif ($_GET['rental_status'] === 'rented') {
        $rental_condition = " AND isExist = 0";
        $rental_filter = 'rented';
        $filter_applied = true;
    }
}

// Lọc theo trạng thái đặt cọc
$deposit_filter = '';
$deposit_condition = '';
if (isset($_GET['deposit_status'])) {
    if ($_GET['deposit_status'] === 'required') {
        $deposit_condition = " AND m.default_deposit > 0";
        $deposit_filter = 'required';
        $filter_applied = true;
    } elseif ($_GET['deposit_status'] === 'no_required') {
        $deposit_condition = " AND m.default_deposit = 0";
        $deposit_filter = 'no_required';
        $filter_applied = true;
    } elseif ($_GET['deposit_status'] === 'deposited') {
        $deposit_condition = " AND EXISTS (SELECT 1 FROM bookings b WHERE b.motel_id = m.id AND b.status = 'SUCCESS')";
        $deposit_filter = 'deposited';
        $filter_applied = true;
    }
}

// Lọc theo khu vực nếu được yêu cầu
$district_filter = '';
$district_condition = '';
if (isset($_GET['district']) && is_numeric($_GET['district'])) {
    $district_id = $_GET['district'];
    $district_condition = " AND district_id = $district_id";
    $district_filter = $district_id;
    $filter_applied = true;
}

// Lọc theo loại phòng nếu được yêu cầu
$category_filter = '';
$category_condition = '';
if (isset($_GET['category']) && is_numeric($_GET['category'])) {
    $category_id = $_GET['category'];
    $category_condition = " AND category_id = $category_id";
    $category_filter = $category_id;
    $filter_applied = true;
}

// Sắp xếp theo các tiêu chí
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$sort_condition = '';

switch ($sort_by) {
    case 'price_asc':
        $sort_condition = " ORDER BY price ASC";
        break;
    case 'price_desc':
        $sort_condition = " ORDER BY price DESC";
        break;
    case 'views':
        $sort_condition = " ORDER BY count_view DESC";
        break;
    case 'favorites':
        $sort_condition = " ORDER BY wishlist DESC";
        break;
    case 'oldest':
        $sort_condition = " ORDER BY created_at ASC";
        break;
    default:
        $sort_condition = " ORDER BY created_at DESC"; // Mặc định sắp xếp theo mới nhất
        $sort_by = 'newest';
        break;
}

// Lấy danh sách phòng trọ của người dùng hiện tại
$sql = "SELECT m.*, c.name AS category_name, d.name AS district_name, 
        (SELECT COUNT(*) FROM user_wishlist WHERE motel_id = m.id) AS wishlist_count,
        m.images AS main_image
        FROM motel m
        LEFT JOIN categories c ON m.category_id = c.id
        LEFT JOIN districts d ON m.district_id = d.id
        LEFT JOIN users u ON m.user_id = u.id
        WHERE m.user_id = ?{$status_condition}{$rental_condition}{$deposit_condition}{$district_condition}{$category_condition}
        {$sort_condition}
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $offset, $rooms_per_page);
$stmt->execute();
$result = $stmt->get_result();
$rooms = $result->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách quận/huyện cho bộ lọc
$district_query = "SELECT id, name FROM districts ORDER BY name ASC";
$district_result = $conn->query($district_query);
$districts = $district_result->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách loại phòng cho bộ lọc
$category_query = "SELECT id, name FROM categories ORDER BY name ASC";
$category_result = $conn->query($category_query);
$categories = $category_result->fetch_all(MYSQLI_ASSOC);

// Đếm phòng đang chờ phê duyệt và đã phê duyệt
$pending_count_sql = "SELECT COUNT(*) as total FROM motel WHERE user_id = ? AND approve = 0";
$pending_count_stmt = $conn->prepare($pending_count_sql);
$pending_count_stmt->bind_param("i", $user_id);
$pending_count_stmt->execute();
$pending_count_result = $pending_count_stmt->get_result();
$pending_count = $pending_count_result->fetch_assoc()['total'];

$approved_count_sql = "SELECT COUNT(*) as total FROM motel WHERE user_id = ? AND approve = 1";
$approved_count_stmt = $conn->prepare($approved_count_sql);
$approved_count_stmt->bind_param("i", $user_id);
$approved_count_stmt->execute();
$approved_count_result = $approved_count_stmt->get_result();
$approved_count = $approved_count_result->fetch_assoc()['total'];

// Đếm phòng còn trống và đã cho thuê
$available_count_sql = "SELECT COUNT(*) as total FROM motel WHERE user_id = ? AND isExist = 1";
$available_count_stmt = $conn->prepare($available_count_sql);
$available_count_stmt->bind_param("i", $user_id);
$available_count_stmt->execute();
$available_count_result = $available_count_stmt->get_result();
$available_count = $available_count_result->fetch_assoc()['total'];

$rented_count_sql = "SELECT COUNT(*) as total FROM motel WHERE user_id = ? AND isExist = 0";
$rented_count_stmt = $conn->prepare($rented_count_sql);
$rented_count_stmt->bind_param("i", $user_id);
$rented_count_stmt->execute();
$rented_count_result = $rented_count_stmt->get_result();
$rented_count = $rented_count_result->fetch_assoc()['total'];

// Đếm phòng đã cho thuê
$rented_count_sql = "SELECT COUNT(*) as total FROM motel WHERE user_id = ? AND isExist = 0";
$rented_count_stmt = $conn->prepare($rented_count_sql);
$rented_count_stmt->bind_param("i", $user_id);
$rented_count_stmt->execute();
$rented_count_result = $rented_count_stmt->get_result();
$rented_count = $rented_count_result->fetch_assoc()['total'];

// Format tiền tệ Việt Nam
function formatCurrency($amount)
{
    return number_format($amount, 0, ',', '.') . ' đ';
}

// Định dạng ngày tháng
function formatDateTime($datetime)
{
    $date = new DateTime($datetime);
    return $date->format('d/m/Y H:i');
}

// Rút gọn văn bản
function truncateText($text, $length = 100)
{
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}

// Lấy thông tin về các bài đăng phổ biến nhất của người dùng (top 3)
$popular_sql = "SELECT m.*, 
                m.images AS main_image 
                FROM motel m 
                WHERE m.user_id = ? 
                ORDER BY m.count_view DESC, m.wishlist DESC 
                LIMIT 3";
$popular_stmt = $conn->prepare($popular_sql);
$popular_stmt->bind_param("i", $user_id);
$popular_stmt->execute();
$popular_result = $popular_stmt->get_result();
$popular_rooms = $popular_result->fetch_all(MYSQLI_ASSOC);

// Thêm header và giao diện
include('../components/header.php');
?>
<!-- Link Bootstrap 5 và Font Awesome nếu chưa được include -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

<div class="container mt-5 pt-5 animate__animated animate__fadeIn">
    <h1 class="mb-4 fw-bold text-primary">Quản lý phòng của tôi</h1>
    <div class="row">
        <!-- Thống kê và bài viết nổi bật -->
        <div class="col-md-4 animate__animated animate__fadeInLeft">
            <div class="card mb-4 shadow-sm border-0 rounded">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Thống kê bài đăng</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="statistic-item text-center">

                            <h3 class="mb-0"><?php echo $total_rooms; ?></h3>
                            <p class="text-muted mb-0">Tổng số</p>
                        </div>

                        <div class="statistic-item text-center">
                            <h3 class="mb-0"><?php echo $pending_count; ?></h3>
                            <p class="text-muted mb-0">Chờ duyệt</p>
                        </div>

                        <div class="statistic-item text-center">
                            <h3 class="mb-0"><?php echo $approved_count; ?></h3>
                            <p class="text-muted mb-0">Đã duyệt</p>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <a href="/room/post.php" class="btn btn-primary w-100">
                            <i class="fas fa-plus-circle me-2"></i>Đăng tin mới
                        </a>
                    </div>
                </div>
            </div>

            <?php if (!empty($popular_rooms)): ?>
                <div class="card mb-4 shadow-sm border-0 rounded">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-fire me-2"></i>Phòng nổi bật của bạn</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($popular_rooms as $room): ?>
                            <div class="popular-room p-3 border-bottom">
                                <div class="row">
                                    <div class="col-4">
                                        <?php if (!empty($room['main_image'])): ?>
                                            <img src="../<?php echo $room['main_image']; ?>" class="img-fluid rounded" alt="<?php echo $room['title']; ?>">
                                        <?php else: ?>
                                            <img src="../assets/client/images/no-image.jpg" class="img-fluid rounded" alt="No Image">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-8">
                                        <h6 class="mb-1 text-truncate"><?php echo $room['title']; ?></h6>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-primary fw-bold"><?php echo formatCurrency($room['price']); ?></span>
                                            <span class="badge <?php echo $room['approve'] ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                                <?php echo $room['approve'] ? 'Đã duyệt' : 'Chờ duyệt'; ?>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <small class="text-muted"><i class="fas fa-eye me-1"></i><?php echo $room['count_view']; ?></small>
                                            <small class="text-muted"><i class="fas fa-heart me-1 text-danger"></i><?php echo $room['wishlist']; ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="p-3 bg-light">
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?sort=views" class="text-decoration-none text-primary">
                                <i class="fas fa-arrow-right me-1"></i>Xem tất cả bài đăng của bạn
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 rounded">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Lọc bài đăng</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET" class="filter-form">
                        <div class="mb-3">
                            <label class="form-label">Trạng thái</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="status_all" value=""
                                    <?php echo empty($status_filter) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="status_all">Tất cả</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="status_pending" value="pending"
                                    <?php echo $status_filter === 'pending' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="status_pending">Chờ duyệt</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="status_approved" value="approved"
                                    <?php echo $status_filter === 'approved' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="status_approved">Đã duyệt</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Trạng thái cho thuê</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="rental_status" id="rental_status_all" value=""
                                    <?php echo empty($rental_filter) ? 'checked' : ''; ?>
                                    <label class="form-check-label" for="rental_status_all">Tất cả</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="rental_status" id="rental_status_available" value="available"
                                    <?php echo $rental_filter === 'available' ? 'checked' : ''; ?>
                                    <label class="form-check-label" for="rental_status_available">Còn trống</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="rental_status" id="rental_status_rented" value="rented"
                                    <?php echo $rental_filter === 'rented' ? 'checked' : ''; ?>
                                    <label class="form-check-label" for="rental_status_rented">Đã thuê</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Trạng thái đặt cọc</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="deposit_status" id="deposit_status_all" value=""
                                    <?php echo empty($deposit_filter) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="deposit_status_all">Tất cả</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="deposit_status" id="deposit_status_required" value="required"
                                    <?php echo $deposit_filter === 'required' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="deposit_status_required">Có yêu cầu cọc</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="deposit_status" id="deposit_status_no_required" value="no_required"
                                    <?php echo $deposit_filter === 'no_required' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="deposit_status_no_required">Không yêu cầu cọc</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="deposit_status" id="deposit_status_deposited" value="deposited"
                                    <?php echo $deposit_filter === 'deposited' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="deposit_status_deposited">Đã có người đặt cọc</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="district" class="form-label">Khu vực</label>
                            <select class="form-select" name="district" id="district">
                                <option value="">Tất cả khu vực</option>
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?php echo $district['id']; ?>" <?php echo $district_filter == $district['id'] ? 'selected' : ''; ?>>
                                        <?php echo $district['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="category" class="form-label">Loại phòng</label>
                            <select class="form-select" name="category" id="category">
                                <option value="">Tất cả loại phòng</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo $category['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="sort" class="form-label">Sắp xếp theo</label>
                            <select class="form-select" name="sort" id="sort">
                                <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                                <option value="oldest" <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Cũ nhất</option>
                                <option value="price_asc" <?php echo $sort_by === 'price_asc' ? 'selected' : ''; ?>>Giá thấp đến cao</option>
                                <option value="price_desc" <?php echo $sort_by === 'price_desc' ? 'selected' : ''; ?>>Giá cao đến thấp</option>
                                <option value="views" <?php echo $sort_by === 'views' ? 'selected' : ''; ?>>Lượt xem</option>
                                <option value="favorites" <?php echo $sort_by === 'favorites' ? 'selected' : ''; ?>>Lượt thích</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Lọc
                            </button>
                            <?php if ($filter_applied): ?>
                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Xóa bộ lọc
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Danh sách phòng đã đăng -->
        <div class="col-md-8 animate__animated animate__fadeInRight">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="fas fa-clipboard-list me-2 text-primary"></i>Phòng đã đăng</h2>
                <span class="badge bg-primary rounded-pill"><?php echo $total_rooms; ?> phòng</span>
            </div>

            <?php if (!empty($delete_message)): ?>
                <div class="alert alert-<?php echo $delete_status; ?> alert-dismissible fade show" role="alert">
                    <?php echo $delete_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($rooms)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-home fa-4x mb-3 text-muted animate__animated animate__pulse animate__infinite"></i>
                        <h4>Bạn chưa đăng phòng nào</h4>
                        <p class="text-muted">Hãy bắt đầu đăng tin ngay để tìm người thuê phòng của bạn</p>
                        <a href="/room/post.php" class="btn btn-primary mt-3 btn-lg animate__animated animate__heartBeat animate__delay-1s">
                            <i class="fas fa-plus-circle me-2"></i>Đăng tin ngay
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <?php foreach ($rooms as $room): ?>
                        <div class="col">
                            <div class="card h-100 border-0 shadow room-card">
                                <div class="position-relative room-image-container">
                                    <?php if (!empty($room['main_image'])): ?>
                                        <img src="/<?php echo $room['main_image']; ?>" class="card-img-top room-image" alt="<?php echo $room['title']; ?>">
                                    <?php else: ?>
                                        <img src="/assets/client/images/no-image.jpg" class="card-img-top room-image" alt="No Image">
                                    <?php endif; ?>
                                    <span class="badge position-absolute top-0 start-0 m-2 <?php echo $room['approve'] ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                        <?php echo $room['approve'] ? 'Đã duyệt' : 'Chờ duyệt'; ?>
                                    </span>
                                    <span class="badge position-absolute top-0 end-0 m-2 <?php echo $room['isExist'] == 1 ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $room['isExist'] == 1 ? 'Còn trống' : 'Đã thuê'; ?>
                                    </span>
                                    <?php if ($room['default_deposit'] > 0): ?>
                                        <span class="badge position-absolute bottom-0 start-0 m-2 bg-info">
                                            Có cọc
                                        </span>
                                    <?php endif; ?>
                                    <?php if (isRoomBooked($conn, $room['id'])): ?>
                                        <span class="badge position-absolute bottom-0 end-0 m-2 bg-danger">
                                            <i class="fas fa-lock me-1"></i>Đã có người đặt cọc
                                        </span>
                                    <?php endif; ?>
                                    <div class="room-overlay">
                                        <a href="/room/room_detail.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-light rounded-circle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title text-truncate mb-1">
                                        <a href="/room/room_detail.php?id=<?php echo $room['id']; ?>" class="text-decoration-none text-dark">
                                            <?php echo $room['title']; ?>
                                        </a>
                                    </h5>
                                    <p class="text-primary fw-bold mb-2"><?php echo formatCurrency($room['price']); ?>/tháng</p>

                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted small">
                                            <i class="fas fa-map-marker-alt me-1"></i><?php echo $room['district_name'] ?? 'N/A'; ?>
                                        </span>
                                        <span class="text-muted small">
                                            <i class="fas fa-expand me-1"></i><?php echo $room['area']; ?>m²
                                        </span>
                                    </div>

                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted small">
                                            <i class="fas fa-eye me-1"></i><?php echo $room['count_view']; ?> lượt xem
                                        </span>
                                        <span class="text-muted small">
                                            <i class="fas fa-heart me-1 text-danger"></i><?php echo $room['wishlist']; ?> yêu thích
                                        </span>
                                    </div>

                                    <p class="small text-muted mb-1">
                                        <i class="fas fa-tag me-1"></i><?php echo $room['category_name'] ?? 'N/A'; ?>
                                    </p>
                                    <p class="small text-muted mb-0">
                                        <i class="far fa-clock me-1"></i>Đăng ngày: <?php echo formatDateTime($room['created_at']); ?>
                                    </p>
                                </div>
                                <div class="card-footer bg-white border-top-0">
                                    <div class="d-flex justify-content-between">
                                        <?php
                                        $current_status_text = $room['isExist'] == 1 ? 'Còn trống' : 'Đã thuê';
                                        $next_status = $room['isExist'] == 1 ? 0 : 1;
                                        $next_status_text = $next_status == 1 ? 'Còn trống' : 'Đã thuê';
                                        $button_class = $next_status == 1 ? 'btn-success' : 'btn-secondary';
                                        $confirm_message = 'Bạn có chắc chắn muốn chuyển phòng \'' . addslashes($room['title']) . '\' sang trạng thái \'' . $next_status_text . '\' không?';
                                        $is_released = isRoomReleased($conn, $room['id']);
                                        ?>
                                        <?php if ($is_released): ?>
                                            <span class="badge bg-secondary flex-grow-1 me-2" data-bs-toggle="tooltip" title="Phòng đã giải ngân, không thể thao tác">
                                                <i class="fas fa-lock me-1"></i>Đã giải ngân
                                            </span>
                                        <?php else: ?>
                                            <a href="/room/edit_room.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-primary flex-grow-1 me-2 action-btn">
                                                <i class="fas fa-edit me-1"></i>Sửa
                                            </a>
                                            <?php if (!($next_status == 1 && $is_released)): ?>
                                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=toggle_status&id=<?php echo $room['id']; ?>&new_status=<?php echo $next_status; ?>"
                                                    class="btn btn-sm <?php echo $button_class; ?> flex-grow-1 me-2 action-btn"
                                                    onclick="return confirm('<?php echo $confirm_message; ?>')"
                                                    title="Chuyển trạng thái sang <?php echo $next_status_text; ?>">
                                                    <i class="fas <?php echo $next_status == 1 ? 'fa-circle-check' : 'fa-circle-xmark'; ?> me-1"></i>
                                                    <?php echo 'Chuyển sang ' . $next_status_text; ?>
                                                </a>
                                            <?php elseif ($next_status == 1 && $is_released): ?>
                                                <span class="badge bg-secondary flex-grow-1 me-2" data-bs-toggle="tooltip" title="Phòng đã giải ngân, không thể chuyển lại còn trống">
                                                    <i class="fas fa-lock me-1"></i>Đã giải ngân
                                                </span>
                                            <?php endif; ?>
                                            <a href="#" class="btn btn-sm btn-danger flex-grow-1 action-btn show-delete-modal" data-modal-id="deleteModal<?php echo $room['id']; ?>">
                                                <i class="fas fa-trash-alt me-1"></i>Xóa
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Xác nhận xóa -->
                            <div class="modal fade" id="deleteModal<?php echo $room['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $room['id']; ?>" aria-hidden="true" data-bs-backdrop="static">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $room['id']; ?>">Xác nhận xóa</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Bạn có chắc chắn muốn xóa phòng "<strong><?php echo $room['title']; ?></strong>" không?</p>
                                            <p class="text-danger"><small>Lưu ý: Hành động này không thể hoàn tác và tất cả dữ liệu liên quan đến phòng này sẽ bị xóa.</small></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?delete=<?php echo $room['id']; ?>" class="btn btn-danger">Xóa</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Phân trang -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4" aria-label="Phân trang">
                        <ul class="pagination justify-content-center">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=1<?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($rental_filter) ? '&rental_status=' . $rental_filter : ''; ?><?php echo !empty($deposit_filter) ? '&deposit_status=' . $deposit_filter : ''; ?><?php echo !empty($district_filter) ? '&district=' . $district_filter : ''; ?><?php echo !empty($category_filter) ? '&category=' . $category_filter : ''; ?><?php echo !empty($sort_by) ? '&sort=' . $sort_by : ''; ?>" aria-label="Đầu tiên">
                                        <span aria-hidden="true">&laquo;&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $current_page - 1; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($rental_filter) ? '&rental_status=' . $rental_filter : ''; ?><?php echo !empty($deposit_filter) ? '&deposit_status=' . $deposit_filter : ''; ?><?php echo !empty($district_filter) ? '&district=' . $district_filter : ''; ?><?php echo !empty($category_filter) ? '&category=' . $category_filter : ''; ?><?php echo !empty($sort_by) ? '&sort=' . $sort_by : ''; ?>" aria-label="Trước">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);

                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $i; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($rental_filter) ? '&rental_status=' . $rental_filter : ''; ?><?php echo !empty($deposit_filter) ? '&deposit_status=' . $deposit_filter : ''; ?><?php echo !empty($district_filter) ? '&district=' . $district_filter : ''; ?><?php echo !empty($category_filter) ? '&category=' . $category_filter : ''; ?><?php echo !empty($sort_by) ? '&sort=' . $sort_by : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $current_page + 1; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($rental_filter) ? '&rental_status=' . $rental_filter : ''; ?><?php echo !empty($deposit_filter) ? '&deposit_status=' . $deposit_filter : ''; ?><?php echo !empty($district_filter) ? '&district=' . $district_filter : ''; ?><?php echo !empty($category_filter) ? '&category=' . $category_filter : ''; ?><?php echo !empty($sort_by) ? '&sort=' . $sort_by : ''; ?>" aria-label="Tiếp">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $total_pages; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($rental_filter) ? '&rental_status=' . $rental_filter : ''; ?><?php echo !empty($deposit_filter) ? '&deposit_status=' . $deposit_filter : ''; ?><?php echo !empty($district_filter) ? '&district=' . $district_filter : ''; ?><?php echo !empty($category_filter) ? '&category=' . $category_filter : ''; ?><?php echo !empty($sort_by) ? '&sort=' . $sort_by : ''; ?>" aria-label="Cuối cùng">
                                        <span aria-hidden="true">&raquo;&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    /* Thiết lập giao diện */
    :root {
        --primary-color: #4776e6;
        --secondary-color: #8e54e9;
        --accent-color: #ff6b6b;
        --success-color: #20c997;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --light-color: #f8f9fa;
        --dark-color: #343a40;
    }

    .statistic-icon {
        width: 60px;
        height: 60px;
        line-height: 60px;
        font-size: 24px;
        display: inline-block;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        border-radius: 50%;
    }

    .room-card {
        transition: all 0.4s ease;
        overflow: hidden;
        border-radius: 15px !important;
    }

    .room-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15) !important;
    }

    .room-image-container {
        position: relative;
        overflow: hidden;
        height: 220px;
    }

    .room-image {
        height: 100%;
        width: 100%;
        object-fit: cover;
        transition: transform 0.7s ease;
    }

    .room-card:hover .room-image {
        transform: scale(1.08);
    }

    .room-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .room-card:hover .room-overlay {
        opacity: 1;
    }

    .room-overlay .btn {
        width: 45px;
        height: 45px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        transform: scale(0);
        transition: transform 0.3s ease;
    }

    .room-card:hover .room-overlay .btn {
        transform: scale(1);
    }

    .popular-room {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }

    .popular-room:hover {
        background-color: var(--light-color);
        border-left: 4px solid var(--primary-color);
        transform: translateX(5px);
    }

    .card {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        border: none;
    }

    .card-header {
        border-bottom: none;
        border-radius: 15px 15px 0 0 !important;
        padding: 15px 20px;
    }

    .badge {
        font-weight: 600;
        padding: 0.6em 1em;
        border-radius: 50px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .filter-form .form-check-input:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .filter-form .form-select:focus,
    .filter-form .form-check-input:focus {
        box-shadow: 0 0 0 0.25rem rgba(71, 118, 230, 0.25);
        border-color: #86b7fe;
    }

    .filter-form label {
        font-weight: 500;
        margin-bottom: 8px;
    }

    .filter-form .form-select,
    .filter-form .form-check-input {
        border-radius: 8px;
    }

    .action-btn {
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .action-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
    }

    /* Pagination styling */
    .page-link {
        color: var(--primary-color);
        border-radius: 8px;
        margin: 0 2px;
        border: none;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .page-item.active .page-link {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .pagination {
        gap: 5px;
    }

    /* Responsive fixes */
    @media (max-width: 767px) {
        .statistic-item {
            padding: 10px;
            margin-bottom: 10px;
        }

        .col-md-4 {
            margin-bottom: 20px;
        }
    }

    /* Better typography */
    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        font-weight: 600;
    }

    .card-title {
        font-weight: 600;
        line-height: 1.4;
    }

    /* Animation for cards */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .col {
        animation: fadeInUp 0.6s ease-out forwards;
    }

    .col:nth-child(2) {
        animation-delay: 0.1s;
    }

    .col:nth-child(3) {
        animation-delay: 0.2s;
    }

    .col:nth-child(4) {
        animation-delay: 0.3s;
    }

    .col:nth-child(5) {
        animation-delay: 0.4s;
    }

    .col:nth-child(6) {
        animation-delay: 0.5s;
    }

    /* Fix cho modal backdrop */
    .modal-open {
        overflow: auto !important;
        padding-right: 0 !important;
    }

    body {
        overflow: auto !important;
        padding-right: 0 !important;
    }

    /* Tùy chỉnh modal */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1050;
        overflow: auto;
        padding-right: 0 !important;
        opacity: 0;
        transition: opacity 0.15s linear;
    }

    .modal.show {
        display: block;
        opacity: 1;
    }

    .modal-dialog {
        margin: 1.75rem auto;
        transition: transform 0.3s ease-out;
        transform: translate(0, -50px);
    }

    .modal.show .modal-dialog {
        transform: translate(0, 0);
    }

    .modal-backdrop {
        display: none !important;
    }

    .modal-content {
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
        border: none;
    }

    /* Đảm bảo nút đóng modal hoạt động đúng */
    .modal .btn-close:focus,
    .modal .btn[data-bs-dismiss="modal"]:focus {
        box-shadow: none;
    }
</style>

<!-- Bootstrap 5 JS và Popper JS -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

<script>
    // Hiệu ứng khi tải trang
    document.addEventListener('DOMContentLoaded', function() {
        // Thêm hiệu ứng khi scrolling
        window.addEventListener('scroll', function() {
            const cards = document.querySelectorAll('.room-card');
            cards.forEach(card => {
                const cardPosition = card.getBoundingClientRect().top;
                const screenPosition = window.innerHeight / 1.3;

                if (cardPosition < screenPosition) {
                    card.classList.add('animate__animated', 'animate__fadeIn');
                }
            });
        });

        // Tooltips initialization
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Xóa bất kỳ backdrop nào còn sót lại từ trước
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';

        // Xử lý tự quản lý modal hoàn toàn
        document.querySelectorAll('.show-delete-modal').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();

                // Lấy ID modal từ data attribute
                const modalId = this.getAttribute('data-modal-id');
                const modal = document.getElementById(modalId);

                if (modal) {
                    // Hiển thị modal với style thủ công
                    modal.style.display = 'block';

                    // Thêm độ trễ nhỏ để animation hiển thị mượt mà
                    setTimeout(() => {
                        modal.classList.add('show');
                    }, 10);

                    // Xử lý các nút đóng trong modal
                    modal.querySelectorAll('.btn-close, .btn[data-bs-dismiss="modal"]').forEach(closeBtn => {
                        closeBtn.addEventListener('click', function() {
                            closeModal(modal);
                        });
                    });

                    // Đóng modal khi click vào nền
                    modal.addEventListener('click', function(event) {
                        if (event.target === modal) {
                            closeModal(modal);
                        }
                    });
                }
            });
        });

        // Hàm đóng modal
        function closeModal(modal) {
            modal.style.display = 'none';
            modal.classList.remove('show');
        }

        // Xử lý phím Esc để đóng modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.show').forEach(modal => {
                    closeModal(modal);
                });
            }
        });
    });
</script>

<?php include('../components/footer.php'); ?>