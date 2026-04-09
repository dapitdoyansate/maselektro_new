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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO backup_history VALUES("5","backup_2026-02-24_07-43-36.sql","0.00","2026-02-24","07:43:00","Success","2026-02-24 13:43:36");



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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO order_details VALUES("1","4","1","1","5889000.00","5889000.00");
INSERT INTO order_details VALUES("2","4","2","1","6200000.00","6200000.00");
INSERT INTO order_details VALUES("3","6","1","1","5889000.00","5889000.00");
INSERT INTO order_details VALUES("4","6","2","1","6200000.00","6200000.00");
INSERT INTO order_details VALUES("5","8","3","1","4990000.00","4990000.00");
INSERT INTO order_details VALUES("6","8","2","1","6200000.00","6200000.00");
INSERT INTO order_details VALUES("7","8","1","1","5889000.00","5889000.00");
INSERT INTO order_details VALUES("8","9","3","2","4990000.00","9980000.00");



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
  `detail_pembayaran` varchar(100) DEFAULT NULL,
  `tanggal_transaksi` date NOT NULL,
  `status` enum('pending','diproses','dikirim','selesai','dibatalkan') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO orders VALUES("1","4","200000.00","0.00","","","","m-banking","","2025-06-12","pending","2026-02-24 09:06:24");
INSERT INTO orders VALUES("2","5","98000.00","0.00","","","","m-banking","","2026-01-20","pending","2026-02-24 09:06:24");
INSERT INTO orders VALUES("3","4","120000.00","0.00","","","","m-banking","","2026-01-13","pending","2026-02-24 09:06:24");
INSERT INTO orders VALUES("4","8","12089000.00","0.00","masud","0808080808","jl.masud","m-banking","","2026-04-07","pending","2026-04-07 08:16:32");
INSERT INTO orders VALUES("5","8","0.00","0.00","dadang","0808080808","jl.masud","m-banking","","2026-04-07","pending","2026-04-07 08:27:47");
INSERT INTO orders VALUES("6","8","12089000.00","0.00","dadang","0808080808","JL.HAJI MASUD","m-banking","","2026-04-07","pending","2026-04-07 08:36:58");
INSERT INTO orders VALUES("7","8","12089000.00","0.00","dadang","0808080808","JL.MASUD","m-banking","","2026-04-07","pending","2026-04-07 08:46:10");
INSERT INTO orders VALUES("8","8","17079000.00","0.00","MUKIP","087858127236","JL,SERONG CIPAYUNG KEC,PACORAN MAS DEPOK","m-banking","","2026-04-07","pending","2026-04-07 08:47:13");
INSERT INTO orders VALUES("9","8","9980000.00","0.00","MUKIP","0808080808","jl.masud","","","2026-04-07","pending","2026-04-07 10:16:33");



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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO products VALUES("1","Asus Vivobook Go 14","5889000.00","231","Laptop ringan untuk produktivitas sehari-hari","default.jpg","2026-02-24 08:53:11");
INSERT INTO products VALUES("2","Camera Canon G7X","6200000.00","186","Kamera mirrorless berkualitas tinggi","default.jpg","2026-02-24 08:53:11");
INSERT INTO products VALUES("3","Earbuds Ultra Comfort BOSE","4990000.00","333","Earbuds dengan noise cancellation terbaik","default.jpg","2026-02-24 08:53:11");



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



