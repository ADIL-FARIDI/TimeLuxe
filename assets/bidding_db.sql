-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 05, 2025 at 08:06 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bidding_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `auctions`
--

CREATE TABLE `auctions` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `start_price` decimal(10,2) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('open','closed') DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auctions`
--

INSERT INTO `auctions` (`id`, `title`, `description`, `image`, `start_price`, `start_time`, `end_time`, `status`) VALUES
(18, 'Patek Philippe - PC', 'Perpetual Calendar Chronograph - 8/20, Limited Edition. \r\nThe gleam of platinum showcases the timeless design of this perpetual calendar chronograph with its concave bezel and elegantly fluted lugs.', 'PP-perpetual-calendar-chronograph.jpg', 2794000.00, '0000-00-00 00:00:00', '2025-10-10 12:00:00', 'open'),
(19, 'Richard Mille 002v', 'Richard Mille 002 - A manual winding tourbillon movement with hours, minutes, function selector, power-reserve and torque indicators.', 'richard-mille-rm-002.jpg', 51117000.00, '0000-00-00 00:00:00', '2025-10-20 10:00:00', 'open'),
(20, 'Richard Mille RM 57-01 Tourbillon Phoenix & Dragon Jackie Chan 2', 'Phoenix was known never to harm either a single insect nor blade of grass, eating and drinking nothing but bamboo seeds and sweet spring water. The dragon has always appeared in a magical variety of forms; long or short bodied, small or gigantic in size. Its nature could be both secretive yet active, and it inhabited all areas of the universe from the heights above to the depths below. Since they were able to travel between the skies and earth, dragons were considered the mounts of heavenly deities.\r\nLimited edition of 15 pieces', 'Richard-Mille-RM-57-01-Tourbillon-Phoenix-and-Dragon-Jackie-Chan-2.jpg', 69309100.00, '0000-00-00 00:00:00', '2025-12-01 00:00:00', 'open'),
(21, 'Richard Mille 65-01 Mclaren w1 Split seconds chronograph 05', 'This new type of balance at Richard Mille can measure accurately to 1/10th of a second, ideal for a split-seconds chronograph watch under sporting conditions.', 'richard-mille-rm-65-01-mclaren-w1-split-seconds-chronograph-05.jpg', 51418900.00, '0000-00-00 00:00:00', '2025-10-12 12:00:00', 'open'),
(22, 'Richard Mille Rock & Roll Revolution 66 Gold Titanium', 'The caseband is in grade 5 titanium with 5N red gold polished inserts. The tripartite case is water resistant to 50 metres, ensured by 2 Nitril O-ring seals.\r\nLimited edition of 50 pieces', 'rock-and-roll-revolution-richard-mille-s-rm-66-gold-titanium.jpg', 99999999.99, '0000-00-00 00:00:00', '2025-11-10 23:00:00', 'open');

-- --------------------------------------------------------

--
-- Table structure for table `bids`
--

CREATE TABLE `bids` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `auction_id` int(11) DEFAULT NULL,
  `bid_amount` decimal(10,2) DEFAULT NULL,
  `bid_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`) VALUES
(3, 'admin', 'admin@example.com', '$2y$10$UoWwTLezmmmVsd6HKyUdduFYNAoSmwwWzH3zv57jTczGHr7orQVVK', 'admin'),
(14, 'Adil Faridi', 'adilfaridi07@gmail.com', '$2y$10$3XLzUttCkLlmy9Ooo/FU5OPmVm3Uz75CVeILH8ZYtnQLIQ1DNHZZu', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auctions`
--
ALTER TABLE `auctions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bids`
--
ALTER TABLE `bids`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `auction_id` (`auction_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auctions`
--
ALTER TABLE `auctions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `bids`
--
ALTER TABLE `bids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bids`
--
ALTER TABLE `bids`
  ADD CONSTRAINT `bids_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bids_ibfk_2` FOREIGN KEY (`auction_id`) REFERENCES `auctions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
