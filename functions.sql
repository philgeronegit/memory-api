DELIMITER //

CREATE FUNCTION getAverageNoteScore(item_id INT)
RETURNS DECIMAL(3,2)
DETERMINISTIC
BEGIN
    DECLARE avg_score DECIMAL(3,2);
    SELECT AVG(score) INTO avg_score
    FROM note_scores
    WHERE id_item = item_id;
    RETURN avg_score;
END //

CREATE FUNCTION getCommentCount(item_id INT)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE comment_count INT;
    SELECT COUNT(*) INTO comment_count
    FROM comment
    WHERE id_item = item_id;
    RETURN comment_count;
END //

CREATE FUNCTION getTaskCount(project_id INT)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE task_count INT;
    SELECT COUNT(*) INTO task_count
    FROM task
    WHERE id_project = project_id;
    RETURN task_count;
END //

DELIMITER ;