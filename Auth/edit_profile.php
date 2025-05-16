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
                $upload_dir = '../images/';
                
                // Đảm bảo thư mục tồn tại
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Tạo tên file duy nhất để tránh trùng lặp
                $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $avatar_filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_extension;
                $avatar_path = 'images/' . $avatar_filename;
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
                        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                            // Nếu upload thành công, xóa ảnh cũ nếu có
                            if ($user['avatar'] && $user['avatar'] != 'images/default_avatar.png') {
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
                $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, email = ?, phone = ?, avatar = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $name, $username, $email, $phone, $avatar_path, $user_id);
                
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Assets/style.css">
    <style>
        .profile-body {
            background: linear-gradient(135deg, #4b6cb7, #182848);
            background-size: 400% 400%;
        }
        
        .avatar-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .avatar-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            margin: 0 auto 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .avatar-preview:hover {
            transform: scale(1.05);
            border-color: var(--primary-color);
        }
        
        .custom-file-upload {
            cursor: pointer;
            display: inline-block;
            padding: 8px 15px;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 25px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .custom-file-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
    </style>
</head>
<body class="profile-body">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 form-container">
            <h3 class="text-center mb-4">Chỉnh sửa thông tin tài khoản</h3>
            
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
            
            <form action="edit_profile.php" method="POST" enctype="multipart/form-data">
                <div class="avatar-container">
                    <img src="<?php echo !empty($user['avatar']) ? '../' . $user['avatar'] : '../images/default_avatar.png'; ?>" 
                         alt="Avatar" class="avatar-preview" id="avatar-preview">
                    <div>
                        <label for="avatar" class="custom-file-upload">
                            <i class="fas fa-camera me-2"></i>Thay đổi ảnh đại diện
                        </label>
                        <input type="file" name="avatar" id="avatar" style="display: none;" onchange="previewImage(this)">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label><i class="fas fa-user-circle me-2"></i>Họ tên</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-user-circle"></i></span>
                        <input type="text" class="form-control input-with-icon" name="name" 
                               placeholder="Nhập họ và tên" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label><i class="fas fa-user me-2"></i>Tên đăng nhập</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control input-with-icon" name="username" 
                               placeholder="Nhập tên đăng nhập" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label><i class="fas fa-envelope me-2"></i>Email</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control input-with-icon" name="email" 
                               placeholder="Nhập địa chỉ email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label><i class="fas fa-phone me-2"></i>Số điện thoại</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-phone"></i></span>
                        <input type="text" class="form-control input-with-icon" name="phone" 
                               placeholder="Nhập số điện thoại" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-update">Cập nhật thông tin</button>
                    <a href="../index.php" class="btn btn-outline-secondary">Quay lại trang chủ</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../Assets/main.js"></script>
</body>
</html>
