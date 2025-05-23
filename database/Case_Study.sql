-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost:3306
-- Thời gian đã tạo: Th5 23, 2025 lúc 08:10 PM
-- Phiên bản máy phục vụ: 5.7.41-cll-lve
-- Phiên bản PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `yoxdhlgk_Case_Study`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `motel_id` int(11) NOT NULL,
  `deposit_amount` int(11) NOT NULL,
  `commission_pct` decimal(5,2) NOT NULL DEFAULT '5.00',
  `status` enum('PENDING','SUCCESS','FAILED','REFUND_REQUESTED','RELEASED','REFUNDED') NOT NULL DEFAULT 'PENDING',
  `vnp_transaction_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `request_refund_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `motel_id`, `deposit_amount`, `commission_pct`, `status`, `vnp_transaction_id`, `created_at`, `updated_at`, `request_refund_at`) VALUES
(28, 1, 18, 5000000, '5.00', 'PENDING', NULL, '2025-05-23 12:55:38', '2025-05-23 12:55:38', NULL),
(29, 1, 18, 5000000, '5.00', 'PENDING', NULL, '2025-05-23 12:58:14', '2025-05-23 12:58:14', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Phòng trọ thường'),
(2, 'Phòng ở ghép'),
(3, 'Chung cư mini'),
(4, 'Ký túc xá'),
(5, 'Phòng cao cấp');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `districts`
--

CREATE TABLE `districts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `districts`
--

INSERT INTO `districts` (`id`, `name`) VALUES
(14, 'Bến Thủy'),
(15, 'Cửa Nam'),
(16, 'Đông Vĩnh'),
(17, 'Hà Huy Tập'),
(18, 'Hưng Bình'),
(19, 'Hưng Dũng'),
(20, 'Hưng Phúc'),
(21, 'Lê Lợi'),
(22, 'Quán Bàu'),
(23, 'Quang Trung'),
(24, 'Trung Đô'),
(25, 'Trường Thi'),
(26, 'Vinh Tân'),
(27, 'Hưng Đông'),
(28, 'Hưng Lộc'),
(29, 'Nghi Đức'),
(30, 'Nghi Phú'),
(31, 'Nghi Hải'),
(32, 'Nghi Hòa'),
(33, 'Nghi Hương'),
(34, 'Nghi Thu'),
(35, 'Thu Thủy'),
(36, 'Nghi Thủy'),
(37, 'Nghi Tân');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `motel`
--

CREATE TABLE `motel` (
  `id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` int(11) DEFAULT NULL,
  `area` int(11) DEFAULT NULL,
  `count_view` int(11) DEFAULT '0',
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latlng` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `district_id` int(11) DEFAULT NULL,
  `utilities` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approve` int(11) DEFAULT '0',
  `wishlist` int(11) DEFAULT '0',
  `default_deposit` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `motel`
--

INSERT INTO `motel` (`id`, `title`, `description`, `price`, `area`, `count_view`, `address`, `latlng`, `images`, `user_id`, `category_id`, `district_id`, `utilities`, `created_at`, `phone`, `approve`, `wishlist`, `default_deposit`) VALUES
(16, 'Chung cư CT 2B Quang Trung Diện Tích 56m2', '<p>giá thuê 5 triệu/ Tháng .</p><p>Em cho thuê chung cư CT 2B Quang Trung Diện Tích 56m2</p><p>-nội thất đầy đủ</p><p>- Liên Hệ Xem Nhà :0355793581 ( Em Thăng )</p>', 5000000, 56, 5, 'chung cư CT 2B, Quang Trung, Thành phố Vinh, Nghệ An', '18.6763,105.67613', 'uploads/1747841457_60eb11f6d8fa3aa463eb_1551067970.jpg', 11, 3, 23, 'Wifi, Điều hòa, Nóng lạnh, Gửi xe, Bảo vệ, WC riêng, Bếp, Tủ lạnh', '2025-05-21 04:37:24', '0365043804', 1, 2, 2500000),
(17, 'Phòng trọ dạng căn hộ mini cao cấp', '<p>Phòng sạch sẽ, có gác xép, máy lạnh, wifi miễn phí. Khu vực an ninh.</p>', 2500000, 30, 1, 'Số 06, ngõ 1A, đường Phạm Thị Tảo, Bến Thủy, Thành phố Vinh, Nghệ An', '18.663709,105.701212', 'uploads/banner/1747820435_z2855400259529-10bc9fcf3c4b7da3c267a27fd9f3124a_1634531314.jpg', 11, 3, 14, 'Wifi, Điều hòa, Nóng lạnh, Gửi xe, Bảo vệ, WC riêng, Bếp, Tủ lạnh', '2025-05-21 09:40:35', '0365043804', 1, 0, 1000000),
(18, 'THANH DAT HOME - hệ thống căn hộ dịch vụ, chung cư mini, phòng trọ cao cấp.', '<p>THANH DAT HOME - hệ thống căn hộ dịch vụ, chung cư mini, phòng trọ cao cấp.</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t75/2/16/2728.png\" alt=\"✨\" height=\"16\" width=\"16\"> Vừa khai trương cơ sở mới:</p><p>Số 5, Ngõ 9, Phan Thái Ất – P. Hà Huy Tập</p><p>Và còn phòng ở cơ sở khác rải khắp TP. Vinh:</p><p>- Home 1: 21B Hồ Tùng Mậu, phường Trường Thi (gần Quảng Trường, Bưu điện)</p><p>- Home 13: 13 Lý Tự Trọng, phường Hà Huy Tập (gần ĐH Kinh Tế Nghệ An)</p><p>- Home 14: 230C Võ Nguyên Hiến, phường Hưng Dũng (ks Đạt Phú, gần Hồ Goong)</p><p>- Home 15: 99 Đặng Tất, Phường Lê Mao (gần Go! Big C)</p><p>- Home 19: Số 5 Ngõ 9 Phan Thái Ất, phường Hà Huy Tập (gần trường ĐH Kinh Tế Nghệ An)</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/ta3/2/16/260e.png\" alt=\"☎️\" height=\"16\" width=\"16\"> 08.3233.3737 (zl) liên hệ xem phòng nhé!</p>', 9900000, 50, 2, 'Hẻm 1A Đường Phạm Thị Tảo 6, Bến Thủy, Thành phố Vinh, Nghệ An', '18.66798,105.7059', 'uploads/banner/1747904854_500107773_2722267114627774_7367159711626814427_n.jpg', 11, 3, 14, 'Wifi, Điều hòa, Nóng lạnh, Gửi xe, Bảo vệ, WC riêng, Bếp, Tủ lạnh', '2025-05-22 09:07:34', '0365043804', 1, 1, 5000000),
(19, 'Phòng trọ khép kín', '<p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t59/2/16/1f4e3.png\" alt=\"📣\" height=\"16\" width=\"16\"> TRỐNG 1 PHÒNG Ở ĐƯỢC LUÔN </p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t16/2/16/1f3e1.png\" alt=\"🏡\" height=\"16\" width=\"16\"> Địa chỉ : số 2/N ngõ 15 đường Nguyễn Văn Trỗi, Bến Thuỷ, Thành phố Vinh, Nghệ An</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/tcc/2/16/1f4cd.png\" alt=\"📍\" height=\"16\" width=\"16\"> Vị trí : Siêu Hót ở khu vực Trung tâm: ngay Đại học Vinh, Chợ, Bệnh viện, Quảng trường, Công viên,….</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t4b/2/16/1f4cc.png\" alt=\"📌\" height=\"16\" width=\"16\">Thiết kế :  khép kín, có gác xép </p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t7e/2/16/1f4b5.png\" alt=\"💵\" height=\"16\" width=\"16\">Giá : 1.9triệu</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t50/2/16/1f6cf.png\" alt=\"🛏\" height=\"16\" width=\"16\"> NỘI THẤT : NÓNG LẠNH, ĐIỀU HOÀ, KỆ BẾP, WIFI, MÁY GIẶT...</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t89/2/16/1f4ec.png\" alt=\"📬\" height=\"16\" width=\"16\"> Có an ninh đảm bảo <img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/tb5/2/16/23f0.png\" alt=\"⏰\" height=\"16\" width=\"16\">Cổng vân tay , giờ giấc tự do </p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/td8/2/16/1f4e2.png\" alt=\"📢\" height=\"16\" width=\"16\"> Chỉ còn 1 số phòng, Liên hệ sớm xem phòng và giữ chỗ: 0985138511</p>', 1900000, 50, 1, 'số 2/N ngõ 15 đường Nguyễn Văn Trỗi, Bến Thủy, Thành phố Vinh, Nghệ An', '18.65915,105.70024', 'uploads/banner/1747905030_499990681_9977864108926643_6198584699326133275_n.jpg', 11, 2, 14, 'Wifi, Điều hòa, Nóng lạnh, Gửi xe, Bảo vệ, WC riêng, Bếp, Tủ lạnh', '2025-05-22 09:10:30', '0365043804', 1, 0, 1000000);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `motel_images`
--

CREATE TABLE `motel_images` (
  `id` int(11) NOT NULL,
  `motel_id` int(11) NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_order` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `motel_images`
--

INSERT INTO `motel_images` (`id`, `motel_id`, `image_path`, `display_order`, `created_at`) VALUES
(36, 16, 'uploads/rooms/1747802244_1_photo-1-16446400187821764344412.png', 1, '2025-05-21 04:37:24'),
(37, 16, 'uploads/rooms/1747802244_2_photo-1-16446400216191316154621.jpeg', 2, '2025-05-21 04:37:24'),
(38, 16, 'uploads/rooms/1747802244_3_photo-2-16446400216851972532173.jpeg', 3, '2025-05-21 04:37:24'),
(39, 17, 'uploads/rooms/1747820435_0_b6210c39c535276b7e24_1551067968.jpg', 0, '2025-05-21 09:40:35'),
(40, 17, 'uploads/rooms/1747820435_1_z2855400230250-0407dd8ca845c5a00c19ac29a7728f68_1634531321.jpg', 1, '2025-05-21 09:40:35'),
(41, 17, 'uploads/rooms/1747820435_2_z2855400236910-509061cc2c6e2d8478ebbcf128836c01_1634531320.jpg', 2, '2025-05-21 09:40:35'),
(42, 17, 'uploads/rooms/1747820435_3_z2855400250427-8f5eee0957ec9d7ae9779b626107a049_1634531321.jpg', 3, '2025-05-21 09:40:35'),
(43, 17, 'uploads/rooms/1747820435_4_z2855400259529-10bc9fcf3c4b7da3c267a27fd9f3124a_1634531314.jpg', 4, '2025-05-21 09:40:35'),
(44, 17, 'uploads/rooms/1747820435_5_z2855400265005-8c866bb37a39e726bfbaa1ab9634753b_1634531320.jpg', 5, '2025-05-21 09:40:35'),
(45, 16, 'uploads/rooms/1747841457_0_60eb11f6d8fa3aa463eb_1551067970.jpg', 0, '2025-05-21 15:30:57'),
(46, 18, 'uploads/rooms/1747904854_0_499796835_2722267181294434_8024619422153518198_n.jpg', 0, '2025-05-22 09:07:34'),
(47, 18, 'uploads/rooms/1747904854_1_499866893_2722267187961100_7462858564938584970_n.jpg', 1, '2025-05-22 09:07:34'),
(48, 18, 'uploads/rooms/1747904854_2_499932212_2722267121294440_7444071810466581045_n.jpg', 2, '2025-05-22 09:07:34'),
(49, 19, 'uploads/rooms/1747905030_0_498913603_9977864268926627_1597753980648069284_n.jpg', 0, '2025-05-22 09:10:30'),
(50, 19, 'uploads/rooms/1747905030_1_499250549_9977864262259961_2852767426034860054_n.jpg', 1, '2025-05-22 09:10:30'),
(51, 19, 'uploads/rooms/1747905030_2_499532684_9977864368926617_9022903704693140757_n.jpg', 2, '2025-05-22 09:10:30'),
(52, 19, 'uploads/rooms/1747905030_3_499606779_9977864145593306_792390631925851312_n.jpg', 3, '2025-05-22 09:10:30');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` int(11) DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `role`, `phone`, `avatar`) VALUES
(1, 'Nguyễn Trọng Truyền', 'admin', 'vana@example.com', '$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe', 1, '0901234567', 'uploads/avatar/avatar_1747809296_2038.jpg'),
(2, 'Trần Thị B', 'tranthib', 'thib@example.com', '123456', 2, '0912345678', 'uploads/avatar/avatar_1747801182_5629.jpg'),
(3, 'Lê Văn C', 'levanc', 'vanc@example.com', '123456', 1, '0923456789', 'uploads/avatar/avatar_1747801182_5629.jpg'),
(4, 'Phạm Thị D', 'phamthid', 'thid@example.com', '123456', 2, '0934567890', 'uploads/avatar/avatar_1747801182_5629.jpg'),
(5, 'Hoàng Văn E', 'hoangvane', 'vane@example.com', '123456', 2, '0945678901', 'uploads/avatar/avatar_1747801182_5629.jpg'),
(6, 'Đỗ Thị F', 'dothif', 'thif@example.com', '123456', 2, '0956789012', 'uploads/avatar/avatar_1747801182_5629.jpg'),
(7, 'Ngô Văn G', 'ngovang', 'vang@example.com', '123456', 2, '0967890123', 'uploads/avatar/avatar_1747801182_5629.jpg'),
(8, 'Vũ Thị H', 'vuthih', 'thih@example.com', '123456', 2, '0978901234', 'uploads/avatar/avatar_1747801182_5629.jpg'),
(9, 'Bùi Văn I', 'buivani', 'vani@example.com', '123456', 2, '0989012345', 'uploads/avatar/avatar_1747801182_5629.jpg'),
(10, 'Đặng Thị K', 'dangthik', 'thik@example.com', '123456', 2, '0990123456', 'uploads/avatar/avatar_1747801182_5629.jpg'),
(11, 'Phan Quốc Tuấn', 'tuannopro', 'pqtuan2k4@gmail.com', '$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe', 1, '0987654321', 'uploads/avatar/avatar_1747754361_5067.jpg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_wishlist`
--

CREATE TABLE `user_wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `motel_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `user_wishlist`
--

INSERT INTO `user_wishlist` (`id`, `user_id`, `motel_id`, `created_at`) VALUES
(1, 1, 16, '2025-05-21 06:52:32'),
(3, 11, 16, '2025-05-23 11:02:02'),
(4, 11, 18, '2025-05-23 11:44:15');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `motel_id` (`motel_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `motel`
--
ALTER TABLE `motel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_motel_category` (`category_id`),
  ADD KEY `fk_motel_district` (`district_id`);

--
-- Chỉ mục cho bảng `motel_images`
--
ALTER TABLE `motel_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_motel_images_motel` (`motel_id`);

--
-- Chỉ mục cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `user_wishlist`
--
ALTER TABLE `user_wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_motel` (`user_id`,`motel_id`),
  ADD KEY `motel_id` (`motel_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT cho bảng `motel`
--
ALTER TABLE `motel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT cho bảng `motel_images`
--
ALTER TABLE `motel_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT cho bảng `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `user_wishlist`
--
ALTER TABLE `user_wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`motel_id`) REFERENCES `motel` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `motel`
--
ALTER TABLE `motel`
  ADD CONSTRAINT `fk_motel_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_motel_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `motel_images`
--
ALTER TABLE `motel_images`
  ADD CONSTRAINT `fk_motel_images_motel` FOREIGN KEY (`motel_id`) REFERENCES `motel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `user_wishlist`
--
ALTER TABLE `user_wishlist`
  ADD CONSTRAINT `user_wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_wishlist_ibfk_2` FOREIGN KEY (`motel_id`) REFERENCES `motel` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
