<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class ProgrammingLanguageModel extends Database implements IModel
{
  public function getAll($args)
  {
    $limit = $args['limit'];
    return $this->select("SELECT * FROM programming_language ORDER BY id_programming_language ASC LIMIT ?", ["i", $limit]);
  }

  public function getOne($id)
  {
    return $this->selectOne("SELECT * FROM programming_language WHERE id_programming_language = ?", ["i", $id]);
  }

  public function remove($id)
  {
    return $this->delete("DELETE FROM programming_language WHERE id_programming_language = ?", ["i", $id]);
  }

  public function add($paramsArray)
  {
    $name = $paramsArray['name'];
    return $this->insert(
      "INSERT INTO programming_language (name) VALUES (?)",
      ["s", $name]
    );
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];
    $name = $paramsArray['name'];
    return $this->update(
      "UPDATE programming_language SET name = ? WHERE id_programming_language = ?",
      ["si", $name, $id]
    );
  }
}