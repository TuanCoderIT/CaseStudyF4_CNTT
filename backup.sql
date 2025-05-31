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
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (41,18,19,1000000,5.00,'REFUND_REQUESTED','14978135','2025-05-25 09:15:46','2025-05-25 14:15:51','2025-05-25 21:15:51',NULL),(42,3,18,5000000,5.00,'REFUND_REQUESTED','14978189','2025-05-25 09:35:41','2025-05-25 09:41:35','2025-05-25 16:41:35',NULL),(43,18,17,1000000,5.00,'PENDING',NULL,'2025-05-25 10:20:54','2025-05-25 10:20:54',NULL,NULL),(44,18,25,2500000,5.00,'PENDING',NULL,'2025-05-25 10:42:02','2025-05-25 10:42:02',NULL,NULL);
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
INSERT INTO `categories` VALUES (1,'Phòng trọ thường'),(2,'Phòng ở ghép'),(3,'Chung cư mini'),(4,'Ký túc xá'),(5,'Phòng cao cấp'),(6,'Phòng trọ khép kín'),(7,'Phòng trọ chung vệ sinh'),(8,'Phòng trọ gác lửng'),(9,'Phòng trọ mini'),(10,'Phòng trọ giá rẻ'),(11,'Nhà trọ nguyên căn'),(12,'Chung cư'),(13,'Căn hộ studio'),(14,'Căn hộ dịch vụ'),(15,'Căn hộ 1 phòng ngủ'),(16,'Căn hộ 2 phòng ngủ'),(17,'Căn hộ 3 phòng ngủ'),(18,'Officetel'),(19,'Penthouse'),(20,'Duplex'),(21,'Ở ghép phòng trọ'),(22,'Ở ghép căn hộ'),(23,'Ký túc xá sinh viên'),(24,'Ký túc xá tư nhân'),(25,'Nhà nguyên căn'),(26,'Nhà mặt phố'),(27,'Nhà trong hẻm'),(28,'Homestay dài hạn'),(29,'Nhà container'),(30,'Tiny house'),(31,'Coliving / Cohousing'),(32,'Farmstay / Nhà vườn');
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
  `isExist` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_motel_category` (`category_id`),
  KEY `fk_motel_district` (`district_id`),
  CONSTRAINT `fk_motel_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_motel_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `motel`
--

