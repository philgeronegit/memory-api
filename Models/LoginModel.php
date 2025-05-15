<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class LoginModel extends Database
{
  public function add($paramsArray)
  {
    $username = $paramsArray['username'];
    $password = $paramsArray['password'];

    $query = <<<SQL
      SELECT
          u.id_user,
          u.username,
          u.email,
          u.avatar_url,
          u.created_at,
          u.id_role,
          r.name as role_name,
          r.role as role_value
      FROM
          user u
      JOIN role r ON r.id_role = u.id_role
      WHERE u.username = ? AND u.password = ?

    SQL;

    return $this->selectOne($query, ["ss", $username, $password]);
  }
}