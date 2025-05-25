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
    $category_id = (int)($_POST['category_id'] ?? 0);
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $lat = isset($_POST['lat']) ? mysqli_real_escape_string($conn, $_POST['lat']) : '';
    $lng = isset($_POST['lng']) ? mysqli_real_escape_string($conn, $_POST['lng']) : '';
    $latlng = (!empty($lat) && !empty($lng)) ? $lat . ',' . $lng : '';
    $default_deposit = (int)($_POST['default_deposit'] ?? 0);
    $isExist = isset($_POST['isExist']) ? (int)$_POST['isExist'] : 1; // Mặc định là còn trống (1)

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
    }

    if ($price <= 0) {
        $errors[] = "Vui lòng nhập giá thuê hợp lệ.";
    }

    if ($area <= 0) {
        $errors[] = "Vui lòng nhập diện tích hợp lệ.";
    }

    if (empty($address_detail)) {
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
    $banner_image = '';
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == 0) {
        $upload_dir = PROJECT_ROOT . '/uploads/banner/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_name = time() . '_' . $_FILES['banner_image']['name'];
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $target_file)) {
            $banner_image = 'uploads/banner/' . $file_name;
        } else {
            $errors[] = "Không thể upload ảnh banner.";
        }
    } else {
        $errors[] = "Vui lòng tải lên ảnh banner.";
    }

    // Xử lý upload nhiều hình ảnh
    $additional_images = [];
    if (isset($_FILES['additional_images'])) {
        $upload_dir = PROJECT_ROOT . '/uploads/rooms/';
        // Tạo thư mục nếu chưa tồn tại
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_count = count($_FILES['additional_images']['name']);

        if ($file_count == 0 || empty($_FILES['additional_images']['name'][0])) {
            // Có thể cho phép không có ảnh bổ sung
        } else {
            for ($i = 0; $i < $file_count; $i++) {
                // Kiểm tra nếu file hợp lệ
                if ($_FILES['additional_images']['error'][$i] == 0) {
                    $file_name = time() . '_' . $i . '_' . $_FILES['additional_images']['name'][$i];
                    $target_file = $upload_dir . $file_name;

                    // Di chuyển file tạm vào thư mục đích
                    if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$i], $target_file)) {
                        $additional_images[] = 'uploads/rooms/' . $file_name;
                    } else {
                        $errors[] = "Không thể upload ảnh bổ sung #{$i}.";
                    }
                }
            }
        }
    }

    // Nếu không có lỗi, thực hiện lưu vào CSDL
    if (empty($errors)) {
        try {
            $user_id = $_SESSION['user_id'];
            mysqli_begin_transaction($conn);

            // Thêm phòng trọ vào database
            $query = "INSERT INTO motel (title, description, price, area, address, latlng, phone, 
                      category_id, district_id, utilities, user_id, images, approve, default_deposit, isExist)
                      VALUES ('$title', '$description', $price, $area, '$address', '$latlng', '$phone',
                              $category_id, $district_id, '$utilities', $user_id, '$banner_image', 0, $default_deposit, $isExist)";

            if (mysqli_query($conn, $query)) {
                $motel_id = mysqli_insert_id($conn);

                // Lưu các hình ảnh bổ sung
                foreach ($additional_images as $index => $image_path) {
                    $insert_image = "INSERT INTO motel_images (motel_id, image_path, display_order) 
                                    VALUES ($motel_id, '$image_path', $index)";
                    mysqli_query($conn, $insert_image);
                }

                mysqli_commit($conn);
                $success_message = "Phòng trọ đã được đăng thành công! Vui lòng chờ quản trị viên phê duyệt.";
                // Reset form sau khi submit thành công
                $_POST = [];
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = "Có lỗi xảy ra khi lưu thông tin phòng trọ: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng tin phòng trọ - Phòng trọ sinh viên</title>
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
            pointer-events: none;
        }

        .map-loading {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.7);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .map-loading-spinner {
            font-size: 30px;
            color: #4e73df;
            margin-bottom: 10px;
        }

        .leaflet-popup-content {
            font-size: 14px;
            line-height: 1.4;
        }

        .leaflet-popup-content-wrapper {
            border-left: 3px solid #4e73df;
        }

        .image-preview img {
            max-width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body class="home-body">
    <?php include dirname(__DIR__) . '/components/header.php' ?>

    <main class="py-5 mt-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <!-- Tiêu đề trang -->
                    <h1 class="text-center mb-4">
                        <i class="fas fa-plus-circle text-primary me-2"></i>
                        Đăng tin phòng trọ
                    </h1>
                    <p class="text-center text-muted mb-4">
                        Hãy cung cấp thông tin chi tiết về phòng trọ của bạn để thu hút người thuê.
                        <br>
                        <small>Thông tin của bạn sẽ được duyệt trước khi hiển thị công khai.</small>
                    </p>

                    <!-- Hiển thị thông báo lỗi -->
                    <?php if (!empty($errors)) : ?>
                        <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                            <strong><i class="fas fa-exclamation-circle me-2"></i>Lỗi!</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error) : ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Hiển thị thông báo thành công -->
                    <?php if (!empty($success_message)) : ?>
                        <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Form đăng tin -->
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <!-- Thông tin cơ bản -->
                                <h4 class="mb-4 border-bottom pb-2"><i class="fas fa-info-circle me-2 text-primary"></i>Thông tin cơ bản</h4>

                                <div class="mb-3">
                                    <label for="title" class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title"
                                        placeholder="Nhập tiêu đề phòng trọ" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                                    <div class="form-text">Tiêu đề nên mô tả ngắn gọn và hấp dẫn về phòng trọ.</div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="price" class="form-label">Giá thuê (VNĐ/tháng) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="price" name="price"
                                                placeholder="VD: 1500000" value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" required>
                                            <span class="input-group-text">VNĐ</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="area" class="form-label">Diện tích (m²) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="area" name="area"
                                                placeholder="VD: 20" value="<?php echo isset($_POST['area']) ? htmlspecialchars($_POST['area']) : ''; ?>" required>
                                            <span class="input-group-text">m²</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="editor-container" class="form-label">Mô tả chi tiết <span class="text-danger">*</span></label>
                                    <div id="editor-container"><?php echo isset($_POST['description']) ? $_POST['description'] : ''; ?></div>
                                    <textarea name="description" id="description" style="display: none;"><?php echo isset($_POST['description']) ? $_POST['description'] : ''; ?></textarea>
                                </div>

                                <!-- Thông tin địa chỉ -->
                                <h4 class="mt-5 mb-4 border-bottom pb-2"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Địa chỉ phòng trọ</h4>

                                <div class="mb-3">
                                    <label for="address_detail" class="form-label">Địa chỉ cụ thể <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="address_detail" name="address_detail"
                                        placeholder="Nhập địa chỉ cụ thể" value="<?php echo isset($_POST['address_detail']) ? htmlspecialchars($_POST['address_detail']) : ''; ?>" required>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="district_id" class="form-label">Khu vực <span class="text-danger">*</span></label>
                                        <select class="form-select" id="district_id" name="district_id" required>
                                            <option value="">-- Chọn khu vực --</option>
                                            <?php
                                            mysqli_data_seek($districts, 0); // Reset pointer to beginning
                                            while ($district = $districts->fetch_assoc()) : ?>
                                                <option value="<?php echo $district['id']; ?>" <?php echo (isset($_POST['district_id']) && $_POST['district_id'] == $district['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($district['name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-map-pin me-1 text-primary"></i> Tọa độ địa điểm</label>
                                    <div class="btn-group d-flex mb-3">
                                        <button class="btn btn-outline-primary" type="button" id="get_ip_location" title="Lấy vị trí từ IP">
                                            <i class="fas fa-location-arrow me-1"></i> Lấy vị trí hiện tại từ IP
                                        </button>
                                        <button class="btn btn-outline-info" type="button" id="get_browser_location" title="Lấy vị trí từ trình duyệt">
                                            <i class="fas fa-crosshairs me-1"></i> Lấy vị trí từ trình duyệt
                                        </button>
                                    </div>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text bg-primary text-white"><i class="fas fa-map-marker-alt"></i></span>
                                        <input type="text" class="form-control" id="coordinates_display" placeholder="Tọa độ sẽ hiển thị ở đây sau khi nhập địa chỉ" readonly>
                                        <button class="btn btn-primary" type="button" id="refresh_coordinates" title="Cập nhật tọa độ">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                    <small class="form-text text-muted">Kéo thả điểm trên bản đồ để điều chỉnh vị trí chính xác.</small>
                                    <input type="hidden" id="lat" name="lat">
                                    <input type="hidden" id="lng" name="lng">
                                    <div class="position-relative">
                                        <div id="map" style="width: 100%; margin-top: 10px; height: 300px;">
                                            <div class="map-loading" id="map-loading">
                                                <div class="map-loading-spinner">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </div>
                                                <div>Đang tải bản đồ...</div>
                                            </div>
                                        </div>
                                        <div id="location_error" class="mt-2" style="display:none;"></div>
                                        <div class="map-instruction">
                                            <i class="fas fa-info-circle me-1"></i> Click vào bản đồ để chọn vị trí hoặc kéo marker để điều chỉnh
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="latlng" class="form-label">Tọa độ (vĩ độ, kinh độ) <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="latlng" name="latlng" readonly
                                            placeholder="VD: 18.6763,105.67613" value="<?php echo isset($_POST['latlng']) ? htmlspecialchars($_POST['latlng']) : ''; ?>" required>
                                        <div class="form-text">Giá trị này sẽ tự động cập nhật từ bản đồ</div>
                                    </div>
                                </div>

                                <!-- Thông tin trạng thái và đặt cọc -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="default_deposit" class="form-label">Tiền đặt cọc (VNĐ)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="default_deposit" name="default_deposit"
                                                placeholder="VD: 1000000" value="<?php echo isset($_POST['default_deposit']) ? htmlspecialchars($_POST['default_deposit']) : '0'; ?>">
                                            <span class="input-group-text">VNĐ</span>
                                        </div>
                                        <div class="form-text">Nhập số tiền đặt cọc nếu có yêu cầu (nhập 0 nếu không yêu cầu đặt cọc)</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label d-block">Trạng thái phòng</label>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="isExist" id="isExist_available" value="1"
                                                <?php echo !isset($_POST['isExist']) || $_POST['isExist'] == '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="isExist_available">
                                                <span class="text-success"><i class="fas fa-check-circle me-1"></i> Còn trống</span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="isExist" id="isExist_unavailable" value="0"
                                                <?php echo isset($_POST['isExist']) && $_POST['isExist'] == '0' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="isExist_unavailable">
                                                <span class="text-secondary"><i class="fas fa-times-circle me-1"></i> Đã thuê</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Thông tin phân loại và tiện ích -->
                                <h4 class="mt-5 mb-4 border-bottom pb-2"><i class="fas fa-list-alt me-2 text-primary"></i>Phân loại & Tiện ích</h4>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="category_id" class="form-label">Loại phòng</label>
                                        <select class="form-select" id="category_id" name="category_id">
                                            <option value="">-- Chọn loại phòng --</option>
                                            <?php while ($category = $categories->fetch_assoc()) : ?>
                                                <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Số điện thoại liên hệ <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="phone" name="phone"
                                            placeholder="Nhập số điện thoại của bạn" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label d-block">Tiện ích có sẵn</label>
                                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="utilities[]" value="Wifi" id="wifi" <?php echo (isset($_POST['utilities']) && in_array('Wifi', $_POST['utilities'])) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="wifi">
                                                    <i class="fas fa-wifi me-1 text-primary"></i> Wifi
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="utilities[]" value="Máy giặt" id="washing" <?php echo (isset($_POST['utilities']) && in_array('Máy giặt', $_POST['utilities'])) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="washing">
                                                    <i class="fas fa-tshirt me-1 text-primary"></i> Máy giặt
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="utilities[]" value="Gần trường" id="university" <?php echo (isset($_POST['utilities']) && in_array('Gần trường', $_POST['utilities'])) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="university">
                                                    <i class="fas fa-school me-1 text-primary"></i> Gần trường
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="utilities[]" value="Điều hòa" id="ac" <?php echo (isset($_POST['utilities']) && in_array('Điều hòa', $_POST['utilities'])) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="ac">
                                                    <i class="fas fa-snowflake me-1 text-primary"></i> Điều hòa
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="utilities[]" value="Tủ lạnh" id="fridge" <?php echo (isset($_POST['utilities']) && in_array('Tủ lạnh', $_POST['utilities'])) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="fridge">
                                                    <i class="fas fa-cube me-1 text-primary"></i> Tủ lạnh
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="utilities[]" value="Chỗ để xe" id="motorcycle" <?php echo (isset($_POST['utilities']) && in_array('Chỗ để xe', $_POST['utilities'])) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="motorcycle">
                                                    <i class="fas fa-motorcycle me-1 text-primary"></i> Chỗ để xe
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="utilities[]" value="An ninh" id="security" <?php echo (isset($_POST['utilities']) && in_array('An ninh', $_POST['utilities'])) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="security">
                                                    <i class="fas fa-shield-alt me-1 text-primary"></i> An ninh
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="utilities[]" value="Tự do" id="freedom" <?php echo (isset($_POST['utilities']) && in_array('Tự do', $_POST['utilities'])) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="freedom">
                                                    <i class="fas fa-door-open me-1 text-primary"></i> Tự do
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Hình ảnh phòng trọ -->
                                <h4 class="mt-5 mb-4 border-bottom pb-2"><i class="fas fa-images me-2 text-primary"></i>Hình ảnh phòng trọ</h4>

                                <div class="mb-3">
                                    <label for="banner_image" class="form-label"><i class="fas fa-image text-primary me-1"></i> Ảnh banner</label>
                                    <input type="file" class="form-control" id="banner_image" name="banner_image" accept="image/*" required>
                                    <div class="form-text">Chọn ảnh đại diện chính (bắt buộc)</div>
                                    <div id="banner_preview" class="image-preview mt-2"></div>
                                </div>

                                <div class="mb-3">
                                    <label for="additional_images" class="form-label"><i class="fas fa-images text-primary me-1"></i> Hình ảnh bổ sung</label>
                                    <input type="file" class="form-control" id="additional_images" name="additional_images[]" multiple accept="image/*">
                                    <div class="form-text">Chọn nhiều hình ảnh để mô tả chi tiết phòng trọ (tối đa 10 hình).</div>
                                    <div id="additional_preview" class="row mt-2"></div>
                                </div>

                                <!-- Nút đăng tin -->
                                <div class="mt-5 d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i>Đăng tin
                                    </button>
                                    <p class="text-center text-muted small">
                                        Bằng việc đăng tin, bạn đã đồng ý với <a href="#">điều khoản sử dụng</a> của chúng tôi.
                                    </p>
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

                            // Check if we have any location information to work with
                            const hasLocationData = addressData.district || addressData.cityDistrict ||
                                addressData.subdistrict || addressData.city ||
                                addressData.county || addressData.state;

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
                        ['image', 'link'],
                        ['clean']
                    ]
                }
            });

            // Cập nhật nội dung vào textarea khi submit form
            $('form').on('submit', function() {
                $('#description').val(quill.root.innerHTML);
            });

            // Cập nhật utilities khi chọn tiện ích
            $('input[name="utilities[]"]').change(function() {
                var selected = [];
                $('input[name="utilities[]"]:checked').each(function() {
                    selected.push($(this).val());
                });
                // Không cần thiết vì chúng ta đã có các checkbox với name="utilities[]"
            });

            // Hiển thị preview cho ảnh banner
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

            // Hiển thị preview cho nhiều ảnh
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
                                col.append('<img src="' + e.target.result + '" class="img-fluid rounded" style="height: 120px; object-fit: cover;">');
                                preview.append(col);
                            }
                            reader.readAsDataURL(file);
                        })(files[i]);
                    }
                }
            });

            // Hàm debounce để giới hạn số lần gọi API
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

            // Biến toàn cục cho bản đồ và marker
            let map = null;
            let marker = null;
            let pendingCoordinates = null;

            // Hàm lấy tọa độ từ địa chỉ
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
                            // Reset refresh button
                            $('#refresh_coordinates').html('<i class="fas fa-sync-alt"></i>');
                            $('#map').show();

                            // Check if we have valid coordinates
                            if (response.success && response.coordinates && response.coordinates.lat && response.coordinates.lng) {
                                // Display coordinates in input
                                var lat = response.coordinates.lat;
                                var lng = response.coordinates.lng;

                                $('#coordinates_display').val('Vĩ độ: ' + lat + ', Kinh độ: ' + lng);
                                $('#lat').val(lat);
                                $('#lng').val(lng);
                                $('#latlng').val(lat + ',' + lng);

                                // Initialize map if not already done
                                if (!map) {
                                    initMap(lat, lng);
                                } else {
                                    // Update map center and marker
                                    map.setView([lat, lng], 16);
                                    if (marker) {
                                        marker.setLatLng([lat, lng]);
                                    } else {
                                        marker = createMarker(lat, lng, fullAddress);
                                    }
                                }
                            } else {
                                // Handle error or no coordinates found
                                $('#coordinates_display').val('Không tìm thấy tọa độ cho địa chỉ này');
                                $('#location_error').html('<div class="alert alert-warning">Không thể tìm thấy tọa độ chính xác cho địa chỉ này. Vui lòng kiểm tra lại địa chỉ hoặc đánh dấu vị trí trực tiếp trên bản đồ.</div>').show();

                                // Initialize map at Vinh City center if not already done
                                if (!map) {
                                    initMap(18.6667, 105.6667, 13); // Vinh City center with lower zoom
                                }
                            }
                        },
                        error: function() {
                            $('#refresh_coordinates').html('<i class="fas fa-sync-alt"></i>');
                            $('#coordinates_display').val('Lỗi khi tìm tọa độ');
                            $('#location_error').html('<div class="alert alert-danger">Có lỗi xảy ra khi tìm tọa độ. Vui lòng thử lại sau.</div>').show();
                        }
                    });
                } else if (showLoading) {
                    $('#location_error').html('<div class="alert alert-info">Vui lòng nhập địa chỉ chi tiết và chọn khu vực trước khi tìm tọa độ.</div>').show();
                    setTimeout(function() {
                        $('#location_error').fadeOut();
                    }, 3000);
                }
            }

            // Helper function to create a marker
            function createMarker(lat, lng, title) {
                let newMarker = L.marker([lat, lng], {
                    draggable: true
                }).addTo(map);

                // Add popup with info
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

                    // Check if the location is within Vinh City (15km radius)
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

            // Initialize map with coordinates
            function initMap(lat, lng, zoom = 16) {
                // Remove loading indicator
                $('#map-loading').hide();

                // Create map instance
                map = L.map('map').setView([lat, lng], zoom);

                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                // Add a circle showing the 15km boundary around Vinh City center
                var vinhCenter = [18.6667, 105.6667]; // Tọa độ trung tâm thành phố Vinh
                L.circle(vinhCenter, {
                    color: 'blue',
                    fillColor: '#30f',
                    fillOpacity: 0.1,
                    radius: 15000 // 15km in meters
                }).addTo(map);

                // Add a marker at Vinh City center
                L.marker(vinhCenter).bindPopup('Trung tâm thành phố Vinh').addTo(map);

                // Create initial marker if coordinates are provided
                if (lat && lng) {
                    marker = createMarker(lat, lng, 'Vị trí phòng trọ');
                }

                // Allow clicking on map to set marker
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

                    // Check if the location is within Vinh City (15km radius)
                    const vinhCenter = [18.6667, 105.6667];
                    const distance = haversineDistance(e.latlng.lat, e.latlng.lng, vinhCenter[0], vinhCenter[1]);

                    if (distance > 15) {
                        $('#location_error').html('<div class="alert alert-warning">Vị trí bạn chọn nằm ngoài phạm vi thành phố Vinh (khoảng cách ' +
                            distance.toFixed(2) + ' km). Hệ thống chỉ hỗ trợ đăng tin phòng trọ trong thành phố Vinh.</div>').show();
                    } else {
                        $('#location_error').hide();
                    }
                });

                // Make map resize properly
                setTimeout(function() {
                    map.invalidateSize();
                }, 500);
            }

            // Tạo phiên bản debounced của hàm getCoordinates
            const debouncedGetCoordinates = debounce(getCoordinates, 1000);

            // Gọi hàm lấy tọa độ khi thay đổi quận hoặc địa chỉ
            $('#district_id, #address_detail').on('change keyup blur', function() {
                debouncedGetCoordinates();
            });

            // Thêm sự kiện cho nút làm mới tọa độ
            $('#refresh_coordinates').on('click', function() {
                getCoordinates(true);
            });

            // Lấy vị trí từ IP
            $('#get_ip_location').on('click', function() {
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang lấy vị trí...');

                $.ajax({
                    url: '../api/maps/get_ip_location.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        $('#get_ip_location').prop('disabled', false).html('<i class="fas fa-location-arrow me-1"></i> Lấy vị trí hiện tại từ IP');

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

                            // Update address using reverse geocoding
                            updateAddressFromCoordinates(lat, lng, marker, false);

                            // Check if the location is within Vinh City (15km radius)
                            const vinhCenter = [18.6667, 105.6667];
                            const distance = haversineDistance(lat, lng, vinhCenter[0], vinhCenter[1]);

                            if (distance > 15) {
                                $('#location_error').html('<div class="alert alert-warning">Vị trí bạn chọn nằm ngoài phạm vi thành phố Vinh (khoảng cách ' +
                                    distance.toFixed(2) + ' km). Hệ thống chỉ hỗ trợ đăng tin phòng trọ trong thành phố Vinh.</div>').show();
                            } else {
                                $('#location_error').hide();
                            }
                        } else {
                            $('#location_error').html('<div class="alert alert-warning">Không thể xác định vị trí từ IP. Vui lòng sử dụng vị trí từ trình duyệt hoặc nhập địa chỉ.</div>').show();
                            setTimeout(function() {
                                $('#location_error').fadeOut();
                            }, 5000);
                        }
                    },
                    error: function() {
                        $('#get_ip_location').prop('disabled', false).html('<i class="fas fa-location-arrow me-1"></i> Lấy vị trí hiện tại từ IP');
                        $('#location_error').html('<div class="alert alert-danger">Có lỗi xảy ra khi lấy vị trí từ IP. Vui lòng thử lại sau.</div>').show();
                    }
                });
            });

            // Lấy vị trí từ trình duyệt
            $('#get_browser_location').on('click', function() {
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang lấy vị trí...');

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            $('#get_browser_location').prop('disabled', false).html('<i class="fas fa-crosshairs me-1"></i> Lấy vị trí từ trình duyệt');

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

                            // Update address using reverse geocoding
                            updateAddressFromCoordinates(lat, lng, marker, false);

                            // Check if the location is within Vinh City (15km radius)
                            const vinhCenter = [18.6667, 105.6667];
                            const distance = haversineDistance(lat, lng, vinhCenter[0], vinhCenter[1]);

                            if (distance > 15) {
                                $('#location_error').html('<div class="alert alert-warning">Vị trí bạn chọn nằm ngoài phạm vi thành phố Vinh (khoảng cách ' +
                                    distance.toFixed(2) + ' km). Hệ thống chỉ hỗ trợ đăng tin phòng trọ trong thành phố Vinh.</div>').show();
                            } else {
                                $('#location_error').hide();
                            }
                        },
                        function(error) {
                            $('#get_browser_location').prop('disabled', false).html('<i class="fas fa-crosshairs me-1"></i> Lấy vị trí từ trình duyệt');

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
                    $('#get_browser_location').prop('disabled', false).html('<i class="fas fa-crosshairs me-1"></i> Lấy vị trí từ trình duyệt');
                    $('#location_error').html('<div class="alert alert-warning">Trình duyệt của bạn không hỗ trợ Geolocation.</div>').show();
                }
            });

            // Initialize map with Vinh City center at startup
            setTimeout(function() {
                if (!map) {
                    initMap(18.6667, 105.6667, 13);
                }
            }, 1000);

            // Initialize form validation
            const forms = document.querySelectorAll('.needs-validation');

            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    // Check if coordinates are set
                    const latValue = $('#lat').val();
                    const lngValue = $('#lng').val();
                    const latlngValue = $('#latlng').val();

                    if (!latValue || !lngValue || !latlngValue) {
                        event.preventDefault();
                        $('#location_error').html('<div class="alert alert-danger">Vui lòng chọn vị trí trên bản đồ trước khi đăng tin.</div>').show();
                        setTimeout(function() {
                            $('html, body').animate({
                                scrollTop: $("#map").offset().top - 100
                            }, 500);
                        }, 100);
                        return;
                    }

                    // Check if location is within Vinh City boundaries (15km radius)
                    const vinhCenter = [18.6667, 105.6667];
                    const pointLat = parseFloat(latValue);
                    const pointLng = parseFloat(lngValue);

                    const distance = haversineDistance(pointLat, pointLng, vinhCenter[0], vinhCenter[1]);

                    if (distance > 15) {
                        event.preventDefault();
                        $('#location_error').html('<div class="alert alert-danger">Vị trí bạn chọn nằm ngoài phạm vi thành phố Vinh (khoảng cách ' +
                            distance.toFixed(2) + ' km). Hệ thống chỉ hỗ trợ đăng tin phòng trọ trong thành phố Vinh.</div>').show();
                        setTimeout(function() {
                            $('html, body').animate({
                                scrollTop: $("#map").offset().top - 100
                            }, 500);
                        }, 100);
                        return;
                    }

                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        });
    </script>
</body>

</html>