<?php
require_once PROJECT_ROOT_PATH . "/Models/Database.php";
require_once PROJECT_ROOT_PATH . "/Models/IModel.php";

class CommentModel extends Database implements IModel
{
  private $baseQuery;

  public function __construct()
  {
    parent::__construct();

    $this->baseQuery = <<<SQL
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
          users ON users.id_users = note.id_users

      SQL;
  }

  public function getAll($args)
  {
    $limit = $args['limit'];
    if (array_key_exists('id', $args)) {
      $id = $args['id'];
      $query = $this->baseQuery . <<<SQL
      WHERE
          c.id_item = ?
      ORDER BY id_comment ASC LIMIT ?
      SQL;

      return $this->select($query, ["ii", $id, $limit]);
    }

    $query = $this->baseQuery . <<<SQL
    ORDER BY id_comment ASC LIMIT ?
    SQL;

    return $this->select($query, ["i", $limit]);
  }

  public function getOne($id)
  {
    $query = $this->baseQuery . " WHERE id_comment = ?";

    return $this->selectOne($query, ["i", $id]);
  }

  public function remove($id)
  {
    return $this->delete("DELETE FROM comment WHERE id_comment = ?", ["i", $id]);
  }

  public function add($paramsArray)
  {
    $content = $paramsArray['content'];
    $id_developer = $paramsArray['id_developer'];
    $id_item = $paramsArray['id_item'];
    $now = date('Y-m-d H:i:s');
    $id = $this->insert(
      "INSERT INTO comment (content, created_at,id_developer, id_item) VALUES (?, ?, ?, ?)",
      ["ssii", $content, $now, $id_developer, $id_item]
    );

    $query = $this->baseQuery . <<<SQL
    WHERE id_comment = ?
    SQL;
    return $this->selectOne($query, ["i", $id]);
  }

  public function modify($paramsArray)
  {
    $id = $paramsArray['id'];
    $content = $paramsArray['content'];
    $now = date('Y-m-d H:i:s');
    return $this->update(
      "UPDATE comment SET content = ?, updated_at = ? WHERE id_comment = ?",
      ["ssi", $content, $now, $id]
    );
  }
}