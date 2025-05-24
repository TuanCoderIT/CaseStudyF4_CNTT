<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/client/css/style.css">
</head>

<body class="register-body">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 form-container">
                <h3 class="text-center mb-4">Đăng ký tài khoản</h3>

                <?php
                // Khởi tạo phiên làm việc
                if (!isset($_SESSION)) {
                    session_start();
                }

                // Hiển thị thông báo lỗi nếu có
                if (isset($_SESSION['register_error'])) {
                    echo '<div class="alert alert-danger" role="alert">';
                    echo '<i class="fas fa-exclamation-circle me-2"></i>' . $_SESSION['register_error'];
                    echo '</div>';
                    unset($_SESSION['register_error']);
                }
                ?>

                <form action="register_handler.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label><i class="fas fa-user-circle me-2"></i>Họ tên</label>
                        <div class="input-group">
                            <input type="text" class="form-control input-with-icon" name="name" placeholder="Nhập họ và tên" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label><i class="fas fa-user me-2"></i>Tên đăng nhập</label>
                        <div class="input-group">
                            <input type="text" class="form-control input-with-icon" name="username" placeholder="Nhập tên đăng nhập" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label><i class="fas fa-envelope me-2"></i>Email</label>
                        <div class="input-group">
                            <input type="email" class="form-control input-with-icon" name="email" placeholder="Nhập địa chỉ email" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label><i class="fas fa-lock me-2"></i>Mật khẩu</label>
                        <div class="password-container">
                            <input type="password" class="form-control input-with-icon" name="password" id="password" placeholder="Nhập mật khẩu" required>
                            <span class="password-toggle" onclick="togglePassword()"><i class="fas fa-eye"></i></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label><i class="fas fa-phone me-2"></i>Số điện thoại</label>
                        <div class="input-group">
                            <input type="text" class="form-control input-with-icon" name="phone" placeholder="Nhập số điện thoại" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label><i class="fas fa-image me-2"></i>Ảnh đại diện</label>
                        <div class="input-group">
                            <input type="file" class="form-control input-with-icon" name="avatar" accept="image/*">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Đăng ký</button>
                    <div class="text-center mt-4">
                        <a href="login.php" class="login-link">Đã có tài khoản? Đăng nhập</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Thêm tham chiếu đến file JavaScript chung -->
    <script src="../assets/admin/js/main.js"></script>
</body>

</html>