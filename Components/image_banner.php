<?php
// filepath: /Users/huynh04/Dev/phongtro/CaseStudyF4_CNTT/Components/image_banner.php
// Kết nối đến CSDL nếu chưa được kết nối
if (!isset($conn)) {
    require_once(__DIR__ . '/../config/db.php');
}

// Truy vấn lấy các phòng trọ nổi bật (dựa vào lượt xem cao nhất) và ảnh của chúng
$banner_query = "
    SELECT 
        m.id, 
        m.title, 
        m.price, 
        m.address, 
        m.count_view,
        m.images as main_image, 
        GROUP_CONCAT(DISTINCT mi.image_path ORDER BY mi.display_order ASC SEPARATOR '|') as image_paths,
        c.name as category_name,
        d.name as district_name
    FROM motel m
    LEFT JOIN motel_images mi ON m.id = mi.motel_id
    LEFT JOIN categories c ON m.category_id = c.id
    LEFT JOIN districts d ON m.district_id = d.id
    WHERE m.approve = 1
    GROUP BY m.id
    ORDER BY m.count_view DESC
    LIMIT 5
";

$banner_result = $conn->query($banner_query);
$banner_rooms = [];

if ($banner_result && $banner_result->num_rows > 0) {
    while ($room = $banner_result->fetch_assoc()) {
        // Tách các đường dẫn ảnh thành mảng
        $room['images'] = [];

        // Thêm ảnh từ bảng motel_images nếu có
        if (!empty($room['image_paths'])) {
            $room['images'] = explode('|', $room['image_paths']);
        }

        // Thêm ảnh chính từ trường images trong bảng motel nếu chưa có trong danh sách
        if (!empty($room['main_image']) && (!in_array($room['main_image'], $room['images']))) {
            array_unshift($room['images'], $room['main_image']); // Thêm vào đầu mảng
        }

        // Nếu không có ảnh nào, sử dụng ảnh mặc định
        if (empty($room['images'])) {
            $room['images'] = ['images/default.jpg'];
        }

        $banner_rooms[] = $room;
    }
}
?>

<!-- Banner Slider với nhiều hình ảnh -->
<div class="image-banner-container">
    <div class="swiper banner-swiper">
        <div class="swiper-wrapper">
            <?php foreach ($banner_rooms as $room): ?>
                <div class="swiper-slide">
                    <div class="banner-slide-content">
                        <!-- Slider con cho mỗi phòng trọ -->
                        <div class="swiper room-image-swiper">
                            <div class="swiper-wrapper">
                                <?php foreach ($room['images'] as $image): ?>
                                    <div class="swiper-slide">
                                        <img src="<?php echo str_starts_with($image, '/') ? substr($image, 1) : $image; ?>" alt="<?php echo htmlspecialchars($room['title']); ?>" class="banner-image">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="swiper-pagination"></div>
                            <!-- Simplified navigation buttons -->
                            <div class="swiper-button-next">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                            <div class="swiper-button-prev">
                                <i class="fas fa-chevron-left"></i>
                            </div>
                        </div>
                        <div class="banner-info">
                            <h3 class="banner-title"><?php echo htmlspecialchars($room['title']); ?></h3>
                            <p class="banner-price"><?php echo number_format($room['price']); ?> đ/tháng</p>
                            <p class="banner-address"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($room['address']); ?></p>
                            <div class="d-flex flex-wrap gap-3 mb-3">
                                <div class="banner-view-count">
                                    <i class="fas fa-eye"></i> <?php echo number_format($room['count_view']); ?> lượt xem
                                </div>
                                <?php if (!empty($room['category_name'])): ?>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($room['category_name']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($room['district_name'])): ?>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($room['district_name']); ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="room_detail.php?id=<?php echo $room['id']; ?>" class="banner-btn">Xem chi tiết</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <!-- Điều hướng chính và phân trang -->
        <div class="swiper-main-pagination"></div>
        <!-- Simplified main navigation buttons -->
        <div class="swiper-button-next main-next">
            <i class="fas fa-chevron-right"></i>
        </div>
        <div class="swiper-button-prev main-prev">
            <i class="fas fa-chevron-left"></i>
        </div>
    </div>
