<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class StatusModel extends Database implements IModel
{
  public function getAll($args)
  {
    $limit = $args['limit'];

    if (array_key_exists('search', $args)) {
      $search = "%" . $args['search'] . "%";
      $query = "SELECT * FROM status WHERE name LIKE ? ORDER BY name ASC LIMIT ?";
      return $this->select($query, ["si", $search, $limit]);
    }

    return $this->select("SELECT * FROM status ORDER BY name ASC LIMIT ?", ["i", $limit]);
  }

  public function getOne($id, $args = null)
  {
    return $this->selectOne("SELECT * FROM status WHERE id_status = ?", ["i", $id]);
  }

  public function remove($id)
  {
    return $this->delete("DELETE FROM status WHERE id_status = ?", ["i", $id]);
  }

  public function add($paramsArray)
  {
    $name = $paramsArray['name'];
    return $this->insert(
      "INSERT INTO status (name) VALUES (?)",
      ["s", $name]
    );
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];
    $name = $paramsArray['name'];
    return $this->update(
      "UPDATE status SET name = ? WHERE id_status = ?",
      ["si", $name, $id]
    );
  }
}