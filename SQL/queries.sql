-- Example usage of the functions defined in functions.sql
SELECT getCommentCount(1) AS comment_count, getAverageNoteScore(1) AS average_score;
SELECT getTaskCount(1) AS task_count;

-- Example usage of the views defined in views.sql
SELECT * FROM task_details LIMIT 10;
SELECT * FROM comment_details LIMIT 10;

-- Example usage of triggers defined in triggers.sql
-- Add a new user to see the welcome message and log entry
INSERT INTO user (username, email, password) VALUES ('newuser', 'newuser@example.com', 'password123');
-- Check the message and log tables for entries related to the new user
SELECT * FROM message WHERE text = 'Welcome to Memory!';
SELECT * FROM messages WHERE id_user = (SELECT id_user FROM user WHERE username = 'newuser');
SELECT * FROM log WHERE source = 'user' AND source_id = (SELECT id_user FROM user WHERE username = 'newuser');
-- Add a new note to see the log entry
INSERT INTO note (id_item, content, is_public) VALUES (1, 'This is a new note', TRUE);
-- Check the log table for the new note entry
SELECT * FROM log WHERE source = 'note' AND source_id = (SELECT id_item FROM note WHERE content = 'This is a new note');
-- Update a note to see the log entry with changes
UPDATE note SET is_public = FALSE WHERE id_item = 1;
-- Check the log table for the updated note entry
SELECT * FROM log WHERE source = 'note' AND source_id = 1 ORDER BY date_time DESC LIMIT 1;
-- Add a new tag to see the log entry
INSERT INTO tag (name) VALUES ('NewTag');
-- Check the log table for the new tag entry
SELECT * FROM log WHERE source = 'tag' AND source_id = (SELECT id_tag FROM tag WHERE name = 'NewTag');
-- Add a new comment to see the log entry
INSERT INTO comment (id_item, id_user, content) VALUES (1, 1, 'This is a new comment');
-- Check the log table for the new comment entry
SELECT * FROM log WHERE source = 'comment' AND source_id = (SELECT id_comment FROM comment WHERE content = 'This is a new comment');
