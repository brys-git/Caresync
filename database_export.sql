-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: kaagapay_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL COMMENT 'User who performed the action',
  `action` varchar(100) NOT NULL COMMENT 'Action performed',
  `module` varchar(50) NOT NULL COMMENT 'Module affected',
  `target_id` int(10) unsigned DEFAULT NULL COMMENT 'ID of the record affected',
  `description` text DEFAULT NULL COMMENT 'Detailed description',
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Previous values' CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'New values' CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_module` (`module`),
  KEY `idx_target_id` (`target_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,16,'created','user',16,'Registered new client account',NULL,'{\"linked_existing_plan_holder\":false}','::1','2026-05-18 06:35:02'),(3,6,'created','payment',12,'Recorded cash payment for plan #4',NULL,'{\"plan_id\":4,\"amount\":240,\"payment_method\":\"cash\",\"status\":\"paid\"}','::1','2026-05-18 06:44:52'),(4,17,'created','user',17,'Registered new client account',NULL,'{\"linked_existing_plan_holder\":false}','::1','2026-05-18 08:09:20'),(6,6,'created','payment',13,'Recorded cash payment for plan #5',NULL,'{\"plan_id\":5,\"amount\":240,\"payment_method\":\"cash\",\"status\":\"paid\"}','::1','2026-05-18 08:18:45'),(7,6,'created','payment',14,'Recorded cash payment for plan #5',NULL,'{\"plan_id\":5,\"amount\":240,\"payment_method\":\"cash\",\"status\":\"paid\"}','::1','2026-05-18 09:04:23'),(8,6,'created','payment',15,'Recorded cash payment for plan #5',NULL,'{\"plan_id\":5,\"amount\":240,\"payment_method\":\"cash\",\"status\":\"paid\"}','::1','2026-05-20 13:34:29'),(9,18,'created','user',18,'Registered new client account',NULL,'{\"linked_existing_plan_holder\":false}','::1','2026-06-03 07:11:56'),(10,6,'approved','plan_holder',13,'Auto-approved plan holder after initial payment verification','{\"status\":\"inactive\"}','{\"status\":\"active\"}','::1','2026-06-03 07:17:00'),(11,6,'created','payment',16,'Recorded cash payment for plan #6',NULL,'{\"plan_id\":6,\"amount\":240,\"payment_method\":\"cash\",\"status\":\"paid\"}','::1','2026-06-03 07:17:00'),(12,21,'created','user',21,'Registered new client account',NULL,'{\"linked_existing_plan_holder\":false}','::1','2026-06-04 06:29:19'),(13,22,'created','user',22,'Registered new client account',NULL,'{\"linked_existing_plan_holder\":false}','::1','2026-06-04 09:12:15'),(14,5,'created','user',23,'Created user account',NULL,NULL,'::1','2026-06-16 01:00:37'),(15,24,'created','user',24,'Registered new client account',NULL,'{\"linked_existing_plan_holder\":false}','::1','2026-06-16 04:19:21'),(16,6,'approved','plan_holder',14,'Auto-approved plan holder after initial payment verification','{\"status\":\"inactive\"}','{\"status\":\"active\"}','::1','2026-06-16 04:34:05'),(17,6,'created','payment',17,'Recorded cash payment for plan #7',NULL,'{\"plan_id\":7,\"amount\":240,\"payment_method\":\"cash\",\"status\":\"paid\"}','::1','2026-06-16 04:34:05'),(18,25,'created','user',25,'Registered new client account',NULL,'{\"linked_existing_plan_holder\":false}','::1','2026-07-16 13:42:19'),(19,6,'approved','plan_holder',16,'Auto-approved plan holder after initial payment verification','{\"status\":\"inactive\"}','{\"status\":\"active\"}','::1','2026-07-27 11:30:56'),(20,6,'created','payment',18,'Recorded cash payment for plan #8',NULL,'{\"plan_id\":8,\"amount\":240,\"payment_method\":\"cash\",\"status\":\"paid\"}','::1','2026-07-27 11:30:56'),(23,57,'updated','user',57,'Changed account password',NULL,NULL,'::1','2026-08-07 17:23:26'),(27,91,'created','user',91,'Registered new client account',NULL,'{\"linked_existing_plan_holder\":false}','::1','2026-08-08 00:06:44'),(28,6,'uploaded','client_import',13,'Uploaded and parsed \"RECORD PROFILING.docx\" — 36 records staged for review.',NULL,NULL,'::1','2026-08-08 13:41:48');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;

--
-- Table structure for table `add_ons`
--

DROP TABLE IF EXISTS `add_ons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `add_ons` (
  `add_on_id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`add_on_id`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `add_ons_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `add_ons`
--

/*!40000 ALTER TABLE `add_ons` DISABLE KEYS */;
/*!40000 ALTER TABLE `add_ons` ENABLE KEYS */;

--
-- Table structure for table `address_barangays`
--

DROP TABLE IF EXISTS `address_barangays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `address_barangays` (
  `code` varchar(20) NOT NULL,
  `city_code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`code`),
  KEY `city_code` (`city_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `address_barangays`
--

/*!40000 ALTER TABLE `address_barangays` DISABLE KEYS */;
INSERT INTO `address_barangays` VALUES ('0102801001','0102801000','Adams');
/*!40000 ALTER TABLE `address_barangays` ENABLE KEYS */;

--
-- Table structure for table `address_cities`
--

DROP TABLE IF EXISTS `address_cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `address_cities` (
  `code` varchar(20) NOT NULL,
  `province_code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`code`),
  KEY `province_code` (`province_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `address_cities`
--

/*!40000 ALTER TABLE `address_cities` DISABLE KEYS */;
INSERT INTO `address_cities` VALUES ('0102801000','0102800000','Adams'),('0102802000','0102800000','Bacarra'),('0102803000','0102800000','Badoc'),('0102804000','0102800000','Bangui'),('0102805000','0102800000','City of Batac'),('0102806000','0102800000','Burgos'),('0102807000','0102800000','Carasi'),('0102808000','0102800000','Currimao'),('0102809000','0102800000','Dingras'),('0102810000','0102800000','Dumalneg'),('0102811000','0102800000','Banna'),('0102812000','0102800000','City of Laoag'),('0102813000','0102800000','Marcos'),('0102814000','0102800000','Nueva Era'),('0102815000','0102800000','Pagudpud'),('0102816000','0102800000','Paoay'),('0102817000','0102800000','Pasuquin'),('0102818000','0102800000','Piddig'),('0102819000','0102800000','Pinili'),('0102820000','0102800000','San Nicolas'),('0102821000','0102800000','Sarrat'),('0102822000','0102800000','Solsona'),('0102823000','0102800000','Vintar');
/*!40000 ALTER TABLE `address_cities` ENABLE KEYS */;

--
-- Table structure for table `address_provinces`
--

DROP TABLE IF EXISTS `address_provinces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `address_provinces` (
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`code`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `address_provinces`
--

/*!40000 ALTER TABLE `address_provinces` DISABLE KEYS */;
INSERT INTO `address_provinces` VALUES ('1400100000','Abra'),('1600200000','Agusan del Norte'),('1600300000','Agusan del Sur'),('0600400000','Aklan'),('0500500000','Albay'),('0600600000','Antique'),('1408100000','Apayao'),('0307700000','Aurora'),('1900700000','Basilan'),('0300800000','Bataan'),('0200900000','Batanes'),('0401000000','Batangas'),('1401100000','Benguet'),('0807800000','Biliran'),('0701200000','Bohol'),('1001300000','Bukidnon'),('0301400000','Bulacan'),('0201500000','Cagayan'),('0501600000','Camarines Norte'),('0501700000','Camarines Sur'),('1001800000','Camiguin'),('0601900000','Capiz'),('0502000000','Catanduanes'),('0402100000','Cavite'),('0702200000','Cebu'),('1204700000','Cotabato'),('1108200000','Davao de Oro'),('1102300000','Davao del Norte'),('1102400000','Davao del Sur'),('1108600000','Davao Occidental'),('1102500000','Davao Oriental'),('1608500000','Dinagat Islands'),('0802600000','Eastern Samar'),('0607900000','Guimaras'),('1402700000','Ifugao'),('0102800000','Ilocos Norte'),('0102900000','Ilocos Sur'),('0603000000','Iloilo'),('0203100000','Isabela'),('1403200000','Kalinga'),('0103300000','La Union'),('0403400000','Laguna'),('1003500000','Lanao del Norte'),('1903600000','Lanao del Sur'),('0803700000','Leyte'),('1908700000','Maguindanao del Norte'),('1908800000','Maguindanao del Sur'),('1704000000','Marinduque'),('0504100000','Masbate'),('1004200000','Misamis Occidental'),('1004300000','Misamis Oriental'),('1404400000','Mountain Province'),('0604500000','Negros Occidental'),('0704600000','Negros Oriental'),('0804800000','Northern Samar'),('0304900000','Nueva Ecija'),('0205000000','Nueva Vizcaya'),('1705100000','Occidental Mindoro'),('1705200000','Oriental Mindoro'),('1705300000','Palawan'),('0305400000','Pampanga'),('0105500000','Pangasinan'),('0405600000','Quezon'),('0205700000','Quirino'),('0405800000','Rizal'),('1705900000','Romblon'),('0806000000','Samar'),('1208000000','Sarangani'),('0706100000','Siquijor'),('0506200000','Sorsogon'),('1206300000','South Cotabato'),('0806400000','Southern Leyte'),('1206500000','Sultan Kudarat'),('1906600000','Sulu'),('1606700000','Surigao del Norte'),('1606800000','Surigao del Sur'),('0306900000','Tarlac'),('1907000000','Tawi-Tawi'),('0307100000','Zambales'),('0907200000','Zamboanga del Norte'),('0907300000','Zamboanga del Sur'),('0908300000','Zamboanga Sibugay');
/*!40000 ALTER TABLE `address_provinces` ENABLE KEYS */;

--
-- Table structure for table `api_keys`
--

DROP TABLE IF EXISTS `api_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_keys` (
  `key_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `api_secret` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `last_used` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `ip_whitelist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ip_whitelist`)),
  PRIMARY KEY (`key_id`),
  UNIQUE KEY `api_key` (`api_key`),
  KEY `idx_api_key` (`api_key`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `api_keys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_keys`
--

/*!40000 ALTER TABLE `api_keys` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_keys` ENABLE KEYS */;

--
-- Table structure for table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assignments` (
  `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`assignment_id`),
  KEY `service_id` (`service_id`),
  KEY `staff_id` (`staff_id`),
  CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`),
  CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignments`
--

/*!40000 ALTER TABLE `assignments` DISABLE KEYS */;
INSERT INTO `assignments` VALUES (1,1,21,'2026-06-03 22:31:01'),(2,2,23,'2026-06-15 17:03:02'),(3,2,21,'2026-06-23 06:46:49');
/*!40000 ALTER TABLE `assignments` ENABLE KEYS */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `audit_id` int(11) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(100) NOT NULL,
  `record_id` int(11) NOT NULL,
  `action` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `changed_by` int(11) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`audit_id`),
  KEY `idx_table_record` (`table_name`,`record_id`),
  KEY `idx_changed_at` (`changed_at`),
  KEY `idx_changed_by` (`changed_by`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;

--
-- Table structure for table `beneficiaries`
--

DROP TABLE IF EXISTS `beneficiaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `beneficiaries` (
  `beneficiary_id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_holder_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `name_extension` varchar(10) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `relationship` varchar(50) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  PRIMARY KEY (`beneficiary_id`),
  KEY `plan_holder_id` (`plan_holder_id`),
  KEY `idx_beneficiaries_holder` (`plan_holder_id`),
  CONSTRAINT `beneficiaries_ibfk_1` FOREIGN KEY (`plan_holder_id`) REFERENCES `plan_holders` (`plan_holder_id`)
) ENGINE=InnoDB AUTO_INCREMENT=205 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `beneficiaries`
--

