DROP TABLE IF EXISTS backup_history;

CREATE TABLE `backup_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `file_size` decimal(10,2) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` time NOT NULL,
  `status` varchar(50) DEFAULT 'Success',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO backup_history VALUES("5","backup_2026-02-24_07-43-36.sql","0.00","2026-02-24","07:43:00","Success","2026-02-24 13:43:36");
INSERT INTO backup_history VALUES("6","backup_2026-04-07_05-34-25.sql","0.01","2026-04-07","05:34:00","Success","2026-04-07 10:34:25");



DROP TABLE IF EXISTS cart;

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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO cart VALUES("3","4","3","1","2026-03-31 08:11:00");
INSERT INTO cart VALUES("6","5","3","1","2026-04-06 10:55:19");



DROP TABLE IF EXISTS kategori;

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




DROP TABLE IF EXISTS order_details;

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO order_details VALUES("1","4","1","1","5889000.00","5889000.00");
INSERT INTO order_details VALUES("2","4","2","1","6200000.00","6200000.00");
INSERT INTO order_details VALUES("3","6","1","1","5889000.00","5889000.00");
INSERT INTO order_details VALUES("4","6","2","1","6200000.00","6200000.00");
INSERT INTO order_details VALUES("5","8","3","1","4990000.00","4990000.00");
INSERT INTO order_details VALUES("6","8","2","1","6200000.00","6200000.00");
INSERT INTO order_details VALUES("7","8","1","1","5889000.00","5889000.00");
INSERT INTO order_details VALUES("8","9","3","2","4990000.00","9980000.00");
INSERT INTO order_details VALUES("9","10","4","1","2500000.00","2500000.00");
INSERT INTO order_details VALUES("10","10","3","1","7000000.00","7000000.00");



DROP TABLE IF EXISTS orders;

CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `total_bayar` decimal(10,2) NOT NULL,
  `ongkir` decimal(10,2) DEFAULT 0.00,
  `nama_penerima` varchar(100) NOT NULL,
  `no_telp` varchar(20) NOT NULL,
  `alamat` text NOT NULL,
  `metode_pembayaran` enum('m-banking','e-wallet','cod') DEFAULT 'm-banking',
  `virtual_account` varchar(20) DEFAULT NULL,
  `detail_pembayaran` varchar(100) DEFAULT NULL,
  `tanggal_transaksi` date NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `status` enum('pending','diproses','dikirim','selesai','dibatalkan') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO orders VALUES("1","4","200000.00","0.00","","","","m-banking","VA1","","2025-06-12","2026-04-07 11:00:38","pending","2026-02-24 09:06:24");
INSERT INTO orders VALUES("2","5","98000.00","0.00","","","","m-banking","VA2","","2026-01-20","2026-04-07 11:00:38","pending","2026-02-24 09:06:24");
INSERT INTO orders VALUES("3","4","120000.00","0.00","","","","m-banking","VA3","","2026-01-13","2026-04-07 11:00:38","pending","2026-02-24 09:06:24");
INSERT INTO orders VALUES("4","8","12089000.00","0.00","masud","0808080808","jl.masud","m-banking","VA4","","2026-04-07","2026-04-07 13:33:01","","2026-04-07 08:16:32");
INSERT INTO orders VALUES("5","8","0.00","0.00","dadang","0808080808","jl.masud","m-banking","VA5","","2026-04-07","2026-04-07 11:00:38","pending","2026-04-07 08:27:47");
INSERT INTO orders VALUES("6","8","12089000.00","0.00","dadang","0808080808","JL.HAJI MASUD","m-banking","VA6","","2026-04-07","2026-04-07 11:00:38","pending","2026-04-07 08:36:58");
INSERT INTO orders VALUES("7","8","12089000.00","0.00","dadang","0808080808","JL.MASUD","m-banking","VA7","","2026-04-07","2026-04-07 11:00:38","pending","2026-04-07 08:46:10");
INSERT INTO orders VALUES("8","8","17079000.00","0.00","MUKIP","087858127236","JL,SERONG CIPAYUNG KEC,PACORAN MAS DEPOK","m-banking","VA8","","2026-04-07","2026-04-07 11:00:38","pending","2026-04-07 08:47:13");
INSERT INTO orders VALUES("9","8","9980000.00","0.00","MUKIP","0808080808","jl.masud","","VA9","","2026-04-07","2026-04-07 11:00:38","pending","2026-04-07 10:16:33");
INSERT INTO orders VALUES("10","8","9500000.00","0.00","masud","0808080808","jl,beji","","7001500010","","2026-04-07","2026-04-07 11:24:33","","2026-04-07 11:24:33");



