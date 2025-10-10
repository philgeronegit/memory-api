CREATE VIEW task_details AS
SELECT
    t.id_item,
    i.title,
    i.description,
    i.created_at,
    i.updated_at,
    i.archived_at,
    t.done_at,
    t.priority,
    t.due_at,
    t.task_order,
    s.name AS status_name,
    p.name AS project_name,
    ue.username AS executive_username,
    ud.username AS developer_username
FROM
    task t
JOIN
    item i ON t.id_item = i.id_item
JOIN
    status s ON t.id_status = s.id_status
JOIN
    project p ON t.id_project = p.id_project
JOIN
    user ue ON t.id_executive = ue.id_user
JOIN
    user ud ON t.id_developer = ud.id_user;

CREATE VIEW comment_details AS
SELECT
    c.id_comment,
    c.content,
    c.created_at,
    c.updated_at,
    u.username AS author_username,
    u.email AS author_email,
    i.title AS item_title,
    i.description AS item_description
FROM
    comment c
JOIN
    user u ON c.id_user = u.id_user
JOIN
    item i ON c.id_item = i.id_item;