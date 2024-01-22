-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 19, 2022 at 07:54 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.1.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `betshopme_8`
--

-- --------------------------------------------------------

--
-- Table structure for table `w_game_categories`
--

CREATE TABLE `w_game_categories` (
  `id` int(55) NOT NULL,
  `game_id` int(55) NOT NULL,
  `category_id` int(55) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `w_game_categories`
--

INSERT INTO `w_game_categories` (`id`, `game_id`, `category_id`) VALUES
(1017, 892, 24),
(1018, 893, 24),
(1019, 938, 24),
(1020, 939, 24),
(1021, 977, 24),
(1022, 976, 24),
(1023, 975, 24),
(1024, 974, 24),
(1025, 972, 24),
(1026, 971, 24),
(1027, 992, 24),
(1028, 993, 24),
(1029, 994, 24),
(1030, 995, 24),
(1031, 990, 24),
(1032, 991, 24),
(1033, 980, 24),
(1034, 981, 24),
(1035, 988, 24),
(1036, 989, 24),
(1037, 1000, 24),
(1038, 986, 24),
(1039, 987, 24),
(1041, 983, 24),
(1042, 998, 24),
(1043, 999, 24),
(1044, 996, 24),
(1045, 997, 24),
(1046, 984, 24),
(1047, 985, 24),
(1048, 1001, 24),
(1049, 1004, 24),
(1050, 1003, 24),
(1051, 1002, 24),
(1052, 1014, 24),
(1053, 1017, 24),
(1054, 1030, 24),
(1055, 1031, 24),
(1056, 1032, 24),
(1057, 1033, 24),
(1058, 1034, 24),
(1059, 1035, 24),
(1095, 1036, 24),
(1096, 1037, 24),
(1097, 1038, 24),
(1098, 1039, 24),
(1099, 1040, 24),
(1100, 1048, 24),
(1103, 1051, 24),
(1104, 1052, 24),
(1105, 1053, 24),
(1106, 1054, 24),
(1110, 1058, 24),
(1111, 1059, 24),
(1251, 1049, 24),
(1435, 982, 24),
(1452, 1055, 24),
(1519, 1050, 24);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `w_game_categories`
--
ALTER TABLE `w_game_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `game_id` (`game_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `w_game_categories`
--
ALTER TABLE `w_game_categories`
  MODIFY `id` int(55) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1548;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
-- Dumping data for table `w_categories`
--

INSERT INTO `w_categories` (`id`, `title`, `parent`, `position`, `href`, `original_id`, `shop_id`) VALUES
(24, 'Arcade', 0, 1000, 'arcade', 0, 0);

--
-- Indexes for dumped tables