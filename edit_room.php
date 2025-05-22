<?php
// Khởi tạo phiên làm việc
session_start();

// Kiểm tra xem người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: ./auth/login.php');
    exit;
}

// Kết nối đến CSDL
require_once('./config/db.php');

// Khởi tạo mảng favorite_rooms từ CSDL
require_once('./config/favorites.php');

// Lấy ID của phòng trọ cần chỉnh sửa
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: my_posted_rooms.php');
    exit;
}

$room_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Kiểm tra xem phòng trọ có thuộc về người dùng hiện tại không
$check_stmt = $conn->prepare("SELECT * FROM motel WHERE id = ? AND user_id = ?");
$check_stmt->bind_param("ii", $room_id, $user_id);
$check_stmt->execute();
$room_result = $check_stmt->get_result();

if ($room_result->num_rows === 0) {
    header('Location: my_posted_rooms.php');
    exit;
}

$room = $room_result->fetch_assoc();

// Danh sách quận/huyện
$stmt_districts = $conn->prepare("SELECT * FROM districts ORDER BY name");
$stmt_districts->execute();
$districts = $stmt_districts->get_result();

// Danh sách danh mục
$stmt_categories = $conn->prepare("SELECT * FROM categories ORDER BY name");
$stmt_categories->execute();
$categories = $stmt_categories->get_result();

// Khởi tạo biến lỗi và thông báo
$errors = [];
$success_message = '';

// Xử lý khi form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy thông tin từ form
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (int)($_POST['price'] ?? 0);
    $area = (int)($_POST['area'] ?? 0);
    $address = trim($_POST['address'] ?? '');
    $district_id = (int)($_POST['district_id'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $phone = trim($_POST['phone'] ?? '');
    $utilities = isset($_POST['utilities']) ? implode(',', $_POST['utilities']) : '';
    $latlng = trim($_POST['latlng'] ?? '');

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

    // Xử lý upload ảnh mới (nếu có)
    $uploadedImages = [];
    $currentImages = explode(',', $room['images']);

    // Nếu người dùng không xóa ảnh nào, giữ nguyên các ảnh cũ
    if (!isset($_POST['deleted_images'])) {
        $uploadedImages = $currentImages;
    } else {
        // Nếu người dùng xóa ảnh, lọc ra các ảnh không bị xóa
        $deletedImages = $_POST['deleted_images'];
        foreach ($currentImages as $img) {
            if (!in_array($img, $deletedImages)) {
                $uploadedImages[] = $img;
            } else {
                // Xóa file ảnh cũ
                $filePath = './' . $img;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }
    }

    // Xử lý upload ảnh mới
    $uploadDir = './uploads/';

    // Kiểm tra xem thư mục upload đã tồn tại chưa, nếu chưa thì tạo mới
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['images']['name'][$key];
                $fileSize = $_FILES['images']['size'][$key];
                $fileType = $_FILES['images']['type'][$key];
                $fileTmp = $_FILES['images']['tmp_name'][$key];

                // Kiểm tra định dạng file
                $validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!in_array($fileType, $validTypes)) {
                    $errors[] = "File $fileName không phải là ảnh hợp lệ (JPG, PNG, GIF).";
                    continue;
                }

                // Kiểm tra kích thước file (giới hạn 5MB)
                if ($fileSize > 5 * 1024 * 1024) {
                    $errors[] = "File $fileName vượt quá kích thước cho phép (5MB).";
                    continue;
                }

                // Tạo tên file mới để tránh trùng lặp
                $newFileName = 'room_' . time() . '_' . mt_rand(1000, 9999) . '_' . $fileName;
                $destination = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmp, $destination)) {
                    $uploadedImages[] = 'uploads/' . $newFileName;
                } else {
                    $errors[] = "Có lỗi khi upload file $fileName.";
                }
            } else if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "Có lỗi xảy ra với file " . $_FILES['images']['name'][$key];
            }
        }
    }

    // Nếu không có ảnh nào, báo lỗi
    if (empty($uploadedImages)) {
        $errors[] = "Phòng trọ phải có ít nhất một ảnh.";
    }

    // Nếu không có lỗi, thực hiện cập nhật vào CSDL
    if (empty($errors)) {
        $images = implode(',', $uploadedImages);

        // Phòng đã chỉnh sửa sẽ trở về trạng thái chờ duyệt
        $approve = 0;

        $stmt = $conn->prepare("
            UPDATE motel 
            SET title = ?, description = ?, price = ?, area = ?, address = ?, latlng = ?, 
                images = ?, category_id = ?, district_id = ?, utilities = ?, phone = ?, approve = ?
            WHERE id = ? AND user_id = ?
        ");

        $stmt->bind_param(
            "ssiisssiissiii",
            $title,
            $description,
            $price,
            $area,
            $address,
            $latlng,
            $images,
            $category_id,
            $district_id,
            $utilities,
            $phone,
            $approve,
            $room_id,
            $user_id
        );

        if ($stmt->execute()) {
            $success_message = "Phòng trọ đã được cập nhật thành công! Vui lòng chờ quản trị viên phê duyệt lại.";

            // Đợi 2 giây rồi chuyển hướng về trang danh sách phòng đã đăng
            echo '<meta http-equiv="refresh" content="2;url=my_posted_rooms.php">';
        } else {
            $errors[] = "Có lỗi xảy ra khi cập nhật thông tin phòng trọ: " . $conn->error;
        }
    }
}

