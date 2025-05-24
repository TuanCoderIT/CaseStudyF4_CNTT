<?php
session_start();
require_once '../../config/db.php';

// Kiểm tra quyền truy cập (chỉ admin mới được phép)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    header("Location: ../../auth/login.php");
    exit;
}

// Xử lý lọc theo trạng thái
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$status_condition = '';
$params = [];
$types = '';

if (!empty($status_filter)) {
    $status_condition = "AND b.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = "AND (m.title LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR b.id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ssss';
}

// Xử lý phân trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Truy vấn tổng số bản ghi để phân trang
$count_sql = "SELECT COUNT(*) as total FROM bookings b 
              JOIN motel m ON b.motel_id = m.id 
              JOIN users u ON b.user_id = u.id 
              JOIN users o ON m.user_id = o.id
              WHERE 1=1 $status_condition $search_condition";

$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $limit);

// Reset params và types cho câu query chính
$params_main = $params;
$types_main = $types;
$params_main[] = $offset;
$params_main[] = $limit;
$types_main .= 'ii';

// Truy vấn danh sách đặt cọc
$sql = "SELECT b.*, 
        m.title AS motel_title, 
        m.price AS motel_price,
        m.images AS motel_image,
        u.name AS renter_name, 
        u.email AS renter_email,
        u.phone AS renter_phone,
        o.name AS owner_name,
        o.email AS owner_email,
        o.phone AS owner_phone
        FROM bookings b
        JOIN motel m ON b.motel_id = m.id
        JOIN users u ON b.user_id = u.id
        JOIN users o ON m.user_id = o.id
        WHERE 1=1 $status_condition $search_condition
        ORDER BY b.created_at DESC
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);
if (!empty($params_main)) {
    $stmt->bind_param($types_main, ...$params_main);
}
$stmt->execute();
$result = $stmt->get_result();

// Tổng hợp thống kê
$stats_sql = "SELECT 
    SUM(CASE WHEN b.status = 'PENDING' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN b.status = 'SUCCESS' THEN 1 ELSE 0 END) as success_count,
    SUM(CASE WHEN b.status = 'FAILED' THEN 1 ELSE 0 END) as failed_count,
    SUM(CASE WHEN b.status = 'REFUND_REQUESTED' THEN 1 ELSE 0 END) as refund_requested_count,
    SUM(CASE WHEN b.status = 'RELEASED' THEN 1 ELSE 0 END) as released_count,
    SUM(CASE WHEN b.status = 'REFUNDED' THEN 1 ELSE 0 END) as refunded_count,
    SUM(CASE WHEN b.status = 'SUCCESS' THEN b.deposit_amount ELSE 0 END) as total_success_amount,
    SUM(CASE WHEN b.status = 'RELEASED' THEN b.deposit_amount ELSE 0 END) as total_released_amount,
    SUM(CASE WHEN b.status = 'REFUNDED' THEN b.deposit_amount ELSE 0 END) as total_refunded_amount
    FROM bookings b";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

function formatCurrency($amount)
{
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}

