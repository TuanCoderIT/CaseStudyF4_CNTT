<?php
// Khởi tạo phiên làm việc
session_start();

// Function to check if a room has been booked
function isRoomBooked($conn, $room_id)
{
    $query = "SELECT id FROM bookings WHERE motel_id = ? AND status != 'REFUNDED'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return ($result->num_rows > 0);
}

// Kiểm tra xem người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: ./auth/login.php');
    exit;
}

// Kết nối đến CSDL
require_once(dirname(__DIR__) . '/config/db.php');

// Khởi tạo mảng favorite_rooms từ CSDL
require_once(dirname(__DIR__) . '/config/favorites.php');

// Danh sách quận/huyện
$stmt_districts = $conn->prepare("SELECT * FROM districts ORDER BY name");
$stmt_districts->execute();
$districts = $stmt_districts->get_result();

// Danh sách danh mục
$stmt_categories = $conn->prepare("SELECT * FROM categories ORDER BY name");
$stmt_categories->execute();
$categories = $stmt_categories->get_result();

// Mặc định sort là newest
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$sort_sql = "";

// Xử lý tìm kiếm và sắp xếp
switch ($sort) {
    case 'view':
        $sort_sql = "ORDER BY m.count_view DESC";
        $sort_text = "Phòng trọ xem nhiều nhất";
        $sort_icon = "fas fa-fire text-danger";
        break;
    case 'nearest':
        $sort_text = "Phòng trọ gần trường ĐH Vinh";
        $sort_icon = "fas fa-university text-primary";
        break;
    case 'price_asc':
        $sort_sql = "ORDER BY m.price ASC";
        $sort_text = "Giá từ thấp đến cao";
        $sort_icon = "fas fa-sort-amount-down text-success";
        break;
    case 'price_desc':
        $sort_sql = "ORDER BY m.price DESC";
        $sort_text = "Giá từ cao đến thấp";
        $sort_icon = "fas fa-sort-amount-up text-warning";
        break;
    case 'newest':
        $sort_sql = "ORDER BY m.price DESC";
        $sort_text = "Phòng trọ mới nhất";
        $sort_icon = "fas fa-sort-amount-up text-warning";
        break;
    case 'nearyou':
        $sort_sql = "";
        $sort_text = "Phòng trọ gần bạn";
        $sort_icon = "fas fa-user-friends text-info";
        break;
    default:
        $sort_sql = "ORDER BY m.created_at DESC";
        $sort_text = "Phòng trọ mới đăng tải";
        $sort_icon = "fas fa-clock text-success";
        break;
}

// Xây dựng query dựa trên các tham số tìm kiếm
$where_conditions = ["m.approve = 1"];
$params = [];
$param_types = "";

// Lọc theo quận/huyện
if (isset($_GET['district']) && !empty($_GET['district'])) {
    $where_conditions[] = "m.district_id = ?";
    $params[] = $_GET['district'];
    $param_types .= "i";
}

// Lọc theo danh mục
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $where_conditions[] = "m.category_id = ?";
    $params[] = $_GET['category'];
    $param_types .= "i";
}

// Lọc theo khoảng giá
if (isset($_GET['price']) && !empty($_GET['price'])) {
    $price_range = explode('-', $_GET['price']);
    if (count($price_range) == 2) {
        $where_conditions[] = "m.price BETWEEN ? AND ?";
        $params[] = $price_range[0];
        $params[] = $price_range[1];
        $param_types .= "ii";
    }
}

// Lọc theo tiện ích
if (isset($_GET['utilities']) && !empty($_GET['utilities'])) {
    // Xử lý nhiều tiện ích được chọn
    $utilities = is_array($_GET['utilities']) ? $_GET['utilities'] : [$_GET['utilities']];

    foreach ($utilities as $utility) {
        $where_conditions[] = "m.utilities LIKE ?";
        $params[] = "%$utility%";
        $param_types .= "s";
    }
}

// Lọc theo diện tích
if (isset($_GET['area_min']) && !empty($_GET['area_min']) && isset($_GET['area_max']) && !empty($_GET['area_max'])) {
    $where_conditions[] = "m.area BETWEEN ? AND ?";
    $params[] = $_GET['area_min'];
    $params[] = $_GET['area_max'];
    $param_types .= "ii";
}

