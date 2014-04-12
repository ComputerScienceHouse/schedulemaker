delimiter //
DROP PROCEDURE IF EXISTS InsertOrUpdateCourse//
CREATE PROCEDURE InsertOrUpdateCourse(
  IN p_quarter INT,
  IN p_department_num INT,
  IN p_department_code VARCHAR(4),
  IN p_course VARCHAR(4),
  IN p_credits INT,
  IN p_title VARCHAR(50),
  IN p_description TEXT
)
BEGIN
  -- Determine if the course already exists
  DECLARE recordFound INT(1);
  DECLARE v_department INT(10);

  -- Select the department ID for the course
  IF p_quarter > 20130 THEN
  -- NOTE: This LIMIT 1 looks jank as fuck. Here's why it's important: There are some
  -- departments under semesters that condensed multiple departments from quarters
  -- (eg: CSCI is made from 4003 and 4005). That's ok. Problems arose because when deciding
  -- which department record would own a course under semesters, this query would return
  -- multiple records. Limiting it to one seems like a cop-out since (eg here) what if
  -- a CSCI course belongs to 4003?? The truth: It doesn't matter. By limiting it to one, we
  -- are storing all semester courses under the same department record (a "random" CSCI
  -- one not the 4003 CSCI or the 4005 CSCI). Ordering it will guarantee it's the same one each time.
  SET v_department = (SELECT d.id FROM departments AS d WHERE d.code = p_department_code ORDER BY d.number LIMIT 1);
    ELSE
    SET v_department = (SELECT d.id FROM departments AS d WHERE d.number = p_department_num);
  END IF;

  -- Does the record exist?
  SET recordFound = (
            SELECT COUNT(*)
            FROM courses AS c
            WHERE c.department = v_department
              AND c.course = p_course
              AND c.quarter = p_quarter
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
END
