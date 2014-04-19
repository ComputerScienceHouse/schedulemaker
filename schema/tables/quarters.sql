-- -------------------------------------------------------------------------
-- Quarters Table
-- 
-- @author  Benjamin Russell (benrr101@csh.rit.edu)
-- @descrip Table for quarters. Although RIT changed their formatting for quarters
--          we convert their new format (2135) to the old format (20135) to
--          preserve sorting.
-- -------------------------------------------------------------------------

-- CREATE TABLE ------------------------------------------------------------
CREATE TABLE quarters (
  `quarter`     SMALLINT(5) UNSIGNED NOT NULL PRIMARY KEY,
  `start`       DATE NOT NULL,
  `end`         DATE NOT NULL,
  `breakstart`  DATE NOT NULL,
  `breakend`    DATE NOT NULL
) ENGINE=InnoDb;