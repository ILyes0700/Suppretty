-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 08, 2025 at 09:10 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `produits_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cards`
--

DROP TABLE IF EXISTS `cards`;
CREATE TABLE IF NOT EXISTS `cards` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cards`
--

INSERT INTO `cards` (`id`, `title`, `created_at`) VALUES
(7, 'zied', '2025-02-07 12:36:25'),
(11, 'adnen', '2025-02-07 18:14:27');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
CREATE TABLE IF NOT EXISTS `clients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `nom`, `created_at`) VALUES
(1, 'ilyes rejeb', '2025-02-06 13:49:56'),
(3, 'sami', '2025-02-06 13:50:52'),
(4, 'dali', '2025-02-06 13:51:04'),
(5, 'oussama hamdi', '2025-02-06 13:51:12'),
(6, 'ridha', '2025-02-06 13:51:20'),
(7, 'jassem', '2025-02-06 13:51:28'),
(8, 'islen', '2025-02-06 14:39:16'),
(9, 'isam', '2025-02-06 14:45:55'),
(10, 'ichrak', '2025-02-06 14:46:02'),
(11, 'ichgd', '2025-02-06 14:46:09'),
(12, 'kamel', '2025-02-06 20:52:01'),
(13, 'ahmed', '2025-02-06 22:11:12'),
(14, 'oussama', '2025-02-07 08:11:30'),
(15, 'ilyes rejeb', '2025-02-07 18:10:34'),
(16, 'adnen', '2025-02-07 18:11:03'),
(17, 'sihm', '2025-10-08 19:40:50');

-- --------------------------------------------------------

--
-- Table structure for table `commandes`
--

DROP TABLE IF EXISTS `commandes`;
CREATE TABLE IF NOT EXISTS `commandes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `commandes`
--

INSERT INTO `commandes` (`id`, `product_id`, `product_name`, `quantity`, `total_price`, `created_at`) VALUES
(1, 2, 'farina', 1, 1200.00, '2025-02-06 22:34:05'),
(6, 2, 'farina', 1, 1200.00, '2025-02-07 16:48:07'),
(7, 4, '7chicha', 1, 13.20, '2025-02-07 16:48:07'),
(8, 2, 'farina', 1, 1200.00, '2025-02-07 16:48:21'),
(9, 4, '7chicha', 1, 13.20, '2025-02-07 16:48:21'),
(10, 2, 'farina', 2, 2400.00, '2025-02-07 18:09:39'),
(11, 3, 'colgate', 3, 23400.00, '2025-02-07 19:08:36'),
(12, 2, 'farina', 2, 2400.00, '2025-02-07 19:08:36'),
(13, 4, '7chicha', 2, 26400.00, '2025-02-07 19:08:36'),
(14, 2, 'farina', 1, 1200.00, '2025-02-07 22:01:33'),
(15, 2, 'farina', 5, 6000.00, '2025-10-08 20:28:53');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
CREATE TABLE IF NOT EXISTS `items` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `card_id` int UNSIGNED NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `price_total` decimal(10,2) NOT NULL,
  `text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_card` (`card_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `card_id`, `quantity`, `unit_price`, `price_total`, `text`, `created_at`) VALUES
(9, 11, 4, 1200.00, 4800.00, 'farina', '2025-02-07 18:14:48'),
(10, 11, 2, 9700.00, 19400.00, '9az', '2025-02-07 18:15:05'),
(11, 11, 1, 1200.00, 1200.00, 'kabab', '2025-10-08 19:09:50');

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
CREATE TABLE IF NOT EXISTS `notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`id`, `client_id`, `note`) VALUES
(4, 4, 'slm'),
(9, 5, 'jab 9az far8'),
(10, 13, 'jab 9az far8'),
(11, 16, 'jab 9az far8'),
(15, 13, 'jab 150d');

-- --------------------------------------------------------

--
-- Table structure for table `notess`
--

DROP TABLE IF EXISTS `notess`;
CREATE TABLE IF NOT EXISTS `notess` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notess`
--

INSERT INTO `notess` (`id`, `content`, `created_at`, `updated_at`) VALUES
(7, 'bonjour,', '2025-02-07 09:10:41', '2025-02-07 09:10:41'),
(8, 'slm', '2025-02-07 15:17:04', '2025-02-07 15:17:04'),
(10, 'jjj', '2025-02-07 18:01:30', '2025-02-07 18:01:30'),
(11, 'jehfjenf jek', '2025-02-07 19:13:55', '2025-02-07 19:13:55'),
(13, 'taw', '2025-10-08 20:08:36', '2025-10-08 20:08:36');

-- --------------------------------------------------------

--
-- Table structure for table `panier`
--

DROP TABLE IF EXISTS `panier`;
CREATE TABLE IF NOT EXISTS `panier` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `panier`
--

INSERT INTO `panier` (`id`, `client_id`, `montant`, `date`) VALUES
(35, 13, 0.01, '2025-02-06 22:11:54'),
(36, 5, 1200.00, '2025-02-07 08:12:17'),
(37, 5, 520.00, '2025-02-07 08:12:30'),
(38, 13, 2600.00, '2025-02-07 14:32:38'),
(43, 16, 1200.00, '2025-10-08 19:10:18'),
(44, 16, 1400.00, '2025-10-08 19:10:24'),
(45, 16, 200.00, '2025-10-08 19:10:32'),
(46, 13, 1200.00, '2025-10-08 19:38:59'),
(47, 13, 0.00, '2025-10-08 19:39:01'),
(48, 13, 0.00, '2025-10-08 19:39:02'),
(49, 13, 0.00, '2025-10-08 19:39:03'),
(50, 13, 0.00, '2025-10-08 19:39:03'),
(51, 13, 0.00, '2025-10-08 19:39:04'),
(52, 13, 0.00, '2025-10-08 19:39:04');

-- --------------------------------------------------------

--
-- Table structure for table `produits`
--

DROP TABLE IF EXISTS `produits`;
CREATE TABLE IF NOT EXISTS `produits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `image` varchar(255) NOT NULL,
  `codeqr` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `produits`
--

INSERT INTO `produits` (`id`, `nom`, `prix`, `image`, `codeqr`) VALUES
(1, 'mechoir', 250.00, 'uploads/téléchargement (2).jpg', '6192011803672'),
(2, 'farina', 1200.00, 'uploads/téléchargement.webp', 'WIFI:T:WPA;P:changeme;S:globalnet2;H:false;'),
(3, 'colgate', 7800.00, 'uploads/67a3ce7e0a324.png', '7891024133545'),
(4, '7chicha', 13200.00, 'uploads/67a4aea1a54ed.jpg', '4032489010702'),
(5, 'ilyes', 1200.00, 'uploads/68e6b5b771be9.png', 'WIFI:T:WPA;P:changeme;S:globalnet2;H:false;');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `fk_card` FOREIGN KEY (`card_id`) REFERENCES `cards` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);

--
-- Constraints for table `panier`
--
ALTER TABLE `panier`
  ADD CONSTRAINT `panier_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
