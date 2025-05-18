<?php
// Khởi tạo phiên làm việc
session_start();

// Kiểm tra xem người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Auth/login.php');
    exit;
}

// Kết nối đến CSDL
require_once('../config/db.php');

// Lấy ID người dùng từ session
$user_id = $_SESSION['user_id'];

// Xử lý xóa phòng trọ
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $room_id = $_GET['id'];
    
    // Kiểm tra xem phòng trọ có thuộc về người dùng không
    $stmt_check = $conn->prepare("SELECT id FROM motel WHERE id = ? AND user_id = ?");
    $stmt_check->bind_param("ii", $room_id, $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        // Lấy đường dẫn hình ảnh để xóa file
        $stmt_img = $conn->prepare("SELECT images FROM motel WHERE id = ?");
        $stmt_img->bind_param("i", $room_id);
        $stmt_img->execute();
        $result_img = $stmt_img->get_result();
        $room_img = $result_img->fetch_assoc();
        
        // Xóa phòng trọ khỏi CSDL
        $stmt_delete = $conn->prepare("DELETE FROM motel WHERE id = ?");
        $stmt_delete->bind_param("i", $room_id);
        
        if ($stmt_delete->execute()) {
            // Xóa các file hình ảnh
            $images_path = explode(',', $room_img['images']);
            foreach ($images_path as $path) {
                $full_path = "../" . $path;
                if (file_exists($full_path)) {
                    unlink($full_path);
                }
            }
            
            $success_message = "Xóa phòng trọ thành công!";
        } else {
            $error_message = "Có lỗi xảy ra khi xóa phòng trọ!";
        }
    } else {
        $error_message = "Bạn không có quyền xóa phòng trọ này!";
    }
}

// Xử lý cập nhật trạng thái phòng trọ
if (isset($_GET['action']) && $_GET['action'] == 'status' && isset($_GET['id']) && isset($_GET['status'])) {
    $room_id = $_GET['id'];
    $new_status = $_GET['status'] == 'available' ? 'available' : 'rented';
    
    // Kiểm tra xem phòng trọ có thuộc về người dùng không
    $stmt_check = $conn->prepare("SELECT id FROM motel WHERE id = ? AND user_id = ?");
    $stmt_check->bind_param("ii", $room_id, $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        // Cập nhật trạng thái
        $stmt_update = $conn->prepare("UPDATE motel SET status = ? WHERE id = ?");
        $stmt_update->bind_param("si", $new_status, $room_id);
        
        if ($stmt_update->execute()) {
            $success_message = "Cập nhật trạng thái phòng trọ thành công!";
        } else {
            $error_message = "Có lỗi xảy ra khi cập nhật trạng thái!";
        }
    } else {
        $error_message = "Bạn không có quyền cập nhật phòng trọ này!";
    }
}

