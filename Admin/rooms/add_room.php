<?php
session_start();
require_once '../../config/db.php';
require_once '../../configs/config.php';

// Kiểm tra đăng nhập với quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header('Location: ../../Auth/login.php?message=Bạn cần đăng nhập với tài khoản admin');
    exit();
}

// Lấy thông tin danh mục
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");
$address = $conn->query("SELECT * FROM districts");

// Xử lý thêm phòng trọ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ form
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = (int)$_POST['price'];
    $area = (int)$_POST['area'];
    $address_detail = mysqli_real_escape_string($conn, $_POST['address_detail']);
    $ward_name = mysqli_real_escape_string($conn, $_POST['ward_name']);
    $district_name = mysqli_real_escape_string($conn, $_POST['district_name']);
    $province_name = mysqli_real_escape_string($conn, $_POST['province_name']);
    $address = $address_detail . ', ' . $ward_name . ', ' . $district_name . ', ' . $province_name;
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $category_id = (int)$_POST['category_id'];
    $district_id = (int)$_POST['district_id'];
    $utilities = mysqli_real_escape_string($conn, $_POST['utilities']);
    $user_id = $_SESSION['user_id'];

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
        }
    }

    try {
        // Thêm phòng trọ vào database
        $query = "INSERT INTO motel (title, description, price, area, address, latlng, phone, 
                                category_id, district_id, utilities, user_id, images, approve)
                VALUES ('$title', '$description', '$price', '$area', '$address', '$latlng', '$phone',
                        '$category_id', '$district_id', '$utilities', '$user_id', '$banner_image', 1)";

        if (mysqli_query($conn, $query)) {
            $motel_id = mysqli_insert_id($conn);

            // Xử lý upload nhiều hình ảnh
            if (isset($_FILES['additional_images'])) {
                $upload_dir = PROJECT_ROOT . '/uploads/rooms/';

                // Tạo thư mục nếu chưa tồn tại
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $file_count = count($_FILES['additional_images']['name']);

                for ($i = 0; $i < $file_count; $i++) {
                    // Kiểm tra nếu file hợp lệ
                    if ($_FILES['additional_images']['error'][$i] == 0) {
                        $file_name = time() . '_' . $i . '_' . $_FILES['additional_images']['name'][$i];
                        $target_file = $upload_dir . $file_name;

                        // Di chuyển file tạm vào thư mục đích
                        if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$i], $target_file)) {
                            $image_path = 'uploads/rooms/' . $file_name;

                            // Lưu thông tin hình ảnh vào bảng motel_images
                            $insert_image = "INSERT INTO motel_images (motel_id, image_path, display_order) 
                                            VALUES ($motel_id, '$image_path', $i)";
                            mysqli_query($conn, $insert_image);
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Lỗi: " . $e->getMessage();
    }

    $_SESSION['success'] = "Thêm phòng trọ thành công!";
    header('Location: manage_rooms.php');
    exit();
}