// Lấy danh sách tiện ích đã có
$room_utilities = explode(',', $room['utilities']);

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa phòng trọ - Phòng trọ sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./Assets/style.css">
    <!-- Thêm các thư viện Tagify cho chọn nhiều tiện ích -->
    <link rel="stylesheet" href="https://unpkg.com/@yaireo/tagify/dist/tagify.css">
    <script src="https://unpkg.com/@yaireo/tagify/dist/tagify.min.js"></script>
    <!-- Thư viện cho editor -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs5.min.css">
    <style>
        .image-preview {
            position: relative;
            margin-bottom: 15px;
        }

        .image-preview img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
        }

        .image-preview .delete-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body class="home-body">
    <?php include './Components/header.php' ?>

    <main class="py-5 mt-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <!-- Tiêu đề trang -->
                    <h1 class="text-center mb-4">
                        <i class="fas fa-edit text-primary me-2"></i>
                        Chỉnh sửa thông tin phòng trọ
                    </h1>
                    <p class="text-center text-muted mb-4">
                        Chỉnh sửa thông tin chi tiết về phòng trọ của bạn.
                        <br>
                        <small>Phòng trọ sau khi chỉnh sửa sẽ cần được duyệt lại trước khi hiển thị công khai.</small>
                    </p>

                    <!-- Hiển thị thông báo lỗi -->
                    <?php if (!empty($errors)) : ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error) : ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Hiển thị thông báo thành công -->
                    <?php if (!empty($success_message)) : ?>
                        <div class="alert alert-success">
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Form chỉnh sửa phòng trọ -->
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <!-- Thông tin cơ bản -->
                                <h4 class="mb-4 border-bottom pb-2"><i class="fas fa-info-circle me-2 text-primary"></i>Thông tin cơ bản</h4>

                                <div class="mb-3">
                                    <label for="title" class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title"
                                        placeholder="Nhập tiêu đề phòng trọ" value="<?php echo htmlspecialchars($room['title']); ?>" required>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="price" class="form-label">Giá thuê (VNĐ/tháng) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="price" name="price"
                                                placeholder="Nhập giá thuê" value="<?php echo $room['price']; ?>" required>
                                            <span class="input-group-text">VNĐ</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="area" class="form-label">Diện tích (m²) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="area" name="area"
                                                placeholder="Nhập diện tích" value="<?php echo $room['area']; ?>" required>
                                            <span class="input-group-text">m²</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="category_id" class="form-label">Loại phòng <span class="text-danger">*</span></label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">-- Chọn loại phòng --</option>
                                            <?php while ($category = $categories->fetch_assoc()) : ?>
                                                <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $room['category_id'] ? 'selected' : ''; ?>>
                                                    <?php echo $category['name']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="district_id" class="form-label">Khu vực <span class="text-danger">*</span></label>
                                        <select class="form-select" id="district_id" name="district_id" required>
                                            <option value="">-- Chọn khu vực --</option>
                                            <?php
                                            // Reset con trỏ để đọc lại từ đầu
                                            $districts->data_seek(0);
                                            while ($district = $districts->fetch_assoc()) :
                                            ?>
                                                <option value="<?php echo $district['id']; ?>" <?php echo $district['id'] == $room['district_id'] ? 'selected' : ''; ?>>
                                                    <?php echo $district['name']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label">Địa chỉ cụ thể <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="address" name="address"
                                        placeholder="Nhập địa chỉ cụ thể, ví dụ: 123 Nguyễn Văn A, phường X, ..."
                                        value="<?php echo htmlspecialchars($room['address']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">Số điện thoại liên hệ <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        placeholder="Nhập số điện thoại liên hệ" value="<?php echo htmlspecialchars($room['phone']); ?>" required>
                                </div>

                                <!-- Mô tả chi tiết -->
                                <h4 class="mb-4 border-bottom pb-2 mt-5"><i class="fas fa-align-left me-2 text-primary"></i>Mô tả chi tiết</h4>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Mô tả chi tiết <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="description" name="description" rows="6" required><?php echo htmlspecialchars($room['description']); ?></textarea>
                                    <div class="form-text">Mô tả chi tiết về phòng trọ, tiện ích, nội thất, môi trường xung quanh...</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Tiện ích có sẵn</label>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="utilities[]" value="Wifi miễn phí" id="wifi"
                                                    <?php echo in_array('Wifi miễn phí', $room_utilities) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="wifi">Wifi miễn phí</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="utilities[]" value="Điều hòa" id="aircon"
                                                    <?php echo in_array('Điều hòa', $room_utilities) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="aircon">Điều hòa</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="utilities[]" value="Máy giặt" id="washer"
                                                    <?php echo in_array('Máy giặt', $room_utilities) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="washer">Máy giặt</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="utilities[]" value="Tủ lạnh" id="fridge"
                                                    <?php echo in_array('Tủ lạnh', $room_utilities) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="fridge">Tủ lạnh</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="utilities[]" value="Chỗ để xe" id="parking"
                                                    <?php echo in_array('Chỗ để xe', $room_utilities) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="parking">Chỗ để xe</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="utilities[]" value="An ninh" id="security"
                                                    <?php echo in_array('An ninh', $room_utilities) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="security">An ninh</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="utilities[]" value="Nhà bếp riêng" id="kitchen"
                                                    <?php echo in_array('Nhà bếp riêng', $room_utilities) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="kitchen">Nhà bếp riêng</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="utilities[]" value="WC riêng" id="wc"
                                                    <?php echo in_array('WC riêng', $room_utilities) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="wc">WC riêng</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="utilities[]" value="Nước nóng" id="hotwater"
                                                    <?php echo in_array('Nước nóng', $room_utilities) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="hotwater">Nước nóng</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Hình ảnh -->
                                <h4 class="mb-4 border-bottom pb-2 mt-5"><i class="fas fa-images me-2 text-primary"></i>Hình ảnh</h4>

                                <!-- Hiển thị hình ảnh hiện có -->
                                <div class="mb-3">
                                    <label class="form-label">Hình ảnh hiện tại</label>
                                    <div class="row" id="current-images">
                                        <?php foreach ($currentImages as $index => $img) : ?>
                                            <div class="col-6 col-md-3 mb-3">
                                                <div class="image-preview">
                                                    <img src="./<?php echo $img; ?>" class="img-thumbnail" alt="Ảnh phòng trọ">
                                                    <button type="button" class="delete-btn" data-image="<?php echo $img; ?>">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <input type="hidden" name="current_images[]" value="<?php echo $img; ?>">
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="images" class="form-label">Thêm hình ảnh mới</label>
                                    <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple>
                                    <div class="form-text">Có thể chọn nhiều hình ảnh, kích thước tối đa 5MB/ảnh, định dạng jpg, png, gif</div>
                                </div>

                                <!-- Preview hình ảnh mới -->
                                <div class="mb-5">
                                    <div class="row" id="imagePreviewContainer"></div>
                                </div>

                                <!-- Vị trí (tùy chọn) -->
                                <h4 class="mb-4 border-bottom pb-2 mt-5"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Thông tin vị trí</h4>

                                <div class="mb-3">
                                    <label for="latlng" class="form-label">Tọa độ (để trống nếu không rõ)</label>
                                    <input type="text" class="form-control" id="latlng" name="latlng"
                                        placeholder="Tọa độ vị trí (lat,lng)" value="<?php echo htmlspecialchars($room['latlng']); ?>">
                                    <div class="form-text">Định dạng: latitude,longitude (ví dụ: 18.679585,105.681335)</div>
                                </div>

                                <div class="mt-5 d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-2"></i>Lưu thay đổi
                                    </button>
                                    <a href="my_posted_rooms.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include './Components/footer.php' ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="./Assets/main.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs5.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize form validation
            const forms = document.querySelectorAll('.needs-validation');

            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });

            // Image preview functionality for new images
            const imageInput = document.getElementById('images');
            const previewContainer = document.getElementById('imagePreviewContainer');

            imageInput.addEventListener('change', function() {
                // Clear previous previews
                previewContainer.innerHTML = '';

                if (this.files) {
                    // Limit to first 5 files
                    const filesToPreview = Array.from(this.files).slice(0, 5);

                    filesToPreview.forEach(file => {
                        if (file.type.match('image.*')) {
                            const reader = new FileReader();

                            reader.onload = function(e) {
                                const col = document.createElement('div');
                                col.className = 'col-6 col-md-4 col-lg-3';

                                const previewCard = document.createElement('div');
                                previewCard.className = 'card h-100';

                                const img = document.createElement('img');
                                img.src = e.target.result;
                                img.className = 'card-img-top';
                                img.style.height = '120px';
                                img.style.objectFit = 'cover';

                                const cardBody = document.createElement('div');
                                cardBody.className = 'card-body p-2';
                                cardBody.innerHTML = `<p class="card-text small text-truncate">${file.name}</p>`;

                                previewCard.appendChild(img);
                                previewCard.appendChild(cardBody);
                                col.appendChild(previewCard);
                                previewContainer.appendChild(col);
                            }

                            reader.readAsDataURL(file);
                        }
                    });
                }
            });

            // Handle delete current images
            const deleteButtons = document.querySelectorAll('.delete-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const image = this.getAttribute('data-image');
                    const container = this.parentElement.parentElement;

                    // Create hidden input to track deleted images
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'deleted_images[]';
                    hiddenInput.value = image;
                    document.querySelector('form').appendChild(hiddenInput);

                    // Remove the image preview
                    container.remove();
                });
            });

            // Initialize rich text editor for description
            $('#description').summernote({
                placeholder: 'Viết mô tả chi tiết về phòng trọ...',
                tabsize: 2,
                height: 200,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
        });
    </script>
</body>

</html>