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

// Xử lý đổi mật khẩu khi form được gửi đi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Kiểm tra mật khẩu hiện tại có đúng không
    if (!password_verify($current_password, $user['password'])) {
        $error_message = "Mật khẩu hiện tại không đúng.";
    }
    // Kiểm tra mật khẩu mới và xác nhận mật khẩu có khớp nhau không
    elseif ($new_password !== $confirm_password) {
        $error_message = "Mật khẩu mới và xác nhận mật khẩu không khớp.";
    }
    // Kiểm tra độ dài mật khẩu mới
    elseif (strlen($new_password) < 6) {
        $error_message = "Mật khẩu mới phải có ít nhất 6 ký tự.";
    }
    // Nếu không có lỗi, tiến hành cập nhật mật khẩu mới
    else {
        // Mã hóa mật khẩu mới
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Cập nhật mật khẩu mới vào CSDL
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);

        if ($stmt->execute()) {
            $success_message = "Đổi mật khẩu thành công!";
        } else {
            $error_message = "Có lỗi xảy ra khi cập nhật mật khẩu. Vui lòng thử lại.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi mật khẩu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/client/css/style.css">
    <style>
        .profile-body {
            background: linear-gradient(135deg, #4b6cb7, #182848);
            background-size: 400% 400%;
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .btn-update {
            background: linear-gradient(45deg, #3a7bd5, #00d2ff);
            border: none;
        }

        .btn-update:hover {
            background: linear-gradient(45deg, #00d2ff, #3a7bd5);
            transform: translateY(-3px);
        }

        .form-control:focus {
            border-color: #3a7bd5;
            box-shadow: 0 0 0 0.25rem rgba(58, 123, 213, 0.25);
        }

        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
        }
    </style>
</head>

<body class="profile-body">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 form-container">
                <h3 class="text-center mb-4">Đổi mật khẩu</h3>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form action="change_password.php" method="POST">
                    <div class="mb-3">
                        <label><i class="fas fa-lock me-2"></i>Mật khẩu hiện tại</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" name="current_password" id="current_password"
                                placeholder="Nhập mật khẩu hiện tại" required>
                            <span class="password-toggle" onclick="togglePasswordVisibility('current_password')">
                                <i class="fas fa-eye" id="current_password_toggle"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label><i class="fas fa-key me-2"></i>Mật khẩu mới</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" class="form-control" name="new_password" id="new_password"
                                placeholder="Nhập mật khẩu mới (ít nhất 6 ký tự)" minlength="6" required>
                            <span class="password-toggle" onclick="togglePasswordVisibility('new_password')">
                                <i class="fas fa-eye" id="new_password_toggle"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label><i class="fas fa-check-circle me-2"></i>Xác nhận mật khẩu mới</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                            <input type="password" class="form-control" name="confirm_password" id="confirm_password"
                                placeholder="Nhập lại mật khẩu mới" minlength="6" required>
                            <span class="password-toggle" onclick="togglePasswordVisibility('confirm_password')">
                                <i class="fas fa-eye" id="confirm_password_toggle"></i>
                            </span>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-update">Cập nhật mật khẩu</button>
                        <a href="edit_profile.php" class="btn btn-outline-secondary">Quay lại</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(inputId + '_toggle');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>

</html>