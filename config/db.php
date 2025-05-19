<?php
// Thông tin kết nối đến cơ sở dữ liệu
$host = 'localhost:3307';       // Host CSDL
$username = 'root';        // Tên đăng nhập MySQL (mặc định là 'root')
$password = '';            // Mật khẩu MySQL (mặc định là trống cho XAMPP)
$database = 'gtpt';  // Tên CSDL

// Tạo kết nối đến MySQL
$conn = new mysqli($host, $username, $password, $database);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối đến cơ sở dữ liệu thất bại: " . $conn->connect_error);
}

// Thiết lập charset là utf8mb4 để hỗ trợ đầy đủ tiếng Việt và các ký tự đặc biệt
$conn->set_charset("utf8mb4");
?>
