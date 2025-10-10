CREATE TABLE tag(
   id_tag INT AUTO_INCREMENT,
   name VARCHAR(50) ,
   PRIMARY KEY(id_tag),
   UNIQUE(name)
);

CREATE TABLE programming_language(
   id_programming_language INT AUTO_INCREMENT,
   name VARCHAR(50)  NOT NULL,
   PRIMARY KEY(id_programming_language),
   UNIQUE(name)
);

CREATE TABLE technical_skill(
   id_technical_skill INT AUTO_INCREMENT,
   name VARCHAR(100) ,
   PRIMARY KEY(id_technical_skill),
   UNIQUE(name)
);

CREATE TABLE role(
   id_role INT AUTO_INCREMENT,
   name VARCHAR(50) ,
   role VARCHAR(50)  NOT NULL,
   PRIMARY KEY(id_role),
   UNIQUE(name)
);

CREATE TABLE item(
   id_item INT AUTO_INCREMENT,
   title VARCHAR(50) ,
   description TEXT,
   created_at DATETIME,
   updated_at DATETIME,
   archived_at DATETIME,
   PRIMARY KEY(id_item)
);

CREATE TABLE status(
   id_status INT AUTO_INCREMENT,
   name VARCHAR(50) ,
   PRIMARY KEY(id_status)
);

CREATE TABLE message(
   id_message INT AUTO_INCREMENT,
   text VARCHAR(250)  NOT NULL,
   created_at DATETIME NOT NULL,
   PRIMARY KEY(id_message)
);

CREATE TABLE user(
   id_user INT AUTO_INCREMENT,
   username VARCHAR(50)  NOT NULL,
   email VARCHAR(50) ,
   password VARCHAR(255)  NOT NULL,
   created_at DATETIME NOT NULL,
   is_admin BOOLEAN,
   avatar_url VARCHAR(100) ,
   refresh_token VARCHAR(500) ,
   id_role INT NOT NULL,
   PRIMARY KEY(id_user),
   UNIQUE(username),
   UNIQUE(email),
   FOREIGN KEY(id_role) REFERENCES role(id_role)
);

CREATE TABLE executive(
   id_user INT,
   PRIMARY KEY(id_user),
   FOREIGN KEY(id_user) REFERENCES user_(id_user)
);

CREATE TABLE project_manager(
   id_user INT,
   PRIMARY KEY(id_user),
   FOREIGN KEY(id_user) REFERENCES executive(id_user)
);

CREATE TABLE developer(
   id_user INT,
   PRIMARY KEY(id_user),
   FOREIGN KEY(id_user) REFERENCES user_(id_user)
);

CREATE TABLE project(
   id_project INT AUTO_INCREMENT,
   name VARCHAR(50)  NOT NULL,
   description TEXT,
   created_at DATETIME,
   modified_at DATETIME,
   archived_at DATETIME,
   id_user INT,
   PRIMARY KEY(id_project),
   UNIQUE(name),
   FOREIGN KEY(id_user) REFERENCES project_manager(id_user)
);

CREATE TABLE comment(
   id_comment INT AUTO_INCREMENT,
   content VARCHAR(255)  NOT NULL,
   created_at DATETIME NOT NULL,
   updated_at DATETIME,
   id_user INT NOT NULL,
   id_item INT NOT NULL,
   PRIMARY KEY(id_comment),
   FOREIGN KEY(id_user) REFERENCES user_(id_user),
   FOREIGN KEY(id_item) REFERENCES item(id_item)
);

CREATE TABLE note(
   id_item INT,
   type enum('text', 'image', 'code'),
   is_public BOOLEAN,
   id_user INT NOT NULL,
   id_project INT,
   id_programming_language INT,
   PRIMARY KEY(id_item),
   FOREIGN KEY(id_item) REFERENCES item(id_item),
   FOREIGN KEY(id_user) REFERENCES user_(id_user),
   FOREIGN KEY(id_project) REFERENCES project(id_project),
   FOREIGN KEY(id_programming_language) REFERENCES programming_language(id_programming_language)
);

CREATE TABLE task(
   id_item INT,
   done_at DATETIME,
   priority ENUM('low', 'medium', 'high'),
   due_at DATETIME,
   task_order TINYINT,
   id_project INT NOT NULL,
   id_executive INT NOT NULL,
   id_developer INT NOT NULL,
   id_status INT NOT NULL,
   PRIMARY KEY(id_item),
   FOREIGN KEY(id_item) REFERENCES item(id_item),
   FOREIGN KEY(id_project) REFERENCES project(id_project),
   FOREIGN KEY(id_executive) REFERENCES executive(id_user),
   FOREIGN KEY(id_developer) REFERENCES developer(id_user),
   FOREIGN KEY(id_status) REFERENCES status(id_status)
);

CREATE TABLE tags(
   id_tag INT,
   id_item INT,
   PRIMARY KEY(id_tag, id_item),
   FOREIGN KEY(id_tag) REFERENCES tag(id_tag),
   FOREIGN KEY(id_item) REFERENCES note(id_item)
);

CREATE TABLE projects(
   id_project INT,
   id_user INT,
   PRIMARY KEY(id_project, id_user),
   FOREIGN KEY(id_project) REFERENCES project(id_project),
   FOREIGN KEY(id_user) REFERENCES user_(id_user)
);

CREATE TABLE skills(
   id_technical_skill INT,
   id_user INT,
   year_experience TINYINT,
   PRIMARY KEY(id_technical_skill, id_user),
   FOREIGN KEY(id_technical_skill) REFERENCES technical_skill(id_technical_skill),
   FOREIGN KEY(id_user) REFERENCES developer(id_user)
);

CREATE TABLE shared(
   id_user INT,
   id_item INT,
   PRIMARY KEY(id_user, id_item),
   FOREIGN KEY(id_user) REFERENCES user_(id_user),
   FOREIGN KEY(id_item) REFERENCES note(id_item)
);

CREATE TABLE comment_scores(
   id_comment INT,
   id_user INT,
   score TINYINT,
   PRIMARY KEY(id_comment, id_user),
   FOREIGN KEY(id_comment) REFERENCES comment(id_comment),
   FOREIGN KEY(id_user) REFERENCES user_(id_user)
);

CREATE TABLE note_scores(
   id_user INT,
   id_item INT,
   score TINYINT,
   PRIMARY KEY(id_user, id_item),
   FOREIGN KEY(id_user) REFERENCES user_(id_user),
   FOREIGN KEY(id_item) REFERENCES note(id_item)
);

CREATE TABLE messages(
   id_user INT,
   id_message INT,
   read_at DATETIME,
   PRIMARY KEY(id_user, id_message),
   FOREIGN KEY(id_user) REFERENCES user_(id_user),
   FOREIGN KEY(id_message) REFERENCES message(id_message)
);

CREATE TABLE log(
   Id_log INT AUTO_INCREMENT,
   source VARCHAR(50) ,
   source_id INT,
   text VARCHAR(100) ,
   date_time DATETIME NOT NULL,
   PRIMARY KEY(Id_log)
);
