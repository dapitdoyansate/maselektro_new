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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




DROP TABLE IF EXISTS orders;

CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `total_bayar` decimal(10,2) NOT NULL,
  `tanggal_transaksi` date NOT NULL,
  `status` enum('pending','success','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO orders VALUES("1","1","200000.00","2025-06-12","success","2026-02-24 09:06:24");
INSERT INTO orders VALUES("2","2","98000.00","2026-01-20","pending","2026-02-24 09:06:24");
INSERT INTO orders VALUES("3","3","120000.00","2026-01-13","cancelled","2026-02-24 09:06:24");



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

INSERT INTO products VALUES("1","Asus Vivobook Go 14","5889000.00","234","Laptop ringan untuk produktivitas sehari-hari","default.jpg","2026-02-24 08:53:11");
INSERT INTO products VALUES("2","Camera Canon G7X","6200000.00","189","Kamera mirrorless berkualitas tinggi","default.jpg","2026-02-24 08:53:11");
INSERT INTO products VALUES("3","Earbuds Ultra Comfort BOSE","4990000.00","336","Earbuds dengan noise cancellation terbaik","default.jpg","2026-02-24 08:53:11");



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




DROP TABLE IF EXISTS users;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','petugas','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO users VALUES("1","admin","admin@maselektro.com","$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi","admin","2026-02-24 08:19:52");
INSERT INTO users VALUES("2","masudgaming","masudgaming@gmail.com","$2y$10$.LmJVd35GMJkG55uvMRIwOaoF6E5xTxDKK2YSt0i9YLoA10FJO3Yu","user","2026-02-24 10:29:53");



