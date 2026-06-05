-- MySQL dump 10.13  Distrib 8.0.45, for Win64 (x86_64)
--
-- Host: localhost    Database: utc_elibrary
-- ------------------------------------------------------
-- Server version	8.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `authors`
--

DROP TABLE IF EXISTS `authors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `authors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `params` json DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `authors_slug_unique` (`slug`),
  KEY `authors_created_by_foreign` (`created_by`),
  KEY `authors_updated_by_foreign` (`updated_by`),
  KEY `authors_deleted_by_foreign` (`deleted_by`),
  KEY `authors_name_index` (`name`),
  CONSTRAINT `authors_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `authors_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `authors_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `authors`
--

LOCK TABLES `authors` WRITE;
/*!40000 ALTER TABLE `authors` DISABLE KEYS */;
/*!40000 ALTER TABLE `authors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_authors`
--

DROP TABLE IF EXISTS `book_authors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_authors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `book_id` bigint unsigned NOT NULL,
  `author_id` bigint unsigned NOT NULL,
  `order` smallint unsigned NOT NULL DEFAULT '0',
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `book_authors_book_id_author_id_unique` (`book_id`,`author_id`),
  KEY `book_authors_author_id_foreign` (`author_id`),
  KEY `book_authors_created_by_foreign` (`created_by`),
  KEY `book_authors_updated_by_foreign` (`updated_by`),
  KEY `book_authors_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `book_authors_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `book_authors_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `book_authors_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `book_authors_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `book_authors_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_authors`
--

LOCK TABLES `book_authors` WRITE;
/*!40000 ALTER TABLE `book_authors` DISABLE KEYS */;
/*!40000 ALTER TABLE `book_authors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_copies`
--

DROP TABLE IF EXISTS `book_copies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_copies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `book_id` bigint unsigned NOT NULL,
  `barcode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `call_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT 'BookStatus: 1 available, 2 borrowed, …',
  `physical_condition` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'good' COMMENT 'Vật lý: good, fair, worn, needs_repair, damaged',
  `warehouse_id` bigint unsigned DEFAULT NULL,
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `params` json DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `book_copies_barcode_unique` (`barcode`),
  KEY `book_copies_warehouse_id_foreign` (`warehouse_id`),
  KEY `book_copies_created_by_foreign` (`created_by`),
  KEY `book_copies_updated_by_foreign` (`updated_by`),
  KEY `book_copies_deleted_by_foreign` (`deleted_by`),
  KEY `book_copies_book_id_status_index` (`book_id`,`status`),
  KEY `book_copies_status_index` (`status`),
  KEY `book_copies_physical_condition_index` (`physical_condition`),
  CONSTRAINT `book_copies_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `book_copies_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `book_copies_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `book_copies_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `book_copies_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_copies`
--

