-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: ecommerce_david_enh
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `backup_history`
--

DROP TABLE IF EXISTS `backup_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backup_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `file_size` decimal(10,2) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` time NOT NULL,
  `status` varchar(50) DEFAULT 'Success',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backup_history`
--

LOCK TABLES `backup_history` WRITE;
/*!40000 ALTER TABLE `backup_history` DISABLE KEYS */;
INSERT INTO `backup_history` VALUES (5,'backup_2026-02-24_07-43-36.sql',0.00,'2026-02-24','07:43:00','Success','2026-02-24 06:43:36'),(6,'backup_2026-04-07_05-34-25.sql',0.01,'2026-04-07','05:34:00','Success','2026-04-07 03:34:25'),(7,'backup_2026-04-07_08-50-48.sql',0.01,'2026-04-07','08:50:00','Success','2026-04-07 06:50:48'),(8,'backup_2026-04-08_05-55-16.sql',0.02,'2026-04-08','05:55:00','Success','2026-04-08 03:55:16'),(9,'backup_2026-04-08_08-56-53.sql',0.02,'2026-04-08','08:56:00','Success','2026-04-08 06:56:53');
/*!40000 ALTER TABLE `backup_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
INSERT INTO `cart` VALUES (3,4,3,1,'2026-03-31 01:11:00'),(6,5,3,1,'2026-04-06 03:55:19');
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kategori`
--

DROP TABLE IF EXISTS `kategori`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kategori` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kategori`
--

LOCK TABLES `kategori` WRITE;
/*!40000 ALTER TABLE `kategori` DISABLE KEYS */;
/*!40000 ALTER TABLE `kategori` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_details`
--

DROP TABLE IF EXISTS `order_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `harga_satuan` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_details`
--

