CREATE TABLE IF NOT EXISTS `#__websourcepreorder_rules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `published` tinyint unsigned NOT NULL DEFAULT 1,
  `featured` tinyint unsigned NOT NULL DEFAULT 0,
  `in_ticker` tinyint unsigned NOT NULL DEFAULT 1,
  `in_list` tinyint unsigned NOT NULL DEFAULT 1,
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `date_release` datetime DEFAULT NULL,
  `end_on_release` tinyint unsigned NOT NULL DEFAULT 1,
  `price_mode` tinyint unsigned NOT NULL DEFAULT 0,
  `price_value` decimal(20,6) NOT NULL DEFAULT 0,
  `quota` int unsigned NOT NULL DEFAULT 0,
  `max_per_order` int unsigned NOT NULL DEFAULT 0,
  `max_per_customer` int unsigned NOT NULL DEFAULT 0,
  `label` varchar(255) NOT NULL DEFAULT '',
  `message` text,
  `translations` text,
  `state` tinyint unsigned NOT NULL DEFAULT 0,
  `prev_state` text,
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_state` (`published`,`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__websourcepreorder_bookings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `rule_id` int unsigned NOT NULL,
  `order_id` int unsigned NOT NULL DEFAULT 0,
  `order_product_id` int unsigned NOT NULL DEFAULT 0,
  `hika_user_id` int unsigned NOT NULL DEFAULT 0,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `email` varchar(255) NOT NULL DEFAULT '',
  `customer_name` varchar(255) NOT NULL DEFAULT '',
  `lang` varchar(10) NOT NULL DEFAULT '',
  `product_id` int unsigned NOT NULL DEFAULT 0,
  `product_name` varchar(255) NOT NULL DEFAULT '',
  `quantity` int unsigned NOT NULL DEFAULT 1,
  `unit_price` decimal(20,6) NOT NULL DEFAULT 0,
  `release_date` datetime DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT 0,
  `created` datetime NOT NULL,
  `notified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_orderproduct` (`order_product_id`),
  KEY `idx_rule_status` (`rule_id`,`status`),
  KEY `idx_hika_user` (`hika_user_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__websourcepreorder_subscribers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `lang` varchar(10) NOT NULL DEFAULT '',
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `token` varchar(64) NOT NULL,
  `confirmed` tinyint unsigned NOT NULL DEFAULT 0,
  `source` varchar(32) NOT NULL DEFAULT '',
  `ip` varchar(45) NOT NULL DEFAULT '',
  `created` datetime NOT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `last_sent` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_email` (`email`),
  UNIQUE KEY `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__websourcepreorder_mails` (
  `mail_key` varchar(32) NOT NULL,
  `lang` varchar(10) NOT NULL,
  `subject` varchar(255) NOT NULL DEFAULT '',
  `body` mediumtext,
  PRIMARY KEY (`mail_key`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__websourcepreorder_state` (
  `name` varchar(64) NOT NULL,
  `value` text,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__websourcepreorder_ratelimit` (
  `ip` varchar(45) NOT NULL,
  `hits` int unsigned NOT NULL DEFAULT 0,
  `window_start` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
