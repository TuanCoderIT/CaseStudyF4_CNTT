-- Tạo bảng mới để lưu trữ hình ảnh của phòng trọ
CREATE TABLE IF NOT EXISTS `motel_images` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `motel_id` int(10) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_motel_images_motel` (`motel_id`),
  CONSTRAINT `fk_motel_images_motel` FOREIGN KEY (`motel_id`) REFERENCES `motel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chỉ định ý nghĩa cho cột images trong bảng motel
-- ALTER TABLE `motel` CHANGE `images` `banner_image` varchar(255) DEFAULT NULL COMMENT 'Ảnh banner chính của phòng trọ';
