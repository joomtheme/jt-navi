CREATE TABLE IF NOT EXISTS `#__jtnavi_usage` (
 `id` tinyint unsigned NOT NULL,
 `day` date NOT NULL,
 `requests` int unsigned NOT NULL DEFAULT 0,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO `#__jtnavi_usage` (`id`, `day`, `requests`) VALUES (1, '2000-01-01', 0);