function formatDate($dateString)
{
    $date = new DateTime($dateString);
    return $date->format('d/m/Y H:i:s');
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tiền cọc - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="../../assets/admin/css/admin.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --info-color: #36b9cc;
            --secondary-color: #858796;
            --light-bg: #f8f9fc;
        }

        body {
            background-color: var(--light-bg);
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 30px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .dashboard-stats {
            background: linear-gradient(to right, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.95)),
                url('../../assets/admin/images/pattern-bg.svg');
            background-size: cover;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .dashboard-stats:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
        }

        .stat-card.success::before {
            background-color: var(--success-color);
        }

        .stat-card.pending::before {
            background-color: var(--warning-color);
        }

        .stat-card.failed::before {
            background-color: var(--danger-color);
        }

        .stat-card.released::before {
            background-color: var(--primary-color);
        }

        .stat-card.refunded::before {
            background-color: var(--secondary-color);
        }

        .stat-card h3 {
            margin-bottom: 8px;
            font-size: 26px;
            font-weight: 600;
            color: #333;
        }

        .stat-card p {
            color: #6c757d;
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.2rem 1.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            border-top: 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: rgba(248, 249, 252, 0.5);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .table tr:hover {
            background-color: rgba(78, 115, 223, 0.03);
        }

        .btn {
            border-radius: 5px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-sm {
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .btn-outline-primary {
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-outline-info {
            border: 1px solid var(--info-color);
            color: var(--info-color);
        }

        .btn-outline-info:hover {
            background-color: var(--info-color);
            color: white;
        }

        .pagination {
            margin-top: 2rem;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .pagination .page-link {
            color: var(--primary-color);
            padding: 0.5rem 0.75rem;
            margin: 0 3px;
            border-radius: 5px;
        }

        .pagination .page-item:first-child .page-link,
        .pagination .page-item:last-child .page-link {
            border-radius: 5px;
        }

        .actions-column {
            min-width: 180px;
        }
    </style>
</head>

<body>
    <?php include '../../components/admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-12">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Quản lý tiền cọc</h1>
                </div>
                <div class="dashboard-stats">
                    <h4 class="mb-4"><i class="fas fa-chart-pie me-2"></i>Thống kê tiền cọc</h4>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="stat-card success">
                                <div class="position-absolute end-0 top-0 mt-3 me-3 opacity-25">
                                    <i class="fas fa-check-circle fa-3x"></i>
                                </div>
                                <p><i class="fas fa-coins me-2"></i>Tổng tiền đặt cọc thành công</p>
                                <h3><?= formatCurrency($stats['total_success_amount']) ?></h3>
                                <p><span class="badge bg-success me-1"><?= $stats['success_count'] ?></span> giao dịch</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card released">
                                <div class="position-absolute end-0 top-0 mt-3 me-3 opacity-25">
                                    <i class="fas fa-money-bill-wave fa-3x"></i>
                                </div>
                                <p><i class="fas fa-hand-holding-usd me-2"></i>Tổng tiền đã giải ngân</p>
                                <h3><?= formatCurrency($stats['total_released_amount']) ?></h3>
                                <p><span class="badge bg-primary me-1"><?= $stats['released_count'] ?></span> giao dịch</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card refunded">
                                <div class="position-absolute end-0 top-0 mt-3 me-3 opacity-25">
                                    <i class="fas fa-undo-alt fa-3x"></i>
                                </div>
                                <p><i class="fas fa-exchange-alt me-2"></i>Tổng tiền đã hoàn trả</p>
                                <h3><?= formatCurrency($stats['total_refunded_amount']) ?></h3>
                                <p><span class="badge bg-secondary me-1"><?= $stats['refunded_count'] ?></span> giao dịch</p>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="stat-card pending">
                                <div class="position-absolute end-0 top-0 mt-3 me-3 opacity-25">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                                <p><i class="fas fa-hourglass-half me-2"></i>Đang chờ xử lý</p>
                                <h3><?= $stats['pending_count'] ?></h3>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?= ($total_records > 0) ? ($stats['pending_count'] / $total_records * 100) : 0 ?>%" aria-valuenow="<?= $stats['pending_count'] ?>" aria-valuemin="0" aria-valuemax="<?= $total_records ?>"></div>
                                </div>
                                <p class="mt-2">giao dịch</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card success">
                                <div class="position-absolute end-0 top-0 mt-3 me-3 opacity-25">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                                <p><i class="fas fa-thumbs-up me-2"></i>Đặt cọc thành công</p>
                                <h3><?= $stats['success_count'] ?></h3>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= ($total_records > 0) ? ($stats['success_count'] / $total_records * 100) : 0 ?>%" aria-valuenow="<?= $stats['success_count'] ?>" aria-valuemin="0" aria-valuemax="<?= $total_records ?>"></div>
                                </div>
                                <p class="mt-2">giao dịch</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card failed">
                                <div class="position-absolute end-0 top-0 mt-3 me-3 opacity-25">
                                    <i class="fas fa-times-circle fa-2x"></i>
                                </div>
                                <p><i class="fas fa-thumbs-down me-2"></i>Đặt cọc thất bại</p>
                                <h3><?= $stats['failed_count'] ?></h3>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?= ($total_records > 0) ? ($stats['failed_count'] / $total_records * 100) : 0 ?>%" aria-valuenow="<?= $stats['failed_count'] ?>" aria-valuemin="0" aria-valuemax="<?= $total_records ?>"></div>
                                </div>
                                <p class="mt-2">giao dịch</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card refunded">
                                <div class="position-absolute end-0 top-0 mt-3 me-3 opacity-25">
                                    <i class="fas fa-undo fa-2x"></i>
                                </div>
                                <p><i class="fas fa-hand-holding me-2"></i>Yêu cầu hoàn tiền</p>
                                <h3><?= $stats['refund_requested_count'] ?></h3>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?= ($total_records > 0) ? ($stats['refund_requested_count'] / $total_records * 100) : 0 ?>%" aria-valuenow="<?= $stats['refund_requested_count'] ?>" aria-valuemin="0" aria-valuemax="<?= $total_records ?>"></div>
                                </div>
                                <p class="mt-2">giao dịch</p>
                            </div>
                        </div>
                    </div>
                </div> <!-- Search & Filter -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Tìm kiếm & Lọc dữ liệu</h5>
                    </div>
                    <div class="card-body">
                        <form method="get" action="" class="row g-3 align-items-center">
                            <div class="col-md-5">
                                <label for="search" class="form-label small text-muted mb-1">Tìm kiếm theo tên, email hoặc ID</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" id="search" placeholder="Nhập từ khóa tìm kiếm..." name="search" value="<?= htmlspecialchars($search) ?>">
                                    <button class="btn btn-primary" type="submit">
                                        Tìm kiếm
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label small text-muted mb-1">Lọc theo trạng thái</label>
                                <select name="status" id="status" class="form-select" onchange="this.form.submit()">
                                    <option value="" <?= $status_filter == '' ? 'selected' : '' ?>>Tất cả trạng thái</option>
                                    <option value="PENDING" <?= $status_filter == 'PENDING' ? 'selected' : '' ?>>
                                        <i class="fas fa-hourglass-half"></i> Đang chờ xử lý
                                    </option>
                                    <option value="SUCCESS" <?= $status_filter == 'SUCCESS' ? 'selected' : '' ?>>
                                        <i class="fas fa-check-circle"></i> Đặt cọc thành công
                                    </option>
                                    <option value="FAILED" <?= $status_filter == 'FAILED' ? 'selected' : '' ?>>
                                        <i class="fas fa-times-circle"></i> Đặt cọc thất bại
                                    </option>
                                    <option value="REFUND_REQUESTED" <?= $status_filter == 'REFUND_REQUESTED' ? 'selected' : '' ?>>
                                        <i class="fas fa-undo"></i> Yêu cầu hoàn tiền
                                    </option>
                                    <option value="RELEASED" <?= $status_filter == 'RELEASED' ? 'selected' : '' ?>>
                                        <i class="fas fa-check-double"></i> Đã giải ngân
                                    </option>
                                    <option value="REFUNDED" <?= $status_filter == 'REFUNDED' ? 'selected' : '' ?>>
                                        <i class="fas fa-undo-alt"></i> Đã hoàn tiền
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3 text-end">
                                <label class="form-label small text-muted mb-1">&nbsp;</label>
                                <div>
                                    <?php if (!empty($search) || !empty($status_filter)): ?>
                                        <a href="manage_deposits.php" class="btn btn-outline-secondary w-100">
                                            <i class="fas fa-times me-1"></i> Xóa bộ lọc
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div> <!-- Deposits Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách tiền cọc</h5>
                        <span class="badge bg-primary"><?= $total_records ?> giao dịch</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-hashtag me-2"></i>ID</th>
                                        <th><i class="fas fa-home me-2"></i>Phòng</th>
                                        <th><i class="fas fa-user me-2"></i>Người thuê</th>
                                        <th><i class="fas fa-money-bill me-2"></i>Số tiền</th>
                                        <th><i class="fas fa-info-circle me-2"></i>Trạng thái</th>
                                        <th><i class="fas fa-calendar me-2"></i>Ngày đặt cọc</th>
                                        <th><i class="fas fa-cogs me-2"></i>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= $row['id'] ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($row['motel_image'])): ?>
                                                            <img src="../../<?= htmlspecialchars($row['motel_image']) ?>" width="50" height="50" class="rounded me-2" alt="Room Image">
                                                        <?php else: ?>
                                                            <div class="bg-light rounded me-2" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                                                <i class="fas fa-home text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <div><?= htmlspecialchars($row['motel_title']) ?></div>
                                                            <small class="text-muted">Chủ nhà: <?= htmlspecialchars($row['owner_name']) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div><?= htmlspecialchars($row['renter_name']) ?></div>
                                                    <div><small><?= htmlspecialchars($row['renter_email']) ?></small></div>
                                                    <div><small><?= htmlspecialchars($row['renter_phone']) ?></small></div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold"><?= formatCurrency($row['deposit_amount']) ?></div>
                                                    <small class="text-muted">Hoa hồng: <?= $row['commission_pct'] ?>%</small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $badgeClass = '';
                                                    $statusText = '';

                                                    switch ($row['status']) {
                                                        case 'PENDING':
                                                            $badgeClass = 'bg-warning';
                                                            $statusText = 'Đang chờ xử lý';
                                                            break;
                                                        case 'SUCCESS':
                                                            $badgeClass = 'bg-success';
                                                            $statusText = 'Đặt cọc thành công';
                                                            break;
                                                        case 'FAILED':
                                                            $badgeClass = 'bg-danger';
                                                            $statusText = 'Đặt cọc thất bại';
                                                            break;
                                                        case 'REFUND_REQUESTED':
                                                            $badgeClass = 'bg-info';
                                                            $statusText = 'Yêu cầu hoàn tiền';
                                                            break;
                                                        case 'RELEASED':
                                                            $badgeClass = 'bg-primary';
                                                            $statusText = 'Đã giải ngân';
                                                            break;
                                                        case 'REFUNDED':
                                                            $badgeClass = 'bg-secondary';
                                                            $statusText = 'Đã hoàn tiền';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?= $badgeClass ?> status-badge">
                                                        <?= $statusText ?>
                                                    </span>

                                                    <?php if ($row['status'] == 'REFUND_REQUESTED' && $row['request_refund_at']): ?>
                                                        <div class="small mt-1">
                                                            <i class="far fa-clock"></i> <?= formatDate($row['request_refund_at']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?= formatDate($row['created_at']) ?>
                                                    <div class="small text-muted">
                                                        Cập nhật: <?= formatDate($row['updated_at']) ?>
                                                    </div>
                                                </td>
                                                <td class="actions-column">
                                                    <div class="d-flex flex-column gap-2">
                                                        <a href="view_deposit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i> Chi tiết
                                                        </a>

                                                        <?php if ($row['status'] == 'REFUND_REQUESTED'): ?>
                                                            <a href="refund_deposit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-info">
                                                                <i class="fas fa-undo"></i> Hoàn tiền
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Không tìm thấy dữ liệu tiền cọc nào
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>

                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $start_page + 4);
                                    if ($end_page - $start_page < 4) {
                                        $start_page = max(1, $end_page - 4);
                                    }

                                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Thêm hiệu ứng và tính năng tương tác
        document.addEventListener('DOMContentLoaded', function() {
            // Hiệu ứng hover cho thẻ card
            const cards = document.querySelectorAll('.stat-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.15)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 3px 10px rgba(0, 0, 0, 0.05)';
                });
            });

            // Hiệu ứng hover cho các nút
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    this.style.transition = 'all 0.3s ease';
                    this.style.transform = 'translateY(-2px)';
                });

                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Hiệu ứng hover cho hàng bảng
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transition = 'all 0.2s ease';
                    this.style.backgroundColor = 'rgba(78, 115, 223, 0.05)';
                    this.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.05)';
                });

                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                    this.style.boxShadow = 'none';
                });
            });

            // Hiệu ứng đếm số trong các stat card khi trang được tải
            function animateValue(obj, start, end, duration) {
                let startTimestamp = null;
                const step = (timestamp) => {
                    if (!startTimestamp) startTimestamp = timestamp;
                    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                    obj.innerHTML = Math.floor(progress * (end - start) + start).toLocaleString('vi-VN');
                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    }
                };
                window.requestAnimationFrame(step);
            }

            // Áp dụng hiệu ứng đếm cho các tiêu đề h3 trong stat card
            document.querySelectorAll('.stat-card h3').forEach(el => {
                const finalValue = parseInt(el.textContent.replace(/\D/g, '')) || 0;
                animateValue(el, 0, finalValue, 1000);
            });
        });
    </script>
</body>

</html>