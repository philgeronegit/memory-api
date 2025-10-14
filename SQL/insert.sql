INSERT INTO role (name, role) VALUES ('Admin', 'admin');
SET @admin_role_id = LAST_INSERT_ID();
INSERT INTO role (name, role) VALUES ('Développeur', 'developer');
INSERT INTO role (name, role) VALUES ('Lead Développeur', 'leadDeveloper');
INSERT INTO role (name, role) VALUES ('Chef de projet', 'projectManager');
INSERT INTO role (name, role) VALUES ('Externe', 'external');

INSERT INTO user (username, email, password, created_at, is_admin, id_role) VALUES ('admin', 'admin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 1, @admin_role_id);
