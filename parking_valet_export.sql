mysqldump: [Warning] Using a password on the command line interface can be insecure.
-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: parking_valet
-- ------------------------------------------------------
-- Server version	8.0.42

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `feedbacks`
--

DROP TABLE IF EXISTS `feedbacks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedbacks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` enum('general','bug','feature','parking') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` int DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issues` json DEFAULT NULL,
  `device_info` json DEFAULT NULL,
  `status` enum('pending','reviewed','resolved') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_response` text COLLATE utf8mb4_unicode_ci,
  `admin_id` bigint unsigned DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `feedbacks_user_id_index` (`user_id`),
  KEY `feedbacks_status_index` (`status`),
  KEY `feedbacks_type_index` (`type`),
  KEY `feedbacks_admin_id_foreign` (`admin_id`),
  CONSTRAINT `feedbacks_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `sys_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `feedbacks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedbacks`
--

LOCK TABLES `feedbacks` WRITE;
/*!40000 ALTER TABLE `feedbacks` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedbacks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `floor_layouts`
--

DROP TABLE IF EXISTS `floor_layouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `floor_layouts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `floor_level` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `layout_json` text COLLATE utf8mb4_unicode_ci,
  `background_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `canvas_width` int NOT NULL DEFAULT '1000',
  `canvas_height` int NOT NULL DEFAULT '600',
  `version` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `floor_layouts_floor_level_unique` (`floor_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `floor_layouts`
--

LOCK TABLES `floor_layouts` WRITE;
/*!40000 ALTER TABLE `floor_layouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `floor_layouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (15,'2025_12_05_143019_add_layout_fields_to_parking_spaces_table',2),(16,'2025_12_05_143211_create_floor_layouts_table',2),(18,'0001_01_01_000000_create_users_table',3),(19,'2025_05_14_044951_create_sys_users_table',3),(20,'2025_06_27_160516_create_personal_access_tokens_table',3),(21,'2025_08_05_130812_create_feedbacks_table',3),(22,'2025_08_05_130812_create_parking_spaces_table',3),(23,'2025_08_05_130813_create_vehicles_table',3),(24,'2025_08_05_131021_create_pending_accounts_table',3),(25,'2025_12_10_152310_add_missing_columns_to_parking_spaces_table',3),(26,'2025_12_10_162810_fix_parking_spaces_table_structure',3),(27,'2025_12_12_030329_add_space_code_to_parking_spaces_table',3),(28,'2025_12_12_030341_create_parking_configuration_tables',3);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parking_columns`
--

DROP TABLE IF EXISTS `parking_columns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parking_columns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `floor_id` bigint unsigned NOT NULL,
  `column_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `column_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parking_columns_floor_id_column_code_unique` (`floor_id`,`column_code`),
  KEY `parking_columns_floor_id_is_active_index` (`floor_id`,`is_active`),
  CONSTRAINT `parking_columns_floor_id_foreign` FOREIGN KEY (`floor_id`) REFERENCES `parking_floors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parking_columns`
--

LOCK TABLES `parking_columns` WRITE;
/*!40000 ALTER TABLE `parking_columns` DISABLE KEYS */;
/*!40000 ALTER TABLE `parking_columns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parking_config`
--

DROP TABLE IF EXISTS `parking_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parking_config` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parking_config_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parking_config`
--

LOCK TABLES `parking_config` WRITE;
/*!40000 ALTER TABLE `parking_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `parking_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parking_floors`
--

DROP TABLE IF EXISTS `parking_floors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parking_floors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `floor_number` int NOT NULL,
  `floor_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parking_floors_floor_number_unique` (`floor_number`),
  KEY `parking_floors_floor_number_index` (`floor_number`),
  KEY `parking_floors_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parking_floors`
--

LOCK TABLES `parking_floors` WRITE;
/*!40000 ALTER TABLE `parking_floors` DISABLE KEYS */;
/*!40000 ALTER TABLE `parking_floors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parking_spaces`
--

DROP TABLE IF EXISTS `parking_spaces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parking_spaces` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sensor_id` int NOT NULL,
  `space_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `floor_number` int DEFAULT NULL,
  `column_code` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slot_number` int DEFAULT NULL,
  `slot_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `section` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_occupied` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `distance_cm` int DEFAULT NULL,
  `floor_level` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '4th Floor',
  `x_position` decimal(8,2) DEFAULT NULL,
  `y_position` decimal(8,2) DEFAULT NULL,
  `rotation` int NOT NULL DEFAULT '0',
  `width` decimal(8,2) NOT NULL DEFAULT '60.00',
  `height` decimal(8,2) NOT NULL DEFAULT '85.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parking_spaces_sensor_id_unique` (`sensor_id`),
  UNIQUE KEY `parking_spaces_space_code_unique` (`space_code`),
  KEY `parking_spaces_sensor_id_index` (`sensor_id`),
  KEY `parking_spaces_is_occupied_index` (`is_occupied`),
  KEY `parking_spaces_floor_level_index` (`floor_level`),
  KEY `parking_spaces_space_code_index` (`space_code`),
  KEY `parking_spaces_floor_number_column_code_slot_number_index` (`floor_number`,`column_code`,`slot_number`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parking_spaces`
--

LOCK TABLES `parking_spaces` WRITE;
/*!40000 ALTER TABLE `parking_spaces` DISABLE KEYS */;
INSERT INTO `parking_spaces` VALUES (1,401,NULL,NULL,NULL,NULL,'4B4','B',0,1,NULL,'4th Floor',750.00,48.00,0,80.00,120.00,'2025-12-13 04:11:34','2025-12-13 04:37:22'),(2,402,NULL,NULL,NULL,NULL,'4B3','B',0,1,NULL,'4th Floor',817.50,48.00,0,80.00,120.00,'2025-12-13 04:11:34','2025-12-13 04:37:22'),(3,403,NULL,NULL,NULL,NULL,'4B2','B',0,1,NULL,'4th Floor',885.00,48.00,0,80.00,120.00,'2025-12-13 04:11:34','2025-12-13 04:37:22'),(4,404,NULL,NULL,NULL,NULL,'4B1','B',0,1,NULL,'4th Floor',952.50,48.00,0,80.00,120.00,'2025-12-13 04:11:34','2025-12-13 04:37:22'),(5,405,NULL,NULL,NULL,NULL,'4C1','C',0,1,NULL,'4th Floor',675.00,142.50,270,80.00,120.00,'2025-12-13 04:11:34','2025-12-13 04:37:22'),(6,201,NULL,NULL,NULL,NULL,'2B1','B',0,1,NULL,'2nd Floor',100.00,100.00,0,80.00,120.00,'2025-12-13 04:11:34','2025-12-13 04:11:34'),(7,202,NULL,NULL,NULL,NULL,'2B2','B',0,1,NULL,'2nd Floor',180.00,100.00,0,80.00,120.00,'2025-12-13 04:11:34','2025-12-13 04:11:34'),(8,203,NULL,NULL,NULL,NULL,'2B3','B',0,1,NULL,'2nd Floor',260.00,100.00,0,80.00,120.00,'2025-12-13 04:11:34','2025-12-13 04:11:34'),(9,407,NULL,NULL,NULL,NULL,'4A1','A',0,1,NULL,'4th Floor',1027.50,174.00,180,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(10,406,NULL,NULL,NULL,NULL,'4C2','C',0,1,NULL,'4th Floor',675.00,210.00,270,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(11,414,NULL,NULL,NULL,NULL,'4D7','D',0,1,NULL,'4th Floor',150.00,300.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(12,413,NULL,NULL,NULL,NULL,'4D6','D',0,1,NULL,'4th Floor',240.00,300.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(13,412,NULL,NULL,NULL,NULL,'4D5','D',0,1,NULL,'4th Floor',315.00,300.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(14,411,NULL,NULL,NULL,NULL,'4D4','D',0,1,NULL,'4th Floor',388.50,300.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(15,410,NULL,NULL,NULL,NULL,'4D3','D',0,1,NULL,'4th Floor',465.00,300.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(16,409,NULL,NULL,NULL,NULL,'4D2','D',0,1,NULL,'4th Floor',532.50,300.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(17,408,NULL,NULL,NULL,NULL,'4D1','D',0,1,NULL,'4th Floor',600.00,300.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(18,417,NULL,NULL,NULL,NULL,'4E3','E',0,1,NULL,'4th Floor',82.50,472.50,180,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(19,416,NULL,NULL,NULL,NULL,'4E2','E',0,1,NULL,'4th Floor',82.50,570.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(20,415,NULL,NULL,NULL,NULL,'4E1','E',0,1,NULL,'4th Floor',82.50,667.50,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(21,418,NULL,NULL,NULL,NULL,'4F1','F',0,1,NULL,'4th Floor',180.00,780.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(22,419,NULL,NULL,NULL,NULL,'4F2','F',0,1,NULL,'4th Floor',247.50,780.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(23,420,NULL,NULL,NULL,NULL,'4F3','F',0,1,NULL,'4th Floor',330.00,780.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(24,421,NULL,NULL,NULL,NULL,'4F4','F',0,1,NULL,'4th Floor',397.50,780.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(25,422,NULL,NULL,NULL,NULL,'4F5','F',0,1,NULL,'4th Floor',465.00,780.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(26,423,NULL,NULL,NULL,NULL,'4F6','F',0,1,NULL,'4th Floor',547.50,780.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(27,424,NULL,NULL,NULL,NULL,'4F7','F',0,1,NULL,'4th Floor',615.00,780.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(28,425,NULL,NULL,NULL,NULL,'4G1','G',0,1,NULL,'4th Floor',750.00,885.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(29,426,NULL,NULL,NULL,NULL,'4G2','G',0,1,NULL,'4th Floor',750.00,975.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(30,427,NULL,NULL,NULL,NULL,'4G3','G',0,1,NULL,'4th Floor',750.00,1065.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(31,428,NULL,NULL,NULL,NULL,'4G4','G',0,1,NULL,'4th Floor',750.00,1155.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(32,429,NULL,NULL,NULL,NULL,'4G5','G',0,1,NULL,'4th Floor',750.00,1245.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(33,430,NULL,NULL,NULL,NULL,'4H1','H',0,1,NULL,'4th Floor',840.00,1335.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(34,431,NULL,NULL,NULL,NULL,'4H2','H',0,1,NULL,'4th Floor',907.50,1335.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(35,432,NULL,NULL,NULL,NULL,'4H3','H',0,1,NULL,'4th Floor',975.00,1335.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(36,437,NULL,NULL,NULL,NULL,'4I5','I',0,1,NULL,'4th Floor',1020.00,885.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(37,436,NULL,NULL,NULL,NULL,'4I4','I',0,1,NULL,'4th Floor',1020.00,975.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(38,435,NULL,NULL,NULL,NULL,'4I3','I',0,1,NULL,'4th Floor',1020.00,1065.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(39,434,NULL,NULL,NULL,NULL,'4I2','I',0,1,NULL,'4th Floor',1020.00,1155.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(40,433,NULL,NULL,NULL,NULL,'4I1','I',0,1,NULL,'4th Floor',1020.00,1245.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(41,442,NULL,NULL,NULL,NULL,'4J5','J',0,1,NULL,'4th Floor',405.00,555.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(42,441,NULL,NULL,NULL,NULL,'4J4','J',0,1,NULL,'4th Floor',480.00,555.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(43,440,NULL,NULL,NULL,NULL,'4J3','J',0,1,NULL,'4th Floor',570.00,555.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(44,439,NULL,NULL,NULL,NULL,'4J2','J',0,1,NULL,'4th Floor',660.00,555.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22'),(45,438,NULL,NULL,NULL,NULL,'4J1','J',0,1,NULL,'4th Floor',735.00,555.00,0,60.00,85.00,'2025-12-13 04:33:19','2025-12-13 04:37:22');
/*!40000 ALTER TABLE `parking_spaces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `pending_accounts`
--

DROP TABLE IF EXISTS `pending_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pending_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('user','security','ssd','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pending_accounts_status_index` (`status`),
  KEY `pending_accounts_created_by_index` (`created_by`),
  KEY `pending_accounts_reviewed_by_index` (`reviewed_by`),
  CONSTRAINT `pending_accounts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pending_accounts_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `sys_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pending_accounts`
--

LOCK TABLES `pending_accounts` WRITE;
/*!40000 ALTER TABLE `pending_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `pending_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
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
INSERT INTO `sessions` VALUES ('Ze985VAxjO1VuG4NYZw5y0RIg5SfdNcaCxnPUNx8',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoib1BCTmhmVUhFR3B2SDJUOE1FZHlMOTl4Y1FZNGpXNjhUNlVPM3VkUCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMzoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL3BhcmtpbmctbWFwIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYXNoYm9hcmQiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=',1765630299);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_users`
--

DROP TABLE IF EXISTS `sys_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('user','security','ssd','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `employee_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sys_users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_users`
--

LOCK TABLES `sys_users` WRITE;
/*!40000 ALTER TABLE `sys_users` DISABLE KEYS */;
INSERT INTO `sys_users` VALUES (1,'VALET Administrator','admin@valet.com',NULL,'$2y$12$mR0H5n9fl2Owt2.IF6CYteUjmYDw/kbMVMdXPs5cK4inC.soKa3Z2','admin',1,'ADMIN001','IT Administration',NULL,'2025-12-13 04:11:34','2025-12-13 04:11:34'),(2,'John Doe','user@valet.com',NULL,'$2y$12$3bdu4BruikhpX8XbYIafee18RANv9jPEHabc9yQ7.rVJHFRXzU8Ga','user',1,'USER001','General User',NULL,'2025-12-13 04:11:34','2025-12-13 04:11:34'),(3,'SSD User','ssd@valet.com',NULL,'$2y$12$RLsBOcEIA6HTmO6POeA2vO8lKabV0ixniVIEok45Ws7s1nLteAJSe','ssd',1,'SSD001','SSD Department',NULL,'2025-12-13 04:11:34','2025-12-13 04:11:34'),(4,'Ahh Chip','security@valet.com',NULL,'$2y$12$tzOZbmmDFhkj.GdeOvYy3OcD3DCejIcylfvn2D.Z1jlgyDuFfo9ye','security',1,'SECURITY001','Security Department',NULL,'2025-12-13 04:11:34','2025-12-13 04:11:34');
/*!40000 ALTER TABLE `sys_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plate_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_make` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_model` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_color` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_type` enum('car','suv','truck','van') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'car',
  `rfid_tag` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner_id` bigint unsigned NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicles_plate_number_unique` (`plate_number`),
  UNIQUE KEY `vehicles_rfid_tag_unique` (`rfid_tag`),
  KEY `vehicles_plate_number_index` (`plate_number`),
  KEY `vehicles_rfid_tag_index` (`rfid_tag`),
  KEY `vehicles_owner_id_index` (`owner_id`),
  KEY `vehicles_expires_at_index` (`expires_at`),
  KEY `vehicles_is_active_index` (`is_active`),
  CONSTRAINT `vehicles_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `sys_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-13 20:52:10
