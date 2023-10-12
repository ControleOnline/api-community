<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class VersionDatabase extends AbstractMigration
{
  public function getDescription(): string
  {
    return '';
  }

  public function up(Schema $schema): void
  {
    // this up() migration is auto-generated, please modify it to your needs
    $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

    $this->addSql('CREATE TABLE IF NOT EXISTS `address` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `people_id` int(11) DEFAULT NULL,
            `number` int(11) DEFAULT NULL,
            `street_id` int(11) NOT NULL,
            `nickname` varchar(50) NOT NULL,
            `complement` varchar(50) NOT NULL,
            `latitude` double NOT NULL,
            `longitude` double NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_id_3` (`people_id`,`number`,`street_id`,`complement`) USING BTREE,
            KEY `user_id` (`people_id`),
            KEY `cep_id` (`street_id`),
            KEY `user_id_2` (`people_id`,`nickname`) USING BTREE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `carrier_integration` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `carrier_id` int(11) NOT NULL,
            `integration_type` enum(\'correios\',\'jadlog\',\'ssw\') DEFAULT NULL,
            `integration_user` varchar(100) DEFAULT NULL,
            `integration_password` varchar(100) DEFAULT NULL,
            `enable` tinyint(1) NOT NULL DEFAULT 0,
            `average_rating` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `carrier_integration_ibfk_1` (`carrier_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `car_manufacturer` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `car_type_id` int(11) NOT NULL,
            `car_type_ref` int(11) NOT NULL,
            `label` varchar(255) NOT NULL,
            `value` int(11) NOT NULL,
            `created_at` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `car_model` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `car_manufacturer_id` int(11) NOT NULL,
            `label` varchar(255) NOT NULL,
            `value` int(11) NOT NULL,
            `created_at` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `car_manufacturer_id` (`car_manufacturer_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `car_year_price` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `car_type_id` int(11) NOT NULL,
            `car_type_ref` int(11) DEFAULT NULL,
            `fuel_type_code` int(11) DEFAULT NULL,
            `car_manufacturer_id` int(11) NOT NULL,
            `car_model_id` int(11) NOT NULL,
            `label` varchar(255) NOT NULL,
            `value` varchar(255) NOT NULL,
            `price` double DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `car_manufacturer_id` (`car_manufacturer_id`),
            KEY `car_model_id` (`car_model_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `category` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `company_id` int(11) DEFAULT NULL,
            `name` varchar(100) NOT NULL,
            `icon` varchar(50) DEFAULT NULL,
            `color` varchar(50) NOT NULL DEFAULT \'$secundary\',
            `context` varchar(100) NOT NULL,
            `parent_id` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `company_id` (`company_id`),
            KEY `parent_id` (`parent_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');


    $this->addSql('CREATE TABLE IF NOT EXISTS `cep` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `cep` int(8) UNSIGNED ZEROFILL NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `CEP` (`cep`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');


    $this->addSql('CREATE TABLE IF NOT EXISTS `city` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `cod_ibge` int(11) DEFAULT NULL,
            `city` varchar(80) NOT NULL,
            `state_id` int(11) NOT NULL,
            `seo` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            UNIQUE KEY `city` (`city`,`state_id`),
            UNIQUE KEY `cod_ibge` (`cod_ibge`),
            KEY `state_id` (`state_id`),
            KEY `seo` (`seo`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `company_expense` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `company_id` int(11) NOT NULL,
            `category_id` int(11) NOT NULL,
            `provider_id` int(11) NOT NULL,
            `order_id` int(11) NOT NULL,
            `parcels` tinyint(4) DEFAULT NULL,
            `amount` double NOT NULL,
            `duedate` date NOT NULL,
            `description` varchar(100) DEFAULT NULL,
            `payment_day` tinyint(4) DEFAULT NULL,
            `active` tinyint(1) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `company_id` (`company_id`),
            KEY `category_id` (`category_id`),
            KEY `provider_id` (`provider_id`),
            KEY `order_id` (`order_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `config` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `visibility` enum(\'public\',\'private\') NOT NULL DEFAULT \'private\',
            `people_id` int(11) NOT NULL,
            `config_key` varchar(50) NOT NULL,
            `config_value` text NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `people_id` (`people_id`,`config_key`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `contract` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `contract_model_id` int(11) NOT NULL,
            `contract_status` enum(\'Draft\',\'Waiting approval\',\'Active\',\'Canceled\',\'Amended\',\'Waiting signatures\') NOT NULL DEFAULT \'Draft\',
            `start_date` date NOT NULL,
            `end_date` date DEFAULT NULL,
            `creation_date` datetime NOT NULL DEFAULT current_timestamp(),
            `alter_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            `contract_parent_id` int(11) DEFAULT NULL,
            `html_content` text DEFAULT NULL,
            `doc_key` varchar(100) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `contract_parent_id` (`contract_parent_id`) USING BTREE,
            KEY `contract_model_id` (`contract_model_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `contract_model` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `contract_model` varchar(255) NOT NULL,
            `content` mediumtext NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `contract_model` (`contract_model`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `contract_people` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `contract_id` int(11) NOT NULL,
            `people_id` int(11) NOT NULL,
            `people_type` enum(\'Beneficiary\',\'Witness\',\'Payer\',\'Provider\',\'Contractor\') NOT NULL,
            `contract_percentage` double DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `contract_id` (`contract_id`),
            KEY `people_id` (`people_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `contract_product` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `quantity` double NOT NULL DEFAULT 1,
            `product_id` int(11) NOT NULL,
            `contract_id` int(11) NOT NULL,
            `product_price` double NOT NULL,
            `other_informations` longtext DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `contract_id` (`contract_id`),
            KEY `product_id` (`product_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `contract_product_payment` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `contract_id` int(11) NOT NULL,
            `product_id` int(11) NOT NULL,
            `payer_id` int(11) NOT NULL,
            `amount` double NOT NULL,
            `duedate` date DEFAULT NULL,
            `sequence` tinyint(1) NOT NULL,
            `processed` tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `product_id` (`product_id`),
            KEY `payer_id` (`payer_id`),
            KEY `contract_id` (`contract_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `country` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
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
            `geonameId` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `countryCode` (`countryCode`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `delivery_region` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `id_copia` int(11) DEFAULT NULL,
            `region` varchar(255) NOT NULL,
            `people_id` int(11) NOT NULL,
            `deadline` int(3) NOT NULL,
            `retrieve_tax` double NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `region` (`region`,`people_id`),
            KEY `people_id` (`people_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `delivery_region_city` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `delivery_region_id` int(11) NOT NULL,
            `city_id` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `delivery_region_id` (`delivery_region_id`,`city_id`) USING BTREE,
            KEY `city_id` (`city_id`) USING BTREE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `delivery_restriction_material` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `people_id` int(11) NOT NULL,
            `product_material_id` int(11) NOT NULL,
            `restriction_type` enum(\'delivery_denied\',\'delivery_restricted\') NOT NULL DEFAULT \'delivery_denied\',
            `public` tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `people_id` (`people_id`,`product_material_id`),
            KEY `product_material_id` (`product_material_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');


    $this->addSql('CREATE TABLE IF NOT EXISTS `delivery_tax` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `tax_name` varchar(255) NOT NULL,
            `tax_description` varchar(255) DEFAULT NULL,
            `tax_type` enum(\'fixed\',\'percentage\') NOT NULL,
            `tax_subtype` enum(\'invoice\',\'kg\',\'order\',\'km\') DEFAULT NULL,
            `people_id` int(11) DEFAULT NULL,
            `final_weight` double DEFAULT NULL,
            `region_origin_id` int(11) DEFAULT NULL,
            `region_destination_id` int(11) DEFAULT NULL,
            `tax_order` int(11) NOT NULL DEFAULT 0,
            `price` double NOT NULL,
            `minimum_price` double NOT NULL,
            `optional` tinyint(1) NOT NULL DEFAULT 0,
            `delivery_tax_group_id` int(11) NOT NULL DEFAULT 1,
            `creation_date` datetime NOT NULL DEFAULT current_timestamp(),
            `alter_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `tax_name` (`tax_name`,`tax_type`,`tax_subtype`,`people_id`,`final_weight`,`region_origin_id`,`region_destination_id`,`delivery_tax_group_id`),
            KEY `people_id` (`people_id`),
            KEY `region_destination_id` (`region_destination_id`) USING BTREE,
            KEY `region_origin_id` (`region_origin_id`) USING BTREE,
            KEY `delivery_tax_group_id` (`delivery_tax_group_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `delivery_tax_group` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `carrier_id` int(11) NOT NULL,
            `code` varchar(50) DEFAULT NULL,
            `group_name` enum(\'BALCAO\',\'FRACIONADO\',\'FRACIONADO / AEREO\',\'FRACIONADO / EXPRESSO\',\'LOTACAO / GERAL\',\'LOTACAO / GRANEL LIQUIDO\',\'LOTACAO / GRANEL SOLIDO\',\'LOTACAO / GRANEL PRESSURIZADA\',\'LOTACAO / CONTEINERIZADA\',\'LOTACAO / FRIGORIFICADA\',\'LOTACAO / NEOGRANEL\',\'LOTACAO / PERIGOSA GERAL\',\'LOTACAO / PERIGOSA GRANEL SOLIDO\',\'LOTACAO / PERIGOSA GRANEL LIQUIDO\',\'LOTACAO / PERIGOSA FRIGORIFICADA\',\'LOTACAO / PERIGOSA CONTEINERIZADA\',\'MOTO FRETE\',\'VEICULO DEDICADO\') NOT NULL DEFAULT \'FRACIONADO\',
            `cubage` int(11) NOT NULL DEFAULT 300,
            `max_height` double NOT NULL DEFAULT 3,
            `max_width` double NOT NULL DEFAULT 3,
            `max_depth` double NOT NULL DEFAULT 3,
            `min_cubage` double NOT NULL DEFAULT 0,
            `max_cubage` double NOT NULL DEFAULT 10000,
            `marketplace` tinyint(1) NOT NULL DEFAULT 1,
            `remote` tinyint(1) NOT NULL DEFAULT 0,
            `website` tinyint(1) NOT NULL DEFAULT 1,
            `alter_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `group_name` (`group_name`,`carrier_id`,`code`) USING BTREE,
            KEY `carrier_id` (`carrier_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `discount_coupon` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `code` varchar(10) NOT NULL,
            `type` enum(\'percentage\',\'amount\') NOT NULL DEFAULT \'percentage\',
            `creator_id` int(11) NOT NULL,
            `company_id` int(11) DEFAULT NULL,
            `client_id` int(11) DEFAULT NULL,
            `discount_date` datetime NOT NULL DEFAULT current_timestamp(),
            `discount_start_date` date DEFAULT NULL,
            `discount_end_date` date DEFAULT NULL,
            `config` longtext DEFAULT NULL,
            `value` double NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `code` (`code`),
            KEY `creator_id` (`creator_id`),
            KEY `client_id` (`client_id`),
            KEY `discount_coupon_ibfk_3` (`company_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');


    $this->addSql('CREATE TABLE IF NOT EXISTS `district` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `district` varchar(255) NOT NULL,
            `city_id` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `city_id` (`city_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');


    $this->addSql('CREATE TABLE IF NOT EXISTS `docs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `register_date` datetime NOT NULL DEFAULT current_timestamp(),
            `type` enum(\'imposto\',\'declaracao\') NOT NULL,
            `name` enum(\'das\',\'pis\',\'confins\') NOT NULL,
            `date_period` date NOT NULL,
            `status_id` int(11) NOT NULL,
            `file_name_guide` varchar(255) DEFAULT NULL,
            `file_name_receipt` varchar(255) DEFAULT NULL,
            `people_id` int(11) NOT NULL,
            `company_id` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `company_id` (`company_id`),
            KEY `people_id` (`people_id`),
            KEY `status_id` (`status_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `document` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `document` bigint(20) NOT NULL,
            `document_type_id` int(11) NOT NULL,
            `people_id` int(11) NOT NULL,
            `file_id` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `doc` (`people_id`,`document_type_id`) USING BTREE,
            UNIQUE KEY `document` (`document`,`document_type_id`),
            KEY `type_2` (`document_type_id`),
            KEY `image_id` (`file_id`),
            KEY `type` (`people_id`,`document_type_id`) USING BTREE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `document_type` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `document_type` varchar(50) NOT NULL,
            `people_type` enum(\'F\',\'J\') NOT NULL COMMENT \' Individual or juridical person\',
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `email` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `email` varchar(50) NOT NULL,
            `types` varchar(50) DEFAULT NULL,
            `confirmed` tinyint(1) NOT NULL DEFAULT 0,
            `people_id` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `email` (`email`),
            KEY `IDX_E7927C743147C936` (`people_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `files` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `url` varchar(255) NOT NULL,
            `path` varchar(255) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `url` (`url`),
            UNIQUE KEY `path` (`path`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `food` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `food` varchar(50) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `food` (`food`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `imports` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `import_type` enum(\'table\',\'DACTE\') NOT NULL DEFAULT \'table\',
            `status` enum(\'waiting\',\'importing\',\'imported\',\'failed\') NOT NULL,
            `name` varchar(255) NOT NULL,
            `file_id` int(11) NOT NULL,
            `people_id` int(11) DEFAULT NULL,
            `file_format` enum(\'csv\',\'xml\') NOT NULL DEFAULT \'csv\',
            `feedback` longtext DEFAULT NULL,
            `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `people_id` (`people_id`),
            KEY `imports_ibfk_1` (`file_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `invoice` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `invoice_status_id` int(11) NOT NULL,
            `invoice_date` datetime NOT NULL DEFAULT current_timestamp(),
            `alter_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            `due_date` date NOT NULL,
            `payment_date` datetime DEFAULT NULL,
            `price` double NOT NULL,
            `invoice_type` varchar(50) DEFAULT NULL,
            `invoice_subtype` varchar(50) DEFAULT NULL,
            `payment_response` longblob DEFAULT NULL,
            `notified` tinyint(1) NOT NULL DEFAULT 0,
            `category_id` int(11) DEFAULT NULL,
            `description` varchar(150) DEFAULT NULL,
            `payment_mode` tinyint(4) DEFAULT NULL,
            `invoice_bank_id` varchar(30) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `invoice_subtype` (`invoice_subtype`),
            KEY `invoice_type` (`invoice_type`),
            KEY `invoice_status_id` (`invoice_status_id`),
            KEY `invoice_ibfk_2` (`category_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');


    $this->addSql('CREATE TABLE IF NOT EXISTS `invoice_log` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `alter_type` enum(\'Value\',\'Date\') NOT NULL,
            `invoice_id` int(11) NOT NULL,
            `people_id` int(11) NOT NULL,
            `alter_date` datetime NOT NULL DEFAULT current_timestamp(),
            `note` text NOT NULL,
            PRIMARY KEY (`id`),
            KEY `people_id` (`people_id`),
            KEY `invoice_id` (`invoice_id`) USING BTREE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `invoice_status` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `status` varchar(50) NOT NULL,
            `real_status` enum(\'open\',\'pending\',\'canceled\',\'closed\') NOT NULL DEFAULT \'open\',
            `visibility` enum(\'public\',\'private\') NOT NULL DEFAULT \'public\',
            `notify` tinyint(1) NOT NULL,
            `system` tinyint(1) NOT NULL,
            `color` varchar(7) NOT NULL DEFAULT \'#000000\',
            PRIMARY KEY (`id`),
            UNIQUE KEY `status` (`status`),
            KEY `real_status` (`real_status`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `invoice_tax` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `invoice_key` int(11) DEFAULT NULL,
            `invoice_number` int(11) DEFAULT NULL,
            `invoice` longtext NOT NULL,
            PRIMARY KEY (`id`),
            KEY `invoice_number` (`invoice_number`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `labels` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
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
            `created_at` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `people_id` (`people_id`),
            KEY `carrier_id` (`carrier_id`),
            KEY `labels_ibfk_3` (`order_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `language` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `language` varchar(10) NOT NULL,
            `locked` tinyint(1) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `language` (`language`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `language_country` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `language_id` int(11) NOT NULL,
            `country_id` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `language_id` (`language_id`,`country_id`),
            KEY `country_id` (`country_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `lessons` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `lesson_name` varchar(255) NOT NULL,
            `lesson_points` double NOT NULL,
            `lesson_category_id` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `lesson_name` (`lesson_name`),
            KEY `lesson_category_id` (`lesson_category_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `log` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `row_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `table` varchar(50) NOT NULL,
            `old` longtext NOT NULL,
            `new` longtext NOT NULL,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `measure` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `measure` varchar(50) NOT NULL,
            `measure_type_id` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `measure` (`measure`),
            KEY `measuretype_id` (`measure_type_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `measure_type` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `measure_type` varchar(50) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `measure_type` (`measure_type`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `menu` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `module_id` int(11) NOT NULL,
            `category_id` int(11) NOT NULL,
            `menu` varchar(50) NOT NULL,
            `route` varchar(50) NOT NULL,
            `color` varchar(50) NOT NULL DEFAULT \'$primary\',
            `icon` varchar(50) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `route` (`route`),
            KEY `module_id` (`module_id`),
            KEY `category_id` (`category_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `menu_role` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `menu_id` int(11) NOT NULL,
            `role_id` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `menu_id` (`menu_id`,`role_id`),
            KEY `role_id` (`role_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');






    $this->addSql('CREATE TABLE IF NOT EXISTS `module` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `color` varchar(50) NOT NULL DEFAULT \'$primary\',
            `icon` varchar(50) NOT NULL,
            `description` varchar(255) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `UX_MODULE_NAME` (`name`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `muniment` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `muniment_group_id` int(11) NOT NULL,
            `muniment_identifier` varchar(20) NOT NULL,
            `muniment` varchar(60) NOT NULL,
            `hash` varchar(32) NOT NULL,
            `image_id` int(11) DEFAULT NULL,
            `creation_date` datetime NOT NULL DEFAULT current_timestamp(),
            `alter_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            `validation_date` datetime NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `image_id` (`image_id`) USING BTREE,
            KEY `creation_date` (`creation_date`),
            KEY `alter_date` (`alter_date`),
            KEY `validation_date` (`validation_date`),
            KEY `muniment_group_id` (`muniment_group_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `muniment_group` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `group_name` varchar(60) NOT NULL,
            `people_id` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `requesting_people_id` (`people_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `muniment_signature` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `muniment_id` int(11) NOT NULL,
            `people_id` int(11) NOT NULL,
            `creation_date` datetime NOT NULL DEFAULT current_timestamp(),
            `signature_date` datetime DEFAULT NULL,
            `details` longtext NOT NULL,
            `request` varchar(60) NOT NULL,
            `confirmation` varchar(60) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `muniment_id` (`muniment_id`,`people_id`),
            KEY `signature_date` (`signature_date`),
            KEY `people_id` (`people_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `nutritional_information` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `food_id` int(11) NOT NULL,
            `nutritional_information_type_id` int(11) NOT NULL,
            `amount` float NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `food_id` (`food_id`,`nutritional_information_type_id`),
            KEY `nutritional_information_type_id` (`nutritional_information_type_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `nutritional_information_type` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nutritional_information_type` varchar(50) NOT NULL,
            `measure_id` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `nutritional_information_type` (`nutritional_information_type`),
            KEY `measure_id` (`measure_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `orders` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_type` enum(\'sale\',\'purchase\',\'comission\',\'royalties\') NOT NULL DEFAULT \'sale\',
            `app` varchar(50) DEFAULT NULL,
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
            `contract_id` int(11) DEFAULT NULL,
            `address_origin_id` int(11) DEFAULT NULL,
            `address_destination_id` int(11) DEFAULT NULL,
            `retrieve_contact_id` int(11) DEFAULT NULL,
            `delivery_contact_id` int(11) DEFAULT NULL,
            `comments` text DEFAULT NULL,
            `other_informations` longtext DEFAULT NULL,
            `price` double NOT NULL,
            `invoice_total` double NOT NULL,
            `cubage` double NOT NULL,
            `product_type` text NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `discount_id` (`discount_coupon_id`),
            KEY `provider_id` (`provider_id`),
            KEY `adress_origin_id` (`address_origin_id`),
            KEY `adress_destination_id` (`address_destination_id`),
            KEY `retrieve_contact_id` (`retrieve_contact_id`),
            KEY `delivery_contact_id` (`delivery_contact_id`),
            KEY `retrieve_people_id` (`retrieve_people_id`),
            KEY `delivery_people_id` (`delivery_people_id`),
            KEY `payer_people_id` (`payer_people_id`),
            KEY `order_status_id` (`status_id`),
            KEY `client_id` (`client_id`) USING BTREE,
            KEY `order_date` (`order_date`),
            KEY `alter_date` (`alter_date`),
            KEY `quote_id` (`quote_id`,`provider_id`) USING BTREE,
            KEY `notified` (`notified`),
            KEY `main_order_id` (`main_order_id`),
            KEY `contract_id` (`contract_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');


    $this->addSql('CREATE TABLE IF NOT EXISTS `order_invoice` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` int(11) NOT NULL,
            `invoice_id` int(11) NOT NULL,
            `real_price` double NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `order_id` (`order_id`,`invoice_id`) USING BTREE,
            KEY `invoice_id` (`invoice_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `order_invoice_tax` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` int(11) NOT NULL,
            `invoice_tax_id` int(11) NOT NULL,
            `invoice_type` int(11) NOT NULL,
            `issuer_id` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `order_id` (`order_id`,`invoice_tax_id`) USING BTREE,
            UNIQUE KEY `order_id_2` (`issuer_id`,`invoice_type`,`order_id`) USING BTREE,
            KEY `invoice_tax_id` (`invoice_tax_id`) USING BTREE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `order_log` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `alter_type` enum(\'Value\',\'Status\',\'Document\') NOT NULL,
            `order_id` int(11) NOT NULL,
            `people_id` int(11) NOT NULL,
            `alter_date` datetime NOT NULL DEFAULT current_timestamp(),
            `note` text NOT NULL,
            PRIMARY KEY (`id`),
            KEY `order_id` (`order_id`),
            KEY `people_id` (`people_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `order_package` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` int(11) NOT NULL,
            `qtd` double NOT NULL,
            `height` double NOT NULL,
            `width` double NOT NULL,
            `depth` double NOT NULL,
            `weight` double NOT NULL,
            PRIMARY KEY (`id`),
            KEY `order_id` (`order_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `order_tracking` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
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
            `nro_doc_recebedor` varchar(100) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `order_id` (`order_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `particulars` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `particulars_type_id` int(11) NOT NULL,
            `people_id` int(11) NOT NULL,
            `particular_value` varchar(255) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `people_id` (`people_id`),
            KEY `particulars_type_id` (`particulars_type_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `particulars_type` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `people_type` char(1) NOT NULL,
            `type_value` varchar(255) NOT NULL,
            `field_type` varchar(255) NOT NULL,
            `context` varchar(255) NOT NULL,
            `required` varchar(255) DEFAULT NULL,
            `field_configs` mediumtext DEFAULT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `people` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` text NOT NULL,
            `alias` text NOT NULL,
            `register_date` datetime NOT NULL DEFAULT current_timestamp(),
            `enable` tinyint(1) NOT NULL,
            `people_type` enum(\'F\',\'J\') NOT NULL COMMENT \' Individual or juridical person\',
            `image_id` int(11) DEFAULT NULL,
            `background_image` int(11) DEFAULT NULL,
            `alternative_image` int(11) DEFAULT NULL,
            `language_id` int(11) NOT NULL,
            `billing` double NOT NULL DEFAULT 0,
            `billing_days` enum(\'daily\',\'weekly\',\'biweekly\',\'monthly\') NOT NULL DEFAULT \'daily\',
            `payment_term` int(11) NOT NULL DEFAULT 1,
            `icms` tinyint(1) NOT NULL DEFAULT 1,
            `foundation_date` datetime DEFAULT NULL,
            `other_informations` longtext DEFAULT NULL,
            `source` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `language_id` (`language_id`),
            KEY `alternative_image` (`background_image`),
            KEY `image_id` (`image_id`) USING BTREE,
            KEY `alternative_image_2` (`alternative_image`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `people_carrier` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `carrier_id` int(11) NOT NULL,
            `company_id` int(11) NOT NULL,
            `enable` tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `carrier_id` (`carrier_id`,`company_id`) USING BTREE,
            KEY `company_id` (`company_id`) USING BTREE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `people_client` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `client_id` int(11) NOT NULL,
            `company_id` int(11) NOT NULL,
            `commission` double NOT NULL DEFAULT 0,
            `enable` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            UNIQUE KEY `client_id` (`client_id`,`company_id`),
            KEY `provider_id` (`company_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `people_domain` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `people_id` int(11) NOT NULL,
            `domain` varchar(255) NOT NULL,
            `domain_type` enum(\'cfc\',\'cfp\',\'cfcc\',\'simple\',\'ceg\') NOT NULL DEFAULT \'cfp\' COMMENT \'cfc=Company for Company,cfcc=Company for Corporate Company,cfp=Company for People,simple=Simple Company\',
            PRIMARY KEY (`id`),
            UNIQUE KEY `domain` (`domain`),
            KEY `people_id` (`people_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `people_employee` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `employee_id` int(11) NOT NULL,
            `company_id` int(11) NOT NULL,
            `enable` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            UNIQUE KEY `employee_id` (`employee_id`,`company_id`) USING BTREE,
            KEY `company_id` (`company_id`) USING BTREE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `people_franchisee` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `franchisee_id` int(11) NOT NULL,
            `franchisor_id` int(11) NOT NULL,
            `royalties` double NOT NULL DEFAULT 5,
            `minimum_royalties` int(11) NOT NULL DEFAULT 2000,
            `enable` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            UNIQUE KEY `franchisee_id` (`franchisee_id`,`franchisor_id`) USING BTREE,
            KEY `franchisor_id` (`franchisor_id`) USING BTREE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `people_order` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `people_client_id` int(11) NOT NULL,
            `order_value` float NOT NULL,
            PRIMARY KEY (`id`),
            KEY `people_client_id` (`people_client_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `people_procurator` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `procurator_id` int(11) NOT NULL,
            `grantor_id` int(11) NOT NULL,
            `muniment_signature_id` int(11) NOT NULL,
            `enable` tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `client_id` (`procurator_id`,`grantor_id`),
            UNIQUE KEY `muniment_signature_id` (`muniment_signature_id`),
            KEY `provider_id` (`grantor_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `people_provider` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `provider_id` int(11) NOT NULL,
            `company_id` int(11) NOT NULL,
            `enable` tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `client_id` (`provider_id`,`company_id`),
            KEY `provider_id` (`company_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `people_role` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `company_id` int(11) NOT NULL,
            `people_id` int(11) NOT NULL,
            `role_id` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `company_id` (`company_id`,`people_id`,`role_id`),
            KEY `people_id` (`people_id`),
            KEY `role_id` (`role_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `people_salesman` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `salesman_id` int(11) NOT NULL,
            `company_id` int(11) NOT NULL,
            `commission` double NOT NULL DEFAULT 2.8,
            `salesman_type` enum(\'salesman\',\'affiliate\') NOT NULL DEFAULT \'affiliate\',
            `enable` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            UNIQUE KEY `salesman_id` (`salesman_id`,`company_id`) USING BTREE,
            KEY `provider_id` (`company_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `people_states` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `people_id` int(11) NOT NULL,
            `state_id` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `state_id` (`state_id`),
            KEY `people_id` (`people_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');


    $this->addSql('CREATE TABLE IF NOT EXISTS `people_student` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `student_id` int(11) NOT NULL,
            `company_id` int(11) NOT NULL,
            `enable` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            UNIQUE KEY `student_id` (`student_id`,`company_id`) USING BTREE,
            KEY `company_id` (`company_id`) USING BTREE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `people_support` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `support_id` int(11) NOT NULL,
            `company_id` int(11) NOT NULL,
            `commission` double NOT NULL DEFAULT 20,
            `enable` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            UNIQUE KEY `support_id` (`support_id`,`company_id`) USING BTREE,
            KEY `provider_id` (`company_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `people_team` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `people_id` int(11) NOT NULL,
            `team_id` int(11) NOT NULL,
            `people_type` enum(\'student\',\'professional\') NOT NULL DEFAULT \'student\',
            `enable` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            UNIQUE KEY `people_id` (`people_id`,`team_id`) USING BTREE,
            KEY `team_id` (`team_id`) USING BTREE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `people_professional` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `professional_id` int(11) NOT NULL,
            `company_id` int(11) NOT NULL,
            `enable` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            UNIQUE KEY `professional_id` (`professional_id`,`company_id`) USING BTREE,
            KEY `company_id` (`company_id`) USING BTREE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `phone` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `ddd` int(2) NOT NULL,
            `phone` int(10) NOT NULL,
            `confirmed` tinyint(1) NOT NULL DEFAULT 0,
            `people_id` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `IDX_E7927C743147C936` (`people_id`),
            KEY `phone` (`phone`,`ddd`,`people_id`) USING BTREE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `product` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_provider` int(11) NOT NULL,
            `product` varchar(255) NOT NULL,
            `product_parent` int(11) DEFAULT NULL,
            `product_quantity` int(11) NOT NULL DEFAULT 1,
            `product_type` enum(\'Service\',\'Virtual\',\'Physical\',\'Variation\') NOT NULL,
            `product_subtype` enum(\'Package\',\'Hours\') DEFAULT NULL,
            `product_period` enum(\'Days\',\'Week\',\'Month\',\'Year\') DEFAULT NULL,
            `price` double NOT NULL,
            `billing_unit` enum(\'Single\',\'Hour\',\'Weekly\',\'Monthly\',\'Yearly\') NOT NULL DEFAULT \'Single\',
            `other_informations` longtext DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `product_provider` (`product_provider`),
            KEY `product_parent` (`product_parent`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `product_material` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `material` varchar(500) NOT NULL,
            `revised` tinyint(1) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `material` (`material`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `quote` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
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
            `total` double NOT NULL,
            `denied` tinyint(1) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `order_id` (`order_id`),
            KEY `client_id` (`client_id`),
            KEY `provider_id` (`provider_id`),
            KEY `city_origin_id` (`city_origin_id`),
            KEY `city_destination_id` (`city_destination_id`),
            KEY `carrier_id` (`carrier_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `quote_detail` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `quote_id` int(11) NOT NULL,
            `delivery_tax_id` int(11) DEFAULT NULL,
            `tax_name` varchar(255) NOT NULL,
            `tax_description` varchar(255) DEFAULT NULL,
            `tax_type` enum(\'fixed\',\'percentage\') NOT NULL,
            `tax_subtype` enum(\'invoice\',\'kg\',\'order\',\'km\') DEFAULT NULL,
            `final_weight` double DEFAULT NULL,
            `region_origin_id` int(11) DEFAULT NULL,
            `region_destination_id` int(11) DEFAULT NULL,
            `tax_order` int(11) NOT NULL,
            `price` double NOT NULL,
            `minimum_price` double NOT NULL,
            `optional` tinyint(1) NOT NULL,
            `price_calculated` double NOT NULL,
            PRIMARY KEY (`id`),
            KEY `region_destination_id` (`region_destination_id`) USING BTREE,
            KEY `region_origin_id` (`region_origin_id`) USING BTREE,
            KEY `delivery_tax_id` (`delivery_tax_id`),
            KEY `quote` (`quote_id`),
            KEY `price_calculated` (`price_calculated`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `rating` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `rating` enum(\'1\',\'2\',\'3\',\'4\',\'5\') NOT NULL DEFAULT \'5\',
            `rating_type` enum(\'Confidence\',\'Speed\',\'Quality\',\'Attendance\') NOT NULL,
            `order_rated` int(11) DEFAULT NULL,
            `people_rated` int(11) NOT NULL,
            `people_evaluator` int(11) NOT NULL,
            `rating_date` datetime NOT NULL DEFAULT current_timestamp(),
            `note` text DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `order_rated` (`order_rated`,`rating_type`,`people_evaluator`,`people_rated`) USING BTREE,
            KEY `people_evaluator` (`people_evaluator`),
            KEY `rating` (`rating`),
            KEY `rating_type` (`rating_type`),
            KEY `people_rated` (`people_rated`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `retrieve` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` int(11) NOT NULL,
            `retrieve_number` int(11) NOT NULL,
            `retrieve_date` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `order_id` (`order_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `role` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `role` varchar(50) NOT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `school_class` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `team_id` int(11) NOT NULL,
            `school_class_status_id` int(11) NOT NULL,
            `original_start_prevision` datetime DEFAULT NULL,
            `start_prevision` datetime NOT NULL,
            `end_prevision` datetime NOT NULL,
            `lesson_start` datetime DEFAULT NULL,
            `lesson_end` datetime DEFAULT NULL,
            `homework` mediumtext NOT NULL,
            `homework_correction` mediumtext NOT NULL,
            `board` mediumtext NOT NULL,
            `important_notes` mediumtext NOT NULL,
            `observations` varchar(200) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `team` (`team_id`) USING BTREE,
            KEY `school_class_status_id` (`school_class_status_id`) USING BTREE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `school_class_files` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `school_class_id` int(11) NOT NULL,
            `file_id` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `school_class_id` (`school_class_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `school_class_lessons` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `school_class_id` int(11) NOT NULL,
            `lesson_id` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `lesson_id` (`lesson_id`),
            KEY `school_class_id` (`school_class_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `school_class_status` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `lesson_status` varchar(255) NOT NULL,
            `lesson_real_status` enum(\'Pending\',\'Missed\',\'Given\') NOT NULL,
            `lesson_color` varchar(7) NOT NULL DEFAULT \'#000000\',
            `generate_payment` tinyint(1) NOT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `school_team_schedule` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `team_id` int(11) NOT NULL,
            `professional_id` int(11) NOT NULL,
            `week_day` varchar(10) NOT NULL,
            `start_time` time NOT NULL,
            `end_time` time NOT NULL,
            PRIMARY KEY (`id`),
            KEY `team_id` (`team_id`),
            KEY `professional_id` (`professional_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `school_professional_weekly` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `professional_id` int(11) NOT NULL,
            `week_day` varchar(10) NOT NULL,
            `start_time` time NOT NULL,
            `end_time` time NOT NULL,
            PRIMARY KEY (`id`),
            KEY `professional_id` (`professional_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `seo_url` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `url` varchar(255) NOT NULL,
            `city_origin` int(11) NOT NULL,
            `city_destination` int(11) NOT NULL,
            `weight` float NOT NULL DEFAULT 1,
            `order_id` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `url` (`url`) USING BTREE,
            UNIQUE KEY `city_origin_2` (`city_origin`,`city_destination`,`weight`),
            UNIQUE KEY `order_id` (`order_id`),
            KEY `city_origin` (`city_origin`),
            KEY `city_destination` (`city_destination`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `service_invoice_tax` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `invoice_id` int(11) NOT NULL,
            `invoice_tax_id` int(11) NOT NULL,
            `invoice_type` int(11) NOT NULL,
            `issuer_id` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `invoice_id` (`invoice_id`,`invoice_tax_id`) USING BTREE,
            UNIQUE KEY `invoice_type` (`issuer_id`,`invoice_type`,`invoice_id`) USING BTREE,
            KEY `invoice_tax_id` (`invoice_tax_id`) USING BTREE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `sessions` (
            `id` char(32) NOT NULL DEFAULT \'\',
            `name` varchar(255) NOT NULL,
            `modified` int(11) DEFAULT NULL,
            `lifetime` int(11) DEFAULT NULL,
            `data` text DEFAULT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `state` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `cod_ibge` int(11) DEFAULT NULL,
            `state` varchar(50) NOT NULL,
            `country_id` int(11) NOT NULL,
            `UF` varchar(2) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `UF` (`UF`),
            UNIQUE KEY `cod_ibge` (`cod_ibge`),
            KEY `country_id` (`country_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `status` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `status` varchar(50) NOT NULL,
            `context` enum(\'order\',\'support\',\'relationship\') NOT NULL DEFAULT \'order\',
            `real_status` enum(\'open\',\'pending\',\'canceled\',\'closed\') NOT NULL DEFAULT \'open\',
            `visibility` enum(\'public\',\'private\') NOT NULL DEFAULT \'public\',
            `notify` tinyint(1) NOT NULL,
            `system` tinyint(1) NOT NULL,
            `color` varchar(7) NOT NULL DEFAULT \'#000000\',
            PRIMARY KEY (`id`),
            UNIQUE KEY `status` (`status`,`context`) USING BTREE,
            KEY `real_status` (`real_status`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `street` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `street` varchar(255) NOT NULL,
            `cep_id` int(10) NOT NULL,
            `district_id` int(11) NOT NULL,
            `confirmed` tinyint(1) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `street_2` (`street`,`district_id`,`cep_id`) USING BTREE,
            KEY `country_id` (`district_id`),
            KEY `cep` (`cep_id`) USING BTREE,
            KEY `street` (`street`) USING BTREE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `student_proficiency` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `student_id` int(11) NOT NULL,
            `professional_id` int(11) NOT NULL,
            `lesson_id` int(11) NOT NULL,
            `proficiency` enum(\'Not Proficiency\',\'Developing\',\'Proficiency\') NOT NULL DEFAULT \'Not Proficiency\',
            `proficiency_date` date NOT NULL,
            PRIMARY KEY (`id`),
            KEY `lesson_id` (`lesson_id`),
            KEY `student_id` (`student_id`),
            KEY `professional_id` (`professional_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `tasks` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `task_type` enum(\'support\',\'relationship\') NOT NULL DEFAULT \'support\',
            `name` varchar(50) NOT NULL,
            `due_date` datetime DEFAULT NULL,
            `registered_by_id` int(11) NOT NULL,
            `task_for_id` int(11) NOT NULL,
            `provider_id` int(11) NOT NULL,
            `client_id` int(11) NOT NULL,
            `task_status_id` int(11) NOT NULL,
            `category_id` int(11) NOT NULL,
            `reason_id` int(11) DEFAULT NULL,
            `criticality_id` int(11) DEFAULT NULL,
            `order_id` int(11) DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT current_timestamp(),
            `alter_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `registered_by_id` (`registered_by_id`),
            KEY `task_for_id` (`task_for_id`),
            KEY `provider_id` (`provider_id`),
            KEY `client_id` (`client_id`),
            KEY `task_status_id` (`task_status_id`),
            KEY `task_category_id` (`category_id`),
            KEY `order_id` (`order_id`),
            KEY `reason_id` (`reason_id`),
            KEY `criticality_id` (`criticality_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `tasks_surveys` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `token_url` binary(7) NOT NULL,
            `tasks_id` int(11) NOT NULL,
            `professional_id` int(11) DEFAULT NULL,
            `address_id` int(11) DEFAULT NULL,
            `surveyor_id` int(11) DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT current_timestamp(),
            `updated_at` datetime DEFAULT NULL,
            `type_survey` enum(\'collect\',\'delivery\',\'others\') DEFAULT NULL,
            `other_informations` text DEFAULT NULL,
            `belongings_removed` enum(\'no\',\'yes\') DEFAULT NULL,
            `vehicle_km` int(11) DEFAULT NULL,
            `status` enum(\'pending\',\'complete\',\'canceled\') NOT NULL DEFAULT \'pending\',
            `comments` text DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `status` (`status`),
            KEY `token_url` (`token_url`,`id`),
            KEY `tasks_surveys_address_id_fk` (`address_id`),
            KEY `tasks_surveys_people_id_fk` (`professional_id`),
            KEY `tasks_surveys_people_id_fk_2` (`surveyor_id`),
            KEY `tasks_surveys_tasks_id_fk` (`tasks_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `tasks_surveys_files` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `created_at` datetime NOT NULL DEFAULT current_timestamp(),
            `tasks_surveys_id` int(11) NOT NULL,
            `filename` varchar(255) DEFAULT NULL,
            `region` enum(\'front\',\'left_side\',\'right_side\',\'rear\',\'panel\',\'motor\',\'others\') DEFAULT NULL,
            `breakdown` enum(\'none\',\'kneaded\',\'absence\',\'chop\',\'broke\',\'scratched\',\'cracked\') DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `tasks_surveys_files_tasks_surveys_id_fk` (`tasks_surveys_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `task_interations` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `type` varchar(50) NOT NULL,
            `visibility` enum(\'private\',\'public\') NOT NULL DEFAULT \'private\',
            `body` longtext NOT NULL,
            `registered_by_id` int(11) NOT NULL,
            `file_id` int(11) DEFAULT NULL,
            `task_id` int(11) NOT NULL,
            `created_at` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `task_interations_ibfk_1` (`registered_by_id`),
            KEY `task_interations_ibfk_2` (`file_id`),
            KEY `task_interations_ibfk_3` (`task_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `task_status` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `task_type` enum(\'support\',\'relationship\') NOT NULL DEFAULT \'support\',
            `name` varchar(50) NOT NULL,
            `created_at` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `tax` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `tax_name` varchar(255) NOT NULL,
            `tax_type` enum(\'fixed\',\'percentage\') NOT NULL,
            `tax_subtype` enum(\'invoice\',\'kg\',\'order\') DEFAULT NULL,
            `people_id` int(11) NOT NULL,
            `state_origin_id` int(11) NOT NULL,
            `state_destination_id` int(11) NOT NULL,
            `tax_order` int(11) NOT NULL DEFAULT 0,
            `price` double NOT NULL,
            `minimum_price` double NOT NULL DEFAULT 0,
            `optional` tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `tax_name` (`tax_name`,`tax_type`,`tax_subtype`,`people_id`,`state_origin_id`,`state_destination_id`),
            KEY `people_id` (`people_id`),
            KEY `region_destination_id` (`state_destination_id`) USING BTREE,
            KEY `region_origin_id` (`state_origin_id`) USING BTREE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `team` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `company_team_id` int(11) NOT NULL,
            `contract_id` int(11) NOT NULL,
            `type` enum(\'school\',\'ead\',\'company\') NOT NULL DEFAULT \'school\',
            PRIMARY KEY (`id`),
            KEY `company_team` (`company_team_id`),
            KEY `contract_id` (`contract_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `translate` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `people_id` int(11) NOT NULL,
            `lang_id` int(11) NOT NULL,
            `translate_key` varchar(255) NOT NULL,
            `translate` text DEFAULT NULL,
            `status` enum(\'1\',\'2\',\'3\') NOT NULL DEFAULT \'1\',
            PRIMARY KEY (`id`),
            UNIQUE KEY `language_id` (`lang_id`,`translate_key`,`people_id`) USING BTREE,
            KEY `translate_key` (`translate_key`),
            KEY `status` (`status`),
            KEY `people_id` (`people_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `hash` varchar(255) NOT NULL,
            `oauth_user` varchar(20) DEFAULT NULL,
            `oauth_hash` varchar(40) DEFAULT NULL,
            `lost_password` varchar(60) DEFAULT NULL,
            `api_key` varchar(60) NOT NULL,
            `people_id` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_name` (`username`),
            UNIQUE KEY `api_key` (`api_key`),
            KEY `people_id` (`people_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');



    $this->addSql('ALTER TABLE `address`
            ADD CONSTRAINT `address_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `address_ibfk_2` FOREIGN KEY (`street_id`) REFERENCES `street` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `carrier_integration`
            ADD CONSTRAINT `carrier_integration_ibfk_1` FOREIGN KEY (`carrier_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `car_model`
            ADD CONSTRAINT `car_model_ibfk_1` FOREIGN KEY (`car_manufacturer_id`) REFERENCES `car_manufacturer` (`id`);
            ');

    $this->addSql('ALTER TABLE `car_year_price`
            ADD CONSTRAINT `car_year_price_ibfk_1` FOREIGN KEY (`car_manufacturer_id`) REFERENCES `car_manufacturer` (`id`),
            ADD CONSTRAINT `car_year_price_ibfk_2` FOREIGN KEY (`car_model_id`) REFERENCES `car_model` (`id`);
            ');
    $this->addSql('ALTER TABLE `category`
            ADD CONSTRAINT `category_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `category_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');
    $this->addSql('ALTER TABLE `city`
            ADD CONSTRAINT `city_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `state` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `company_expense`
            ADD CONSTRAINT `company_expense_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE,
            ADD CONSTRAINT `company_expense_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE,
            ADD CONSTRAINT `company_expense_ibfk_3` FOREIGN KEY (`provider_id`) REFERENCES `people` (`id`) ON DELETE CASCADE,
            ADD CONSTRAINT `company_expense_ibfk_4` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
            ');

    $this->addSql('ALTER TABLE `config`
            ADD CONSTRAINT `config_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');
    $this->addSql('ALTER TABLE `contract`
            ADD CONSTRAINT `contract_ibfk_1` FOREIGN KEY (`contract_parent_id`) REFERENCES `contract` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `contract_ibfk_2` FOREIGN KEY (`contract_model_id`) REFERENCES `contract_model` (`id`) ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `contract_people`
            ADD CONSTRAINT `contract_people_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contract` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `contract_people_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `contract_product`
            ADD CONSTRAINT `contract_product_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contract` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `contract_product_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `contract_product_payment`
            ADD CONSTRAINT `contract_product_payment_ibfk_5` FOREIGN KEY (`product_id`) REFERENCES `contract_product` (`id`) ON DELETE CASCADE,
            ADD CONSTRAINT `contract_product_payment_ibfk_6` FOREIGN KEY (`payer_id`) REFERENCES `people` (`id`) ON DELETE CASCADE,
            ADD CONSTRAINT `contract_product_payment_ibfk_7` FOREIGN KEY (`contract_id`) REFERENCES `contract` (`id`) ON DELETE CASCADE;
            ');

    $this->addSql('ALTER TABLE `delivery_region`
            ADD CONSTRAINT `delivery_region_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `delivery_region_city`
            ADD CONSTRAINT `delivery_region_city_ibfk_1` FOREIGN KEY (`delivery_region_id`) REFERENCES `delivery_region` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `delivery_region_city_ibfk_2` FOREIGN KEY (`city_id`) REFERENCES `city` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `delivery_restriction_material`
            ADD CONSTRAINT `delivery_restriction_material_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `delivery_restriction_material_ibfk_2` FOREIGN KEY (`product_material_id`) REFERENCES `product_material` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `delivery_tax`
            ADD CONSTRAINT `delivery_tax_ibfk_1` FOREIGN KEY (`region_origin_id`) REFERENCES `delivery_region` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `delivery_tax_ibfk_2` FOREIGN KEY (`region_destination_id`) REFERENCES `delivery_region` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `delivery_tax_ibfk_3` FOREIGN KEY (`delivery_tax_group_id`) REFERENCES `delivery_tax_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `delivery_tax_group`
            ADD CONSTRAINT `delivery_tax_group_ibfk_1` FOREIGN KEY (`carrier_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `discount_coupon`
            ADD CONSTRAINT `discount_coupon_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `discount_coupon_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `discount_coupon_ibfk_3` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `district`
            ADD CONSTRAINT `district_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `city` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `docs`
            ADD CONSTRAINT `company_ibfk` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `docs_ibfk_1` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `people_ibfk` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `document`
            ADD CONSTRAINT `document_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `document_ibfk_2` FOREIGN KEY (`document_type_id`) REFERENCES `document_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `email`
            ADD CONSTRAINT `FK_E7927C743147C936` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `imports`
            ADD CONSTRAINT `imports_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `imports_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`);
            ');

    $this->addSql('ALTER TABLE `invoice`
            ADD CONSTRAINT `invoice__ibfk_1i` FOREIGN KEY (`invoice_status_id`) REFERENCES `invoice_status` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `invoice__ibfk_2i` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`);
            ');

    $this->addSql('ALTER TABLE `invoice_log`
            ADD CONSTRAINT `invoice_log_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoice` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `invoice_log_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `labels`
            ADD CONSTRAINT `labels_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`),
            ADD CONSTRAINT `labels_ibfk_2` FOREIGN KEY (`carrier_id`) REFERENCES `people` (`id`),
            ADD CONSTRAINT `labels_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);
            ');

    $this->addSql('ALTER TABLE `language_country`
            ADD CONSTRAINT `language_country_ibfk_1` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `language_country_ibfk_2` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `lessons`
            ADD CONSTRAINT `lessons_ibfk_1` FOREIGN KEY (`lesson_category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `log`
            ADD CONSTRAINT `log_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `measure`
            ADD CONSTRAINT `measure_ibfk_1` FOREIGN KEY (`measure_type_id`) REFERENCES `measure_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `menu`
            ADD CONSTRAINT `menu_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `menu_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `menu_role`
            ADD CONSTRAINT `menu_role_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `menu_role_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `muniment`
            ADD CONSTRAINT `muniment_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `files` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `muniment_ibfk_2` FOREIGN KEY (`muniment_group_id`) REFERENCES `muniment_group` (`id`) ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `muniment_group`
            ADD CONSTRAINT `muniment_group_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `muniment_signature`
            ADD CONSTRAINT `muniment_signature_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `muniment_signature_ibfk_2` FOREIGN KEY (`muniment_id`) REFERENCES `muniment` (`id`) ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `nutritional_information`
            ADD CONSTRAINT `nutritional_information_ibfk_1` FOREIGN KEY (`nutritional_information_type_id`) REFERENCES `nutritional_information_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `nutritional_information_ibfk_2` FOREIGN KEY (`food_id`) REFERENCES `food` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `nutritional_information_type`
            ADD CONSTRAINT `nutritional_information_type_ibfk_1` FOREIGN KEY (`measure_id`) REFERENCES `measure` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `orders`
            ADD CONSTRAINT `fk_contract_idd` FOREIGN KEY (`contract_id`) REFERENCES `contract` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`discount_coupon_id`) REFERENCES `discount_coupon` (`id`),
            ADD CONSTRAINT `orders_ibfk_10d` FOREIGN KEY (`address_destination_id`) REFERENCES `address` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `orders_ibfk_11d` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `orders_ibfk_12d` FOREIGN KEY (`main_order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `orders_ibfk_1d` FOREIGN KEY (`quote_id`) REFERENCES `quote` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
            ADD CONSTRAINT `orders_ibfk_2d` FOREIGN KEY (`client_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `orders_ibfk_3d` FOREIGN KEY (`provider_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `orders_ibfk_4d` FOREIGN KEY (`retrieve_contact_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `orders_ibfk_5d` FOREIGN KEY (`delivery_contact_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `orders_ibfk_6d` FOREIGN KEY (`retrieve_people_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `orders_ibfk_7d` FOREIGN KEY (`delivery_people_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `orders_ibfk_8d` FOREIGN KEY (`payer_people_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `orders_ibfk_9d` FOREIGN KEY (`address_origin_id`) REFERENCES `address` (`id`) ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `order_invoice`
            ADD CONSTRAINT `order_invoice__ibfk_1i` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `order_invoice__ibfk_2i` FOREIGN KEY (`invoice_id`) REFERENCES `invoice` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `order_invoice_tax`
            ADD CONSTRAINT `order_invoice_tax_ibfk_2` FOREIGN KEY (`invoice_tax_id`) REFERENCES `invoice_tax` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `order_invoice_tax_ibfk_3` FOREIGN KEY (`issuer_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `order_invoice_tax_ibfk_4` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `order_log`
            ADD CONSTRAINT `order_log_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `order_log_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `order_package`
            ADD CONSTRAINT `order_package_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `order_tracking`
            ADD CONSTRAINT `order_tracking_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);
            ');

    $this->addSql('ALTER TABLE `people`
            ADD CONSTRAINT `people_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `files` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
            ADD CONSTRAINT `people_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `people_ibfk_3` FOREIGN KEY (`background_image`) REFERENCES `files` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
            ADD CONSTRAINT `people_ibfk_4` FOREIGN KEY (`alternative_image`) REFERENCES `files` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `people_carrier`
            ADD CONSTRAINT `people_carrier_ibfk_1` FOREIGN KEY (`carrier_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `people_carrier_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `people_client`
            ADD CONSTRAINT `people_client_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `people_client_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `people_domain`
            ADD CONSTRAINT `people_domain_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `people_employee`
            ADD CONSTRAINT `people_employee_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `people_employee_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `people_franchisee`
            ADD CONSTRAINT `people_franchisee_ibfk_1` FOREIGN KEY (`franchisee_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `people_franchisee_ibfk_2` FOREIGN KEY (`franchisor_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `people_order`
            ADD CONSTRAINT `people_order_ibfk_1` FOREIGN KEY (`people_client_id`) REFERENCES `people_client` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `people_procurator`
            ADD CONSTRAINT `people_procurator_ibfk_1` FOREIGN KEY (`procurator_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `people_procurator_ibfk_2` FOREIGN KEY (`grantor_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `people_procurator_ibfk_3` FOREIGN KEY (`muniment_signature_id`) REFERENCES `muniment_signature` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `people_provider`
            ADD CONSTRAINT `people_provider_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `people_provider_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `people_role`
            ADD CONSTRAINT `people_role_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `people_role_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `people_role_ibfk_3` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `people_salesman`
            ADD CONSTRAINT `people_salesman_ibfk_1` FOREIGN KEY (`salesman_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `people_salesman_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `people_states`
            ADD CONSTRAINT `people_states_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `people_states_ibfk_2` FOREIGN KEY (`state_id`) REFERENCES `state` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `product`
            ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`product_provider`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `product_ibfk_2` FOREIGN KEY (`product_parent`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `quote`
            ADD CONSTRAINT `quote_ibfk_3` FOREIGN KEY (`city_origin_id`) REFERENCES `city` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `quote_ibfk_4` FOREIGN KEY (`city_destination_id`) REFERENCES `city` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `quote_ibfk_5` FOREIGN KEY (`client_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `quote_ibfk_6` FOREIGN KEY (`provider_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `quote_ibfk_8` FOREIGN KEY (`carrier_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `quote_ibfk_9` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `quote_detail`
            ADD CONSTRAINT `quote_detail_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quote` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `quote_detail_ibfk_3` FOREIGN KEY (`region_origin_id`) REFERENCES `delivery_region` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
            ADD CONSTRAINT `quote_detail_ibfk_4` FOREIGN KEY (`region_destination_id`) REFERENCES `delivery_region` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
            ADD CONSTRAINT `quote_detail_ibfk_5` FOREIGN KEY (`delivery_tax_id`) REFERENCES `delivery_tax` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `rating`
            ADD CONSTRAINT `rating_ibfk_1` FOREIGN KEY (`people_evaluator`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `rating_ibfk_2` FOREIGN KEY (`order_rated`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `rating_ibfk_3` FOREIGN KEY (`people_rated`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `retrieve`
            ADD CONSTRAINT `retrieve_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `school_class_files`
            ADD CONSTRAINT `school_class_files_ibfk_1` FOREIGN KEY (`id`) REFERENCES `school_class` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `school_class_files_ibfk_2` FOREIGN KEY (`school_class_id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `school_team_schedule`
            ADD CONSTRAINT `school_team_schedule_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `team` (`id`),
            ADD CONSTRAINT `school_team_schedule_ibfk_2` FOREIGN KEY (`professional_id`) REFERENCES `people_professional` (`id`);
            ');

    $this->addSql('ALTER TABLE `school_professional_weekly`
            ADD CONSTRAINT `school_professional_weekly_ibfk_2` FOREIGN KEY (`professional_id`) REFERENCES `people_professional` (`id`);
            ');

    $this->addSql('ALTER TABLE `seo_url`
            ADD CONSTRAINT `seo_url_ibfk_1` FOREIGN KEY (`city_origin`) REFERENCES `city` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `seo_url_ibfk_2` FOREIGN KEY (`city_destination`) REFERENCES `city` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `seo_url_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `service_invoice_tax`
            ADD CONSTRAINT `service_invoice_tax_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoice` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `service_invoice_tax_ibfk_2` FOREIGN KEY (`invoice_tax_id`) REFERENCES `invoice_tax` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `service_invoice_tax_ibfk_3` FOREIGN KEY (`issuer_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');
    $this->addSql('ALTER TABLE `state`
            ADD CONSTRAINT `state_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');
    $this->addSql('ALTER TABLE `street`
            ADD CONSTRAINT `street_ibfk_1` FOREIGN KEY (`district_id`) REFERENCES `district` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `street_ibfk_2` FOREIGN KEY (`cep_id`) REFERENCES `cep` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `tasks`
            ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`registered_by_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `tasks_ibfk_10` FOREIGN KEY (`criticality_id`) REFERENCES `category` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`task_for_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`provider_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `tasks_ibfk_4` FOREIGN KEY (`client_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `tasks_ibfk_5` FOREIGN KEY (`task_status_id`) REFERENCES `status` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `tasks_ibfk_6` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON UPDATE CASCADE,
            ADD CONSTRAINT `tasks_ibfk_8` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
            ADD CONSTRAINT `tasks_ibfk_9` FOREIGN KEY (`reason_id`) REFERENCES `category` (`id`) ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `tasks_surveys`
            ADD CONSTRAINT `tasks_surveys_address_id_fk` FOREIGN KEY (`address_id`) REFERENCES `address` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `tasks_surveys_people_id_fk` FOREIGN KEY (`professional_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `tasks_surveys_people_id_fk_2` FOREIGN KEY (`surveyor_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `tasks_surveys_tasks_id_fk` FOREIGN KEY (`tasks_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `tasks_surveys_files`
            ADD CONSTRAINT `tasks_surveys_files_tasks_surveys_id_fk` FOREIGN KEY (`tasks_surveys_id`) REFERENCES `tasks_surveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `task_interations`
            ADD CONSTRAINT `task_interations_ibfk_1` FOREIGN KEY (`registered_by_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `task_interations_ibfk_2` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `task_interations_ibfk_3` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `tax`
            ADD CONSTRAINT `tax_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `tax_ibfk_2` FOREIGN KEY (`state_origin_id`) REFERENCES `state` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `tax_ibfk_3` FOREIGN KEY (`state_destination_id`) REFERENCES `state` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ');

    $this->addSql('ALTER TABLE `users`
            ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;            ;
            ');
  }

  public function down(Schema $schema): void
  {
    // this down() migration is auto-generated, please modify it to your needs

  }
}
