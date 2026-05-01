CREATE DATABASE IF NOT EXISTS `partyapp`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `partyapp`;

CREATE TABLE IF NOT EXISTS `district_projects` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `district_id` VARCHAR(80) NOT NULL,
    `project_name` VARCHAR(255) NOT NULL,
    `project_slug` VARCHAR(255) NOT NULL,
    `project_image` LONGTEXT DEFAULT NULL,
    `project_description` TEXT NOT NULL,
    `category` VARCHAR(120) DEFAULT NULL,
    `status` VARCHAR(80) DEFAULT 'Planning',
    `budget` VARCHAR(80) DEFAULT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_project_slug_per_district` (`district_id`, `project_slug`),
    KEY `idx_district_projects_district_active` (`district_id`, `is_active`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `district_projects`
    (`district_id`, `project_name`, `project_slug`, `project_image`, `project_description`, `category`, `status`, `budget`, `display_order`)
VALUES
    ('hambantota', 'Hambantota Harbour Gateway', 'hambantota-harbour-gateway', NULL, 'Port-led industrial growth', 'Logistics', 'Active', 'LKR 88M', 1),
    ('hambantota', 'Green Fuel Innovation Yard', 'green-fuel-innovation-yard', NULL, 'Clean fuels and storage demonstration', 'Energy', 'Planning', 'LKR 36M', 2),
    ('hambantota', 'Southern Dry Zone Park', 'southern-dry-zone-park', NULL, 'Industrial and services campus', 'Industry', 'Featured', 'LKR 24M', 3);

SELECT
    `district_id`,
    `project_name`,
    `project_slug`,
    `project_image`,
    `project_description`,
    `category`,
    `status`,
    `budget`,
    `display_order`
FROM `district_projects`
WHERE `is_active` = 1
ORDER BY `district_id`, `display_order`, `id`;