// Lấy danh sách phòng trọ của người dùng
$stmt = $conn->prepare("
    SELECT m.*, COUNT(m.id) as count_view 
    FROM motel m
    WHERE m.user_id = ?
    GROUP BY m.id
    ORDER BY m.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rooms = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phòng trọ của tôi - Phòng trọ sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Assets/style.css">
    <!-- CSS cho trang my rooms -->
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            font-family: 'Lexend', sans-serif;
            padding-top: 70px;
        }
        
        .my-rooms-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .room-card {
            margin-bottom: 20px;
            border: none;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .room-status {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-available {
            background: rgba(25, 135, 84, 0.8);
            color: white;
        }
        
        .status-rented {
            background: rgba(220, 53, 69, 0.8);
            color: white;
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.8);
            color: #212529;
        }
        
        .room-image {
            height: 200px;
        }
        
        .room-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .actions-dropdown .dropdown-toggle::after {
            display: none;
        }
        
        .stats-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            background: #f8f9fa;
            transition: all 0.3s;
        }
        
        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #6c757d;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include '../Components/header.php' ?>

    <main class="py-5">
        <div class="container">
            <h1 class="mb-4"><i class="fas fa-clipboard-list me-2 text-primary"></i>Phòng trọ của tôi</h1>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Thống kê tổng quan -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="stat-item">
                        <div class="stat-value">
                            <?php echo $rooms->num_rows; ?>
                        </div>
                        <div class="stat-label">Tổng số phòng trọ</div>
                    </div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="stat-item">
                        <div class="stat-value">
                            <?php
                                $total_views = 0;
                                $rooms_data = [];
                                while ($room = $rooms->fetch_assoc()) {
                                    $rooms_data[] = $room;
                                    $total_views += $room['count_view'];
                                }
                                echo $total_views;
                            ?>
                        </div>
                        <div class="stat-label">Tổng lượt xem</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-value">
                            <a href="post.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus"></i>
                            </a>
                        </div>
                        <div class="stat-label">Đăng tin mới</div>
                    </div>
                </div>
            </div>
            
            <!-- Danh sách phòng trọ -->
            <div class="my-rooms-container">
                <?php if (count($rooms_data) > 0): ?>
                    <?php foreach ($rooms_data as $room): ?>
                        <div class="card room-card">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <div class="room-image">
                                        <img src="../<?php echo $room['images']; ?>" alt="<?php echo $room['title']; ?>">
                                        <?php 
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            if ($room['approve'] == 0) {
                                                $status_class = 'status-pending';
                                                $status_text = 'Đang chờ duyệt';
                                            } else {
                                                if (isset($room['status']) && $room['status'] == 'rented') {
                                                    $status_class = 'status-rented';
                                                    $status_text = 'Đã cho thuê';
                                                } else {
                                                    $status_class = 'status-available';
                                                    $status_text = 'Còn trống';
                                                }
                                            }
                                        ?>
                                        <span class="room-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h5 class="card-title mb-1">
                                                <a href="room_detail.php?id=<?php echo $room['id']; ?>"><?php echo $room['title']; ?></a>
                                            </h5>
                                            <div class="dropdown actions-dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                                                    <li><a class="dropdown-item" href="room_detail.php?id=<?php echo $room['id']; ?>"><i class="fas fa-eye me-2"></i>Xem chi tiết</a></li>
                                                    <li><a class="dropdown-item" href="edit_room.php?id=<?php echo $room['id']; ?>"><i class="fas fa-edit me-2"></i>Chỉnh sửa</a></li>
                                                    <?php if ($room['approve'] == 1): ?>
                                                        <?php if (isset($room['status']) && $room['status'] == 'available'): ?>
                                                            <li><a class="dropdown-item" href="my_rooms.php?action=status&id=<?php echo $room['id']; ?>&status=rented"><i class="fas fa-check-circle me-2"></i>Đánh dấu đã cho thuê</a></li>
                                                        <?php else: ?>
                                                            <li><a class="dropdown-item" href="my_rooms.php?action=status&id=<?php echo $room['id']; ?>&status=available"><i class="fas fa-history me-2"></i>Đánh dấu còn trống</a></li>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $room['id']; ?>">
                                                            <i class="fas fa-trash me-2"></i>Xóa
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <p class="card-text text-primary mb-1"><?php echo number_format($room['price']); ?> VNĐ/tháng</p>
                                        <p class="card-text small text-muted mb-2">
                                            <i class="fas fa-map-marker-alt me-1"></i> <?php echo $room['address']; ?>
                                        </p>
                                        <div class="room-info mb-3">
                                            <span><i class="fas fa-expand me-1"></i><?php echo $room['area']; ?> m²</span>
                                            <span><i class="fas fa-eye me-1"></i><?php echo $room['count_view']; ?> lượt xem</span>
                                            <span><i class="fas fa-clock me-1"></i>
                                                <?php 
                                                    $date = new DateTime($room['created_at']);
                                                    echo $date->format('d/m/Y'); 
                                                ?>
                                            </span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="room_detail.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i>Xem chi tiết
                                            </a>
                                            <a href="edit_room.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-edit me-1"></i>Chỉnh sửa
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modal Xác nhận xóa -->
                        <div class="modal fade" id="deleteModal<?php echo $room['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Xác nhận xóa</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Bạn có chắc chắn muốn xóa phòng trọ này không?</p>
                                        <p><strong><?php echo $room['title']; ?></strong></p>
                                        <p class="text-danger">Lưu ý: Hành động này không thể hoàn tác!</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                        <a href="my_rooms.php?action=delete&id=<?php echo $room['id']; ?>" class="btn btn-danger">Xóa</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-home-alt fa-4x mb-3 text-muted"></i>
                        <h4>Bạn chưa đăng tin nào</h4>
                        <p>Đăng tin ngay để tiếp cận với nhiều người thuê tiềm năng!</p>
                        <a href="post.php" class="btn btn-primary mt-3">
                            <i class="fas fa-plus me-2"></i>Đăng tin mới
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include '../Components/footer.php' ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
