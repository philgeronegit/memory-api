<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class ProjectModel extends Database implements IModel
{
  public function getAll($args)
  {
    $limit = $args['limit'];
    return $this->select("SELECT * FROM project ORDER BY name ASC LIMIT ?", ["i", $limit]);
  }

  public function getOne($id)
  {
    return $this->selectOne("SELECT * FROM project WHERE id_project = ?", ["i", $id]);
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
    return $this->insert(
      "INSERT INTO project (name, description, created_at) VALUES (?, ?, ?)",
      ["sss", $name, $description, $now]
    );
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];
    $name = $paramsArray['name'];
    $description = $paramsArray['description'];
    return $this->update(
      "UPDATE project SET name = ?, description = ? WHERE id_project = ?",
      ["ssi", $name, $description, $id]
    );
  }
}