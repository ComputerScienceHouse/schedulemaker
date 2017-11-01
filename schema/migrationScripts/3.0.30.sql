ALTER TABLE `sections`
  CHANGE `type` `type` ENUM ('R', 'N', 'H', 'BL', 'OL')
CHARACTER SET latin1
COLLATE latin1_swedish_ci NOT NULL DEFAULT 'R'
COMMENT 'R=regular, N=night, OL=online, H=honors, BL=????',
  CHANGE `maxenroll` `maxenroll` SMALLINT(3) UNSIGNED NOT NULL
COMMENT 'max enrollment',
  CHANGE `curenroll` `curenroll` SMALLINT(3) UNSIGNED NOT NULL
COMMENT 'current enrollment',
  CHANGE `instructor` `instructor` VARCHAR(64) NOT NULL DEFAULT 'TBA'
COMMENT 'Instructor\'s Name';

ALTER TABLE `times`
  CHANGE `room` `room` VARCHAR(10)
CHARACTER SET latin1
COLLATE latin1_swedish_ci NOT NULL
COMMENT 'room number';

INSERT INTO `buildings` (`number`, `code`, `name`) VALUES ('ZAG', 'ZAG', 'Building in Croatia');

ALTER TABLE `quarters` DROP `breakstart`, DROP `breakend`;