CREATE TABLE IF NOT EXISTS `vision_pillars` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `pillar_tag` VARCHAR(12) NOT NULL,
    `title` VARCHAR(160) NOT NULL,
    `short_description` TEXT NOT NULL,
    `full_description` TEXT NOT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `accent_primary` VARCHAR(20) DEFAULT '#8f463d',
    `accent_secondary` VARCHAR(20) DEFAULT '#d88b63',
    `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_vision_pillars_active_order` (`is_active`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `vision_pillars` (
    `pillar_tag`,
    `title`,
    `short_description`,
    `full_description`,
    `image_path`,
    `accent_primary`,
    `accent_secondary`,
    `display_order`,
    `is_active`
) VALUES
('ECO', 'Economy & Jobs', 'Build a confident economy that creates better jobs, stronger enterprise, and steady household opportunity.', 'We focus on local industry, export competitiveness, tourism renewal, and practical support for small and medium businesses to expand employment across every district.', NULL, '#a24d43', '#d88b63', 1, 1),
('EDU', 'Education Reform', 'Modernize learning so every student is prepared for higher education, work, and national leadership.', 'Our approach strengthens teachers, updates curriculum quality, expands technical pathways, and connects schools with the skills needed in a changing economy.', NULL, '#8d433d', '#e0b66f', 2, 1),
('HLT', 'Healthcare for All', 'Protect families with accessible care, stronger hospitals, and dependable public health services.', 'We aim to improve frontline clinics, medicine access, maternal care, and regional health infrastructure so quality treatment is available beyond major cities.', NULL, '#a5534a', '#d97873', 3, 1),
('INF', 'Infrastructure Development', 'Invest in transport, logistics, utilities, and public systems that support long-term national growth.', 'From roads and ports to water systems and public services, we prioritize durable infrastructure that improves connectivity and economic productivity.', NULL, '#7f3a35', '#c97057', 4, 1),
('ENV', 'Environment & Clean Energy', 'Advance responsible growth with cleaner energy, better stewardship, and resilience for future generations.', 'Our vision supports conservation, modern waste systems, climate resilience, and renewable energy solutions that reduce long-term national vulnerability.', NULL, '#6d6948', '#a7bf6d', 5, 1),
('DIG', 'Digital Sri Lanka', 'Create a smarter state with digital services, stronger connectivity, and innovation-led opportunity.', 'We want citizens and businesses to access faster services, dependable infrastructure, and technology pathways that raise efficiency across government and industry.', NULL, '#4b5f8f', '#64a9d8', 6, 1),
('YTH', 'Youth Empowerment', 'Give young people the confidence, skills, and opportunity to shape the country’s next chapter.', 'This includes entrepreneurship support, leadership development, sports and creative opportunities, and practical employment pathways for emerging talent.', NULL, '#99504c', '#e2a96f', 7, 1),
('GOV', 'Good Governance', 'Strengthen trust through accountability, discipline, transparency, and service-driven leadership.', 'We believe public institutions must be more responsive, more transparent, and more focused on delivery so citizens see integrity in action.', NULL, '#73403f', '#c08a70', 8, 1);