LOCK TABLES `motel` WRITE;
/*!40000 ALTER TABLE `motel` DISABLE KEYS */;
INSERT INTO `motel` VALUES (16,'Chung cư CT 2B Quang Trung Diện Tích 56m2','<p>giá thuê 5 triệu/ Tháng .</p><p>Em cho thuê chung cư CT 2B Quang Trung Diện Tích 56m2</p><p>-nội thất đầy đủ</p><p>- Liên Hệ Xem Nhà :0355793581 ( Em Thăng )</p>',5000000,56,22,'chung cư CT 2B, Quang Trung, Thành phố Vinh, Nghệ An','18.6763,105.67613','uploads/banner/1748138747_63b548ae81a263fc3ab3_1551067966.jpg',11,3,23,'Wifi, Điều hòa, Nóng lạnh, Gửi xe, Bảo vệ, WC riêng, Bếp, Tủ lạnh','2025-05-21 04:37:24','0365043804',1,1,2500000,1),(17,'Phòng trọ dạng căn hộ mini cao cấp','<p>Phòng sạch sẽ, có gác xép, máy lạnh, wifi miễn phí. Khu vực an ninh.</p>',2500000,30,9,'Số 06, ngõ 1A, đường Phạm Thị Tảo, Bến Thủy, Thành phố Vinh, Nghệ An','18.663709,105.701212','uploads/banner/1747820435_z2855400259529-10bc9fcf3c4b7da3c267a27fd9f3124a_1634531314.jpg',11,3,14,'Wifi, Điều hòa, Nóng lạnh, Gửi xe, Bảo vệ, WC riêng, Bếp, Tủ lạnh','2025-05-21 09:40:35','0365043804',1,3,1000000,1),(18,'THANH DAT HOME - hệ thống căn hộ dịch vụ, chung cư mini, phòng trọ cao cấp.','<p>THANH DAT HOME - hệ thống căn hộ dịch vụ, chung cư mini, phòng trọ cao cấp.</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t75/2/16/2728.png\" alt=\"✨\" height=\"16\" width=\"16\"> Vừa khai trương cơ sở mới:</p><p>Số 5, Ngõ 9, Phan Thái Ất – P. Hà Huy Tập</p><p>Và còn phòng ở cơ sở khác rải khắp TP. Vinh:</p><p>- Home 1: 21B Hồ Tùng Mậu, phường Trường Thi (gần Quảng Trường, Bưu điện)</p><p>- Home 13: 13 Lý Tự Trọng, phường Hà Huy Tập (gần ĐH Kinh Tế Nghệ An)</p><p>- Home 14: 230C Võ Nguyên Hiến, phường Hưng Dũng (ks Đạt Phú, gần Hồ Goong)</p><p>- Home 15: 99 Đặng Tất, Phường Lê Mao (gần Go! Big C)</p><p>- Home 19: Số 5 Ngõ 9 Phan Thái Ất, phường Hà Huy Tập (gần trường ĐH Kinh Tế Nghệ An)</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/ta3/2/16/260e.png\" alt=\"☎️\" height=\"16\" width=\"16\"> 08.3233.3737 (zl) liên hệ xem phòng nhé!</p>',9900000,50,16,'Hẻm 1A Đường Phạm Thị Tảo 6, Bến Thủy, Thành phố Vinh, Nghệ An','18.66798,105.7059','uploads/banner/1747904854_500107773_2722267114627774_7367159711626814427_n.jpg',11,3,14,'Wifi, Điều hòa, Nóng lạnh, Gửi xe, Bảo vệ, WC riêng, Bếp, Tủ lạnh','2025-05-22 09:07:34','0365043804',1,3,5000000,1),(19,'Phòng trọ khép kín cao tầng','<p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t59/2/16/1f4e3.png\" alt=\"📣\" height=\"16\" width=\"16\"> TRỐNG 1 PHÒNG Ở ĐƯỢC LUÔN </p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t16/2/16/1f3e1.png\" alt=\"🏡\" height=\"16\" width=\"16\"> Địa chỉ : số 2/N ngõ 15 đường Nguyễn Văn Trỗi, Bến Thuỷ, Thành phố Vinh, Nghệ An</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/tcc/2/16/1f4cd.png\" alt=\"📍\" height=\"16\" width=\"16\"> Vị trí : Siêu Hót ở khu vực Trung tâm: ngay Đại học Vinh, Chợ, Bệnh viện, Quảng trường, Công viên,….</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t4b/2/16/1f4cc.png\" alt=\"📌\" height=\"16\" width=\"16\">Thiết kế :  khép kín, có gác xép </p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t7e/2/16/1f4b5.png\" alt=\"💵\" height=\"16\" width=\"16\">Giá : 1.9triệu</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t50/2/16/1f6cf.png\" alt=\"🛏\" height=\"16\" width=\"16\"> NỘI THẤT : NÓNG LẠNH, ĐIỀU HOÀ, KỆ BẾP, WIFI, MÁY GIẶT...</p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/t89/2/16/1f4ec.png\" alt=\"📬\" height=\"16\" width=\"16\"> Có an ninh đảm bảo <img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/tb5/2/16/23f0.png\" alt=\"⏰\" height=\"16\" width=\"16\">Cổng vân tay , giờ giấc tự do </p><p><img src=\"https://static.xx.fbcdn.net/images/emoji.php/v9/td8/2/16/1f4e2.png\" alt=\"📢\" height=\"16\" width=\"16\"> Chỉ còn 1 số phòng, Liên hệ sớm xem phòng và giữ chỗ: 0985138511</p>',1900000,50,11,'số 2/N ngõ 15 đường Nguyễn Văn Trỗi, Bến Thủy, Thành phố Vinh, Nghệ An','18.65915,105.70024','uploads/banner/1747905030_499990681_9977864108926643_6198584699326133275_n.jpg',11,2,14,'Wifi, Điều hòa, Nóng lạnh, Gửi xe, Bảo vệ, WC riêng, Bếp, Tủ lạnh','2025-05-22 09:10:30','0365043804',1,0,1000000,0),(23,'Chung cư ','<p>Chỉ 2tr3</p><p>Hỗ trợ tháng đầu chuyển vào ở ngay được từ hôm nay</p><p>Phòng hiện đại rất rộng, đầy đủ nội thất, cửa sổ rộng, ban công thoáng mát, thang máy lên tận cửa phòng, ra vào khoá vân tay, camera từ cổng vào đến nhà luôn ạ</p><p>1 cọc 1. Giá phòng những tháng tiếp theo 4tr3 (bao gồm tất cả dịch vụ) rồi nhé mn</p>',2300000,50,7,'4 Đường Nguyễn Huy Oánh, Trường Thi, Thành phố Vinh, Nghệ An','18.66414,105.69822','uploads/banner/1748154258_499857147_4184182368492144_8003637233330776640_n.jpg',18,12,25,'Điều hòa, Tủ lạnh, Máy giặt, Nóng lạnh, WiFi, Giường, Tủ quần áo, Bàn học, Toilet riêng, Ban công, Gửi xe, An ninh 24/7, Gần trường học, Gần chợ, Gần bệnh viện','2025-05-25 06:24:18','0365043804',1,2,0,1),(24,'Mình tìm bạn nữ ở cùng bạn của mình','<p>Mình tìm bạn nữ ở cùng bạn của mình</p><p>Địa chỉ : Chùa láng ( không cần cọc )</p><p>Giá phòng 1tr350/tháng/người</p><p>Phòng có sẵn đồ rồi chỉ việc xách vali và vào ở thôi ạ(Giường,tủ lạnh,điều hoà,bếp,…).</p><p>-điện 4k</p><p>-nước 120k/n</p><p>-mạng 50k/n</p><p>-máy giặt 50k/n</p><p> Giờ giấc tự do không chung chủ</p>',1350000,50,5,'Đường Nguyễn Thái Học, Bến Thủy, Thành phố Vinh, Nghệ An','18.68452,105.69663','uploads/banner/1748154415_500120020_122230936556189794_7290920334073151472_n.jpg',18,2,14,'Tủ lạnh, Máy giặt','2025-05-25 06:26:55','0987654321',1,1,0,1),(25,'Chung cư Vinhomes Quang Trung','<h2><strong>Vị trí</strong></h2><p>Dự án Vinhomes Quang Trung tọa lạc ngay trung tâm TP. Vinh, với 2 mặt tiền đường Quang Trung và đường Hồng Bàng. Đây chính là khu vực trung tâm kinh tế, văn hóa, chính trị của TP Vinh, thuận lợi cho giao thông đi lại, sinh sống cũng như hoạt động kinh doanh.</p><ul><li>Cách 500m đến chợ Vinh</li><li>2km đến ga Vinh</li><li>6km đến sân bay Vinh</li><li>15km đến bãi biển Cửa Lò</li><li>15km đến bãi biển Xuân Thành</li></ul><p><br></p><h2><strong>Tiện ích</strong></h2><p>Hệ thống tiện ích dịch vụ cao cấp trong quần thể dự án Vincom Shophouse Diamond Legacy rất đa dạng, trong đó tiêu biểu là:</p><ul><li>Trung tâm thương mại Vincom với các nhãn hàng cao cấp</li><li>Phòng tập gym, chăm sóc sức khỏe</li><li>Spa, phòng tập Yoga</li><li>Hồ bơi</li><li>Phòng họp, phòng hội nghị</li><li>Rạp chiếu phim</li><li>Khu vui chơi, giải trí</li></ul><p>Vincom Vinh không chỉ là nơi ở mà còn đáp ứng các nhu cầu tận hưởng cuộc sống đẳng cấp của cư dân tại đây, hứa hẹn tạo nên cộng đồng đẳng cấp nhất TP. Vinh.</p><p>CĐT Vinhomes quy hoạch đường nội khu rộng từ 18m trở lên, đặc biệt là trục đường chính nội khu rộng 32m nối với 2 trục đường chính Đinh Công Tráng và Lê Hồng Phong, hứa hẹn sẽ trở thành con phố mua sắm sầm uất nhất TP.Vinh.</p>',9990000,80,8,'Nguyen Dinh Cuong Transportation Service, Bến Thủy, Thành phố Vinh, Nghệ An','18.66694,105.68566','uploads/banner/1748167052_phong-khach-vinhomes-ocean-park.jpg',11,17,14,'Wifi, Điều hòa, Nóng lạnh, Gửi xe, Bảo vệ, WC riêng, Bếp, Tủ lạnh','2025-05-25 09:57:32','0922 355 565',1,3,2500000,1),(26,'Chung cư mini','<p>Phòng có điều hòa, khép kín ,nóng lạnh ,chỗ phơi đồ thoáng mát, giá cả phải chăng,chủ trọ hòa đồng có cổng riêng và an ninh</p>',3000000,50,1,'Đại học vinh, Bến Thủy, Thành phố Vinh, Nghệ An','18.69795,105.67504','uploads/banner/1748181208_IMG_5093.webp',24,15,14,'Wifi, Gần trường, Điều hòa, Chỗ để xe, An ninh, Tự do','2025-05-25 13:53:29','0123456789',1,0,1500000,1),(27,'tròng trọ giá rẻ ','<p>Phòng có điều hòa, khép kín ,nóng lạnh ,chỗ phơi đồ thoáng mát, giá cả phải chăng,chủ trọ hòa đồng có cổng riêng và an ninh</p>',1500000,25,2,'nhà 20 ngõ 19 nguyễn kiệm, Bến Thủy, Thành phố Vinh, Nghệ An','18.65273,105.69671','uploads/banner/1748181512_trọ.jpg',22,15,14,'Wifi, Máy giặt, Gần trường, Tủ lạnh, Chỗ để xe, An ninh, Tự do','2025-05-25 13:58:33','0965887160',1,1,1500000,1),(28,'Chung cư Green View 3','<p>Phòng có điều hòa, khép kín ,nóng lạnh ,chỗ phơi đồ thoáng mát, giá cả phải chăng,chủ trọ hòa đồng có cổng riêng và an ninh</p>',2000000,25,1,'Đường Hoàng Nghĩa Lương, Lê Lợi, Thành phố Vinh, Nghệ An','18.68015,105.67055','uploads/banner/1748184066_OIP (2).jpg',27,15,21,'Tủ lạnh','2025-05-25 14:28:41','0357169403',1,0,0,1),(29,'chung cư Handico 3D','<p>Phòng có điều hòa, khép kín ,nóng lạnh ,chỗ phơi đồ thoáng mát, giá cả phải chăng,chủ trọ hòa đồng có cổng riêng và an ninh</p>',6000000,50,1,'đường Nguyễn Viết Xuân, Bến Thủy, Thành phố Vinh, Nghệ An','18.67231,105.70571','uploads/banner/1748183715_OIP.jpg',27,16,14,'Wifi, Máy giặt, Gần trường, Điều hòa, Tủ lạnh, Chỗ để xe, An ninh, Tự do','2025-05-25 14:35:15','0357169403',1,0,0,1),(30,'Phòng trọ khép kín','<p>Phòng có điều hòa, khép kín ,nóng lạnh ,chỗ phơi đồ thoáng mát, giá cả phải chăng,chủ trọ hòa đồng có cổng riêng và an ninh</p>',1300000,20,0,'đường Bạch Liêu, Bến Thủy, Thành phố Vinh, Nghệ An','18.66112,105.69713','uploads/banner/1748183825_Nhatro.6.jpg',27,6,14,'Wifi, Gần trường, Chỗ để xe, An ninh, Tự do','2025-05-25 14:37:05','0357169403',1,0,0,1),(31,'Chung Cư Cửa Tiền Home','<p>sạch sẽ, thoáng mát, gần trường học, gần chợ,...</p>',4000000,50,0,'khối Yên Giang, Vinh Tân, Thành phố Vinh, Nghệ An','18.67884,105.67566','uploads/banner/1748184394_OIP (3).jpg',27,3,26,'Wifi, Máy giặt, Gần trường, Điều hòa, Tủ lạnh, Chỗ để xe, An ninh, Tự do','2025-05-25 14:46:34','0357169403',1,0,0,1);
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
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `motel_images`
--

