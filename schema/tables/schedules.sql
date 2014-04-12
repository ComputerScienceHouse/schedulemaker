-- -------------------------------------------------------------------------
-- SAVED SCHEDULE TABLE
-- 
-- @author  Benjamin Russell (benrr101@csh.rit.edu)
-- @descrip A table for storing saved schedule records.
-- -------------------------------------------------------------------------

CREATE TABLE schedules (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,     -- Will be displayed to user as hex
  -- @TODO Safely remove this column when old schedules have been pruned
  `oldid`             VARCHAR(7) NULL DEFAULT NULL COLLATE latin1_general_cs,  -- Old index from Resig's schedule maker. It's case sensitive
  `datelastaccessed`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,         -- Last date accessed. Used for determining when to prune.
  `startday`          TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,               -- Start day for schedule. 0 = Sunday, 1 = Monday, etc
  `endday`            TINYINT(1) UNSIGNED NOT NULL DEFAULT 6,               -- End day for schedule. See above.
  -- @TODO Safely remove the zerofill from these columns
  `starttime`         SMALLINT(4) UNSIGNED ZEROFILL NOT NULL DEFAULT 0480,  -- Start time for schedule. Value is minutes into the day (eg. 0=midnight, 480=8AM)
  `endtime`           SMALLINT(4) UNSIGNED ZEROFILL NOT NULL DEFAULT 1320,  -- End time for schedule. Value is minutes into the day (eg. 1320=10PM)
  `building`          SET('code', 'number') NOT NULL DEFAULT 'number',      -- Whether to show old or new building id. Defaulting to number for old fogies
  `quarter`           SMALLINT(5) UNSIGNED NULL DEFAULT NULL,               -- The quarter the schedule was made for. Not necessary for it to reference quarters table
  `image`             BOOL NOT NULL DEFAULT FALSE                           -- Whether or not an image of the schedule has been generated and saved
)ENGINE=InnoDb;

-- Add index to searchable columns
ALTER TABLE schedules ADD INDEX (`oldid`);

-- FOREIGN KEYS ------------------------------------------------------------
ALTER TABLE `schedules`
  ADD FOREIGN KEY  FK_schedules_quarter(`quarter`)
  REFERENCES `quarters`(`quarter`)
  ON UPDATE CASCADE
  ON DELETE SET NULL;