$page_title = "Thêm phòng trọ mới";
include_once '../../Components/admin_header.php';
?>

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2><i class="fas fa-plus-circle mr-2"></i> Thêm phòng trọ mới</h2>
        <a href="manage_rooms.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại danh sách
        </a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?php echo $error; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-gradient-primary text-white">
        <h5 class="m-0 font-weight-bold"><i class="fas fa-edit mr-2"></i>Thông tin phòng trọ</h5>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="roomForm">
            <div class="form-group">
                <label for="title">Tiêu đề</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="description">Mô tả</label>
                <div id="editor-container" style="height: 300px;"></div>
                <textarea name="description" id="description" style="display: none;"></textarea>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="price">Giá (VNĐ)</label>
                        <input type="number" class="form-control" id="price" name="price" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="area">Diện tích (m²)</label>
                        <input type="number" class="form-control" id="area" name="area" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="ward">Phường/Xã</label>
                <select class="form-control" id="ward" name="ward_name" required>
                    <option value="">-- Chọn Phường/Xã --</option>
                    <?php while ($cat = mysqli_fetch_assoc($address)): ?>
                        <option value="<?php echo $cat['name']; ?>" data-id="<?php echo $cat['id']; ?>">
                            <?php echo $cat['name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="hidden" name="district_id" id="district_id">
            </div>

            <div class="form-group">
                <label for="address_detail">Địa chỉ chi tiết</label>
                <input type="text" class="form-control" id="address_detail" name="address_detail" required>
            </div>

            <div class="form-group">
                <label>Tọa độ địa điểm</label>
                <div id="coordinates" class="alert alert-info" style="display: none;">
                    <i class="fas fa-map-marker-alt"></i>
                    <span id="coordinates_text">Chưa có tọa độ</span>
                </div>
            </div>

            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input type="text" class="form-control" id="phone" name="phone" required>
            </div>

            <div class="form-group">
                <label for="category_id">Danh mục</label>
                <select class="form-control" id="category_id" name="category_id" required>
                    <option value="">-- Chọn danh mục --</option>
                    <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Tiện ích</label>
                <div class="row">
                    <div class="col-md-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="utility_wifi" name="utility_items[]" value="Wifi">
                            <label class="custom-control-label" for="utility_wifi">Wifi</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="utility_ac" name="utility_items[]" value="Điều hòa">
                            <label class="custom-control-label" for="utility_ac">Điều hòa</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="utility_water_heater" name="utility_items[]" value="Nóng lạnh">
                            <label class="custom-control-label" for="utility_water_heater">Nóng lạnh</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="utility_parking" name="utility_items[]" value="Gửi xe">
                            <label class="custom-control-label" for="utility_parking">Gửi xe</label>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="utilities" id="utilities">
            </div>

            <div class="form-group">
                <label for="banner_image">Ảnh banner</label>
                <input type="file" class="form-control-file" id="banner_image" name="banner_image" accept="image/*">
            </div>

            <div class="form-group">
                <label for="additional_images">Hình ảnh bổ sung</label>
                <input type="file" class="form-control-file" id="additional_images" name="additional_images[]" multiple accept="image/*">
            </div>

            <input type="hidden" name="district_name" value="Thành phố Vinh">
            <input type="hidden" name="province_name" value="Nghệ An">

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Thêm phòng trọ</button>
                <a href="manage_rooms.php" class="btn btn-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>

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
                    ['image', 'link'],
                    ['clean']
                ]
            }
        });

        // Cập nhật nội dung vào textarea khi submit form
        $('#roomForm').on('submit', function() {
            $('#description').val(quill.root.innerHTML);
        });

        // Cập nhật district_id khi chọn ward
        $('#ward').change(function() {
            var selectedOption = $(this).find('option:selected');
            $('#district_id').val(selectedOption.data('id'));
        });

        // Cập nhật utilities khi chọn tiện ích
        $('input[name="utility_items[]"]').change(function() {
            var selected = [];
            $('input[name="utility_items[]"]:checked').each(function() {
                selected.push($(this).val());
            });
            $('#utilities').val(selected.join(', '));
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

        // Hàm lấy tọa độ từ địa chỉ
        function getCoordinates() {
            var ward = $('#ward').val();
            var addressDetail = $('#address_detail').val();

            if (ward && addressDetail) {
                var fullAddress = addressDetail + ', ' + ward + ', Thành phố Vinh, Nghệ An';
                console.log('Full Address:', fullAddress);

                $.ajax({
                    url: '../api/maps/get_coordinates.php',
                    method: 'POST',
                    data: {
                        address: fullAddress
                    },
                    success: function(response) {
                        if (response.lat && response.lng) {
                            $('#coordinates').show();
                            $('#coordinates_text').text('Vĩ độ: ' + response.lat + ', Kinh độ: ' + response.lng);
                        } else {
                            $('#coordinates').hide();
                        }
                    },
                    error: function() {
                        $('#coordinates').hide();
                    }
                });
            } else {
                $('#coordinates').hide();
            }
        }

        // Tạo phiên bản debounced của hàm getCoordinates
        const debouncedGetCoordinates = debounce(getCoordinates, 1000);

        // Gọi hàm lấy tọa độ khi thay đổi phường hoặc địa chỉ
        $('#ward, #address_detail').on('change keyup', function() {
            debouncedGetCoordinates();
        });
    });
</script>

<?php include_once '../../Components/admin_footer.php'; ?>