<?php
session_start();
require_once '../../config/db.php';
require_once '../../configs/config.php';

// Kiểm tra đăng nhập với quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header('Location: ../../auth/login.php?message=Bạn cần đăng nhập với tài khoản admin');
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
    $default_deposit = isset($_POST['default_deposit']) ? (int)$_POST['default_deposit'] : 0;
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

    // Xử lý tọa độ
    $lat = isset($_POST['lat']) ? mysqli_real_escape_string($conn, $_POST['lat']) : '';
    $lng = isset($_POST['lng']) ? mysqli_real_escape_string($conn, $_POST['lng']) : '';
    $latlng = (!empty($lat) && !empty($lng)) ? $lat . ',' . $lng : '';

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
        $query = "INSERT INTO motel (title, description, price, default_deposit, area, address, latlng, phone, 
                                category_id, district_id, utilities, user_id, images, approve)
                VALUES ('$title', '$description', '$price', '$default_deposit', '$area', '$address', '$latlng', '$phone',
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
<!-- Leaflet CSS and JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

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

    .custom-control-input:checked~.custom-control-label::before {
        background-color: #4e73df;
        border-color: #4e73df;
    }

    .form-control:focus {
        border-color: #bac8f3;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }

    .btn-primary {
        background-color: #4e73df;
        border-color: #4e73df;
    }

    .btn-primary:hover {
        background-color: #2e59d9;
        border-color: #2653d4;
    }

    #map {
        transition: all 0.3s ease;
        border: 2px solid #4e73df;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        height: 350px !important;
        z-index: 1;
        /* Ensure map is above other elements */
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
        /* Ensure it's above the map */
        background: rgba(255, 255, 255, 0.8);
        padding: 8px 12px;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        font-size: 13px;
        max-width: 250px;
        pointer-events: none;
        /* Allow clicking through to map */
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

    .map-error {
        color: #dc3545;
        padding: 10px;
        text-align: center;
    }

    /* Leaflet specific styles */
    .leaflet-popup-content {
        font-size: 14px;
        line-height: 1.4;
    }

    .leaflet-popup-content-wrapper {
        border-left: 3px solid #4e73df;
    }
</style>

<div class="card shadow">
    <div class="card-header py-3 bg-gradient-primary text-black">
        <h5 class="m-0 font-weight-bold"><i class="fas fa-edit mr-2"></i>Thông tin phòng trọ</h5>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="roomForm">
            <!-- THÔNG TIN CƠ BẢN -->
            <div class="form-section">
                <h5 class="section-title"><i class="fas fa-info-circle mr-2"></i>Thông tin cơ bản</h5>
                <div class="form-group">
                    <label for="title"><i class="fas fa-file-signature text-primary mr-1"></i> Tiêu đề</label>
                    <input type="text" class="form-control" id="title" name="title" placeholder="Nhập tiêu đề cho phòng trọ" required>
                </div>

                <div class="form-group">
                    <label for="description"><i class="fas fa-align-left text-primary mr-1"></i> Mô tả</label>
                    <div id="editor-container" style="height: 300px; border-radius: 4px;"></div>
                    <textarea name="description" id="description" style="display: none;"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="price"><i class="fas fa-money-bill-wave text-primary mr-1"></i> Giá (VNĐ)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">₫</span>
                                </div>
                                <input type="number" class="form-control" id="price" name="price" placeholder="Giá thuê hàng tháng" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="default_deposit"><i class="fas fa-hand-holding-usd text-primary mr-1"></i> Tiền cọc (VNĐ)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">₫</span>
                                </div>
                                <input type="number" class="form-control" id="default_deposit" name="default_deposit" placeholder="Tiền đặt cọc">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="area"><i class="fas fa-vector-square text-primary mr-1"></i> Diện tích (m²)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="area" name="area" placeholder="Diện tích phòng" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">m²</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="category_id"><i class="fas fa-list text-primary mr-1"></i> Danh mục</label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php
                            // mysqli_data_seek($categories, offset: 0); // Reset pointer to beginning
                            while ($cat = mysqli_fetch_assoc($categories)): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ĐỊA CHỈ & VỊ TRÍ -->
            <div class="form-section">
                <h5 class="section-title"><i class="fas fa-map-marked-alt mr-2"></i>Địa chỉ & Vị trí</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ward"><i class="fas fa-map text-primary mr-1"></i> Phường/Xã</label>
                            <select class="form-control" id="ward" name="ward_name" required>
                                <option value="">-- Chọn Phường/Xã --</option>
                                <?php
                                mysqli_data_seek($address, 0); // Reset pointer to beginning
                                while ($cat = mysqli_fetch_assoc($address)): ?>
                                    <option value="<?php echo $cat['name']; ?>" data-id="<?php echo $cat['id']; ?>">
                                        <?php echo $cat['name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <input type="hidden" name="district_id" id="district_id">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="address_detail"><i class="fas fa-home text-primary mr-1"></i> Địa chỉ chi tiết</label>
                            <input type="text" class="form-control" id="address_detail" name="address_detail" placeholder="Ví dụ: Số 123 Đường XYZ" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-map-pin text-primary mr-1"></i> Tọa độ địa điểm</label>
                    <div class="btn-group d-flex mb-3">
                        <button class="btn btn-outline-primary" type="button" id="get_ip_location" title="Lấy vị trí từ IP">
                            <i class="fas fa-location-arrow mr-1"></i> Lấy vị trí hiện tại từ IP
                        </button>
                        <button class="btn btn-outline-info" type="button" id="get_browser_location" title="Lấy vị trí từ trình duyệt">
                            <i class="fas fa-crosshairs mr-1"></i> Lấy vị trí từ trình duyệt
                        </button>
                    </div>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-primary text-white"><i class="fas fa-map-marker-alt"></i></span>
                        </div>
                        <input type="text" class="form-control" id="coordinates_display" placeholder="Tọa độ sẽ hiển thị ở đây sau khi nhập địa chỉ" readonly>
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="button" id="refresh_coordinates" title="Cập nhật tọa độ">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    <small class="form-text text-muted">Kéo thả điểm trên bản đồ để điều chỉnh vị trí chính xác.</small>
                    <input type="hidden" id="lat" name="lat">
                    <input type="hidden" id="lng" name="lng">
                    <div class="position-relative">
                        <div id="map" style="width: 100%; margin-top: 10px;">
                            <div class="map-loading" id="map-loading">
                                <div class="map-loading-spinner">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                                <div>Đang tải bản đồ...</div>
                            </div>
                        </div>
                        <!-- Container for location error messages -->
                        <div id="location_error" class="mt-2" style="display:none;"></div>
                        <div class="map-instruction">
                            <i class="fas fa-info-circle mr-1"></i> Click vào bản đồ để chọn vị trí hoặc kéo marker để điều chỉnh
                        </div>
                    </div>
                </div>
            </div>

            <!-- THÔNG TIN LIÊN HỆ & TIỆN ÍCH -->
            <div class="form-section">
                <h5 class="section-title"><i class="fas fa-concierge-bell mr-2"></i>Thông tin liên hệ & Tiện ích</h5>
                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone text-primary mr-1"></i> Số điện thoại</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        </div>
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="Số điện thoại liên hệ" required>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-star text-primary mr-1"></i> Tiện ích</label>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="utility_wifi" name="utility_items[]" value="Wifi">
                                <label class="custom-control-label" for="utility_wifi"><i class="fas fa-wifi mr-1"></i> Wifi</label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="utility_ac" name="utility_items[]" value="Điều hòa">
                                <label class="custom-control-label" for="utility_ac"><i class="fas fa-snowflake mr-1"></i> Điều hòa</label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="utility_water_heater" name="utility_items[]" value="Nóng lạnh">
                                <label class="custom-control-label" for="utility_water_heater"><i class="fas fa-hot-tub mr-1"></i> Nóng lạnh</label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="utility_parking" name="utility_items[]" value="Gửi xe">
                                <label class="custom-control-label" for="utility_parking"><i class="fas fa-motorcycle mr-1"></i> Gửi xe</label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="utility_security" name="utility_items[]" value="Bảo vệ">
                                <label class="custom-control-label" for="utility_security"><i class="fas fa-shield-alt mr-1"></i> Bảo vệ</label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="utility_wc" name="utility_items[]" value="WC riêng">
                                <label class="custom-control-label" for="utility_wc"><i class="fas fa-toilet mr-1"></i> WC riêng</label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="utility_kitchen" name="utility_items[]" value="Bếp">
                                <label class="custom-control-label" for="utility_kitchen"><i class="fas fa-utensils mr-1"></i> Bếp</label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="utility_fridge" name="utility_items[]" value="Tủ lạnh">
                                <label class="custom-control-label" for="utility_fridge"><i class="fas fa-temperature-low mr-1"></i> Tủ lạnh</label>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="utilities" id="utilities">
                </div>
            </div>

            <!-- HÌNH ẢNH -->
            <div class="form-section">
                <h5 class="section-title"><i class="fas fa-images mr-2"></i>Hình ảnh</h5>
                <div class="form-group">
                    <label for="banner_image"><i class="fas fa-image text-primary mr-1"></i> Ảnh banner</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="banner_image" name="banner_image" accept="image/*">
                        <label class="custom-file-label" for="banner_image">Chọn ảnh banner...</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="additional_images"><i class="fas fa-images text-primary mr-1"></i> Hình ảnh bổ sung</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="additional_images" name="additional_images[]" multiple accept="image/*">
                        <label class="custom-file-label" for="additional_images">Chọn nhiều hình ảnh...</label>
                    </div>
                    <small class="form-text text-muted">Chọn nhiều hình ảnh để mô tả chi tiết phòng trọ (tối đa 10 hình).</small>
                </div>
            </div>

            <input type="hidden" name="district_name" value="Thành phố Vinh">
            <input type="hidden" name="province_name" value="Nghệ An">

            <div class="form-group text-center mt-4">
                <button type="submit" class="btn btn-primary btn-lg px-5">
                    <i class="fas fa-plus-circle mr-2"></i>Thêm phòng trọ
                </button>
                <a href="manage_rooms.php" class="btn btn-secondary btn-lg px-5 ml-2">
                    <i class="fas fa-times-circle mr-2"></i>Hủy
                </a>
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

        // Biến toàn cục cho bản đồ và marker
        let map = null;
        let marker = null;
        let pendingCoordinates = null;

        // Hàm lấy tọa độ từ địa chỉ
        function getCoordinates(showLoading = true) {
            var ward = $('#ward').val();
            var addressDetail = $('#address_detail').val();

            if (ward && addressDetail) {
                if (showLoading) {
                    $('#coordinates_display').val('Đang tìm tọa độ...');
                    $('#refresh_coordinates').html('<i class="fas fa-spinner fa-spin"></i>');
                }

                var fullAddress = addressDetail + ', ' + ward + ', Thành phố Vinh, Nghệ An';
                $.ajax({
                    url: '/api/location/get_coordinates.php',
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

                            // Initialize map if not already done
                            if (!map) {
                                initMap(lat, lng);
                            } else {
                                // Update map center and marker
                                map.setView([lat, lng], 16);

                                if (marker) {
                                    marker.setLatLng([lat, lng]);
                                } else {
                                    createMarker(lat, lng);
                                }

                                // Create popup with location info                                var popupContent = '<div><strong>' + response.coordinates.address + '</strong></div>' +
                                '<div>Độ tin cậy: ' + response.coordinates.confidence + '</div>' +
                                    '<div>Điểm tương đồng: ' + response.coordinates.match_score + '</div>';

                                marker.bindPopup(popupContent).openPopup();
                            }
                        } else {
                            $('#coordinates_display').val('Không tìm thấy tọa độ. Bạn có thể click trên bản đồ để chọn vị trí');

                            // Show map anyway so user can manually select location
                            if (!map) {
                                // Default coordinates for Vinh City
                                initMap(18.679585, 105.681335);
                            }

                            console.log("API response error:", response);
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#coordinates_display').val('Lỗi khi tìm tọa độ. Bạn có thể click trên bản đồ để chọn vị trí');
                        $('#refresh_coordinates').html('<i class="fas fa-sync-alt"></i>');

                        // Show map anyway so user can manually select location
                        if (!map) {
                            // Default coordinates for Vinh City
                            initMap(18.679585, 105.681335);
                        }

                        console.log("AJAX Error:", status, error, xhr.responseText);
                    }
                });
            } else {
                $('#coordinates_display').val('Vui lòng nhập phường và địa chỉ chi tiết');

                // Show map anyway so user can manually select location
                if (!map) {
                    // Default coordinates for Vinh City
                    initMap(18.679585, 105.681335);
                }
            }
        }

        // Helper function to create a marker
        function createMarker(lat, lng, title) {
            // If marker exists, update its position
            if (marker) {
                marker.setLatLng([lat, lng]);
                return marker;
            }

            // Create a new marker
            marker = L.marker([lat, lng], {
                draggable: true,
                title: title || 'Vị trí đã chọn'
            }).addTo(map);

            // Add drag end event
            marker.on('dragend', function() {
                var position = marker.getLatLng();
                var lat = position.lat.toFixed(6);
                var lng = position.lng.toFixed(6);
                $('#coordinates_display').val('Vĩ độ: ' + lat + ', Kinh độ: ' + lng);
                $('#lat').val(lat);
                $('#lng').val(lng);

                // Gọi API reverse geocoding để cập nhật địa chỉ
                $.ajax({
                    url: '/api/location/reverse-here.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        lat: lat,
                        lng: lng
                    },
                    success: function(response) {
                        if (response.success) {
                            // Tính khoảng cách đến trung tâm Vinh
                            var vinhLat = 18.6667;
                            var vinhLng = 105.6667;
                            var R = 6371; // Bán kính Trái Đất (km)
                            var dLat = (lat - vinhLat) * Math.PI / 180;
                            var dLon = (lng - vinhLng) * Math.PI / 180;
                            var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                                Math.cos(vinhLat * Math.PI / 180) * Math.cos(lat * Math.PI / 180) *
                                Math.sin(dLon / 2) * Math.sin(dLon / 2);
                            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                            var distance = R * c;

                            var isInVinh = distance <= 15; // Kiểm tra khoảng cách <= 15km

                            if (!isInVinh) {
                                $('#location_error').html(`
                                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        <strong>Cảnh báo!</strong> Vị trí đã chọn không nằm trong thành phố Vinh. Vui lòng chọn một vị trí trong thành phố Vinh.
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                `).show();
                            } else {
                                $('#location_error').empty().hide();

                                // Tự động điền thông tin địa chỉ
                                var address = response.raw.address;

                                // Địa chỉ chi tiết (thường là đường + số nhà)
                                var addressDetail = '';
                                if (address.street) {
                                    addressDetail = address.street;
                                    if (address.houseNumber) {
                                        addressDetail += ' ' + address.houseNumber;
                                    }
                                }
                                $('#address_detail').val(addressDetail);

                                // Tìm và chọn phường từ dropdown
                                var wardSelect = $('#ward');
                                var wardName = address.district || '';

                                if (wardName) {
                                    // Tìm option gần đúng với tên phường
                                    var found = false;
                                    $("#ward option").each(function() {
                                        if ($(this).text().indexOf(wardName) !== -1 || wardName.indexOf($(this).text()) !== -1) {
                                            wardSelect.val($(this).val()).trigger('change');
                                            found = true;
                                            return false; // break the loop
                                        }
                                    });

                                    if (!found) {
                                        // Nếu không tìm thấy, chọn option đầu tiên
                                        if ($("#ward option").length > 1) { // Không phải option placeholder
                                            wardSelect.val($("#ward option:eq(1)").val()).trigger('change');
                                        }
                                    }
                                }
                            }
                        }
                    }
                });
            });

            return marker;
        }

        // Initialize map with coordinates
        function initMap(lat, lng, zoom = 16) {
            // Hide loading indicator
            $('#map-loading').hide();

            // If map already exists, just update center
            if (map) {
                map.setView([lat, lng], zoom);
                if (marker) {
                    marker.setLatLng([lat, lng]);
                } else {
                    createMarker(lat, lng);
                }
                return;
            }

            // Create the map
            map = L.map('map').setView([lat, lng], zoom);

            // Add OpenStreetMap tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // Create initial marker
            createMarker(lat, lng);

            // Add click event to map to update marker position
            map.on('click', function(e) {
                var lat = e.latlng.lat.toFixed(6);
                var lng = e.latlng.lng.toFixed(6);

                $('#coordinates_display').val('Vĩ độ: ' + lat + ', Kinh độ: ' + lng);
                $('#lat').val(lat);
                $('#lng').val(lng);

                if (marker) {
                    marker.setLatLng(e.latlng);
                } else {
                    createMarker(lat, lng);
                }

                // Gọi API reverse geocoding để cập nhật địa chỉ khi click trên bản đồ
                $.ajax({
                    url: '/api/location/reverse-here.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        lat: lat,
                        lng: lng
                    },
                    success: function(response) {
                        if (response.success) {
                            // Kiểm tra xem có nằm trong Vinh không
                            var vinhLat = 18.6667;
                            var vinhLng = 105.6667;
                            var R = 6371; // Bán kính Trái Đất (km)
                            var dLat = (lat - vinhLat) * Math.PI / 180;
                            var dLon = (lng - vinhLng) * Math.PI / 180;
                            var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                                Math.cos(vinhLat * Math.PI / 180) * Math.cos(lat * Math.PI / 180) *
                                Math.sin(dLon / 2) * Math.sin(dLon / 2);
                            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                            var distance = R * c;

                            var isInVinh = distance <= 15; // Kiểm tra khoảng cách <= 15km

                            if (!isInVinh) {
                                $('#location_error').html(`
                                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        <strong>Cảnh báo!</strong> Vị trí đã chọn không nằm trong thành phố Vinh. Vui lòng chọn một vị trí trong thành phố Vinh.
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                `).show();
                            } else {
                                $('#location_error').empty().hide();

                                // Tự động điền thông tin địa chỉ
                                var address = response.raw.address;

                                // Địa chỉ chi tiết (thường là đường + số nhà)
                                var addressDetail = '';
                                if (address.street) {
                                    addressDetail = address.street;
                                    if (address.houseNumber) {
                                        addressDetail += ' ' + address.houseNumber;
                                    }
                                }
                                $('#address_detail').val(addressDetail);

                                // Tìm và chọn phường từ dropdown
                                var wardSelect = $('#ward');
                                var wardName = address.district || '';

                                if (wardName) {
                                    // Tìm option gần đúng với tên phường
                                    var found = false;
                                    $("#ward option").each(function() {
                                        if ($(this).text().indexOf(wardName) !== -1 || wardName.indexOf($(this).text()) !== -1) {
                                            wardSelect.val($(this).val()).trigger('change');
                                            found = true;
                                            return false; // break the loop
                                        }
                                    });

                                    if (!found) {
                                        // Nếu không tìm thấy, chọn option đầu tiên
                                        if ($("#ward option").length > 1) { // Không phải option placeholder
                                            wardSelect.val($("#ward option:eq(1)").val()).trigger('change');
                                        }
                                    }
                                }
                            }
                        }
                    }
                });
            });

            // Add map instruction
            var mapInstructions = L.control({
                position: 'topleft'
            });
            mapInstructions.onAdd = function(map) {
                var div = L.DomUtil.create('div', 'map-instruction');
                div.innerHTML = '<i class="fas fa-info-circle mr-1"></i> Click vào bản đồ để chọn vị trí hoặc kéo marker để điều chỉnh';
                return div;
            };
            mapInstructions.addTo(map);

            // Make sure all map components are visible
            setTimeout(function() {
                map.invalidateSize();
            }, 100);
        }

        // Tạo phiên bản debounced của hàm getCoordinates
        const debouncedGetCoordinates = debounce(getCoordinates, 1000);

        // Gọi hàm lấy tọa độ khi thay đổi phường hoặc địa chỉ
        $('#ward, #address_detail').on('change keyup blur', function() {
            debouncedGetCoordinates();
        });

        // Thêm sự kiện cho nút làm mới tọa độ
        $('#refresh_coordinates').on('click', function() {
            getCoordinates(true);
        });

        // Initialize Leaflet map on page load
        $(document).ready(function() {
            // Default coordinates for Vinh City
            const defaultLat = 18.679585;
            const defaultLng = 105.681335;

            try {
                // Initialize map with default coordinates
                initMap(defaultLat, defaultLng, 13);
            } catch (error) {
                console.error("Lỗi khởi tạo bản đồ:", error);
                $('#coordinates_display').val('Lỗi khi tải bản đồ. Vui lòng làm mới trang.');
                $('#map-loading').html('<div class="map-error"><i class="fas fa-exclamation-triangle mb-2"></i><br>Không thể tải bản đồ.<br><button class="btn btn-sm btn-primary mt-2" onclick="location.reload()">Tải lại trang</button></div>');
            }
        });
        // No need for another document ready since we already have one above

        // Hiển thị tên file khi chọn
        $('.custom-file-input').on('change', function() {
            var fileName = '';
            if (this.files && this.files.length > 1) {
                fileName = (this.getAttribute('data-multiple-caption') || '').replace('{count}', this.files.length);
            } else {
                fileName = $(this).val().split('\\').pop();
            }

            if (fileName) {
                $(this).next('.custom-file-label').html(fileName);
            }
        });

        // Hiển thị preview cho ảnh banner
        $('#banner_image').change(function() {
            const file = this.files[0];
            if (file) {
                let reader = new FileReader();
                reader.onload = function(event) {
                    // Thêm preview nếu chưa có
                    if ($('#banner-preview').length === 0) {
                        $(this).closest('.form-group').append('<div class="mt-3"><img id="banner-preview" src="' + event.target.result + '" class="img-thumbnail" style="max-height: 200px"></div>');
                    } else {
                        $('#banner-preview').attr('src', event.target.result);
                    }
                }.bind(this);
                reader.readAsDataURL(file);
            }
        });

        // Hiển thị preview cho nhiều ảnh
        $('#additional_images').change(function() {
            const files = Array.from(this.files);

            // Xóa preview cũ
            $('#additional-previews').remove();
            if (files.length > 0) {
                // Tạo container mới
                $(this).closest('.form-group').append('<div id="additional-previews" class="row mt-3"></div>');

                // Hiển thị tối đa 5 ảnh preview
                const maxPreviewCount = Math.min(files.length, 5);

                for (let i = 0; i < maxPreviewCount; i++) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        $('#additional-previews').append('<div class="col-md-3 mb-2"><div class="card"><img src="' + event.target.result + '" class="card-img-top" style="height: 150px; object-fit: cover;"></div></div>');
                    }
                    reader.readAsDataURL(files[i]);
                }

                if (files.length > 5) {
                    $('#additional-previews').append('<div class="col-md-3 mb-2"><div class="card d-flex justify-content-center align-items-center" style="height: 150px;"><div class="text-center">+' + (files.length - 5) + ' ảnh<br>khác</div></div></div>');
                }
            }
        });

        // Cập nhật giá trị tiện ích khi chọn
        function updateUtilities() {
            var selected = [];
            $('input[name="utility_items[]"]:checked').each(function() {
                selected.push($(this).val());
            });
            $('#utilities').val(selected.join(', '));

            // Hiển thị số tiện ích đã chọn
            const count = selected.length;
            if (count > 0) {
                if (!$('#utilities-count').length) {
                    $('label[for="utilities"]').append('<span id="utilities-count" class="badge badge-primary ml-2">' + count + ' tiện ích</span>');
                } else {
                    $('#utilities-count').text(count + ' tiện ích');
                }
            } else {
                $('#utilities-count').remove();
            }
        }

        // Cập nhật utilities khi chọn/bỏ chọn
        $('input[name="utility_items[]"]').change(function() {
            updateUtilities();
        });

        // Form validation trước khi submit
        $('#roomForm').on('submit', function(e) {
            // Kiểm tra có nhập tọa độ không
            if ($('#lat').val() === '' || $('#lng').val() === '') {
                if (!confirm('Bạn chưa định vị tọa độ cho phòng trọ. Bạn có muốn tiếp tục không?')) {
                    e.preventDefault();
                    return false;
                }
            }

            // Cập nhật nội dung từ Quill Editor
            $('#description').val(quill.root.innerHTML);

            // Cập nhật tiện ích
            updateUtilities();
        });

        // Lấy vị trí từ IP
        $('#get_ip_location').on('click', function() {
            $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span> Đang tìm vị trí...');

            $.ajax({
                url: '/api/maps/get_ip_location.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    $('#get_ip_location').prop('disabled', false).html('<i class="fas fa-location-arrow mr-1"></i> Lấy vị trí hiện tại từ IP');

                    if (response.success && response.coordinates) {
                        var lat = response.coordinates.lat;
                        var lng = response.coordinates.lng;

                        // Cập nhật tọa độ
                        $('#lat').val(lat);
                        $('#lng').val(lng);
                        $('#coordinates_display').val('Vĩ độ: ' + lat + ', Kinh độ: ' + lng);

                        // Cập nhật bản đồ
                        if (map && marker) {
                            map.setView([lat, lng], 16);
                            marker.setLatLng([lat, lng]);
                        } else {
                            initMap(lat, lng);
                        }

                        // Kiểm tra và thông báo nếu không nằm trong Vinh
                        if (!response.within_vinh) {
                            $('#location_error').html(`
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    <strong>Cảnh báo!</strong> Vị trí của bạn không nằm trong thành phố Vinh. Vui lòng chọn một vị trí trong thành phố Vinh.
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            `).show();
                        } else {
                            $('#location_error').empty().hide();

                            // Tự động điền thông tin địa chỉ
                            if (response.address) {
                                $('#address_detail').val(response.address.address_detail);

                                // Tìm và chọn phường từ dropdown
                                var wardSelect = $('#ward');
                                var wardName = response.address.ward_name;
                                if (wardName) {
                                    // Tìm option gần đúng với tên phường
                                    var found = false;
                                    $("#ward option").each(function() {
                                        if ($(this).text().indexOf(wardName) !== -1 || wardName.indexOf($(this).text()) !== -1) {
                                            wardSelect.val($(this).val()).trigger('change');
                                            found = true;
                                            return false; // break the loop
                                        }
                                    });

                                    if (!found) {
                                        // Nếu không tìm thấy, chọn option đầu tiên
                                        if ($("#ward option").length > 1) { // Không phải option placeholder
                                            wardSelect.val($("#ward option:eq(1)").val()).trigger('change');
                                        }
                                    }
                                }

                                // Hiện thông báo thành công
                                $('#location_error').html(`
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Đã lấy vị trí thành công và điền thông tin địa chỉ!
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                `).show();
                            }
                        }
                    } else {
                        $('#location_error').html(`
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                ${response.message || 'Không thể lấy vị trí từ IP. Vui lòng kiểm tra lại.'}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        `).show();
                    }
                },
                error: function() {
                    $('#get_ip_location').prop('disabled', false).html('<i class="fas fa-location-arrow mr-1"></i> Lấy vị trí hiện tại từ IP');
                    $('#location_error').html(`
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            Lỗi khi kết nối đến máy chủ. Vui lòng thử lại sau.
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    `).show();
                }
            });
        });

        // Lấy vị trí từ trình duyệt
        $('#get_browser_location').on('click', function() {
            if (navigator.geolocation) {
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span> Đang tìm vị trí...');

                navigator.geolocation.getCurrentPosition(function(position) {
                    $('#get_browser_location').prop('disabled', false).html('<i class="fas fa-crosshairs mr-1"></i> Lấy vị trí từ trình duyệt');
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;

                    // Cập nhật tọa độ
                    $('#lat').val(lat);
                    $('#lng').val(lng);
                    $('#coordinates_display').val('Vĩ độ: ' + lat + ', Kinh độ: ' + lng);

                    // Cập nhật bản đồ
                    if (map && marker) {
                        map.setView([lat, lng], 16);
                        marker.setLatLng([lat, lng]);
                    } else {
                        initMap(lat, lng);
                    }

                    // Gọi API reverse geocoding để lấy địa chỉ và kiểm tra xem có nằm trong Vinh không
                    $.ajax({
                        url: '/api/location/reverse-here.php',
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            lat: lat,
                            lng: lng
                        },
                        success: function(response) {
                            if (response.success) {
                                // Kiểm tra xem có nằm trong Vinh không
                                // Tọa độ trung tâm thành phố Vinh
                                var vinhLat = 18.6667;
                                var vinhLng = 105.6667;

                                // Tính khoảng cách đến trung tâm Vinh (km)
                                var R = 6371; // Bán kính Trái Đất (km)
                                var dLat = (lat - vinhLat) * Math.PI / 180;
                                var dLon = (lng - vinhLng) * Math.PI / 180;
                                var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                                    Math.cos(vinhLat * Math.PI / 180) * Math.cos(lat * Math.PI / 180) *
                                    Math.sin(dLon / 2) * Math.sin(dLon / 2);
                                var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                                var distance = R * c;

                                var isInVinh = distance <= 15; // Kiểm tra khoảng cách <= 15km

                                if (!isInVinh) {
                                    $('#location_error').html(`
                                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            <strong>Cảnh báo!</strong> Vị trí của bạn không nằm trong thành phố Vinh. Vui lòng chọn một vị trí trong thành phố Vinh.
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                    `).show();
                                } else {
                                    $('#location_error').empty().hide();

                                    // Tự động điền thông tin địa chỉ từ kết quả reverse geocoding
                                    var address = response.raw.address;

                                    // Địa chỉ chi tiết (thường là đường + số nhà)
                                    var addressDetail = '';
                                    if (address.street) {
                                        addressDetail = address.street;
                                        if (address.houseNumber) {
                                            addressDetail += ' ' + address.houseNumber;
                                        }
                                    }
                                    $('#address_detail').val(addressDetail);

                                    // Tìm và chọn phường từ dropdown
                                    var wardSelect = $('#ward');
                                    var wardName = address.district || '';

                                    if (wardName) {
                                        // Tìm option gần đúng với tên phường
                                        var found = false;
                                        $("#ward option").each(function() {
                                            if ($(this).text().indexOf(wardName) !== -1 || wardName.indexOf($(this).text()) !== -1) {
                                                wardSelect.val($(this).val()).trigger('change');
                                                found = true;
                                                return false; // break the loop
                                            }
                                        });

                                        if (!found) {
                                            // Nếu không tìm thấy, chọn option đầu tiên
                                            if ($("#ward option").length > 1) { // Không phải option placeholder
                                                wardSelect.val($("#ward option:eq(1)").val()).trigger('change');
                                            }
                                        }
                                    }

                                    // Hiển thị thông báo thành công
                                    $('#location_error').html(`
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Đã lấy vị trí thành công và điền thông tin địa chỉ!
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                    `).show();
                                }
                            } else {
                                $('#location_error').html(`
                                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        Không thể lấy thông tin địa chỉ từ vị trí này.
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                `).show();
                            }
                        },
                        error: function() {
                            $('#location_error').html(`
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    Đã lưu tọa độ nhưng không thể xác định thông tin địa chỉ.
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            `).show();
                        }
                    });

                }, function(error) {
                    $('#get_browser_location').prop('disabled', false).html('<i class="fas fa-crosshairs mr-1"></i> Lấy vị trí từ trình duyệt');

                    var errorMsg = 'Không thể lấy vị trí từ trình duyệt.';
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg += ' Bạn đã từ chối cho phép truy cập vị trí.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg += ' Thông tin vị trí không khả dụng.';
                            break;
                        case error.TIMEOUT:
                            errorMsg += ' Yêu cầu vị trí đã hết thời gian.';
                            break;
                        case error.UNKNOWN_ERROR:
                            errorMsg += ' Đã xảy ra lỗi không xác định.';
                            break;
                    }

                    $('#location_error').html(`
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            ${errorMsg}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    `).show();
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            } else {
                $('#location_error').html(`
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Trình duyệt không hỗ trợ tính năng lấy vị trí. Vui lòng cập nhật trình duyệt hoặc sử dụng trình duyệt khác.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                `).show();
            }
        });
    });
</script>

<?php include_once '../../Components/admin_footer.php'; ?>