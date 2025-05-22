<?php
// Khởi tạo phiên làm việc
session_start();

// Kiểm tra vai trò admin
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

// Kết nối đến CSDL
require_once('../config/db.php');

// Lấy danh sách phòng trọ
$stmt = $conn->prepare("
    SELECT m.*, u.name as owner_name, u.email as owner_email, c.name as category_name, d.name as district_name
    FROM motel m 
    LEFT JOIN users u ON m.user_id = u.id
    LEFT JOIN categories c ON m.category_id = c.id
    LEFT JOIN districts d ON m.district_id = d.id
    ORDER BY m.created_at DESC
");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý phòng trọ - Trang Quản trị</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/CaseStudyF4_CNTT/admin/assets/css/admin.css">

    <style>
        .admin-container {
            padding-top: 2rem;
        }

        .status-badge {
            width: 90px;
            text-align: center;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .room-title {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .filter-row {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <?php include 'admin_header.php'; ?>

    <div class="container admin-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-home me-2 text-primary"></i>Quản lý phòng trọ
            </h1>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-filter me-2"></i>Lọc phòng trọ
                </h5>
            </div>
            <div class="card-body filter-row">
                <form id="filterForm" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" id="searchKeyword" placeholder="Tìm theo tiêu đề, địa chỉ...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filterStatus">
                            <option value="">Tất cả trạng thái</option>
                            <option value="0">Chờ duyệt</option>
                            <option value="1">Đã duyệt</option>
                            <option value="2">Đã hủy</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="button" id="filterBtn" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Lọc
                        </button>
                        <button type="button" id="resetBtn" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-redo me-2"></i>Đặt lại
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Danh sách phòng trọ</h5>
                <span class="badge bg-primary"><?php echo $result->num_rows; ?> phòng</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" style="width: 50px;">ID</th>
                                <th scope="col">Tiêu đề</th>
                                <th scope="col">Người đăng</th>
                                <th scope="col">Loại phòng</th>
                                <th scope="col">Giá</th>
                                <th scope="col">Ngày đăng</th>
                                <th scope="col" class="text-center">Trạng thái</th>
                                <th scope="col" class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($room = $result->fetch_assoc()): ?>
                                    <tr class="room-row" data-approve="<?php echo $room['approve']; ?>">
                                        <td><?php echo $room['id']; ?></td>
                                        <td>
                                            <div class="room-title">
                                                <a href="../Home/room_detail.php?id=<?php echo $room['id']; ?>" target="_blank">
                                                    <?php echo $room['title']; ?>
                                                </a>
                                            </div>
                                            <small class="text-muted"><?php echo $room['address']; ?></small>
                                        </td>
                                        <td>
                                            <div><?php echo $room['owner_name']; ?></div>
                                            <small class="text-muted"><?php echo $room['owner_email']; ?></small>
                                        </td>
                                        <td><?php echo $room['category_name'] ?: 'Chưa phân loại'; ?></td>
                                        <td><?php echo number_format($room['price']); ?> đ</td>
                                        <td><?php echo date('d/m/Y', strtotime($room['created_at'])); ?></td>
                                        <td class="text-center">
                                            <?php if ($room['approve'] == 0): ?>
                                                <span class="badge bg-warning status-badge">Chờ duyệt</span>
                                            <?php elseif ($room['approve'] == 1): ?>
                                                <span class="badge bg-success status-badge">Đã duyệt</span>
                                            <?php elseif ($room['approve'] == 2): ?>
                                                <span class="badge bg-danger status-badge">Đã hủy</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Thay đổi trạng thái
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <form action="update_room_status.php" method="POST">
                                                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                                            <input type="hidden" name="status" value="0">
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="fas fa-clock me-2 text-warning"></i>Chờ duyệt
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form action="update_room_status.php" method="POST">
                                                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                                            <input type="hidden" name="status" value="1">
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="fas fa-check-circle me-2 text-success"></i>Duyệt phòng
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form action="update_room_status.php" method="POST">
                                                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                                            <input type="hidden" name="status" value="2">
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="fas fa-ban me-2 text-danger"></i>Hủy phòng
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li>
                                                        <a href="/CaseStudyF4_CNTT/Home/room_detail.php?id=<?php echo $room['id']; ?>" target="_blank" class="dropdown-item">
                                                            <i class="fas fa-eye me-2 text-primary"></i>Xem chi tiết
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">Không có phòng trọ nào</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchKeyword = document.getElementById('searchKeyword');
            const filterStatus = document.getElementById('filterStatus');
            const filterBtn = document.getElementById('filterBtn');
            const resetBtn = document.getElementById('resetBtn');
            const roomRows = document.querySelectorAll('.room-row');

            // Hàm lọc phòng
            function filterRooms() {
                const keyword = searchKeyword.value.toLowerCase();
                const status = filterStatus.value;

                roomRows.forEach(row => {
                    const title = row.querySelector('.room-title').textContent.toLowerCase();
                    const address = row.querySelector('.text-muted').textContent.toLowerCase();
                    const roomStatus = row.getAttribute('data-approve');

                    const matchesKeyword = title.includes(keyword) || address.includes(keyword);
                    const matchesStatus = status === '' || roomStatus === status;

                    if (matchesKeyword && matchesStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            // Sự kiện lọc
            filterBtn.addEventListener('click', filterRooms);

            // Sự kiện đặt lại
            resetBtn.addEventListener('click', function() {
                searchKeyword.value = '';
                filterStatus.value = '';

                roomRows.forEach(row => {
                    row.style.display = '';
                });
            });

            // Tự động lọc khi nhập tìm kiếm
            searchKeyword.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    filterRooms();
                }
            });
        });
    </script>
</body>

</html>