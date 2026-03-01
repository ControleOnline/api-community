--------------------------------
-- PASTE HERE ALL YOUR SQL DUMP
--------------------------------
-- -- -- REMOVE ONLY:
    -- CREATE DATABASE
    -- USE <database name>;
    -- START TRANSACTION;
    -- COMMIT;
--------------------------------
-- -- -- KEEP:
    -- CREATE TABLE
    -- ALTER TABLE
    -- AUTO_INCREMENT
    -- INDEXES
    -- FOREIGN KEYS
    -- VIEWS
--------------------------------


-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 28, 2026 at 05:43 PM
-- Server version: 10.11.15-MariaDB-cll-lve
-- PHP Version: 8.4.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `control2_erp`
--

DELIMITER $$
--
-- Procedures
--
$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

CREATE TABLE `address` (
  `id` int(11) NOT NULL,
  `people_id` int(11) DEFAULT NULL,
  `number` int(11) DEFAULT NULL,
  `street_id` int(11) NOT NULL,
  `nickname` varchar(50) NOT NULL,
  `complement` varchar(250) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `locator` varchar(250) DEFAULT NULL,
  `opening_time` time DEFAULT NULL,
  `closing_time` time DEFAULT NULL,
  `search_for` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `card`
--

CREATE TABLE `card` (
  `id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `type` enum('credit','debit','voucher','') NOT NULL,
  `name` blob NOT NULL,
  `document` blob NOT NULL,
  `number_group_1` blob NOT NULL,
  `number_group_2` blob NOT NULL,
  `number_group_3` blob NOT NULL,
  `number_group_4` blob NOT NULL,
  `ccv` blob NOT NULL COMMENT 'remover. proibido pelas normas internacionais de segurança (PCI-DSS)',
  `expiration_month` blob NOT NULL,
  `expiration_year` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `car_manufacturer`
--

CREATE TABLE `car_manufacturer` (
  `id` int(11) NOT NULL,
  `car_type_id` int(11) NOT NULL,
  `car_type_ref` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `value` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `car_model`
--

CREATE TABLE `car_model` (
  `id` int(11) NOT NULL,
  `car_manufacturer_id` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `value` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `car_year_price`
--

CREATE TABLE `car_year_price` (
  `id` int(11) NOT NULL,
  `car_type_id` int(11) NOT NULL,
  `car_type_ref` int(11) DEFAULT NULL,
  `fuel_type_code` int(11) DEFAULT NULL,
  `car_manufacturer_id` int(11) NOT NULL,
  `car_model_id` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(50) DEFAULT '$primary',
  `context` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `company_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `category_file`
--

CREATE TABLE `category_file` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cep`
--

CREATE TABLE `cep` (
  `id` int(11) NOT NULL,
  `cep` int(8) UNSIGNED ZEROFILL NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `city`
--

CREATE TABLE `city` (
  `id` int(11) NOT NULL,
  `cod_ibge` int(11) DEFAULT NULL,
  `city` varchar(80) NOT NULL,
  `state_id` int(11) NOT NULL,
  `seo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cms`
--

CREATE TABLE `cms` (
  `id` int(11) NOT NULL,
  `title` varchar(80) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `cms_type` enum('page','article') NOT NULL,
  `people_domain_id` int(11) NOT NULL,
  `class` varchar(500) DEFAULT NULL,
  `style` text DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT current_timestamp(),
  `alter_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cms_section`
--

CREATE TABLE `cms_section` (
  `id` int(11) NOT NULL,
  `cms_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `class` varchar(500) DEFAULT NULL,
  `style` text DEFAULT NULL,
  `order` int(11) NOT NULL,
  `creation_date` datetime NOT NULL DEFAULT current_timestamp(),
  `alter_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cms_section_component`
--

CREATE TABLE `cms_section_component` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_document`
--

CREATE TABLE `company_document` (
  `id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `document_type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE `config` (
  `id` int(11) NOT NULL,
  `visibility` enum('public','private') NOT NULL DEFAULT 'private',
  `people_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `config_key` varchar(50) NOT NULL,
  `config_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `connections`
--

CREATE TABLE `connections` (
  `id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL,
  `type` enum('crm','support','order') DEFAULT NULL,
  `name` varchar(50) NOT NULL,
  `phone_id` int(11) DEFAULT NULL,
  `channel` enum('whatsapp') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contract`
--

CREATE TABLE `contract` (
  `id` int(11) NOT NULL,
  `contract_model_id` int(11) NOT NULL,
  `beneficiary_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT current_timestamp(),
  `alter_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `contract_file_id` int(11) DEFAULT NULL,
  `doc_key` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contract_people`
--

CREATE TABLE `contract_people` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `people_type` enum('Beneficiary','Witness','Contractor') NOT NULL,
  `contract_percentage` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `country`
--

CREATE TABLE `country` (
  `id` int(11) NOT NULL,
  `countryCode` char(3) NOT NULL,
  `countryName` varchar(45) NOT NULL,
  `currencyCode` char(3) DEFAULT NULL,
  `population` int(20) DEFAULT NULL,
  `fipsCode` char(2) DEFAULT NULL,
  `isoNumeric` char(4) DEFAULT NULL,
  `north` varchar(30) DEFAULT NULL,
  `south` varchar(30) DEFAULT NULL,
  `east` varchar(30) DEFAULT NULL,
  `west` varchar(30) DEFAULT NULL,
  `capital` varchar(30) DEFAULT NULL,
  `continentName` varchar(15) DEFAULT NULL,
  `continent` char(2) DEFAULT NULL,
  `areaInSqKm` varchar(20) DEFAULT NULL,
  `isoAlpha3` char(3) DEFAULT NULL,
  `geonameId` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_region`
--

CREATE TABLE `delivery_region` (
  `id` int(11) NOT NULL,
  `region` varchar(255) NOT NULL,
  `people_id` int(11) NOT NULL,
  `deadline` int(3) NOT NULL,
  `retrieve_tax` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_region_city`
--

CREATE TABLE `delivery_region_city` (
  `id` int(11) NOT NULL,
  `delivery_region_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_restriction_material`
--

CREATE TABLE `delivery_restriction_material` (
  `id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `product_material_id` int(11) NOT NULL,
  `restriction_type` enum('delivery_denied','delivery_restricted') NOT NULL DEFAULT 'delivery_denied'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_tax`
--

CREATE TABLE `delivery_tax` (
  `id` int(11) NOT NULL,
  `tax_name` varchar(255) NOT NULL,
  `tax_description` varchar(255) DEFAULT NULL,
  `tax_type` enum('fixed','percentage') NOT NULL,
  `tax_subtype` enum('invoice','kg','order','km') DEFAULT NULL,
  `people_id` int(11) DEFAULT NULL,
  `final_weight` decimal(10,3) DEFAULT NULL,
  `region_origin_id` int(11) DEFAULT NULL,
  `region_destination_id` int(11) DEFAULT NULL,
  `tax_order` int(11) NOT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `minimum_price` decimal(15,2) DEFAULT NULL,
  `optional` tinyint(1) NOT NULL,
  `delivery_tax_group_id` int(11) NOT NULL DEFAULT 1,
  `creation_date` datetime NOT NULL DEFAULT current_timestamp(),
  `alter_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deadline` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_tax_group`
--

CREATE TABLE `delivery_tax_group` (
  `id` int(11) NOT NULL,
  `carrier_id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `group_name` varchar(255) NOT NULL,
  `cubage` int(11) NOT NULL DEFAULT 300,
  `max_height` decimal(10,2) DEFAULT NULL,
  `max_width` decimal(10,2) DEFAULT NULL,
  `max_depth` decimal(10,2) DEFAULT NULL,
  `min_cubage` decimal(12,4) DEFAULT NULL,
  `max_cubage` decimal(12,4) DEFAULT NULL,
  `marketplace` tinyint(1) NOT NULL DEFAULT 1,
  `remote` tinyint(1) NOT NULL DEFAULT 0,
  `website` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `device`
--

CREATE TABLE `device` (
  `id` int(11) NOT NULL,
  `alias` varchar(50) DEFAULT 'Caixa',
  `device` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `device_configs`
--

CREATE TABLE `device_configs` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `configs` longtext NOT NULL CHECK (json_valid(`configs`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discount_coupon`
--

CREATE TABLE `discount_coupon` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `type` enum('percentage','amount') NOT NULL DEFAULT 'percentage',
  `company_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `discount_date` datetime NOT NULL DEFAULT current_timestamp(),
  `discount_start_date` date NOT NULL,
  `discount_end_date` date NOT NULL,
  `config` longtext NOT NULL,
  `value` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `display`
--

CREATE TABLE `display` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `display` varchar(50) NOT NULL,
  `display_type` enum('products','orders') NOT NULL DEFAULT 'products'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `display_queue`
--

CREATE TABLE `display_queue` (
  `id` int(11) NOT NULL,
  `display_id` int(11) NOT NULL,
  `queue_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `district`
--

CREATE TABLE `district` (
  `id` int(11) NOT NULL,
  `district` varchar(255) NOT NULL,
  `city_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `docs`
--

CREATE TABLE `docs` (
  `id` int(11) NOT NULL,
  `register_date` datetime NOT NULL DEFAULT current_timestamp(),
  `type` enum('imposto','declaracao') NOT NULL,
  `name` enum('das','pis','confins') NOT NULL,
  `date_period` date NOT NULL,
  `status_id` int(11) NOT NULL,
  `file_name_guide` varchar(255) DEFAULT NULL,
  `file_name_receipt` varchar(255) DEFAULT NULL,
  `people_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document`
--

CREATE TABLE `document` (
  `id` int(11) NOT NULL,
  `document` bigint(20) NOT NULL,
  `document_type_id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `file_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_type`
--

CREATE TABLE `document_type` (
  `id` int(11) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `people_type` enum('F','J') NOT NULL COMMENT ' Individual or juridical person'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ead_classes`
--

CREATE TABLE `ead_classes` (
  `id` int(11) NOT NULL,
  `classes` varchar(255) NOT NULL,
  `courses_id` int(11) NOT NULL,
  `subjects_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ead_content`
--

CREATE TABLE `ead_content` (
  `id` int(11) NOT NULL,
  `content` varchar(255) NOT NULL,
  `subjects_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ead_exercises`
--

CREATE TABLE `ead_exercises` (
  `id` int(11) NOT NULL,
  `exercise_type` enum('exercise','exam') NOT NULL,
  `content_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ead_exercises_options`
--

CREATE TABLE `ead_exercises_options` (
  `id` int(11) NOT NULL,
  `exercise_id` int(11) NOT NULL,
  `option` varchar(255) NOT NULL,
  `correct` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ead_people_classes`
--

CREATE TABLE `ead_people_classes` (
  `id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `people_type` enum('student','teacher') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ead_sessions`
--

CREATE TABLE `ead_sessions` (
  `id` int(11) NOT NULL,
  `session_type` enum('class','exam') NOT NULL,
  `session` varchar(255) NOT NULL,
  `start_data` datetime DEFAULT NULL,
  `end_data` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ead_sessions_content`
--

CREATE TABLE `ead_sessions_content` (
  `id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ead_student_sessions`
--

CREATE TABLE `ead_student_sessions` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `status_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ead_student_session_responses`
--

CREATE TABLE `ead_student_session_responses` (
  `id` int(11) NOT NULL,
  `student_session_id` int(11) NOT NULL,
  `exercise_id` int(11) NOT NULL,
  `response_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email`
--

CREATE TABLE `email` (
  `id` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `types` varchar(50) DEFAULT NULL,
  `confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `people_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `extra_data`
--

CREATE TABLE `extra_data` (
  `id` int(11) NOT NULL,
  `extra_fields_id` int(11) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `entity_name` varchar(60) NOT NULL,
  `data_value` varchar(255) NOT NULL,
  `source` varchar(64) DEFAULT NULL,
  `dateTime` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `extra_fields`
--

CREATE TABLE `extra_fields` (
  `id` int(11) NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `field_type` varchar(50) NOT NULL,
  `context` varchar(50) NOT NULL,
  `required` tinyint(1) NOT NULL,
  `field_configs` longtext DEFAULT NULL CHECK (json_valid(`field_configs`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `people_id` int(11) NOT NULL,
  `content` longblob NOT NULL,
  `context` varchar(50) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `extension` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hardware`
--

CREATE TABLE `hardware` (
  `id` int(11) NOT NULL,
  `hardware` varchar(50) NOT NULL,
  `hardware_type` varchar(50) DEFAULT 'display',
  `company_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `imports`
--

CREATE TABLE `imports` (
  `id` int(11) NOT NULL,
  `import_type` enum('table','DACTE') NOT NULL DEFAULT 'table',
  `status_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `file_id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `file_format` enum('csv','xml') NOT NULL DEFAULT 'csv',
  `feedback` longtext DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `integration`
--

CREATE TABLE `integration` (
  `id` bigint(20) NOT NULL,
  `queue_status_id` int(11) NOT NULL,
  `body` longtext NOT NULL CHECK (json_valid(`body`)),
  `headers` longtext DEFAULT NULL CHECK (json_valid(`headers`)),
  `queue_name` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `device_id` int(11) DEFAULT NULL,
  `people_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `inventory` varchar(50) NOT NULL,
  `type` enum('sales','internal','consignment','damaged') NOT NULL DEFAULT 'internal',
  `people_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

CREATE TABLE `invoice` (
  `id` int(11) NOT NULL,
  `payer_id` int(11) DEFAULT NULL,
  `portion_number` int(11) NOT NULL,
  `installments` int(11) NOT NULL,
  `installment_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL,
  `invoice_date` datetime NOT NULL DEFAULT current_timestamp(),
  `alter_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `due_date` date NOT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `notified` tinyint(1) NOT NULL DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `invoice_bank_id` varchar(30) DEFAULT NULL,
  `other_informations` longtext NOT NULL CHECK (json_valid(`other_informations`)),
  `source_wallet_id` int(11) DEFAULT NULL,
  `destination_wallet_id` int(11) DEFAULT NULL,
  `payment_type_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_tax`
--

CREATE TABLE `invoice_tax` (
  `id` int(11) NOT NULL,
  `invoice_key` int(11) DEFAULT NULL,
  `invoice_number` int(11) DEFAULT NULL,
  `invoice` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `labels`
--

CREATE TABLE `labels` (
  `id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `carrier_id` int(11) NOT NULL,
  `shipment_id` varchar(255) NOT NULL,
  `order_id` int(11) NOT NULL,
  `cod_barra` varchar(255) NOT NULL,
  `last_mile` varchar(255) NOT NULL,
  `unidade_destino` varchar(255) NOT NULL,
  `posicao` varchar(255) NOT NULL,
  `prioridade` int(11) NOT NULL,
  `seq_volume` int(11) NOT NULL,
  `rota` varchar(255) NOT NULL,
  `rua` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `language`
--

CREATE TABLE `language` (
  `id` int(11) NOT NULL,
  `language` varchar(10) NOT NULL,
  `locked` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `language_country`
--

CREATE TABLE `language_country` (
  `id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `country_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE `log` (
  `id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `row` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `object` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `measure`
--

CREATE TABLE `measure` (
  `id` int(11) NOT NULL,
  `measure` varchar(50) NOT NULL,
  `measure_type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `measure_type`
--

CREATE TABLE `measure_type` (
  `id` int(11) NOT NULL,
  `measure_type` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `menu` varchar(50) NOT NULL,
  `route_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_role`
--

CREATE TABLE `menu_role` (
  `id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messengers`
--

CREATE TABLE `messengers` (
  `id` int(11) NOT NULL,
  `type` enum('whatsapp') NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `online` tinyint(1) NOT NULL,
  `people_id` int(11) NOT NULL,
  `other_informations` longtext DEFAULT NULL CHECK (json_valid(`other_informations`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model`
--

CREATE TABLE `model` (
  `id` int(11) NOT NULL,
  `model` varchar(255) NOT NULL,
  `context` varchar(50) NOT NULL,
  `people_id` int(11) NOT NULL,
  `signer_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `module`
--

CREATE TABLE `module` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `color` varchar(50) NOT NULL DEFAULT '$primary',
  `icon` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `module_component`
--

CREATE TABLE `module_component` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `component` varchar(50) NOT NULL,
  `props` longtext NOT NULL CHECK (json_valid(`props`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `module_product`
--

CREATE TABLE `module_product` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `notification` text NOT NULL,
  `route` varchar(50) NOT NULL,
  `route_id` int(11) NOT NULL,
  `notification_read` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth`
--

CREATE TABLE `oauth` (
  `id` int(11) NOT NULL,
  `app_type` enum('mercado_livre') NOT NULL,
  `user_id` int(11) NOT NULL,
  `refresh_token` varchar(255) DEFAULT NULL,
  `access_token` varchar(255) NOT NULL,
  `company_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_type` varchar(50) DEFAULT 'sale',
  `app` text DEFAULT NULL,
  `discount_coupon_id` int(11) DEFAULT NULL,
  `main_order_id` int(11) DEFAULT NULL,
  `notified` tinyint(1) NOT NULL DEFAULT 0,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `alter_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status_id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `provider_id` int(11) NOT NULL,
  `retrieve_people_id` int(11) DEFAULT NULL,
  `delivery_people_id` int(11) DEFAULT NULL,
  `payer_people_id` int(11) DEFAULT NULL,
  `quote_id` int(11) DEFAULT NULL,
  `address_origin_id` int(11) DEFAULT NULL,
  `address_destination_id` int(11) DEFAULT NULL,
  `retrieve_contact_id` int(11) DEFAULT NULL,
  `delivery_contact_id` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `other_informations` longtext DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `invoice_total` decimal(15,2) DEFAULT NULL,
  `cubage` decimal(12,4) DEFAULT NULL,
  `product_type` text DEFAULT NULL,
  `estimated_parking_date` datetime DEFAULT NULL,
  `parking_date` datetime DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `device_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_invoice`
--

CREATE TABLE `order_invoice` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `real_price` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_invoice_tax`
--

CREATE TABLE `order_invoice_tax` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `invoice_tax_id` int(11) NOT NULL,
  `invoice_type` int(11) NOT NULL,
  `issuer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_log`
--

CREATE TABLE `order_log` (
  `id` int(11) NOT NULL,
  `alter_type` enum('Value','Status','Document') NOT NULL,
  `order_id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `alter_date` datetime NOT NULL DEFAULT current_timestamp(),
  `note` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_logistic`
--

CREATE TABLE `order_logistic` (
  `id` int(11) NOT NULL,
  `origin_provider_id` int(11) DEFAULT NULL,
  `order_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL,
  `estimated_shipping_date` date DEFAULT NULL,
  `shipping_date` date DEFAULT NULL,
  `estimated_arrival_date` date DEFAULT NULL,
  `arrival_date` date DEFAULT NULL,
  `origin_type` int(11) DEFAULT NULL,
  `origin_city_id` int(100) DEFAULT NULL,
  `origin_address` varchar(150) DEFAULT NULL,
  `destination_type` int(11) DEFAULT NULL,
  `destination_city_id` int(100) DEFAULT NULL,
  `destination_address` varchar(150) DEFAULT NULL,
  `destination_provider_id` int(11) DEFAULT NULL,
  `price` float NOT NULL DEFAULT 0,
  `amount_paid` float NOT NULL DEFAULT 0,
  `balance` float NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_modified` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_logistic_surveys`
--

CREATE TABLE `order_logistic_surveys` (
  `id` int(11) NOT NULL,
  `token_url` binary(7) NOT NULL,
  `order_logistic_id` int(11) DEFAULT NULL,
  `professional_id` int(11) DEFAULT NULL,
  `address_id` int(11) DEFAULT NULL,
  `surveyor_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `type_survey` enum('collect','delivery','others') DEFAULT NULL,
  `other_informations` text DEFAULT NULL,
  `belongings_removed` enum('no','yes') DEFAULT NULL,
  `vehicle_km` int(11) DEFAULT NULL,
  `status` enum('pending','complete','canceled') NOT NULL DEFAULT 'pending',
  `comments` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_logistic_surveys_files`
--

CREATE TABLE `order_logistic_surveys_files` (
  `id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `order_logistic_surveys_id` int(11) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `region` enum('front','left_side','right_side','rear','panel','motor','others') DEFAULT NULL,
  `breakdown` enum('none','kneaded','absence','chop','broke','scratched','cracked') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_package`
--

CREATE TABLE `order_package` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `qtd` decimal(10,3) DEFAULT NULL,
  `height` decimal(10,2) DEFAULT NULL,
  `width` decimal(10,2) DEFAULT NULL,
  `depth` decimal(10,2) DEFAULT NULL,
  `weight` decimal(10,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_product`
--

CREATE TABLE `order_product` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `in_inventory_id` int(11) DEFAULT NULL,
  `out_inventory_id` int(11) DEFAULT NULL,
  `product_group_id` int(11) DEFAULT NULL,
  `parent_product_id` int(11) DEFAULT NULL,
  `order_product_id` int(11) DEFAULT NULL,
  `quantity` float NOT NULL,
  `price` float NOT NULL,
  `total` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_product_queue`
--

CREATE TABLE `order_product_queue` (
  `id` int(11) NOT NULL,
  `queue_id` int(11) DEFAULT NULL,
  `order_product_id` int(11) NOT NULL,
  `priority` enum('Default','Priority','Emergency') NOT NULL,
  `status_id` int(11) NOT NULL,
  `register_time` datetime NOT NULL DEFAULT current_timestamp(),
  `update_time` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_tracking`
--

CREATE TABLE `order_tracking` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `system_type` varchar(50) NOT NULL,
  `notified` tinyint(4) NOT NULL DEFAULT 0,
  `tracking_status` int(11) DEFAULT NULL,
  `data_hora` varchar(50) DEFAULT NULL,
  `dominio` varchar(50) DEFAULT NULL,
  `filial` varchar(50) DEFAULT NULL,
  `cidade` varchar(50) DEFAULT NULL,
  `ocorrencia` varchar(255) DEFAULT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `data_hora_efetiva` varchar(50) DEFAULT NULL,
  `nome_recebedor` varchar(100) DEFAULT NULL,
  `nro_doc_recebedor` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package`
--

CREATE TABLE `package` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_modules`
--

CREATE TABLE `package_modules` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `users` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `particulars`
--

CREATE TABLE `particulars` (
  `id` int(11) NOT NULL,
  `particulars_type_id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `particular_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `particulars_type`
--

CREATE TABLE `particulars_type` (
  `id` int(11) NOT NULL,
  `type_value` varchar(255) NOT NULL,
  `field_type` varchar(255) NOT NULL,
  `context` varchar(255) NOT NULL,
  `required` tinyint(1) NOT NULL,
  `field_configs` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_type`
--

CREATE TABLE `payment_type` (
  `id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `payment_type` varchar(50) NOT NULL,
  `frequency` enum('monthly','daily','weekly','single') NOT NULL,
  `installments` enum('single','split') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `people`
--

CREATE TABLE `people` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `alias` varchar(64) NOT NULL,
  `register_date` datetime NOT NULL DEFAULT current_timestamp(),
  `enable` tinyint(1) NOT NULL,
  `people_type` enum('F','J') NOT NULL COMMENT ' Individual or juridical person',
  `image_id` int(11) DEFAULT NULL,
  `background_image` int(11) DEFAULT NULL,
  `alternative_image` int(11) DEFAULT NULL,
  `language_id` int(11) NOT NULL,
  `foundation_date` datetime DEFAULT NULL,
  `other_informations` longtext DEFAULT NULL,
  `source` int(11) DEFAULT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `subsector_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `people_domain`
--

CREATE TABLE `people_domain` (
  `id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `theme_id` int(11) DEFAULT NULL,
  `domain_type` enum('API','APP','ERP','SHOP','WEBSITE') NOT NULL DEFAULT 'ERP'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `people_link`
--

CREATE TABLE `people_link` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `link_type` varchar(50) NOT NULL,
  `comission` decimal(15,2) DEFAULT NULL,
  `minimum_comission` int(11) NOT NULL DEFAULT 2000,
  `enable` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `people_order`
--

CREATE TABLE `people_order` (
  `id` int(11) NOT NULL,
  `people_client_id` int(11) NOT NULL,
  `order_value` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `people_package`
--

CREATE TABLE `people_package` (
  `id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `people_procurator`
--

CREATE TABLE `people_procurator` (
  `id` int(11) NOT NULL,
  `procurator_id` int(11) NOT NULL,
  `grantor_id` int(11) NOT NULL,
  `muniment_signature_id` int(11) NOT NULL,
  `enable` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `people_role`
--

CREATE TABLE `people_role` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `people_support`
--

CREATE TABLE `people_support` (
  `id` int(11) NOT NULL,
  `support_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `commission` decimal(15,2) DEFAULT NULL,
  `enable` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `phone`
--

CREATE TABLE `phone` (
  `id` int(11) NOT NULL,
  `ddi` smallint(5) UNSIGNED NOT NULL,
  `ddd` smallint(5) UNSIGNED NOT NULL,
  `phone` int(10) UNSIGNED NOT NULL,
  `confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `people_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `product` varchar(255) NOT NULL,
  `queue_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `sku` varchar(32) DEFAULT NULL,
  `type` enum('manufactured','custom','product','service','component','feedstock','package') NOT NULL DEFAULT 'product',
  `price` float NOT NULL DEFAULT 0,
  `product_unity_id` int(11) NOT NULL,
  `product_condition` enum('new','used','recondicioned') NOT NULL DEFAULT 'new',
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `default_out_inventory_id` int(11) DEFAULT NULL,
  `default_in_inventory_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_category`
--

CREATE TABLE `product_category` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_file`
--

CREATE TABLE `product_file` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_group`
--

CREATE TABLE `product_group` (
  `id` int(11) NOT NULL,
  `parent_product_id` int(11) NOT NULL,
  `product_group` varchar(255) NOT NULL,
  `price_calculation` enum('sum','average','biggest','free') NOT NULL DEFAULT 'sum',
  `required` tinyint(1) NOT NULL DEFAULT 0,
  `minimum` float NOT NULL,
  `maximum` float NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `group_order` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_group_product`
--

CREATE TABLE `product_group_product` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_group_id` int(11) NOT NULL,
  `product_type` enum('feedstock','component','package') NOT NULL,
  `product_child_id` int(11) NOT NULL,
  `quantity` decimal(10,3) DEFAULT NULL,
  `price` float NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_inventory`
--

CREATE TABLE `product_inventory` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `available` float NOT NULL,
  `sales` float NOT NULL,
  `ordered` float NOT NULL,
  `transit` float NOT NULL,
  `minimum` float NOT NULL,
  `maximum` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_material`
--

CREATE TABLE `product_material` (
  `id` int(11) NOT NULL,
  `material` varchar(500) NOT NULL,
  `revised` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_unity`
--

CREATE TABLE `product_unity` (
  `id` int(11) NOT NULL,
  `product_unit` varchar(3) NOT NULL,
  `unit_type` enum('I','F') NOT NULL DEFAULT 'I' COMMENT 'Integer, Fractioned'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `queue`
--

CREATE TABLE `queue` (
  `id` int(11) NOT NULL,
  `queue` varchar(50) NOT NULL,
  `status_in_id` int(11) NOT NULL,
  `status_working_id` int(11) NOT NULL,
  `status_out_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quote`
--

CREATE TABLE `quote` (
  `id` int(11) NOT NULL,
  `quote_date` datetime NOT NULL DEFAULT current_timestamp(),
  `ip` varchar(15) DEFAULT NULL,
  `internal_ip` varchar(15) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `provider_id` int(11) NOT NULL,
  `carrier_id` int(11) NOT NULL,
  `city_origin_id` int(11) NOT NULL,
  `city_destination_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `deadline` int(11) NOT NULL,
  `total` decimal(15,2) DEFAULT NULL,
  `denied` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quote_detail`
--

CREATE TABLE `quote_detail` (
  `id` int(11) NOT NULL,
  `quote_id` int(11) NOT NULL,
  `delivery_tax_id` int(11) DEFAULT NULL,
  `tax_name` varchar(255) NOT NULL,
  `tax_description` varchar(255) DEFAULT NULL,
  `tax_type` enum('fixed','percentage') NOT NULL,
  `tax_subtype` enum('invoice','kg','order','km') DEFAULT NULL,
  `final_weight` decimal(10,3) DEFAULT NULL,
  `region_origin_id` int(11) DEFAULT NULL,
  `region_destination_id` int(11) DEFAULT NULL,
  `tax_order` int(11) NOT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `minimum_price` decimal(15,2) DEFAULT NULL,
  `optional` tinyint(1) NOT NULL,
  `price_calculated` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rating`
--

CREATE TABLE `rating` (
  `id` int(11) NOT NULL,
  `rating` enum('1','2','3','4','5') NOT NULL DEFAULT '5',
  `rating_type` enum('Confidence','Speed','Quality','Attendance') NOT NULL,
  `order_rated` int(11) DEFAULT NULL,
  `people_rated` int(11) NOT NULL,
  `people_evaluator` int(11) NOT NULL,
  `rating_date` datetime NOT NULL DEFAULT current_timestamp(),
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `retrieve`
--

CREATE TABLE `retrieve` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `retrieve_number` int(11) NOT NULL,
  `retrieve_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL,
  `people_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `route` varchar(50) NOT NULL,
  `color` varchar(50) NOT NULL,
  `icon` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seo_url`
--

CREATE TABLE `seo_url` (
  `id` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `city_origin` int(11) NOT NULL,
  `city_destination` int(11) NOT NULL,
  `weight` float NOT NULL DEFAULT 1,
  `order_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_invoice_tax`
--

CREATE TABLE `service_invoice_tax` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `invoice_tax_id` int(11) NOT NULL,
  `invoice_type` int(11) NOT NULL,
  `issuer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` char(32) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL,
  `modified` int(11) DEFAULT NULL,
  `lifetime` int(11) DEFAULT NULL,
  `data` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `spool`
--

CREATE TABLE `spool` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `register_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `state`
--

CREATE TABLE `state` (
  `id` int(11) NOT NULL,
  `cod_ibge` int(11) DEFAULT NULL,
  `state` varchar(50) NOT NULL,
  `country_id` int(11) NOT NULL,
  `UF` varchar(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

CREATE TABLE `status` (
  `id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `context` enum('print','connections','integration','order','support','relationship','invoice','docs','logistic','display','contract','proposal') NOT NULL DEFAULT 'order',
  `real_status` enum('open','pending','canceled','closed') NOT NULL DEFAULT 'open',
  `visibility` enum('public','private') NOT NULL DEFAULT 'public',
  `notify` tinyint(1) NOT NULL,
  `system` tinyint(1) NOT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#000000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `street`
--

CREATE TABLE `street` (
  `id` int(11) NOT NULL,
  `street` varchar(255) NOT NULL,
  `cep_id` int(10) NOT NULL,
  `district_id` int(11) NOT NULL,
  `confirmed` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `task_type` enum('support','relationship') NOT NULL DEFAULT 'support',
  `name` varchar(50) DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `registered_by_id` int(11) NOT NULL,
  `task_for_id` int(11) DEFAULT NULL,
  `provider_id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `task_status_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `reason_id` int(11) DEFAULT NULL,
  `criticality_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `alter_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `announce` longtext DEFAULT NULL CHECK (json_valid(`announce`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_interations`
--

CREATE TABLE `task_interations` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `visibility` enum('private','public') NOT NULL DEFAULT 'private',
  `body` longtext DEFAULT NULL,
  `registered_by_id` int(11) NOT NULL,
  `file_id` int(11) DEFAULT NULL,
  `task_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `notified` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tax`
--

CREATE TABLE `tax` (
  `id` int(11) NOT NULL,
  `tax_name` varchar(255) NOT NULL,
  `tax_type` enum('fixed','percentage') NOT NULL,
  `tax_subtype` enum('invoice','kg','order') DEFAULT NULL,
  `people_id` int(11) NOT NULL,
  `state_origin_id` int(11) NOT NULL,
  `state_destination_id` int(11) NOT NULL,
  `tax_order` int(11) NOT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `minimum_price` decimal(15,2) DEFAULT NULL,
  `optional` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `theme`
--

CREATE TABLE `theme` (
  `id` int(11) NOT NULL,
  `theme` varchar(50) NOT NULL DEFAULT 'Default',
  `background` int(11) NOT NULL,
  `colors` longtext NOT NULL CHECK (json_valid(`colors`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `translate`
--

CREATE TABLE `translate` (
  `id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `store` varchar(64) NOT NULL,
  `type` varchar(64) NOT NULL,
  `lang_id` int(11) NOT NULL,
  `translate_key` varchar(64) NOT NULL,
  `translate` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `hash` varchar(255) NOT NULL,
  `oauth_user` varchar(20) DEFAULT NULL,
  `oauth_hash` varchar(40) DEFAULT NULL,
  `lost_password` varchar(60) DEFAULT NULL,
  `api_key` varchar(60) NOT NULL,
  `people_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallet`
--

CREATE TABLE `wallet` (
  `id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `wallet` varchar(50) NOT NULL,
  `balance` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallet_payment_type`
--

CREATE TABLE `wallet_payment_type` (
  `id` int(11) NOT NULL,
  `wallet_id` int(11) NOT NULL,
  `payment_type_id` int(11) NOT NULL,
  `payment_code` varchar(80) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `address`
--
ALTER TABLE `address`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id_3` (`people_id`,`number`,`street_id`,`complement`) USING BTREE,
  ADD KEY `user_id` (`people_id`),
  ADD KEY `cep_id` (`street_id`),
  ADD KEY `user_id_2` (`people_id`,`nickname`) USING BTREE;

--
-- Indexes for table `card`
--
ALTER TABLE `card`
  ADD PRIMARY KEY (`id`),
  ADD KEY `people_id` (`people_id`);

--
-- Indexes for table `car_manufacturer`
--
ALTER TABLE `car_manufacturer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `car_model`
--
ALTER TABLE `car_model`
  ADD PRIMARY KEY (`id`),
  ADD KEY `car_manufacturer_id` (`car_manufacturer_id`);

--
-- Indexes for table `car_year_price`
--
ALTER TABLE `car_year_price`
  ADD PRIMARY KEY (`id`),
  ADD KEY `car_manufacturer_id` (`car_manufacturer_id`),
  ADD KEY `car_model_id` (`car_model_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `category_ibfk_2` (`parent_id`);

--
-- Indexes for table `category_file`
--
ALTER TABLE `category_file`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_id` (`category_id`,`file_id`),
  ADD KEY `file_id` (`file_id`);

--
-- Indexes for table `cep`
--
ALTER TABLE `cep`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `CEP` (`cep`);

--
-- Indexes for table `city`
--
ALTER TABLE `city`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `city` (`city`,`state_id`),
  ADD UNIQUE KEY `cod_ibge` (`cod_ibge`),
  ADD KEY `state_id` (`state_id`),
  ADD KEY `seo` (`seo`);

--
-- Indexes for table `cms`
--
ALTER TABLE `cms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `people_domain_id` (`people_domain_id`),
  ADD KEY `cms_type_id` (`cms_type`) USING BTREE;

--
-- Indexes for table `cms_section`
--
ALTER TABLE `cms_section`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`section_id`),
  ADD KEY `cms_id` (`cms_id`);

--
-- Indexes for table `cms_section_component`
--
ALTER TABLE `cms_section_component`
  ADD PRIMARY KEY (`id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `component_id` (`component_id`);

--
-- Indexes for table `company_document`
--
ALTER TABLE `company_document`
  ADD PRIMARY KEY (`id`),
  ADD KEY `people_id` (`people_id`),
  ADD KEY `documentType_id` (`document_type_id`);

--
-- Indexes for table `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `people_id` (`people_id`,`config_key`,`module_id`) USING BTREE,
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `connections`
--
ALTER TABLE `connections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone_id` (`phone_id`,`channel`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `people_id` (`people_id`);

--
-- Indexes for table `contract`
--
ALTER TABLE `contract`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_model_id` (`contract_model_id`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `beneficiary_id` (`beneficiary_id`),
  ADD KEY `contract_file_id` (`contract_file_id`);

--
-- Indexes for table `contract_people`
--
ALTER TABLE `contract_people`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_id` (`contract_id`),
  ADD KEY `people_id` (`people_id`);

--
-- Indexes for table `country`
--
ALTER TABLE `country`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `countryCode` (`countryCode`);

--
-- Indexes for table `delivery_region`
--
ALTER TABLE `delivery_region`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `region` (`region`,`people_id`),
  ADD KEY `people_id` (`people_id`);

--
-- Indexes for table `delivery_region_city`
--
ALTER TABLE `delivery_region_city`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `delivery_region_id` (`delivery_region_id`,`city_id`) USING BTREE,
  ADD KEY `city_id` (`city_id`) USING BTREE;

--
-- Indexes for table `delivery_restriction_material`
--
ALTER TABLE `delivery_restriction_material`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `people_id` (`people_id`,`product_material_id`),
  ADD KEY `product_material_id` (`product_material_id`);

--
-- Indexes for table `delivery_tax`
--
ALTER TABLE `delivery_tax`
  ADD PRIMARY KEY (`id`),
  ADD KEY `people_id` (`people_id`),
  ADD KEY `region_destination_id` (`region_destination_id`) USING BTREE,
  ADD KEY `region_origin_id` (`region_origin_id`) USING BTREE,
  ADD KEY `delivery_tax_group_id` (`delivery_tax_group_id`);

--
-- Indexes for table `delivery_tax_group`
--
ALTER TABLE `delivery_tax_group`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `group_name` (`group_name`,`carrier_id`) USING BTREE,
  ADD KEY `carrier_id` (`carrier_id`);

--
-- Indexes for table `device`
--
ALTER TABLE `device`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device` (`device`);

--
-- Indexes for table `device_configs`
--
ALTER TABLE `device_configs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device_id` (`device_id`,`people_id`),
  ADD KEY `people_id` (`people_id`);

--
-- Indexes for table `discount_coupon`
--
ALTER TABLE `discount_coupon`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `creator_id` (`creator_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `display`
--
ALTER TABLE `display`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `display_queue`
--
ALTER TABLE `display_queue`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hardware_id` (`display_id`,`queue_id`),
  ADD KEY `queue_id` (`queue_id`);

--
-- Indexes for table `district`
--
ALTER TABLE `district`
  ADD PRIMARY KEY (`id`),
  ADD KEY `city_id` (`city_id`);

--
-- Indexes for table `docs`
--
ALTER TABLE `docs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `people_id` (`people_id`),
  ADD KEY `status_id` (`status_id`);

--
-- Indexes for table `document`
--
ALTER TABLE `document`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `doc` (`document`,`document_type_id`),
  ADD UNIQUE KEY `document` (`document_type_id`,`people_id`) USING BTREE,
  ADD KEY `type_2` (`document_type_id`),
  ADD KEY `image_id` (`file_id`),
  ADD KEY `type` (`people_id`,`document_type_id`) USING BTREE;

--
-- Indexes for table `document_type`
--
ALTER TABLE `document_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ead_classes`
--
ALTER TABLE `ead_classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `courses_id` (`courses_id`),
  ADD KEY `subjects_id` (`subjects_id`);

--
-- Indexes for table `ead_content`
--
ALTER TABLE `ead_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subjects_id` (`subjects_id`),
  ADD KEY `file_id` (`file_id`);

--
-- Indexes for table `ead_exercises`
--
ALTER TABLE `ead_exercises`
  ADD PRIMARY KEY (`id`),
  ADD KEY `content_id` (`content_id`),
  ADD KEY `file_id` (`file_id`);

--
-- Indexes for table `ead_exercises_options`
--
ALTER TABLE `ead_exercises_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exercise_id` (`exercise_id`);

--
-- Indexes for table `ead_people_classes`
--
ALTER TABLE `ead_people_classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `people_id` (`people_id`);

--
-- Indexes for table `ead_sessions`
--
ALTER TABLE `ead_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ead_sessions_content`
--
ALTER TABLE `ead_sessions_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `content_id` (`content_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `ead_student_sessions`
--
ALTER TABLE `ead_student_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `ead_student_session_responses`
--
ALTER TABLE `ead_student_session_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exercise_id` (`exercise_id`),
  ADD KEY `response_id` (`response_id`),
  ADD KEY `student_session_id` (`student_session_id`);

--
-- Indexes for table `email`
--
ALTER TABLE `email`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `IDX_E7927C743147C936` (`people_id`);

--
-- Indexes for table `extra_data`
--
ALTER TABLE `extra_data`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `extra_fields_id` (`extra_fields_id`,`entity_id`,`entity_name`,`data_value`) USING BTREE,
  ADD KEY `people_id` (`entity_id`),
  ADD KEY `particulars_type_id` (`extra_fields_id`);

--
-- Indexes for table `extra_fields`
--
ALTER TABLE `extra_fields`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `people_id` (`people_id`);

--
-- Indexes for table `hardware`
--
ALTER TABLE `hardware`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `imports`
--
ALTER TABLE `imports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `file_id` (`file_id`),
  ADD KEY `people_id` (`people_id`),
  ADD KEY `status_id` (`status_id`);

--
-- Indexes for table `integration`
--
ALTER TABLE `integration`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_75EA56E0FB7336F0` (`queue_name`),
  ADD KEY `queue_status_id` (`queue_status_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `device_id` (`device_id`),
  ADD KEY `people_id` (`people_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `people_id` (`people_id`);

--
-- Indexes for table `invoice`
--
ALTER TABLE `invoice`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_status_id` (`status_id`),
  ADD KEY `invoice_ibfk_2` (`category_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `wallet_id` (`destination_wallet_id`),
  ADD KEY `payment_type_id` (`payment_type_id`),
  ADD KEY `installment_id` (`installment_id`),
  ADD KEY `source_wallet_id` (`source_wallet_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `payer_id` (`payer_id`),
  ADD KEY `invoice_ibfk_11` (`device_id`);

--
-- Indexes for table `invoice_tax`
--
ALTER TABLE `invoice_tax`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_number` (`invoice_number`);

--
-- Indexes for table `labels`
--
ALTER TABLE `labels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `people_id` (`people_id`),
  ADD KEY `carrier_id` (`carrier_id`),
  ADD KEY `labels_ibfk_3` (`order_id`);

--
-- Indexes for table `language`
--
ALTER TABLE `language`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `language` (`language`);

--
-- Indexes for table `language_country`
--
ALTER TABLE `language_country`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `language_id` (`language_id`,`country_id`),
  ADD KEY `country_id` (`country_id`);

--
-- Indexes for table `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `measure`
--
ALTER TABLE `measure`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `measure` (`measure`),
  ADD KEY `measuretype_id` (`measure_type_id`);

--
-- Indexes for table `measure_type`
--
ALTER TABLE `measure_type`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `measure_type` (`measure_type`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `menu_ibfk_3` (`route_id`);

--
-- Indexes for table `menu_role`
--
ALTER TABLE `menu_role`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `menu_id` (`menu_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `messengers`
--
ALTER TABLE `messengers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type` (`type`,`identifier`),
  ADD KEY `people_id` (`people_id`);

--
-- Indexes for table `model`
--
ALTER TABLE `model`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `model` (`model`,`context`,`people_id`) USING BTREE,
  ADD KEY `category_id` (`category_id`),
  ADD KEY `people_id` (`people_id`),
  ADD KEY `signer_id` (`signer_id`),
  ADD KEY `file_id` (`file_id`);

--
-- Indexes for table `module`
--
ALTER TABLE `module`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UX_MODULE_NAME` (`name`);

--
-- Indexes for table `module_component`
--
ALTER TABLE `module_component`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `module_product`
--
ALTER TABLE `module_product`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_id` (`product_id`,`module_id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `people_id` (`people_id`);

--
-- Indexes for table `oauth`
--
ALTER TABLE `oauth`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `app_type` (`app_type`,`user_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `discount_id` (`discount_coupon_id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `adress_origin_id` (`address_origin_id`),
  ADD KEY `adress_destination_id` (`address_destination_id`),
  ADD KEY `retrieve_contact_id` (`retrieve_contact_id`),
  ADD KEY `delivery_contact_id` (`delivery_contact_id`),
  ADD KEY `retrieve_people_id` (`retrieve_people_id`),
  ADD KEY `delivery_people_id` (`delivery_people_id`),
  ADD KEY `payer_people_id` (`payer_people_id`),
  ADD KEY `order_status_id` (`status_id`),
  ADD KEY `client_id` (`client_id`) USING BTREE,
  ADD KEY `order_date` (`order_date`),
  ADD KEY `alter_date` (`alter_date`),
  ADD KEY `quote_id` (`quote_id`,`provider_id`) USING BTREE,
  ADD KEY `notified` (`notified`),
  ADD KEY `main_order_id` (`main_order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `orders_ibfk_16` (`device_id`);

--
-- Indexes for table `order_invoice`
--
ALTER TABLE `order_invoice`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`,`invoice_id`) USING BTREE,
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `order_invoice_tax`
--
ALTER TABLE `order_invoice_tax`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`,`invoice_tax_id`) USING BTREE,
  ADD UNIQUE KEY `order_id_2` (`issuer_id`,`invoice_type`,`order_id`) USING BTREE,
  ADD KEY `invoice_tax_id` (`invoice_tax_id`) USING BTREE;

--
-- Indexes for table `order_log`
--
ALTER TABLE `order_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `people_id` (`people_id`);

--
-- Indexes for table `order_logistic`
--
ALTER TABLE `order_logistic`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `provider_id` (`origin_provider_id`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `order_logistic__ibfk_4` (`destination_provider_id`),
  ADD KEY `order_logistic__ibfk_5` (`created_by`),
  ADD KEY `destination_type` (`destination_type`),
  ADD KEY `origin_type` (`origin_type`),
  ADD KEY `origin_city_id` (`origin_city_id`),
  ADD KEY `destination_city_id` (`destination_city_id`);

--
-- Indexes for table `order_logistic_surveys`
--
ALTER TABLE `order_logistic_surveys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `token_url` (`token_url`,`id`),
  ADD KEY `order_logistic_surveys_order_logistic_id_fk` (`order_logistic_id`),
  ADD KEY `tasks_surveys_address_id_fk` (`address_id`),
  ADD KEY `tasks_surveys_people_id_fk` (`professional_id`),
  ADD KEY `tasks_surveys_people_id_fk_2` (`surveyor_id`);

--
-- Indexes for table `order_logistic_surveys_files`
--
ALTER TABLE `order_logistic_surveys_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tasks_surveys_files_tasks_surveys_id_fk` (`order_logistic_surveys_id`);

--
-- Indexes for table `order_package`
--
ALTER TABLE `order_package`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `order_product`
--
ALTER TABLE `order_product`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orders_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `parent_product_id` (`parent_product_id`),
  ADD KEY `parent_order_product_id` (`order_product_id`),
  ADD KEY `product_group_id` (`product_group_id`),
  ADD KEY `inventory_id` (`out_inventory_id`),
  ADD KEY `in_inventory_id` (`in_inventory_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_product_queue`
--
ALTER TABLE `order_product_queue`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_product_id` (`order_product_id`) USING BTREE,
  ADD KEY `status_id` (`status_id`),
  ADD KEY `queue_id` (`queue_id`);

--
-- Indexes for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `package`
--
ALTER TABLE `package`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `package_modules`
--
ALTER TABLE `package_modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `package_id` (`package_id`,`module_id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `particulars`
--
ALTER TABLE `particulars`
  ADD PRIMARY KEY (`id`),
  ADD KEY `people_id` (`people_id`),
  ADD KEY `particulars_type_id` (`particulars_type_id`);

--
-- Indexes for table `particulars_type`
--
ALTER TABLE `particulars_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_type`
--
ALTER TABLE `payment_type`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `people_id` (`people_id`,`payment_type`);

--
-- Indexes for table `people`
--
ALTER TABLE `people`
  ADD PRIMARY KEY (`id`),
  ADD KEY `language_id` (`language_id`),
  ADD KEY `alternative_image` (`background_image`),
  ADD KEY `image_id` (`image_id`) USING BTREE,
  ADD KEY `alternative_image_2` (`alternative_image`),
  ADD KEY `sector_id` (`sector_id`),
  ADD KEY `subsector_id` (`subsector_id`);

--
-- Indexes for table `people_domain`
--
ALTER TABLE `people_domain`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `domain` (`domain`),
  ADD KEY `people_id` (`people_id`),
  ADD KEY `theme_id` (`theme_id`);

--
-- Indexes for table `people_link`
--
ALTER TABLE `people_link`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `franchisee_id` (`company_id`,`people_id`,`link_type`) USING BTREE,
  ADD KEY `franchisor_id` (`people_id`) USING BTREE;

--
-- Indexes for table `people_order`
--
ALTER TABLE `people_order`
  ADD PRIMARY KEY (`id`),
  ADD KEY `people_client_id` (`people_client_id`);

--
-- Indexes for table `people_package`
--
ALTER TABLE `people_package`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `people_id` (`people_id`);

--
-- Indexes for table `people_procurator`
--
ALTER TABLE `people_procurator`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `client_id` (`procurator_id`,`grantor_id`),
  ADD UNIQUE KEY `muniment_signature_id` (`muniment_signature_id`),
  ADD KEY `provider_id` (`grantor_id`);

--
-- Indexes for table `people_role`
--
ALTER TABLE `people_role`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`people_id`,`role_id`),
  ADD KEY `people_id` (`people_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `people_support`
--
ALTER TABLE `people_support`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `support_id` (`support_id`,`company_id`) USING BTREE,
  ADD KEY `provider_id` (`company_id`);

--
-- Indexes for table `phone`
--
ALTER TABLE `phone`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_E7927C743147C936` (`people_id`),
  ADD KEY `phone` (`phone`,`ddd`,`people_id`) USING BTREE;

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`sku`),
  ADD KEY `product_ unit_id` (`product_unity_id`),
  ADD KEY `queue_id` (`queue_id`),
  ADD KEY `out_inventory_id` (`default_out_inventory_id`) USING BTREE,
  ADD KEY `in_inventory_id` (`default_in_inventory_id`) USING BTREE;

--
-- Indexes for table `product_category`
--
ALTER TABLE `product_category`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_id` (`product_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_file`
--
ALTER TABLE `product_file`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_id` (`product_id`,`file_id`),
  ADD KEY `file_id` (`file_id`);

--
-- Indexes for table `product_group`
--
ALTER TABLE `product_group`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_parent_id` (`parent_product_id`);

--
-- Indexes for table `product_group_product`
--
ALTER TABLE `product_group_product`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_group` (`product_group_id`,`product_type`,`product_child_id`,`product_id`) USING BTREE,
  ADD KEY `product_id` (`product_child_id`),
  ADD KEY `product_id_2` (`product_id`);

--
-- Indexes for table `product_inventory`
--
ALTER TABLE `product_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `inventory_id` (`inventory_id`,`product_id`) USING BTREE,
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_material`
--
ALTER TABLE `product_material`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `material` (`material`);

--
-- Indexes for table `product_unity`
--
ALTER TABLE `product_unity`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_unit` (`product_unit`);

--
-- Indexes for table `queue`
--
ALTER TABLE `queue`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `queue` (`queue`,`company_id`) USING BTREE,
  ADD KEY `company_id` (`company_id`),
  ADD KEY `status_in_id` (`status_in_id`),
  ADD KEY `status_out_id` (`status_out_id`),
  ADD KEY `status_working_id` (`status_working_id`);

--
-- Indexes for table `quote`
--
ALTER TABLE `quote`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `city_origin_id` (`city_origin_id`),
  ADD KEY `city_destination_id` (`city_destination_id`),
  ADD KEY `carrier_id` (`carrier_id`);

--
-- Indexes for table `quote_detail`
--
ALTER TABLE `quote_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `region_destination_id` (`region_destination_id`) USING BTREE,
  ADD KEY `region_origin_id` (`region_origin_id`) USING BTREE,
  ADD KEY `delivery_tax_id` (`delivery_tax_id`),
  ADD KEY `quote` (`quote_id`);

--
-- Indexes for table `rating`
--
ALTER TABLE `rating`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_rated` (`order_rated`,`rating_type`,`people_evaluator`,`people_rated`) USING BTREE,
  ADD KEY `people_evaluator` (`people_evaluator`),
  ADD KEY `rating` (`rating`),
  ADD KEY `rating_type` (`rating_type`),
  ADD KEY `people_rated` (`people_rated`);

--
-- Indexes for table `retrieve`
--
ALTER TABLE `retrieve`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id`),
  ADD KEY `people_id` (`people_id`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `route` (`route`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `seo_url`
--
ALTER TABLE `seo_url`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `url` (`url`) USING BTREE,
  ADD UNIQUE KEY `city_origin_2` (`city_origin`,`city_destination`,`weight`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `city_origin` (`city_origin`),
  ADD KEY `city_destination` (`city_destination`);

--
-- Indexes for table `service_invoice_tax`
--
ALTER TABLE `service_invoice_tax`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_id` (`invoice_id`,`invoice_tax_id`) USING BTREE,
  ADD UNIQUE KEY `invoice_type` (`issuer_id`,`invoice_type`,`invoice_id`) USING BTREE,
  ADD KEY `invoice_tax_id` (`invoice_tax_id`) USING BTREE;

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `spool`
--
ALTER TABLE `spool`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`),
  ADD KEY `file_id` (`file_id`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `state`
--
ALTER TABLE `state`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UF` (`UF`),
  ADD UNIQUE KEY `cod_ibge` (`cod_ibge`),
  ADD KEY `country_id` (`country_id`);

--
-- Indexes for table `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `status` (`status`,`context`) USING BTREE,
  ADD KEY `real_status` (`real_status`);

--
-- Indexes for table `street`
--
ALTER TABLE `street`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `street_2` (`street`,`district_id`),
  ADD KEY `country_id` (`district_id`),
  ADD KEY `cep` (`cep_id`) USING BTREE,
  ADD KEY `street` (`street`) USING BTREE;

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registered_by_id` (`registered_by_id`),
  ADD KEY `task_for_id` (`task_for_id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `reason_id` (`reason_id`),
  ADD KEY `criticality_id` (`criticality_id`),
  ADD KEY `tasks_ibfk_5` (`task_status_id`);

--
-- Indexes for table `task_interations`
--
ALTER TABLE `task_interations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_interations_ibfk_1` (`registered_by_id`),
  ADD KEY `task_interations_ibfk_2` (`file_id`),
  ADD KEY `task_interations_ibfk_3` (`task_id`);

--
-- Indexes for table `tax`
--
ALTER TABLE `tax`
  ADD PRIMARY KEY (`id`),
  ADD KEY `people_id` (`people_id`),
  ADD KEY `region_destination_id` (`state_destination_id`) USING BTREE,
  ADD KEY `region_origin_id` (`state_origin_id`) USING BTREE;

--
-- Indexes for table `theme`
--
ALTER TABLE `theme`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `theme` (`theme`),
  ADD KEY `background` (`background`);

--
-- Indexes for table `translate`
--
ALTER TABLE `translate`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `language_id` (`lang_id`,`translate_key`,`people_id`,`store`,`type`) USING BTREE,
  ADD KEY `translate_key` (`translate_key`),
  ADD KEY `people_id` (`people_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_name` (`username`),
  ADD UNIQUE KEY `api_key` (`api_key`),
  ADD KEY `people_id` (`people_id`);

--
-- Indexes for table `wallet`
--
ALTER TABLE `wallet`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `people_id` (`people_id`,`wallet`);

--
-- Indexes for table `wallet_payment_type`
--
ALTER TABLE `wallet_payment_type`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wallet_id` (`wallet_id`,`payment_type_id`),
  ADD KEY `payment_type_id` (`payment_type_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `address`
--
ALTER TABLE `address`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `card`
--
ALTER TABLE `card`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `car_manufacturer`
--
ALTER TABLE `car_manufacturer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `car_model`
--
ALTER TABLE `car_model`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `car_year_price`
--
ALTER TABLE `car_year_price`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `category_file`
--
ALTER TABLE `category_file`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cep`
--
ALTER TABLE `cep`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `city`
--
ALTER TABLE `city`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cms`
--
ALTER TABLE `cms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cms_section`
--
ALTER TABLE `cms_section`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cms_section_component`
--
ALTER TABLE `cms_section_component`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `company_document`
--
ALTER TABLE `company_document`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `config`
--
ALTER TABLE `config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `connections`
--
ALTER TABLE `connections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contract`
--
ALTER TABLE `contract`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contract_people`
--
ALTER TABLE `contract_people`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `country`
--
ALTER TABLE `country`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_region`
--
ALTER TABLE `delivery_region`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_region_city`
--
ALTER TABLE `delivery_region_city`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_restriction_material`
--
ALTER TABLE `delivery_restriction_material`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_tax`
--
ALTER TABLE `delivery_tax`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_tax_group`
--
ALTER TABLE `delivery_tax_group`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `device`
--
ALTER TABLE `device`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `device_configs`
--
ALTER TABLE `device_configs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `discount_coupon`
--
ALTER TABLE `discount_coupon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `display`
--
ALTER TABLE `display`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `display_queue`
--
ALTER TABLE `display_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `district`
--
ALTER TABLE `district`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `docs`
--
ALTER TABLE `docs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document`
--
ALTER TABLE `document`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_type`
--
ALTER TABLE `document_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ead_classes`
--
ALTER TABLE `ead_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ead_content`
--
ALTER TABLE `ead_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ead_exercises`
--
ALTER TABLE `ead_exercises`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ead_exercises_options`
--
ALTER TABLE `ead_exercises_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ead_people_classes`
--
ALTER TABLE `ead_people_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ead_sessions`
--
ALTER TABLE `ead_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ead_sessions_content`
--
ALTER TABLE `ead_sessions_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ead_student_sessions`
--
ALTER TABLE `ead_student_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ead_student_session_responses`
--
ALTER TABLE `ead_student_session_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email`
--
ALTER TABLE `email`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `extra_data`
--
ALTER TABLE `extra_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `extra_fields`
--
ALTER TABLE `extra_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hardware`
--
ALTER TABLE `hardware`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `imports`
--
ALTER TABLE `imports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `integration`
--
ALTER TABLE `integration`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice`
--
ALTER TABLE `invoice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_tax`
--
ALTER TABLE `invoice_tax`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `labels`
--
ALTER TABLE `labels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `language`
--
ALTER TABLE `language`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `language_country`
--
ALTER TABLE `language_country`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log`
--
ALTER TABLE `log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `measure`
--
ALTER TABLE `measure`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `measure_type`
--
ALTER TABLE `measure_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu_role`
--
ALTER TABLE `menu_role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messengers`
--
ALTER TABLE `messengers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `model`
--
ALTER TABLE `model`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `module`
--
ALTER TABLE `module`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `module_component`
--
ALTER TABLE `module_component`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `module_product`
--
ALTER TABLE `module_product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `oauth`
--
ALTER TABLE `oauth`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_invoice`
--
ALTER TABLE `order_invoice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_invoice_tax`
--
ALTER TABLE `order_invoice_tax`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_log`
--
ALTER TABLE `order_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_logistic`
--
ALTER TABLE `order_logistic`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_logistic_surveys`
--
ALTER TABLE `order_logistic_surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_logistic_surveys_files`
--
ALTER TABLE `order_logistic_surveys_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_package`
--
ALTER TABLE `order_package`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_product`
--
ALTER TABLE `order_product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_product_queue`
--
ALTER TABLE `order_product_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_tracking`
--
ALTER TABLE `order_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package`
--
ALTER TABLE `package`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_modules`
--
ALTER TABLE `package_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `particulars`
--
ALTER TABLE `particulars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `particulars_type`
--
ALTER TABLE `particulars_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_type`
--
ALTER TABLE `payment_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `people`
--
ALTER TABLE `people`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `people_domain`
--
ALTER TABLE `people_domain`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `people_link`
--
ALTER TABLE `people_link`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `people_order`
--
ALTER TABLE `people_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `people_package`
--
ALTER TABLE `people_package`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `people_procurator`
--
ALTER TABLE `people_procurator`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `people_role`
--
ALTER TABLE `people_role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `people_support`
--
ALTER TABLE `people_support`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `phone`
--
ALTER TABLE `phone`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_category`
--
ALTER TABLE `product_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_file`
--
ALTER TABLE `product_file`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_group`
--
ALTER TABLE `product_group`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_group_product`
--
ALTER TABLE `product_group_product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_inventory`
--
ALTER TABLE `product_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_material`
--
ALTER TABLE `product_material`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_unity`
--
ALTER TABLE `product_unity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `queue`
--
ALTER TABLE `queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quote`
--
ALTER TABLE `quote`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quote_detail`
--
ALTER TABLE `quote_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rating`
--
ALTER TABLE `rating`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `retrieve`
--
ALTER TABLE `retrieve`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seo_url`
--
ALTER TABLE `seo_url`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_invoice_tax`
--
ALTER TABLE `service_invoice_tax`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `spool`
--
ALTER TABLE `spool`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `state`
--
ALTER TABLE `state`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `status`
--
ALTER TABLE `status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `street`
--
ALTER TABLE `street`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_interations`
--
ALTER TABLE `task_interations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tax`
--
ALTER TABLE `tax`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `theme`
--
ALTER TABLE `theme`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translate`
--
ALTER TABLE `translate`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallet`
--
ALTER TABLE `wallet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallet_payment_type`
--
ALTER TABLE `wallet_payment_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `address`
--
ALTER TABLE `address`
  ADD CONSTRAINT `address_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `address_ibfk_2` FOREIGN KEY (`street_id`) REFERENCES `street` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `card`
--
ALTER TABLE `card`
  ADD CONSTRAINT `card_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `car_model`
--
ALTER TABLE `car_model`
  ADD CONSTRAINT `car_model_ibfk_1` FOREIGN KEY (`car_manufacturer_id`) REFERENCES `car_manufacturer` (`id`);

--
-- Constraints for table `car_year_price`
--
ALTER TABLE `car_year_price`
  ADD CONSTRAINT `car_year_price_ibfk_1` FOREIGN KEY (`car_manufacturer_id`) REFERENCES `car_manufacturer` (`id`),
  ADD CONSTRAINT `car_year_price_ibfk_2` FOREIGN KEY (`car_model_id`) REFERENCES `car_model` (`id`);

--
-- Constraints for table `category`
--
ALTER TABLE `category`
  ADD CONSTRAINT `category_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `category_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `category_file`
--
ALTER TABLE `category_file`
  ADD CONSTRAINT `category_file_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `category_file_ibfk_2` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `city`
--
ALTER TABLE `city`
  ADD CONSTRAINT `city_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `state` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cms`
--
ALTER TABLE `cms`
  ADD CONSTRAINT `cms_ibfk_1` FOREIGN KEY (`people_domain_id`) REFERENCES `people_domain` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cms_section`
--
ALTER TABLE `cms_section`
  ADD CONSTRAINT `cms_section_ibfk_1` FOREIGN KEY (`cms_id`) REFERENCES `cms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cms_section_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `cms_section` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cms_section_component`
--
ALTER TABLE `cms_section_component`
  ADD CONSTRAINT `cms_section_component_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `cms_section` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cms_section_component_ibfk_2` FOREIGN KEY (`component_id`) REFERENCES `module_component` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `company_document`
--
ALTER TABLE `company_document`
  ADD CONSTRAINT `company_document_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`),
  ADD CONSTRAINT `company_document_ibfk_2` FOREIGN KEY (`document_type_id`) REFERENCES `document_type` (`id`);

--
-- Constraints for table `config`
--
ALTER TABLE `config`
  ADD CONSTRAINT `config_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `config_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `connections`
--
ALTER TABLE `connections`
  ADD CONSTRAINT `connections_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `connections_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `connections_ibfk_3` FOREIGN KEY (`phone_id`) REFERENCES `phone` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `contract`
--
ALTER TABLE `contract`
  ADD CONSTRAINT `contract_ibfk_5` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `contract_ibfk_6` FOREIGN KEY (`contract_model_id`) REFERENCES `model` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `contract_ibfk_7` FOREIGN KEY (`beneficiary_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `contract_ibfk_8` FOREIGN KEY (`contract_file_id`) REFERENCES `files` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `contract_people`
--
ALTER TABLE `contract_people`
  ADD CONSTRAINT `contract_people_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contract` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `contract_people_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `delivery_region`
--
ALTER TABLE `delivery_region`
  ADD CONSTRAINT `delivery_region_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `delivery_region_city`
--
ALTER TABLE `delivery_region_city`
  ADD CONSTRAINT `delivery_region_city_ibfk_1` FOREIGN KEY (`delivery_region_id`) REFERENCES `delivery_region` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `delivery_region_city_ibfk_2` FOREIGN KEY (`city_id`) REFERENCES `city` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `delivery_restriction_material`
--
ALTER TABLE `delivery_restriction_material`
  ADD CONSTRAINT `delivery_restriction_material_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `delivery_restriction_material_ibfk_2` FOREIGN KEY (`product_material_id`) REFERENCES `product_material` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `delivery_tax`
--
ALTER TABLE `delivery_tax`
  ADD CONSTRAINT `delivery_tax_ibfk_1` FOREIGN KEY (`region_origin_id`) REFERENCES `delivery_region` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `delivery_tax_ibfk_2` FOREIGN KEY (`region_destination_id`) REFERENCES `delivery_region` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `delivery_tax_ibfk_3` FOREIGN KEY (`delivery_tax_group_id`) REFERENCES `delivery_tax_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `delivery_tax_group`
--
ALTER TABLE `delivery_tax_group`
  ADD CONSTRAINT `delivery_tax_group_ibfk_1` FOREIGN KEY (`carrier_id`) REFERENCES `people_carrier` (`carrier_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `device_configs`
--
ALTER TABLE `device_configs`
  ADD CONSTRAINT `device_configs_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `device_configs_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `discount_coupon`
--
ALTER TABLE `discount_coupon`
  ADD CONSTRAINT `discount_coupon_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `discount_coupon_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `discount_coupon_ibfk_3` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `display`
--
ALTER TABLE `display`
  ADD CONSTRAINT `display_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `display_queue`
--
ALTER TABLE `display_queue`
  ADD CONSTRAINT `display_queue_ibfk_2` FOREIGN KEY (`queue_id`) REFERENCES `queue` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `display_queue_ibfk_3` FOREIGN KEY (`display_id`) REFERENCES `display` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `district`
--
ALTER TABLE `district`
  ADD CONSTRAINT `district_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `city` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `docs`
--
ALTER TABLE `docs`
  ADD CONSTRAINT `company_ibfk` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `docs_ibfk_1` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `people_ibfk` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `document`
--
ALTER TABLE `document`
  ADD CONSTRAINT `document_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `document_ibfk_2` FOREIGN KEY (`document_type_id`) REFERENCES `document_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ead_classes`
--
ALTER TABLE `ead_classes`
  ADD CONSTRAINT `ead_classes_ibfk_1` FOREIGN KEY (`courses_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ead_classes_ibfk_2` FOREIGN KEY (`subjects_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ead_content`
--
ALTER TABLE `ead_content`
  ADD CONSTRAINT `ead_content_ibfk_1` FOREIGN KEY (`subjects_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ead_content_ibfk_2` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ead_exercises`
--
ALTER TABLE `ead_exercises`
  ADD CONSTRAINT `ead_exercises_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `ead_content` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ead_exercises_ibfk_2` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ead_exercises_options`
--
ALTER TABLE `ead_exercises_options`
  ADD CONSTRAINT `ead_exercises_options_ibfk_1` FOREIGN KEY (`exercise_id`) REFERENCES `ead_exercises` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ead_people_classes`
--
ALTER TABLE `ead_people_classes`
  ADD CONSTRAINT `ead_people_classes_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ead_sessions_content`
--
ALTER TABLE `ead_sessions_content`
  ADD CONSTRAINT `ead_sessions_content_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `ead_content` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ead_sessions_content_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `ead_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ead_student_sessions`
--
ALTER TABLE `ead_student_sessions`
  ADD CONSTRAINT `ead_student_sessions_ibfk_1` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ead_student_sessions_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `ead_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ead_student_session_responses`
--
ALTER TABLE `ead_student_session_responses`
  ADD CONSTRAINT `ead_student_session_responses_ibfk_1` FOREIGN KEY (`exercise_id`) REFERENCES `ead_exercises` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ead_student_session_responses_ibfk_2` FOREIGN KEY (`response_id`) REFERENCES `ead_exercises_options` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ead_student_session_responses_ibfk_3` FOREIGN KEY (`student_session_id`) REFERENCES `ead_student_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `email`
--
ALTER TABLE `email`
  ADD CONSTRAINT `FK_E7927C743147C936` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `extra_data`
--
ALTER TABLE `extra_data`
  ADD CONSTRAINT `extra_data_ibfk_1` FOREIGN KEY (`extra_fields_id`) REFERENCES `extra_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `hardware`
--
ALTER TABLE `hardware`
  ADD CONSTRAINT `hardware_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `imports`
--
ALTER TABLE `imports`
  ADD CONSTRAINT `imports_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`),
  ADD CONSTRAINT `imports_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`),
  ADD CONSTRAINT `imports_ibfk_3` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `integration`
--
ALTER TABLE `integration`
  ADD CONSTRAINT `integration_ibfk_1` FOREIGN KEY (`queue_status_id`) REFERENCES `status` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `integration_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `integration_ibfk_3` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `integration_ibfk_4` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `invoice`
--
ALTER TABLE `invoice`
  ADD CONSTRAINT `invoice_ibfk_1` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `invoice_ibfk_10` FOREIGN KEY (`payer_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `invoice_ibfk_11` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `invoice_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`),
  ADD CONSTRAINT `invoice_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `invoice_ibfk_5` FOREIGN KEY (`destination_wallet_id`) REFERENCES `wallet` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `invoice_ibfk_6` FOREIGN KEY (`payment_type_id`) REFERENCES `payment_type` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `invoice_ibfk_7` FOREIGN KEY (`installment_id`) REFERENCES `invoice` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `invoice_ibfk_8` FOREIGN KEY (`source_wallet_id`) REFERENCES `wallet` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `invoice_ibfk_9` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `language_country`
--
ALTER TABLE `language_country`
  ADD CONSTRAINT `language_country_ibfk_1` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `language_country_ibfk_2` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `log`
--
ALTER TABLE `log`
  ADD CONSTRAINT `log_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `measure`
--
ALTER TABLE `measure`
  ADD CONSTRAINT `measure_ibfk_1` FOREIGN KEY (`measure_type_id`) REFERENCES `measure_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `menu`
--
ALTER TABLE `menu`
  ADD CONSTRAINT `menu_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `menu_ibfk_3` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `menu_role`
--
ALTER TABLE `menu_role`
  ADD CONSTRAINT `menu_role_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `menu_role_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `messengers`
--
ALTER TABLE `messengers`
  ADD CONSTRAINT `messengers_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `model`
--
ALTER TABLE `model`
  ADD CONSTRAINT `model_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `model_ibfk_3` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `model_ibfk_4` FOREIGN KEY (`signer_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `model_ibfk_5` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `module_component`
--
ALTER TABLE `module_component`
  ADD CONSTRAINT `module_component_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `module_product`
--
ALTER TABLE `module_product`
  ADD CONSTRAINT `module_product_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `module_product_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `oauth`
--
ALTER TABLE `oauth`
  ADD CONSTRAINT `oauth_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quote` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_10` FOREIGN KEY (`address_destination_id`) REFERENCES `address` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_11` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_12` FOREIGN KEY (`main_order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_14` FOREIGN KEY (`discount_coupon_id`) REFERENCES `discount_coupon` (`id`),
  ADD CONSTRAINT `orders_ibfk_15` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_16` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`provider_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_4` FOREIGN KEY (`retrieve_contact_id`) REFERENCES `people` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_5` FOREIGN KEY (`delivery_contact_id`) REFERENCES `people` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_6` FOREIGN KEY (`retrieve_people_id`) REFERENCES `people` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_7` FOREIGN KEY (`delivery_people_id`) REFERENCES `people` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_8` FOREIGN KEY (`payer_people_id`) REFERENCES `people` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_9` FOREIGN KEY (`address_origin_id`) REFERENCES `address` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `order_invoice`
--
ALTER TABLE `order_invoice`
  ADD CONSTRAINT `order_invoice_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_invoice_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `invoice` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_invoice_tax`
--
ALTER TABLE `order_invoice_tax`
  ADD CONSTRAINT `order_invoice_tax_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_invoice_tax_ibfk_2` FOREIGN KEY (`invoice_tax_id`) REFERENCES `invoice_tax` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_invoice_tax_ibfk_3` FOREIGN KEY (`issuer_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_log`
--
ALTER TABLE `order_log`
  ADD CONSTRAINT `order_log_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_log_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_logistic`
--
ALTER TABLE `order_logistic`
  ADD CONSTRAINT `order_logistic_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_logistic_ibfk_10` FOREIGN KEY (`origin_city_id`) REFERENCES `city` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `order_logistic_ibfk_11` FOREIGN KEY (`destination_city_id`) REFERENCES `city` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `order_logistic_ibfk_2` FOREIGN KEY (`origin_provider_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_logistic_ibfk_3` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_logistic_ibfk_4` FOREIGN KEY (`destination_provider_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_logistic_ibfk_7` FOREIGN KEY (`origin_provider_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_logistic_ibfk_8` FOREIGN KEY (`destination_type`) REFERENCES `category` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `order_logistic_ibfk_9` FOREIGN KEY (`origin_type`) REFERENCES `category` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `order_logistic_surveys`
--
ALTER TABLE `order_logistic_surveys`
  ADD CONSTRAINT `order_logistic_surveys_order_logistic_id_fk` FOREIGN KEY (`order_logistic_id`) REFERENCES `order_logistic` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tasks_surveys_address_id_fk` FOREIGN KEY (`address_id`) REFERENCES `address` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tasks_surveys_people_id_fk` FOREIGN KEY (`professional_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tasks_surveys_people_id_fk_2` FOREIGN KEY (`surveyor_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `order_logistic_surveys_files`
--
ALTER TABLE `order_logistic_surveys_files`
  ADD CONSTRAINT `tasks_surveys_files_tasks_surveys_id_fk` FOREIGN KEY (`order_logistic_surveys_id`) REFERENCES `order_logistic_surveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_package`
--
ALTER TABLE `order_package`
  ADD CONSTRAINT `order_package_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_product`
--
ALTER TABLE `order_product`
  ADD CONSTRAINT `order_product_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_product_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_product_ibfk_4` FOREIGN KEY (`parent_product_id`) REFERENCES `product` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `order_product_ibfk_5` FOREIGN KEY (`order_product_id`) REFERENCES `order_product` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `order_product_ibfk_6` FOREIGN KEY (`product_group_id`) REFERENCES `product_group` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `order_product_ibfk_7` FOREIGN KEY (`out_inventory_id`) REFERENCES `inventory` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `order_product_ibfk_8` FOREIGN KEY (`in_inventory_id`) REFERENCES `inventory` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `order_product_ibfk_9` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `order_product_queue`
--
ALTER TABLE `order_product_queue`
  ADD CONSTRAINT `order_product_queue_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_product_queue_ibfk_3` FOREIGN KEY (`queue_id`) REFERENCES `queue` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_product_queue_ibfk_4` FOREIGN KEY (`order_product_id`) REFERENCES `order_product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD CONSTRAINT `order_tracking_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `package_modules`
--
ALTER TABLE `package_modules`
  ADD CONSTRAINT `package_modules_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `package_modules_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `particulars`
--
ALTER TABLE `particulars`
  ADD CONSTRAINT `particulars_ibfk_1` FOREIGN KEY (`particulars_type_id`) REFERENCES `particulars_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payment_type`
--
ALTER TABLE `payment_type`
  ADD CONSTRAINT `payment_type_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `people`
--
ALTER TABLE `people`
  ADD CONSTRAINT `people_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `files` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `people_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `people_ibfk_3` FOREIGN KEY (`background_image`) REFERENCES `files` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `people_ibfk_4` FOREIGN KEY (`alternative_image`) REFERENCES `files` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `people_ibfk_5` FOREIGN KEY (`sector_id`) REFERENCES `category` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `people_ibfk_6` FOREIGN KEY (`subsector_id`) REFERENCES `category` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `people_domain`
--
ALTER TABLE `people_domain`
  ADD CONSTRAINT `people_domain_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `people_domain_ibfk_2` FOREIGN KEY (`theme_id`) REFERENCES `theme` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `people_link`
--
ALTER TABLE `people_link`
  ADD CONSTRAINT `people_link_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `people_link_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `people_order`
--
ALTER TABLE `people_order`
  ADD CONSTRAINT `people_order_ibfk_1` FOREIGN KEY (`people_client_id`) REFERENCES `people_client` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `people_package`
--
ALTER TABLE `people_package`
  ADD CONSTRAINT `people_package_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `people_package_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `people_procurator`
--
ALTER TABLE `people_procurator`
  ADD CONSTRAINT `people_procurator_ibfk_1` FOREIGN KEY (`procurator_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `people_procurator_ibfk_2` FOREIGN KEY (`grantor_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `people_procurator_ibfk_3` FOREIGN KEY (`muniment_signature_id`) REFERENCES `muniment_signature` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `people_role`
--
ALTER TABLE `people_role`
  ADD CONSTRAINT `people_role_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `people_role_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `people_role_ibfk_3` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `phone`
--
ALTER TABLE `phone`
  ADD CONSTRAINT `phone_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `product_ibfk_2` FOREIGN KEY (`product_unity_id`) REFERENCES `product_unity` (`id`),
  ADD CONSTRAINT `product_ibfk_3` FOREIGN KEY (`queue_id`) REFERENCES `queue` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `product_ibfk_4` FOREIGN KEY (`default_out_inventory_id`) REFERENCES `inventory` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `product_ibfk_5` FOREIGN KEY (`default_in_inventory_id`) REFERENCES `inventory` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `product_category`
--
ALTER TABLE `product_category`
  ADD CONSTRAINT `product_category_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `product_category_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product_file`
--
ALTER TABLE `product_file`
  ADD CONSTRAINT `product_file_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `product_file_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product_group`
--
ALTER TABLE `product_group`
  ADD CONSTRAINT `product_group_ibfk_1` FOREIGN KEY (`parent_product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product_group_product`
--
ALTER TABLE `product_group_product`
  ADD CONSTRAINT `product_group_product_ibfk_2` FOREIGN KEY (`product_child_id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `product_group_product_ibfk_4` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `product_group_product_ibfk_5` FOREIGN KEY (`product_group_id`) REFERENCES `product_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product_inventory`
--
ALTER TABLE `product_inventory`
  ADD CONSTRAINT `product_inventory_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `product_inventory_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `queue`
--
ALTER TABLE `queue`
  ADD CONSTRAINT `queue_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `queue_ibfk_2` FOREIGN KEY (`status_in_id`) REFERENCES `status` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `queue_ibfk_3` FOREIGN KEY (`status_out_id`) REFERENCES `status` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `queue_ibfk_4` FOREIGN KEY (`status_working_id`) REFERENCES `status` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `quote`
--
ALTER TABLE `quote`
  ADD CONSTRAINT `quote_ibfk_3` FOREIGN KEY (`city_origin_id`) REFERENCES `city` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `quote_ibfk_4` FOREIGN KEY (`city_destination_id`) REFERENCES `city` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `quote_ibfk_5` FOREIGN KEY (`client_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `quote_ibfk_6` FOREIGN KEY (`provider_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `quote_ibfk_7` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `quote_ibfk_8` FOREIGN KEY (`carrier_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quote_detail`
--
ALTER TABLE `quote_detail`
  ADD CONSTRAINT `quote_detail_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quote` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `quote_detail_ibfk_3` FOREIGN KEY (`region_origin_id`) REFERENCES `delivery_region` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `quote_detail_ibfk_4` FOREIGN KEY (`region_destination_id`) REFERENCES `delivery_region` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `quote_detail_ibfk_5` FOREIGN KEY (`delivery_tax_id`) REFERENCES `delivery_tax` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rating`
--
ALTER TABLE `rating`
  ADD CONSTRAINT `rating_ibfk_1` FOREIGN KEY (`people_evaluator`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `rating_ibfk_2` FOREIGN KEY (`order_rated`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `rating_ibfk_3` FOREIGN KEY (`people_rated`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `retrieve`
--
ALTER TABLE `retrieve`
  ADD CONSTRAINT `retrieve_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `role`
--
ALTER TABLE `role`
  ADD CONSTRAINT `role_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `routes`
--
ALTER TABLE `routes`
  ADD CONSTRAINT `routes_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `seo_url`
--
ALTER TABLE `seo_url`
  ADD CONSTRAINT `seo_url_ibfk_1` FOREIGN KEY (`city_origin`) REFERENCES `city` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `seo_url_ibfk_2` FOREIGN KEY (`city_destination`) REFERENCES `city` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `seo_url_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `service_invoice_tax`
--
ALTER TABLE `service_invoice_tax`
  ADD CONSTRAINT `service_invoice_tax_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoice` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `service_invoice_tax_ibfk_2` FOREIGN KEY (`invoice_tax_id`) REFERENCES `invoice_tax` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `service_invoice_tax_ibfk_3` FOREIGN KEY (`issuer_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `spool`
--
ALTER TABLE `spool`
  ADD CONSTRAINT `spool_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `spool_ibfk_3` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `spool_ibfk_4` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `spool_ibfk_5` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `state`
--
ALTER TABLE `state`
  ADD CONSTRAINT `state_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `street`
--
ALTER TABLE `street`
  ADD CONSTRAINT `street_ibfk_1` FOREIGN KEY (`district_id`) REFERENCES `district` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `street_ibfk_2` FOREIGN KEY (`cep_id`) REFERENCES `cep` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`registered_by_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_10` FOREIGN KEY (`reason_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_11` FOREIGN KEY (`criticality_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_12` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`task_for_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`provider_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_4` FOREIGN KEY (`client_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_5` FOREIGN KEY (`task_status_id`) REFERENCES `status` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_8` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `task_interations`
--
ALTER TABLE `task_interations`
  ADD CONSTRAINT `task_interations_ibfk_1` FOREIGN KEY (`registered_by_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `task_interations_ibfk_2` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `task_interations_ibfk_3` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tax`
--
ALTER TABLE `tax`
  ADD CONSTRAINT `tax_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tax_ibfk_2` FOREIGN KEY (`state_origin_id`) REFERENCES `state` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tax_ibfk_3` FOREIGN KEY (`state_destination_id`) REFERENCES `state` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `theme`
--
ALTER TABLE `theme`
  ADD CONSTRAINT `theme_ibfk_1` FOREIGN KEY (`background`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `translate`
--
ALTER TABLE `translate`
  ADD CONSTRAINT `translate_ibfk_1` FOREIGN KEY (`lang_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `translate_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `wallet`
--
ALTER TABLE `wallet`
  ADD CONSTRAINT `wallet_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `wallet_payment_type`
--
ALTER TABLE `wallet_payment_type`
  ADD CONSTRAINT `wallet_payment_type_ibfk_1` FOREIGN KEY (`wallet_id`) REFERENCES `wallet` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `wallet_payment_type_ibfk_2` FOREIGN KEY (`payment_type_id`) REFERENCES `payment_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;
