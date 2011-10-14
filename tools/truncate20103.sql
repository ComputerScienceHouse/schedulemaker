DELETE FROM times WHERE section IN (
    SELECT id FROM sections WHERE course IN (
       SELECT id FROM courses WHERE quarter >= 20103
    )
);

DELETE FROM sections WHERE course IN (
    SELECT id FROM courses WHERE quarter >= 20103
);

DELETE FROM courses WHERE quarter >= 20103;
