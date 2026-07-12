CREATE DATABASE IF NOT EXISTS `partyapp`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `partyapp`;

CREATE TABLE IF NOT EXISTS `district_coordination_heads` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `coordinator_name` VARCHAR(255) NOT NULL,
    `district_name` VARCHAR(120) NOT NULL,
    `phone_number` VARCHAR(40) DEFAULT NULL,
    `photo_path` VARCHAR(255) DEFAULT NULL,
    `wikipedia_url` VARCHAR(500) NOT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_district_coordination_heads_active_order` (`is_active`, `display_order`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `district_coordination_heads` (
    `coordinator_name`,
    `district_name`,
    `phone_number`,
    `photo_path`,
    `wikipedia_url`,
    `display_order`,
    `is_active`
) VALUES
('Mahinda Rajapaksa', 'Hambantota', NULL, 'images/mahinda.jpg', 'https://en.wikipedia.org/wiki/Mahinda_Rajapaksa', 1, 1),
('Namal Rajapaksa', 'Matara', NULL, 'images/namal.jpg', 'https://en.wikipedia.org/wiki/Namal_Rajapaksa', 2, 1),
('Sagara Kariyawasam', 'Galle', NULL, 'images/sagala.jpg', 'https://en.wikipedia.org/wiki/Sagara_Kariyawasam', 3, 1),
('Johnston Fernando', 'Kurunegala', NULL, 'images/Johnston.jpg', 'https://en.wikipedia.org/wiki/Johnston_Fernando', 4, 1),
('D. V. Chanaka', 'Badulla', NULL, 'images/chanaka.jpg', 'https://en.wikipedia.org/wiki/D._V._Chanaka', 5, 1);

/*
To add the phone column to an existing table:
ALTER TABLE `district_coordination_heads`
    ADD COLUMN `phone_number` VARCHAR(40) DEFAULT NULL AFTER `district_name`;
*/

/*
Query used by the page:
SELECT coordinator_name, district_name, phone_number, photo_path, wikipedia_url, display_order
FROM district_coordination_heads
WHERE is_active = 1
ORDER BY display_order ASC, id ASC;
*/
