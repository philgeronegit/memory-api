<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class DeveloperModel extends Database implements IModel
{
  public function __construct()
  {
    parent::__construct();

    $this->baseQuery = <<<SQL
      SELECT
          user.id_user,
          user.username,
          user.email,
          user.avatar_url,
          user.created_at,
          user.is_admin,
          user.id_role,
          role.name as role_name,
          role.role as role_value
      FROM
          user
      JOIN
          developer ON user.id_user = developer.id_user
      JOIN
          role ON user.id_role = role.id_role

      SQL;
  }

  public function getAll($args)
  {
    $limit = $args['limit'];
    $query = $this->baseQuery . <<<SQL
    ORDER BY username ASC
    LIMIT ?
    SQL;

    return $this->select($query, ["i", $limit]);
  }

  public function getOne($id, $args = null)
  {
    $query = $this->baseQuery . " WHERE user.id_user = ?";

    return $this->selectOne($query, ["i", $id]);
  }

  public function remove($id)
  {
    return $this->delete("DELETE FROM user WHERE id_user = ?", ["i", $id]);
  }

  public function add($paramsArray)
  {
    $username = $paramsArray['username'];
    $email = $paramsArray['email'];
    $avatar_url = $paramsArray['avatar_url'];
    $id_role = $paramsArray['role_id'];
    $is_admin = $paramsArray['is_admin'];
    $now = date('Y-m-d H:i:s');
    $id = $this->insert(
      "INSERT INTO user (username, email, avatar_url, id_role, is_admin, created_at) " .
      "VALUES (?, ?, ?, ?, ?, ?)",
      ["sssiis", $username, $email, $avatar_url, $id_role, $is_admin, $now]
    );
    $this->insert(
      "INSERT INTO developer (id_user) VALUES (?)",
      ["i", $id]
    );

    $query = $this->baseQuery . <<<SQL
    WHERE user.id_user = ?
    SQL;
    return $this->selectOne($query, ["i", $id]);
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];

    $query = $this->baseQuery . <<<SQL
    WHERE user.id_user = ?
    SQL;
    $user = $this->selectOne($query, ["i", $id]);

    $username = $paramsArray['username'] ?? $user->username;
    $email = $paramsArray['email'] ?? $user->email;
    $avatar_url = $paramsArray['avatar_url'] ?? $user->avatar_url;
    $id_role = $paramsArray['id_role'] ?? $user->id_role;
    $is_admin = $paramsArray['is_admin'] ?? $user->is_admin;

    $this->update(
      "UPDATE user SET username = ?, email = ?, avatar_url = ?, id_role = ?, is_admin = ? " .
      "WHERE user.id_user = ?",
      ["sssiii", $username, $email, $avatar_url, $id_role, $is_admin, $id]
    );

    return $this->selectOne($query, ["i", $id]);
  }
}