LOCK TABLES `order_details` WRITE;
/*!40000 ALTER TABLE `order_details` DISABLE KEYS */;
INSERT INTO `order_details` VALUES (1,4,1,1,5889000.00,5889000.00),(2,4,2,1,6200000.00,6200000.00),(3,6,1,1,5889000.00,5889000.00),(4,6,2,1,6200000.00,6200000.00),(5,8,3,1,4990000.00,4990000.00),(6,8,2,1,6200000.00,6200000.00),(7,8,1,1,5889000.00,5889000.00),(8,9,3,2,4990000.00,9980000.00),(9,10,4,1,2500000.00,2500000.00),(10,10,3,1,7000000.00,7000000.00),(11,11,2,1,42355380.00,42355380.00),(12,11,3,1,7000000.00,7000000.00),(13,11,4,1,2500000.00,2500000.00),(14,12,2,1,42355380.00,42355380.00),(15,12,3,1,7000000.00,7000000.00),(16,12,4,2,2500000.00,5000000.00),(17,13,4,1,2500000.00,2500000.00),(18,13,3,1,7000000.00,7000000.00),(19,14,3,1,7000000.00,7000000.00),(20,15,4,1,2500000.00,2500000.00),(21,16,3,1,7000000.00,7000000.00),(22,17,2,2,42355380.00,84710760.00),(23,18,2,1,42355380.00,42355380.00),(24,19,1,1,5889000.00,5889000.00),(25,20,2,1,42355380.00,42355380.00),(26,21,2,1,42355380.00,42355380.00),(27,23,2,1,42355380.00,42355380.00),(28,24,1,1,5889000.00,5889000.00),(29,26,4,1,2500000.00,2500000.00);
/*!40000 ALTER TABLE `order_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `total_bayar` decimal(10,2) NOT NULL,
  `ongkir` decimal(10,2) DEFAULT 0.00,
  `nama_penerima` varchar(100) NOT NULL,
  `no_telp` varchar(20) NOT NULL,
  `alamat` text NOT NULL,
  `metode_pembayaran` varchar(50) NOT NULL DEFAULT 'm-banking',
  `virtual_account` varchar(20) DEFAULT NULL,
  `detail_pembayaran` varchar(100) DEFAULT NULL,
  `tanggal_transaksi` date NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,4,200000.00,0.00,'','','','m-banking','VA1',NULL,'2025-06-12','2026-04-07 04:00:38','pending','2026-02-24 02:06:24'),(2,5,98000.00,0.00,'','','','m-banking','VA2',NULL,'2026-01-20','2026-04-07 04:00:38','pending','2026-02-24 02:06:24'),(3,4,120000.00,0.00,'','','','m-banking','VA3',NULL,'2026-01-13','2026-04-07 04:00:38','pending','2026-02-24 02:06:24'),(4,8,12089000.00,0.00,'masud','0808080808','jl.masud','m-banking','VA4',NULL,'2026-04-07','2026-04-07 06:33:01','','2026-04-07 01:16:32'),(5,8,0.00,0.00,'dadang','0808080808','jl.masud','m-banking','VA5',NULL,'2026-04-07','2026-04-08 06:39:52','paid','2026-04-07 01:27:47'),(6,8,12089000.00,0.00,'dadang','0808080808','JL.HAJI MASUD','m-banking','VA6',NULL,'2026-04-07','2026-04-08 06:39:57','paid','2026-04-07 01:36:58'),(7,8,12089000.00,0.00,'dadang','0808080808','JL.MASUD','m-banking','VA7',NULL,'2026-04-07','2026-04-07 04:00:38','pending','2026-04-07 01:46:10'),(8,8,17079000.00,0.00,'MUKIP','087858127236','JL,SERONG CIPAYUNG KEC,PACORAN MAS DEPOK','m-banking','VA8',NULL,'2026-04-07','2026-04-07 04:00:38','pending','2026-04-07 01:47:13'),(9,8,9980000.00,0.00,'MUKIP','0808080808','jl.masud','','VA9',NULL,'2026-04-07','2026-04-07 04:00:38','pending','2026-04-07 03:16:33'),(10,8,9500000.00,0.00,'masud','0808080808','jl,beji','','7001500010',NULL,'2026-04-07','2026-04-07 04:24:33','','2026-04-07 04:24:33'),(11,8,51855380.00,0.00,'labib','089979795','jl.labib maulana firman','','7001500011',NULL,'2026-04-08','2026-04-08 06:08:42','paid','2026-04-08 00:39:03'),(12,8,54355380.00,0.00,'permana','08080808','jl,serong','','9007700012',NULL,'2026-04-08','2026-04-08 06:08:42','paid','2026-04-08 01:05:16'),(13,8,9500000.00,0.00,'permana','08080808','jl,serong','','9007700013',NULL,'2026-04-08','2026-04-08 06:08:42','paid','2026-04-08 01:12:28'),(14,8,7000000.00,0.00,'permana','08080808','jl,serong','','9008800014',NULL,'2026-04-08','2026-04-08 06:08:42','paid','2026-04-08 01:17:14'),(15,8,2500000.00,0.00,'permana','08080808','jl,serong','','9007700015',NULL,'2026-04-08','2026-04-08 06:08:42','paid','2026-04-08 01:31:26'),(16,8,7000000.00,0.00,'permana','08080808','jl,serong','','9007700016',NULL,'2026-04-08','2026-04-08 06:08:42','paid','2026-04-08 01:34:30'),(17,8,84710760.00,0.00,'permana','08080808','jl,serong','','9008800017',NULL,'2026-04-08','2026-04-08 06:08:42','paid','2026-04-08 01:41:36'),(18,8,42355380.00,0.00,'permana','08080808','jl,serong','','9007700018',NULL,'2026-04-08','2026-04-08 06:08:42','paid','2026-04-08 01:55:13'),(19,8,5889000.00,0.00,'permana','08080808','jl,serong','','9007700019',NULL,'2026-04-08','2026-04-08 06:08:42','paid','2026-04-08 01:57:42'),(20,8,42355380.00,0.00,'permana','08080808','jl,serong','',NULL,NULL,'2026-04-08','2026-04-08 06:08:42','paid','2026-04-08 02:41:47'),(21,8,42355380.00,0.00,'permana','08080808','jl,serong','',NULL,NULL,'2026-04-08','2026-04-08 06:08:42','paid','2026-04-08 02:46:53'),(22,8,42355380.00,0.00,'permana','08080808','jl,serong','',NULL,NULL,'2026-04-08','2026-04-08 06:08:42','paid','2026-04-08 02:53:25'),(23,8,42355380.00,0.00,'permana','08080808','jl,serong','E-Wallet (Dana)',NULL,NULL,'2026-04-08','2026-04-08 06:08:42','paid','2026-04-08 03:01:23'),(24,8,5889000.00,0.00,'permana','08080808','jl,serong','E-Wallet (Dana)',NULL,NULL,'2026-04-08','2026-04-08 06:08:42','paid','2026-04-08 03:09:20'),(25,8,5889000.00,0.00,'permana','08080808','jl,serong','E-Wallet (Dana)',NULL,NULL,'2026-04-08','2026-04-08 06:08:42','paid','2026-04-08 03:13:20'),(26,8,2500000.00,0.00,'permana','08080808','jl,serong','E-Wallet (GoPay)',NULL,NULL,'2026-04-08','2026-04-08 06:08:42','paid','2026-04-08 03:17:11');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_produk` varchar(255) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int(11) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT 'default.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'Asus Vivobook Go 14',5889000.00,28,'Ringan dan Ringkas, ini adalah ASUS Vivobook Go 14, laptop yang dirancang untuk membuat para pelajar lebih produktif dan tetap terhibur dimanapun! Dengan engsel lay-flat 180°, pelindung webcam fisik, dan banyak fitur desain yang cermat, Vivobook Go 14 adalah laptop yang membebaskan Anda!','1775533974_vivobook_go_14_e1404f_e1404g_product_photo_1k_mixed_black_13_fingerprint_backlit_1-removebg-preview.png','2026-02-24 01:53:11'),(2,'Apple MacBook Pro 14 inch',42355380.00,7,'- M2 PRO 16/512GB\r\n12-Core CPU\r\n19-Core GPU\r\n16GB Unified Memory\r\n512GB SSD Storage\r\n\r\n16-core Neural Engine\r\n16-inch Liquid Retina XDR display²\r\nThree Thunderbolt 4 ports, HDMI port, SDXC card slot, headphone jack, MagSafe 3 port\r\nMagic Keyboard with Touch ID\r\nForce Touch trackpad\r\n140W USB-C Power Adapter','1775535420_311-removebg-preview.png','2026-02-24 01:53:11'),(3,'iPad Mini 7',7000000.00,14,'Menampilkan audio spasial yang inovatif, peredam kebisingan kelas dunia, dan teknologi CustomTune yang menyesuaikan suara dan keheningan untuk Anda, earbud in-ear premium kami terasa senyaman suaranya. Nyaman dan aman, earbud ini hadir dengan sembilan kombinDeskripsi ProdukApple iPad Mini 7 Tablet✨ Tablet dengan Layar Warna-warni ✨ Apple iPad Mini 7 Tablet dilengkapi dengan layar warna-warni yang menawarkan pengalaman visual yang menarik. Layar ini memungkinkan Anda menikmati konten dengan warna yang hidup dan jelas. Desain minimalis tablet ini membuatnya mudah digunakan dan cocok untuk berbagai keperluan.\r\nDesain Minimalis\r\nDesain yang Minimalis : iPad Mini 7 memiliki desain yang simpel dan modern.\r\nFungsionalitas: Desain ini memudahkan penggunaan sehari-hari tanpa mengorbankan gaya.\r\nSpesifikasi\r\nLayar Warna-warni: Menyediakan tampilan yang menarik dan jelas.\r\nDesain Minimalis: Cocok untuk berbagai keperluan pengguna.\r\nApple iPad Mini 7 Tablet adalah pilihan yang tepat untuk Anda yang mencari tablet dengan layar warna-warni dan desain minimalis.','1775535557_ipad-mini-finish-unselect-gallery-1-202410-removebg-preview.png','2026-02-24 01:53:11'),(4,'POCO X3 NFC',2500000.00,3,'Xiaomi Poco X3 NFC adalah smartphone kelas menengah dari Xiaomi sub-brand POCO yang terkenal dengan spesifikasi tinggi seperti layar 120Hz, chipset Snapdragon 732G, kamera utama 64MP, baterai 5160mAh dengan fast charging 33W, dual stereo speaker, serta adanya fitur NFC','1775533951_Poco-X3-NFC-664-600x600-removebg-preview.png','2026-04-07 03:38:32');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produk`
--

DROP TABLE IF EXISTS `produk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_produk` varchar(200) NOT NULL,
  `kategori` varchar(100) DEFAULT NULL,
  `harga` int(11) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `deskripsi` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produk`
--

LOCK TABLES `produk` WRITE;
/*!40000 ALTER TABLE `produk` DISABLE KEYS */;
/*!40000 ALTER TABLE `produk` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `restore_history`
--

DROP TABLE IF EXISTS `restore_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `restore_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `file_size` decimal(10,2) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` time NOT NULL,
  `status` varchar(50) DEFAULT 'Success',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `restore_history`
--

LOCK TABLES `restore_history` WRITE;
/*!40000 ALTER TABLE `restore_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `restore_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaksi`
--

DROP TABLE IF EXISTS `transaksi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_produk` int(11) NOT NULL,
  `nama_pembeli` varchar(100) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `total_harga` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` time NOT NULL,
  `petugas` varchar(100) DEFAULT NULL,
  `status` enum('pending','success','cancelled') DEFAULT 'success',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_produk` (`id_produk`),
  CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaksi`
--

LOCK TABLES `transaksi` WRITE;
/*!40000 ALTER TABLE `transaksi` DISABLE KEYS */;
/*!40000 ALTER TABLE `transaksi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `role` enum('admin','petugas','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','0192023a7bbd73250516f069df18b500','Administrator',NULL,NULL,NULL,'admin','2026-02-24 07:19:42'),(2,'petugas','5f4dcc3b5aa765d61d8327deb882cf99','Staff Kasir',NULL,NULL,NULL,'petugas','2026-02-24 07:19:42'),(3,'kasir2','827ccb0eea8a706c4c34a16891f84e7b','Kasir 2',NULL,NULL,NULL,'petugas','2026-02-24 07:19:42'),(4,'masud','f4ad231214cb99a985dff0f056a36242','masud23','','','','user','2026-02-24 07:19:42'),(5,'customer','f4ad231214cb99a985dff0f056a36242','Customer Demo',NULL,NULL,NULL,'user','2026-04-06 00:41:29'),(8,'bayu','$2y$10$i2pT4.0XatQJZ7wDfwDgIePXy1COntvIL9udM7Jvn4c.T0GHW.jke','permana','bayu@gmail.com','08080808','jl,serong','user','2026-04-06 23:57:54');
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

-- Dump completed on 2026-04-09  8:36:25
