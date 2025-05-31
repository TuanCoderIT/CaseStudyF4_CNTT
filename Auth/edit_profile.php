<?php
// Khởi tạo phiên làm việc
session_start();

// Kiểm tra xem người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Kết nối CSDL
require_once('../config/db.php');

// Lấy thông tin người dùng từ CSDL
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Khởi tạo biến thông báo
$success_message = '';
$error_message = '';

// Xử lý cập nhật thông tin khi form được gửi đi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $bank_name = $_POST['bank_name'] ?? null;
    $bank_code = $_POST['bank_code'] ?? null;

    // Kiểm tra xem username có bị trùng không (trừ username hiện tại của người dùng)
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $username, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error_message = "Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.";
    } else {
        // Kiểm tra xem email có bị trùng không (trừ email hiện tại của người dùng)
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = "Email đã tồn tại. Vui lòng sử dụng email khác.";
        } else {
            // Xử lý upload ảnh đại diện nếu có
            $avatar_path = $user['avatar']; // Mặc định giữ nguyên ảnh cũ

            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
                // Thư mục lưu trữ ảnh

                $upload_dir = '../uploads/avatar/';

                // Đảm bảo thư mục tồn tại
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Tạo tên file duy nhất để tránh trùng lặp
                $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);

                $avatar_filename = 'avatar_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
                $avatar_path = 'uploads/avatar/' . $avatar_filename; // Đường dẫn tương đối để lưu vào database
                $upload_path = $upload_dir . $avatar_filename; // Đường dẫn vật lý để upload file

                // Các định dạng ảnh được phép
                $allowed_types = array('jpg', 'jpeg', 'png', 'gif');

                // Kiểm tra định dạng file
                if (!in_array(strtolower($file_extension), $allowed_types)) {
                    $error_message = "Chỉ chấp nhận file ảnh có định dạng JPG, JPEG, PNG hoặc GIF.";
                } else {
                    // Giới hạn kích thước file (5MB)
                    if ($_FILES['avatar']['size'] > 5242880) {
                        $error_message = "Kích thước file quá lớn. Vui lòng chọn file nhỏ hơn 5MB.";
                    } else {
                        // Di chuyển file từ thư mục tạm vào thư mục upload
                        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                            // Nếu upload thành công, xóa ảnh cũ nếu có
                            if ($user['avatar'] && $user['avatar'] != 'uploads/avatar/default-avatar.jpg') {
                                $old_avatar_path = '../' . $user['avatar'];
                                if (file_exists($old_avatar_path)) {
                                    unlink($old_avatar_path);
                                }
                            }
                        } else {
                            $error_message = "Có lỗi xảy ra khi upload ảnh. Vui lòng thử lại.";
                            $avatar_path = $user['avatar']; // Giữ nguyên ảnh cũ nếu có lỗi
                        }
                    }
                }
            }

            // Nếu không có lỗi, tiến hành cập nhật thông tin vào CSDL
            if (empty($error_message)) {
                // Cập nhật thông tin người dùng
                $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, email = ?, phone = ?, avatar = ?, bankName = ?, bankCode = ? WHERE id = ?");
                $stmt->bind_param("sssssssi", $name, $username, $email, $phone, $avatar_path, $bank_name, $bank_code, $user_id);

                if ($stmt->execute()) {
                    $success_message = "Cập nhật thông tin thành công!";

                    // Cập nhật lại thông tin người dùng sau khi cập nhật
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                } else {
                    $error_message = "Có lỗi xảy ra khi cập nhật thông tin. Vui lòng thử lại.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa thông tin tài khoản</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Assets/client/css/style.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #2e59d9;
            --accent-color: #36b9cc;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
            --shadow-color: rgba(0, 0, 0, 0.15);
            --font-family: 'Nunito', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        body {
            font-family: var(--font-family);
        }

        body.profile-body {
            background: linear-gradient(-45deg, #4e73df, #224abe, #36b9cc, #1cc88a);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            min-height: 100vh;
            padding: 40px 0;
        }

        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .form-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1), 0 5px 15px rgba(0, 0, 0, 0.07);
            padding: 30px;
            margin-top: 20px;
            margin-bottom: 20px;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h3 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 10px;
        }

        h3:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            border-radius: 3px;
        }

        .avatar-container {
            text-align: center;
            margin-bottom: 35px;
            position: relative;
        }

        .avatar-preview {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            border: 5px solid rgba(78, 115, 223, 0.2);
            object-fit: cover;
            margin: 0 auto 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .avatar-preview:hover {
            transform: scale(1.05);
            border-color: var(--primary-color);
            box-shadow: 0 15px 35px rgba(78, 115, 223, 0.3);
        }

        .custom-file-upload {
            cursor: pointer;
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.2);
        }

        .custom-file-upload:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(78, 115, 223, 0.4);
        }

        .custom-file-upload i {
            margin-right: 8px;
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 25px;
            padding: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .alert-success {
            background-color: rgba(28, 200, 138, 0.15);
            color: #1cc88a;
            border-left: 4px solid #1cc88a;
        }

        .alert-danger {
            background-color: rgba(231, 74, 59, 0.15);
            color: #e74a3b;
            border-left: 4px solid #e74a3b;
        }

        .form-control,
        .input-group-text,
        .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #e0e3e9;
            transition: all 0.3s;
        }

        .input-group {
            margin-bottom: 5px;
        }

        .input-group-text {
            background-color: #f8f9fc;
            border-right: none;
            color: var(--primary-color);
        }

        .form-control {
            border-left: none;
        }

        .form-control:focus,
        .form-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
            border-color: #bac8f3;
        }

        label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-text {
            color: #858796;
            font-size: 12px;
            margin-top: 5px;
        }

        .mb-3,
        .mb-4 {
            margin-bottom: 20px !important;
        }

        .btn {
            padding: 12px 20px;
            font-weight: 600;
            border-radius: 8px;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }

        .btn-update {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.2);
        }

        .btn-update:hover {
            background: linear-gradient(45deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(78, 115, 223, 0.4);
        }

        .btn-outline-warning {
            color: var(--warning-color);
            border-color: var(--warning-color);
        }

        .btn-outline-warning:hover {
            background-color: var(--warning-color);
            color: white;
            transform: translateY(-2px);
        }

        .btn-outline-secondary {
            color: var(--dark-color);
            border-color: #d1d3e2;
        }

        .btn-outline-secondary:hover {
            background-color: #f8f9fc;
            color: var(--dark-color);
            border-color: #d1d3e2;
            transform: translateY(-2px);
        }

        .d-grid .btn {
            margin-bottom: 10px;
        }
    </style>
</head>

<body class="profile-body">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 form-container">
                <h3 class="text-center mb-4">Chỉnh sửa thông tin tài khoản</h3> <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form action="edit_profile.php" method="POST" enctype="multipart/form-data">
                    <div class="avatar-container">
                        <img src="<?php echo !empty($user['avatar']) ? '../' . $user['avatar'] : '../images/default_avatar.png'; ?>"
                            alt="Avatar" class="avatar-preview" id="avatar-preview">
                        <div>
                            <label for="avatar" class="custom-file-upload">
                                <i class="fas fa-camera me-2"></i>Thay đổi ảnh đại diện
                            </label>
                            <input type="file" name="avatar" id="avatar" style="display: none;"
                                onchange="previewImage(this)">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label><i class="fas fa-user-circle me-2"></i>Họ tên</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user-circle"></i></span>
                            <input type="text" class="form-control" name="name"
                                placeholder="Nhập họ và tên"
                                value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label><i class="fas fa-user me-2"></i>Tên đăng nhập</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control"
                                name="username" placeholder="Nhập tên đăng nhập"
                                value="<?php echo htmlspecialchars($user['username']); ?>"
                                required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label><i class="fas fa-envelope me-2"></i>Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control"
                                name="email" placeholder="Nhập địa chỉ email"
                                value="<?php echo htmlspecialchars($user['email']); ?>"
                                required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label><i class="fas fa-phone me-2"></i>Số điện thoại</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="text" class="form-control" name="phone"
                                placeholder="Nhập số điện thoại"
                                value="<?php echo htmlspecialchars($user['phone']); ?>"
                                required>
                        </div>
                    </div>

                    <!-- Thông tin ngân hàng -->
                    <div class="mb-3">
                        <label><i class="fas fa-university me-2"></i>Ngân hàng</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-university"></i></span>
                            <select class="form-select" name="bank_name">
                                <option value="" <?php echo empty($user['bankName']) ? 'selected' : ''; ?>>-- Chọn ngân hàng --</option>
                                <option value="Vietcombank" <?php echo $user['bankName'] == 'Vietcombank' ? 'selected' : ''; ?>>Vietcombank</option>
                                <option value="VietinBank" <?php echo $user['bankName'] == 'VietinBank' ? 'selected' : ''; ?>>VietinBank</option>
                                <option value="BIDV" <?php echo $user['bankName'] == 'BIDV' ? 'selected' : ''; ?>>BIDV</option>
                                <option value="Agribank" <?php echo $user['bankName'] == 'Agribank' ? 'selected' : ''; ?>>Agribank</option>
                                <option value="MBBank" <?php echo $user['bankName'] == 'MBBank' ? 'selected' : ''; ?>>MB Bank</option>
                                <option value="Techcombank" <?php echo $user['bankName'] == 'Techcombank' ? 'selected' : ''; ?>>Techcombank</option>
                                <option value="ACB" <?php echo $user['bankName'] == 'ACB' ? 'selected' : ''; ?>>ACB</option>
                                <option value="TPBank" <?php echo $user['bankName'] == 'TPBank' ? 'selected' : ''; ?>>TPBank</option>
                                <option value="VPBank" <?php echo $user['bankName'] == 'VPBank' ? 'selected' : ''; ?>>VPBank</option>
                                <option value="HDBank" <?php echo $user['bankName'] == 'HDBank' ? 'selected' : ''; ?>>HDBank</option>
                                <option value="SacomBank" <?php echo $user['bankName'] == 'SacomBank' ? 'selected' : ''; ?>>SacomBank</option>
                                <option value="OCB" <?php echo $user['bankName'] == 'OCB' ? 'selected' : ''; ?>>OCB</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label><i class="fas fa-credit-card me-2"></i>Số tài khoản (STK)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-credit-card"></i></span>
                            <input type="text" class="form-control" name="bank_code"
                                placeholder="Nhập số tài khoản ngân hàng"
                                value="<?php echo htmlspecialchars($user['bankCode'] ?? ''); ?>">
                        </div>
                        <div class="form-text">Số tài khoản ngân hàng sẽ được sử dụng cho các giao dịch thanh toán.</div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-update">Cập nhật thông tin</button>
                        <a href="change_password.php" class="btn btn-outline-warning">
                            <i class="fas fa-key me-2"></i>Đổi mật khẩu
                        </a>
                        <a href="../index.php" class="btn btn-outline-secondary">Quay lại trang chủ</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
    </div>
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatar-preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
    <script src="../assets/main.js"></script>
    <script src="../assets/admin/js/main.js"></script>
</body>

</html>