CREATE DATABASE IF NOT EXISTS `partyapp`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `partyapp`;

CREATE TABLE IF NOT EXISTS `hero_slider` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `eyebrow` VARCHAR(120) NOT NULL,
    `eyebrow_color` VARCHAR(16) NOT NULL DEFAULT '#FFF7EA',
    `title` VARCHAR(255) NOT NULL,
    `title_color` VARCHAR(16) NOT NULL DEFAULT '#FFF7EA',
    `description` TEXT NOT NULL,
    `description_color` VARCHAR(16) NOT NULL DEFAULT '#FFF2DD',
    `media_type` ENUM('image', 'video') NOT NULL DEFAULT 'image',
    `media_path` VARCHAR(255) DEFAULT NULL,
    `video_start_time` VARCHAR(16) DEFAULT NULL,
    `video_end_time` VARCHAR(16) DEFAULT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_hero_slider_active_order` (`is_active`, `display_order`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `hero_slider`
    (`eyebrow`, `eyebrow_color`, `title`, `title_color`, `description`, `description_color`, `media_type`, `media_path`, `video_start_time`, `video_end_time`, `display_order`, `is_active`)
VALUES
    ('National Vision', '#FFF7EA', 'Build a stronger digital presence with connected district stories.', '#FFF7EA', 'A modern single-page experience for projects, leadership, and future public-facing components.', '#FFF2DD', 'image', 'images/namal.jpg', NULL, NULL, 1, 1),
    ('Project Focus', '#FFF7EA', 'Showcase regional opportunities with smoother discovery and smarter presentation.', '#FFF7EA', 'Maps, district projects, and leadership content now live inside one scalable page structure.', '#FFF2DD', 'video', 'images/vision_piller/vision-bg.mp4', '00:27', '00:32', 2, 1),
    ('Leadership Layer', '#FFF7EA', 'Bring together strategy, place, and identity in one polished interface.', '#FFF7EA', 'The layout is ready for future sections while staying responsive across mobile, tablet, and desktop.', '#FFF2DD', 'image', 'images/mahinda.jpg', NULL, NULL, 3, 1);

/*
Run this on an existing database before using the new text color and video clip controls:

ALTER TABLE `hero_slider`
    ADD COLUMN `eyebrow_color` VARCHAR(16) NOT NULL DEFAULT '#FFF7EA' AFTER `eyebrow`,
    ADD COLUMN `title_color` VARCHAR(16) NOT NULL DEFAULT '#FFF7EA' AFTER `title`,
    ADD COLUMN `description_color` VARCHAR(16) NOT NULL DEFAULT '#FFF2DD' AFTER `description`,
    ADD COLUMN `video_start_time` VARCHAR(16) DEFAULT NULL AFTER `media_path`,
    ADD COLUMN `video_end_time` VARCHAR(16) DEFAULT NULL AFTER `video_start_time`;

Example update query:

UPDATE `hero_slider`
SET
    `eyebrow_color` = '#FFE9C2',
    `title_color` = '#FFFFFF',
    `description_color` = '#F7E7CE',
    `video_start_time` = '00:27',
    `video_end_time` = '00:32'
WHERE `id` = 2;

If `video_start_time` and `video_end_time` are both `NULL`, the full video will play.
These two columns only affect rows where `media_type = 'video'`.
Supported formats include `00:27`, `01:15`, `01:02:10`, or plain seconds like `27.5`.
*/

/*
Simple query used by index.php:
SELECT eyebrow, eyebrow_color, title, title_color, description, description_color, media_type, media_path, video_start_time, video_end_time, display_order
FROM hero_slider
WHERE is_active = 1
ORDER BY display_order, id;
*/
