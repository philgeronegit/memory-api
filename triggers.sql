DELIMITER //

CREATE TRIGGER after_note_insert
AFTER INSERT ON note
FOR EACH ROW
BEGIN
    INSERT INTO log (source, source_id, text, date_time)
    VALUES ('note', NEW.id_item,
            CASE WHEN NEW.is_public THEN 'new public note inserted'
                 ELSE 'new private note inserted' END,
            NOW());
END //

CREATE TRIGGER after_tag_insert
AFTER INSERT ON tag
FOR EACH ROW
BEGIN
    INSERT INTO log (source, source_id, text, date_time)
    VALUES ('tag', NEW.id_tag, CONCAT('new tag inserted: ', NEW.name), NOW());
END //

CREATE TRIGGER after_user_insert
AFTER INSERT ON user
FOR EACH ROW
BEGIN
    -- Insert welcome message into message table
    INSERT INTO message (text, created_at) VALUES ('Welcome to Memory!', NOW());

    -- Get the ID of the newly inserted message
    SET @message_id = LAST_INSERT_ID();

    -- Add entry to messages table for the new user
    INSERT INTO messages (id_user, id_message) VALUES (NEW.id_user, @message_id);

    -- Log the user insertion
    INSERT INTO log (source, source_id, text, date_time)
    VALUES ('user', NEW.id_user, CONCAT('new user inserted: ', NEW.username), NOW());
END //

CREATE TRIGGER after_note_update
AFTER UPDATE ON note
FOR EACH ROW
BEGIN
    DECLARE reason TEXT DEFAULT 'note updated';

    -- Check for changes in key fields and build reason
    IF OLD.type != NEW.type THEN
        SET reason = CONCAT(reason, ': type changed from ', OLD.type, ' to ', NEW.type);
    END IF;

    IF OLD.is_public != NEW.is_public THEN
        SET reason = CONCAT(reason, ': is_public changed from ', IF(OLD.is_public, 'true', 'false'), ' to ', IF(NEW.is_public, 'true', 'false'));
    END IF;

    IF OLD.id_project != NEW.id_project THEN
        SET reason = CONCAT(reason, ': id_project changed from ', IFNULL(OLD.id_project, 'NULL'), ' to ', IFNULL(NEW.id_project, 'NULL'));
    END IF;

    IF OLD.id_programming_language != NEW.id_programming_language THEN
        SET reason = CONCAT(reason, ': id_programming_language changed from ', IFNULL(OLD.id_programming_language, 'NULL'), ' to ', IFNULL(NEW.id_programming_language, 'NULL'));
    END IF;

    -- Insert log entry
    INSERT INTO log (source, source_id, text, date_time)
    VALUES ('note', NEW.id_item, reason, NOW());
END //

DELIMITER ;