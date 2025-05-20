<?php
session_start();
require_once '../../config/db.php';

// Kiểm tra đăng nhập với quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header('Location: ../../Auth/login.php?message=Bạn cần đăng nhập với tài khoản admin');
    exit();
}

// Xử lý thêm người dùng mới
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form 
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $phone = trim($_POST['phone']);
    $role = (int)$_POST['role'];

    // Kiểm tra dữ liệu đầu vào
    $error = false;

    // Kiểm tra các trường không được để trống
    if (empty($name) || empty($username) || empty($email) || empty($password) || empty($phone)) {
        $_SESSION['error'] = "Vui lòng điền đầy đủ thông tin.";
        $error = true;
    }
    // Kiểm tra định dạng email
    else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Địa chỉ email không hợp lệ.";
        $error = true;
    }
    // Kiểm tra độ dài và định dạng mật khẩu
    else if (strlen($password) < 6) {
        $_SESSION['error'] = "Mật khẩu phải có ít nhất 6 ký tự.";
        $error = true;
    }
    // Kiểm tra vai trò
    else if ($role != 0 && $role != 1) {
        $_SESSION['error'] = "Vai trò không hợp lệ.";
        $error = true;
    }

    if (!$error) {
        // Kiểm tra sự tồn tại của username
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error'] = "Tên đăng nhập đã tồn tại. Vui lòng chọn tên đăng nhập khác.";
        } else {
            // Kiểm tra sự tồn tại của email
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $_SESSION['error'] = "Email đã được sử dụng. Vui lòng sử dụng email khác.";
            } else {
                // Mã hóa mật khẩu
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Xử lý tải lên ảnh đại diện (nếu có)
                $avatar = "default-avatar.jpg";

                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
                    $allowed = array('jpg', 'jpeg', 'png', 'gif');
                    $filename = $_FILES['avatar']['name'];
                    $file_ext = pathinfo($filename, PATHINFO_EXTENSION);

                    if (in_array(strtolower($file_ext), $allowed)) {
                        // Tạo tên file duy nhất
                        $new_filename = 'avatar_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                        $upload_path = '../../uploads/avatar/' . $new_filename;

                        // Kiểm tra và tạo thư mục nếu không tồn tại
                        if (!file_exists('../../uploads/avatar/')) {
                            mkdir('../../uploads/avatar/', 0777, true);
                        }

                        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                            $avatar = 'uploads/avatar/' . $new_filename;
                        }
                    } else {
                        $_SESSION['error'] = "Chỉ chấp nhận file ảnh có định dạng JPG, JPEG, PNG, hoặc GIF.";
                        $error = true;
                    }
                }
                if (!$error) {
                    // Thêm người dùng mới vào CSDL
                    $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, phone, avatar, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssssi", $name, $username, $email, $hashed_password, $phone, $avatar, $role);

                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Đã thêm người dùng $name thành công!";
                        header('Location: manage_users.php');
                        exit();
                    } else {
                        $_SESSION['error'] = "Lỗi khi thêm người dùng: " . $conn->error;
                    }
                }
            }
        }
    }
}

$page_title = "Thêm người dùng mới";
include_once '../../Components/admin_header.php';
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2><i class="fas fa-user-plus mr-2"></i> Thêm người dùng mới</h2>
        <a href="manage_users.php" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại danh sách
        </a>
    </div>
</div>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?php
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-gradient-primary text-white">
        <h5 class="m-0 font-weight-bold"><i class="fas fa-user-edit mr-2"></i>Thông tin người dùng</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="add_user.php" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="name" class="form-label">Họ tên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Nhập họ và tên" required>
                        <div class="invalid-feedback">Vui lòng nhập họ tên.</div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="username" class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Nhập tên đăng nhập" required>
                        <div class="invalid-feedback">Vui lòng nhập tên đăng nhập.</div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Nhập địa chỉ email" required>
                        <div class="invalid-feedback">Vui lòng nhập địa chỉ email hợp lệ.</div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="phone" class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="Nhập số điện thoại" required>
                        <div class="invalid-feedback">Vui lòng nhập số điện thoại.</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="form-text text-muted">Mật khẩu phải có ít nhất 6 ký tự.</small>
                        <div class="invalid-feedback">Vui lòng nhập mật khẩu (ít nhất 6 ký tự).</div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="role" class="form-label">Vai trò <span class="text-danger">*</span></label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="0">Người dùng thông thường</option>
                            <option value="1">Quản trị viên</option>
                        </select>
                        <div class="invalid-feedback">Vui lòng chọn vai trò.</div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="avatar" class="form-label">Ảnh đại diện</label>
                        <input type="file" class="form-control" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif,image/jpg">
                        <small class="form-text text-muted">Chấp nhận các định dạng: JPG, JPEG, PNG, GIF. Nếu không chọn, sẽ dùng ảnh mặc định.</small>
                    </div>

                    <div class="avatar-preview mt-3 text-center">
                        <img id="avatar-preview" src="../../images/default-avatar.jpg" class="img-thumbnail" alt="Avatar Preview" style="max-width: 150px; max-height: 150px;">
                    </div>
                </div>
            </div>

            <div class="form-group mt-4 text-center">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>Thêm người dùng
                </button>
                <a href="manage_users.php" class="btn btn-secondary ml-2">
                    <i class="fas fa-times mr-2"></i>Hủy bỏ
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    // Hiển thị xem trước ảnh đại diện
    document.getElementById('avatar').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('avatar-preview').src = event.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            document.getElementById('avatar-preview').src = '../../images/default-avatar.jpg';
        }
    });

    // Hiển thị/ẩn mật khẩu
    document.querySelector('.toggle-password').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

    // Form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.getElementsByClassName('needs-validation');
            // Loop over them and prevent submission
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });

            // Kiểm tra mật khẩu 
            const passwordInput = document.getElementById('password');
            passwordInput.addEventListener('input', function() {
                if (this.value.length < 6) {
                    this.setCustomValidity('Mật khẩu phải có ít nhất 6 ký tự');
                } else {
                    this.setCustomValidity('');
                }
            });
        }, false);
    })();
</script>

<?php include_once '../../Components/admin_footer.php'; ?>