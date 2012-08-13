delimiter //
DROP PROCEDURE IF EXISTS InsertOrUpdateCourse//
CREATE PROCEDURE InsertOrUpdateCourse(
    IN p_quarter INT,
    IN p_department INT,
    IN p_course INT,
    IN p_credits INT,
    IN p_title VARCHAR(50),
    IN p_description TEXT
)
BEGIN
    -- Determine if the course already exists
    DECLARE recordfound INT(1);
    SET recordfound = (SELECT COUNT(id) FROM courses 
                                    WHERE department = p_department AND course = p_course AND quarter = p_quarter);
    IF recordfound > 0 THEN
        -- Course exists, so update it
        UPDATE courses
            SET title = p_title, description = p_description, credits = p_credits
            WHERE department = p_department AND course = p_course AND quarter = p_quarter;
        SELECT "updated" AS action;
    ELSE
        -- Course doesn't exist, so insert it
        INSERT INTO courses (quarter, department, course, title, description, credits)
            VALUES(p_quarter, p_department, p_course, p_title, p_description, p_credits);
        SELECT "inserted" AS action;
    END IF;
   
    -- Return the id of the course
    SELECT id FROM courses WHERE department = p_department AND course = p_course AND quarter = p_quarter;
END//