/*!40000 ALTER TABLE `beneficiaries` DISABLE KEYS */;
INSERT INTO `beneficiaries` VALUES (5,12,'Carlos Miguel','','Garcia',NULL,'1976-05-14','Husband',1,'pending'),(6,12,'Angelica Marie','','Garcia',NULL,'2005-11-02','Daughter',0,'pending'),(7,13,'Mizon','','Kyle',NULL,'2009-03-12','Uncle',1,'pending'),(8,14,'John Michael','','Santos',NULL,'1985-08-19','Husband',1,'pending'),(9,14,'Angela Mae','','Santos',NULL,'2010-02-02','Daughter',0,'pending'),(10,16,'Michelle Anne','','Mendoza',NULL,'1988-11-23','Wife',1,'pending'),(11,16,'Mendoza','','Mendoza',NULL,'2013-08-11','Son',0,'pending');
/*!40000 ALTER TABLE `beneficiaries` ENABLE KEYS */;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `branch_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,'Calapan Branch',NULL,'Sta.Isabel','Calapan City','Oriental Mindoro',NULL,'Lannie',NULL,'De Mesa',NULL,'Branch Manager','2026-03-01','active','2026-03-31 14:55:32','2026-03-31 14:55:32');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;

--
-- Table structure for table `cash_payment_records`
--

DROP TABLE IF EXISTS `cash_payment_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cash_payment_records` (
  `cash_record_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) unsigned NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `months_covered` int(3) DEFAULT 1,
  `amount` decimal(10,2) NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `recorded_by` int(11) unsigned DEFAULT NULL,
  `recorded_date` date NOT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `verified_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`cash_record_id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `idx_branch` (`branch_id`),
  KEY `idx_receipt` (`receipt_number`),
  KEY `idx_verified` (`verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_payment_records`
--

/*!40000 ALTER TABLE `cash_payment_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `cash_payment_records` ENABLE KEYS */;

--
-- Table structure for table `client_import_batches`
--

DROP TABLE IF EXISTS `client_import_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `client_import_batches` (
  `import_batch_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` int(10) unsigned NOT NULL,
  `uploaded_by` int(10) unsigned NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(10) unsigned DEFAULT NULL,
  `format` varchar(20) NOT NULL DEFAULT 'docx',
  `parse_status` enum('pending','processing','parsed','failed') NOT NULL DEFAULT 'pending',
  `status` enum('staged','committed','discarded') NOT NULL DEFAULT 'staged',
  `total_records` int(10) unsigned NOT NULL DEFAULT 0,
  `ready_count` int(10) unsigned NOT NULL DEFAULT 0,
  `needs_attention_count` int(10) unsigned NOT NULL DEFAULT 0,
  `duplicate_count` int(10) unsigned NOT NULL DEFAULT 0,
  `skipped_count` int(10) unsigned NOT NULL DEFAULT 0,
  `committed_count` int(10) unsigned NOT NULL DEFAULT 0,
  `raw_text` longtext DEFAULT NULL,
  `summary_json` longtext DEFAULT NULL,
  `parse_error` text DEFAULT NULL,
  `committed_at` datetime DEFAULT NULL,
  `committed_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`import_batch_id`),
  KEY `branch_id` (`branch_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_import_batches`
--

/*!40000 ALTER TABLE `client_import_batches` DISABLE KEYS */;
INSERT INTO `client_import_batches` VALUES (13,1,6,'1786196506_0c22eaf7abe872ff7e21.docx','RECORD PROFILING.docx','application/vnd.openxmlformats-officedocument.wordprocessingml.document','client_imports/1786196506_0c22eaf7abe872ff7e21.docx',97030,'docx','parsed','staged',36,32,4,0,0,0,'Coordinator Name: Faustino V. Aclan\nDate of Application: 04-26-2022\nName of Plan Holder: Remegia Bacay Dinglasan\nDate of Birth: 10-20-1958\nAddress: Sitio Ilaya, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Rexel Dinglasan | 12-03-1980 | Son\n2. Mario Dinglasan | 11-22-1985 | Son\n3. Basil Dinglasan | 04-15-1990 | Son\n4. John Paul Dinglasan | 11-05-1998 | Son\n5. Venancio Dinglasan | 03-31-1951 | Husband\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Lino Datinguinoo Ilagan (Deceased)\nDate of Birth: 09-10-1943\nAddress: Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Gregorio Ilagan | 11-28-1966 | Son\n2. Manuel Ilagan | 02-17-1970 | Son\n3. Oellia Ilagan | 12-03-1972 | Daughter\n4. Marilou Ilagan | 08-25-1975 | Daughter\n5. Josephine Ilagan | 02-06-1977 | Daughter\n6. Lonchie Ilagan | 02-02-1980 | Son\n7. Ernesto Ilagan | 04-12-1984 | Son\n8. Rowena Ilagan | 07-16-1985 | Daughter\n9. Maricris Ilagan | 06-20-1986 | Daughter\n10. Mary Jane Ilagan | 05-09-1990 | Daughter\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Faustino Verguera Aclan\nDate of Birth: 02-27-1972\nAddress: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Romel I. Aclan | 09-25-1994 | Son\n2. Rhea I. Aclan | 01-25-1996 | Daughter\n3. Rechel I. Aclan | 05-12-1998 | Daughter\n4. Reynan I. Aclan | 11-14-2002 | Son\n5. Regine I. Aclan | 12-23-2004 | Daughter\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Rolando Ilao Cay\nDate of Birth: 04-18-1975\nAddress: Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Roland P. Cay | 03-28-2000 | Son\n2. Rionel P. Cay | 04-03-2001 | Son\n3. Randel P. Cay | 05-01-2003 | Son\n4. Prince Reymark P. Cay | 06-12-2015 | Son\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Angelita Sajer Laguerta\nDate of Birth: 04-24-1954\nAddress: Sitio B-4, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Orlando S. Laguerta | 01-24-1979 | Son\n2. Rico S. Laguerta | 01-09-1982 | Son\n3. Richard S. Laguerta | 08-31-1987 | Son\n4. Gilbert S. Laguerta | 03-05-1989 | Son\n5. Fejay Rey S. Laguerta | 08-26-1992 | Son\n6. Jay - ar S. Laguerta | 09-21-1995 | Son\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Purperia Mayores Laguerta\nDate of Birth: 11-04-1943\nAddress: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Danilo M. Laguerta | 01-11-1964 | Son\n2. Ruel M. Laguerta | 10-28-1968 | Son\n3. Nowilo M. Laguerta | 04-17-1972 | Son\n4. Amelia M. Laguerta | 04-02-1976 | Daughter\n5. Ariel M. Laguerta | 05-17-1979 | Son\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Jovielyn B. Concepcion\nDate of Birth: 10-11-1994\nAddress: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Arvie B. Concepcion | 11-17-2017 | Daughter\n2. Ana Rose B. Concepcion | 04-14-2019 | Daughter\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Teresita Casapao Concepcion\nDate of Birth: 01-08-1964\nAddress: Sitio Anilao, Batino, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Allan C. Concepcion | 04-29-1981 | Son\n2. Alona C. Concepcion | 06-27-1985 | Daughter\n3. Albert C. Concepcion | 06-23-1983 | Son\n4. Anthony C. Concepcion | 02-05-1987 | Son\n5. Aiza C. Concepcion | 08-08-1990 | Daughter\n6. Arnel C. Concepcion | 04-25-1982 | Son\n7. Arnan C. Concepcion | 03-11-1995 | Son\n8. Alvin C. Concepcion | 06-14-1998 | Son\n9. Abegail C. Concepcion | 06-13-2001 | Daughter\n10. Andy C. Concepcion | 11-01-2004 | Son\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Estrelleta Berguera Aclan\nDate of Birth: 01-15-1954\nAddress: Sitio, Balanga, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Faustino V. Aclan | 02-27-2972 | Son\n2. Remegio B. Aclan | 10-01-1973 | Son\n3. Felix B. Aclan | 06-10-1975 | Son\n4. Melanio V. Aclan | 01-06-1982 | Son\n5. Emeliano B. Aclan | 02-08-1984 | Son\n6. Jose B. Aclan | 02-04-1986 | Son\n7. Cristina A. Macandili | 03-17-1988 | Daughter\n8. Jimmy B. Aclan | 05-16-1989 | Son\n9. Daisy B. Aclan | 12-19-1993 | Daughter\n10. Mario B. Aclan | 05-28-1996 | Son\n11. Rainer B. Aclan | 04-22-2000 | Son\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Arcelita Berguera Santiago\nDate of Birth: 09-10-1950\nAddress: Arangin, Naujan, Oriental Mindoro\nBENEFICIARIES\n1. Edilberto B. Santiago | 03-20-1969 | Son\n2. Armando B. Santiago | 11-28-1971 | Son\n3. Evelyn S. Rivera | 12-28-1973 | Daughter\n4. Diobe B. Santiago | 02-07-1976 | Son\n5. Benjamin B. Santiago | 06-27-1978 | Son\n6. Ricky B. Santiago | 05-20-1980 | Son\n7. Susan S. Macandili | 09-01-1986 | Daughter\n8. Mark Jayson B. Santiago | 02-13-1989 | Son\n9. Randy B. Santiago | 06-14-1991 | Son\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Maria Cristina Zaulda Ordinado\nDate of Birth: 01-28-1993\nAddress: Sitio Balanga, Batino, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Ma. Janica Zaulda | 06-21-2012 | Daughter\n2. Cris Ivan Z. Ordinado | 05-18-2019 | Son\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Ronalyn Bonquin Aclan\nDate of Birth: 05-06-1992\nAddress: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Joyce B. Aclan | 10-03-2010 | Daughter\n2. Junior B. Aclan | 08-08-2016 | Son\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Macaria Evangelista Soriano\nDate of Birth: 10-31-1965\nAddress: Sitio Balaasan, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Pelagia E. Soriano | 10-08-1983 | Daughter\n2. Eulalia S. Ronquillo | 12-10-1985 | Daughter\n3. Maria Grace S. Garez | 01-25-1988 | Daughter\n4. Maria Shiela S. Cleofe | 08-14-1989 | Daughter\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 05-23-2022\nName of Plan Holder: Rocelyn Laguerta Ilagan\nDate of Birth: 12-12-1984\nAddress: Sitio Ilaya, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Summer Crystal Jade L. Ilagan | 01-01-2018 | Daughter\n2. Caihdenn Kade L. Ilagan | 09-20-2022 | Son\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Jonalyn Lanaza Ilagan\nDate of Birth: 03-19-1984\nAddress: Sitio Ilaya, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Ken Aljune L. Ilagan | 05-25-2010 | Son\n2. Kim Aljean L. Ilagan | 12-17-2016 | Daughter\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 05-23-2022\nName of Plan Holder: Anastacia Evangelista Ilagan\nDate of Birth: 04-15-1971\nAddress: Sitio Ilaya, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Alvin E. Ilagan | 09-15-1989 | Son\n2. Albert E. Ilagan | 12-31-1992 | Son\n3. Allan E. Ilagan | 04-08-1995 | Son\n4. Ailyn E. Ilagan | 12-30-1999 | Daughter\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 05-23-2022\nName of Plan Holder: Josephine Ilagan Fontilo\nDate of Birth: 02-06-1977\nAddress: Sitio Ilaya, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Joseph I. Fontilo | 07-10-1994 | Son\n2. Maylord I. Fontilo | 05-08-1996 | Son\n3. Geciell I. Fontilo | 06-16-1998 | Daughter\n4. Darwin I. Fontilo | 12-18-2004 | Son\n5. Maybelle I. Fontilo | 05-26-2007 | Daughter\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 05-23-2022\nName of Plan Holder: Maria Shiela Soriano Cleofe\nDate of Birth: 08-14-1989\nAddress: Sitio Balaasan, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Lanz Aaron S. Cleofe | 07-19-2010 | Son\n2. Lanz Adrian S. Cleofe | 02-04-2013 | Son\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 06-02-2022\nName of Plan Holder: Gerry Magsisi Ilao\nDate of Birth: 09-01-1990\nAddress: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Ghieyan R. Ilao | 10-31-2010 | Son\n2. Glyza R. Ilao | 09-22-2012 | Daughter\n3. Ghiecel R. Ilao | 09-08-2017 | Daughter\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 06-02-2022\nName of Plan Holder: Rinalita Siscar Ilao\nDate of Birth: 01-15-1966\nAddress: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Julita S. Ilao | 01-08-1985 | Daughter\n2. Marck S. Ilao | 11-02-1987 | Son\n3. Ranzel Paul S. Ilao | 12-24-1990 | Son\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 06-0205-04-2022\nName of Plan Holder: Ludecia Clor Ilao\nDate of Birth: 10-09-197304-18-75\nAddress: Camansihan, Calapan City, Oriental Mindoro Address: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Judy Rose I. Malarasta | 06-02-1990 | Daughter\n2. Judy Mark C. Ilao | 11-22-1992 | Son\n3. Judy Ann I. Pagunawan | 01-19-2000 | Daughter\n4. Jhon Mark. C. Ilao | 09-01-2007 | Son\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 06-0205-04-2022\nName of Plan Holder: Gaudencio De Torres Ilao (Deceased)\nDate of Birth: 02-12-196104-18-75\nAddress: Camansihan, Calapan City, Oriental Mindoro Address: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Jessie M. Ilao | 05-20-1980 | Son\n2. Lorena I. Mendoza | 05-31-1984 | Daughter\n3. Leony I. Manalo | 09-18-1986 | Daughter\n4. Lanie M. Ilao | 12-09-1988 | Daughter\n5. James Isaac M. Ilao | 09-28-2009 | Gerry M. Ilao\n6. 09-01-1990 | Son | \n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 06-02-2022\nName of Plan Holder: Inescencia Corpuz Escalona\nDate of Birth: 01-21-1970\nAddress: Sitio Balaasan, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Rommel C. Escalona | 12-15-1989 | Son\n2. Ransel C. Escalona | 05-27-1984 | Son\n3. Rosel C. Escalona | 12-27-2002 | Son\n4. Rayven C. Escalona | 11-05-2005 | Son\n5. Jennefer C. Escalona | 10-07-1992 | Daughter\n6. Jenilyn C. Escalona | 03-11-2001 | Daughter\n7. Jenny C. Escalona | 03-28-1991 | Daughter\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 06-02-2022\nName of Plan Holder: Mayleen Camille Visto Manalo\nDate of Birth: 05-15-1997\nAddress: Sitio Balaasan, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Mark Clyde Gabrielle V. Manalo | 12-26-2019 | Son\n2. Mccaiden Ezekiel V. Manalo | 09-10-2022 | Son\n3. Miesha Amaris V. Manalo | 01-21-2025 | Daughter\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 06-02-2022\nName of Plan Holder: Mark Kelvin Masangkay\nDate of Birth: 11-02-1993\nAddress: Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Mark Xhian Kheal F. Masangkay | 05-08-2018 | Son\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 06-02-2022\nName of Plan Holder: Maria Teressa Datinguinoo Bonquin\nDate of Birth: 04-02-1973\nAddress: Sitio Balaasan, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Mary Danice B. Rayos | 01-08-1999 | Daughter\n2. Daius D. Bonquin | 06-06-2000 | Son\n3. Dairheen D. Bonquin | 05-20-2001 | Daughter\n4. Danver D. Bonquin | 02-05-2004 | Son\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 06-02-2022\nName of Plan Holder: Placida Delos Reyes Bacay\nDate of Birth: 11-15-1956\nAddress: Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Jingle D. Bacay | 01-31-1979 | Son\n2. Jovelyn B. Ilao | 1027-1972 | Daughter\n3. Lino D. Bacay | 09-23-1981 | Son\n4. Cris D. Bacay | 01-07-1983 | Son\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 06-02-2022\nName of Plan Holder: Norberta Manalo Serrano\nDate of Birth: 06-07-1966\nAddress: Sitio Mangindat, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Arnold M. Serrano | 10-17-1993 | Son\n2. Arleo M. Serrano | 11-25-2005 | Son\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 06-02-2022\nName of Plan Holder: Lerma Hernandez Ilagan\nDate of Birth: 01-04-1988\nAddress: Sitio Ilaya, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Ernesto S. Ilagan | 04-12-1984 | Husband\n2. Carl John Paul H. Ilagan | 04-24-2010 | Son\n3. Samantha Faith H. Ilagan | 09-03-2017 | Daughter\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 07-01-2022\nName of Plan Holder: Marites Escarez Ola\nDate of Birth: 08-25-1960\nAddress: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Mark Anthony E. Ola | 07-27-1983 | Son\n2. Sheryl O. Garcellano | 04-06-1988 | Daughter\n3. Reyzan E. Ola | 07-30-1991 | Son\n4. Quezbel O. Jaectin | 07-16-1993 | Daughter\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 07-01-2022\nName of Plan Holder: Teresita Ilagan Concepcion\nDate of Birth: 10-03-1956\nAddress: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Nilo I. Datinggaling | 02-20-1981 | Son\n2. Ryan I. Datinggaling | 05-13-1985 | Son\n3. Eric I. Concepcion | 10-25-1989 | Son\n4. Janeth I. Concepcion | 06-03-1991 | Daughter\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 07-01-2022\nName of Plan Holder: Leticia Javier Datinggaling\nDate of Birth: 03-30-1964\nAddress: Batino, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Jayson J. Datinggaling | 03-24-1988 | Son\n2. Jelyn J. Datinggaling | 02-20-1985 | Daughter\n3. Jay J. Datinggaling | 02-13-1990 | Son\n4. Jeaniht J. Datinggaling | 06-06-1997 | Daughter\n5. Jennilyn J. Datinggaling | 11-28-1998 | Daughter\n6. Jessa J. Datinggaling | 01-04-2000 | Daughter\n7. Lyncel J. Datinggaling | 01-17-2002 | Daughter\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 08-20-2022\nName of Plan Holder: Suzette Carandang Evangelista\nDate of Birth: 06-18-1980\nAddress: Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Precious C. Evangelista | 09-18-1999 | Daughter\n2. John Kelvin C. Evangelista | 10-22-2000 | Son\n3. Princess C. Evangelista | 08-30-2023 | Daughter\n4. John Cyron C. Evangelista | 02-14-2007 | Son\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 08-21-2022\nName of Plan Holder: Gregorio Soriano Ilagan\nDate of Birth: 11-28-1966\nAddress: Sitio Mangindat, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Rey Mark D. Ilagan | 12-09-1989 | Son\n2. Roylan D. Ilagan | 12-27-1991 | Son\n3. Rennalene D. Ilagan | 09-26-1993 | Daughter\n4. Ransel D. Ilagan | 10-20-1995 | Son\n5. Rhona Mae D. Ilagan | 11-20-1999 | Daughter\n6. Ryza Grenda Erika D. Ilagan | 12-18-2005 | Daughter\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 08-22-2022\nName of Plan Holder: Marieta Viana Aclan\nDate of Birth: 11-14-1981\nAddress: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Felix B. Aclan | 06-10-1975 | Husband\n2. Kyla V. Aclan | 12-16-2003 | Daughter\n3. Kian V. Aclan | 07-06-2005 | Son\n4. Kathleen V. Aclan | 07-20-2006 | Daughter\n\n---\n\nCoordinator Name: Faustino V. Aclan\nDate of Application: 11-19-2022\nName of Plan Holder: Editha Magsisi Hernandez\nDate of Birth: 07-03-1964\nAddress: Malubay, Gloria, Oriental Mindoro\nBENEFICIARIES\n1. Jayson M. Hernandez | 12-22-1984 | Son\n2. Joserie M. Hernandez | 05-01-1986 | Daughter\n3. Lerma H. Ilagan | 01-04-1988 | Daughter\n4. Elmer M. Hernandez | 12-04-1990 | Son\n5. Elven M. Hernandez | 10-08-1992 | Son\n6. Arnel M. Hernandez | 03-10-1996 | Son\n7. Mariel M. Hernandez | 08-19-1998 | Daughter','{\"warnings\":[\"Unrecognized paragraph skipped: \\\"-\\\"\"],\"generated_at\":\"2026-08-08 13:41:48\"}',NULL,NULL,NULL,'2026-08-08 13:41:46');
/*!40000 ALTER TABLE `client_import_batches` ENABLE KEYS */;

--
-- Table structure for table `client_import_records`
--

DROP TABLE IF EXISTS `client_import_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `client_import_records` (
  `import_record_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `import_batch_id` int(10) unsigned NOT NULL,
  `source_index` int(10) unsigned NOT NULL,
  `coordinator` varchar(100) DEFAULT NULL,
  `application_date` date DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `name_extension` varchar(10) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address_raw` varchar(500) DEFAULT NULL,
  `address_no` varchar(20) DEFAULT NULL,
  `address_street` varchar(100) DEFAULT NULL,
  `address_barangay` varchar(100) DEFAULT NULL,
  `address_city` varchar(100) DEFAULT NULL,
  `mapped_data` longtext DEFAULT NULL,
  `beneficiaries_json` longtext DEFAULT NULL,
  `extracted_text` text DEFAULT NULL,
  `validation_errors_json` longtext DEFAULT NULL,
  `match_candidates_json` longtext DEFAULT NULL,
  `duplicate_key` varchar(255) DEFAULT NULL,
  `record_status` enum('ready','needs_attention','duplicate','skip') NOT NULL DEFAULT 'needs_attention',
  `admin_decision` enum('pending','create_new','link_existing','skip') NOT NULL DEFAULT 'pending',
  `linked_user_id` int(10) unsigned DEFAULT NULL,
  `linked_plan_holder_id` int(10) unsigned DEFAULT NULL,
  `created_user_id` int(10) unsigned DEFAULT NULL,
  `created_plan_holder_id` int(10) unsigned DEFAULT NULL,
  `created_plan_id` int(10) unsigned DEFAULT NULL,
  `temp_username` varchar(50) DEFAULT NULL,
  `temp_email` varchar(100) DEFAULT NULL,
  `temp_password_hash` varchar(255) DEFAULT NULL,
  `temp_password_plain` varchar(50) DEFAULT NULL,
  `committed_at` datetime DEFAULT NULL,
  `committed_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`import_record_id`),
  KEY `import_batch_id` (`import_batch_id`),
  KEY `record_status` (`record_status`),
  KEY `duplicate_key` (`duplicate_key`),
  KEY `admin_decision` (`admin_decision`),
  CONSTRAINT `fk_import_records_batch` FOREIGN KEY (`import_batch_id`) REFERENCES `client_import_batches` (`import_batch_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=285 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_import_records`
--

/*!40000 ALTER TABLE `client_import_records` DISABLE KEYS */;
INSERT INTO `client_import_records` VALUES (249,13,0,'Faustino V. Aclan','2022-04-26','Remegia','Bacay','Dinglasan','','1958-10-20','Sitio Ilaya, Camansihan, Calapan City, Oriental Mindoro','','Sitio Ilaya','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Rexel\",\"middle_name\":\"\",\"last_name\":\"Dinglasan\",\"name_extension\":\"\",\"date_of_birth\":\"1980-12-03\",\"birthday_raw\":\"12-03-1980\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Mario\",\"middle_name\":\"\",\"last_name\":\"Dinglasan\",\"name_extension\":\"\",\"date_of_birth\":\"1985-11-22\",\"birthday_raw\":\"11-22-1985\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Basil\",\"middle_name\":\"\",\"last_name\":\"Dinglasan\",\"name_extension\":\"\",\"date_of_birth\":\"1990-04-15\",\"birthday_raw\":\"04-15-1990\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"John\",\"middle_name\":\"Paul\",\"last_name\":\"Dinglasan\",\"name_extension\":\"\",\"date_of_birth\":\"1998-11-05\",\"birthday_raw\":\"11-05-1998\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Venancio\",\"middle_name\":\"\",\"last_name\":\"Dinglasan\",\"name_extension\":\"\",\"date_of_birth\":\"1951-03-31\",\"birthday_raw\":\"03-31-1951\",\"relationship\":\"Husband\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 04-26-2022\nName of Plan Holder: Remegia Bacay Dinglasan\nDate of Birth: 10-20-1958\nAddress: Sitio Ilaya, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Rexel Dinglasan | 12-03-1980 | Son\n2. Mario Dinglasan | 11-22-1985 | Son\n3. Basil Dinglasan | 04-15-1990 | Son\n4. John Paul Dinglasan | 11-05-1998 | Son\n5. Venancio Dinglasan | 03-31-1951 | Husband','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','dinglasan|remegia|1958-10-20','ready','pending',NULL,NULL,NULL,NULL,NULL,'rdinglasan','rdinglasan@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(250,13,1,'Faustino V. Aclan','2022-05-04','Lino','Datinguinoo Ilagan','(deceased)','','1943-09-10','Camansihan, Calapan City, Oriental Mindoro','','','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Gregorio\",\"middle_name\":\"\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1966-11-28\",\"birthday_raw\":\"11-28-1966\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Manuel\",\"middle_name\":\"\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1970-02-17\",\"birthday_raw\":\"02-17-1970\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Oellia\",\"middle_name\":\"\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1972-12-03\",\"birthday_raw\":\"12-03-1972\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Marilou\",\"middle_name\":\"\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1975-08-25\",\"birthday_raw\":\"08-25-1975\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Josephine\",\"middle_name\":\"\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1977-02-06\",\"birthday_raw\":\"02-06-1977\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Lonchie\",\"middle_name\":\"\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1980-02-02\",\"birthday_raw\":\"02-02-1980\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Ernesto\",\"middle_name\":\"\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1984-04-12\",\"birthday_raw\":\"04-12-1984\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Rowena\",\"middle_name\":\"\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1985-07-16\",\"birthday_raw\":\"07-16-1985\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Maricris\",\"middle_name\":\"\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1986-06-20\",\"birthday_raw\":\"06-20-1986\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Mary\",\"middle_name\":\"Jane\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1990-05-09\",\"birthday_raw\":\"05-09-1990\",\"relationship\":\"Daughter\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Lino Datinguinoo Ilagan (Deceased)\nDate of Birth: 09-10-1943\nAddress: Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Gregorio Ilagan | 11-28-1966 | Son\n2. Manuel Ilagan | 02-17-1970 | Son\n3. Oellia Ilagan | 12-03-1972 | Daughter\n4. Marilou Ilagan | 08-25-1975 | Daughter\n5. Josephine Ilagan | 02-06-1977 | Daughter\n6. Lonchie Ilagan | 02-02-1980 | Son\n7. Ernesto Ilagan | 04-12-1984 | Son\n8. Rowena Ilagan | 07-16-1985 | Daughter\n9. Maricris Ilagan | 06-20-1986 | Daughter\n10. Mary Jane Ilagan | 05-09-1990 | Daughter','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[{\"type\":\"beneficiary_is_plan_holder\",\"source_index\":33,\"name\":\"Gregorio Ilagan\",\"text\":\"Beneficiary \\\"Gregorio Ilagan\\\" is also a plan holder in this document (record #33, Gregorio Soriano Ilagan).\"}]}','deceased|lino|1943-09-10','ready','pending',NULL,NULL,NULL,NULL,NULL,'ldeceased','ldeceased@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:48'),(251,13,2,'Faustino V. Aclan','2022-05-04','Faustino','Verguera','Aclan','','1972-02-27','Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro','','Sitio Anilao','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Romel\",\"middle_name\":\"I.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"1994-09-25\",\"birthday_raw\":\"09-25-1994\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Rhea\",\"middle_name\":\"I.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"1996-01-25\",\"birthday_raw\":\"01-25-1996\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Rechel\",\"middle_name\":\"I.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"1998-05-12\",\"birthday_raw\":\"05-12-1998\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Reynan\",\"middle_name\":\"I.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"2002-11-14\",\"birthday_raw\":\"11-14-2002\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Regine\",\"middle_name\":\"I.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"2004-12-23\",\"birthday_raw\":\"12-23-2004\",\"relationship\":\"Daughter\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Faustino Verguera Aclan\nDate of Birth: 02-27-1972\nAddress: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Romel I. Aclan | 09-25-1994 | Son\n2. Rhea I. Aclan | 01-25-1996 | Daughter\n3. Rechel I. Aclan | 05-12-1998 | Daughter\n4. Reynan I. Aclan | 11-14-2002 | Son\n5. Regine I. Aclan | 12-23-2004 | Daughter','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[{\"type\":\"plan_holder_is_beneficiary\",\"source_index\":8,\"name\":\"Faustino V. Aclan\",\"text\":\"This person also appears as a beneficiary of record #8 (Estrelleta Berguera Aclan).\"}]}','aclan|faustino|1972-02-27','ready','pending',NULL,NULL,NULL,NULL,NULL,'faclan','faclan@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:48'),(252,13,3,'Faustino V. Aclan','2022-05-04','Rolando','Ilao','Cay','','1975-04-18','Camansihan, Calapan City, Oriental Mindoro','','','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Roland\",\"middle_name\":\"P.\",\"last_name\":\"Cay\",\"name_extension\":\"\",\"date_of_birth\":\"2000-03-28\",\"birthday_raw\":\"03-28-2000\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Rionel\",\"middle_name\":\"P.\",\"last_name\":\"Cay\",\"name_extension\":\"\",\"date_of_birth\":\"2001-04-03\",\"birthday_raw\":\"04-03-2001\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Randel\",\"middle_name\":\"P.\",\"last_name\":\"Cay\",\"name_extension\":\"\",\"date_of_birth\":\"2003-05-01\",\"birthday_raw\":\"05-01-2003\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Prince\",\"middle_name\":\"Reymark P.\",\"last_name\":\"Cay\",\"name_extension\":\"\",\"date_of_birth\":\"2015-06-12\",\"birthday_raw\":\"06-12-2015\",\"relationship\":\"Son\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Rolando Ilao Cay\nDate of Birth: 04-18-1975\nAddress: Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Roland P. Cay | 03-28-2000 | Son\n2. Rionel P. Cay | 04-03-2001 | Son\n3. Randel P. Cay | 05-01-2003 | Son\n4. Prince Reymark P. Cay | 06-12-2015 | Son','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','cay|rolando|1975-04-18','ready','pending',NULL,NULL,NULL,NULL,NULL,'rcay','rcay@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(253,13,4,'Faustino V. Aclan','2022-05-04','Angelita','Sajer','Laguerta','','1954-04-24','Sitio B-4, Camansihan, Calapan City, Oriental Mindoro','','Sitio B-4','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Orlando\",\"middle_name\":\"S.\",\"last_name\":\"Laguerta\",\"name_extension\":\"\",\"date_of_birth\":\"1979-01-24\",\"birthday_raw\":\"01-24-1979\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Rico\",\"middle_name\":\"S.\",\"last_name\":\"Laguerta\",\"name_extension\":\"\",\"date_of_birth\":\"1982-01-09\",\"birthday_raw\":\"01-09-1982\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Richard\",\"middle_name\":\"S.\",\"last_name\":\"Laguerta\",\"name_extension\":\"\",\"date_of_birth\":\"1987-08-31\",\"birthday_raw\":\"08-31-1987\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Gilbert\",\"middle_name\":\"S.\",\"last_name\":\"Laguerta\",\"name_extension\":\"\",\"date_of_birth\":\"1989-03-05\",\"birthday_raw\":\"03-05-1989\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Fejay\",\"middle_name\":\"Rey S.\",\"last_name\":\"Laguerta\",\"name_extension\":\"\",\"date_of_birth\":\"1992-08-26\",\"birthday_raw\":\"08-26-1992\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Jay\",\"middle_name\":\"- ar S.\",\"last_name\":\"Laguerta\",\"name_extension\":\"\",\"date_of_birth\":\"1995-09-21\",\"birthday_raw\":\"09-21-1995\",\"relationship\":\"Son\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Angelita Sajer Laguerta\nDate of Birth: 04-24-1954\nAddress: Sitio B-4, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Orlando S. Laguerta | 01-24-1979 | Son\n2. Rico S. Laguerta | 01-09-1982 | Son\n3. Richard S. Laguerta | 08-31-1987 | Son\n4. Gilbert S. Laguerta | 03-05-1989 | Son\n5. Fejay Rey S. Laguerta | 08-26-1992 | Son\n6. Jay - ar S. Laguerta | 09-21-1995 | Son','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','laguerta|angelita|1954-04-24','ready','pending',NULL,NULL,NULL,NULL,NULL,'alaguerta','alaguerta@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(254,13,5,'Faustino V. Aclan','2022-05-04','Purperia','Mayores','Laguerta','','1943-11-04','Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro','','Sitio Anilao','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Danilo\",\"middle_name\":\"M.\",\"last_name\":\"Laguerta\",\"name_extension\":\"\",\"date_of_birth\":\"1964-01-11\",\"birthday_raw\":\"01-11-1964\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Ruel\",\"middle_name\":\"M.\",\"last_name\":\"Laguerta\",\"name_extension\":\"\",\"date_of_birth\":\"1968-10-28\",\"birthday_raw\":\"10-28-1968\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Nowilo\",\"middle_name\":\"M.\",\"last_name\":\"Laguerta\",\"name_extension\":\"\",\"date_of_birth\":\"1972-04-17\",\"birthday_raw\":\"04-17-1972\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Amelia\",\"middle_name\":\"M.\",\"last_name\":\"Laguerta\",\"name_extension\":\"\",\"date_of_birth\":\"1976-04-02\",\"birthday_raw\":\"04-02-1976\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Ariel\",\"middle_name\":\"M.\",\"last_name\":\"Laguerta\",\"name_extension\":\"\",\"date_of_birth\":\"1979-05-17\",\"birthday_raw\":\"05-17-1979\",\"relationship\":\"Son\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Purperia Mayores Laguerta\nDate of Birth: 11-04-1943\nAddress: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Danilo M. Laguerta | 01-11-1964 | Son\n2. Ruel M. Laguerta | 10-28-1968 | Son\n3. Nowilo M. Laguerta | 04-17-1972 | Son\n4. Amelia M. Laguerta | 04-02-1976 | Daughter\n5. Ariel M. Laguerta | 05-17-1979 | Son','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','laguerta|purperia|1943-11-04','ready','pending',NULL,NULL,NULL,NULL,NULL,'plaguerta','plaguerta@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(255,13,6,'Faustino V. Aclan','2022-05-04','Jovielyn','B.','Concepcion','','1994-10-11','Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro','','Sitio Anilao','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Arvie\",\"middle_name\":\"B.\",\"last_name\":\"Concepcion\",\"name_extension\":\"\",\"date_of_birth\":\"2017-11-17\",\"birthday_raw\":\"11-17-2017\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Ana\",\"middle_name\":\"Rose B.\",\"last_name\":\"Concepcion\",\"name_extension\":\"\",\"date_of_birth\":\"2019-04-14\",\"birthday_raw\":\"04-14-2019\",\"relationship\":\"Daughter\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Jovielyn B. Concepcion\nDate of Birth: 10-11-1994\nAddress: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Arvie B. Concepcion | 11-17-2017 | Daughter\n2. Ana Rose B. Concepcion | 04-14-2019 | Daughter','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','concepcion|jovielyn|1994-10-11','ready','pending',NULL,NULL,NULL,NULL,NULL,'jconcepcion','jconcepcion@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(256,13,7,'Faustino V. Aclan','2022-05-04','Teresita','Casapao','Concepcion','','1964-01-08','Sitio Anilao, Batino, Calapan City, Oriental Mindoro','','Sitio Anilao','Batino','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Allan\",\"middle_name\":\"C.\",\"last_name\":\"Concepcion\",\"name_extension\":\"\",\"date_of_birth\":\"1981-04-29\",\"birthday_raw\":\"04-29-1981\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Alona\",\"middle_name\":\"C.\",\"last_name\":\"Concepcion\",\"name_extension\":\"\",\"date_of_birth\":\"1985-06-27\",\"birthday_raw\":\"06-27-1985\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Albert\",\"middle_name\":\"C.\",\"last_name\":\"Concepcion\",\"name_extension\":\"\",\"date_of_birth\":\"1983-06-23\",\"birthday_raw\":\"06-23-1983\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Anthony\",\"middle_name\":\"C.\",\"last_name\":\"Concepcion\",\"name_extension\":\"\",\"date_of_birth\":\"1987-02-05\",\"birthday_raw\":\"02-05-1987\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Aiza\",\"middle_name\":\"C.\",\"last_name\":\"Concepcion\",\"name_extension\":\"\",\"date_of_birth\":\"1990-08-08\",\"birthday_raw\":\"08-08-1990\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Arnel\",\"middle_name\":\"C.\",\"last_name\":\"Concepcion\",\"name_extension\":\"\",\"date_of_birth\":\"1982-04-25\",\"birthday_raw\":\"04-25-1982\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Arnan\",\"middle_name\":\"C.\",\"last_name\":\"Concepcion\",\"name_extension\":\"\",\"date_of_birth\":\"1995-03-11\",\"birthday_raw\":\"03-11-1995\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Alvin\",\"middle_name\":\"C.\",\"last_name\":\"Concepcion\",\"name_extension\":\"\",\"date_of_birth\":\"1998-06-14\",\"birthday_raw\":\"06-14-1998\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Abegail\",\"middle_name\":\"C.\",\"last_name\":\"Concepcion\",\"name_extension\":\"\",\"date_of_birth\":\"2001-06-13\",\"birthday_raw\":\"06-13-2001\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Andy\",\"middle_name\":\"C.\",\"last_name\":\"Concepcion\",\"name_extension\":\"\",\"date_of_birth\":\"2004-11-01\",\"birthday_raw\":\"11-01-2004\",\"relationship\":\"Son\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Teresita Casapao Concepcion\nDate of Birth: 01-08-1964\nAddress: Sitio Anilao, Batino, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Allan C. Concepcion | 04-29-1981 | Son\n2. Alona C. Concepcion | 06-27-1985 | Daughter\n3. Albert C. Concepcion | 06-23-1983 | Son\n4. Anthony C. Concepcion | 02-05-1987 | Son\n5. Aiza C. Concepcion | 08-08-1990 | Daughter\n6. Arnel C. Concepcion | 04-25-1982 | Son\n7. Arnan C. Concepcion | 03-11-1995 | Son\n8. Alvin C. Concepcion | 06-14-1998 | Son\n9. Abegail C. Concepcion | 06-13-2001 | Daughter\n10. Andy C. Concepcion | 11-01-2004 | Son','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','concepcion|teresita|1964-01-08','ready','pending',NULL,NULL,NULL,NULL,NULL,'tconcepcion','tconcepcion@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(257,13,8,'Faustino V. Aclan','2022-05-04','Estrelleta','Berguera','Aclan','','1954-01-15','Sitio, Balanga, Camansihan, Calapan City, Oriental Mindoro','','Sitio, Balanga','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Faustino\",\"middle_name\":\"V.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":null,\"birthday_raw\":\"02-27-2972\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Remegio\",\"middle_name\":\"B.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"1973-10-01\",\"birthday_raw\":\"10-01-1973\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Felix\",\"middle_name\":\"B.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"1975-06-10\",\"birthday_raw\":\"06-10-1975\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Melanio\",\"middle_name\":\"V.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"1982-01-06\",\"birthday_raw\":\"01-06-1982\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Emeliano\",\"middle_name\":\"B.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"1984-02-08\",\"birthday_raw\":\"02-08-1984\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Jose\",\"middle_name\":\"B.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"1986-02-04\",\"birthday_raw\":\"02-04-1986\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Cristina\",\"middle_name\":\"A.\",\"last_name\":\"Macandili\",\"name_extension\":\"\",\"date_of_birth\":\"1988-03-17\",\"birthday_raw\":\"03-17-1988\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Jimmy\",\"middle_name\":\"B.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"1989-05-16\",\"birthday_raw\":\"05-16-1989\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Daisy\",\"middle_name\":\"B.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"1993-12-19\",\"birthday_raw\":\"12-19-1993\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Mario\",\"middle_name\":\"B.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"1996-05-28\",\"birthday_raw\":\"05-28-1996\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Rainer\",\"middle_name\":\"B.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"2000-04-22\",\"birthday_raw\":\"04-22-2000\",\"relationship\":\"Son\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Estrelleta Berguera Aclan\nDate of Birth: 01-15-1954\nAddress: Sitio, Balanga, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Faustino V. Aclan | 02-27-2972 | Son\n2. Remegio B. Aclan | 10-01-1973 | Son\n3. Felix B. Aclan | 06-10-1975 | Son\n4. Melanio V. Aclan | 01-06-1982 | Son\n5. Emeliano B. Aclan | 02-08-1984 | Son\n6. Jose B. Aclan | 02-04-1986 | Son\n7. Cristina A. Macandili | 03-17-1988 | Daughter\n8. Jimmy B. Aclan | 05-16-1989 | Son\n9. Daisy B. Aclan | 12-19-1993 | Daughter\n10. Mario B. Aclan | 05-28-1996 | Son\n11. Rainer B. Aclan | 04-22-2000 | Son','[{\"field\":\"beneficiaries\",\"level\":\"warning\",\"message\":\"Beneficiary \\\"Faustino V. Aclan\\\" — Invalid date \\\"02-27-2972\\\" — fix manually on the review screen.\"}]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[{\"type\":\"beneficiary_is_plan_holder\",\"source_index\":2,\"name\":\"Faustino V. Aclan\",\"text\":\"Beneficiary \\\"Faustino V. Aclan\\\" is also a plan holder in this document (record #2, Faustino Verguera Aclan).\"}]}','aclan|estrelleta|1954-01-15','needs_attention','pending',NULL,NULL,NULL,NULL,NULL,'eaclan','eaclan@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(258,13,9,'Faustino V. Aclan','2022-05-04','Arcelita','Berguera','Santiago','','1950-09-10','Arangin, Naujan, Oriental Mindoro','','','Arangin','Naujan','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Edilberto\",\"middle_name\":\"B.\",\"last_name\":\"Santiago\",\"name_extension\":\"\",\"date_of_birth\":\"1969-03-20\",\"birthday_raw\":\"03-20-1969\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Armando\",\"middle_name\":\"B.\",\"last_name\":\"Santiago\",\"name_extension\":\"\",\"date_of_birth\":\"1971-11-28\",\"birthday_raw\":\"11-28-1971\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Evelyn\",\"middle_name\":\"S.\",\"last_name\":\"Rivera\",\"name_extension\":\"\",\"date_of_birth\":\"1973-12-28\",\"birthday_raw\":\"12-28-1973\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Diobe\",\"middle_name\":\"B.\",\"last_name\":\"Santiago\",\"name_extension\":\"\",\"date_of_birth\":\"1976-02-07\",\"birthday_raw\":\"02-07-1976\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Benjamin\",\"middle_name\":\"B.\",\"last_name\":\"Santiago\",\"name_extension\":\"\",\"date_of_birth\":\"1978-06-27\",\"birthday_raw\":\"06-27-1978\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Ricky\",\"middle_name\":\"B.\",\"last_name\":\"Santiago\",\"name_extension\":\"\",\"date_of_birth\":\"1980-05-20\",\"birthday_raw\":\"05-20-1980\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Susan\",\"middle_name\":\"S.\",\"last_name\":\"Macandili\",\"name_extension\":\"\",\"date_of_birth\":\"1986-09-01\",\"birthday_raw\":\"09-01-1986\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Mark\",\"middle_name\":\"Jayson B.\",\"last_name\":\"Santiago\",\"name_extension\":\"\",\"date_of_birth\":\"1989-02-13\",\"birthday_raw\":\"02-13-1989\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Randy\",\"middle_name\":\"B.\",\"last_name\":\"Santiago\",\"name_extension\":\"\",\"date_of_birth\":\"1991-06-14\",\"birthday_raw\":\"06-14-1991\",\"relationship\":\"Son\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Arcelita Berguera Santiago\nDate of Birth: 09-10-1950\nAddress: Arangin, Naujan, Oriental Mindoro\nBENEFICIARIES\n1. Edilberto B. Santiago | 03-20-1969 | Son\n2. Armando B. Santiago | 11-28-1971 | Son\n3. Evelyn S. Rivera | 12-28-1973 | Daughter\n4. Diobe B. Santiago | 02-07-1976 | Son\n5. Benjamin B. Santiago | 06-27-1978 | Son\n6. Ricky B. Santiago | 05-20-1980 | Son\n7. Susan S. Macandili | 09-01-1986 | Daughter\n8. Mark Jayson B. Santiago | 02-13-1989 | Son\n9. Randy B. Santiago | 06-14-1991 | Son','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','santiago|arcelita|1950-09-10','ready','pending',NULL,NULL,NULL,NULL,NULL,'asantiago','asantiago@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(259,13,10,'Faustino V. Aclan','2022-05-04','Maria','Cristina Zaulda','Ordinado','','1993-01-28','Sitio Balanga, Batino, Calapan City, Oriental Mindoro','','Sitio Balanga','Batino','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Ma.\",\"middle_name\":\"Janica\",\"last_name\":\"Zaulda\",\"name_extension\":\"\",\"date_of_birth\":\"2012-06-21\",\"birthday_raw\":\"06-21-2012\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Cris\",\"middle_name\":\"Ivan Z.\",\"last_name\":\"Ordinado\",\"name_extension\":\"\",\"date_of_birth\":\"2019-05-18\",\"birthday_raw\":\"05-18-2019\",\"relationship\":\"Son\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Maria Cristina Zaulda Ordinado\nDate of Birth: 01-28-1993\nAddress: Sitio Balanga, Batino, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Ma. Janica Zaulda | 06-21-2012 | Daughter\n2. Cris Ivan Z. Ordinado | 05-18-2019 | Son','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','ordinado|maria|1993-01-28','ready','pending',NULL,NULL,NULL,NULL,NULL,'mordinado','mordinado@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(260,13,11,'Faustino V. Aclan','2022-05-04','Ronalyn','Bonquin','Aclan','','1992-05-06','Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro','','Sitio Anilao','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Joyce\",\"middle_name\":\"B.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"2010-10-03\",\"birthday_raw\":\"10-03-2010\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Junior\",\"middle_name\":\"B.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"2016-08-08\",\"birthday_raw\":\"08-08-2016\",\"relationship\":\"Son\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Ronalyn Bonquin Aclan\nDate of Birth: 05-06-1992\nAddress: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Joyce B. Aclan | 10-03-2010 | Daughter\n2. Junior B. Aclan | 08-08-2016 | Son','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','aclan|ronalyn|1992-05-06','ready','pending',NULL,NULL,NULL,NULL,NULL,'raclan','raclan@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(261,13,12,'Faustino V. Aclan','2022-05-04','Macaria','Evangelista','Soriano','','1965-10-31','Sitio Balaasan, Camansihan, Calapan City, Oriental Mindoro','','Sitio Balaasan','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Pelagia\",\"middle_name\":\"E.\",\"last_name\":\"Soriano\",\"name_extension\":\"\",\"date_of_birth\":\"1983-10-08\",\"birthday_raw\":\"10-08-1983\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Eulalia\",\"middle_name\":\"S.\",\"last_name\":\"Ronquillo\",\"name_extension\":\"\",\"date_of_birth\":\"1985-12-10\",\"birthday_raw\":\"12-10-1985\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Maria\",\"middle_name\":\"Grace S.\",\"last_name\":\"Garez\",\"name_extension\":\"\",\"date_of_birth\":\"1988-01-25\",\"birthday_raw\":\"01-25-1988\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Maria\",\"middle_name\":\"Shiela S.\",\"last_name\":\"Cleofe\",\"name_extension\":\"\",\"date_of_birth\":\"1989-08-14\",\"birthday_raw\":\"08-14-1989\",\"relationship\":\"Daughter\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Macaria Evangelista Soriano\nDate of Birth: 10-31-1965\nAddress: Sitio Balaasan, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Pelagia E. Soriano | 10-08-1983 | Daughter\n2. Eulalia S. Ronquillo | 12-10-1985 | Daughter\n3. Maria Grace S. Garez | 01-25-1988 | Daughter\n4. Maria Shiela S. Cleofe | 08-14-1989 | Daughter','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[{\"type\":\"beneficiary_is_plan_holder\",\"source_index\":17,\"name\":\"Maria Shiela S. Cleofe\",\"text\":\"Beneficiary \\\"Maria Shiela S. Cleofe\\\" is also a plan holder in this document (record #17, Maria Shiela Soriano Cleofe).\"}]}','soriano|macaria|1965-10-31','ready','pending',NULL,NULL,NULL,NULL,NULL,'msoriano','msoriano@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:48'),(262,13,13,'Faustino V. Aclan','2022-05-23','Rocelyn','Laguerta','Ilagan','','1984-12-12','Sitio Ilaya, Camansihan, Calapan City, Oriental Mindoro','','Sitio Ilaya','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Summer\",\"middle_name\":\"Crystal Jade L.\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"2018-01-01\",\"birthday_raw\":\"01-01-2018\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Caihdenn\",\"middle_name\":\"Kade L.\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"2022-09-20\",\"birthday_raw\":\"09-20-2022\",\"relationship\":\"Son\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 05-23-2022\nName of Plan Holder: Rocelyn Laguerta Ilagan\nDate of Birth: 12-12-1984\nAddress: Sitio Ilaya, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Summer Crystal Jade L. Ilagan | 01-01-2018 | Daughter\n2. Caihdenn Kade L. Ilagan | 09-20-2022 | Son','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','ilagan|rocelyn|1984-12-12','ready','pending',NULL,NULL,NULL,NULL,NULL,'rilagan','rilagan@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(263,13,14,'Faustino V. Aclan','2022-05-04','Jonalyn','Lanaza','Ilagan','','1984-03-19','Sitio Ilaya, Camansihan, Calapan City, Oriental Mindoro','','Sitio Ilaya','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Ken\",\"middle_name\":\"Aljune L.\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"2010-05-25\",\"birthday_raw\":\"05-25-2010\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Kim\",\"middle_name\":\"Aljean L.\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"2016-12-17\",\"birthday_raw\":\"12-17-2016\",\"relationship\":\"Daughter\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 05-04-2022\nName of Plan Holder: Jonalyn Lanaza Ilagan\nDate of Birth: 03-19-1984\nAddress: Sitio Ilaya, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Ken Aljune L. Ilagan | 05-25-2010 | Son\n2. Kim Aljean L. Ilagan | 12-17-2016 | Daughter','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','ilagan|jonalyn|1984-03-19','ready','pending',NULL,NULL,NULL,NULL,NULL,'jilagan','jilagan@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(264,13,15,'Faustino V. Aclan','2022-05-23','Anastacia','Evangelista','Ilagan','','1971-04-15','Sitio Ilaya, Camansihan, Calapan City, Oriental Mindoro','','Sitio Ilaya','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Alvin\",\"middle_name\":\"E.\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1989-09-15\",\"birthday_raw\":\"09-15-1989\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Albert\",\"middle_name\":\"E.\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1992-12-31\",\"birthday_raw\":\"12-31-1992\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Allan\",\"middle_name\":\"E.\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1995-04-08\",\"birthday_raw\":\"04-08-1995\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Ailyn\",\"middle_name\":\"E.\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1999-12-30\",\"birthday_raw\":\"12-30-1999\",\"relationship\":\"Daughter\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 05-23-2022\nName of Plan Holder: Anastacia Evangelista Ilagan\nDate of Birth: 04-15-1971\nAddress: Sitio Ilaya, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Alvin E. Ilagan | 09-15-1989 | Son\n2. Albert E. Ilagan | 12-31-1992 | Son\n3. Allan E. Ilagan | 04-08-1995 | Son\n4. Ailyn E. Ilagan | 12-30-1999 | Daughter','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','ilagan|anastacia|1971-04-15','ready','pending',NULL,NULL,NULL,NULL,NULL,'ailagan','ailagan@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(265,13,16,'Faustino V. Aclan','2022-05-23','Josephine','Ilagan','Fontilo','','1977-02-06','Sitio Ilaya, Camansihan, Calapan City, Oriental Mindoro','','Sitio Ilaya','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Joseph\",\"middle_name\":\"I.\",\"last_name\":\"Fontilo\",\"name_extension\":\"\",\"date_of_birth\":\"1994-07-10\",\"birthday_raw\":\"07-10-1994\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Maylord\",\"middle_name\":\"I.\",\"last_name\":\"Fontilo\",\"name_extension\":\"\",\"date_of_birth\":\"1996-05-08\",\"birthday_raw\":\"05-08-1996\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Geciell\",\"middle_name\":\"I.\",\"last_name\":\"Fontilo\",\"name_extension\":\"\",\"date_of_birth\":\"1998-06-16\",\"birthday_raw\":\"06-16-1998\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Darwin\",\"middle_name\":\"I.\",\"last_name\":\"Fontilo\",\"name_extension\":\"\",\"date_of_birth\":\"2004-12-18\",\"birthday_raw\":\"12-18-2004\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Maybelle\",\"middle_name\":\"I.\",\"last_name\":\"Fontilo\",\"name_extension\":\"\",\"date_of_birth\":\"2007-05-26\",\"birthday_raw\":\"05-26-2007\",\"relationship\":\"Daughter\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 05-23-2022\nName of Plan Holder: Josephine Ilagan Fontilo\nDate of Birth: 02-06-1977\nAddress: Sitio Ilaya, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Joseph I. Fontilo | 07-10-1994 | Son\n2. Maylord I. Fontilo | 05-08-1996 | Son\n3. Geciell I. Fontilo | 06-16-1998 | Daughter\n4. Darwin I. Fontilo | 12-18-2004 | Son\n5. Maybelle I. Fontilo | 05-26-2007 | Daughter','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','fontilo|josephine|1977-02-06','ready','pending',NULL,NULL,NULL,NULL,NULL,'jfontilo','jfontilo@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(266,13,17,'Faustino V. Aclan','2022-05-23','Maria','Shiela Soriano','Cleofe','','1989-08-14','Sitio Balaasan, Camansihan, Calapan City, Oriental Mindoro','','Sitio Balaasan','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Lanz\",\"middle_name\":\"Aaron S.\",\"last_name\":\"Cleofe\",\"name_extension\":\"\",\"date_of_birth\":\"2010-07-19\",\"birthday_raw\":\"07-19-2010\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Lanz\",\"middle_name\":\"Adrian S.\",\"last_name\":\"Cleofe\",\"name_extension\":\"\",\"date_of_birth\":\"2013-02-04\",\"birthday_raw\":\"02-04-2013\",\"relationship\":\"Son\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 05-23-2022\nName of Plan Holder: Maria Shiela Soriano Cleofe\nDate of Birth: 08-14-1989\nAddress: Sitio Balaasan, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Lanz Aaron S. Cleofe | 07-19-2010 | Son\n2. Lanz Adrian S. Cleofe | 02-04-2013 | Son','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[{\"type\":\"plan_holder_is_beneficiary\",\"source_index\":12,\"name\":\"Maria Shiela S. Cleofe\",\"text\":\"This person also appears as a beneficiary of record #12 (Macaria Evangelista Soriano).\"}]}','cleofe|maria|1989-08-14','ready','pending',NULL,NULL,NULL,NULL,NULL,'mcleofe','mcleofe@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(267,13,18,'Faustino V. Aclan','2022-06-02','Gerry','Magsisi','Ilao','','1990-09-01','Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro','','Sitio Anilao','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Ghieyan\",\"middle_name\":\"R.\",\"last_name\":\"Ilao\",\"name_extension\":\"\",\"date_of_birth\":\"2010-10-31\",\"birthday_raw\":\"10-31-2010\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Glyza\",\"middle_name\":\"R.\",\"last_name\":\"Ilao\",\"name_extension\":\"\",\"date_of_birth\":\"2012-09-22\",\"birthday_raw\":\"09-22-2012\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Ghiecel\",\"middle_name\":\"R.\",\"last_name\":\"Ilao\",\"name_extension\":\"\",\"date_of_birth\":\"2017-09-08\",\"birthday_raw\":\"09-08-2017\",\"relationship\":\"Daughter\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 06-02-2022\nName of Plan Holder: Gerry Magsisi Ilao\nDate of Birth: 09-01-1990\nAddress: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Ghieyan R. Ilao | 10-31-2010 | Son\n2. Glyza R. Ilao | 09-22-2012 | Daughter\n3. Ghiecel R. Ilao | 09-08-2017 | Daughter','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','ilao|gerry|1990-09-01','ready','pending',NULL,NULL,NULL,NULL,NULL,'gilao','gilao@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(268,13,19,'Faustino V. Aclan','2022-06-02','Rinalita','Siscar','Ilao','','1966-01-15','Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro','','Sitio Anilao','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Julita\",\"middle_name\":\"S.\",\"last_name\":\"Ilao\",\"name_extension\":\"\",\"date_of_birth\":\"1985-01-08\",\"birthday_raw\":\"01-08-1985\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Marck\",\"middle_name\":\"S.\",\"last_name\":\"Ilao\",\"name_extension\":\"\",\"date_of_birth\":\"1987-11-02\",\"birthday_raw\":\"11-02-1987\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Ranzel\",\"middle_name\":\"Paul S.\",\"last_name\":\"Ilao\",\"name_extension\":\"\",\"date_of_birth\":\"1990-12-24\",\"birthday_raw\":\"12-24-1990\",\"relationship\":\"Son\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 06-02-2022\nName of Plan Holder: Rinalita Siscar Ilao\nDate of Birth: 01-15-1966\nAddress: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Julita S. Ilao | 01-08-1985 | Daughter\n2. Marck S. Ilao | 11-02-1987 | Son\n3. Ranzel Paul S. Ilao | 12-24-1990 | Son','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','ilao|rinalita|1966-01-15','ready','pending',NULL,NULL,NULL,NULL,NULL,'rilao','rilao@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(269,13,20,'Faustino V. Aclan',NULL,'Ludecia','Clor','Ilao','',NULL,'Camansihan, Calapan City, Oriental Mindoro Address: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro','','Camansihan, Calapan City, Oriental Mindoro Address: Sitio Anilao','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Judy\",\"middle_name\":\"Rose I.\",\"last_name\":\"Malarasta\",\"name_extension\":\"\",\"date_of_birth\":\"1990-06-02\",\"birthday_raw\":\"06-02-1990\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Judy\",\"middle_name\":\"Mark C.\",\"last_name\":\"Ilao\",\"name_extension\":\"\",\"date_of_birth\":\"1992-11-22\",\"birthday_raw\":\"11-22-1992\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Judy\",\"middle_name\":\"Ann I.\",\"last_name\":\"Pagunawan\",\"name_extension\":\"\",\"date_of_birth\":\"2000-01-19\",\"birthday_raw\":\"01-19-2000\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Jhon\",\"middle_name\":\"Mark. C.\",\"last_name\":\"Ilao\",\"name_extension\":\"\",\"date_of_birth\":\"2007-09-01\",\"birthday_raw\":\"09-01-2007\",\"relationship\":\"Son\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 06-0205-04-2022\nName of Plan Holder: Ludecia Clor Ilao\nDate of Birth: 10-09-197304-18-75\nAddress: Camansihan, Calapan City, Oriental Mindoro Address: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Judy Rose I. Malarasta | 06-02-1990 | Daughter\n2. Judy Mark C. Ilao | 11-22-1992 | Son\n3. Judy Ann I. Pagunawan | 01-19-2000 | Daughter\n4. Jhon Mark. C. Ilao | 09-01-2007 | Son','[{\"field\":\"date_of_birth\",\"level\":\"error\",\"message\":\"Invalid date \\\"10-09-197304-18-75\\\" — fix manually on the review screen.\"},{\"field\":\"general\",\"level\":\"warning\",\"message\":\"Invalid date \\\"06-0205-04-2022\\\" — fix manually on the review screen.\"}]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','ilao|ludecia','needs_attention','pending',NULL,NULL,NULL,NULL,NULL,'lilao','lilao@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(270,13,21,'Faustino V. Aclan',NULL,'Gaudencio','DE Torres Ilao','(deceased)','',NULL,'Camansihan, Calapan City, Oriental Mindoro Address: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro','','Camansihan, Calapan City, Oriental Mindoro Address: Sitio Anilao','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Jessie\",\"middle_name\":\"M.\",\"last_name\":\"Ilao\",\"name_extension\":\"\",\"date_of_birth\":\"1980-05-20\",\"birthday_raw\":\"05-20-1980\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Lorena\",\"middle_name\":\"I.\",\"last_name\":\"Mendoza\",\"name_extension\":\"\",\"date_of_birth\":\"1984-05-31\",\"birthday_raw\":\"05-31-1984\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Leony\",\"middle_name\":\"I.\",\"last_name\":\"Manalo\",\"name_extension\":\"\",\"date_of_birth\":\"1986-09-18\",\"birthday_raw\":\"09-18-1986\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Lanie\",\"middle_name\":\"M.\",\"last_name\":\"Ilao\",\"name_extension\":\"\",\"date_of_birth\":\"1988-12-09\",\"birthday_raw\":\"12-09-1988\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"James\",\"middle_name\":\"Isaac M.\",\"last_name\":\"Ilao\",\"name_extension\":\"\",\"date_of_birth\":\"2009-09-28\",\"birthday_raw\":\"09-28-2009\",\"relationship\":\"Gerry M. Ilao\",\"is_primary\":false},{\"first_name\":\"09-01-1990\",\"middle_name\":\"\",\"last_name\":\"\",\"name_extension\":\"\",\"date_of_birth\":null,\"birthday_raw\":\"Son\",\"relationship\":\"\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 06-0205-04-2022\nName of Plan Holder: Gaudencio De Torres Ilao (Deceased)\nDate of Birth: 02-12-196104-18-75\nAddress: Camansihan, Calapan City, Oriental Mindoro Address: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Jessie M. Ilao | 05-20-1980 | Son\n2. Lorena I. Mendoza | 05-31-1984 | Daughter\n3. Leony I. Manalo | 09-18-1986 | Daughter\n4. Lanie M. Ilao | 12-09-1988 | Daughter\n5. James Isaac M. Ilao | 09-28-2009 | Gerry M. Ilao\n6. 09-01-1990 | Son | ','[{\"field\":\"date_of_birth\",\"level\":\"error\",\"message\":\"Invalid date \\\"02-12-196104-18-75\\\" — fix manually on the review screen.\"},{\"field\":\"beneficiaries\",\"level\":\"error\",\"message\":\"Beneficiary \\\"09-01-1990\\\" is missing a last name.\"},{\"field\":\"beneficiaries\",\"level\":\"error\",\"message\":\"Beneficiary \\\"09-01-1990\\\" is missing a relationship.\"},{\"field\":\"beneficiaries\",\"level\":\"warning\",\"message\":\"Beneficiary \\\"09-01-1990\\\" — Invalid date \\\"Son\\\" — fix manually on the review screen.\"},{\"field\":\"general\",\"level\":\"warning\",\"message\":\"Invalid date \\\"06-0205-04-2022\\\" — fix manually on the review screen.\"}]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','deceased|gaudencio','needs_attention','pending',NULL,NULL,NULL,NULL,NULL,'gdeceased','gdeceased@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(271,13,22,'Faustino V. Aclan','2022-06-02','Inescencia','Corpuz','Escalona','','1970-01-21','Sitio Balaasan, Camansihan, Calapan City, Oriental Mindoro','','Sitio Balaasan','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Rommel\",\"middle_name\":\"C.\",\"last_name\":\"Escalona\",\"name_extension\":\"\",\"date_of_birth\":\"1989-12-15\",\"birthday_raw\":\"12-15-1989\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Ransel\",\"middle_name\":\"C.\",\"last_name\":\"Escalona\",\"name_extension\":\"\",\"date_of_birth\":\"1984-05-27\",\"birthday_raw\":\"05-27-1984\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Rosel\",\"middle_name\":\"C.\",\"last_name\":\"Escalona\",\"name_extension\":\"\",\"date_of_birth\":\"2002-12-27\",\"birthday_raw\":\"12-27-2002\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Rayven\",\"middle_name\":\"C.\",\"last_name\":\"Escalona\",\"name_extension\":\"\",\"date_of_birth\":\"2005-11-05\",\"birthday_raw\":\"11-05-2005\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Jennefer\",\"middle_name\":\"C.\",\"last_name\":\"Escalona\",\"name_extension\":\"\",\"date_of_birth\":\"1992-10-07\",\"birthday_raw\":\"10-07-1992\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Jenilyn\",\"middle_name\":\"C.\",\"last_name\":\"Escalona\",\"name_extension\":\"\",\"date_of_birth\":\"2001-03-11\",\"birthday_raw\":\"03-11-2001\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Jenny\",\"middle_name\":\"C.\",\"last_name\":\"Escalona\",\"name_extension\":\"\",\"date_of_birth\":\"1991-03-28\",\"birthday_raw\":\"03-28-1991\",\"relationship\":\"Daughter\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 06-02-2022\nName of Plan Holder: Inescencia Corpuz Escalona\nDate of Birth: 01-21-1970\nAddress: Sitio Balaasan, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Rommel C. Escalona | 12-15-1989 | Son\n2. Ransel C. Escalona | 05-27-1984 | Son\n3. Rosel C. Escalona | 12-27-2002 | Son\n4. Rayven C. Escalona | 11-05-2005 | Son\n5. Jennefer C. Escalona | 10-07-1992 | Daughter\n6. Jenilyn C. Escalona | 03-11-2001 | Daughter\n7. Jenny C. Escalona | 03-28-1991 | Daughter','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','escalona|inescencia|1970-01-21','ready','pending',NULL,NULL,NULL,NULL,NULL,'iescalona','iescalona@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(272,13,23,'Faustino V. Aclan','2022-06-02','Mayleen','Camille Visto','Manalo','','1997-05-15','Sitio Balaasan, Camansihan, Calapan City, Oriental Mindoro','','Sitio Balaasan','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Mark\",\"middle_name\":\"Clyde Gabrielle V.\",\"last_name\":\"Manalo\",\"name_extension\":\"\",\"date_of_birth\":\"2019-12-26\",\"birthday_raw\":\"12-26-2019\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Mccaiden\",\"middle_name\":\"Ezekiel V.\",\"last_name\":\"Manalo\",\"name_extension\":\"\",\"date_of_birth\":\"2022-09-10\",\"birthday_raw\":\"09-10-2022\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Miesha\",\"middle_name\":\"Amaris V.\",\"last_name\":\"Manalo\",\"name_extension\":\"\",\"date_of_birth\":\"2025-01-21\",\"birthday_raw\":\"01-21-2025\",\"relationship\":\"Daughter\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 06-02-2022\nName of Plan Holder: Mayleen Camille Visto Manalo\nDate of Birth: 05-15-1997\nAddress: Sitio Balaasan, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Mark Clyde Gabrielle V. Manalo | 12-26-2019 | Son\n2. Mccaiden Ezekiel V. Manalo | 09-10-2022 | Son\n3. Miesha Amaris V. Manalo | 01-21-2025 | Daughter','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','manalo|mayleen|1997-05-15','ready','pending',NULL,NULL,NULL,NULL,NULL,'mmanalo','mmanalo@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(273,13,24,'Faustino V. Aclan','2022-06-02','Mark','Kelvin','Masangkay','','1993-11-02','Camansihan, Calapan City, Oriental Mindoro','','','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Mark\",\"middle_name\":\"Xhian Kheal F.\",\"last_name\":\"Masangkay\",\"name_extension\":\"\",\"date_of_birth\":\"2018-05-08\",\"birthday_raw\":\"05-08-2018\",\"relationship\":\"Son\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 06-02-2022\nName of Plan Holder: Mark Kelvin Masangkay\nDate of Birth: 11-02-1993\nAddress: Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Mark Xhian Kheal F. Masangkay | 05-08-2018 | Son','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','masangkay|mark|1993-11-02','ready','pending',NULL,NULL,NULL,NULL,NULL,'mmasangkay','mmasangkay@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(274,13,25,'Faustino V. Aclan','2022-06-02','Maria','Teressa Datinguinoo','Bonquin','','1973-04-02','Sitio Balaasan, Camansihan, Calapan City, Oriental Mindoro','','Sitio Balaasan','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Mary\",\"middle_name\":\"Danice B.\",\"last_name\":\"Rayos\",\"name_extension\":\"\",\"date_of_birth\":\"1999-01-08\",\"birthday_raw\":\"01-08-1999\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Daius\",\"middle_name\":\"D.\",\"last_name\":\"Bonquin\",\"name_extension\":\"\",\"date_of_birth\":\"2000-06-06\",\"birthday_raw\":\"06-06-2000\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Dairheen\",\"middle_name\":\"D.\",\"last_name\":\"Bonquin\",\"name_extension\":\"\",\"date_of_birth\":\"2001-05-20\",\"birthday_raw\":\"05-20-2001\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Danver\",\"middle_name\":\"D.\",\"last_name\":\"Bonquin\",\"name_extension\":\"\",\"date_of_birth\":\"2004-02-05\",\"birthday_raw\":\"02-05-2004\",\"relationship\":\"Son\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 06-02-2022\nName of Plan Holder: Maria Teressa Datinguinoo Bonquin\nDate of Birth: 04-02-1973\nAddress: Sitio Balaasan, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Mary Danice B. Rayos | 01-08-1999 | Daughter\n2. Daius D. Bonquin | 06-06-2000 | Son\n3. Dairheen D. Bonquin | 05-20-2001 | Daughter\n4. Danver D. Bonquin | 02-05-2004 | Son','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','bonquin|maria|1973-04-02','ready','pending',NULL,NULL,NULL,NULL,NULL,'mbonquin','mbonquin@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(275,13,26,'Faustino V. Aclan','2022-06-02','Placida','Delos Reyes','Bacay','','1956-11-15','Camansihan, Calapan City, Oriental Mindoro','','','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Jingle\",\"middle_name\":\"D.\",\"last_name\":\"Bacay\",\"name_extension\":\"\",\"date_of_birth\":\"1979-01-31\",\"birthday_raw\":\"01-31-1979\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Jovelyn\",\"middle_name\":\"B.\",\"last_name\":\"Ilao\",\"name_extension\":\"\",\"date_of_birth\":null,\"birthday_raw\":\"1027-1972\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Lino\",\"middle_name\":\"D.\",\"last_name\":\"Bacay\",\"name_extension\":\"\",\"date_of_birth\":\"1981-09-23\",\"birthday_raw\":\"09-23-1981\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Cris\",\"middle_name\":\"D.\",\"last_name\":\"Bacay\",\"name_extension\":\"\",\"date_of_birth\":\"1983-01-07\",\"birthday_raw\":\"01-07-1983\",\"relationship\":\"Son\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 06-02-2022\nName of Plan Holder: Placida Delos Reyes Bacay\nDate of Birth: 11-15-1956\nAddress: Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Jingle D. Bacay | 01-31-1979 | Son\n2. Jovelyn B. Ilao | 1027-1972 | Daughter\n3. Lino D. Bacay | 09-23-1981 | Son\n4. Cris D. Bacay | 01-07-1983 | Son','[{\"field\":\"beneficiaries\",\"level\":\"warning\",\"message\":\"Beneficiary \\\"Jovelyn B. Ilao\\\" — Invalid date \\\"1027-1972\\\" — fix manually on the review screen.\"}]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','bacay|placida|1956-11-15','needs_attention','pending',NULL,NULL,NULL,NULL,NULL,'pbacay','pbacay@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(276,13,27,'Faustino V. Aclan','2022-06-02','Norberta','Manalo','Serrano','','1966-06-07','Sitio Mangindat, Camansihan, Calapan City, Oriental Mindoro','','Sitio Mangindat','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Arnold\",\"middle_name\":\"M.\",\"last_name\":\"Serrano\",\"name_extension\":\"\",\"date_of_birth\":\"1993-10-17\",\"birthday_raw\":\"10-17-1993\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Arleo\",\"middle_name\":\"M.\",\"last_name\":\"Serrano\",\"name_extension\":\"\",\"date_of_birth\":\"2005-11-25\",\"birthday_raw\":\"11-25-2005\",\"relationship\":\"Son\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 06-02-2022\nName of Plan Holder: Norberta Manalo Serrano\nDate of Birth: 06-07-1966\nAddress: Sitio Mangindat, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Arnold M. Serrano | 10-17-1993 | Son\n2. Arleo M. Serrano | 11-25-2005 | Son','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','serrano|norberta|1966-06-07','ready','pending',NULL,NULL,NULL,NULL,NULL,'nserrano','nserrano@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(277,13,28,'Faustino V. Aclan','2022-06-02','Lerma','Hernandez','Ilagan','','1988-01-04','Sitio Ilaya, Camansihan, Calapan City, Oriental Mindoro','','Sitio Ilaya','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Ernesto\",\"middle_name\":\"S.\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1984-04-12\",\"birthday_raw\":\"04-12-1984\",\"relationship\":\"Husband\",\"is_primary\":false},{\"first_name\":\"Carl\",\"middle_name\":\"John Paul H.\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"2010-04-24\",\"birthday_raw\":\"04-24-2010\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Samantha\",\"middle_name\":\"Faith H.\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"2017-09-03\",\"birthday_raw\":\"09-03-2017\",\"relationship\":\"Daughter\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 06-02-2022\nName of Plan Holder: Lerma Hernandez Ilagan\nDate of Birth: 01-04-1988\nAddress: Sitio Ilaya, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Ernesto S. Ilagan | 04-12-1984 | Husband\n2. Carl John Paul H. Ilagan | 04-24-2010 | Son\n3. Samantha Faith H. Ilagan | 09-03-2017 | Daughter','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[{\"type\":\"plan_holder_is_beneficiary\",\"source_index\":35,\"name\":\"Lerma H. Ilagan\",\"text\":\"This person also appears as a beneficiary of record #35 (Editha Magsisi Hernandez).\"}]}','ilagan|lerma|1988-01-04','ready','pending',NULL,NULL,NULL,NULL,NULL,'lilagan','lilagan@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:48'),(278,13,29,'Faustino V. Aclan','2022-07-01','Marites','Escarez','Ola','','1960-08-25','Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro','','Sitio Anilao','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Mark\",\"middle_name\":\"Anthony E.\",\"last_name\":\"Ola\",\"name_extension\":\"\",\"date_of_birth\":\"1983-07-27\",\"birthday_raw\":\"07-27-1983\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Sheryl\",\"middle_name\":\"O.\",\"last_name\":\"Garcellano\",\"name_extension\":\"\",\"date_of_birth\":\"1988-04-06\",\"birthday_raw\":\"04-06-1988\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Reyzan\",\"middle_name\":\"E.\",\"last_name\":\"Ola\",\"name_extension\":\"\",\"date_of_birth\":\"1991-07-30\",\"birthday_raw\":\"07-30-1991\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Quezbel\",\"middle_name\":\"O.\",\"last_name\":\"Jaectin\",\"name_extension\":\"\",\"date_of_birth\":\"1993-07-16\",\"birthday_raw\":\"07-16-1993\",\"relationship\":\"Daughter\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 07-01-2022\nName of Plan Holder: Marites Escarez Ola\nDate of Birth: 08-25-1960\nAddress: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Mark Anthony E. Ola | 07-27-1983 | Son\n2. Sheryl O. Garcellano | 04-06-1988 | Daughter\n3. Reyzan E. Ola | 07-30-1991 | Son\n4. Quezbel O. Jaectin | 07-16-1993 | Daughter','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','ola|marites|1960-08-25','ready','pending',NULL,NULL,NULL,NULL,NULL,'mola','mola@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(279,13,30,'Faustino V. Aclan','2022-07-01','Teresita','Ilagan','Concepcion','','1956-10-03','Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro','','Sitio Anilao','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Nilo\",\"middle_name\":\"I.\",\"last_name\":\"Datinggaling\",\"name_extension\":\"\",\"date_of_birth\":\"1981-02-20\",\"birthday_raw\":\"02-20-1981\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Ryan\",\"middle_name\":\"I.\",\"last_name\":\"Datinggaling\",\"name_extension\":\"\",\"date_of_birth\":\"1985-05-13\",\"birthday_raw\":\"05-13-1985\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Eric\",\"middle_name\":\"I.\",\"last_name\":\"Concepcion\",\"name_extension\":\"\",\"date_of_birth\":\"1989-10-25\",\"birthday_raw\":\"10-25-1989\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Janeth\",\"middle_name\":\"I.\",\"last_name\":\"Concepcion\",\"name_extension\":\"\",\"date_of_birth\":\"1991-06-03\",\"birthday_raw\":\"06-03-1991\",\"relationship\":\"Daughter\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 07-01-2022\nName of Plan Holder: Teresita Ilagan Concepcion\nDate of Birth: 10-03-1956\nAddress: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Nilo I. Datinggaling | 02-20-1981 | Son\n2. Ryan I. Datinggaling | 05-13-1985 | Son\n3. Eric I. Concepcion | 10-25-1989 | Son\n4. Janeth I. Concepcion | 06-03-1991 | Daughter','[]','{\"candidates\":[{\"source\":\"batch\",\"id\":7,\"full_name\":\"Teresita Casapao Concepcion\",\"date_of_birth\":\"1964-01-08\",\"score\":0.6,\"reason\":\"A similar record appears earlier in this same document.\"}],\"status\":\"ready\",\"informational\":[]}','concepcion|teresita|1956-10-03','ready','pending',NULL,NULL,NULL,NULL,NULL,'tconcepcion_2','tconcepcion_2@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(280,13,31,'Faustino V. Aclan','2022-07-01','Leticia','Javier','Datinggaling','','1964-03-30','Batino, Calapan City, Oriental Mindoro','','','Batino','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Jayson\",\"middle_name\":\"J.\",\"last_name\":\"Datinggaling\",\"name_extension\":\"\",\"date_of_birth\":\"1988-03-24\",\"birthday_raw\":\"03-24-1988\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Jelyn\",\"middle_name\":\"J.\",\"last_name\":\"Datinggaling\",\"name_extension\":\"\",\"date_of_birth\":\"1985-02-20\",\"birthday_raw\":\"02-20-1985\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Jay\",\"middle_name\":\"J.\",\"last_name\":\"Datinggaling\",\"name_extension\":\"\",\"date_of_birth\":\"1990-02-13\",\"birthday_raw\":\"02-13-1990\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Jeaniht\",\"middle_name\":\"J.\",\"last_name\":\"Datinggaling\",\"name_extension\":\"\",\"date_of_birth\":\"1997-06-06\",\"birthday_raw\":\"06-06-1997\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Jennilyn\",\"middle_name\":\"J.\",\"last_name\":\"Datinggaling\",\"name_extension\":\"\",\"date_of_birth\":\"1998-11-28\",\"birthday_raw\":\"11-28-1998\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Jessa\",\"middle_name\":\"J.\",\"last_name\":\"Datinggaling\",\"name_extension\":\"\",\"date_of_birth\":\"2000-01-04\",\"birthday_raw\":\"01-04-2000\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Lyncel\",\"middle_name\":\"J.\",\"last_name\":\"Datinggaling\",\"name_extension\":\"\",\"date_of_birth\":\"2002-01-17\",\"birthday_raw\":\"01-17-2002\",\"relationship\":\"Daughter\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 07-01-2022\nName of Plan Holder: Leticia Javier Datinggaling\nDate of Birth: 03-30-1964\nAddress: Batino, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Jayson J. Datinggaling | 03-24-1988 | Son\n2. Jelyn J. Datinggaling | 02-20-1985 | Daughter\n3. Jay J. Datinggaling | 02-13-1990 | Son\n4. Jeaniht J. Datinggaling | 06-06-1997 | Daughter\n5. Jennilyn J. Datinggaling | 11-28-1998 | Daughter\n6. Jessa J. Datinggaling | 01-04-2000 | Daughter\n7. Lyncel J. Datinggaling | 01-17-2002 | Daughter','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','datinggaling|leticia|1964-03-30','ready','pending',NULL,NULL,NULL,NULL,NULL,'ldatinggaling','ldatinggaling@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(281,13,32,'Faustino V. Aclan','2022-08-20','Suzette','Carandang','Evangelista','','1980-06-18','Camansihan, Calapan City, Oriental Mindoro','','','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Precious\",\"middle_name\":\"C.\",\"last_name\":\"Evangelista\",\"name_extension\":\"\",\"date_of_birth\":\"1999-09-18\",\"birthday_raw\":\"09-18-1999\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"John\",\"middle_name\":\"Kelvin C.\",\"last_name\":\"Evangelista\",\"name_extension\":\"\",\"date_of_birth\":\"2000-10-22\",\"birthday_raw\":\"10-22-2000\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Princess\",\"middle_name\":\"C.\",\"last_name\":\"Evangelista\",\"name_extension\":\"\",\"date_of_birth\":\"2023-08-30\",\"birthday_raw\":\"08-30-2023\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"John\",\"middle_name\":\"Cyron C.\",\"last_name\":\"Evangelista\",\"name_extension\":\"\",\"date_of_birth\":\"2007-02-14\",\"birthday_raw\":\"02-14-2007\",\"relationship\":\"Son\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 08-20-2022\nName of Plan Holder: Suzette Carandang Evangelista\nDate of Birth: 06-18-1980\nAddress: Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Precious C. Evangelista | 09-18-1999 | Daughter\n2. John Kelvin C. Evangelista | 10-22-2000 | Son\n3. Princess C. Evangelista | 08-30-2023 | Daughter\n4. John Cyron C. Evangelista | 02-14-2007 | Son','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','evangelista|suzette|1980-06-18','ready','pending',NULL,NULL,NULL,NULL,NULL,'sevangelista','sevangelista@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(282,13,33,'Faustino V. Aclan','2022-08-21','Gregorio','Soriano','Ilagan','','1966-11-28','Sitio Mangindat, Camansihan, Calapan City, Oriental Mindoro','','Sitio Mangindat','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Rey\",\"middle_name\":\"Mark D.\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1989-12-09\",\"birthday_raw\":\"12-09-1989\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Roylan\",\"middle_name\":\"D.\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1991-12-27\",\"birthday_raw\":\"12-27-1991\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Rennalene\",\"middle_name\":\"D.\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1993-09-26\",\"birthday_raw\":\"09-26-1993\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Ransel\",\"middle_name\":\"D.\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1995-10-20\",\"birthday_raw\":\"10-20-1995\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Rhona\",\"middle_name\":\"Mae D.\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1999-11-20\",\"birthday_raw\":\"11-20-1999\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Ryza\",\"middle_name\":\"Grenda Erika D.\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"2005-12-18\",\"birthday_raw\":\"12-18-2005\",\"relationship\":\"Daughter\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 08-21-2022\nName of Plan Holder: Gregorio Soriano Ilagan\nDate of Birth: 11-28-1966\nAddress: Sitio Mangindat, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Rey Mark D. Ilagan | 12-09-1989 | Son\n2. Roylan D. Ilagan | 12-27-1991 | Son\n3. Rennalene D. Ilagan | 09-26-1993 | Daughter\n4. Ransel D. Ilagan | 10-20-1995 | Son\n5. Rhona Mae D. Ilagan | 11-20-1999 | Daughter\n6. Ryza Grenda Erika D. Ilagan | 12-18-2005 | Daughter','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[{\"type\":\"plan_holder_is_beneficiary\",\"source_index\":1,\"name\":\"Gregorio Ilagan\",\"text\":\"This person also appears as a beneficiary of record #1 (Lino Datinguinoo Ilagan (deceased)).\"}]}','ilagan|gregorio|1966-11-28','ready','pending',NULL,NULL,NULL,NULL,NULL,'gilagan','gilagan@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(283,13,34,'Faustino V. Aclan','2022-08-22','Marieta','Viana','Aclan','','1981-11-14','Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro','','Sitio Anilao','Camansihan','Calapan City','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Felix\",\"middle_name\":\"B.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"1975-06-10\",\"birthday_raw\":\"06-10-1975\",\"relationship\":\"Husband\",\"is_primary\":false},{\"first_name\":\"Kyla\",\"middle_name\":\"V.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"2003-12-16\",\"birthday_raw\":\"12-16-2003\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Kian\",\"middle_name\":\"V.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"2005-07-06\",\"birthday_raw\":\"07-06-2005\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Kathleen\",\"middle_name\":\"V.\",\"last_name\":\"Aclan\",\"name_extension\":\"\",\"date_of_birth\":\"2006-07-20\",\"birthday_raw\":\"07-20-2006\",\"relationship\":\"Daughter\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 08-22-2022\nName of Plan Holder: Marieta Viana Aclan\nDate of Birth: 11-14-1981\nAddress: Sitio Anilao, Camansihan, Calapan City, Oriental Mindoro\nBENEFICIARIES\n1. Felix B. Aclan | 06-10-1975 | Husband\n2. Kyla V. Aclan | 12-16-2003 | Daughter\n3. Kian V. Aclan | 07-06-2005 | Son\n4. Kathleen V. Aclan | 07-20-2006 | Daughter','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[]}','aclan|marieta|1981-11-14','ready','pending',NULL,NULL,NULL,NULL,NULL,'maclan','maclan@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47'),(284,13,35,'Faustino V. Aclan','2022-11-19','Editha','Magsisi','Hernandez','','1964-07-03','Malubay, Gloria, Oriental Mindoro','','','Malubay','Gloria','{\"address_province\":\"Oriental Mindoro\",\"optional\":{\"contact_number\":\"\",\"email\":\"\",\"gender\":\"\",\"civil_status\":\"\",\"citizenship\":\"\",\"place_of_birth\":\"\",\"senior_citizen_id\":\"\",\"id_control_no\":\"\",\"emergency_contact_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_contact_address\":\"\"},\"plan\":{\"plan_status\":\"active\",\"monthly_fee\":240,\"package_id\":1}}','[{\"first_name\":\"Jayson\",\"middle_name\":\"M.\",\"last_name\":\"Hernandez\",\"name_extension\":\"\",\"date_of_birth\":\"1984-12-22\",\"birthday_raw\":\"12-22-1984\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Joserie\",\"middle_name\":\"M.\",\"last_name\":\"Hernandez\",\"name_extension\":\"\",\"date_of_birth\":\"1986-05-01\",\"birthday_raw\":\"05-01-1986\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Lerma\",\"middle_name\":\"H.\",\"last_name\":\"Ilagan\",\"name_extension\":\"\",\"date_of_birth\":\"1988-01-04\",\"birthday_raw\":\"01-04-1988\",\"relationship\":\"Daughter\",\"is_primary\":false},{\"first_name\":\"Elmer\",\"middle_name\":\"M.\",\"last_name\":\"Hernandez\",\"name_extension\":\"\",\"date_of_birth\":\"1990-12-04\",\"birthday_raw\":\"12-04-1990\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Elven\",\"middle_name\":\"M.\",\"last_name\":\"Hernandez\",\"name_extension\":\"\",\"date_of_birth\":\"1992-10-08\",\"birthday_raw\":\"10-08-1992\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Arnel\",\"middle_name\":\"M.\",\"last_name\":\"Hernandez\",\"name_extension\":\"\",\"date_of_birth\":\"1996-03-10\",\"birthday_raw\":\"03-10-1996\",\"relationship\":\"Son\",\"is_primary\":false},{\"first_name\":\"Mariel\",\"middle_name\":\"M.\",\"last_name\":\"Hernandez\",\"name_extension\":\"\",\"date_of_birth\":\"1998-08-19\",\"birthday_raw\":\"08-19-1998\",\"relationship\":\"Daughter\",\"is_primary\":false}]','Coordinator Name: Faustino V. Aclan\nDate of Application: 11-19-2022\nName of Plan Holder: Editha Magsisi Hernandez\nDate of Birth: 07-03-1964\nAddress: Malubay, Gloria, Oriental Mindoro\nBENEFICIARIES\n1. Jayson M. Hernandez | 12-22-1984 | Son\n2. Joserie M. Hernandez | 05-01-1986 | Daughter\n3. Lerma H. Ilagan | 01-04-1988 | Daughter\n4. Elmer M. Hernandez | 12-04-1990 | Son\n5. Elven M. Hernandez | 10-08-1992 | Son\n6. Arnel M. Hernandez | 03-10-1996 | Son\n7. Mariel M. Hernandez | 08-19-1998 | Daughter','[]','{\"candidates\":[],\"status\":\"ready\",\"informational\":[{\"type\":\"beneficiary_is_plan_holder\",\"source_index\":28,\"name\":\"Lerma H. Ilagan\",\"text\":\"Beneficiary \\\"Lerma H. Ilagan\\\" is also a plan holder in this document (record #28, Lerma Hernandez Ilagan).\"}]}','hernandez|editha|1964-07-03','ready','pending',NULL,NULL,NULL,NULL,NULL,'ehernandez','ehernandez@kaagapay.local',NULL,NULL,NULL,NULL,'2026-08-08 13:41:47','2026-08-08 13:41:47');
/*!40000 ALTER TABLE `client_import_records` ENABLE KEYS */;

--
-- Table structure for table `deceased`
--

DROP TABLE IF EXISTS `deceased`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deceased` (
  `deceased_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `address_at_death` text DEFAULT NULL,
  PRIMARY KEY (`deceased_id`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `deceased_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deceased`
--

/*!40000 ALTER TABLE `deceased` DISABLE KEYS */;
/*!40000 ALTER TABLE `deceased` ENABLE KEYS */;

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_logs` (
  `email_log_id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(100) NOT NULL,
  `recipient_user_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `email_type` enum('verification','reset_password','payment_reminder','service_notification','other') DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('sent','failed','bounced') DEFAULT 'sent',
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`email_log_id`),
  KEY `idx_recipient_date` (`recipient_email`,`sent_at`),
  KEY `idx_status` (`status`),
  KEY `idx_user_id` (`recipient_user_id`),
  CONSTRAINT `email_logs_ibfk_1` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;

--
-- Table structure for table `embalmers`
--

DROP TABLE IF EXISTS `embalmers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `embalmers` (
  `embalmer_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) unsigned NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `status` enum('available','busy','unavailable','inactive') NOT NULL DEFAULT 'available',
  `experience_years` int(3) unsigned DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`embalmer_id`),
  KEY `branch_id` (`branch_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `embalmers`
--

/*!40000 ALTER TABLE `embalmers` DISABLE KEYS */;
/*!40000 ALTER TABLE `embalmers` ENABLE KEYS */;

--
-- Table structure for table `hearses`
--

DROP TABLE IF EXISTS `hearses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hearses` (
  `hearse_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) unsigned NOT NULL,
  `hearse_name` varchar(100) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `model_year` int(4) unsigned DEFAULT NULL,
  `capacity` int(3) unsigned NOT NULL DEFAULT 1,
  `status` enum('available','unavailable','maintenance','retired') NOT NULL DEFAULT 'available',
  `last_maintenance` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`hearse_id`),
  UNIQUE KEY `plate_number` (`plate_number`),
  KEY `branch_id` (`branch_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hearses`
--

/*!40000 ALTER TABLE `hearses` DISABLE KEYS */;
/*!40000 ALTER TABLE `hearses` ENABLE KEYS */;

--
-- Table structure for table `membership_programs`
--

DROP TABLE IF EXISTS `membership_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membership_programs` (
  `program_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `program_name` varchar(150) NOT NULL,
  `monthly_fee` decimal(10,2) NOT NULL DEFAULT 240.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`program_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membership_programs`
--

/*!40000 ALTER TABLE `membership_programs` DISABLE KEYS */;
INSERT INTO `membership_programs` VALUES (1,'Damayan Burial Program',240.00,1,'2026-05-18 20:13:17','2026-05-18 20:13:17');
/*!40000 ALTER TABLE `membership_programs` ENABLE KEYS */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026-03-31-140500','App\\Database\\Migrations\\AddPackageIdToPlans','default','App',1774967895,1),(2,'2026-04-06-120000','App\\Database\\Migrations\\AddUniqueEmailIndexToUsers','default','App',1775480995,2),(3,'2026-04-17-120000','App\\Database\\Migrations\\CreatePackageServicesTable','default','App',1779135197,3),(4,'2026-04-18-000000','App\\Database\\Migrations\\AllowServiceOnlyApplications','default','App',1779135197,3),(5,'2026-04-18-120000','App\\Database\\Migrations\\CreateBranchManagementApprovalWorkflow','default','App',1779135197,3),(6,'2026-04-18-140000','App\\Database\\Migrations\\CreatePendingPlanHolderRegistrations','default','App',1779135197,3),(7,'2026-04-18-160000','App\\Database\\Migrations\\EnforceSinglePlanHolderPerUser','default','App',1779135197,3),(8,'2026-04-27-150000','App\\Database\\Migrations\\FixActivePlanConsistency','default','App',1779135197,3),(9,'2026-04-28-100000','App\\Database\\Migrations\\NormalizeServiceListStatus','default','App',1779135197,3),(10,'2026-04-28-110000','App\\Database\\Migrations\\NormalizePackagesStatus','default','App',1779135197,3),(11,'2026-04-28-120000','App\\Database\\Migrations\\AddPaymentTypeToPayments','default','App',1779135197,3),(12,'2026-04-28-130000','App\\Database\\Migrations\\CreateActivityLogsTable','default','App',1779135197,3),(13,'2026-04-28-140000','App\\Database\\Migrations\\AddTypeToNotifications','default','App',1779135197,3),(14,'2026-05-04-090000','App\\Database\\Migrations\\AddAdvancePaymentFields','default','App',1779135197,3),(15,'2026-05-05-090000','App\\Database\\Migrations\\CreateMembershipProgramsTable','default','App',1779135197,3),(16,'2026-05-05-110000','App\\Database\\Migrations\\AddPlanHolderRegistrationFields','default','App',1779135197,3),(17,'2026-05-05-120000','App\\Database\\Migrations\\AllowNullReceivedByInPayments','default','App',1779135197,3),(18,'2026-05-07-100000','App\\Database\\Migrations\\AddMembershipTrackingToPlan','default','App',1779135197,3),(19,'2026-05-07-110000','App\\Database\\Migrations\\AddVerificationStatusToBeneficiaries','default','App',1779135197,3),(20,'2026-05-07-120000','App\\Database\\Migrations\\CreateServiceSchedulesTable','default','App',1779135197,3),(21,'2026-05-07-130000','App\\Database\\Migrations\\CreateResourceAssignmentsTable','default','App',1779135197,3),(22,'2026-05-07-140000','App\\Database\\Migrations\\AddNotificationEnhancements','default','App',1779135197,3),(23,'2026-05-07-150000','App\\Database\\Migrations\\CreateServiceSchedulingTablesExpanded','default','App',1779135198,3),(24,'2026-05-10-120000','App\\Database\\Migrations\\CreateAuditInfrastructure','default','App',1779135246,4),(25,'2026-05-10-130000','App\\Database\\Migrations\\CreateSecurityTables','default','App',1779135317,5),(26,'2026-05-17-000000','App\\Database\\Migrations\\CreateServiceBalanceWorkflow','default','App',1785661456,6),(27,'2026-05-17-100000','App\\Database\\Migrations\\AddApplicationDetailsAndDocuments','default','App',1785661457,7),(28,'2026-08-07-042533','App\\Database\\Migrations\\AddRejectionReasonToServiceApplications','default','App',1786125953,8),(29,'2026-05-12-000001','App\\Database\\Migrations\\CreateCashPaymentRecords','default','App',1786127654,9),(30,'2026-05-12-100000','App\\Database\\Migrations\\UpdatePaymentStatusEnums','default','App',1786127654,9),(31,'2026-05-14-000000','App\\Database\\Migrations\\AddIsPrimaryToBeneficiaries','default','App',1786127654,9),(32,'2026-05-18-000000','App\\Database\\Migrations\\EnsurePlansNextDueDate','default','App',1786127654,9),(33,'2026-06-04-000000','App\\Database\\Migrations\\AddServiceListIdToServices','default','App',1786127654,9),(34,'2026-06-16-120000','App\\Database\\Migrations\\UpdateServiceLogsColumns','default','App',1786127654,9),(35,'2026-08-07-100000','App\\Database\\Migrations\\AddApplicationIdToServices','default','App',1786127654,9),(36,'2026-08-07-120000','App\\Database\\Migrations\\CreateClientImportTables','default','App',1786127654,9),(37,'2026-08-08-000000','App\\Database\\Migrations\\RegistrationCoordinatorGcashIdVerification','default','App',1786200765,10),(38,'2026-08-09-000000','App\\Database\\Migrations\\CreateAddressReferenceTables','default','App',1786280602,11);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `notification_type` enum('payment','membership','service','schedule','system') DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT 0,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `read_at` datetime DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `type` enum('payment_approved','payment_rejected','service_approved','service_rejected','registration_pending','service_completed','general') NOT NULL DEFAULT 'general' COMMENT 'Notification classification for filtering and display',
  `status` enum('read','unread') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_notifications_user_status` (`user_id`,`status`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (2,17,'Your advance payment covering 1 month has been approved.','system',0,'normal',NULL,0,'payment_approved','unread','2026-05-20 05:34:29'),(3,18,'Your account was created. Complete plan holder registration to unlock services and payments.','system',0,'normal',NULL,0,'registration_pending','unread','2026-06-02 23:11:56'),(4,18,'Your registration has been approved. Your plan is now active.','system',0,'normal',NULL,0,'registration_pending','unread','2026-06-02 23:17:00'),(5,18,'Your advance payment covering 1 month has been approved.','system',0,'normal',NULL,0,'payment_approved','unread','2026-06-02 23:17:00'),(9,17,'Your application for Burial Service has been submitted.','system',0,'normal',NULL,0,'registration_pending','unread','2026-06-03 04:09:50'),(12,21,'Your account was created. Complete plan holder registration to unlock services and payments.','system',0,'normal',NULL,0,'registration_pending','unread','2026-06-03 22:29:19'),(13,22,'Your account was created. Complete plan holder registration to unlock services and payments.','system',0,'normal',NULL,0,'registration_pending','unread','2026-06-04 01:12:15'),(14,23,'Your account was created successfully.','system',0,'normal',NULL,0,'registration_pending','unread','2026-06-15 17:00:37'),(15,24,'Your account was created. Complete plan holder registration to unlock services and payments.','system',0,'normal',NULL,0,'registration_pending','unread','2026-06-15 20:19:20'),(16,24,'Your registration has been approved. Your plan is now active.','system',0,'normal',NULL,0,'registration_pending','unread','2026-06-15 20:34:05'),(17,24,'Your advance payment covering 1 month has been approved.','system',0,'normal',NULL,0,'payment_approved','unread','2026-06-15 20:34:05'),(19,25,'Your account was created. Complete plan holder registration to unlock services and payments.','system',0,'normal',NULL,0,'registration_pending','unread','2026-07-16 05:42:19'),(20,25,'Your registration has been approved. Your plan is now active.','system',0,'normal',NULL,0,'registration_pending','unread','2026-07-27 03:30:56'),(21,25,'Your advance payment covering 1 month has been approved.','system',0,'normal',NULL,0,'payment_approved','unread','2026-07-27 03:30:56'),(85,91,'Your account was created. Complete plan holder registration to unlock services and payments.','system',0,'normal',NULL,0,'registration_pending','unread','2026-08-07 16:06:44');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;

--
-- Table structure for table `package_items`
--

DROP TABLE IF EXISTS `package_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `package_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `package_items_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `package_items`
--

/*!40000 ALTER TABLE `package_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `package_items` ENABLE KEYS */;

--
-- Table structure for table `package_services`
--

DROP TABLE IF EXISTS `package_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `package_services` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `service_list_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`),
  KEY `service_list_id` (`service_list_id`),
  CONSTRAINT `package_services_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `package_services_service_list_id_foreign` FOREIGN KEY (`service_list_id`) REFERENCES `service_list` (`service_list_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `package_services`
--

/*!40000 ALTER TABLE `package_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `package_services` ENABLE KEYS */;

--
-- Table structure for table `package_versions`
--

DROP TABLE IF EXISTS `package_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `package_versions` (
  `version_id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `effective_date` date NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`version_id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `package_versions_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `package_versions`
--

/*!40000 ALTER TABLE `package_versions` DISABLE KEYS */;
INSERT INTO `package_versions` VALUES (1,1,240.00,'2026-05-10','active');
/*!40000 ALTER TABLE `package_versions` ENABLE KEYS */;

--
-- Table structure for table `packages`
--

DROP TABLE IF EXISTS `packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packages` (
  `package_id` int(11) NOT NULL AUTO_INCREMENT,
  `package_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `is_customizable` tinyint(1) DEFAULT 1,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('pending','approved','rejected','inactive') NOT NULL DEFAULT 'approved' COMMENT 'Package approval status: pending (awaiting approval), approved (visible), rejected, inactive (unavailable)',
  PRIMARY KEY (`package_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packages`
--

/*!40000 ALTER TABLE `packages` DISABLE KEYS */;
INSERT INTO `packages` VALUES (1,'Basic Funeral Package','Essential funeral services including viewing, wake setup, and basic burial arrangements. Includes 3 days of wake facility and basic catering for attendees.',15000.00,1,1,'approved'),(2,'Premium Funeral Package','Comprehensive funeral services with premium arrangements, 5 days of wake facility, professional catering, and dedicated service coordinator. Includes embalming and premium casket options.',35000.00,1,1,'approved'),(3,'Complete Funeral Package','Full-service funeral package with premium everything: luxury catering, premium venue, professional photography, flower arrangements, transportation, and 7 days of wake facility.',60000.00,1,1,'approved'),(4,'Burial Service Package','Specialized burial services including ground preparation, grave location coordination, burial ceremony arrangement, and documentation handling.',8000.00,0,1,'approved'),(5,'Cremation Service Package','Professional cremation services including facility arrangement, documentation, and ash handling. Includes temporary urn and placement service.',12000.00,0,1,'approved'),(6,'Memorial Package','Memorial service planning including venue, catering, program coordination, and guest management. Perfect for religious and cultural memorial events.',20000.00,1,1,'approved'),(7,'Corporate Funeral Package','Large-scale funeral arrangements for corporate employees and officials. Includes mass catering, extended venue rental, and professional coordination team.',85000.00,1,1,'approved'),(8,'Eco-Friendly Package','Environmentally conscious funeral arrangements with biodegradable materials, green burial options, and sustainable catering practices.',25000.00,0,1,'approved');
/*!40000 ALTER TABLE `packages` ENABLE KEYS */;

--
-- Table structure for table `payment_transactions`
--

DROP TABLE IF EXISTS `payment_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_transactions` (
  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `status_before` enum('paid','pending','cancelled') DEFAULT NULL,
  `status_after` enum('paid','pending','cancelled') DEFAULT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`transaction_id`),
  KEY `idx_payment_id` (`payment_id`),
  KEY `idx_changed_at` (`changed_at`),
  KEY `idx_changed_by` (`changed_by`),
  CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE CASCADE,
  CONSTRAINT `payment_transactions_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_transactions`
--

/*!40000 ALTER TABLE `payment_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_transactions` ENABLE KEYS */;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('cash','gcash') NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `official_receipt_number` varchar(100) DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `branch_id` int(11) NOT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('paid','pending','cancelled') DEFAULT 'paid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `months_covered` int(11) NOT NULL DEFAULT 1,
  `proof_image` varchar(255) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `payment_type` enum('initial_registration','monthly_contribution','service_payment','addon_payment') NOT NULL DEFAULT 'monthly_contribution' COMMENT 'Payment classification for reporting and filtering',
  PRIMARY KEY (`payment_id`),
  KEY `plan_id` (`plan_id`),
  KEY `received_by` (`received_by`),
  KEY `branch_id` (`branch_id`),
  KEY `idx_payments_plan_status` (`plan_id`,`status`),
  KEY `idx_payments_branch_date` (`branch_id`,`payment_date`),
  KEY `idx_payments_reference` (`reference_number`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`plan_id`),
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (13,5,240.00,'2026-05-18','cash','2026-0001','2026-0001',6,1,'Recorded at branch counter','paid','2026-05-18 08:18:45','2026-05-18 08:18:45',1,NULL,'2026-05-18 08:18:45',6,'monthly_contribution'),(14,5,240.00,'2026-05-18','cash','2026-0002','2026-0002',6,1,'Recorded at branch counter','paid','2026-05-18 09:04:22','2026-05-18 09:04:22',1,NULL,'2026-05-18 09:04:22',6,'monthly_contribution'),(15,5,240.00,'2026-05-20','cash','2026-0003','2026-0003',6,1,'Recorded at branch counter','paid','2026-05-20 13:34:29','2026-05-20 13:34:29',1,NULL,'2026-05-20 13:34:29',6,'monthly_contribution'),(16,6,240.00,'2026-06-03','cash','2026-0002','2026-0002',6,1,'Recorded at branch counter','paid','2026-06-03 07:17:00','2026-06-03 07:17:00',1,NULL,'2026-06-03 07:17:00',6,'monthly_contribution'),(17,7,240.00,'2026-06-16','cash','2026-0005','2026-0005',6,1,'Recorded at branch counter','paid','2026-06-16 04:34:05','2026-06-16 04:34:05',1,NULL,'2026-06-16 04:34:05',6,'monthly_contribution'),(18,8,240.00,'2026-07-27','cash','2026-0006','2026-0006',6,1,'Recorded at branch counter','paid','2026-07-27 11:30:56','2026-07-27 11:30:56',1,NULL,'2026-07-27 11:30:56',6,'monthly_contribution'),(19,7,240.00,'2026-08-08','gcash','2026-0007',NULL,NULL,1,'Submitted by client, awaiting branch verification','pending','2026-08-08 17:03:22','2026-08-08 17:03:22',1,NULL,NULL,NULL,'monthly_contribution'),(20,7,240.00,'2026-08-08','gcash','2026-0008',NULL,NULL,1,'Submitted by client, awaiting branch verification','pending','2026-08-08 17:03:40','2026-08-08 17:03:40',1,NULL,NULL,NULL,'monthly_contribution');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;

--
-- Table structure for table `pending_packages`
--

DROP TABLE IF EXISTS `pending_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pending_packages` (
  `pending_package_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `package_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_customizable` tinyint(1) NOT NULL DEFAULT 1,
  `initial_effective_date` date DEFAULT NULL,
  `service_list_ids` text DEFAULT NULL,
  `created_by` int(11) unsigned NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`pending_package_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pending_packages`
--

/*!40000 ALTER TABLE `pending_packages` DISABLE KEYS */;
/*!40000 ALTER TABLE `pending_packages` ENABLE KEYS */;

--
-- Table structure for table `pending_plan_holder_registrations`
--

DROP TABLE IF EXISTS `pending_plan_holder_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pending_plan_holder_registrations` (
  `pending_registration_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`pending_registration_id`),
  KEY `pending_plan_holder_registrations_reviewed_by_foreign` (`reviewed_by`),
  KEY `user_id` (`user_id`),
  KEY `branch_id` (`branch_id`),
  KEY `status` (`status`),
  CONSTRAINT `pending_plan_holder_registrations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pending_plan_holder_registrations_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE SET NULL,
  CONSTRAINT `pending_plan_holder_registrations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pending_plan_holder_registrations`
--

/*!40000 ALTER TABLE `pending_plan_holder_registrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `pending_plan_holder_registrations` ENABLE KEYS */;

--
-- Table structure for table `pending_services`
--

DROP TABLE IF EXISTS `pending_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pending_services` (
  `pending_service_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `service_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `requested_status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` int(11) unsigned NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`pending_service_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pending_services`
--

/*!40000 ALTER TABLE `pending_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `pending_services` ENABLE KEYS */;

--
-- Table structure for table `plan_holders`
--

DROP TABLE IF EXISTS `plan_holders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plan_holders` (
  `plan_holder_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `coordinator_user_id` int(11) unsigned DEFAULT NULL,
  `id_document_path` varchar(500) DEFAULT NULL,
  `id_type` varchar(50) DEFAULT NULL,
  `id_number` varchar(100) DEFAULT NULL,
  `id_match_score` decimal(5,2) DEFAULT NULL,
  `id_verification_status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `id_verified_at` datetime DEFAULT NULL,
  `id_verified_by` int(11) unsigned DEFAULT NULL,
  `application_date` date DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_number` varchar(30) DEFAULT NULL,
  `emergency_contact_address` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`plan_holder_id`),
  UNIQUE KEY `ux_plan_holders_user_id` (`user_id`),
  UNIQUE KEY `unique_identifier` (`unique_identifier`),
  KEY `user_id` (`user_id`),
  KEY `branch_id` (`branch_id`),
  KEY `ix_plan_holders_coordinator` (`coordinator_user_id`),
  CONSTRAINT `plan_holders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `plan_holders_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plan_holders`
--

/*!40000 ALTER TABLE `plan_holders` DISABLE KEYS */;
INSERT INTO `plan_holders` VALUES (12,17,'88-B','JP Rizal Street','San Vicente Central','Calapan City','1997-07-08','calapan city',45,'Female','Married','FIlipino',NULL,NULL,'Carlos Miguel Garcia','1976-05-14','Truck Driver','','',1,'active','2026-05-18 08:18:25',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,NULL,NULL,NULL,NULL),(13,18,'','sitio tabag','Maidang II','Calapan City','2005-09-07','Maidlang II Calapan City',20,'Male','Single','FIlipino',165.00,50.00,'',NULL,'','','',1,'active','2026-06-03 07:16:04',0,NULL,'','',NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,'2026-06-03','John Bryan Bulan Erazo','09369111954','sito.putol'),(14,24,'','','guinobatan','Calapan City','1983-10-06','calapan city',43,'Female','Married','FIlipino',NULL,NULL,'John Michael Santos','1985-03-10','Truck Driver','','',1,'active','2026-06-16 04:33:05',0,NULL,'','',NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,'2026-06-16','John Michael Santos','0998-221-7745','Guinoatan Calapan city'),(16,25,'','','Guinobatan','City of Calapan','1989-04-25','Calapan City',37,'Male','Married','Filipino',NULL,NULL,'Michelle Anne Mendoza','1989-11-23','','','',1,'active','2026-07-27 11:28:41',0,NULL,'','',NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,'2026-07-27','','','');
/*!40000 ALTER TABLE `plan_holders` ENABLE KEYS */;

--
-- Table structure for table `plans`
--

DROP TABLE IF EXISTS `plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plans` (
  `plan_id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_holder_id` int(11) NOT NULL,
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
  `membership_state` enum('active','delinquent','suspended','completed') DEFAULT 'active',
  PRIMARY KEY (`plan_id`),
  KEY `plan_holder_id` (`plan_holder_id`),
  KEY `plans_ibfk_2` (`package_id`),
  KEY `fk_plan_version` (`version_id`),
  KEY `idx_plans_holder_status` (`plan_holder_id`,`status`),
  KEY `idx_plans_package` (`package_id`,`start_date`),
  CONSTRAINT `fk_plan_version` FOREIGN KEY (`version_id`) REFERENCES `package_versions` (`version_id`),
  CONSTRAINT `plans_ibfk_1` FOREIGN KEY (`plan_holder_id`) REFERENCES `plan_holders` (`plan_holder_id`),
  CONSTRAINT `plans_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`)
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plans`
--

/*!40000 ALTER TABLE `plans` DISABLE KEYS */;
INSERT INTO `plans` VALUES (5,12,1,240.00,50.00,'2026-05-18','active',3,2880.00,1,'2026-06-19','2026-06-18',0,'active'),(6,13,1,240.00,50.00,'2026-06-03','active',1,2880.00,1,'2026-07-04','2026-07-03',0,'active'),(7,14,1,240.00,50.00,'2026-06-16','active',1,2880.00,1,'2026-07-17','2026-07-16',0,'active'),(8,16,1,240.00,50.00,'2026-07-27','active',1,2880.00,1,'2026-08-28','2026-08-27',0,'active');
/*!40000 ALTER TABLE `plans` ENABLE KEYS */;

--
-- Table structure for table `rate_limits`
--

DROP TABLE IF EXISTS `rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rate_limits` (
  `limit_id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `action` varchar(100) NOT NULL,
  `attempt_count` int(11) DEFAULT 1,
  `first_attempt` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_blocked` tinyint(1) DEFAULT 0,
  `blocked_until` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`limit_id`),
  UNIQUE KEY `idx_ip_action` (`ip_address`,`action`),
  KEY `idx_blocked_status` (`is_blocked`),
  KEY `idx_blocked_until` (`blocked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rate_limits`
--

/*!40000 ALTER TABLE `rate_limits` DISABLE KEYS */;
/*!40000 ALTER TABLE `rate_limits` ENABLE KEYS */;

--
-- Table structure for table `resource_assignments`
--

DROP TABLE IF EXISTS `resource_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resource_assignments` (
  `assignment_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) unsigned DEFAULT NULL,
  `staff_id` int(11) unsigned DEFAULT NULL,
  `vehicle_id` int(11) unsigned DEFAULT NULL,
  `resource_type` enum('staff','vehicle','equipment') NOT NULL DEFAULT 'staff',
  `status` enum('assigned','in_use','completed','cancelled') NOT NULL DEFAULT 'assigned',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`assignment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resource_assignments`
--

/*!40000 ALTER TABLE `resource_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `resource_assignments` ENABLE KEYS */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Admin'),(2,'Branch Admin'),(3,'Staff'),(4,'Plan Holder');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;

--
-- Table structure for table `service_application_documents`
--

DROP TABLE IF EXISTS `service_application_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_application_documents` (
  `document_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `application_id` int(10) unsigned NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `path` varchar(500) NOT NULL,
  `uploaded_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`document_id`),
  KEY `idx_app_doc_application` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_application_documents`
--

/*!40000 ALTER TABLE `service_application_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_application_documents` ENABLE KEYS */;

--
-- Table structure for table `service_applications`
--

DROP TABLE IF EXISTS `service_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_applications` (
  `application_id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_holder_id` int(11) NOT NULL,
  `service_list_id` int(11) DEFAULT NULL,
  `package_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deceased_name` varchar(150) DEFAULT NULL,
  `deceased_date_of_death` date DEFAULT NULL,
  `deceased_address` varchar(255) DEFAULT NULL,
  `relationship_to_deceased` varchar(100) DEFAULT NULL,
  `beneficiary_name` varchar(150) DEFAULT NULL,
  `beneficiary_contact` varchar(50) DEFAULT NULL,
  `application_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  PRIMARY KEY (`application_id`),
  KEY `plan_holder_id` (`plan_holder_id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `service_applications_ibfk_1` FOREIGN KEY (`plan_holder_id`) REFERENCES `plan_holders` (`plan_holder_id`),
  CONSTRAINT `service_applications_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_applications`
--

/*!40000 ALTER TABLE `service_applications` DISABLE KEYS */;
INSERT INTO `service_applications` VALUES (1,12,2,NULL,'pending','2026-06-03 12:09:50','Angelica Marie Garcia','2026-03-06','88-B, JP Rizal Street, San Vicente Central, Calapan City','Daughter','Maria Lourdes Garcia','12345678910',NULL,NULL);
/*!40000 ALTER TABLE `service_applications` ENABLE KEYS */;

--
-- Table structure for table `service_balance_payments`
--

DROP TABLE IF EXISTS `service_balance_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_balance_payments` (
  `service_balance_payment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `service_balance_id` int(10) unsigned NOT NULL,
  `paid_by_user_id` int(10) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','paid','failed','void') NOT NULL DEFAULT 'paid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`service_balance_payment_id`),
  KEY `idx_service_balance_payments_balance` (`service_balance_id`),
  KEY `idx_service_balance_payments_paid_by` (`paid_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_balance_payments`
--

/*!40000 ALTER TABLE `service_balance_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_balance_payments` ENABLE KEYS */;

--
-- Table structure for table `service_balances`
--

DROP TABLE IF EXISTS `service_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_balances` (
  `service_balance_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `application_id` int(10) unsigned NOT NULL,
  `service_id` int(10) unsigned DEFAULT NULL,
  `plan_holder_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `service_type` enum('service','package') NOT NULL DEFAULT 'package',
  `service_name` varchar(150) NOT NULL,
  `package_name` varchar(150) DEFAULT NULL,
  `package_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `monthly_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `months_paid` int(11) NOT NULL DEFAULT 0,
  `total_contributions` decimal(10,2) NOT NULL DEFAULT 0.00,
  `assistance_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `remaining_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `installment_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `beneficiary_user_id` int(10) unsigned DEFAULT NULL,
  `beneficiary_name` varchar(150) DEFAULT NULL,
  `beneficiary_relationship` varchar(100) DEFAULT NULL,
  `acknowledgement_notes` text DEFAULT NULL,
  `beneficiary_acknowledged_at` datetime DEFAULT NULL,
  `acknowledged_by` int(10) unsigned DEFAULT NULL,
  `status` enum('pending_acknowledgment','active','completed','cancelled') NOT NULL DEFAULT 'pending_acknowledgment',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`service_balance_id`),
  UNIQUE KEY `uq_service_balances_application` (`application_id`),
  KEY `idx_service_balances_plan_holder` (`plan_holder_id`),
  KEY `idx_service_balances_branch` (`branch_id`),
  KEY `idx_service_balances_service` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_balances`
--

/*!40000 ALTER TABLE `service_balances` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_balances` ENABLE KEYS */;

--
-- Table structure for table `service_calendar`
--

DROP TABLE IF EXISTS `service_calendar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_calendar` (
  `calendar_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) unsigned NOT NULL,
  `service_id` int(11) unsigned DEFAULT NULL,
  `plan_holder_id` int(11) unsigned DEFAULT NULL,
  `event_type` enum('funeral','viewing','burial','other') NOT NULL DEFAULT 'funeral',
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `status` enum('scheduled','in-progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `hearse_id` int(11) unsigned DEFAULT NULL,
  `embalmer_id` int(11) unsigned DEFAULT NULL,
  `assigned_staff_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`assigned_staff_ids`)),
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`calendar_id`),
  KEY `branch_id` (`branch_id`),
  KEY `service_id` (`service_id`),
  KEY `plan_holder_id` (`plan_holder_id`),
  KEY `event_date` (`event_date`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_calendar`
--

/*!40000 ALTER TABLE `service_calendar` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_calendar` ENABLE KEYS */;

--
-- Table structure for table `service_costs`
--

DROP TABLE IF EXISTS `service_costs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_costs` (
  `cost_id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`cost_id`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `service_costs_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_costs`
--

/*!40000 ALTER TABLE `service_costs` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_costs` ENABLE KEYS */;

--
-- Table structure for table `service_list`
--

DROP TABLE IF EXISTS `service_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_list` (
  `service_list_id` int(11) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`service_list_id`),
  KEY `idx_status` (`status`),
  KEY `idx_is_available` (`is_available`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_list`
--

/*!40000 ALTER TABLE `service_list` DISABLE KEYS */;
INSERT INTO `service_list` VALUES (1,'Funeral Service','Complete funeral arrangement and management',5000.00,'active',1,'2026-05-10 10:15:55','2026-05-10 10:15:55'),(2,'Burial Service','Professional burial service',3000.00,'active',1,'2026-05-10 10:15:55','2026-05-10 10:15:55'),(3,'Cremation Service','Professional cremation service',4000.00,'active',1,'2026-05-10 10:15:55','2026-05-10 10:15:55'),(4,'Memorial Service','Memorial planning and coordination',2000.00,'active',1,'2026-05-10 10:15:55','2026-05-10 10:15:55'),(5,'Documentation Assistance','Help with funeral documents and permits',500.00,'active',1,'2026-05-10 10:15:55','2026-05-10 10:15:55'),(6,'Transport Service','Transportation of deceased',2500.00,'active',1,'2026-05-10 10:15:55','2026-05-10 10:15:55'),(7,'Embalming Service','Professional embalming service',1500.00,'active',1,'2026-05-10 10:15:55','2026-05-10 10:15:55'),(8,'Catering Service','Catering for wake and funeral events',3500.00,'active',1,'2026-05-10 10:15:55','2026-05-10 10:15:55');
/*!40000 ALTER TABLE `service_list` ENABLE KEYS */;

--
-- Table structure for table `service_logs`
--

DROP TABLE IF EXISTS `service_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `old_status` varchar(50) NOT NULL DEFAULT '',
  `new_status` varchar(50) NOT NULL DEFAULT '',
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_before` enum('pending','ongoing','completed','cancelled') DEFAULT NULL,
  `status_after` enum('pending','ongoing','completed','cancelled') DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `idx_service_id` (`service_id`),
  KEY `idx_changed_at` (`changed_at`),
  KEY `idx_changed_by` (`changed_by`),
  CONSTRAINT `service_logs_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE,
  CONSTRAINT `service_logs_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_logs`
--

/*!40000 ALTER TABLE `service_logs` DISABLE KEYS */;
INSERT INTO `service_logs` VALUES (1,2,'pending','ongoing','2026-06-16 01:47:24',NULL,NULL,NULL,'2026-06-16 01:47:24',NULL),(2,1,'pending','completed','2026-06-16 01:49:10',NULL,NULL,NULL,'2026-06-16 01:49:10',NULL);
/*!40000 ALTER TABLE `service_logs` ENABLE KEYS */;

--
-- Table structure for table `service_schedules`
--

DROP TABLE IF EXISTS `service_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_schedules` (
  `schedule_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `service_application_id` int(11) unsigned DEFAULT NULL,
  `service_date` date DEFAULT NULL,
  `service_time` time DEFAULT NULL,
  `branch_id` int(11) unsigned DEFAULT NULL,
  `status` enum('pending','scheduled','ongoing','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_schedules`
--

/*!40000 ALTER TABLE `service_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_schedules` ENABLE KEYS */;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services` (
  `service_id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_holder_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `application_id` int(11) unsigned DEFAULT NULL,
  `service_list_id` int(11) DEFAULT NULL,
  `service_type` varchar(50) DEFAULT NULL,
  `package_id` int(11) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `service_date` date NOT NULL,
  `service_time` time DEFAULT NULL,
  `burial_location` varchar(150) DEFAULT NULL,
  `assigned_staff` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','ongoing','completed','cancelled') NOT NULL,
  PRIMARY KEY (`service_id`),
  KEY `plan_holder_id` (`plan_holder_id`),
  KEY `branch_id` (`branch_id`),
  KEY `package_id` (`package_id`),
  KEY `assigned_staff` (`assigned_staff`),
  KEY `idx_services_holder_status` (`plan_holder_id`,`status`),
  KEY `idx_services_branch_date` (`branch_id`,`service_date`),
  CONSTRAINT `services_ibfk_1` FOREIGN KEY (`plan_holder_id`) REFERENCES `plan_holders` (`plan_holder_id`),
  CONSTRAINT `services_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`),
  CONSTRAINT `services_ibfk_3` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`),
  CONSTRAINT `services_ibfk_4` FOREIGN KEY (`assigned_staff`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,13,1,NULL,NULL,NULL,1,15000.00,'2026-06-04','15:12:00','Maidlang 2',23,NULL,'completed'),(2,12,1,NULL,6,NULL,1,15000.00,'2026-06-16','20:30:00','Maidlang 2',21,NULL,'ongoing');
/*!40000 ALTER TABLE `services` ENABLE KEYS */;

--
-- Table structure for table `staff_schedules`
--

DROP TABLE IF EXISTS `staff_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_schedules` (
  `schedule_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `branch_id` int(11) unsigned NOT NULL,
  `schedule_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duty_type` enum('regular','on-call','training','meeting','other') NOT NULL DEFAULT 'regular',
  `status` enum('scheduled','assigned','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`schedule_id`),
  KEY `user_id` (`user_id`),
  KEY `branch_id` (`branch_id`),
  KEY `schedule_date` (`schedule_date`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_schedules`
--

/*!40000 ALTER TABLE `staff_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_schedules` ENABLE KEYS */;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `data_type` enum('string','integer','decimal','boolean','json') DEFAULT NULL,
  `is_sensitive` tinyint(1) DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_setting_key` (`setting_key`),
  KEY `idx_updated_at` (`updated_at`),
  CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'minimum_payment','240.00','payment','Minimum payment amount in PHP',1,'decimal',0,NULL,'2026-08-02 08:51:25'),(2,'maximum_advance_months','12','payment','Maximum months allowed for advance payment',1,'integer',0,NULL,'2026-08-02 08:51:25'),(3,'delinquent_threshold_months','3','payment','Months overdue before marking as delinquent',1,'integer',0,NULL,'2026-08-02 08:51:25'),(4,'payment_reminder_days','5','payment','Days before payment due to send reminder',1,'integer',0,NULL,'2026-08-02 08:51:25'),(5,'service_advance_notice_days','7','service','Minimum days required to book service in advance',1,'integer',0,NULL,'2026-08-02 08:51:25'),(6,'password_expiry_days','90','security','Days before password reset is required',1,'integer',0,NULL,'2026-08-02 08:51:25'),(7,'session_timeout_minutes','30','security','Session timeout in minutes',1,'integer',0,NULL,'2026-08-02 08:51:25'),(8,'max_login_attempts','5','security','Maximum failed login attempts before account lock',1,'integer',0,NULL,'2026-08-02 08:51:25'),(9,'account_lockout_minutes','15','security','Minutes to lock account after max login attempts',1,'integer',0,NULL,'2026-08-02 08:51:25'),(10,'email_verification_required','true','security','Require email verification for new accounts',1,'boolean',0,NULL,'2026-08-02 08:51:25'),(11,'two_factor_enabled','false','security','Enable two-factor authentication system-wide',1,'boolean',0,NULL,'2026-08-02 08:51:25'),(12,'notification_retention_days','30','system','Days to retain notifications',1,'integer',0,NULL,'2026-08-02 08:51:25'),(13,'backup_frequency','daily','system','Database backup frequency',1,'string',0,NULL,'2026-08-02 08:51:25'),(14,'currency_symbol','?','system','Currency symbol for display',1,'string',0,NULL,'2026-08-02 08:51:25'),(15,'timezone','Asia/Manila','system','System timezone',1,'string',0,NULL,'2026-08-02 08:51:25'),(16,'company_name','KaaGapay','system','Company name for emails and reports',1,'string',0,NULL,'2026-08-02 08:51:25'),(17,'support_email','support@kaagapay.local','system','Support contact email',1,'string',0,NULL,'2026-08-02 08:51:25'),(18,'max_file_upload_mb','5','system','Maximum file upload size in MB',1,'integer',0,NULL,'2026-08-02 08:51:25');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_sessions` (
  `session_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `idx_user_token` (`user_id`,`session_token`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_sessions` ENABLE KEYS */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `name_extension` varchar(10) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `gcash_number` varchar(50) DEFAULT NULL,
  `gcash_name` varchar(100) DEFAULT NULL,
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
  `failed_login_attempts` int(11) DEFAULT 0,
  `last_failed_login` timestamp NULL DEFAULT NULL,
  `locked_until` timestamp NULL DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `ip_address_created` varchar(45) DEFAULT NULL,
  `ip_address_last_login` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `role_id` (`role_id`),
  KEY `branch_id` (`branch_id`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_branch` (`branch_id`,`role_id`),
  KEY `idx_users_status` (`status`,`created_at`),
  KEY `idx_locked_until` (`locked_until`),
  KEY `idx_two_factor` (`two_factor_enabled`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`),
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (5,'systemadmin','$2y$10$tfD.MPa6K5M7OdkHaa32wOqOQyoBscarE1uU7RTNfZ2IGUaJoQMBC','systemadmin@gmail.com','John Bryan',NULL,'John Bryan',NULL,'09369111954',NULL,NULL,1,NULL,'active','2026-04-07 05:43:54','2026-08-11 03:27:57','pending',0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,NULL,NULL),(6,'branchadmin','$2y$10$0BnBsvpToY/E49xZa8bbTeIrFaCueLxQK.62NEqK38A8xrE/OlYN2','branchadmin@gmail.com','REGINE','ILAGAN','ACLAN',NULL,'09674073500','09171234567','REGINE ACLAN',2,1,'active','2026-04-07 05:48:53','2026-08-11 03:26:54','pending',0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,NULL,NULL),(11,'mein','$2y$10$B96o2f71l59IsxtPnk06A.A13xNWMoMQ0pnIFX7acB2FGNkUDOwmq','meim@gmail.com','Ivan',NULL,'Meim',NULL,'09674073456',NULL,NULL,4,NULL,'active','2026-04-09 04:39:51','2026-04-08 20:40:01','pending',0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,NULL,NULL),(17,'marialourdes','$2y$10$.Ahz0g5ZnOW8wcHNRjwK3O3WdRBHHpLjfe3lXdr7rm3gf74YB5FaW','mlgarcia.test@gmail.com','Maria Lourdes',NULL,'Garcia',NULL,'0916-884-1203',NULL,NULL,4,1,'active','2026-05-18 08:09:20','2026-08-08 05:59:22','verified',1,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,NULL,NULL),(18,'ivantzy','$2y$10$vMkFqr7u2qRU6uoE8KwS8unyPnI9sgL18QIKLEvw4XfRjr9fqBiUi','ivan125@gmail.cm','Ivan',NULL,'Meim',NULL,'09776987473',NULL,NULL,4,1,'active','2026-06-03 07:11:56','2026-06-02 23:12:08','verified',1,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,NULL,NULL),(21,'mikey','$2y$10$zOTZ8xvVDp.n8Bs6TXc3q.xA4GIncPNLVjHNW.nKXfXfMqBgUqcZ6','mikey@gmail.com','Mickey',NULL,'laurence',NULL,'09369111954',NULL,NULL,3,NULL,'active','2026-06-04 06:29:19','2026-06-23 06:36:18','pending',0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,NULL,NULL),(22,'paulluna','$2y$10$7YCJPSVl5mMj7UqsIsTznuzpF.Cto94tyEpPiH9lTKG0lBosSvclm','paulluna@gmail.com','Paul',NULL,'Luna',NULL,'12345678910',NULL,NULL,4,NULL,'active','2026-06-04 09:12:15','2026-06-10 05:01:53','pending',0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,NULL,NULL),(23,'renatoalcalde','$2y$10$Ha1HAGrhXhNZvcMFy0LTJOP1/vYxsNKHlkFhloAfoKstCG6NWfCc2','renatoalcalde@gmail.com','Renato',NULL,'Alcalde',NULL,'12345678901',NULL,NULL,3,1,'active','2026-06-16 01:00:37','2026-08-07 16:06:56','verified',0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,NULL,NULL),(24,'maria1','$2y$10$Yb/tMb3tO6kodVI7bEN0.O3wQDK0UkZzApcYBj0i4ccENsFlWvxlW','maria1@gmail.com','Maria Cristina',NULL,'Santos',NULL,'09178452136',NULL,NULL,4,1,'active','2026-06-16 04:19:20','2026-08-10 07:05:59','verified',1,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,NULL,NULL),(25,'robertomendoza','$2y$10$CzYRPTUSLZTAtR.WyeZmX.N.YzdGJ62bqi2jGMTCfuMjgnt.svYt.','robertomendoza@gmail.com','Roberto',NULL,'Mendoza',NULL,'09176324815',NULL,NULL,4,1,'active','2026-07-16 13:42:19','2026-07-27 02:58:57','verified',1,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,NULL,NULL),(91,'regineaclan','$2y$10$GT56pgEUnUZtvoH46vlWRukx28gUTvQ.cr.flY7OPnNXv9b9u8mHO','regineaclan01@gmail.com','Regine',NULL,'Aclan',NULL,'09120546771',NULL,NULL,4,NULL,'active','2026-08-08 00:06:44','2026-08-08 05:15:25','pending',0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-11 21:32:23
