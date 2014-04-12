-- -------------------------------------------------------------------------
-- Saved Schedule Sections
-- 
-- @author  Benjamin Russell (benrr101@csh.rit.edu)
-- @descrip Table for linking sections with saved schedules.
-- -------------------------------------------------------------------------

-- CREATE TABLE ------------------------------------------------------------
CREATE TABLE schedulecourses (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `schedule`  INT UNSIGNED NOT NULL,
  `section`   INT UNSIGNED NOT NULL
)ENGINE=InnoDb;

-- FOREIGN KEY CONSTRAINTS -------------------------------------------------
ALTER TABLE `schedulecourses`
  ADD FOREIGN KEY FK_schedcourses_schedule(`schedule`)
  REFERENCES `schedules`(`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `schedulecourses`
  ADD FOREIGN KEY FK_schedcourses_section(`section`)
  REFERENCES `sections`(`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;