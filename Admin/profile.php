<?php
session_start();
require_once '../config/db.php';

// Kiểm tra đăng nhập với quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header('Location: ../auth/login.php?message=Bạn cần đăng nhập với tài khoản admin');
    exit();
}

$page_title = "Thông tin cá nhân";
$admin_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        // Validate input
        if (empty($name) || empty($email)) {
            $error = 'Tên và email không được để trống';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email không hợp lệ';
        } else {
            // Fetch current data for comparison BEFORE attempting update
            $current_admin_data_query = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
            $current_admin_data_query->bind_param("i", $admin_id);
            $current_admin_data_query->execute();
            $current_admin_data_result = $current_admin_data_query->get_result();
            $current_admin_data = $current_admin_data_result->fetch_assoc();
            $current_admin_data_query->close();

            if (!$current_admin_data) {
                $error = "Không thể lấy dữ liệu admin hiện tại để so sánh.";
            } else {
                $email_changed = ($email !== $current_admin_data['email']);
                $proceed_with_update = true;

                if ($email_changed) {
                    $check_email_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $check_email_stmt->bind_param("si", $email, $admin_id);
                    $check_email_stmt->execute();
                    $check_email_result = $check_email_stmt->get_result();
                    if ($check_email_result->num_rows > 0) {
                        $error = 'Email này đã được sử dụng bởi tài khoản khác';
                        $proceed_with_update = false;
                    }
                    $check_email_stmt->close();
                }

                if ($proceed_with_update) {
                    // Debug: Hiển thị thông tin trước khi cập nhật
                    $debug_info = "Đang thực hiện cập nhật cho ID: $admin_id<br>";
                    $debug_info .= "Dữ liệu hiện tại: " . htmlspecialchars(print_r($current_admin_data, true)) . "<br>";
                    $debug_info .= "Dữ liệu mới: Tên='$name', Email='$email', Phone='$phone'<br>";
                    $error .= "<div class='debug-info alert alert-info'>" . $debug_info . "</div>";

                    $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                    $update_stmt->bind_param("sssi", $name, $email, $phone, $admin_id);

                    if ($update_stmt->execute()) {
                        // Debug: Thêm thông tin về affected_rows
                        $affected = $update_stmt->affected_rows;
                        $debug_info .= "affected_rows = $affected<br>";

                        if ($update_stmt->affected_rows > 0) {
                            $message = 'Cập nhật thông tin thành công';
                            // Làm mới thông tin admin để hiển thị thay đổi
                            $profile_info_refresh_stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 1");
                            $profile_info_refresh_stmt->bind_param("i", $admin_id);
                            $profile_info_refresh_stmt->execute();
                            $admin_result_profile_refresh = $profile_info_refresh_stmt->get_result();
                            $admin_info = $admin_result_profile_refresh->fetch_assoc();
                            $profile_info_refresh_stmt->close();
                        } elseif ($update_stmt->affected_rows == 0) {
                            if ($name !== $current_admin_data['name'] || $email !== $current_admin_data['email'] || $phone !== $current_admin_data['phone']) {
                                $error = 'Dữ liệu đã được gửi nhưng không có thay đổi nào được ghi nhận trong cơ sở dữ liệu. Lỗi DB: ' . htmlspecialchars($update_stmt->error ?: $conn->error);
                            } else {
                                $message = 'Không có thông tin nào được thay đổi vì dữ liệu giống với hiện tại.';
                            }
                        } else { // affected_rows == -1
                            $error = 'Lỗi cập nhật thông tin (affected_rows): ' . htmlspecialchars($update_stmt->error ?: $conn->error);
                        }
                    } else { // execute() returned false
                        $error = 'Lỗi thực thi cập nhật: ' . htmlspecialchars($update_stmt->error ?: $conn->error);
                    }
                    $update_stmt->close();
                }
            }
        }
    }

    // Xử lý thay đổi mật khẩu
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Vui lòng điền đầy đủ thông tin mật khẩu';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Mật khẩu mới và xác nhận mật khẩu không khớp';
        } elseif (strlen($new_password) < 6) {
            $error = 'Mật khẩu mới phải có ít nhất 6 ký tự';
        } else {
            // Kiểm tra mật khẩu hiện tại
            $check_password = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $check_password->bind_param("i", $admin_id);
            $check_password->execute();
            $result = $check_password->get_result();
            $user_data = $result->fetch_assoc();

            if (password_verify($current_password, $user_data['password'])) {
                // Mã hóa mật khẩu mới
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $update_password = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_password->bind_param("si", $hashed_password, $admin_id);

                if ($update_password->execute()) {
                    $message = 'Đổi mật khẩu thành công';
                } else {
                    $error = 'Có lỗi xảy ra khi đổi mật khẩu';
                }
            } else {
                $error = 'Mật khẩu hiện tại không đúng';
            }
        }
    }

    // Xử lý upload avatar
    if (isset($_POST['upload_avatar']) && isset($_FILES['avatar'])) {
        $file = $_FILES['avatar'];

        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = mime_content_type($file['tmp_name']); // More reliable way to get mime type

            if (in_array($file_type, $allowed_types)) {
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $new_filename = 'admin_' . $admin_id . '_' . time() . '.' . $file_extension;
                $upload_dir = '../assets/uploads/avatars/'; // Relative to Admin/profile.php

                // Tạo thư mục nếu chưa tồn tại
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0755, true)) {
                        $error = "Không thể tạo thư mục upload: " . $upload_dir;
                        // Skip further processing if directory creation fails
                        goto end_avatar_processing; // Using goto for cleaner exit from nested ifs
                    }
                }

                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Xóa avatar cũ nếu có
                    $old_avatar_stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
                    $old_avatar_stmt->bind_param("i", $admin_id);
                    $old_avatar_stmt->execute();
                    $old_avatar_result = $old_avatar_stmt->get_result();
                    $old_data = $old_avatar_result->fetch_assoc();
                    $old_avatar_stmt->close();

                    if ($old_data && $old_data['avatar']) {
                        $old_file_path = '../' . $old_data['avatar']; // Path relative to Admin/profile.php
                        if (file_exists($old_file_path)) {
                            if (!unlink($old_file_path)) {
                                $error .= " Cảnh báo: Không thể xóa avatar cũ: " . $old_file_path;
                            }
                        }
                    }

                    // Cập nhật avatar trong database (lưu đường dẫn không có ../)
                    $avatar_db_path = 'assets/uploads/avatars/' . $new_filename;
                    $update_avatar_stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    $update_avatar_stmt->bind_param("si", $avatar_db_path, $admin_id);

                    if ($update_avatar_stmt->execute()) {
                        if ($update_avatar_stmt->affected_rows > 0) {
                            $message = 'Cập nhật avatar thành công';
                            // Refresh thông tin admin để hiển thị avatar mới
                            $avatar_refresh_stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 1");
                            $avatar_refresh_stmt->bind_param("i", $admin_id);
                            $avatar_refresh_stmt->execute();
                            $admin_result_avatar_refresh = $avatar_refresh_stmt->get_result();
                            $admin_info = $admin_result_avatar_refresh->fetch_assoc(); // Update $admin_info
                            $avatar_refresh_stmt->close();
                        } elseif ($update_avatar_stmt->affected_rows == 0) {
                            $error = 'Avatar đã được upload nhưng không có thay đổi nào trong database. Có thể avatar mới giống hệt avatar cũ hoặc lỗi DB: ' . htmlspecialchars($update_avatar_stmt->error ?: $conn->error);
                        } else { // affected_rows == -1
                            $error = 'Lỗi cập nhật avatar trong database (affected_rows): ' . htmlspecialchars($update_avatar_stmt->error ?: $conn->error);
                        }
                    } else {
                        $error = 'Lỗi thực thi cập nhật avatar: ' . htmlspecialchars($update_avatar_stmt->error ?: $conn->error);
                    }
                    $update_avatar_stmt->close();
                } else {
                    $error = 'Có lỗi xảy ra khi di chuyển file upload. Kiểm tra quyền ghi vào thư mục: ' . $upload_dir;
                }
            } else {
                $error = 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF). Loại file phát hiện: ' . htmlspecialchars($file_type);
            }
        } else {
            $error = 'Có lỗi xảy ra khi upload file. Mã lỗi: ' . $file['error'];
        }
    }
    end_avatar_processing: // Label for goto
}

