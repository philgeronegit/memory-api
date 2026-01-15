INSERT INTO role (name, role) VALUES ('Admin', 'admin');
SET @admin_role_id = LAST_INSERT_ID();
INSERT INTO role (name, role) VALUES ('Développeur', 'developer');
SET @dev_role_id = LAST_INSERT_ID();
INSERT INTO role (name, role) VALUES ('Lead Développeur', 'leadDeveloper');
SET @lead_dev_role_id = LAST_INSERT_ID();
INSERT INTO role (name, role) VALUES ('Chef de projet', 'projectManager');
SET @pm_role_id = LAST_INSERT_ID();
INSERT INTO role (name, role) VALUES ('Externe', 'external');
SET @external_role_id = LAST_INSERT_ID();

INSERT INTO user (username, email, password, created_at, is_admin, id_role) VALUES ('admin', 'admin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 1, @admin_role_id);
INSERT INTO user (username, email, password, created_at, is_admin, id_role) VALUES ('user', 'user@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 1, @dev_role_id);

INSERT INTO status (name, value) VALUES ('À faire', 'a_faire');
INSERT INTO status (name, value) VALUES ('En cours', 'en_cours');
INSERT INTO status (name, value) VALUES ('Fait', 'fait');

-- Permission dictionary
INSERT INTO permission (name) VALUES
('create:messages'),
('view:comments'),
('create:comments'),
('update:ownComments'),
('delete:ownComments'),
('view:notes'),
('create:notes'),
('update:ownNotes'),
('delete:ownNotes'),
('view:users'),
('create:users'),
('update:users'),
('delete:users'),
('view:roles'),
('create:roles'),
('update:roles'),
('delete:roles'),
('view:permissions'),
('create:permissions'),
('update:permissions'),
('delete:permissions'),
('view:tasks'),
('create:tasks'),
('update:tasks'),
('delete:tasks'),
('view:tags'),
('create:tags'),
('update:tags'),
('view:noteTags'),
('create:noteTags'),
('update:noteTags'),
('delete:noteTags'),
('view:technicalSkills'),
('create:technicalSkills'),
('update:technicalSkills'),
('delete:technicalSkills'),
('view:projects'),
('create:projects'),
('update:projects'),
('delete:projects'),
('update:comments');

-- Role-Permission assignments
-- Admin permissions
INSERT INTO permissions (id_role, id_permission)
SELECT @admin_role_id, id_permission FROM permission WHERE name IN (
'create:messages', 'view:comments', 'create:comments', 'update:ownComments', 'delete:ownComments',
'view:notes', 'create:notes', 'update:ownNotes', 'delete:ownNotes', 'view:users', 'create:users',
'update:users', 'delete:users', 'view:roles', 'create:roles', 'update:roles', 'delete:roles',
'view:permissions', 'create:permissions', 'update:permissions', 'delete:permissions',
'view:tasks', 'create:tasks', 'update:tasks', 'delete:tasks'
);

-- Developer permissions
INSERT INTO permissions (id_role, id_permission)
SELECT @dev_role_id, id_permission FROM permission WHERE name IN (
'create:messages', 'view:comments', 'create:comments', 'update:ownComments', 'delete:ownComments',
'view:notes', 'create:notes', 'update:ownNotes', 'delete:ownNotes', 'view:tags', 'create:tags',
'update:tags', 'view:noteTags', 'create:noteTags', 'update:noteTags', 'delete:noteTags',
'view:technicalSkills', 'create:technicalSkills', 'update:technicalSkills', 'delete:technicalSkills'
);

-- Lead Developer permissions
INSERT INTO permissions (id_role, id_permission)
SELECT @lead_dev_role_id, id_permission FROM permission WHERE name IN (
'create:messages', 'view:users', 'view:comments', 'create:comments', 'update:ownComments',
'view:notes', 'create:notes', 'update:ownNotes', 'delete:ownNotes'
);

-- Project Manager permissions
INSERT INTO permissions (id_role, id_permission)
SELECT @pm_role_id, id_permission FROM permission WHERE name IN (
'create:messages', 'create:users', 'view:users', 'view:tasks', 'create:tasks', 'update:tasks',
'delete:tasks', 'view:projects', 'create:projects', 'update:projects', 'delete:projects'
);

-- External permissions
INSERT INTO permissions (id_role, id_permission)
SELECT @external_role_id, id_permission FROM permission WHERE name IN (
'view:comments', 'create:comments', 'update:comments'
);
