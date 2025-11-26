<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class ProjectModel extends Database implements IModel
{
  public function __construct()
  {
    parent::__construct();

    $this->baseQuery = <<<SQL
      SELECT
        project.id_project,
        name,
        description,
        project.created_at,
        project.id_user AS created_by_id,
        creator.username AS created_by_name,
        modified_at as updated_at,
        archived_at,
        group_concat(projects.id_user) as id_users,
        group_concat(user.username) as users,
        CONCAT(
            '[',
            GROUP_CONCAT(
                JSON_OBJECT('id_user', projects.id_user, 'username', user.username)
            ),
            ']'
        ) AS users_json,
        (SELECT group_concat(id_item) FROM note WHERE note.id_project = project.id_project) as id_notes
      FROM project
      LEFT JOIN user AS creator ON creator.id_user = project.id_user
      LEFT JOIN projects ON projects.id_project = project.id_project
      LEFT JOIN user ON user.id_user = projects.id_user
      GROUP by project.id_project
      SQL;
  }

  public function getAll($args)
  {
    $limit = $args['limit'];
    if (array_key_exists('id', $args)) {
      $id = $args['id'];
      $query = <<<SQL
        SELECT
          project.id_project,
          name,
          description,
          project.created_at,
          project.id_user AS created_by_id,
          creator.username AS created_by_name,
          modified_at as updated_at,
          archived_at
        FROM project
        LEFT JOIN user AS creator ON creator.id_user = project.id_user
        LEFT JOIN projects ON projects.id_project = project.id_project
        LEFT JOIN user ON user.id_user = projects.id_user
        WHERE projects.id_user = ?
        ORDER BY name ASC LIMIT ?
      SQL;

      return $this->select($query, ["ii", $id, $limit]);
    }

    $query = $this->baseQuery . <<<SQL
      ORDER BY name ASC LIMIT ?
    SQL;

    return $this->select($query, ["i", $limit]);
  }

  public function getOne($id, $args = null)
  {
    $query = <<<SQL
            SELECT
          project.id_project,
          name,
          description,
          project.created_at,
          project.id_user AS created_by_id,
          creator.username AS created_by_name,
          modified_at as updated_at,
          archived_at
        FROM project
        LEFT JOIN user AS creator ON creator.id_user = project.id_user
        WHERE project.id_project = ?
    SQL;
    return $this->selectOne($query, ["i", $id]);
  }

  public function remove($id)
  {
    return $this->delete("DELETE FROM project WHERE id_project = ?", ["i", $id]);
  }

  public function add($paramsArray)
  {
    $name = $paramsArray['name'];
    $description = $paramsArray['description'];
    $id_user = $paramsArray['id_user'] ?? null;
    $now = date('Y-m-d H:i:s');
    $id = $this->insert(
      "INSERT INTO project (name, description, created_at, id_user) VALUES (?, ?, ?, ?)",
      ["ssss", $name, $description, $now, $id_user]
    );

    $query = <<<SQL
    SELECT * FROM project
    WHERE id_project = ?
    SQL;
    return $this->selectOne($query, ["i", $id]);
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];

    $query = <<<SQL
    SELECT * FROM project
    WHERE id_project = ?
    SQL;
    $project = $this->selectOne($query, ["i", $id]);

    $name = $paramsArray['name'] ?? $project->name;
    $description = $paramsArray['description'] ?? $project->description;
    $this->update(
      "UPDATE project SET name = ?, description = ? WHERE id_project = ?",
      ["ssi", $name, $description, $id]
    );

    return $this->selectOne($query, ["i", $id]);
  }

  /**
   * Add users to a project
   * @param array $paramsArray
   * @return bool
   */
  public function addToProject($paramsArray)
  {
    $project_id = $paramsArray['project_id'];
    $user_ids = $paramsArray['user_ids'];

    if (!is_array($user_ids)) {
      throw new InvalidArgumentException('user_ids must be an array.');
    }

    // delete all users for the project
    $this->delete("DELETE FROM projects WHERE id_project = ?", ["i", $project_id]);

    // insert new users
    foreach ($user_ids as $user_id) {
      $this->insert(
        "INSERT INTO projects (id_user, id_project) VALUES (?, ?)",
        ["ii", $user_id, $project_id]
      );
    }

    return true;
  }
}