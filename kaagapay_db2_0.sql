-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 08, 2026 at 05:42 PM
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
-- Database: `kaagapay_db2.0`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'User who performed the action',
  `action` varchar(100) NOT NULL COMMENT 'Action performed (approved, rejected, created, updated, deleted)',
  `module` varchar(50) NOT NULL COMMENT 'Module affected (payment, service, package, plan_holder)',
  `target_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID of the record affected',
  `description` text DEFAULT NULL COMMENT 'Detailed description of the action',
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Previous values of modified fields' CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'New values of modified fields' CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of the user',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `module`, `target_id`, `description`, `old_values`, `new_values`, `ip_address`, `created_at`) VALUES
(1, 6, 'created', 'plan_holder', 9, 'Registered plan holder from branch admin client form', NULL, '{\"branch_id\":1}', '::1', '2026-04-28 14:10:54'),
(2, 6, 'created', 'service_offer', 34, 'Submitted new service request for approval', NULL, '{\"service_name\":\"Sample service 5\",\"status\":\"pending\"}', '::1', '2026-05-01 07:39:43'),
(3, 5, 'approved', 'service_offer', 9, 'Approved pending service request', '{\"status\":\"pending\"}', '{\"status\":\"approved\"}', '::1', '2026-05-01 08:26:31'),
(4, 5, 'approved', 'package', 7, 'Approved pending package request', '{\"status\":\"pending\"}', '{\"status\":\"approved\"}', '::1', '2026-05-01 08:27:03'),
(5, 5, 'approved', 'package', 8, 'Approved pending package request', '{\"status\":\"pending\"}', '{\"status\":\"approved\"}', '::1', '2026-05-01 08:27:09'),
(6, 18, 'created', 'user', 18, 'Registered new client account', NULL, '{\"linked_existing_plan_holder\":false}', '::1', '2026-05-05 14:30:59'),
(7, 19, 'created', 'user', 19, 'Registered new client account', NULL, '{\"linked_existing_plan_holder\":false}', '::1', '2026-05-05 14:54:53'),
(8, 6, 'created', 'payment', 7, 'Recorded cash payment for plan #5', NULL, '{\"plan_id\":5,\"amount\":240,\"payment_method\":\"cash\",\"status\":\"paid\"}', '::1', '2026-05-05 15:09:07'),
(9, 6, 'created', 'payment', 8, 'Recorded cash payment for plan #6', NULL, '{\"plan_id\":6,\"amount\":240,\"payment_method\":\"cash\",\"status\":\"paid\"}', '::1', '2026-05-05 15:09:22'),
(10, 6, 'rejected', 'payment', 2, 'Rejected GCash payment', '{\"status\":\"pending\"}', '{\"status\":\"cancelled\"}', '::1', '2026-05-05 15:28:20'),
(11, 6, 'rejected', 'payment', 6, 'Rejected GCash payment', '{\"status\":\"pending\"}', '{\"status\":\"cancelled\"}', '::1', '2026-05-05 15:28:22'),
(12, 6, 'created', 'payment', 9, 'Recorded cash payment for plan #6', NULL, '{\"plan_id\":6,\"amount\":240,\"payment_method\":\"cash\",\"status\":\"paid\"}', '::1', '2026-05-05 15:32:22'),
(13, 6, 'created', 'payment', 10, 'Recorded cash payment for plan #5', NULL, '{\"plan_id\":5,\"amount\":240,\"payment_method\":\"cash\",\"status\":\"paid\"}', '::1', '2026-05-05 15:32:31');

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

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`assignment_id`, `service_id`, `staff_id`, `assigned_date`) VALUES
(1, 1, 12, '2026-04-16 09:45:27'),
(2, 2, 12, '2026-04-16 23:22:51'),
(3, 3, 12, '2026-04-18 00:44:55');

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
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending'
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
-- Table structure for table `embalmers`
--

CREATE TABLE `embalmers` (
  `embalmer_id` int(11) UNSIGNED NOT NULL,
  `branch_id` int(11) UNSIGNED NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `status` enum('available','busy','unavailable','inactive') NOT NULL DEFAULT 'available',
  `experience_years` int(3) UNSIGNED DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hearses`
--

