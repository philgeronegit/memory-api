<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class DeveloperModel extends Database implements IModel
{
  private $baseQuery;

  public function __construct()
  {
    parent::__construct();

    $this->baseQuery = <<<SQL
      SELECT
          developer.id_developer,
          users.username,
          users.email,
          users.avatar_url,
          users.created_at,
          users.is_admin,
          role.name AS role
      FROM
          users
      JOIN
          developer ON users.id_developer = developer.id_developer
      JOIN
          role ON users.id_role = role.id_role

      SQL;
  }

  public function getAll(...$params)
  {
    $limit = $params[0];
    $query = $this->baseQuery . <<<SQL
    ORDER BY username ASC
    LIMIT ?
    SQL;

    return $this->select($query, ["i", $limit]);
  }

  public function getOne($id)
  {
    $query = $this->baseQuery . " WHERE developer.id_developer = ?";

    return $this->selectOne($query, ["i", $id]);
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