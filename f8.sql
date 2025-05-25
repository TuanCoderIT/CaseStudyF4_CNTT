-- MySQL dump 10.13  Distrib 8.0.42, for macos13.7 (arm64)
--
-- Host: localhost    Database: f8_api
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
-- Table structure for table `__EFMigrationsHistory`
--

DROP TABLE IF EXISTS `__EFMigrationsHistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `__EFMigrationsHistory` (
  `MigrationId` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `ProductVersion` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`MigrationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `__EFMigrationsHistory`
--

LOCK TABLES `__EFMigrationsHistory` WRITE;
/*!40000 ALTER TABLE `__EFMigrationsHistory` DISABLE KEYS */;
INSERT INTO `__EFMigrationsHistory` VALUES ('20250421101241_InitCreate2','8.0.10'),('20250421101404_InitCreate2','8.0.10');
/*!40000 ALTER TABLE `__EFMigrationsHistory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lessontype`
--

DROP TABLE IF EXISTS `lessontype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lessontype` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lessontype`
--

LOCK TABLES `lessontype` WRITE;
/*!40000 ALTER TABLE `lessontype` DISABLE KEYS */;
INSERT INTO `lessontype` VALUES (1,'Bài giảng'),(2,'Câu hỏi về Code'),(3,'Câu hỏi trắc nghiệm'),(4,'Thông tin');
/*!40000 ALTER TABLE `lessontype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `level`
--

DROP TABLE IF EXISTS `level`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `level` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `level`
--

LOCK TABLES `level` WRITE;
/*!40000 ALTER TABLE `level` DISABLE KEYS */;
INSERT INTO `level` VALUES (1,'Kiến thức cơ bản',NULL,NULL),(2,'Kiến thức nâng cao',NULL,NULL);
/*!40000 ALTER TABLE `level` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblblogger`
--

DROP TABLE IF EXISTS `tblblogger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblblogger` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `isActive` tinyint(1) DEFAULT '0',
  `blog_type_id` int NOT NULL,
  `banner` varchar(100) DEFAULT NULL,
  `UserId` int DEFAULT NULL,
  `title` text,
  PRIMARY KEY (`id`),
  KEY `tblBlogger_tblBlogType_FK` (`blog_type_id`),
  KEY `tblBlogger_tblUser_FK` (`UserId`),
  CONSTRAINT `tblBlogger_tblBlogType_FK` FOREIGN KEY (`blog_type_id`) REFERENCES `tblblogtype` (`id`),
  CONSTRAINT `tblBlogger_tblUser_FK` FOREIGN KEY (`UserId`) REFERENCES `tbluser` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblblogger`
--

LOCK TABLES `tblblogger` WRITE;
/*!40000 ALTER TABLE `tblblogger` DISABLE KEYS */;
INSERT INTO `tblblogger` VALUES (5,'# Markdown: Syntax\r\n\r\n*   [Overview](#overview)\r\n    *   [Philosophy](#philosophy)\r\n    *   [Inline HTML](#html)\r\n    *   [Automatic Escaping for Special Characters](#autoescape)\r\n*   [Block Elements](#block)\r\n    *   [Paragraphs and Line Breaks](#p)\r\n    *   [Headers](#header)\r\n    *   [Blockquotes](#blockquote)\r\n    *   [Lists](#list)\r\n    *   [Code Blocks](#precode)\r\n    *   [Horizontal Rules](#hr)\r\n*   [Span Elements](#span)\r\n    *   [Links](#link)\r\n    *   [Emphasis](#em)\r\n    *   [Code](#code)\r\n    *   [Images](#img)\r\n*   [Miscellaneous](#misc)\r\n    *   [Backslash Escapes](#backslash)\r\n    *   [Automatic Links](#autolink)\r\n\r\n\r\n**Note:** This document is itself written using Markdown; you\r\ncan [see the source for it by adding \'.text\' to the URL](/projects/markdown/syntax.text).\r\n\r\n----\r\n\r\n## Overview\r\n\r\n### Philosophy\r\n\r\nMarkdown is intended to be as easy-to-read and easy-to-write as is feasible.\r\n\r\nReadability, however, is emphasized above all else. A Markdown-formatted\r\ndocument should be publishable as-is, as plain text, without looking\r\nlike it\'s been marked up with tags or formatting instructions. While\r\nMarkdown\'s syntax has been influenced by several existing text-to-HTML\r\nfilters -- including [Setext](http://docutils.sourceforge.net/mirror/setext.html), [atx](http://www.aaronsw.com/2002/atx/), [Textile](http://textism.com/tools/textile/), [reStructuredText](http://docutils.sourceforge.net/rst.html),\r\n[Grutatext](http://www.triptico.com/software/grutatxt.html), and [EtText](http://ettext.taint.org/doc/) -- the single biggest source of\r\ninspiration for Markdown\'s syntax is the format of plain text email.\r\n\r\n## Block Elements\r\n\r\n### Paragraphs and Line Breaks\r\n\r\nA paragraph is simply one or more consecutive lines of text, separated\r\nby one or more blank lines. (A blank line is any line that looks like a\r\nblank line -- a line containing nothing but spaces or tabs is considered\r\nblank.) Normal paragraphs should not be indented with spaces or tabs.\r\n\r\nThe implication of the \"one or more consecutive lines of text\" rule is\r\nthat Markdown supports \"hard-wrapped\" text paragraphs. This differs\r\nsignificantly from most other text-to-HTML formatters (including Movable\r\nType\'s \"Convert Line Breaks\" option) which translate every line break\r\ncharacter in a paragraph into a `<br />` tag.\r\n\r\nWhen you *do* want to insert a `<br />` break tag using Markdown, you\r\nend a line with two or more spaces, then type return.\r\n\r\n### Headers\r\n\r\nMarkdown supports two styles of headers, [Setext] [1] and [atx] [2].\r\n\r\nOptionally, you may \"close\" atx-style headers. This is purely\r\ncosmetic -- you can use this if you think it looks better. The\r\nclosing hashes don\'t even need to match the number of hashes\r\nused to open the header. (The number of opening hashes\r\ndetermines the header level.)\r\n\r\n\r\n### Blockquotes\r\n\r\nMarkdown uses email-style `>` characters for blockquoting. If you\'re\r\nfamiliar with quoting passages of text in an email message, then you\r\nknow how to create a blockquote in Markdown. It looks best if you hard\r\nwrap the text and put a `>` before every line:\r\n\r\n> This is a blockquote with two paragraphs. Lorem ipsum dolor sit amet,\r\n> consectetuer adipiscing elit. Aliquam hendrerit mi posuere lectus.\r\n> Vestibulum enim wisi, viverra nec, fringilla in, laoreet vitae, risus.\r\n> \r\n> Donec sit amet nisl. Aliquam semper ipsum sit amet velit. Suspendisse\r\n> id sem consectetuer libero luctus adipiscing.\r\n\r\nMarkdown allows you to be lazy and only put the `>` before the first\r\nline of a hard-wrapped paragraph:\r\n\r\n> This is a blockquote with two paragraphs. Lorem ipsum dolor sit amet,\r\nconsectetuer adipiscing elit. Aliquam hendrerit mi posuere lectus.\r\nVestibulum enim wisi, viverra nec, fringilla in, laoreet vitae, risus.\r\n\r\n> Donec sit amet nisl. Aliquam semper ipsum sit amet velit. Suspendisse\r\nid sem consectetuer libero luctus adipiscing.\r\n\r\nBlockquotes can be nested (i.e. a blockquote-in-a-blockquote) by\r\nadding additional levels of `>`:\r\n\r\n> This is the first level of quoting.\r\n>\r\n> > This is nested blockquote.\r\n>\r\n> Back to the first level.\r\n\r\nBlockquotes can contain other Markdown elements, including headers, lists,\r\nand code blocks:\r\n\r\n> ## This is a header.\r\n> \r\n> 1.   This is the first list item.\r\n> 2.   This is the second list item.\r\n> \r\n> Here\'s some example code:\r\n> \r\n>     return shell_exec(\"echo $input | $markdown_script\");\r\n\r\nAny decent text editor should make email-style quoting easy. For\r\nexample, with BBEdit, you can make a selection and choose Increase\r\nQuote Level from the Text menu.\r\n\r\n\r\n### Lists\r\n\r\nMarkdown supports ordered (numbered) and unordered (bulleted) lists.\r\n\r\nUnordered lists use asterisks, pluses, and hyphens -- interchangably\r\n-- as list markers:\r\n\r\n*   Red\r\n*   Green\r\n*   Blue\r\n\r\nis equivalent to:\r\n\r\n+   Red\r\n+   Green\r\n+   Blue\r\n\r\nand:\r\n\r\n-   Red\r\n-   Green\r\n-   Blue\r\n\r\nOrdered lists use numbers followed by periods:\r\n\r\n1.  Bird\r\n2.  McHale\r\n3.  Parish\r\n\r\nIt\'s important to note that the actual numbers you use to mark the\r\nlist have no effect on the HTML output Markdown produces. The HTML\r\nMarkdown produces from the above list is:\r\n\r\nIf you instead wrote the list in Markdown like this:\r\n\r\n1.  Bird\r\n1.  McHale\r\n1.  Parish\r\n\r\nor even:\r\n\r\n3. Bird\r\n1. McHale\r\n8. Parish\r\n\r\nyou\'d get the exact same HTML output. The point is, if you want to,\r\nyou can use ordinal numbers in your ordered Markdown lists, so that\r\nthe numbers in your source match the numbers in your published HTML.\r\nBut if you want to be lazy, you don\'t have to.\r\n\r\nTo make lists look nice, you can wrap items with hanging indents:\r\n\r\n*   Lorem ipsum dolor sit amet, consectetuer adipiscing elit.\r\n    Aliquam hendrerit mi posuere lectus. Vestibulum enim wisi,\r\n    viverra nec, fringilla in, laoreet vitae, risus.\r\n*   Donec sit amet nisl. Aliquam semper ipsum sit amet velit.\r\n    Suspendisse id sem consectetuer libero luctus adipiscing.\r\n\r\nBut if you want to be lazy, you don\'t have to:\r\n\r\n*   Lorem ipsum dolor sit amet, consectetuer adipiscing elit.\r\nAliquam hendrerit mi posuere lectus. Vestibulum enim wisi,\r\nviverra nec, fringilla in, laoreet vitae, risus.\r\n*   Donec sit amet nisl. Aliquam semper ipsum sit amet velit.\r\nSuspendisse id sem consectetuer libero luctus adipiscing.\r\n\r\nList items may consist of multiple paragraphs. Each subsequent\r\nparagraph in a list item must be indented by either 4 spaces\r\nor one tab:\r\n\r\n1.  This is a list item with two paragraphs. Lorem ipsum dolor\r\n    sit amet, consectetuer adipiscing elit. Aliquam hendrerit\r\n    mi posuere lectus.\r\n\r\n    Vestibulum enim wisi, viverra nec, fringilla in, laoreet\r\n    vitae, risus. Donec sit amet nisl. Aliquam semper ipsum\r\n    sit amet velit.\r\n\r\n2.  Suspendisse id sem consectetuer libero luctus adipiscing.\r\n\r\nIt looks nice if you indent every line of the subsequent\r\nparagraphs, but here again, Markdown will allow you to be\r\nlazy:\r\n\r\n*   This is a list item with two paragraphs.\r\n\r\n    This is the second paragraph in the list item. You\'re\r\nonly required to indent the first line. Lorem ipsum dolor\r\nsit amet, consectetuer adipiscing elit.\r\n\r\n*   Another item in the same list.\r\n\r\nTo put a blockquote within a list item, the blockquote\'s `>`\r\ndelimiters need to be indented:\r\n\r\n*   A list item with a blockquote:\r\n\r\n    > This is a blockquote\r\n    > inside a list item.\r\n\r\nTo put a code block within a list item, the code block needs\r\nto be indented *twice* -- 8 spaces or two tabs:\r\n\r\n*   A list item with a code block:\r\n\r\n        <code goes here>\r\n\r\n### Code Blocks\r\n\r\nPre-formatted code blocks are used for writing about programming or\r\nmarkup source code. Rather than forming normal paragraphs, the lines\r\nof a code block are interpreted literally. Markdown wraps a code block\r\nin both `<pre>` and `<code>` tags.\r\n\r\nTo produce a code block in Markdown, simply indent every line of the\r\nblock by at least 4 spaces or 1 tab.\r\n\r\nThis is a normal paragraph:\r\n\r\n    This is a code block.\r\n\r\nHere is an example of AppleScript:\r\n\r\n    tell application \"Foo\"\r\n        beep\r\n    end tell\r\n\r\nA code block continues until it reaches a line that is not indented\r\n(or the end of the article).\r\n\r\nWithin a code block, ampersands (`&`) and angle brackets (`<` and `>`)\r\nare automatically converted into HTML entities. This makes it very\r\neasy to include example HTML source code using Markdown -- just paste\r\nit and indent it, and Markdown will handle the hassle of encoding the\r\nampersands and angle brackets. For example, this:\r\n\r\n    <div class=\"footer\">\r\n        &copy; 2004 Foo Corporation\r\n    </div>\r\n\r\nRegular Markdown syntax is not processed within code blocks. E.g.,\r\nasterisks are just literal asterisks within a code block. This means\r\nit\'s also easy to use Markdown to write about Markdown\'s own syntax.\r\n\r\n```\r\ntell application \"Foo\"\r\n    beep\r\nend tell\r\n```\r\n\r\n## Span Elements\r\n\r\n### Links\r\n\r\nMarkdown supports two style of links: *inline* and *reference*.\r\n\r\nIn both styles, the link text is delimited by [square brackets].\r\n\r\nTo create an inline link, use a set of regular parentheses immediately\r\nafter the link text\'s closing square bracket. Inside the parentheses,\r\nput the URL where you want the link to point, along with an *optional*\r\ntitle for the link, surrounded in quotes. For example:\r\n\r\nThis is [an example](http://example.com/) inline link.\r\n\r\n[This link](http://example.net/) has no title attribute.\r\n\r\n### Emphasis\r\n\r\nMarkdown treats asterisks (`*`) and underscores (`_`) as indicators of\r\nemphasis. Text wrapped with one `*` or `_` will be wrapped with an\r\nHTML `<em>` tag; double `*`\'s or `_`\'s will be wrapped with an HTML\r\n`<strong>` tag. E.g., this input:\r\n\r\n*single asterisks*\r\n\r\n_single underscores_\r\n\r\n**double asterisks**\r\n\r\n__double underscores__\r\n\r\n### Code\r\n\r\nTo indicate a span of code, wrap it with backtick quotes (`` ` ``).\r\nUnlike a pre-formatted code block, a code span indicates code within a\r\nnormal paragraph. For example:\r\n\r\nUse the `printf()` function.','2024-11-23 20:01:23','2024-11-23 20:01:23',0,1,'/uploads/blogger/banner/z6042757190445_63f84f8ef1f0c5b6b64e338eac8c4ce5.jpg',36,'Hello Xin chào'),(6,'# ❤️ Tình Yêu: Ngọn Lửa Sưởi Ấm Trái Tim\r\n\r\n---\r\n![images.jpg](http://localhost:5217/uploads/images.jpg)\r\n\r\n## 🌸 Tình Yêu Là Gì?  \r\nTình yêu là một cảm xúc thuần khiết, đẹp đẽ và mạnh mẽ. Nó không chỉ là sự rung động giữa hai con người mà còn là nguồn cảm hứng vô tận cho nghệ thuật, văn học, và cuộc sống.  \r\n\r\nTình yêu không cần lý do, không có định nghĩa cụ thể. Đó có thể là:  \r\n- 🌟 **Tình yêu gia đình**: Gắn bó và hy sinh vô điều kiện.  \r\n- 🌹 **Tình yêu lứa đôi**: Sự đồng điệu giữa hai trái tim.  \r\n- 🌍 **Tình yêu cuộc sống**: Niềm đam mê với thế giới xung quanh.  \r\n\r\n---\r\n\r\n## 💌 Tại Sao Tình Yêu Quan Trọng?  \r\nTình yêu giúp con người:  \r\n1. 🫶 **Kết nối**: Xây dựng mối quan hệ ý nghĩa.  \r\n2. 💪 **Trưởng thành**: Hiểu và chấp nhận chính mình cùng người khác.  \r\n3. 🌈 **Hạnh phúc**: Mang lại niềm vui và cảm giác an yên.  \r\n\r\n---\r\n\r\n## 🌟 Những Điều Tạo Nên Tình Yêu Đẹp  \r\n- **Sự chân thành**: Hãy luôn trung thực với cảm xúc của bạn.  \r\n- **Tôn trọng**: Hiểu và chấp nhận sự khác biệt của nhau.  \r\n- **Chia sẻ**: Cùng nhau vượt qua thử thách, xây dựng niềm tin.  \r\n\r\n---\r\n\r\n## ✨ Một Vài Câu Nói Hay Về Tình Yêu  \r\n> \"Tình yêu không phải là thứ để tìm kiếm mà là thứ để cảm nhận.\"  \r\n> *- Vô danh*  \r\n\r\n> \"Người yêu bạn sẽ thấy cả nghìn điểm tốt ở bạn, trong khi người không yêu bạn chỉ nhìn thấy một sai lầm nhỏ.\"  \r\n> *- Vô danh*  \r\n\r\n---\r\n\r\n## 🎨 Kết Luận  \r\nTình yêu không chỉ là một phần của cuộc sống, mà là linh hồn của nó. Hãy yêu thương và trân trọng những người xung quanh, bởi tình yêu chính là điều kỳ diệu nhất mà chúng ta có thể trao và nhận.  \r\n\r\n❤️ **Yêu và được yêu là hạnh phúc lớn nhất trong đời.** ❤️\r\n','2024-11-30 23:38:38','2024-11-30 23:38:38',0,6,'/uploads/blogger/banner/images.jpg',36,'Tình yêu là gì ?'),(7,'Hình nền máy tính 4k thiên nhiên\r\nHình nền máy tính 4k về thiên nhiên là một trong những chủ đề được nhiều người ưa thích lựa chọn bởi các cảnh quan thiên nhiên đầy hùng vĩ, thơ mộng, từ đó tạo cảm giác thư giãn mỗi khi nhìn vào hình nền màn hình máy tính và giúp xoa dịu ánh mắt mệt mỏi sau nhiều giờ làm việc hay học tập.![image.png](http://localhost:5217/uploads/image.png)\r\n\r\nVới độ phân giải cao và chất lượng hình ảnh sắc nét, đây chắc chắn là lựa chọn lý tưởng cho hình nền máy tính, đặc biệt phù hợp với những người đam mê thiên nhiên hoặc muốn thưởng thức vẻ đẹp của cảnh quan, thực vật, và địa hình núi non. Hình nền 4K siêu đẹp, tha hồ lựa chọn. Click vào ảnh để tải về máy tính ngay!\r\n\r\n130+ hình nền máy tính 4k, full HD đa dạng\r\nBức tranh thiên nhiên về thác nước sinh động\r\nHình nền máy tính 4k thiên nhiên\r\nBầu trời bình minh trên khu rừng thông đầy tuyết\r\nHình nền máy tính 4k thiên nhiên siêu đẹp\r\nCon thuyền trôi giữa mặt nước tĩnh lặng\r\nHình nền máy tính 4k về chủ đề thiên nhiên \r\nKhung cảnh hùng vĩ với ngồi nhà giữa làn sương khói huyền ảo\r\nHình nền máy tính thiên nhiên siêu đẹp\r\nCảnh núi hùng vĩ trong bầu trời đầy nắng\r\nHình nền 4k thiên nhiên\r\nBầu trời đầy sương khói giữa làn nắng trog veo trên các ngọn đồi\r\n130+ hình nền máy tính 4k, full HD siêu đẹp\r\nNhững ngọn núi cao tận trời kết họp cùng bầu trời ảm đạm\r\nHình nền máy tính đẹp về thiên nhiên\r\nKhu rừng xanh mắt với các ngọn núi đầy hùng vĩ\r\ntổng hợp kho Hình nền máy tính 4k thiên nhiên\r\nKhu rừng ngập tràn sắc màu đỏ từ lá cây\r\nhình nền màn hình 4k thiên nhiên cực chill\r\nBầu trời huyền ảo muôn màu kết hợp cùng dòng sông xanh biếc\r\n130+ hình nền máy tính full HD đa dạng, siêu đẹp\r\nCánh đồng xanh với những ngôi nhà trước ngọn núi hùng vĩ\r\n130+ hình nền máy tính full HD về chủ đề thiên nhiên\r\nMặt biển tĩnh lặng trước khung cảnh thiên nhiên hùng vĩ\r\n130+ hình nền máy tính full HD đa dạng, siêu đẹp về chủ dề thiên nhiên\r\nBuổi chiều tà tại các những ngọn núi thiên nhiên đồ sộ\r\ntổng hợp hình nền về thiên nhiên\r\nVùng đất đầy ắp cây xanh với những ngọn núi trập trùng to lớn\r\nhình nền máy tính 4k, full HD đa dạng, siêu đẹp\r\nCảnh tượng thác nước chảy trên bức tượng người tại quần đảo\r\ntổng hợp hình nền cực đẹp về thiên nhiên\r\nMặt hồ yên tĩnh nhỏ bé trước cảnh tượng hùng vĩ\r\nhình nền thiên nhiên cho máy tính\r\nThiên nhiên hùng vĩ trước bầu trời rộng lớn\r\n130 hinh nen may tinh 4k 19\r\nMặt biển yên tĩnh trước ngọn đồi đầy tuyết\r\ntổng hợp Hình nền máy tính 4k thiên nhiên\r\nCon đường dẫn đến mặt biển tĩnh lặng giữa bầu trời rộng lớn\r\nHình nền máy tính 4k du lịch\r\nChủ đề về du lịch cũng là chủ đề ưa thích để nhiều người lựa chọn làm hình nền, với các cảnh quan của nhiều địa điểm nổi tiếng trên thế giới. Đây chắc chắn là chủ đề phù hợp với những người thích phiêu lưu, khám phá những vùng đất mới mà trong tương lai sẽ trải nghiệm thử. Sau đây là một sô hình ảnh bạn có thể tham khảo:\r\n\r\nHình nền máy tính 4k du lịch\r\nKhám phá kỳ quang Taj Mahal tráng lệ\r\nHình nền máy tính 4k du lịch cực đẹp\r\nNgất ngây với vẻ đẹp kỳ thú ở cao nguyên Kon Hà Nừng\r\nHình nền máy tính 4k du lịch siêu đẹp\r\nBán đảo Lofoten Na Uy của vùng cực Bắc?\r\nHình nền 4k du lịch dành cho máy tính\r\nMặt biển trong xanh tại một đảo nhỏ\r\ntổng hợp Hình nền máy tính 4k du lịch\r\nKhung cảnh tại Vịnh Hạ Long đầy hùng vĩ\r\ntổng hợp các hình nền máy tính 4k du lịch\r\nTháp đồng hồ Big Ben điểm đáng hàng đầu trên thế giới\r\nHình nền máy tính về du lịch\r\nBãi đá cổ Stonehenge tại London\r\nHình nền máy tính về chủ đề du lịch\r\nThị trấn tại một thành phố của Ý\r\nHình nền máy tính 4k du lịch cực kỳ đẹp mắt\r\nChiêm ngưỡng tháp đồng hồ tại Phú Quốc\r\nhình nền về chủ đề du lịch cực đẹp\r\nThành phố Melbourne điểm đến du lịch hàng đầu tại Úc\r\nHình nền máy tính 4k du lịch siêu nét\r\nSpiez, hồ Thun tại Thụy Sĩ\r\n130 Hình nền máy tính 4k du lịch\r\nPhố cổ Lucerne tại Thụy sĩ\r\n130 Hình nền về máy tính 4k du lịch\r\nCổng thành Brandenburg, biểu tượng của nước Đức\r\n130 Hình nền máy tính 4k du lịch siêu nét\r\nĐấu trường La Mã nổi tiếng bạn nên đến một lần\r\ntổng hợp các Hình nền máy tính 4k du lịch\r\nKhu phố cổ tại thành phố của Trung Quốc\r\ntổng hợp các bức hình 4k về chủ đề du lịch\r\nPhượng Hoàng cổ Trấn đầy cổ kín\r\nhình nền máy tính 4k full HD\r\nNhà hát Opera Sydney biểu tượng của nước Úc\r\nhình nền máy tính 4k phong cảnh\r\n![130-hinh-nen-may-tinh-4k-80.jpg](http://localhost:5217/uploads/130-hinh-nen-may-tinh-4k-80.jpg)\r\nCổng Torri, lối vảo thế giới thần linh tại Nhật Bản\r\nhình nền phong cảnh du lịch\r\nKotor, miền cổ tích lãng quên\r\nhình nền về du lịch chill\r\nThị trấn cổ tích tại Hà Lan\r\ntổng hợp Hình nền về du lịch cực kỳ đẹp mắt\r\nNhững ngôi nhà trắng mái xanh tại Hy Lạp\r\n','2024-12-07 21:56:35','2024-12-07 21:56:35',0,6,'/uploads/blogger/banner/130-hinh-nen-may-tinh-4k-80.jpg',36,'Tổng hợp các hình nền đẹp');
/*!40000 ALTER TABLE `tblblogger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblblogtype`
--

DROP TABLE IF EXISTS `tblblogtype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblblogtype` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblblogtype`
--

LOCK TABLES `tblblogtype` WRITE;
/*!40000 ALTER TABLE `tblblogtype` DISABLE KEYS */;
INSERT INTO `tblblogtype` VALUES (1,'Front-end'),(2,'Mobile apps'),(3,'Back-end'),(4,'Dev'),(5,'UX-UI'),(6,'Others');
/*!40000 ALTER TABLE `tblblogtype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblcode`
--

DROP TABLE IF EXISTS `tblcode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblcode` (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` text,
  `codeId` varchar(10) DEFAULT NULL,
  `discount` decimal(10,0) DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `startDate` datetime DEFAULT NULL,
  `endDate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblcode`
--

LOCK TABLES `tblcode` WRITE;
/*!40000 ALTER TABLE `tblcode` DISABLE KEYS */;
/*!40000 ALTER TABLE `tblcode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblcodesubmit`
--

DROP TABLE IF EXISTS `tblcodesubmit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblcodesubmit` (
  `id` int NOT NULL,
  `userId` int NOT NULL,
  `submittedCode` text,
  `isCorrect` tinyint(1) DEFAULT NULL,
  `createdAt` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`,`userId`),
  KEY `tblCodeSubmit_tblUser_FK` (`userId`),
  CONSTRAINT `tblCodeSubmit_tblQuestionCode_FK` FOREIGN KEY (`id`) REFERENCES `tblquestioncode` (`id`),
  CONSTRAINT `tblCodeSubmit_tblUser_FK` FOREIGN KEY (`userId`) REFERENCES `tbluser` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblcodesubmit`
--

LOCK TABLES `tblcodesubmit` WRITE;
/*!40000 ALTER TABLE `tblcodesubmit` DISABLE KEYS */;
INSERT INTO `tblcodesubmit` VALUES (54,32,'console.log(\'Hello world\');',1,'2024-12-29 12:27:15'),(54,36,'console.log(\'Hello world\');',0,'2025-03-20 19:52:32'),(57,36,'',1,'2025-01-01 13:14:27'),(58,32,'',1,'2025-05-06 15:22:08'),(58,36,'',1,'2025-05-13 15:08:09');
/*!40000 ALTER TABLE `tblcodesubmit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblcommentlesson`
--

DROP TABLE IF EXISTS `tblcommentlesson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblcommentlesson` (
  `userId` int DEFAULT NULL,
  `lessonId` int DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `content` text,
  `id` int NOT NULL AUTO_INCREMENT,
  `createAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `prohibited` tinyint(1) DEFAULT '0',
  `updateAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `isDelete` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `tblCommentLesson_tblUser_FK` (`userId`),
  KEY `tblCommentLesson_tblLectureDetails_FK` (`lessonId`),
  KEY `tblCommentLesson_tblCommentLesson_FK` (`parent_id`),
  CONSTRAINT `tblCommentLesson_tblCommentLesson_FK` FOREIGN KEY (`parent_id`) REFERENCES `tblcommentlesson` (`id`),
  CONSTRAINT `tblCommentLesson_tblLectureDetails_FK` FOREIGN KEY (`lessonId`) REFERENCES `tbllecturedetails` (`id`),
  CONSTRAINT `tblCommentLesson_tblUser_FK` FOREIGN KEY (`userId`) REFERENCES `tbluser` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=187 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblcommentlesson`
--

LOCK TABLES `tblcommentlesson` WRITE;
/*!40000 ALTER TABLE `tblcommentlesson` DISABLE KEYS */;
INSERT INTO `tblcommentlesson` VALUES (32,49,NULL,'xịn quá',158,'2025-01-03 19:42:05',0,'2025-01-03 19:42:05',0),(36,55,NULL,'haha',159,'2025-03-20 19:48:05',0,'2025-03-20 19:48:05',0),(36,55,NULL,'khá',160,'2025-03-20 19:48:24',0,'2025-03-20 19:48:24',1),(36,55,NULL,'alo',161,'2025-03-20 19:48:41',0,'2025-03-20 19:48:41',0),(36,33,NULL,'skibidi 😂',162,'2025-04-28 09:24:57',0,'2025-04-28 22:49:12',0),(32,35,NULL,'dạ',163,'2025-05-05 19:23:53',0,'2025-05-05 19:23:53',1),(32,35,NULL,'â',164,'2025-05-06 12:22:45',0,'2025-05-06 12:22:45',0),(36,35,NULL,'sấ',165,'2025-05-06 12:22:57',0,'2025-05-06 12:22:57',0),(36,58,NULL,'hí',166,'2025-05-06 12:24:32',0,'2025-05-06 12:24:32',0),(32,58,NULL,'alo',167,'2025-05-06 12:26:55',0,'2025-05-06 12:26:55',0),(32,35,NULL,'ủa',168,'2025-05-06 12:40:19',0,'2025-05-06 12:40:19',0),(32,35,NULL,'ll',169,'2025-05-06 12:59:48',0,'2025-05-06 12:59:48',0),(32,35,NULL,'j',170,'2025-05-06 13:11:01',0,'2025-05-06 13:11:01',0),(32,58,NULL,'aa',171,'2025-05-06 17:03:47',0,'2025-05-06 17:03:47',0),(32,58,NULL,'a',172,'2025-05-06 17:34:47',0,'2025-05-06 17:34:47',0),(32,58,NULL,'a',173,'2025-05-06 17:34:51',0,'2025-05-06 17:34:51',0),(36,58,NULL,'aaa',174,'2025-05-06 17:41:31',0,'2025-05-06 17:41:31',0),(32,58,NULL,'s',175,'2025-05-06 17:44:07',NULL,'2025-05-06 17:44:07',0),(32,33,NULL,'hello',176,'2025-05-08 15:59:16',0,'2025-05-08 15:59:16',0),(32,33,162,'ủa',177,'2025-05-08 16:00:29',0,'2025-05-08 16:00:29',0),(36,49,NULL,'aa',178,'2025-05-08 16:53:12',0,'2025-05-08 16:53:12',0),(36,34,NULL,'ủa',179,'2025-05-08 23:18:09',0,'2025-05-08 23:18:09',0),(36,34,NULL,'ủa\n',180,'2025-05-08 23:19:01',0,'2025-05-08 23:19:01',0),(36,34,NULL,'ủa',181,'2025-05-08 23:19:21',0,'2025-05-08 23:19:21',0),(36,34,NULL,'yêu anh không',182,'2025-05-08 23:19:29',0,'2025-05-08 23:20:06',0),(36,34,182,'ủa anh',183,'2025-05-08 23:20:56',0,'2025-05-08 23:20:56',0),(32,34,NULL,'aduf',184,'2025-05-11 17:02:11',0,'2025-05-11 17:02:11',0),(36,60,NULL,'fghj',185,'2025-05-13 14:22:51',0,'2025-05-13 14:22:51',0),(36,49,158,'dạ cảm ơn',186,'2025-05-13 15:02:02',0,'2025-05-13 15:02:02',0);
/*!40000 ALTER TABLE `tblcommentlesson` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblcommentreport`
--

DROP TABLE IF EXISTS `tblcommentreport`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblcommentreport` (
  `id` int NOT NULL AUTO_INCREMENT,
  `userIdReport` int DEFAULT NULL,
  `commentId` int DEFAULT NULL,
  `createAt` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tblCommentReport_tblUser_FK` (`userIdReport`),
  KEY `tblCommentReport_tblCommentLesson_FK` (`commentId`),
  CONSTRAINT `tblCommentReport_tblCommentLesson_FK` FOREIGN KEY (`commentId`) REFERENCES `tblcommentlesson` (`id`),
  CONSTRAINT `tblCommentReport_tblUser_FK` FOREIGN KEY (`userIdReport`) REFERENCES `tbluser` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblcommentreport`
--

LOCK TABLES `tblcommentreport` WRITE;
/*!40000 ALTER TABLE `tblcommentreport` DISABLE KEYS */;
/*!40000 ALTER TABLE `tblcommentreport` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblcourse`
--

DROP TABLE IF EXISTS `tblcourse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblcourse` (
  `id` int NOT NULL AUTO_INCREMENT,
  `levelId` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `banner` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `isActive` tinyint(1) DEFAULT NULL,
  `duration` bigint DEFAULT '0',
  `introduce` text,
  PRIMARY KEY (`id`),
  KEY `levelId` (`levelId`),
  CONSTRAINT `tblCourse_ibfk_1` FOREIGN KEY (`levelId`) REFERENCES `level` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblcourse`
--

LOCK TABLES `tblcourse` WRITE;
/*!40000 ALTER TABLE `tblcourse` DISABLE KEYS */;
INSERT INTO `tblcourse` VALUES (3,1,'Lập Trình JavaScript Cơ Bản','2024-12-12 00:00:00','2025-01-02 17:29:24','/images/courses/JS.png',1,99365,'<p>Hiểu sâu hơn về cách Javascript hoạt động, tìm hiểu về IIFE, closure, reference types, this keyword, bind, call, apply, prototype, ...</p>'),(36,1,'Kiến Thức Nhập Môn IT','2025-05-09 10:14:58','2025-05-09 10:14:58','/images/courses/7.png',1,0,'<p><span style=\"color: rgba(0, 0, 0, 0.8);\">Để có cái nhìn tổng quan về ngành IT - Lập trình web các bạn nên xem các videos tại khóa này trước nhé.</span></p>'),(37,1,'Lập trình C++ cơ bản, nâng cao','2025-05-09 10:16:34','2025-05-09 10:16:34','/images/courses/c++.png',1,0,'<p><span style=\"color: rgba(0, 0, 0, 0.8);\">Khóa học lập trình C++ từ cơ bản tới nâng cao dành cho người mới bắt đầu. Mục tiêu của khóa học này nhằm giúp các bạn nắm được các khái niệm căn cơ của lập trình, giúp các bạn có nền tảng vững chắc để chinh phục con đường trở thành một lập trình viên.</span></p>'),(38,1,'HTML CSS từ Zero đến Hero','2025-05-09 10:17:52','2025-05-09 10:17:52','/images/courses/2 (1).png',1,0,'<p><span style=\"color: rgba(0, 0, 0, 0.8);\">Trong khóa này chúng ta sẽ cùng nhau xây dựng giao diện 2 trang web là The Band &amp; Shopee.</span></p>'),(39,1,'Responsive Với Grid System','2025-05-09 10:18:50','2025-05-09 10:18:50','/images/courses/3.png',1,0,'<p><span style=\"color: rgba(0, 0, 0, 0.8);\">Trong khóa này chúng ta sẽ học về cách xây dựng giao diện web responsive với Grid System, tương tự Bootstrap 4.</span></p>'),(40,2,'Lập Trình JavaScript Nâng Cao','2025-05-09 10:20:48','2025-05-09 10:20:48','/images/courses/12.png',1,0,'<p><span style=\"color: rgba(0, 0, 0, 0.8);\">Hiểu sâu hơn về cách Javascript hoạt động, tìm hiểu về IIFE, closure, reference types, this keyword, bind, call, apply, prototype, ...</span></p>'),(41,2,'Làm việc với Terminal & Ubuntu','2025-05-09 10:22:53','2025-05-09 10:22:53','/images/courses/624faac11d109.png',1,0,'<p><span style=\"color: rgba(0, 0, 0, 0.8);\">Sở hữu một Terminal hiện đại, mạnh mẽ trong tùy biến và học cách làm việc với Ubuntu là một bước quan trọng trên con đường trở thành một Web Developer.</span></p>'),(42,1,'Xây Dựng Website với ReactJS','2025-05-09 10:24:25','2025-05-09 12:12:04','/images/courses/13.png',1,0,'<p><span style=\"color: rgba(0, 0, 0, 0.8);\">Khóa học ReactJS từ cơ bản tới nâng cao, kết quả của khóa học này là bạn có thể làm hầu hết các dự án thường gặp với ReactJS. Cuối khóa học này bạn sẽ sở hữu một dự án giống Tiktok.com, bạn có thể tự tin đi xin việc khi nắm chắc các kiến thức được chia sẻ trong khóa học này.</span></p>'),(43,2,'Node & ExpressJS','2025-05-09 10:25:50','2025-05-09 10:25:50','/images/courses/6.png',1,0,'<p><span style=\"color: rgba(0, 0, 0, 0.8);\">Học Back-end với Node &amp; ExpressJS framework, hiểu các khái niệm khi làm Back-end và xây dựng RESTful API cho trang web.</span></p>'),(44,2,'Lập trình Website với Next.JS','2025-05-09 10:27:36','2025-05-09 10:27:36','/images/courses/nextjs.jpeg',1,0,'<p><span style=\"background-color: rgb(255, 255, 255); color: rgb(27, 27, 27);\">Next.js là một framework phổ biến trong việc phát triển ứng dụng web dựa trên React và được phát triển bởi Vercel.&nbsp;</span></p>');
/*!40000 ALTER TABLE `tblcourse` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblcoursedetail`
--

DROP TABLE IF EXISTS `tblcoursedetail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblcoursedetail` (
  `id` int NOT NULL AUTO_INCREMENT,
  `isFree` tinyint(1) DEFAULT NULL,
  `price` decimal(10,0) DEFAULT NULL,
  `description` text,
  `resultsAfterStudying` text,
  `slogan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `courseSuggestions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `priceOld` decimal(10,0) DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `tblCourseDetail_tblCourse_FK` FOREIGN KEY (`id`) REFERENCES `tblcourse` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblcoursedetail`
--

LOCK TABLES `tblcoursedetail` WRITE;
/*!40000 ALTER TABLE `tblcoursedetail` DISABLE KEYS */;
INSERT INTO `tblcoursedetail` VALUES (3,1,NULL,'<p>Học Javascript cơ bản phù hợp cho người chưa từng học lập trình. Với hơn 100 bài học và có bài tập thực hành sau mỗi bài học.</p>',NULL,'Học mọi lúc, mọi nơi nhá',NULL,'2025-01-02 17:29:24','<blockquote>Nếu bạn chưa học HTML, CSS, vui lòng xem kỹ lộ trình học tại đây:&nbsp;<a href=\"https://fullstack.edu.vn/learning-paths\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"color: rgb(240, 102, 102);\"><strong>https://fullstack.edu.vn/learning-paths</strong></a></blockquote><p>Tham gia các cộng đồng để cùng học hỏi, chia sẻ và \"thám thính\" xem F8 sắp có gì mới nhé!</p><ul><li>Fanpage:&nbsp;<a href=\"https://www.facebook.com/f8vnofficial\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"color: rgb(255, 153, 0);\">https://www.facebook.com/f8vnofficial</a></li><li>Group:&nbsp;<a href=\"https://www.facebook.com/groups/649972919142215\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"color: rgb(255, 153, 0);\">https://www.facebook.com/groups/649972919142215</a></li><li>Youtube:&nbsp;<a href=\"https://www.youtube.com/F8VNOfficial\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"color: rgb(255, 153, 0);\">https://www.youtube.com/F8VNOfficial</a></li><li>Sơn Đặng:&nbsp;<a href=\"https://www.facebook.com/sondnf8\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"color: rgb(255, 153, 0);\">https://www.facebook.com/sondnf8</a></li></ul><p><br></p>',NULL),(36,1,NULL,'<ul><li>Các kiến thức cơ bản, nền móng của ngành IT</li><li>Các mô hình, kiến trúc cơ bản khi triển khai ứng dụng</li><li>Các khái niệm, thuật ngữ cốt lõi khi triển khai ứng dụng</li><li>Hiểu hơn về cách internet và máy vi tính hoạt động</li></ul><p><br></p>',NULL,'Học mọi lúc, mọi nơi','2025-05-09 10:14:58','2025-05-09 10:14:58',NULL,NULL),(37,1,NULL,NULL,NULL,'Học mọi lúc, mọi nơi','2025-05-09 10:16:34','2025-05-09 10:16:34',NULL,NULL),(38,1,NULL,'<p><br></p>',NULL,'Học mọi lúc, mọi nơi','2025-05-09 10:17:52','2025-05-09 10:17:52',NULL,NULL),(39,1,NULL,NULL,NULL,'Học mọi lúc, mọi nơi','2025-05-09 10:18:50','2025-05-09 10:18:50',NULL,NULL),(40,0,999000,'<ul><li>Được học kiến thức miễn phí với nội dung chất lượng hơn mất phí</li><li>Các kiến thức nâng cao của Javascript giúp code trở nên tối ưu hơn</li><li>Hiểu được cách tư duy nâng cao của các lập trình viên có kinh nghiệm</li><li>Hiểu được các khái niệm khó như từ khóa this, phương thức bind, call, apply &amp; xử lý bất đồng bộ</li><li>Có nền tảng Javascript vững chắc để làm việc với mọi thư viện, framework viết bởi Javascript</li><li>Nâng cao cơ hội thành công khi phỏng vấn xin việc nhờ kiến thức chuyên môn vững chắc</li></ul><p><br></p>',NULL,'Học mọi lúc, mọi nơi','2025-05-09 10:20:48','2025-05-09 10:20:48',NULL,1290000),(41,0,499000,'<ul><li>Biết cách cài đặt và tùy biến Windows Terminal</li><li>Biết sử dụng Windows Subsystem for Linux</li><li>Thành thạo sử dụng các lệnh Linux/Ubuntu</li><li>Biết cài đặt Node và tạo dự án ReactJS/ExpressJS</li><li>Biết cài đặt PHP 7.4 và MariaDB trên Ubuntu 20.04</li><li>Hiểu về Ubuntu và biết tự cài đặt các phần mềm khác</li></ul><p><br></p><p><br></p>',NULL,'Học mọi lúc, mọi nơi','2025-05-09 10:22:53','2025-05-09 10:22:53',NULL,999000),(42,0,1499000,'<ul><li>Hiểu về khái niệm SPA/MPA</li><li>Hiểu về khái niệm hooks</li><li>Hiểu cách ReactJS hoạt động</li><li>Hiểu về function/class component</li><li>Biết cách tối ưu hiệu năng ứng dụng</li><li>Thành thạo làm việc với RESTful API</li><li>Hiểu rõ ràng Redux workflow</li><li>Thành thạo sử dụng Redux vào dự án</li><li>Biết sử dụng redux-thunk middleware</li><li>Xây dựng sản phẩm thực tế (clone Tiktok)</li><li>Triển khai dự án React ra Internet</li><li>Đủ hành trang tự tin apply đi xin việc</li><li>Biết cách Deploy lên Github/Gitlab page</li><li>Nhận chứng chỉ khóa học do F8 cấp</li></ul>',NULL,'Học mọi lúc, mọi nơi','2025-05-09 10:24:25','2025-05-09 12:12:04',NULL,2199000),(43,0,1499000,NULL,NULL,'Học mọi lúc, mọi nơi','2025-05-09 10:25:50','2025-05-09 10:25:50',NULL,999000),(44,0,2199000,NULL,NULL,'Học mọi lúc, mọi nơi','2025-05-09 10:27:36','2025-05-09 10:27:36',NULL,1499000);
/*!40000 ALTER TABLE `tblcoursedetail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblEnrollments`
--

DROP TABLE IF EXISTS `tblEnrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblEnrollments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `course_id` int NOT NULL,
  `enrolled_at` datetime DEFAULT NULL,
  `order_id` int DEFAULT NULL,
  `isFree` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `tblUserCourses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbluser` (`Id`),
  CONSTRAINT `tblUserCourses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `tblcourse` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblEnrollments`
--

LOCK TABLES `tblEnrollments` WRITE;
/*!40000 ALTER TABLE `tblEnrollments` DISABLE KEYS */;
INSERT INTO `tblEnrollments` VALUES (43,36,3,'2025-05-18 15:44:01',NULL,1);
/*!40000 ALTER TABLE `tblEnrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblFriendRequests`
--

DROP TABLE IF EXISTS `tblFriendRequests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblFriendRequests` (
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `status` enum('pending','accepted','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sender_id`,`receiver_id`),
  KEY `tblFriendRequests_tbluser_FK_1` (`receiver_id`),
  CONSTRAINT `tblFriendRequests_tbluser_FK` FOREIGN KEY (`sender_id`) REFERENCES `tbluser` (`Id`),
  CONSTRAINT `tblFriendRequests_tbluser_FK_1` FOREIGN KEY (`receiver_id`) REFERENCES `tbluser` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblFriendRequests`
--

LOCK TABLES `tblFriendRequests` WRITE;
/*!40000 ALTER TABLE `tblFriendRequests` DISABLE KEYS */;
INSERT INTO `tblFriendRequests` VALUES (32,36,'pending','2025-05-08 16:04:08'),(32,41,'pending','2025-05-02 22:07:05'),(54,36,'pending','2025-05-03 13:31:23'),(55,36,'pending','2025-05-03 13:31:23'),(56,32,'pending','2025-05-03 13:31:23'),(57,32,'pending','2025-05-03 13:31:23'),(59,32,'pending','2025-05-03 13:31:23'),(68,36,'pending','2025-05-03 13:35:44'),(71,32,'pending','2025-05-02 22:07:05'),(87,36,'pending','2025-05-03 13:35:44'),(88,32,'accepted','2025-05-03 13:36:00'),(89,32,NULL,'2025-05-03 21:56:49');
/*!40000 ALTER TABLE `tblFriendRequests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblFriends`
--

DROP TABLE IF EXISTS `tblFriends`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblFriends` (
  `user_id` int NOT NULL,
  `friend_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`friend_id`),
  KEY `tblFriends_tbluser_FK_1` (`friend_id`),
  CONSTRAINT `tblFriends_tbluser_FK` FOREIGN KEY (`user_id`) REFERENCES `tbluser` (`Id`),
  CONSTRAINT `tblFriends_tbluser_FK_1` FOREIGN KEY (`friend_id`) REFERENCES `tbluser` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblFriends`
--

LOCK TABLES `tblFriends` WRITE;
/*!40000 ALTER TABLE `tblFriends` DISABLE KEYS */;
INSERT INTO `tblFriends` VALUES (32,88,'2025-05-03 13:40:36'),(59,36,'2025-05-03 22:02:18'),(70,36,'2025-05-03 22:02:18'),(86,32,'2025-05-03 22:01:42'),(86,36,'2025-05-03 22:02:18'),(87,32,'2025-05-03 22:01:42'),(87,36,'2025-05-03 22:02:18'),(88,36,'2025-05-03 13:40:36'),(89,32,'2025-05-03 22:01:42'),(89,36,'2025-05-03 22:02:18');
/*!40000 ALTER TABLE `tblFriends` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbllanguagecode`
--

DROP TABLE IF EXISTS `tbllanguagecode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbllanguagecode` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `dateCreate` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbllanguagecode`
--

LOCK TABLES `tbllanguagecode` WRITE;
/*!40000 ALTER TABLE `tbllanguagecode` DISABLE KEYS */;
INSERT INTO `tbllanguagecode` VALUES (1,'JavaScript','2024-12-16 10:28:50'),(2,'C++','2024-12-16 10:28:50'),(3,'HTML & CSS','2024-12-16 10:28:50');
/*!40000 ALTER TABLE `tbllanguagecode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblLearningpathCourse`
--

DROP TABLE IF EXISTS `tblLearningpathCourse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblLearningpathCourse` (
  `learning_path_step_id` int NOT NULL,
  `CourseId` int NOT NULL,
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `order_index` int DEFAULT NULL,
  PRIMARY KEY (`learning_path_step_id`,`CourseId`),
  KEY `tblLearningpathCourse_tblcourse_FK` (`CourseId`),
  CONSTRAINT `tblLearningpathCourse_tblcourse_FK` FOREIGN KEY (`CourseId`) REFERENCES `tblcourse` (`id`),
  CONSTRAINT `tblLearningpathCourse_tblLearningPathStep_FK` FOREIGN KEY (`learning_path_step_id`) REFERENCES `tblLearningPathStep` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblLearningpathCourse`
--

LOCK TABLES `tblLearningpathCourse` WRITE;
/*!40000 ALTER TABLE `tblLearningpathCourse` DISABLE KEYS */;
/*!40000 ALTER TABLE `tblLearningpathCourse` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblLearningPaths`
--

DROP TABLE IF EXISTS `tblLearningPaths`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblLearningPaths` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Level` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `estimatedTime` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `Status` tinyint DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblLearningPaths`
--

LOCK TABLES `tblLearningPaths` WRITE;
/*!40000 ALTER TABLE `tblLearningPaths` DISABLE KEYS */;
INSERT INTO `tblLearningPaths` VALUES (1,'Lộ trình Frontend','Hầu hết các websites hoặc ứng dụng di động đều có 2 phần là Front-end và Back-end. Front-end là phần giao diện người dùng nhìn thấy và có thể tương tác, đó chính là các ứng dụng mobile hay những website bạn đã từng sử dụng. Vì vậy, nhiệm vụ của lập trình viên Front-end là xây dựng các giao diện đẹp, dễ sử dụng và tối ưu trải nghiệm người dùng.\n\nTại Việt Nam, lương trung bình cho lập trình viên front-end vào khoảng 16.000.000đ / tháng.\n\nDưới đây là các khóa học F8 đã tạo ra dành cho bất cứ ai theo đuổi sự nghiệp trở thành một lập trình viên Front-end.\n\nCác khóa học có thể chưa đầy đủ, F8 vẫn đang nỗ lực hoàn thiện trong thời gian sớm nhất.','/images/path/fontend_path.png','beginner',30,'2025-04-22 10:00:04','2025-04-23 07:42:52',1),(2,'Lộ trình Backend','Học C#, .NET, Database để thành backend developer chuyên nghiệp','/images/path/backend_path.png','intermediate',40,'2025-04-22 10:00:04','2025-04-23 07:42:22',1),(3,'Lộ trình Fullstack','Kết hợp cả frontend và backend, dùng React + .NET Core','/images/path/fullstack_path.png','advanced',60,'2025-04-22 10:00:04','2025-04-23 07:42:22',1),(4,'Lộ trình AI','Học Python, Machine Learning, và các thư viện như TensorFlow, PyTorch','/images/path/ai_path.png','intermediate',45,'2025-04-22 10:00:04','2025-04-23 07:42:22',1);
/*!40000 ALTER TABLE `tblLearningPaths` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblLearningPathStep`
--

DROP TABLE IF EXISTS `tblLearningPathStep`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblLearningPathStep` (
  `learning_path_id` int NOT NULL,
  `order_index` int DEFAULT NULL,
  `step_id` int NOT NULL,
  `id` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  KEY `tblLearningPathStep_tblLearningPaths_FK` (`learning_path_id`),
  KEY `tblLearningPathStep_tblSteps_FK` (`step_id`),
  CONSTRAINT `tblLearningPathStep_tblLearningPaths_FK` FOREIGN KEY (`learning_path_id`) REFERENCES `tblLearningPaths` (`id`),
  CONSTRAINT `tblLearningPathStep_tblSteps_FK` FOREIGN KEY (`step_id`) REFERENCES `tblSteps` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblLearningPathStep`
--

LOCK TABLES `tblLearningPathStep` WRITE;
/*!40000 ALTER TABLE `tblLearningPathStep` DISABLE KEYS */;
INSERT INTO `tblLearningPathStep` VALUES (1,1,13,1),(1,2,1,2),(1,3,3,3),(1,4,4,4),(1,5,5,5);
/*!40000 ALTER TABLE `tblLearningPathStep` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbllecturedetails`
--

DROP TABLE IF EXISTS `tbllecturedetails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbllecturedetails` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lessonGroup` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `level` int DEFAULT NULL,
  `lessonTypeId` int NOT NULL,
  `courseId` int DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lessonGroup` (`lessonGroup`),
  KEY `tblLectureDetails_LessonType_FK` (`lessonTypeId`),
  KEY `tblLectureDetails_tblCourse_FK` (`courseId`),
  CONSTRAINT `tblLectureDetails_ibfk_1` FOREIGN KEY (`lessonGroup`) REFERENCES `tbllessongroup` (`id`),
  CONSTRAINT `tblLectureDetails_LessonType_FK` FOREIGN KEY (`lessonTypeId`) REFERENCES `lessontype` (`id`),
  CONSTRAINT `tblLectureDetails_tblCourse_FK` FOREIGN KEY (`courseId`) REFERENCES `tblcourse` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbllecturedetails`
--

LOCK TABLES `tbllecturedetails` WRITE;
/*!40000 ALTER TABLE `tbllecturedetails` DISABLE KEYS */;
INSERT INTO `tbllecturedetails` VALUES (32,26,'2024-11-08 23:01:27','2024-11-08 23:01:27',1,1,3,'Xử lý báo lỗi cơ bản'),(33,1,'2024-11-10 04:03:37','2024-11-10 04:03:37',2,1,3,'Lời khuyên trước khóa học'),(34,1,'2024-11-10 19:18:20','2024-11-10 19:18:20',3,1,3,'Javascript có thể làm được gì?'),(35,1,'2024-11-10 19:19:36','2024-11-10 19:19:36',4,1,3,'Cài đặt môi trường'),(48,1,'2024-11-13 07:20:58','2024-11-13 07:20:58',7,4,3,'Tham gia cộng đồng F8 trên Discord'),(49,1,'2024-11-15 10:46:37','2024-11-15 10:46:37',8,3,3,'Ôn tập toán tử so sánh'),(50,14,'2024-11-15 13:04:33','2024-11-15 13:04:33',9,3,3,'Ôn lại kiến thức về hàm'),(51,2,'2024-12-13 12:46:02','2024-12-13 12:46:02',10,1,3,'Sử dụng JavaScript với HTML'),(52,2,'2024-12-13 13:22:41','2024-12-13 13:22:41',11,4,3,'Làm quen với màn thử thách'),(53,2,'2024-12-13 13:23:26','2024-12-13 13:23:26',12,4,3,'Lưu ý khi học lập trình tại F8'),(54,2,'2024-12-22 15:17:11','2024-12-22 15:17:11',1,2,3,'Bắt đầu với một thử thách nhỏ'),(55,2,'2024-12-29 08:21:41','2024-12-29 08:21:41',13,1,3,'Khái niệm biến và cách sử dụng'),(57,14,'2025-01-01 11:40:59','2025-01-01 11:40:59',14,2,3,'Thực hành tạo hàm sum #1'),(58,1,'2025-01-01 14:04:00','2025-01-01 14:04:00',15,2,3,'Thực hành sử dụng Spread'),(59,31,'2025-05-09 12:22:13','2025-05-09 12:22:13',1,1,42,'ReactJS là gì? Tại sao nên học ReactJS?'),(60,31,'2025-05-09 12:23:05','2025-05-09 12:23:05',2,1,42,'SPA/MPA là gì?'),(61,31,'2025-05-09 12:24:45','2025-05-09 12:24:45',3,3,42,'Ưu điểm của SPA');
/*!40000 ALTER TABLE `tbllecturedetails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbllesson`
--

DROP TABLE IF EXISTS `tbllesson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbllesson` (
  `id` int NOT NULL AUTO_INCREMENT,
  `videoLink` varchar(355) DEFAULT NULL,
  `description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `duration` bigint DEFAULT '0',
  PRIMARY KEY (`id`),
  CONSTRAINT `tblLesson_ibfk_1` FOREIGN KEY (`id`) REFERENCES `tbllecturedetails` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbllesson`
--

LOCK TABLES `tbllesson` WRITE;
/*!40000 ALTER TABLE `tbllesson` DISABLE KEYS */;
INSERT INTO `tbllesson` VALUES (32,'https://www.youtube.com/watch?v=ZdvRm1bfGAk&t=785s','<p>Xử lý báo lỗi cơ bản</p><p><br></p>','2024-11-08 23:01:27','2024-11-08 23:01:27',2478),(33,'https://www.youtube.com/watch?v=-jV06pqjUUc&t=1s','<p><span style=\"background-color: rgba(255, 255, 255, 0.1);\">Chào các bạn, video thứ 2 này là chia sẻ của mình tới các bạn về những lưu ý và lời khuyên trước khóa học | Lộ trình khóa học JavaScript cơ bản tại F8</span></p><p><a href=\"https://www.youtube.com/hashtag/hoclaptrinh\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"background-color: rgba(255, 255, 255, 0.1); color: inherit;\">#hoclaptrinh</a><span style=\"background-color: rgba(255, 255, 255, 0.1); color: rgb(255, 255, 255);\">  </span><a href=\"https://www.youtube.com/hashtag/hoclaptrinhmienphi\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"background-color: rgba(255, 255, 255, 0.1); color: inherit;\">#hoclaptrinhmienphi</a><span style=\"background-color: rgba(255, 255, 255, 0.1); color: rgb(255, 255, 255);\">  </span><a href=\"https://www.youtube.com/hashtag/javascript\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"background-color: rgba(255, 255, 255, 0.1); color: inherit;\">#javascript</a><span style=\"background-color: rgba(255, 255, 255, 0.1); color: rgb(255, 255, 255);\">  </span><a href=\"https://www.youtube.com/hashtag/frontend\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"background-color: rgba(255, 255, 255, 0.1); color: inherit;\">#frontend</a><span style=\"background-color: rgba(255, 255, 255, 0.1); color: rgb(255, 255, 255);\">  </span><a href=\"https://www.youtube.com/hashtag/backend\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"background-color: rgba(255, 255, 255, 0.1); color: inherit;\">#backend</a><span style=\"background-color: rgba(255, 255, 255, 0.1); color: rgb(255, 255, 255);\">  </span><a href=\"https://www.youtube.com/hashtag/devops\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"background-color: rgba(255, 255, 255, 0.1); color: inherit;\">#devops</a><span style=\"background-color: rgba(255, 255, 255, 0.1); color: rgb(255, 255, 255);\">  </span><a href=\"https://www.youtube.com/hashtag/f8\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"background-color: rgba(255, 255, 255, 0.1); color: inherit;\">#f8</a></p>','2024-11-10 04:03:38','2024-11-10 04:03:38',260),(34,'https://www.youtube.com/watch?v=0SJE9dYdpps&list=PL_-VfJajZj0VgpFpEVFzS5Z-lkXtBe-x5','<p><strong>Javascript có thể làm được gì? Giới thiệu về trang F8 | Học lập trình Javascript cơ bản</strong></p>','2024-11-10 19:18:21','2024-11-10 19:18:21',473),(35,'https://www.youtube.com/watch?v=efI98nT8Ffo&list=PL_-VfJajZj0VgpFpEVFzS5Z-lkXtBe-x5','<p><span style=\"background-color: rgba(255, 255, 255, 0.1);\">Video này mình sẽ hướng dẫn các bạn cài đặt môi trường, công cụ phù hợp để học JavaScript</span></p><p><a href=\"https://www.youtube.com/hashtag/hoclaptrinh\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"background-color: rgba(255, 255, 255, 0.1); color: inherit;\">#hoclaptrinh</a><span style=\"background-color: rgba(255, 255, 255, 0.1); color: rgb(255, 255, 255);\">  </span><a href=\"https://www.youtube.com/hashtag/hoclaptrinhmienphi\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"background-color: rgba(255, 255, 255, 0.1); color: inherit;\">#hoclaptrinhmienphi</a><span style=\"background-color: rgba(255, 255, 255, 0.1); color: rgb(255, 255, 255);\">  </span><a href=\"https://www.youtube.com/hashtag/javascript\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"background-color: rgba(255, 255, 255, 0.1); color: inherit;\">#javascript</a><span style=\"background-color: rgba(255, 255, 255, 0.1); color: rgb(255, 255, 255);\">  </span><a href=\"https://www.youtube.com/hashtag/frontend\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"background-color: rgba(255, 255, 255, 0.1); color: inherit;\">#frontend</a><span style=\"background-color: rgba(255, 255, 255, 0.1); color: rgb(255, 255, 255);\">  </span><a href=\"https://www.youtube.com/hashtag/backend\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"background-color: rgba(255, 255, 255, 0.1); color: inherit;\">#backend</a><span style=\"background-color: rgba(255, 255, 255, 0.1); color: rgb(255, 255, 255);\">  </span><a href=\"https://www.youtube.com/hashtag/devops\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"background-color: rgba(255, 255, 255, 0.1); color: inherit;\">#devops</a><span style=\"background-color: rgba(255, 255, 255, 0.1); color: rgb(255, 255, 255);\">  </span><a href=\"https://www.youtube.com/hashtag/f8\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"background-color: rgba(255, 255, 255, 0.1); color: inherit;\">#f8</a></p>','2024-11-10 19:19:37','2024-11-10 19:19:37',128),(51,'https://www.youtube.com/watch?v=W0vEUmyvthQ&t=1s','<h2>Cách Internal (sử dụng nội bộ)</h2><p>Đặt trực tiếp cặp thẻ&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">script</span>&nbsp;vào mã HTML và viết&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">javascript</span>&nbsp;giữa cặp thẻ này.</p><pre class=\"ql-syntax\" spellcheck=\"false\">&lt;body&gt;\n    ...\n    &lt;script&gt;\n        alert(\'Xin chào các bạn!\')\n    &lt;/script&gt;\n    ...\n&lt;/body&gt;\n</pre><p><br></p><h2>Cách External (sử dụng file .js bên ngoài)</h2><p>Các bạn sẽ thường thấy cách này được sử dụng vì mã&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">javascript</span>&nbsp;được viết riêng biệt ra một file&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">.js</span>&nbsp;ở bên ngoài. Mã của chúng ta sẽ gọn gàng, dễ nhìn, dễ chỉnh sửa hơn vì không bị viết lẫn lộn vào HTML như cách&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">Internal</span>.</p><pre class=\"ql-syntax\" spellcheck=\"false\">&lt;body&gt;\n    ...\n    &lt;script src=\"đường_dẫn_tới_file.js\"&gt;&lt;/script&gt;\n&lt;/body&gt;\n</pre><p><br></p><p>Trong trường hợp sử dụng file&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">.js</span>&nbsp;thì nội dung của file không được chứa thẻ&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">&lt;script&gt;</span>. Sau đây là ví dụ nội dung file&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">.js</span>.</p><h3>Đúng</h3><pre class=\"ql-syntax\" spellcheck=\"false\">// Nội dung file .js\nalert(\'Xin chào các bạn!\')\n</pre><p><br></p><h3>Sai</h3><pre class=\"ql-syntax\" spellcheck=\"false\">// Nội dung file .js\n&lt;script&gt;\n    alert(\'Xin chào các bạn!\')\n&lt;/script&gt;\n</pre><p><br></p><blockquote>Trong thực tế cách&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">Internal</span>&nbsp;cũng được sử dụng khá phổ biến trong các trường hợp mã&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">javascript</span>&nbsp;đó chỉ sử dụng tại duy nhất một màn hình và số lượng các dòng code không nhiều. Tuy nhiên cách này các bạn nên tránh việc lạm dụng vì sẽ dễ gây rác source code và lặp lại code không mong muốn.</blockquote><p><br></p>','2024-12-13 12:46:02','2024-12-13 12:46:02',273),(55,'https://www.youtube.com/watch?v=CLbx37dqYEI&t','<h2>Biến là gì?</h2><p>Trong quá trình xây dựng website hoặc các ứng dụng với Javascript chúng ta sẽ cần phải làm việc với các dạng thông tin dữ liệu khác nhau. Ví dụ:</p><ol><li>Phần mềm kế toán - Chúng ta sẽ làm việc với những con số</li><li>Website bán hàng - Làm việc với dữ liệu thông tin sản phẩm, đơn hàng và giỏ hàng</li><li>Ứng dụng Chat - Dữ liệu là những đoạn chat, tin nhắn, thông tin người chat</li></ol><p>Biến được sử dụng để lưu trữ các thông tin trên trong quá trình ứng dụng Javascript hoạt động.</p><h2>Khai báo biến</h2><p>Để khai báo biến ta sẽ bắt đầu bằng từ khóa&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">var</span>&nbsp;(var là viết tắt của từ&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">variable</span>&nbsp;- nghĩa là biến). Khai báo biến có cú pháp như sau:</p><pre class=\"ql-syntax\" spellcheck=\"false\">var [dấu cách] [tên biến];\n</pre><p><br></p><p>Theo cú pháp trên, mình sẽ định nghĩa một biến có tên là&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">fullName</span>&nbsp;với dự định để lưu tên đầy đủ của mình vào đó.</p><pre class=\"ql-syntax\" spellcheck=\"false\">var fullName;\n</pre><p><br></p><p>Tiếp theo, ta có thể lưu thông tin vào biến&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">fullName</span>&nbsp;này:</p><pre class=\"ql-syntax\" spellcheck=\"false\">var fullName; // khai báo biến\n\nfullName = \'Sơn Đặng\'; // gán giá trị\n</pre><p><br></p><p>Các bạn chú ý có dấu nháy đơn&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">\'\'</span>&nbsp;bao ngoài chữ&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">Sơn Đặng</span>. Đó là cách để thể hiện dữ liệu dạng&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">chuỗi</span>&nbsp;(văn bản) trong Javascript.</p><blockquote>Khi đoạn mã trên được chạy (thực thi) Javascript sẽ tạo biến với tên&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">fullName</span>&nbsp;và gán giá trị&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">\'Sơn Đặng\'</span>&nbsp;cho biến này. Một vùng nhớ trong RAM của máy tính sẽ được sử dụng để phục vụ việc lưu trữ những giá trị của biến khi chương trình được thực thi.</blockquote><p>Chuỗi&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">\'Sơn Đặng\'</span>&nbsp;đã được lưu vào vùng nhớ tương ứng với biến&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">fullName</span>. Ta có thể truy cập tới chuỗi này qua tên biến:</p><pre class=\"ql-syntax\" spellcheck=\"false\">var fullName;\n\nfullName = \'Sơn Đặng\';\n\nalert(fullName); // hiển thị giá trị của biến\n</pre><p><br></p><p>Để đơn giản và ngắn gọn, ta có thể kết hợp việc khai báo biến và gán giá trị cho biến thành một dòng:</p><pre class=\"ql-syntax\" spellcheck=\"false\">var fullName = \'Sơn Đặng\'; // khai báo và gán giá trị\n\nalert(fullName);\n</pre><p><br></p><p>Ta cũng có thể khai báo nhiều biến trong cùng một dòng cách nhau bởi dấu&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">,</span>&nbsp;như sau:</p><pre class=\"ql-syntax\" spellcheck=\"false\">var fullName = \'Sơn Đặng\', age = 18, workAt = \'F8\';\n</pre><p><br></p><p>Trông có vẻ ngắn gọn, tuy nhiên mình khuyên các bạn không nên dùng cách này. Khi cần khai báo nhiều biến hơn thì cách này trở nên rất khó đọc.</p><p><br></p><p>Ta nên khai báo biến trên mỗi dòng khác nhau để dễ đọc hơn (nên dùng cách này):</p><pre class=\"ql-syntax\" spellcheck=\"false\">var fullName = \'Sơn Đặng\';\nvar age = 18;\nvar workAt = \'F8\';\n</pre><p><br></p><p>Một số cách khai báo biến trên nhiều dòng khác như sau:</p><pre class=\"ql-syntax\" spellcheck=\"false\">var fullName = \'Sơn Đặng\',\n    age = 18,\n    workAt = \'F8\';\n</pre><p><br></p><p>Thậm chí có cả phong cách sau:</p><pre class=\"ql-syntax\" spellcheck=\"false\">var fullName = \'Sơn Đặng\'\n    , age = 18\n    , workAt = \'F8\';\n</pre><p><br></p><p>Về mặt kỹ thuật thì tất cả các cách đều tương tự nhau. Vì vậy dùng cách nào là tùy theo sở thích của bạn.</p><blockquote>Khi gán giá trị dạng số cho biến chúng ta không sử dụng dấu nháy đơn&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">\'\'</span>&nbsp;bao bọc bên ngoài. Như ví dụ trên thì&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">age = 18</span>&nbsp;ta sẽ viết luôn là số&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">18</span>&nbsp;và không sử dụng dấu nháy.</blockquote><h2>Quy tắc đặt tên</h2><ol><li>Tên biến có thể bao gồm chữ cái, số, dấu gạch dưới ( _ ) và kí tự đô la ( $ )</li><li>Tên biến không thể bắt đầu bằng số, phải bắt đầu bằng một chữ cái hoặc dấu gạch dưới hoặc dấu đô la</li><li>Tên biến phân biệt chữ hoa và chữ thường. Vì vậy&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">tenbien</span>&nbsp;và&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">TenBien</span>&nbsp;là 2 biến khác nhau</li><li>Tên biến không được (không thể) đặt trùng với các từ khóa của Javascript</li></ol><blockquote>Từ khóa là những từ được Javascript sử dụng để tạo nên những quy chuẩn về mặt chức năng và cú pháp trong Javascript. Ví dụ: Để khai báo một biến ta sẽ sử dụng từ khóa&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">var</span>. Vì vậy ta không thể đặt tên biến là \"var\"</blockquote><h4>Ví dụ cho tên biến hợp lệ</h4><pre class=\"ql-syntax\" spellcheck=\"false\">var address; // tên biến sử dụng chữ cái\n\nvar first_name; // kết hợp chữ cái và gạch dưới\n\nvar $last_name; // dấu đô la, gạch dưới và chữ cái\n\nvar f8; // chữ cái và số, số đứng sau chữ cái\n</pre><p><br></p><h4>Ví dụ cho tên biến không hợp lệ</h4><pre class=\"ql-syntax\" spellcheck=\"false\">var java-script; // bao gồm dấu gạch ngang\n\nvar 8f; // bắt đầu với chữ số\n\nvar var = \'Biến\'; // sử dụng trùng từ khóa `var`\n</pre><p><br></p><p>Các chữ cái không phải tiếng Lating vẫn có thể được sử dụng làm tên biến (không sử dụng cách này):</p><pre class=\"ql-syntax\" spellcheck=\"false\">var ດ້ານວິຊາການ = \'...\'; // tiếng Pháp\nvar ਤਕਨੀਕੀ = \'...\'; // tiếng Lào\n</pre><p><br></p><blockquote>Trong thực tế chúng ta sẽ sử dụng tiếng Anh để đặt tên biến vì đó là quy ước chung Quốc Tế.</blockquote><h2>Gán giá trị cho biến</h2><p>Các bạn hãy tưởng tượng biến như một chiếc hộp và giá trị gán cho biến như là đồ vật được bỏ vào hộp. Vì vậy ta có thể đặt bất cứ giá trị gì vào hộp và ta cũng có thể thay thế chúng nếu muốn:</p><pre class=\"ql-syntax\" spellcheck=\"false\">var fullName; // tạo chiếc hộp\n\nfullName = \'Sơn Đặng\'; // cho đồ vật vào hộp\n\nfullName = \'Nguyễn Văn A\'; // thay thế đồ vật khác\n\nalert(fullName); // Nguyễn Văn A\n</pre><p><br></p><blockquote>Khi giá trị của biến được thay đổi, giá trị cũ sẽ bị xóa khỏi biến.</blockquote><p>Ta cũng có thể sao chép giá trị từ biến này sang biến khác:</p><pre class=\"ql-syntax\" spellcheck=\"false\">var currentCourse = \'Javascript\';\n\nvar newCourse;\n\n// copy giá trị \'Javascript\' từ biến\n// \'currentCourse\' sang biến \'newCourse\'\nnewCourse = currentCourse;\n\n// bây giờ, biến \'newCourse\' và \'currentCourse\'\n// đều có giá trị là \'Javascript\'\n\nalert(currentCourse); // Javascript\n\nalert(newCourse); // Javascript\n</pre><p><br></p><blockquote>Có thể bạn chưa biết có những ngôn ngữ lập trình như&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">Scala</span>,&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">Erlang</span>&nbsp;không cho phép thay đổi giá trị của biến đã định nghĩa. Ta bắt buộc phải tạo biến mới khi cần lưu giá trị và không thể gán lại giá trị cho biến cũ.</blockquote><h2>Đặt tên biến như nào cho đúng?</h2><p>Đặt tên biến hợp lệ theo quy tắc của Javascript là việc đơn giản, tuy nhiên trong thực tế đặt tên biến không chỉ dừng lại ở việc đặt cho hợp lệ mà ta còn phải quan tâm tới các yếu tố khác như:</p><ol><li>Tên biến phải có ý nghĩa cụ thể, phải rõ ràng và thể hiện được nó đang lưu trữ cái gì.</li><li>Sử dụng tiếng Anh để đặt tên biến, sử dụng các từ có thể đọc lên được như&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">userName</span>,&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">phoneNumber</span>,&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">verifyEmail</span>, ..</li><li>Tránh đặt tên biến ngắn như&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">a</span>,&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">b</span>,&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">p</span>&nbsp;trừ khi bạn chỉ đang làm ví dụ hoặc bạn thật sự hiểu trường hợp đó có thể đặt tên như vậy.</li><li>Tránh đặt tên biến chung chung kiểu như&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">data</span>,&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">value</span>. Vì khi nhìn vào không thể hiểu&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">data</span>&nbsp;là&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">data</span>&nbsp;của cái gì,&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">value</span>&nbsp;là&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">value</span>&nbsp;của cái gì. Chỉ sử dụng tên dạng này khi đang trong ngữ cảnh cụ thể giúp bổ nghĩa cho những từ chung chung đó.</li></ol><h4>Đặt tên biến chung chung (trường hợp nên tránh)</h4><p><br></p><p>Ví dụ:</p><pre class=\"ql-syntax\" spellcheck=\"false\">var data = \'...\'; // không biết data là data của cái gì\nvar value = \'...\'; // không biết value là value của cái gì\n\n// var documentData = \'...\' ; Nên đặt rõ ràng ra như này\n// var documentValue = \'...\'; và như này\n</pre><p><br></p><h4>Đặt tên biến chung chung (trường hợp nên dùng)</h4><p><br></p><p>Ví dụ:</p><pre class=\"ql-syntax\" spellcheck=\"false\">function Document() {\n     var data = \'...\';\n    // hoặc\n     var value = \'...\';\n     \n    // var documentValue = \'...\'; Đặt như này sẽ bị lặp lại chữ \"document\" không cần thiết\n}\n</pre><p><br></p><p>Bạn chưa cần quan tâm&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">function</span>&nbsp;là gì vì ta sẽ học nó ở những bài sau. Trong trường hợp này biến&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">data</span>&nbsp;hoặc&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">value</span>&nbsp;nằm trong&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">Document</span>. Vì vậy&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">Document</span>&nbsp;đã giúp lập trình viên khi nhìn vào hiểu được&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">data</span>,&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">value</span>&nbsp;là thuộc về&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">Document</span>. Trong trường hợp này thì tên biến giúp đơn giản hóa và vẫn truyền đạt được đầy đủ ý nghĩa.</p><h2>Có thể bạn chưa biết</h2><ul><li>Đặt tên biến là một trong những kỹ năng quan trọng và phức tạp nhất trong lập trình. Nhìn lướt qua các tên biến có thể biết code nào được viết bởi người mới và người đã có nhiều kinh nghiệm.</li><li>Trong thực tế nhiều khi chúng ta phải làm việc trên code đã có sẵn thay vì viết hoàn toàn mới. Có khi bạn sẽ làm việc trên code cũ của người khác và ngược lại. Vì vậy đặt tên biến rõ ràng, dễ hiểu, truyền đạt đúng mục đích sử dụng là quan trọng hơn cả.</li><li>Chỉ sau vài tháng bạn có thể quên đi đoạn mã do chính tay mình viết. Để chính bạn hiểu bạn đã từng code cái gì trong quá khứ thì việc đặt tên biến tuân thủ các nguyên tắc trên là vô cùng quan trọng.</li></ul><p><br></p><p>Khi phải lựa chọn giữa&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">performance</span>&nbsp;(hiệu năng) và&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">clean code</span>&nbsp;(code sạch) người ta thường lựa chọn clean code. Việc đánh đổi này là cần thiết để giúp code dễ hiểu, dễ bảo trì và nâng cấp về sau. Và đặt tên biến chính là một trong những yếu tố giúp code của bạn trở nên clear hơn.</p><p>Fact:&nbsp;Code cho máy hiểu thì dễ, code cho người hiểu mới khó!</p><p><br></p>','2024-12-29 08:21:42','2024-12-29 08:21:42',246),(59,'https://www.youtube.com/watch?v=x0fSBAgBrOQ','<p><span style=\"background-color: rgba(255, 255, 255, 0.1);\">Đây là video mở đầu trong chuối video khóa học ReactJS miễn phí của F8, video này mình sẽ giới thiệu tới các bạn ReactJS là gì | Tại sao nên học ReactJS | Khóa học ReactJS miễn phí</span></p>','2025-05-09 12:22:14','2025-05-09 12:22:14',273),(60,'https://www.youtube.com/watch?v=30sMCciFIAM','<p><span style=\"background-color: rgba(255, 255, 255, 0.1);\">Ở video này chúng ta sẽ cùng nhau tìm hiểu về SPA/MPA là gì? | Khái niệm SPA |  ReactJS </span></p><p><span style=\"background-color: rgba(255, 255, 255, 0.1);\">SPA hay Single-page application là gì? Ngược lại chúng ta có MPA hay Multi-page application là gì? Hãy cùng tìm hiểu SPA &amp; MPA qua bài học thuộc khóa ReactJS này nhé.</span></p>','2025-05-09 12:23:05','2025-05-09 12:23:05',273);
/*!40000 ALTER TABLE `tbllesson` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbllessongroup`
--

DROP TABLE IF EXISTS `tbllessongroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbllessongroup` (
  `id` int NOT NULL AUTO_INCREMENT,
  `courseId` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `Level` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `courseId` (`courseId`),
  CONSTRAINT `tblLessonGroup_ibfk_1` FOREIGN KEY (`courseId`) REFERENCES `tblcourse` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbllessongroup`
--

LOCK TABLES `tbllessongroup` WRITE;
/*!40000 ALTER TABLE `tbllessongroup` DISABLE KEYS */;
INSERT INTO `tbllessongroup` VALUES (1,3,'Giới thiệu','2024-05-05 00:00:00','2024-05-05 00:00:00',1),(2,3,'Biến, comments, built-in','2024-05-05 00:00:00','2024-05-05 00:00:00',2),(13,3,'Toán tử, kiểu dữ liệu','2024-11-06 09:15:26','2024-11-06 09:15:26',3),(14,3,'Làm việc với hàm','2024-11-06 09:16:16','2024-11-06 09:16:16',4),(15,3,'Làm việc với chuỗi','2024-11-06 09:17:51','2024-11-06 09:17:51',5),(16,3,'Làm việc với số','2024-11-06 09:18:30','2024-11-06 09:18:30',6),(17,3,'Làm việc với object','2024-11-06 09:19:42','2024-11-06 09:19:42',7),(18,3,'Lệnh rẽ nhánh, toán tử 3 ngôi','2024-11-06 09:20:14','2024-11-06 09:20:14',8),(19,3,'Vòng lặp','2024-11-06 09:20:50','2024-11-06 09:20:50',9),(21,3,'Callback JS','2024-11-06 09:22:18','2024-11-06 09:22:18',11),(22,3,'HTML DOM','2024-11-06 09:22:52','2024-11-06 09:22:52',12),(23,3,'JSON, Fetch, Postman','2024-11-06 09:23:14','2024-11-06 09:23:14',13),(24,3,'ECMAScript 6+','2024-11-06 09:23:57','2024-11-06 09:23:57',14),(25,3,'Các bài thực hành','2024-11-06 09:24:27','2024-11-06 09:24:27',15),(26,3,'Form validation I','2024-11-06 09:25:21','2024-11-06 09:25:21',16),(27,3,'Form validation II','2024-11-06 09:25:47','2024-11-06 09:25:47',17),(28,3,'Tham khảo thêm','2024-11-06 09:26:08','2024-11-06 09:26:08',18),(29,3,'Hoàn thành khóa học','2024-11-06 09:26:28','2024-11-06 09:26:28',19),(30,3,'Làm việc với mảng II','2024-12-31 21:20:02','2024-12-31 21:20:02',20),(31,42,'Giới thiệu','2025-05-09 12:12:55','2025-05-09 12:12:55',1),(32,42,'Ôn lại ES6+','2025-05-09 12:13:11','2025-05-09 12:13:11',2),(33,42,'React, ReactDOM','2025-05-09 12:13:23','2025-05-09 12:13:23',3),(34,42,'JSX, Components, Props','2025-05-09 12:13:49','2025-05-09 12:13:49',4),(35,42,'Create React App','2025-05-09 12:14:05','2025-05-09 12:14:05',5),(36,42,'Hooks ','2025-05-09 12:14:25','2025-05-09 12:14:25',6),(37,42,'CSS, SCSS và CSS modules','2025-05-09 12:14:41','2025-05-09 12:14:41',7),(38,42,'React Router V6','2025-05-09 12:14:59','2025-05-09 12:14:59',8),(39,42,'Dựng base dự án Tiktok','2025-05-09 12:15:13','2025-05-09 12:15:13',9),(40,42,'Xây dựng phần Header','2025-05-09 12:15:29','2025-05-09 12:15:29',10),(41,42,'Xây dựng UI phần Header #6','2025-05-09 12:15:52','2025-05-09 12:15:52',11),(42,42,'Xây dựng phần Sidebar','2025-05-09 12:16:14','2025-05-09 12:16:14',12),(43,42,'Xây dựng phần Authen','2025-05-09 12:16:29','2025-05-09 12:16:29',13),(44,42,'Xây dựng phần xem video','2025-05-09 12:16:42','2025-05-09 12:16:42',14),(45,42,'Dựng phần theo dõi & thả tim','2025-05-09 12:17:01','2025-05-09 12:17:01',15);
/*!40000 ALTER TABLE `tbllessongroup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbllikeblog`
--

DROP TABLE IF EXISTS `tbllikeblog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbllikeblog` (
  `userId` int NOT NULL,
  `blogId` int NOT NULL,
  `heartDay` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`userId`,`blogId`),
  KEY `tblLikeBlog_tblBlogger_FK` (`blogId`),
  CONSTRAINT `tblLikeBlog_tblBlogger_FK` FOREIGN KEY (`blogId`) REFERENCES `tblblogger` (`id`),
  CONSTRAINT `tblLikeBlog_tblUser_FK` FOREIGN KEY (`userId`) REFERENCES `tbluser` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbllikeblog`
--

LOCK TABLES `tbllikeblog` WRITE;
/*!40000 ALTER TABLE `tbllikeblog` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbllikeblog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbllikecomment`
--

DROP TABLE IF EXISTS `tbllikecomment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbllikecomment` (
  `userId` int NOT NULL,
  `commentId` int NOT NULL,
  `createAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `updateAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `icon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`userId`,`commentId`),
  KEY `tblLikeComment_tblCommentLesson_FK` (`commentId`),
  CONSTRAINT `tblLikeComment_tblCommentLesson_FK` FOREIGN KEY (`commentId`) REFERENCES `tblcommentlesson` (`id`),
  CONSTRAINT `tblLikeComment_tblUser_FK` FOREIGN KEY (`userId`) REFERENCES `tbluser` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbllikecomment`
--

LOCK TABLES `tbllikecomment` WRITE;
/*!40000 ALTER TABLE `tbllikecomment` DISABLE KEYS */;
INSERT INTO `tbllikecomment` VALUES (32,186,'2025-05-13 15:02:09','2025-05-13 15:02:08','happy'),(36,158,'2025-05-13 15:01:28','2025-05-13 15:01:28','satisfaction');
/*!40000 ALTER TABLE `tbllikecomment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblnote`
--

DROP TABLE IF EXISTS `tblnote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblnote` (
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `description` text,
  `id` int NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `tblNote_tblLectureDetails_FK` FOREIGN KEY (`id`) REFERENCES `tbllecturedetails` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblnote`
--

LOCK TABLES `tblnote` WRITE;
/*!40000 ALTER TABLE `tblnote` DISABLE KEYS */;
INSERT INTO `tblnote` VALUES ('2024-11-13 07:20:58','2024-11-13 07:20:58','<p>Học lập trình một mình sao bằng có bạn bè cùng tiến? Đừng để bản thân phải lạc lõng, hãy ghé qua Discord của F8 và cảm nhận sự khác biệt nhé!</p><ul><li>Bạn sẽ được học cùng những người bạn mới, giỏi giang, đẹp trai, xinh gái!</li><li>Cùng xây dựng team code siêu chất, học hỏi lẫn nhau và tiến bộ cùng nhau!</li><li>Học hỏi từ người đi trước, có thêm động lực và sự tự giác trong học tập!</li><li>Nơi mà sự tiêu cực không có chỗ đứng, câu hỏi nào cũng được trả lời, không sợ bị đánh giá toxic, chỉ có sự hỗ trợ và tôn trọng lẫn nhau!</li></ul><p>✅&nbsp;<strong>THAM GIA NGAY</strong>:&nbsp;<a href=\"https://discord.gg/sCdvr5MufX\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"color: var(--primary-color);\">https://discord.gg/sCdvr5MufX</a></p><p><a href=\"https://discord.gg/sCdvr5MufX\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"color: var(--primary-color);\"><img src=\"https://files.fullstack.edu.vn/f8-prod/public-images/6603da227f20c.png\"></a></p><p><em>Hãy biến quá trình học lập trình của bạn thành một hành trình thú vị và đầy ắp tiếng cười!</em></p>',48),('2024-12-13 13:22:41','2024-12-13 13:22:41','<h1>Làm quen với màn thử thách</h1><p>Cập nhật&nbsp;tháng 6 năm 2024</p><p><br></p><blockquote>Nội dung quan trọng! Vui lòng đọc kỹ!</blockquote><p>Chào các bạn, tại F8 các bạn không chỉ được học qua video, F8 có ít nhất 3 loại bài học dành cho các bạn:</p><ol><li>Bài học dạng video</li><li>Bài học dạng text - văn bản</li><li>Bài học dạng thử thách - bài tập</li></ol><p>Trong bài sau, các bạn sẽ được làm quen với màn&nbsp;<strong>Thử thách</strong>.</p><h2>Màn thử thách chia làm 4 phần</h2><ol><li><strong>NỘI DUNG:</strong>&nbsp;Chứa mô tả - yêu cầu của thử thách, cho bạn biết cách để vượt qua thử thách</li><li><strong>TRÌNH DUYỆT:</strong>&nbsp;Hiển thị trang web của bạn, khi viết code tại&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">index.html</span>&nbsp;giao diện sẽ tự động được làm mới</li><li><strong>CODE EDITOR:</strong>&nbsp;Nơi chứa các file như&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">index.html</span>,&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">index.js</span>, các bạn sẽ viết code tại đây để hoàn thành thử thách</li><li><strong>BÀI KIỂM TRA:</strong>&nbsp;Danh sách các bài kiểm tra để xác minh phần trả lời của bạn là đúng yêu cầu đề bài. Các bài kiểm tra sẽ báo lỗi khi bạn làm sai, hãy dựa vào các thông báo lỗi để tìm cách vượt qua thử thách nhé</li></ol><h2>Demo cách sử dụng màn thử thách</h2><h2>Tổng kết</h2><ol><li><strong>Luôn luôn đọc kỹ yêu cầu trong phần NỘI DUNG</strong></li><li><strong>Khi viết code trong EDITOR, luôn luôn mở TRÌNH DUYỆT để xem giao diện trực quan (nếu có tệp index.html)</strong></li><li><strong>Nhấn KIỂM TRA để chấm phần trả lời, đọc kỹ thông báo lỗi để tìm cách giải quyết</strong></li></ol><p>Chúc các bạn học tập tốt 🥰</p><p><br></p>',52),('2024-12-13 13:23:26','2024-12-13 13:23:26','<p>Tham gia các cộng đồng để cùng học hỏi, chia sẻ và \"thám thính\" xem F8 sắp có gì mới nhé!</p><ul><li>Fanpage:&nbsp;<a href=\"https://www.facebook.com/f8vnofficial\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"color: var(--primary-color);\">https://www.facebook.com/f8vnofficial</a></li><li>Group:&nbsp;<a href=\"https://www.facebook.com/groups/649972919142215\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"color: var(--primary-color);\">https://www.facebook.com/groups/649972919142215</a></li><li>Youtube:&nbsp;<a href=\"https://www.youtube.com/F8VNOfficial\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"color: var(--primary-color);\">https://www.youtube.com/F8VNOfficial</a></li></ul><h2>Học Offline tại F8?</h2><p>F8 có các lớp học Offline tại Hà Nội các bạn nhé. Lớp học linh hoạt, phù hợp cho cả sinh viên và người đi làm.</p><p>Hình ảnh không gian học tập tại F8:</p><p><img src=\"https://files.fullstack.edu.vn/f8-prod/public-images/646de7c4d0d94.jpg\"></p><p><img src=\"https://files.fullstack.edu.vn/f8-prod/public-images/646de7ce47ddb.jpg\"></p><p>✅ Để lại thông tin để F8 tư vấn miễn phí cho bạn:&nbsp;<a href=\"https://short.f8team.dev/dang-ky-hoc-offline-hn\" rel=\"noopener noreferrer\" target=\"_blank\" style=\"color: var(--primary-color);\">https://short.f8team.dev/dang-ky-hoc-offline-hn</a></p><h2>Cách hoàn thành bài học video?</h2><ul><li>Xem hết nội dung video là sẽ hoàn thành bài học</li><li>Tắt extension chặn quảng cáo (VD adsblock) vì có thể gây xung đột</li><li>Xem video ở tốc độ vừa phải, tua quá nhanh hoặc để tốc độ quá nhanh có thể không hoàn thành được bài học</li></ul><h2>Cách hoàn thành bài học text?</h2><ul><li>Bạn cần đọc hết nội dung, cuộn xuống dưới cùng để hoàn thành bài</li><li>Nếu cuộn xuống quá nhanh, có thể bạn sẽ không hoàn thành được bài học</li></ul><blockquote>Bài này chính là một bài học dạng text, bạn cần đọc hết nội dung để có thể hoàn thành bài học này.</blockquote><h2>Tại sao bài học lại bị khóa?</h2><ul><li>Giúp người mới học tập đúng lộ trình một cách bài bản</li><li>Cấp chứng chỉ hoàn thành khóa học cho bạn 🎉🎉</li></ul><h2>Bài kiểm tra là gì?</h2><p>Tại F8, bạn có thể thực hành sau mỗi bài học ngay tại trang web này, mỗi bài thực hành có thể có những bài kiểm tra. Các bài kiểm tra được đưa ra nhằm đảm bảo code của bạn đã đạt yêu cầu.</p><blockquote>Một số bài thực hành có thể không có bài kiểm tra, những bài này thường mang tính ví dụ, bạn có thể nhấn&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">Kiểm tra</span>&nbsp;để hoàn thành các bài ví dụ.</blockquote><h2>Giúp admin report các bình luận spam nhé!</h2><p>Xin nhắc lại, phần Hỏi đáp tại mỗi bài học là để hỏi đáp/trao đổi về kiến thức đã học. Các bình luận spam không mang lại giá trị cho người đọc, vì vậy chúng ta nên tránh nhé.</p><p><strong>Những nội dung sau được coi là spam:</strong></p><ol><li>\"Đã xong\", \"Đã hoàn thành\", v.v</li><li>\"Tôi đã ở đây\"</li><li>\"Day 1\", \"Day 2\", \"Day xx\", v.v</li><li>Các bình luận không phù hợp văn hóa, thuần phong mỹ tục</li></ol><blockquote>Nếu thấy các bình luận spam, các bạn giúp admin nhấn vào nút \"Báo cáo bình luận\" bên cạnh mỗi bình luận nhé. Admin đang xây dựng chức năng block tài khoản, một số tài khoản vi phạm có thể bị block vô thời hạn trong tương lai.</blockquote><p>Cảm ơn các bạn! Chúc các bạn học vui &lt;3</p><p><br></p>',53);
/*!40000 ALTER TABLE `tblnote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblNotifications`
--

DROP TABLE IF EXISTS `tblNotifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblNotifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `message` text,
  `is_read` tinyint(1) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` varchar(100) DEFAULT NULL,
  `data_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tblNotifications_tbluser_FK` (`user_id`),
  CONSTRAINT `tblNotifications_tbluser_FK` FOREIGN KEY (`user_id`) REFERENCES `tbluser` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblNotifications`
--

LOCK TABLES `tblNotifications` WRITE;
/*!40000 ALTER TABLE `tblNotifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `tblNotifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblOrderDetails`
--

DROP TABLE IF EXISTS `tblOrderDetails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblOrderDetails` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `course_id` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_OrderDetails_Orders` (`order_id`),
  KEY `FK_OrderDetails_Course` (`course_id`),
  CONSTRAINT `FK_OrderDetails_Course` FOREIGN KEY (`course_id`) REFERENCES `tblcourse` (`id`),
  CONSTRAINT `FK_OrderDetails_Orders` FOREIGN KEY (`order_id`) REFERENCES `tblOrders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblOrderDetails`
--

LOCK TABLES `tblOrderDetails` WRITE;
/*!40000 ALTER TABLE `tblOrderDetails` DISABLE KEYS */;
INSERT INTO `tblOrderDetails` VALUES (14,14,42,1499000.00),(15,15,42,1499000.00),(16,16,42,1499000.00),(17,17,42,1499000.00),(18,18,42,1499000.00),(19,19,42,1499000.00),(20,20,42,1499000.00),(21,21,42,1499000.00),(22,22,42,1499000.00),(23,23,42,1499000.00),(24,24,42,1499000.00),(25,25,42,1499000.00),(26,26,42,1499000.00),(27,27,42,1499000.00),(28,28,42,1499000.00);
/*!40000 ALTER TABLE `tblOrderDetails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblOrders`
--

DROP TABLE IF EXISTS `tblOrders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblOrders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `amount` decimal(10,0) DEFAULT NULL,
  `transactionId` bigint DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `paymentGateway` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tblOrders_tbluser_FK` (`user_id`),
  CONSTRAINT `tblOrders_tbluser_FK` FOREIGN KEY (`user_id`) REFERENCES `tbluser` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblOrders`
--

LOCK TABLES `tblOrders` WRITE;
/*!40000 ALTER TABLE `tblOrders` DISABLE KEYS */;
INSERT INTO `tblOrders` VALUES (14,32,1499000,1747044440235,'pending','vnpay','2025-05-12 10:07:20','2025-05-12 17:07:20'),(15,32,1499000,1747044699132,'pending','vnpay','2025-05-12 10:11:39','2025-05-12 17:11:39'),(16,32,1499000,1747045564019,'pending','vnpay','2025-05-12 10:26:04','2025-05-12 17:26:04'),(17,32,1499000,1747045588560,'pending','vnpay','2025-05-12 10:26:29','2025-05-12 17:26:28'),(18,32,1499000,1747052100690,'pending','vnpay','2025-05-12 12:15:01','2025-05-12 19:15:00'),(19,32,1499000,1747052417755,'pending','vnpay','2025-05-12 12:20:18','2025-05-12 19:20:17'),(20,32,1499000,1747052647905,'pending','vnpay','2025-05-12 12:24:08','2025-05-12 19:24:07'),(21,32,1499000,1747052702814,'pending','vnpay','2025-05-12 12:25:03','2025-05-12 19:25:02'),(22,32,1499000,1747052737895,'pending','vnpay','2025-05-12 12:25:38','2025-05-12 19:25:37'),(23,32,1499000,1747055963720,'pending','vnpay','2025-05-12 13:19:24','2025-05-12 20:19:23'),(24,32,1499000,1747056472951,'success','vnpay','2025-05-12 13:27:53','2025-05-12 13:28:25'),(25,36,1499000,1747105847587,'success','vnpay','2025-05-13 03:10:48','2025-05-13 03:11:47'),(26,36,1499000,1747108972288,'success','vnpay','2025-05-13 04:02:52','2025-05-13 04:03:27'),(27,36,1499000,1747123406530,'success','vnpay','2025-05-13 08:03:27','2025-05-13 08:04:20'),(28,36,1499000,1747562532197,'pending','vnpay','2025-05-18 10:02:12','2025-05-18 17:02:12');
/*!40000 ALTER TABLE `tblOrders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblPaymentResults`
--

DROP TABLE IF EXISTS `tblPaymentResults`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblPaymentResults` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transactionId` bigint NOT NULL,
  `queryString` text,
  `isSuccess` tinyint(1) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblPaymentResults`
--

LOCK TABLES `tblPaymentResults` WRITE;
/*!40000 ALTER TABLE `tblPaymentResults` DISABLE KEYS */;
INSERT INTO `tblPaymentResults` VALUES (4,1747056472951,'Microsoft.AspNetCore.Http.QueryCollectionInternal',1,'2025-05-12 13:28:25'),(5,1747105847587,'Microsoft.AspNetCore.Http.QueryCollectionInternal',1,'2025-05-13 03:11:47'),(6,1747108972288,'Microsoft.AspNetCore.Http.QueryCollectionInternal',1,'2025-05-13 04:03:27'),(7,1747123406530,'Microsoft.AspNetCore.Http.QueryCollectionInternal',1,'2025-05-13 08:04:20');
/*!40000 ALTER TABLE `tblPaymentResults` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblquestioncode`
--

DROP TABLE IF EXISTS `tblquestioncode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblquestioncode` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime DEFAULT (now()),
  `updated_at` datetime DEFAULT (now()),
  `description` text,
  `languageId` int DEFAULT NULL,
  `starterCode` text,
  `solution` text,
  `resultcode` text,
  PRIMARY KEY (`id`),
  KEY `tblQuestionCode_tblLanguageCode_FK` (`languageId`),
  CONSTRAINT `tblQuestionCode_ibfk_1` FOREIGN KEY (`id`) REFERENCES `tbllecturedetails` (`id`),
  CONSTRAINT `tblQuestionCode_tblLanguageCode_FK` FOREIGN KEY (`languageId`) REFERENCES `tbllanguagecode` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblquestioncode`
--

LOCK TABLES `tblquestioncode` WRITE;
/*!40000 ALTER TABLE `tblquestioncode` DISABLE KEYS */;
INSERT INTO `tblquestioncode` VALUES (54,'2024-12-22 15:24:43','2024-12-22 15:24:43','<h2>Xin chào các bạn!</h2><p>Đây là màn hình Thử Thách tại F8 các bạn nhé. Từ các bài học sau, các bạn sẽ có những bài tập cần phải vượt qua sau khi học mỗi kiến thức mới.</p><p>Hãy bắt đầu làm quen với màn Thử Thách này bằng cách làm theo yêu cầu dưới đây:</p><p>👉 Hãy nhấn copy và dán đoạn code sau vào file&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">main.js</span>:</p><pre class=\"ql-syntax\" spellcheck=\"false\">console.log(\'Hello world\');',1,'console.log();','Thêm console.log(\'Hello world\'); vào tệp index.js','console.log(\'Hello world\');'),(57,'2025-01-01 11:40:59','2025-01-01 11:40:59','<p>Vượt qua thử thách này bằng cách tạo một hàm tên là&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">sum</span>.</p><blockquote>Chỉ cần tạo hàm, không cần viết gì trong phần thân của hàm.</blockquote><p><br></p>',1,'// code ở đây','<p>Tạo hàm tên là sum</p>','function sum () {}'),(58,'2025-01-01 14:04:00','2025-01-01 14:04:00','<p><span style=\"color: rgb(41, 41, 41);\">Bạn hãy sử dụng&nbsp;</span><span style=\"color: rgb(41, 41, 41); background-color: var(--prism-inline-code-bg, #c9fffc);\">spread</span><span style=\"color: rgb(41, 41, 41);\">&nbsp;để sao chép tất cả các&nbsp;</span><span style=\"color: rgb(41, 41, 41); background-color: var(--prism-inline-code-bg, #c9fffc);\">key</span><span style=\"color: rgb(41, 41, 41);\">&nbsp;và&nbsp;</span><span style=\"color: rgb(41, 41, 41); background-color: var(--prism-inline-code-bg, #c9fffc);\">value</span><span style=\"color: rgb(41, 41, 41);\">&nbsp;từ object&nbsp;</span><span style=\"color: rgb(41, 41, 41); background-color: var(--prism-inline-code-bg, #c9fffc);\">person1</span><span style=\"color: rgb(41, 41, 41);\">&nbsp;sang&nbsp;</span><span style=\"color: rgb(41, 41, 41); background-color: var(--prism-inline-code-bg, #c9fffc);\">person2</span></p>',1,'const person1 = {\r\n    name: \'Son\',\r\n    age: 21\r\n}\r\n\r\nconst person2 = \r\n\r\n// Expected results\r\nconsole.log(person2.name) // Output: \'Son\'\r\nconsole.log(person2.age) // Output: 21\r\nconsole.log(person1 === person2) // Output: false','<p>Tạo biến person2</p>','const person1 = {\r\n    name: \'Son\',\r\n    age: 21\r\n}\r\n\r\nconst person2 = {...person1}\r\n// Expected results\r\n// console.log(person2.name) // Output: \'Son\'\r\n// console.log(person2.age) // Output: 21\r\n// console.log(person1 === person2) // Output: false');
/*!40000 ALTER TABLE `tblquestioncode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblquestionslesson`
--

DROP TABLE IF EXISTS `tblquestionslesson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblquestionslesson` (
  `id` int NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `created_at` datetime DEFAULT (now()),
  `updated_at` datetime DEFAULT (now()),
  PRIMARY KEY (`id`),
  CONSTRAINT `tblQuestionsLesson_ibfk_1` FOREIGN KEY (`id`) REFERENCES `tbllecturedetails` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblquestionslesson`
--

LOCK TABLES `tblquestionslesson` WRITE;
/*!40000 ALTER TABLE `tblquestionslesson` DISABLE KEYS */;
INSERT INTO `tblquestionslesson` VALUES (49,'<pre class=\"ql-syntax\" spellcheck=\"false\">var a = 1;\nvar b = -1;\nvar c = 0;\nvar d = 0;\n\nvar e = a &lt;= b;\nvar f = c === d;\nvar g = a &gt;= c;\n\nconsole.log(e, f, g) // Output: ?\n</pre>','2024-11-15 10:46:37','2024-11-15 10:46:37'),(50,'<pre class=\"ql-syntax\" spellcheck=\"false\">function showMessage(message) {\n&nbsp;&nbsp;console.log(message);\n}\n\nshowMessage(\"Hi anh em F8!\");\n</pre><p><br></p>','2024-11-15 13:04:33','2024-11-15 13:04:33'),(61,'<p><span style=\"color: rgb(41, 41, 41);\">Ưu điểm của SPA là gì? Chọn câu trả lời đúng.</span></p>','2025-05-09 12:24:45','2025-05-09 12:24:45');
/*!40000 ALTER TABLE `tblquestionslesson` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblquestionslessondetails`
--

DROP TABLE IF EXISTS `tblquestionslessondetails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblquestionslessondetails` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `quesonId` int DEFAULT NULL,
  `answer` text,
  `isTrue` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`Id`),
  KEY `tblQuestionsLessonDetails_tblQuestionsLesson_FK` (`quesonId`),
  CONSTRAINT `tblQuestionsLessonDetails_tblQuestionsLesson_FK` FOREIGN KEY (`quesonId`) REFERENCES `tblquestionslesson` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblquestionslessondetails`
--

LOCK TABLES `tblquestionslessondetails` WRITE;
/*!40000 ALTER TABLE `tblquestionslessondetails` DISABLE KEYS */;
INSERT INTO `tblquestionslessondetails` VALUES (14,49,'true false true',0),(15,49,'false false true',0),(16,49,'false true true',1),(17,50,'message là đối số (argument)',0),(18,50,'message là tham số (parameter)',1),(19,50,'\"Hi anh em F8!\" là tham số (parameter)',0),(20,61,'Không yêu cầu tải lại trang khi chuyển trang.',1),(21,61,'Có thể làm được nhiều hiệu ứng chuyển động trên web',0),(22,61,'Thời gian phát triển ứng dụng nhanh hơn',0);
/*!40000 ALTER TABLE `tblquestionslessondetails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblrole`
--

DROP TABLE IF EXISTS `tblrole`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblrole` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `Authorrities` int DEFAULT NULL,
  `CreatedAt` datetime(6) DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblrole`
--

LOCK TABLES `tblrole` WRITE;
/*!40000 ALTER TABLE `tblrole` DISABLE KEYS */;
INSERT INTO `tblrole` VALUES (1,'User',0,NULL,NULL),(2,'Admin',1,NULL,NULL);
/*!40000 ALTER TABLE `tblrole` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblroute`
--

DROP TABLE IF EXISTS `tblroute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblroute` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `description` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblroute`
--

LOCK TABLES `tblroute` WRITE;
/*!40000 ALTER TABLE `tblroute` DISABLE KEYS */;
/*!40000 ALTER TABLE `tblroute` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblSteps`
--

DROP TABLE IF EXISTS `tblSteps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblSteps` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblSteps`
--

LOCK TABLES `tblSteps` WRITE;
/*!40000 ALTER TABLE `tblSteps` DISABLE KEYS */;
INSERT INTO `tblSteps` VALUES (1,'HTML và CSS','Để học web Front-end chúng ta luôn bắt đầu với ngôn ngữ HTML và CSS, đây là 2 ngôn ngữ có mặt trong mọi website trên internet. Trong khóa học này F8 sẽ chia sẻ từ những kiến thức cơ bản nhất. Sau khóa học này bạn sẽ tự làm được 2 giao diện websites là The Band và Shopee.','2025-04-22 10:01:07','2025-04-22 10:07:15'),(2,'CSS cơ bản','Làm đẹp giao diện với CSS, flexbox, grid.','2025-04-22 10:01:07','2025-04-22 10:01:07'),(3,'JavaScript','Với HTML, CSS bạn mới chỉ xây dựng được các websites tĩnh, chỉ bao gồm phần giao diện và gần như chưa có xử lý tương tác gì. Để thêm nhiều chức năng phong phú và tăng tính tương tác cho website bạn cần học Javascript.\n\nLập Trình JavaScript Cơ Bản\n','2025-04-22 10:01:07','2025-04-22 10:07:27'),(4,'Sử dụng Ubuntu/Linux','Cách làm việc với hệ điều hành Ubuntu/Linux qua Windows Terminal & WSL. Khi đi làm, nhiều trường hợp bạn cần nắm vững các dòng lệnh cơ bản của Ubuntu/Linux.','2025-04-22 10:01:07','2025-04-22 10:07:39'),(5,'Libraries and Frameworks','ột websites hay ứng dụng hiện đại rất phức tạp, chỉ sử dụng HTML, CSS, Javascript theo cách code thuần (tự code từ đầu tới cuối) sẽ rất khó khăn. Vì vậy các Libraries, Frameworks ra đời nhằm đơn giản hóa, tiết kiệm chi phí và thời gian để hoàn thành một sản phẩm website hoặc ứng dụng mobile.','2025-04-22 10:01:07','2025-04-22 10:07:59'),(6,'.NET Core Web API','Tạo RESTful API backend.','2025-04-22 10:01:07','2025-04-22 10:01:07'),(7,'Entity Framework','Quản lý database hiệu quả bằng EF.','2025-04-22 10:01:07','2025-04-22 10:01:07'),(8,'JWT & bảo mật API','Xác thực, phân quyền người dùng.','2025-04-22 10:01:07','2025-04-22 10:01:07'),(9,'Python cơ bản','Lập trình nền tảng cho AI.','2025-04-22 10:01:07','2025-04-22 10:01:07'),(10,'Machine Learning cơ bản','Tư duy mô hình học máy.','2025-04-22 10:01:07','2025-04-22 10:01:07'),(11,'Xử lý dữ liệu','Dùng Pandas, NumPy để xử lý data.','2025-04-22 10:01:07','2025-04-22 10:01:07'),(12,'Deep Learning','Xây dựng mạng neural bằng TensorFlow.','2025-04-22 10:01:07','2025-04-22 10:01:07'),(13,'Nhập môn CNTT','Nắm được 1 số khái niệm về CNTT, biết cách sử dụng IDE','2025-04-22 10:05:29','2025-04-22 10:05:29');
/*!40000 ALTER TABLE `tblSteps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbluser`
--

DROP TABLE IF EXISTS `tbluser`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbluser` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `FullName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `Email` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `Password` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `Avatar` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `IsActive` int DEFAULT NULL,
  `CodeId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `codeExpired` datetime(6) DEFAULT NULL,
  `CreatedAt` datetime(6) DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `RoleId` int NOT NULL DEFAULT '0',
  `Bio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `FacebookLink` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `GithubLink` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `PersonalWebsite` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `UserName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `YoutubeLink` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `GithubId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `IX_tblUser_RoleId` (`RoleId`),
  CONSTRAINT `tblUser_ibfk_1` FOREIGN KEY (`RoleId`) REFERENCES `tblrole` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbluser`
--

LOCK TABLES `tbluser` WRITE;
/*!40000 ALTER TABLE `tbluser` DISABLE KEYS */;
INSERT INTO `tbluser` VALUES (32,'Nguyễn Xuân Huỳnh','nguyenhuynhdt37@gmail.com','$2a$11$TLIWWU58ofqY3IBCmvNOSeMT.9BSf0uPcr1GanH8aLeYQ6aMUnkri','/images/users/avatars/anh-trai-dep-deo-kinh-600x600.jpg',1,'571980','2024-11-04 14:40:25.640807','2024-10-14 17:51:23.688676','2024-11-23 09:44:34.862024',1,'<h4>2. <strong>Props được truyền vào</strong></h4><ul><li><strong>data={lessonType}</strong>:</li><li class=\"ql-indent-1\">lessonType là một <strong>state</strong> hoặc một biến dữ liệu chứa các tùy chọn (options).</li><li class=\"ql-indent-1\">Được truyền vào component &lt;OptionType /&gt; để làm nguồn dữ liệu, có thể dùng để hiển thị danh sách hoặc lựa chọn.</li><li><strong>typeChoise={lessonTypeIsChoise}</strong>:</li><li class=\"ql-indent-1\">lessonTypeIsChoise là một <strong>state</strong> (thông qua useState trong React) chứa thông tin về loại nào đang được chọn.</li><li class=\"ql-indent-1\">Trong &lt;OptionType /&gt;, giá trị này được sử dụng để hiển thị hoặc xử lý logic liên quan đến tùy chọn hiện tại.</li><li><strong>setTypeChoise={setLessonTypeIsChoise}</strong>:</li><li class=\"ql-indent-1\">setLessonTypeIsChoise là hàm <strong>setState</strong> để cập nhật lessonTypeIsChoise.</li><li class=\"ql-indent-1\">Khi một tùy chọn được chọn hoặc thay đổi trong &lt;OptionType /&gt;, hàm này sẽ được gọi để cập nhật trạng thái.</li></ul><p class=\"ql-align-justify\"><br></p>','https://www.facebook.com/nguyenxuanhuynh2004/','https://github.com/nguyenhuynhdt37/',NULL,'huynhnguyenxuan','https://www.youtube.com/@nguyenxuanhuynh2211',127924881),(36,'Admin F8','admin_f8@gmail.com','$2a$11$TLIWWU58ofqY3IBCmvNOSeMT.9BSf0uPcr1GanH8aLeYQ6aMUnkri','/images/users/avatars/f8.png',1,NULL,NULL,'2024-10-14 17:51:23.688676','2024-10-14 17:51:23.688682',2,NULL,NULL,NULL,NULL,'adminF8',NULL,NULL),(41,'Nguyễn Thị Truyền','admin_f811@gmail.com','$2a$11$6AFw/nL9qAaHEx0BSy8XV.h8NDXGmYuydGP6xbBY6moy6a76Nit7W','/images/users/avatars/d36860ee80ca26ccbb00762f94080501.jpg',0,NULL,NULL,'2024-10-16 13:56:24.437841','2024-12-22 15:35:41.791658',1,'<h2>Xin chào các bạn!</h2><p>Đây là màn hình Thử Thách tại F8 các bạn nhé. Từ các bài học sau, các bạn sẽ có những bài tập cần phải vượt qua sau khi học mỗi kiến thức mới.</p><p>Hãy bắt đầu làm quen với màn Thử Thách này bằng cách làm theo yêu cầu dưới đây:</p><p>👉 Hãy nhấn copy và dán đoạn code sau vào file&nbsp;<span style=\"background-color: var(--prism-inline-code-bg, #c9fffc); color: var(--prism-inline-code-color);\">main.js</span>:</p><pre class=\"ql-syntax\" spellcheck=\"false\">alert(\'Hello world\');\n</pre><p><br></p><p>Sau đó, nhấn nút \"Kiểm tra\" để qua bài (alert có thể bật lên thêm vài lần sau khi nhấn kiểm tra).</p><blockquote>Tại trang web này, các bạn không cần phải liên kết file JavaScript (bằng cách internal hoặc external), vì F8 đã tự động làm điều này rồi các bạn nhé.</blockquote><p><br></p>','https://github.com/nguyenhuynhdt37/','https://github.com/nguyenhuynhdt37/',NULL,NULL,NULL,NULL),(54,'odasidoa','admin_f881@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(55,'odasidoa','admin_f871@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(56,'odasidoa','admin_f861@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(57,'odasidoa','admin_f851@gmail.com',NULL,'/images/users/avatars/anh-trai-dep-deo-kinh-600x600.jpg',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(59,'odasidoa','admin_f831@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(66,'Linh Nguyễn','nguyenhu3ynhdt37121@gmail.com','$2a$11$hqAO5G0vWTOKGWqdteUFtuxfO/Ysw.ANCE1cJo8ypXH4YkCluRbPe',NULL,1,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(67,'Linh Nguyễn','nguyenhu3yn3hdt37121@gmail.com','$2a$11$hqAO5G0vWTOKGWqdteUFtuxfO/Ysw.ANCE1cJo8ypXH4YkCluRbPe',NULL,1,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(68,'Linh Nguyễn','nguyenhu3ynqhdt37121@gmail.com','$2a$11$hqAO5G0vWTOKGWqdteUFtuxfO/Ysw.ANCE1cJo8ypXH4YkCluRbPe',NULL,1,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(69,'Linh Nguyễn','nguyenehu3ynhdt37121@gmail.com','$2a$11$hqAO5G0vWTOKGWqdteUFtuxfO/Ysw.ANCE1cJo8ypXH4YkCluRbPe',NULL,1,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(70,'Linh Nguyễn','nguyenheuynhdt37121@gmail.com','$2a$11$hqAO5G0vWTOKGWqdteUFtuxfO/Ysw.ANCE1cJo8ypXH4YkCluRbPe',NULL,1,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(71,'Linh Nguyễn','nguyenhu3ynehdt37121@gmail.com','$2a$11$hqAO5G0vWTOKGWqdteUFtuxfO/Ysw.ANCE1cJo8ypXH4YkCluRbPe',NULL,1,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(86,'Nguyễn Thị Truyền','admin_f8222@gmail.com','$2a$11$3sYdW.McbRRCoek3knE.Du9IuqbmoGDQScmh6PLUltscE3LRpHt7G',NULL,1,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(87,'Nguyễn Thị Truyền','admin1_f18@gmail.com','$2a$11$ya//pfyxqHWly5F9k/i8uefn6nXQjCKG6vEGbOJL0zjdCsjPRBdwG',NULL,0,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,'nttruyn',NULL,NULL),(88,'Nguyễn Thị Truyền','admin11_f8@gmail.com','$2a$11$M2VStp.SsYteq9VdXxLVAeUOoukH6ysF1BQN87525T7o/MA6oWQ06',NULL,0,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,'nttruyn1',NULL,NULL),(89,'Huỳnh Bảnh','nguyenhuynhtk37@gmail.com',NULL,'/users/avatars/785e94d8c028062dacf490f32f73ef58.png',1,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,'',NULL,NULL);
/*!40000 ALTER TABLE `tbluser` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbluseractivelessonbycourse`
--

DROP TABLE IF EXISTS `tbluseractivelessonbycourse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbluseractivelessonbycourse` (
  `userId` int NOT NULL,
  `courseId` int NOT NULL,
  `lessonId` int DEFAULT NULL,
  `groupId` int DEFAULT NULL,
  PRIMARY KEY (`userId`,`courseId`),
  KEY `tblUserActiveLessonByCourse_tblLectureDetails_FK` (`lessonId`),
  KEY `tblUserActiveLessonByCourse_tblCourse_FK` (`courseId`),
  KEY `tblUserActiveLessonByCourse_tblLessonGroup_FK` (`groupId`),
  CONSTRAINT `tblUserActiveLessonByCourse_tblCourse_FK` FOREIGN KEY (`courseId`) REFERENCES `tblcourse` (`id`),
  CONSTRAINT `tblUserActiveLessonByCourse_tblLectureDetails_FK` FOREIGN KEY (`lessonId`) REFERENCES `tbllecturedetails` (`id`),
  CONSTRAINT `tblUserActiveLessonByCourse_tblLessonGroup_FK` FOREIGN KEY (`groupId`) REFERENCES `tbllessongroup` (`id`),
  CONSTRAINT `tblUserActiveLessonByCourse_tblUser_FK` FOREIGN KEY (`userId`) REFERENCES `tbluser` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbluseractivelessonbycourse`
--

LOCK TABLES `tbluseractivelessonbycourse` WRITE;
/*!40000 ALTER TABLE `tbluseractivelessonbycourse` DISABLE KEYS */;
INSERT INTO `tbluseractivelessonbycourse` VALUES (36,3,33,1);
/*!40000 ALTER TABLE `tbluseractivelessonbycourse` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblusercodes`
--

DROP TABLE IF EXISTS `tblusercodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblusercodes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `code_id` int DEFAULT NULL,
  `used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `code_id` (`code_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `tblUserCodes_ibfk_1` FOREIGN KEY (`code_id`) REFERENCES `tblcode` (`id`),
  CONSTRAINT `tblUserCodes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `tbluser` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblusercodes`
--

LOCK TABLES `tblusercodes` WRITE;
/*!40000 ALTER TABLE `tblusercodes` DISABLE KEYS */;
/*!40000 ALTER TABLE `tblusercodes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tblusercompletelesson`
--

DROP TABLE IF EXISTS `tblusercompletelesson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tblusercompletelesson` (
  `userId` int NOT NULL,
  `lessonId` int NOT NULL,
  `courseId` int NOT NULL,
  `createAt` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`userId`,`lessonId`),
  KEY `tblUserCompleteLesson_tblLectureDetails_FK` (`lessonId`),
  KEY `tblUserCompleteLesson_tblCourse_FK` (`courseId`),
  CONSTRAINT `tblUserCompleteLesson_tblCourse_FK` FOREIGN KEY (`courseId`) REFERENCES `tblcourse` (`id`),
  CONSTRAINT `tblUserCompleteLesson_tblLectureDetails_FK` FOREIGN KEY (`lessonId`) REFERENCES `tbllecturedetails` (`id`),
  CONSTRAINT `tblUserCompleteLesson_tblUser_FK` FOREIGN KEY (`userId`) REFERENCES `tbluser` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tblusercompletelesson`
--

LOCK TABLES `tblusercompletelesson` WRITE;
/*!40000 ALTER TABLE `tblusercompletelesson` DISABLE KEYS */;
INSERT INTO `tblusercompletelesson` VALUES (32,33,3,'2025-05-13 01:30:48'),(32,49,3,'2025-05-06 16:26:03'),(32,53,3,'2025-05-10 14:52:10'),(32,58,3,'2025-05-10 14:51:45'),(32,61,42,'2025-05-13 01:22:53'),(36,48,3,'2025-05-08 12:51:50'),(36,49,3,'2025-05-08 16:52:14'),(36,58,3,'2025-05-13 15:08:09'),(36,59,42,'2025-05-13 11:07:03'),(36,61,42,'2025-05-13 16:22:19');
/*!40000 ALTER TABLE `tblusercompletelesson` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-25 11:30:24
