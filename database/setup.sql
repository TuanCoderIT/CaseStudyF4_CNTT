-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3307
-- Thời gian đã tạo: Th5 19, 2025 lúc 11:01 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `gtpt`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
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
  `id` int(10) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `districts`
--

INSERT INTO `districts` (`id`, `name`) VALUES
(1, 'TP Vinh'),
(2, 'Hưng Dũng'),
(3, 'Hà Huy Tập'),
(4, 'Bến Thủy'),
(5, 'Quang Trung');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `motel`
--

CREATE TABLE `motel` (
  `id` int(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `area` int(11) DEFAULT NULL,
  `count_view` int(11) DEFAULT 0,
  `address` varchar(255) DEFAULT NULL,
  `latlng` varchar(255) DEFAULT NULL,
  `images` varchar(255) DEFAULT NULL,
  `user_id` int(10) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `district_id` int(10) DEFAULT NULL,
  `utilities` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(255) DEFAULT NULL,
  `approve` int(11) DEFAULT 0,
  `wishlist` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `motel`
--

INSERT INTO `motel` (`id`, `title`, `description`, `price`, `area`, `count_view`, `address`, `latlng`, `images`, `user_id`, `category_id`, `district_id`, `utilities`, `created_at`, `phone`, `approve`, `wishlist`) VALUES
(1, 'Phòng trọ giá rẻ gần ĐH Vinh', 'Phòng rộng 25m2, có wifi, máy giặt, khu vực an ninh. Phù hợp sinh viên.', 1200000, 25, 16, 'Số 10, Hà Huy Tập, TP Vinh', NULL, 'uploads/phong1.jpg', 1, NULL, 3, 'Wifi, Máy giặt, Gần trường', '2025-05-16 15:33:23', '0901234567', 1, 0),
(2, 'Chung cư mini mới xây', 'Phòng 30m², sạch sẽ, có ban công, máy giặt chung, bảo vệ 24/7.', 1800000, 30, 21, 'Số 12, Quang Trung, TP Vinh', '18.6680, 105.6800', 'uploads/phong2.jpg', 1, 3, 5, 'Máy giặt, Bảo vệ, Ban công', '2025-05-19 06:04:09', '0922222222', 1, 0),
(3, 'Ký túc xá giá rẻ', 'Phòng ở ghép, sạch sẽ, mỗi người 600k, có wifi và khu nấu ăn riêng.', 600000, 25, 5, 'Số 9, Hưng Dũng, TP Vinh', '18.6610, 105.6700', 'uploads/phong3.jpg', 2, 4, 2, 'Wifi, Nhà bếp, Giá rẻ', '2025-05-19 06:04:09', '0933333333', 1, 0),
(4, 'Phòng trọ yên tĩnh khu dân cư', 'Phòng riêng biệt, 18m², có gác xép, khu vực yên tĩnh, thích hợp sinh viên nữ.', 1000000, 18, 7, 'Số 22, Bến Thủy, TP Vinh', '18.6690, 105.6600', 'uploads/phong4.jpg', 3, 1, 4, 'Gác xép, Yên tĩnh', '2025-05-19 06:04:09', '0944444444', 1, 0),
(5, 'Phòng cao cấp full nội thất', 'Phòng đẹp, có máy lạnh, tủ lạnh, giường nệm, diện tích 35m². Bao phí dịch vụ.', 2500000, 35, 33, 'Số 3, TP Vinh', '18.6700, 105.6750', 'uploads/phong5.jpg', 1, 5, 1, 'Máy lạnh, Tủ lạnh, Full nội thất', '2025-05-19 06:04:09', '0955555555', 1, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(10) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` int(11) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `role`, `phone`, `avatar`) VALUES
(1, 'Nguyễn Văn A', 'nguyenvana', 'vana@example.com', '123456', 1, '0901234567', 'avatar1.jpg'),
(2, 'Trần Thị B', 'tranthib', 'thib@example.com', '123456', 2, '0912345678', 'avatar2.jpg'),
(3, 'Lê Văn C', 'levanc', 'vanc@example.com', '123456', 1, '0923456789', 'avatar3.jpg'),
(4, 'Phạm Thị D', 'phamthid', 'thid@example.com', '123456', 2, '0934567890', 'avatar4.jpg'),
(5, 'Hoàng Văn E', 'hoangvane', 'vane@example.com', '123456', 1, '0945678901', 'avatar5.jpg'),
(6, 'Đỗ Thị F', 'dothif', 'thif@example.com', '123456', 2, '0956789012', 'avatar6.jpg'),
(7, 'Ngô Văn G', 'ngovang', 'vang@example.com', '123456', 1, '0967890123', 'avatar7.jpg'),
(8, 'Vũ Thị H', 'vuthih', 'thih@example.com', '123456', 2, '0978901234', 'avatar8.jpg'),
(9, 'Bùi Văn I', 'buivani', 'vani@example.com', '123456', 1, '0989012345', 'avatar9.jpg'),
(10, 'Đặng Thị K', 'dangthik', 'thik@example.com', '123456', 2, '0990123456', 'avatar10.jpg'),
(11, 'Phan Quốc Tuấn', 'tuannopro', 'pqtuan2k4@gmail.com', '$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe', NULL, '0987654321', 'images/avatar_1747412009_3868.jpg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_wishlist`
--

CREATE TABLE `user_wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(10) DEFAULT NULL,
  `motel_id` int(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

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
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `motel`
--
ALTER TABLE `motel`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `user_wishlist`
--
ALTER TABLE `user_wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `motel`
--
ALTER TABLE `motel`
  ADD CONSTRAINT `fk_motel_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_motel_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
