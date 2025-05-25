<?php
// Kết nối CSDL
require_once('../config/db.php');

// Khởi tạo SESSION
session_start();

// Khởi tạo biến thông báo
$error_message = '';

// Kiểm tra xem form đã được gửi chưa
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form đăng ký
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $phone = trim($_POST['phone']);

    // Kiểm tra các trường không được để trống
    if (empty($name) || empty($username) || empty($email) || empty($password) || empty($phone)) {
        $error_message = "Vui lòng điền đầy đủ thông tin.";
    } else {
        // Kiểm tra định dạng email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Địa chỉ email không hợp lệ.";
        }

        // Kiểm tra độ dài và định dạng mật khẩu
        else if (strlen($password) < 6) {
            $error_message = "Mật khẩu phải có ít nhất 6 ký tự.";
        }

        // Kiểm tra sự tồn tại của username
        else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error_message = "Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.";
            } else {
                // Kiểm tra sự tồn tại của email
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $error_message = "Email đã được sử dụng. Vui lòng sử dụng email khác.";
                } else {
                    // Xử lý upload ảnh đại diện nếu có
                    $avatar_path = 'images/default_avatar.png'; // Đường dẫn mặc định

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
                        $avatar_path = 'uploads/avatar/' . $avatar_filename;
                        $upload_path = $upload_dir . $avatar_filename;

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
                                if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                                    $error_message = "Có lỗi xảy ra khi upload ảnh. Vui lòng thử lại.";
                                    $avatar_path = 'images/default_avatar.png'; // Sử dụng ảnh mặc định nếu có lỗi
                                }
                            }
                        }
                    }

                    // Nếu không có lỗi, tiến hành đăng ký tài khoản
                    if (empty($error_message)) {
                        // Mã hóa mật khẩu trước khi lưu vào CSDL
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                        // Chuẩn bị câu truy vấn SQL để chèn người dùng mới
                        $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, phone, avatar) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("ssssss", $name, $username, $email, $hashed_password, $phone, $avatar_path);

                        // Thực hiện truy vấn
                        if ($stmt->execute()) {
                            // Đăng ký thành công, lưu thông tin vào session
                            $_SESSION['user_id'] = $stmt->insert_id;
                            $_SESSION['username'] = $username;
                            $_SESSION['name'] = $name;

                            // Chuyển hướng đến trang chính sau khi đăng ký thành công
                            header("Location: ../");
                            exit();
                        } else {
                            $error_message = "Đã xảy ra lỗi khi đăng ký. Vui lòng thử lại sau.";

                            // Nếu có lỗi và đã upload ảnh, xóa ảnh đã upload
                            if ($avatar_path != 'images/default_avatar.png' && file_exists('../' . $avatar_path)) {
                                unlink('../' . $avatar_path);
                            }
                        }
                    }
                }
            }
        }
    }

    // Nếu có lỗi, chuyển hướng về trang đăng ký với thông báo lỗi
    if (!empty($error_message)) {
        $_SESSION['register_error'] = $error_message;
        header("Location: register.php");
        exit();
    }
} else {
    // Nếu không phải là POST request, chuyển hướng về trang đăng ký
    header("Location: register.php");
    exit();
}