// Tìm kiếm theo từ khóa (địa chỉ hoặc tiêu đề)
if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
    $where_conditions[] = "(m.title LIKE ? OR m.address LIKE ?)";
    $params[] = "%{$_GET['keyword']}%";
    $params[] = "%{$_GET['keyword']}%";
    $param_types .= "ss";
}

$where_clause = implode(" AND ", $where_conditions);

// Chuẩn bị và thực thi truy vấn
$sql = "
    SELECT m.*, u.name as owner_name, c.name as category_name, d.name as district_name 
    FROM motel m 
    LEFT JOIN users u ON m.user_id = u.id 
    LEFT JOIN categories c ON m.category_id = c.id
    LEFT JOIN districts d ON m.district_id = d.id
    WHERE $where_clause && m.isExist = 1
    $sort_sql
";



$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$rooms = $stmt->get_result();
$rooms = $rooms->fetch_all(MYSQLI_ASSOC);

if ($sort == 'nearest') {
    require_once(dirname(__DIR__) . '/utils/haversine.php');
    $rooms = handleGetRoomByIP($rooms, uniLatVinh, unitLngVinh);
}
if ($sort == 'nearyou') {
    require_once(dirname(__DIR__) . '/utils/haversine.php');
    $rooms = handleGetRoomByIP($rooms, $_SESSION['lat'], $_SESSION['lng']);
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tìm phòng trọ - Phòng trọ sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nouislider@14.7.0/distribute/nouislider.min.css">
</head>

<body class="search-body">
    <?php include dirname(__DIR__) . '/components/header.php' ?>

    <!-- Banner tìm kiếm -->
    <?php include dirname(__DIR__) . '/components/banner_search.php' ?>

    <!-- Kết quả tìm kiếm -->
    <section class="py-5 bg-light">
        <div class="container">
            <!-- Mobile filter toggle button -->
            <button id="mobile-filter-toggle" class="mobile-filter-toggle d-lg-none">
                <i class="fas fa-filter"></i>
            </button>

            <div class="row">
                <!-- Sidebar lọc -->
                <div class="col-lg-3 mb-4">
                    <div class="filter-sidebar">
                        <div class="filter-header">
                            <h4 class="filter-title mb-0"><i class="fas fa-sliders-h"></i> Lọc kết quả</h4>
                            <button type="button" class="btn-close d-lg-none" aria-label="Close"></button>
                        </div>
                        <form action="search.php" method="GET" id="filterForm">
                            <!-- Khu vực dropdown -->
                            <div class="filter-section mb-4">
                                <div class="filter-header">
                                    <h6 class="filter-title">
                                        <i class="fas fa-map-marker-alt"></i> Khu vực
                                        <i class="fas fa-chevron-down ms-auto filter-toggle d-lg-none"></i>
                                    </h6>
                                </div>
                                <div class="filter-content">
                                    <select name="district" class="form-select" onchange="this.form.submit()">
                                        <option value="">Tất cả khu vực</option>
                                        <?php while ($district = $districts->fetch_assoc()): ?>
                                            <option value="<?php echo $district['id']; ?>" <?php echo (isset($_GET['district']) && $_GET['district'] == $district['id']) ? 'selected' : ''; ?>>
                                                <?php echo $district['name']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Loại phòng dropdown -->
                            <div class="filter-section mb-4">
                                <div class="filter-header">
                                    <h6 class="filter-title">
                                        <i class="fas fa-home"></i> Loại phòng
                                        <i class="fas fa-chevron-down ms-auto filter-toggle d-lg-none"></i>
                                    </h6>
                                </div>
                                <div class="filter-content">
                                    <select name="category" class="form-select" onchange="this.form.submit()">
                                        <option value="">Tất cả loại phòng</option>
                                        <?php while ($category = $categories->fetch_assoc()): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
                                                <?php echo $category['name']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <!-- Khoảng giá -->
                                <div class="filter-group">
                                    <h6 class="fw-bold mt-4">Khoảng giá</h6>
                                    <select name="price" class="form-select" onchange="this.form.submit()">
                                        <option value="">Tất cả khoảng giá</option>
                                        <option value="0-1000000" <?php echo (isset($_GET['price']) && $_GET['price'] == '0-1000000') ? 'selected' : ''; ?>>Dưới 1 triệu</option>
                                        <option value="1000000-2000000" <?php echo (isset($_GET['price']) && $_GET['price'] == '1000000-2000000') ? 'selected' : ''; ?>>1 - 2 triệu</option>
                                        <option value="2000000-3000000" <?php echo (isset($_GET['price']) && $_GET['price'] == '2000000-3000000') ? 'selected' : ''; ?>>2 - 3 triệu</option>
                                        <option value="3000000-5000000" <?php echo (isset($_GET['price']) && $_GET['price'] == '3000000-5000000') ? 'selected' : ''; ?>>3 - 5 triệu</option>
                                        <option value="5000000-999999999" <?php echo (isset($_GET['price']) && $_GET['price'] == '5000000-999999999') ? 'selected' : ''; ?>>Trên 5 triệu</option>
                                    </select>
                                </div>

                                <!-- Diện tích -->
                                <div class="filter-group">
                                    <h6 class="fw-bold">Diện tích</h6>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div class="input-group">
                                                <input type="number" name="area_min" class="form-control" placeholder="Từ" value="<?php echo isset($_GET['area_min']) ? htmlspecialchars($_GET['area_min']) : ''; ?>">
                                                <span class="input-group-text">m²</span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="input-group">
                                                <input type="number" name="area_max" class="form-control" placeholder="Đến" value="<?php echo isset($_GET['area_max']) ? htmlspecialchars($_GET['area_max']) : ''; ?>">
                                                <span class="input-group-text">m²</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tiện ích -->
                                <div class="filter-group">
                                    <h6 class="fw-bold">Tiện ích</h6>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="utilities[]" value="Wifi" id="wifi" <?php echo (isset($_GET['utilities']) && in_array('Wifi', (array)$_GET['utilities'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="wifi">
                                            <i class="fas fa-wifi me-1 text-primary"></i> Wifi
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="utilities[]" value="Máy giặt" id="washer" <?php echo (isset($_GET['utilities']) && in_array('Máy giặt', (array)$_GET['utilities'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="washer">
                                            <i class="fas fa-tshirt me-1 text-warning"></i> Máy giặt
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="utilities[]" value="Gần trường" id="school" <?php echo (isset($_GET['utilities']) && in_array('Gần trường', (array)$_GET['utilities'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="school">
                                            <i class="fas fa-school me-1 text-success"></i> Gần trường
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="utilities[]" value="Điều hòa" id="ac" <?php echo (isset($_GET['utilities']) && in_array('Điều hòa', (array)$_GET['utilities'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="ac">
                                            <i class="fas fa-snowflake me-1 text-info"></i> Điều hòa
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="utilities[]" value="Tủ lạnh" id="fridge" <?php echo (isset($_GET['utilities']) && in_array('Tủ lạnh', (array)$_GET['utilities'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="fridge">
                                            <i class="fas fa-cube me-1 text-danger"></i> Tủ lạnh
                                        </label>
                                    </div>
                                </div>

                                <!-- Button áp dụng lọc -->
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter me-2"></i>Áp dụng bộ lọc
                                    </button>
                                    <a href="search.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-sync-alt me-2"></i>Xóa bộ lọc
                                    </a>
                                </div>

                                <!-- Hidden field for sort -->
                                <input type="hidden" name="sort" value="<?php echo $sort; ?>" id="sortField">
                        </form>
                    </div>
                </div>
                <!-- Kết quả phòng trọ -->
            </div>
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-0 d-inline-block">
                            <i class="<?php echo $sort_icon; ?> me-2"></i><?php echo $sort_text; ?>
                        </h4>
                        <span class="ms-2 text-muted">(<?php echo count($rooms); ?> phòng)</span>
                    </div>
                    <!-- Dropdown menu sắp xếp -->
                    <div class="dropdown sort-dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="dropdownSort" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-sort-amount-down me-2"></i>Sắp xếp theo
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownSort">
                            <li>
                                <h6 class="dropdown-header">Tiêu chí chính:</h6>
                            </li>
                            <li><a class="dropdown-item <?php echo $sort == 'view' ? 'active' : ''; ?>" href="javascript:void(0)" onclick="setSort('view')">
                                    <i class="fas fa-fire text-danger"></i> Phòng trọ xem nhiều nhất
                                </a></li>
                            <li><a class="dropdown-item <?php echo $sort == 'newest' ? 'active' : ''; ?>" href="javascript:void(0)" onclick="setSort('newest')">
                                    <i class="fas fa-clock text-success"></i> Phòng trọ mới được đăng tải
                                </a></li>
                            <li><a class="dropdown-item <?php echo $sort == 'nearest' ? 'active' : ''; ?>" href="javascript:void(0)" onclick="setSort('nearest')">
                                    <i class="fas fa-university text-primary"></i> Phòng trọ gần trường ĐH Vinh
                                </a></li>
                            <li>
                            <li><a class="dropdown-item <?php echo $sort == 'nearyou' ? 'active' : ''; ?>" href="javascript:void(0)" onclick="setSort('nearyou')">
                                    <i class="fas fa-university text-primary"></i> Phòng trọ gần bạn
                                </a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <h6 class="dropdown-header">Tiêu chí khác:</h6>
                            </li>
                            <li><a class="dropdown-item <?php echo $sort == 'price_asc' ? 'active' : ''; ?>" href="javascript:void(0)" onclick="setSort('price_asc')">
                                    <i class="fas fa-sort-amount-down text-success"></i> Giá thấp đến cao
                                </a></li>
                            <li><a class="dropdown-item <?php echo $sort == 'price_desc' ? 'active' : ''; ?>" href="javascript:void(0)" onclick="setSort('price_desc')">
                                    <i class="fas fa-sort-amount-up text-warning"></i> Giá cao đến thấp
                                </a></li>
                        </ul>
                    </div>
                </div>

                <?php if (count($rooms) > 0): ?>
                    <div class="row">
                        <?php foreach ($rooms as $room): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card room-card four-col h-100">
                                    <div class="room-image">
                                        <img src="/<?php echo $room['images']; ?>" class="card-img-top" alt="<?php echo $room['title']; ?>">
                                        <span class="price-tag"><?php echo number_format($room['price']); ?> đ/tháng</span>
                                        <span class="view-count"><i class="fas fa-eye me-1"></i><?php echo number_format($room['count_view']); ?></span>
                                        <?php if (isRoomBooked($conn, $room['id'])): ?>
                                            <span class="booked-tag"><i class="fas fa-lock me-1"></i>Đã có người đặt cọc</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="room_detail.php?id=<?php echo $room['id']; ?>"><?php echo $room['title']; ?></a>
                                        </h5>
                                        <p class="card-text address"><i class="fas fa-map-marker-alt me-2"></i><?php echo $room['address']; ?></p>
                                        <div class="room-info">
                                            <span><i class="fas fa-expand me-1"></i><?php echo $room['area']; ?> m²</span>
                                            <span><i class="fas fa-bolt me-1"></i>
                                                <?php
                                                $utilities = explode(',', $room['utilities']);
                                                echo count($utilities) . ' tiện ích';
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <small class="text-muted">Đăng bởi: <?php echo $room['owner_name']; ?></small>
                                        <small class="text-muted float-end">
                                            <i class="far fa-clock me-1"></i>
                                            <?php
                                            $date = new DateTime($room['created_at']);
                                            echo $date->format('d/m/Y');
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center py-5">
                        <i class="fas fa-search-minus fa-3x mb-3"></i>
                        <h4>Không tìm thấy phòng trọ</h4>
                        <p>Không tìm thấy phòng trọ nào phù hợp với tiêu chí tìm kiếm của bạn.</p>
                        <a href="search.php" class="btn btn-primary mt-3">
                            <i class="fas fa-sync-alt me-2"></i>Xóa bộ lọc và tìm lại
                        </a>
                    </div>
                <?php endif; ?>
            </div>
    </section>

    <?php include dirname(__DIR__) . '/components/footer.php' ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/nouislider@14.7.0/distribute/nouislider.min.js"></script>
    <script>
        // Hàm thiết lập sort và submit form
        function setSort(sortValue) {
            document.getElementById('sortField').value = sortValue;
            document.getElementById('filterForm').submit();
        }

        // Xử lý submit form khi checkbox tiện ích thay đổi
        document.querySelectorAll('input[type=checkbox]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        });
    </script>
</body>

</html>