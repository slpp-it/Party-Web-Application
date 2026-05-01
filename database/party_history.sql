CREATE DATABASE IF NOT EXISTS `partyapp`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `partyapp`;

CREATE TABLE IF NOT EXISTS `party_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `history_year` VARCHAR(10) NOT NULL,
    `event_title` VARCHAR(255) NOT NULL,
    `event_description` TEXT NOT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `accent_primary` VARCHAR(20) NOT NULL DEFAULT '#8f463d',
    `accent_secondary` VARCHAR(20) NOT NULL DEFAULT '#e4bf6d',
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_party_history_year_order` (`is_active`, `history_year`, `display_order`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `party_history` (
    `history_year`,
    `event_title`,
    `event_description`,
    `image_path`,
    `accent_primary`,
    `accent_secondary`,
    `display_order`,
    `is_active`
) VALUES
('2020', 'National leadership transition', 'A year of transition, coordination, and renewed public-facing structure across the party.', 'images/history_cards/basil.jpg', '#8f463d', '#e4bf6d', 1, 1),
('2019', 'Presidential victory', 'A decisive election year that reshaped organization, momentum, and public attention.', 'images/mahinda.jpg', '#a94e43', '#f0d79a', 2, 1),
('2015', 'Rebuilding the movement', 'Attention shifted to consolidation, local coordination, and rebuilding confidence across districts.', 'images/namal.jpg', '#b76854', '#efcf93', 3, 1),
('2010', 'Development momentum', 'Infrastructure, regional development, and national identity messaging carried strong visibility.', 'images/sagala.jpg', '#9f4f47', '#e6b765', 4, 1);

UPDATE `party_history`
SET `image_path` = 'images/history_cards/basil.jpg'
WHERE `history_year` = '2020' AND `event_title` = 'National leadership transition';

UPDATE `party_history`
SET `image_path` = 'images/mahinda.jpg'
WHERE `history_year` = '2019' AND `event_title` = 'Presidential victory';

UPDATE `party_history`
SET `image_path` = 'images/namal.jpg'
WHERE `history_year` = '2015' AND `event_title` = 'Rebuilding the movement';

UPDATE `party_history`
SET `image_path` = 'images/sagala.jpg'
WHERE `history_year` = '2010' AND `event_title` = 'Development momentum';

/*
Query used by the page:
SELECT history_year, event_title, event_description, image_path, accent_primary, accent_secondary, display_order
FROM party_history
WHERE is_active = 1
ORDER BY history_year ASC, display_order ASC, id ASC;
*/
