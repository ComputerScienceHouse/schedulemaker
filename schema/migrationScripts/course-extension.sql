-- -------------------------------------------------------------------------
-- Migration Script for course-extension
-- 
-- @author  Nathan Russo (nathanr@csh.rit.edu)
-- @descrip Migration script to add prerequisites, typically offered, and contact hours to the course table.
-- -------------------------------------------------------------------------

ALTER TABLE courses
    ADD COLUMN IF NOT EXISTS prerequisites TEXT NOT NULL DEFAULT '',
    ADD COLUMN IF NOT EXISTS typically_offered TEXT NOT NULL DEFAULT '',
    ADD COLUMN IF NOT EXISTS contact_hours FLOAT UNSIGNED NOT NULL DEFAULT 0;
