-- Database Structure for GPT33 Investment Platform
-- Generated dynamically after updates on 2026-05-21
-- All test users removed except Admin; transaction and earnings history reset.

CREATE DATABASE IF NOT EXISTS `gpt33_db`;
USE `gpt33_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `referral_code` varchar(10) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `deposit_balance` decimal(15,8) DEFAULT 0.00000000,
  `earnings_balance` decimal(15,8) DEFAULT 0.00000000,
  `referred_by` int(11) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `is_banned` tinyint(1) DEFAULT 0,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tier` varchar(50) DEFAULT 'Bronze',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `referral_code` (`referral_code`),
  KEY `referred_by` (`referred_by`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`referred_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed data for `users`
INSERT INTO `users` (`id`, `referral_code`, `full_name`, `email`, `password_hash`, `deposit_balance`, `earnings_balance`, `referred_by`, `is_admin`, `is_banned`, `last_login_ip`, `created_at`, `tier`) VALUES ('1', 'T7QLRP6C', 'Admin', 'ranaumairnawaz@gmail.com', '$2y$10$8tFRbxMcllcG1VKyxc6S2OovGlQaXjOP8pel423q.pIqfOdhUP08a', '0.00000000', '0.00000000', NULL, '1', '0', '::1', '2026-05-08 16:45:34', 'Bronze');

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_name` varchar(255) DEFAULT 'GPT32',
  `currency_rate` decimal(10,4) DEFAULT 1.0000,
  `whatsapp_link` varchar(255) DEFAULT '',
  `telegram_link` varchar(255) DEFAULT '',
  `ref_level_1` decimal(5,2) DEFAULT 5.00,
  `ref_level_2` decimal(5,2) DEFAULT 4.00,
  `ref_level_3` decimal(5,2) DEFAULT 3.00,
  `ref_level_4` decimal(5,2) DEFAULT 2.00,
  `ref_level_5` decimal(5,2) DEFAULT 1.00,
  `is_maintenance` tinyint(1) DEFAULT 0,
  `announcement_text` text DEFAULT NULL,
  `announcement_active` tinyint(1) DEFAULT 0,
  `announcement_image` varchar(255) DEFAULT NULL,
  `deposit_bonus` decimal(5,2) DEFAULT 0.00,
  `broadcast_followers_base` int(11) DEFAULT 3450,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed data for `settings`
INSERT INTO `settings` (`id`, `site_name`, `currency_rate`, `whatsapp_link`, `telegram_link`, `ref_level_1`, `ref_level_2`, `ref_level_3`, `ref_level_4`, `ref_level_5`, `is_maintenance`, `announcement_text`, `announcement_active`, `announcement_image`, `deposit_bonus`, `broadcast_followers_base`) VALUES ('1', 'GPT33', '1.0000', '', '', '5.00', '4.00', '3.00', '2.00', '1.00', '0', '', '0', NULL, '0.00', '3450');

DROP TABLE IF EXISTS `tiers`;
CREATE TABLE `tiers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `min_investment` decimal(10,2) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed data for `tiers`
INSERT INTO `tiers` (`id`, `name`, `min_investment`, `color`, `icon`) VALUES ('1', 'Bronze', '0.00', '#cd7f32', 'fa-solid fa-award');
INSERT INTO `tiers` (`id`, `name`, `min_investment`, `color`, `icon`) VALUES ('2', 'Silver', '1000.00', '#c0c0c0', 'fa-solid fa-medal');
INSERT INTO `tiers` (`id`, `name`, `min_investment`, `color`, `icon`) VALUES ('3', 'Gold', '5000.00', '#ffd700', 'fa-solid fa-crown');
INSERT INTO `tiers` (`id`, `name`, `min_investment`, `color`, `icon`) VALUES ('4', 'Platinum', '10000.00', '#e5e4e2', 'fa-solid fa-gem');

DROP TABLE IF EXISTS `banned_ips`;
CREATE TABLE `banned_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `payment_methods`;
CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `details` text NOT NULL,
  `copyable_fields` text DEFAULT NULL,
  `min_amount` decimal(15,2) DEFAULT 1.00,
  `max_amount` decimal(15,2) DEFAULT 10000.00,
  `is_active` tinyint(1) DEFAULT 1,
  `currency` varchar(10) DEFAULT 'USD',
  `rate` decimal(15,8) DEFAULT 1.00000000,
  `qr_code` varchar(255) DEFAULT NULL,
  `fixed_fee` decimal(10,4) DEFAULT 0.0000,
  `percent_fee` decimal(10,4) DEFAULT 0.0000,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed data for `payment_methods`
INSERT INTO `payment_methods` (`id`, `name`, `details`, `copyable_fields`, `min_amount`, `max_amount`, `is_active`, `currency`, `rate`, `qr_code`, `fixed_fee`, `percent_fee`) VALUES ('1', 'EasyPaisa', 'Send Carefully', '[{\"label\":\"Account Name\",\"value\":\"GPT33\"},{\"label\":\"Account Number\",\"value\":\"03001234567\"}]', '10.00', '1000.00', '1', 'PKR', '277.96000000', NULL, '0.0000', '0.0000');
INSERT INTO `payment_methods` (`id`, `name`, `details`, `copyable_fields`, `min_amount`, `max_amount`, `is_active`, `currency`, `rate`, `qr_code`, `fixed_fee`, `percent_fee`) VALUES ('2', 'Bank Transfer', 'Please send payment carefully if you send to wrong account, company will not be responsible.', '[{\"label\":\"Bank Name\",\"value\":\"Test Bank\"},{\"label\":\"Account Title\",\"value\":\"Test Account\"},{\"label\":\"Account Number\",\"value\":\"PK43TST28374528647\"}]', '1.00', '10000.00', '1', 'PKR', '277.96000000', NULL, '0.0000', '0.0000');
INSERT INTO `payment_methods` (`id`, `name`, `details`, `copyable_fields`, `min_amount`, `max_amount`, `is_active`, `currency`, `rate`, `qr_code`, `fixed_fee`, `percent_fee`) VALUES ('3', 'USDT BEP20', 'Send USDT using BEP20/ (BSC)BNB SMART CHAIN', '[{\"label\":\"Address\",\"value\":\"0xdb06e175182cac7dfea759238ae7dfe411869b42\"}]', '10.00', '5000.00', '1', 'USDT', '1.00000000', 'assets/img/qr/qr_1778501170.jpeg', '1.0000', '0.0000');

DROP TABLE IF EXISTS `withdrawal_methods`;
CREATE TABLE `withdrawal_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `instructions` text DEFAULT NULL,
  `min_amount` decimal(15,2) DEFAULT 1.00,
  `max_amount` decimal(15,2) DEFAULT 10000.00,
  `is_active` tinyint(1) DEFAULT 1,
  `currency` varchar(10) DEFAULT 'USD',
  `rate` decimal(15,8) DEFAULT 1.00000000,
  `fixed_fee` decimal(10,4) DEFAULT 0.0000,
  `percent_fee` decimal(10,4) DEFAULT 0.0000,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed data for `withdrawal_methods`
INSERT INTO `withdrawal_methods` (`id`, `name`, `instructions`, `min_amount`, `max_amount`, `is_active`, `currency`, `rate`, `fixed_fee`, `percent_fee`) VALUES ('1', 'EasyPaisa', 'provide account details carefully.', '0.50', '5000.00', '1', 'PKR', '277.96000000', '0.0000', '0.0000');

DROP TABLE IF EXISTS `plans`;
CREATE TABLE `plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `hourly_rate` decimal(10,4) NOT NULL,
  `min_invest` decimal(15,2) DEFAULT 10.00,
  `max_invest` decimal(15,2) DEFAULT 10000.00,
  `is_active` tinyint(1) DEFAULT 1,
  `fixed_price` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed data for `plans`
INSERT INTO `plans` (`id`, `name`, `hourly_rate`, `min_invest`, `max_invest`, `is_active`, `fixed_price`) VALUES ('1', 'GPT-3.5', '0.1500', '10.00', '100.00', '1', '10.00');
INSERT INTO `plans` (`id`, `name`, `hourly_rate`, `min_invest`, `max_invest`, `is_active`, `fixed_price`) VALUES ('2', 'GPT-4', '0.1700', '20.00', '200.00', '1', '20.00');
INSERT INTO `plans` (`id`, `name`, `hourly_rate`, `min_invest`, `max_invest`, `is_active`, `fixed_price`) VALUES ('3', 'GPT-4 Turbo', '0.1800', '25.00', '250.00', '1', '25.00');

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('deposit','withdrawal','referral_commission','manual_adjustment','investment','profit','hourly_profit') DEFAULT NULL,
  `amount` decimal(15,8) NOT NULL,
  `method_name` varchar(100) DEFAULT NULL,
  `account_details` text DEFAULT NULL,
  `screenshot` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `user_investments`;
CREATE TABLE `user_investments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `invested_amount` decimal(15,8) NOT NULL,
  `total_profit` decimal(15,8) DEFAULT 0.00000000,
  `status` enum('active','completed') DEFAULT 'active',
  `last_profit_calc` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `plan_id` (`plan_id`),
  CONSTRAINT `user_investments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_investments_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `chat_messages`;
CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `admin_logs`;
CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `support_links`;
CREATE TABLE `support_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform_name` varchar(50) NOT NULL,
  `link` varchar(255) NOT NULL,
  `icon` varchar(50) DEFAULT 'fa-solid fa-link',
  `color_bg` varchar(20) DEFAULT '#3b82f6',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed data for `support_links`
INSERT INTO `support_links` (`id`, `platform_name`, `link`, `icon`, `color_bg`, `is_active`, `created_at`) VALUES ('2', 'Test Support', 'https://wa.me/+923164851631', 'fa-brands fa-whatsapp', '#25d366', '1', '2026-05-10 01:51:16');

DROP TABLE IF EXISTS `user_notifications`;
CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `admin_notifications`;
CREATE TABLE `admin_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `live_feed`;
CREATE TABLE `live_feed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('admin','withdrawal') NOT NULL DEFAULT 'withdrawal',
  `user_name` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `rate_limits`;
CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  `hits` int(11) DEFAULT 1,
  `window_end` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_identifier_endpoint` (`identifier`,`endpoint`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
