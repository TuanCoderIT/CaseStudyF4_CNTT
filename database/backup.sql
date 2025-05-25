-- MySQL dump 10.13  Distrib 8.0.42, for macos13.7 (arm64)
--
-- Host: 103.97.126.29    Database: yoxdhlgk_Case_Study
-- ------------------------------------------------------
-- Server version	5.7.41-cll-lve

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
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `motel_id` int(11) NOT NULL,
  `deposit_amount` int(11) NOT NULL,
  `commission_pct` decimal(5,2) NOT NULL DEFAULT '5.00',
  `status` enum('PENDING','SUCCESS','FAILED','REFUND_REQUESTED','RELEASED','REFUNDED') NOT NULL DEFAULT 'PENDING',
  `vnp_transaction_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `request_refund_at` datetime DEFAULT NULL,
  `refund_code` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `motel_id` (`motel_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`motel_id`) REFERENCES `motel` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (29,1,18,5000000,5.00,'REFUNDED','14975742','2025-05-23 12:58:14','2025-05-24 09:20:23','2025-05-24 16:20:01',NULL),(31,1,19,1000000,5.00,'REFUNDED','14976016','2025-05-23 16:14:48','2025-05-24 09:19:38','2025-05-24 15:54:54',NULL),(34,6,16,2500000,5.00,'REFUNDED','14976146','2025-05-23 17:38:28','2025-05-24 08:43:07','2025-05-24 03:57:17',NULL),(35,3,16,2500000,5.00,'PENDING',NULL,'2025-05-24 14:26:30','2025-05-24 14:26:30',NULL,NULL);
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Phòng trọ thường'),(2,'Phòng ở ghép'),(3,'Chung cư mini'),(4,'Ký túc xá'),(5,'Phòng cao cấp'),(6,'Phòng trọ khép kín'),(7,'Phòng trọ chung vệ sinh'),(8,'Phòng trọ gác lửng'),(9,'Phòng trọ mini'),(10,'Phòng trọ giá rẻ'),(11,'Nhà trọ nguyên căn'),(12,'Chung cư mini'),(13,'Căn hộ studio'),(14,'Căn hộ dịch vụ'),(15,'Căn hộ 1 phòng ngủ'),(16,'Căn hộ 2 phòng ngủ'),(17,'Căn hộ 3 phòng ngủ'),(18,'Officetel'),(19,'Penthouse'),(20,'Duplex'),(21,'Ở ghép phòng trọ'),(22,'Ở ghép căn hộ'),(23,'Ký túc xá sinh viên'),(24,'Ký túc xá tư nhân'),(25,'Nhà nguyên căn'),(26,'Nhà mặt phố'),(27,'Nhà trong hẻm'),(28,'Homestay dài hạn'),(29,'Nhà container'),(30,'Tiny house'),(31,'Coliving / Cohousing'),(32,'Farmstay / Nhà vườn');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `districts`
--

DROP TABLE IF EXISTS `districts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `districts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `districts`
--

