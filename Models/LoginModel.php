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
    SELECT id_users, username, password
    FROM users
    WHERE username = ? AND password = ?
    SQL;

    return $this->selectOne($query, ["ss", $username, $password]);
  }
}