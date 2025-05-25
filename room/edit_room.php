<?php
// Khởi tạo phiên làm việc
session_start();

// Kiểm tra xem người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Kết nối đến CSDL
require_once '../config/db.php';
require_once '../config/config.php';

// Khởi tạo mảng favorite_rooms từ CSDL
require_once '../config/favorites.php';

// Lấy ID của phòng trọ cần chỉnh sửa
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: my_posted_rooms.php');
    exit;
}

$room_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Kiểm tra xem phòng trọ có thuộc về người dùng hiện tại không
$check_query = "SELECT m.id, m.title, m.description, m.price, m.area, m.address, m.latlng, m.phone, 
                m.category_id, m.district_id, m.utilities, m.images, m.default_deposit, m.isExist,
                d.name as district_name, c.name as category_name 
                FROM motel m 
                LEFT JOIN districts d ON m.district_id = d.id 
                LEFT JOIN categories c ON m.category_id = c.id
                WHERE m.id = $room_id AND m.user_id = $user_id";
$room_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($room_result) === 0) {
    header('Location: my_posted_rooms.php');
    exit;
}

$room = mysqli_fetch_assoc($room_result);

// Debug: Check room data structure
if (isset($_GET['debug']) && $_GET['debug'] == 1) {
    echo '<pre>';
    echo 'Room data structure:<br>';
    print_r($room);
    echo '</pre>';
}

// If address_detail doesn't exist in the database, extract it from the address
if (!isset($room['address_detail']) && isset($room['address'])) {
    // Extract address_detail from full address (assuming format: detail, district, city, province)
    $address_parts = explode(',', $room['address']);
    if (count($address_parts) > 0) {
        // Extract the first part (the street address)
        $room['address_detail'] = trim($address_parts[0]);

        // If district_name is in the address, extract everything before it
        if (isset($room['district_name'])) {
            $district_pos = stripos($room['address'], $room['district_name']);
            if ($district_pos !== false) {
                // Get everything before the district name
                $address_detail = trim(substr($room['address'], 0, $district_pos));
                // Remove trailing comma if present
                $room['address_detail'] = rtrim($address_detail, ', ');
            }
        }
    } else {
        $room['address_detail'] = '';
    }
}

// Parse latlng
$lat = '';
$lng = '';
if (!empty($room['latlng'])) {
    $coords = explode(',', $room['latlng']);
    if (count($coords) == 2) {
        $lat = trim($coords[0]);
        $lng = trim($coords[1]);
    }
}

// Danh sách quận/huyện
$districts = mysqli_query($conn, "SELECT * FROM districts ORDER BY name");

// Danh sách danh mục
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

// Khởi tạo biến lỗi và thông báo
$errors = [];
$success_message = '';