LOCK TABLES `motel_images` WRITE;
/*!40000 ALTER TABLE `motel_images` DISABLE KEYS */;
INSERT INTO `motel_images` VALUES (36,16,'uploads/rooms/1747802244_1_photo-1-16446400187821764344412.png',1,'2025-05-21 04:37:24'),(37,16,'uploads/rooms/1747802244_2_photo-1-16446400216191316154621.jpeg',2,'2025-05-21 04:37:24'),(38,16,'uploads/rooms/1747802244_3_photo-2-16446400216851972532173.jpeg',3,'2025-05-21 04:37:24'),(39,17,'uploads/rooms/1747820435_0_b6210c39c535276b7e24_1551067968.jpg',0,'2025-05-21 09:40:35'),(40,17,'uploads/rooms/1747820435_1_z2855400230250-0407dd8ca845c5a00c19ac29a7728f68_1634531321.jpg',1,'2025-05-21 09:40:35'),(41,17,'uploads/rooms/1747820435_2_z2855400236910-509061cc2c6e2d8478ebbcf128836c01_1634531320.jpg',2,'2025-05-21 09:40:35'),(42,17,'uploads/rooms/1747820435_3_z2855400250427-8f5eee0957ec9d7ae9779b626107a049_1634531321.jpg',3,'2025-05-21 09:40:35'),(43,17,'uploads/rooms/1747820435_4_z2855400259529-10bc9fcf3c4b7da3c267a27fd9f3124a_1634531314.jpg',4,'2025-05-21 09:40:35'),(44,17,'uploads/rooms/1747820435_5_z2855400265005-8c866bb37a39e726bfbaa1ab9634753b_1634531320.jpg',5,'2025-05-21 09:40:35'),(45,16,'uploads/rooms/1747841457_0_60eb11f6d8fa3aa463eb_1551067970.jpg',0,'2025-05-21 15:30:57'),(46,18,'uploads/rooms/1747904854_0_499796835_2722267181294434_8024619422153518198_n.jpg',0,'2025-05-22 09:07:34'),(47,18,'uploads/rooms/1747904854_1_499866893_2722267187961100_7462858564938584970_n.jpg',1,'2025-05-22 09:07:34'),(48,18,'uploads/rooms/1747904854_2_499932212_2722267121294440_7444071810466581045_n.jpg',2,'2025-05-22 09:07:34'),(49,19,'uploads/rooms/1747905030_0_498913603_9977864268926627_1597753980648069284_n.jpg',0,'2025-05-22 09:10:30'),(50,19,'uploads/rooms/1747905030_1_499250549_9977864262259961_2852767426034860054_n.jpg',1,'2025-05-22 09:10:30'),(51,19,'uploads/rooms/1747905030_2_499532684_9977864368926617_9022903704693140757_n.jpg',2,'2025-05-22 09:10:30'),(52,19,'uploads/rooms/1747905030_3_499606779_9977864145593306_792390631925851312_n.jpg',3,'2025-05-22 09:10:30'),(60,23,'uploads/rooms/1748154258_0_499825683_4184182375158810_6426505456004790655_n.jpg',0,'2025-05-25 06:24:18'),(61,23,'uploads/rooms/1748154258_1_499857147_4184182368492144_8003637233330776640_n (1).jpg',1,'2025-05-25 06:24:18'),(62,23,'uploads/rooms/1748160692_0_500107204_1419295455747986_2206032282926747300_n.jpg',2,'2025-05-25 08:11:33'),(63,24,'uploads/rooms/1748160864_0_495211684_1256274162502464_2149269544201324824_n.jpg',1,'2025-05-25 08:14:24'),(64,24,'uploads/rooms/1748160864_1_499825683_4184182375158810_6426505456004790655_n.jpg',2,'2025-05-25 08:14:24'),(65,24,'uploads/rooms/1748160864_2_499857147_4184182368492144_8003637233330776640_n (1).jpg',3,'2025-05-25 08:14:24'),(66,25,'uploads/rooms/1748167052_0_1606460581_z2095227097278_b87fd10ab66ff8c27a6744e100525527.jp.jpg',0,'2025-05-25 09:57:32'),(67,25,'uploads/rooms/1748167052_1_1606460584_z2095227103526_f26fd57823676bde9b424859c5813f86.jp.jpg',1,'2025-05-25 09:57:32'),(68,25,'uploads/rooms/1748167052_2_hinh-anh-can-ho-vinhomes-central -park-2-phong-ngu-thiet-ke-chi-tiet-so-2.jpg',2,'2025-05-25 09:57:32'),(69,25,'uploads/rooms/1748167052_3_phong-khach-vinhomes-ocean-park.jpg',3,'2025-05-25 09:57:32'),(70,27,'uploads/rooms/1748181512_0_t.jpg',0,'2025-05-25 13:58:33');
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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,11,'Có đặt cọc mới!','Phòng \"Phòng trọ khép kín\" vừa được đặt cọc thành công bởi Nguyễn Trọng Truyền. Số tiền cọc: 1,000,000₫',1,'2025-05-23 16:18:23'),(2,1,'Đặt cọc thành công!','Bạn đã đặt cọc thành công phòng \"Phòng trọ khép kín\". Số tiền cọc: 1,000,000₫',1,'2025-05-23 16:18:23'),(3,11,'Có đặt cọc mới!','Phòng \"Chung cư CT 2B Quang Trung Diện Tích 56m2\" vừa được đặt cọc thành công bởi Đỗ Thị F. Số tiền cọc: 2,500,000₫',1,'2025-05-23 17:39:35'),(4,6,'Đặt cọc thành công!','Bạn đã đặt cọc thành công phòng \"Chung cư CT 2B Quang Trung Diện Tích 56m2\". Số tiền cọc: 2,500,000₫',1,'2025-05-23 17:39:35'),(5,6,'Yêu cầu hoàn tiền đã được gửi','Yêu cầu hoàn tiền đặt cọc cho phòng \"Chung cư CT 2B Quang Trung Diện Tích 56m2\" đã được gửi. Chúng tôi sẽ xử lý trong vòng 24 giờ.',1,'2025-05-23 20:57:17'),(6,11,'Có yêu cầu hoàn tiền mới','Người thuê Đỗ Thị F đã yêu cầu hoàn tiền đặt cọc cho phòng \"Chung cư CT 2B Quang Trung Diện Tích 56m2\". Hệ thống sẽ xử lý trong vòng 24 giờ.',1,'2025-05-23 20:57:17'),(7,6,'Yêu cầu hoàn tiền đã được chấp thuận','Yêu cầu hoàn tiền cọc của bạn cho phòng \"Chung cư CT 2B Quang Trung Diện Tích 56m2\" đã được chấp thuận. Tiền cọc sẽ được hoàn trả trong vòng 24 giờ.',1,'2025-05-24 08:43:07'),(8,11,'Thông báo hoàn tiền cọc','Yêu cầu hoàn tiền cọc từ người thuê Đỗ Thị F cho phòng \"Chung cư CT 2B Quang Trung Diện Tích 56m2\" đã được chấp thuận.',1,'2025-05-24 08:43:07'),(9,1,'Yêu cầu hoàn tiền đã được gửi','Yêu cầu hoàn tiền đặt cọc cho phòng \"Phòng trọ khép kín\" đã được gửi. Chúng tôi sẽ xử lý trong vòng 24 giờ.',1,'2025-05-24 08:54:54'),(10,11,'Có yêu cầu hoàn tiền mới','Người thuê Nguyễn Trọng Truyền đã yêu cầu hoàn tiền đặt cọc cho phòng \"Phòng trọ khép kín\". Hệ thống sẽ xử lý trong vòng 24 giờ.',1,'2025-05-24 08:54:54'),(13,1,'Yêu cầu hoàn tiền đã được chấp thuận','Yêu cầu hoàn tiền cọc của bạn cho phòng \"Phòng trọ khép kín\" đã được chấp thuận. Tiền cọc sẽ được hoàn trả trong vòng 24 giờ.',1,'2025-05-24 09:19:38'),(14,11,'Thông báo hoàn tiền cọc','Yêu cầu hoàn tiền cọc từ người thuê Nguyễn Trọng Truyền cho phòng \"Phòng trọ khép kín\" đã được chấp thuận.',1,'2025-05-24 09:19:38'),(15,1,'Yêu cầu hoàn tiền đã được gửi','Yêu cầu hoàn tiền đặt cọc cho phòng \"THANH DAT HOME - hệ thống căn hộ dịch vụ, chung cư mini, phòng trọ cao cấp.\" đã được gửi. Chúng tôi sẽ xử lý trong vòng 24 giờ.',1,'2025-05-24 09:20:01'),(16,11,'Có yêu cầu hoàn tiền mới','Người thuê Nguyễn Trọng Truyền đã yêu cầu hoàn tiền đặt cọc cho phòng \"THANH DAT HOME - hệ thống căn hộ dịch vụ, chung cư mini, phòng trọ cao cấp.\". Hệ thống sẽ xử lý trong vòng 24 giờ.',1,'2025-05-24 09:20:01'),(17,1,'Yêu cầu hoàn tiền đã được chấp thuận','Yêu cầu hoàn tiền cọc của bạn cho phòng \"THANH DAT HOME - hệ thống căn hộ dịch vụ, chung cư mini, phòng trọ cao cấp.\" đã được chấp thuận. Tiền cọc sẽ được hoàn trả trong vòng 24 giờ.',1,'2025-05-24 09:20:23'),(18,11,'Thông báo hoàn tiền cọc','Yêu cầu hoàn tiền cọc từ người thuê Nguyễn Trọng Truyền cho phòng \"THANH DAT HOME - hệ thống căn hộ dịch vụ, chung cư mini, phòng trọ cao cấp.\" đã được chấp thuận.',1,'2025-05-24 09:20:23'),(19,11,'Có đặt cọc mới!','Phòng \"Phòng trọ khép kín cao tầng\" vừa được đặt cọc thành công bởi Nguyễn Xuân Huỳnh. Số tiền cọc: 1,000,000₫',1,'2025-05-25 09:16:19'),(20,18,'Đặt cọc thành công!','Bạn đã đặt cọc thành công phòng \"Phòng trọ khép kín cao tầng\". Số tiền cọc: 1,000,000₫',0,'2025-05-25 09:16:19'),(21,11,'Có đặt cọc mới!','Phòng \"Phòng trọ khép kín cao tầng\" vừa được đặt cọc thành công bởi Nguyễn Xuân Huỳnh. Số tiền cọc: 1,000,000₫',1,'2025-05-25 09:18:37'),(22,18,'Đặt cọc thành công!','Bạn đã đặt cọc thành công phòng \"Phòng trọ khép kín cao tầng\". Số tiền cọc: 1,000,000₫',0,'2025-05-25 09:18:37'),(23,18,'Xác nhận giải ngân tiền cọc','Bạn đã xác nhận giải ngân tiền cọc cho phòng \"Phòng trọ khép kín cao tầng\". Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi.',0,'2025-05-25 09:27:35'),(24,11,'Tiền cọc đã được giải ngân','Người thuê Nguyễn Xuân Huỳnh đã xác nhận giải ngân tiền cọc cho phòng \"Phòng trọ khép kín cao tầng\". Tiền đặt cọc đã được chuyển cho bạn.',1,'2025-05-25 09:27:35'),(25,11,'Có đặt cọc mới!','Phòng \"THANH DAT HOME - hệ thống căn hộ dịch vụ, chung cư mini, phòng trọ cao cấp.\" vừa được đặt cọc thành công bởi Lê Văn C. Số tiền cọc: 5,000,000₫',1,'2025-05-25 09:40:49'),(26,3,'Đặt cọc thành công!','Bạn đã đặt cọc thành công phòng \"THANH DAT HOME - hệ thống căn hộ dịch vụ, chung cư mini, phòng trọ cao cấp.\". Số tiền cọc: 5,000,000₫',0,'2025-05-25 09:40:49'),(27,3,'Yêu cầu hoàn tiền đã được gửi','Yêu cầu hoàn tiền đặt cọc cho phòng \"THANH DAT HOME - hệ thống căn hộ dịch vụ, chung cư mini, phòng trọ cao cấp.\" đã được gửi. Chúng tôi sẽ xử lý trong vòng 24 giờ.',0,'2025-05-25 09:41:35'),(28,11,'Có yêu cầu hoàn tiền mới','Người thuê Lê Văn C đã yêu cầu hoàn tiền đặt cọc cho phòng \"THANH DAT HOME - hệ thống căn hộ dịch vụ, chung cư mini, phòng trọ cao cấp.\". Hệ thống sẽ xử lý trong vòng 24 giờ.',1,'2025-05-25 09:41:35'),(29,11,'Có đặt cọc mới!','Phòng \"Phòng trọ khép kín cao tầng\" vừa được đặt cọc thành công bởi Nguyễn Xuân Huỳnh. Số tiền cọc: 1,000,000₫',1,'2025-05-25 14:15:46'),(30,18,'Đặt cọc thành công!','Bạn đã đặt cọc thành công phòng \"Phòng trọ khép kín cao tầng\". Số tiền cọc: 1,000,000₫',0,'2025-05-25 14:15:46'),(31,18,'Yêu cầu hoàn tiền đã được gửi','Yêu cầu hoàn tiền đặt cọc cho phòng \"Phòng trọ khép kín cao tầng\" đã được gửi. Chúng tôi sẽ xử lý trong vòng 24 giờ.',0,'2025-05-25 14:15:51'),(32,11,'Có yêu cầu hoàn tiền mới','Người thuê Nguyễn Xuân Huỳnh đã yêu cầu hoàn tiền đặt cọc cho phòng \"Phòng trọ khép kín cao tầng\". Hệ thống sẽ xử lý trong vòng 24 giờ.',1,'2025-05-25 14:15:51');
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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_wishlist`
--

LOCK TABLES `user_wishlist` WRITE;
/*!40000 ALTER TABLE `user_wishlist` DISABLE KEYS */;
INSERT INTO `user_wishlist` VALUES (3,11,16,'2025-05-23 11:02:02'),(4,11,18,'2025-05-23 11:44:15'),(5,3,18,'2025-05-25 09:33:12'),(6,21,23,'2025-05-25 13:43:15'),(7,21,17,'2025-05-25 13:43:47'),(8,21,25,'2025-05-25 13:44:03'),(9,22,25,'2025-05-25 13:47:29'),(10,24,17,'2025-05-25 13:47:46'),(11,22,18,'2025-05-25 13:48:03'),(12,24,23,'2025-05-25 13:48:16'),(13,24,25,'2025-05-25 13:48:36'),(14,22,24,'2025-05-25 13:49:18'),(15,22,17,'2025-05-25 13:50:05'),(16,11,27,'2025-05-25 14:05:53');
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
  `role` int(11) DEFAULT '2',
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Nguyễn Trọng Truyền','admin','vana@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',1,'0901234567','uploads/avatar/avatar_1747809296_2038.jpg'),(2,'Trần Thị B','tranthib','thib@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',2,'0912345678','uploads/avatar/avatar_1747801182_5629.jpg'),(3,'Lê Văn C','levanc','vanc@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',1,'0923456789','uploads/avatar/avatar_1747801182_5629.jpg'),(4,'Phạm Thị D','phamthid','thid@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',2,'0934567890','uploads/avatar/avatar_1747801182_5629.jpg'),(5,'Hoàng Văn E','hoangvane','vane@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',2,'0945678901','uploads/avatar/avatar_1747801182_5629.jpg'),(6,'Đỗ Thị F','dothif','thif@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',2,'0956789012','uploads/avatar/avatar_1747801182_5629.jpg'),(7,'Ngô Văn G','ngovang','vang@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',2,'0967890123','uploads/avatar/avatar_1747801182_5629.jpg'),(8,'Vũ Thị H','vuthih','thih@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',2,'0978901234','uploads/avatar/avatar_1747801182_5629.jpg'),(9,'Bùi Văn I','buivani','vani@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',2,'0989012345','uploads/avatar/avatar_1747801182_5629.jpg'),(10,'Đặng Thị K','dangthik','thik@example.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',2,'0990123456','uploads/avatar/avatar_1747801182_5629.jpg'),(11,'Phan Quốc Tuấn','tuannopro','pqtuan2k4@gmail.com','$2y$10$guS8PsCFpxZWK4hPw74VOuA7WzTg3mdbuUfmFFZpHk5hckaEb5TOe',1,'0987654321','uploads/avatar/avatar_1748087748_7310.png'),(18,'Nguyễn Xuân Huỳnh','huynh','nguyenhuynhdt37@gmail.com','$2y$12$DljcxdroIgbDKxDoWaIs7eUpOHD66Sn/RZ4grGoNw2wVX9MTn.kni',1,'0365043804','uploads/avatar/avatar_1747809296_2038.jpg'),(19,'Natasha Black','tuannopro22','nguyenhuynhdt3117@gmail.com','$2y$12$iKaTBPGxT7ZcZa6/uFQa1.nZL.gKTus2UU9rduZjGmc7qnmTf13my',0,'9876543','uploads/avatar/avatar_1748166864_8824.jpg'),(20,'lê văn bằng','bang','levanbang332004@gmail.com','$2y$12$6S3myapWdog0hMOXdcTOveM9YFZho.jVF07oxxRWcjLJALuydCgTS',2,'0967266408','uploads/avatar/avatar_1748166864_8824.jpg'),(21,'Trần Thị Linh','linhthitran','huyen20021998@gmail.com','$2y$12$WlXpjcgsPP20rXeo0WyR0ezbIKsjNQr9zSPeBQedW5j9Fgb2t27b2',2,'0377537270','uploads/avatar/avatar_1748166864_8824.jpg'),(22,'Trần Thị Thùy','thuy','tranthithuy110405@gmail.com','$2y$12$V6Z24QtdP.4m/ydjIai8RO94eANzfV/c9dX/FVB7ehiRNzayCC0Gi',2,'0965887160','uploads/avatar/default_avatar.png'),(23,'Natasha Black','aaaaaa','nguyenhuynhdt371@gmail.com','$2y$12$Q3r1piPHyWif/9NFfW5VCewAWDrckQz2GnOTxvC1LpkIq.KwqF.w6',2,'9876543','uploads/avatar/avatar_1748181802_8389.jpg'),(24,'Trần Thị Thuỳ','Thuỳ Trần','tranthuy@gmail.com','$2y$12$7KgMe0eweTYOWr2OcjgjtevH4OyX5EHi1PxixObK83hQZ0yEvdY2O',2,'0123456789','uploads/avatar/default_avatar.png'),(25,'Kim danh','Kimdanh','danhnguyenthikim@gmail.com','$2y$12$2ehCOQaM5UGB3npkD6Zoi.Zrh0kMYBjmZi5NO3dWsDzUHEeAWdmDS',2,'0812394700','uploads/avatar/avatar_1748181018_4476.jpeg'),(26,'Danh','Danhkim1924','danhkim@gmail.com','$2y$12$o5S4aH1saY88yCbOd0y3SenyxA3yn1I0DyP7bjrsvyg9rpUY9/iT6',2,'0812394700','uploads/avatar/default_avatar.png'),(27,'Bùi Minh Tâm','Mtam2004','btam1009@gmail.com','$2y$12$5dMmBtAMTYVhtwMV88wbQucoj7t4peE.FMJZwFb6ZvwKXRCn3X/vS',2,'0357169403','uploads/avatar/default_avatar.png'),(28,'Phan Bá Mạnh','phanbamanhskymtp','manhdusenpaifg@gmail.com','$2y$12$b7uNRXJp4olfXPbT/Ebd3uaHQ.s04raDZZhthBQAc/9E/B4uzT6li',2,'0946512908','uploads/avatar/avatar_1748184096_3435.jpg'),(29,'pn','pn','phamngoc14022004@gmail.com','$2y$12$0FIW/5d63x4S7X7KnwDVSeKZgc.ziek8f6B8q9v/AgYPCAzxsRqaq',2,'0982345768','uploads/avatar/avatar_1748185099_4819.png');
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

-- Dump completed on 2025-05-26 14:45:07
