<?php
// Kết nối CSDL
require_once('../config/db.php');

// Khởi tạo SESSION
session_start();

// Khởi tạo biến thông báo
$error_message = '';

// Kiểm tra xem form đã được gửi chưa
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form đăng nhập
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Kiểm tra các trường không được để trống
    if (empty($username) || empty($password)) {
        $error_message = "Vui lòng điền đầy đủ thông tin đăng nhập.";
    } else {
        // Kiểm tra xem có cần kiểm tra CAPTCHA không
        if (isset($_SESSION['login_attempt']) && $_SESSION['login_attempt'] >= 3) {
            // Xác minh CAPTCHA
            $recaptcha_secret = "6LdqYD0rAAAAAF-8kaMHyGC8gS9UYhrsUvBptUfh"; // Thay thế bằng secret key của bạn
            $response = $_POST["g-recaptcha-response"];

            $verify_response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $recaptcha_secret . '&response=' . $response);
            $response_data = json_decode($verify_response);

            if (!$response_data->success) {
                $error_message = "Vui lòng xác nhận bạn không phải là robot.";
            }
        }

        if (empty($error_message)) {
            // Tìm kiếm người dùng theo tên đăng nhập hoặc email
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                print_r($user);
                // Kiểm tra mật khẩu
                if (password_verify($password, $user['password'])) {
                    // Đăng nhập thành công, lưu thông tin vào session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = $user['role'];

                    // Xóa số lần đăng nhập thất bại
                    unset($_SESSION['login_attempt']);

                    // Chuyển hướng đến trang chính sau khi đăng nhập thành công
                    // header("Location: ../Home/index.php");
                    exit();
                } else {
                    $error_message = "Mật khẩu không chính xác.";
                }
            } else {
                $error_message = "Tên đăng nhập hoặc email không tồn tại.";
            }

            // Nếu đăng nhập thất bại, tăng số lần thử
            if (!isset($_SESSION['login_attempt'])) {
                $_SESSION['login_attempt'] = 1;
            } else {
                $_SESSION['login_attempt']++;
            }
        }
    }

    // Nếu có lỗi, chuyển hướng về trang đăng nhập với thông báo lỗi
    if (!empty($error_message)) {
        $_SESSION['login_error'] = $error_message;
        header("Location: login.php");
        exit();
    }
} else {
    // Nếu không phải là POST request, chuyển hướng về trang đăng nhập
    header("Location: login.php");
    exit();
}
