<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class CommentModel extends Database implements IModel
{
  public function getAll($limit)
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
        ORDER BY id_comment ASC LIMIT ?
    SQL;
    return $this->select($query, ["i", $limit]);
  }

  public function getOne($id)
  {
    return $this->select("SELECT * FROM comment WHERE id_comment = ?", ["i", $id]);
  }

  public function remove($id)
  {
    return $this->delete("DELETE FROM comment WHERE id_comment = ?", ["i", $id]);
  }

  public function add($paramsArray)
  {
    $content = $paramsArray['content'];
    return $this->insert(
      "INSERT INTO comment (content, created_at) VALUES (?, ?)",
      ["ss", $content, current_time()]
    );
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];
    $content = $paramsArray['content'];
    return $this->update(
      "UPDATE comment SET content = ? WHERE id_comment = ?",
      ["si", $content, $id]
    );
  }
}