LOCK TABLES `book_copies` WRITE;
/*!40000 ALTER TABLE `book_copies` DISABLE KEYS */;
/*!40000 ALTER TABLE `book_copies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_publishers`
--

DROP TABLE IF EXISTS `book_publishers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_publishers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `book_id` bigint unsigned NOT NULL,
  `publisher_id` bigint unsigned NOT NULL,
  `order` smallint unsigned NOT NULL DEFAULT '0',
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `book_publishers_book_id_publisher_id_unique` (`book_id`,`publisher_id`),
  KEY `book_publishers_publisher_id_foreign` (`publisher_id`),
  KEY `book_publishers_created_by_foreign` (`created_by`),
  KEY `book_publishers_updated_by_foreign` (`updated_by`),
  KEY `book_publishers_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `book_publishers_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `book_publishers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `book_publishers_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `book_publishers_publisher_id_foreign` FOREIGN KEY (`publisher_id`) REFERENCES `publishers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `book_publishers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_publishers`
--

LOCK TABLES `book_publishers` WRITE;
/*!40000 ALTER TABLE `book_publishers` DISABLE KEYS */;
/*!40000 ALTER TABLE `book_publishers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `books` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `registration_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `book_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sub_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `edition` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_year` smallint unsigned DEFAULT NULL,
  `pages` int unsigned DEFAULT NULL,
  `illustration_pages` int unsigned DEFAULT NULL,
  `book_size` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` bigint unsigned DEFAULT NULL,
  `quantity` int unsigned NOT NULL DEFAULT '0',
  `view_count` bigint unsigned NOT NULL DEFAULT '0' COMMENT 'Lượt xem trang chi tiết (sách in)',
  `summary` longtext COLLATE utf8mb4_unicode_ci,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `publisher_place` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cabinet` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `classification_id` bigint unsigned DEFAULT NULL,
  `warehouse_id` bigint unsigned DEFAULT NULL,
  `resource_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reference' COMMENT 'Loại tài liệu: textbook, reference, digital',
  `access_mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'circulation_only' COMMENT 'circulation_only|online_only|both',
  `params` json DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `books_registration_number_unique` (`registration_number`),
  KEY `books_created_by_foreign` (`created_by`),
  KEY `books_updated_by_foreign` (`updated_by`),
  KEY `books_deleted_by_foreign` (`deleted_by`),
  KEY `books_classification_id_index` (`classification_id`),
  KEY `books_warehouse_id_index` (`warehouse_id`),
  KEY `books_resource_type_classification_idx` (`resource_type`,`classification_id`),
  KEY `books_resource_deleted_created_id_idx` (`resource_type`,`deleted_at`,`created_at`,`id`),
  KEY `books_book_code_index` (`book_code`),
  KEY `books_title_index` (`title`),
  CONSTRAINT `books_classification_id_foreign` FOREIGN KEY (`classification_id`) REFERENCES `classifications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `books_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `books_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `books_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `books_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `books`
--

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
/*!40000 ALTER TABLE `books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
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
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` bigint unsigned NOT NULL,
  `item_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'loan_book_copy|digital_asset_unlock',
  `digital_asset_id` bigint unsigned DEFAULT NULL,
  `book_copy_id` bigint unsigned DEFAULT NULL COMMENT 'FK tới book_copies nếu item_type=loan_book_copy',
  `quantity` int unsigned NOT NULL DEFAULT '1',
  `unit_price_vnd_snapshot` bigint unsigned DEFAULT NULL COMMENT 'Snapshot giá tại thời điểm add vào giỏ (digital); loan để null',
  `line_total_vnd_snapshot` bigint unsigned DEFAULT NULL COMMENT 'Snapshot thành tiền; có thể tính = unit_price * quantity',
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cart_items_cart_digital_asset_unique` (`cart_id`,`digital_asset_id`),
  KEY `cart_items_cart_id_item_type_index` (`cart_id`,`item_type`),
  KEY `cart_items_digital_asset_id_index` (`digital_asset_id`),
  KEY `cart_items_book_copy_id_index` (`book_copy_id`),
  CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_digital_asset_id_foreign` FOREIGN KEY (`digital_asset_id`) REFERENCES `digital_assets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_items`
--

LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carts`
--

DROP TABLE IF EXISTS `carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'loan|digital_purchase',
  `price_locked_until` timestamp NULL DEFAULT NULL COMMENT 'Chỉ áp dụng cho giỏ digital_purchase nếu muốn giữ giá tạm thời; null = chưa khóa',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `carts_user_id_type_unique` (`user_id`,`type`),
  KEY `carts_type_updated_at_index` (`type`,`updated_at`),
  KEY `carts_price_locked_until_index` (`price_locked_until`),
  CONSTRAINT `carts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carts`
--

LOCK TABLES `carts` WRITE;
/*!40000 ALTER TABLE `carts` DISABLE KEYS */;
/*!40000 ALTER TABLE `carts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classifications`
--

DROP TABLE IF EXISTS `classifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `params` json DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `classifications_code_unique` (`code`),
  KEY `classifications_created_by_foreign` (`created_by`),
  KEY `classifications_updated_by_foreign` (`updated_by`),
  KEY `classifications_deleted_by_foreign` (`deleted_by`),
  KEY `classifications_name_index` (`name`),
  CONSTRAINT `classifications_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `classifications_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `classifications_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classifications`
--

LOCK TABLES `classifications` WRITE;
/*!40000 ALTER TABLE `classifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `classifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `params` json DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1' COMMENT 'Trạng thái bản ghi (domain-specific)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customers_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `faculty_id` int unsigned NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `params` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_code_unique` (`code`),
  KEY `departments_faculty_id_foreign` (`faculty_id`),
  CONSTRAINT `departments_faculty_id_foreign` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `digital_access_sessions`
--

DROP TABLE IF EXISTS `digital_access_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `digital_access_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `digital_asset_id` bigint unsigned NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `download_count` int unsigned NOT NULL DEFAULT '0',
  `max_downloads` int unsigned DEFAULT NULL,
  `params` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `digital_access_sessions_digital_asset_id_foreign` (`digital_asset_id`),
  KEY `digital_access_sessions_user_id_digital_asset_id_index` (`user_id`,`digital_asset_id`),
  KEY `digital_access_sessions_expires_at_index` (`expires_at`),
  CONSTRAINT `digital_access_sessions_digital_asset_id_foreign` FOREIGN KEY (`digital_asset_id`) REFERENCES `digital_assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `digital_access_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `digital_access_sessions`
--

LOCK TABLES `digital_access_sessions` WRITE;
/*!40000 ALTER TABLE `digital_access_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `digital_access_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `digital_asset_paywall_settings`
--

DROP TABLE IF EXISTS `digital_asset_paywall_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `digital_asset_paywall_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `digital_asset_id` bigint unsigned NOT NULL,
  `is_paywall_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'false: không thu phí, tải PDF miễn phí',
  `pdf_download_price_vnd` bigint unsigned NOT NULL COMMENT 'Giá tải PDF toàn bộ (VND, số nguyên)',
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VND',
  `internal_note` text COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú nội bộ thủ thư, không hiển thị độc giả',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `digital_asset_paywall_settings_digital_asset_id_unique` (`digital_asset_id`),
  KEY `digital_asset_paywall_settings_created_by_foreign` (`created_by`),
  KEY `digital_asset_paywall_settings_updated_by_foreign` (`updated_by`),
  KEY `digital_asset_paywall_settings_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `digital_asset_paywall_settings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `digital_asset_paywall_settings_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `digital_asset_paywall_settings_digital_asset_id_foreign` FOREIGN KEY (`digital_asset_id`) REFERENCES `digital_assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `digital_asset_paywall_settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `digital_asset_paywall_settings`
--

LOCK TABLES `digital_asset_paywall_settings` WRITE;
/*!40000 ALTER TABLE `digital_asset_paywall_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `digital_asset_paywall_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `digital_asset_pdf_download_entitlements`
--

DROP TABLE IF EXISTS `digital_asset_pdf_download_entitlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `digital_asset_pdf_download_entitlements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `digital_asset_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `granted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'null: quyền không hết hạn theo thời gian (trừ khi thu hồi)',
  `revoked_at` timestamp NULL DEFAULT NULL COMMENT 'Thu hồi thủ công / vi phạm',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `digital_asset_entitlement_user_asset_unique` (`user_id`,`digital_asset_id`),
  KEY `digital_asset_pdf_download_entitlements_order_id_foreign` (`order_id`),
  KEY `da_entitlement_asset_revoked_idx` (`digital_asset_id`,`revoked_at`),
  KEY `digital_asset_pdf_download_entitlements_expires_at_index` (`expires_at`),
  KEY `digital_asset_pdf_download_entitlements_revoked_at_index` (`revoked_at`),
  CONSTRAINT `digital_asset_pdf_download_entitlements_digital_asset_id_foreign` FOREIGN KEY (`digital_asset_id`) REFERENCES `digital_assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `digital_asset_pdf_download_entitlements_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `digital_asset_pdf_download_entitlements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `digital_asset_pdf_download_entitlements`
--

LOCK TABLES `digital_asset_pdf_download_entitlements` WRITE;
/*!40000 ALTER TABLE `digital_asset_pdf_download_entitlements` DISABLE KEYS */;
/*!40000 ALTER TABLE `digital_asset_pdf_download_entitlements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `digital_assets`
--

DROP TABLE IF EXISTS `digital_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `digital_assets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `book_id` bigint unsigned NOT NULL,
  `version` smallint unsigned NOT NULL DEFAULT '1',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `storage_disk` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local' COMMENT 'Disk lưu PDF gốc — config filesystems.digital_assets_disk',
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `preview_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'PDF N trang đầu (FPDI), cùng disk với bản gốc',
  `preview_page_count` tinyint unsigned DEFAULT NULL,
  `preview_generated_at` timestamp NULL DEFAULT NULL,
  `preview_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending|processing|ready|failed|disabled — tạo preview qua queue',
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `byte_size` bigint unsigned DEFAULT NULL,
  `view_count` bigint unsigned NOT NULL DEFAULT '0' COMMENT 'Lượt xem trang chi tiết + xem trước PDF',
  `download_count` bigint unsigned NOT NULL DEFAULT '0' COMMENT 'Lượt tải PDF gốc',
  `preview_display` json DEFAULT NULL COMMENT 'PNG từng trang xem trước (preview_display.pages[].path)',
  `checksum_sha256` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visibility` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'internal',
  `embargo_until` date DEFAULT NULL,
  `params` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  PRIMARY KEY (`id`),
  KEY `digital_assets_created_by_foreign` (`created_by`),
  KEY `digital_assets_updated_by_foreign` (`updated_by`),
  KEY `digital_assets_deleted_by_foreign` (`deleted_by`),
  KEY `digital_assets_book_id_is_primary_index` (`book_id`,`is_primary`),
  KEY `digital_assets_book_id_version_index` (`book_id`,`version`),
  KEY `digital_assets_checksum_sha256_index` (`checksum_sha256`),
  KEY `digital_assets_visibility_index` (`visibility`),
  CONSTRAINT `digital_assets_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `digital_assets_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `digital_assets_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `digital_assets_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `digital_assets`
--

LOCK TABLES `digital_assets` WRITE;
/*!40000 ALTER TABLE `digital_assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `digital_assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `digital_document_submissions`
--

DROP TABLE IF EXISTS `digital_document_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `digital_document_submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `submitted_by` int unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author_names` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Danh sách tác giả, phân tách bằng ;',
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `byte_size` bigint unsigned DEFAULT NULL,
  `cover_image_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ảnh bìa tùy chọn do độc giả gửi kèm',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending|approved|rejected',
  `review_note` text COLLATE utf8mb4_unicode_ci,
  `reviewed_by` int unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `approved_book_id` bigint unsigned DEFAULT NULL,
  `user_hidden_at` timestamp NULL DEFAULT NULL COMMENT 'Độc giả ẩn khỏi danh sách của mình; thủ thư vẫn quản lý đầy đủ',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `digital_document_submissions_submitted_by_foreign` (`submitted_by`),
  KEY `digital_document_submissions_reviewed_by_foreign` (`reviewed_by`),
  KEY `digital_document_submissions_approved_book_id_foreign` (`approved_book_id`),
  KEY `digital_document_submissions_status_created_at_index` (`status`,`created_at`),
  KEY `digital_document_submissions_user_hidden_at_index` (`user_hidden_at`),
  CONSTRAINT `digital_document_submissions_approved_book_id_foreign` FOREIGN KEY (`approved_book_id`) REFERENCES `books` (`id`) ON DELETE SET NULL,
  CONSTRAINT `digital_document_submissions_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `digital_document_submissions_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `digital_document_submissions`
--

LOCK TABLES `digital_document_submissions` WRITE;
/*!40000 ALTER TABLE `digital_document_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `digital_document_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_otp`
--

DROP TABLE IF EXISTS `email_otp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_otp` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otp` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expired_at` timestamp NULL DEFAULT NULL,
  `params` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_otp_email_unique` (`email`),
  KEY `email_otp_expired_at_index` (`expired_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_otp`
--

LOCK TABLES `email_otp` WRITE;
/*!40000 ALTER TABLE `email_otp` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_otp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faculties`
--

DROP TABLE IF EXISTS `faculties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `faculties` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `params` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `faculties_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faculties`
--

LOCK TABLES `faculties` WRITE;
/*!40000 ALTER TABLE `faculties` DISABLE KEYS */;
/*!40000 ALTER TABLE `faculties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_card_payments`
--

DROP TABLE IF EXISTS `library_card_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_card_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `library_card_id` bigint unsigned NOT NULL,
  `payment_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'pending|paid|failed|refunded',
  `payment_amount` decimal(12,2) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `payment_method` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_collected_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `library_card_payments_library_card_id_unique` (`library_card_id`),
  KEY `library_card_payments_payment_collected_by_foreign` (`payment_collected_by`),
  KEY `library_card_payments_payment_status_index` (`payment_status`),
  CONSTRAINT `library_card_payments_library_card_id_foreign` FOREIGN KEY (`library_card_id`) REFERENCES `library_cards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `library_card_payments_payment_collected_by_foreign` FOREIGN KEY (`payment_collected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_card_payments`
--

LOCK TABLES `library_card_payments` WRITE;
/*!40000 ALTER TABLE `library_card_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_card_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_cards`
--

DROP TABLE IF EXISTS `library_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_cards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `period_id` int unsigned DEFAULT NULL,
  `card_number` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `holder_type` enum('student','teacher','external') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'external' COMMENT 'Sinh viên / Giảng viên / Bạn đọc ngoài',
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `faculty_id` int unsigned DEFAULT NULL,
  `department_id` int unsigned DEFAULT NULL,
  `class_code` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã lớp hành chính',
  `date_of_birth` date DEFAULT NULL,
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_organization` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `workflow_status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft' COMMENT 'draft|pending_payment|pending_review|…',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT 'LibraryCardStatus enum int',
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `issued_by` int unsigned DEFAULT NULL,
  `reviewed_by` int unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `params` json DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú nội bộ / lý do từ chối',
  `revoked_at` timestamp NULL DEFAULT NULL,
  `revoked_reason` text COLLATE utf8mb4_unicode_ci,
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `library_cards_created_by_foreign` (`created_by`),
  KEY `library_cards_updated_by_foreign` (`updated_by`),
  KEY `library_cards_deleted_by_foreign` (`deleted_by`),
  KEY `library_cards_issued_by_foreign` (`issued_by`),
  KEY `library_cards_reviewed_by_foreign` (`reviewed_by`),
  KEY `library_cards_type_status_index` (`holder_type`,`status`),
  KEY `library_cards_workflow_created_id_idx` (`workflow_status`,`created_at`,`id`),
  KEY `library_cards_holder_created_id_idx` (`holder_type`,`created_at`,`id`),
  KEY `library_cards_status_created_id_idx` (`status`,`created_at`,`id`),
  KEY `library_cards_wf_holder_status_created_id_idx` (`workflow_status`,`holder_type`,`status`,`created_at`,`id`),
  KEY `library_cards_full_name_id_idx` (`full_name`,`id`),
  KEY `library_cards_user_id_index` (`user_id`),
  KEY `library_cards_period_id_index` (`period_id`),
  KEY `library_cards_card_number_index` (`card_number`),
  KEY `library_cards_holder_type_index` (`holder_type`),
  KEY `library_cards_code_index` (`code`),
  KEY `library_cards_email_index` (`email`),
  KEY `library_cards_phone_index` (`phone`),
  KEY `library_cards_faculty_id_index` (`faculty_id`),
  KEY `library_cards_department_id_index` (`department_id`),
  KEY `library_cards_workflow_status_index` (`workflow_status`),
  KEY `library_cards_status_index` (`status`),
  CONSTRAINT `library_cards_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `library_cards_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `library_cards_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `library_cards_faculty_id_foreign` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `library_cards_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `library_cards_period_id_foreign` FOREIGN KEY (`period_id`) REFERENCES `periods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `library_cards_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `library_cards_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `library_cards_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_cards`
--

LOCK TABLES `library_cards` WRITE;
/*!40000 ALTER TABLE `library_cards` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_settings`
--

DROP TABLE IF EXISTS `library_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string' COMMENT 'string|int|bool|json',
  `value` text COLLATE utf8mb4_unicode_ci,
  `json_value` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `library_settings_key_unique` (`key`),
  KEY `library_settings_created_by_foreign` (`created_by`),
  KEY `library_settings_updated_by_foreign` (`updated_by`),
  KEY `library_settings_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `library_settings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `library_settings_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `library_settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_settings`
--

LOCK TABLES `library_settings` WRITE;
/*!40000 ALTER TABLE `library_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_borrow_request_items`
--

DROP TABLE IF EXISTS `loan_borrow_request_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_borrow_request_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `borrow_request_id` bigint unsigned NOT NULL,
  `book_id` bigint unsigned NOT NULL,
  `quantity` int unsigned NOT NULL DEFAULT '1',
  `condition_on_loan` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'tot|hong|mat; thủ thư nhập khi duyệt',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loan_borrow_request_items_book_id_foreign` (`book_id`),
  KEY `loan_borrow_request_items_req_book_idx` (`borrow_request_id`,`book_id`),
  CONSTRAINT `loan_borrow_request_items_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `loan_borrow_request_items_borrow_request_id_foreign` FOREIGN KEY (`borrow_request_id`) REFERENCES `loan_borrow_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_borrow_request_items`
--

LOCK TABLES `loan_borrow_request_items` WRITE;
/*!40000 ALTER TABLE `loan_borrow_request_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_borrow_request_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_borrow_requests`
--

DROP TABLE IF EXISTS `loan_borrow_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_borrow_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_code` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `library_card_id` bigint unsigned NOT NULL,
  `requested_by` int unsigned NOT NULL COMMENT 'Bạn đọc tạo yêu cầu',
  `loan_type` enum('home','onsite') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'home',
  `requested_loan_date` date DEFAULT NULL COMMENT 'Ngày mượn bạn đọc mong muốn',
  `requested_due_date` date DEFAULT NULL COMMENT 'Ngày hẹn trả bạn đọc mong muốn',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending|approved|rejected|cancelled',
  `request_note` text COLLATE utf8mb4_unicode_ci,
  `reviewed_by` int unsigned DEFAULT NULL COMMENT 'Thủ thư/Admin xử lý',
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_note` text COLLATE utf8mb4_unicode_ci,
  `approved_loan_id` bigint unsigned DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_borrow_requests_request_code_unique` (`request_code`),
  KEY `loan_borrow_requests_approved_loan_id_foreign` (`approved_loan_id`),
  KEY `loan_borrow_requests_created_by_foreign` (`created_by`),
  KEY `loan_borrow_requests_updated_by_foreign` (`updated_by`),
  KEY `loan_borrow_requests_deleted_by_foreign` (`deleted_by`),
  KEY `loan_borrow_requests_library_card_id_status_index` (`library_card_id`,`status`),
  KEY `loan_borrow_requests_requested_by_status_index` (`requested_by`,`status`),
  KEY `loan_borrow_requests_reviewed_by_foreign` (`reviewed_by`),
  KEY `loan_borrow_requests_loan_type_index` (`loan_type`),
  KEY `loan_borrow_requests_status_index` (`status`),
  CONSTRAINT `loan_borrow_requests_approved_loan_id_foreign` FOREIGN KEY (`approved_loan_id`) REFERENCES `loans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_borrow_requests_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_borrow_requests_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_borrow_requests_library_card_id_foreign` FOREIGN KEY (`library_card_id`) REFERENCES `library_cards` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `loan_borrow_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_borrow_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_borrow_requests_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_borrow_requests`
--

LOCK TABLES `loan_borrow_requests` WRITE;
/*!40000 ALTER TABLE `loan_borrow_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_borrow_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_items`
--

DROP TABLE IF EXISTS `loan_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint unsigned NOT NULL,
  `book_id` bigint unsigned NOT NULL,
  `quantity` int unsigned NOT NULL DEFAULT '1',
  `condition_on_loan` enum('tot','hong','mat') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `condition_on_return` enum('tot','hong','mat') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `damage_percent` tinyint unsigned DEFAULT NULL COMMENT '% mức hư hỏng khi trả (0–100); mất sách = 100',
  `fine_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú thủ thư',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loan_items_loan_id_book_id_index` (`loan_id`,`book_id`),
  KEY `loan_items_book_id_index` (`book_id`),
  CONSTRAINT `loan_items_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `loan_items_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_items`
--

LOCK TABLES `loan_items` WRITE;
/*!40000 ALTER TABLE `loan_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_policies`
--

DROP TABLE IF EXISTS `loan_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Khớp RoleType/user; null = mặc định mọi đối tượng',
  `max_books` int unsigned NOT NULL DEFAULT '0' COMMENT 'Số đầu sách mượn tối đa đồng thời',
  `max_days` int unsigned NOT NULL DEFAULT '0' COMMENT 'Thời hạn mượn (ngày)',
  `max_renewals` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'Số lần gia hạn tối đa',
  `overdue_fine_per_day` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Phạt mỗi ngày trễ hạn',
  `allow_home` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Được mượn về nhà',
  `allow_onsite` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Được đọc/mượn tại chỗ',
  `params` json DEFAULT NULL COMMENT 'Mở rộng cấu hình',
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_policies_code_unique` (`code`),
  KEY `loan_policies_created_by_foreign` (`created_by`),
  KEY `loan_policies_updated_by_foreign` (`updated_by`),
  KEY `loan_policies_deleted_by_foreign` (`deleted_by`),
  KEY `loan_policies_user_type_index` (`user_type`),
  CONSTRAINT `loan_policies_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_policies_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_policies_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_policies`
--

LOCK TABLES `loan_policies` WRITE;
/*!40000 ALTER TABLE `loan_policies` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_policies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_renewal_requests`
--

DROP TABLE IF EXISTS `loan_renewal_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_renewal_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint unsigned NOT NULL,
  `requested_by` int unsigned NOT NULL COMMENT 'Người gửi yêu cầu',
  `current_due_date` date NOT NULL COMMENT 'Hạn trả tại thời điểm gửi yêu cầu',
  `requested_due_date` date DEFAULT NULL COMMENT 'Hạn trả mong muốn sau gia hạn',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending|approved|rejected',
  `request_note` text COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú của người dùng',
  `reviewed_by` int unsigned DEFAULT NULL COMMENT 'Người duyệt yêu cầu',
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_note` text COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú xử lý của admin/thủ thư',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loan_renewal_requests_loan_id_status_index` (`loan_id`,`status`),
  KEY `loan_renewal_requests_requested_by_status_index` (`requested_by`,`status`),
  KEY `loan_renewal_requests_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `loan_renewal_requests_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_renewal_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_renewal_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_renewal_requests`
--

LOCK TABLES `loan_renewal_requests` WRITE;
/*!40000 ALTER TABLE `loan_renewal_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_renewal_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loans`
--

DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `library_card_id` bigint unsigned NOT NULL,
  `loan_type` enum('home','onsite') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'home',
  `loan_date` date NOT NULL COMMENT 'Ngày mượn',
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('da_muon','da_tra','qua_han') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'da_muon',
  `deleted` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Ẩn phiếu khỏi danh sách khi xóa (chỉ áp dụng phiếu đã trả)',
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loans_loan_code_unique` (`loan_code`),
  KEY `loans_created_by_foreign` (`created_by`),
  KEY `loans_updated_by_foreign` (`updated_by`),
  KEY `loans_deleted_by_foreign` (`deleted_by`),
  KEY `loans_library_card_id_status_index` (`library_card_id`,`status`),
  KEY `loans_status_due_date_index` (`status`,`due_date`),
  KEY `loans_loan_type_index` (`loan_type`),
  KEY `loans_status_index` (`status`),
  KEY `loans_loan_date_index` (`loan_date`),
  KEY `loans_return_date_index` (`return_date`),
  CONSTRAINT `loans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loans_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loans_library_card_id_foreign` FOREIGN KEY (`library_card_id`) REFERENCES `library_cards` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `loans_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loans`
--

LOCK TABLES `loans` WRITE;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
/*!40000 ALTER TABLE `loans` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0000_00_00_000000_create_customers_table',1),(2,'0000_00_00_000001_create_faculties_table',1),(3,'0000_00_00_000002_create_departments_table',1),(4,'0001_01_01_000000_create_users_table',1),(5,'0001_01_01_000001_create_cache_table',1),(6,'0001_01_01_000002_create_jobs_table',1),(7,'2026_01_28_041716_create_personal_access_tokens_table',1),(8,'2026_01_28_122231_create_permission_tables',1),(9,'2026_01_28_135726_create_email_otp_table',1),(10,'2026_03_12_100000_create_authors_table',1),(11,'2026_03_12_100100_create_publishers_table',1),(12,'2026_03_12_100200_create_classifications_table',1),(13,'2026_03_12_100400_create_warehouses_table',1),(14,'2026_03_12_100500_create_books_table',1),(15,'2026_03_12_100600_create_book_authors_table',1),(16,'2026_03_12_100700_create_book_publishers_table',1),(17,'2026_03_12_101000_create_loan_policies_table',1),(18,'2026_03_12_101100_create_book_copies_table',1),(19,'2026_03_12_101250_create_periods_table',1),(20,'2026_03_12_101300_create_library_cards_table',1),(21,'2026_03_12_101350_create_loans_table',1),(22,'2026_03_12_101360_create_loan_items_table',1),(23,'2026_03_12_101370_create_site_contents_table',1),(24,'2026_03_21_120100_create_digital_assets_table',1),(25,'2026_03_21_120200_create_thesis_metadata_table',1),(26,'2026_03_21_120300_create_digital_access_sessions_table',1),(27,'2026_04_13_180000_create_user_profile_update_requests_table',1),(28,'2026_04_14_160000_create_saved_books_table',1),(29,'2026_04_15_000000_create_notifications_table',1),(30,'2026_04_18_090000_create_loan_renewal_requests_table',1),(31,'2026_04_22_090000_create_storage_cabinets_and_slots',1),(32,'2026_04_26_181500_create_loan_borrow_requests_tables',1),(33,'2026_04_30_090000_create_digital_document_submissions_table',1),(34,'2026_05_05_080000_create_news_posts_tables',1),(35,'2026_05_12_120000_create_digital_asset_paywall_and_payment_tables',1),(36,'2026_05_12_130000_create_library_settings_table',1),(37,'2026_05_16_100000_add_performance_indexes_for_loans_and_items',1),(38,'2026_05_17_100000_add_preview_status_to_digital_assets_table',1),(39,'2026_05_18_120000_expand_book_summary_and_submission_description_columns',1),(40,'2026_05_31_120000_add_damage_percent_to_loan_items_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news_attachments`
--

DROP TABLE IF EXISTS `news_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `news_post_id` bigint unsigned NOT NULL,
  `storage_disk` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `byte_size` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `news_attachments_news_post_id_id_index` (`news_post_id`,`id`),
  CONSTRAINT `news_attachments_news_post_id_foreign` FOREIGN KEY (`news_post_id`) REFERENCES `news_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news_attachments`
--

LOCK TABLES `news_attachments` WRITE;
/*!40000 ALTER TABLE `news_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `news_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news_posts`
--

DROP TABLE IF EXISTS `news_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft' COMMENT 'draft|published',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'news' COMMENT 'Loại bài viết: news|notice',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `news_posts_slug_unique` (`slug`),
  KEY `news_posts_created_by_foreign` (`created_by`),
  KEY `news_posts_updated_by_foreign` (`updated_by`),
  KEY `news_posts_deleted_by_foreign` (`deleted_by`),
  KEY `news_posts_status_type_published_id_idx` (`status`,`type`,`published_at`,`id`),
  KEY `news_posts_type_status_published_id_idx` (`type`,`status`,`published_at`,`id`),
  KEY `news_posts_status_id_idx` (`status`,`id`),
  KEY `news_posts_status_type_id_idx` (`status`,`type`,`id`),
  KEY `news_posts_status_index` (`status`),
  KEY `news_posts_type_index` (`type`),
  KEY `news_posts_published_at_index` (`published_at`),
  CONSTRAINT `news_posts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `news_posts_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `news_posts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news_posts`
--

LOCK TABLES `news_posts` WRITE;
/*!40000 ALTER TABLE `news_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `news_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `recipient_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nhóm nhận: admin|user',
  `recipient_id` int unsigned NOT NULL COMMENT 'ID người nhận thông báo',
  `type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Loại thông báo nghiệp vụ',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tiêu đề hiển thị',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nội dung thông báo',
  `severity` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info' COMMENT 'Mức độ: info|warning|critical',
  `entity_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Loại thực thể liên quan',
  `entity_id` int unsigned DEFAULT NULL COMMENT 'ID thực thể liên quan',
  `action_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'URL điều hướng khi click thông báo',
  `meta` json DEFAULT NULL COMMENT 'Dữ liệu mở rộng cho frontend',
  `dedupe_key` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Khóa chống trùng thông báo',
  `read_at` timestamp NULL DEFAULT NULL COMMENT 'Thời điểm đã đọc',
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notifications_dedupe_key_unique` (`dedupe_key`),
  KEY `notifications_created_by_foreign` (`created_by`),
  KEY `notifications_updated_by_foreign` (`updated_by`),
  KEY `notifications_deleted_by_foreign` (`deleted_by`),
  KEY `notifications_recipient_stream_idx` (`recipient_type`,`recipient_id`,`id`),
  KEY `notifications_recipient_unread_idx` (`recipient_type`,`recipient_id`,`read_at`),
  KEY `notifications_recipient_type_index` (`recipient_type`),
  KEY `notifications_recipient_id_index` (`recipient_id`),
  KEY `notifications_type_index` (`type`),
  KEY `notifications_read_at_index` (`read_at`),
  CONSTRAINT `notifications_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notifications_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notifications_recipient_id_foreign` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `item_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'digital_asset_unlock',
  `digital_asset_id` bigint unsigned NOT NULL,
  `quantity` int unsigned NOT NULL DEFAULT '1',
  `unit_price_vnd_snapshot` bigint unsigned NOT NULL,
  `line_total_vnd_snapshot` bigint unsigned NOT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_item_type_index` (`order_id`,`item_type`),
  KEY `order_items_digital_asset_id_index` (`digital_asset_id`),
  CONSTRAINT `order_items_digital_asset_id_foreign` FOREIGN KEY (`digital_asset_id`) REFERENCES `digital_assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mã đơn an toàn khi expose ra client / gateway',
  `user_id` int unsigned NOT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'digital_purchase',
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending|paid|expired|cancelled|failed',
  `subtotal_vnd_snapshot` bigint unsigned NOT NULL,
  `total_vnd_snapshot` bigint unsigned NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VND',
  `price_locked_until` timestamp NULL DEFAULT NULL COMMENT 'Hết hạn giữ giá / thanh toán nếu vẫn pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `gateway` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sepay' COMMENT 'Cổng thanh toán, mặc định SePay',
  `merchant_reference` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mã tham chiếu gửi gateway (unique, idempotent)',
  `gateway_init_payload` json DEFAULT NULL COMMENT 'Dữ liệu khởi tạo thanh toán (QR/VA/URL…), tránh PII',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_public_id_unique` (`public_id`),
  UNIQUE KEY `orders_merchant_reference_unique` (`merchant_reference`),
  KEY `orders_user_id_status_created_at_index` (`user_id`,`status`,`created_at`),
  KEY `orders_type_status_created_at_index` (`type`,`status`,`created_at`),
  KEY `orders_price_locked_until_index` (`price_locked_until`),
  KEY `orders_paid_at_index` (`paid_at`),
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
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
-- Table structure for table `payment_transactions`
--

DROP TABLE IF EXISTS `payment_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `gateway` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sepay',
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending|success|failed',
  `amount_vnd` bigint unsigned NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VND',
  `gateway_transaction_id` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idempotency_key` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Chống webhook bắn lại / xử lý lặp',
  `verified_at` timestamp NULL DEFAULT NULL,
  `callback_meta` json DEFAULT NULL COMMENT 'Meta sau xác minh webhook, tránh lưu payload đầy đủ nếu có PII',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_transactions_idempotency_key_unique` (`idempotency_key`),
  KEY `payment_transactions_order_id_foreign` (`order_id`),
  KEY `payment_transactions_gateway_status_created_at_index` (`gateway`,`status`,`created_at`),
  KEY `payment_transactions_gateway_transaction_id_index` (`gateway_transaction_id`),
  KEY `payment_transactions_verified_at_index` (`verified_at`),
  CONSTRAINT `payment_transactions_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_transactions`
--

LOCK TABLES `payment_transactions` WRITE;
/*!40000 ALTER TABLE `payment_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `periods`
--

DROP TABLE IF EXISTS `periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `periods` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_year` smallint unsigned DEFAULT NULL,
  `end_year` smallint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `periods_code_unique` (`code`),
  KEY `periods_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `periods`
--

LOCK TABLES `periods` WRITE;
/*!40000 ALTER TABLE `periods` DISABLE KEYS */;
/*!40000 ALTER TABLE `periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` int unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
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
-- Table structure for table `publishers`
--

DROP TABLE IF EXISTS `publishers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `publishers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `params` json DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `publishers_slug_unique` (`slug`),
  KEY `publishers_created_by_foreign` (`created_by`),
  KEY `publishers_updated_by_foreign` (`updated_by`),
  KEY `publishers_deleted_by_foreign` (`deleted_by`),
  KEY `publishers_name_index` (`name`),
  CONSTRAINT `publishers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `publishers_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `publishers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `publishers`
--

LOCK TABLES `publishers` WRITE;
/*!40000 ALTER TABLE `publishers` DISABLE KEYS */;
/*!40000 ALTER TABLE `publishers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saved_books`
--

DROP TABLE IF EXISTS `saved_books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saved_books` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `book_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saved_books_user_id_book_id_unique` (`user_id`,`book_id`),
  KEY `saved_books_book_id_foreign` (`book_id`),
  KEY `saved_books_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `saved_books_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saved_books_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saved_books`
--

LOCK TABLES `saved_books` WRITE;
/*!40000 ALTER TABLE `saved_books` DISABLE KEYS */;
/*!40000 ALTER TABLE `saved_books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
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
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_contents`
--

DROP TABLE IF EXISTS `site_contents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_contents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kind` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'page|post|service',
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `subtype` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'page: intro|rule…; post: news|…; service: mã nghiệp vụ (vd MUON_VE_NHA)',
  `author_id` int unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `published_at` timestamp NULL DEFAULT NULL,
  `params` json DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_contents_slug_unique` (`slug`),
  KEY `site_contents_author_id_foreign` (`author_id`),
  KEY `site_contents_created_by_foreign` (`created_by`),
  KEY `site_contents_updated_by_foreign` (`updated_by`),
  KEY `site_contents_deleted_by_foreign` (`deleted_by`),
  KEY `site_contents_kind_is_published_index` (`kind`,`is_published`),
  KEY `site_contents_kind_index` (`kind`),
  KEY `site_contents_subtype_index` (`subtype`),
  KEY `site_contents_is_published_index` (`is_published`),
  CONSTRAINT `site_contents_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `site_contents_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `site_contents_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `site_contents_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_contents`
--

LOCK TABLES `site_contents` WRITE;
/*!40000 ALTER TABLE `site_contents` DISABLE KEYS */;
/*!40000 ALTER TABLE `site_contents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `storage_cabinets`
--

DROP TABLE IF EXISTS `storage_cabinets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `storage_cabinets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` bigint unsigned NOT NULL,
  `classification_id` bigint unsigned DEFAULT NULL,
  `code` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã tủ lưu trữ',
  `name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên tủ lưu trữ',
  `capacity_total` int unsigned NOT NULL DEFAULT '0' COMMENT 'Tổng sức chứa tủ',
  `current_quantity` int unsigned NOT NULL DEFAULT '0' COMMENT 'Tổng số lượng hiện có trong tủ',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `params` json DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `storage_cabinets_classification_id_foreign` (`classification_id`),
  KEY `storage_cabinets_created_by_foreign` (`created_by`),
  KEY `storage_cabinets_updated_by_foreign` (`updated_by`),
  KEY `storage_cabinets_deleted_by_foreign` (`deleted_by`),
  KEY `storage_cabinets_wh_class_idx` (`warehouse_id`,`classification_id`),
  KEY `storage_cabinets_wh_code_idx` (`warehouse_id`,`code`),
  KEY `storage_cabinets_is_active_index` (`is_active`),
  CONSTRAINT `storage_cabinets_classification_id_foreign` FOREIGN KEY (`classification_id`) REFERENCES `classifications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `storage_cabinets_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `storage_cabinets_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `storage_cabinets_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `storage_cabinets_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `storage_cabinets`
--

LOCK TABLES `storage_cabinets` WRITE;
/*!40000 ALTER TABLE `storage_cabinets` DISABLE KEYS */;
/*!40000 ALTER TABLE `storage_cabinets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `thesis_metadata`
--

DROP TABLE IF EXISTS `thesis_metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `thesis_metadata` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `book_id` bigint unsigned NOT NULL,
  `work_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `degree_program` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supervisor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supervisor_user_id` int unsigned DEFAULT NULL,
  `defense_year` smallint unsigned DEFAULT NULL,
  `keywords` text COLLATE utf8mb4_unicode_ci,
  `abstract_text` text COLLATE utf8mb4_unicode_ci,
  `params` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `thesis_metadata_book_id_unique` (`book_id`),
  KEY `thesis_metadata_created_by_foreign` (`created_by`),
  KEY `thesis_metadata_updated_by_foreign` (`updated_by`),
  KEY `thesis_metadata_deleted_by_foreign` (`deleted_by`),
  KEY `thesis_metadata_work_type_index` (`work_type`),
  KEY `thesis_metadata_supervisor_user_id_index` (`supervisor_user_id`),
  CONSTRAINT `thesis_metadata_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `thesis_metadata_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `thesis_metadata_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `thesis_metadata_supervisor_user_id_foreign` FOREIGN KEY (`supervisor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `thesis_metadata_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `thesis_metadata`
--

LOCK TABLES `thesis_metadata` WRITE;
/*!40000 ALTER TABLE `thesis_metadata` DISABLE KEYS */;
/*!40000 ALTER TABLE `thesis_metadata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_profile_update_requests`
--

DROP TABLE IF EXISTS `user_profile_update_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_profile_update_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL COMMENT 'Người tạo yêu cầu',
  `requested_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã định danh mới',
  `requested_user_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Loại bạn đọc yêu cầu xác nhận: STUDENT|TEACHER',
  `requested_faculty_id` int unsigned DEFAULT NULL COMMENT 'Khoa mới',
  `requested_period_id` int unsigned DEFAULT NULL COMMENT 'Niên khóa mới',
  `requested_class_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Lớp mới',
  `proof_image_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ảnh minh chứng',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending|approved|rejected',
  `is_visible` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Hiển thị cho người gửi: true|false',
  `reason` text COLLATE utf8mb4_unicode_ci COMMENT 'Lý do người dùng gửi',
  `review_note` text COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú duyệt/từ chối',
  `reviewed_by` int unsigned DEFAULT NULL COMMENT 'Người duyệt',
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `applied_at` timestamp NULL DEFAULT NULL COMMENT 'Thời điểm áp dụng vào users',
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_profile_update_requests_created_by_foreign` (`created_by`),
  KEY `user_profile_update_requests_updated_by_foreign` (`updated_by`),
  KEY `user_profile_update_requests_deleted_by_foreign` (`deleted_by`),
  KEY `user_profile_update_requests_requested_faculty_id_foreign` (`requested_faculty_id`),
  KEY `user_profile_update_requests_requested_period_id_foreign` (`requested_period_id`),
  KEY `user_profile_update_requests_reviewed_by_foreign` (`reviewed_by`),
  KEY `user_profile_update_requests_user_id_index` (`user_id`),
  KEY `user_profile_update_requests_status_index` (`status`),
  KEY `user_profile_update_requests_is_visible_index` (`is_visible`),
  CONSTRAINT `user_profile_update_requests_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_profile_update_requests_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_profile_update_requests_requested_faculty_id_foreign` FOREIGN KEY (`requested_faculty_id`) REFERENCES `faculties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_profile_update_requests_requested_period_id_foreign` FOREIGN KEY (`requested_period_id`) REFERENCES `periods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_profile_update_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_profile_update_requests_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_profile_update_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_profile_update_requests`
--

LOCK TABLES `user_profile_update_requests` WRITE;
/*!40000 ALTER TABLE `user_profile_update_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_profile_update_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số điện thoại',
  `user_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MEMBER' COMMENT 'MEMBER|STUDENT|TEACHER|LIBRARIAN|ADMIN|EXTERNAL|…',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `faculty_id` int unsigned DEFAULT NULL COMMENT 'Khoa',
  `department_id` int unsigned DEFAULT NULL COMMENT 'Bộ môn/Trường con',
  `cohort` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Niên khóa/Khoá',
  `period_id` int unsigned DEFAULT NULL COMMENT 'Niên khóa (bảng periods)',
  `class_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Lớp hành chính',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_code_unique` (`code`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_phone_unique` (`phone`),
  KEY `users_created_by_foreign` (`created_by`),
  KEY `users_updated_by_foreign` (`updated_by`),
  KEY `users_deleted_by_foreign` (`deleted_by`),
  KEY `users_user_type_is_active_index` (`user_type`,`is_active`),
  KEY `users_active_type_id_idx` (`is_active`,`user_type`,`id`),
  KEY `users_type_id_idx` (`user_type`,`id`),
  KEY `users_faculty_id_foreign` (`faculty_id`),
  KEY `users_department_id_foreign` (`department_id`),
  KEY `users_name_index` (`name`),
  KEY `users_user_type_index` (`user_type`),
  KEY `users_cohort_index` (`cohort`),
  KEY `users_period_id_foreign` (`period_id`),
  CONSTRAINT `users_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_faculty_id_foreign` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_period_id_foreign` FOREIGN KEY (`period_id`) REFERENCES `periods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `params` json DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL COMMENT 'Người tạo bản ghi',
  `updated_by` int unsigned DEFAULT NULL COMMENT 'Người cập nhật bản ghi',
  `deleted_by` int unsigned DEFAULT NULL COMMENT 'Người xóa (soft/hard)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouses_code_unique` (`code`),
  KEY `warehouses_parent_id_foreign` (`parent_id`),
  KEY `warehouses_created_by_foreign` (`created_by`),
  KEY `warehouses_updated_by_foreign` (`updated_by`),
  KEY `warehouses_deleted_by_foreign` (`deleted_by`),
  KEY `warehouses_name_index` (`name`),
  KEY `warehouses_is_active_index` (`is_active`),
  CONSTRAINT `warehouses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warehouses_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warehouses_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warehouses_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-05 13:01:03