CREATE TABLE `hearses` (
  `hearse_id` int(11) UNSIGNED NOT NULL,
  `branch_id` int(11) UNSIGNED NOT NULL,
  `hearse_name` varchar(100) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `model_year` int(4) UNSIGNED DEFAULT NULL,
  `capacity` int(3) UNSIGNED NOT NULL DEFAULT 1,
  `status` enum('available','unavailable','maintenance','retired') NOT NULL DEFAULT 'available',
  `last_maintenance` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `membership_programs`
--

CREATE TABLE `membership_programs` (
  `program_id` int(11) UNSIGNED NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `monthly_fee` decimal(10,2) NOT NULL DEFAULT 240.00,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership_programs`
--

INSERT INTO `membership_programs` (`program_id`, `program_name`, `monthly_fee`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Damayan Burial Program', 240.00, 'Official membership program of KAAGAPAY MO KARAMAY FUNERAL HOMES CO.', 1, NULL, NULL);

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
(2, '2026-04-06-120000', 'App\\Database\\Migrations\\AddUniqueEmailIndexToUsers', 'default', 'App', 1775480995, 2),
(3, '2026-04-17-120000', 'App\\Database\\Migrations\\CreatePackageServicesTable', 'default', 'App', 1776486206, 3),
(4, '2026-04-18-000000', 'App\\Database\\Migrations\\AllowServiceOnlyApplications', 'default', 'App', 1776486207, 3),
(5, '2026-04-18-120000', 'App\\Database\\Migrations\\CreateBranchManagementApprovalWorkflow', 'default', 'App', 1776486348, 4),
(6, '2026-04-18-140000', 'App\\Database\\Migrations\\CreatePendingPlanHolderRegistrations', 'default', 'App', 1776491869, 5),
(7, '2026-04-18-160000', 'App\\Database\\Migrations\\EnforceSinglePlanHolderPerUser', 'default', 'App', 1776497237, 6),
(8, '2026-04-27-150000', 'App\\Database\\Migrations\\FixActivePlanConsistency', 'default', 'App', 1777302735, 7),
(9, '2026-04-28-100000', 'App\\Database\\Migrations\\NormalizeServiceListStatus', 'default', 'App', 1777380947, 8),
(10, '2026-04-28-110000', 'App\\Database\\Migrations\\NormalizePackagesStatus', 'default', 'App', 1777380947, 8),
(11, '2026-04-28-120000', 'App\\Database\\Migrations\\AddPaymentTypeToPayments', 'default', 'App', 1777380947, 8),
(12, '2026-04-28-130000', 'App\\Database\\Migrations\\CreateActivityLogsTable', 'default', 'App', 1777380947, 8),
(13, '2026-04-28-140000', 'App\\Database\\Migrations\\AddTypeToNotifications', 'default', 'App', 1777380947, 8),
(14, '2026-05-04-090000', 'App\\Database\\Migrations\\AddAdvancePaymentFields', 'default', 'App', 1777990481, 9),
(15, '2026-05-05-090000', 'App\\Database\\Migrations\\CreateMembershipProgramsTable', 'default', 'App', 1777990481, 9),
(16, '2026-05-05-110000', 'App\\Database\\Migrations\\AddPlanHolderRegistrationFields', 'default', 'App', 1777993342, 10),
(17, '2026-05-05-120000', 'App\\Database\\Migrations\\AllowNullReceivedByInPayments', 'default', 'App', 1777993343, 10),
(18, '2026-05-07-100000', 'App\\Database\\Migrations\\AddMembershipTrackingToPlan', 'default', 'App', 1778159267, 11),
(19, '2026-05-07-110000', 'App\\Database\\Migrations\\AddVerificationStatusToBeneficiaries', 'default', 'App', 1778159267, 11),
(20, '2026-05-07-120000', 'App\\Database\\Migrations\\CreateServiceSchedulesTable', 'default', 'App', 1778159267, 11),
(21, '2026-05-07-130000', 'App\\Database\\Migrations\\CreateResourceAssignmentsTable', 'default', 'App', 1778159267, 11),
(22, '2026-05-07-140000', 'App\\Database\\Migrations\\AddNotificationEnhancements', 'default', 'App', 1778160440, 12),
(23, '2026-05-07-150000', 'App\\Database\\Migrations\\CreateServiceSchedulingTablesExpanded', 'default', 'App', 1778160494, 13);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `notification_type` enum('payment','membership','service','schedule','system') DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT 0,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `read_at` datetime DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `type` enum('payment_approved','payment_rejected','service_approved','service_rejected','registration_pending','service_completed','general') NOT NULL DEFAULT 'general' COMMENT 'Notification classification for filtering and display',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `message`, `notification_type`, `is_read`, `priority`, `read_at`, `is_archived`, `type`, `created_at`) VALUES
(1, 8, 'Your plan holder profile was created or linked successfully.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-16 15:27:53'),
(2, 8, 'Your service application has been submitted for review.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-16 17:19:17'),
(3, 8, 'Your service request has been approved.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-16 17:20:12'),
(4, 8, 'Your service application has been submitted for review.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-17 07:20:58'),
(5, 8, 'Your service request has been approved.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-17 07:21:15'),
(6, 8, 'Your service application has been submitted for review.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-17 17:35:58'),
(7, 5, 'New package pending approval from branch admin.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 04:25:57'),
(8, 5, 'New service pending approval from branch admin.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 04:32:34'),
(9, 6, 'Your service request has been approved.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 04:54:45'),
(10, 8, 'Your service request has been approved.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 04:56:08'),
(11, 8, 'Your service application has been submitted for review from a selected service.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 05:02:00'),
(12, 8, 'Your service request has been rejected.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 05:02:33'),
(13, 13, 'Your plan holder registration was received and is pending verification.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 05:03:43'),
(14, 14, 'Your plan holder registration was received and is pending verification.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 05:05:38'),
(15, 15, 'Your account was created. Complete plan holder registration to unlock services and payments.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 07:36:58'),
(16, 8, 'Your service application has been submitted for review from a selected service.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 07:56:04'),
(18, 8, 'Your service request has been rejected.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 07:57:04'),
(19, 5, 'New service pending approval from branch admin.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 08:06:30'),
(20, 6, 'Your service request has been approved.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 08:08:03'),
(21, 15, 'Your plan holder registration was submitted and is pending approval.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 08:13:44'),
(22, 5, 'Renier Manongsong submitted a plan holder registration request for approval.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 08:13:44'),
(23, 6, 'Renier Manongsong submitted a plan holder registration request for approval.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 08:13:44'),
(24, 15, 'Your plan holder registration was approved. You can now apply for services and manage payments.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 08:15:09'),
(25, 15, 'Your account was linked as a plan holder and your Damayan Burial Program plan was registered by branch admin.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 08:25:22'),
(26, 15, 'A payment has been recorded on your plan.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 08:27:06'),
(27, 8, 'Your service application has been submitted for review.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-18 08:36:40'),
(28, 16, 'Your account was created. Complete plan holder registration to unlock services and payments.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-27 14:03:44'),
(29, 16, 'Plan registration details submitted. Proceed with your initial payment.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-27 14:05:52'),
(30, 16, 'Initial payment submitted and pending verification.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-27 14:06:00'),
(31, 16, 'Your cash payment has been confirmed.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-27 14:39:15'),
(32, 16, 'Your cash payment has been confirmed.', 'system', 0, 'normal', NULL, 0, 'general', '2026-04-27 14:52:02'),
(33, 16, 'Your account was linked as a plan holder and your Damayan Burial Program plan was registered by branch admin.', 'system', 0, 'normal', NULL, 0, 'registration_pending', '2026-04-28 06:10:53'),
(34, 5, 'New service pending approval from branch admin.', 'system', 0, 'normal', NULL, 0, 'service_approved', '2026-04-30 23:39:43'),
(35, 5, 'New package pending approval from branch admin.', 'system', 0, 'normal', NULL, 0, 'general', '2026-05-01 07:40:43'),
(36, 16, 'Your application for Balik Probinsya has been submitted.', 'system', 0, 'normal', NULL, 0, 'registration_pending', '2026-05-01 00:24:09'),
(37, 5, 'New package pending approval from branch admin.', 'system', 0, 'normal', NULL, 0, 'general', '2026-05-01 08:25:26'),
(38, 6, 'Your service request has been approved.', 'system', 0, 'normal', NULL, 0, 'service_approved', '2026-05-01 00:26:31'),
(39, 6, 'Your package request has been approved.', 'system', 0, 'normal', NULL, 0, 'service_approved', '2026-05-01 00:27:03'),
(40, 6, 'Your package request has been approved.', 'system', 0, 'normal', NULL, 0, 'service_approved', '2026-05-01 00:27:09'),
(41, 18, 'Your account was created. Complete plan holder registration to unlock services and payments.', 'system', 0, 'normal', NULL, 0, 'registration_pending', '2026-05-05 06:30:59'),
(42, 18, 'Plan registration details submitted. Proceed with your initial payment.', 'system', 0, 'normal', NULL, 0, 'registration_pending', '2026-05-05 06:33:56'),
(43, 18, 'Initial cash payment received. Waiting for registration approval.', 'system', 0, 'normal', NULL, 0, 'payment_approved', '2026-05-05 06:34:12'),
(44, 19, 'Your account was created. Complete plan holder registration to unlock services and payments.', 'system', 0, 'normal', NULL, 0, 'registration_pending', '2026-05-05 06:54:53'),
(45, 19, 'Plan registration details submitted. Proceed with your initial payment.', 'system', 0, 'normal', NULL, 0, 'registration_pending', '2026-05-05 06:56:18'),
(46, 19, 'Initial payment submitted and pending verification.', 'system', 0, 'normal', NULL, 0, 'registration_pending', '2026-05-05 07:03:30'),
(47, 18, 'Your advance payment covering 1 month has been approved. Your membership remains active until June 2026.', 'system', 0, 'normal', NULL, 0, 'payment_approved', '2026-05-05 07:09:07'),
(48, 19, 'Your advance payment covering 1 month has been approved. Your membership remains active until June 2026.', 'system', 0, 'normal', NULL, 0, 'payment_approved', '2026-05-05 07:09:22'),
(49, 16, 'Your payment was rejected. Please verify and resubmit.', 'system', 0, 'normal', NULL, 0, 'payment_rejected', '2026-05-05 07:28:20'),
(50, 19, 'Your payment was rejected. Please verify and resubmit.', 'system', 0, 'normal', NULL, 0, 'payment_rejected', '2026-05-05 07:28:22'),
(51, 19, 'Your advance payment covering 1 month has been approved. Your membership remains active until June 2026.', 'system', 0, 'normal', NULL, 0, 'payment_approved', '2026-05-05 07:32:22'),
(52, 18, 'Your advance payment covering 1 month has been approved. Your membership remains active until June 2026.', 'system', 0, 'normal', NULL, 0, 'payment_approved', '2026-05-05 07:32:31');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `package_id` int(11) NOT NULL,
  `package_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `is_customizable` tinyint(1) DEFAULT 1,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('pending','approved','rejected','inactive') NOT NULL DEFAULT 'approved' COMMENT 'Package approval status: pending (awaiting approval), approved (visible), rejected, inactive (unavailable)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`package_id`, `package_name`, `description`, `base_price`, `is_customizable`, `is_available`, `status`) VALUES
(1, 'SAMPLE PACKAGE 1', 'SAMPLE PACKAGES', 1000.00, 1, 1, 'approved'),
(6, 'sample package 2', '', 10000.00, 1, 1, 'approved'),
(7, 'Sample Package 5', 'Sample Package 5', 20000.00, 1, 1, 'approved'),
(8, 'Sample Package 5', 'Sample Package 5', 20000.00, 1, 1, 'approved');

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

--
-- Dumping data for table `package_items`
--

INSERT INTO `package_items` (`item_id`, `package_id`, `item_name`, `description`) VALUES
(1, 1, 'SAMPLE SERVICE 1', 'SAMPLE SERVICE 1'),
(18, 6, 'Body Retrieval', 'Transport of deceased from location'),
(19, 6, 'Embalming', 'Body preservation and preparation'),
(20, 6, 'Free Makeup', ''),
(21, 6, 'Sample Service 3', ''),
(22, 7, 'Sample Service 3', ''),
(23, 8, 'Sample Service 3', '');

-- --------------------------------------------------------

--
-- Table structure for table `package_services`
--

CREATE TABLE `package_services` (
  `service_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_services`
--

INSERT INTO `package_services` (`service_id`, `package_id`, `service_name`, `description`) VALUES
(1, 1, 'SAMPLE SERVICE 1', 'SAMPLE SERVICE 1');

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

--
-- Dumping data for table `package_versions`
--

INSERT INTO `package_versions` (`version_id`, `package_id`, `price`, `effective_date`, `status`) VALUES
(1, 1, 1000.00, '2026-04-16', 'active'),
(6, 6, 10000.00, '2026-04-17', 'active'),
(7, 6, 20000.00, '2026-05-01', 'active'),
(8, 7, 20000.00, '2026-05-01', 'active'),
(9, 8, 20000.00, '2026-05-01', 'active');

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
  `received_by` int(11) DEFAULT NULL,
  `branch_id` int(11) NOT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('paid','pending','cancelled') DEFAULT 'paid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_type` enum('initial_registration','monthly_contribution','service_payment','addon_payment') NOT NULL DEFAULT 'monthly_contribution' COMMENT 'Payment classification for reporting and filtering',
  `months_covered` int(11) NOT NULL DEFAULT 1,
  `proof_image` varchar(255) DEFAULT NULL,
  `official_receipt_number` varchar(100) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `coverage_start` date DEFAULT NULL,
  `coverage_end` date DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `plan_id`, `amount`, `payment_date`, `payment_method`, `reference_number`, `received_by`, `branch_id`, `remarks`, `status`, `created_at`, `updated_at`, `payment_type`, `months_covered`, `proof_image`, `official_receipt_number`, `verified_at`, `verified_by`, `coverage_start`, `coverage_end`, `deleted_at`) VALUES
(1, 3, 240.00, '2026-04-18', 'cash', NULL, 5, 1, '', 'paid', '2026-04-18 08:27:06', '2026-05-08 15:01:43', 'monthly_contribution', 1, NULL, NULL, NULL, NULL, '2026-04-18', '2026-05-18', NULL),
(2, 4, 240.00, '2026-04-27', 'cash', NULL, 6, 1, 'GCash rejected by branch admin', 'cancelled', '2026-04-27 14:06:00', '2026-05-08 15:01:43', 'monthly_contribution', 1, NULL, NULL, '2026-05-05 15:28:20', 6, '2026-04-27', '2026-05-27', NULL),
(3, 4, 240.00, '2026-04-27', 'cash', NULL, 6, 1, 'Recorded at branch counter', 'paid', '2026-04-27 14:39:15', '2026-05-08 15:01:43', 'monthly_contribution', 1, NULL, NULL, NULL, NULL, '2026-04-27', '2026-05-27', NULL),
(4, 4, 240.00, '2026-04-27', 'cash', NULL, 6, 1, 'Recorded at branch counter', 'paid', '2026-04-27 14:52:02', '2026-05-08 15:01:43', 'monthly_contribution', 1, NULL, NULL, NULL, NULL, '2026-04-27', '2026-05-27', NULL),
(5, 5, 240.00, '2026-05-05', 'cash', NULL, 18, 1, 'Initial payment verified via cash', 'paid', '2026-05-05 14:34:12', '2026-05-08 15:01:43', 'monthly_contribution', 1, NULL, NULL, NULL, NULL, '2026-05-05', '2026-06-05', NULL),
(6, 6, 240.00, '2026-05-05', 'cash', NULL, 6, 1, 'GCash rejected by branch admin', 'cancelled', '2026-05-05 15:03:30', '2026-05-08 15:01:43', 'monthly_contribution', 1, NULL, NULL, '2026-05-05 15:28:22', 6, '2026-05-05', '2026-06-05', NULL),
(7, 5, 240.00, '2026-05-05', 'cash', NULL, 6, 1, 'Recorded at branch counter', 'paid', '2026-05-05 15:09:07', '2026-05-08 15:01:43', 'monthly_contribution', 1, NULL, '123456', '2026-05-05 15:09:07', 6, '2026-05-05', '2026-06-05', NULL),
(8, 6, 240.00, '2026-05-05', 'cash', NULL, 6, 1, 'Recorded at branch counter', 'paid', '2026-05-05 15:09:22', '2026-05-08 15:01:43', 'monthly_contribution', 1, NULL, '1234567', '2026-05-05 15:09:22', 6, '2026-05-05', '2026-06-05', NULL),
(9, 6, 240.00, '2026-05-05', 'cash', NULL, 6, 1, 'Recorded at branch counter', 'paid', '2026-05-05 15:32:22', '2026-05-08 15:01:43', 'monthly_contribution', 1, NULL, '1', '2026-05-05 15:32:22', 6, '2026-05-05', '2026-06-05', NULL),
(10, 5, 240.00, '2026-05-05', 'cash', NULL, 6, 1, 'Recorded at branch counter', 'paid', '2026-05-05 15:32:31', '2026-05-08 15:01:43', 'monthly_contribution', 1, NULL, '122', '2026-05-05 15:32:31', 6, '2026-05-05', '2026-06-05', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pending_packages`
--

CREATE TABLE `pending_packages` (
  `pending_package_id` int(11) UNSIGNED NOT NULL,
  `package_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_customizable` tinyint(1) NOT NULL DEFAULT 1,
  `initial_effective_date` date DEFAULT NULL,
  `service_list_ids` text DEFAULT NULL,
  `created_by` int(11) UNSIGNED NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pending_packages`
--

INSERT INTO `pending_packages` (`pending_package_id`, `package_name`, `description`, `base_price`, `is_customizable`, `initial_effective_date`, `service_list_ids`, `created_by`, `status`, `created_at`) VALUES
(1, 'Sample PAckage 3', 'Sample PAckage 3', 1000000.00, 1, '2026-04-18', '[2,3]', 6, 'pending', '2026-04-18 04:25:57'),
(2, 'Sample Package 5', 'Sample Package 5', 20000.00, 1, '2026-05-01', '[7]', 6, 'approved', '2026-05-01 07:40:43'),
(3, 'Sample Package 5', 'Sample Package 5', 20000.00, 1, '2026-05-01', '[7]', 6, 'approved', '2026-05-01 08:25:26');

-- --------------------------------------------------------

--
-- Table structure for table `pending_plan_holder_registrations`
--

CREATE TABLE `pending_plan_holder_registrations` (
  `pending_registration_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
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
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pending_plan_holder_registrations`
--

INSERT INTO `pending_plan_holder_registrations` (`pending_registration_id`, `user_id`, `branch_id`, `address_no`, `address_street`, `address_barangay`, `address_city`, `date_of_birth`, `place_of_birth`, `age`, `gender`, `civil_status`, `citizenship`, `height`, `weight`, `spouse_name`, `spouse_birthdate`, `spouse_occupation`, `senior_citizen_id`, `organization_affiliation`, `status`, `rejection_notes`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(1, 15, 1, '', 'sitio putol', 'sta isabel', 'Calapan City', '2005-10-04', 'calapan city', 21, 'male', 'Single', 'FIlipino', NULL, NULL, '', NULL, '', '', '', 'approved', NULL, 5, '2026-04-18 08:15:09', '2026-04-18 16:13:44', '2026-04-18 16:15:09');

-- --------------------------------------------------------

--
-- Table structure for table `pending_services`
--

CREATE TABLE `pending_services` (
  `pending_service_id` int(11) UNSIGNED NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `requested_status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` int(11) UNSIGNED NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pending_services`
--

INSERT INTO `pending_services` (`pending_service_id`, `service_name`, `description`, `base_price`, `requested_status`, `created_by`, `status`, `created_at`) VALUES
(1, 'Sample Service 3', '', 10000.00, 'active', 6, 'approved', '2026-04-18 04:32:34'),
(2, 'Free Makeup', '', 10000.00, 'active', 6, 'approved', '2026-04-18 08:06:30'),
(3, 'Sample service 5', 'Sample Service 5', 1000.00, 'active', 6, 'approved', '2026-05-01 07:39:43');

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `plan_id` int(11) NOT NULL,
  `plan_holder_id` int(11) NOT NULL,
  `program_id` int(11) UNSIGNED DEFAULT NULL,
  `monthly_fee` decimal(10,2) NOT NULL,
  `passbook_fee` decimal(10,2) DEFAULT 50.00,
  `start_date` date NOT NULL,
  `status` enum('active','inactive','completed') NOT NULL,
  `months_paid` int(11) DEFAULT 0,
  `legacy_remaining_balance` decimal(10,2) DEFAULT NULL,
  `version_id` int(11) NOT NULL,
  `next_due_date` date DEFAULT NULL,
  `payment_coverage_until` date DEFAULT NULL,
  `overdue_months` int(11) DEFAULT 0,
  `membership_state` enum('active','delinquent','suspended','completed') DEFAULT 'active',
  `coverage_until` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plans`
--

INSERT INTO `plans` (`plan_id`, `plan_holder_id`, `program_id`, `monthly_fee`, `passbook_fee`, `start_date`, `status`, `months_paid`, `legacy_remaining_balance`, `version_id`, `next_due_date`, `payment_coverage_until`, `overdue_months`, `membership_state`, `coverage_until`) VALUES
(1, 6, 1, 240.00, 50.00, '2026-04-18', 'active', 0, 0.00, 1, '2026-06-08', '2026-05-08', 0, 'active', '2026-05-08'),
(2, 7, 1, 240.00, 50.00, '2026-04-18', 'active', 0, 0.00, 6, '2026-06-08', '2026-05-08', 0, 'active', '2026-05-08'),
(3, 8, 1, 240.00, 50.00, '2026-04-18', 'active', 0, 9760.00, 6, '2026-06-08', '2026-05-08', 0, 'active', '2026-05-08'),
(4, 9, 1, 240.00, 50.00, '2026-04-27', 'active', 1, 2640.00, 1, '2026-06-08', '2026-06-08', 0, 'active', '2026-06-08'),
(5, 10, 1, 240.00, 50.00, '2026-05-05', 'inactive', 0, 2880.00, 1, '2026-06-08', '2026-05-08', 0, 'active', '2026-05-08'),
(6, 11, 1, 240.00, 50.00, '2026-05-05', 'inactive', 0, 2880.00, 1, '2026-06-08', '2026-05-08', 0, 'active', '2026-05-08');

-- --------------------------------------------------------

--
-- Table structure for table `plans_backup`
--

CREATE TABLE `plans_backup` (
  `plan_id` int(11) NOT NULL DEFAULT 0,
  `plan_holder_id` int(11) NOT NULL,
  `program_id` int(11) UNSIGNED DEFAULT NULL,
  `package_id` int(11) NOT NULL,
  `monthly_fee` decimal(10,2) NOT NULL,
  `passbook_fee` decimal(10,2) DEFAULT 50.00,
  `start_date` date NOT NULL,
  `status` enum('active','inactive','completed') NOT NULL,
  `months_paid` int(11) DEFAULT 0,
  `remaining_balance` decimal(10,2) DEFAULT 0.00,
  `version_id` int(11) NOT NULL,
  `next_due_date` date DEFAULT NULL,
  `payment_coverage_until` date DEFAULT NULL,
  `overdue_months` int(11) DEFAULT 0,
  `membership_state` enum('active','delinquent','suspended','completed') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plans_backup`
--

INSERT INTO `plans_backup` (`plan_id`, `plan_holder_id`, `program_id`, `package_id`, `monthly_fee`, `passbook_fee`, `start_date`, `status`, `months_paid`, `remaining_balance`, `version_id`, `next_due_date`, `payment_coverage_until`, `overdue_months`, `membership_state`) VALUES
(1, 6, 1, 1, 1000.00, 50.00, '2026-04-18', 'active', 0, 0.00, 1, '2026-06-08', '2026-05-08', 0, 'active'),
(2, 7, 1, 6, 10000.00, 50.00, '2026-04-18', 'active', 0, 0.00, 6, '2026-06-08', '2026-05-08', 0, 'active'),
(3, 8, 1, 6, 10000.00, 50.00, '2026-04-18', 'active', 0, 9760.00, 6, '2026-06-08', '2026-05-08', 0, 'active'),
(4, 9, 1, 1, 240.00, 50.00, '2026-04-27', 'active', 1, 2640.00, 1, '2026-06-08', '2026-06-08', 0, 'active'),
(5, 10, 1, 1, 240.00, 50.00, '2026-05-05', 'inactive', 0, 2880.00, 1, '2026-06-08', '2026-05-08', 0, 'active'),
(6, 11, 1, 1, 240.00, 50.00, '2026-05-05', 'inactive', 0, 2880.00, 1, '2026-06-08', '2026-05-08', 0, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `plan_holders`
--

CREATE TABLE `plan_holders` (
  `plan_holder_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
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
  `branch_id` int(11) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_linked_account` tinyint(1) DEFAULT 0,
  `unique_identifier` varchar(100) DEFAULT NULL,
  `id_control_no` varchar(50) DEFAULT NULL,
  `coordinator` varchar(100) DEFAULT NULL,
  `application_date` date DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_number` varchar(30) DEFAULT NULL,
  `emergency_contact_address` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plan_holders`
--

INSERT INTO `plan_holders` (`plan_holder_id`, `user_id`, `address_no`, `address_street`, `address_barangay`, `address_city`, `date_of_birth`, `place_of_birth`, `age`, `gender`, `civil_status`, `citizenship`, `height`, `weight`, `spouse_name`, `spouse_birthdate`, `spouse_occupation`, `senior_citizen_id`, `organization_affiliation`, `branch_id`, `status`, `created_at`, `is_linked_account`, `unique_identifier`, `id_control_no`, `coordinator`, `application_date`, `emergency_contact_name`, `emergency_contact_number`, `emergency_contact_address`) VALUES
(1, 7, '', '', 'Maidlang', 'Calapan City', NULL, '', NULL, '', '', '', NULL, NULL, '', NULL, '', '', '', 1, 'active', '2026-04-06 16:00:00', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 9, NULL, NULL, '', '', '0000-00-00', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'active', '2026-04-08 16:00:00', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 10, NULL, NULL, '', '', '0000-00-00', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'active', '2026-04-08 16:00:00', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 12, NULL, NULL, '', '', '0000-00-00', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'active', '2026-04-14 05:37:26', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 14, '', '', '', '', NULL, '', NULL, '', '', '', NULL, NULL, '', NULL, '', '', '', 1, 'active', '2026-04-18 07:16:35', 1, 'MIZON-KYLE-496595', NULL, NULL, NULL, NULL, NULL, NULL),
(7, 8, '', 'sitio Putol', 'Managpi', 'Calapan CIty', NULL, '', NULL, '', 'Single', 'Filipino', NULL, NULL, '', NULL, '', '', '', 1, 'active', '2026-04-18 07:17:11', 1, 'TEGIO-IVY-496631', NULL, NULL, NULL, NULL, NULL, NULL),
(8, 15, '', '', '', '', NULL, '', NULL, '', '', '', NULL, NULL, '', NULL, '', '', '', 1, 'active', '2026-04-18 08:15:09', 1, 'PH-00015-20260418', NULL, NULL, NULL, NULL, NULL, NULL),
(9, 16, '', '', '', '', NULL, '', NULL, '', '', '', NULL, NULL, '', NULL, '', '', '', 1, 'active', '2026-04-27 14:05:52', 1, 'DELACRUZ-JUAN-385453', NULL, NULL, NULL, NULL, NULL, NULL),
(10, 18, '', '', 'Palhi', 'Calapan City', '1999-07-06', 'calapan city', 27, 'Female', 'Single', '', NULL, NULL, '', NULL, '', '', '', 1, 'inactive', '2026-05-05 14:33:56', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 19, '', '', 'Managpi', 'Calapan City', '1998-04-09', 'calapan city', 28, 'Male', 'Single', 'FIlipino', NULL, NULL, '', NULL, '', '', '', 1, 'inactive', '2026-05-05 14:56:18', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `resource_assignments`
--

CREATE TABLE `resource_assignments` (
  `assignment_id` int(11) UNSIGNED NOT NULL,
  `schedule_id` int(11) UNSIGNED DEFAULT NULL,
  `staff_id` int(11) UNSIGNED DEFAULT NULL,
  `vehicle_id` int(11) UNSIGNED DEFAULT NULL,
  `resource_type` enum('staff','vehicle','equipment') NOT NULL DEFAULT 'staff',
  `status` enum('assigned','in_use','completed','cancelled') NOT NULL DEFAULT 'assigned',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `service_list_id` int(11) DEFAULT NULL,
  `package_id` int(11) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `service_date` date NOT NULL,
  `service_time` time DEFAULT NULL,
  `burial_location` varchar(150) DEFAULT NULL,
  `assigned_staff` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','ongoing','completed','cancelled') NOT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `plan_holder_id`, `branch_id`, `service_list_id`, `package_id`, `total_cost`, `service_date`, `service_time`, `burial_location`, `assigned_staff`, `notes`, `status`, `deleted_at`) VALUES
(1, 7, 1, NULL, 1, 1000.00, '2026-04-16', NULL, NULL, 12, 'Created from approved service request.', 'pending', NULL),
(2, 7, 1, NULL, 1, 1000.00, '2026-04-17', NULL, NULL, 12, 'Created from approved service request.', 'completed', NULL),
(3, 7, 1, NULL, 1, 1000.00, '2026-04-18', NULL, NULL, 12, 'Created from approved service request.', 'pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `service_applications`
--

CREATE TABLE `service_applications` (
  `application_id` int(11) NOT NULL,
  `plan_holder_id` int(11) NOT NULL,
  `service_list_id` int(11) DEFAULT NULL,
  `package_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_applications`
--

INSERT INTO `service_applications` (`application_id`, `plan_holder_id`, `service_list_id`, `package_id`, `status`, `created_at`) VALUES
(1, 7, NULL, 1, 'approved', '2026-04-16 17:19:17'),
(2, 7, NULL, 1, 'approved', '2026-04-17 07:20:58'),
(3, 7, NULL, 1, 'approved', '2026-04-17 17:35:58'),
(4, 7, 6, NULL, 'rejected', '2026-04-18 05:02:00'),
(5, 7, 6, NULL, 'rejected', '2026-04-18 07:56:04'),
(6, 7, NULL, 6, 'pending', '2026-04-18 08:36:40'),
(7, 9, 6, NULL, 'pending', '2026-05-01 08:24:09');

-- --------------------------------------------------------

--
-- Table structure for table `service_calendar`
--

CREATE TABLE `service_calendar` (
  `calendar_id` int(11) UNSIGNED NOT NULL,
  `branch_id` int(11) UNSIGNED NOT NULL,
  `service_id` int(11) UNSIGNED DEFAULT NULL,
  `plan_holder_id` int(11) UNSIGNED DEFAULT NULL,
  `event_type` enum('funeral','viewing','burial','other') NOT NULL DEFAULT 'funeral',
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `status` enum('scheduled','in-progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `hearse_id` int(11) UNSIGNED DEFAULT NULL,
  `embalmer_id` int(11) UNSIGNED DEFAULT NULL,
  `assigned_staff_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`assigned_staff_ids`)),
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
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
-- Table structure for table `service_list`
--

CREATE TABLE `service_list` (
  `service_list_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_list`
--

INSERT INTO `service_list` (`service_list_id`, `service_name`, `description`, `base_price`, `status`, `is_available`, `created_at`) VALUES
(1, 'Body Retrieval', 'Transport of deceased from location', 5000.00, 'active', 1, '2026-04-17 09:18:53'),
(2, 'Embalming', 'Body preservation and preparation', 8000.00, 'active', 1, '2026-04-17 09:18:53'),
(3, 'Viewing Setup', 'Lights, curtains, registry stand', 10000.00, 'active', 1, '2026-04-17 09:18:53'),
(4, 'Funeral Ceremony', 'Complete funeral arrangement', 30000.00, 'active', 1, '2026-04-17 09:18:53'),
(5, 'Cremation Service', 'Cremation process', 25000.00, 'active', 1, '2026-04-17 09:18:53'),
(6, 'Balik Probinsya', 'Transport to province', 20000.00, 'active', 1, '2026-04-17 09:18:53'),
(7, 'Sample Service 3', '', 10000.00, 'active', 1, '2026-04-18 04:54:45'),
(8, 'Free Makeup', '', 10000.00, 'active', 1, '2026-04-18 08:08:03'),
(9, 'Sample service 5', 'Sample Service 5', 1000.00, 'active', 1, '2026-05-01 08:26:31');

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

--
-- Dumping data for table `service_offers`
--

INSERT INTO `service_offers` (`offer_id`, `service_name`, `description`, `base_price`, `status`, `created_at`) VALUES
(1, 'SAMPLE SERVICE 1', 'SAMPLE SERVICE 1', 1000.00, 'inactive', '2026-04-16 17:16:35');

-- --------------------------------------------------------

--
-- Table structure for table `service_schedules`
--

CREATE TABLE `service_schedules` (
  `schedule_id` int(11) UNSIGNED NOT NULL,
  `service_application_id` int(11) UNSIGNED DEFAULT NULL,
  `service_date` date DEFAULT NULL,
  `service_time` time DEFAULT NULL,
  `branch_id` int(11) UNSIGNED DEFAULT NULL,
  `status` enum('pending','scheduled','ongoing','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_schedules`
--

CREATE TABLE `staff_schedules` (
  `schedule_id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `branch_id` int(11) UNSIGNED NOT NULL,
  `schedule_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duty_type` enum('regular','on-call','training','meeting','other') NOT NULL DEFAULT 'regular',
  `status` enum('scheduled','assigned','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
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
  `reset_token_expiry` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `email`, `first_name`, `middle_name`, `last_name`, `name_extension`, `contact_number`, `role_id`, `branch_id`, `status`, `created_at`, `last_login`, `account_status`, `is_plan_holder`, `must_change_password`, `email_verification_token`, `token_expiry`, `reset_token`, `reset_token_expiry`, `deleted_at`) VALUES
(5, 'bryan', '$2y$10$vJouP0F18O0oD9kcnsfbSuTWlapAV3ty/k2sRPgJG7auFsPx82qDS', 'erazojohnbryan08@gmail.com', 'John Bryan', NULL, 'John Bryan', NULL, '09369111954', 1, NULL, 'active', '2026-04-07 05:43:54', '2026-05-05 06:46:53', 'pending', 0, 0, NULL, NULL, NULL, NULL, NULL),
(6, 'regine', '$2y$10$EVYKLzLmCrxWLxPMPM1Z1e0wYA0pYhrOnO39PU9wgCsKABBfpZ6ae', 'aclanregine2@gmail.com', 'REGINE', 'ILAGAN', 'ACLAN', NULL, '09674073500', 2, 1, 'active', '2026-04-07 05:48:53', '2026-05-07 04:39:22', 'pending', 0, 0, NULL, NULL, NULL, NULL, NULL),
(7, 'ivanmeim_20260407055007', '$2y$10$/og5/NO/3ExSYbDFg662qu2/bZ/aQ7acKIePARdOGFmPt.nH5l.Ku', 'ivanmeim_20260407055007@kaagapay.local', 'Ivan', NULL, 'Meim', NULL, '', 4, 1, 'active', '2026-04-06 16:00:00', NULL, 'pending', 1, 0, NULL, NULL, NULL, NULL, NULL),
(8, 'ivy', '$2y$10$Ixjc29uvcnC2GSZ/c4xMjus5QfSaWHNayBRiU2tU5U6jW1YA7mera', 'ivy@gmail.com', 'Ivy', NULL, 'Tegio', NULL, '09369111954', 4, 1, 'active', '2026-04-09 03:39:05', '2026-04-28 06:09:26', 'verified', 1, 0, NULL, NULL, NULL, NULL, NULL),
(9, 'ivytegio_20260409034320', '$2y$10$bGm0SupxvSQXwO8qXdoiDOL8IMdO4b585cS/WMTut8rQRrzPgRvWG', 'ivytegio_20260409034320@kaagapay.local', 'Ivy', 'Cagalpin', 'Tegio', NULL, NULL, 4, 1, 'active', '2026-04-08 16:00:00', NULL, 'pending', 1, 0, NULL, NULL, NULL, NULL, NULL),
(10, 'johnbryanaclan_20260409042603', '$2y$10$eonjp9LL/kXt9zl0nYlSVOhu8LvOUGTkBv167aSZ2G4kEBeq/EXLi', 'johnbryanaclan_20260409042603@kaagapay.local', 'John Bryan', 'Ilagan', 'Aclan', NULL, NULL, 4, 1, 'active', '2026-04-08 16:00:00', NULL, 'pending', 1, 0, NULL, NULL, NULL, NULL, NULL),
(11, 'mein', '$2y$10$B96o2f71l59IsxtPnk06A.A13xNWMoMQ0pnIFX7acB2FGNkUDOwmq', 'meim@gmail.com', 'Ivan', NULL, 'Meim', NULL, '09674073456', 4, NULL, 'active', '2026-04-09 04:39:51', '2026-04-08 20:40:01', 'pending', 0, 0, NULL, NULL, NULL, NULL, NULL),
(12, 'judelesther', '$2y$10$d6/ws6AYjRqAyLKtRLftlul2L.eyU9l3tIYsDPFySUqoFXaXC8jQy', 'sarmiento@gmail.com', 'Jude Lesther', NULL, 'Sarmiento', NULL, '09674073456', 3, 1, 'active', '2026-04-14 13:37:26', '2026-04-28 06:11:54', 'pending', 1, 0, NULL, NULL, NULL, NULL, NULL),
(13, 'jerevin', '$2y$10$Qa5Sl9hAKjdzlafyX/0QoeUt8qxKE0ZCcjOCBacv268MRnRPG31am', 'rodelsa@gmail.com', 'Jerevin', NULL, 'Rodelas', NULL, '093669111954', 4, NULL, 'active', '2026-04-18 05:03:43', NULL, 'pending', 1, 0, NULL, NULL, NULL, NULL, NULL),
(14, 'kyle', '$2y$10$b5DieTsaLNtKKEj.gI3tuO2njmzebMl1054yGC4tZ6KA9P17wPNBS', 'mizon@gmail.com', 'kyle', NULL, 'Mizon', NULL, '093669111954', 4, NULL, 'active', '2026-04-18 05:05:38', '2026-04-17 23:07:33', 'pending', 1, 0, NULL, NULL, NULL, NULL, NULL),
(15, 'renier', '$2y$10$VcARhAfNr2r7n1q13xwWGuJvrSML3vJgpALc/mWe6AdFsr07IoRGm', 'renier@gmail.com', 'Renier', NULL, 'Manongsong', NULL, '093669111954', 4, 1, 'active', '2026-04-18 07:36:58', '2026-04-18 00:12:35', 'verified', 1, 0, NULL, NULL, NULL, NULL, NULL),
(16, 'juandelacruz', '$2y$10$hjl4oePGz.1tjyPmpmffLuSD2ynJJPaJuD7SBAUeQ1aq8LC4gWPFy', 'juandelacruz@gmail.com', 'Juan', NULL, 'Dela Cruz', NULL, '09876543212', 4, 1, 'active', '2026-04-27 14:03:43', '2026-05-05 06:00:58', 'verified', 1, 0, NULL, NULL, NULL, NULL, NULL),
(18, 'mariasantos', '$2y$10$TsdckxcsoOWvWyxKgaaHK.UBav0/ZSpfsLT2j3rTWJPoDyigoCLri', 'mariasanto@gmail.com', 'Maria', '', 'Santos', NULL, '093669111954', 4, 1, 'active', '2026-05-05 14:30:58', '2026-05-05 06:47:30', 'pending', 1, 0, NULL, NULL, NULL, NULL, NULL),
(19, 'josereyes', '$2y$10$H2zad0BT1KJWdqhkxILAgenJnd9oLqASN8Ma6GWBALbuC64MI5LDK', 'josereyes@gmail.com', 'Jose', '', 'Reyes', NULL, '093669111954', 4, 1, 'active', '2026-05-05 14:54:53', '2026-05-05 06:54:58', 'pending', 1, 0, NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `module` (`module`),
  ADD KEY `target_id` (`target_id`),
  ADD KEY `created_at` (`created_at`);

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
-- Indexes for table `embalmers`
--
ALTER TABLE `embalmers`
  ADD PRIMARY KEY (`embalmer_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `hearses`
--
ALTER TABLE `hearses`
  ADD PRIMARY KEY (`hearse_id`),
  ADD UNIQUE KEY `plate_number` (`plate_number`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `membership_programs`
--
ALTER TABLE `membership_programs`
  ADD PRIMARY KEY (`program_id`);

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
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_notifications_user` (`user_id`),
  ADD KEY `idx_notifications_read` (`is_read`);

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
-- Indexes for table `package_services`
--
ALTER TABLE `package_services`
  ADD KEY `package_services_ibfk_1` (`package_id`);

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
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `idx_payments_status` (`status`),
  ADD KEY `idx_payments_branch` (`branch_id`),
  ADD KEY `idx_payments_plan` (`plan_id`);

--
-- Indexes for table `pending_packages`
--
ALTER TABLE `pending_packages`
  ADD PRIMARY KEY (`pending_package_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `pending_plan_holder_registrations`
--
ALTER TABLE `pending_plan_holder_registrations`
  ADD PRIMARY KEY (`pending_registration_id`),
  ADD KEY `pending_plan_holder_registrations_reviewed_by_foreign` (`reviewed_by`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `pending_services`
--
ALTER TABLE `pending_services`
  ADD PRIMARY KEY (`pending_service_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `plan_holder_id` (`plan_holder_id`),
  ADD KEY `fk_plan_version` (`version_id`),
  ADD KEY `idx_plans_holder` (`plan_holder_id`),
  ADD KEY `idx_plans_status` (`status`),
  ADD KEY `idx_plans_due` (`next_due_date`),
  ADD KEY `fk_plans_program` (`program_id`);

--
-- Indexes for table `plan_holders`
--
ALTER TABLE `plan_holders`
  ADD PRIMARY KEY (`plan_holder_id`),
  ADD UNIQUE KEY `ux_plan_holders_user_id` (`user_id`),
  ADD UNIQUE KEY `unique_identifier` (`unique_identifier`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `resource_assignments`
--
ALTER TABLE `resource_assignments`
  ADD PRIMARY KEY (`assignment_id`);

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
  ADD KEY `assigned_staff` (`assigned_staff`),
  ADD KEY `fk_service_list` (`service_list_id`),
  ADD KEY `idx_services_status` (`status`),
  ADD KEY `idx_services_branch` (`branch_id`);

--
-- Indexes for table `service_applications`
--
ALTER TABLE `service_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `plan_holder_id` (`plan_holder_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `service_calendar`
--
ALTER TABLE `service_calendar`
  ADD PRIMARY KEY (`calendar_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `plan_holder_id` (`plan_holder_id`),
  ADD KEY `event_date` (`event_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `service_costs`
--
ALTER TABLE `service_costs`
  ADD PRIMARY KEY (`cost_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `service_list`
--
ALTER TABLE `service_list`
  ADD PRIMARY KEY (`service_list_id`);

--
-- Indexes for table `service_offers`
--
ALTER TABLE `service_offers`
  ADD PRIMARY KEY (`offer_id`);

--
-- Indexes for table `service_schedules`
--
ALTER TABLE `service_schedules`
  ADD PRIMARY KEY (`schedule_id`);

--
-- Indexes for table `staff_schedules`
--
ALTER TABLE `staff_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `schedule_date` (`schedule_date`),
  ADD KEY `status` (`status`);

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
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `add_ons`
--
ALTER TABLE `add_ons`
  MODIFY `add_on_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- AUTO_INCREMENT for table `embalmers`
--
ALTER TABLE `embalmers`
  MODIFY `embalmer_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hearses`
--
ALTER TABLE `hearses`
  MODIFY `hearse_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `membership_programs`
--
ALTER TABLE `membership_programs`
  MODIFY `program_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `package_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `package_items`
--
ALTER TABLE `package_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `package_versions`
--
ALTER TABLE `package_versions`
  MODIFY `version_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `pending_packages`
--
ALTER TABLE `pending_packages`
  MODIFY `pending_package_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pending_plan_holder_registrations`
--
ALTER TABLE `pending_plan_holder_registrations`
  MODIFY `pending_registration_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pending_services`
--
ALTER TABLE `pending_services`
  MODIFY `pending_service_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `plan_holders`
--
ALTER TABLE `plan_holders`
  MODIFY `plan_holder_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `resource_assignments`
--
ALTER TABLE `resource_assignments`
  MODIFY `assignment_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `service_applications`
--
ALTER TABLE `service_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `service_calendar`
--
ALTER TABLE `service_calendar`
  MODIFY `calendar_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_costs`
--
ALTER TABLE `service_costs`
  MODIFY `cost_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_list`
--
ALTER TABLE `service_list`
  MODIFY `service_list_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `service_offers`
--
ALTER TABLE `service_offers`
  MODIFY `offer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `service_schedules`
--
ALTER TABLE `service_schedules`
  MODIFY `schedule_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_schedules`
--
ALTER TABLE `staff_schedules`
  MODIFY `schedule_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

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
-- Constraints for table `package_services`
--
ALTER TABLE `package_services`
  ADD CONSTRAINT `package_services_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`);

--
-- Constraints for table `package_versions`
--
ALTER TABLE `package_versions`
  ADD CONSTRAINT `package_versions_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`plan_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`plan_id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`);

--
-- Constraints for table `pending_plan_holder_registrations`
--
ALTER TABLE `pending_plan_holder_registrations`
  ADD CONSTRAINT `pending_plan_holder_registrations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pending_plan_holder_registrations_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE SET NULL,
  ADD CONSTRAINT `pending_plan_holder_registrations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `plans`
--
ALTER TABLE `plans`
  ADD CONSTRAINT `fk_plan_version` FOREIGN KEY (`version_id`) REFERENCES `package_versions` (`version_id`),
  ADD CONSTRAINT `fk_plans_holder` FOREIGN KEY (`plan_holder_id`) REFERENCES `plan_holders` (`plan_holder_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_plans_program` FOREIGN KEY (`program_id`) REFERENCES `membership_programs` (`program_id`),
  ADD CONSTRAINT `plans_ibfk_1` FOREIGN KEY (`plan_holder_id`) REFERENCES `plan_holders` (`plan_holder_id`);

--
-- Constraints for table `plan_holders`
--
ALTER TABLE `plan_holders`
  ADD CONSTRAINT `plan_holders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `plan_holders_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`);

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `fk_service_list` FOREIGN KEY (`service_list_id`) REFERENCES `service_list` (`service_list_id`),
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
