<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class TechnicalSkillModel extends Database implements IModel
{
  public function getAll($args)
  {
    $limit = $args['limit'];
    $id = $params[1];
    if ($id != null) {
      $query = <<<SQL
      SELECT * FROM technical_skill
      WHERE id_technical_skill = ?
      ORDER BY name ASC LIMIT ?
      SQL;

      return $this->select($query, ["ii", $id, $limit]);
    }

    return $this->select("SELECT * FROM technical_skill ORDER BY name ASC LIMIT ?", ["i", $limit]);
  }

  public function getOne($id)
  {
    return $this->selectOne("SELECT * FROM technical_skill WHERE id_technical_skill = ?", ["i", $id]);
  }

  public function remove($id)
  {
    return $this->delete("DELETE FROM technical_skill WHERE id_technical_skill = ?", ["i", $id]);
  }

  public function add($paramsArray)
  {
    $name = $paramsArray['name'];
    $year = $paramsArray['year'];
    return $this->insert(
      "INSERT INTO technical_skill (name, year_experience) VALUES (?, ?)",
      ["si", $name, $year]
    );
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];
    $name = $paramsArray['name'];
    $year = $paramsArray['year'];
    return $this->update(
      "UPDATE technical_skill SET name = ?, year_experience = ? WHERE id_technical_skill = ?",
      ["sii", $name, $year, $id]
    );
  }
}