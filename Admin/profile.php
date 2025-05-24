<?php
session_start();
require_once '../config/db.php';

// Kiểm tra đăng nhập với quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header('Location: ../Auth/login.php?message=Bạn cần đăng nhập với tài khoản admin');
    exit();
}

// Lấy thông tin admin từ database
$admin_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ? AND role = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

// Lấy số phòng mà admin đã duyệt
$query = "SELECT COUNT(*) as approved_rooms FROM motel WHERE approve = 1";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$approved_rooms = $row['approved_rooms'];

// Lấy số phòng đã hủy
$query = "SELECT COUNT(*) as cancelled FROM motel WHERE approve = 2";
$result = mysqli_query($conn, $query);
$row_cancelled = mysqli_fetch_assoc($result);
$cancelled_rooms = $row_cancelled['cancelled'];

// Lấy số phòng chờ duyệt
$query = "SELECT COUNT(*) as pending_rooms FROM motel WHERE approve = 0";
$result = mysqli_query($conn, $query);
$row_pending = mysqli_fetch_assoc($result);
$pending_rooms = $row_pending['pending_rooms'];

// Lấy tổng số người dùng
$query = "SELECT COUNT(*) as total_users FROM users";
$result = mysqli_query($conn, $query);
$row_users = mysqli_fetch_assoc($result);
$total_users = $row_users['total_users'];

