<?php
// Khởi tạo phiên làm việc
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập tài khoản</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/Assets/client/css/style.css">
</head>
<body class="login-body">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 form-container">            <h3 class="text-center mb-4">Đăng nhập</h3>
            
            <?php
            // Hiển thị thông báo lỗi nếu có
            if (isset($_SESSION['login_error'])) {
                echo '<div class="alert alert-danger" role="alert">';
                echo '<i class="fas fa-exclamation-circle me-2"></i>' . $_SESSION['login_error'];
                echo '</div>';
                unset($_SESSION['login_error']);
            }
            ?>
            
            <form action="login_handler.php" method="POST">
                <div class="mb-4">
                    <label><i class="fas fa-user me-2"></i>Tên đăng nhập hoặc Email</label>
                    <div class="input-group">
                        <input type="text" class="form-control input-with-icon" name="username" placeholder="Nhập tên đăng nhập hoặc email" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label><i class="fas fa-lock me-2"></i>Mật khẩu</label>
                    <div class="password-container">
                        <input type="password" class="form-control input-with-icon" name="password" id="password" placeholder="Nhập mật khẩu" required>
                        <span class="password-toggle" onclick="togglePassword()"><i class="fas fa-eye"></i></span>
                    </div>                </div>
                <!-- Chỗ này sẽ hiển thị Captcha nếu sai quá 3 lần -->
                <?php
                if (isset($_SESSION['login_attempt']) && $_SESSION['login_attempt'] >= 3) {
                    echo '<div class="mb-3 captcha-container">
                            <div class="g-recaptcha" data-sitekey="6LdqYD0rAAAAAL-dXI36p_k0Vn_RRq3KTOFiQrIi"></div>
                          </div>';
                    echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
                }
                ?>
                <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
                <div class="text-center mt-4">
                    <a href="register.php" class="register-link">Chưa có tài khoản? Đăng ký</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Thêm tham chiếu đến file JavaScript chung -->
<script src="../assets/admin/js/main.js"></script>
</body>
</html>
