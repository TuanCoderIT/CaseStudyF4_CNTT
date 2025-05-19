<?php
session_start();
require_once '../../config/db.php';

// Kiểm tra đăng nhập với quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header('Location: ../../Auth/login.php?message=Bạn cần đăng nhập với tài khoản admin');
    exit();
}

// Kiểm tra ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: manage_rooms.php?error=ID không hợp lệ');
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Lấy thông tin phòng trọ
$query = "SELECT * FROM motel WHERE id = '$id'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    header('Location: manage_rooms.php?error=Không tìm thấy phòng trọ');
    exit();
}

$room = mysqli_fetch_assoc($result);

// Lấy thông tin các hình ảnh phụ
$query_images = "SELECT * FROM motel_images WHERE motel_id = '$id' ORDER BY display_order";
$result_images = mysqli_query($conn, $query_images);

// Lấy thông tin danh mục và quận/huyện
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");
$districts = mysqli_query($conn, "SELECT * FROM districts ORDER BY name");

// Xử lý cập nhật phòng trọ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Kiểm tra tọa độ đã được cung cấp chưa
    if (empty($_POST['latitude']) || empty($_POST['longitude'])) {
        $error = "Lỗi: Bạn cần phải xác định tọa độ vị trí của phòng trọ trước khi lưu";
    } else {
        // Lấy dữ liệu từ form
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $price = (int)$_POST['price'];
        $area = (int)$_POST['area'];
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $category_id = (int)$_POST['category_id'];
        $district_id = (int)$_POST['district_id'];
        $utilities = mysqli_real_escape_string($conn, $_POST['utilities']);
        $approve = (int)$_POST['approve'];

        // Lấy tọa độ từ form
        $latitude = (float)$_POST['latitude'];
        $longitude = (float)$_POST['longitude'];
        $latlng = $latitude . ', ' . $longitude;

        $image_path = $room['images'];

        // Bắt đầu transaction
        mysqli_begin_transaction($conn);

        try {
            // Xử lý upload ảnh banner mới nếu có
            if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == 0) {
                $upload_dir = '../../uploads/';

                // Tạo thư mục nếu chưa tồn tại
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $file_name = time() . '_' . $_FILES['banner_image']['name'];
                $target_file = $upload_dir . $file_name;

                // Di chuyển file tạm vào thư mục đích
                if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $target_file)) {
                    // Xóa ảnh cũ nếu có
                    if (!empty($room['images']) && file_exists('../../' . $room['images'])) {
                        unlink('../../' . $room['images']);
                    }

                    $image_path = 'uploads/' . $file_name;
                }
            }

            // Cập nhật phòng trọ trong database
            $update_query = "UPDATE motel SET 
                            title = '$title',
                            description = '$description',
                            price = '$price',
                            area = '$area',
                            address = '$address',
                            phone = '$phone',
                            category_id = '$category_id',
                            district_id = '$district_id',
                            utilities = '$utilities',
                            images = '$image_path',
                            approve = '$approve',
                            latitude = '$latitude',
                            longitude = '$longitude',
                            latlng = '$latlng'
                            WHERE id = '$id'";

            if (mysqli_query($conn, $update_query)) {
                // Xử lý upload nhiều hình ảnh mới nếu có
                if (isset($_FILES['additional_images']) && count($_FILES['additional_images']['name']) > 0) {
                    $upload_dir = '../../uploads/rooms/';

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
                                                VALUES ($id, '$image_path', $i)";
                                mysqli_query($conn, $insert_image);
                            }
                        }
                    }
                }

                // Xóa hình ảnh nếu được yêu cầu
                if (isset($_POST['delete_image']) && is_array($_POST['delete_image'])) {
                    foreach ($_POST['delete_image'] as $image_id) {
                        $image_id = (int)$image_id;

                        // Lấy thông tin ảnh trước khi xóa
                        $query = "SELECT image_path FROM motel_images WHERE id = $image_id AND motel_id = $id";
                        $result = mysqli_query($conn, $query);

                        if ($result && $row = mysqli_fetch_assoc($result)) {
                            $image_path = $row['image_path'];

                            // Xóa file ảnh
                            if (file_exists('../../' . $image_path)) {
                                unlink('../../' . $image_path);
                            }

                            // Xóa record trong database
                            mysqli_query($conn, "DELETE FROM motel_images WHERE id = $image_id");
                        }
                    }
                }

                mysqli_commit($conn);
                $_SESSION['success'] = "Cập nhật phòng trọ thành công!";
                header('Location: manage_rooms.php');
                exit();
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Lỗi: " . $e->getMessage();
        }
    }
}

