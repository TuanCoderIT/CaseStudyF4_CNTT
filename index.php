<?php
// Khởi tạo phiên làm việc
session_start();

// Kiểm tra xem người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: Auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="Assets/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #43cea2, #185a9d);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .welcome-section {
            margin-top: 100px;
            text-align: center;
            color: white;
            animation: fadeIn 1s;
        }
        
        .welcome-section h1 {
            font-size: 3rem;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .btn-edit-profile {
            background: linear-gradient(45deg, #3a7bd5, #00d2ff);
            border: none;
            padding: 10px 20px;
            margin-top: 20px;
            animation: pulse 2s infinite;
        }
        
        .btn-edit-profile:hover {
            background: linear-gradient(45deg, #00d2ff, #3a7bd5);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .btn-logout {
            background: linear-gradient(45deg, #FF416C, #FF4B2B);
            border: none;
        }
        
        .btn-logout:hover {
            background: linear-gradient(45deg, #FF4B2B, #FF416C);
            transform: translateY(-2px);
        }
        
        .user-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(58, 123, 213, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(58, 123, 213, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(58, 123, 213, 0);
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">F4 Case Study</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="Auth/edit_profile.php">
                            <i class="fas fa-user-edit me-1"></i> Chỉnh sửa hồ sơ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="Auth/logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> Đăng xuất
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-8 mx-auto welcome-section">
                <?php
                // Kết nối đến CSDL
                require_once('config/db.php');
                
                // Lấy thông tin người dùng
                $user_id = $_SESSION['user_id'];
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                ?>
                
                <img src="<?php echo $user['avatar']; ?>" alt="Avatar" class="user-avatar">
                <h1>Xin chào, <?php echo htmlspecialchars($user['name']); ?>!</h1>
                <p class="lead">Bạn đã đăng nhập thành công vào hệ thống.</p>
                <div class="mt-4">
                    <a href="Auth/edit_profile.php" class="btn btn-primary btn-lg btn-edit-profile">
                        <i class="fas fa-user-edit me-2"></i> Chỉnh sửa thông tin tài khoản
                    </a>
                    <a href="Auth/logout.php" class="btn btn-danger btn-lg ms-2 btn-logout">
                        <i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="Assets/main.js"></script>
</body>
</html>
