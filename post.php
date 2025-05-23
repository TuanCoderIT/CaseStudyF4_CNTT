<?php
// Khởi tạo phiên làm việc
session_start();

// Kiểm tra xem người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Kết nối đến CSDL
require_once __DIR__ . '/config/db.php';

// Khởi tạo mảng favorite_rooms từ CSDL
require_once __DIR__ . '/config/favorites.php';

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
    $latlng = trim($_POST['latlng'] ?? ''); // Distance from Vinh University

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

    // Xử lý upload ảnh
    $uploadedImages = [];
    $uploadDir = '../uploads/';

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
            } else {
                $errors[] = "Có lỗi xảy ra với file " . $_FILES['images']['name'][$key];
            }
        }
    } else {
        $errors[] = "Vui lòng upload ít nhất một ảnh của phòng trọ.";
    }

    // Nếu không có lỗi, thực hiện lưu vào CSDL
    if (empty($errors)) {
        $user_id = $_SESSION['user_id'];
        $images = implode(',', $uploadedImages);

        $stmt = $conn->prepare("
            INSERT INTO motel 
            (title, description, price, area, address, latlng, images, user_id, category_id, district_id, utilities, phone, approve) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
        ");

        $stmt->bind_param(
            "ssiississssi",
            $title,
            $description,
            $price,
            $area,
            $address,
            $latlng,
            $images,
            $user_id,
            $category_id,
            $district_id,
            $utilities,
            $phone
        );

        if ($stmt->execute()) {
            $success_message = "Phòng trọ đã được đăng thành công! Vui lòng chờ quản trị viên phê duyệt.";
            // Reset form sau khi submit thành công
            $_POST = [];
        } else {
            $errors[] = "Có lỗi xảy ra khi lưu thông tin phòng trọ: " . $conn->error;
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
    <link rel="stylesheet" href="../Assets/style.css">
    <!-- Thêm các thư viện Tagify cho chọn nhiều tiện ích -->
    <link rel="stylesheet" href="https://unpkg.com/@yaireo/tagify/dist/tagify.css">
    <script src="https://unpkg.com/@yaireo/tagify/dist/tagify.min.js"></script>
    <!-- Thư viện cho editor -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs5.min.css">
</head>

<body class="home-body">
    <?php include __DIR__ . '/components/header.php' ?>

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
                                    <label for="description" class="form-label">Mô tả chi tiết <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="description" name="description" rows="6"
                                        placeholder="Mô tả chi tiết về phòng trọ, các tiện nghi, quy định..." required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                </div>

                                <!-- Thông tin địa chỉ -->
                                <h4 class="mt-5 mb-4 border-bottom pb-2"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Địa chỉ phòng trọ</h4>

                                <div class="mb-3">
                                    <label for="address" class="form-label">Địa chỉ cụ thể <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="address" name="address"
                                        placeholder="Nhập địa chỉ cụ thể" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>" required>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="district_id" class="form-label">Khu vực <span class="text-danger">*</span></label>
                                        <select class="form-select" id="district_id" name="district_id" required>
                                            <option value="">-- Chọn khu vực --</option>
                                            <?php while ($district = $districts->fetch_assoc()) : ?>
                                                <option value="<?php echo $district['id']; ?>" <?php echo (isset($_POST['district_id']) && $_POST['district_id'] == $district['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($district['name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="latlng" class="form-label">Khoảng cách đến ĐH Vinh (km)</label>
                                        <input type="text" class="form-control" id="latlng" name="latlng"
                                            placeholder="VD: 1.5" value="<?php echo isset($_POST['latlng']) ? htmlspecialchars($_POST['latlng']) : ''; ?>">
                                        <div class="form-text">Nhập khoảng cách đến Đại học Vinh (nếu biết)</div>
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

                                <div class="mb-4">
                                    <label for="images" class="form-label">Upload hình ảnh <span class="text-danger">*</span></label>
                                    <input class="form-control" type="file" id="images" name="images[]" multiple accept="image/*" required>
                                    <div class="form-text">Tải lên tối đa 5 hình, mỗi hình không quá 5MB. Định dạng cho phép: JPG, PNG, GIF</div>

                                    <div id="imagePreviewContainer" class="mt-3 row g-2"></div>
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

    <?php include '../components/footer.php' ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/admin/js/main.js"></script>
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

            // Image preview functionality
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