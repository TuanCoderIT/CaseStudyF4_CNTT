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
$address = $conn->query("SELECT * FROM districts");
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
        $district_id = (int)$_POST['district_id']; // Đây là ID của phường/xã từ bảng districts
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
            $upload_dir = '../uploads/';

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
                    $upload_dir = '../uploads/rooms/';

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
<!-- 1) Load jQuery trước -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- 2) Load Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<!-- Include Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css"
    integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A=="
    crossorigin="" />

<!-- Include Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"
    integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA=="
    crossorigin=""></script>
<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<!-- không đổi link này -->


<!-- Include form validation script -->
<script src="../assets/js/validation/add_room_validation.js"></script>
<style>
    .utility-option {
        padding: 8px 10px;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .utility-option:hover {
        background-color: #f8f9fa;
    }

    .utility-option .custom-control-label {
        cursor: pointer;
        font-weight: 500;
    }

    .utility-option .custom-control-input:checked~.custom-control-label {
        color: #1a73e8;
    }

    #selected_utilities {
        padding: 5px 10px;
        border-radius: 4px;
        background-color: #f8f9fa;
    }

    /* Make TinyMCE editor more visible */
    .tox-tinymce {
        border: 1px solid #ced4da !important;
        border-radius: 0.25rem !important;
    }

    .tox-statusbar {
        border-top: 1px solid #ced4da !important;
    }
