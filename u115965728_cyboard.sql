-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 27, 2026 at 04:20 AM
-- Server version: 11.8.6-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u115965728_cyboard`
--

-- --------------------------------------------------------

--
-- Table structure for table `amenities`
--

CREATE TABLE `amenities` (
  `id` int(11) NOT NULL,
  `amenity_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `amenities`
--

INSERT INTO `amenities` (`id`, `amenity_name`) VALUES
(1, 'Air Conditioning'),
(2, 'Private Bathroom'),
(3, 'Bed'),
(4, 'Study Table'),
(5, 'Cabinet / Closet'),
(6, 'WiFi'),
(7, 'Parking Space'),
(8, 'Laundry Area');

-- --------------------------------------------------------

--
-- Table structure for table `bh_amenities`
--

CREATE TABLE `bh_amenities` (
  `id` int(11) NOT NULL,
  `bh_id` int(11) NOT NULL,
  `amenity_id` int(11) DEFAULT NULL,
  `custom_amenity` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bh_amenities`
--

INSERT INTO `bh_amenities` (`id`, `bh_id`, `amenity_id`, `custom_amenity`) VALUES
(251, 24, 3, NULL),
(252, 24, 5, NULL),
(253, 24, 8, NULL),
(254, 24, NULL, 'Foam');

-- --------------------------------------------------------

--
-- Table structure for table `bh_details`
--

CREATE TABLE `bh_details` (
  `id` int(11) NOT NULL,
  `bh_id` int(11) DEFAULT NULL,
  `payment_status` varchar(255) DEFAULT NULL,
  `bh_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bh_details`
--

INSERT INTO `bh_details` (`id`, `bh_id`, `payment_status`, `bh_pic`) VALUES
(5, 24, 'monthly', 'uploads/bh_696af7937596d1.99850647.jpg'),
(6, 25, 'monthly', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bh_utilities`
--

CREATE TABLE `bh_utilities` (
  `bh_utility_id` int(11) NOT NULL,
  `bh_id` int(11) NOT NULL,
  `utility_id` int(11) NOT NULL,
  `is_included` enum('yes','no') NOT NULL DEFAULT 'no'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bh_utilities`
--

INSERT INTO `bh_utilities` (`bh_utility_id`, `bh_id`, `utility_id`, `is_included`) VALUES
(54, 24, 1, 'yes'),
(55, 24, 3, 'yes'),
(56, 24, 2, 'yes');

-- --------------------------------------------------------

--
-- Table structure for table `boarding_houses`
--

CREATE TABLE `boarding_houses` (
  `bh_id` int(11) NOT NULL,
  `bh_name` varchar(50) DEFAULT NULL,
  `longitude` decimal(9,6) DEFAULT NULL,
  `latitude` decimal(9,6) DEFAULT NULL,
  `bh_address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `boarding_houses`
--

INSERT INTO `boarding_houses` (`bh_id`, `bh_name`, `longitude`, `latitude`, `bh_address`) VALUES
(20, 'Jansam Apartment ', 122.504501, 10.680742, 'MGJ3+7QQ, Villa Arevalo District, Iloilo City, Iloilo'),
(21, 'Mavylex Boarding House ', 122.504309, 10.681099, 'seafront subdivision, santo nino arevalo, Villa Arevalo District, Iloilo City, Iloilo'),
(22, 'NOAH LEONS BOARDING HOUSE', 122.504359, 10.681497, 'zone2 seafront subdivision barangay santo nino sur arevalo iloilo city'),
(23, 'Isunza Boarding House ', 122.518404, 10.682636, 'MONTAÑO COMPOUND, BRGY. YULO DRIVE, FLORA ST, Villa Arevalo District'),
(24, 'Vilma Boarding House ', 122.503379, 10.683673, 'Santo Niño Sur Villa Arevalo District'),
(25, 'Wilma Boarding House ', 122.503529, 10.682727, 'Santo Niño Sur Villa Arevalo District');

-- --------------------------------------------------------

--
-- Table structure for table `file_access_tokens`
--

CREATE TABLE `file_access_tokens` (
  `token_id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `status` enum('pending','used','expired') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `attempts` int(11) DEFAULT 0,
  `otp_requests` int(11) DEFAULT 0,
  `request_reset_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `file_access_tokens`
--

INSERT INTO `file_access_tokens` (`token_id`, `landlord_id`, `token`, `otp_code`, `otp_expires_at`, `status`, `created_at`, `expires_at`, `used_at`, `attempts`, `otp_requests`, `request_reset_at`) VALUES
(66, 20, '10db32ea9b39c2912c1eaaa80c2253bed52a63a64a2d9adee785d443906641f2', '', NULL, 'expired', '2025-12-04 09:32:01', '2025-12-06 09:32:00', '2025-12-04 09:34:12', 0, 1, '2025-12-05 09:33:10'),
(67, 21, 'aad37c067412afc096ec0fb1b5cbf8a1dc2d8a21571558a91206a0620c53ebab', '', NULL, 'expired', '2025-12-04 09:57:20', '2025-12-06 09:57:19', '2025-12-04 09:59:15', 0, 1, '2025-12-05 09:58:21'),
(70, 22, 'bfc66fe72ac06e8c2ddcb1ff2301ba33362570d48d9469796e444cddd3e7633f', '', NULL, 'expired', '2025-12-04 10:14:40', '2025-12-06 10:14:39', '2025-12-04 10:17:21', 0, 1, '2025-12-05 10:15:39'),
(71, 23, 'fe9232d94bd6b5e3515836619de2f06f4e5d69fc084412811264d9943b77c402', '', NULL, 'expired', '2025-12-05 13:27:26', '2025-12-07 13:27:25', '2025-12-05 13:29:07', 0, 1, '2025-12-06 13:28:38'),
(72, 24, '736a3de57b3023fab4fe3d7296fc2d729e03e8f896b5eab40ce01538735de4b2', '', NULL, 'expired', '2026-01-15 15:21:04', '2026-01-17 15:21:03', '2026-01-15 15:29:05', 0, 2, '2026-01-16 15:21:50'),
(75, 25, '820891dd892e0bb5c8a7cea5c790b212b65efb6be9b7f4e3b655005e9c0113e1', '', NULL, 'expired', '2026-01-19 10:19:08', '2026-01-21 10:19:06', '2026-01-19 10:20:20', 0, 1, '2026-01-20 10:19:28');

-- --------------------------------------------------------

--
-- Table structure for table `landlords`
--

CREATE TABLE `landlords` (
  `landlord_id` int(11) NOT NULL,
  `landlord_name` varchar(255) DEFAULT NULL,
  `landlord_email` varchar(255) DEFAULT NULL,
  `landlord_password` varchar(255) DEFAULT NULL,
  `landlord_number` varchar(11) DEFAULT NULL,
  `bh_id` int(11) DEFAULT NULL,
  `password_set` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `landlords`
--

INSERT INTO `landlords` (`landlord_id`, `landlord_name`, `landlord_email`, `landlord_password`, `landlord_number`, `bh_id`, `password_set`) VALUES
(20, 'rommel faunillan', 'wilsengaborno@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$ZDcweUVkMlJKR1J5dEVwLg$bNEqUoLLaR7E8kJ3+JQb0Oz1jLQbb80/dPtzPhfiKkc', '09516647592', 20, 1),
(21, 'Mary grace tagoon', 'dumaraog29@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$L0dSSy9FRklRcDRQVUtGSQ$iAMPHtfoHOJ0C1e11nAtS1kvoyEoRWvzjyTJyqAS2gk', '09930700550', 21, 1),
(22, 'erna sanglap', 'ernasanglap21@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$TXlJdTVmaGIvTmx0U2dLWQ$p1OWfGDotEvU6roPOJcnlpE3D3JJef3pHOUEsOgeiIE', '09093757387', 22, 1),
(23, 'lark shamisen isunza', 'larkshamisen@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$bHhadkg0S1ZtSEgwODltYg$DAdXpT6N0YcgChBO28FK/gThT+zchtiNvZIR8djsQks', '09205053133', 23, 1),
(24, 'Vilma Tirona', 'vilmatirona1964@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$MXkuZGRYZHkxZXVoTFR6Vg$yMqhy7JAHVZ05xC1vtqolEX7u9LneWxuS/so0MW0BG0', '09461192046', 24, 1),
(25, 'Wilma Paquingan', 'wilma.paquingan55@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$WGZQOWEvVm1HMUZQVkRYTA$b4ZWwlK2/Owub6kKVmF6KXd3wii4WcoFdSi9f7CGems', '09455910056', 25, 1);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `superadmin_id` int(11) NOT NULL,
  `sender_type` enum('superadmin','landlord') NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `move_in` date DEFAULT NULL,
  `duration` varchar(255) DEFAULT NULL,
  `status` enum('waiting','active','pending','cancel','rejected','ended') DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `reserved_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `bh_id` int(11) DEFAULT NULL,
  `room_type` varchar(255) DEFAULT NULL,
  `room_picture` varchar(255) DEFAULT NULL,
  `gender_policy` varchar(255) DEFAULT NULL,
  `room_rate` int(11) DEFAULT NULL,
  `downpayment` int(11) DEFAULT NULL,
  `room_capacity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `bh_id`, `room_type`, `room_picture`, `gender_policy`, `room_rate`, `downpayment`, `room_capacity`) VALUES
(23, 20, 'Dormitory style', 'Dormitory style1764812388.jpg', 'Female Only', 2500, 5000, 6),
(24, 20, 'Dormitory style', 'Dormitory style1764812426.jpg', 'Male Only', 2500, 5000, 6),
(26, 24, 'Double', 'Double1768618243.jpg', 'Mixed', 1700, 3400, 2),
(27, 24, 'Double', 'Double1768618308.jpg', 'Mixed', 1700, 3400, 2),
(28, 24, 'Double', 'Double1768618342.jpg', 'Mixed', 1700, 3400, 2),
(29, 24, 'Double', 'Double1768618378.jpg', 'Mixed', 1700, 3400, 2);

-- --------------------------------------------------------

--
-- Table structure for table `room_excluded_amenities`
--

CREATE TABLE `room_excluded_amenities` (
  `id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `bh_amenity_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_excluded_utilities`
--

CREATE TABLE `room_excluded_utilities` (
  `id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `bh_utility_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `superadmin`
--

CREATE TABLE `superadmin` (
  `superadmin_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `superadmin`
--

INSERT INTO `superadmin` (`superadmin_id`, `name`, `email`, `password`) VALUES
(1, 'Super Admin', 'cyboard.reservations@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$aFVBS1dvL1RhSHZoTWRRTw$7b5eCKwF9/ctw1wr2AOICAPm+g3zqoqLO0yof6VBHxc');

-- --------------------------------------------------------

--
-- Table structure for table `tenants`
--

CREATE TABLE `tenants` (
  `tenant_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `stud_id` varchar(255) DEFAULT NULL,
  `year_level` varchar(255) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tenants`
--

INSERT INTO `tenants` (`tenant_id`, `name`, `email`, `phone_number`, `password`, `stud_id`, `year_level`, `program`, `photo`) VALUES
(17, 'Lesly Ann Tanate', 'tanateleslyannmillan@gmail.com', '09194504687', '$argon2id$v=19$m=65536,t=4,p=1$RTBJNzhCSEpnRk9TRVZPdA$05d2x9NfyBqiAOxrHPHRgvR5ERFBPpatShWCpn2RZks', '04-2223-038015', '4th Year', 'BS Civil Engineering', '1ZDpTpopvJycaJWcW0gYEk1MQUtoT0pZRitBOHJNT21kaUNWblgxR2ZHUXdqTVd1R2EzTTNiMFZKOWxVR1ZsZWpyUUZ6WWljRUNjUHd6d29zZmRPQnBITmZNRG1xK3dzaVZ6dERXWE54M0hnaHdqN0V3TmdIQVhoSUhDUlVFbUwwb2k4bDhnQnNmdmoxekRLejlTbS9ta0FGc2FQWmJ2enJWNE4wZz09'),
(21, 'Paul Panelo Celestre', 'papa.celestre.ui@phinmaed.com', '09566955998', '$argon2id$v=19$m=65536,t=4,p=1$WVFHTll4N3ZvZUpyMXhETw$hNeueyW5Ydj0xtMJoOlJsoegAkUu3/NqkXdN7RkY6k8', '04-2223-034052', '4th Year', 'BS Information Technology', 'https://lh3.googleusercontent.com/a/ACg8ocK-69V24Jjl3IJOxUsrS4GN46-ioEjUh5Pan9-elgCXvd8Hhg=s96-c'),
(24, 'Reamipum', 'dallisongude83@hotmail.com', '81559532487', '$argon2id$v=19$m=65536,t=4,p=1$OFA2Q1pVOEpSRmFDbG5adw$/QrSxo67AmHZASiOiV/nbkmDAx0uy6bMaVLxhYVchmo', '81629724188', '3rd Year', 'BS Business Administration', '');

-- --------------------------------------------------------

--
-- Table structure for table `utilities`
--

CREATE TABLE `utilities` (
  `utility_id` int(11) NOT NULL,
  `utility_name` varchar(100) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `utilities`
--

INSERT INTO `utilities` (`utility_id`, `utility_name`, `is_default`) VALUES
(1, 'Electricity', 1),
(2, 'Water', 1),
(3, 'Internet', 1);

-- --------------------------------------------------------

--
-- Table structure for table `utility_pricing`
--

CREATE TABLE `utility_pricing` (
  `utility_pricing_id` int(11) NOT NULL,
  `bh_id` int(11) NOT NULL,
  `bh_utility_id` int(11) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `unit` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `amenities`
--
ALTER TABLE `amenities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bh_amenities`
--
ALTER TABLE `bh_amenities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bh_id` (`bh_id`),
  ADD KEY `amenity_id` (`amenity_id`);

--
-- Indexes for table `bh_details`
--
ALTER TABLE `bh_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bh_id` (`bh_id`);

--
-- Indexes for table `bh_utilities`
--
ALTER TABLE `bh_utilities`
  ADD PRIMARY KEY (`bh_utility_id`),
  ADD KEY `bh_id` (`bh_id`),
  ADD KEY `utility_id` (`utility_id`);

--
-- Indexes for table `boarding_houses`
--
ALTER TABLE `boarding_houses`
  ADD PRIMARY KEY (`bh_id`);

--
-- Indexes for table `file_access_tokens`
--
ALTER TABLE `file_access_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `token` (`token`),
  ADD KEY `landlord_id` (`landlord_id`);

--
-- Indexes for table `landlords`
--
ALTER TABLE `landlords`
  ADD PRIMARY KEY (`landlord_id`),
  ADD KEY `fk_bh_id` (`bh_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `landlord_id` (`landlord_id`),
  ADD KEY `superadmin_id` (`superadmin_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `fk_user_reservation` (`tenant_id`),
  ADD KEY `fk_room_reservation` (`room_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `bh_id` (`bh_id`);

--
-- Indexes for table `room_excluded_amenities`
--
ALTER TABLE `room_excluded_amenities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `bh_amenity_id` (`bh_amenity_id`);

--
-- Indexes for table `room_excluded_utilities`
--
ALTER TABLE `room_excluded_utilities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_excluded_utilities_ibfk_2` (`bh_utility_id`),
  ADD KEY `room_excluded_utilities_ibfk_1` (`room_id`);

--
-- Indexes for table `superadmin`
--
ALTER TABLE `superadmin`
  ADD PRIMARY KEY (`superadmin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`tenant_id`);

--
-- Indexes for table `utilities`
--
ALTER TABLE `utilities`
  ADD PRIMARY KEY (`utility_id`);

--
-- Indexes for table `utility_pricing`
--
ALTER TABLE `utility_pricing`
  ADD PRIMARY KEY (`utility_pricing_id`),
  ADD KEY `fk_utilitypricing_bh` (`bh_id`),
  ADD KEY `fk_utilitypricing_utility` (`bh_utility_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `amenities`
--
ALTER TABLE `amenities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `bh_amenities`
--
ALTER TABLE `bh_amenities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=255;

--
-- AUTO_INCREMENT for table `bh_details`
--
ALTER TABLE `bh_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `bh_utilities`
--
ALTER TABLE `bh_utilities`
  MODIFY `bh_utility_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `boarding_houses`
--
ALTER TABLE `boarding_houses`
  MODIFY `bh_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `file_access_tokens`
--
ALTER TABLE `file_access_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `landlords`
--
ALTER TABLE `landlords`
  MODIFY `landlord_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `room_excluded_amenities`
--
ALTER TABLE `room_excluded_amenities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT for table `room_excluded_utilities`
--
ALTER TABLE `room_excluded_utilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `superadmin`
--
ALTER TABLE `superadmin`
  MODIFY `superadmin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tenants`
--
ALTER TABLE `tenants`
  MODIFY `tenant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `utilities`
--
ALTER TABLE `utilities`
  MODIFY `utility_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `utility_pricing`
--
ALTER TABLE `utility_pricing`
  MODIFY `utility_pricing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bh_amenities`
--
ALTER TABLE `bh_amenities`
  ADD CONSTRAINT `bh_amenities_ibfk_1` FOREIGN KEY (`bh_id`) REFERENCES `boarding_houses` (`bh_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bh_amenities_ibfk_2` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bh_details`
--
ALTER TABLE `bh_details`
  ADD CONSTRAINT `bh_details_ibfk_1` FOREIGN KEY (`bh_id`) REFERENCES `boarding_houses` (`bh_id`) ON DELETE CASCADE;

--
-- Constraints for table `bh_utilities`
--
ALTER TABLE `bh_utilities`
  ADD CONSTRAINT `bh_utilities_ibfk_1` FOREIGN KEY (`bh_id`) REFERENCES `boarding_houses` (`bh_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bh_utilities_ibfk_2` FOREIGN KEY (`utility_id`) REFERENCES `utilities` (`utility_id`) ON DELETE CASCADE;

--
-- Constraints for table `file_access_tokens`
--
ALTER TABLE `file_access_tokens`
  ADD CONSTRAINT `file_access_tokens_ibfk_1` FOREIGN KEY (`landlord_id`) REFERENCES `landlords` (`landlord_id`) ON DELETE CASCADE;

--
-- Constraints for table `landlords`
--
ALTER TABLE `landlords`
  ADD CONSTRAINT `fk_bh_id` FOREIGN KEY (`bh_id`) REFERENCES `boarding_houses` (`bh_id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`landlord_id`) REFERENCES `landlords` (`landlord_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`superadmin_id`) REFERENCES `superadmin` (`superadmin_id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_room_reservation` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_reservation` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`bh_id`) REFERENCES `boarding_houses` (`bh_id`) ON DELETE CASCADE;

--
-- Constraints for table `room_excluded_amenities`
--
ALTER TABLE `room_excluded_amenities`
  ADD CONSTRAINT `room_excluded_amenities_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_excluded_amenities_ibfk_2` FOREIGN KEY (`bh_amenity_id`) REFERENCES `bh_amenities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_excluded_utilities`
--
ALTER TABLE `room_excluded_utilities`
  ADD CONSTRAINT `room_excluded_utilities_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_excluded_utilities_ibfk_2` FOREIGN KEY (`bh_utility_id`) REFERENCES `bh_utilities` (`bh_utility_id`) ON DELETE CASCADE;

--
-- Constraints for table `utility_pricing`
--
ALTER TABLE `utility_pricing`
  ADD CONSTRAINT `fk_utilitypricing_bh` FOREIGN KEY (`bh_id`) REFERENCES `boarding_houses` (`bh_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_utilitypricing_utility` FOREIGN KEY (`bh_utility_id`) REFERENCES `bh_utilities` (`bh_utility_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
