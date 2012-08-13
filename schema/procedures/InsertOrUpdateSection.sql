delimiter //
DROP PROCEDURE IF EXISTS InsertOrUpdateSection//
CREATE PROCEDURE InsertOrUpdateSection(
    IN p_course INT,
    IN p_section VARCHAR(4),
    IN p_title VARCHAR(50),
    IN p_instructor VARCHAR(30),
    IN p_type VARCHAR(1),
    IN p_status VARCHAR(1),
    IN p_maxenroll INT,
    IN p_curenroll INT
)
BEGIN
    -- Attempt to find the section
    DECLARE recordfound INT(1);
    SET recordfound = (SELECT COUNT(id) FROM sections WHERE course = p_course AND section = p_section);
    
    IF recordfound > 0 THEN
        -- Section exists, so just update it
        UPDATE sections
            SET title = p_title, 
                instructor = p_instructor, 
                type = p_type, 
                status = p_status, 
                maxenroll = p_maxenroll, 
                curenroll = p_curenroll
            WHERE course = p_course AND section = p_section;
        SELECT "updated" AS action;
    ELSE
        -- Section does not exist, so insert it
        INSERT INTO sections (course, section, title, instructor, type, status, maxenroll, curenroll)
            VALUES(p_course, p_section, p_title, p_instructor, p_type, p_status, p_maxenroll, p_curenroll);
        SELECT "inserted" AS action;
    END IF;
    
    -- Get the id of the section we just inserted or updated
    SELECT id FROM sections WHERE course = p_course AND section = p_section;
END//
