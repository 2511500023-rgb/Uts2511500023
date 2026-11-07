-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 03, 2026 at 07:13 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_kafe_pos`
--

-- --------------------------------------------------------

--
-- Table structure for table `detail_transaksi`
--

CREATE TABLE `detail_transaksi` (
  `id` int NOT NULL,
  `transaksi_id` int NOT NULL,
  `produk_id` int NOT NULL,
  `jumlah` int NOT NULL,
  `harga_satuan` int NOT NULL,
  `subtotal` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` int NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `created_at` date DEFAULT (curdate())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`, `created_at`) VALUES
(1, 'General Coffee', '2026-11-04'),
(2, 'Signature Coffee', '2026-11-04'),
(3, 'Mocktail', '2026-11-04'),
(4, 'Yakult Series', '2026-11-04'),
(5, 'Pure Refreshment', '2026-11-04'),
(6, 'Milk Based Non-Coffee', '2026-11-04'),
(7, 'Food', '2026-11-04');

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `id` int NOT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `harga` int NOT NULL,
  `gambar` varchar(255) DEFAULT 'default.jpg',
  `kategori_id` int NOT NULL,
  `created_at` date DEFAULT (curdate())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`id`, `nama_produk`, `harga`, `gambar`, `kategori_id`, `created_at`) VALUES
(1, 'Roots Latte (Hot)', 17000, 'default.jpg', 1, '2026-11-04'),
(2, 'Roots Latte (Ice)', 20000, 'default.jpg', 1, '2026-11-04'),
(3, 'LaRoots. Latte', 22000, 'default.jpg', 1, '2026-11-04'),
(4, 'LaRoots. Palm Sugar', 22000, 'default.jpg', 1, '2026-11-04'),
(5, 'Latte (Ice)', 22000, 'default.jpg', 1, '2026-11-04'),
(6, 'Caramel Latte', 22000, 'default.jpg', 1, '2026-11-04'),
(7, 'Hazelnut Latte', 22000, 'default.jpg', 1, '2026-11-04'),
(8, 'Minty Latte', 22000, 'default.jpg', 1, '2026-11-04'),
(9, 'Americano (Hot)', 13000, 'default.jpg', 1, '2026-11-04'),
(10, 'Americano (Ice)', 17000, 'default.jpg', 1, '2026-11-04'),
(11, 'Berry Cream Top', 23000, 'default.jpg', 2, '2026-11-04'),
(12, 'Salted Cream Top', 23000, 'default.jpg', 2, '2026-11-04'),
(13, 'Lecy Dream', 23000, 'default.jpg', 2, '2026-11-04'),
(14, 'Bomb Fresh', 23000, 'default.jpg', 2, '2026-11-04'),
(15, 'Black Lemonade', 23000, 'default.jpg', 2, '2026-11-04'),
(16, 'Redlips', 23000, 'default.jpg', 2, '2026-11-04'),
(17, 'Salty Midnight Cloud', 24000, 'default.jpg', 2, '2026-11-04'),
(18, 'Blossom Midnight Cloud', 24000, 'default.jpg', 2, '2026-11-04'),
(19, 'Bitter Midnight Cloud', 24000, 'default.jpg', 2, '2026-11-04'),
(20, 'Sweet Caffeine', 25000, 'default.jpg', 2, '2026-11-04'),
(21, 'Americorn Pop', 25000, 'default.jpg', 2, '2026-11-04'),
(22, 'Pink Lady', 23000, 'default.jpg', 3, '2026-11-04'),
(23, 'Night Furry', 23000, 'default.jpg', 3, '2026-11-04'),
(24, 'Aqua Marine', 23000, 'default.jpg', 3, '2026-11-04'),
(25, 'Virgin Mint\'s', 23000, 'default.jpg', 3, '2026-11-04'),
(26, 'Strawberry Yakult Sq', 19000, 'default.jpg', 4, '2026-11-04'),
(27, 'Orange Yakult Sq', 19000, 'default.jpg', 4, '2026-11-04'),
(28, 'Manggo Yakult Sq', 19000, 'default.jpg', 4, '2026-11-04'),
(29, 'Lychee Yakult Sq', 19000, 'default.jpg', 4, '2026-11-04'),
(30, 'LemonTi (Hot)', 13000, 'default.jpg', 5, '2026-11-04'),
(31, 'LemonTi (Ice)', 15000, 'default.jpg', 5, '2026-11-04'),
(32, 'Lemonade (Hot)', 15000, 'default.jpg', 5, '2026-11-04'),
(33, 'Lemonade (Ice)', 17000, 'default.jpg', 5, '2026-11-04'),
(34, 'Sodasu (Ice)', 15000, 'default.jpg', 5, '2026-11-04'),
(35, 'Air Mineral', 5000, 'default.jpg', 5, '2026-11-04'),
(36, 'Chocolate', 20000, 'default.jpg', 6, '2026-11-04'),
(37, 'Choco Minty', 20000, 'default.jpg', 6, '2026-11-04'),
(38, 'Choco Caramel', 20000, 'default.jpg', 6, '2026-11-04'),
(39, 'Choco Hazelnut', 20000, 'default.jpg', 6, '2026-11-04'),
(40, 'Redvelved', 20000, 'default.jpg', 6, '2026-11-04'),
(41, 'Greentea', 20000, 'default.jpg', 6, '2026-11-04'),
(42, 'Milodino', 20000, 'default.jpg', 6, '2026-11-04'),
(43, 'Cookie Cream', 20000, 'default.jpg', 6, '2026-11-04'),
(44, 'Milk Minty', 20000, 'default.jpg', 6, '2026-11-04'),
(45, 'Milk Caramel', 20000, 'default.jpg', 6, '2026-11-04'),
(46, 'Milk Hazelnut', 20000, 'default.jpg', 6, '2026-11-04'),
(47, 'Pisang Goreng', 15000, 'default.jpg', 7, '2026-11-04'),
(48, 'Kentang Goreng', 15000, 'default.jpg', 7, '2026-11-04'),
(49, 'Cireng Salju', 15000, 'default.jpg', 7, '2026-11-04'),
(50, 'Mi Tektek', 15000, 'default.jpg', 7, '2026-11-04'),
(51, 'Nasi Goreng', 18000, 'default.jpg', 7, '2026-11-04'),
(52, 'Burger', 25000, 'default.jpg', 7, '2026-11-04');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int NOT NULL,
  `no_faktur` varchar(50) NOT NULL,
  `subtotal` int NOT NULL,
  `pajak` int NOT NULL,
  `total_bayar` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_detail_transaksi` (`transaksi_id`),
  ADD KEY `fk_detail_produk` (`produk_id`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_produk_kategori` (`kategori_id`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_faktur` (`no_faktur`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD CONSTRAINT `fk_detail_produk` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_transaksi` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `fk_produk_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