$page_title = "Cập nhật phòng trọ";
include_once '../../Components/admin_header.php';
?>

<!-- Include Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css"
    integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A=="
    crossorigin="" />

<!-- Include Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"
    integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA=="
    crossorigin=""></script>

<!-- Include form validation script -->
<script src="../assets/js/validation/edit_room_validation.js"></script>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2><i class="fas fa-edit mr-2"></i> Cập nhật phòng trọ</h2>
        <div>
            <a href="manage_rooms.php" class="btn btn-outline-secondary mr-2">
                <i class="fas fa-list mr-1"></i> Danh sách phòng
            </a>
            <a href="../Home/room_detail.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-info">
                <i class="fas fa-eye mr-1"></i> Xem phòng
            </a>
        </div>
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
        <h5 class="m-0 font-weight-bold"><i class="fas fa-home mr-2"></i>Thông tin phòng trọ</h5>
    </div>
    <div class="card-body">

        <form method="POST" enctype="multipart/form-data" id="roomForm">
            <div class="form-group">
                <label for="title"><i class="fas fa-heading mr-1"></i> Tiêu đề</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($room['title']); ?>" required>
                <small class="form-text text-muted">
                    Tiêu đề nên ngắn gọn, dễ hiểu và mô tả chính xác về phòng trọ
                </small>
            </div>

            <div class="form-group">
                <label for="description"><i class="fas fa-align-left mr-1"></i> Mô tả</label>
                <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($room['description']); ?></textarea>
                <small class="form-text text-muted">
                    Mô tả đầy đủ về phòng trọ để người tìm kiếm có thông tin chi tiết
                </small>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="price"><i class="fas fa-money-bill-wave mr-1"></i> Giá (VNĐ)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">₫</span>
                            </div>
                            <input type="number" class="form-control" id="price" name="price" value="<?php echo $room['price']; ?>" required>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="area"><i class="fas fa-vector-square mr-1"></i> Diện tích (m²)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="area" name="area" value="<?php echo $room['area']; ?>" required>
                            <div class="input-group-append">
                                <span class="input-group-text">m²</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="address"><i class="fas fa-map-marker-alt mr-1"></i> Địa chỉ</label>
                <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($room['address']); ?>" required>
                <small class="form-text text-muted">
                    Cần nhập địa chỉ chính xác để người thuê dễ dàng tìm kiếm
                </small>

                <div class="input-group mt-2">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                    </div>
                    <input type="text" class="form-control" id="coordinates_display"
                        value="<?php echo htmlspecialchars($room['latlng']); ?>" readonly
                        placeholder="Tọa độ sẽ hiển thị ở đây...">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="geocode_address">
                            <i class="fas fa-search-location"></i> Tìm tọa độ
                        </button>
                    </div>
                </div>
                <!-- Trường ẩn để lưu trữ tọa độ địa lý -->
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="latlng" id="latlng" value="<?php echo htmlspecialchars($room['latlng']); ?>">
                <div id="geocode_status" class="mt-2"></div>
            </div>

            <div class="form-group">
                <label for="phone"><i class="fas fa-phone-alt mr-1"></i> Số điện thoại liên hệ</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    </div>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($room['phone']); ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="category_id"><i class="fas fa-th-list mr-1"></i> Danh mục</label>
                        <select class="form-control custom-select" id="category_id" name="category_id" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php mysqli_data_seek($categories, 0); ?>
                            <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php if ($cat['id'] == $room['category_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="district_id"><i class="fas fa-map mr-1"></i> Khu vực</label>
                        <select class="form-control custom-select" id="district_id" name="district_id" required>
                            <option value="">-- Chọn khu vực --</option>
                            <?php mysqli_data_seek($districts, 0); ?>
                            <?php while ($district = mysqli_fetch_assoc($districts)): ?>
                                <option value="<?php echo $district['id']; ?>" <?php if ($district['id'] == $room['district_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($district['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="utilities">Tiện ích (ngăn cách bởi dấu phẩy)</label>
                <input type="text" class="form-control" id="utilities" name="utilities" value="<?php echo $room['utilities']; ?>" placeholder="Wifi, Máy giặt, Nhà bếp,...">
            </div>

            <div class="form-group">
                <label for="approve">Trạng thái</label>
                <select class="form-control" id="approve" name="approve">
                    <option value="1" <?php if ($room['approve'] == 1) echo 'selected'; ?>>Đã duyệt</option>
                    <option value="0" <?php if ($room['approve'] == 0) echo 'selected'; ?>>Chưa duyệt</option>
                </select>
            </div>

            <!-- Map hiển thị vị trí -->
            <div class="form-group">
                <label><i class="fas fa-map mr-1"></i> Bản đồ vị trí</label>
                <div id="map_manual_select" class="mb-2">
                    <button type="button" class="btn btn-primary" id="show_map_manual">
                        <i class="fas fa-map-marker-alt mr-1"></i> Hiển thị bản đồ để chọn vị trí
                    </button>
                </div>
                <div id="map" style="height: 300px; width: 100%; border-radius: 5px; display: none;"></div>
                <div id="map_message" class="alert alert-info mt-2">
                    <i class="fas fa-info-circle mr-1"></i> Bản đồ sẽ hiển thị sau khi tọa độ được xác định
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="banner_image"><i class="fas fa-image mr-1"></i> Ảnh banner chính</label>
                        <?php if (!empty($room['images'])): ?>
                            <div class="mb-2">
                                <img src="../../<?php echo $room['images']; ?>" class="img-thumbnail" style="max-height: 200px;">
                            </div>
                        <?php endif; ?>
                        <div class="custom-file mt-2">
                            <input type="file" class="custom-file-input" id="banner_image" name="banner_image" accept="image/*" onchange="previewBannerImage(this);">
                            <label class="custom-file-label" for="banner_image">Thay đổi ảnh banner...</label>
                        </div>
                        <small class="form-text text-muted">Để trống nếu không muốn thay đổi ảnh banner</small>
                        <div class="mt-2 banner-preview" style="display: none;">
                            <img id="banner_preview" src="" alt="Banner Preview" class="img-fluid rounded" style="max-height: 200px;">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="additional_images"><i class="fas fa-images mr-1"></i> Thêm hình ảnh mới</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="additional_images" name="additional_images[]" multiple accept="image/*" onchange="previewAdditionalImages(this);">
                            <label class="custom-file-label" for="additional_images">Chọn thêm hình ảnh...</label>
                        </div>
                        <small class="form-text text-muted">Bạn có thể chọn nhiều hình ảnh để thêm vào phòng trọ</small>
                        <div class="mt-2 row additional-images-preview" id="additional_images_preview">
                            <!-- Các ảnh xem trước sẽ được hiển thị ở đây -->
                        </div>
                    </div>
                </div>
            </div>

            <?php if (mysqli_num_rows($result_images) > 0): ?>
                <div class="form-group">
                    <label><i class="fas fa-images mr-1"></i> Các hình ảnh hiện có</label>
                    <div class="row">
                        <?php while ($image = mysqli_fetch_assoc($result_images)): ?>
                            <div class="col-md-3 mb-3">
                                <div class="card h-100">
                                    <img src="../<?php echo $image['image_path']; ?>" class="card-img-top" style="height: 150px; object-fit: cover;">
                                    <div class="card-body p-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="delete_image_<?php echo $image['id']; ?>" name="delete_image[]" value="<?php echo $image['id']; ?>">
                                            <label class="custom-control-label text-danger" for="delete_image_<?php echo $image['id']; ?>">
                                                <i class="fas fa-trash"></i> Xóa ảnh
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <small class="form-text text-muted">Đánh dấu vào các ảnh bạn muốn xóa</small>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="latitude"><i class="fas fa-map-marker-alt mr-1"></i> Tọa độ trên bản đồ</label>
                <div class="row">
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="latitude" name="latitude" value="<?php echo $room['latitude']; ?>" placeholder="Nhập vĩ độ">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="longitude" name="longitude" value="<?php echo $room['longitude']; ?>" placeholder="Nhập kinh độ">
                    </div>
                </div>
                <small class="form-text text-muted">
                    Nhập tọa độ chính xác để hiển thị đúng vị trí trên bản đồ. <a href="https://www.latlong.net/" target="_blank">Lấy tọa độ tại đây</a>
                </small>
            </div>

            <div class="form-group">
                <label for="map_iframe"><i class="fas fa-map mr-1"></i> Bản đồ vị trí</label>
                <textarea class="form-control" id="map_iframe" name="map_iframe" rows="3" readonly><?php echo htmlspecialchars($room['map_iframe']); ?></textarea>
                <small class="form-text text-muted">
                    Nhúng bản đồ vị trí của phòng trọ. Sử dụng iframe từ Google Maps.
                </small>
            </div>

            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary" id="submitButton">
                    <i class="fas fa-save mr-2"></i> Cập nhật phòng trọ
                </button>
                <a href="manage_rooms.php" class="btn btn-secondary ml-2">
                    <i class="fas fa-times mr-2"></i> Quay lại
                </a>
            </div>

            <!-- Thông báo lỗi -->
            <div class="alert alert-danger mt-3" id="coordinatesError" style="display: none;">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Bạn cần phải xác định vị trí trên bản đồ trước khi cập nhật phòng trọ
            </div>
        </form>
    </div>
</div>
</div>

<script>
    // Hiển thị tên file đã chọn
    $(".custom-file-input").on("change", function() {
        var fileName = $(this).val().split("\\").pop();
        $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
    });

    // Xem trước ảnh banner
    function previewBannerImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function(e) {
                $('#banner_preview').attr('src', e.target.result);
                $('.banner-preview').show();
            }

            reader.readAsDataURL(input.files[0]);
        }
    }

    // Xem trước nhiều ảnh
    function previewAdditionalImages(input) {
        var preview = $('#additional_images_preview');
        preview.empty();

        if (input.files) {
            var filesAmount = input.files.length;

            for (i = 0; i < filesAmount; i++) {
                var reader = new FileReader();

                reader.onload = function(event) {
                    $($.parseHTML('<div class="col-md-4 mb-2"><img src="' + event.target.result + '" class="img-fluid rounded" style="height: 150px; object-fit: cover;"></div>')).appendTo(preview);
                }

                reader.readAsDataURL(input.files[i]);
            }
        }
    }

    // Biến để lưu trữ marker và bản đồ
    var marker;
    var map;

    $(document).ready(function() {
        // Khởi tạo tọa độ từ giá trị trong db
        var latlng = $('#latlng').val();
        if (latlng) {
            var coordinates = latlng.split(',');
            if (coordinates.length === 2) {
                var lat = parseFloat(coordinates[0].trim());
                var lng = parseFloat(coordinates[1].trim());

                if (!isNaN(lat) && !isNaN(lng)) {
                    $('#latitude').val(lat);
                    $('#longitude').val(lng);

                    // Hiển thị bản đồ ngay khi trang tải
                    setTimeout(function() {
                        initMap(lat, lng);
                    }, 500);
                }
            }
        }

        // Xử lý nút tìm tọa độ
        $('#geocode_address').on('click', function() {
            var address = $('#address').val();
            if (address) {
                getCoordinatesFromAddress(address);
            } else {
                alert('Vui lòng nhập địa chỉ trước khi tìm tọa độ!');
            }
        });

        // Xử lý nút hiển thị bản đồ thủ công
        $('#show_map_manual').on('click', function() {
            // Nếu đã có tọa độ, sử dụng tọa độ đó
            var lat = $('#latitude').val();
            var lng = $('#longitude').val();

            if (!lat || !lng) {
                // Vị trí mặc định (TP. Hồ Chí Minh)
                lat = 10.762622;
                lng = 106.660172;
            }

            // Hiển thị bản đồ với vị trí
            initMap(parseFloat(lat), parseFloat(lng));

            // Thông báo hướng dẫn
            $('#geocode_status').html(
                '<div class="alert alert-info">' +
                '<i class="fas fa-info-circle"></i> Hãy click vào vị trí phòng trọ trên bản đồ để chọn tọa độ.' +
                '</div>'
            );
        });
    });

    // Hàm lấy tọa độ từ địa chỉ
    function getCoordinatesFromAddress(address) {
        $('#geocode_status').html('<div class="text-info"><i class="fas fa-spinner fa-spin"></i> Đang lấy tọa độ...</div>');

        $.ajax({
            url: '../api/maps/get_coordinates.php',
            type: 'POST',
            dataType: 'json',
            data: {
                address: address
            },
            success: function(response) {
                if (response.success) {
                    $('#coordinates_display').val(response.lat + ', ' + response.lng);
                    $('#latitude').val(response.lat);
                    $('#longitude').val(response.lng);
                    $('#geocode_status').html('<div class="text-success"><i class="fas fa-check-circle"></i> Đã lấy tọa độ thành công!</div>');

                    // Hiển thị bản đồ với vị trí đã chọn
                    initMap(parseFloat(response.lat), parseFloat(response.lng));

                } else {
                    $('#geocode_status').html(
                        '<div class="text-danger mb-2"><i class="fas fa-exclamation-circle"></i> ' + response.message + '</div>' +
                        '<div class="alert alert-info">' +
                        '<i class="fas fa-info-circle"></i> Bạn vẫn có thể chọn vị trí thủ công trên bản đồ bên dưới bằng cách click vào bản đồ.' +
                        '</div>'
                    );

                    // Hiển thị bản đồ với vị trí mặc định nếu không có tọa độ
                    initMap(10.762622, 106.660172); // TP. Hồ Chí Minh
                }
            },
            error: function() {
                $('#coordinates_display').val('Lỗi khi lấy tọa độ');
                $('#geocode_status').html(
                    '<div class="text-danger mb-2"><i class="fas fa-exclamation-circle"></i> Lỗi kết nối khi lấy tọa độ</div>' +
                    '<div class="alert alert-info">' +
                    '<i class="fas fa-info-circle"></i> Vui lòng kiểm tra kết nối mạng hoặc thử lại sau. ' +
                    'Bạn cũng có thể chọn vị trí thủ công trên bản đồ.' +
                    '</div>'
                );
            }
        });
    }

    // Khởi tạo bản đồ
    function initMap(lat, lng) {
        // Nếu bản đồ đã được khởi tạo, xóa và tạo lại
        if (map) {
            map.remove();
        }

        // Hiển thị bản đồ
        $('#map').show();
        $('#map_message').hide();

        map = L.map('map').setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Thêm marker vào bản đồ
        marker = L.marker([lat, lng], {
                draggable: true
            }).addTo(map)
            .bindPopup('Vị trí của phòng trọ. Kéo để thay đổi vị trí.')
            .openPopup();

        // Xử lý sự kiện khi kéo marker
        marker.on('dragend', function(event) {
            var position = marker.getLatLng();
            updateCoordinates(position.lat, position.lng);
        });

        // Xử lý sự kiện khi click vào bản đồ
        map.on('click', function(e) {
            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng, {
                    draggable: true
                }).addTo(map);
            }
            updateCoordinates(e.latlng.lat, e.latlng.lng);
        });

        // Cập nhật kích thước bản đồ sau khi hiển thị
        setTimeout(function() {
            map.invalidateSize();
        }, 100);
    }

    // Hàm cập nhật giá trị tọa độ
    function updateCoordinates(lat, lng) {
        lat = parseFloat(lat).toFixed(6);
        lng = parseFloat(lng).toFixed(6);
        $('#coordinates_display').val(lat + ', ' + lng);
        $('#latitude').val(lat);
        $('#longitude').val(lng);
        $('#latlng').val(lat + ', ' + lng);
        $('#geocode_status').html('<div class="text-success"><i class="fas fa-check-circle"></i> Đã cập nhật tọa độ thủ công!</div>');

        // Sau 3 giây, ẩn thông báo
        setTimeout(function() {
            $('#geocode_status').html('');
        }, 3000);
    }
</script>

<?php include_once '../../Components/admin_footer.php'; ?>