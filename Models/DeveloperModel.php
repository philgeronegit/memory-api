<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class DeveloperModel extends Database implements IModel
{
  public function getAll($limit)
  {
    return $this->select("SELECT * FROM developer ORDER BY id_developer ASC LIMIT ?", ["i", $limit]);
  }

  public function getOne($id)
  {
    return $this->selectOne("SELECT * FROM developer WHERE id_developer = ?", ["i", $id]);
  }

  public function remove($id)
  {
    return $this->delete("DELETE FROM developer WHERE id_developer = ?", ["i", $id]);
  }

  public function add($paramsArray)
  {
    $name = $paramsArray['name'];
    $email = $paramsArray['email'];
    $now = date('Y-m-d H:i:s');
    return $this->insert(
      "INSERT INTO developer (username, email, created_at) VALUES (?, ?, ?)",
      ["sss", $name, $email, $now]
    );
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];
    $name = $paramsArray['name'];
    $email = $paramsArray['email'];
    return $this->update(
      "UPDATE developer SET username = ?, email = ? WHERE id_developer = ?",
      ["ssi", $name, $email, $id]
    );
  }
}