// Xử lý khi form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy thông tin từ form
    $title = mysqli_real_escape_string($conn, $_POST['title'] ?? '');
    $description = $_POST['description'] ?? '';
    $price = (int)($_POST['price'] ?? 0);
    $area = (int)($_POST['area'] ?? 0);
    $address_detail = mysqli_real_escape_string($conn, $_POST['address_detail'] ?? '');
    $district_id = (int)($_POST['district_id'] ?? 0);
    $category_id = (int)$_POST['category_id'];
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $lat = isset($_POST['lat']) ? mysqli_real_escape_string($conn, $_POST['lat']) : '';
    $lng = isset($_POST['lng']) ? mysqli_real_escape_string($conn, $_POST['lng']) : '';
    $latlng = (!empty($lat) && !empty($lng)) ? $lat . ',' . $lng : '';
    $default_deposit = (int)($_POST['default_deposit'] ?? 0);
    $isExist = isset($_POST['isExist']) ? (int)$_POST['isExist'] : 1;

    // Lấy tên quận từ district_id
    $district_query = mysqli_query($conn, "SELECT name FROM districts WHERE id = $district_id");
    $district_name = '';
    if ($district_row = mysqli_fetch_assoc($district_query)) {
        $district_name = $district_row['name'];
    }

    $address = $address_detail . ', ' . $district_name . ', Thành phố Vinh, Nghệ An';

    // Lấy danh sách tiện ích
    $utilities = isset($_POST['utilities']) ? implode(', ', $_POST['utilities']) : '';

    // Validate dữ liệu
    if (empty($title)) {
        $errors[] = "Vui lòng nhập tiêu đề phòng trọ.";
    } elseif (strlen($title) < 10 || strlen($title) > 255) {
        $errors[] = "Tiêu đề phải từ 10 đến 255 ký tự.";
    }

    if (empty($description)) {
        $errors[] = "Vui lòng nhập mô tả chi tiết.";
    } elseif (strlen($description) < 30) {
        $errors[] = "Mô tả phải có ít nhất 30 ký tự.";
    }

    if ($price <= 0) {
        $errors[] = "Vui lòng nhập giá thuê hợp lệ.";
    }

    if ($area <= 0) {
        $errors[] = "Vui lòng nhập diện tích hợp lệ.";
    }

    if (empty($address)) {
        $errors[] = "Vui lòng nhập địa chỉ phòng trọ.";
    }

    if ($district_id <= 0) {
        $errors[] = "Vui lòng chọn khu vực.";
    }

    if (empty($phone)) {
        $errors[] = "Vui lòng nhập số điện thoại liên hệ.";
    } elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) {
        $errors[] = "Số điện thoại không hợp lệ (phải có 10-11 số).";
    }

    // Kiểm tra tiền đặt cọc không được âm
    if ($default_deposit < 0) {
        $errors[] = "Tiền đặt cọc không được âm.";
    }

    // Kiểm tra xem latlng có đúng định dạng không
    if (!empty($_POST['latlng'])) {
        $latlngInput = $_POST['latlng'];
        if (!preg_match('/^[-]?[0-9]{1,2}\.?[0-9]*,[-]?[0-9]{1,3}\.?[0-9]*$/', $latlngInput)) {
            $errors[] = "Định dạng tọa độ không hợp lệ. Vui lòng sử dụng định dạng: vĩ_độ,kinh_độ";
        } else {
            // Nếu latlng được nhập trực tiếp, ưu tiên sử dụng giá trị này
            $latlng = $latlngInput;
            list($lat, $lng) = explode(',', $latlng);
            $lat = trim($lat);
            $lng = trim($lng);
        }
    }

    // Kiểm tra nếu có tọa độ thì phải nằm trong thành phố Vinh
    if (!empty($lat) && !empty($lng)) {
        $vinhLat = 18.6667;
        $vinhLng = 105.6667;
        require_once('../utils/haversine.php');
        $distance = haversine($lat, $lng, $vinhLat, $vinhLng);
        if ($distance > 15) {
            $errors[] = "Vị trí bạn chọn nằm ngoài phạm vi thành phố Vinh (khoảng cách {$distance} km). Hệ thống chỉ hỗ trợ đăng tin phòng trọ trong thành phố Vinh. Vui lòng chọn lại vị trí!";
        }
    } else {
        $errors[] = "Vui lòng chỉ định vị trí phòng trọ trên bản đồ.";
    }

    // Xử lý upload ảnh banner
    $banner_image = isset($room['images']) ? $room['images'] : ''; // Giữ ảnh cũ nếu có
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == 0 && $_FILES['banner_image']['size'] > 0) {
        // Xóa ảnh banner cũ
        if (!empty($room['images']) && file_exists(PROJECT_ROOT . '/' . $room['images'])) {
            unlink(PROJECT_ROOT . '/' . $room['images']);
        }

        $upload_dir = PROJECT_ROOT . '/uploads/banner/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_name = time() . '_' . $_FILES['banner_image']['name'];
        $target_file = $upload_dir . $file_name;

        if (isset($_GET['debug']) && $_GET['debug'] == 1) {
            echo "Banner upload: Target file path: " . $target_file . "<br>";
        }

        if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $target_file)) {
            $banner_image = 'uploads/banner/' . $file_name;

            if (isset($_GET['debug']) && $_GET['debug'] == 1) {
                echo "Banner upload: Success. Path saved to DB: " . $banner_image . "<br>";
            }
        } else {
            $errors[] = "Không thể upload ảnh banner mới.";

            if (isset($_GET['debug']) && $_GET['debug'] == 1) {
                echo "Banner upload: Failed. Error code: " . $_FILES['banner_image']['error'] . "<br>";
            }
        }
    }

    // Xử lý upload nhiều hình ảnh bổ sung - sẽ được lưu vào bảng motel_images
    $additional_images = [];

    // Thêm ảnh mới
    if (isset($_FILES['additional_images']) && isset($_FILES['additional_images']['name'])) {
        $upload_dir = PROJECT_ROOT . '/uploads/rooms/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_count = count($_FILES['additional_images']['name']);

        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['additional_images']['error'][$i] == 0 && $_FILES['additional_images']['size'][$i] > 0) {
                $file_name = time() . '_' . $i . '_' . $_FILES['additional_images']['name'][$i];
                $target_file = $upload_dir . $file_name;
                if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$i], $target_file)) {
                    $additional_images[] = 'uploads/rooms/' . $file_name;
                }
            }
        }
    }

    // Nếu không có lỗi, thực hiện cập nhật
    if (empty($errors)) {
        try {
            mysqli_begin_transaction($conn);

            // Cập nhật thông tin phòng trọ
            $query = "UPDATE motel SET 
                        title = '$title',
                        description = '$description',
                        price = $price,
                        area = $area,
                        address = '$address',
                        district_id = $district_id,
                        category_id = $category_id,
                        latlng = '$latlng',
                        phone = '$phone',
                        images = '$banner_image',
                        utilities = '$utilities',
                        default_deposit = $default_deposit,
                        isExist = $isExist
                      WHERE id = $room_id AND user_id = $user_id";

            if (mysqli_query($conn, $query)) {
                // Xử lý ảnh bổ sung - lưu vào bảng motel_images
                // Xóa ảnh đã bị đánh dấu để xóa
                if (isset($_POST['deleted_images']) && is_array($_POST['deleted_images'])) {
                    foreach ($_POST['deleted_images'] as $image_id) {
                        $id = (int)$image_id;
                        if ($id > 0) {
                            // Lấy đường dẫn ảnh trước khi xóa
                            $img_query = "SELECT image_path FROM motel_images WHERE id = $id AND motel_id = $room_id";
                            $img_result = mysqli_query($conn, $img_query);
                            if ($img_row = mysqli_fetch_assoc($img_result)) {
                                // Xóa file ảnh
                                if (file_exists(PROJECT_ROOT . '/' . $img_row['image_path'])) {
                                    unlink(PROJECT_ROOT . '/' . $img_row['image_path']);
                                }
                            }

                            // Xóa bản ghi trong database
                            mysqli_query($conn, "DELETE FROM motel_images WHERE id = $id AND motel_id = $room_id");
                        }
                    }
                }

                // Thêm ảnh mới vào bảng motel_images
                if (!empty($additional_images)) {
                    // Lấy display_order lớn nhất hiện tại
                    $max_order_query = "SELECT MAX(display_order) as max_order FROM motel_images WHERE motel_id = $room_id";
                    $max_order_result = mysqli_query($conn, $max_order_query);
                    $max_order = 0;
                    if ($order_row = mysqli_fetch_assoc($max_order_result)) {
                        $max_order = (int)$order_row['max_order'] + 1;
                    }

                    foreach ($additional_images as $image_path) {
                        $image_path = mysqli_real_escape_string($conn, $image_path);
                        $insert_image = "INSERT INTO motel_images (motel_id, image_path, display_order) 
                                        VALUES ($room_id, '$image_path', $max_order)";
                        mysqli_query($conn, $insert_image);
                        $max_order++;
                    }
                }

                mysqli_commit($conn);
                $success_message = "Cập nhật thông tin phòng trọ thành công!";

                // Reload room data
                $room_result = mysqli_query($conn, $check_query);
                $room = mysqli_fetch_assoc($room_result);

                // Parse latlng again
                if (!empty($room['latlng'])) {
                    $coords = explode(',', $room['latlng']);
                    if (count($coords) == 2) {
                        $lat = trim($coords[0]);
                        $lng = trim($coords[1]);
                    }
                }
            } else {
                mysqli_rollback($conn);
                $errors[] = "Có lỗi xảy ra khi cập nhật phòng trọ: " . mysqli_error($conn);
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = "Có lỗi xảy ra khi cập nhật phòng trọ: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa phòng trọ - Phòng trọ sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <!-- Quill CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

    <style>
        .form-section {
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #4e73df;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .section-title {
            color: #4e73df;
            font-weight: 600;
            margin-bottom: 20px;
            border-bottom: 1px solid #e3e6f0;
            padding-bottom: 10px;
        }

        #map {
            height: 350px !important;
            border: 2px solid #4e73df;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 1;
        }

        #coordinates_display {
            font-weight: 500;
            color: #2e59d9;
        }

        .map-instruction {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.8);
            padding: 8px 12px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            font-size: 13px;
            max-width: 250px;
        }

        .map-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 350px;
            background-color: #f8f9fa;
            border: 2px solid #4e73df;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .map-loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #e3e6f0;
            border-top: 5px solid #4e73df;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .leaflet-popup-content {
            margin: 8px 12px;
            line-height: 1.4;
        }

        .leaflet-popup-content-wrapper {
            background: white;
            color: #333;
            border-radius: 8px;
            box-shadow: 0 3px 14px rgba(0, 0, 0, 0.4);
        }

        .image-preview img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .existing-image {
            position: relative;
            display: inline-block;
            margin: 5px;
        }

        .delete-image-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
            font-size: 12px;
        }

        .delete-image-btn:hover {
            background: #c82333;
        }
    </style>