$page_title = "Thông tin tài khoản";
include_once '../components/admin_header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .profile-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .profile-header {
        padding: 20px;
        border-bottom: 1px solid #eee;
        position: relative;
    }

    .profile-body {
        padding: 20px;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .change-avatar {
        position: absolute;
        bottom: 0;
        left: 110px;
        width: 30px;
        height: 30px;
        background: #4e73df;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .info-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .info-item {
        padding: 10px 0;
        border-bottom: 1px solid #f1f1f1;
        display: flex;
        align-items: center;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: bold;
        width: 150px;
        color: #555;
    }

    .info-value {
        flex: 1;
    }

    .info-icon {
        margin-right: 10px;
        width: 20px;
        text-align: center;
        color: #4e73df;
    }

    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .stat-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        padding: 15px;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
    }

    .stat-info {
        flex: 1;
    }

    .stat-title {
        font-size: 0.9rem;
        color: #555;
        margin-bottom: 5px;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: bold;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-right: 15px;
    }

    .primary-bg {
        background-color: rgba(78, 115, 223, 0.1);
        color: #4e73df;
    }

    .success-bg {
        background-color: rgba(28, 200, 138, 0.1);
        color: #1cc88a;
    }

    .warning-bg {
        background-color: rgba(246, 194, 62, 0.1);
        color: #f6c23e;
    }

    .danger-bg {
        background-color: rgba(231, 74, 59, 0.1);
        color: #e74a3b;
    }

    .custom-file-upload {
        display: none;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .chart-container {
        height: 300px;
    }
</style>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Thông tin tài khoản</h1>
    </div>

    <div class="row">
        <!-- Thông tin cá nhân -->
        <div class="col-md-6 mb-4">
            <div class="profile-card">
                <div class="profile-header d-flex align-items-center">
                    <img src="../<?php echo $admin['avatar'] ? $admin['avatar'] : 'uploads/avatar/default-avatar.jpg'; ?>" alt="Avatar" class="profile-avatar">
                    <div class="change-avatar" onclick="document.getElementById('avatar-upload').click();">
                        <i class="fas fa-camera"></i>
                    </div>
                    <form id="avatar-form" action="../Auth/edit_profile.php" method="post" enctype="multipart/form-data">
                        <input type="file" name="avatar" id="avatar-upload" class="custom-file-upload" onchange="document.getElementById('avatar-form').submit();">
                    </form>
                    <div class="ml-3">
                        <h4 class="mb-1"><?php echo htmlspecialchars($admin['name']); ?></h4>
                        <p class="text-light mb-0"><i class="fas fa-shield-alt mr-1"></i> Quản trị viên</p>
                    </div>
                </div>
                <div class="profile-body">
                    <ul class="info-list">
                        <li class="info-item">
                            <div class="info-icon"><i class="fas fa-envelope"></i></div>
                            <div class="info-label">Email:</div>
                            <div class="info-value"><?php echo htmlspecialchars($admin['email']); ?></div>
                        </li>
                        <li class="info-item">
                            <div class="info-icon"><i class="fas fa-user"></i></div>
                            <div class="info-label">Tên đăng nhập:</div>
                            <div class="info-value"><?php echo htmlspecialchars($admin['username']); ?></div>
                        </li>
                        <li class="info-item">
                            <div class="info-icon"><i class="fas fa-phone"></i></div>
                            <div class="info-label">Số điện thoại:</div>
                            <div class="info-value"><?php echo htmlspecialchars($admin['phone']); ?></div>
                        </li>
                    </ul>

                    <div class="action-buttons">
                        <a href="../Auth/edit_profile.php" class="btn btn-primary">
                            <i class="fas fa-pencil-alt mr-1"></i> Chỉnh sửa thông tin
                        </a>
                        <a href="../Auth/logout.php" class="btn btn-secondary">
                            <i class="fas fa-sign-out-alt mr-1"></i> Đăng xuất
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thống kê phòng -->
        <div class="col-md-6 mb-4">
            <div class="profile-card h-100">
                <div class="profile-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie mr-2"></i> Thống kê phòng trọ</h5>
                </div>
                <div class="profile-body">
                    <div class="chart-container mb-3">
                        <canvas id="roomStatusChart"></canvas>
                    </div>
                    <div class="text-center">
                        <p class="mb-1"><span class="badge badge-success mr-1">■</span> Đã duyệt: <?php echo $approved_rooms; ?> phòng</p>
                        <p class="mb-1"><span class="badge badge-warning mr-1">■</span> Chờ duyệt: <?php echo $pending_rooms; ?> phòng</p>
                        <p class="mb-0"><span class="badge badge-danger mr-1">■</span> Đã hủy: <?php echo $cancelled_rooms; ?> phòng</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thống kê nhanh -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon primary-bg">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <div class="stat-title">Phòng đã duyệt</div>
                <div class="stat-value"><?php echo $approved_rooms; ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon warning-bg">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <div class="stat-title">Phòng chờ duyệt</div>
                <div class="stat-value"><?php echo $pending_rooms; ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon danger-bg">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-info">
                <div class="stat-title">Phòng đã hủy</div>
                <div class="stat-value"><?php echo $cancelled_rooms; ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon success-bg">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <div class="stat-title">Tổng người dùng</div>
                <div class="stat-value"><?php echo $total_users; ?></div>
            </div>
        </div>
    </div>

    <!-- Truy cập nhanh -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Truy cập nhanh</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="rooms/pending_rooms.php" class="btn btn-warning btn-block py-3">
                                <i class="fas fa-clipboard-check mr-1"></i> Duyệt phòng trọ
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="rooms/manage_rooms.php" class="btn btn-primary btn-block py-3">
                                <i class="fas fa-building mr-1"></i> Quản lý phòng trọ
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="users/manage_users.php" class="btn btn-success btn-block py-3">
                                <i class="fas fa-users mr-1"></i> Quản lý người dùng
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="../index.php" target="_blank" class="btn btn-info btn-block py-3">
                                <i class="fas fa-home mr-1"></i> Xem trang chủ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Biểu đồ thống kê phòng trọ
    var ctx = document.getElementById('roomStatusChart').getContext('2d');
    var roomStatusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Đã duyệt', 'Chờ duyệt', 'Đã hủy'],
            datasets: [{
                label: 'Số phòng',
                data: [<?php echo $approved_rooms; ?>, <?php echo $pending_rooms; ?>, <?php echo $cancelled_rooms; ?>],
                backgroundColor: [
                    '#1cc88a',
                    '#f6c23e',
                    '#e74a3b'
                ],
                borderColor: [
                    '#169872',
                    '#dda20a',
                    '#be2617'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.raw + ' phòng';
                        }
                    }
                }
            }
        }
    });
</script>

<?php include_once '../components/admin_footer.php'; ?>