</div>

<!-- CSS được định nghĩa trong Assets/banner.css -->

<!-- JavaScript để khởi tạo Swiper -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Phương pháp mới: xóa và tạo lại các nút điều hướng để khắc phục vấn đề méo mó
        setTimeout(() => {
            // Chờ một khoảng thời gian ngắn để đảm bảo Swiper đã được khởi tạo hoàn toàn
            document.querySelectorAll('.swiper-button-next, .swiper-button-prev').forEach(button => {
                const parent = button.parentElement;
                const isNext = button.classList.contains('swiper-button-next');
                const isMain = button.classList.contains('main-next') || button.classList.contains('main-prev');

                // Lấy nội dung icon từ nút cũ nếu có, hoặc tạo mới nếu không có
                let icon;
                if (button.querySelector('i')) {
                    icon = button.querySelector('i').cloneNode(true);
                } else {
                    icon = document.createElement('i');
                    icon.className = isNext ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
                }

                // Xóa nút cũ
                const buttonClasses = button.className;
                button.remove();

                // Tạo nút mới với các class giống nút cũ để Swiper có thể nhận diện
                const newButton = document.createElement('div');
                newButton.className = buttonClasses + ' fixed-nav-button';

                // Thêm icon vào nút
                newButton.appendChild(icon);

                // Thêm nút vào DOM
                parent.appendChild(newButton);
            });
        }, 100);

        // Xử lý hiệu ứng khi cuộn trang
        window.addEventListener('scroll', function() {
            const bannerContainer = document.querySelector('.image-banner-container');
            if (bannerContainer) {
                if (window.scrollY > 50) {
                    bannerContainer.classList.add('scrolled');
                } else {
                    bannerContainer.classList.remove('scrolled');
                }
            }
        });

        // Khởi tạo Swiper con cho từng phòng trọ
        const roomSwipers = new Swiper('.room-image-swiper', {
            loop: true,
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
            speed: 800,
            grabCursor: true
        });

        // Khởi tạo Swiper chính cho banner
        const bannerSwiper = new Swiper('.banner-swiper', {
            loop: true,
            navigation: {
                nextEl: '.main-next',
                prevEl: '.main-prev',
            },
            pagination: {
                el: '.swiper-main-pagination',
                clickable: true,
                dynamicBullets: true,
            },
            autoplay: {
                delay: 8000,
                disableOnInteraction: false,
            },
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
            speed: 1000,
            grabCursor: true,
            keyboard: {
                enabled: true,
            },
            on: {
                slideChangeTransitionStart: function() {
                    // Tạo hiệu ứng khi chuyển slide
                    document.querySelectorAll('.banner-info').forEach(info => {
                        info.style.opacity = 0;
                        info.style.transform = 'translateY(20px)';
                    });
                },
                slideChangeTransitionEnd: function() {
                    // Hiện thông tin khi slide hiển thị
                    const activeSlide = document.querySelector('.swiper-slide-active .banner-info');
                    if (activeSlide) {
                        activeSlide.style.opacity = 1;
                        activeSlide.style.transform = 'translateY(0)';
                        activeSlide.style.transition = 'all 0.5s ease';
                    }
                },
            }
        });

        // Khởi tạo hiệu ứng cho slide đầu tiên
        setTimeout(() => {
            const activeSlide = document.querySelector('.swiper-slide-active .banner-info');
            if (activeSlide) {
                activeSlide.style.opacity = 1;
                activeSlide.style.transform = 'translateY(0)';
                activeSlide.style.transition = 'all 0.5s ease';
            }
        }, 100);

        // Thêm sự kiện cho các nút điều hướng để có hiệu ứng nhấn chuột đẹp
        document.querySelectorAll('.swiper-button-next, .swiper-button-prev').forEach(button => {
            // Thêm hiệu ứng ripple khi click
            button.addEventListener('click', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const ripple = document.createElement('span');
                ripple.classList.add('ripple-effect');
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';

                this.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    });
</script>