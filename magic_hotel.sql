-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 28, 2026 at 05:14 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `magic_hotel`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','staff') DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `full_name`, `email`, `password`, `role`, `created_at`, `last_login`) VALUES
(5, 'Super Admin', 'admin@magichotel.com', 'admin123', 'admin', '2026-04-28 10:05:37', NULL),
(6, 'Hotel Manager', 'manager@magichotel.com', 'manager123', 'manager', '2026-04-28 10:05:37', NULL),
(7, 'Front Desk Staff', 'staff@magichotel.com', 'staff123', 'staff', '2026-04-28 10:05:37', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price_per_unit` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','refunded') DEFAULT 'unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `service_name`, `quantity`, `price_per_unit`, `total_price`, `booking_date`, `status`, `payment_status`) VALUES
(1, 1, 'Double Room', 2, 200000.00, 400000.00, '2026-04-28 08:51:09', 'confirmed', 'unpaid'),
(2, 1, 'Massage Therapy', 1, 100000.00, 100000.00, '2026-04-28 08:51:09', 'pending', 'unpaid'),
(3, 2, 'Single Room', 2, 100000.00, 200000.00, '2026-04-28 11:40:18', 'pending', 'unpaid'),
(4, 2, 'Facial Treatment', 1, 100000.00, 100000.00, '2026-04-28 12:06:58', 'pending', 'unpaid'),
(5, 2, 'Facial Treatment', 1, 100000.00, 100000.00, '2026-04-28 12:07:03', 'pending', 'unpaid'),
(6, 2, 'Normal Workout', 1, 100000.00, 100000.00, '2026-04-28 12:09:36', 'pending', 'unpaid'),
(7, 2, 'Salon Services', 1, 100000.00, 100000.00, '2026-04-28 12:11:24', 'pending', 'unpaid'),
(8, 2, 'Facial Treatment', 1, 20000.00, 20000.00, '2026-04-28 12:22:23', 'pending', 'unpaid'),
(9, 2, 'Salon Services', 1, 50000.00, 50000.00, '2026-04-28 12:26:53', 'pending', 'unpaid'),
(10, 2, 'Normal Workout', 1, 20000.00, 20000.00, '2026-04-28 12:33:58', 'cancelled', 'unpaid');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_category` enum('rooms','spa','gym','restaurant','bar') NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_approved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `user_id`, `service_category`, `service_name`, `rating`, `comment`, `created_at`, `is_approved`) VALUES
(1, 1, 'rooms', 'Double Room', 5, 'The room was amazing! Very clean and comfortable.', '2026-04-28 08:51:09', 1),
(2, 1, 'spa', 'Massage Therapy', 4, 'Great massage, very relaxing atmosphere.', '2026-04-28 08:51:09', 1);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_available` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `category`, `service_name`, `description`, `price`, `is_available`) VALUES
(1, 'rooms', 'Single Room', 'Comfortable single bed room with city view', 100000.00, 1),
(2, 'rooms', 'Double Room', 'Spacious double room with premium amenities', 200000.00, 1),
(3, 'spa', 'Facial Treatment', 'Rejuvenating facial with organic products', 20000.00, 1),
(4, 'spa', 'Massage Therapy', 'Full body relaxation massage', 100000.00, 1),
(5, 'spa', 'Body Treatment', 'Exfoliating scrub and body wrap', 80000.00, 1),
(6, 'spa', 'Salon Services', 'Hair styling and nail care', 50000.00, 1),
(7, 'gym', 'Normal Workout', 'Self-guided gym session', 20000.00, 1),
(8, 'gym', 'Workout with Trainer', 'Personal training session', 50000.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `created_at`, `last_login`) VALUES
(1, 'Test User', 'test@example.com', '256700000123', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-28 08:51:09', NULL),
(2, 'enos iragaba', 'enos@magichotel.com', '0774000700', '$2y$10$ZER4E8POKyoTxHSFHf7y2./BIcUbVeC/ZlgV56.AJ4BjNjVWmkkk6', '2026-04-28 11:40:17', '2026-04-28 12:35:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_service` (`service_category`),
  ADD KEY `idx_rating` (`rating`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
