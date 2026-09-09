-- -------------------------------------------------------------------------
-- Migration Script for course-extension
-- 
-- @author  Nathan Russo (nathanr@csh.rit.edu)
-- @descrip Migration script to add prerequisites, typically offered, and contact hours to the course table.
-- -------------------------------------------------------------------------

ALTER TABLE courses
    ADD COLUMN prerequisites TEXT,
    ADD COLUMN typically_offered TEXT,
    ADD COLUMN contact_hours FLOAT UNSIGNED;
