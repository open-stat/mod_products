CREATE TABLE `mod_products` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `parent_id` int unsigned DEFAULT NULL,
    `section` varchar(255) DEFAULT NULL,
    `category` varchar(255) DEFAULT NULL,
    `title` varchar(500) DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `parent_id` (`parent_id`),
    CONSTRAINT `fk1_mod_products` FOREIGN KEY (`parent_id`) REFERENCES `mod_products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `mod_products_links` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `product_id` int unsigned NOT NULL,
    `url` varchar(500) DEFAULT NULL,
    `count_failed_try` int unsigned DEFAULT NULL,
    `is_available` enum('Y','N') NOT NULL DEFAULT 'Y',
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `fk1_mod_products_links` FOREIGN KEY (`product_id`) REFERENCES `mod_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `mod_products_pages` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `shop_name` varchar(255) DEFAULT NULL,
    `url` varchar(500) DEFAULT NULL,
    `section` varchar(255) DEFAULT NULL,
    `category` varchar(255) DEFAULT NULL,
    `content` mediumtext,
    `content_hash` varchar(255) DEFAULT NULL,
    `is_parsed_sw` enum('Y','N') NOT NULL DEFAULT 'N',
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `mod_products_prices` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `product_id` int unsigned DEFAULT NULL,
    `shop_id` int unsigned DEFAULT NULL,
    `link_id` int unsigned DEFAULT NULL,
    `city` varchar(50) DEFAULT NULL,
    `is_available` enum('Y','N') NOT NULL DEFAULT 'Y',
    `price` decimal(8,2) DEFAULT NULL,
    `measure` varchar(20) DEFAULT NULL,
    `quantity` decimal(8,2) DEFAULT NULL,
    `unit` varchar(20) DEFAULT NULL,
    `currency` varchar(3) DEFAULT NULL,
    `delivery_price` decimal(8,2) DEFAULT NULL,
    `delivery_currency` varchar(3) DEFAULT NULL,
    `delivery_days` int unsigned DEFAULT NULL,
    `halva_price` decimal(8,2) DEFAULT NULL,
    `halva_currency` varchar(3) DEFAULT NULL,
    `halva_term` int unsigned DEFAULT NULL,
    `warranty` int DEFAULT NULL,
    `date_mark` timestamp NULL DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `product_id` (`product_id`),
    KEY `shop_id` (`shop_id`),
    KEY `link_id` (`link_id`),
    CONSTRAINT `fk1_mod_products_prices` FOREIGN KEY (`product_id`) REFERENCES `mod_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_products_prices` FOREIGN KEY (`shop_id`) REFERENCES `mod_products_shops` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk3_mod_products_prices` FOREIGN KEY (`link_id`) REFERENCES `mod_products_links` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `mod_products_shops` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(500) DEFAULT NULL,
    `source_name` varchar(255) NOT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;