</style>

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
    <div class="card-header bg-gradient-primary">
        <h5 class="m-0 font-weight-bold text-black"><i class="fas fa-edit mr-2"></i>Thông tin phòng trọ</h5>
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
                <!-- Quill Editor -->
                <div id="editor-container" style="height: 300px;"></div>

                <!-- Hidden textarea để submit về server -->
                <textarea name="description" id="description" style="display: none;"></textarea>
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
                        <label for="district"><i class="fas fa-map mr-1"></i> Quận/Huyện</label>
                        <select disabled class="form-control custom-select" id="district" required>
                            <option value="">Thành phố Vinh</option>
                        </select>
                        <input type="hidden" name="district_name" id="district_name">
                        <input type="hidden" name="district_id" id="district_id">
                        <input type="hidden" name="province_name" id="province_name">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="ward"><i class="fas fa-map-pin mr-1"></i> Phường/Xã</label>
                        <select class="form-control custom-select" id="ward" required>
                            <option value="">-- Chọn Phường/Xã --</option>
                            <?php while ($cat = mysqli_fetch_assoc($address)): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                            <?php endwhile; ?>
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
                <label><i class="fas fa-tools mr-1"></i> Tiện ích</label>
                <div class="card border-light mb-2">
                    <div class="card-body pb-0">
                        <p class="text-muted small mb-2">Chọn các tiện ích có sẵn trong phòng trọ:</p>
                        <div class="row mt-2">
                            <div class="col-md-3 mb-3">
                                <div class="custom-control custom-checkbox utility-option" data-toggle="tooltip" title="Có cung cấp wifi miễn phí">
                                    <input type="checkbox" class="custom-control-input" id="utility_wifi" name="utility_items[]" value="Wifi">
                                    <label class="custom-control-label" for="utility_wifi">
                                        <i class="fas fa-wifi mr-1 text-primary"></i> Wifi
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="custom-control custom-checkbox utility-option" data-toggle="tooltip" title="Có máy giặt hoặc dịch vụ giặt ủi">
                                    <input type="checkbox" class="custom-control-input" id="utility_washer" name="utility_items[]" value="Máy giặt">
                                    <label class="custom-control-label" for="utility_washer">
                                        <i class="fas fa-tshirt mr-1 text-info"></i> Máy giặt
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="custom-control custom-checkbox utility-option" data-toggle="tooltip" title="Có khu vực nấu ăn riêng">
                                    <input type="checkbox" class="custom-control-input" id="utility_kitchen" name="utility_items[]" value="Nhà bếp">
                                    <label class="custom-control-label" for="utility_kitchen">
                                        <i class="fas fa-utensils mr-1 text-danger"></i> Nhà bếp
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="custom-control custom-checkbox utility-option" data-toggle="tooltip" title="Có trang bị máy điều hòa">
                                    <input type="checkbox" class="custom-control-input" id="utility_ac" name="utility_items[]" value="Điều hòa">
                                    <label class="custom-control-label" for="utility_ac">
                                        <i class="fas fa-snowflake mr-1 text-info"></i> Điều hòa
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="custom-control custom-checkbox utility-option" data-toggle="tooltip" title="Có bình nước nóng lạnh">
                                    <input type="checkbox" class="custom-control-input" id="utility_water_heater" name="utility_items[]" value="Nóng lạnh">
                                    <label class="custom-control-label" for="utility_water_heater">
                                        <i class="fas fa-water mr-1 text-primary"></i> Nóng lạnh
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="custom-control custom-checkbox utility-option" data-toggle="tooltip" title="Có tủ lạnh trong phòng">
                                    <input type="checkbox" class="custom-control-input" id="utility_fridge" name="utility_items[]" value="Tủ lạnh">
                                    <label class="custom-control-label" for="utility_fridge">
                                        <i class="fas fa-cube mr-1 text-success"></i> Tủ lạnh
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="custom-control custom-checkbox utility-option" data-toggle="tooltip" title="Có chỗ để xe (miễn phí hoặc có phí)">
                                    <input type="checkbox" class="custom-control-input" id="utility_parking" name="utility_items[]" value="Gửi xe">
                                    <label class="custom-control-label" for="utility_parking">
                                        <i class="fas fa-motorcycle mr-1 text-dark"></i> Gửi xe
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="custom-control custom-checkbox utility-option" data-toggle="tooltip" title="Có dịch vụ bảo vệ hoặc hệ thống an ninh">
                                    <input type="checkbox" class="custom-control-input" id="utility_security" name="utility_items[]" value="Bảo vệ">
                                    <label class="custom-control-label" for="utility_security">
                                        <i class="fas fa-shield-alt mr-1 text-success"></i> Bảo vệ
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="selected_utilities" class="mb-1 text-muted small"></div>
                <input type="hidden" name="utilities" id="utilities">
            </div>

            <!-- Map hiển thị vị trí -->
            <div class="form-group">
                <label><i class="fas fa-map mr-1"></i> Bản đồ vị trí</label>
                <div id="map_manual_select" style="display: none;" class="mb-2">
                    <button type="button" class="btn btn-primary" id="show_map_manual">
                        <i class="fas fa-map-marker-alt mr-1"></i> Hiển thị bản đồ để chọn vị trí
                    </button>
                    <button type="button" class="btn btn-secondary ml-2" id="reset_coordinates">
                        <i class="fas fa-redo mr-1"></i> Khôi phục tự động
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
<a href="../api/ckeditor_upload/ckeditor_upload.php"></a>
<script>
    $(function() {
        // Biến toàn cục
        var typingTimer;
        var userHasManuallySelectedLocation = false;
        var marker, map;

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
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Xem trước nhiều ảnh
        function previewAdditionalImages(input) {
            var preview = $('#additional_images_preview');
            preview.empty();

            if (input.files) {
                var filesAmount = input.files.length;
                for (let i = 0; i < filesAmount; i++) {
                    let reader = new FileReader();
                    reader.onload = function(event) {
                        $($.parseHTML('<div class="col-md-4 mb-2"><img src="' + event.target.result + '" class="img-fluid rounded" style="height: 150px; object-fit: cover;"></div>')).appendTo(preview);
                    };
                    reader.readAsDataURL(input.files[i]);
                }
            }
        }

        var quill = new Quill('#editor-container', {
            theme: 'snow',
            placeholder: 'Mô tả chi tiết về phòng trọ...',
            modules: {
                toolbar: {
                    container: [
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
                        ['image', 'link']
                    ],
                    handlers: {
                        image: function() {
                            selectLocalImage();
                        }
                    }
                }
            }
        });

        function selectLocalImage() {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.click();

            input.onchange = () => {
                const file = input.files[0];
                if (/^image\//.test(file.type)) {
                    saveToServer(file);
                } else {
                    alert('Vui lòng chọn file ảnh hợp lệ');
                }
            };
        }

        function saveToServer(file) {
            const formData = new FormData();
            formData.append('image', file);

            fetch('../api/ckeditor_upload/ckeditor_upload.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    insertToEditor(result.url);
                })
                .catch(() => {
                    alert('Lỗi khi tải ảnh');
                });
        }

        function insertToEditor(url) {
            const range = quill.getSelection();
            quill.insertEmbed(range.index, 'image', url);
        }

        // Gán nội dung HTML vào textarea khi submit form
        document.querySelector('#roomForm').addEventListener('submit', function() {
            document.querySelector('#description').value = quill.root.innerHTML;
        });
        // Nút chọn vị trí thủ công
        $('#show_map_manual').on('click', function() {
            let defaultLat = 18.679585;
            let defaultLng = 105.681335;
            $('#map').show();
            $('#map_message').hide();
            initMap(defaultLat, defaultLng);
            $('#geocode_status').html(
                '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Hãy click vào vị trí phòng trọ trên bản đồ để chọn tọa độ.</div>'
            );
        });

        // Submit form
        $('#roomForm').on('submit', function(e) {
            if (!$('#ward').val()) {
                e.preventDefault();
                alert('Vui lòng chọn Phường/Xã');
                return false;
            }

            if (!$('#ward_name').val()) {
                let selectedWard = $('#ward option:selected').text();
                $('#ward_name').val(selectedWard);
            }

            if (!$('#district_name').val()) {
                $('#district_name').val('Thành phố Vinh');
            }

            if (!$('#province_name').val()) {
                $('#province_name').val('Nghệ An');
            }

            if (!$('#latitude').val() || !$('#longitude').val()) {
                e.preventDefault();
                alert('Vui lòng chọn vị trí trên bản đồ');
                $('#map_manual_select').show();
                return false;
            }

            let selectedUtilities = [];
            $('input[name="utility_items[]"]:checked').each(function() {
                selectedUtilities.push($(this).val());
            });
            $('#utilities').val(selectedUtilities.join(', '));
            tinymce.triggerSave();
        });

        $('#district_id').val(1);
        $('#district_name').val('Thành phố Vinh');
        $('#province_name').val('Nghệ An');
        $('#map_manual_select').show();

        $('#reset_coordinates').on('click', function() {
            userHasManuallySelectedLocation = false;
            $('#coordinates_display').val('');
            $('#latitude').val('');
            $('#longitude').val('');
            $('#geocode_status').html('<div class="text-info"><i class="fas fa-info-circle"></i> Đã khôi phục chế độ tự động tìm tọa độ.</div>');
            updateFullAddressPreview();
        });

        $('#ward').on('change', function() {
            let selectedOption = $(this).find('option:selected');
            let wardId = selectedOption.val();
            let wardName = selectedOption.text();

            if (!wardId || !wardName) return;

            $('#ward_name').val(wardName);
            $('#district_id').val(wardId);
            userHasManuallySelectedLocation = false;

            if (!$('#district_name').val()) $('#district_name').val('Thành phố Vinh');
            if (!$('#province_name').val()) $('#province_name').val('Nghệ An');

            $('#map_manual_select').show();

            if ($('#address_detail').val().trim()) {
                setTimeout(function() {
                    let fullAddress = $('#address_detail').val().trim() + ', ' + wardName + ', Thành phố Vinh, Nghệ An';
                    $('#coordinates_display').val('');
                    $('#latitude').val('');
                    $('#longitude').val('');
                    getCoordinatesFromAddress(fullAddress);
                }, 100);
            } else {
                updateFullAddressPreview();
            }
        });

        $('input[name="utility_items[]"]').on('change', updateSelectedUtilitiesDisplay);
        updateSelectedUtilitiesDisplay();
        $('[data-toggle="tooltip"]').tooltip();

        $('#address_detail').on('input', function() {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(updateFullAddressPreview, 500);
        });

        function updateFullAddressPreview() {
            let addressDetail = $('#address_detail').val().trim();
            let wardName = $('#ward_name').val();
            let districtName = $('#district_name').val() || 'Thành phố Vinh';
            let provinceName = $('#province_name').val() || 'Nghệ An';

            let parts = [];
            if (addressDetail) parts.push(addressDetail);
            if (wardName) parts.push(wardName);
            if (districtName) parts.push(districtName);
            if (provinceName) parts.push(provinceName);

            let fullAddress = parts.join(', ');
            $('#full_address_preview').html(fullAddress || '<i class="text-muted">Địa chỉ sẽ hiển thị ở đây sau khi chọn đầy đủ thông tin</i>');

            if (wardName && provinceName && !userHasManuallySelectedLocation) {
                getCoordinatesFromAddress(fullAddress);
            }
        }

        function getCoordinatesFromAddress(address) {
            if (!$('#address_detail').val().trim() || !$('#ward_name').val()) return;

            if (userHasManuallySelectedLocation) {
                $('#geocode_status').html('<div class="alert alert-warning mb-2"><i class="fas fa-info-circle"></i> Bạn đã chọn vị trí thủ công. Để sử dụng tọa độ tự động, hãy nhấn "Khôi phục tự động".</div>');
                return;
            }

            $('#geocode_status').html('<div class="text-info"><i class="fas fa-spinner fa-spin"></i> Đang tự động tìm tọa độ...</div>');

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
                        $('#geocode_status').html('<div class="text-success"><i class="fas fa-check-circle"></i> Đã tìm tọa độ tự động!<small class="d-block mt-1">Địa chỉ: ' + (response.formatted_address || 'Không có thông tin') + '</small></div>');
                        $('#map').show();
                        $('#map_message').hide();
                        initMap(response.lat, response.lng);
                        setTimeout(() => $('#geocode_status').html(''), 5000);
                    } else {
                        showDefaultMapWithMessage('Không thể tự động tìm tọa độ. ' + (response.message || 'Không xác định'));
                    }
                },
                error: function() {
                    showDefaultMapWithMessage('Lỗi khi tìm tọa độ tự động.');
                }
            });
        }

        function showDefaultMapWithMessage(message) {
            $('#geocode_status').html('<div class="text-warning mb-2"><i class="fas fa-exclamation-circle"></i> ' + message + '<br><small>Vui lòng chọn vị trí thủ công trên bản đồ.</small></div>');
            initMap(18.679585, 105.681335);
        }

        function initMap(lat, lng) {
            if (map && map.remove) map.remove();

            $('#map').show();
            $('#map_message').hide();

            map = L.map('map').setView([lat, lng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            marker = L.marker([lat, lng], {
                    draggable: true
                })
                .addTo(map)
                .bindPopup('Vị trí của phòng trọ. Kéo để thay đổi vị trí.')
                .openPopup();

            marker.on('dragend', function(event) {
                let position = marker.getLatLng();
                updateCoordinates(position.lat, position.lng);
            });

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

        function updateCoordinates(lat, lng) {
            lat = parseFloat(lat).toFixed(6);
            lng = parseFloat(lng).toFixed(6);
            $('#coordinates_display').val(lat + ', ' + lng);
            $('#latitude').val(lat);
            $('#longitude').val(lng);
            $('#geocode_status').html('<div class="text-success"><i class="fas fa-check-circle"></i> Đã cập nhật tọa độ thủ công!</div>');
            userHasManuallySelectedLocation = true;
            setTimeout(() => $('#geocode_status').html(''), 3000);
        }

        function updateSelectedUtilitiesDisplay() {
            let selected = $('input[name="utility_items[]"]:checked').map(function() {
                return $(this).val();
            }).get();

            if (selected.length > 0) {
                $('#selected_utilities').html('<i class="fas fa-check-circle text-success mr-1"></i> Đã chọn: ' + selected.join(', '));
            } else {
                $('#selected_utilities').html('<i class="fas fa-info-circle text-muted mr-1"></i> Chưa có tiện ích nào được chọn');
            }

            $('#utilities').val(selected.join(', '));
        }
    });
</script>

<?php include_once '../../Components/admin_footer.php'; ?>