</head>

<body class="home-body">
    <?php include dirname(__DIR__) . '/components/header.php' ?>

    <main class="py-5 mt-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h3 class="mb-0"><i class="fas fa-edit me-2"></i>Chỉnh sửa phòng trọ</h3>
                        </div>
                        <div class="card-body">

                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($success_message)): ?>
                                <div class="alert alert-success">
                                    <?php echo $success_message; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <!-- Thông tin cơ bản -->
                                <div class="form-section">
                                    <h4 class="section-title"><i class="fas fa-info-circle me-2"></i>Thông tin cơ bản</h4>

                                    <div class="mb-3">
                                        <label for="title" class="form-label">Tiêu đề phòng trọ <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($room['title']); ?>" required>
                                        <div class="invalid-feedback">Vui lòng nhập tiêu đề phòng trọ.</div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="category_id" class="form-label">Loại phòng <span class="text-danger">*</span></label>
                                            <select class="form-select" id="category_id" name="category_id" required>
                                                <option value="">-- Chọn loại phòng --</option>
                                                <?php mysqli_data_seek($categories, 0); ?>
                                                <?php while ($category = mysqli_fetch_assoc($categories)): ?>
                                                    <option value="<?php echo $category['id']; ?>" <?php echo ($room['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                        <?php echo $category['name']; ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                            <div class="invalid-feedback">Vui lòng chọn loại phòng.</div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">Số điện thoại liên hệ <span class="text-danger">*</span></label>
                                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($room['phone']); ?>" required>
                                            <div class="invalid-feedback">Vui lòng nhập số điện thoại hợp lệ.</div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="price" class="form-label">Giá thuê (VNĐ/tháng) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="price" name="price" value="<?php echo $room['price']; ?>" min="1" required>
                                            <div class="invalid-feedback">Vui lòng nhập giá thuê hợp lệ.</div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="area" class="form-label">Diện tích (m²) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="area" name="area" value="<?php echo $room['area']; ?>" min="1" required>
                                            <div class="invalid-feedback">Vui lòng nhập diện tích hợp lệ.</div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="default_deposit" class="form-label">Tiền đặt cọc (VNĐ)</label>
                                            <input type="number" class="form-control" id="default_deposit" name="default_deposit" value="<?php echo $room['default_deposit']; ?>" min="0">
                                            <div class="form-text">Để trống nếu không yêu cầu đặt cọc</div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="isExist" class="form-label">Tình trạng phòng</label>
                                        <select class="form-select" id="isExist" name="isExist">
                                            <option value="1" <?php echo ($room['isExist'] == 1) ? 'selected' : ''; ?>>Còn trống</option>
                                            <option value="0" <?php echo ($room['isExist'] == 0) ? 'selected' : ''; ?>>Đã có người thuê</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Vị trí và địa chỉ -->
                                <div class="form-section">
                                    <h4 class="section-title"><i class="fas fa-map-marker-alt me-2"></i>Vị trí và địa chỉ</h4>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="district_id" class="form-label">Khu vực <span class="text-danger">*</span></label>
                                            <select class="form-select" id="district_id" name="district_id" required>
                                                <option value="">-- Chọn khu vực --</option>
                                                <?php mysqli_data_seek($districts, 0); ?>
                                                <?php while ($district = mysqli_fetch_assoc($districts)): ?>
                                                    <option value="<?php echo $district['id']; ?>" <?php echo ($room['district_id'] == $district['id']) ? 'selected' : ''; ?>>
                                                        <?php echo $district['name']; ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                            <div class="invalid-feedback">Vui lòng chọn khu vực.</div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="address_detail" class="form-label">Địa chỉ chi tiết <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="address_detail" name="address_detail" value="<?php echo isset($room['address_detail']) ? htmlspecialchars($room['address_detail']) : ''; ?>" placeholder="Số nhà, tên đường..." required>
                                            <div class="invalid-feedback">Vui lòng nhập địa chỉ chi tiết.</div>
                                        </div>
                                    </div>

                                    <!-- Tọa độ -->
                                    <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <label for="coordinates_display" class="form-label">Tọa độ hiện tại</label>
                                            <input type="text" class="form-control" id="coordinates_display" readonly value="<?php echo !empty($lat) && !empty($lng) ? "Vĩ độ: $lat, Kinh độ: $lng" : 'Chưa có tọa độ'; ?>">
                                        </div>
                                        <div class="col-md-4 mb-3 d-flex align-items-end">
                                            <button type="button" class="btn btn-outline-primary me-2" id="refresh_coordinates">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-info me-2" id="get_ip_location">
                                                <i class="fas fa-location-arrow me-1"></i> IP
                                            </button>
                                            <button type="button" class="btn btn-outline-success" id="get_browser_location">
                                                <i class="fas fa-crosshairs me-1"></i> GPS
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="latlng" class="form-label">Tọa độ thủ công</label>
                                        <input type="text" class="form-control" id="latlng" name="latlng" value="<?php echo $room['latlng']; ?>" placeholder="vĩ_độ,kinh_độ (ví dụ: 18.6667,105.6667)">
                                        <div class="form-text">Nhập tọa độ theo định dạng: vĩ_độ,kinh_độ</div>
                                    </div>

                                    <!-- Hidden inputs for coordinates -->
                                    <input type="hidden" id="lat" name="lat" value="<?php echo $lat; ?>">
                                    <input type="hidden" id="lng" name="lng" value="<?php echo $lng; ?>">

                                    <!-- Map -->
                                    <div class="mb-3">
                                        <label class="form-label">Bản đồ vị trí</label>
                                        <div id="location_error" style="display: none;"></div>
                                        <div id="map-loading" class="map-loading">
                                            <div class="text-center">
                                                <div class="map-loading-spinner"></div>
                                                <p class="mt-2">Đang tải bản đồ...</p>
                                            </div>
                                        </div>
                                        <div id="map" style="display: none;"></div>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Click trên bản đồ hoặc kéo thả marker để chọn vị trí chính xác
                                        </div>
                                    </div>
                                </div>

                                <!-- Mô tả -->
                                <div class="form-section">
                                    <h4 class="section-title"><i class="fas fa-file-alt me-2"></i>Mô tả chi tiết</h4>

                                    <div class="mb-3">
                                        <label for="description" class="form-label">Mô tả phòng trọ <span class="text-danger">*</span></label>
                                        <div id="editor-container" style="height: 200px;"></div>
                                        <textarea id="description" name="description" style="display: none;" required><?php echo htmlspecialchars($room['description']); ?></textarea>
                                        <div class="invalid-feedback">Vui lòng nhập mô tả chi tiết.</div>
                                    </div>
                                </div>

                                <!-- Tiện ích -->
                                <div class="form-section">
                                    <h4 class="section-title"><i class="fas fa-list-check me-2"></i>Tiện ích</h4>

                                    <?php
                                    $utilities_list = [
                                        'Điều hòa',
                                        'Tủ lạnh',
                                        'Máy giặt',
                                        'Nóng lạnh',
                                        'WiFi',
                                        'Giường',
                                        'Tủ quần áo',
                                        'Bàn học',
                                        'Toilet riêng',
                                        'Ban công',
                                        'Gửi xe',
                                        'An ninh 24/7',
                                        'Gần trường học',
                                        'Gần chợ',
                                        'Gần bệnh viện'
                                    ];
                                    $current_utilities = !empty($room['utilities']) ? explode(', ', $room['utilities']) : [];
                                    ?>

                                    <div class="row">
                                        <?php foreach ($utilities_list as $utility): ?>
                                            <div class="col-md-4 col-sm-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="utilities[]" value="<?php echo $utility; ?>" id="utility_<?php echo str_replace(' ', '_', $utility); ?>" <?php echo in_array($utility, $current_utilities) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="utility_<?php echo str_replace(' ', '_', $utility); ?>">
                                                        <?php echo $utility; ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Hình ảnh -->
                                <div class="form-section">
                                    <h4 class="section-title"><i class="fas fa-images me-2"></i>Hình ảnh</h4>

                                    <!-- Ảnh banner -->
                                    <div class="mb-4">
                                        <label for="banner_image" class="form-label">Ảnh đại diện</label>
                                        <input type="file" class="form-control" id="banner_image" name="banner_image" accept="image/*">
                                        <div class="form-text">Chọn ảnh mới để thay thế ảnh đại diện hiện tại</div>

                                        <?php if (isset($room['images']) && !empty($room['images'])): ?>
                                            <div class="mt-3">
                                                <p><strong>Ảnh đại diện hiện tại:</strong></p>
                                                <?php if (file_exists(PROJECT_ROOT . '/' . $room['images'])): ?>
                                                    <img src="../<?php echo $room['images']; ?>" alt="Banner" class="img-fluid" style="max-height: 200px; border-radius: 8px;">
                                                <?php else: ?>
                                                    <div class="alert alert-warning">
                                                        Ảnh không tồn tại tại đường dẫn: <?php echo $room['images']; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="mt-3">
                                                <p><strong>Chưa có ảnh đại diện</strong></p>
                                            </div>
                                        <?php endif; ?>

                                        <div id="banner_preview" class="mt-3"></div>
                                    </div>

                                    <!-- Ảnh bổ sung -->
                                    <div class="mb-3">
                                        <label for="additional_images" class="form-label">Thêm ảnh mới</label>
                                        <input type="file" class="form-control" id="additional_images" name="additional_images[]" accept="image/*" multiple>
                                        <div class="form-text">Chọn nhiều ảnh để bổ sung thêm (tối đa 10 ảnh)</div>

                                        <!-- Ảnh hiện tại từ bảng motel_images -->
                                        <?php
                                        // Lấy danh sách ảnh bổ sung từ bảng motel_images
                                        $images_query = "SELECT * FROM motel_images WHERE motel_id = $room_id ORDER BY display_order";
                                        $images_result = mysqli_query($conn, $images_query);

                                        if (mysqli_num_rows($images_result) > 0):
                                        ?>
                                            <div class="mt-3">
                                                <p><strong>Ảnh bổ sung hiện tại:</strong></p>
                                                <div class="row" id="current-images">
                                                    <?php
                                                    while ($image = mysqli_fetch_assoc($images_result)):
                                                        if (!empty($image['image_path']) && file_exists(PROJECT_ROOT . '/' . $image['image_path'])):
                                                    ?>
                                                            <div class="col-md-3 mb-3 existing-image" data-image="<?php echo $image['id']; ?>">
                                                                <div class="position-relative">
                                                                    <img src="../<?php echo $image['image_path']; ?>" alt="Room image" class="img-fluid rounded" style="height: 120px; object-fit: cover; width: 100%;">
                                                                    <button type="button" class="delete-image-btn" onclick="markImageForDeletion('<?php echo $image['id']; ?>', this)">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                    <?php
                                                        endif;
                                                    endwhile;
                                                    ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <div id="additional_preview" class="row mt-3"></div>
                                    </div>
                                </div>

                                <!-- Submit buttons -->
                                <div class="text-center">
                                    <a href="my_posted_rooms.php" class="btn btn-secondary me-2">
                                        <i class="fas fa-arrow-left me-1"></i> Quay lại
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Cập nhật phòng trọ
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include dirname(__DIR__) . '/components/footer.php' ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/admin/js/main.js"></script>
    <!-- Quill JS -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        $(document).ready(function() {
            // Khởi tạo Quill editor
            var quill = new Quill('#editor-container', {
                theme: 'snow',
                placeholder: 'Mô tả chi tiết về phòng trọ...',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline'],
                        [{
                            'header': 1
                        }, {
                            'header': 2
                        }],
                        [{
                            'list': 'ordered'
                        }, {
                            'list': 'bullet'
                        }],
                        ['link', 'image'],
                        ['clean']
                    ]
                }
            });

            // Load existing content into editor
            quill.root.innerHTML = $('#description').val();

            // Update textarea when form is submitted
            $('form').on('submit', function() {
                $('#description').val(quill.root.innerHTML);
            });

            // Haversine formula to calculate distance between two coordinates
            function haversineDistance(lat1, lon1, lat2, lon2) {
                const R = 6371; // Earth radius in kilometers
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLon = (lon2 - lon1) * Math.PI / 180;
                const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                    Math.sin(dLon / 2) * Math.sin(dLon / 2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return R * c; // Distance in km
            }

            // Function to update address and district from coordinates
            function updateAddressFromCoordinates(lat, lng, marker = null, showLoading = true) {
                // Check if we recently geocoded these coordinates to avoid duplicate calls
                const coordKey = `${lat.toFixed(6)},${lng.toFixed(6)}`;
                const lastGeocode = sessionStorage.getItem('lastGeocode');
                const lastGeocodeData = lastGeocode ? JSON.parse(lastGeocode) : null;

                // If we geocoded these exact coordinates in the last 5 minutes, skip
                if (lastGeocodeData &&
                    lastGeocodeData.coords === coordKey &&
                    (Date.now() - lastGeocodeData.timestamp) < 300000) { // 5 minutes
                    console.log('Using cached geocoding result for', coordKey);
                    return Promise.resolve(lastGeocodeData.response);
                }

                if (showLoading) {
                    $('#address_detail').addClass('loading');
                    $('#address_detail').attr('placeholder', 'Đang cập nhật địa chỉ...');
                }

                return $.ajax({
                    url: '../api/location/reverse-here.php',
                    method: 'POST',
                    data: {
                        lat: lat,
                        lng: lng
                    },
                    dataType: 'json',
                    success: function(response) {
                        // Remove loading state
                        if (showLoading) {
                            $('#address_detail').removeClass('loading');
                            $('#address_detail').attr('placeholder', 'Nhập địa chỉ cụ thể');
                        }

                        if (response.success && response.raw) {
                            // Extract address components from HERE API response
                            const addressData = response.raw.address;

                            // Check if we have any location information to work with
                            const hasLocationData = addressData.district || addressData.cityDistrict ||
                                addressData.subdistrict || addressData.city ||
                                addressData.county || addressData.state;

                            // Update address_detail with street address
                            let streetAddress = '';
                            if (addressData.houseNumber) {
                                streetAddress += addressData.houseNumber + ' ';
                            }
                            if (addressData.street) {
                                streetAddress += addressData.street;
                            } else if (addressData.label) {
                                // Use the first part of label if no street is available
                                const labelParts = addressData.label.split(',');
                                streetAddress = labelParts[0].trim();
                            }

                            if (streetAddress) {
                                $('#address_detail').val(streetAddress);
                            }

                            // Update district selection based on district or city district
                            let districtName = addressData.district || addressData.cityDistrict || addressData.subdistrict;

                            // If no district found but we have location data, try to extract from label
                            if (!districtName && hasLocationData && addressData.label) {
                                const labelParts = addressData.label.split(',').map(part => part.trim());
                                // Look for potential district names in label parts
                                for (let part of labelParts) {
                                    if (part.match(/^(Phường|Xã|Thị trấn|Quận|Huyện)\s+/iu)) {
                                        districtName = part;
                                        console.log('Extracted district from label:', districtName);
                                        break;
                                    }
                                }
                            }

                            if (districtName) {
                                console.log('Original district name:', districtName);

                                // Clean district name (remove "Phường", "Xã", etc.)
                                const cleanDistrictName = districtName.replace(/^(Phường|Xã|Thị trấn|Quận|Huyện)\s+/iu, '').trim();
                                console.log('Cleaned district name:', cleanDistrictName);

                                // Normalize text for better matching (remove accents, lowercase)
                                function normalizeText(text) {
                                    return text.toLowerCase()
                                        .normalize('NFD')
                                        .replace(/[\u0300-\u036f]/g, '') // Remove accents
                                        .replace(/[đĐ]/g, 'd') // Replace đ with d
                                        .trim();
                                }

                                const normalizedDistrictName = normalizeText(cleanDistrictName);
                                const normalizedOriginalName = normalizeText(districtName);

                                // Try to match with dropdown options
                                let matchFound = false;
                                let bestMatch = null;
                                let bestScore = 0;

                                $('#district_id option').each(function() {
                                    const optionText = $(this).text().trim();
                                    const optionValue = $(this).val();

                                    // Skip empty option
                                    if (!optionValue || optionText === '-- Chọn khu vực --') {
                                        return true; // Continue to next iteration
                                    }

                                    const normalizedOption = normalizeText(optionText);

                                    console.log('Comparing:', {
                                        option: optionText,
                                        normalized: normalizedOption,
                                        district: normalizedDistrictName,
                                        original: normalizedOriginalName
                                    });

                                    let score = 0;

                                    // Exact match (highest priority)
                                    if (normalizedOption === normalizedDistrictName ||
                                        normalizedOption === normalizedOriginalName) {
                                        score = 100;
                                    }
                                    // Option contains district name
                                    else if (normalizedOption.includes(normalizedDistrictName) && normalizedDistrictName.length > 2) {
                                        score = 90;
                                    }
                                    // District name contains option
                                    else if (normalizedDistrictName.includes(normalizedOption) && normalizedOption.length > 2) {
                                        score = 85;
                                    }
                                    // Word-by-word matching for compound names
                                    else {
                                        const districtWords = normalizedDistrictName.split(/\s+/).filter(w => w.length > 2);
                                        const optionWords = normalizedOption.split(/\s+/).filter(w => w.length > 2);

                                        let matchingWords = 0;
                                        districtWords.forEach(dWord => {
                                            optionWords.forEach(oWord => {
                                                if (dWord === oWord || dWord.includes(oWord) || oWord.includes(dWord)) {
                                                    matchingWords++;
                                                }
                                            });
                                        });

                                        if (matchingWords > 0 && districtWords.length > 0) {
                                            score = Math.round((matchingWords / districtWords.length) * 70);
                                        }
                                    }

                                    console.log('Match score for', optionText, ':', score);

                                    if (score > bestScore && score >= 70) {
                                        bestScore = score;
                                        bestMatch = {
                                            value: optionValue,
                                            text: optionText,
                                            score: score
                                        };
                                    }
                                });

                                if (bestMatch) {
                                    console.log('Best match found:', bestMatch.text, 'with score:', bestMatch.score);
                                    $('#district_id').val(bestMatch.value).change();
                                    matchFound = true;
                                }

                                // If no match found, try with city name or broader area
                                if (!matchFound && (addressData.city || addressData.county)) {
                                    const cityName = (addressData.city || addressData.county).replace(/^(Thành phố|Tỉnh)\s+/iu, '').trim();
                                    const normalizedCityName = normalizeText(cityName);
                                    console.log('Trying city match:', cityName, 'normalized:', normalizedCityName);

                                    let cityBestMatch = null;
                                    let cityBestScore = 0;

                                    $('#district_id option').each(function() {
                                        const optionText = $(this).text().trim();
                                        const optionValue = $(this).val();

                                        if (!optionValue || optionText === '-- Chọn khu vực --') {
                                            return true;
                                        }

                                        const normalizedOption = normalizeText(optionText);
                                        let score = 0;

                                        if (normalizedOption.includes(normalizedCityName) && normalizedCityName.length > 2) {
                                            score = 60;
                                        } else if (normalizedCityName.includes(normalizedOption) && normalizedOption.length > 2) {
                                            score = 55;
                                        }

                                        if (score > cityBestScore && score >= 50) {
                                            cityBestScore = score;
                                            cityBestMatch = {
                                                value: optionValue,
                                                text: optionText,
                                                score: score
                                            };
                                        }
                                    });

                                    if (cityBestMatch) {
                                        console.log('City match found:', cityBestMatch.text, 'with score:', cityBestMatch.score);
                                        $('#district_id').val(cityBestMatch.value).change();
                                        matchFound = true;
                                    }
                                }

                                if (!matchFound) {
                                    console.log('No district match found for:', districtName);
                                    console.log('Available options:', $('#district_id option').map(function() {
                                        return $(this).text();
                                    }).get());
                                }
                            }

                            // Update marker popup with new address if marker is provided
                            if (marker) {
                                const displayAddress = response.address || streetAddress || "Vị trí đã chọn";
                                marker.setPopupContent("<b>Vị trí phòng trọ</b><br>" + displayAddress);
                            }

                            console.log('Address updated:', {
                                street: streetAddress,
                                district: districtName,
                                full: response.address,
                                hasMatch: matchFound
                            });

                            // Show notification to user about auto-update
                            if (streetAddress || matchFound) {
                                let message = 'Đã tự động cập nhật: ';
                                let updates = [];

                                if (streetAddress) {
                                    updates.push('địa chỉ chi tiết');
                                }
                                if (matchFound) {
                                    updates.push('khu vực');
                                }

                                message += updates.join(' và ');

                                // Show a temporary notification
                                if ($('#auto_update_notification').length === 0) {
                                    $('<div id="auto_update_notification" class="alert alert-info alert-dismissible fade show" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 300px;">' +
                                        '<i class="fas fa-info-circle me-2"></i>' + message +
                                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                                        '</div>').appendTo('body');

                                    // Auto hide after 4 seconds
                                    setTimeout(function() {
                                        $('#auto_update_notification').fadeOut(function() {
                                            $(this).remove();
                                        });
                                    }, 4000);
                                }
                            }

                            // Cache successful geocoding result
                            const coordKey = `${lat.toFixed(6)},${lng.toFixed(6)}`;
                            sessionStorage.setItem('lastGeocode', JSON.stringify({
                                coords: coordKey,
                                timestamp: Date.now(),
                                response: response
                            }));
                        }
                    },
                    error: function() {
                        // Remove loading state on error
                        if (showLoading) {
                            $('#address_detail').removeClass('loading');
                            $('#address_detail').attr('placeholder', 'Nhập địa chỉ cụ thể');
                        }
                        console.log('Không thể cập nhật địa chỉ từ tọa độ');
                    }
                });
            }

            // Function to create a marker
            function createMarker(lat, lng, title) {
                let newMarker = L.marker([lat, lng], {
                    draggable: true
                }).addTo(map);

                newMarker.bindPopup("<b>Vị trí phòng trọ</b><br>" + title);

                // Update coordinates when marker is dragged
                newMarker.on('dragend', function(e) {
                    let position = newMarker.getLatLng();
                    $('#lat').val(position.lat);
                    $('#lng').val(position.lng);
                    $('#latlng').val(position.lat + ',' + position.lng);
                    $('#coordinates_display').val('Vĩ độ: ' + position.lat + ', Kinh độ: ' + position.lng);

                    // Update address using reverse geocoding
                    updateAddressFromCoordinates(position.lat, position.lng, newMarker, true);

                    // Check distance
                    const vinhCenter = [18.6667, 105.6667];
                    const distance = haversineDistance(position.lat, position.lng, vinhCenter[0], vinhCenter[1]);
                    if (distance > 15) {
                        $('#location_error').html('<div class="alert alert-warning">Vị trí bạn chọn nằm ngoài phạm vi thành phố Vinh (khoảng cách ' +
                            distance.toFixed(2) + ' km). Hệ thống chỉ hỗ trợ đăng tin phòng trọ trong thành phố Vinh.</div>').show();
                    } else {
                        $('#location_error').hide();
                    }
                });

                return newMarker;
            }

            // Function to get coordinates from address
            function getCoordinates(showLoading = true) {
                var district = $('#district_id option:selected').text();
                var addressDetail = $('#address_detail').val();

                if (district && district !== '-- Chọn khu vực --' && addressDetail) {
                    if (showLoading) {
                        $('#coordinates_display').val('Đang tìm tọa độ...');
                        $('#refresh_coordinates').html('<i class="fas fa-spinner fa-spin"></i>');
                    }

                    var fullAddress = addressDetail + ', ' + district + ', Thành phố Vinh, Nghệ An';
                    $.ajax({
                        url: '../api/location/get_coordinates.php',
                        method: 'POST',
                        data: {
                            address: fullAddress
                        },
                        dataType: 'json',
                        success: function(response) {
                            $('#refresh_coordinates').html('<i class="fas fa-sync-alt"></i>');
                            $('#map').show();

                            if (response.success && response.coordinates && response.coordinates.lat && response.coordinates.lng) {
                                var lat = response.coordinates.lat;
                                var lng = response.coordinates.lng;

                                $('#coordinates_display').val('Vĩ độ: ' + lat + ', Kinh độ: ' + lng);
                                $('#lat').val(lat);
                                $('#lng').val(lng);
                                $('#latlng').val(lat + ',' + lng);

                                if (!map) {
                                    initMap(lat, lng);
                                } else {
                                    map.setView([lat, lng], 16);
                                    if (marker) {
                                        marker.setLatLng([lat, lng]);
                                    } else {
                                        marker = createMarker(lat, lng, fullAddress);
                                    }
                                }
                            } else {
                                $('#coordinates_display').val('Không tìm thấy tọa độ cho địa chỉ này');
                                $('#location_error').html('<div class="alert alert-warning">Không thể tìm thấy tọa độ chính xác cho địa chỉ này.</div>').show();
                                if (!map) {
                                    initMap(18.6667, 105.6667, 13);
                                }
                            }
                        },
                        error: function() {
                            $('#refresh_coordinates').html('<i class="fas fa-sync-alt"></i>');
                            $('#coordinates_display').val('Lỗi khi tìm tọa độ');
                            $('#location_error').html('<div class="alert alert-danger">Có lỗi xảy ra khi tìm tọa độ.</div>').show();
                        }
                    });
                } else {
                    $('#location_error').html('<div class="alert alert-info">Vui lòng nhập địa chỉ chi tiết và chọn khu vực trước khi tìm tọa độ.</div>').show();
                    setTimeout(function() {
                        $('#location_error').fadeOut();
                    }, 3000);
                }
            }

            // Debounce function
            function debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }

            // Variables for map and marker
            let map = null;
            let marker = null;

            // Initialize map with coordinates
            function initMap(lat, lng, zoom = 16) {
                // Remove loading indicator
                $('#map-loading').hide();
                $('#map').show();

                // Validate coordinates
                if (isNaN(lat) || isNaN(lng) || !lat || !lng) {
                    lat = 18.6667;
                    lng = 105.6667;
                    zoom = 13;
                }

                // If map exists, just update it
                if (map) {
                    map.setView([lat, lng], zoom);
                    if (marker) {
                        marker.setLatLng([lat, lng]);
                    } else {
                        marker = createMarker(lat, lng, 'Vị trí phòng trọ');
                    }
                    return;
                }

                // Create map instance
                map = L.map('map').setView([lat, lng], zoom);

                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                // Add a circle showing the 15km boundary around Vinh City center
                var vinhCenter = [18.6667, 105.6667];
                L.circle(vinhCenter, {
                    color: 'blue',
                    fillColor: '#30f',
                    fillOpacity: 0.1,
                    radius: 15000
                }).addTo(map);

                L.marker(vinhCenter).bindPopup('Trung tâm thành phố Vinh').addTo(map);

                // Create marker if coordinates exist
                if (lat && lng) {
                    marker = createMarker(lat, lng, 'Vị trí phòng trọ');
                }

                // Map click event
                map.on('click', function(e) {
                    if (marker) {
                        marker.setLatLng(e.latlng);
                    } else {
                        marker = createMarker(e.latlng.lat, e.latlng.lng, 'Vị trí phòng trọ');
                    }

                    $('#lat').val(e.latlng.lat);
                    $('#lng').val(e.latlng.lng);
                    $('#latlng').val(e.latlng.lat + ',' + e.latlng.lng);
                    $('#coordinates_display').val('Vĩ độ: ' + e.latlng.lat + ', Kinh độ: ' + e.latlng.lng);

                    // Update address using reverse geocoding
                    updateAddressFromCoordinates(e.latlng.lat, e.latlng.lng, marker, false);

                    const vinhCenter = [18.6667, 105.6667];
                    const distance = haversineDistance(e.latlng.lat, e.latlng.lng, vinhCenter[0], vinhCenter[1]);
                    if (distance > 15) {
                        $('#location_error').html('<div class="alert alert-warning">Vị trí bạn chọn nằm ngoài phạm vi thành phố Vinh (khoảng cách ' +
                            distance.toFixed(2) + ' km). Hệ thống chỉ hỗ trợ đăng tin phòng trọ trong thành phố Vinh.</div>').show();
                    } else {
                        $('#location_error').hide();
                    }
                });

                setTimeout(function() {
                    map.invalidateSize();
                }, 500);
            }

            // Initialize map with existing coordinates or default location
            setTimeout(function() {
                const currentLat = $('#lat').val();
                const currentLng = $('#lng').val();

                if (currentLat && currentLng) {
                    initMap(parseFloat(currentLat), parseFloat(currentLng));
                } else {
                    initMap(18.6667, 105.6667, 13);
                }
            }, 1000);

            // Form validation
            const forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    const latValue = $('#lat').val();
                    const lngValue = $('#lng').val();

                    if (!latValue || !lngValue) {
                        event.preventDefault();
                        event.stopPropagation();
                        $('#location_error').html('<div class="alert alert-danger">Vui lòng chọn vị trí trên bản đồ.</div>').show();
                    } else {
                        const vinhCenter = [18.6667, 105.6667];
                        const distance = haversineDistance(parseFloat(latValue), parseFloat(lngValue), vinhCenter[0], vinhCenter[1]);
                        if (distance > 15) {
                            event.preventDefault();
                            event.stopPropagation();
                            $('#location_error').html('<div class="alert alert-danger">Vị trí nằm ngoài phạm vi thành phố Vinh.</div>').show();
                        }
                    }

                    form.classList.add('was-validated');
                }, false);
            });

            // Debounce function for address changes
            const debouncedGetCoordinates = debounce(getCoordinates, 1000);

            // Auto-update coordinates when district or address changes
            $('#district_id, #address_detail').on('change keyup blur', function() {
                debouncedGetCoordinates();
            });

            // Manual refresh coordinates button
            $('#refresh_coordinates').on('click', function() {
                getCoordinates(true);
            });

            // Browser geolocation
            $('#get_browser_location').on('click', function() {
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang lấy...');

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            $('#get_browser_location').prop('disabled', false).html('<i class="fas fa-crosshairs me-1"></i> GPS');

                            var lat = position.coords.latitude;
                            var lng = position.coords.longitude;

                            $('#lat').val(lat);
                            $('#lng').val(lng);
                            $('#latlng').val(lat + ',' + lng);
                            $('#coordinates_display').val('Vĩ độ: ' + lat + ', Kinh độ: ' + lng);

                            if (!map) {
                                initMap(lat, lng);
                            } else {
                                map.setView([lat, lng], 16);
                                if (marker) {
                                    marker.setLatLng([lat, lng]);
                                } else {
                                    marker = createMarker(lat, lng, 'Vị trí hiện tại của bạn');
                                }
                            }

                            updateAddressFromCoordinates(lat, lng, marker, false);

                            const vinhCenter = [18.6667, 105.6667];
                            const distance = haversineDistance(lat, lng, vinhCenter[0], vinhCenter[1]);
                            if (distance > 15) {
                                $('#location_error').html('<div class="alert alert-warning">Vị trí bạn chọn nằm ngoài phạm vi thành phố Vinh.</div>').show();
                            } else {
                                $('#location_error').hide();
                            }
                        },
                        function(error) {
                            $('#get_browser_location').prop('disabled', false).html('<i class="fas fa-crosshairs me-1"></i> GPS');

                            let errorMsg;
                            switch (error.code) {
                                case error.PERMISSION_DENIED:
                                    errorMsg = 'Bạn đã từ chối quyền truy cập vị trí.';
                                    break;
                                case error.POSITION_UNAVAILABLE:
                                    errorMsg = 'Thông tin vị trí không khả dụng.';
                                    break;
                                case error.TIMEOUT:
                                    errorMsg = 'Đã hết thời gian chờ khi lấy vị trí.';
                                    break;
                                default:
                                    errorMsg = 'Đã xảy ra lỗi không xác định khi lấy vị trí.';
                            }

                            $('#location_error').html('<div class="alert alert-warning">' + errorMsg + '</div>').show();
                        }
                    );
                } else {
                    $('#get_browser_location').prop('disabled', false).html('<i class="fas fa-crosshairs me-1"></i> GPS');
                    $('#location_error').html('<div class="alert alert-warning">Trình duyệt không hỗ trợ Geolocation.</div>').show();
                }
            });

            // IP Location button handler
            $('#get_ip_location').on('click', function() {
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang lấy...');

                $.ajax({
                    url: '../api/maps/get_ip_location.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        $('#get_ip_location').prop('disabled', false).html('<i class="fas fa-location-arrow me-1"></i> IP');

                        if (response.success && response.lat && response.lng) {
                            var lat = response.lat;
                            var lng = response.lng;

                            $('#lat').val(lat);
                            $('#lng').val(lng);
                            $('#latlng').val(lat + ',' + lng);
                            $('#coordinates_display').val('Vĩ độ: ' + lat + ', Kinh độ: ' + lng);

                            if (!map) {
                                initMap(lat, lng);
                            } else {
                                map.setView([lat, lng], 16);
                                if (marker) {
                                    marker.setLatLng([lat, lng]);
                                } else {
                                    marker = createMarker(lat, lng, 'Vị trí của bạn (theo IP)');
                                }
                            }

                            updateAddressFromCoordinates(lat, lng, marker, false);

                            const vinhCenter = [18.6667, 105.6667];
                            const distance = haversineDistance(lat, lng, vinhCenter[0], vinhCenter[1]);
                            if (distance > 15) {
                                $('#location_error').html('<div class="alert alert-warning">Vị trí bạn chọn nằm ngoài phạm vi thành phố Vinh.</div>').show();
                            } else {
                                $('#location_error').hide();
                            }
                        } else {
                            $('#location_error').html('<div class="alert alert-warning">Không thể xác định vị trí từ IP.</div>').show();
                        }
                    },
                    error: function() {
                        $('#get_ip_location').prop('disabled', false).html('<i class="fas fa-location-arrow me-1"></i> IP');
                        $('#location_error').html('<div class="alert alert-danger">Có lỗi xảy ra khi lấy vị trí từ IP.</div>').show();
                    }
                });
            });

            // Image handling
            $('#banner_image').change(function() {
                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#banner_preview').html('<img src="' + e.target.result + '" class="img-fluid rounded">');
                    }
                    reader.readAsDataURL(file);
                }
            });

            $('#additional_images').change(function() {
                var files = this.files;
                var preview = $('#additional_preview');
                preview.empty();

                if (files) {
                    for (var i = 0; i < Math.min(files.length, 10); i++) {
                        (function(file) {
                            var reader = new FileReader();
                            reader.onload = function(e) {
                                var col = $('<div class="col-md-3 mb-3"></div>');
                                col.append('<img src="' + e.target.result + '" class="img-fluid rounded" style="height: 120px; object-fit: cover; width: 100%;">');
                                preview.append(col);
                            }
                            reader.readAsDataURL(file);
                        })(files[i]);
                    }
                }
            });

            // Function to mark image for deletion
            function markImageForDeletion(imageId, button) {
                if (confirm('Bạn có chắc chắn muốn xóa ảnh này?')) {
                    // Add hidden input to track deleted images
                    if ($('input[name="deleted_images[]"][value="' + imageId + '"]').length === 0) {
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'deleted_images[]',
                            value: imageId
                        }).appendTo('form');
                    }

                    // Hide the image container
                    $(button).closest('.existing-image').fadeOut();
                }
            }
        });
    </script>

</body>

</html>