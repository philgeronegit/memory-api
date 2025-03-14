<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class TagModel extends Database implements IModel
{
  public function getAll($limit)
  {
    return $this->select("SELECT * FROM tag ORDER BY id_tag ASC LIMIT ?", ["i", $limit]);
  }

  public function getOne($id)
  {
    return $this->selectOne("SELECT * FROM tag WHERE id_tag = ?", ["i", $id]);
  }

  public function remove($id)
  {
    return $this->delete("DELETE FROM tag WHERE id_tag = ?", ["i", $id]);
  }

  public function add($paramsArray)
  {
    $name = $paramsArray['name'];
    return $this->insert(
      "INSERT INTO tag (name) VALUES (?)",
      ["s", $name]
    );
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];
    $name = $paramsArray['name'];
    return $this->update(
      "UPDATE tag SET name = ? WHERE id_tag = ?",
      ["si", $name, $id]
    );
  }
}