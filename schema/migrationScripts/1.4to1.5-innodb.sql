-- Switch all the tables to innodb
ALTER TABLE `buildings` ENGINE = InnoDB;
ALTER TABLE `courses` ENGINE = InnoDB;
ALTER TABLE `departments` ENGINE = InnoDB;
ALTER TABLE `quarters` ENGINE = InnoDB;
ALTER TABLE `schedulecourses` ENGINE = InnoDB;
ALTER TABLE `schedulenoncourses` ENGINE = InnoDB;
ALTER TABLE `schedules` ENGINE = InnoDB;
ALTER TABLE `schools` ENGINE = InnoDB;
ALTER TABLE `scrapelog` ENGINE = InnoDB;
ALTER TABLE `sections` ENGINE = InnoDB;
ALTER TABLE `times` ENGINE = InnoDB;

-- Buildings ---------------------------------------------------------------
ALTER TABLE `times` ADD INDEX ( `building` );
ALTER TABLE `times`
  CHANGE `building` `building` VARCHAR( 4 ) CHARACTER SET latin1
    COLLATE latin1_swedish_ci NULL DEFAULT NULL
    COMMENT 'building number bitches!';
ALTER TABLE `buildings`
  CHANGE `code` `code` VARCHAR( 4 ) CHARACTER SET latin1
    COLLATE latin1_swedish_ci NULL DEFAULT NULL;
ALTER TABLE `buildings`
  CHANGE `number` `number` VARCHAR( 4 ) CHARACTER SET latin1
    COLLATE latin1_swedish_ci NOT NULL;
UPDATE times SET building = NULL WHERE building = "";

-- Update broken building codes
UPDATE `scheduleprod`.`buildings`
  SET `number` = 'ACMT',
        `code` = 'ACMT',
        `name` = 'American College of Management and Technology'
  WHERE `buildings`.`number` = 'ACM';
UPDATE `buildings` SET `name` = 'Online' WHERE `buildings`.`number` = 'ONL';
INSERT INTO `buildings` (`number`, `code`, `name`) VALUES ('OFFC', 'OFFC', 'UNKNOWN');
INSERT INTO `buildings` (`number`, `code`, `name`) VALUES ('07', '07', 'Gannet/Booth Hall');

-- Prune bad times from olden days
DELETE FROM times WHERE day=0 AND start=0 AND end=0;  -- Prune bad times from old days

-- Fix bad building numbers
UPDATE times SET building = TRIM(building);
UPDATE times SET building = CONCAT("0",building) WHERE LENGTH(building) = 1 AND building NOT IN(SELECT number FROM buildings);
UPDATE times SET building = CONCAT("0",building) WHERE LENGTH(building) = 2 AND building NOT IN(SELECT number FROM buildings);
UPDATE times SET building = SUBSTR(building, -2)
  WHERE LENGTH(building) = 3
          AND building NOT IN(SELECT number FROM buildings)
          AND building LIKE "0%";
UPDATE times SET building='TBA' WHERE building='TBD';
UPDATE times SET building='ACMT' WHERE building='CMT';
UPDATE times SET building='OFF' WHERE building='FF';
UPDATE times SET building='DUB' WHERE building='NA';
UPDATE times SET building='ONL' WHERE building='INE';
UPDATE times SET building='OFFC' WHERE building='FFC';
UPDATE times SET building='ACMT' WHERE building='ACM';
UPDATE times SET building=NULL WHERE building IN("115", "12A", "15A", "1A", "550", "78A", "83", "8A", "950", "00", "1A");

ALTER TABLE `times` ADD FOREIGN KEY ( `building` )
  REFERENCES `buildings` (`number`)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- Time to section
ALTER TABLE `times` ADD FOREIGN KEY ( `section` )
  REFERENCES `sections` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- Section to courses
DELETE FROM sections WHERE course NOT IN(SELECT id FROM courses);
ALTER TABLE `sections` ADD FOREIGN KEY ( `course` )
  REFERENCES `courses` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- Schools -----------------------------------------------------------------
