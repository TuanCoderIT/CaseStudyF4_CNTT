-- Tạo bảng Tỉnh/Thành phố
CREATE TABLE IF NOT EXISTS `provinces` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm dữ liệu mẫu cho bảng Tỉnh/Thành phố
INSERT INTO `provinces` (`id`, `name`, `code`) VALUES
(1, 'Nghệ An', 'NA'),
(2, 'Hà Tĩnh', 'HT'),
(3, 'Hà Nội', 'HN'),
(4, 'TP. Hồ Chí Minh', 'HCM'),
(5, 'Đà Nẵng', 'DN');

-- Cập nhật bảng Quận/Huyện để tham chiếu tới Tỉnh/Thành phố
ALTER TABLE `districts` ADD COLUMN `province_id` int(10) DEFAULT NULL AFTER `name`;
ALTER TABLE `districts` ADD CONSTRAINT `fk_district_province` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Cập nhật dữ liệu cho bảng Quận/Huyện
UPDATE `districts` SET `province_id` = 1 WHERE id IN (1, 2, 3, 4, 5);

-- Tạo bảng Phường/Xã
CREATE TABLE IF NOT EXISTS `wards` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `district_id` int(10) DEFAULT NULL,
  `code` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ward_district` (`district_id`),
  CONSTRAINT `fk_ward_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm dữ liệu mẫu cho bảng Phường/Xã
INSERT INTO `wards` (`name`, `district_id`, `code`) VALUES
('Phường Quán Bàu', 1, 'QB'),
('Phường Hưng Bình', 1, 'HB'),
('Phường Hưng Phúc', 1, 'HP'),
('Xã Hưng Dũng', 2, 'HD01'),
('Xã Hưng Đông', 2, 'HD02'),
('Xã Hưng Lộc', 2, 'HL'),
('Phường Bến Thủy', 4, 'BT01'),
('Phường Trung Đô', 4, 'TD'),
('Phường Trường Thi', 5, 'TT'),
('Phường Quang Trung', 5, 'QT');

-- Thêm cột chi tiết địa chỉ vào bảng motel
ALTER TABLE `motel` ADD COLUMN `address_detail` varchar(255) DEFAULT NULL AFTER `address`;
ALTER TABLE `motel` ADD COLUMN `ward_id` int(10) DEFAULT NULL AFTER `district_id`;
ALTER TABLE `motel` ADD CONSTRAINT `fk_motel_ward` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Tạo Stored Procedure để lấy địa chỉ đầy đủ
DELIMITER //
CREATE PROCEDURE GetFullAddress(IN motel_id INT)
BEGIN
    SELECT 
        m.address_detail,
        w.name AS ward_name,
        d.name AS district_name,
        p.name AS province_name
    FROM motel m
    LEFT JOIN wards w ON m.ward_id = w.id
    LEFT JOIN districts d ON m.district_id = d.id
    LEFT JOIN provinces p ON d.province_id = p.id
    WHERE m.id = motel_id;
END //
DELIMITER ;
