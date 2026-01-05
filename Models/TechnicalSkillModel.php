<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class TechnicalSkillModel extends Database implements IModel
{
  public function getAll($args)
  {
    $limit = $args['limit'];
    if (array_key_exists('id', $args)) {
      $id = $args['id'];
      $query = <<<SQL
      SELECT
          ts.id_technical_skill, name, id_user, year_experience
      FROM
          technical_skill ts
              JOIN
          skills ON skills.id_technical_skill = ts.id_technical_skill
      WHERE
          id_user = ?
      ORDER BY ts.name ASC LIMIT ?
      SQL;

      return $this->select($query, ["ii", $id, $limit]);
    }

    return $this->select("SELECT * FROM technical_skill ORDER BY name ASC LIMIT ?", ["i", $limit]);
  }

  public function getOne($id, $args = null)
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
    return $this->insert(
      "INSERT INTO technical_skill (name) VALUES (?)",
      ["s", $name]
    );
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];
    $name = $paramsArray['name'];
    return $this->update(
      "UPDATE technical_skill SET name = ? WHERE id_technical_skill = ?",
      ["si", $name, $id]
    );
  }

  public function addToTechnicalSkill($paramsArray)
  {
    $user_id = $paramsArray['user_id'];
    $technical_skill_id = $paramsArray['technical_skill_id'];
    $year_experience = $paramsArray['year_experience'];

    $this->insert(
      "INSERT INTO skills (id_user, id_technical_skill, year_experience) VALUES (?, ?, ?)",
      ["iii", $user_id, $technical_skill_id, $year_experience]
    );

    return true;
  }

  public function updateTechnicalSkill($paramsArray)
  {
    $user_id = $paramsArray['user_id'];
    $technical_skill_id = $paramsArray['technical_skill_id'];
    $year_experience = $paramsArray['year_experience'];

    $this->update(
      "UPDATE skills SET year_experience = ? WHERE id_user = ? AND id_technical_skill = ?",
      ["iii", $year_experience, $user_id, $technical_skill_id]
    );

    return true;
  }

  public function removeFromTechnicalSkill($paramsArray)
  {
    $user_id = $paramsArray['user_id'];
    $technical_skill_id = $paramsArray['technical_skill_id'];

    $this->delete(
      "DELETE FROM skills WHERE id_user = ? AND id_technical_skill = ?",
      ["ii", $user_id, $technical_skill_id]
    );

    return true;
  }
}