-- -------------------------------------------------------------------------
-- Section Times Table
-- 
-- @author  Benjamin Russell (benrr101@csh.rit.edu)
-- @descrip Table for storing the times that a section meets. Also includes
--          foreign keys for linking up the times to the section and location
--          to a building.
-- -------------------------------------------------------------------------

-- CREATE TABLE ------------------------------------------------------------
CREATE TABLE times (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `section`     INT UNSIGNED NOT NULL,
  `day`         TINYINT(1) UNSIGNED NOT NULL,
  `start`       SMALLINT(4) UNSIGNED NOT NULL,
  `end`         SMALLINT(4) UNSIGNED NOT NULL,
  `building`    VARCHAR(5) NULL DEFAULT NULL,
  `room`        VARCHAR(10) NULL DEFAULT NULL
)ENGINE=InnoDB;

-- FOREIGN KEY CONSTRAINTS -------------------------------------------------
ALTER TABLE times
    ADD CONSTRAINT FK_times_section
    FOREIGN KEY (`section`)
    REFERENCES sections(`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE;

ALTER TABLE times
    ADD CONSTRAINT FK_times_building
    FOREIGN KEY (`building`)
    REFERENCES buildings(`number`)
    ON DELETE SET NULL
    ON UPDATE CASCADE;