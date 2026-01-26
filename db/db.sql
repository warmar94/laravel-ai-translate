CREATE TABLE IF NOT EXISTS `translation_progress` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('url_collection','string_extraction','translation') NOT NULL,
  `locale` varchar(10) DEFAULT NULL,
  `total` int(10) unsigned NOT NULL DEFAULT 0,
  `completed` int(10) unsigned NOT NULL DEFAULT 0,
  `failed` int(10) unsigned NOT NULL DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `translation_progress_type_locale_unique` (`type`,`locale`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