ALTER TABLE `schools` CHANGE `id` `number` TINYINT( 2 ) UNSIGNED ZEROFILL NULL DEFAULT NULL;
ALTER TABLE `schools` CHANGE `code` `code` VARCHAR( 8 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;
UPDATE schools SET code = NULL;
ALTER TABLE schools DROP PRIMARY KEY;
ALTER TABLE `schools` ADD `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `schools` ADD UNIQUE `UNI_id-number` ( `number` , `code` );

-- Fix the departments table
ALTER TABLE `departments` CHANGE `number` `number` SMALLINT( 4 ) UNSIGNED ZEROFILL NULL DEFAULT NULL;
ALTER TABLE `departments` CHANGE `code` `code` VARCHAR( 5 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;
ALTER TABLE `departments` DROP PRIMARY KEY;
ALTER TABLE `departments` ADD `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `departments` CHANGE `number` `number` SMALLINT( 4 ) UNSIGNED ZEROFILL NULL DEFAULT NULL;
UPDATE departments SET code=NULL WHERE code='0';

-- Correct the department->school stuff
UPDATE departments AS d SET school = (SELECT id FROM schools AS s WHERE s.number = d.school);
ALTER TABLE `departments` CHANGE `school` `school` INT( 10 ) UNSIGNED NULL DEFAULT NULL;


-- Correct the course department number
ALTER TABLE `courses` ADD `departmentid` INT UNSIGNED NOT NULL AFTER `id`;
UPDATE courses AS c SET departmentid = (SELECT id FROM departments AS d WHERE c.department=d.number);
DELETE FROM courses WHERE departmentid=0; -- bye bye Indoor Gardening :(
ALTER TABLE courses DROP INDEX department_2;
ALTER TABLE courses DROP INDEX department;
ALTER TABLE `courses` ADD UNIQUE `coursenumbers` ( `quarter` , `departmentid` , `course`);
ALTER TABLE courses DROP COLUMN department;
ALTER TABLE `courses` CHANGE `departmentid` `department` INT( 10 ) UNSIGNED NOT NULL;
ALTER TABLE `courses` ADD INDEX `department` (`department`);
ALTER TABLE `courses` ADD FOREIGN KEY ( `department` )
  REFERENCES `departments` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE ;

-- Department to School
ALTER TABLE `departments` ADD INDEX `school` ( `school` );
ALTER TABLE `departments` CHANGE `school` `school` TINYINT( 2 ) UNSIGNED ZEROFILL NULL DEFAULT NULL;
UPDATE departments SET school=NULL WHERE school="00";
ALTER TABLE `departments` ADD FOREIGN KEY ( `school` )
  REFERENCES `schools` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE ;

-- Course to Quarter
ALTER TABLE `courses` ADD INDEX `quarter` ( `quarter` );
ALTER TABLE `courses` ADD FOREIGN KEY ( `quarter` )
  REFERENCES `quarters` (`quarter`)
  ON DELETE CASCADE ON UPDATE CASCADE ;

-- Schedulecourses to schedule
ALTER TABLE `schedulecourses` ADD FOREIGN KEY ( `schedule` )
  REFERENCES `schedules` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- Schedulecourses to Sections
ALTER TABLE `schedulecourses` ADD INDEX `FK_schedulecourses-sections` ( `section` );
DELETE FROM schedulecourses WHERE section NOT IN(SELECT id FROM sections);
ALTER TABLE `schedulecourses` ADD FOREIGN KEY ( `section` )
  REFERENCES `sections` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE ; -- Not sure why, but this one takes decades to run


-- Schedulenoncourses to schedule
DELETE FROM schedulenoncourses
  WHERE schedule NOT IN (SELECT id FROM schedules );
ALTER TABLE `schedulenoncourses` ADD FOREIGN KEY ( `schedule` )
  REFERENCES `schedules` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE ;

-- Add foreign keys
