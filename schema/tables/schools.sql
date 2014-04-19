-- -------------------------------------------------------------------------
-- SCHOOL LOOKUP TABLE
--
-- @author  Benjamin Russell (benrr101@csh.rit.edu)
-- @descrip This table holds school codes and links them to numbers and
--          to the name of the school
-- -------------------------------------------------------------------------

-- TABLE CREATION ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `schools` (
  `id`      INT UNSIGNED NOT NULL PRIMARY KEY,
  `number`  tinyint(2) UNSIGNED ZEROFILL NULL DEFAULT NULL,
  `code`    VARCHAR(5) NULL DEFAULT NULL,
  `title` varchar(30) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDb;

-- ADD INDEXES -------------------------------------------------------------
ALTER TABLE `schools`
  ADD CONSTRAINT UQ_schools_number_code
  UNIQUE (`number`, `code`);
