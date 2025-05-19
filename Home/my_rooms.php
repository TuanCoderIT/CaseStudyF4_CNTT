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

// Khởi tạo mảng favorite_rooms nếu chưa tồn tại trong session
if (!isset($_SESSION['favorite_rooms'])) {
    $_SESSION['favorite_rooms'] = array();
}

// Xử lý xóa phòng khỏi yêu thích
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id'])) {
    $room_id = $_GET['id'];
    if (($key = array_search($room_id, $_SESSION['favorite_rooms'])) !== false) {
        unset($_SESSION['favorite_rooms'][$key]);
        // Sắp xếp lại mảng
        $_SESSION['favorite_rooms'] = array_values($_SESSION['favorite_rooms']);

        // Thêm thông báo khi xóa thành công
        $message = "Đã xóa phòng trọ khỏi danh sách yêu thích!";
        $message_type = "warning";
    }

    // Redirect về trang danh sách yêu thích
    header("Location: my_rooms.php" . (isset($message) ? "?message=" . urlencode($message) . "&type=" . $message_type : ""));
    exit;
}

// Lấy danh sách phòng trọ yêu thích
$favorite_rooms = array();

if (!empty($_SESSION['favorite_rooms'])) {
    $placeholders = implode(',', array_fill(0, count($_SESSION['favorite_rooms']), '?'));
    $types = str_repeat('i', count($_SESSION['favorite_rooms']));

    $sql = "
        SELECT m.*, u.name as owner_name, c.name as category_name
        FROM motel m 
        LEFT JOIN users u ON m.user_id = u.id 
        LEFT JOIN categories c ON m.category_id = c.id
        WHERE m.id IN ($placeholders)
        AND m.approve = 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$_SESSION['favorite_rooms']);
    $stmt->execute();
    $favorite_rooms = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phòng trọ yêu thích - Phòng trọ sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>

<body class="home-body"> <?php include '../Components/header.php' ?>

    <main class="py-5 mt-5">
        <div class="container">
            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-<?php echo isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'info'; ?> alert-dismissible fade show animate__animated animate__fadeIn mb-4" role="alert">
                    <i class="fas <?php echo ($_GET['type'] == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($_GET['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="section-title"><i class="fas fa-heart me-2 text-danger"></i>Phòng trọ yêu thích</h1>
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="fas fa-home me-2"></i>Về trang chủ
                </a>
            </div>

            <?php if (!empty($_SESSION['favorite_rooms']) && $favorite_rooms->num_rows > 0): ?>
                <div class="row">
                    <?php while ($room = $favorite_rooms->fetch_assoc()): ?>
                        <div class="col-md-4 mb-4 animated-element">
                            <div class="card room-card h-100">
                                <div class="room-image">
                                    <img src="../<?php echo $room['images']; ?>" class="card-img-top" alt="<?php echo $room['title']; ?>">
                                    <span class="price-tag"><?php echo number_format($room['price']); ?> đ/tháng</span>
                                    <span class="view-count"><i class="fas fa-eye me-1"></i><?php echo number_format($room['count_view']); ?></span>
                                    <span class="favorite-badge">
                                        <i class="fas fa-heart"></i>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="room_detail.php?id=<?php echo $room['id']; ?>"><?php echo $room['title']; ?></a>
                                    </h5>
                                    <p class="card-text address"><i class="fas fa-map-marker-alt me-2"></i><?php echo $room['address']; ?></p>
                                    <div class="room-info">
                                        <span><i class="fas fa-expand me-1"></i><?php echo $room['area']; ?> m²</span>
                                        <span><i class="fas fa-bolt me-1"></i>
                                            <?php
                                            $utilities = explode(',', $room['utilities']);
                                            echo count($utilities) . ' tiện ích';
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-footer d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Đăng bởi: <?php echo $room['owner_name']; ?></small>
                                    <div>
                                        <a href="room_detail.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-primary me-1" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="my_rooms.php?action=remove&id=<?php echo $room['id']; ?>" class="btn btn-sm btn-danger" title="Xóa khỏi yêu thích" onclick="return confirm('Bạn có chắc muốn xóa phòng này khỏi danh sách yêu thích?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5 empty-favorites">
                    <div class="mb-4">
                        <i class="far fa-heart text-danger fa-4x"></i>
                    </div>
                    <h3>Bạn chưa có phòng trọ yêu thích nào</h3>
                    <p class="text-muted mb-4">Hãy thêm các phòng trọ bạn quan tâm vào danh sách yêu thích để xem lại sau</p>
                    <a href="search.php" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Tìm kiếm phòng trọ
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../Components/footer.php' ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hiệu ứng xuất hiện cho các phòng
            const animatedElements = document.querySelectorAll('.animated-element');

            animatedElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('animated', 'animate__fadeInUp');
                }, index * 100);
            });

            // Hiệu ứng xóa phòng khỏi yêu thích
            const deleteButtons = document.querySelectorAll('.btn-danger[title="Xóa khỏi yêu thích"]');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (confirm('Bạn có chắc muốn xóa phòng này khỏi danh sách yêu thích?')) {
                        const roomCard = this.closest('.animated-element');

                        // Thêm hiệu ứng biến mất trước khi chuyển hướng
                        e.preventDefault();
                        roomCard.classList.add('animate__animated', 'animate__fadeOutRight');

                        // Chờ hiệu ứng hoàn thành rồi mới chuyển hướng
                        setTimeout(() => {
                            window.location.href = this.getAttribute('href');
                        }, 500);
                    } else {
                        e.preventDefault(); // Ngăn chặn chuyển hướng nếu không xác nhận
                    }
                });
            });

            // Tự động ẩn alert sau 5 giây
            const alertElement = document.querySelector('.alert');
            if (alertElement) {
                setTimeout(() => {
                    alertElement.classList.remove('animate__fadeIn');
                    alertElement.classList.add('animate__fadeOut');
                    setTimeout(() => {
                        alertElement.remove();
                    }, 500);
                }, 5000);
            }
        });
    </script>
</body>

</html>