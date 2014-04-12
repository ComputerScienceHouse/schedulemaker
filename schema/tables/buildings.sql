-- -------------------------------------------------------------------------
-- BUILDING LOOKUP TABLE
-- 
-- @author  Benjamin Russell (benrr101@csh.rit.edu)
-- @descrip This table holds building codes and links them to numbers and
--          to the name of the building
-- -------------------------------------------------------------------------

DROP TABLE IF EXISTS buildings;
CREATE TABLE buildings (
    `number`      VARCHAR(5) PRIMARY KEY,
    `code`        VARCHAR(5) UNIQUE,
    `name`        VARCHAR(100),
    `off_campus`  BOOLEAN DEFAULT TRUE
) Engine=InnoDb;