// Lấy thông tin admin hiện tại
$admin_query = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 1");
$admin_query->bind_param("i", $admin_id);
$admin_query->execute();
$admin_result = $admin_query->get_result();
$admin_info = $admin_result->fetch_assoc();

if (!$admin_info) {
    header('Location: ../auth/login.php?message=Tài khoản không tồn tại');
    exit();
}

// Lấy thống kê hoạt động
$stats_queries = [
    'total_rooms' => "SELECT COUNT(*) as count FROM motel",
    'pending_rooms' => "SELECT COUNT(*) as count FROM motel WHERE approve = 0",
    'approved_rooms' => "SELECT COUNT(*) as count FROM motel WHERE approve = 1",
    'total_users' => "SELECT COUNT(*) as count FROM users WHERE role != 1",
    'recent_rooms' => "SELECT COUNT(*) as count FROM motel WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
];

$stats = [];
foreach ($stats_queries as $key => $query) {
    $result = $conn->query($query);
    $stats[$key] = $result->fetch_assoc()['count'];
}

// Lấy hoạt động gần đây
$recent_activity = $conn->query("
    SELECT 'room' as type, title, created_at, approve 
    FROM motel 
    ORDER BY created_at DESC 
    LIMIT 5
");

include '../Components/admin_header.php';
?>

<div class="admin-content1">
    <div class="container-fluid">
        <!-- Header -->
        <div class="row">
            <div class="col-12">
                <div class="admin-page-header">
                    <h1 class="admin-page-title">
                        <i class="fas fa-user-circle"></i>
                        Thông tin cá nhân
                    </h1>
                    <p class="admin-page-subtitle">Quản lý thông tin tài khoản và cài đặt cá nhân</p>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Information -->
            <div class="col-lg-8">
                <div class="card admin-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-edit"></i>
                            Thông tin cá nhân
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="profileForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Họ và tên <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="<?php echo htmlspecialchars($admin_info['name'] ?? ''); ?>"
                                            data-toggle="tooltip" title="Nhập họ và tên đầy đủ" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="username">Tên đăng nhập</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_info['username'] ?? ''); ?>" readonly>
                                        <small class="form-text text-muted">Tên đăng nhập không thể thay đổi</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            value="<?php echo htmlspecialchars($admin_info['email'] ?? ''); ?>"
                                            data-toggle="tooltip" title="Email sẽ được sử dụng để đăng nhập" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Số điện thoại</label>
                                        <input type="tel" class="form-control" id="phone" name="phone"
                                            value="<?php echo htmlspecialchars($admin_info['phone'] ?? ''); ?>"
                                            data-toggle="tooltip" title="Định dạng: 0901234567 hoặc +84901234567"
                                            placeholder="0901234567">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Cập nhật thông tin
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card admin-card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-lock"></i>
                            Đổi mật khẩu
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="passwordForm">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="current_password">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="current_password" name="current_password"
                                            placeholder="Nhập mật khẩu hiện tại"
                                            data-toggle="tooltip" title="Nhập mật khẩu hiện tại để xác nhận danh tính" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="new_password">Mật khẩu mới <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="new_password" name="new_password"
                                            placeholder="Nhập mật khẩu mới"
                                            data-toggle="tooltip" title="Mật khẩu mạnh nên chứa chữ hoa, chữ thường, số và ký tự đặc biệt" required>
                                        <small class="form-text text-muted">Ít nhất 6 ký tự, khuyến nghị sử dụng mật khẩu mạnh</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="confirm_password">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                            placeholder="Nhập lại mật khẩu mới"
                                            data-toggle="tooltip" title="Nhập lại mật khẩu mới để xác nhận" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" name="change_password" class="btn btn-warning">
                                    <i class="fas fa-key"></i>
                                    Đổi mật khẩu
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Profile Sidebar -->
            <div class="col-lg-4">
                <!-- Avatar -->
                <div class="card admin-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-image"></i>
                            Ảnh đại diện
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="profile-avatar-container mb-4">
                            <div class="profile-avatar-wrapper">
                                <?php if ($admin_info['avatar']): ?>
                                    <?php
                                    // Xử lý đường dẫn avatar
                                    $avatar_path = $admin_info['avatar'];
                                    if (strpos($avatar_path, '../') === 0) {
                                        $avatar_path = substr($avatar_path, 3);
                                    }
                                    $full_avatar_path = '../' . $avatar_path;
                                    ?>
                                    <?php if (file_exists($full_avatar_path)): ?>
                                        <img src="../<?php echo htmlspecialchars($avatar_path); ?>"
                                            alt="Avatar" class="profile-avatar-img">
                                        <div class="avatar-overlay">
                                            <i class="fas fa-camera"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                                            <div class="avatar-overlay">
                                                <i class="fas fa-camera"></i>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="avatar-placeholder">
                                        <i class="fas fa-user-circle fa-5x text-primary"></i>
                                        <div class="avatar-overlay">
                                            <i class="fas fa-camera"></i>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="profile-info mt-3">
                                <h5 class="profile-name"><?php echo htmlspecialchars($admin_info['name'] ?? 'Admin'); ?></h5>
                                <p class="profile-role">
                                    <span class="badge badge-primary">
                                        <i class="fas fa-crown"></i> Quản trị viên
                                    </span>
                                </p>
                            </div>
                        </div>

                        <form method="POST" enctype="multipart/form-data" id="avatarForm" class="avatar-upload-form">
                            <div class="upload-zone" onclick="document.getElementById('avatar').click();">
                                <input type="file" class="form-control-file" id="avatar" name="avatar"
                                    accept="image/jpeg,image/png,image/gif" style="display: none;">
                                <div class="upload-content">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-primary mb-2"></i>
                                    <p class="mb-1"><strong>Nhấp để chọn ảnh</strong></p>
                                    <small class="text-muted">hoặc kéo thả ảnh vào đây</small>
                                </div>
                            </div>
                            <div class="upload-info mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Định dạng: JPG, PNG, GIF | Kích thước tối đa: 5MB
                                </small>
                            </div>
                            <button type="submit" name="upload_avatar" class="btn btn-success btn-sm mt-3" style="display: none;" id="uploadBtn">
                                <i class="fas fa-upload"></i>
                                Cập nhật avatar
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="card admin-card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar"></i>
                            Thống kê hệ thống
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo number_format($stats['total_rooms']); ?></div>
                                <div class="stat-label">Tổng phòng</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo number_format($stats['pending_rooms']); ?></div>
                                <div class="stat-label">Chờ duyệt</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo number_format($stats['approved_rooms']); ?></div>
                                <div class="stat-label">Đã duyệt</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                                <div class="stat-label">Người dùng</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card admin-card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clock"></i>
                            Hoạt động gần đây
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-home text-primary"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">
                                            <?php echo htmlspecialchars(substr($activity['title'], 0, 30)); ?>...
                                        </div>
                                        <div class="activity-meta">
                                            <span class="badge badge-<?php echo $activity['approve'] ? 'success' : 'warning'; ?>">
                                                <?php echo $activity['approve'] ? 'Đã duyệt' : 'Chờ duyệt'; ?>
                                            </span>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .profile-avatar {
        position: relative;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .stat-item {
        text-align: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .stat-value {
        font-size: 24px;
        font-weight: bold;
        color: #007bff;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 12px;
        color: #6c757d;
        text-transform: uppercase;
    }

    .activity-list {
        max-height: 300px;
        overflow-y: auto;
    }

    .activity-item {
        display: flex;
        align-items: flex-start;
        padding: 10px 0;
        border-bottom: 1px solid #e9ecef;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-icon {
        width: 30px;
        text-align: center;
        margin-right: 10px;
        margin-top: 2px;
    }

    .activity-content {
        flex: 1;
    }

    .activity-title {
        font-weight: 500;
        margin-bottom: 5px;
    }

    .activity-meta {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .admin-page-header {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e9ecef;
    }

    .admin-page-title {
        color: #2c3e50;
        font-size: 28px;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .admin-page-subtitle {
        color: #6c757d;
        margin-bottom: 0;
    }
</style>

<script>
    $(document).ready(function() {
        // Enhanced Avatar Upload with Drag & Drop
        const uploadZone = $('.upload-zone');
        const fileInput = $('#avatar');
        const uploadBtn = $('#uploadBtn');

        // File input change handler
        fileInput.on('change', function() {
            handleFileSelect(this.files[0]);
        });

        // Drag and drop handlers
        uploadZone.on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('drag-over');
        });

        uploadZone.on('dragleave dragend', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
        });

        uploadZone.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');

            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelect(files[0]);
            }
        });

        function handleFileSelect(file) {
            if (!file) return;

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                showAlert('Chỉ chấp nhận file ảnh (JPG, PNG, GIF)', 'danger');
                return;
            }

            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                showAlert('Kích thước file không được vượt quá 5MB', 'danger');
                return;
            }

            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewHtml = `
                    <div class="upload-preview">
                        <img src="${e.target.result}" class="preview-image" alt="Preview">
                        <div class="preview-overlay">
                            <p class="mb-1"><strong>${file.name}</strong></p>
                            <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                        </div>
                    </div>
                `;
                uploadZone.html(previewHtml);
                uploadBtn.show();
            };
            reader.readAsDataURL(file);

            // Set file to input
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            fileInput[0].files = dataTransfer.files;
        }

        // Avatar form submission with progress
        $('#avatarForm').on('submit', function(e) {
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();

            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang tải lên...');

            // Create progress bar
            const progressHtml = `
                <div class="upload-progress mt-2">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            `;
            uploadZone.after(progressHtml);

            // Simulate upload progress
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 30;
                if (progress > 90) progress = 90;
                $('.progress-bar').css('width', progress + '%');
            }, 200);

            // Re-enable button after form submission
            setTimeout(() => {
                clearInterval(progressInterval);
                $('.progress-bar').css('width', '100%');
                setTimeout(() => {
                    submitBtn.prop('disabled', false).html(originalText);
                    $('.upload-progress').remove();
                }, 500);
            }, 2000);
        });

        // Click avatar to upload
        $('.profile-avatar-wrapper').on('click', function() {
            fileInput.click();
        });

        // Enhanced Password strength indicator with visual bar
        $('#new_password').on('input', function() {
            const password = $(this).val();
            let strength = 0;
            let strengthText = '';
            let strengthClass = '';

            // Check password criteria
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            // Remove existing strength indicator
            $('#password-strength-container').remove();

            if (password.length > 0) {
                switch (strength) {
                    case 0:
                    case 1:
                        strengthText = 'Rất yếu';
                        strengthClass = 'bg-danger';
                        break;
                    case 2:
                        strengthText = 'Yếu';
                        strengthClass = 'bg-warning';
                        break;
                    case 3:
                        strengthText = 'Trung bình';
                        strengthClass = 'bg-info';
                        break;
                    case 4:
                        strengthText = 'Mạnh';
                        strengthClass = 'bg-success';
                        break;
                    case 5:
                        strengthText = 'Rất mạnh';
                        strengthClass = 'bg-success';
                        break;
                }

                const strengthHtml = `
                    <div id="password-strength-container" class="password-strength-container">
                        <div class="password-strength-bar">
                            <div class="password-strength-fill ${strengthClass}" style="width: ${strength * 20}%"></div>
                        </div>
                        <div class="password-strength-text">
                            <small class="text-muted">Độ mạnh: <span class="${strengthClass.replace('bg-', 'text-')}">${strengthText}</span></small>
                        </div>
                    </div>
                `;
                $(this).after(strengthHtml);
            }
        });

        // Enhanced Password confirmation validation
        $('#confirm_password').on('input', function() {
            const password = $('#new_password').val();
            const confirmPassword = $(this).val();

            // Remove existing feedback
            $(this).removeClass('is-invalid is-valid');
            $(this).next('.invalid-feedback, .valid-feedback').remove();

            if (confirmPassword === '') {
                return;
            }

            if (password === confirmPassword) {
                $(this).addClass('is-valid');
                $(this).after('<div class="valid-feedback"><i class="fas fa-check"></i> Mật khẩu khớp</div>');
            } else {
                $(this).addClass('is-invalid');
                $(this).after('<div class="invalid-feedback"><i class="fas fa-times"></i> Mật khẩu không khớp</div>');
            }
        });

        // Email validation with visual feedback
        $('#email').on('blur input', function() {
            const email = $(this).val().trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            $(this).removeClass('is-invalid is-valid');
            $(this).next('.invalid-feedback, .valid-feedback').remove();

            if (email.length > 0) {
                if (!emailRegex.test(email)) {
                    $(this).addClass('is-invalid');
                    $(this).after('<div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Email không hợp lệ</div>');
                } else {
                    $(this).addClass('is-valid');
                    $(this).after('<div class="valid-feedback"><i class="fas fa-check"></i> Email hợp lệ</div>');
                }
            }
        });

        // Phone validation with Vietnamese format
        $('#phone').on('blur input', function() {
            const phone = $(this).val().trim();
            const phoneRegex = /^(0|\+84)[3|5|7|8|9][0-9]{8}$/;

            $(this).removeClass('is-invalid is-valid');
            $(this).next('.invalid-feedback, .valid-feedback').remove();

            if (phone.length > 0) {
                if (!phoneRegex.test(phone)) {
                    $(this).addClass('is-invalid');
                    $(this).after('<div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Số điện thoại không hợp lệ (VD: 0901234567)</div>');
                } else {
                    $(this).addClass('is-valid');
                    $(this).after('<div class="valid-feedback"><i class="fas fa-check"></i> Số điện thoại hợp lệ</div>');
                }
            }
        });

        // Utility function to show custom alerts
        function showAlert(message, type) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'}"></i>
                    ${message}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `;

            // Remove existing alerts
            $('.alert:not(.show)').remove();

            // Add new alert at top
            $('.container-fluid').prepend(alertHtml);

            // Auto dismiss after 5 seconds
            setTimeout(() => {
                $('.alert').fadeOut(500, function() {
                    $(this).remove();
                });
            }, 5000);

            // Smooth scroll to top
            $('html, body').animate({
                scrollTop: 0
            }, 300);
        }

        // Enhanced form validation with loading states
        $('#profileForm').on('submit', function(e) {
            const name = $('#name').val().trim();
            const email = $('#email').val().trim();

            if (!name) {
                e.preventDefault();
                $('#name').focus();
                showAlert('Vui lòng nhập họ và tên', 'danger');
                return false;
            }

            if (!email) {
                e.preventDefault();
                $('#email').focus();
                showAlert('Vui lòng nhập email', 'danger');
                return false;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                $('#email').focus();
                showAlert('Email không hợp lệ', 'danger');
                return false;
            }

            // Show loading state
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang cập nhật...');

            // Re-enable button after timeout (failsafe)
            setTimeout(() => {
                submitBtn.prop('disabled', false).html(originalText);
            }, 10000);
        });

        // Enhanced password form validation
        $('#passwordForm').on('submit', function(e) {
            const currentPassword = $('#current_password').val().trim();
            const newPassword = $('#new_password').val();
            const confirmPassword = $('#confirm_password').val();

            if (!currentPassword) {
                e.preventDefault();
                $('#current_password').focus();
                showAlert('Vui lòng nhập mật khẩu hiện tại', 'danger');
                return false;
            }

            if (!newPassword) {
                e.preventDefault();
                $('#new_password').focus();
                showAlert('Vui lòng nhập mật khẩu mới', 'danger');
                return false;
            }

            if (newPassword.length < 6) {
                e.preventDefault();
                $('#new_password').focus();
                showAlert('Mật khẩu mới phải có ít nhất 6 ký tự', 'danger');
                return false;
            }

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                $('#confirm_password').focus();
                showAlert('Mật khẩu xác nhận không khớp', 'danger');
                return false;
            }

            // Show loading state
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang đổi mật khẩu...');

            // Re-enable button after timeout (failsafe)
            setTimeout(() => {
                submitBtn.prop('disabled', false).html(originalText);
            }, 10000);
        });

        // Auto dismiss existing alerts with smooth animation
        setTimeout(function() {
            $('.alert').fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);

        // Smooth scroll to alerts if they exist
        if ($('.alert').length) {
            $('html, body').animate({
                scrollTop: $('.alert').first().offset().top - 100
            }, 500);
        }

        // Initialize tooltips and popovers with enhanced options
        $('[data-toggle="tooltip"]').tooltip({
            trigger: 'hover',
            delay: {
                show: 500,
                hide: 100
            }
        });

        $('[data-toggle="popover"]').popover({
            trigger: 'click',
            html: true
        });

        // Animate statistics counters
        $('.stat-value').each(function() {
            const $this = $(this);
            const countTo = parseInt($this.text().replace(/,/g, ''));

            if (!isNaN(countTo)) {
                $this.text('0');
                $({
                    countNum: 0
                }).animate({
                    countNum: countTo
                }, {
                    duration: 2000,
                    easing: 'swing',
                    step: function() {
                        $this.text(Math.floor(this.countNum).toLocaleString());
                    },
                    complete: function() {
                        $this.text(countTo.toLocaleString());
                    }
                });
            }
        });

        // Enhanced activity feed interactions
        $('.activity-item').on('mouseenter', function() {
            $(this).css('background-color', '#f8f9fa');
        }).on('mouseleave', function() {
            $(this).css('background-color', 'transparent');
        });

        // Form field focus animations
        $('.form-control').on('focus', function() {
            $(this).parent().addClass('form-group-focused');
        }).on('blur', function() {
            $(this).parent().removeClass('form-group-focused');
        });

        // Add loading overlay for forms
        function showLoadingOverlay(text = 'Đang xử lý...') {
            const overlay = `
                <div class="loading-overlay" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(255, 255, 255, 0.9);
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-direction: column;
                ">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">${text}</p>
                </div>
            `;
            $('body').append(overlay);
        }

        function hideLoadingOverlay() {
            $('.loading-overlay').fadeOut(300, function() {
                $(this).remove();
            });
        }

        // Auto-save functionality for forms (optional)
        let autoSaveTimeout;
        $('.form-control').on('input', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                // Could implement auto-save here
                console.log('Auto-save triggered');
            }, 2000);
        });

        // Enhanced keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + S to save profile
            if ((e.ctrlKey || e.metaKey) && e.which === 83) {
                e.preventDefault();
                if ($('#profileForm').is(':visible')) {
                    $('#profileForm').submit();
                }
            }
        });

        // Add subtle animations on page load
        $('.card').each(function(index) {
            $(this).css({
                'opacity': '0',
                'transform': 'translateY(20px)'
            }).delay(index * 100).animate({
                'opacity': '1'
            }, 500, function() {
                $(this).css('transform', 'translateY(0)');
            });
        });
    });
</script>

<?php include '../Components/admin_footer.php'; ?>