<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class CommentsModel extends Database
{
  public function getOne($id)
  {
    $query = <<<SQL
    SELECT
        c.id_comment,
        c.content,
        c.created_at,
        c.updated_at,
        users.username,
        users.email
    FROM
        comment AS c
            INNER JOIN
        note ON note.id_item = c.id_item
            INNER JOIN
        users ON users.id_developer = note.id_developer
    WHERE
        c.id_item = ?
    SQL;
    return $this->select(
      $query,
      ["i", $id]
    );
  }
}
