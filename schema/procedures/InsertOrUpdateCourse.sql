delimiter //
DROP PROCEDURE IF EXISTS InsertOrUpdateCourse//
CREATE PROCEDURE InsertOrUpdateCourse(
  IN p_quarter INT,
  IN p_department_num INT,
  IN p_department_code VARCHAR(4),
  IN p_course INT,
  IN p_credits INT,
  IN p_title VARCHAR(50),
  IN p_description TEXT
)
BEGIN
  -- Determine if the course already exists
  DECLARE recordFound INT(1);
  DECLARE v_department INT(10);
  SET recordFound = (
            SELECT COUNT(*)
            FROM courses AS c
              JOIN departments AS d ON c.department = d.id
            WHERE (d.code = p_department_code OR d.number = p_department_num)
              AND c.course = p_course
              AND c.quarter = p_quarter
            );
  SET v_department = (
            SELECT d.id
            FROM departments AS d
            WHERE d.code = p_department_code
              OR d.number = p_department_num
            );

  IF recordFound > 0 THEN
      -- Course exists, so update it
      UPDATE courses AS c
          SET c.title = p_title, c.description = p_description, c.credits = p_credits
          WHERE c.department = v_department AND course = p_course AND quarter = p_quarter;
      SELECT "updated" AS action;
  ELSE
      -- Course doesn't exist, so insert it
      INSERT INTO courses (quarter, department, course, title, description, credits)
          VALUES(p_quarter, v_department, p_course, p_title, p_description, p_credits);
      SELECT "inserted" AS action;
  END IF;

  -- Return the id of the course
  SELECT c.id FROM courses AS c WHERE c.department = v_department AND c.course = p_course AND c.quarter = p_quarter;
END//
