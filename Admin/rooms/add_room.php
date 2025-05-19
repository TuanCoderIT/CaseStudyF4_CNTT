<?php
session_start();
require_once '../../config/db.php';

// Kiểm tra đăng nhập với quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header('Location: ../../Auth/login.php?message=Bạn cần đăng nhập với tài khoản admin');
    exit();
}

// Lấy thông tin danh mục
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

// Xử lý thêm phòng trọ
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
        $address_detail = mysqli_real_escape_string($conn, $_POST['address_detail']);
        $ward_name = mysqli_real_escape_string($conn, $_POST['ward_name']);
        $district_name = mysqli_real_escape_string($conn, $_POST['district_name']);
        $province_name = mysqli_real_escape_string($conn, $_POST['province_name']);
        // Tạo địa chỉ đầy đủ
        $address = $address_detail . ', ' . $ward_name . ', ' . $district_name . ', ' . $province_name;
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $category_id = (int)$_POST['category_id'];
        $district_id = (int)$_POST['district_id'];
        $utilities = mysqli_real_escape_string($conn, $_POST['utilities']);
        $user_id = $_SESSION['user_id'];

        // Lấy dữ liệu tọa độ địa lý
        $latitude = isset($_POST['latitude']) ? mysqli_real_escape_string($conn, $_POST['latitude']) : '';
        $longitude = isset($_POST['longitude']) ? mysqli_real_escape_string($conn, $_POST['longitude']) : '';
        $latlng = '';
        if (!empty($latitude) && !empty($longitude)) {
            $latlng = $latitude . ', ' . $longitude;
        }

        // Lấy tọa độ từ địa chỉ nếu được cung cấp
        $latlng = '';
        if (isset($_POST['latitude']) && isset($_POST['longitude'])) {
            $lat = (float)$_POST['latitude'];
            $lng = (float)$_POST['longitude'];
            if ($lat && $lng) {
                $latlng = "$lat, $lng";
            }
        }

        // Xử lý upload ảnh banner
        $banner_image = '';
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
                $banner_image = 'uploads/' . $file_name;
            }
        }

        // Bắt đầu transaction
        mysqli_begin_transaction($conn);

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
                                                VALUES ($motel_id, '$image_path', $i)";
                                mysqli_query($conn, $insert_image);
                            }
                        }
                    }
                }

                mysqli_commit($conn);
                $_SESSION['success'] = "Thêm phòng trọ thành công!";
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

