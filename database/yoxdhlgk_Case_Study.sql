-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost:3306
-- Thời gian đã tạo: Th5 21, 2025 lúc 02:22 PM
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
(16, 'sadfghjkl;', '<p>sdafghjkl;</p>', 432536457, 22, 2, 'Số 06, đường Phạm Thị Tảo, Bến Thủy, Thành phố Vinh, Nghệ An', '', 'uploads/banner/1747802244_493313024_1805617966684068_7495885866524337764_n.jpg', 11, 4, 14, 'Wifi, Điều hòa', '2025-05-21 04:37:24', '0987654321', 1, 1, NULL);

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
(35, 16, 'uploads/rooms/1747802244_0_493313024_1805617966684068_7495885866524337764_n.jpg', 0, '2025-05-21 04:37:24'),
(36, 16, 'uploads/rooms/1747802244_1_photo-1-16446400187821764344412.png', 1, '2025-05-21 04:37:24'),
(37, 16, 'uploads/rooms/1747802244_2_photo-1-16446400216191316154621.jpeg', 2, '2025-05-21 04:37:24'),
(38, 16, 'uploads/rooms/1747802244_3_photo-2-16446400216851972532173.jpeg', 3, '2025-05-21 04:37:24');

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
(11, 'Phan Quốc Tuấn', 'tuannopro', 'pqtuan2k4@gmail.com', '$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe', 1, '0987654321', 'uploads/avatar/avatar_1747809296_2038.jpg');

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
(1, 1, 16, '2025-05-21 06:52:32');

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
-- Chỉ mục cho bảng `motel_images`
--
ALTER TABLE `motel_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_motel_images_motel` (`motel_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT cho bảng `motel`
--
ALTER TABLE `motel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `motel_images`
--
ALTER TABLE `motel_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `user_wishlist`
--
ALTER TABLE `user_wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- Các ràng buộc cho bảng `motel_images`
--
ALTER TABLE `motel_images`
  ADD CONSTRAINT `fk_motel_images_motel` FOREIGN KEY (`motel_id`) REFERENCES `motel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