DROP TABLE IF EXISTS products;

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

INSERT INTO products VALUES("1","Asus Vivobook Go 14","5889000.00","30","Ringan dan Ringkas, ini adalah ASUS Vivobook Go 14, laptop yang dirancang untuk membuat para pelajar lebih produktif dan tetap terhibur dimanapun! Dengan engsel lay-flat 180°, pelindung webcam fisik, dan banyak fitur desain yang cermat, Vivobook Go 14 adalah laptop yang membebaskan Anda!","1775533974_vivobook_go_14_e1404f_e1404g_product_photo_1k_mixed_black_13_fingerprint_backlit_1-removebg-preview.png","2026-02-24 08:53:11");
INSERT INTO products VALUES("2","Apple MacBook Pro 14 inch","42355380.00","15","- M2 PRO 16/512GB\n12-Core CPU\n19-Core GPU\n16GB Unified Memory\n512GB SSD Storage\n\n16-core Neural Engine\n16-inch Liquid Retina XDR display²\nThree Thunderbolt 4 ports, HDMI port, SDXC card slot, headphone jack, MagSafe 3 port\nMagic Keyboard with Touch ID\nForce Touch trackpad\n140W USB-C Power Adapter","1775535420_311-removebg-preview.png","2026-02-24 08:53:11");
INSERT INTO products VALUES("3","iPad Mini 7","7000000.00","19","Menampilkan audio spasial yang inovatif, peredam kebisingan kelas dunia, dan teknologi CustomTune yang menyesuaikan suara dan keheningan untuk Anda, earbud in-ear premium kami terasa senyaman suaranya. Nyaman dan aman, earbud ini hadir dengan sembilan kombinDeskripsi ProdukApple iPad Mini 7 Tablet✨ Tablet dengan Layar Warna-warni ✨ Apple iPad Mini 7 Tablet dilengkapi dengan layar warna-warni yang menawarkan pengalaman visual yang menarik. Layar ini memungkinkan Anda menikmati konten dengan warna yang hidup dan jelas. Desain minimalis tablet ini membuatnya mudah digunakan dan cocok untuk berbagai keperluan.\nDesain Minimalis\nDesain yang Minimalis : iPad Mini 7 memiliki desain yang simpel dan modern.\nFungsionalitas: Desain ini memudahkan penggunaan sehari-hari tanpa mengorbankan gaya.\nSpesifikasi\nLayar Warna-warni: Menyediakan tampilan yang menarik dan jelas.\nDesain Minimalis: Cocok untuk berbagai keperluan pengguna.\nApple iPad Mini 7 Tablet adalah pilihan yang tepat untuk Anda yang mencari tablet dengan layar warna-warni dan desain minimalis.","1775535557_ipad-mini-finish-unselect-gallery-1-202410-removebg-preview.png","2026-02-24 08:53:11");
INSERT INTO products VALUES("4","POCO X3 NFC","2500000.00","4","Xiaomi Poco X3 NFC adalah smartphone kelas menengah dari Xiaomi sub-brand POCO yang terkenal dengan spesifikasi tinggi seperti layar 120Hz, chipset Snapdragon 732G, kamera utama 64MP, baterai 5160mAh dengan fast charging 33W, dual stereo speaker, serta adanya fitur NFC","1775533951_Poco-X3-NFC-664-600x600-removebg-preview.png","2026-04-07 10:38:32");



DROP TABLE IF EXISTS produk;

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




DROP TABLE IF EXISTS restore_history;

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




DROP TABLE IF EXISTS transaksi;

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




DROP TABLE IF EXISTS users;

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

INSERT INTO users VALUES("1","admin","0192023a7bbd73250516f069df18b500","Administrator","","","","admin","2026-02-24 14:19:42");
INSERT INTO users VALUES("2","petugas","5f4dcc3b5aa765d61d8327deb882cf99","Staff Kasir","","","","petugas","2026-02-24 14:19:42");
INSERT INTO users VALUES("3","kasir2","827ccb0eea8a706c4c34a16891f84e7b","Kasir 2","","","","petugas","2026-02-24 14:19:42");
INSERT INTO users VALUES("4","masud","f4ad231214cb99a985dff0f056a36242","masud23","","","","user","2026-02-24 14:19:42");
INSERT INTO users VALUES("5","customer","f4ad231214cb99a985dff0f056a36242","Customer Demo","","","","user","2026-04-06 07:41:29");
INSERT INTO users VALUES("8","bayu","$2y$10$i2pT4.0XatQJZ7wDfwDgIePXy1COntvIL9udM7Jvn4c.T0GHW.jke","","bayu@gmail.com","","","user","2026-04-07 06:57:54");



