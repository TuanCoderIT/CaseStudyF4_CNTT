<?php
// Khởi tạo phiên làm việc
session_start();

// Kiểm tra xem người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Auth/login.php');
    exit;
}

// Kết nối đến CSDL
require_once('../config/db.php');

// Lấy danh sách quận/huyện
$stmt_districts = $conn->prepare("SELECT * FROM district ORDER BY name");
$stmt_districts->execute();
$districts = $stmt_districts->get_result();

// Danh sách các tiện ích
$utilities = [
    'Wifi', 'Điều hòa', 'Nóng lạnh', 'Tủ lạnh', 'Máy giặt',
    'Gác lửng', 'Nhà vệ sinh riêng', 'Ban công', 'Chỗ để xe', 
    'Tự do giờ giấc', 'Camera an ninh', 'Bếp riêng'
];

// Xử lý khi form được gửi
$success_message = $error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ form
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = (int) str_replace(['.', ','], '', $_POST['price']);
    $area = (int) $_POST['area'];
    $address = trim($_POST['address']);
    $district_id = (int) $_POST['district'];
    $distance = (float) $_POST['distance'];
    $selected_utilities = isset($_POST['utilities']) ? $_POST['utilities'] : [];
    $phone = trim($_POST['phone']);
    
    // Xác thực dữ liệu
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Tiêu đề không được để trống";
    } elseif (strlen($title) < 10 || strlen($title) > 100) {
        $errors[] = "Tiêu đề phải từ 10 đến 100 ký tự";
    }
    
    if (empty($description)) {
        $errors[] = "Mô tả không được để trống";
    }
    
    if ($price <= 0 || $price > 50000000) {
        $errors[] = "Giá phải lớn hơn 0 và nhỏ hơn 50 triệu";
    }
    
    if ($area <= 0 || $area > 500) {
        $errors[] = "Diện tích phải lớn hơn 0 và nhỏ hơn 500m²";
    }
    
    if (empty($address)) {
        $errors[] = "Địa chỉ không được để trống";
    }
    
    if ($district_id <= 0) {
        $errors[] = "Vui lòng chọn quận/huyện";
    }
    
    if ($distance < 0) {
        $errors[] = "Khoảng cách không được âm";
    }
    
    if (empty($phone)) {
        $errors[] = "Số điện thoại liên hệ không được để trống";
    } elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) {
        $errors[] = "Số điện thoại không hợp lệ";
    }
    
    // Kiểm tra hình ảnh
    $total_images = count($_FILES['images']['name']);
    
    if ($total_images == 0 || empty($_FILES['images']['name'][0])) {
        $errors[] = "Vui lòng tải lên ít nhất 1 hình ảnh";
    } elseif ($total_images > 10) {
        $errors[] = "Chỉ được phép tải lên tối đa 10 hình ảnh";
    }
    
    $images_path = [];
    
    // Nếu không có lỗi, tiến hành xử lý và lưu dữ liệu
    if (empty($errors)) {
        // Tạo thư mục nếu chưa tồn tại
        $target_dir = "../images/rooms/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Xử lý hình ảnh
        for ($i = 0; $i < $total_images; $i++) {
            $file_extension = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
            $file_name = uniqid('room_') . '_' . ($i + 1) . '.' . $file_extension;
            $target_file = $target_dir . $file_name;
            
            if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $target_file)) {
                $images_path[] = 'images/rooms/' . $file_name;
            }
        }
        
        // Chuyển đổi mảng tiện ích thành chuỗi
        $utilities_str = implode(',', $selected_utilities);
        
        // Lưu đường dẫn hình ảnh dưới dạng chuỗi (hình ảnh đầu tiên sẽ là thumbnail)
        $thumbnail = $images_path[0];
        $images_json = implode(',', $images_path);
        
        // Lưu vào CSDL
        $user_id = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("INSERT INTO motel (title, description, price, area, count_view, address, latlng, images, user_id, district_id, utilities, phone, approve) 
                                VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("sssisssiiss", $title, $description, $price, $area, $address, $distance, $images_json, $user_id, $district_id, $utilities_str, $phone);
        
        if ($stmt->execute()) {
            $success_message = "Đăng tin thành công! Tin của bạn đang được chờ duyệt và sẽ hiển thị sau khi được kiểm duyệt.";
        } else {
            $error_message = "Có lỗi xảy ra khi đăng tin: " . $conn->error;
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng tin cho thuê - Phòng trọ sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Assets/style.css">
    <!-- Thêm CSS cho trang đăng tin -->
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            font-family: 'Lexend', sans-serif;
            padding-top: 70px;
        }
        
        .post-form-container {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .form-section {
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        
        .form-section:last-child {
            border-bottom: none;
        }
        
        .section-title {
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--dark-color);
        }
        
        .form-label {
            font-weight: 600;
        }
        
        .price-input {
            position: relative;
        }
        
        .price-input::after {
            content: "VND";
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-weight: 500;
        }
        
        .utilities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .utility-checkbox {
            display: block;
            padding: 10px 15px;
            border-radius: 10px;
            background: #f8f9fa;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .utility-checkbox:hover {
            background: #e9ecef;
        }
        
        .utility-checkbox input {
            margin-right: 10px;
        }
        
        .image-preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .preview-item {
            height: 150px;
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            border: 2px dashed #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #aaa;
        }
        
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .preview-item .remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
        }
        
        .add-img-btn {
            cursor: pointer;
            height: 150px;
            border: 2px dashed #ddd;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            transition: all 0.3s;
        }
        
        .add-img-btn:hover {
            background: #f8f9fa;
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .add-img-btn i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
            <div class="container">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-home me-2"></i>Phòng trọ sinh viên
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Trang chủ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="search.php">Tìm kiếm</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="post.php">Đăng tin</a>
                        </li>
                    </ul>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <?php
                                // Lấy thông tin người dùng
                                $user_id = $_SESSION['user_id'];
                                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                                $stmt->bind_param("i", $user_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $user = $result->fetch_assoc();
                                
                                echo '<img src="../' . $user['avatar'] . '" class="avatar-small me-2" alt="Avatar"> ';
                                echo htmlspecialchars($user['name']);
                                ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="../Auth/edit_profile.php"><i class="fas fa-user-edit me-2"></i>Chỉnh sửa hồ sơ</a></li>
                                <li><a class="dropdown-item" href="my_rooms.php"><i class="fas fa-list me-2"></i>Phòng trọ của tôi</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../Auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <h1 class="mb-4 text-center">Đăng tin cho thuê phòng trọ</h1>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="post.php" method="POST" class="post-form-container" enctype="multipart/form-data">
                        <!-- Thông tin cơ bản -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-info-circle me-2 text-primary"></i>Thông tin cơ bản
                            </h3>
                            <div class="mb-3">
                                <label for="title" class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required 
                                       placeholder="VD: Phòng trọ cao cấp, gần Đại học Vinh" 
                                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                                <div class="form-text">Tiêu đề nên mô tả ngắn gọn về phòng trọ, từ 10-100 ký tự</div>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Mô tả chi tiết <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="6" required 
                                          placeholder="Mô tả chi tiết về phòng trọ, tiện ích, dịch vụ..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                <div class="form-text">Thông tin càng chi tiết, cơ hội tìm được người thuê càng cao</div>
                            </div>
                        </div>
                        
                        <!-- Thông tin chi tiết -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-list-alt me-2 text-primary"></i>Thông tin chi tiết
                            </h3>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label">Giá cho thuê (VND/tháng) <span class="text-danger">*</span></label>
                                    <div class="price-input">
                                        <input type="text" class="form-control" id="price" name="price" required 
                                               placeholder="VD: 2000000" 
                                               value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="area" class="form-label">Diện tích (m²) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="area" name="area" required 
                                           placeholder="VD: 25" 
                                           value="<?php echo isset($_POST['area']) ? htmlspecialchars($_POST['area']) : ''; ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Địa chỉ cụ thể <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="address" name="address" required 
                                       placeholder="VD: Số 123 Nguyễn Thái Học, Phường Trường Thi" 
                                       value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="district" class="form-label">Quận/Huyện <span class="text-danger">*</span></label>
                                    <select class="form-select" id="district" name="district" required>
                                        <option value="">-- Chọn Quận/Huyện --</option>
                                        <?php while($district = $districts->fetch_assoc()): ?>
                                            <option value="<?php echo $district['id']; ?>" <?php echo (isset($_POST['district']) && $_POST['district'] == $district['id']) ? 'selected' : ''; ?>>
                                                <?php echo $district['name']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="distance" class="form-label">Khoảng cách đến ĐH Vinh (km) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.1" class="form-control" id="distance" name="distance" required 
                                           placeholder="VD: 1.5" 
                                           value="<?php echo isset($_POST['distance']) ? htmlspecialchars($_POST['distance']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tiện ích phòng trọ -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-lightbulb me-2 text-primary"></i>Tiện ích phòng trọ
                            </h3>
                            <div class="utilities-grid">
                                <?php foreach ($utilities as $utility): ?>
                                    <label class="utility-checkbox">
                                        <input type="checkbox" name="utilities[]" value="<?php echo $utility; ?>" 
                                               <?php echo (isset($_POST['utilities']) && in_array($utility, $_POST['utilities'])) ? 'checked' : ''; ?>>
                                        <?php echo $utility; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Hình ảnh -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-images me-2 text-primary"></i>Hình ảnh phòng trọ
                            </h3>
                            <div class="mb-3">
                                <p class="form-text">Tối đa 10 hình ảnh, định dạng .jpg, .jpeg, .png (Hình ảnh đầu tiên sẽ là ảnh đại diện)</p>
                                <input type="file" class="form-control" id="roomImages" name="images[]" accept="image/*" multiple hidden>
                                <div class="image-preview-container">
                                    <!-- Nút thêm ảnh -->
                                    <div class="add-img-btn" id="addImagesBtn">
                                        <i class="fas fa-plus"></i>
                                        <span>Thêm hình ảnh</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Thông tin liên hệ -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-user me-2 text-primary"></i>Thông tin liên hệ
                            </h3>
                            <div class="mb-3">
                                <label for="contactName" class="form-label">Tên liên hệ</label>
                                <input type="text" class="form-control" id="contactName" value="<?php echo $user['name']; ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone" required 
                                       placeholder="VD: 0912345678" 
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : (isset($user['phone']) ? $user['phone'] : ''); ?>">
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-paper-plane me-2"></i>Đăng tin ngay
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Phòng trọ sinh viên</h5>
                    <p class="text-muted">Trang web tìm kiếm phòng trọ dành cho sinh viên trường Đại học Vinh.</p>
                    <div class="social-links">
                        <a href="#" class="me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="me-2"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Liên kết</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php"><i class="fas fa-angle-right me-2"></i>Trang chủ</a></li>
                        <li><a href="search.php"><i class="fas fa-angle-right me-2"></i>Tìm phòng trọ</a></li>
                        <li><a href="post.php"><i class="fas fa-angle-right me-2"></i>Đăng tin</a></li>
                        <li><a href="#"><i class="fas fa-angle-right me-2"></i>Liên hệ</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Liên hệ với chúng tôi</h5>
                    <ul class="list-unstyled contact-info">
                        <li><i class="fas fa-map-marker-alt me-2"></i>182 Lê Duẩn, TP. Vinh, Nghệ An</li>
                        <li><i class="fas fa-phone me-2"></i>0123 456 789</li>
                        <li><i class="fas fa-envelope me-2"></i>info@phongtrodhvinh.com</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col">
                    <p class="text-center mb-0">&copy; <?php echo date('Y'); ?> Phòng trọ sinh viên. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Xử lý định dạng tiền tệ cho input giá
            $('#price').on('input', function() {
                var value = $(this).val().replace(/[^\d]/g, '');
                if (value) {
                    var formattedValue = new Intl.NumberFormat('vi-VN').format(value);
                    $(this).val(formattedValue);
                }
            });
            
            // Xử lý thêm hình ảnh
            $('#addImagesBtn').click(function() {
                $('#roomImages').click();
            });
            
            // Hiển thị xem trước hình ảnh
            $('#roomImages').change(function() {
                var files = this.files;
                var maxFiles = 10;
                
                if (files.length > maxFiles) {
                    alert('Bạn chỉ có thể tải lên tối đa ' + maxFiles + ' hình ảnh.');
                    $(this).val('');
                    return;
                }
                
                // Xóa hình ảnh cũ
                $('.preview-item:not(.add-img-btn)').remove();
                
                // Thêm hình ảnh mới
                for (var i = 0; i < files.length; i++) {
                    if (i >= maxFiles) break;
                    
                    var file = files[i];
                    var reader = new FileReader();
                    
                    reader.onload = (function(file, i) {
                        return function(e) {
                            var previewItem = $('<div class="preview-item"></div>');
                            var img = $('<img src="' + e.target.result + '" alt="Image ' + (i+1) + '">');
                            var removeBtn = $('<span class="remove-btn"><i class="fas fa-times"></i></span>');
                            
                            removeBtn.click(function() {
                                $(this).parent().remove();
                                // Không thể trực tiếp xóa file từ FileList, nên cần reset input
                                if ($('.preview-item:not(.add-img-btn)').length === 0) {
                                    $('#roomImages').val('');
                                }
                            });
                            
                            previewItem.append(img);
                            previewItem.append(removeBtn);
                            previewItem.insertBefore('#addImagesBtn');
                        };
                    })(file, i);
                    
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
</body>
</html>
