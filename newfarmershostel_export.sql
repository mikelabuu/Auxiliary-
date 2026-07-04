-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: newfarmershostel
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
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'staff',
  `action` varchar(100) NOT NULL,
  `target_type` varchar(100) NOT NULL,
  `target_id` bigint(20) unsigned DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_target_type_target_id_index` (`target_type`,`target_id`),
  KEY `audit_logs_staff_id_index` (`staff_id`),
  CONSTRAINT `audit_logs_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,9,'master_admin','otp_requested','Staff',9,NULL,'{\"otp_code\":\"52***\"}','Staff requested a login OTP','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-14 01:28:07','2026-04-14 01:28:07'),(2,9,'master_admin','otp_requested','Staff',9,NULL,'{\"otp_code\":\"75***\"}','Staff requested a login OTP','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-14 01:28:16','2026-04-14 01:28:16'),(3,7,'frontdesk','otp_requested','Staff',7,NULL,'{\"otp_code\":\"92***\"}','Staff requested a login OTP','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-14 01:29:05','2026-04-14 01:29:05'),(4,9,'master_admin','otp_requested','Staff',9,NULL,'{\"otp_code\":\"23***\"}','Staff requested a login OTP','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-14 01:30:03','2026-04-14 01:30:03'),(5,9,'master_admin','staff_created','Staff',11,NULL,'{\"id\":11,\"name\":\"Artemio D. Mangaoang Jr.\",\"email\":\"foartemiomangaoang01@gmail.com\",\"role\":\"admin\"}','Staff maclccacho (master_admin) created a new staff account: Artemio D. Mangaoang Jr. (admin).','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-14 01:34:33','2026-04-14 01:34:33'),(6,11,'admin','otp_requested','Staff',11,NULL,'{\"otp_code\":\"83***\"}','Staff requested a login OTP','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-14 01:38:45','2026-04-14 01:38:45'),(7,9,'master_admin','otp_requested','Staff',9,NULL,'{\"otp_code\":\"22***\"}','Staff requested a login OTP','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-14 01:39:10','2026-04-14 01:39:10'),(8,9,'master_admin','otp_requested','Staff',9,NULL,'{\"otp_code\":\"96***\"}','Staff requested a login OTP','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-14 01:39:13','2026-04-14 01:39:13'),(9,11,'admin','manual_booking_created','Booking',1,NULL,'{\"status\":\"paid\"}','Front desk staff Artemio D. Mangaoang Jr. created walk-in booking #1 (Status: paid)','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-14 01:43:43','2026-04-14 01:43:43'),(10,11,'admin','view_booking_modal','Booking',1,NULL,NULL,'Admin Artemio D. Mangaoang Jr. viewed booking #1 in modal','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-14 01:44:33','2026-04-14 01:44:33'),(11,9,'master_admin','booking_checked_in','Booking',1,'{\"status\":\"paid\"}','{\"status\":\"active\"}','Booking #1 checked in by maclccacho','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-14 01:45:19','2026-04-14 01:45:19'),(12,9,'master_admin','view_booking_modal','Booking',1,NULL,NULL,'Admin maclccacho viewed booking #1 in modal','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-14 01:45:30','2026-04-14 01:45:30'),(13,9,'master_admin','view_booking_modal','Booking',1,NULL,NULL,'Admin maclccacho viewed booking #1 in modal','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-14 01:46:08','2026-04-14 01:46:08'),(14,9,'master_admin','booking_checked_out','Booking',1,'{\"status\":\"active\"}','{\"status\":\"completed\"}','Booking #1 checked out by maclccacho','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-14 01:50:18','2026-04-14 01:50:18'),(15,11,'admin','booking_checked_out','Booking',1,'{\"status\":\"active\"}','{\"status\":\"completed\"}','Booking #1 checked out by Artemio D. Mangaoang Jr.','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-14 01:50:32','2026-04-14 01:50:32'),(16,11,'admin','view_completed_booking_details','Booking',1,NULL,NULL,'Staff Artemio D. Mangaoang Jr. viewed details for completed booking #1','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-14 01:50:49','2026-04-14 01:50:49'),(17,11,'admin','manual_booking_created','Booking',2,NULL,'{\"status\":\"paid\"}','Front desk staff Artemio D. Mangaoang Jr. created walk-in booking #2 (Status: paid)','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-14 01:53:09','2026-04-14 01:53:09'),(18,9,'master_admin','view_discount_requests','Discount',1,NULL,NULL,'Staff maclccacho viewed discount request #1 for booking #3','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-14 01:56:58','2026-04-14 01:56:58'),(19,9,'master_admin','discount_file_approved','DiscountFile',1,'{\"status\":\"pending\"}','{\"status\":\"approved\"}','Staff maclccacho approved a discount ID file (ID: 1) for booking #3','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-14 01:57:01','2026-04-14 01:57:01'),(20,9,'master_admin','view_discount_requests','Discount',1,NULL,NULL,'Staff maclccacho viewed discount request #1 for booking #3','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-14 01:57:01','2026-04-14 01:57:01'),(21,9,'master_admin','discount_request_applied','Discount',1,'{\"status\":\"pending\"}','{\"status\":\"approved\"}','Staff maclccacho Approved a discount #1 for booking #3 (₱300).','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-14 01:57:05','2026-04-14 01:57:05'),(22,11,'admin','otp_requested','Staff',11,NULL,'{\"otp_code\":\"56***\"}','Staff requested a login OTP','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-14 02:08:29','2026-04-14 02:08:29'),(23,11,'admin','otp_requested','Staff',11,NULL,'{\"otp_code\":\"31***\"}','Staff requested a login OTP','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-14 02:08:32','2026-04-14 02:08:32'),(24,11,'admin','manual_booking_created','Booking',4,NULL,'{\"status\":\"paid\"}','Front desk staff Artemio D. Mangaoang Jr. created walk-in booking #4 (Status: paid)','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-14 02:12:05','2026-04-14 02:12:05'),(25,11,'admin','manual_booking_created','Booking',5,NULL,'{\"status\":\"paid\"}','Front desk staff Artemio D. Mangaoang Jr. created walk-in booking #5 (Status: paid)','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-14 02:15:14','2026-04-14 02:15:14'),(26,9,'master_admin','view_booking_modal','Booking',3,NULL,NULL,'Admin maclccacho viewed booking #3 in modal','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-14 02:15:39','2026-04-14 02:15:39'),(27,9,'master_admin','otp_requested','Staff',9,NULL,'{\"otp_code\":\"77***\"}','Staff requested a login OTP','202.90.128.130','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-15 03:55:27','2026-04-15 03:55:27'),(28,9,'master_admin','otp_requested','Staff',9,NULL,'{\"otp_code\":\"29***\"}','Staff requested a login OTP','202.90.128.130','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-15 03:55:29','2026-04-15 03:55:29'),(29,11,'master_admin','otp_requested','Staff',11,NULL,'{\"otp_code\":\"92***\"}','Staff requested a login OTP','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-15 06:29:16','2026-04-15 06:29:16'),(30,11,'master_admin','otp_requested','Staff',11,NULL,'{\"otp_code\":\"53***\"}','Staff requested a login OTP','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-15 06:29:16','2026-04-15 06:29:16'),(31,11,'master_admin','manual_booking_created','Booking',6,NULL,'{\"status\":\"paid\"}','Front desk staff Artemio D. Mangaoang Jr. created walk-in booking #6 (Status: paid)','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-15 06:30:21','2026-04-15 06:30:21'),(32,11,'master_admin','manual_booking_created','Booking',7,NULL,'{\"status\":\"paid\"}','Front desk staff Artemio D. Mangaoang Jr. created walk-in booking #7 (Status: paid)','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-15 06:31:14','2026-04-15 06:31:14'),(33,11,'master_admin','manual_booking_created','Booking',8,NULL,'{\"status\":\"paid\"}','Front desk staff Artemio D. Mangaoang Jr. created walk-in booking #8 (Status: paid)','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-15 06:32:33','2026-04-15 06:32:33'),(34,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"83***\"}','Staff requested a login OTP','103.39.146.112','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-15 10:31:10','2026-04-15 10:31:10'),(35,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"62***\"}','Staff requested a login OTP','103.39.146.116','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-15 15:29:40','2026-04-15 15:29:40'),(36,1,'master_admin','view_payment_records','Payments',NULL,NULL,NULL,'Staff Admin1 viewed payment records (page 1 showing 8 of 8 total).','103.39.146.116','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-15 15:30:32','2026-04-15 15:30:32'),(37,1,'master_admin','view_payment_records','Payments',NULL,NULL,NULL,'Staff Admin1 viewed payment records (page 1 showing 8 of 8 total).','103.39.146.116','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-15 15:31:08','2026-04-15 15:31:08'),(38,1,'master_admin','view_payment_records','Payments',NULL,NULL,NULL,'Staff Admin1 viewed payment records (page 1 showing 8 of 8 total).','103.39.146.116','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-15 15:31:22','2026-04-15 15:31:22'),(39,1,'master_admin','view_payment_records','Payments',NULL,NULL,NULL,'Staff Admin1 viewed payment records (page 1 showing 8 of 8 total).','103.39.146.115','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-15 15:34:45','2026-04-15 15:34:45'),(40,1,'master_admin','view_payment_records','Payments',NULL,NULL,NULL,'Staff Admin1 viewed payment records (page 1 showing 8 of 8 total).','103.39.146.115','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-15 15:35:34','2026-04-15 15:35:34'),(41,1,'master_admin','view_payment_records','Payments',NULL,NULL,NULL,'Staff Admin1 viewed payment records (page 1 showing 8 of 8 total).','103.39.146.115','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-15 15:36:13','2026-04-15 15:36:13'),(42,1,'master_admin','view_payment_records','Payments',NULL,NULL,NULL,'Staff Admin1 viewed payment records (page 1 showing 8 of 8 total).','103.39.146.114','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-15 15:41:33','2026-04-15 15:41:33'),(43,1,'master_admin','view_payment_records','Payments',NULL,NULL,NULL,'Staff Admin1 viewed payment records (page 1 showing 8 of 8 total).','103.39.146.114','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-15 15:41:35','2026-04-15 15:41:35'),(44,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"91***\"}','Staff requested a login OTP','103.39.146.118','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-15 15:50:55','2026-04-15 15:50:55'),(45,11,'master_admin','otp_requested','Staff',11,NULL,'{\"otp_code\":\"73***\"}','Staff requested a login OTP','136.239.247.101	','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-15 15:51:29','2026-04-15 15:51:29'),(46,11,'master_admin','otp_requested','Staff',11,NULL,'{\"otp_code\":\"11***\"}','Staff requested a login OTP','136.239.247.101	','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-16 04:00:13','2026-04-16 04:00:13'),(47,11,'master_admin','booking_checked_in','Booking',2,'{\"status\":\"paid\"}','{\"status\":\"active\"}','Booking #2 checked in by Artemio D. Mangaoang Jr.','136.239.247.101	','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-16 07:47:09','2026-04-16 07:47:09'),(48,11,'master_admin','otp_requested','Staff',11,NULL,'{\"otp_code\":\"92***\"}','Staff requested a login OTP','136.239.247.101	','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 04:34:00','2026-04-17 04:34:00'),(49,11,'master_admin','booking_checked_out','Booking',2,'{\"status\":\"active\"}','{\"status\":\"completed\"}','Booking #2 checked out by Artemio D. Mangaoang Jr.','136.239.247.101	','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 04:34:20','2026-04-17 04:34:20'),(50,11,'master_admin','booking_checked_in','Booking',4,'{\"status\":\"paid\"}','{\"status\":\"active\"}','Booking #4 checked in by Artemio D. Mangaoang Jr.','136.239.247.101	','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-17 04:35:21','2026-04-17 04:35:21'),(51,11,'master_admin','otp_requested','Staff',11,NULL,'{\"otp_code\":\"89***\"}','Staff requested a login OTP','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 05:53:56','2026-04-18 05:53:56'),(52,11,'master_admin','booking_checked_out','Booking',4,'{\"status\":\"active\"}','{\"status\":\"completed\"}','Booking #4 checked out by Artemio D. Mangaoang Jr.','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 05:54:27','2026-04-18 05:54:27'),(53,11,'master_admin','booking_checked_in','Booking',6,'{\"status\":\"paid\"}','{\"status\":\"active\"}','Booking #6 checked in by Artemio D. Mangaoang Jr.','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 05:54:31','2026-04-18 05:54:31'),(54,11,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"89***\"}','Staff requested a login OTP','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-19 04:18:32','2026-04-19 04:18:32'),(55,11,'master_admin','booking_checked_out','Booking',6,'{\"status\":\"active\"}','{\"status\":\"completed\"}','Booking #6 checked out by Artemio D. Mangaoang Jr.','136.239.247.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-19 04:18:57','2026-04-19 04:18:57'),(56,5,'master_admin','otp_requested','Staff',5,NULL,'{\"otp_code\":\"30***\"}','Staff requested a login OTP','136.158.124.194','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-19 11:33:28','2026-04-19 11:33:28'),(57,5,'master_admin','view_payment_records','Payments',NULL,NULL,NULL,'Staff Xciamiah Abad viewed payment records (page 1 showing 8 of 8 total).','136.158.124.194','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-19 11:41:13','2026-04-19 11:41:13'),(58,5,'master_admin','staff_created','Staff',12,NULL,'{\"id\":12,\"name\":\"Xciamiah Gail Abad\",\"email\":\"maxciamiah.abad@clsu2.edu.ph\",\"role\":\"frontdesk\"}','Staff Xciamiah Abad (master_admin) created a new staff account: Xciamiah Gail Abad (frontdesk).','136.158.124.194','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-19 11:53:09','2026-04-19 11:53:09'),(59,12,'frontdesk','otp_requested','Staff',12,NULL,'{\"otp_code\":\"96***\"}','Staff requested a login OTP','136.158.124.194','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-19 11:54:04','2026-04-19 11:54:04'),(60,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"23***\"}','Staff requested a login OTP','202.90.128.130','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:27:40','2026-04-20 01:27:40'),(61,1,'master_admin','view_booking_modal','Booking',5,NULL,NULL,'Admin Admin1 viewed booking #5 in modal','202.90.128.130','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:29:42','2026-04-20 01:29:42'),(62,1,'master_admin','view_booking_modal','Booking',5,NULL,NULL,'Admin Admin1 viewed booking #5 in modal','202.90.128.130','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:31:48','2026-04-20 01:31:48'),(63,5,'master_admin','otp_requested','Staff',5,NULL,'{\"otp_code\":\"86***\"}','Staff requested a login OTP','124.83.118.209','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Safari/605.1.15','2026-04-22 05:37:36','2026-04-22 05:37:36'),(64,5,'master_admin','otp_requested','Staff',5,NULL,'{\"otp_code\":\"57***\"}','Staff requested a login OTP','124.83.118.209','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Safari/605.1.15','2026-04-22 05:37:36','2026-04-22 05:37:36'),(65,5,'master_admin','view_payment_records','Payments',NULL,NULL,NULL,'Staff Xciamiah Abad viewed payment records (page 1 showing 8 of 8 total).','124.83.118.209','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Safari/605.1.15','2026-04-22 05:38:48','2026-04-22 05:38:48'),(66,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"76***\"}','Staff requested a login OTP','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 17:00:48','2026-06-30 17:00:48'),(67,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"12***\"}','Staff requested a login OTP','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 17:01:55','2026-06-30 17:01:55'),(68,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"15***\"}','Staff requested a login OTP','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 17:08:08','2026-06-30 17:08:08'),(69,1,'master_admin','view_booking_modal','Booking',9,NULL,NULL,'Admin Mike Francis Vengazo viewed booking #9 in modal','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 17:09:16','2026-06-30 17:09:16'),(70,1,'master_admin','view_booking_modal','Booking',8,NULL,NULL,'Admin Mike Francis Vengazo viewed booking #8 in modal','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 17:09:28','2026-06-30 17:09:28'),(71,1,'master_admin','view_payment_records','Payments',NULL,NULL,NULL,'Staff Mike Francis Vengazo viewed payment records (page 1 showing 9 of 9 total).','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 17:10:06','2026-06-30 17:10:06'),(72,1,'master_admin','view_payment_records','Payments',NULL,NULL,NULL,'Staff Mike Francis Vengazo viewed payment records (page 1 showing 9 of 9 total).','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 17:11:25','2026-06-30 17:11:25'),(73,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"38***\"}','Staff requested a login OTP','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 20:38:13','2026-06-30 20:38:13'),(74,1,'master_admin','view_booking_modal','Booking',9,NULL,NULL,'Admin Mike Francis Vengazo viewed booking #9 in modal','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 20:40:11','2026-06-30 20:40:11'),(75,1,'master_admin','view_completed_booking_details','Booking',6,NULL,NULL,'Staff Mike Francis Vengazo viewed details for completed booking #6','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 20:40:35','2026-06-30 20:40:35'),(76,1,'master_admin','view_discount_requests','Discount',1,NULL,NULL,'Staff Mike Francis Vengazo viewed discount request #1 for booking #3','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 20:40:59','2026-06-30 20:40:59'),(77,1,'master_admin','view_payment_records','Payments',NULL,NULL,NULL,'Staff Mike Francis Vengazo viewed payment records (page 1 showing 9 of 9 total).','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 20:41:08','2026-06-30 20:41:08'),(78,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"63***\"}','Staff requested a login OTP','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 21:57:55','2026-06-30 21:57:55'),(79,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"74***\"}','Staff requested a login OTP','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 21:59:51','2026-06-30 21:59:51'),(80,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"56***\"}','Staff requested a login OTP','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 22:00:54','2026-06-30 22:00:54'),(81,1,'master_admin','view_booking_modal','Booking',9,NULL,NULL,'Admin Mike Francis Vengazo viewed booking #9 in modal','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 22:02:43','2026-06-30 22:02:43'),(82,1,'master_admin','view_payment_records','Payments',NULL,NULL,NULL,'Staff Mike Francis Vengazo viewed payment records (page 1 showing 9 of 9 total).','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 22:03:03','2026-06-30 22:03:03'),(83,1,'master_admin','view_payment_records','Payments',NULL,NULL,NULL,'Staff Mike Francis Vengazo viewed payment records (page 1 showing 9 of 9 total).','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 22:03:20','2026-06-30 22:03:20'),(84,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"72***\"}','Staff requested a login OTP','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-01 00:15:54','2026-07-01 00:15:54'),(85,1,'master_admin','manual_booking_created','Booking',10,NULL,'{\"status\":\"paid\"}','Front desk staff Mike Francis Vengazo created walk-in booking #10 (Status: paid)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-01 00:34:50','2026-07-01 00:34:50'),(86,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"76***\"}','Staff requested a login OTP','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-01 05:12:31','2026-07-01 05:12:31'),(87,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"77***\"}','Staff requested a login OTP','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-01 21:05:04','2026-07-01 21:05:04'),(88,1,'master_admin','view_payment_records','Payments',NULL,NULL,NULL,'Staff Mike Francis Vengazo viewed payment records (page 1 showing 10 of 10 total).','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-01 21:21:48','2026-07-01 21:21:48'),(89,13,'master_admin','otp_requested','Staff',13,NULL,'{\"otp_code\":\"14***\"}','Staff requested a login OTP','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-01 22:38:17','2026-07-01 22:38:17'),(90,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"76***\"}','Staff requested a login OTP','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-02 18:30:03','2026-07-02 18:30:03'),(91,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"57***\"}','Staff requested a login OTP','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-02 23:01:02','2026-07-02 23:01:02'),(92,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"45***\"}','Staff requested a login OTP','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-02 23:04:49','2026-07-02 23:04:49'),(93,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"69***\"}','Staff requested a login OTP','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-02 23:14:17','2026-07-02 23:14:17'),(94,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"81***\"}','Staff requested a login OTP','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-03 15:33:36','2026-07-03 15:33:36'),(95,1,'master_admin','otp_requested','Staff',1,NULL,'{\"otp_code\":\"56***\"}','Staff requested a login OTP','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-03 15:34:17','2026-07-03 15:34:17');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `balances`
--

DROP TABLE IF EXISTS `balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `balances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `remaining_balance` decimal(10,2) NOT NULL,
  `status` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `balances_booking_id_foreign` (`booking_id`),
  KEY `balances_user_id_foreign` (`user_id`),
  CONSTRAINT `balances_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `balances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `balances`
--

LOCK TABLES `balances` WRITE;
/*!40000 ALTER TABLE `balances` DISABLE KEYS */;
/*!40000 ALTER TABLE `balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_room`
--

DROP TABLE IF EXISTS `booking_room`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `booking_room` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `room_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_room_booking_id_room_id_unique` (`booking_id`,`room_id`),
  KEY `booking_room_room_id_foreign` (`room_id`),
  CONSTRAINT `booking_room_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_room_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_room`
--

LOCK TABLES `booking_room` WRITE;
/*!40000 ALTER TABLE `booking_room` DISABLE KEYS */;
INSERT INTO `booking_room` VALUES (1,1,11,'2026-04-14 01:43:43','2026-04-14 01:43:43'),(2,2,7,'2026-04-14 01:53:09','2026-04-14 01:53:09'),(3,3,5,'2026-04-14 01:56:16','2026-04-14 01:56:16'),(4,4,6,'2026-04-14 02:12:05','2026-04-14 02:12:05'),(5,5,13,'2026-04-14 02:15:14','2026-04-14 02:15:14'),(6,5,14,'2026-04-14 02:15:14','2026-04-14 02:15:14'),(7,5,19,'2026-04-14 02:15:14','2026-04-14 02:15:14'),(8,5,20,'2026-04-14 02:15:14','2026-04-14 02:15:14'),(9,5,21,'2026-04-14 02:15:14','2026-04-14 02:15:14'),(10,5,18,'2026-04-14 02:15:14','2026-04-14 02:15:14'),(11,6,8,'2026-04-15 06:30:21','2026-04-15 06:30:21'),(12,7,10,'2026-04-15 06:31:14','2026-04-15 06:31:14'),(13,8,12,'2026-04-15 06:32:33','2026-04-15 06:32:33'),(14,9,11,'2026-06-30 16:50:52','2026-06-30 16:50:52'),(15,10,1,'2026-07-01 00:34:50','2026-07-01 00:34:50');
/*!40000 ALTER TABLE `booking_room` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `room_numbers` varchar(255) NOT NULL,
  `expected_guests` int(11) NOT NULL DEFAULT 1,
  `guest_name` varchar(255) NOT NULL,
  `guest_address` varchar(255) NOT NULL,
  `guest_phone` varchar(255) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL,
  `payable_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending_discount','pending_payment','paid','confirmed','active','completed','cancelled','no_show','expired') NOT NULL DEFAULT 'pending_payment',
  `payment_mode` varchar(255) DEFAULT NULL,
  `pending_payment_since` timestamp NULL DEFAULT NULL,
  `num_seniors` int(11) DEFAULT 0,
  `wants_discount` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bookings_user_id_foreign` (`user_id`),
  KEY `idx_bookings_availability` (`check_in`,`check_out`,`status`),
  CONSTRAINT `bookings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (1,NULL,'112',2,'Warren','Cabatang, Alicia, Bohol','09123456789','2026-04-14','2026-04-16',360.00,3600.00,3240.00,'completed','manual',NULL,0,1,'2026-04-14 01:43:43','2026-04-14 01:50:18'),(2,NULL,'107',3,'Clarrise Viernes','Bantug, Science City of Muñoz, Nueva Ecija','09353336064','2026-04-16','2026-04-17',0.00,2400.00,2400.00,'completed','manual',NULL,0,0,'2026-04-14 01:53:09','2026-04-17 04:34:20'),(3,11,'105',1,'thepresident, office, of','Bantug, Science City of Muñoz, Nueva Ecija','09123456789','2026-04-17','2026-04-18',300.00,2500.00,2200.00,'expired','system',NULL,1,1,'2026-04-14 01:56:16','2026-04-14 02:58:03'),(4,NULL,'106',2,'Kenn','Bantug, Science City of Muñoz, Nueva Ecija','09353336064','2026-04-17','2026-04-18',0.00,3000.00,3000.00,'completed','manual',NULL,0,0,'2026-04-14 02:12:05','2026-04-18 05:54:27'),(5,NULL,'203,204,214,215,216,212',30,'loida','Bantug, Science City of Muñoz, Nueva Ecija','09123456789','2026-04-20','2026-04-23',0.00,50100.00,50100.00,'no_show','manual',NULL,0,0,'2026-04-14 02:15:14','2026-04-21 00:05:03'),(6,NULL,'108',3,'Voltair','Bantug, Science City of Muñoz, Nueva Ecija','09123456789','2026-04-18','2026-04-19',0.00,2400.00,2400.00,'completed','manual',NULL,0,0,'2026-04-15 06:30:21','2026-04-19 04:18:57'),(7,NULL,'110',2,'USSC','Bantug, Science City of Muñoz, Nueva Ecija','09123456789','2026-04-15','2026-04-16',0.00,1800.00,1800.00,'no_show','manual',NULL,0,0,'2026-04-15 06:31:14','2026-04-16 00:05:03'),(8,NULL,'202',4,'CLAARDEC','Bantug, Science City of Muñoz, Nueva Ecija','09123456789','2026-04-20','2026-04-24',0.00,11200.00,11200.00,'no_show','manual',NULL,0,0,'2026-04-15 06:32:33','2026-04-21 00:05:03'),(9,12,'112',2,'doe, john, Francis','Ben-agan , City of Batac, Ilocos Norte','09123532161','2026-07-01','2026-07-02',0.00,1600.00,NULL,'pending_payment','system','2026-06-30 16:50:52',0,1,'2026-06-30 16:50:52','2026-06-30 16:50:52'),(10,NULL,'101',1,'<script>console.log(\"JETS_OUTERHTML:\", document.querySelector(\'aside\').outerHTML)</script>','Caddayan, Akbar, Basilan','09123456789','2026-07-01','2026-07-02',0.00,3000.00,3000.00,'paid','manual',NULL,0,0,'2026-07-01 00:34:50','2026-07-01 00:34:50');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('farmershostel_cache_dashboard:occupancy:2026-07-03','a:13:{s:5:\"total\";i:22;s:8:\"occupied\";i:0;s:9:\"available\";i:22;s:7:\"percent\";d:0;s:9:\"dormTotal\";i:4;s:12:\"dormOccupied\";i:0;s:11:\"dormPercent\";d:0;s:13:\"standardTotal\";i:11;s:16:\"standardOccupied\";i:0;s:15:\"standardPercent\";d:0;s:11:\"deluxeTotal\";i:7;s:14:\"deluxeOccupied\";i:0;s:13:\"deluxePercent\";d:0;}',1783063413),('farmershostel_cache_dashboard:occupancy:2026-07-04','a:13:{s:5:\"total\";i:22;s:8:\"occupied\";i:0;s:9:\"available\";i:22;s:7:\"percent\";d:0;s:9:\"dormTotal\";i:4;s:12:\"dormOccupied\";i:0;s:11:\"dormPercent\";d:0;s:13:\"standardTotal\";i:11;s:16:\"standardOccupied\";i:0;s:15:\"standardPercent\";d:0;s:11:\"deluxeTotal\";i:7;s:14:\"deluxeOccupied\";i:0;s:13:\"deluxePercent\";d:0;}',1783121708),('farmershostel_cache_psgc_regions','a:17:{i:0;a:5:{s:4:\"code\";s:9:\"150000000\";s:4:\"name\";s:5:\"BARMM\";s:10:\"regionName\";s:47:\"Bangsamoro Autonomous Region in Muslim Mindanao\";s:15:\"islandGroupCode\";s:8:\"mindanao\";s:15:\"psgc10DigitCode\";s:10:\"1900000000\";}i:1;a:5:{s:4:\"code\";s:9:\"050000000\";s:4:\"name\";s:12:\"Bicol Region\";s:10:\"regionName\";s:8:\"Region V\";s:15:\"islandGroupCode\";s:5:\"luzon\";s:15:\"psgc10DigitCode\";s:10:\"0500000000\";}i:2;a:5:{s:4:\"code\";s:9:\"040000000\";s:4:\"name\";s:10:\"CALABARZON\";s:10:\"regionName\";s:11:\"Region IV-A\";s:15:\"islandGroupCode\";s:5:\"luzon\";s:15:\"psgc10DigitCode\";s:10:\"0400000000\";}i:3;a:5:{s:4:\"code\";s:9:\"140000000\";s:4:\"name\";s:3:\"CAR\";s:10:\"regionName\";s:32:\"Cordillera Administrative Region\";s:15:\"islandGroupCode\";s:5:\"luzon\";s:15:\"psgc10DigitCode\";s:10:\"1400000000\";}i:4;a:5:{s:4:\"code\";s:9:\"020000000\";s:4:\"name\";s:14:\"Cagayan Valley\";s:10:\"regionName\";s:9:\"Region II\";s:15:\"islandGroupCode\";s:5:\"luzon\";s:15:\"psgc10DigitCode\";s:10:\"0200000000\";}i:5;a:5:{s:4:\"code\";s:9:\"160000000\";s:4:\"name\";s:6:\"Caraga\";s:10:\"regionName\";s:11:\"Region XIII\";s:15:\"islandGroupCode\";s:8:\"mindanao\";s:15:\"psgc10DigitCode\";s:10:\"1600000000\";}i:6;a:5:{s:4:\"code\";s:9:\"030000000\";s:4:\"name\";s:13:\"Central Luzon\";s:10:\"regionName\";s:10:\"Region III\";s:15:\"islandGroupCode\";s:5:\"luzon\";s:15:\"psgc10DigitCode\";s:10:\"0300000000\";}i:7;a:5:{s:4:\"code\";s:9:\"070000000\";s:4:\"name\";s:15:\"Central Visayas\";s:10:\"regionName\";s:10:\"Region VII\";s:15:\"islandGroupCode\";s:7:\"visayas\";s:15:\"psgc10DigitCode\";s:10:\"0700000000\";}i:8;a:5:{s:4:\"code\";s:9:\"110000000\";s:4:\"name\";s:12:\"Davao Region\";s:10:\"regionName\";s:9:\"Region XI\";s:15:\"islandGroupCode\";s:8:\"mindanao\";s:15:\"psgc10DigitCode\";s:10:\"1100000000\";}i:9;a:5:{s:4:\"code\";s:9:\"080000000\";s:4:\"name\";s:15:\"Eastern Visayas\";s:10:\"regionName\";s:11:\"Region VIII\";s:15:\"islandGroupCode\";s:7:\"visayas\";s:15:\"psgc10DigitCode\";s:10:\"0800000000\";}i:10;a:5:{s:4:\"code\";s:9:\"010000000\";s:4:\"name\";s:13:\"Ilocos Region\";s:10:\"regionName\";s:8:\"Region I\";s:15:\"islandGroupCode\";s:5:\"luzon\";s:15:\"psgc10DigitCode\";s:10:\"0100000000\";}i:11;a:5:{s:4:\"code\";s:9:\"170000000\";s:4:\"name\";s:15:\"MIMAROPA Region\";s:10:\"regionName\";s:15:\"MIMAROPA Region\";s:15:\"islandGroupCode\";s:5:\"luzon\";s:15:\"psgc10DigitCode\";s:10:\"1700000000\";}i:12;a:5:{s:4:\"code\";s:9:\"130000000\";s:4:\"name\";s:3:\"NCR\";s:10:\"regionName\";s:23:\"National Capital Region\";s:15:\"islandGroupCode\";s:5:\"luzon\";s:15:\"psgc10DigitCode\";s:10:\"1300000000\";}i:13;a:5:{s:4:\"code\";s:9:\"100000000\";s:4:\"name\";s:17:\"Northern Mindanao\";s:10:\"regionName\";s:8:\"Region X\";s:15:\"islandGroupCode\";s:8:\"mindanao\";s:15:\"psgc10DigitCode\";s:10:\"1000000000\";}i:14;a:5:{s:4:\"code\";s:9:\"120000000\";s:4:\"name\";s:12:\"SOCCSKSARGEN\";s:10:\"regionName\";s:10:\"Region XII\";s:15:\"islandGroupCode\";s:8:\"mindanao\";s:15:\"psgc10DigitCode\";s:10:\"1200000000\";}i:15;a:5:{s:4:\"code\";s:9:\"060000000\";s:4:\"name\";s:15:\"Western Visayas\";s:10:\"regionName\";s:9:\"Region VI\";s:15:\"islandGroupCode\";s:7:\"visayas\";s:15:\"psgc10DigitCode\";s:10:\"0600000000\";}i:16;a:5:{s:4:\"code\";s:9:\"090000000\";s:4:\"name\";s:19:\"Zamboanga Peninsula\";s:10:\"regionName\";s:9:\"Region IX\";s:15:\"islandGroupCode\";s:8:\"mindanao\";s:15:\"psgc10DigitCode\";s:10:\"0900000000\";}}',1785655388);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cancellation_logs`
--

DROP TABLE IF EXISTS `cancellation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cancellation_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `cancelled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cancelled_by` varchar(255) NOT NULL DEFAULT 'user',
  `reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cancellation_logs_booking_id_foreign` (`booking_id`),
  CONSTRAINT `cancellation_logs_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cancellation_logs`
--

LOCK TABLES `cancellation_logs` WRITE;
/*!40000 ALTER TABLE `cancellation_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `cancellation_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checkins`
--

DROP TABLE IF EXISTS `checkins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `checkins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `checked_in_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `checkins_booking_id_foreign` (`booking_id`),
  KEY `checkins_processed_by_foreign` (`processed_by`),
  CONSTRAINT `checkins_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `checkins_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checkins`
--

LOCK TABLES `checkins` WRITE;
/*!40000 ALTER TABLE `checkins` DISABLE KEYS */;
INSERT INTO `checkins` VALUES (1,1,'2026-04-14 09:45:19',11,'2026-04-14 01:45:19','2026-04-14 01:45:19'),(2,2,'2026-04-16 15:47:09',11,'2026-04-16 07:47:09','2026-04-16 07:47:09'),(3,4,'2026-04-17 12:35:21',11,'2026-04-17 04:35:21','2026-04-17 04:35:21'),(4,6,'2026-04-18 13:54:31',11,'2026-04-18 05:54:31','2026-04-18 05:54:31');
/*!40000 ALTER TABLE `checkins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checkouts`
--

DROP TABLE IF EXISTS `checkouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `checkouts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `checked_out_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `method` enum('auto','manual') NOT NULL DEFAULT 'auto',
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `checkouts_booking_id_foreign` (`booking_id`),
  CONSTRAINT `checkouts_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checkouts`
--

LOCK TABLES `checkouts` WRITE;
/*!40000 ALTER TABLE `checkouts` DISABLE KEYS */;
INSERT INTO `checkouts` VALUES (1,1,'2026-04-14 09:50:18','manual',11,'2026-04-14 01:50:18','2026-04-14 01:50:18'),(2,2,'2026-04-17 12:34:20','manual',11,'2026-04-17 04:34:20','2026-04-17 04:34:20'),(4,4,'2026-04-18 13:54:27','manual',11,'2026-04-18 05:54:27','2026-04-18 05:54:27'),(5,6,'2026-04-19 12:18:57','manual',11,'2026-04-19 04:18:57','2026-04-19 04:18:57');
/*!40000 ALTER TABLE `checkouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discount_files`
--

DROP TABLE IF EXISTS `discount_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `discount_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `discount_id` bigint(20) unsigned NOT NULL,
  `reservation_id` bigint(20) unsigned DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `discount_files_discount_id_foreign` (`discount_id`),
  KEY `discount_files_reservation_id_foreign` (`reservation_id`),
  CONSTRAINT `discount_files_discount_id_foreign` FOREIGN KEY (`discount_id`) REFERENCES `discounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `discount_files_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discount_files`
--

LOCK TABLES `discount_files` WRITE;
/*!40000 ALTER TABLE `discount_files` DISABLE KEYS */;
INSERT INTO `discount_files` VALUES (1,1,3,'discount_temp/bolw9jrURNmg4EgM6z4LvIqtL7E7QHjFDaxNYvTw.jpg','approved',9,'2026-04-14 01:57:01','2026-04-14 01:56:34','2026-04-14 01:56:34','2026-04-14 01:57:01');
/*!40000 ALTER TABLE `discount_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discounts`
--

DROP TABLE IF EXISTS `discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `discounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `discounts_booking_id_foreign` (`booking_id`),
  KEY `discounts_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `discounts_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `discounts_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discounts`
--

LOCK TABLES `discounts` WRITE;
/*!40000 ALTER TABLE `discounts` DISABLE KEYS */;
INSERT INTO `discounts` VALUES (1,3,300.00,'approved','2026-04-14 01:56:34','2026-04-14 01:57:05',9,NULL,'2026-04-14 01:56:34','2026-04-14 01:57:05');
/*!40000 ALTER TABLE `discounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expiry_logs`
--

DROP TABLE IF EXISTS `expiry_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expiry_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `previous_status` varchar(255) NOT NULL DEFAULT 'pending_payment',
  `new_status` varchar(255) NOT NULL DEFAULT 'expired',
  `reason` varchar(255) DEFAULT NULL,
  `expired_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expiry_logs_booking_id_foreign` (`booking_id`),
  KEY `expiry_logs_processed_by_foreign` (`processed_by`),
  CONSTRAINT `expiry_logs_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `expiry_logs_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expiry_logs`
--

LOCK TABLES `expiry_logs` WRITE;
/*!40000 ALTER TABLE `expiry_logs` DISABLE KEYS */;
INSERT INTO `expiry_logs` VALUES (1,3,'pending_payment','expired','Booking did not complete payment before expiry window.','2026-04-14 10:58:03',NULL,'2026-04-14 02:58:03','2026-04-14 02:58:03');
/*!40000 ALTER TABLE `expiry_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2025_09_05_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_09_11_000003_create_rooms_table',2),(5,'2025_09_11_000000_create_bookings_table',3),(6,'2025_09_11_000000_create_staff_table',4),(7,'2025_09_11_150608_create_booking_room_table',5),(8,'2025_09_12_000000_rename_name_to_username_in_users_table',6),(9,'2025_09_11_162128_add_price_to_rooms_table',7),(10,'2025_09_11_185808_create_discounts_table',8),(11,'2025_09_11_185903_create_discount_files_table',8),(12,'2025_09_12_234548_add_expected_guests_and_discounted_price_to_bookings_table',9),(13,'2025_09_13_010449_alter_bookings_status_column',10),(14,'2025_09_13_162029_add_wants_discount_to_bookings_table',11),(15,'2025_09_14_050908_add_status_and_review_columns_to_discount_files_table',12),(16,'2025_09_14_055410_rename_discounted_price_to_payable_amount_in_bookings_table',13),(17,'2025_09_15_083752_add_phone_to_users_table',14),(18,'2025_09_15_103241_add_last_cancelled_at_to_users_table',15),(19,'2025_09_19_043715_create_reservations_table',16),(20,'2025_09_19_043930_update_discount_files_table_add_reservation_id',17),(21,'2025_09_24_003824_add_num_guests_to_reservations_table',18),(22,'2025_09_25_023439_remove_meal_from_bookings_table',19),(23,'2025_09_25_023556_add_meal_to_reservations_table',20),(24,'2025_09_27_064233_add_expires_at_to_bookings_table',21),(25,'2025_09_27_064346_add_expired_status_to_bookings_table',22),(26,'2025_09_27_071001_add_pending_payment_since_to_bookings_table',23),(27,'2025_09_27_081043_remove_expires_at_from_bookings_table',24),(28,'2025_09_28_065019_create_checkouts_table',25),(29,'2025_09_28_085243_create_payments_table',26),(30,'2025_09_29_090856_create_staff_otps_table',27),(31,'2025_09_30_074057_add_status_to_rooms_table',28),(32,'2025_09_30_105537_add_last_edited_by',29),(34,'2025_10_04_080621_create_audit_logs_table',30),(35,'2025_10_06_075026_create_no_show_logs_table',31),(36,'2025_10_06_075627_create_expiry_logs_table',32),(37,'2025_10_08_140437_create_receipts_table',33),(38,'2025_10_08_143939_add_generated_by_to_receipts_table',34),(40,'2025_10_09_122453_create_checkin_table',35),(41,'2025_10_12_002753_add_processed_by_to_expiry_and_no_show_logs_tables',36),(42,'2025_10_12_062238_add_last_login_and_suspended_to_users_table',37),(43,'2025_10_12_082153_add_role_to_staff_table',38),(44,'2025_10_12_091614_add_last_login_at',39),(45,'2025_10_12_092104_add_verified_to_staff_table',40),(46,'2025_10_12_105354_add_is_suspended_column_to_staff',41),(47,'2025_10_12_125103_add_master_admin_role_to_staff',42),(49,'2025_10_12_161244_create_cancellation_logs_table',43),(50,'2025_12_15_133814_add_columns_to_payment_table',44),(51,'2025_12_15_134228_add_user_fk_to_payments_table',45),(52,'2025_12_15_134306_create_balances_table',46),(53,'2025_12_15_134359_add_payment_mode_to_bookings_table',47),(58,'2026_02_23_064905_create_regions_table',48),(59,'2026_02_23_064915_create_provinces_table',48),(60,'2026_02_23_064934_create_cities_table',48),(61,'2026_02_23_064956_create_baranggays_table',48),(62,'2026_07_01_052851_drop_location_tables',49);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `no_show_logs`
--

DROP TABLE IF EXISTS `no_show_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `no_show_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `previous_status` varchar(255) NOT NULL DEFAULT 'paid',
  `new_status` varchar(255) NOT NULL DEFAULT 'no_show',
  `reason` varchar(255) DEFAULT NULL,
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_show_logs_booking_id_foreign` (`booking_id`),
  KEY `no_show_logs_processed_by_foreign` (`processed_by`),
  CONSTRAINT `no_show_logs_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `no_show_logs_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `no_show_logs`
--

LOCK TABLES `no_show_logs` WRITE;
/*!40000 ALTER TABLE `no_show_logs` DISABLE KEYS */;
INSERT INTO `no_show_logs` VALUES (1,7,'paid','no_show','Guest did not check in by 11:00 PM.','2026-04-16 08:05:03',NULL,'2026-04-16 00:05:03','2026-04-16 00:05:03'),(2,5,'paid','no_show','Guest did not check in by 11:00 PM.','2026-04-21 08:05:03',NULL,'2026-04-21 00:05:03','2026-04-21 00:05:03'),(3,8,'paid','no_show','Guest did not check in by 11:00 PM.','2026-04-21 08:05:03',NULL,'2026-04-21 00:05:03','2026-04-21 00:05:03');
/*!40000 ALTER TABLE `no_show_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_type` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `reference_no` varchar(255) NOT NULL,
  `gateway` varchar(255) NOT NULL DEFAULT 'landbank',
  `paid_at` timestamp NULL DEFAULT NULL,
  `gateway_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_response`)),
  `landbank_transaction_id` varchar(255) DEFAULT NULL,
  `webhook_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_reference_no_unique` (`reference_no`),
  KEY `payments_booking_id_foreign` (`booking_id`),
  KEY `payments_user_id_foreign` (`user_id`),
  CONSTRAINT `payments_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,NULL,3240.00,'manual','success','PVAFDH46ZX','manual',NULL,NULL,NULL,0,'2026-04-14 01:43:43','2026-04-14 01:43:43'),(2,2,NULL,2400.00,'manual','success','MI4PJBAULM','manual',NULL,NULL,NULL,0,'2026-04-14 01:53:09','2026-04-14 01:53:09'),(3,3,11,2200.00,'online','failed','273PDEONKG','sandbox',NULL,NULL,NULL,0,'2026-04-14 01:57:13','2026-04-14 02:58:03'),(4,4,NULL,3000.00,'manual','success','6SDF4WMZJ8','manual',NULL,NULL,NULL,0,'2026-04-14 02:12:05','2026-04-14 02:12:05'),(5,5,NULL,50100.00,'manual','success','1XSVUL8C63','manual',NULL,NULL,NULL,0,'2026-04-14 02:15:14','2026-04-14 02:15:14'),(6,6,NULL,2400.00,'manual','success','7NY6ODPTCA','manual',NULL,NULL,NULL,0,'2026-04-15 06:30:21','2026-04-15 06:30:21'),(7,7,NULL,1800.00,'manual','success','BRBLVF8Q1D','manual',NULL,NULL,NULL,0,'2026-04-15 06:31:14','2026-04-15 06:31:14'),(8,8,NULL,11200.00,'manual','success','7HSROIOWXA','manual',NULL,NULL,NULL,0,'2026-04-15 06:32:33','2026-04-15 06:32:33'),(9,9,12,1600.00,'online','pending','AW3LIXAKEU','sandbox',NULL,NULL,NULL,0,'2026-06-30 16:50:58','2026-06-30 16:50:58'),(10,10,NULL,3000.00,'manual','success','JDAEQVSHRQ','manual',NULL,NULL,NULL,0,'2026-07-01 00:34:50','2026-07-01 00:34:50');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receipts`
--

DROP TABLE IF EXISTS `receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `receipt_number` varchar(255) NOT NULL,
  `generated_by` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `sha256_hash` varchar(64) NOT NULL,
  `issued_by` bigint(20) unsigned DEFAULT NULL,
  `issued_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipts_receipt_number_unique` (`receipt_number`),
  KEY `receipts_booking_id_foreign` (`booking_id`),
  CONSTRAINT `receipts_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receipts`
--

LOCK TABLES `receipts` WRITE;
/*!40000 ALTER TABLE `receipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reservations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `room_number` varchar(255) NOT NULL,
  `room_type` varchar(255) NOT NULL,
  `capacity` int(10) unsigned NOT NULL,
  `num_seniors` int(10) unsigned NOT NULL DEFAULT 0,
  `num_guests` int(10) unsigned DEFAULT NULL,
  `meal` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meal`)),
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reservations_booking_id_foreign` (`booking_id`),
  CONSTRAINT `reservations_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES (1,1,'112','double',2,0,2,NULL,1800.00,'2026-04-14 01:43:43','2026-04-14 01:43:43'),(2,2,'107','triple',3,0,3,NULL,2400.00,'2026-04-14 01:53:09','2026-04-14 01:53:09'),(3,3,'105','deluxe',2,1,1,'{\"bangsilog\":\"0\",\"tocilog\":\"0\",\"hotsilog\":\"0\",\"spamsilog\":\"0\",\"tapsilog\":\"1\"}',2500.00,'2026-04-14 01:56:16','2026-04-14 01:56:16'),(4,4,'106','deluxe',2,0,2,NULL,3000.00,'2026-04-14 02:12:05','2026-04-14 02:12:05'),(5,5,'203','dormitory2',6,0,6,NULL,3000.00,'2026-04-14 02:15:14','2026-04-14 02:15:14'),(6,5,'204','dormitory2',6,0,6,NULL,3000.00,'2026-04-14 02:15:14','2026-04-14 02:15:14'),(7,5,'214','dormitory2',6,0,6,NULL,3000.00,'2026-04-14 02:15:14','2026-04-14 02:15:14'),(8,5,'215','dormitory1',5,0,5,NULL,2500.00,'2026-04-14 02:15:14','2026-04-14 02:15:14'),(9,5,'216','quadruple',4,0,4,NULL,2800.00,'2026-04-14 02:15:14','2026-04-14 02:15:14'),(10,5,'212','triple',3,0,3,NULL,2400.00,'2026-04-14 02:15:14','2026-04-14 02:15:14'),(11,6,'108','triple',3,0,3,NULL,2400.00,'2026-04-15 06:30:21','2026-04-15 06:30:21'),(12,7,'110','double',2,0,2,NULL,1800.00,'2026-04-15 06:31:14','2026-04-15 06:31:14'),(13,8,'202','quadruple',4,0,4,NULL,2800.00,'2026-04-15 06:32:33','2026-04-15 06:32:33'),(14,9,'112','double',2,0,2,'{\"bangsilog\":\"2\",\"tocilog\":\"0\",\"hotsilog\":\"0\",\"spamsilog\":\"0\",\"tapsilog\":\"0\"}',1600.00,'2026-06-30 16:50:52','2026-06-30 16:50:52'),(15,10,'101','deluxe',2,0,1,NULL,3000.00,'2026-07-01 00:34:50','2026-07-01 00:34:50');
/*!40000 ALTER TABLE `reservations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rooms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `room_number` varchar(50) NOT NULL,
  `room_type` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('available','occupied','maintenance','cleaning') NOT NULL DEFAULT 'available',
  `wing` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_edited_by` bigint(20) unsigned DEFAULT NULL COMMENT 'Staff ID of last editor',
  PRIMARY KEY (`id`),
  KEY `rooms_last_edited_by_foreign` (`last_edited_by`),
  KEY `idx_rooms_number` (`room_number`),
  CONSTRAINT `rooms_last_edited_by_foreign` FOREIGN KEY (`last_edited_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES (1,'101','deluxe',3000.00,'available','rooster','2025-09-11 08:22:16','2026-04-13 10:33:31',NULL),(2,'102','deluxe',3000.00,'available','rooster','2025-09-11 09:58:20','2025-09-11 09:58:20',NULL),(3,'103','deluxe',3000.00,'available','rooster','2025-09-11 09:58:45','2025-10-10 09:57:46',NULL),(4,'104','deluxe',3000.00,'available','tumana','2025-09-11 09:59:17','2025-10-09 01:19:01',NULL),(5,'105','deluxe',3000.00,'available','tumana','2025-09-11 10:00:18','2025-09-11 10:00:18',NULL),(6,'106','deluxe',3000.00,'available','tumana','2025-09-11 10:00:31','2026-04-18 05:54:27',NULL),(7,'107','triple',2400.00,'available','tumana','2025-09-11 10:00:49','2026-04-17 04:34:20',NULL),(8,'108','triple',2400.00,'available','tumana','2025-09-11 10:03:57','2026-04-19 04:18:57',NULL),(9,'109','deluxe',3000.00,'available','tumana','2025-09-11 10:12:44','2025-09-11 10:12:44',NULL),(10,'110','double',1800.00,'available','rooster','2025-09-11 10:12:58','2026-04-13 14:50:36',NULL),(11,'112','double',1800.00,'available','rooster','2025-09-11 10:13:29','2026-04-14 01:50:18',NULL),(12,'202','quadruple',2800.00,'available','chev_re','2025-09-11 10:13:43','2025-10-10 09:43:23',NULL),(13,'203','dormitory2',3000.00,'available','chev_re','2025-09-11 10:14:22','2025-09-11 10:14:22',NULL),(14,'204','dormitory2',3000.00,'available','tumana','2025-09-11 10:14:29','2025-10-05 10:02:40',1),(15,'208','triple',2400.00,'available','torii','2025-09-11 10:15:17','2026-02-22 22:35:53',NULL),(16,'210','triple',2400.00,'available','torii','2025-09-11 10:15:25','2025-10-10 09:37:45',NULL),(17,'211','triple',2400.00,'available','torii','2025-09-11 10:15:32','2025-09-11 10:15:32',NULL),(18,'212','triple',2400.00,'available','torii','2025-09-11 10:15:44','2026-02-21 23:10:09',NULL),(19,'214','dormitory2',3000.00,'available','chev_re','2025-09-11 10:16:05','2025-09-11 10:16:05',NULL),(20,'215','dormitory1',2500.00,'available','chev_re','2025-09-11 10:16:13','2025-09-11 10:16:13',NULL),(21,'216','quadruple',2800.00,'available','torii','2025-09-11 10:16:25','2026-02-22 22:36:00',NULL),(22,'217','double',1800.00,'available','tumana','2025-09-11 10:16:43','2025-10-12 03:47:23',1);
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('A031U816EzruFuZEpG2j5Jfi2J3nK3Lir0UyhtxF',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.126.0 Chrome/148.0.7778.97 Electron/42.2.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiTm9OWkdLcWhxc0ZneEZPbTdwT3dTZ3BydzN0anRlejJ4cWROWWdzSCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1783126656),('JfN8dERhIICGHa1Y5DQiVrL1L01ZzHv9dplrD9p0',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoicktyRENzTTFDcDdNblhQWk1qNlhydFl4MHAxTUJMMFpwT1ZkUnlrWiI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozNzoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL3N0YWZmL2Rhc2hib2FyZCI7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjMzOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvc3RhZmYvcm9vbXMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUyOiJsb2dpbl9zdGFmZl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==',1783121811),('wSWKGKJFZVZGUMQqNduFQfrZ8HLROSi8pGE511Xo',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiMmI1d1pCbGdBRkd6dkM2eHM1Q1Y1dUxYeUJCWWVTRnk5S01uaFp3dCI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozNzoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL3N0YWZmL2Rhc2hib2FyZCI7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjI3OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1783121658);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp when staff email was verified',
  `password` varchar(255) NOT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `role` enum('master_admin','admin','frontdesk','housekeeping') NOT NULL DEFAULT 'frontdesk',
  `is_suspended` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indicates if staff account is suspended',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` VALUES (1,'Mike Francis Vengazo','mikefrancisvengazo@gmail.com','2025-10-12 09:47:10','$2y$12$qyKBwqQdS6xf3QVuiL5Ki.NOQ0t4XUpR6MCTkSnd8aKytfr4ZzbCq','2026-07-03 15:34:38','master_admin',0,NULL,'2025-08-30 20:07:25','2026-07-03 15:34:38'),(3,'frontDesk1','eggpot2@gmail.com',NULL,'$2y$12$qmcgjQLEOwKKX8ugD5TMIOW3BoRHdXfBk.dEtDX7Uyu8v/Fw3YeMW','2026-04-13 10:52:58','frontdesk',0,NULL,'2025-10-12 02:15:10','2026-04-13 10:52:58'),(5,'Xciamiah Abad','abadxciamiah@gmail.com',NULL,'$2y$12$C8l/HHYUTCgtrn7Z64PgpONwATDxtD9a3MFBJothZH5QXoKCMNgJ2','2026-04-22 05:37:48','master_admin',0,NULL,'2026-02-23 12:33:40','2026-04-22 05:37:48'),(6,'Hanz Busine','busine.hanzlionel@clsu2.edu.ph',NULL,'$2y$12$qmcgjQLEOwKKX8ugD5TMIOW3BoRHdXfBk.dEtDX7Uyu8v/Fw3YeMW','2026-02-23 12:41:00','admin',0,NULL,'2026-02-23 12:36:31','2026-02-23 12:41:00'),(7,'Mark Louie Cacho','marklouieccacho@gmail.com',NULL,'$2y$12$fzvzsnAlAheNp8641.8bquM31yeflw/72MBe3VjoRomJdDqQ5pIRK','2026-04-14 01:29:20','frontdesk',0,NULL,'2026-02-23 12:39:46','2026-04-14 01:29:20'),(8,'Mac Cacho','macyatomac@gmail.com',NULL,'$2y$12$WvKp7DJvlHE0Z.Wpcf91EOjVHDiXYVVsT/Q5wbVyk9CGT3q9Nn05u','2026-03-26 02:09:31','admin',1,NULL,'2026-03-23 04:02:13','2026-06-30 22:04:18'),(9,'maclccacho','maclccacho@gmail.com',NULL,'$2y$12$zvgF5MzBbyfwvFYvBf7Mguu3DCI5/165JmliZlzAQ7vYg.5BTpq6a','2026-04-15 03:57:50','master_admin',0,NULL,'2026-03-26 02:04:05','2026-04-15 03:57:50'),(10,'FhEmployee1','zyrashane11@gmail.com',NULL,'$2y$12$W0GfHoCdu6vYC4tRhqdbW.8BaaocWDTyaIi5ooao0fwLgZFqAvCCu',NULL,'master_admin',0,NULL,'2026-04-13 10:07:24','2026-04-13 10:07:24'),(11,'Artemio D. Mangaoang Jr.','foartemiomangaoang01@gmail.com',NULL,'$2y$12$MlqKl7BhA8XpCkwxCFcuN.FprZgXfO/UDQY9dPYnSyzxFbroWcSm.','2026-04-15 06:29:30','master_admin',0,NULL,'2026-04-14 01:34:33','2026-04-15 06:29:30'),(12,'Xciamiah Gail Abad','maxciamiah.abad@clsu2.edu.ph',NULL,'$2y$12$mJ6jOkbz/CsacEwxcxq1Fu1jkkgVrl.AteCiKpzbrb0/TZQMaV1MK','2026-04-19 11:54:35','frontdesk',0,NULL,'2026-04-19 11:53:09','2026-04-19 11:54:35'),(13,'Temp Admin','admin_temp@example.com',NULL,'$2y$12$jiTSvwLzFRn8CYrb8FGxqumVbfZEue32m2DseMSGObkfHC32Mxc8i','2026-07-01 22:38:50','master_admin',0,NULL,'2026-07-01 22:37:15','2026-07-01 22:38:50');
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_otps`
--

DROP TABLE IF EXISTS `staff_otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_otps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `otp_code` varchar(255) NOT NULL,
  `otp_expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_otps_staff_id_foreign` (`staff_id`),
  CONSTRAINT `staff_otps_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=122 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_otps`
--

LOCK TABLES `staff_otps` WRITE;
/*!40000 ALTER TABLE `staff_otps` DISABLE KEYS */;
INSERT INTO `staff_otps` VALUES (1,1,'266491','2026-02-23 12:24:12','2026-02-23 12:24:12','2026-02-23 12:23:51','2026-02-23 12:24:12'),(2,1,'627149','2026-02-23 12:37:45','2026-02-23 12:37:45','2026-02-23 12:37:28','2026-02-23 12:37:45'),(3,6,'143550','2026-02-23 12:41:00','2026-02-23 12:41:00','2026-02-23 12:40:50','2026-02-23 12:41:00'),(4,1,'712965','2026-02-23 12:58:27','2026-02-23 12:58:27','2026-02-23 12:58:15','2026-02-23 12:58:27'),(5,1,'544343','2026-02-24 10:19:14','2026-02-24 10:19:14','2026-02-24 10:18:56','2026-02-24 10:19:14'),(6,3,'526891','2026-02-25 09:57:14','2026-02-25 09:57:14','2026-02-25 09:57:02','2026-02-25 09:57:14'),(7,1,'256508','2026-02-25 16:36:22','2026-02-25 16:36:22','2026-02-25 16:36:04','2026-02-25 16:36:22'),(8,1,'897093','2026-02-26 03:09:18',NULL,'2026-02-26 03:04:18','2026-02-26 03:04:18'),(9,1,'427281','2026-02-26 03:15:45','2026-02-26 03:15:45','2026-02-26 03:15:09','2026-02-26 03:15:45'),(10,1,'459520','2026-02-26 03:28:13',NULL,'2026-02-26 03:23:13','2026-02-26 03:23:13'),(11,1,'609597','2026-02-26 03:45:04','2026-02-26 03:45:04','2026-02-26 03:44:19','2026-02-26 03:45:04'),(12,1,'897748','2026-02-26 04:11:14',NULL,'2026-02-26 04:06:14','2026-02-26 04:06:14'),(13,1,'947619','2026-02-26 13:16:25','2026-02-26 13:16:25','2026-02-26 13:13:38','2026-02-26 13:16:25'),(14,1,'211892','2026-03-09 01:45:29','2026-03-09 01:45:29','2026-03-09 01:45:05','2026-03-09 01:45:29'),(15,3,'247440','2026-03-09 02:48:11',NULL,'2026-03-09 02:43:11','2026-03-09 02:43:11'),(16,1,'522449','2026-03-19 02:16:10','2026-03-19 02:16:10','2026-03-19 02:15:48','2026-03-19 02:16:10'),(17,5,'314033','2026-03-23 03:45:22','2026-03-23 03:45:22','2026-03-23 03:44:27','2026-03-23 03:45:22'),(18,7,'203534','2026-03-23 06:49:37',NULL,'2026-03-23 06:44:37','2026-03-23 06:44:37'),(19,7,'380264','2026-03-23 06:49:19','2026-03-23 06:49:19','2026-03-23 06:47:41','2026-03-23 06:49:19'),(20,7,'427546','2026-03-23 06:54:46',NULL,'2026-03-23 06:49:46','2026-03-23 06:49:46'),(21,7,'816024','2026-03-23 07:00:04',NULL,'2026-03-23 06:55:04','2026-03-23 06:55:04'),(22,5,'459154','2026-03-23 06:59:16','2026-03-23 06:59:16','2026-03-23 06:58:19','2026-03-23 06:59:16'),(23,5,'155743','2026-03-23 07:10:31','2026-03-23 07:10:31','2026-03-23 07:10:11','2026-03-23 07:10:31'),(24,7,'473686','2026-03-23 23:45:49','2026-03-23 23:45:49','2026-03-23 23:45:25','2026-03-23 23:45:49'),(25,7,'481950','2026-03-23 23:47:21','2026-03-23 23:47:21','2026-03-23 23:46:02','2026-03-23 23:47:21'),(26,5,'677329','2026-03-24 00:02:43','2026-03-24 00:02:43','2026-03-24 00:02:11','2026-03-24 00:02:43'),(27,7,'451148','2026-03-24 03:36:40','2026-03-24 03:36:40','2026-03-24 03:36:22','2026-03-24 03:36:40'),(28,5,'940619','2026-03-24 03:41:31','2026-03-24 03:41:31','2026-03-24 03:41:11','2026-03-24 03:41:31'),(29,7,'916891','2026-03-24 03:42:56','2026-03-24 03:42:56','2026-03-24 03:42:33','2026-03-24 03:42:56'),(30,1,'244725','2026-03-24 10:26:48',NULL,'2026-03-24 10:21:48','2026-03-24 10:21:48'),(31,1,'220140','2026-03-24 10:32:36','2026-03-24 10:32:36','2026-03-24 10:32:13','2026-03-24 10:32:36'),(32,1,'338821','2026-03-24 10:37:18',NULL,'2026-03-24 10:32:18','2026-03-24 10:32:18'),(33,7,'747300','2026-03-24 23:39:20','2026-03-24 23:39:20','2026-03-24 23:38:53','2026-03-24 23:39:20'),(34,5,'659459','2026-03-25 00:38:59',NULL,'2026-03-25 00:33:59','2026-03-25 00:33:59'),(35,7,'206065','2026-03-25 06:07:34','2026-03-25 06:07:34','2026-03-25 06:06:39','2026-03-25 06:07:34'),(36,7,'832917','2026-03-25 07:31:50','2026-03-25 07:31:50','2026-03-25 07:30:54','2026-03-25 07:31:50'),(37,1,'326532','2026-03-25 07:50:59',NULL,'2026-03-25 07:45:59','2026-03-25 07:45:59'),(38,5,'313333','2026-03-25 08:01:10','2026-03-25 08:01:10','2026-03-25 08:00:34','2026-03-25 08:01:10'),(39,5,'814419','2026-03-25 23:40:15','2026-03-25 23:40:15','2026-03-25 23:38:54','2026-03-25 23:40:15'),(40,1,'135789','2026-03-25 23:46:02',NULL,'2026-03-25 23:41:02','2026-03-25 23:41:02'),(41,7,'962368','2026-03-25 23:42:41','2026-03-25 23:42:41','2026-03-25 23:42:24','2026-03-25 23:42:41'),(42,5,'999930','2026-03-26 01:53:32','2026-03-26 01:53:32','2026-03-26 01:53:16','2026-03-26 01:53:32'),(43,5,'967272','2026-03-26 01:57:48','2026-03-26 01:57:48','2026-03-26 01:57:31','2026-03-26 01:57:48'),(44,1,'762182','2026-03-26 02:03:21','2026-03-26 02:03:21','2026-03-26 02:03:05','2026-03-26 02:03:21'),(45,9,'540924','2026-03-26 02:05:23','2026-03-26 02:05:23','2026-03-26 02:04:49','2026-03-26 02:05:23'),(46,8,'452515','2026-03-26 02:09:31','2026-03-26 02:09:31','2026-03-26 02:08:34','2026-03-26 02:09:31'),(47,7,'634979','2026-03-26 02:15:24','2026-03-26 02:15:24','2026-03-26 02:14:44','2026-03-26 02:15:24'),(48,9,'543059','2026-03-26 03:42:11','2026-03-26 03:42:11','2026-03-26 03:41:34','2026-03-26 03:42:11'),(49,5,'669192','2026-03-26 06:00:03','2026-03-26 06:00:03','2026-03-26 05:59:12','2026-03-26 06:00:03'),(50,7,'120185','2026-03-26 06:10:02',NULL,'2026-03-26 06:05:02','2026-03-26 06:05:02'),(51,7,'947177','2026-03-26 06:06:01','2026-03-26 06:06:01','2026-03-26 06:05:06','2026-03-26 06:06:01'),(52,1,'784430','2026-03-26 08:26:55','2026-03-26 08:26:55','2026-03-26 08:26:36','2026-03-26 08:26:55'),(53,9,'237205','2026-03-27 02:40:27','2026-03-27 02:40:27','2026-03-27 02:39:23','2026-03-27 02:40:27'),(54,9,'575266','2026-03-28 12:22:32','2026-03-28 12:22:32','2026-03-28 12:22:14','2026-03-28 12:22:32'),(55,7,'665941','2026-03-28 12:39:07','2026-03-28 12:39:07','2026-03-28 12:38:36','2026-03-28 12:39:07'),(56,1,'412688','2026-03-29 08:47:59','2026-03-29 08:47:59','2026-03-29 08:47:29','2026-03-29 08:47:59'),(57,1,'971999','2026-03-29 09:24:21','2026-03-29 09:24:21','2026-03-29 09:24:01','2026-03-29 09:24:21'),(58,1,'357526','2026-03-29 09:37:05','2026-03-29 09:37:05','2026-03-29 09:36:20','2026-03-29 09:37:05'),(59,3,'782316','2026-03-29 10:17:10','2026-03-29 10:17:10','2026-03-29 10:16:48','2026-03-29 10:17:10'),(60,9,'706346','2026-03-30 05:40:53',NULL,'2026-03-30 05:35:53','2026-03-30 05:35:53'),(61,9,'458799','2026-04-05 12:24:15','2026-04-05 12:24:15','2026-04-05 12:23:46','2026-04-05 12:24:15'),(62,7,'836781','2026-04-05 12:30:26','2026-04-05 12:30:26','2026-04-05 12:30:08','2026-04-05 12:30:26'),(63,9,'153828','2026-04-05 12:31:39','2026-04-05 12:31:39','2026-04-05 12:31:18','2026-04-05 12:31:39'),(64,9,'719166','2026-04-13 01:15:14','2026-04-13 01:15:14','2026-04-13 01:14:19','2026-04-13 01:15:14'),(65,3,'463532','2026-04-13 01:23:44','2026-04-13 01:23:44','2026-04-13 01:23:17','2026-04-13 01:23:44'),(66,3,'792425','2026-04-13 01:26:05','2026-04-13 01:26:05','2026-04-13 01:25:24','2026-04-13 01:26:05'),(67,1,'175104','2026-04-13 01:31:56','2026-04-13 01:31:56','2026-04-13 01:31:14','2026-04-13 01:31:56'),(68,1,'583525','2026-04-13 09:34:11','2026-04-13 09:34:11','2026-04-13 09:33:49','2026-04-13 09:34:11'),(69,1,'387032','2026-04-13 09:41:53','2026-04-13 09:41:53','2026-04-13 09:41:32','2026-04-13 09:41:53'),(70,3,'950584','2026-04-13 10:16:28','2026-04-13 10:16:28','2026-04-13 10:15:43','2026-04-13 10:16:28'),(71,3,'136349','2026-04-13 10:20:14','2026-04-13 10:20:14','2026-04-13 10:20:02','2026-04-13 10:20:14'),(72,1,'351841','2026-04-13 10:27:31','2026-04-13 10:27:31','2026-04-13 10:27:12','2026-04-13 10:27:31'),(73,1,'358947','2026-04-13 10:44:57','2026-04-13 10:44:57','2026-04-13 10:44:42','2026-04-13 10:44:57'),(74,3,'137334','2026-04-13 10:46:00','2026-04-13 10:46:00','2026-04-13 10:45:25','2026-04-13 10:46:00'),(75,3,'272057','2026-04-13 10:52:58','2026-04-13 10:52:58','2026-04-13 10:52:45','2026-04-13 10:52:58'),(76,1,'878443','2026-04-13 14:17:40','2026-04-13 14:17:40','2026-04-13 14:17:06','2026-04-13 14:17:40'),(77,9,'409547','2026-04-13 20:45:07','2026-04-13 20:45:07','2026-04-13 20:44:26','2026-04-13 20:45:07'),(78,9,'918951','2026-04-13 20:48:24','2026-04-13 20:48:24','2026-04-13 20:46:56','2026-04-13 20:48:24'),(79,9,'525062','2026-04-14 01:28:36','2026-04-14 01:28:36','2026-04-14 01:28:03','2026-04-14 01:28:36'),(80,9,'754398','2026-04-14 01:33:14',NULL,'2026-04-14 01:28:14','2026-04-14 01:28:14'),(81,7,'925394','2026-04-14 01:29:20','2026-04-14 01:29:20','2026-04-14 01:29:02','2026-04-14 01:29:20'),(82,9,'238648','2026-04-14 01:30:19','2026-04-14 01:30:19','2026-04-14 01:30:00','2026-04-14 01:30:19'),(83,11,'832324','2026-04-14 01:39:06','2026-04-14 01:39:06','2026-04-14 01:38:42','2026-04-14 01:39:06'),(84,9,'221712','2026-04-14 01:40:31','2026-04-14 01:40:31','2026-04-14 01:39:07','2026-04-14 01:40:31'),(85,9,'966125','2026-04-14 01:44:10',NULL,'2026-04-14 01:39:10','2026-04-14 01:39:10'),(86,11,'564522','2026-04-14 02:10:03','2026-04-14 02:10:03','2026-04-14 02:08:25','2026-04-14 02:10:03'),(87,11,'316563','2026-04-14 02:13:29',NULL,'2026-04-14 02:08:29','2026-04-14 02:08:29'),(88,9,'774258','2026-04-15 03:57:50','2026-04-15 03:57:50','2026-04-15 03:55:23','2026-04-15 03:57:50'),(89,9,'291951','2026-04-15 04:00:26',NULL,'2026-04-15 03:55:26','2026-04-15 03:55:26'),(90,11,'537948','2026-04-15 06:29:30','2026-04-15 06:29:30','2026-04-15 06:29:12','2026-04-15 06:29:30'),(91,11,'924299','2026-04-15 06:34:13',NULL,'2026-04-15 06:29:13','2026-04-15 06:29:13'),(92,1,'831257','2026-04-15 10:36:06',NULL,'2026-04-15 10:31:06','2026-04-15 10:31:06'),(93,1,'624838','2026-04-15 15:30:26','2026-04-15 15:30:26','2026-04-15 15:29:36','2026-04-15 15:30:26'),(94,1,'910931','2026-04-15 15:51:10','2026-04-15 15:51:10','2026-04-15 15:50:52','2026-04-15 15:51:10'),(95,1,'732533','2026-04-15 15:51:44','2026-04-15 15:51:44','2026-04-15 15:51:26','2026-04-15 15:51:44'),(96,1,'114396','2026-04-16 04:00:22','2026-04-16 04:00:22','2026-04-16 04:00:09','2026-04-16 04:00:22'),(97,1,'929778','2026-04-17 04:34:11','2026-04-17 04:34:11','2026-04-17 04:33:56','2026-04-17 04:34:11'),(98,1,'890286','2026-04-18 05:54:21','2026-04-18 05:54:21','2026-04-18 05:53:52','2026-04-18 05:54:21'),(99,1,'890355','2026-04-19 04:18:48','2026-04-19 04:18:48','2026-04-19 04:18:29','2026-04-19 04:18:48'),(100,5,'303656','2026-04-19 11:33:42','2026-04-19 11:33:42','2026-04-19 11:33:24','2026-04-19 11:33:42'),(101,12,'961758','2026-04-19 11:54:35','2026-04-19 11:54:35','2026-04-19 11:54:00','2026-04-19 11:54:35'),(102,1,'238224','2026-04-20 01:27:56','2026-04-20 01:27:56','2026-04-20 01:27:36','2026-04-20 01:27:56'),(103,5,'868226','2026-04-22 05:37:48','2026-04-22 05:37:48','2026-04-22 05:37:31','2026-04-22 05:37:48'),(104,5,'574316','2026-04-22 05:42:32',NULL,'2026-04-22 05:37:32','2026-04-22 05:37:32'),(105,1,'764717','2026-07-01 01:01:27','2026-06-30 17:01:27','2026-06-30 17:00:40','2026-06-30 17:01:27'),(106,1,'121740','2026-07-01 01:02:24','2026-06-30 17:02:24','2026-06-30 17:01:49','2026-06-30 17:02:24'),(107,1,'154648','2026-07-01 01:08:21','2026-06-30 17:08:21','2026-06-30 17:08:03','2026-06-30 17:08:21'),(108,1,'382895','2026-07-01 04:39:05','2026-06-30 20:39:05','2026-06-30 20:38:08','2026-06-30 20:39:05'),(109,1,'634368','2026-06-30 22:02:48',NULL,'2026-06-30 21:57:48','2026-06-30 21:57:48'),(110,1,'741568','2026-07-01 06:00:23','2026-06-30 22:00:23','2026-06-30 21:59:47','2026-06-30 22:00:23'),(111,1,'562532','2026-07-01 06:01:16','2026-06-30 22:01:16','2026-06-30 22:00:50','2026-06-30 22:01:16'),(112,1,'727850','2026-07-01 08:16:20','2026-07-01 00:16:20','2026-07-01 00:15:32','2026-07-01 00:16:20'),(113,1,'760217','2026-07-01 13:12:52','2026-07-01 05:12:52','2026-07-01 05:12:03','2026-07-01 05:12:52'),(114,1,'778787','2026-07-02 05:05:24','2026-07-01 21:05:24','2026-07-01 21:04:56','2026-07-01 21:05:24'),(115,13,'149731','2026-07-02 06:38:50','2026-07-01 22:38:50','2026-07-01 22:38:09','2026-07-01 22:38:50'),(116,1,'760577','2026-07-03 02:30:13','2026-07-02 18:30:13','2026-07-02 18:29:29','2026-07-02 18:30:13'),(117,1,'577541','2026-07-03 07:01:17','2026-07-02 23:01:17','2026-07-02 23:00:46','2026-07-02 23:01:17'),(118,1,'452253','2026-07-03 07:05:10','2026-07-02 23:05:10','2026-07-02 23:04:46','2026-07-02 23:05:10'),(119,1,'691713','2026-07-03 07:14:47','2026-07-02 23:14:47','2026-07-02 23:14:13','2026-07-02 23:14:47'),(120,1,'814426','2026-07-03 15:38:31',NULL,'2026-07-03 15:33:31','2026-07-03 15:33:31'),(121,1,'560110','2026-07-03 23:34:37','2026-07-03 15:34:37','2026-07-03 15:34:11','2026-07-03 15:34:37');
/*!40000 ALTER TABLE `staff_otps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `is_suspended` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_cancelled_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'hanz','eggpot2@gmail.com','09652473385','2025-09-11 08:14:50','$2y$12$Dl3X.aU8OjbBfB4.nEr3N.f/uT2F4NtjryiPDD/np7WrFdH/creO6','mSG43FS5gbJoacpwOYNxGy0cfE0Bue0nnin79KLS4ixqQGPiRwFZiK0eO8Dk','2026-02-23 13:12:32',0,'2025-09-11 08:14:11','2026-02-23 13:12:32','2025-09-15 05:37:55'),(2,'Hanzkie','businehanz@gmail.com','09652473385','2025-09-17 00:50:23','$2y$12$c.hpKWyjcuswRpuyLYoHyunnVcUUBa0lQSP7wWZtsRCBDgcFicspy','YtocGyqhXY6tpX9hZTwPVaFXhuGEIwRHApgjWIDkexZOmY0OQFyT3I3BP9rp','2026-04-20 01:31:40',0,'2025-09-17 00:49:51','2026-04-20 01:31:40','2025-10-12 08:16:43'),(3,'Rosalinda','busine.hanzlionel@clsu2.edu.ph',NULL,'2025-10-12 01:31:53','$2y$12$AW3Yyc4QgJZlWq7DzgfTBeYOCky7khkoIss/eAZPzKFKjjhbhTAD2',NULL,'2025-10-12 01:44:32',0,'2025-10-12 01:30:27','2025-10-12 01:44:32',NULL),(4,'User','abadxciamiah@gmail.com',NULL,'2026-03-19 02:38:25','$2y$12$3YYKdfozfjQd87HRNpb4d.sH1u.0dMfUU/dOhAs33aL9yZCzIEHFK','vCVZWeXvoR2nc4bqbFsuyPcE3277ghBFm6QaGf3raRa5CagRHnF8MJNcEaAv','2026-04-19 11:20:47',0,'2026-03-19 02:36:43','2026-04-19 11:20:47',NULL),(5,'lia','maxciamiah.abad@clsu.edu.ph',NULL,NULL,'$2y$12$PD7KUXTqmP/VizjdAbVdTOt0HIU59AGsP405vpM4jJ/s58lOVBfaW',NULL,NULL,0,'2026-03-23 07:13:46','2026-03-23 07:13:46',NULL),(6,'macco','maclccacho@gmail.com',NULL,'2026-03-24 03:53:28','$2y$12$lUkkgeg3qbSL11Uc39AFp.3afBQdj6uTIwL1UmpLqL071u9cuK93K',NULL,'2026-04-14 05:40:31',0,'2026-03-24 03:51:21','2026-04-14 05:40:31','2026-03-26 03:29:46'),(7,'johndoe','john.doe@gmail.com',NULL,NULL,'$2y$12$uuSnFtKCo9cnVDl5HBJO4eONDCQ8QHiS1FRZbV1BT2f4DDnAOF.z6',NULL,'2026-03-28 12:37:09',0,'2026-03-28 12:36:54','2026-03-28 12:37:09',NULL),(8,'seph1roth','seph.grospe@gmail.com',NULL,'2026-03-28 16:47:48','$2y$12$QLwABVJM9aGV8jn0zSEjKOGBuITcmX84uIOjT0dKmZNWIGcKeJRJK',NULL,'2026-03-28 16:47:48',0,'2026-03-28 16:45:48','2026-03-28 16:47:48',NULL),(9,'aaronbc','mit.aaroncacho@gmail.com',NULL,'2026-04-01 08:09:40','$2y$12$5Bz3q2rtjgwVhnsssDImKOrIo.7KvDJpppyb0prhMLKxPDCG6S4Qe',NULL,'2026-04-01 08:07:44',0,'2026-04-01 08:07:27','2026-04-01 08:09:40',NULL),(10,'casey','llenacasey08@gmail.com','09123456789','2026-04-01 12:02:24','$2y$12$TBqmEclaieRIc0EYkOzMxutX6b04JP/3LC/c52/4fSpG6j8hbGEG.',NULL,'2026-04-01 12:01:29',0,'2026-04-01 12:00:26','2026-04-01 12:04:35',NULL),(11,'artemio01','foartemiomangaoang01@gmail.com',NULL,'2026-04-14 01:36:19','$2y$12$tcXYHl05B9wAlqmzPQjryOdFxlCN.l.bW6aOwk98KYH8d2ErgVuQ2',NULL,'2026-04-14 01:54:09',0,'2026-04-14 01:34:30','2026-04-14 01:54:09',NULL),(12,'juan','pransesadd1@gmail.com',NULL,'2026-06-30 16:38:35','$2y$12$O4UhxTzq8gVZiRovdnAPUettL7XivBwXkLJR0r5aIFRO2bXPVj/YS',NULL,'2026-07-01 18:44:25',0,'2026-06-30 16:36:27','2026-07-01 18:44:25',NULL),(13,'jdoe','jdoe@clsu.edu.ph',NULL,NULL,'$2y$12$NGT.GnhrdNJuajGLBLEGuuAcb4rvA8Oz.3t32pY6IeBQ9G8YZ2CLm',NULL,'2026-07-01 17:26:20',0,'2026-07-01 17:26:04','2026-07-01 17:26:20',NULL),(14,'testuser','testuser@example.com',NULL,'2026-07-01 18:12:46','$2y$12$haUzpLXJzVt8DUkszaiQa.I3BPP/NzKK3cez61lU5rm4fRT9JCVD2',NULL,'2026-07-01 18:05:56',0,'2026-07-01 18:05:33','2026-07-01 18:12:46',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-04  9:48:37
