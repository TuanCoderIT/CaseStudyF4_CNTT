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

<!-- Bootstrap Carousel Banner -->
<div class="container-fluid p-0 mb-4">
    <div id="featuredRoomsCarousel" class="carousel slide" data-bs-ride="carousel">
        <!-- Indicators -->
        <div class="carousel-indicators">
            <?php foreach ($banner_rooms as $index => $room): ?>
                <button type="button" data-bs-target="#featuredRoomsCarousel" data-bs-slide-to="<?= $index ?>"
                    <?= $index === 0 ? 'class="active" aria-current="true"' : '' ?>
                    aria-label="Slide <?= $index + 1 ?>"></button>
            <?php endforeach; ?>
        </div>

        <!-- Carousel slides -->
        <div class="carousel-inner">
            <?php foreach ($banner_rooms as $index => $room): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <a href="room_detail.php?id=<?= $room['id']; ?>" class="position-relative banner-slide">
                        <!-- Image -->
                        <?php if (isset($room['images'][0])): ?>
                            <img src="<?= str_starts_with($room['images'][0], '/') ? substr($room['images'][0], 1) : $room['images'][0]; ?>"
                                class="d-block w-100 banner-image" alt="<?= htmlspecialchars($room['title']); ?>">
                        <?php endif; ?>

                        <!-- Info overlay -->
                        <div class="carousel-caption d-none d-md-block banner-info-overlay">
                            <h3 class="banner-title"><?= htmlspecialchars($room['title']); ?></h3>
                            <p class="banner-price"><?= number_format($room['price']); ?> đ/tháng</p>
                            <p class="banner-address"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($room['address']); ?></p>

                            <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                                <div class="banner-view-badge">
                                    <i class="fas fa-eye"></i> <?= number_format($room['count_view']); ?> lượt xem
                                </div>

                                <?php if (!empty($room['category_name'])): ?>
                                    <span class="badge bg-primary"><?= htmlspecialchars($room['category_name']); ?></span>
                                <?php endif; ?>

                                <?php if (!empty($room['district_name'])): ?>
                                    <span class="badge bg-info"><?= htmlspecialchars($room['district_name']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="pt-2"></div>
                        </div>

                        <!-- Mobile info (displayed below the image on smaller screens) -->
                        <div class="d-md-none mobile-banner-info">
                            <h3 class="h5"><?= htmlspecialchars($room['title']); ?></h3>
                            <p class="text-danger fw-bold"><?= number_format($room['price']); ?> đ/tháng</p>
                            <a href="room_detail.php?id=<?= $room['id']; ?>" class="btn btn-sm btn-primary">Chi tiết</a>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Controls/Navigation buttons -->
        <button class="carousel-control-prev" type="button" data-bs-target="#featuredRoomsCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#featuredRoomsCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
</div>

<style>
    /* Custom styling for Bootstrap Carousel Banner */
    .carousel {
        margin-bottom: 2rem;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    }

    .banner-slide {
        height: 500px;
        overflow: hidden;
    }

    .banner-image {
        width: 100%;
        height: 500px;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .carousel-item:hover .banner-image {
        transform: scale(1.05);
    }

    .banner-info-overlay {
        background: linear-gradient(to top, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0.4) 70%, transparent 100%);
        border-radius: 0 0 8px 8px;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 2rem 1.5rem 1.5rem;
    }

    .banner-title {
        font-size: 1.75rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .banner-price {
        color: #ff6b6b;
        font-weight: bold;
        font-size: 1.4rem;
        margin-bottom: 0.75rem;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
    }

    .banner-address {
        margin-bottom: 0.75rem;
        font-size: 1.1rem;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .banner-address i {
        color: #5eead4;
        margin-right: 8px;
    }

    .banner-view-badge {
        margin-bottom: 0;
        font-size: 0.9rem;
        background: rgba(255, 255, 255, 0.2);
        padding: 4px 10px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
    }

    .banner-view-badge i {
        color: #fcd34d;
        margin-right: 6px;
    }

    /* Mobile info styling */
    .mobile-banner-info {
        background: #fff;
        padding: 15px;
        text-align: center;
        border-top: 1px solid #eee;
    }

    /* Enhance carousel controls */
    .carousel-control-prev,
    .carousel-control-next {
        width: 50px;
        height: 50px;
        background-color: rgba(255, 255, 255, 0.3);
        top: 50%;
        transform: translateY(-50%);
        border-radius: 50%;
        opacity: 0.8;
    }

    .carousel-control-prev {
        left: 20px;
    }

    .carousel-control-next {
        right: 20px;
    }

    .carousel-control-prev:hover,
    .carousel-control-next:hover {
        background-color: rgba(255, 255, 255, 0.5);
    }

    .carousel-indicators [data-bs-target] {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin: 0 5px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {

        .banner-slide,
        .banner-image {
            height: 400px;
        }

        .banner-title {
            font-size: 1.4rem;
        }
    }

    @media (max-width: 576px) {

        .banner-slide,
        .banner-image {
            height: 350px;
        }
    }
</style>

<script>
    // Initialize the carousel with options
    document.addEventListener('DOMContentLoaded', function() {
        const carousel = new bootstrap.Carousel(document.getElementById('featuredRoomsCarousel'), {
            interval: 5000, // 5 seconds between slides
            wrap: true, // Continuous loop
            keyboard: true // Respond to keyboard
        });

        // Optional: Add fade animation to caption text when slide changes
        const carouselElement = document.getElementById('featuredRoomsCarousel');
        carouselElement.addEventListener('slide.bs.carousel', function() {
            document.querySelectorAll('.carousel-caption').forEach(caption => {
                caption.style.opacity = 0;
                caption.style.transform = 'translateY(20px)';
            });
        });

        carouselElement.addEventListener('slid.bs.carousel', function() {
            const activeCaption = document.querySelector('.carousel-item.active .carousel-caption');
            if (activeCaption) {
                activeCaption.style.opacity = 1;
                activeCaption.style.transform = 'translateY(0)';
                activeCaption.style.transition = 'all 0.5s ease';
            }
        });
    });
</script>