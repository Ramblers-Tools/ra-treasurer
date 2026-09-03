CREATE TABLE IF NOT EXISTS `#__ra_bookings` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`state` TINYINT(1)  NULL  DEFAULT 1,
`ordering` INT(11)  NULL  DEFAULT 0,
`checked_out` INT(11)  UNSIGNED,
`checked_out_time` DATETIME NULL  DEFAULT NULL ,
`created_by` INT(11)  NULL  DEFAULT 0,
`modified_by` INT(11)  NULL  DEFAULT 0,
`member_name` VARCHAR(255)  NOT NULL ,
`event_name` VARCHAR(30)  NULL  DEFAULT "",
`amount_due` VARCHAR(255)  NULL  DEFAULT "",
`event_date` DATETIME NULL  DEFAULT NULL ,
`created` DATETIME NULL  DEFAULT NULL ,
`modified` VARCHAR(255)  NULL  DEFAULT "",
`amount_paid` DECIMAL(7,2) NULL DEFAULT NULL,
`date_paid` DATE NULL DEFAULT NULL,
`payment_created_by` INT NULL DEFAULT NULL,
`payment_created` DATETIME NULL DEFAULT NULL,
`payment_modified` DATETIME NULL DEFAULT NULL,
`payment_modified_by` INT NULL DEFAULT NULL,
PRIMARY KEY (`id`)
,KEY `idx_state` (`state`)
,KEY `idx_checked_out` (`checked_out`)
,KEY `idx_created_by` (`created_by`)
,KEY `idx_modified_by` (`modified_by`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ra_claim_types` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`description` VARCHAR(50) NOT NULL,
`state` TINYINT(1) NOT NULL DEFAULT 1,
PRIMARY KEY (`id`),
KEY `idx_state` (`state`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;


INSERT INTO `#__action_log_config` (`type_title`, `type_alias`, `id_holder`, `title_holder`, `table_name`, `text_prefix`)
SELECT * FROM ( SELECT 'booking','com_ra_treasurer.booking','id','member_name','#__ra_bookings','PLG_ACTIONLOG_RA_TREASURER' ) AS tmp
WHERE NOT EXISTS (
	SELECT type_alias FROM `#__action_log_config` WHERE (`type_alias` = 'com_ra_treasurer.booking')
) LIMIT 1;

INSERT INTO `#__action_logs_extensions` (`extension`)
SELECT * FROM ( SELECT 'com_ra_treasurer' ) AS tmp
WHERE NOT EXISTS (
	SELECT extension FROM `#__action_logs_extensions` WHERE (`extension` = 'com_ra_treasurer')
) LIMIT 1;
