-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 16, 2026 at 06:58 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kaagapay_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `add_ons`
--

CREATE TABLE `add_ons` (
  `add_on_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `assignment_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `beneficiaries`
--

CREATE TABLE `beneficiaries` (
  `beneficiary_id` int(11) NOT NULL,
  `plan_holder_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `name_extension` varchar(10) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `relationship` varchar(50) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `branch_id` int(11) NOT NULL,
  `branch_name` varchar(100) NOT NULL,
  `address_street` varchar(100) DEFAULT NULL,
  `address_barangay` varchar(100) NOT NULL,
  `address_city` varchar(100) NOT NULL,
  `address_province` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `manager_first_name` varchar(50) DEFAULT NULL,
  `manager_middle_name` varchar(50) DEFAULT NULL,
  `manager_last_name` varchar(50) DEFAULT NULL,
  `manager_extension` varchar(10) DEFAULT NULL,
  `manager_position` varchar(50) DEFAULT NULL,
  `date_established` date DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`branch_id`, `branch_name`, `address_street`, `address_barangay`, `address_city`, `address_province`, `contact_number`, `manager_first_name`, `manager_middle_name`, `manager_last_name`, `manager_extension`, `manager_position`, `date_established`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Calapan Branch', NULL, 'Sta.Isabel', 'Calapan City', 'Oriental Mindoro', NULL, 'Lannie', NULL, 'De Mesa', NULL, 'Branch Manager', '2026-03-01', 'active', '2026-03-31 14:55:32', '2026-03-31 14:55:32');

-- --------------------------------------------------------

--
-- Table structure for table `deceased`
--

CREATE TABLE `deceased` (
  `deceased_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `name_extension` varchar(10) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `date_of_death` date NOT NULL,
  `time_of_death` time DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `cause_of_death` text DEFAULT NULL,
  `place_of_death` varchar(100) DEFAULT NULL,
  `death_certificate_number` varchar(100) DEFAULT NULL,
  `burial_permit_number` varchar(100) DEFAULT NULL,
  `address_at_death` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES
(1, '2026-03-31-140500', 'App\\Database\\Migrations\\AddPackageIdToPlans', 'default', 'App', 1774967895, 1),
(2, '2026-04-06-120000', 'App\\Database\\Migrations\\AddUniqueEmailIndexToUsers', 'default', 'App', 1775480995, 2);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` enum('read','unread') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `message`, `status`, `created_at`) VALUES
(1, 8, 'Your plan holder profile was created or linked successfully.', 'unread', '2026-04-16 15:27:53');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `package_id` int(11) NOT NULL,
  `package_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `is_customizable` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_items`
--

CREATE TABLE `package_items` (
  `item_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_versions`
--

CREATE TABLE `package_versions` (
  `version_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `effective_date` date NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('cash','gcash') NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `months_covered` int(11) DEFAULT 1,
  `official_receipt_number` varchar(100) DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `branch_id` int(11) NOT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('paid','pending','cancelled') DEFAULT 'paid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `plan_id` int(11) NOT NULL,
  `plan_holder_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `monthly_fee` decimal(10,2) NOT NULL,
  `passbook_fee` decimal(10,2) DEFAULT 50.00,
  `start_date` date NOT NULL,
  `status` enum('active','inactive','completed') NOT NULL,
  `months_paid` int(11) DEFAULT 0,
  `remaining_balance` decimal(10,2) DEFAULT 0.00,
  `payment_coverage_until` date DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `overdue_months` int(11) DEFAULT 0,
  `membership_state` enum('active','delinquent','suspended','inactive') DEFAULT 'inactive',
  `total_plan_amount` decimal(10,2) DEFAULT NULL,
  `version_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plan_holders`
--

CREATE TABLE `plan_holders` (
  `plan_holder_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `id_control_no` varchar(50) DEFAULT NULL,
  `coordinator` varchar(100) DEFAULT NULL,
  `application_date` date DEFAULT NULL,
  `address_no` varchar(20) DEFAULT NULL,
  `address_street` varchar(100) DEFAULT NULL,
  `address_barangay` varchar(100) DEFAULT NULL,
  `address_city` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `place_of_birth` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `citizenship` varchar(50) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `spouse_name` varchar(100) DEFAULT NULL,
  `spouse_birthdate` date DEFAULT NULL,
  `spouse_occupation` varchar(100) DEFAULT NULL,
  `senior_citizen_id` varchar(50) DEFAULT NULL,
  `organization_affiliation` varchar(100) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `emergency_contact_address` varchar(255) DEFAULT NULL,
  `branch_id` int(11) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_linked_account` tinyint(1) DEFAULT 0,
  `unique_identifier` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `plan_holder`
-- (compatibility table for current controller logic)
--

CREATE TABLE `plan_holder` (
  `plan_holder_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `package_id` int(11) DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `citizenship` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `memberships`
-- (compatibility table for current controller logic)
--

CREATE TABLE `memberships` (
  `membership_id` int(11) NOT NULL,
  `plan_holder_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plan_holders`
--

INSERT INTO `plan_holders` (`plan_holder_id`, `user_id`, `address_no`, `address_street`, `address_barangay`, `address_city`, `date_of_birth`, `place_of_birth`, `age`, `gender`, `civil_status`, `citizenship`, `height`, `weight`, `spouse_name`, `spouse_birthdate`, `spouse_occupation`, `senior_citizen_id`, `organization_affiliation`, `branch_id`, `status`, `created_at`, `is_linked_account`, `unique_identifier`) VALUES
(1, 7, '', '', 'Maidlang', 'Calapan City', NULL, '', NULL, '', '', '', NULL, NULL, '', NULL, '', '', '', 1, 'active', '2026-04-06 16:00:00', 0, NULL),
(2, 9, NULL, NULL, '', '', '0000-00-00', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'active', '2026-04-08 16:00:00', 0, NULL),
(3, 10, NULL, NULL, '', '', '0000-00-00', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'active', '2026-04-08 16:00:00', 0, NULL),
(4, 12, NULL, NULL, '', '', '0000-00-00', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'active', '2026-04-14 05:37:26', 0, NULL),
(5, 8, '', '', '', '', NULL, '', NULL, '', '', '', NULL, NULL, '', NULL, '', '', '', 1, 'active', '2026-04-16 15:27:53', 1, 'ivy@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'Admin'),
(2, 'Branch Admin'),
(3, 'Staff'),
(4, 'Plan Holder');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `plan_holder_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `service_type` varchar(50) DEFAULT NULL,
  `package_id` int(11) NOT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `service_date` date NOT NULL,
  `service_time` time DEFAULT NULL,
  `burial_location` varchar(150) DEFAULT NULL,
  `assigned_staff` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','ongoing','completed','cancelled') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_applications`
--

CREATE TABLE `service_applications` (
  `application_id` int(11) NOT NULL,
  `plan_holder_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_costs`
--

CREATE TABLE `service_costs` (
  `cost_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_offers`
--

CREATE TABLE `service_offers` (
  `offer_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `name_extension` varchar(10) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `account_status` enum('pending','verified') DEFAULT 'pending',
  `is_plan_holder` tinyint(1) DEFAULT 0,
  `must_change_password` tinyint(1) DEFAULT 0,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `email`, `first_name`, `middle_name`, `last_name`, `name_extension`, `contact_number`, `role_id`, `branch_id`, `status`, `created_at`, `last_login`, `account_status`, `is_plan_holder`, `must_change_password`, `email_verification_token`, `token_expiry`, `reset_token`, `reset_token_expiry`) VALUES
(5, 'bryan', '$2y$10$vJouP0F18O0oD9kcnsfbSuTWlapAV3ty/k2sRPgJG7auFsPx82qDS', 'erazojohnbryan08@gmail.com', 'John Bryan', NULL, 'John Bryan', NULL, '09369111954', 1, NULL, 'active', '2026-04-07 05:43:54', '2026-04-15 09:40:06', 'pending', 0, 0, NULL, NULL, NULL, NULL),
(6, 'regine', '$2y$10$EVYKLzLmCrxWLxPMPM1Z1e0wYA0pYhrOnO39PU9wgCsKABBfpZ6ae', 'aclanregine2@gmail.com', 'REGINE', 'ILAGAN', 'ACLAN', NULL, '09674073500', 2, 1, 'active', '2026-04-07 05:48:53', '2026-04-16 07:29:55', 'pending', 0, 0, NULL, NULL, NULL, NULL),
(7, 'ivanmeim_20260407055007', '$2y$10$/og5/NO/3ExSYbDFg662qu2/bZ/aQ7acKIePARdOGFmPt.nH5l.Ku', 'ivanmeim_20260407055007@kaagapay.local', 'Ivan', NULL, 'Meim', NULL, '', 4, 1, 'active', '2026-04-06 16:00:00', NULL, 'pending', 1, 0, NULL, NULL, NULL, NULL),
(8, 'ivy', '$2y$10$Ixjc29uvcnC2GSZ/c4xMjus5QfSaWHNayBRiU2tU5U6jW1YA7mera', 'ivy@gmail.com', 'Ivy', NULL, 'Tegio', NULL, '09369111954', 4, 1, 'active', '2026-04-09 03:39:05', '2026-04-16 07:29:20', 'verified', 1, 0, NULL, NULL, NULL, NULL),
(9, 'ivytegio_20260409034320', '$2y$10$bGm0SupxvSQXwO8qXdoiDOL8IMdO4b585cS/WMTut8rQRrzPgRvWG', 'ivytegio_20260409034320@kaagapay.local', 'Ivy', 'Cagalpin', 'Tegio', NULL, NULL, 4, 1, 'active', '2026-04-08 16:00:00', NULL, 'pending', 1, 0, NULL, NULL, NULL, NULL),
(10, 'johnbryanaclan_20260409042603', '$2y$10$eonjp9LL/kXt9zl0nYlSVOhu8LvOUGTkBv167aSZ2G4kEBeq/EXLi', 'johnbryanaclan_20260409042603@kaagapay.local', 'John Bryan', 'Ilagan', 'Aclan', NULL, NULL, 4, 1, 'active', '2026-04-08 16:00:00', NULL, 'pending', 1, 0, NULL, NULL, NULL, NULL),
(11, 'mein', '$2y$10$B96o2f71l59IsxtPnk06A.A13xNWMoMQ0pnIFX7acB2FGNkUDOwmq', 'meim@gmail.com', 'Ivan', NULL, 'Meim', NULL, '09674073456', 4, NULL, 'active', '2026-04-09 04:39:51', '2026-04-08 20:40:01', 'pending', 0, 0, NULL, NULL, NULL, NULL),
(12, 'judelesther', '$2y$10$d6/ws6AYjRqAyLKtRLftlul2L.eyU9l3tIYsDPFySUqoFXaXC8jQy', 'sarmiento@gmail.com', 'Jude Lesther', NULL, 'Sarmiento', NULL, '09674073456', 3, NULL, 'active', '2026-04-14 13:37:26', '2026-04-16 07:27:01', 'pending', 1, 0, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `add_ons`
--
ALTER TABLE `add_ons`
  ADD PRIMARY KEY (`add_on_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  ADD PRIMARY KEY (`beneficiary_id`),
  ADD KEY `plan_holder_id` (`plan_holder_id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`branch_id`);

--
-- Indexes for table `deceased`
--
ALTER TABLE `deceased`
  ADD PRIMARY KEY (`deceased_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`package_id`);

--
-- Indexes for table `package_items`
--
ALTER TABLE `package_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `package_versions`
--
ALTER TABLE `package_versions`
  ADD PRIMARY KEY (`version_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `plan_holder_id` (`plan_holder_id`),
  ADD KEY `plans_ibfk_2` (`package_id`),
  ADD KEY `fk_plan_version` (`version_id`);

--
-- Indexes for table `plan_holders`
--
ALTER TABLE `plan_holders`
  ADD PRIMARY KEY (`plan_holder_id`),
  ADD UNIQUE KEY `unique_identifier` (`unique_identifier`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `plan_holder`
--
ALTER TABLE `plan_holder`
  ADD PRIMARY KEY (`plan_holder_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `memberships`
--
ALTER TABLE `memberships`
  ADD PRIMARY KEY (`membership_id`),
  ADD KEY `plan_holder_id` (`plan_holder_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`),
  ADD KEY `plan_holder_id` (`plan_holder_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `assigned_staff` (`assigned_staff`);

--
-- Indexes for table `service_applications`
--
ALTER TABLE `service_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `plan_holder_id` (`plan_holder_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `service_costs`
--
ALTER TABLE `service_costs`
  ADD PRIMARY KEY (`cost_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `service_offers`
--
ALTER TABLE `service_offers`
  ADD PRIMARY KEY (`offer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `add_ons`
--
ALTER TABLE `add_ons`
  MODIFY `add_on_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  MODIFY `beneficiary_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `branch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `deceased`
--
ALTER TABLE `deceased`
  MODIFY `deceased_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `package_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_items`
--
ALTER TABLE `package_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_versions`
--
ALTER TABLE `package_versions`
  MODIFY `version_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plan_holders`
--
ALTER TABLE `plan_holders`
  MODIFY `plan_holder_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `plan_holder`
--
ALTER TABLE `plan_holder`
  MODIFY `plan_holder_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `memberships`
--
ALTER TABLE `memberships`
  MODIFY `membership_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_applications`
--
ALTER TABLE `service_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_costs`
--
ALTER TABLE `service_costs`
  MODIFY `cost_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_offers`
--
ALTER TABLE `service_offers`
  MODIFY `offer_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `add_ons`
--
ALTER TABLE `add_ons`
  ADD CONSTRAINT `add_ons_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`);

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`),
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  ADD CONSTRAINT `beneficiaries_ibfk_1` FOREIGN KEY (`plan_holder_id`) REFERENCES `plan_holders` (`plan_holder_id`);

--
-- Constraints for table `deceased`
--
ALTER TABLE `deceased`
  ADD CONSTRAINT `deceased_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `package_items`
--
ALTER TABLE `package_items`
  ADD CONSTRAINT `package_items_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`);

--
-- Constraints for table `package_versions`
--
ALTER TABLE `package_versions`
  ADD CONSTRAINT `package_versions_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`plan_id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`);

--
-- Constraints for table `plans`
--
ALTER TABLE `plans`
  ADD CONSTRAINT `fk_plan_version` FOREIGN KEY (`version_id`) REFERENCES `package_versions` (`version_id`),
  ADD CONSTRAINT `plans_ibfk_1` FOREIGN KEY (`plan_holder_id`) REFERENCES `plan_holders` (`plan_holder_id`),
  ADD CONSTRAINT `plans_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`);

--
-- Constraints for table `plan_holders`
--
ALTER TABLE `plan_holders`
  ADD CONSTRAINT `plan_holders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `plan_holders_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`);

--
-- Constraints for table `plan_holder`
--
ALTER TABLE `plan_holder`
  ADD CONSTRAINT `plan_holder_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `plan_holder_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`plan_id`),
  ADD CONSTRAINT `plan_holder_ibfk_3` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`),
  ADD CONSTRAINT `plan_holder_ibfk_4` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`);

--
-- Constraints for table `memberships`
--
ALTER TABLE `memberships`
  ADD CONSTRAINT `memberships_ibfk_1` FOREIGN KEY (`plan_holder_id`) REFERENCES `plan_holder` (`plan_holder_id`),
  ADD CONSTRAINT `memberships_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`plan_id`);

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`plan_holder_id`) REFERENCES `plan_holders` (`plan_holder_id`),
  ADD CONSTRAINT `services_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`),
  ADD CONSTRAINT `services_ibfk_3` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`),
  ADD CONSTRAINT `services_ibfk_4` FOREIGN KEY (`assigned_staff`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `service_applications`
--
ALTER TABLE `service_applications`
  ADD CONSTRAINT `service_applications_ibfk_1` FOREIGN KEY (`plan_holder_id`) REFERENCES `plan_holders` (`plan_holder_id`),
  ADD CONSTRAINT `service_applications_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`);

--
-- Constraints for table `service_costs`
--
ALTER TABLE `service_costs`
  ADD CONSTRAINT `service_costs_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
