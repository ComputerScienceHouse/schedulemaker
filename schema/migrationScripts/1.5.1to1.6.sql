-- -------------------------------------------------------------------------
-- Migration to version 1.6 for semester support
--
-- @author  Benjamin Russell (benrr101@csh.rit.edu)
-- @descrip This script will perform the necessary database schema migration
--          to enable robust semester support
-- -------------------------------------------------------------------------

-- Step 1) Drop existing semester courses
DELETE FROM courses WHERE `quarter` > 20130;

-- Step 2a) Add the new column for the quarternumbers
ALTER TABLE departments ADD COLUMN `qtrnums` VARCHAR(20) NULL DEFAULT NULL;

-- Step 2b) Insert new records for departments under semesters
INSERT INTO departments(`number`, `code`, `title`, `school`, `qtrnums`)
  SELECT NULL, `code`, `title`, `school`, TRIM(TRAILING ', ' FROM GROUP_CONCAT(`number` SEPARATOR ', '))
  FROM departments
  WHERE `code` IS NOT NULL
  GROUP BY `code`;

-- Step 2c) Update the old records to remove the codes
UPDATE departments SET `code` = NULL WHERE `number` IS NOT NULL;
UPDATE departments SET `qtrnums` = NULL WHERE `qtrnums` = '';

-- Step 3) Delete pesky duplicates (not sure how those got in there...)
-- Adapted from http://stackoverflow.com/a/3671629
DELETE d1 FROM departments AS d1 JOIN departments AS d2 ON d1.code = d2.code WHERE d1.id > d2.id;

-- Step 4) Create unique keys on department codes and numbers
-- NOTE: This will work b/c InnoDB allows multiple NULLs in unique columns (unlike MyISAM)
ALTER TABLE departments ADD UNIQUE `UNI_deptnumber` ( `number` );
ALTER TABLE departments ADD UNIQUE `UNI_deptcode` ( `code` );

-- Step 3) Run the processDump script