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
          modified_at,
          archived_at,
          project.id_user AS created_by_id,
          user.username AS created_by_name,
          projects.id_user
      FROM
          project
      JOIN
          projects ON projects.id_project = project.id_project
      JOIN user ON user.id_user = project.id_user
      SQL;
  }

  public function getAll($args)
  {
    $limit = $args['limit'];
    if (array_key_exists('id', $args)) {
      $id = $args['id'];
      $query = $this->baseQuery . <<<SQL
       WHERE
            projects.id_user = ?
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
    return $this->selectOne($this->baseQuery . " WHERE id_project = ?", ["i", $id]);
  }

  public function remove($id)
  {
    return $this->delete("DELETE FROM project WHERE id_project = ?", ["i", $id]);
  }

  public function add($paramsArray)
  {
    $name = $paramsArray['name'];
    $description = $paramsArray['description'];
    $now = date('Y-m-d H:i:s');
    $id = $this->insert(
      "INSERT INTO project (name, description, created_at) VALUES (?, ?, ?)",
      ["sss", $name, $description, $now]
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
}