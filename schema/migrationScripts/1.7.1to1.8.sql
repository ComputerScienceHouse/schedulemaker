-- -------------------------------------------------------------------------
-- MIGRATION SCRIPT 1.7.1 to 1.8
-- 
-- @author  Ben (benrr101@csh.rit.edu)
-- @descrip DB migration script for moving from 1.7.1 to 1.8 (or higher)
--          + Adding column for schedule image location
-- -------------------------------------------------------------------------

ALTER TABLE schedules ADD COLUMN (`image` BOOL NOT NULL DEFAULT FALSE);