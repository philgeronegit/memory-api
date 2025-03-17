<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class TaskModel extends Database implements IModel
{
  public function getAll(...$params)
  {
    $limit = $params[0];
    return $this->select("SELECT * FROM task ORDER BY id_item ASC LIMIT ?", ["i", $limit]);
  }

  public function getOne($id)
  {
    return $this->selectOne("SELECT * FROM task WHERE id_item = ?", ["i", $id]);
  }

  public function remove($id)
  {
    return $this->delete("DELETE FROM task WHERE id_item = ?", ["i", $id]);
  }

  public function add($paramsArray)
  {
    $name = $paramsArray['name'];
    return $this->insert(
      "INSERT INTO task (name) VALUES (?)",
      ["s", $name]
    );
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];
    $name = $paramsArray['name'];
    return $this->update(
      "UPDATE task SET name = ? WHERE id_item = ?",
      ["si", $name, $id]
    );
  }
}