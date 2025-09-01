<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class RoleModel extends Database implements IModel
{
  public function getAll($args)
  {
    $limit = $args['limit'];
    return $this->select("SELECT * FROM role ORDER BY name ASC LIMIT ?", ["i", $limit]);
  }

  public function getOne($id, $args = null)
  {
    return $this->selectOne("SELECT * FROM role WHERE id_role = ?", ["i", $id]);
  }

  public function remove($id)
  {
    return $this->delete("DELETE FROM role WHERE id_role = ?", ["i", $id]);
  }

  public function add($paramsArray)
  {
    $name = $paramsArray['name'];
    return $this->insert(
      "INSERT INTO role (name) VALUES (?)",
      ["s", $name]
    );
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];
    $name = $paramsArray['name'];
    return $this->update(
      "UPDATE role SET name = ? WHERE id_role = ?",
      ["si", $name, $id]
    );
  }
}