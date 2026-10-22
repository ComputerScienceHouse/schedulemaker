-- -------------------------------------------------------------------------
-- Migration Script for building-extension
-- 
-- @author  Nathan Russo (nathanr@csh.rit.edu)
-- @descrip Migration script to add '909', 'G', 'H', 'TIRM', and 'TIRT' to 
--          the buildings table.
-- -------------------------------------------------------------------------

INSERT INTO buildings (number, code, name, off_campus) VALUES
    ('83', 'PAC', 'Performing Arts Center', 0),
    ('820', '820', '820', 0),
    ('909', '909', 'Annex IX', 0),
    ('G', 'G', 'G DUBAI', 1),
    ('H', 'H', 'H DUBAI', 1),
    ('TIRM', 'TIRM', 'TIRANA', 1),
    ('TIRT', 'TIRT', 'TIRT', 1);