$page_title = "Thêm phòng trọ mới";
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
<script src="../assets/js/validation/add_room_validation.js"></script>

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
                <label for="title"><i class="fas fa-heading mr-1"></i> Tiêu đề</label>
                <input type="text" class="form-control" id="title" name="title" required
                    placeholder="Nhập tiêu đề phòng trọ...">
                <small class="form-text text-muted">
                    Tiêu đề nên ngắn gọn, dễ hiểu và mô tả chính xác về phòng trọ
                </small>
            </div>

            <div class="form-group">
                <label for="description"><i class="fas fa-align-left mr-1"></i> Mô tả</label>
                <textarea class="form-control" id="description" name="description" rows="5"
                    placeholder="Mô tả chi tiết về phòng trọ..."></textarea>
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
                            <input type="number" class="form-control" id="price" name="price" required
                                placeholder="Nhập giá phòng...">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="area"><i class="fas fa-vector-square mr-1"></i> Diện tích (m²)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="area" name="area" required
                                placeholder="Nhập diện tích...">
                            <div class="input-group-append">
                                <span class="input-group-text">m²</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="province"><i class="fas fa-map-marker-alt mr-1"></i> Tỉnh/Thành phố</label>
                        <select class="form-control custom-select" id="province" required>
                            <option value="">-- Chọn Tỉnh/Thành phố --</option>
                        </select>
                        <input type="hidden" name="province_name" id="province_name">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="district"><i class="fas fa-map mr-1"></i> Quận/Huyện</label>
                        <select class="form-control custom-select" id="district" required>
                            <option value="">-- Chọn Quận/Huyện --</option>
                        </select>
                        <input type="hidden" name="district_name" id="district_name">
                        <input type="hidden" name="district_id" id="district_id">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="ward"><i class="fas fa-map-pin mr-1"></i> Phường/Xã</label>
                        <select class="form-control custom-select" id="ward" required>
                            <option value="">-- Chọn Phường/Xã --</option>
                        </select>
                        <input type="hidden" name="ward_name" id="ward_name">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="address_detail"><i class="fas fa-home mr-1"></i> Địa chỉ cụ thể</label>
                        <input type="text" class="form-control" id="address_detail" name="address_detail" required
                            placeholder="Số nhà, tên đường...">
                        <small class="form-text text-muted">
                            Vd: Số 10, đường Nguyễn Du
                        </small>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="phone"><i class="fas fa-phone-alt mr-1"></i> Số điện thoại liên hệ</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    </div>
                    <input type="text" class="form-control" id="phone" name="phone" required
                        placeholder="Nhập số điện thoại liên hệ...">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="category_id"><i class="fas fa-th-list mr-1"></i> Danh mục</label>
                        <select class="form-control custom-select" id="category_id" name="category_id" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-map-marked-alt mr-1"></i> Địa chỉ đầy đủ</label>
                        <p class="form-control-static bg-light p-2 rounded" id="full_address_preview">
                            <i class="text-muted">Địa chỉ sẽ hiển thị ở đây sau khi chọn đầy đủ thông tin</i>
                        </p>
                        <div class="input-group mt-2">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                            </div>
                            <input type="text" class="form-control" id="coordinates_display" readonly
                                placeholder="Tọa độ sẽ tự động hiển thị ở đây...">
                        </div>
                        <small class="text-muted">Tọa độ sẽ tự động cập nhật khi thông tin địa chỉ thay đổi</small>
                        <!-- Trường ẩn để lưu trữ tọa độ địa lý -->
                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">
                        <div id="geocode_status" class="mt-2"></div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="utilities"><i class="fas fa-tools mr-1"></i> Tiện ích</label>
                <input type="text" class="form-control" id="utilities" name="utilities"
                    placeholder="Wifi, Máy giặt, Nhà bếp, Điều hòa, Nóng lạnh,...">
                <small class="form-text text-muted">
                    Các tiện ích ngăn cách bởi dấu phẩy, giúp hiển thị các điểm nổi bật của phòng trọ
                </small>
            </div>

            <!-- Map hiển thị vị trí -->
            <div class="form-group">
                <label><i class="fas fa-map mr-1"></i> Bản đồ vị trí</label>
                <div id="map_manual_select" style="display: none;" class="mb-2">
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
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="banner_image" name="banner_image" accept="image/*" onchange="previewBannerImage(this);">
                            <label class="custom-file-label" for="banner_image">Chọn ảnh banner...</label>
                        </div>
                        <small class="form-text text-muted">
                            Ảnh banner sẽ hiển thị nổi bật trong danh sách tìm kiếm.
                        </small>
                        <div class="mt-2 banner-preview" style="display: none;">
                            <img id="banner_preview" src="" alt="Banner Preview" class="img-fluid rounded" style="max-height: 200px;">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="additional_images"><i class="fas fa-images mr-1"></i> Hình ảnh bổ sung</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="additional_images" name="additional_images[]" multiple accept="image/*" onchange="previewAdditionalImages(this);">
                            <label class="custom-file-label" for="additional_images">Chọn nhiều hình ảnh...</label>
                        </div>
                        <small class="form-text text-muted">
                            Bạn có thể chọn nhiều hình ảnh để hiển thị chi tiết về phòng trọ.
                        </small>
                        <div class="mt-2 row additional-images-preview" id="additional_images_preview">
                            <!-- Các ảnh xem trước sẽ được hiển thị ở đây -->
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-lg" id="submitButton">
                    <i class="fas fa-save mr-2"></i> Thêm phòng trọ
                </button>
                <a href="manage_rooms.php" class="btn btn-secondary btn-lg ml-2">
                    <i class="fas fa-times mr-2"></i> Hủy
                </a>
            </div>

            <!-- Thông báo lỗi -->
            <div class="alert alert-danger mt-3" id="coordinatesError" style="display: none;">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Bạn cần phải xác định vị trí trên bản đồ trước khi thêm phòng trọ
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

    // Xử lý địa chỉ từ API
    $(document).ready(function() {
        // Xử lý hiển thị bản đồ thủ công
        $('#show_map_manual').on('click', function() {
            // Vị trí mặc định (có thể đặt một vị trí của Việt Nam)
            var defaultLat = 10.762622; // TP. Hồ Chí Minh 
            var defaultLng = 106.660172;

            // Hiển thị bản đồ với vị trí mặc định
            $('#map').show();
            $('#map_message').hide();
            initMap(defaultLat, defaultLng);

            // Thông báo hướng dẫn
            $('#geocode_status').html(
                '<div class="alert alert-info">' +
                '<i class="fas fa-info-circle"></i> Hãy click vào vị trí phòng trọ trên bản đồ để chọn tọa độ.' +
                '</div>'
            );
        });

        // Lấy danh sách tỉnh/thành phố
        $.ajax({
            url: '../api/location/get_location_data.php?action=get_provinces',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('API response:', response);
                var provinceSelect = $('#province');
                if (Array.isArray(response)) {
                    $.each(response, function(index, province) {
                        provinceSelect.append('<option value="' + province.code + '" data-name="' + province.name + '">' + province.name + '</option>');
                    });
                } else {
                    console.error('Unexpected response format:', response);
                    alert('Định dạng dữ liệu không đúng từ API tỉnh/thành phố.');
                }
            },
            error: function(error) {
                console.error('Lỗi khi lấy danh sách tỉnh/thành phố:', error);
                alert('Không thể tải danh sách tỉnh/thành phố. Vui lòng làm mới trang và thử lại.');
            }
        });

        // Khi chọn tỉnh/thành phố
        $('#province').on('change', function() {
            var provinceCode = $(this).val();
            var provinceName = $(this).find('option:selected').data('name');
            $('#province_name').val(provinceName);

            // Reset các dropdown phụ thuộc
            $('#district').html('<option value="">-- Chọn Quận/Huyện --</option>');
            $('#ward').html('<option value="">-- Chọn Phường/Xã --</option>');
            $('#district_name, #ward_name').val('');

            updateFullAddressPreview();

            if (provinceCode) {
                // Lấy danh sách quận/huyện
                $.ajax({
                    url: '../api/location/get_location_data.php?action=get_districts&province_code=' + provinceCode,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('API district response:', response);
                        var districtSelect = $('#district');
                        if (response.districts && response.districts.length > 0) {
                            $.each(response.districts, function(index, district) {
                                districtSelect.append('<option value="' + district.code + '" data-name="' + district.name + '">' + district.name + '</option>');
                            });
                        }
                    },
                    error: function(error) {
                        console.error('Lỗi khi lấy danh sách quận/huyện:', error);
                        alert('Không thể tải danh sách quận/huyện. Vui lòng thử lại sau.');
                    }
                });
            }
        });

        // Khi chọn quận/huyện
        $('#district').on('change', function() {
            var districtCode = $(this).val();
            var districtName = $(this).find('option:selected').data('name');
            $('#district_name').val(districtName);

            // Gọi API để tìm hoặc tạo district_id trong database
            $.ajax({
                url: '../api/location/get_location_data.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_district_id',
                    district_name: districtName
                },
                success: function(response) {
                    if (response.success && response.district_id) {
                        $('#district_id').val(response.district_id);
                    } else {
                        console.error('Không thể lấy district_id:', response.message);
                        $('#district_id').val(1); // Giá trị mặc định, hãy thay đổi nếu cần
                    }
                },
                error: function(error) {
                    console.error('Lỗi khi lấy district_id:', error);
                    $('#district_id').val(1); // Giá trị mặc định, hãy thay đổi nếu cần
                }
            });

            // Reset dropdown phường/xã
            $('#ward').html('<option value="">-- Chọn Phường/Xã --</option>');
            $('#ward_name').val('');

            updateFullAddressPreview();

            if (districtCode) {
                // Lấy danh sách phường/xã
                $.ajax({
                    url: '../api/location/get_location_data.php?action=get_wards&district_code=' + districtCode,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('API ward response:', response);
                        var wardSelect = $('#ward');
                        if (response.wards && response.wards.length > 0) {
                            $.each(response.wards, function(index, ward) {
                                wardSelect.append('<option value="' + ward.code + '" data-name="' + ward.name + '">' + ward.name + '</option>');
                            });
                        }
                    },
                    error: function(error) {
                        console.error('Lỗi khi lấy danh sách phường/xã:', error);
                        alert('Không thể tải danh sách phường/xã. Vui lòng thử lại sau.');
                    }
                });
            }
        });

        // Khi chọn phường/xã
        $('#ward').on('change', function() {
            var wardName = $(this).find('option:selected').data('name');
            $('#ward_name').val(wardName);
            updateFullAddressPreview();
        });

        // Khi nhập địa chỉ chi tiết
        $('#address_detail').on('input', function() {
            updateFullAddressPreview();
        });

        // Hàm cập nhật xem trước địa chỉ đầy đủ và lấy tọa độ
        function updateFullAddressPreview() {
            var addressDetail = $('#address_detail').val().trim();
            var wardName = $('#ward_name').val();
            var districtName = $('#district_name').val();
            var provinceName = $('#province_name').val();

            var parts = [];
            if (addressDetail) parts.push(addressDetail);
            if (wardName) parts.push(wardName);
            if (districtName) parts.push(districtName);
            if (provinceName) parts.push(provinceName);

            var fullAddress = parts.join(', ');

            if (parts.length > 0) {
                $('#full_address_preview').html(fullAddress);

                // Nếu đã nhập đủ thông tin địa chỉ (có ít nhất 3 phần), tự động lấy tọa độ
                if (parts.length >= 3) {
                    getCoordinatesFromAddress(fullAddress);
                }
            } else {
                $('#full_address_preview').html('<i class="text-muted">Địa chỉ sẽ hiển thị ở đây sau khi chọn đầy đủ thông tin</i>');
                $('#coordinates_display').val('');
                $('#latitude').val('');
                $('#longitude').val('');
            }
        }

        // Hàm lấy tọa độ từ địa chỉ
        function getCoordinatesFromAddress(address) {
            $('#geocode_status').html('<div class="text-info"><i class="fas fa-spinner fa-spin"></i> Đang lấy tọa độ...</div>');

            $.ajax({
                url: '../api/maps/get_coordinates.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    address_detail: $('#address_detail').val(),
                    ward_name: $('#ward_name').val(),
                    district_name: $('#district_name').val(),
                    province_name: $('#province_name').val()
                },
                success: function(response) {
                    if (response.success) {
                        $('#coordinates_display').val(response.lat + ', ' + response.lng);
                        $('#latitude').val(response.lat);
                        $('#longitude').val(response.lng);
                        $('#geocode_status').html('<div class="text-success"><i class="fas fa-check-circle"></i> Đã lấy tọa độ thành công!</div>');

                        // Hiển thị bản đồ với vị trí đã chọn
                        $('#map').show();
                        initMap(response.lat, response.lng);

                        // Sau 3 giây, ẩn thông báo
                        setTimeout(function() {
                            $('#geocode_status').html('');
                        }, 3000);
                    } else {
                        $('#coordinates_display').val('Không tìm thấy tọa độ');
                        $('#latitude').val('');
                        $('#longitude').val('');
                        $('#geocode_status').html(
                            '<div class="text-danger mb-2"><i class="fas fa-exclamation-circle"></i> ' + response.message + '</div>' +
                            '<div class="alert alert-info">' +
                            '<i class="fas fa-info-circle"></i> Bạn vẫn có thể chọn vị trí thủ công trên bản đồ bên dưới. ' +
                            'Click vào nút <strong>Hiển thị bản đồ để chọn vị trí</strong> và click vào vị trí mong muốn.' +
                            '</div>'
                        );

                        // Hiển thị nút chọn thủ công
                        $('#map_manual_select').show();
                    }
                },
                error: function() {
                    $('#coordinates_display').val('Lỗi khi lấy tọa độ');
                    $('#latitude').val('');
                    $('#longitude').val('');
                    $('#geocode_status').html(
                        '<div class="text-danger mb-2"><i class="fas fa-exclamation-circle"></i> Lỗi kết nối khi lấy tọa độ</div>' +
                        '<div class="alert alert-info">' +
                        '<i class="fas fa-info-circle"></i> Vui lòng kiểm tra kết nối mạng hoặc thử lại sau. ' +
                        'Bạn cũng có thể chọn vị trí thủ công trên bản đồ.' +
                        '</div>'
                    );

                    // Hiển thị nút chọn thủ công
                    $('#map_manual_select').show();
                }
            });
        }

        // Khởi tạo bản đồ
        var marker; // Biến global để lưu marker
        var map; // Biến global để lưu bản đồ

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
        }

        // Hàm cập nhật giá trị tọa độ
        function updateCoordinates(lat, lng) {
            lat = parseFloat(lat).toFixed(6);
            lng = parseFloat(lng).toFixed(6);
            $('#coordinates_display').val(lat + ', ' + lng);
            $('#latitude').val(lat);
            $('#longitude').val(lng);
            $('#geocode_status').html('<div class="text-success"><i class="fas fa-check-circle"></i> Đã cập nhật tọa độ thủ công!</div>');

            // Sau 3 giây, ẩn thông báo
            setTimeout(function() {
                $('#geocode_status').html('');
            }, 3000);
        }
    });
</script>

<?php include_once '../../Components/admin_footer.php'; ?>