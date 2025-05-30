<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/config.php';

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header('Location: ../../auth/login.php?message=Bạn cần đăng nhập với tài khoản admin');
    exit();
}

// Lọc
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$start = ($page - 1) * $limit;

$where = '1=1';
if ($status !== '') {
    $where .= " AND b.status = '" . mysqli_real_escape_string($conn, $status) . "'";
}
if ($search !== '') {
    $search_esc = mysqli_real_escape_string($conn, $search);
    $where .= " AND (u.name LIKE '%$search_esc%' OR u.email LIKE '%$search_esc%' OR u.phone LIKE '%$search_esc%' OR m.title LIKE '%$search_esc%' OR m.address LIKE '%$search_esc%')";
}

// Đếm tổng số booking
$count_sql = "SELECT COUNT(*) as total FROM bookings b JOIN users u ON b.user_id = u.id JOIN motel m ON b.motel_id = m.id WHERE $where";
$count_result = mysqli_query($conn, $count_sql);

if (!$count_result) {
    $error_message = "Lỗi truy vấn: " . mysqli_error($conn);
} else {
    $total = mysqli_fetch_assoc($count_result)['total'];
    $total_pages = ceil($total / $limit);

    // Lấy danh sách booking
    $sql = "SELECT b.*, u.name as user_name, m.title as motel_title FROM bookings b JOIN users u ON b.user_id = u.id JOIN motel m ON b.motel_id = m.id WHERE $where ORDER BY b.created_at DESC LIMIT $start, $limit";
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        $error_message = "Lỗi truy vấn: " . mysqli_error($conn);
    }
}

include_once '../../components/admin_header.php';
?>
<style>
    @media (max-width: 767.98px) {

        .card,
        .card-body,
        .card-header {
            background: #fff !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .table-responsive {
            background: #fff !important;
            border-radius: 8px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table th,
        .table td {
            font-size: 15px;
            padding: 10px 6px;
            white-space: nowrap;
        }

        .badge,
        .btn {
            font-size: 13px !important;
            margin-bottom: 3px;
        }

        .card-header,
        .card-footer {
            padding: 0.75rem 1rem;
        }

        .container-fluid {
            padding-left: 5px;
            padding-right: 5px;
        }
    }

    @media (max-width: 991.98px) {

        .navbar,
        .navbar-header,
        .navbar.navbar-expand,
        .navbar.navbar-expand-lg,
        .navbar.navbar-expand-md {
            background-color: #fff !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            backdrop-filter: none !important;
        }

        .navbar .nav-link,
        .navbar .navbar-brand {
            color: #333 !important;
        }
    }
</style>
<div class="container-fluid mt-4">
    <h2 class="mb-4"><i class="fas fa-money-check-alt mr-2"></i>Quản lý tiền cọc</h2>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo $error_message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if (!empty($search)): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <i class="fas fa-search mr-2"></i>
            Kết quả tìm kiếm cho: <strong><?php echo htmlspecialchars($search); ?></strong>
            <span class="badge badge-pill badge-primary ml-2"><?php echo isset($total) ? $total : 0; ?> kết quả</span>
            <a href="manage_deposits.php<?php echo !empty($status) ? '?status=' . $status : ''; ?>" class="ml-2">Xóa tìm kiếm</a>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-gradient-primary text-white">
            <form class="form-inline row" method="get">
                <div class="col-md-3 mb-2">
                    <select name="status" class="form-control">
                        <option value="">Tất cả trạng thái</option>
                        <option value="PENDING" <?php if ($status == 'PENDING') echo 'selected'; ?>>Chờ thanh toán</option>
                        <option value="SUCCESS" <?php if ($status == 'SUCCESS') echo 'selected'; ?>>Đã đặt cọc</option>
                        <option value="REFUND_REQUESTED" <?php if ($status == 'REFUND_REQUESTED') echo 'selected'; ?>>Yêu cầu hoàn tiền</option>
                        <option value="REFUNDED" <?php if ($status == 'REFUNDED') echo 'selected'; ?>>Đã hoàn tiền</option>
                        <option value="RELEASED" <?php if ($status == 'RELEASED') echo 'selected'; ?>>Đã giải ngân</option>
                        <option value="FAILED" <?php if ($status == 'FAILED') echo 'selected'; ?>>Thất bại</option>
                    </select>
                </div>
                <div class="col-md-5 mb-2">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-primary"></i></span>
                        </div>
                        <input type="text" name="search" class="form-control" placeholder="Tìm kiếm theo tên, email, số điện thoại, phòng trọ..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="fas fa-search mr-1"></i> Tìm kiếm</button>
                </div>
                <div class="col-md-2 mb-2">
                    <a href="manage_deposits.php" class="btn btn-secondary w-100"><i class="fas fa-sync-alt mr-1"></i> Đặt lại</a>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Người đặt</th>
                            <th>Phòng trọ</th>
                            <th>Số tiền cọc</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['motel_title']); ?></td>
                                    <td><?php echo number_format($row['deposit_amount']); ?>₫</td>
                                    <td>
                                        <?php
                                        $status_map = [
                                            'PENDING' => 'secondary',
                                            'SUCCESS' => 'success',
                                            'REFUND_REQUESTED' => 'warning',
                                            'REFUNDED' => 'info',
                                            'RELEASED' => 'primary',
                                            'FAILED' => 'danger',
                                        ];
                                        $badge = isset($status_map[$row['status']]) ? $status_map[$row['status']] : 'secondary';
                                        ?>
                                        <span class="badge badge-<?php echo $badge; ?>"><?php echo $row['status']; ?></span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <a href="deposit_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i> Xem</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Không có giao dịch nào</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (isset($total_pages) && $total_pages > 1): ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <!-- First Page and Previous -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1<?php if ($status) echo '&status=' . urlencode($status); ?><?php if ($search) echo '&search=' . urlencode($search); ?>" aria-label="First">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page - 1); ?><?php if ($status) echo '&status=' . urlencode($status); ?><?php if ($search) echo '&search=' . urlencode($search); ?>" aria-label="Previous">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-angle-double-left"></i></span>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-angle-left"></i></span>
                            </li>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $start_page + 4);

                        if ($end_page - $start_page < 4) {
                            $start_page = max(1, $end_page - 4);
                        }

                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php if ($status) echo '&status=' . urlencode($status); ?><?php if ($search) echo '&search=' . urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next and Last Page -->
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page + 1); ?><?php if ($status) echo '&status=' . urlencode($status); ?><?php if ($search) echo '&search=' . urlencode($search); ?>" aria-label="Next">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php if ($status) echo '&status=' . urlencode($status); ?><?php if ($search) echo '&search=' . urlencode($search); ?>" aria-label="Last">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-angle-right"></i></span>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-angle-double-right"></i></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include_once '../../components/admin_footer.php'; ?>