DROP TABLE IF EXISTS `#__ra_bookings`;

DELETE FROM `#__action_log_config` WHERE (type_alias LIKE 'com_ra_treasurer.%');
DELETE FROM `#__action_logs_extensions` WHERE (extension = 'com_ra_treasurer');