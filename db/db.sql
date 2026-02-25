-- translation_urls
CREATE TABLE `translation_urls` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `url` TEXT NOT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `is_api` TINYINT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `translation_urls_active_index` (`active`),
    INDEX `translation_urls_is_api_index` (`is_api`),
    INDEX `translation_urls_active_is_api_index` (`active`, `is_api`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- translation_progress
CREATE TABLE `translation_progress` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type` ENUM('string_extraction', 'translation') NOT NULL,
    `locale` VARCHAR(10) NULL DEFAULT NULL,
    `total` INT UNSIGNED NOT NULL DEFAULT 0,
    `completed` INT UNSIGNED NOT NULL DEFAULT 0,
    `failed` INT UNSIGNED NOT NULL DEFAULT 0,
    `started_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `translation_progress_type_locale_unique` (`type`, `locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;