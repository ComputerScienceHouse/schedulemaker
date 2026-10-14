-- -------------------------------------------------------------------------
-- Migration Script for section-type-extension
-- 
-- @author  Nathan Russo (nathanr@csh.rit.edu)
-- @descrip Migration script to add 'OA' and 'OS' to the possible enum values for a section's type.
-- -------------------------------------------------------------------------

ALTER TABLE sections MODIFY COLUMN type 
    ENUM('R', 'N', 'H', 'BL', 'OL', 'OS', 'OA') DEFAULT 'R' NOT NULL 
    COMMENT 'R=Regular, N=Night, H=Honors, BL=Blended Learning, OL=Online, OS=Online - Synchronous, OA=Online - Asynchronous';
