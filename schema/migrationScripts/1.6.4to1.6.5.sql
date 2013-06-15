-- -------------------------------------------------------------------------
-- Migration to version 1.6.5 for CROATIA MODE support
--
-- @author  Benjamin Russell (benrr101@csh.rit.edu)
-- @descrip This script will perform the necessary database schema migration
--          to enable off campus buildings to be set
-- -------------------------------------------------------------------------

-- Add the column
ALTER TABLE buildings
  ADD off_campus  BOOLEAN DEFAULT 0;

-- Set known off campus buildings as such
UPDATE buildings SET off_campus = 1
WHERE `number` IN (
    'OFFC',
    'OFF',
    'DUB',
    'CMT',
    'AUK',
    'ACMT',
    '93'
);