LOCK TABLES `districts` WRITE;
/*!40000 ALTER TABLE `districts` DISABLE KEYS */;
INSERT INTO `districts` VALUES (14,'Bến Thủy'),(15,'Cửa Nam'),(16,'Đông Vĩnh'),(17,'Hà Huy Tập'),(18,'Hưng Bình'),(19,'Hưng Dũng'),(20,'Hưng Phúc'),(21,'Lê Lợi'),(22,'Quán Bàu'),(23,'Quang Trung'),(24,'Trung Đô'),(25,'Trường Thi'),(26,'Vinh Tân'),(27,'Hưng Đông'),(28,'Hưng Lộc'),(29,'Nghi Đức'),(30,'Nghi Phú'),(31,'Nghi Hải'),(32,'Nghi Hòa'),(33,'Nghi Hương'),(34,'Nghi Thu'),(35,'Thu Thủy'),(36,'Nghi Thủy'),(37,'Nghi Tân');
/*!40000 ALTER TABLE `districts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `motel`
--

DROP TABLE IF EXISTS `motel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `motel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `default_deposit` int(11) DEFAULT NULL,
  `isExist` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_motel_category` (`category_id`),
  KEY `fk_motel_district` (`district_id`),
  CONSTRAINT `fk_motel_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_motel_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `motel`
--

LOCK TABLES `motel` WRITE;
/*!40000 ALTER TABLE `motel` DISABLE KEYS */;
INSERT INTO `motel` VALUES (16,'Chung cư CT 2B Quang Trung Diện Tích 56m2','<p>giá thuê 5 triệu/ Tháng .</p><p>Em cho thuê chung cư CT 2B Quang Trung Diện Tích 56m2</p><p>-nội thất đầy đủ</p><p>- Liên Hệ Xem Nhà :0355793581 ( Em Thăng )</p>',5000000,56,12,'chung cư CT 2B, Quang Trung, Thành phố Vinh, Nghệ An','18.6763,105.67613','uploads/banner/1748138747_63b548ae81a263fc3ab3_1551067966.jpg',11,3,23,'Wifi, Điều hòa, Nóng lạnh, Gửi xe, Bảo vệ, WC riêng, Bếp, Tủ lạnh','2025-05-21 04:37:24','0365043804',1,1,2500000,0),(17,'Phòng trọ dạng căn hộ mini cao cấp','<p>Phòng sạch sẽ, có gác xép, máy lạnh, wifi miễn phí. Khu vực an ninh.</p>',2500000,30,2,'Số 06, ngõ 1A, đường Phạm Thị Tảo, Bến Thủy, Thành phố Vinh, Nghệ An','18.663709,105.701212','uploads/banner/1747820435_z2855400259529-10bc9fcf3c4b7da3c267a27fd9f3124a_1634531314.jpg',11,3,14,'55','2025-05-21 09:40:35','0365043804',1,0,1000000,1),(18,'THANH DAT HOME - hệ thống căn hộ dịch vụ, chung cư mini, phòng trọ cao cấp.','<p>THANH DAT HOME - hệ thống căn hộ dịch vụ, chung cư mini, phòng trọ cao cấp.</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t75/2/16/2728.png\" alt=\"✨\" height=\"16\" width=\"16\"> Vừa khai trương cơ sở mới:</p><p>Số 5, Ngõ 9, Phan Thái Ất – P. Hà Huy Tập</p><p>Và còn phòng ở cơ sở khác rải khắp TP. Vinh:</p><p>- Home 1: 21B Hồ Tùng Mậu, phường Trường Thi (gần Quảng Trường, Bưu điện)</p><p>- Home 13: 13 Lý Tự Trọng, phường Hà Huy Tập (gần ĐH Kinh Tế Nghệ An)</p><p>- Home 14: 230C Võ Nguyên Hiến, phường Hưng Dũng (ks Đạt Phú, gần Hồ Goong)</p><p>- Home 15: 99 Đặng Tất, Phường Lê Mao (gần Go! Big C)</p><p>- Home 19: Số 5 Ngõ 9 Phan Thái Ất, phường Hà Huy Tập (gần trường ĐH Kinh Tế Nghệ An)</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/ta3/2/16/260e.png\" alt=\"☎️\" height=\"16\" width=\"16\"> 08.3233.3737 (zl) liên hệ xem phòng nhé!</p>',9900000,50,7,'Hẻm 1A Đường Phạm Thị Tảo 6, Bến Thủy, Thành phố Vinh, Nghệ An','18.66798,105.7059','uploads/banner/1747904854_500107773_2722267114627774_7367159711626814427_n.jpg',11,3,14,'Wifi, Điều hòa, Nóng lạnh, Gửi xe, Bảo vệ, WC riêng, Bếp, Tủ lạnh','2025-05-22 09:07:34','0365043804',1,1,5000000,1),(19,'Phòng trọ khép kín cao tầng','<p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t59/2/16/1f4e3.png\" alt=\"📣\" height=\"16\" width=\"16\"> TRỐNG 1 PHÒNG Ở ĐƯỢC LUÔN </p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t16/2/16/1f3e1.png\" alt=\"🏡\" height=\"16\" width=\"16\"> Địa chỉ : số 2/N ngõ 15 đường Nguyễn Văn Trỗi, Bến Thuỷ, Thành phố Vinh, Nghệ An</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/tcc/2/16/1f4cd.png\" alt=\"📍\" height=\"16\" width=\"16\"> Vị trí : Siêu Hót ở khu vực Trung tâm: ngay Đại học Vinh, Chợ, Bệnh viện, Quảng trường, Công viên,….</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t4b/2/16/1f4cc.png\" alt=\"📌\" height=\"16\" width=\"16\">Thiết kế :  khép kín, có gác xép </p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t7e/2/16/1f4b5.png\" alt=\"💵\" height=\"16\" width=\"16\">Giá : 1.9triệu</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t50/2/16/1f6cf.png\" alt=\"🛏\" height=\"16\" width=\"16\"> NỘI THẤT : NÓNG LẠNH, ĐIỀU HOÀ, KỆ BẾP, WIFI, MÁY GIẶT...</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t89/2/16/1f4ec.png\" alt=\"📬\" height=\"16\" width=\"16\"> Có an ninh đảm bảo <img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/tb5/2/16/23f0.png\" alt=\"⏰\" height=\"16\" width=\"16\">Cổng vân tay , giờ giấc tự do </p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/td8/2/16/1f4e2.png\" alt=\"📢\" height=\"16\" width=\"16\"> Chỉ còn 1 số phòng, Liên hệ sớm xem phòng và giữ chỗ: 0985138511</p>',1900000,50,7,'số 2/N ngõ 15 đường Nguyễn Văn Trỗi, Bến Thủy, Thành phố Vinh, Nghệ An','18.65915,105.70024','uploads/banner/1747905030_499990681_9977864108926643_6198584699326133275_n.jpg',11,2,14,'Wifi, Điều hòa, Nóng lạnh, Gửi xe, Bảo vệ, WC riêng, Bếp, Tủ lạnh','2025-05-22 09:10:30','0365043804',1,0,1000000,1);
/*!40000 ALTER TABLE `motel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `motel_images`
--

DROP TABLE IF EXISTS `motel_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `motel_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `motel_id` int(11) NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_order` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_motel_images_motel` (`motel_id`),
  CONSTRAINT `fk_motel_images_motel` FOREIGN KEY (`motel_id`) REFERENCES `motel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `motel_images`
--

LOCK TABLES `motel_images` WRITE;
/*!40000 ALTER TABLE `motel_images` DISABLE KEYS */;
INSERT INTO `motel_images` VALUES (36,16,'uploads/rooms/1747802244_1_photo-1-16446400187821764344412.png',1,'2025-05-21 04:37:24'),(37,16,'uploads/rooms/1747802244_2_photo-1-16446400216191316154621.jpeg',2,'2025-05-21 04:37:24'),(38,16,'uploads/rooms/1747802244_3_photo-2-16446400216851972532173.jpeg',3,'2025-05-21 04:37:24'),(39,17,'uploads/rooms/1747820435_0_b6210c39c535276b7e24_1551067968.jpg',0,'2025-05-21 09:40:35'),(40,17,'uploads/rooms/1747820435_1_z2855400230250-0407dd8ca845c5a00c19ac29a7728f68_1634531321.jpg',1,'2025-05-21 09:40:35'),(41,17,'uploads/rooms/1747820435_2_z2855400236910-509061cc2c6e2d8478ebbcf128836c01_1634531320.jpg',2,'2025-05-21 09:40:35'),(42,17,'uploads/rooms/1747820435_3_z2855400250427-8f5eee0957ec9d7ae9779b626107a049_1634531321.jpg',3,'2025-05-21 09:40:35'),(43,17,'uploads/rooms/1747820435_4_z2855400259529-10bc9fcf3c4b7da3c267a27fd9f3124a_1634531314.jpg',4,'2025-05-21 09:40:35'),(44,17,'uploads/rooms/1747820435_5_z2855400265005-8c866bb37a39e726bfbaa1ab9634753b_1634531320.jpg',5,'2025-05-21 09:40:35'),(45,16,'uploads/rooms/1747841457_0_60eb11f6d8fa3aa463eb_1551067970.jpg',0,'2025-05-21 15:30:57'),(46,18,'uploads/rooms/1747904854_0_499796835_2722267181294434_8024619422153518198_n.jpg',0,'2025-05-22 09:07:34'),(47,18,'uploads/rooms/1747904854_1_499866893_2722267187961100_7462858564938584970_n.jpg',1,'2025-05-22 09:07:34'),(48,18,'uploads/rooms/1747904854_2_499932212_2722267121294440_7444071810466581045_n.jpg',2,'2025-05-22 09:07:34'),(49,19,'uploads/rooms/1747905030_0_498913603_9977864268926627_1597753980648069284_n.jpg',0,'2025-05-22 09:10:30'),(50,19,'uploads/rooms/1747905030_1_499250549_9977864262259961_2852767426034860054_n.jpg',1,'2025-05-22 09:10:30'),(51,19,'uploads/rooms/1747905030_2_499532684_9977864368926617_9022903704693140757_n.jpg',2,'2025-05-22 09:10:30'),(52,19,'uploads/rooms/1747905030_3_499606779_9977864145593306_792390631925851312_n.jpg',3,'2025-05-22 09:10:30');
/*!40000 ALTER TABLE `motel_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,11,'Có đặt cọc mới!','Phòng \"Phòng trọ khép kín\" vừa được đặt cọc thành công bởi Nguyễn Trọng Truyền. Số tiền cọc: 1,000,000₫',1,'2025-05-23 16:18:23'),(2,1,'Đặt cọc thành công!','Bạn đã đặt cọc thành công phòng \"Phòng trọ khép kín\". Số tiền cọc: 1,000,000₫',1,'2025-05-23 16:18:23'),(3,11,'Có đặt cọc mới!','Phòng \"Chung cư CT 2B Quang Trung Diện Tích 56m2\" vừa được đặt cọc thành công bởi Đỗ Thị F. Số tiền cọc: 2,500,000₫',1,'2025-05-23 17:39:35'),(4,6,'Đặt cọc thành công!','Bạn đã đặt cọc thành công phòng \"Chung cư CT 2B Quang Trung Diện Tích 56m2\". Số tiền cọc: 2,500,000₫',1,'2025-05-23 17:39:35'),(5,6,'Yêu cầu hoàn tiền đã được gửi','Yêu cầu hoàn tiền đặt cọc cho phòng \"Chung cư CT 2B Quang Trung Diện Tích 56m2\" đã được gửi. Chúng tôi sẽ xử lý trong vòng 24 giờ.',1,'2025-05-23 20:57:17'),(6,11,'Có yêu cầu hoàn tiền mới','Người thuê Đỗ Thị F đã yêu cầu hoàn tiền đặt cọc cho phòng \"Chung cư CT 2B Quang Trung Diện Tích 56m2\". Hệ thống sẽ xử lý trong vòng 24 giờ.',1,'2025-05-23 20:57:17'),(7,6,'Yêu cầu hoàn tiền đã được chấp thuận','Yêu cầu hoàn tiền cọc của bạn cho phòng \"Chung cư CT 2B Quang Trung Diện Tích 56m2\" đã được chấp thuận. Tiền cọc sẽ được hoàn trả trong vòng 24 giờ.',1,'2025-05-24 08:43:07'),(8,11,'Thông báo hoàn tiền cọc','Yêu cầu hoàn tiền cọc từ người thuê Đỗ Thị F cho phòng \"Chung cư CT 2B Quang Trung Diện Tích 56m2\" đã được chấp thuận.',1,'2025-05-24 08:43:07'),(9,1,'Yêu cầu hoàn tiền đã được gửi','Yêu cầu hoàn tiền đặt cọc cho phòng \"Phòng trọ khép kín\" đã được gửi. Chúng tôi sẽ xử lý trong vòng 24 giờ.',1,'2025-05-24 08:54:54'),(10,11,'Có yêu cầu hoàn tiền mới','Người thuê Nguyễn Trọng Truyền đã yêu cầu hoàn tiền đặt cọc cho phòng \"Phòng trọ khép kín\". Hệ thống sẽ xử lý trong vòng 24 giờ.',1,'2025-05-24 08:54:54'),(13,1,'Yêu cầu hoàn tiền đã được chấp thuận','Yêu cầu hoàn tiền cọc của bạn cho phòng \"Phòng trọ khép kín\" đã được chấp thuận. Tiền cọc sẽ được hoàn trả trong vòng 24 giờ.',1,'2025-05-24 09:19:38'),(14,11,'Thông báo hoàn tiền cọc','Yêu cầu hoàn tiền cọc từ người thuê Nguyễn Trọng Truyền cho phòng \"Phòng trọ khép kín\" đã được chấp thuận.',1,'2025-05-24 09:19:38'),(15,1,'Yêu cầu hoàn tiền đã được gửi','Yêu cầu hoàn tiền đặt cọc cho phòng \"THANH DAT HOME - hệ thống căn hộ dịch vụ, chung cư mini, phòng trọ cao cấp.\" đã được gửi. Chúng tôi sẽ xử lý trong vòng 24 giờ.',1,'2025-05-24 09:20:01'),(16,11,'Có yêu cầu hoàn tiền mới','Người thuê Nguyễn Trọng Truyền đã yêu cầu hoàn tiền đặt cọc cho phòng \"THANH DAT HOME - hệ thống căn hộ dịch vụ, chung cư mini, phòng trọ cao cấp.\". Hệ thống sẽ xử lý trong vòng 24 giờ.',1,'2025-05-24 09:20:01'),(17,1,'Yêu cầu hoàn tiền đã được chấp thuận','Yêu cầu hoàn tiền cọc của bạn cho phòng \"THANH DAT HOME - hệ thống căn hộ dịch vụ, chung cư mini, phòng trọ cao cấp.\" đã được chấp thuận. Tiền cọc sẽ được hoàn trả trong vòng 24 giờ.',1,'2025-05-24 09:20:23'),(18,11,'Thông báo hoàn tiền cọc','Yêu cầu hoàn tiền cọc từ người thuê Nguyễn Trọng Truyền cho phòng \"THANH DAT HOME - hệ thống căn hộ dịch vụ, chung cư mini, phòng trọ cao cấp.\" đã được chấp thuận.',1,'2025-05-24 09:20:23');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_wishlist`
--

DROP TABLE IF EXISTS `user_wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `motel_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_motel` (`user_id`,`motel_id`),
  KEY `motel_id` (`motel_id`),
  CONSTRAINT `user_wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_wishlist_ibfk_2` FOREIGN KEY (`motel_id`) REFERENCES `motel` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_wishlist`
--

LOCK TABLES `user_wishlist` WRITE;
/*!40000 ALTER TABLE `user_wishlist` DISABLE KEYS */;
INSERT INTO `user_wishlist` VALUES (3,11,16,'2025-05-23 11:02:02'),(4,11,18,'2025-05-23 11:44:15');
/*!40000 ALTER TABLE `user_wishlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` int(11) DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Nguyễn Trọng Truyền','admin','vana@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',1,'0901234567','uploads/avatar/avatar_1747809296_2038.jpg'),(2,'Trần Thị B','tranthib','thib@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',2,'0912345678','uploads/avatar/avatar_1747801182_5629.jpg'),(3,'Lê Văn C','levanc','vanc@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',1,'0923456789','uploads/avatar/avatar_1747801182_5629.jpg'),(4,'Phạm Thị D','phamthid','thid@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',2,'0934567890','uploads/avatar/avatar_1747801182_5629.jpg'),(5,'Hoàng Văn E','hoangvane','vane@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',2,'0945678901','uploads/avatar/avatar_1747801182_5629.jpg'),(6,'Đỗ Thị F','dothif','thif@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',2,'0956789012','uploads/avatar/avatar_1747801182_5629.jpg'),(7,'Ngô Văn G','ngovang','vang@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',2,'0967890123','uploads/avatar/avatar_1747801182_5629.jpg'),(8,'Vũ Thị H','vuthih','thih@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',2,'0978901234','uploads/avatar/avatar_1747801182_5629.jpg'),(9,'Bùi Văn I','buivani','vani@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',2,'0989012345','uploads/avatar/avatar_1747801182_5629.jpg'),(10,'Đặng Thị K','dangthik','thik@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',2,'0990123456','uploads/avatar/avatar_1747801182_5629.jpg'),(11,'Phan Quốc Tuấn','tuannopro','pqtuan2k4@gmail.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',1,'0987654321','uploads/avatar/avatar_1747801182_5629.jpg'),(18,'Nguyễn Xuân Huỳnh','huynh','nguyenhuynhdt37@gmail.com','$2y$12$DljcxdroIgbDKxDoWaIs7eUpOHD66Sn/RZ4grGoNw2wVX9MTn.kni',NULL,'0365043804','uploads/avatar/avatar_1748140402_9313.jpgpg');
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

-- Dump completed on 2025-05-25 11:32:00
