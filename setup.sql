-- MySQL dump 10.13  Distrib 9.3.0, for macos13.7 (arm64)
--
-- Host: localhost    Database: case_study
-- ------------------------------------------------------
-- Server version	9.3.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Phòng trọ thường'),(2,'Phòng ở ghép'),(3,'Chung cư mini'),(4,'Ký túc xá'),(5,'Phòng cao cấp');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `districts`
--

DROP TABLE IF EXISTS `districts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `districts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `districts`
--

LOCK TABLES `districts` WRITE;
/*!40000 ALTER TABLE `districts` DISABLE KEYS */;
INSERT INTO `districts` VALUES (1,'TP Vinh'),(2,'Hưng Dũng'),(3,'Hà Huy Tập'),(4,'Bến Thủy'),(5,'Quang Trung'),(6,'Thành phố Vinh'),(7,'Huyện Đồng Văn'),(8,'Huyện Mèo Vạc'),(9,'Thị xã Thái Hoà'),(10,'Huyện Đà Bắc'),(11,'Quận Ba Đình'),(12,'Quận Hoàn Kiếm'),(13,'Quận Hai Bà Trưng');
/*!40000 ALTER TABLE `districts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `motel`
--

DROP TABLE IF EXISTS `motel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `motel` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` int DEFAULT NULL,
  `area` int DEFAULT NULL,
  `count_view` int DEFAULT '0',
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latlng` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `district_id` int DEFAULT NULL,
  `utilities` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approve` int DEFAULT '0',
  `wishlist` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_motel_category` (`category_id`),
  KEY `fk_motel_district` (`district_id`),
  CONSTRAINT `fk_motel_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_motel_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `motel`
--

LOCK TABLES `motel` WRITE;
/*!40000 ALTER TABLE `motel` DISABLE KEYS */;
INSERT INTO `motel` VALUES (1,'Phòng trọ giá rẻ gần ĐH Vinh','Phòng rộng 25m2, có wifi, máy giặt, khu vực an ninh. Phù hợp sinh viên.',1200000,25,18,'Số 10, Hà Huy Tập, TP Vinh',NULL,'uploads/phong1.jpg',1,NULL,3,'Wifi, Máy giặt, Gần trường','2025-05-16 15:33:23','0901234567',1,0),(2,'Chung cư mini mới xây','Phòng 30m², sạch sẽ, có ban công, máy giặt chung, bảo vệ 24/7.',1800000,30,23,'Số 12, Quang Trung, TP Vinh','18.6680, 105.6800','uploads/phong2.jpg',1,3,5,'Máy giặt, Bảo vệ, Ban công','2025-05-19 06:04:09','0922222222',1,0),(3,'Ký túc xá giá rẻ','Phòng ở ghép, sạch sẽ, mỗi người 600k, có wifi và khu nấu ăn riêng.',600000,25,6,'Số 9, Hưng Dũng, TP Vinh','18.6610, 105.6700','uploads/phong3.jpg',2,4,2,'Wifi, Nhà bếp, Giá rẻ','2025-05-19 06:04:09','0933333333',1,0),(4,'Phòng trọ yên tĩnh khu dân cư','Phòng riêng biệt, 18m², có gác xép, khu vực yên tĩnh, thích hợp sinh viên nữ.',1000000,18,7,'Số 22, Bến Thủy, TP Vinh','18.6690, 105.6600','uploads/phong4.jpg',3,1,4,'Gác xép, Yên tĩnh','2025-05-19 06:04:09','0944444444',1,0),(5,'Phòng cao cấp full nội thất','Phòng đẹp, có máy lạnh, tủ lạnh, giường nệm, diện tích 35m². Bao phí dịch vụ.',2500000,35,35,'Số 3, TP Vinh','18.6700, 105.6750','uploads/phong5.jpg',1,5,1,'Máy lạnh, Tủ lạnh, Full nội thất','2025-05-19 06:04:09','0955555555',1,0),(6,'dsafsgdhfjg','dsafsgdh',432,432,1,'Số 06, đường Phạm Thị Tảo, Xã Lũng Cú, Huyện Đồng Văn, Tỉnh Hà Giang','10.763497, 106.656303','uploads/1747672343_trang-tri-phong-tro-3.jpg',11,4,7,'Điều hòa, máy giặt, Wifi','2025-05-19 16:32:23','234',1,0);
/*!40000 ALTER TABLE `motel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `motel_images`
--

DROP TABLE IF EXISTS `motel_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `motel_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `motel_id` int NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_order` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_motel_images_motel` (`motel_id`),
  CONSTRAINT `fk_motel_images_motel` FOREIGN KEY (`motel_id`) REFERENCES `motel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `motel_images`
--

LOCK TABLES `motel_images` WRITE;
/*!40000 ALTER TABLE `motel_images` DISABLE KEYS */;
INSERT INTO `motel_images` VALUES (1,6,'uploads/rooms/1747672343_0_mau-thiet-ke-tone-xam-651cda6ec9649b0ef5c6f6f1.webp',0,'2025-05-19 16:32:23'),(2,6,'uploads/rooms/1747672343_1_thiet-ke-nha-tro-dep-2020-bandon-0.jpg',1,'2025-05-19 16:32:23'),(3,6,'uploads/rooms/1747672343_2_trang-tri-phong-tro-3.jpg',2,'2025-05-19 16:32:23');
/*!40000 ALTER TABLE `motel_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_wishlist`
--

DROP TABLE IF EXISTS `user_wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_wishlist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `motel_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_motel` (`user_id`,`motel_id`),
  KEY `motel_id` (`motel_id`),
  CONSTRAINT `user_wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_wishlist_ibfk_2` FOREIGN KEY (`motel_id`) REFERENCES `motel` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_wishlist`
--

LOCK TABLES `user_wishlist` WRITE;
/*!40000 ALTER TABLE `user_wishlist` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_wishlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` int DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Nguyễn Văn A','nguyenvana','vana@example.com','123456',2,'0901234567','avatar1.jpg'),(2,'Trần Thị B','tranthib','thib@example.com','123456',2,'0912345678','avatar2.jpg'),(3,'Lê Văn C','levanc','vanc@example.com','123456',1,'0923456789','avatar3.jpg'),(4,'Phạm Thị D','phamthid','thid@example.com','123456',2,'0934567890','avatar4.jpg'),(5,'Hoàng Văn E','hoangvane','vane@example.com','123456',2,'0945678901','avatar5.jpg'),(6,'Đỗ Thị F','dothif','thif@example.com','123456',2,'0956789012','avatar6.jpg'),(7,'Ngô Văn G','ngovang','vang@example.com','123456',2,'0967890123','avatar7.jpg'),(8,'Vũ Thị H','vuthih','thih@example.com','123456',2,'0978901234','avatar8.jpg'),(9,'Bùi Văn I','buivani','vani@example.com','123456',2,'0989012345','avatar9.jpg'),(10,'Đặng Thị K','dangthik','thik@example.com','123456',2,'0990123456','avatar10.jpg'),(11,'Phan Quốc Tuấn','tuannopro','pqtuan2k4@gmail.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',1,'0987654321','images/avatar_1747412009_3868.jpg');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-19 23:37:43
