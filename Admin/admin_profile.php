<?php
session_start();
require_once '../config/db.php';

// Kiểm tra đăng nhập với quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header('Location: ../Auth/login.php?message=Bạn cần đăng nhập với tài khoản admin');
    exit();
}

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
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

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
                            if ($user['avatar'] && $user['avatar'] != 'images/default-avatar.jpg') {
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

            // Xử lý mật khẩu
            $password_updated = false;
            $password_update_query = "";

            if (!empty($current_password) && !empty($new_password)) {
                // Kiểm tra mật khẩu hiện tại
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_data = $result->fetch_assoc();

                if (password_verify($current_password, $user_data['password'])) {
                    if ($new_password === $confirm_password) {
                        // Mã hóa mật khẩu mới
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $password_update_query = ", password = '$hashed_password'";
                        $password_updated = true;
                    } else {
                        $error_message = "Mật khẩu mới và xác nhận mật khẩu không khớp.";
                    }
                } else {
                    $error_message = "Mật khẩu hiện tại không đúng.";
                }
            }

            // Nếu không có lỗi, tiến hành cập nhật thông tin vào CSDL
            if (empty($error_message)) {
                // Tạo câu lệnh UPDATE
                $update_query = "UPDATE users SET 
                                name = '$name',
                                username = '$username',
                                email = '$email',
                                phone = '$phone',
                                avatar = '$avatar_path'
                                $password_update_query
                                WHERE id = $user_id";

                if (mysqli_query($conn, $update_query)) {
                    $success_message = "Thông tin đã được cập nhật thành công!";
                    if ($password_updated) {
                        $success_message .= " Mật khẩu đã được thay đổi.";
                    }

                    // Cập nhật lại thông tin người dùng trong session
                    $_SESSION['username'] = $username;
                    $_SESSION['name'] = $name;

                    // Lấy lại thông tin người dùng để hiển thị
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                } else {
                    $error_message = "Có lỗi xảy ra khi cập nhật thông tin: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Tiêu đề trang
$page_title = "Hồ sơ Admin";
include_once '../Components/admin_header.php';
?>

<style>
    .admin-profile-container {
        padding: 2rem;
    }

    .profile-header {
        padding: 2.5rem;
        margin-bottom: 2rem;
        border-radius: 15px;
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
        box-shadow: 0 8px 15px rgba(78, 115, 223, 0.25);
    }

    .profile-photo {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        border: 5px solid rgba(255, 255, 255, 0.3);
        object-fit: cover;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        transition: transform 0.3s ease;
    }

    .profile-photo:hover {
        transform: scale(1.05);
    }

    .profile-info {
        margin-left: 2rem;
    }

    .profile-tabs {
        margin-bottom: 2rem;
    }

    .profile-tabs .nav-link {
        border-radius: 10px 10px 0 0;
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        color: #5a5c69;
        border: none;
        transition: all 0.3s ease;
    }

    .profile-tabs .nav-link.active {
        color: #4e73df;
        border-bottom: 3px solid #4e73df;
        background-color: #f8f9fc;
    }

    .profile-tabs .nav-link:hover:not(.active) {
        background-color: #eaecf4;
    }

    .tab-content {
        background-color: white;
        border-radius: 10px;
        padding: 2rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }

    .form-card {
        border: none;
        border-radius: 10px;
    }

    .form-card .card-header {
        background: linear-gradient(45deg, #1cc88a 0%, #36b9cc 100%);
        color: white;
        font-weight: 600;
        border-radius: 10px 10px 0 0;
    }

    .form-control {
        border-radius: 7px;
        transition: all 0.2s ease;
    }

    .form-control:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }

    .btn-gradient-primary {
        background: linear-gradient(45deg, #4e73df 0%, #224abe 100%);
        border: none;
        color: white;
        border-radius: 7px;
        padding: 0.5rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-gradient-primary:hover {
        background: linear-gradient(45deg, #224abe 0%, #4e73df 100%);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
    }

    .stats-card {
        border-left: 4px solid;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .stats-card-primary {
        border-left-color: #4e73df;
        background: linear-gradient(45deg, #f8f9fc 0%, #eaecf4 100%);
    }

    .stats-card-success {
        border-left-color: #1cc88a;
        background: linear-gradient(45deg, #f8f9fc 0%, #eaecf4 100%);
    }

    .stats-card-info {
        border-left-color: #36b9cc;
        background: linear-gradient(45deg, #f8f9fc 0%, #eaecf4 100%);
    }

    .stats-card-warning {
        border-left-color: #f6c23e;
        background: linear-gradient(45deg, #f8f9fc 0%, #eaecf4 100%);
    }

    .stats-icon {
        font-size: 2rem;
        color: #5a5c69;
        opacity: 0.3;
    }

    .stats-title {
        color: #5a5c69;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 0.25rem;
    }

    .stats-value {
        color: #5a5c69;
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0;
    }

    .custom-file-label {
        border-radius: 7px;
    }

    .activity-item {
        display: flex;
        border-left: 2px solid #e3e6f0;
        padding-left: 1.5rem;
        position: relative;
        margin-bottom: 1.5rem;
    }

    .activity-item::before {
        content: '';
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: #4e73df;
        position: absolute;
        left: -7px;
        top: 5px;
    }

    .activity-time {
        font-size: 0.8rem;
        color: #858796;
    }
</style>

<div class="admin-profile-container">
    <div class="page-header mb-4">
        <h1 class="h2 text-gray-800 font-weight-bold">
            <i class="fas fa-user-circle mr-2"></i> Hồ sơ của tôi
        </h1>
        <p class="text-muted">Quản lý thông tin cá nhân và tài khoản của bạn</p>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i> <?php echo $success_message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="profile-header d-flex align-items-center">
        <div>
            <?php if (!empty($user['avatar'])): ?>
                <img class="profile-photo" src="../<?php echo $user['avatar']; ?>" alt="<?php echo $user['name']; ?>">
            <?php else: ?>
                <img class="profile-photo" src="../images/default-avatar.jpg" alt="<?php echo $user['name']; ?>">
            <?php endif; ?>
        </div>
        <div class="profile-info">
            <h2 class="font-weight-bold"><?php echo $user['name']; ?></h2>
            <p class="mb-2"><i class="fas fa-user-tag mr-2"></i> Admin</p>
            <p class="mb-2"><i class="fas fa-envelope mr-2"></i> <?php echo $user['email']; ?></p>
            <p class="mb-3"><i class="fas fa-phone-alt mr-2"></i> <?php echo $user['phone']; ?></p>
            <button class="btn btn-light" data-toggle="modal" data-target="#updateAvatarModal">
                <i class="fas fa-camera mr-2"></i> Thay đổi ảnh
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card stats-card-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-title">Tổng phòng</div>
                        <div class="stats-value">
                            <?php
                            $room_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM motel");
                            echo mysqli_fetch_assoc($room_count)['count'];
                            ?>
                        </div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-building"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card stats-card-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-title">Đã duyệt</div>
                        <div class="stats-value">
                            <?php
                            $approved_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM motel WHERE approve = 1");
                            echo mysqli_fetch_assoc($approved_count)['count'];
                            ?>
                        </div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card stats-card-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-title">Người dùng</div>
                        <div class="stats-value">
                            <?php
                            $user_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
                            echo mysqli_fetch_assoc($user_count)['count'];
                            ?>
                        </div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card stats-card-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-title">Chờ duyệt</div>
                        <div class="stats-value">
                            <?php
                            $pending_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM motel WHERE approve = 0");
                            echo mysqli_fetch_assoc($pending_count)['count'];
                            ?>
                        </div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs profile-tabs" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="info-tab" data-toggle="tab" href="#info" role="tab">
                <i class="fas fa-id-card mr-2"></i> Thông tin cá nhân
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="security-tab" data-toggle="tab" href="#security" role="tab">
                <i class="fas fa-lock mr-2"></i> Bảo mật
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="activity-tab" data-toggle="tab" href="#activity" role="tab">
                <i class="fas fa-history mr-2"></i> Hoạt động gần đây
            </a>
        </li>
    </ul>

    <div class="tab-content" id="profileTabsContent">
        <!-- Thông tin cá nhân -->
        <div class="tab-pane fade show active" id="info" role="tabpanel">
            <div class="card form-card">
                <div class="card-header">
                    <i class="fas fa-user-edit mr-2"></i> Cập nhật thông tin cá nhân
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data" id="profile-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name"><i class="fas fa-user mr-1"></i> Họ và tên</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username"><i class="fas fa-user-tag mr-1"></i> Tên đăng nhập</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email"><i class="fas fa-envelope mr-1"></i> Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone"><i class="fas fa-phone-alt mr-1"></i> Số điện thoại</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group text-right mt-4">
                            <button type="submit" class="btn btn-gradient-primary">
                                <i class="fas fa-save mr-1"></i> Lưu thông tin
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Bảo mật -->
        <div class="tab-pane fade" id="security" role="tabpanel">
            <div class="card form-card">
                <div class="card-header">
                    <i class="fas fa-lock mr-2"></i> Thay đổi mật khẩu
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="password-form">
                        <input type="hidden" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                        <input type="hidden" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">

                        <div class="form-group">
                            <label for="current_password"><i class="fas fa-key mr-1"></i> Mật khẩu hiện tại</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password"><i class="fas fa-lock mr-1"></i> Mật khẩu mới</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <small class="form-text text-muted">Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ, số và ký tự đặc biệt.</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password"><i class="fas fa-lock mr-1"></i> Xác nhận mật khẩu mới</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i> Sau khi thay đổi mật khẩu, bạn có thể cần đăng nhập lại.
                        </div>

                        <div class="form-group text-right mt-4">
                            <button type="submit" class="btn btn-gradient-primary">
                                <i class="fas fa-key mr-1"></i> Cập nhật mật khẩu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Hoạt động gần đây -->
        <div class="tab-pane fade" id="activity" role="tabpanel">
            <div class="card form-card">
                <div class="card-header">
                    <i class="fas fa-history mr-2"></i> Hoạt động gần đây
                </div>
                <div class="card-body">
                    <div class="activity-timeline">
                        <?php
                        // Lấy các phòng trọ được duyệt gần đây bởi admin này
                        $activity_query = "SELECT m.id, m.title, m.updated_at, u.name
                                          FROM motel m 
                                          JOIN users u ON m.user_id = u.id 
                                          WHERE m.approve = 1
                                          ORDER BY m.updated_at DESC 
                                          LIMIT 10";
                        $activity_result = mysqli_query($conn, $activity_query);

                        if (mysqli_num_rows($activity_result) > 0):
                            while ($activity = mysqli_fetch_assoc($activity_result)):
                        ?>
                                <div class="activity-item">
                                    <div>
                                        <p class="mb-1"><strong>Duyệt phòng: </strong> <?php echo htmlspecialchars($activity['title']); ?></p>
                                        <p class="mb-1">Chủ phòng: <?php echo htmlspecialchars($activity['name']); ?></p>
                                        <p class="activity-time mb-0">
                                            <i class="far fa-clock mr-1"></i>
                                            <?php echo date('H:i - d/m/Y', strtotime($activity['updated_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php
                            endwhile;
                        else:
                            ?>
                            <div class="text-center py-5">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <p>Chưa có hoạt động nào gần đây</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thay đổi ảnh đại diện -->
<div class="modal fade" id="updateAvatarModal" tabindex="-1" role="dialog" aria-labelledby="updateAvatarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(45deg, #4e73df 0%, #224abe 100%); color: white;">
                <h5 class="modal-title" id="updateAvatarModalLabel">
                    <i class="fas fa-camera mr-2"></i> Thay đổi ảnh đại diện
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" enctype="multipart/form-data" id="avatar-form">
                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">

                    <div class="text-center mb-4">
                        <?php if (!empty($user['avatar'])): ?>
                            <img id="avatar-preview" src="../<?php echo $user['avatar']; ?>" alt="Avatar Preview" class="img-thumbnail rounded-circle" style="width: 200px; height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <img id="avatar-preview" src="../images/default-avatar.jpg" alt="Avatar Preview" class="img-thumbnail rounded-circle" style="width: 200px; height: 200px; object-fit: cover;">
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="avatar" name="avatar" accept="image/*" onchange="previewAvatar(this);">
                            <label class="custom-file-label" for="avatar">Chọn file ảnh...</label>
                        </div>
                        <small class="form-text text-muted">Chấp nhận file: JPG, JPEG, PNG, GIF (tối đa 5MB)</small>
                    </div>

                    <div class="form-group text-right mt-4">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i> Hủy
                        </button>
                        <button type="submit" class="btn btn-gradient-primary">
                            <i class="fas fa-upload mr-1"></i> Lưu ảnh
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Hiển thị tên file ảnh đã chọn
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass('selected').html(fileName);
    });

    // Preview ảnh đại diện khi chọn file
    function previewAvatar(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function(e) {
                $('#avatar-preview').attr('src', e.target.result);
            }

            reader.readAsDataURL(input.files[0]);
        }
    }

    // Thêm hiệu ứng khi hover vào các card
    $(document).ready(function() {
        // Hiệu ứng cho stats card
        $('.stats-card').hover(function() {
            $(this).css('transform', 'translateY(-5px)');
            $(this).css('box-shadow', '0 5px 15px rgba(0,0,0,0.1)');
        }, function() {
            $(this).css('transform', 'translateY(0)');
            $(this).css('box-shadow', 'none');
        });

        // Validation cho form đổi mật khẩu
        $('#password-form').on('submit', function(e) {
            var newPassword = $('#new_password').val();
            var confirmPassword = $('#confirm_password').val();

            if (newPassword != confirmPassword) {
                e.preventDefault();
                alert('Mật khẩu mới và xác nhận mật khẩu không khớp!');
                return false;
            }

            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Mật khẩu phải có ít nhất 8 ký tự!');
                return false;
            }

            return true;
        });
    });
</script>

<?php include_once '../Components/admin_footer.php'; ?>