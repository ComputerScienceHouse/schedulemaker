-- -------------------------------------------------------------------------
-- Migration Script for building-extension
-- 
-- @author  Nathan Russo (nathanr@csh.rit.edu)
-- @descrip Migration script to add '909', 'G', 'H', and 'TIRM' to the buildings table.
-- -------------------------------------------------------------------------

INSERT INTO buildings (number, code, name, off_campus) VALUES
    ('909', '909', 'Annex IX', 0),
    ('G', 'G', 'G DUBAI', 1),
    ('H', 'H', 'H DUBAI', 1),
    ('TRIM', 'TRIM', 'TIRANA', 1);
