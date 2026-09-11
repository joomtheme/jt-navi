CREATE TABLE IF NOT EXISTS `#__jtnavi_diagnostics` (
 `id` tinyint unsigned NOT NULL,
 `result_code` varchar(64) NOT NULL,
 `http_status` smallint unsigned NOT NULL DEFAULT 0,
 `checked_at` datetime NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
